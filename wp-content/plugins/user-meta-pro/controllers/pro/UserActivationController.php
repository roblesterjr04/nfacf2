<?php
namespace UserMeta;

class UserActivationController
{

    function __construct()
    {
        add_filter('user_row_actions', array(
            $this,
            'userRowActions'
        ), 10, 2);
        add_action('load-users.php', array(
            $this,
            'loadUsersPage'
        ));
        add_action('admin_footer-users.php', array(
            $this,
            'userAdminCustomization'
        ));
        add_action('pre_user_query', array(
            $this,
            'userListingByStatus'
        ), 50);
        
        add_action('wp_authenticate_user', array(
            $this,
            'authenticateUser'
        ));
        add_action('user_register', array(
            $this,
            'userRegister'
        ));
        add_action('admin_notices', array(
            $this,
            'adminNotice'
        ));
        add_action('admin_notices', array(
            $this,
            'adminApproval'
        ));
    }

    /**
     * Addding acitvate/deactivate link under indivisual user.
     */
    function userRowActions($actions, $user)
    {
        global $userMeta;
        
        if ((get_current_user_id() != $user->ID) && current_user_can('edit_user', $user->ID)) {
            
            $status = get_user_meta($user->ID, $userMeta->prefixLong . 'user_status', true);
            switch ($status) {
                case 'inactive':
                    $url = $userMeta->userActivationUrl('activate', $user->ID);
                    $actions['um_user_activate'] = "<a href='{$url}'>" . __('Activate', $userMeta->name) . "</a>";
                    break;
                
                case 'pending':
                case 'admin_approved':
                    $url = $userMeta->userActivationUrl('activate', $user->ID);
                    $actions['um_user_activate'] = "<a href='{$url}'>" . __('Activate', $userMeta->name) . "</a>";
                    
                    $url = $userMeta->userActivationUrl('deactivate', $user->ID);
                    $actions['um_user_deactivate'] = "<a href='{$url}'>" . __('Deactivate', $userMeta->name) . "</a>";
                    break;
                
                default:
                    $url = $userMeta->userActivationUrl('deactivate', $user->ID);
                    $actions['um_user_deactivate'] = "<a href='{$url}'>" . __('Deactivate', $userMeta->name) . "</a>";
                    break;
            }
        }
        
        return $actions;
    }

    /**
     * Running user activation/deactivation while user admin page loaded, based on get paramater.
     */
    function loadUsersPage()
    {
        global $userMeta;
        
        $user = @$_REQUEST['user'];
        $users = @$_REQUEST['users'];
        $action = @$_REQUEST['action'];
        
        // Only handle activate/deactivate action
        if (! in_array($action, array(
            'activate',
            'deactivate'
        )))
            return;
            
            // success message shown by admin_notices.
        if (isset($_GET['success']))
            return;
        
        $count = 0;
        
        // Bulk activation/deactivation
        if (is_array($users) && $users) {
            check_admin_referer('bulk-users');
            foreach ($users as $userID) {
                if ($userID == 1)
                    continue;
                if ($this->userAcitvateDeactivate($userID, $action))
                    $count ++;
            }
            
            // Single activation/deactivation. If nonoce field is set, do activation/deactivation. or attaempt to confirmation screen.
        } elseif ($user) {
            if (isset($_GET['_wpnonce'])) {
                check_admin_referer('um_activation');
                if ($this->userAcitvateDeactivate($user, $action))
                    $count ++;
            } else
                return;
        }
        
        $this->_redirect($action, $count);
    }

    /**
     * Adding item to action dropdown for bulk update.
     * Showing number of active and inactive users.
     */
    function userAdminCustomization()
    {
        global $wpdb, $userMeta;
        
        $countTotal = (int) $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->users");
        
        $metaKey = $userMeta->prefixLong . 'user_status';
        $countPending = $wpdb->get_var("SELECT COUNT(distinct(user_id)) FROM $wpdb->usermeta where meta_key='$metaKey' and (meta_value='pending' or meta_value='admin_approved')");
        $countInactive = $wpdb->get_var("SELECT COUNT(distinct(user_id)) FROM $wpdb->usermeta where meta_key='$metaKey' and meta_value='inactive'");
        
        // $countPending = $countPending + $countPendingVerification;
        $countActive = $countTotal - ($countInactive + $countPending);
        
        $labels = array(
            'active' => __('Active', $userMeta->name),
            'inactive' => __('Inactive', $userMeta->name),
            'pending' => __('Pending', $userMeta->name),
            'pending_approval' => __('Pending Approval', $userMeta->name),
            'pending_verification' => __('Pending Email Verification', $userMeta->name)
        );
        
        if (! empty($_REQUEST['um_status'])) {
            if (array_key_exists($_REQUEST['um_status'], $labels))
                $labels[$_REQUEST['um_status']] = "<strong>{$labels[$_REQUEST['um_status']]}</strong>";
        }
        
        $html = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $html .= "<li><a href=\"" . admin_url('users.php?um_status=active') . "\">{$labels['active']} ({$countActive})</a></li>";
        $html .= "<li> | <a href=\"" . admin_url('users.php?um_status=inactive') . "\">{$labels['inactive']} ({$countInactive})</a></li>";
        
        if ($countPending)
            $html .= "<li> | <a href=\"" . admin_url('users.php?um_status=pending') . "\">{$labels['pending']} ({$countPending})</a></li>";
        
        if ($countPending) {
            $registration = $userMeta->getSettings('registration');
            $user_activation = $registration['user_activation'];
            if ($user_activation == 'both_email_admin') {
                $countApproval = $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->users INNER JOIN $wpdb->usermeta ON ($wpdb->users.ID = $wpdb->usermeta.user_id)
    INNER JOIN $wpdb->usermeta AS mt1 ON ($wpdb->users.ID = mt1.user_id) WHERE 1=1 AND ( ($wpdb->usermeta.meta_key = '$metaKey' AND $wpdb->usermeta.meta_value = 'pending')
    AND  (mt1.meta_key = 'user_meta_email_verification_code' AND mt1.meta_value = '') )");
                
                $countVerification = $wpdb->get_var("SELECT COUNT(distinct(user_id)) FROM $wpdb->usermeta where meta_key='$metaKey' and meta_value='admin_approved'");
                
                if ($countApproval)
                    $html .= "<li> | <a href=\"" . admin_url('users.php?um_status=pending_approval') . "\">{$labels['pending_approval']} ({$countApproval})</a></li>";
                
                if ($countVerification)
                    $html .= "<li> | <a href=\"" . admin_url('users.php?um_status=pending_email_verification') . "\">{$labels['pending_verification']} ({$countVerification})</a></li>";
            }
        }
        
        ?>
<script type="text/javascript">
        jQuery(document).ready(function(){
            jQuery('.actions select[name^="action"]').append('<option value="activate"><?php _e( 'Activate', $userMeta->name ); ?></option><option value="deactivate"><?php _e( 'Deactivate', $userMeta->name ); ?></option>');
            jQuery('.subsubsub').append('<?php echo $html; ?>');
        });
        </script>
<?php
    }

    function userListingByStatus($query)
    {
        global $userMeta, $wpdb, $pagenow;
        
        if ('users.php' != $pagenow)
            return;
        if (empty($_REQUEST['um_status']))
            return;
        
        $metaKey = $userMeta->prefixLong . 'user_status';
        $verificationMetaKey = $userMeta->prefixLong . 'email_verification_code';
        $status = $_REQUEST['um_status'];
        switch ($status) {
            case 'active':
                
                /**
                 * Update $metaKey value if not exists
                 */
                
                $pendingStatus = $wpdb->get_results("SELECT * FROM $wpdb->users LEFT JOIN $wpdb->usermeta ON ($wpdb->users.ID = $wpdb->usermeta.user_id AND $wpdb->usermeta.meta_key = '$metaKey') WHERE $wpdb->usermeta.user_id IS NULL");
                if (! empty($pendingStatus)) {
                    foreach ($pendingStatus as $user)
                        update_user_meta($user->ID, $metaKey, 'active');
                }
            
            case 'inactive':
                $query->query_from = "FROM $wpdb->users INNER JOIN $wpdb->usermeta ON ($wpdb->users.ID = $wpdb->usermeta.user_id)";
                $query->query_where = $query->query_where . $wpdb->prepare(" AND ($wpdb->usermeta.meta_key = '$metaKey' AND $wpdb->usermeta.meta_value = %s)", $status);
            
            case 'pending':
            case 'pending_email_verification':
                $query->query_from = "FROM $wpdb->users INNER JOIN $wpdb->usermeta ON ($wpdb->users.ID = $wpdb->usermeta.user_id)";
                $query->query_where = $query->query_where . $wpdb->prepare(" AND ($wpdb->usermeta.meta_key = '$metaKey' AND ($wpdb->usermeta.meta_value='admin_approved' or $wpdb->usermeta.meta_value = %s))", $status);
                break;
            
            case 'pending_approval':
                $query->query_from = "FROM $wpdb->users INNER JOIN $wpdb->usermeta ON ($wpdb->users.ID = $wpdb->usermeta.user_id) INNER JOIN $wpdb->usermeta AS mt1 ON ($wpdb->users.ID = mt1.user_id)";
                $query->query_where = $query->query_where . " AND ($wpdb->usermeta.meta_key = '$metaKey' AND ($wpdb->usermeta.meta_value = 'pending') AND (mt1.meta_key = '$verificationMetaKey' AND mt1.meta_value = ''))";
                break;
        }
    }

    /**
     * Redirection for showing success message after activate/deactivate.
     */
    function _redirect($action, $count)
    {
        $url = admin_url('users.php');
        $success = $count ? 1 : 0;
        $url = add_query_arg(array(
            'action' => $action,
            'success' => $success,
            'count' => $count ? $count : 0
        ), $url);
        wp_redirect($url);
        exit();
    }

    /**
     * Authenticate a user while login.
     */
    function authenticateUser($userdata)
    {
        global $userMeta;
        
        if (is_wp_error($userdata))
            return $userdata;
        
        if ($userdata->ID == 1)
            return $userdata;
        
        $status = get_user_meta($userdata->ID, $userMeta->prefixLong . 'user_status', true);
        
        if ($status == 'inactive')
            $userdata = new \WP_Error('user_activation_error', $userMeta->getMsg('account_inactive'));
        elseif ($status == 'pending')
            $userdata = new \WP_Error('user_activation_error', $userMeta->getMsg('account_pending'));
        elseif ($status == 'admin_approved')
            $userdata = new \WP_Error('user_activation_error', $userMeta->getMsg('verify_email'));
        
        return $userdata;
    }

    /**
     * Set a user status as inactive while registration.
     * And send email to admin for user approval.
     * Valid status: active | inactive | pending | admin_approved
     */
    function userRegister($userID)
    {
        global $userMeta;
        
        $registration = $userMeta->getSettings('registration');
        $user_activation = $registration['user_activation'];
        
        $status = 'pending';
        if (($user_activation == 'auto_active'))
            $status = 'active';
        elseif (apply_filters('user_meta_user_auto_active_for_add_users_cap', false) && current_user_can('add_users'))
            $status = 'active';
        
        update_user_meta($userID, $userMeta->prefixLong . 'user_status', $status);
    }

    function adminApproval()
    {
        global $userMeta, $pagenow;
        
        if ($pagenow != 'users.php')
            return;
        
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
        $userID = isset($_REQUEST['user']) ? (int) $_REQUEST['user'] : 0;
        
        if (empty($action) || empty($userID))
            return;
        
        $errors = new \WP_Error();
        if (! $userMeta->isAdmin())
            $errors->add('not_admin', __('Not an admin account!', $userMeta->name));
        
        $user = get_user_by('id', $userID);
        if (empty($user->ID))
            $errors->add('user_not_exists', __('User does not exists.', $userMeta->name));
        
        if ($errors->get_error_code()) {
            echo $userMeta->showError($errors);
            return;
        }
        
        $profileUrl = add_query_arg(array(
            'user_id' => $userID
        ), admin_url('user-edit.php'));
        $profileUrl = "<a href=\"$profileUrl\">$user->user_login</a>";
        
        switch ($action) {
            
            case 'admin_approval':
                
                if ($this->isUserInactive($userID)) {
                    $msg = sprintf(__('User %s registered on your site.', $userMeta->name), $profileUrl);
                    $msg .= $this->_button('admin_approval_confirm', __('Approve Now', $userMeta->name), $userID);
                    $msg .= ' ' . __('or', $userMeta->name) . ' ';
                    $msg .= $this->_button('deactivate', __('Deny', $userMeta->name), $userID);
                } else
                    $msg = sprintf(__('User %s is already approved and active.', $userMeta->name), $profileUrl);
                break;
            
            case 'admin_approval_confirm':
                
                if (isset($_GET['_wpnonce'])) {
                    check_admin_referer('um_activation');
                    
                    $registrationSettings = $userMeta->getSettings('registration');
                    $activation = $registrationSettings['user_activation'];
                    $status = 'active';
                    if ($activation == 'both_email_admin') {
                        if (get_user_meta($userID, $userMeta->prefixLong . 'email_verification_code', true)) {
                            $status = 'admin_approved';
                            $msg = sprintf(__('User %s has been approved but still needs email verification.', $userMeta->name), $profileUrl);
                            $msg .= $this->_button('activate', __('Activate', $userMeta->name), $userID) . ' ' . __('now without verifying email.', $userMeta->name);
                        } else {
                            $msg = sprintf(__('User %s is has been approved.', $userMeta->name), $profileUrl);
                        }
                    } else {
                        $msg = sprintf(__('User %s is has been approved and active.', $userMeta->name), $profileUrl);
                    }
                    
                    update_user_meta($userID, $userMeta->prefixLong . 'user_status', $status);
                    do_action('user_meta_user_approved', $userID, $status);
                }
                break;
        }
        
        if (! empty($msg))
            echo $userMeta->showMessage($msg);
    }

    private function _button($action, $text, $userID)
    {
        global $userMeta;
        return " <a class=\"button\" href=\"" . $userMeta->userActivationUrl($action, $userID) . "\">" . $text . "</a>";
    }

    /**
     * Let admin activate new user through email link.
     * And show success message after activation/deactivation.
     */
    function adminNotice()
    {
        global $userMeta, $pagenow;
        
        if ($pagenow == 'users.php') {
            $action = @$_GET['action'];
            $userID = @$_GET['user'];
            if (in_array($action, array(
                'activate',
                'deactivate'
            )) && ! isset($_GET['_wpnonce'])) {
                if ($userID) {
                    $user = get_user_by('id', $userID);
                    $profileUrl = add_query_arg(array(
                        'user_id' => $userID
                    ), admin_url('user-edit.php'));
                    $profileUrl = "<a href=\"$profileUrl\">$user->user_login</a>";
                    
                    if ($this->isUserInactive($userID)) {
                        $html = "<div class=\"updated\"><p>";
                        $html .= sprintf(__('New user %s registered on your site.', $userMeta->name), $profileUrl);
                        $html .= " <a class=\"button\" href=\"" . $userMeta->userActivationUrl($action, $userID) . "\">" . __('Activate', $userMeta->name) . "</a>";
                        $html .= "</p></div>";
                    } else {
                        $html = "<div class=\"updated\"><p>";
                        $html .= sprintf(__('User %s is already activated.', $userMeta->name), $profileUrl);
                        $html .= "<a class=\"button\" href=\"" . $userMeta->userActivationUrl('deactivate', $userID) . "\">" . __('Deactivate', $userMeta->name) . "</a>";
                        $html .= "</p></div>";
                    }
                } elseif (isset($_GET['success'])) {
                    if ($_GET['success']) {
                        if ($action == 'activate')
                            $status = __('activated', $userMeta->name);
                        elseif ($action == 'deactivate')
                            $status = __('deactivated', $userMeta->name);
                        
                        if (@$_GET['count'] > 1)
                            $status = sprintf(__('%1$s users have been %2$s', $userMeta->name), $_GET['count'], $status);
                        else
                            $status = sprintf(__('User has been %s', $userMeta->name), $status);
                        
                        $html = "<div class=\"updated\"><p>$status</p></div>";
                    } else {
                        $status = __('No user updated', $userMeta->name);
                        $html = "<div class=\"error\"><p>$status</p></div>";
                    }
                }
                
                if (isset($html))
                    echo $html;
            }
        }
    }

    /**
     * Do user activation/deactivation.
     */
    function userAcitvateDeactivate($userID, $action)
    {
        global $userMeta;
        
        if ($userID == 1)
            return false;
        
        switch ($action) {
            case 'activate':
                update_user_meta($userID, $userMeta->prefixLong . 'user_status', 'active');
                do_action('user_meta_user_activate', $userID);
                return true;
                break;
            
            case 'deactivate':
                update_user_meta($userID, $userMeta->prefixLong . 'user_status', 'inactive');
                do_action('user_meta_user_deactivate', $userID);
                return true;
                break;
        }
    }

    /**
     * check if user is inactive.
     * Inactive cases: inactive | pending | admin_approved(email not yet verified)
     * Admin Approved but not email verified: user_status: admin_approved
     * Email verified but not admin approved: user_status: pending && email_verification_code is not null
     */
    function isUserInactive($userID)
    {
        global $userMeta;
        $status = get_user_meta($userID, $userMeta->prefixLong . 'user_status', true);
        if (in_array($status, array(
            'inactive',
            'pending',
            'admin_approved'
        )))
            return true;
        return false;
    }
}
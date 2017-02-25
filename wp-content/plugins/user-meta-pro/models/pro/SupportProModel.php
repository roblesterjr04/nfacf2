<?php
namespace UserMeta;

class SupportProModel
{

    /**
     * Not in use since 1.2.1
     * Replaced with Login::loginForm()
     */
    function generateLoginForm($formName)
    {
        global $userMeta;
        
        if (is_user_logged_in())
            return $this->loginResponse();
        
        $output = null;
        
        if ($userMeta->isHookEnable('login_form_login')) {
            ob_start();
            do_action('login_form_login');
            $output .= ob_get_contents();
            ob_end_clean();
        }
        
        $loginSettings = $userMeta->getSettings('login');
        $methodName = 'Login';
        
        $config = array();
        
        if (! empty($formName)) {
            $formBuilder = new FormGenerate($formName, 'login');
            
            if (! $formBuilder->isFound())
                return $userMeta->ShowError(sprintf(__('Form "%s" is not found.', $userMeta->name), $formName));
            
            $form = $formBuilder->getForm();
            
            /*
             * $form = $userMeta->getFormData( $formName );
             * if ( is_wp_error( $form ) )
             * return $userMeta->ShowError( $form );
             */
            
            $form['form_class'] = 'um_login_form ' . ! empty($form['form_class']) ? $form['form_class'] : null;
            if (empty($form['disable_ajax']))
                $form['onsubmit'] = "umLogin(this);";
            
            $output .= $userMeta->renderPro('generateForm', array(
                'form' => $form,
                'actionType' => 'login',
                'methodName' => $methodName
            ));
        } else {
            $title = $userMeta->loginByArray();
            if (isset($userMeta->um_post_method_status->$methodName))
                $output .= $userMeta->um_post_method_status->$methodName;
            
            $config = apply_filters('user_meta_default_login_form', $config);
            
            $output .= $userMeta->renderPro('loginForm', array(
                'config' => $config,
                'loginTitle' => @$title[$loginSettings['login_by']],
                'disableAjax' => ! empty($loginSettings['disable_ajax']) ? true : false,
                'methodName' => $methodName
            ), 'login');
        }
        
        if (empty($loginSettings['disable_lostpassword'])) {
            $output .= $this->lostPasswordForm();
        }
        
        if (empty($loginSettings['disable_registration_link'])) {
            $output .= $userMeta->renderPro('registrationLink', array(
                'config' => $config
            ), 'login');
        }
        
        return $output;
    }

    /**
     * Not in use since 1.2.1
     *
     * Handle resetPassword request, key validation, password reset
     */
    function lostPasswordForm($config = array())
    {
        global $userMeta;
        
        $methodName = "Lostpassword";
        
        $html = null;
        
        if ($userMeta->isHookEnable('login_form_lostpassword')) {
            ob_start();
            do_action('login_form_lostpassword');
            $html .= ob_get_contents();
            ob_end_clean();
        }
        
        // if( is_user_logged_in() ) return;
        
        if (empty($config))
            $config = $userMeta->getExecutionPageConfig('lostpassword');
        
        $login = $userMeta->getSettings('login');
        
        if (! empty($login['disable_lostpassword']))
            return $userMeta->showError(__('Password reset is currently not allowed.', $userMeta->name));
            
            // $html = null;
            // if( !@$_REQUEST['is_ajax'] && @$_REQUEST['method_name'] == 'lostpassword' )
            // $html .= $userMeta->ajaxLostpassword();
        
        $html .= $userMeta->renderPro('lostPasswordForm', array(
            'config' => $config,
            'disableAjax' => ! empty($login['disable_ajax']) ? true : false,
            'methodName' => $methodName
        ), 'login');
        
        return $html;
    }

    /**
     * Not in use since 1.2.1
     *
     * LoggedIn Profile.
     *
     * @uses generateLoginForm()
     * @since 1.1.2
     * @param
     *            string : shortcode, widget, template.
     * @return string containing the form
     */
    function loginResponse($user = null)
    {
        global $userMeta;
        
        if (empty($user))
            $user = wp_get_current_user();
        
        $role = $userMeta->getUserRole($user->ID);
        $login = $userMeta->getSettings('login');
        
        return $userMeta->convertUserContent($user, @$login['loggedin_profile'][$role]);
    }

    /**
     * Not in use since 1.2.1
     */
    function resetPassword($config = array())
    {
        global $userMeta;
        
        if (empty($config))
            $config = $userMeta->getExecutionPageConfig('resetpass');
        
        $html = null;
        
        if ($userMeta->isHookEnable('login_form_resetpass')) {
            ob_start();
            do_action('login_form_resetpass');
            $html .= ob_get_contents();
            ob_end_clean();
        }
        
        if ($userMeta->isHookEnable('login_form_rp')) {
            ob_start();
            do_action('login_form_rp');
            $html .= ob_get_contents();
            ob_end_clean();
        }
        
        // $user = $userMeta->check_password_reset_key( @$_GET['key'], rawurldecode( @$_GET['login'] ) );
        $user = check_password_reset_key(@$_GET['key'], rawurldecode(@$_GET['login']));
        
        $errors = new \WP_Error();
        
        if (! is_wp_error($user)) {
            
            if (isset($_POST['pass1']) && $_POST['pass1'] != $_POST['pass2'])
                $errors->add('password_reset_mismatch', $userMeta->getMsg('password_reset_mismatch'));
            
            if ($userMeta->isHookEnable('validate_password_reset'))
                do_action('validate_password_reset', $errors, $user);
            
            if ((! $errors->get_error_code()) && isset($_POST['pass1']) && ! empty($_POST['pass1'])) {
                $userMeta->reset_password($user, $_POST['pass1']);
                do_action('user_meta_after_reset_password', $user);
                $html .= $userMeta->showMessage($userMeta->getMsg('password_reseted'));
                
                $redirect = ! empty($config['redirect']) ? $config['redirect'] : null;
                $redirect = apply_filters('user_meta_reset_password_redirect', $redirect, $user);
                
                if (! empty($redirect))
                    $html .= $userMeta->jsRedirect($redirect, 5);
                
                return $html;
            }
        } else {
            if ($user->get_error_code() == 'invalid_key')
                return $userMeta->showError($userMeta->getMsg('invalid_key'), false);
            elseif ($user->get_error_code() == 'expired_key')
                return $userMeta->showError($userMeta->getMsg('expired_key'), false);
            else
                return $userMeta->showError($user->get_error_message(), false);
        }
        
        return $userMeta->renderPro('resetPasswordForm', array(
            'config' => $config,
            'user' => $user,
            'errors' => $errors
        ), 'login');
    }

    function emailVerification($config = array())
    {
        global $userMeta;
        
        if (empty($config))
            $config = $userMeta->getExecutionPageConfig('email_verification');
        
        $email = isset($_REQUEST['email']) ? rawurldecode($_REQUEST['email']) : '';
        $key = isset($_REQUEST['key']) ? rawurldecode($_REQUEST['key']) : '';
        
        if (! $email || ! $key)
            return $userMeta->showError($userMeta->getMsg('invalid_parameter'));
        
        $user = get_user_by('email', $email);
        if (! $user)
            return $userMeta->showError($userMeta->getMsg('email_not_found'));
        
        $status = get_user_meta($user->ID, $userMeta->prefixLong . 'user_status', true);
        
        if ($status == 'active')
            return $userMeta->showMessage($userMeta->getMsg('user_already_activated'));
        
        $preSavedKey = get_user_meta($user->ID, $userMeta->prefixLong . 'email_verification_code', true);
        
        if (empty($preSavedKey) && $status == 'pending')
            return $userMeta->showMessage($userMeta->getMsg('email_verified_pending_admin'), 'info');
        
        $html = null;
        if ($preSavedKey == $key) {
            $registration = $userMeta->getSettings('registration');
            $user_activation = $registration['user_activation'];
            
            if ($user_activation == 'email_verification')
                $status = 'active';
            
            update_user_meta($user->ID, $userMeta->prefixLong . 'user_status', $status);
            update_user_meta($user->ID, $userMeta->prefixLong . 'email_verification_code', '');
            do_action('user_meta_email_verified', $user->ID);
            
            $html .= $userMeta->showMessage($userMeta->getMsg($status == 'active' ? 'email_verified' : 'email_verified_pending_admin', esc_url(wp_login_url())));
            if (! empty($config['redirect']))
                $html .= $userMeta->jsRedirect($config['redirect'], 5);
            return $html;
        } else
            return $userMeta->showError($userMeta->getMsg('invalid_key'));
    }

    /**
     *
     * @param type $user            
     * @param type $modifiedOldData:
     *            This variable is set on umEmailNotificationController->userModifiedOldData
     * @return string
     */
    function userModifiedData($user, &$modifiedOldData)
    {
        global $userMeta;
        
        if (! $modifiedOldData || ! is_array($modifiedOldData))
            return;
        
        /**
         * Use filter: user_meta_user_modified_old_data_tracker to filter tracker
         */
        $modifiedOldData = apply_filters('user_meta_user_modified_old_data', $modifiedOldData, $user);
        
        $fieldsLabel = array_combine(array_keys($modifiedOldData), array_keys($modifiedOldData));
        $fieldsLabel['um_new_label'] = __('Updated data:', $userMeta->name);
        $fieldsLabel['um_old_label'] = __('Previous data:', $userMeta->name);
        
        $fieldsLabel = apply_filters('user_meta_user_modified_data_fields_label', $fieldsLabel, $user, $modifiedOldData);
        
        if (isset($modifiedOldData['user_pass'])) {
            $plainPass = isset($user->password) ? $user->password : '';
            $user->user_pass = apply_filters('user_meta_user_modified_data_plainpass', __('Updated password', $userMeta->name), $plainPass, $user);
        }
        
        $msg = '';
        $msg .= isset($fieldsLabel['um_new_label']) ? $fieldsLabel['um_new_label'] . "\r\n" : '';
        foreach ($modifiedOldData as $key => $val) {
            $msg .= $fieldsLabel[$key] . ': ';
            $msg .= is_array($user->$key) ? trim(implode(', ', $user->$key)) : trim($user->$key);
            $msg .= "\r\n";
        }
        
        $msg .= "\r\n";
        
        $msg .= isset($fieldsLabel['um_old_label']) ? $fieldsLabel['um_old_label'] . "\r\n" : '';
        foreach ($modifiedOldData as $key => $val) {
            if (isset($modifiedOldData['user_pass']))
                continue;
            
            $msg .= $fieldsLabel[$key] . ': ';
            $msg .= is_array($val) ? implode(',', $val) : trim($val);
            $msg .= "\r\n";
        }
        
        return $msg;
    }

    /**
     * Not in use since 1.2.1
     *
     * Do login if user not logged on.
     *
     * @return onSuccess : redirect_url | onFailed : WP_Error or false
     */
    function doLogin($creds = array())
    {
        global $userMeta;
        
        if (is_user_logged_in())
            return false;
        
        $loginSettings = $userMeta->getSettings('login');
        
        if (empty($creds['user_login'])) {
            $user = self::findUserForLogin($loginSettings);
            if (is_wp_error($user))
                return $user;
            $userName = $user->user_login;
        } else
            $userName = $creds['user_login'];
        
        if (empty($creds['user_pass'])) {
            if (isset($_REQUEST['pwd']))
                $userPass = $_REQUEST['pwd'];
            elseif (isset($_REQUEST['user_pass']))
                $userPass = $_REQUEST['user_pass'];
        } else
            $userPass = $creds['user_pass'];
        
        $remember = ! empty($creds['remember']) ? $creds['remember'] : @$_REQUEST['rememberme'];
        
        $user = wp_authenticate($userName, $userPass);
        
        if (is_wp_error($user))
            return $user;
            
            // if Prevent user login for non-member of blog is set.
        if (is_multisite()) {
            global $blog_id;
            if (! empty($loginSettings['blog_member_only'])) {
                $userID = username_exists(sanitize_user($userName, true));
                if ($userID) {
                    if (! is_user_member_of_blog($userID))
                        return new \WP_Error('not_member_of_blog', $userMeta->getMsg('not_member_of_blog'));
                }
            }
        }
        
        $secure_cookie = '';
        
        if (force_ssl_admin())
            $secure_cookie = true;
            
            // If the user wants ssl but the session is not ssl, force a secure cookie.
        if (! force_ssl_admin()) {
            if ($user = get_user_by('login', sanitize_user($userName))) {
                if (get_user_option('use_ssl', $user->ID)) {
                    $secure_cookie = true;
                    force_ssl_admin(true);
                }
            }
        }
        
        // if ( !$secure_cookie && is_ssl() && force_ssl_login() && !force_ssl_admin() && ( 0 !== strpos($redirect_to, 'https') ) && ( 0 === strpos($redirect_to, 'http') ) )
        // $secure_cookie = false;
        
        $secure_cookie = apply_filters('user_meta_login_secure_cookie', $secure_cookie, $user);
        
        $user = wp_signon(array(
            'user_login' => $userName,
            'user_password' => $userPass,
            'remember' => $remember ? true : false
        ), $secure_cookie);
        
        if (is_wp_error($user))
            return $user;
        
        $role = $userMeta->getUserRole($user->ID);
        $redirect_to = $role == 'administrator' ? admin_url() : home_url();
        $redirect_to = $userMeta->getRedirectionUrl($redirect_to, 'login', $role);
        
        if ($userMeta->isHookEnable('login_redirect'))
            $redirect_to = apply_filters('login_redirect', $redirect_to, isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : '', $user);
        
        $user->redirect_to = $redirect_to;
        
        return $user;
    }

    /**
     * Not in use since 1.2.1
     *
     * fined user_login form user_login or user_email
     */
    function findUserForLogin($loginSettings)
    {
        global $userMeta;
        
        $loginBy = @$loginSettings['login_by'];
        
        if (isset($_REQUEST['log']))
            $userLogin = $_REQUEST['log'];
        elseif (isset($_REQUEST['user_login']))
            $userLogin = $_REQUEST['user_login'];
        elseif (isset($_REQUEST['user_email']))
            $userLogin = $_REQUEST['user_email'];
        
        if ($loginBy == 'user_login_or_email') {
            $user = get_user_by('email', $userLogin);
            if ($user === false)
                $user = get_user_by('login', $userLogin);
        } elseif ($loginBy == 'user_email')
            $user = get_user_by('email', $userLogin);
        else
            $user = get_user_by('login', $userLogin);
        
        if ($user === false) {
            $title = $userMeta->loginByArray();
            return new \WP_Error('invalid_login', $userMeta->getMsg('invalid_login', @$title[$loginBy]));
        }
        
        return $user;
    }

    function disableAdminRow($id)
    {
        if (in_array($id, array(
            'heading_0',
            'heading_1',
            'heading_2',
            'heading_3'
        ))) {
            ?>
<script type="text/javascript">
                jQuery(document).ready(function(){
                    id = <?php echo str_replace( 'heading_', '', $id ); ?>;
                    jQuery( "h3:eq(" + id + ")" ).hide();
                });
            </script>
<?php
        } elseif (in_array($id, array(
            'color-picker',
            'pass1'
        ))) {
            ?>
<script type="text/javascript">
                jQuery(document).ready(function(){
                    jQuery( "#<?php echo $id; ?>" ).parents( "table" ).hide();
                });
            </script>
<?php
        } else {
            ?>
<script type="text/javascript">
                jQuery(document).ready(function(){
                    jQuery( "#<?php echo $id; ?>" ).parents( "tr" ).hide();
                });
            </script>
<?php
        }
    }

    function isValidCaptcha()
    {
        global $userMeta;
        
        // Was there a reCAPTCHA response?
        if (empty($_POST["g-recaptcha-response"])) {
            echo self::reloadCaptcha();
            return false;
        }
        
        // If key are not set then no validation
        $general = $userMeta->getSettings('general');
        if (empty($general['recaptcha_public_key']) || empty($general['recaptcha_private_key']))
            return false;
        
        $recaptcha = new \ReCaptcha\ReCaptcha($general['recaptcha_private_key']);
        $resp = $recaptcha->verify($_POST["g-recaptcha-response"], $_SERVER["REMOTE_ADDR"]);
        if ($resp->isSuccess()) {
            return true;
        } else {
            echo self::reloadCaptcha();
            return false;
            // $errors = $resp->getErrorCodes();
            // var_export($errors);
        }
        
        /*
         * Comented since 1.1.8rc1
         *
         * if ( ! class_exists( 'ReCaptcha' ) )
         * require_once( $userMeta->pluginPath . '/helpers/recaptchalib.php');
         *
         * $reCaptcha = new ReCaptcha( $general['recaptcha_private_key'] );
         * $resp = $reCaptcha->verifyResponse(
         * $_SERVER["REMOTE_ADDR"],
         * $_POST["g-recaptcha-response"]
         * );
         *
         * if ( ! empty( $resp ) && $resp->success )
         * return true;
         */
    }

    /**
     * Reload reCaptcha
     *
     * @return string javascript
     */
    function reloadCaptcha()
    {
        return '<script>if(typeof grecaptcha!="undefined"){grecaptcha.reset();}</script>';
    }
    
    // TODO: referer
    /**
     * Get redirection url from settings.
     *
     * @param $redirect_to: get
     *            $redirect_to from filter.
     * @param $action: login,
     *            logout or registration
     * @param $role: role
     *            name
     * @return $redirect_to: url
     */
    function getRedirectionUrl($redirect_to, $action, $role = null)
    {
        global $userMeta;
        
        if (! $role)
            $role = $userMeta->getUserRole(get_current_user_id());
        
        $redirection = $userMeta->getSettings('redirection');
        
        if (! empty($redirection['disable']))
            return $redirect_to;
        
        $redirectionType = @$redirection[$action][$role];
        
        $scheme = is_ssl() ? 'https://' : 'http://';
        
        if ($redirectionType == 'same_url') {
            if (! empty($_REQUEST['_wp_http_referer']))
                $redirect_to = $scheme . esc_attr($_SERVER['HTTP_HOST']) . esc_attr($_REQUEST['_wp_http_referer']);
            elseif (! empty($_SERVER['REQUEST_URI']))
                $redirect_to = $scheme . esc_attr($_SERVER['HTTP_HOST']) . esc_attr($_SERVER['REQUEST_URI']);
        } elseif ($redirectionType == 'referer') {
            if (! empty($_REQUEST['redirect_to']))
                $redirect_to = esc_attr($_REQUEST['redirect_to']);
            elseif (! empty($_REQUEST['pf_http_referer']))
                $redirect_to = esc_attr($_REQUEST['pf_http_referer']);
            elseif (! empty($_REQUEST['_wp_http_referer']))
                $redirect_to = $scheme . esc_attr($_SERVER['HTTP_HOST']) . esc_attr($_REQUEST['_wp_http_referer']);
            elseif (! empty($_SERVER['HTTP_REFERER']))
                $redirect_to = esc_attr($_SERVER['HTTP_REFERER']);
            elseif (! empty($_SERVER['REQUEST_URI']))
                $redirect_to = $scheme . esc_attr($_SERVER['HTTP_HOST']) . esc_attr($_SERVER['REQUEST_URI']);
        } elseif ($redirectionType == 'home')
            $redirect_to = home_url();
        elseif ($redirectionType == 'profile')
            $redirect_to = $userMeta->getProfileLink();
        elseif ($redirectionType == 'dashboard')
            $redirect_to = admin_url();
        elseif ($redirectionType == 'login_page')
            $redirect_to = wp_login_url();
        elseif ($redirectionType == 'page') {
            if (isset($redirection[$action . '_page'][$role]))
                $redirect_to = get_permalink($redirection[$action . '_page'][$role]);
        } elseif ($redirectionType == 'custom_url') {
            if (isset($redirection[$action . '_url'][$role]))
                $redirect_to = $redirection[$action . '_url'][$role];
        }
        
        return html_entity_decode($redirect_to);
    }

    /**
     * Generate activation/deactivation link with or without nonce.
     */
    function userActivationUrl($action, $userID, $addNonce = true)
    {
        $url = admin_url('users.php');
        $url = add_query_arg(array(
            'action' => $action,
            'user' => $userID
        ), $url);
        
        if ($addNonce)
            $url = wp_nonce_url($url, 'um_activation');
        
        return $url;
    }

    /**
     * Generate activation/deactivation link with or without nonce.
     */
    function emailVerificationUrl($user)
    {
        global $userMeta;
        
        $settings = $userMeta->getSettings('registration');
        if (empty($settings['email_verification_page']))
            return;
        
        $pageID = (int) $settings['email_verification_page'];
        $url = get_permalink($pageID);
        if (empty($url))
            return;
        
        $hash = get_user_meta($user->ID, $userMeta->prefixLong . 'email_verification_code', true);
        if (! $hash) {
            $hash = wp_generate_password(30, false);
            update_user_meta($user->ID, $userMeta->prefixLong . 'email_verification_code', $hash);
        }
        
        $url = add_query_arg(array(
            'email' => rawurlencode($user->user_email),
            'key' => rawurlencode($hash),
            'action' => 'ev'
        ), $url);
        
        return htmlspecialchars_decode($url);
    }

    /**
     * Generate role based email template
     *
     * @param $slugs :
     *            array containing two value without keys. e.g array( 'registration', 'user_email' )
     * @param $data :
     *            array containing data to populate
     * @return html
     */
    function buildRolesEmailTabs($slugs = array(), $data = array())
    {
        global $userMeta;
        $roles = $userMeta->getRoleList();
        
        foreach ($roles as $key => $val) {
            $forms[$key] = $userMeta->renderPro('singleEmailForm', array(
                'slug' => "{$slugs[0]}[{$slugs[1]}][$key]",
                'from_email' => @$data[$slugs[0]][$slugs[1]][$key]['from_email'],
                'from_name' => @$data[$slugs[0]][$slugs[1]][$key]['from_name'],
                'format' => @$data[$slugs[0]][$slugs[1]][$key]['format'],
                'subject' => @$data[$slugs[0]][$slugs[1]][$key]['subject'],
                'body' => @$data[$slugs[0]][$slugs[1]][$key]['body'],
                /*'after_form'=> $userMeta->createInput( null, 'checkbox', array(
                                    'label'         => __( 'Copy this form data to all others role', $userMeta->name ),
                                    'enclose'       => 'p',
                                    'onclick'       => 'copyFormData(this)',
                                    'class'         => 'asdf',
                                ) ),  */
            ), 'email');
        }
        
        $html = $userMeta->jQueryRolesTab("{$slugs[0]}-{$slugs[1]}", $roles, $forms);
        
        if ('admin_email' == $slugs[1]) {
            $html .= $userMeta->createInput("{$slugs[0]}[{$slugs[1]}][um_all_admin]", 'checkbox', array(
                'label' => __('Send email to all admin', $userMeta->name),
                'id' => "um_{$slugs[0]}_{$slugs[1]}_um_all_admin",
                'value' => @$data[$slugs[0]][$slugs[1]]['um_all_admin'] ? true : false,
                'enclose' => 'p'
            ));
        }
        
        if ('profile_update' == $slugs[0]) {
            $html .= $userMeta->createInput("{$slugs[0]}[{$slugs[1]}][on_modify]", 'checkbox', array(
                'label' => __('Send email only, when user\'s data has been modified', $userMeta->name),
                'id' => "um_{$slugs[0]}_{$slugs[1]}_on_modify",
                'value' => @$data[$slugs[0]][$slugs[1]]['on_modify'] ? true : false,
                'enclose' => 'p'
            ));
        }
        
        $html .= $userMeta->createInput("{$slugs[0]}[{$slugs[1]}][um_disable]", 'checkbox', array(
            'label' => __('Disable this notification', $userMeta->name),
            'id' => "um_{$slugs[0]}_{$slugs[1]}_um_disable",
            'value' => @$data[$slugs[0]][$slugs[1]]['um_disable'] ? true : false,
            'enclose' => 'p'
        ));
        
        return $html;
    }

    /**
     * Callback hook for "pre_user_query".
     * Filter users by registration date
     */
    function filterRegistrationDate($query)
    {
        global $wpdb;
        
        if (! empty($_REQUEST['start_date']))
            $query->query_where = $query->query_where . $wpdb->prepare(" AND $wpdb->users.user_registered >= %s", $_REQUEST['start_date']);
        
        if (! empty($_REQUEST['end_date']))
            $query->query_where = $query->query_where . $wpdb->prepare(" AND $wpdb->users.user_registered <= %s", $_REQUEST['end_date']);
        
        return $query;
    }

    function isPro()
    {
        global $userMeta;
        if (! $userMeta->isPro)
            return false;
        return $userMeta->isLicenceValidated() ? true : false;
    }

    function generatePassword(&$user)
    {
        $pass = wp_generate_password();
        wp_set_password($pass, $user->ID);
        return $pass;
    }

    function loadEncDirectory($dir)
    {
        if (! file_exists($dir))
            return;
        foreach (scandir($dir) as $item) {
            if (preg_match("/Encrypted.php$/i", $item)) {
                $codes = file_get_contents($dir . $item);
                $codes = base64_decode($codes);
                eval($codes);
                $className = str_replace("Encrypted.php", "", $item);
                if (class_exists($className))
                    $classes[] = new $className();
            }
        }
        return isset($classes) ? $classes : false;
    }

    function prepareEmail($key, $user, $extra = array())
    {
        global $userMeta;
        
        $data = $userMeta->getEmailsData($key);
        $role = $userMeta->getUserRole($user->ID);
        
        if ($key == 'profile_update' && ! empty($data['admin_email']['on_modify']) && ! $extra)
            return;
        if ($key == 'profile_update' && ! empty($data['user_email']['on_modify']) && ! $extra)
            return;
        
        if (empty($data['admin_email']['um_disable'])) {
            
            $adminEmails = ! empty($data['admin_email']['um_all_admin']) ? $userMeta->getAllAdminEmail() : get_bloginfo('admin_email');
            $adminEmails = apply_filters('user_meta_admin_email_recipient', $adminEmails, $key, $user, $extra);
            
            $mailData = @$data['admin_email'][$role];
            $mailData['email'] = $adminEmails;
            $mailData['email_type'] = $key;
            $mailData['receipt_type'] = 'admin';
            $userMeta->sendEmail(self::_prepareEmail($mailData, $user, $extra));
        }
        
        if (empty($data['user_email']['um_disable'])) {
            $mailData = @$data['user_email'][$role];
            $mailData['email'] = $user->user_email;
            $mailData['email_type'] = $key;
            $mailData['receipt_type'] = 'user';
            $userMeta->sendEmail(self::_prepareEmail($mailData, $user, $extra));
        }
    }

    function _prepareEmail($mailData, $user, $extra)
    {
        global $userMeta;
        
        $mailData = apply_filters('user_meta_raw_email', $mailData, $user, $extra);
        
        $mailData['subject'] = $userMeta->convertUserContent($user, @$mailData['subject'], $extra);
        $mailData['body'] = $userMeta->convertUserContent($user, @$mailData['body'], $extra);
        
        return $mailData;
    }
}
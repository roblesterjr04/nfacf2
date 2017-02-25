<?php
namespace UserMeta;

class EmailNotificationController
{

    private $userModifiedOldData = array();

    function __construct()
    {
        add_filter('pre_user_login', array(
            $this,
            'trackValidFields'
        ), 99);
        add_action('profile_update', array(
            $this,
            'backendProfileUpdateEmail'
        ), 30);
        
        add_action('user_meta_after_user_update', array(
            $this,
            'profileUpdateEmail'
        ));
        
        add_action('user_meta_after_user_register', array(
            $this,
            'registrationEmail'
        ));
        add_action('user_meta_user_approved', array(
            $this,
            'userApproved'
        ));
        add_action('user_meta_user_activate', array(
            $this,
            'userActivate'
        ));
        add_action('user_meta_user_deactivate', array(
            $this,
            'userDeactivate'
        ));
        add_action('user_meta_email_verified', array(
            $this,
            'emailVerified'
        ));
        add_action('user_meta_after_reset_password', array(
            $this,
            'resetPasswordEmail'
        ));
        
        add_filter('user_meta_raw_email', array(
            $this,
            'addPlaceholder'
        ), 10, 3);
    }

    /**
     * Track for valid fields before user update
     * Set $this->userModifiedData with all valid fields, filter for modify will be done on next step.
     * Populate $this->userModifiedData from old $user object
     *
     * @return $user_login: This always should return $user_login
     */
    function trackValidFields($user_login)
    {
        $user = get_user_by('login', trim($user_login));
        
        $validFields = array();
        if (isset($_POST)) {
            foreach ($_POST as $key => $val) {
                if (isset($user->$key))
                    $validFields[$key] = $user->$key;
            }
        }
        
        if (! empty($_POST['user_pass']) || ! empty($_POST['pass1']))
            $validFields['user_pass'] = '';
        else
            unset($validFields['user_pass']);
        
        $this->userModifiedOldData = $validFields;
        
        return $user_login;
    }

    /**
     * Sanitize $this->userModifiedData
     *
     * @param
     *            object WP_User
     */
    private function setUserModifiedOldData($user)
    {
        foreach ($this->userModifiedOldData as $key => $val) {
            if (isset($user->$key) && $val == $user->$key)
                unset($this->userModifiedOldData[$key]);
        }
        
        $this->userModifiedOldData = apply_filters('user_meta_user_modified_old_data_tracker', $this->userModifiedOldData, $user);
    }

    function backendProfileUpdateEmail($user_id)
    {
        global $userMeta;
        
        if (! (is_admin() && (isset($_POST['from']) && $_POST['from'] == 'profile')))
            return;
        
        $user = new \WP_User($user_id);
        $this->setUserModifiedOldData($user);
        
        if (! empty($_POST['pass1']))
            $user->password = $_POST['pass1'];
        
        $userMeta->prepareEmail('profile_update', $user, $this->userModifiedOldData);
    }

    function profileUpdateEmail($userdata)
    {
        global $userMeta;
        
        $user = new \WP_User($userdata->ID);
        $this->setUserModifiedOldData($user);
        
        if (! empty($userdata->user_pass))
            $user->password = $userdata->user_pass;
        
        $userMeta->prepareEmail('profile_update', $user, $this->userModifiedOldData);
    }

    function registrationEmail($userdata)
    {
        global $userMeta;
        
        $user = new \WP_User($userdata->ID);
        $user->password = $userdata->user_pass;
        $userMeta->prepareEmail('registration', $user);
    }

    function userApproved($userID)
    {
        global $userMeta;
        
        $user = new \WP_User($userID);
        $userMeta->prepareEmail('admin_approval', $user);
    }

    function userActivate($userID)
    {
        global $userMeta;
        
        $user = new \WP_User($userID);
        $userMeta->prepareEmail('activation', $user);
    }

    function userDeactivate($userID)
    {
        global $userMeta;
        
        $user = new \WP_User($userID);
        $userMeta->prepareEmail('deactivation', $user);
    }

    function emailVerified($userID)
    {
        global $userMeta;
        
        $user = new \WP_User($userID);
        $userMeta->prepareEmail('email_verification', $user);
    }

    function resetPasswordEmail($user)
    {
        global $userMeta;
        
        $userMeta->prepareEmail('reset_password', $user);
    }

    function addPlaceholder($mailData, $user, $extra)
    {
        global $userMeta;
        
        if ('lostpassword' == $mailData['email_type'])
            return self::_lostpasswordIntegration($mailData, $user, $extra);
        
        if ('registration' != $mailData['email_type'])
            return $mailData;
        
        $registration = $userMeta->getSettings('registration');
        $user_activation = @$registration['user_activation'];
        
        $mailBody = @$mailData['body'];
        
        /**
         * Adding Password to user email if not added
         */
        // if( ( strpos( $mailBody, '%password%' ) === false ) && ( get_bloginfo( 'admin_email' ) <> @$mailData[ 'email' ] ) )
        // $mailBody .= "\n" . sprintf( __( 'Password: %s', $userMeta->name ), '%password%' ) . "\n";
        
        /**
         * Add/Remove proper placeholder for email_verification, admin_approval and both_email_admin.
         */
        if ($user_activation == 'email_verification') {
            if ((strpos($mailBody, '%email_verification_url%') === false) && ('user' == $mailData['receipt_type']))
                $mailBody .= "\r\n" . sprintf(__('Email verification url: %s', $userMeta->name), '%email_verification_url%') . "\n";
        } elseif ($user_activation == 'admin_approval') {
            if ((strpos($mailBody, '%activation_url%') === false) && ('admin' == $mailData['receipt_type']))
                $mailBody .= "\r\n" . sprintf(__('Activation url: %s', $userMeta->name), '%activation_url%') . "\n";
        } elseif ($user_activation == 'both_email_admin') {
            if ((strpos($mailBody, '%email_verification_url%') === false) && ('user' == $mailData['receipt_type']))
                $mailBody .= "\r\n" . sprintf(__('Email verification url: %s', $userMeta->name), '%email_verification_url%') . "\n";
            if ((strpos($mailBody, '%activation_url%') === false) && ('admin' == $mailData['receipt_type']))
                $mailBody .= "\r\n" . sprintf(__('Activation url: %s', $userMeta->name), '%activation_url%') . "\n";
        }
        $mailData['body'] = $mailBody;
        
        return $mailData;
    }

    function _lostpasswordIntegration($mailData, $user, $extra)
    {
        global $userMeta;
        
        $mailData['body'] = ! empty($mailData['body']) ? $mailData['body'] : '';
        
        if (strpos($mailData['body'], '%reset_password_link%') === false)
            $mailData['body'] .= sprintf(__('To reset your password, please visit the following address: \r\n\r\n %s', $userMeta->name), "%reset_password_link%");
        
        $mailData['body'] = str_replace('%reset_password_link%', ! empty($extra['reset_password_link']) ? $extra['reset_password_link'] : '', $mailData['body']);
        
        if ($userMeta->isHookEnable('retrieve_password_title'))
            $mailData['subject'] = apply_filters('retrieve_password_title', ! empty($mailData['subject']) ? $mailData['subject'] : '');
        
        if ($userMeta->isHookEnable('retrieve_password_message'))
            $mailData['body'] = apply_filters('retrieve_password_message', $mailData['body'], ! empty($extra['key']) ? $extra['key'] : '');
        
        return $mailData;
    }
}
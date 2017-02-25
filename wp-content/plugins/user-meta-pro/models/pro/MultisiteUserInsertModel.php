<?php
namespace UserMeta;

/**
 * Handle multisite related user' insert functionality.
 *
 * @since 1.1.8rc1
 *       
 * @author Khaled Hossain
 */
class MultisiteUserInsertModel
{

    /**
     * Validate blog title, name etc.
     * Used by UserInsert->registerUser()
     *
     * @return array | WP_Error
     */
    public function validateBlogSignup()
    {
        global $userMeta;
        
        if (is_multisite() && wp_verify_nonce(@$_POST['um_newblog'], 'blogname') && ! empty($_POST['blogname'])) {
            $blogData = wpmu_validate_blog_signup($_POST['blogname'], $_POST['blog_title']);
            if ($blogData['errors']->get_error_code()) {
                return $blogData['errors'];
            }
            
            return $blogData;
        }
    }

    /**
     * Add user to blog.
     * If add_user_to_blog set true in UserMeta settings panel.
     * Used by UserInsert->registerUser()
     *
     * @param array $userData            
     * @return $user_id
     */
    public function addUserToBlog(array $userData)
    {
        global $userMeta;
        
        $userID = null;
        if (is_multisite()) {
            $registrationSettings = $userMeta->getSettings('registration');
            if (! empty($registrationSettings['add_user_to_blog'])) {
                $userID = getUserID($userData);
                if ($userID) {
                    $blog_id = get_current_blog_id();
                    if (! is_user_member_of_blog($userID, $blog_id)) {
                        add_user_to_blog($blog_id, $userID, get_option('default_role'));
                    } else {
                        /**
                         * Set user_id to 0 for forcing to show user already exists message.
                         */
                        return 0;
                    }
                }
            }
        }
        
        return $userID;
    }

    /**
     * If add_user_to_blog set true in UserMeta settings panel.
     * Used by UserInsert->postInsertUserProcess()
     */
    public function validateMultisiteRegistration(&$errors)
    {
        global $userMeta;
        
        if (! is_multisite())
            return;
        
        $registrationSettings = $userMeta->getSettings('registration');
        if (! empty($registrationSettings['add_user_to_blog'])) {
            if ($errors->get_error_code()) {
                $skipMsgs = array(
                    'existing_user_login',
                    'existing_user_email',
                    'validate_unique'
                );
                foreach ($skipMsgs as $skipMsg) {
                    if (in_array($skipMsg, $errors->get_error_codes())) {
                        unset($errors->errors[$skipMsg]);
                    }
                }
            }
        }
    }

    /**
     * Register new blog.
     * Used by UserInsert->registerUser()
     *
     * @param array $blogData            
     * @param array $userData            
     */
    public function registerBlog($blogData, $userData)
    {
        global $userMeta;
        extract($blogData);
        
        $active_signup = get_site_option('registration');
        if (! $active_signup) {
            $active_signup = 'all';
        }
        
        $active_signup = apply_filters('wpmu_active_signup', $active_signup); // return "all", "none", "blog" or "user"
        if (! ($active_signup == 'all' || $active_signup == 'blog')) {
            return false;
        }
        
        if ($errors->get_error_code()) {
            return $errors;
        }
        
        // $public = (int) $_POST['blog_public'];
        // $meta = array ('lang_id' => 1, 'public' => $public);
        // $meta = apply_filters( 'add_signup_meta', $meta );
        
        if (empty($userData['user_login']) || empty($userData['user_email'])) {
            return new \WP_Error('login_email_required', $userMeta->getMsg('login_email_required'));
        }
        
        $meta = '';
        
        wpmu_signup_blog($domain, $path, $blog_title, $userData['user_login'], $userData['user_email'], $meta);
        
        $msg = null;
        $msg .= sprintf(__('Congratulations! Your new site, %s, is almost ready.', $userMeta->name), "<a href='http://{$domain}{$path}'>{$blog_title}</a>");
        $msg .= __('But, before you can start using your site, <strong>you must activate it</strong>.', $userMeta->name);
        $msg .= sprintf(__('Check your inbox at <strong>%s</strong> and click the link given.', $userMeta->name), $userData['user_email']);
        $msg .= __('If you do not activate your site within two days, you will have to sign up again.', $userMeta->name);
        
        $msg = apply_filters('user_meta_blog_signup_msg', $msg, "<a href='http://{$domain}{$path}'>{$blog_title}</a>", $userData['user_email']);
        
        do_action('signup_finished');
        
        return $msg;
    }
}

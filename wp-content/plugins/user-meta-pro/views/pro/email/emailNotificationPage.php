<?php
global $userMeta;
// Expected: $data, $roles
?>

<div class="wrap">
	<h1><?php _e( 'E-mail Notifications', $userMeta->name ); ?></h1>
	<p><?php _e( 'Click panel title to expand', $userMeta->name ); ?></p>
    <?php do_action( 'um_admin_notice' ); ?>
    <?php if ( isset( $_REQUEST['method_name'] ) ) $userMeta->ajaxSaveEmailTemplate(); ?>
    <div id="dashboard-widgets-wrap">
		<div class="metabox-holder">
			<div id="um_admin_content">
				<form method="post" onsubmit="pfAjaxRequest(this); return false;">
                <?php
                
                $panelArgs = [
                    'collapsed' => true
                ];
                
                /**
                 * User Registration Email
                 */
                $html = null;
                
                $html .= '<p>' . __('This e-mail will be sent to new user after registration.', $userMeta->name) . '</p>';
                $html .= '<h3>' . __('User Notification', $userMeta->name) . '</h3>';
                $html .= $userMeta->buildRolesEmailTabs(array(
                    'registration',
                    'user_email'
                ), $data);
                $html .= '<p><i>' . sprintf(__('Use placeholder %s if needed.', $userMeta->name), '%email_verification_url%') . '</i></p>';
                
                $html .= '<div class="clear"></div>';
                $html .= '<div class="pf_divider"></div>';
                
                $html .= '<p>' . __('This e-mail will be sent to admin after new user registration.', $userMeta->name) . '</p>';
                $html .= '<h3>' . __('Admin Notification', $userMeta->name) . '</h3>';
                $html .= $userMeta->buildRolesEmailTabs(array(
                    'registration',
                    'admin_email'
                ), $data);
                $html .= '<p><i>' . sprintf(__('Use placeholder %s if needed.', $userMeta->name), '%activation_url%') . '</i></p>';
                
                // echo $userMeta->metaBox( __( 'User Registration E-mail', $userMeta->name ), $html, false, false );
                echo UserMeta\panel(__('User Registration E-mail', $userMeta->name), $html, $panelArgs);
                
                /**
                 * User Email Validation Form
                 */
                $html = null;
                
                $html .= '<p>' . __('This email will be sent to user after email is verified.', $userMeta->name) . '</p>';
                $html .= '<em>' . __("To activate this email, go to <strong>User Meta >> Settings >> Registration</strong> and checked either \"Need email verification\" or \"Need both email verification and admin approval\"
                    ", $userMeta->name) . '</em>';
                $html .= '<h3>' . __('User Notification', $userMeta->name) . '</h3>';
                $html .= $userMeta->buildRolesEmailTabs(array(
                    'email_verification',
                    'user_email'
                ), $data);
                
                $html .= '<div class="clear"></div>';
                $html .= '<div class="pf_divider"></div>';
                
                $html .= '<p>' . __('This email will be sent to admin after user email is verified.', $userMeta->name) . '</p>';
                $html .= '<h3>' . __('Admin Notification', $userMeta->name) . '</h3>';
                $html .= $userMeta->buildRolesEmailTabs(array(
                    'email_verification',
                    'admin_email'
                ), $data);
                $html .= '<p><i>' . sprintf(__('Use placeholder %s if needed.', $userMeta->name), '%activation_url%') . '</i></p>';
                
                // echo $userMeta->metaBox( __( 'After email is verified', $userMeta->name ), $html, false, false );
                echo UserMeta\panel(__('After email is verified', $userMeta->name), $html, $panelArgs);
                
                /**
                 * Admin Approvals Form
                 */
                $html = null;
                $html .= '<p>' . __('This e-mail will be sent to user when an admin approves his/her account.', $userMeta->name) . '</p>';
                $html .= '<em>' . __("To activate this email, go to <strong>User Meta >> Settings >> Registration</strong> and checked either \"Need admin approval\" or \"Need both email verification and admin approval\"
                    ", $userMeta->name) . '</em>';
                
                $html .= '<h3>' . __('User Notification', $userMeta->name) . '</h3>';
                $html .= $userMeta->buildRolesEmailTabs(array(
                    'admin_approval',
                    'user_email'
                ), $data);
                
                // echo $userMeta->metaBox( __( 'Admin Approval E-mail', $userMeta->name ), $html, false, false );
                echo UserMeta\panel(__('Admin Approval E-mail', $userMeta->name), $html, $panelArgs);
                
                /**
                 * User Activation Form
                 */
                $html = null;
                $html .= '<p>' . __('This e-mail will be sent to user upon activation.', $userMeta->name) . '</p>';
                
                $html .= '<h3>' . __('User Notification', $userMeta->name) . '</h3>';
                $html .= $userMeta->buildRolesEmailTabs(array(
                    'activation',
                    'user_email'
                ), $data);
                
                // echo $userMeta->metaBox( __( 'User Activation E-mail', $userMeta->name ), $html, false, false );
                echo UserMeta\panel(__('User Activation E-mail', $userMeta->name), $html, $panelArgs);
                
                /**
                 * User Deactivation Form
                 */
                $html = null;
                $html .= '<p>' . __('This e-mail will be sent to user upon deactivation.', $userMeta->name) . '</p>';
                
                $html .= '<h3>' . __('User Notification', $userMeta->name) . '</h3>';
                $html .= $userMeta->buildRolesEmailTabs(array(
                    'deactivation',
                    'user_email'
                ), $data);
                
                // echo $userMeta->metaBox( __( 'User Deactivation E-mail', $userMeta->name ), $html, false, false );
                echo UserMeta\panel(__('User Deactivation E-mail', $userMeta->name), $html, $panelArgs);
                
                /**
                 * LostPassword Email
                 */
                $html = null;
                $html .= '<p>' . __('This e-mail will be sent to user when requested to reset password.', $userMeta->name) . '</p>';
                
                $html .= '<h3>' . __('User Notification', $userMeta->name) . '</h3>';
                $html .= $userMeta->buildRolesEmailTabs(array(
                    'lostpassword',
                    'user_email'
                ), $data);
                $html .= '<p><i>' . sprintf(__('Use placeholder %s or it will included automatically.', $userMeta->name), '%reset_password_link%') . '</i></p>';
                
                // echo $userMeta->metaBox( __( 'Lost Password E-mail', $userMeta->name ), $html, false, false );
                echo UserMeta\panel(__('Lost Password E-mail', $userMeta->name), $html, $panelArgs);
                
                /**
                 * Password change email
                 */
                $html = null;
                
                // $html .= '<p>'. __( 'This e-mail will be sent to user when they reset their password.', $userMeta->name ) .'</p>';
                // $html .= '<h3>'. __( 'User Notification', $userMeta->name ) . '</h3>';
                // $html .= $userMeta->buildRolesEmailTabs( array( 'reset_password', 'user_email' ), $data );
                
                // $html .= '<div class="clear"></div>';
                // $html .= '<div class="pf_divider"></div>';
                
                $html .= '<p>' . __('This e-mail will be sent to admin when user reset their password.', $userMeta->name) . '</p>';
                $html .= '<h3>' . __('Admin Notification', $userMeta->name) . '</h3>';
                $html .= $userMeta->buildRolesEmailTabs(array(
                    'reset_password',
                    'admin_email'
                ), $data);
                
                // echo $userMeta->metaBox( __( 'Reset Password E-mail', $userMeta->name ), $html, false, false );
                echo UserMeta\panel(__('Reset Password E-mail', $userMeta->name), $html, $panelArgs);
                
                /**
                 * Profile update Email
                 */
                $html = null;
                
                $html .= '<p>' . __('This e-mail will be sent to user when they update their front-end profile.', $userMeta->name) . '</p>';
                $html .= '<h3>' . __('User Notification', $userMeta->name) . '</h3>';
                $html .= $userMeta->buildRolesEmailTabs(array(
                    'profile_update',
                    'user_email'
                ), $data);
                
                $html .= '<div class="clear"></div>';
                $html .= '<div class="pf_divider"></div>';
                
                $html .= '<p>' . __('This e-mail will be sent to admin when user update their front-end profile.', $userMeta->name) . '</p>';
                $html .= '<h3>' . __('Admin Notification', $userMeta->name) . '</h3>';
                $html .= $userMeta->buildRolesEmailTabs(array(
                    'profile_update',
                    'admin_email'
                ), $data);
                
                // echo $userMeta->metaBox( __( 'Profile Update E-mail', $userMeta->name ), $html, false, false );
                echo UserMeta\panel(__('Profile Update E-mail', $userMeta->name), $html, $panelArgs);
                
                echo $userMeta->methodName('SaveEmailTemplate');
                
                /**
                 * Button
                 */
                echo $userMeta->createInput("save_field", "submit", array(
                    "value" => __("Save Changes", $userMeta->name),
                    "id" => "update_settings",
                    "class" => "button-primary",
                    "enclose" => "p"
                ));
                
                ?>
                </form>
			</div>

			<div id="um_admin_sidebar">
                <?php
                $variable = null;
                $variable .= "<strong>" . __('Site Placeholder', $userMeta->name) . "</strong><p>";
                $variable .= "%site_title%, ";
                $variable .= "%site_url%, ";
                $variable .= "%login_url%, ";
                $variable .= "%logout_url%, ";
                $variable .= "%activation_url%, ";
                $variable .= "%email_verification_url%";
                $variable .= "</p>";
                
                $variable .= "<strong>" . __('User Placeholder', $userMeta->name) . "</strong><p>";
                $variable .= "%ID%, ";
                $variable .= "%user_login%, ";
                $variable .= "%user_email%, ";
                $variable .= "%password%, ";
                $variable .= "%display_name%, ";
                $variable .= "%first_name%, ";
                $variable .= "%last_name%, ";
                $variable .= "%generated_password%, ";
                $variable .= "%user_modified_data%";
                $variable .= "</p>";
                
                $variable .= "<strong>" . __('Custom Field', $userMeta->name) . "</strong><p>";
                $variable .= "%your_custom_user_meta_key%</p>";
                
                $variable .= "<p><em>(" . __("Placeholder will be replaced with the relevant value when used in email subject or body.", $userMeta->name) . ")</em></p>";
                
                $panelArgs = [
                    'panel_class' => 'panel-default'
                ];
                
                echo UserMeta\panel(__('Placeholder', $userMeta->name), $variable, $panelArgs);                
                ?>
            </div>
		</div>
	</div>
</div>

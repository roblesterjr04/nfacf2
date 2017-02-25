<?php
global $userMeta;
// Expected: $registration
// field slug: registration

$html = null;

$html .= "<div class='pf_divider'></div>";

$registrationSettings = array(
    'auto_active' => __('User auto activation.', $userMeta->name) . '<br /><em>(' . __('User will be activated automatically after registration', $userMeta->name) . ')</em>',
    'email_verification' => __('Need email verification.', $userMeta->name) . '<br /><em>(' . __('A verification link will be sent to user email. User must verify the link to activate their account', $userMeta->name) . ')</em>',
    'admin_approval' => __('Need admin approval.', $userMeta->name) . '<br /><em>(' . __('Admin needs to approve the new user', $userMeta->name) . ')</em>',
    'both_email_admin' => __('Need both email verification and admin approval.', $userMeta->name) . '<br /><em>(' . __('A verification link will be sent to user email. User must verify the link to activate their account and an admin needs to approve the account', $userMeta->name) . ')</em>'
);

$html .= $userMeta->createInput("registration[user_activation]", "radio", array(
    'label' => __('User Activation', $userMeta->name),
    'value' => @$registration['user_activation'],
    'id' => 'um_registration_user_activation',
    'class' => 'um_registration_user_activation',
    'onchange' => 'umSettingsRegistratioUserActivationChange()',
    'label_class' => 'pf_label',
    'option_before' => '<p>',
    'option_after' => '</p>',
    'by_key' => true
), $registrationSettings);

$html .= "<div id='um_settings_registration_block_1'>";
$html .= "<div class='pf_divider'></div>";
$html .= $userMeta->createInput('registration[auto_login]', 'checkbox', array(
    'label' => __('Auto login after registration', $userMeta->name),
    'value' => ! empty($registration['auto_login']) ? true : false,
    'id' => 'um_registration_auto_login'
));
$html .= '<p><i>' . __('If checked, user will be automatically logged in after registration.', $userMeta->name) . '</i></p>';
$html .= "</div>";

$html .= "<div id='um_settings_registration_block_2'>";
$html .= "<div class='pf_divider'></div>";
$html .= "<p><strong>" . __('Email verification page', $userMeta->name) . "  </strong></p>";
$html .= wp_dropdown_pages(array(
    'name' => 'registration[email_verification_page]',
    'id' => 'um_registration_email_verification_page',
    'selected' => @$registration['email_verification_page'],
    'echo' => 0,
    'show_option_none' => 'None '
));

$createPageUrl = admin_url('admin-ajax.php');
$createPageUrl = add_query_arg(array(
    'page' => 'verify-email',
    'method_name' => 'generatePage',
    'action' => 'pf_ajax_request'
), $createPageUrl);
$createPageUrl = wp_nonce_url($createPageUrl, 'generate_page');
$html .= "<a href='$createPageUrl' id='um_registration_email_verification_page_create' class='button-secondary'>Create Page</a>";

$html .= " <span class='um_required_email_verification_page' style='color:red'><em><strong>(" . __('Email verification will not work until a page is selected here!', $userMeta->name) . ")</strong></em></span>";
$html .= '<p><em>' . __('This is the page a user will be redirected to when they want to verify their email address.', $userMeta->name) . '</em></p>';
$html .= "</div>";

if (is_multisite()) {
    $html .= "<div class='pf_divider'></div>";
    $html .= "<h4>" . __('Multisite Registration', $userMeta->name) . "</h4>";
    
    $html .= $userMeta->createInput("registration[add_user_to_blog]", "checkbox", array(
        'value' => @$registration['add_user_to_blog'],
        'id' => 'um_registration_add_user_to_blog',
        'label' => __('Allow user registration if user already exists in any of the other sites in the network', $userMeta->name)
    ));
}
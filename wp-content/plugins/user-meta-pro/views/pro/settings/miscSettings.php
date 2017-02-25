<?php
global $userMeta;

$html = null;

$html .= "<p><strong>" . __('Reset password page', $userMeta->name) . "</strong></p>";
$html .= wp_dropdown_pages(array(
    'name' => 'misc[resetpass_page]',
    'id' => 'misc_resetpass_page',
    'selected' => @$login['login_page'],
    'echo' => 0,
    'show_option_none' => 'None '
));
$html .= '<p><em>' . __('This is the page a user will be redirected to when they want to reset their password.', $userMeta->name) . '</em></p>';

$html .= "<div class='pf_divider'></div>";

$html .= "<p><strong>" . __('Email verification page', $userMeta->name) . "</strong></p>";
$html .= wp_dropdown_pages(array(
    'name' => 'misc[email_verification_page]',
    'id' => 'misc_email_verification_page',
    'selected' => @$login['login_page'],
    'echo' => 0,
    'show_option_none' => 'None '
));
$html .= '<p><em>' . __('This is the page a user will be redirected to when they want to verify their email address (if needed).', $userMeta->name) . '</em></p>';


/*$html .= $userMeta->createInput( 'value_separator', 'text', array(
	'label'			=> __( 'Value Separator', $userMeta->name ),
	'value'			=> !empty( $misc['value_separator'] ) ? $misc['value_separator'] : ',',
	'label_class'	=> 'pf_label',
) );*/


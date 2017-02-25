<?php
global $userMeta;
// Expected $fields, $forms, $backend_profile

$fieldPrefix = 'backend_profile';

$html = null;

/**
 * Default fields in admin profile
 */
$wpBackendFields = array(
    array(
        'heading_0' => __('Personal Options Heading', $userMeta->name),
        'rich_editing' => __('Visual Editor', $userMeta->name),
        'color-picker' => __('Admin Color Scheme', $userMeta->name), // For older version: admin_color_classic
        'comment_shortcuts' => __('Keyboard Shortcuts', $userMeta->name),
        'admin_bar_front' => __('Toolbar', $userMeta->name)
    ),
    
    array(
        'heading_1' => __('Name Heading', $userMeta->name),
        'user_login' => __('Username', $userMeta->name),
        'first_name' => __('First Name', $userMeta->name),
        'last_name' => __('Last Name', $userMeta->name),
        'nickname' => __('Nickname', $userMeta->name),
        'display_name' => __('Display Name', $userMeta->name)
    ),
    
    array(
        'heading_2' => __('Contact Info Heading', $userMeta->name),
        'email' => __('E-mail', $userMeta->name),
        'url' => __('Website', $userMeta->name),
        'aim' => __('AIM', $userMeta->name),
        'yim' => __('Yahoo IM', $userMeta->name),
        'jabber' => __('Jabber / Google Talk', $userMeta->name),
        'googleplus' => __('Google+', $userMeta->name),
        'twitter' => __('Twitter username', $userMeta->name),
        'facebook' => __('Facebook profile URL', $userMeta->name),
        'google_profile' => __('Google Profile URL', $userMeta->name)
    ),
    
    array(
        'heading_3' => __('About Yourself Heading', $userMeta->name),
        'description' => __('Biographical Info', $userMeta->name),
        'pass1' => __('New Password', $userMeta->name)
    )
);

$html .= "<h4>" . __('Hide WordPress default fields', $userMeta->name) . "</h4>";
$html .= "<p><i>" . __('Select fields from below, which you need to hide from backend profile page', $userMeta->name) . "</i></p>";

foreach ($wpBackendFields as $fieldsGroup) {
    $input = null;
    foreach ($fieldsGroup as $fieldKey => $fieldValue) {
        $input .= $userMeta->createInput("{$fieldPrefix}[hide_fields][{$fieldKey}]", "checkbox", array(
            'value' => @$backend_profile['hide_fields'][$fieldKey] ? true : false,
            'id' => "{$fieldPrefix}_hide_fields_{$fieldKey}",
            'label' => $fieldValue,
            'enclose' => 'div'
        ));
    }
    $html .= "<div class=\"pf_left pf_width_25\">$input</div>";
}

$html .= '<div class="clear"></div>';
$html .= "<div class='pf_divider'></div>";

/**
 * Allow extra fields
 */

// id to support old style dropable code
$id = 1;
$form = $backend_profile;

// Remove not extra fields
if (is_array($fields)) {
    foreach ($fields as $key => $field) {
        if (empty($field['meta_key'])) {
            if (! in_array(@$field['field_type'], array(
                'user_registered',
                'section_heading',
                'user_avatar'
            )))
                unset($fields[$key]);
        }
    }
}

$html .= '<h4>' . __('Extra fields in backend profile', $userMeta->name) . '</h4>';
$html .= '<p><i>' . __('Add extra fields to backend profile. drag from available fields block to backend profile block', $userMeta->name) . '</i></p>';
$html .= "<div class=\"um_left um_block_title\">" . __('Fields in backend profile (Drag from available fields)', $userMeta->name) . "</div>";
$html .= "<div class=\"um_right um_block_title\">" . __('Available Fields', $userMeta->name) . "</div>";
$html .= "<div class='clear'></div>";

// Showing selected fields
$html .= "<div class='um_selected_fields um_left um_dropme'>";
if (isset($form['fields'])) {
    foreach ($form['fields'] as $fieldID) {
        if (isset($fields[$fieldID])) {
            $fieldTitle = isset($fields[$fieldID]['field_title']) ? $fields[$fieldID]['field_title'] : null;
            $html .= "<div class='postbox'>$fieldTitle ({$fields[$fieldID]['field_type']}) ID:$fieldID<input type='hidden' name='{$fieldPrefix}[fields][]' value='$fieldID' /></div>";
            unset($fields[$fieldID]);
        }
    }
}
$html .= "</div>";

// Showing available fields
$html .= "<div class='um_availabele_fields um_right um_dropme'>";
if (is_array($fields)) {
    foreach ($fields as $fieldID => $fieldData) {
        $fieldTitle = isset($fieldData['field_title']) ? $fieldData['field_title'] : null;
        $html .= "<div class='postbox'>$fieldTitle ({$fieldData['field_type']}) ID:$fieldID<input type='hidden' name='{$fieldPrefix}[fields][]' value='$fieldID' /></div>";
    }
}
$html .= "</div>";

$html .= "<div class='clear'></div>";
$html .= "<input type='hidden' name='{$fieldPrefix}[field_count]' id='field_count_$id' value='' />";
//$html .= "<div class='pf_divider'></div>";
                      
//echo $userMeta->metaBox( "Backend Profile Settings", $html );

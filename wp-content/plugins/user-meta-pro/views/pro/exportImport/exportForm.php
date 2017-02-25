<?php
global $userMeta;
// / Expected: $formID, $fieldsSelected, $fieldsAvailable, $roles, $formData

$html = null;

$html .= '<form method="post" class="um_user_export_form">';

$html .= "<div class=\"um_left um_block_title\">" . __('Fields for export (Drag from available fields)', $userMeta->name) . "</div>";
$html .= "<div class=\"um_right um_block_title\">" . __('Available Fields', $userMeta->name) . "</div>";
$html .= '<div class="clear"></div>';

// Showing selected fields
$html .= '<div class="um_selected_fields um_left um_dropme">';
if (is_array($fieldsSelected)) {
    foreach ($fieldsSelected as $key => $val) {
        $html .= "<div class=\"postbox\">Title: <input style=\"width:50%\" type=\"text\" name=\"fields[$key]\" value=\"$val\" /> ($key)</div>";
    }
}
$html .= "</div>";

// Showing available fields
$html .= '<div class="um_availabele_fields um_right um_dropme">';
if (is_array($fieldsAvailable)) {
    foreach ($fieldsAvailable as $key => $val) {
        $html .= "<div class=\"postbox\">Title: <input style=\"width:50%\" type=\"text\" name=\"fields[$key]\" value=\"$val\" /> ($key)</div>";
    }
}
$html .= "</div>";

$html .= "<div class='clear'></div>";

$html .= $userMeta->methodName('UserExport');
$html .= $userMeta->createInput('form_id', 'hidden', array(
    'value' => $formID
));
$html .= $userMeta->nonceField();

$html .= '<br />';

$html .= $userMeta->createInput(null, 'text', array(
    'label' => __("Meta Key", $userMeta->name),
    'after' => ' <input type="button" value="Add New" onclick="umAddFieldToExport(this)" class="button-secondary" />',
    'class' => 'um_add_export_meta_key',
    'enclose' => 'p style="float:left"'
));

$html .= $userMeta->createInput('button', 'button', array(
    'value' => __("Drag all fields", $userMeta->name),
    'class' => 'button-secondary',
    'onclick' => 'umDragAllFieldToExport(this)',
    'enclose' => 'p style="float:right"'
));

$html .= "<br />";

$html .= "<div class=\"pf_divider\"></div>";

// $html .= "<h4>" . __('Export Option', $userMeta->name) ."</h4>";

$block1 = $userMeta->createInput('exclude_roles', 'checkbox', array(
    'label' => __('Exclude Roles', $userMeta->name),
    'value' => ! empty($formData['exclude_roles']) ? $formData['exclude_roles'] : null,
    'id' => 'um_exclude_roles',
    // 'class' => 'um_multiselect', //multiselect bug: cut last part of rendering
    'by_key' => true,
    'combind' => true,
    'label_class' => 'pf_label',
    'option_after' => '<br />',
    'enclose' => 'div class="form-group"'
), $roles);

$block2 = $userMeta->createInput('start_date', 'text', array(
    'label' => __('Start Date', $userMeta->name),
    'value' => @$formData['start_date'],
    'class' => 'um_date',
    'label_class' => 'pf_label',
    'enclose' => 'p'
));

$block2 .= $userMeta->createInput('end_date', 'text', array(
    'label' => __('End Date', $userMeta->name),
    'value' => @$formData['end_date'],
    'class' => 'um_date',
    'label_class' => 'pf_label',
    'enclose' => 'p'
));

$block3 = $userMeta->createInput('orderby', 'select', array(
    'label' => __('Order By', $userMeta->name),
    'value' => @$formData['orderby'],
    'label_class' => 'pf_label',
    'enclose' => 'p'
), array(
    'login',
    'nicename',
    'email',
    'url',
    'registered',
    'display_name',
    'post_count'
));

$block3 .= $userMeta->createInput('order', 'select', array(
    'label' => __('Order', $userMeta->name),
    'value' => @$formData['order'],
    'label_class' => 'pf_label',
    'enclose' => 'p'
), array(
    'ASC',
    'DESC'
));

$html .= "<div class=\"pf_width_30 pf_left\">$block1</div><div class=\"pf_width_30  pf_left\">$block2</div><div class=\"pf_width_30  pf_left\">$block3</div>";

$html .= '<div class="clear"></div>';

$html .= $userMeta->createInput("buttom", "button", array(
    "value" => __("Save Only", $userMeta->name),
    "class" => "button-primary",
    "onclick" => "umUserExport(this,'save')"
));

$html .= "&nbsp;&nbsp;&nbsp;";

$html .= $userMeta->createInput("button", "button", array(
    "value" => __("Export Only", $userMeta->name),
    "class" => "button-primary",
    "onclick" => "umUserExport(this,'export')"
));

$html .= "&nbsp;&nbsp;&nbsp;";

$html .= $userMeta->createInput("buttom", "button", array(
    "value" => __("Save & Export", $userMeta->name),
    "class" => "button-primary",
    "onclick" => "umUserExport(this,'save_export')"
));

if ($formID !== 'default') {
    $html .= $userMeta->createInput("buttom", "button", array(
        "value" => __("Remove", $userMeta->name),
        "class" => "button-secondary",
        "onclick" => "umRemoveFieldToExport(this, $formID)",
        "style" => "float:right;"
    ));
}

$html .= "</form>";

$panelArgs = [
    'collapsed' => ($formID == 'default' || ! empty($_REQUEST['form_id'])) ? false : true
];

$html = UserMeta\panel(sprintf(__('User Export (ID:%s)', $userMeta->name), $formID), $html, $panelArgs);

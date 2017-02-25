<?php
global $userMeta;

$html = null;

$html .= "<form method=\"post\">";

$html .= $userMeta->methodName('ExportUmp');
$html .= $userMeta->nonceField();

$html .= '<p><strong>' . __('Export UMP', $userMeta->name) . '</strong></p>';

$html .= $userMeta->createInput(null, 'checkbox', array(
    'label' => __('Fields', $userMeta->name),
    'id' => 'um_export_field',
    'value' => 'fields',
    'enclose' => 'p',
    'disabled' => 'disabled'
));

$exportList = array(
    'forms' => __('Forms', $userMeta->name),
    'emails' => __('Email Notifications', $userMeta->name),
    'export' => __('User Export Templates', $userMeta->name),
    'settings' => __('Settings', $userMeta->name)
);

$html .= $userMeta->createInput('includes', 'checkbox', array(
    'value' => array_flip($exportList),
    'id' => 'um_export_includes',
    'by_key' => true,
    'combind' => true,
    'option_after' => '<br />',
    'enclose' => 'p'
), $exportList);

$html .= $userMeta->createInput("button", "button", array(
    "value" => __("Export", $userMeta->name),
    "class" => "button-primary",
    "onclick" => "umRedirection(this)"
));

$html .= "</form>";

$html = "<div class=\"pf_left pf_width_30\">$html</div>";

$importDiv = null;
$importDiv .= '<p><strong>' . __('Import UMP', $userMeta->name) . '</strong></p>';
$importDiv .= "<p>" . __('Upload exported txt file to import.', $userMeta->name) . "</p>";
$importDiv .= "<div id=\"txt_upload_ump_import\" name=\"txt_upload_ump_import\" class=\"um_file_uploader_field\" um_field_id=\"txt_upload_ump_import\" extension=\"txt\" maxsize=\"$maxSize\"></div>";
$importDiv .= "<div id=\"txt_upload_ump_import_result\"></div>";

$html .= "<div class=\"pf_left um_import_div\">$importDiv</div>";

$html .= "<div class=\"clear\"></div>";

echo UserMeta\panel(__('Export & Import User Meta Pro', $userMeta->name), $html);

?>

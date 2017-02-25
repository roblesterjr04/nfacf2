<?php
global $userMeta;
// Expected $csvCache, $maxSize

$html = null;

$info = "<li>" . __("First row of csv file will be treated as field name.", $userMeta->name) . "</li>";
$info .= "<li>" . __('Fields should be separated by comma and enclosed with double quotation.', $userMeta->name) . "</li>";

$html .= $userMeta->showInfo($info, __("Upload <strong>CSV</strong> file only.", $userMeta->name));

$html .= '<form id="um_user_import_form" method="post" enctype="multipart/form-data" >';

$html .= "<div id=\"csv_upload_user_import\" name=\"csv_upload_user_import\" class=\"um_file_uploader_field\" um_field_id=\"csv_upload_user_import\" extension=\"csv\" maxsize=\"$maxSize\"></div>";

$html .= "</form>";

$html .= "<div id=\"csv_upload_user_import_result\"></div>";

echo UserMeta\panel(__('User Import', $userMeta->name), $html);

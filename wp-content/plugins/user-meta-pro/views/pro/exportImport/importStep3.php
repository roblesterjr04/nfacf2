<?php
global $userMeta;
// Expected: file_pointer, $percent, $is_loop, $import_count

$html = "

<div id=\"progressbar\" style=\"height:20px\"></div>

<script>
jQuery(document).ready(function(){
	jQuery( \"#progressbar\" ).progressbar({
		value: $percent
	});	   
})
</script>

";

if ($percent == 100) {
    $html .= __('Import completed.', $userMeta->name);
    $html .= '<script>jQuery(".ui-dialog-buttonset .ui-button-text").html("' . __('Close', $userMeta->name) . '")</script>';
} else {
    $html .= "<img src=\"" . $userMeta->assetsUrl . "/images/pf_loading_fb.gif\" />";
}

$rows = ! empty($import_count['rows']) ? $import_count['rows'] : 0;
$status = "Read: $rows<br />";

foreach ($import_count as $key => $val) {
    if (empty($val) || 'rows' == $key)
        continue;
    
    $status .= ucwords($key) . ": $val <br />";
}

$skipped = $import_count['rows'] - ($import_count['created'] + max($import_count['updated'], $import_count['added']));

if ($skipped > 0)
    $status .= "Skipped: $skipped<br />";

$html .= "<p>$status</p>";

$do_loop = $is_loop ? "do_loop=\"do_loop\"" : null;
$html = "<div id=\"import_response\" $do_loop file_pointer=\"$file_pointer\" >$html</div>";


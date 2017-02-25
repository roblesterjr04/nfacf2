<?php
global $userMeta;
// Expected $data;

$html = null;

$html .= "<form method=\"post\" onsubmit=\"pfAjaxRequest(this); return false;\" >";

$html .= $userMeta->methodName('ImportUmp');
$html .= $userMeta->nonceField();

$html .= $userMeta->createInput('filepath', 'hidden', array(
    'value' => @$_REQUEST['filepath']
));

$html .= $userMeta->createInput('do_import', 'hidden', array(
    'value' => '1'
));

if (! empty($data['fields'])) {
    $html .= $userMeta->createInput('includes[fields]', 'radio', array(
        'value' => 'nothing',
        'label' => __('Import Fields', $userMeta->name),
        'id' => 'um_import_includes_fields',
        'by_key' => true,
        'label_class' => 'pf_label',
        'option_after' => '<br />'
    ), array(
        'replace' => __('Replace all existing fields', $userMeta->name),
        'add' => __('Add only new fields', $userMeta->name),
        'nothing' => __('Do not import any fields', $userMeta->name)
    ));
}

if (! empty($data['forms'])) {
    $html .= $userMeta->createInput('includes[forms]', 'radio', array(
        'value' => 'nothing',
        'label' => __('Import Forms', $userMeta->name),
        'id' => 'um_import_includes_forms',
        'by_key' => true,
        'label_class' => 'pf_label',
        'option_after' => '<br />'
    ), array(
        'replace' => __('Replace all existing forms', $userMeta->name),
        'add' => __('Add only new forms', $userMeta->name),
        'nothing' => __('Do not import any forms', $userMeta->name)
    ));
}

if (! empty($data['emails'])) {
    $html .= $userMeta->createInput('includes[emails]', 'radio', array(
        'value' => 'nothing',
        'label' => __('Import Email Notifications', $userMeta->name),
        'id' => 'um_import_includes_emails',
        'by_key' => true,
        'label_class' => 'pf_label',
        'option_after' => '<br />'
    ), array(
        'replace' => __('Replace all existing email templates', $userMeta->name),
        'nothing' => __('Do not import any email template', $userMeta->name)
    ));
}

if (! empty($data['export'])) {
    $html .= $userMeta->createInput('includes[export]', 'radio', array(
        'value' => 'nothing',
        'label' => __('Import user export template', $userMeta->name),
        'id' => 'um_import_includes_export',
        'by_key' => true,
        'label_class' => 'pf_label',
        'option_after' => '<br />'
    ), array(
        'replace' => __('Replace all existing export templates', $userMeta->name),
        'add' => __('Add only new export template', $userMeta->name),
        'nothing' => __('Do not import any export template', $userMeta->name)
    ));
}

if (! empty($data['settings'])) {
    $html .= $userMeta->createInput('includes[settings]', 'radio', array(
        'value' => 'nothing',
        'label' => __('Import Settings', $userMeta->name),
        'id' => 'um_import_includes_settings',
        'by_key' => true,
        'label_class' => 'pf_label',
        'option_after' => '<br />'
    ), array(
        'replace' => __('Replace all existing UMP settings', $userMeta->name),
        'nothing' => __('Do not import any settings', $userMeta->name)
    ));
}

$html .= $userMeta->createInput('button', 'submit', array(
    'value' => __('Import', $userMeta->name),
    'class' => 'button-primary',
    'enclose' => 'p'
));

$html .= "</form>";

?>

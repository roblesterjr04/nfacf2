<?php
global $userMeta;
// Expected: $slug,

$html = null;

// $html .= sanitize_title($slug)

if (isset($before_form))
    $html .= $before_form;

$html .= $userMeta->createInput($slug . '[from_name]', 'text', array(
    'label' => __('From Name', $userMeta->name),
    'value' => @$from_name,
    'after' => ' <i>' . __('(Leave blank to use default)', $userMeta->name) . '</i>',
    'label_class' => 'pf_label',
    'style' => 'width:500px;',
    'enclose' => 'p'
));

$html .= $userMeta->createInput($slug . '[from_email]', 'text', array(
    'label' => __('From E-mail', $userMeta->name),
    'value' => @$from_email,
    'after' => ' <i>' . __('(Leave blank to use default)', $userMeta->name) . '</i>',
    'label_class' => 'pf_label',
    'style' => 'width:500px;',
    'enclose' => 'p'
));

$emailFormat = array(
    '' => null,
    'text/plain' => __('Plain Text', $userMeta->name),
    'text/html' => __('HTML', $userMeta->name)
);

$html .= $userMeta->createInput($slug . '[format]', "select", array(
    'label' => __('E-mail Format', $userMeta->name),
    'value' => @$format,
    'after' => ' <i>' . __('(Leave blank to use default)', $userMeta->name) . '</i>',
    'label_class' => 'pf_label',
    'enclose' => 'p',
    'by_key' => true
), $emailFormat);

$html .= $userMeta->createInput($slug . '[subject]', 'text', array(
    'label' => __('Subject', $userMeta->name),
    'value' => @$subject,
    'label_class' => 'pf_label',
    'style' => 'width:700px;',
    'enclose' => 'p'
));

$html .= $userMeta->createInput($slug . '[body]', 'textarea', array(
    'label' => __('Body', $userMeta->name),
    'value' => @$body,
    'label_class' => 'pf_label',
    'style' => 'width:700px;height:200px;',
    'enclose' => 'p'
));

if (isset($after_form))
    $html .= $after_form;

?>
<?php
namespace UserMeta\Field;

use UserMeta\Html\Html;

/**
 * Handling following pro fields:
 * country
 * number
 * image_url
 * html
 *
 * @author Khaled Hossain
 *        
 * @since 1.2.0
 */
class ProField extends Base
{

    protected function _configure_country()
    {
        $this->inputType = 'select';
    }

    protected function configure_country_()
    {
        $this->setDisabled();
    }

    protected function render_input_country()
    {
        global $userMeta;
        $options = $userMeta->countryArray();
        if (isset($this->field['country_selection_type']) && 'by_country_name' == $this->field['country_selection_type']) {
            $options = array_combine(array_values($options), array_values($options));
        }
        $options = array_merge([
            '' => ''
        ], $options);
        
        return Html::select($this->fieldValue, $this->inputAttr, $options);
    }

    protected function _configure_number()
    {
        $this->inputType = 'number';
        if (! empty($this->field['as_range'])) {
            $this->inputType = 'range';
        }
        
        if (! empty($this->field['integer_only'])) {
            $this->addValidation('custom[integer]');
        } else {
            $this->addValidation('custom[number]');
        }
        
        if (isset($this->field['min_number'])) {
            $this->addValidation("min[{$this->field['min_number']}]");
        }
        if (isset($this->field['max_number'])) {
            $this->addValidation("max[{$this->field['max_number']}]");
        }
    }

    protected function configure_number_()
    {
        if (empty($this->field['integer_only'])) {
            $this->inputAttr['step'] = 'any';
        }
        if (isset($this->field['min_number'])) {
            $this->inputAttr['min'] = $this->field['min_number'];
        }
        if (isset($this->field['max_number'])) {
            $this->inputAttr['max'] = $this->field['max_number'];
        }
        if (isset($this->field['step'])) {
            $this->inputAttr['step'] = $this->field['step'];
        }
    }

    protected function _configure_image_url()
    {
        $this->inputType = 'url';
        $this->addValidation('custom[url]');
    }

    protected function configure_image_url_()
    {
        if ($this->fieldValue) {
            $this->fieldResult = Html::img([
                'src' => $this->fieldValue
            ]);
        }
        $this->inputAttr['onblur'] = 'umShowImage(this)';
    }

    protected function render_input_html()
    {
        return $this->fieldValue ? html_entity_decode($this->fieldValue) : '';
    }
}
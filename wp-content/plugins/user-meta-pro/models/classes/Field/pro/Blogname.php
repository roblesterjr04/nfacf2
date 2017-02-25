<?php
namespace UserMeta\Field;

use UserMeta\Html\Html;

/**
 * Render bloname field.
 *
 * @author Khaled Hossain
 * @todo field_name is missing
 *      
 * @since 1.2.0
 */
class Blogname extends Base
{

    protected $requiredAttrs = [
        'id',
        'field_type'
    ];

    protected function isQualified()
    {
        parent::isQualified();
        
        if ('registration' != $this->actionType || ! is_multisite()) {
            $this->isQualified = false;
        }
        
        return $this->isQualified;
    }

    protected function _configure()
    {
        $this->field['field_name'] = 'blogname';
    }

    protected function configure_()
    {
        global $userMeta, $current_site;
        $this->label = ! is_subdomain_install() ? __('Site Name', $userMeta->name) : __('Site Domain', $userMeta->name);
        // inputBefore and inputAfter is not working as well as previous version (1.1.7)
        if (! is_subdomain_install())
            $this->inputBefore = '<span class="prefix_address">' . $current_site->domain . $current_site->path . '</span><br />';
        else
            $this->inputAfter = '<span class="suffix_address">.' . ($site_domain = preg_replace('|^www\.|', '', $current_site->domain)) . '</span><br />';
    }

    protected function renderInputWithLabel()
    {
        global $userMeta;
        $html = $this->renderLabel() . $this->renderInput();
        $html .= $userMeta->wp_nonce_field('blogname', 'um_newblog', false, false);
        $attr = $this->inputAttr;
        $attr['name'] = 'blog_title';
        $this->label = __('Site Title ', $userMeta->name);
        
        $html2 = $this->renderLabel();
        $html2 .= Html::text(null, $attr);
        
        $html .= "<p>$html2</p>";
        
        return $html;
    }

    public function render()
    {
        global $userMeta;
        
        if (! $this->isQualified)
            return;
        
        $active_signup = get_site_option('registration');
        if (! $active_signup) {
            $active_signup = 'all';
        }
        $active_signup = apply_filters('wpmu_active_signup', $active_signup); // return "all", "none", "blog" or "user"
        if (! ($active_signup == 'all' || $active_signup == 'blog')) {
            return $userMeta->showMessage(__('Site registration has been disabled.', $userMeta->name), 'info');
        }
        
        return parent::render();
    }
}

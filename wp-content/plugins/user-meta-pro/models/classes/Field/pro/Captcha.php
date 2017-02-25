<?php
namespace UserMeta\Field;

use UserMeta\Html\Html;

/**
 * Handling captcha field.
 *
 * @author Khaled Hossain
 * @todo Check title position feature
 *      
 * @since 1.2
 */
class Captcha extends Base
{

    private $siteKey;

    /**
     * Captcha library needs to add only once
     *
     * @var bool
     */
    private static $isCaptchaLibLoaded;

    /**
     * Rendering options for captcha
     *
     * @var array
     */
    private $_renderOptions = [];

    protected function isQualified()
    {
        parent::isQualified();
        if (! empty($this->field['registration_only']) && 'registration' != $this->actionType) {
            return $this->isQualified = false;
        }
        
        return $this->isQualified;
    }

    protected function _configure()
    {
        $this->setSiteKey();
        $this->setCaptchaRenderOptions();
    }

    private function setSiteKey()
    {
        global $userMeta;
        $general = $userMeta->getSettings('general');
        $this->siteKey = ! empty($general['recaptcha_public_key']) ? $general['recaptcha_public_key'] : null;
    }

    private function setCaptchaRenderOptions()
    {
        $this->_renderOptions = [
            'sitekey' => $this->siteKey ?: '6LfO8_8SAAAAAN8YGQhBqM6G0SWv0bhtZNep5Zgw',
            'theme' => ! empty($this->field['captcha_theme']) ? $this->field['captcha_theme'] : 'light',
            'type' => ! empty($this->field['captcha_type']) ? $this->field['captcha_type'] : 'image'
        ];
        
        if (! empty($this->field['render_options']) && is_array($this->field['render_options'])) {
            $this->_renderOptions = array_merge($this->_renderOptions, $this->field['render_options']);
        }
    }

    public function renderInput()
    {
        global $userMeta;
        $html = null;
        $html .= Html::div(null, [
            'class' => 'um_recaptcha'
        ]);
        
        /**
         * Add js outside jQuery ready block
         */
        $captchaCode = '<script type="text/javascript">
            var umReCaptchaCallback = function() {
                jQuery(".um_recaptcha").each(function() {
                    grecaptcha.render(this, ' . json_encode($this->_renderOptions) . ');
                });
            };
        </script>';
        \UserMeta\addFooterCode($captchaCode);
        
        /**
         * Add recaptcha library.
         * Captcha library needs to add only once.
         */
        $query = [
            'onload' => 'umReCaptchaCallback',
            'render' => 'explicit'
        ];
        if (! empty($this->field['captcha_lang'])) {
            $query['hl'] = $this->field['captcha_lang'];
        }
        if (! self::$isCaptchaLibLoaded) {
            $captchaLib = '<script src="https://www.google.com/recaptcha/api.js?' . http_build_query($query) . '" 
            async defer>
            </script>';
            \UserMeta\addFooterCode($captchaLib);
            self::$isCaptchaLibLoaded = true;
        }
        
        /**
         * Showing error
         */
        if (! $this->siteKey) {
            $text = __('Please set "Site key" and "Secret key" from admin panel by User Meta >> Settings Page', $userMeta->name);
            $html .= Html::tag('span', $text, [
                'style' => 'color:red;'
            ]);
        }
        
        return $html;
    }
}
<?php
namespace UserMeta;

class BackendProfileController
{

    function __construct()
    {
        add_action('show_user_profile', array(
            $this,
            'profileField'
        ));
        add_action('edit_user_profile', array(
            $this,
            'profileField'
        ));
        add_action('user_profile_update_errors', array(
            $this,
            'validateBackendFields'
        ), 10, 3);
    }

    function profileField($user)
    {
        global $userMeta, $pagenow;
        
        $userMeta->loadAllScripts();
        
        $this->_hideBackendFields();
        
        if ($pagenow == 'profile.php')
            $userID = $userMeta->userID();
        elseif ($pagenow == 'user-edit.php')
            $userID = esc_attr(@$_REQUEST['user_id']);
        
        if (empty($userID))
            return;
        
        $user = new \WP_User($userID);
        
        $settings = $userMeta->getData('settings');
        $fields = $userMeta->getData('fields');
        $backendFields = @$settings['backend_profile']['fields'];
        
        if (! is_array($backendFields))
            return;
        
        $i = 0;
        foreach ($backendFields as $fieldID) {
            if (empty($fields[$fieldID]))
                continue;
            
            if (! empty($fields[$fieldID]['admin_only'])) {
                if (! $userMeta->isAdmin())
                    continue;
            }
            
            $i ++;
            
            // if first rows is not section heading then initiate html table
            if (($i == 1) || (@$fields[$fieldID]['field_type'] != 'section_heading')) {
                echo "<table class=\"form-table\"><tbody>";
                $inTable = true;
            }
            
            if ($fields[$fieldID]['field_type'] == 'section_heading') {
                if (@$inTable) {
                    echo "</tbody></table>";
                    $inTable = false;
                }
                echo "<h3>" . $fields[$fieldID]['field_title'] . "</h3> <table class='form-table'><tbody>";
                $inTable = true;
                continue;
            }
            
            $fieldName = @$fields[$fieldID]['meta_key'];
            if (! $fieldName)
                $fieldName = $fields[$fieldID]['field_type'];
            
            $fields[$fieldID]['id'] = $fieldID;
            $fields[$fieldID]['field_name'] = $fieldName;
            $fields[$fieldID]['field_value'] = @$user->$fieldName;
            $fields[$fieldID]['title_position'] = 'hidden';
            
            $field = $fields[$fieldID];
            
            $html = (new Field(null, $field, [
                'unique_id' => 'backend_profile',
                'user_id' => $userID,
                'form_key' => 'um_backend_profile'
            ]))->render();
            
            $fieldLabel = ! empty($fields[$fieldID]['field_title']) ? $fields[$fieldID]['field_title'] : '';
            $fieldLabel = ($fields[$fieldID]['field_type'] == 'hidden') ? '' : $fieldLabel;
            
            echo "<tr><th><label for=\"um_field_$fieldID\">$fieldLabel</label></th><td>$html</td></tr>";
            // echo "<td>$html <span class=\"description\"></span></td></tr>";
        }
        
        if (@$inTable)
            echo "</tbody></table>";
        
        $js = '
                jQuery("#your-profile").validationEngine();
                jQuery(".um_rich_text").wysiwyg({initialContent:" "});
                umFileUploader();
                var form = document.getElementById("your-profile");
                form.encoding = "multipart/form-data";
                form.setAttribute("enctype", "multipart/form-data");
            ';
        
        addFooterJs($js);
        
        add_action('admin_footer', function () {
            printFooterJs();
        });
        return;
        
        // Not in use since 1.2.1
        ?>
<script type="text/javascript">
            jQuery(document).ready(function(){
                jQuery("#your-profile").validationEngine();
                jQuery(".um_rich_text").wysiwyg({initialContent:" "});
                umFileUploader();
                var form = document.getElementById("your-profile");
                form.encoding = 'multipart/form-data';
                form.setAttribute("enctype", "multipart/form-data");
            });
        	</script>
<?php
    }

    /**
     * Validate user's input.
     * Add error to $errors object.
     * Assign sanitized array to $userMetaCache->backend_profile_fields
     */
    function validateBackendFields(&$errors, $update, &$user)
    {
        if (! $update)
            return;
        
        $umUserInsert = new UserInsert();
        $umUserInsert->validateBackendFieldsProcess($user, $errors);
    }

    function _hideBackendFields()
    {
        global $userMeta;
        $backend_profile = $userMeta->getSettings('backend_profile');
        $hide_fields = @$backend_profile['hide_fields'];
        
        if (! is_array($hide_fields))
            return;
        
        foreach ($hide_fields as $id => $field)
            $userMeta->disableAdminRow($id);
    }
}
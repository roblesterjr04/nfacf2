<?php
namespace UserMeta;

class AjaxProModel
{

    function ajaxSaveEmailTemplate()
    {
        global $userMeta;
        if (! isset($_REQUEST))
            $userMeta->showError(__('Error occurred while updating', $userMeta->name));
        
        $data = $userMeta->arrayRemoveEmptyValue($_REQUEST);
        $data = $userMeta->removeNonArray($data);
        
        $userMeta->updateData('emails', stripslashes_deep($data));
        echo $userMeta->showMessage(__('Successfully saved.', $userMeta->name));
    }

    /**
     * Export UMP fields,forms,settings etc to txt file.
     */
    function ajaxExportUmp()
    {
        global $userMeta;
        
        $userMeta->verifyNonce();
        
        $result = array();
        $result['fields'] = $userMeta->getData('fields');
        
        if (is_array(@$_REQUEST['includes'])) {
            foreach ($_REQUEST['includes'] as $key) {
                $data = $userMeta->getData($key);
                if ($data)
                    $result[$key] = $data;
            }
        }
        
        $result = base64_encode(serialize($result));
        
        $siteName = str_replace(' ', '_', htmlspecialchars_decode(get_bloginfo('name')));
        $fileName = 'User_Meta_Pro_(' . $siteName . ')_' . date('Y-m-d_H-i') . '.txt';
        $userMeta->generateTextFile($fileName, $result);
        exit();
    }

    /**
     * Import UMP fields,forms,settings etc exported by UMP export tools.
     * Give user choice to replace existing data or add new data.
     */
    function ajaxImportUmp()
    {
        global $userMeta;
        
        $userMeta->verifyNonce();
        
        /**
         * Reading uploaded file and asssign file content to $data
         */
        if (empty($_REQUEST['filepath']))
            return $userMeta->showError(__('Something went wrong. File has not been uploaded', $userMeta->name));
        
        $uploads = $userMeta->determinFileDir($_REQUEST['filepath'], true);
        if (empty($uploads))
            return $userMeta->showError(__('Something went wrong. File has not been uploaded', $userMeta->name));
        
        $fullpath = $uploads['path'];
        
        // $uploads = wp_upload_dir();
        // $fullpath = $uploads[ 'basedir' ] . @$_REQUEST[ 'filepath' ];
        
        $data = file_get_contents($fullpath);
        $data = unserialize(base64_decode($data));
        
        /**
         * Run Import
         */
        if (isset($_REQUEST['do_import'])) {
            if (empty($_REQUEST['includes']) || ! is_array($_REQUEST['includes']))
                return $userMeta->showError(__('Nothing to import!', $userMeta->name));
            
            foreach ($_REQUEST['includes'] as $key => $action) {
                if (empty($data[$key]))
                    continue;
                
                if ($action == 'replace') {
                    $userMeta->updateData($key, $data[$key]);
                    $imported = true;
                } elseif ($action == 'add') {
                    if (is_array($data[$key])) {
                        $existingData = $userMeta->getData($key);
                        if (is_array($existingData))
                            $data[$key] = $existingData + $data[$key];
                        $userMeta->updateData($key, $data[$key]);
                        $imported = true;
                    }
                }
            }
            
            if (! empty($imported)) {
                $config = $userMeta->getData('config');
                $config = is_array($config) ? $config : array();
                $config['max_field_id'] = 0;
                $userMeta->updateData('config', $config);
                
                echo $userMeta->showMessage(__('Import completed.', $userMeta->name));
            } else
                echo $userMeta->showError(__('Nothing to import!', $userMeta->name));
            
            die();
        
        /**
         * Attempt for import
         */
        } elseif (@$_REQUEST['field_id'] == 'txt_upload_ump_import') {
            echo $userMeta->renderPro('importUmStep2', array(
                'data' => $data
            ), 'exportImport');
        }
    }

    /**
     * Perform user exports by ajax call also save user export template.
     */
    function ajaxUserExport()
    {
        global $userMeta;
        $userMeta->verifyNonce(true);
        
        try {
            $export = new UserExport();
            $export->execute();
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }
    
    // Not in use since 1.1.8rc1
    function ajaxUserExportOld()
    {
        global $userMeta, $wpdb, $blog_id;
        $userMeta->verifyNonce(true);
        
        $fieldsSelected = array();
        if (is_array(@$_REQUEST['fields']))
            $fieldsSelected = array_slice($_REQUEST['fields'], 0, $_REQUEST['field_count'], true);
        
        /**
         * Saving Data
         */
        if ($_REQUEST['action_type'] == 'save' || $_REQUEST['action_type'] == 'save_export') {
            $data = array();
            $data['fields'] = $fieldsSelected;
            $data['exclude_roles'] = @$_REQUEST['exclude_roles'];
            $data['start_date'] = @$_REQUEST['start_date'];
            $data['end_date'] = @$_REQUEST['end_date'];
            $data['orderby'] = @$_REQUEST['orderby'];
            $data['order'] = @$_REQUEST['order'];
            
            $export = $userMeta->getData('export');
            
            $export['user'][@$_REQUEST['form_id']] = $data;
            
            $userMeta->updateData('export', $export);
        }
        
        set_time_limit(3600);
        
        /**
         * Export to csv
         */
        if ($_REQUEST['action_type'] == 'export' || $_REQUEST['action_type'] == 'save_export') {
            $meta_query = array();
            if (is_array(@$_REQUEST['exclude_roles'])) {
                foreach (@$_REQUEST['exclude_roles'] as $role) {
                    $meta_query[] = array(
                        'key' => $wpdb->get_blog_prefix($blog_id) . 'capabilities',
                        'value' => "\"$role\"",
                        'compare' => "NOT LIKE"
                    );
                }
            }
            
            $args = array(
                'fields' => 'all_with_meta',
                'meta_query' => $meta_query,
                'orderby' => @$_REQUEST['orderby'],
                'order' => @$_REQUEST['order']
            );
            
            add_action('pre_user_query', array(
                $userMeta,
                'filterRegistrationDate'
            ));
            $users = get_users($args);
            remove_action('pre_user_query', array(
                $userMeta,
                'filterRegistrationDate'
            ));
            
            // $userMeta->dump($users);die();
            // / Add header row for csv
            $fileData = array();
            $fileData[] = $fieldsSelected;
            
            // / Add user data for csv
            foreach ($users as $user) {
                $userData = array();
                foreach ($fieldsSelected as $key => $val) {
                    $fieldValue = ! empty($user->$key) ? $user->$key : null;
                    if ($key == 'role')
                        $fieldValue = is_array($user->roles) ? array_shift($user->roles) : null;
                    if (is_array($fieldValue) || is_object($fieldValue))
                        $userData[$key] = implode(',', (array) $fieldValue);
                    else
                        $userData[$key] = $fieldValue;
                }
                $fileData[] = $userData;
            }
            
            $fileName = 'User Export (' . get_bloginfo('name') . ') ' . date('Y-m-d_H-i') . '.csv';
            $userMeta->generateCsvFile($fileName, $fileData);
        }
    }

    /**
     * Build user export forms in admin section and generate new form by ajax call.
     * verifyNonce is calling inside.
     */
    function ajaxUserExportForm($populateAll = false)
    {
        global $userMeta;
        
        $fieldsDefault = $userMeta->defaultUserFieldsArray();
        $fieldsDefault['user_avatar'] = __('Avatar', $userMeta->name);
        
        $fieldsMeta = array();
        $extraFields = $userMeta->getData('fields');
        if (is_array($extraFields)) {
            foreach ($extraFields as $data) {
                if (! empty($data['meta_key'])) {
                    $fieldTitle = ! empty($data['field_title']) ? $data['field_title'] : $data['meta_key'];
                    $fieldsMeta[$data['meta_key']] = $fieldTitle;
                }
            }
        }
        $fieldsAll = array_merge($fieldsDefault, $fieldsMeta);
        
        $roles = $userMeta->getRoleList();
        
        if ($populateAll) {
            $export = $userMeta->getData('export');
            $formsSaved = @$export['user'];
            if (is_array($formsSaved) && ! empty($formsSaved)) {
                foreach ($formsSaved as $formID => $formData) {
                    $fieldsSelected = $formData['fields'];
                    $fieldsAvailable = $fieldsAll;
                    if (is_array($fieldsSelected)) {
                        foreach ($fieldsSelected as $key => $val)
                            unset($fieldsAvailable[$key]);
                    }
                    
                    echo $userMeta->renderPro('exportForm', array(
                        'formID' => $formID,
                        'fieldsSelected' => $fieldsSelected,
                        'fieldsAvailable' => $fieldsAvailable,
                        'roles' => $roles,
                        'formData' => $formData
                    ), 'exportImport');
                }
                
                $break = true;
            }
            
            $newUserExportFormID = (int) $userMeta->maxKey($formsSaved) + 1;
            echo "<input type=\"hidden\" id=\"new_user_export_form_id\" value=\"$newUserExportFormID\" />";
        }
        
        // / For default or new form
        if (! @$break) {
            $formID = ! empty($_REQUEST['form_id']) ? $_REQUEST['form_id'] : 'default';
            if ($formID != 'default')
                $userMeta->verifyNonce(true);
            
            echo $userMeta->renderPro('exportForm', array(
                'formID' => $formID,
                'fieldsSelected' => array(),
                'fieldsAvailable' => $fieldsAll,
                'roles' => $roles
            ), 'exportImport');
        }
    }

    /**
     * Remove User Export Template by ajax call
     */
    function ajaxRemoveExportForm()
    {
        global $userMeta;
        $userMeta->verifyNonce(true);
        
        $export = $userMeta->getData('export');
        
        if (! empty($export['user'][$_REQUEST['form_id']]) && $export['user'][$_REQUEST['form_id']] != 'default') {
            unset($export['user'][$_REQUEST['form_id']]);
            $userMeta->updateData('export', $export);
        }
    }
}
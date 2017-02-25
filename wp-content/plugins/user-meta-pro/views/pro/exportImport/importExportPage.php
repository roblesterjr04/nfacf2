<?php
global $userMeta;
// Expected $csvCache, $maxSize
?>


<div class="wrap">
	<h1><?php _e( 'Export & Import', $userMeta->name ); ?></h1>
    <?php do_action( 'um_admin_notice' ); ?>
    <div id="dashboard-widgets-wrap">
		<div class="metabox-holder">
			<div id="um_admin_content">
                <?php
                
                $userMeta->renderPro("exportImportUm", array(
                    'csvCache' => $csvCache,
                    'maxSize' => $maxSize
                ), 'exportImport');
                
                $userMeta->renderPro("importStep1", array(
                    'csvCache' => $csvCache,
                    'maxSize' => $maxSize
                ), 'exportImport');
                
                echo $userMeta->ajaxUserExportForm(true);
                
                ?>

                <input type="button" class="button-primary"
					onclick="umNewUserExportForm(this);"
					value="<?php _e( 'New User Export Template', $userMeta->name ); ?>" />
			</div>

			<div id="um_admin_sidebar">
                <?php
                $panelArgs = [
                    'panel_class' => 'panel-default'
                ];
                echo UserMeta\panel(__('Get started', $userMeta->name), $userMeta->boxHowToUse(), $panelArgs);
                if (! @$userMeta->isPro)
                    echo UserMeta\panel(__('User Meta Pro', $userMeta->name), $userMeta->boxGetPro(), $panelArgs);
                echo UserMeta\panel('Shortcodes', $userMeta->boxShortcodesDocs(), $panelArgs);
                ?>
            </div>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function(){

    umFileUploader();

    jQuery('.um_dropme').sortable({
        connectWith: '.um_dropme',
        cursor: 'pointer'
    }).droppable({
        accept: '.button',
        activeClass: 'um_highlight',
    });

    jQuery(".um_date").datepicker({ dateFormat: 'yy-mm-dd', changeYear: true });
});
</script>

<?php get_header(); ?>

<?php theme_page_header(__('Sorry about this...', 'lb-theme'), __('It&apos;s not you, it&apos;s me.', 'lb-theme'), 400); ?>
<div class="container">
	<div class="content" style="text-align: center;">
			<h1>404...</h1>
			<h3><?php _e('The page you are looking for could not be found. <a href="'.site_url().'">Click Here</a> to return home.', 'lb-theme') ?></h3>
	</div>
</div>

<?php get_footer(); ?>
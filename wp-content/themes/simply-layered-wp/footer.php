<footer>
	<div class="container">
		<div class="row">
			<div class="col-sm-6">
				<?php dynamic_sidebar('footer-left'); ?>
			</div>
			<div class="col-sm-6">
				<p><?php _e('Proudly hosted by <a href="http://wordpress.org" target="_blank">Wordpress</a> | Theme by <a href="http://www.rmlsoft.com">RML Soft</a>', 'lb-theme') ?></p>
			</div>
		</div>
		<?php if (get_theme_mod('site_allow_analytics', false)) tracking_pixels(); ?>
	</div>
</footer>
<?php wp_footer() ?>
</body>
</html>
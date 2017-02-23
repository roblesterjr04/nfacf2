<form role="search" method="get" id="search" class="searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<input name="s" id="s" placeholder="<?php _e('Search', 'lb-theme') ?>" type="search" class="type-search" value="<?php echo get_search_query(); ?>">
	<input type="submit" id="searchsubmit" value="">
</form>
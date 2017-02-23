<?php get_header(); ?>

<?php 
	$is_blog_home = 'posts' == get_option( 'show_on_front' );
	$blog_page = $is_blog_home ? false : get_page(get_option('page_for_posts'));
	$ex = $blog_page ? apply_filters('the_excerpt', $blog_page->post_excerpt) : get_bloginfo('description');
		
	theme_page_header(is_search() ? __('Search Results', 'lb-theme') : ($blog_page ? apply_filters('the_title', $blog_page->post_title) : get_bloginfo('name')), $ex  ); ?>
	
<div class="container">
	<div class="row">
		<div class="col-md-8">
			<div class="content">
				<?php if (is_search()) : ?>
					<h2><?php echo __('Your Search: ', 'lb-theme') . get_search_query(); ?></h2>
					<hr />
				<?php endif; ?>
				
				<?php if (is_archive()) : ?>
					<h2><?php _e('Archive:', 'lb-theme') ?><?php single_month_title(' '); single_cat_title(' '); ?></h2>
					<hr />
				<?php endif; ?>
				
				<?php if (have_posts()) : while(have_posts()) : the_post() ?>
				
				<div <?php post_class() ?>>
					<h3 class="blog_title hidden-xs hidden-sm"><a href="<?php the_permalink() ?>"><?php the_title() ?></a></h3>
					<p class="blog_date hidden-xs hidden-sm"><?php the_time( get_option( 'date_format' ) ) ?>&nbsp;&mdash;&nbsp;<?php the_author() ?></p>
					<div class="row">
						<div class="col-md-2 visible-sm visible-xs">
							<?php theme_lb_top_background('#post'.get_the_ID().'bg', get_the_ID()) ?>
							<div id="post<?php the_ID() ?>bg" style="height: 100px; background-position: 50%; background-size: cover; margin-bottom: 15px; margin-top: 5px;"></div>
						</div>
						<div class="col-md-10">
							<h3 class="blog_title visible-sm visible-xs"><a href="<?php the_permalink() ?>"><?php the_title() ?></a></h3>
							<p class="blog_date visible-sm visible-xs"><?php the_time( get_option( 'date_format' ) ) ?>&nbsp;&mdash;&nbsp;<?php the_author() ?></p>
							<?php the_excerpt() ?>
							<?php the_tags('<p class="tags">', ', ', '</p>'); ?>
						</div>
						<div class="col-md-2 hidden-xs hidden-sm">
							<?php the_post_thumbnail('thumbnail'); ?>
						</div>
					</div>
					<hr/>
				</div>
				<?php endwhile; ?>
				<div class="row">
					<div class="col-xs-12">
						<?php posts_nav_link() ?>
					</div>
				</div>
				<?php else : ?>
				
				<h2><?php echo is_search() ? __('No results', 'lb-theme') : __('No posts exist', 'lb-theme') ?></h2>
				<p><?php echo is_search() ? __('There are no results for your search.', 'lb-theme') : __('The author of this blog has not posted anything yet.', 'lb-theme') ?></p>
				
				<?php endif; ?>
			</div>
		</div>
		<div class="col-md-4">
			<div class="content-sidebar">
				<?php dynamic_sidebar('right-sidebar'); ?>
			</div>
		</div>
	</div>
</div>


<?php get_footer(); ?>
<?php get_header(); ?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<?php 
	$ex = strip_tags(apply_filters('the_excerpt', $post->post_excerpt));
	$title = is_front_page() ? get_bloginfo('name') : apply_filters('the_title', $post->post_title);
	$description = is_front_page() ? get_bloginfo('description') : $ex;
	if (is_single()) {
		$is_blog_home = 'posts' == get_option( 'show_on_front' );
		$blog_page = $is_blog_home ? false : get_page(get_option('page_for_posts'));
		$title = apply_filters('the_title', $blog_page?$blog_page->post_title:get_bloginfo('title'));
		$description = strip_tags(apply_filters('the_excerpt', $blog_page?$blog_page->post_excerpt:get_bloginfo('description')));
	}
	theme_page_header( $title, $description ); ?>

<?php if (is_front_page()) : ?>
	<div class="homepage-widgets">
		<div class="container">
			<div class="row">
				<?php dynamic_sidebar('homepage-grid'); ?>
			</div>
		</div>
	</div>
<?php endif; ?>

<div class="container">
	<div class="row">
		<div class="col-sm-8">
			<div <?php post_class(array('content')) ?>>
				<?php if ($post->post_type == 'post') : ?>
					<h2><?php the_title() ?></h2>
					<p class="blog_date"><?php the_time( get_option( 'date_format' ) ) ?>&nbsp;&mdash;&nbsp;<?php the_author() ?></p>
					<?php the_tags('<p class="tags">', ', ', '</p>'); ?>
				<?php endif; ?>
				<?php the_content() ?>
				<div class="row page-links">
					<?php wp_link_pages(array('before'=>'', 'after'=>'', 'next_or_number'=>'next')); ?>
				</div>
				<?php if (is_single()) : ?>
					<hr/>
					<div class="row">
						<div class="col-xs-6"><?php previous_post_link(); ?></div>
						<div class="col-xs-6"><?php next_post_link(); ?></div>
					</div>
				<?php endif; ?>
				<hr/>
				<?php comment_form( lb_theme_comment_args() ); ?>
				<?php if ('open' == $post->comment_status) : ?>
					<?php comments_template(); ?>
				<?php endif; ?>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="content-sidebar">
				<?php dynamic_sidebar('right-sidebar'); ?>
			</div>
		</div>
	</div>
</div>

<?php endwhile; else : ?>

<?php endif; ?>

<?php get_footer(); ?>
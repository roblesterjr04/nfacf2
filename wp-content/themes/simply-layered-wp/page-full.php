<?php 
	
	/*
		Template Name: Full Width
	*/
	
	get_header(); ?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<?php 
	$ex = apply_filters('the_excerpt', $post->post_excerpt);
	
	theme_page_header(is_front_page() ? get_bloginfo('name') : apply_filters('the_title', $post->post_title), is_front_page() ? get_bloginfo('description') : $ex ); ?>

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
		<div class="col-sm-12">
			<div class="content">
				<?php the_content() ?>
			</div>
		</div>
	</div>
</div>

<?php endwhile; else : ?>



<?php endif; ?>

<?php get_footer(); ?>
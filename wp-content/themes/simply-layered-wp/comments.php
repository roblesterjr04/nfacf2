<?php
/**
 * The template for displaying Comments.
 *
 * The area of the page that contains both current comments
 * and the comment form. The actual display of comments is
 * handled by a callback to lb-theme_comment which is
 * located in the functions.php file.
 *
 * @package WordPress
 * @subpackage Twenty_Ten
 * @since Twenty Ten 1.0
 */
 $commenter = wp_get_current_commenter();
$req = get_option( 'require_name_email' );
$aria_req = ( $req ? " aria-required='true'" : '' );
 function change_submit_class($content) {
 	return str_replace('<input', '<input class="btn btn-primary"', $content);
 }
 add_filter('comment_form_field_submit','change_submit_class');
 
/*function gravatar($email) {
	return "http://www.gravatar.com/avatar/" . md5( strtolower( trim( $email ) ) );
}*/

?>

<div id="comments" class="row">

<?php if ( post_password_required() ) : ?>
				<div class="col-xs-12"><p><?php _e( 'This post is password protected. Enter the password to view any comments.', 'lb-theme' ); ?></p></div>
			</div><!-- #comments -->
<?php
		/* Stop the rest of comments.php from being processed,
		 * but don't kill the script entirely -- we still have
		 * to fully load the template.
		 */
		return;
	endif;
?>

<?php
	// You can start editing here -- including this comment!
	
?>
<div class="col-sm-12">
<?php if ( have_comments() ) : ?>

			<h5 id="comments-title"><?php
			printf( _n( 'One Response to %2$s', '%1$s Responses to %2$s', get_comments_number(), 'lb-theme' ),
			number_format_i18n( get_comments_number() ), '<em>' . get_the_title() . '</em>' );
			?></h5>

<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // Are there comments to navigate through? ?>
			<div class="navigation">
				<div class="nav-previous"><?php previous_comments_link( __( '<span class="meta-nav">&larr;</span> Older Comments', 'lb-theme' ) ); ?></div>
				<div class="nav-next"><?php next_comments_link( __( 'Newer Comments <span class="meta-nav">&rarr;</span>', 'lb-theme' ) ); ?></div>
			</div> <!-- .navigation -->
<?php endif; // check for comment navigation ?>

			<div class="commentlist">
				<?php
					/* Loop through and list the comments. Tell wp_list_comments()
					 * to use lb-theme_comment() to format the comments.
					 * If you want to overload this in a child theme then you can
					 * define lb-theme_comment() and that will be used instead.
					 * See lb-theme_comment() in lb-theme/functions.php for more.
					 */
					wp_list_comments( array( 'style' => 'div' ) );
				?>
			</div>

<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // Are there comments to navigate through? ?>
			<div class="navigation">
				<div class="nav-previous"><?php previous_comments_link( __( '<span class="meta-nav">&larr;</span> Older Comments', 'lb-theme' ) ); ?></div>
				<div class="nav-next"><?php next_comments_link( __( 'Newer Comments <span class="meta-nav">&rarr;</span>', 'lb-theme' ) ); ?></div>
			</div><!-- .navigation -->
<?php endif; // check for comment navigation ?>

	<?php
	/* If there are no comments and comments are closed, let's leave a little note, shall we?
	 * But we only want the note on posts and pages that had comments in the first place.
	 */
	if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="nocomments"><?php _e( 'Comments are closed.' , 'lb-theme' ); ?></p>
	<?php endif;  ?>

<?php endif; // end have_comments() ?>

<?php 

?>
</div>
</div><!-- #comments -->

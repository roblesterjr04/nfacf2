<?php if ('posts' == get_option( 'show_on_front' )) : ?>

<?php get_template_part('index') ?>

<?php else : ?>

<?php get_template_part('page-full') ?>

<?php endif; ?>
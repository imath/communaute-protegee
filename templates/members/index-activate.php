<?php
/**
 * Template used to output the activate registration step markup.
 *
 * @package   communaute-protegee
 * @subpackage \templates\members\index-activate
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

bp_get_template_part( 'members/communaute-protegee-header' ); ?>

<div id="activate">
	<h1><a href="<?php echo esc_url( communaute_protegee()->register_header_url ); ?>" title="" tabindex="-1"><?php echo esc_html( communaute_protegee()->register_header_text ); ?></a></h1>
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : ?>

			<?php the_post(); ?>

			<?php the_content(); ?>

		<?php endwhile; ?>
	<?php endif; ?>
</div>
<div class="clear"></div>

<?php bp_get_template_part( 'members/communaute-protegee-footer' ); ?>

<?php
/**
 * Template for the register page.
 *
 * @package   communaute-protegee
 * @subpackage \templates\members\index-register
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

bp_get_template_part( 'members/communaute-protegee-header' ); ?>

<div id="register" class="<?php echo sanitize_html_class( bp_get_current_signup_step() ); ?>">
	<h1><a href="<?php echo esc_url( communaute_protegee()->register_header_url ); ?>" tabindex="-1"><?php echo esc_html( communaute_protegee()->register_header_text ); ?></a></h1>

	<?php if ( communaute_protegee()->is_legacy ) : ?>
		<div id="template-notices" role="alert" aria-atomic="true">
			<?php do_action( 'template_notices' ); ?>
		</div>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : ?>

			<?php the_post(); ?>

			<?php the_content(); ?>

		<?php endwhile; ?>
	<?php endif; ?>
</div>
<div class="clear"></div>

<?php bp_get_template_part( 'members/communaute-protegee-footer' ); ?>

<?php
/**
 * Register template
 */
bp_get_template_part( 'members/communaute-protegee-header' ); ?>

<div id="register" class="<?php echo sanitize_html_class( bp_get_current_signup_step() ); ?>">
	<h1><a href="<?php echo esc_url( communaute_protegee()->register_header_url ); ?>" title="<?php echo esc_attr( communaute_protegee()->register_header_title ); ?>" tabindex="-1"><?php bloginfo( 'name' ); ?></a></h1>

	<?php if ( communaute_protegee()->is_legacy ) : ?>
		<div id="template-notices" role="alert" aria-atomic="true">
			<?php do_action( 'template_notices' ); ?>
		</div>
	<?php endif; ?>

	<?php if ( have_posts() ): while (have_posts()) : the_post(); ?>

		<?php the_content(); ?>

	<?php endwhile; endif; ?>
</div>
<div class="clear"></div>

<?php bp_get_template_part( 'members/communaute-protegee-footer' ); ?>

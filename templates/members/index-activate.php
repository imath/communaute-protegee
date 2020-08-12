<?php
/**
 * Register template
 */
bp_get_template_part( 'members/bp-restricted-community-header' ); ?>

<div id="activate">
	<h1><a href="<?php echo esc_url( communaute_protegee()->register_header_url ); ?>" title="<?php echo esc_attr( communaute_protegee()->register_header_title ); ?>" tabindex="-1"><?php bloginfo( 'name' ); ?></a></h1>
	<?php if ( have_posts() ): while (have_posts()) : the_post(); ?>

		<?php the_content(); ?>

	<?php endwhile; endif; ?>
</div>
<div class="clear"></div>

<?php bp_get_template_part( 'members/bp-restricted-community-footer' ); ?>

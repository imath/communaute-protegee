<?php
/**
 * Register template
 */
bp_get_template_part( 'members/communite-blindee-header' );
add_action( 'template_notices', 'communaute_blindee_activation_feedback', 0 );
?>

<div id="<?php echo ! bp_account_was_activated() ? 'activate' : 'login'; ?>">
	<h1><a href="<?php echo esc_url( communaute_blindee()->register_header_url ); ?>" title="<?php echo esc_attr( communaute_blindee()->register_header_title ); ?>" tabindex="-1"><?php bloginfo( 'name' ); ?></a></h1>

	<div id="template-notices" role="alert" aria-atomic="true">
		<?php do_action( 'template_notices' ); ?>
	</div>

	<?php

	/**
	 * Fires before the display of the member activation page content.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_before_activate_content' );

	if ( bp_account_was_activated() ) :
		wp_login_form( array( 'redirect' => home_url( '/' ), ) );
	?>

	<?php else : ?>

		<div id="buddypress">
			<div class="page" id="activate-page">
				<form action="" method="post" class="standard-form" id="activation-form">

					<p><?php _e( 'Please provide a valid activation key.', 'communaute-blindee' ); ?></p>

					<label for="key"><?php _e( 'Activation Key:', 'communaute-blindee' ); ?></label>
					<input type="text" name="key" id="key" value="<?php echo esc_attr( bp_get_current_activation_key() ); ?>" />

					<p class="submit">
						<input type="submit" name="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Activate', 'communaute-blindee' ); ?>" />
					</p>

				</form>
			</div>
		</div>

	<?php endif ;

	/**
	 * Fires after the display of the member activation page content.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_after_activate_content' ); ?>

</div>
<div class="clear"></div>

<?php bp_get_template_part( 'members/communite-blindee-footer' ); ?>

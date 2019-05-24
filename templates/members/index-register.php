<?php
/**
 * Register template
 */
bp_get_template_part( 'members/bp-restricted-community-header' );
add_action( 'template_notices', 'communaute_blindee_registration_feedback', 0 );
?>

<div id="register" class="<?php echo sanitize_html_class( bp_get_current_signup_step() ); ?>">
	<h1><a href="<?php echo esc_url( communaute_blindee()->register_header_url ); ?>" title="<?php echo esc_attr( communaute_blindee()->register_header_title ); ?>" tabindex="-1"><?php bloginfo( 'name' ); ?></a></h1>

	<div id="template-notices" role="alert" aria-atomic="true">
		<?php do_action( 'template_notices' ); ?>
	</div>

	<div id="buddypress">
		<form action="" name="signup_form" id="signup-form" class="standard-form signup-form" method="post">

			<?php if ( 'privacy-policy' === bp_get_current_signup_step() ) : ?>

				<div class="register-section privacy-policy">
					<h2 class="bp-heading"><?php esc_html_e( 'Privacy policy', 'buddypress' ); ?></h2>
					<p class="description instructions">
						<?php esc_html_e( 'To receive the privacy policy, fill the following field with your email address and hit the "Receive our Privacy policy by email" button.', 'communaute-blindee' ); ?>
						<?php esc_html_e( 'Your email will not be saved on our website.', 'communaute-blindee' ); ?>
					</p>

					<label for="privacy-policy-email"><?php _e( 'Email Address', 'buddypress' ); ?> <?php _e( '(required)', 'buddypress' ); ?></label>
					<input type="email" name="privacy_policy_email" id="privacy-policy-email" <?php bp_form_field_attributes( 'email', array( 'aria-required' => 'true' ) ); ?>/>
				</div>

				<p class="submit">
					<?php wp_nonce_field( 'send-privacy-policy', '_communaute_blindee_status' ); ?>
					<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Receive our Privacy policy by email', 'communaute-blindee' ); ?>">
				</p>

			<?php else : ?>

				<?php if ( 'request-details' === bp_get_current_signup_step() ) : ?>

					<div class="register-section default-profile" id="basic-details-section">
						<h2 class="bp-heading"><?php esc_html_e( 'Account Details', 'buddypress' ); ?></h2>

						<label for="signup_username"><?php _e( 'Username', 'buddypress' ); ?> <?php _e( '(required)', 'buddypress' ); ?></label>
						<?php

						/**
						 * Fires and displays any member registration username errors.
						 *
						 * @since 1.1.0
						 */
						do_action( 'bp_signup_username_errors' ); ?>
						<input type="text" name="signup_username" id="signup_username" value="<?php bp_signup_username_value(); ?>" <?php bp_form_field_attributes( 'username' ); ?>/>

						<label for="signup_email"><?php _e( 'Email Address', 'buddypress' ); ?> <?php _e( '(required)', 'buddypress' ); ?></label>
						<?php

						/**
						 * Fires and displays any member registration email errors.
						 *
						 * @since 1.1.0
						 */
						do_action( 'bp_signup_email_errors' ); ?>
						<input type="email" name="signup_email" id="signup_email" value="<?php bp_signup_email_value(); ?>" <?php bp_form_field_attributes( 'email' ); ?>/>

						<label for="signup_password"><?php _e( 'Choose a Password', 'buddypress' ); ?> <?php _e( '(required)', 'buddypress' ); ?></label>
						<?php

						/**
						 * Fires and displays any member registration password errors.
						 *
						 * @since 1.1.0
						 */
						do_action( 'bp_signup_password_errors' ); ?>

						<?php bp_get_template_part( 'members/register-password' ); ?>
					</div>

				<?php endif ;?>

			<?php endif ; ?>
		</form>
	</div>

</div>
<div class="clear"></div>

<?php bp_get_template_part( 'members/bp-restricted-community-footer' ); ?>

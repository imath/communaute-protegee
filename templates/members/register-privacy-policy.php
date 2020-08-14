<?php
/**
 * Template part to use the WordPress way of updating a user's password.
 *
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( 'privacy-policy' === bp_get_current_signup_step() ) : ?>

	<div class="register-section privacy-policy">
		<h2 class="bp-heading"><?php esc_html_e( 'Privacy policy', 'communaute-protegee' ); ?></h2>
		<p class="description instructions">
			<?php esc_html_e( 'To receive the privacy policy, fill the following field with your email address and hit the "Receive our Privacy policy by email" button.', 'communaute-protegee' ); ?>
			<?php esc_html_e( 'Your email will not be saved on our website.', 'communaute-protegee' ); ?>
		</p>

		<label for="privacy-policy-email"><?php _e( 'Email Address', 'communaute-protegee' ); ?> <?php _e( '(required)', 'communaute-protegee' ); ?></label>
		<input type="email" name="privacy_policy_email" id="privacy-policy-email" <?php bp_form_field_attributes( 'email', array( 'aria-required' => 'true' ) ); ?>/>
	</div>

	<p class="submit">
		<?php wp_nonce_field( 'send-privacy-policy', '_communaute_protegee_status' ); ?>
		<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Receive our Privacy policy by email', 'communaute-protegee' ); ?>">
	</p>

<?php elseif ( 'bp_before_registration_submit_buttons' === current_action() && communaute_protegee()->is_legacy ) : ?>

	<div class="privacy-policy-accept">
		<?php
		/**
		 * Fires and displays any member registration password confirmation errors.
		 *
		 * @since 1.1.0
		 */
		do_action( 'bp_signup_signup_privacy_policy_errors' ); ?>

		<label for="signup-privacy-policy-accept">
			<input type="hidden" name="signup-privacy-policy-check" value="1" />

			<?php /* translators: link to Privacy Policy */ ?>
			<input type="checkbox" name="signup-privacy-policy-accept" id="signup-privacy-policy-accept" required /> <?php printf( esc_html__( 'I have read and agree to this site\'s %s.', 'communaute-protegee' ), sprintf( '<a href="%s">%s</a>', esc_url( get_privacy_policy_url() ), esc_html__( 'Privacy Policy', 'communaute-protegee' ) ) ); ?>
		</label>
	</div>

<?php endif;

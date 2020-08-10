<?php
/**
 * Template part to use the WordPress way of updating a user's password.
 *
 * @since 1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( 'nouveau' === bp_get_theme_package_id() ) {
	return;
}
?>

<div class="user-pass1-wrap">
	<div class="wp-pwd">
		<div class="password-input-wrapper">
			<input type="password" data-reveal="1" data-pw="<?php echo esc_attr( wp_generate_password( 16 ) ); ?>" name="signup_password" id="pass1" class="input password-input" size="24" value="" autocomplete="off" aria-describedby="pass-strength-result" />
			<button type="button" class="button button-secondary wp-hide-pw hide-if-no-js">
				<span class="dashicons dashicons-hidden" aria-hidden="true"></span>
			</button>
		</div>
		<div id="pass-strength-result" class="hide-if-no-js" aria-live="polite"><?php esc_html_e( 'Strength indicator', 'bp-restricted-community' ); ?></div>
	</div>
	<div class="pw-weak">
		<label>
			<input type="checkbox" name="pw_weak" class="pw-checkbox" />
			<?php esc_html_e( 'Confirm use of weak password', 'bp-restricted-community' ); ?>
		</label>
	</div>
</div>

<p class="user-pass2-wrap">
	<label for="pass2"><?php esc_html_e( 'Confirm new password', 'bp-restricted-community' ); ?></label><br />
	<input type="password" name="signup_password_confirm" id="pass2" class="input" size="20" value="" autocomplete="off" />
</p>

<p class="description indicator-hint"><?php echo wp_get_password_hint(); ?></p>

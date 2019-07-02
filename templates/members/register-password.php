<?php
/**
 * Template part to use the WordPress way of updating a user's password.
 *
 * @since 1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

$is_admin = is_admin() && ! wp_doing_ajax();
?>

<div class="user-pass1-wrap">
	<?php if ( $is_admin ) : ?>
		<button type="button" class="button wp-generate-pw hide-if-no-js"><?php esc_html_e( 'Generate Password', 'communaute-blindee' ) ; ?></button>
	<?php endif ;?>

	<div class="wp-pwd">
		<div class="password-input-wrapper">
			<input type="password" data-reveal="1" data-pw="<?php echo esc_attr( wp_generate_password( 16 ) ); ?>" name="<?php echo $is_admin ? 'pass1' : 'signup_password'; ?>" id="pass1" class="input password-input" size="24" value="" autocomplete="off" aria-describedby="pass-strength-result" />
			<button type="button" class="button button-secondary wp-hide-pw hide-if-no-js">
				<span class="dashicons dashicons-hidden" aria-hidden="true"></span>
			</button>

			<?php if ( $is_admin ) : ?>
				<button type="button" class="button wp-cancel-pw hide-if-no-js" data-toggle="0" aria-label="Annuler la modification du mot de passe">
					<span class="dashicons dashicons-no" aria-hidden="true"></span>
					<span class="text">Annuler</span>
				</button>
			<?php endif ; ?>
		</div>
		<div id="pass-strength-result" class="hide-if-no-js" aria-live="polite"><?php esc_html_e( 'Strength indicator', 'communaute-blindee' ); ?></div>
	</div>
	<div class="pw-weak">
		<label>
			<input type="checkbox" name="pw_weak" class="pw-checkbox" />
			<?php esc_html_e( 'Confirm use of weak password', 'communaute-blindee' ); ?>
		</label>
	</div>
</div>

<p class="user-pass2-wrap">
	<label for="pass2"><?php esc_html_e( 'Confirm new password', 'communaute-blindee' ); ?></label><br />
	<input type="password" name="<?php echo $is_admin ? 'pass2' : 'signup_password_confirm'; ?>" id="pass2" class="input" size="20" value="" autocomplete="off" />
</p>

<?php if ( ! $is_admin ) : ?>
	<p class="description indicator-hint"><?php echo wp_get_password_hint(); ?></p>
<?php endif ; ?>

<?php
/**
 * Template part to use the WordPress way of updating a user's password.
 *
 * @package   communaute-protegee
 * @subpackage \templates\members\single\settings\general-password
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set passowrd field attributes.
$password_field_attributes = array(
	'data-pw'          => wp_generate_password( 24 ),
	'aria-describedby' => 'pass-strength-result',
);
?>

<div class="user-pass1-wrap">
	<button type="button" class="button wp-generate-pw">
		<?php esc_html_e( 'Générer un mot de passe', 'communaute-protegee' ); ?>
	</button>

	<div class="wp-pwd">
		<span class="password-input-wrapper">
			<input type="password" name="pass1" id="pass1" size="24" class="settings-input small password-entry" value="" <?php bp_form_field_attributes( 'password', $password_field_attributes ); ?> />
		</span>
		<button type="button" class="button wp-hide-pw" data-toggle="0" aria-label="<?php esc_attr_e( 'Cacher le mot de passe', 'communaute-protegee' ); ?>">
			<span class="dashicons dashicons-hidden" aria-hidden="true"></span>
			<span class="text bp-screen-reader-text"><?php esc_html_e( 'Cacher', 'communaute-protegee' ); ?></span>
		</button>
		<button type="button" class="button wp-cancel-pw" data-toggle="0" aria-label="<?php esc_attr_e( 'Annuler la modification du mot de passe', 'communaute-protegee' ); ?>">
			<span class="text"><?php esc_html_e( 'Annuler', 'communaute-protegee' ); ?></span>
		</button>
		<div id="pass-strength-result" aria-live="polite"></div>
	</div>
</div>

<div class="user-pass2-wrap">
	<label class="label" for="pass2"><?php esc_html_e( 'Répétez le nouveau mot de passe', 'communaute-protegee' ); ?></label>
	<input name="pass2" type="password" id="pass2" size="24" class="settings-input small password-entry-confirm" value="" <?php bp_form_field_attributes( 'password' ); ?> />
</div>

<div class="pw-weak">
	<label>
		<input type="checkbox" name="pw_weak" class="pw-checkbox" />
		<span id="pw-weak-text-label"><?php esc_html_e( 'Confirmez l’utilisation d’un mot de passe potentiellement faible.', 'communaute-protegee' ); ?></span>
	</label>
</div>

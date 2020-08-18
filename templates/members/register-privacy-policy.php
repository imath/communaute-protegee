<?php
/**
 * Template part to output privacy policy markup.
 *
 * @package   communaute-protegee
 * @subpackage \templates\members\register-privacy-policy
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( 'privacy-policy' === bp_get_current_signup_step() ) : ?>

	<div class="register-section privacy-policy">
		<h2 class="bp-heading"><?php esc_html_e( 'Politique de confidentialité', 'communaute-protegee' ); ?></h2>
		<p class="description instructions">
			<?php esc_html_e( 'Pour recevoir la politique de confidentialité, remplissez le champ ci-dessous avec votre adresse e-mail et cliquer sur le bouton « Recevoir notre politique de confidentialité par e-mail ».', 'communaute-protegee' ); ?>
			<?php esc_html_e( 'Votre adresse e-mail ne sera pas enregistrée dans la base de données de notre site.', 'communaute-protegee' ); ?>
		</p>

		<label for="privacy-policy-email"><?php esc_html_e( 'Adresse e-mail', 'communaute-protegee' ); ?> <?php esc_html_e( '(obligatoire)', 'communaute-protegee' ); ?></label>
		<input type="email" name="privacy_policy_email" id="privacy-policy-email" <?php bp_form_field_attributes( 'email', array( 'aria-required' => 'true' ) ); ?> required />
	</div>

	<p class="submit">
		<?php wp_nonce_field( 'send-privacy-policy', '_communaute_protegee_nonce' ); ?>
		<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Recevoir notre politique de confidentialité par e-mail', 'communaute-protegee' ); ?>">
	</p>

<?php elseif ( 'bp_before_registration_submit_buttons' === current_action() && communaute_protegee()->is_legacy ) : ?>

	<div class="privacy-policy-accept">
		<?php
		/**
		 * Fires and displays any member registration password confirmation errors.
		 *
		 * @since 1.1.0
		 */
		do_action( 'bp_signup_privacy_policy_errors' );
		?>

		<label for="signup-privacy-policy-accept">
			<input type="hidden" name="signup-privacy-policy-check" value="1" />

			<?php /* translators: link to Privacy Policy */ ?>
			<input type="checkbox" name="signup-privacy-policy-accept" id="signup-privacy-policy-accept" required /> <?php printf( esc_html__( 'J’ai lu et j’approuve la %s de ce site.', 'communaute-protegee' ), sprintf( '<a href="%s">%s</a>', esc_url( get_privacy_policy_url() ), esc_html__( 'politique de confidentialité', 'communaute-protegee' ) ) ); ?>
		</label>
	</div>

<?php endif; ?>

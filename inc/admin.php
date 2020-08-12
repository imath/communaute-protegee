<?php
/**
 * Administration.
 *
 * @package   communaute-protegee
 * @subpackage \inc\admin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Callback to display the `limited_email_domains` setting field.
 *
 * @since 1.0.0
 */
function communaute_protegee_restrictions_setting_field_callback() {
	$limited_email_domains = get_site_option( 'limited_email_domains' );
	$limited_email_domains = str_replace( ' ', "\n", $limited_email_domains );
	?>
	<textarea name="limited_email_domains" id="limited_email_domains" aria-describedby="limited-email-domains-desc" cols="45" rows="5"><?php echo esc_textarea( $limited_email_domains == '' ? '' : implode( "\n", (array) $limited_email_domains ) ); ?></textarea>
	<p class="description" id="limited-email-domains-desc">
		<?php esc_html_e( 'If you want to limit site registrations to certain domains. One domain per line.', 'bp-restricted-community' ) ?>
	</p>
	<?php
}

/**
 * Adds a setting field for non multisite configs to limit registrations to defined email domains.
 *
 * @since 1.0.0
 */
function communaute_protegee_restrictions_setting_field() {
	add_settings_field(
		'limited_email_domains',
		__( 'Limited Email Registrations', 'bp-restricted-community' ),
		'communaute_protegee_restrictions_setting_field_callback',
		'general',
		'default'
	);
}

/**
 * Allow the `limited_email_domains` for non multisite configs
 *
 * @since 1.0.0
 *
 * @param array $allowed_options The WordPress allowed options.
 * @return array The WordPress allowed options, including the
 *               `limited_email_domains` option for non multisite configs.
 */
function communaute_protegee_restrictions_add_option( $allowed_options = array() ) {
	if ( isset( $allowed_options['general'] ) ) {
		$allowed_options['general'] = array_merge(
			$allowed_options['general'],
			array( 'limited_email_domains' )
		);
	}

	return $allowed_options;
}

/**
 * Use JavaScript to move the `limited_email_domains` setting near the WordPress membership one.
 *
 * @since 1.0.0
 *
 * @param string $hook_suffix The suffix used by the dynamic hook.
 */
function communaute_protegee_move_restriction_settings( $hook_suffix = '' ) {
	if ( 'options-general.php' === $hook_suffix ) {
		$wp_scripts = wp_scripts();

		$data = $wp_scripts->get_data( 'common', 'data' );
		$data .= "\n
if ( 'undefined' !== jQuery ) {
jQuery( '#users_can_register' ).closest( 'tr' ).after( jQuery( '#limited_email_domains' ).closest( 'tr' ) );
}
		";

		$wp_scripts->add_data( 'common', 'data', $data );
	}
}

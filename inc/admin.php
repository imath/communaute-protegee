<?php
/**
 * Communauté Blindée fallback
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Callback to display the setting field
 *
 * @since 1.0.0
 */
function communaute_blindee_limited_email_domains_setting_field() {
	$limited_email_domains = get_site_option( 'limited_email_domains' );
	$limited_email_domains = str_replace( ' ', "\n", $limited_email_domains );

	?>
	<textarea name="limited_email_domains" id="limited_email_domains" aria-describedby="limited-email-domains-desc" cols="45" rows="5"><?php echo esc_textarea( $limited_email_domains == '' ? '' : implode( "\n", (array) $limited_email_domains ) ); ?></textarea>
	<p class="description" id="limited-email-domains-desc">
		<?php _e( 'If you want to limit site registrations to certain domains. One domain per line.', 'bp-restricted-community' ) ?>
	</p>
	<?php
}

/**
 * Add a setting field on non ms configs to limit registrations by email domains
 *
 * @since 1.0.0
 */
function communaute_blindee_email_restrictions_setting_field() {
	add_settings_field(
		'limited_email_domains',
		__( 'Limited Email Registrations', 'communaute-blindee' ),
		'communaute_blindee_limited_email_domains_setting_field',
		'general',
		'default'
	);
}

/**
 * Whitelist the limited_email_domains for non ms configs
 *
 * @since 1.0.0
 */
function communaute_blindee_email_restrictions_add_option( $whitelist_options = array() ) {
	if ( isset( $whitelist_options['general'] ) ) {
		$whitelist_options['general'] = array_merge(
			$whitelist_options['general'],
			array( 'limited_email_domains' )
		);
	}

	return $whitelist_options;
}

/**
 * Use Javascript to move the restriction setting near the Membership one for regulare WordPress
 *
 * @since 1.0.0
 */
function communaute_blindee_move_restriction_settings( $hook_suffix ) {
	if ( 'options-general.php' === $hook_suffix ) {
		wp_add_inline_script( 'common', '
			( function( $ ) {
				$( \'#users_can_register\' ).closest( \'tr\' ).after( $( \'#limited_email_domains\' ).closest( \'tr\' ) );
			} )( jQuery );
		' );
	}
}

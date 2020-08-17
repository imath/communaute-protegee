<?php
/**
 * Warnings.
 *
 * @package   communaute-protegee
 * @subpackage \inc\warnings
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays warning into Administration notices when a dependency is not met.
 *
 * @since 1.0.0
 */
function communaute_protegee_setup_warnings() {
	$warnings = array();

	// Get the Plugin's main instance.
	$cp = communaute_protegee();

	if ( ! communaute_protegee_bp_version_check() ) {
		/* translators: %s is the Plugin's name */
		$warnings[] = sprintf( __( '%s requires at least version 6.2.0 of BuddyPress.', 'communaute-protegee' ), $cp->name );
	}

	if ( ! communaute_protegee_rsa_version_check() ) {
		$warnings[] = sprintf(
			/* translators: 1 is the Plugin's name, 2 is the w.org link to the RSA plugin */
			__( '%1$s needs the <a href="%2$ss">Restricted Site Access</a> plugin (version >= 7.1.0) to be active.', 'communaute-protegee' ),
			$cp->name,
			esc_url( 'https://wordpress.org/plugins/restricted-site-access/' )
		);
	}

	if ( ! empty( $warnings ) ) :
		?>
		<div id="message" class="error notice is-dismissible">
			<?php foreach ( $warnings as $warning ) : ?>
				<p><?php echo wp_kses_data( $warning ); ?>
			<?php endforeach; ?>
		</div>
		<?php
	endif;
}

/**
 * Determines whick hook to use to display the warning messages.
 *
 * @since 1.0.0
 *
 * @param Communaute_Protegee $cp The main instance of the plugin.
 */
function communaute_protegee_setup_warnings_hook( $cp = null ) {
	$network_plugins = get_site_option( 'active_sitewide_plugins', array() );

	$action = 'admin_notices';
	if ( isset( $network_plugins[ $cp->basename ] ) && $network_plugins[ $cp->basename ] ) {
		$action = 'network_admin_notices';
	}

	add_action( $action, 'communaute_protegee_setup_warnings' );
}
add_action( 'communaute_protegee_setup_globals', 'communaute_protegee_setup_warnings_hook', 10, 1 );

<?php
/**
 * Hooks.
 *
 * @package   communaute-protegee
 * @subpackage \inc\hooks
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function communaute_protegee_setup_warnings() {
	$warnings = array();

	// Get the Plugin's main instance.
	$cp = communaute_protegee();

	if( ! $cp->version_check() ) {
		$warnings[] = sprintf( __( '%s requires at least version %s of BuddyPress.', 'bp-restricted-community' ), $cp->name, '2.4.0' );
	}

	if ( ! $cp->dependency_check() ) {
		$warnings[] = sprintf( __( '%s needs the <a href="%s">Restricted Site Access</a> plugin to be active.', 'bp-restricted-community' ), $cp->name, 'https://wordpress.org/plugins/restricted-site-access/' );
	}

	if ( ! empty( $warnings ) ) :
		?>
		<div id="message" class="error notice is-dismissible">
			<?php foreach ( $warnings as $warning ) : ?>
				<p><?php echo wp_kses_data( $warning ) ; ?>
			<?php endforeach ; ?>
		</div>
		<?php
	endif;
}

function communaute_protegee_setup_warnings_hook( $cp = null ) {
	$network_plugins = get_site_option( 'active_sitewide_plugins', array() );

	$action = 'admin_notices';
	if ( isset( $network_plugins[ $cp->basename ] ) && $network_plugins[ $cp->basename ] ) {
		$action = 'network_admin_notices';
	}

	add_action( $action, 'communaute_protegee_setup_warnings' );
}
add_action( 'communaute_protegee_setup_globals', 'communaute_protegee_setup_warnings_hook', 10, 1 );

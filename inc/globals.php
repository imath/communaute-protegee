<?php
/**
 * Globals.
 *
 * @package   communaute-protegee
 * @subpackage \inc\globals
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register plugin globals.
 *
 * @since 1.0.0
 */
function communaute_protegee_globals() {
	// Get the Plugin's main instance.
	$cp = communaute_protegee();

	// Version.
	$cp->version = '1.0.1';

	// Paths.
	$cp->dir           = plugin_dir_path( dirname( __FILE__ ) );
	$cp->inc_path      = plugin_dir_path( __FILE__ );
	$cp->templates_dir = $cp->dir . 'templates';

	// URLs.
	$cp->url    = plugins_url( '/', dirname( __FILE__ ) );
	$cp->js_url = trailingslashit( $cp->url . 'js' );

	// Plugin's identifying constants.
	$cp->domain   = 'communaute-protegee';
	$cp->name     = 'Communauté Protégée';
	$cp->basename = plugin_basename( $cp->dir . 'class-communaute-protegee.php' );

	// Plugin's config.
	$cp->rsa_options    = (array) get_option( 'rsa_options', array() );
	$cp->signup_allowed = bp_get_signup_allowed();
	$cp->use_site_icon  = apply_filters( 'communaute_protegee_use_site_icon', get_site_icon_url( 84, '', bp_get_root_blog_id() ) );
	$cp->is_legacy      = 'legacy' === bp_get_theme_package_id();

	/**
	 * Fires when Plugin globals are set.
	 *
	 * @since 1.0.0
	 *
	 * @param Communaute_Protegee $cp The main instance of the plugin.
	 */
	do_action_ref_array( 'communaute_protegee_setup_globals', array( $cp ) );

	// Load translations.
	load_plugin_textdomain( $cp->domain, false, trailingslashit( basename( $cp->dir ) ) . 'languages' );
}
add_action( 'bp_loaded', 'communaute_protegee_globals' );

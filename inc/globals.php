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
	$cp = communaute_protegee();

	$cp->version = '1.0.0-beta';

	// Paths.
	$cp->dir      = plugin_dir_path( dirname( __FILE__ ) );
	$cp->inc_path = plugin_dir_path( __FILE__ );

	// URLs.
	$cp->css_url = plugins_url( 'assets/css/', dirname( __FILE__ ) );

	// Legacy constants
	$cp->domain        = 'communaute-protegee';
	$cp->name          = 'Communauté Protégée';
	$cp->basename      = plugin_basename( $cp->dir . 'class-communaute-protegee.php' );
	$cp->plugin_dir    = $cp->dir;
	$cp->plugin_url    = plugins_url( '/', dirname( __FILE__ ) );
	$cp->lang_dir      = trailingslashit( $cp->plugin_dir . 'languages' );
	$cp->templates_dir = $cp->plugin_dir . 'templates';
	$cp->plugin_js     = trailingslashit( $cp->plugin_url . 'js' );
	$cp->minified      = '.min';
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		$cp->minified = '';
	}

	/** Plugin config ********************************************/
	$cp->rsa_options    = (array) get_option( 'rsa_options', array() );
	$cp->signup_allowed = bp_get_signup_allowed();
	$cp->use_site_icon  = apply_filters( 'communaute_protegee_use_site_icon', get_site_icon_url( 84 ) );
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

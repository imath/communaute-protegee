<?php
/**
 * Restricted Site Access companion to polish BuddyPress integration.
 *
 * @package   Communaute Blindee
 * @author    imath
 * @license   GPL-2.0+
 * @link      https://imathi.eu
 *
 * @buddypress-plugin
 * Plugin Name:       Communauté Blindée
 * Plugin URI:        https://github.com/imath/communaute-blindee
 * Description:       Compagnon de l’extension Restricted Site Access visant à « blinder » un site communautaire motorisé par BuddyPress.
 * Version:           1.0.0-beta
 * Author:            imath
 * Author URI:        https://github.com/imath
 * Text Domain:       communaute-blindee
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages/
 * GitHub Plugin URI: https://github.com/imath/communaute-blindee
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Communaute_Blindee' ) ) :
/**
 * Main Class
 *
 * @since 1.0.0
 */
final class Communaute_Blindee {
	/**
	 * Instance of this class.
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin
	 */
	private function __construct() {
		$this->setup_globals();
		$this->includes();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 */
	public static function start() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Sets some globals for the plugin
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {
		/** Plugin globals ********************************************/
		$this->version       = '1.0.0-beta';
		$this->domain        = 'communaute-blindee';
		$this->name          = 'Communauté Blindée';
		$this->file          = __FILE__;
		$this->basename      = plugin_basename( $this->file );
		$this->plugin_dir    = plugin_dir_path( $this->file );
		$this->plugin_url    = plugin_dir_url ( $this->file );
		$this->lang_dir      = trailingslashit( $this->plugin_dir . 'languages' );
		$this->templates_dir = $this->plugin_dir . 'templates';
		$this->plugin_js     = trailingslashit( $this->plugin_url . 'js' );

		/** Plugin config ********************************************/
		$this->required_bpdb_version     = 11105;
		$this->required_rsa_version      = '7.1.0';
		$this->rsa_options               = (array) get_option( 'rsa_options', array() );
		$this->minified                  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$this->encrypted_specific_fields = array();
	}

	/**
	 * Includes required files.
	 *
	 * @since 1.0.0
	 */
	private function includes() {
		require trailingslashit( $this->plugin_dir . 'inc' ) . 'functions.php';
		require trailingslashit( $this->plugin_dir . 'inc' ) . 'screens.php';

		if ( bp_is_active( 'xprofile' ) ) {
			require trailingslashit( $this->plugin_dir . 'inc' ) . 'personal-data.php';
		}

		if ( is_admin() ) {
			require trailingslashit( $this->plugin_dir . 'inc' ) . 'admin.php';
		}

		if ( bp_is_active( 'messages' ) ) {
			require trailingslashit( $this->plugin_dir . 'inc' ) . 'messages.php';
		}

		require trailingslashit( $this->plugin_dir . 'inc' ) . 'hooks.php';
	}
}

endif;

// Let's start !
function communaute_blindee() {
	return Communaute_Blindee::start();
}
add_action( 'bp_include', 'communaute_blindee', 8 );

<?php
/**
 * Enrichit l'extension Restricted Site Access pour protéger l'accès aux sites communautaires motorisés par BuddyPress.
 *
 * @package   Communauté Protégée
 * @author    imath
 * @license   GPL-2.0+
 * @link      https://imathi.eu
 *
 * @buddypress-plugin
 * Plugin Name:       Communauté Protégée
 * Plugin URI:        https://github.com/imath/communaute-protegee
 * Description:       Enrichit l'extension Restricted Site Access pour protéger l'accès aux sites communautaires motorisés par BuddyPress.
 * Version:           1.0.0-beta
 * Author:            imath
 * Author URI:        https://github.com/imath
 * Text Domain:       communaute-protegee
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages/
 * GitHub Plugin URI: https://github.com/imath/communaute-protegee
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Class
 *
 * @since 1.0.0
 */
final class Communaute_Protegee {
	/**
	 * Instance of this class.
	 *
	 * @var object $instance
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin
	 */
	private function __construct() {
		$this->inc();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 */
	public static function start() {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Loads needed files.
	 *
	 * @since 1.0.0
	 */
	private function inc() {
		$inc_path = plugin_dir_path( __FILE__ ) . 'inc/';

		require $inc_path . 'dependency.php';
		require $inc_path . 'globals.php';

		if ( communaute_protegee_bp_version_check() && communaute_protegee_rsa_version_check() ) {
			require $inc_path . 'functions.php';

			if ( is_admin() ) {
				require $inc_path . 'admin.php';
			}

			require $inc_path . 'hooks.php';

		} elseif ( is_admin() ) {
			require $inc_path . 'warnings.php';
		}
	}
}

/**
 * Launches Plugin inits.
 *
 * @since 1.0.0
 */
function communaute_protegee() {
	return Communaute_Protegee::start();
}
add_action( 'bp_include', 'communaute_protegee', 7 );

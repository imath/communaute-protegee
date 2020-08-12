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

// Exit if accessed directly
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
	 */
	protected static $instance = null;

	/**
	 * BuddyPress db version
	 */
	public static $bp_db_version_required = 12385;

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
		if ( null == self::$instance ) {
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

		require $inc_path . 'globals.php';

		if ( $this->version_check() && $this->dependency_check() ) {
			require $inc_path . 'functions.php';

			if ( is_admin() ) {
				require $inc_path . 'admin.php';
			}

			require $inc_path . 'hooks.php';

		} elseif ( is_admin() ) {
			require $inc_path . 'warnings.php';
		}
	}

	/**
	 * Checks BuddyPress version
	 *
	 * @since 1.0.0
	 */
	public function version_check() {
		// taking no risk
		if ( ! function_exists( 'bp_get_db_version' ) ) {
			return false;
		}

		return self::$bp_db_version_required <= bp_get_db_version();
	}

	/**
	 * Check if the Restricted Site Access plugin is activated
	 *
	 * @since 1.0.0
	 */
	public function dependency_check() {
		$dependency = class_exists( 'Restricted_Site_Access' );

		// Make sure Restricted Site Access version is 7.1.0 at least.
		if ( $dependency && defined( 'RSA_VERSION' ) ) {
			return version_compare( RSA_VERSION, '7.1.0', '>=' );
		} else {
			return false;
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

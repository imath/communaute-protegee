<?php
/**
 * Restrict the access to your BuddyPress community
 *
 * @package   BP Restricted Community
 * @author    imath
 * @license   GPL-2.0+
 * @link      https://imathi.eu
 *
 * @buddypress-plugin
 * Plugin Name:       BP Restricted Community
 * Plugin URI:        https://github.com/imath/bp-restricted-community
 * Description:       Restrict the access to your BuddyPress community
 * Version:           1.0.0-beta
 * Author:            imath
 * Author URI:        https://github.com/imath
 * Text Domain:       bp-restricted-community
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages/
 * GitHub Plugin URI: https://github.com/imath/bp-restricted-community
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Restricted_Community' ) ) :
/**
 * Main Class
 *
 * @since 1.0.0
 */
class BP_Restricted_Community {
	/**
	 * Instance of this class.
	 */
	protected static $instance = null;

	/**
	 * BuddyPress db version
	 */
	public static $bp_db_version_required = 11105;

	/**
	 * Initialize the plugin
	 */
	private function __construct() {
		$this->setup_globals();
		$this->setup_hooks();
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
		$this->domain        = 'bp-restricted-community';
		$this->name          = 'BP Restricted Community';
		$this->file          = __FILE__;
		$this->basename      = plugin_basename( $this->file );
		$this->plugin_dir    = plugin_dir_path( $this->file );
		$this->plugin_url    = plugin_dir_url ( $this->file );
		$this->lang_dir      = trailingslashit( $this->plugin_dir . 'languages' );
		$this->templates_dir = $this->plugin_dir . 'templates';
		$this->plugin_js     = trailingslashit( $this->plugin_url . 'js' );
		$this->minified      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		/** Plugin config ********************************************/
		$this->rsa_options    = (array) get_option( 'rsa_options', array() );
		$this->signup_allowed = bp_get_signup_allowed();
		$this->use_site_icon  = $this->get_site_icon();
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
		return class_exists( 'Restricted_Site_Access' );
	}

	/**
	 * Check if the plugin is activated on the network
	 *
	 * @since 1.0.0
	 */
	public function is_active_on_network() {
		$network_plugins = get_site_option( 'active_sitewide_plugins', array() );
		return ! empty( $network_plugins[ $this->basename ] );
	}

	/**
	 * Get the site icon to replace WordPress login logo
	 *
	 * @since 1.0.0
	 */
	public function get_site_icon() {
		return apply_filters( 'bp_restricted_community_use_site_icon', get_site_icon_url( 84 ) );
	}

	/**
	 * Set hooks
	 *
	 * @since 1.0.0
	 */
	private function setup_hooks() {
		// BuddyPress version & plugin dependency are ok
		if ( $this->version_check() && $this->dependency_check() ) {

			add_filter( 'site_icon_image_sizes',          array( $this, 'login_screen_icon_size' ), 10, 1 );
			add_action( 'bp_restricted_community_init',   array( $this, 'register_scripts' ), 1 );
			add_action( 'bp_restricted_community_footer', 'wp_print_footer_scripts', 20 );
			add_action( 'bp_restricted_community_head',   'wp_print_styles', 20 );

			if ( $this->signup_allowed ) {
				// Register the template directory
				add_action( 'bp_register_theme_directory', array( $this, 'register_template_dir' ) );

				// Add Registration restrictions by email domain for non ms configs
				if ( ! is_multisite() ) {
					add_filter( 'bp_admin_init',         array( $this, 'email_restrictions_setting_field' )        );
					add_filter( 'allowed_options',       array( $this, 'email_restrictions_add_option'    ), 10, 1 );
					add_action( 'admin_enqueue_scripts', array( $this, 'move_restriction_settings'        ), 10, 1 );
				}

				// Allow Register & Activate page to be displayed
				add_filter( 'restricted_site_access_is_restricted', array( $this, 'allow_bp_registration' ), 10, 1 );

				// Add a security check when a user registers
				add_action( 'bp_signup_validate', array( $this, 'validate_js_email' ) );

			// If signup is disable simply fix Restricted Site Access for BuddyPress specific case (if needed: Approach #4)
			} else {
				add_action( 'restrict_site_access_handling', array( $this, 'fix_restricted_site_access_for_buddypress' ), 10, 1 );
			}

			if ( true === (bool) $this->use_site_icon || ( $this->signup_allowed &&  ( empty( $this->rsa_options['approach'] ) || 1 === $this->rsa_options['approach'] ) ) ) {
				add_action( 'login_init',                   array( $this, 'enqueue_scripts' ) );
				add_action( 'bp_restricted_community_init', array( $this, 'enqueue_scripts' ) );
				add_action( 'bp_enqueue_scripts',           array( $this, 'enqueue_scripts' ), 40 );
			}

		// There's something wrong, inform the Administrator
		} else {
			$action = 'admin_notices';

			if ( $this->is_active_on_network() ) {
				$action = 'network_admin_notices';
			}

			add_action( $action, array( $this, 'admin_warning' ) );
		}

		// load the languages..
		add_action( 'bp_init', array( $this, 'load_textdomain' ), 5 );
	}

	/**
	 * Add Registration restrictions by email domain
	 *
	 * @since 1.0.0
	 */
	public function login_screen_icon_size( $icon_sizes = array() ) {
		return array_merge( $icon_sizes, array( 84 ) );
	}

	/**
	 * Register scripts for the specific templates
	 *
	 * @since 1.0.0
	 */
	public function register_scripts() {
		$scripts = apply_filters( 'bp_restricted_community_register_scripts', array(
			array(
				'handle' => 'bp-restricted-community-register',
				'file'   => $this->plugin_js . "register{$this->minified}.js",
				'deps'   => array( 'jquery', 'user-profile' ),
			),
		) );

		foreach ( (array) $scripts as $script ) {
			wp_register_script( $script['handle'], $script['file'], $script['deps'], $this->version, true );
		}
	}

	/**
	 * Register the template dir into BuddyPress template stack
	 *
	 * @since 1.0.0
	 */
	public function register_template_dir() {
		// After Theme, but before BP Legacy
		bp_register_template_stack( array( $this, 'template_dir' ),  13 );
	}

	/**
	 * Check if the current IP has access the way Restricted Site Access does
	 *
	 * @since 1.0.0
	 */
	public function current_ip_has_access() {
		$retval = false;

		if ( ! empty( $this->rsa_options['allowed'] ) ) {
			$remote_ip = $_SERVER['REMOTE_ADDR'];  //save the remote ip
			if ( strpos( $remote_ip, '.' ) ) {
				$remote_ip = str_replace( '::ffff:', '', $remote_ip ); //handle dual-stack addresses
			}
			$remote_ip = inet_pton( $remote_ip );

			// iterate through the allow list
			foreach( $this->rsa_options['allowed'] as $line ) {
				list( $ip, $mask ) = explode( '/', $line . '/128' );

				$mask = str_repeat( 'f', $mask >> 2 );

				switch( $mask % 4 ) {
					case 1:
						$mask .= '8';
						break;
					case 2:
						$mask .= 'c';
						break;
					case 3:
						$mask .= 'e';
						break;
				}

				$mask = pack( 'H*', $mask );

				// check if the masked versions match
				if ( ( inet_pton( $ip ) & $mask ) == ( $remote_ip & $mask ) ) {
					$retval = true;
					break;
				}
			}
		}

		return $retval;
	}

	/**
	 * Get the template dir
	 *
	 * @since 1.0.0
	 */
	public function template_dir() {
		if ( ! bp_is_register_page() && ! bp_is_activation_page() && ! bp_is_active( 'settings' ) && ! bp_is_user_settings_general() ) {
			return;
		}

		// Restrict Site Access is not using the login screen
		if ( ! empty( $this->rsa_options['approach'] ) && 1 !== $this->rsa_options['approach'] ) {
			return;
		}

		// If an IP is allowed it will get the regular BuddyPress Register/Activate templates
		if ( $this->current_ip_has_access() ) {
			return;
		}

		// Use the plugin's templates
		return apply_filters( 'bp_restricted_community_templates_dir', $this->templates_dir );
	}

	/**
	 * Add a setting field on non ms configs to limit registrations by email domains
	 *
	 * @since 1.0.0
	 */
	public function email_restrictions_setting_field() {
		add_settings_field(
			'limited_email_domains',
			__( 'Limited Email Registrations', 'bp-restricted-community' ),
			array( $this, 'limited_email_domains_setting_field' ),
			'general',
			'default'
		);
	}

	/**
	 * Allow the limited_email_domains for non ms configs
	 *
	 * @since 1.0.0
	 */
	public function email_restrictions_add_option( $allowed_options = array() ) {
		if ( isset( $allowed_options['general'] ) ) {
			$allowed_options['general'] = array_merge(
				$allowed_options['general'],
				array( 'limited_email_domains' )
			);
		}

		return $allowed_options;
	}

	/**
	 * Callback to display the setting field
	 *
	 * @since 1.0.0
	 */
	public function limited_email_domains_setting_field() {
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
	 * Use Javascript to move the restriction setting near the Membership one for regulare WordPress
	 *
	 * @since 1.0.0
	 */
	public function move_restriction_settings( $hook_suffix ) {
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

	/**
	 * Hook the Restrict Site Access approach #4 to make sure BuddyPress pages
	 * are not shown in this case
	 *
	 * @since 1.0.0
	 */
	public function fix_restricted_site_access_for_buddypress( $approach ) {
		if ( empty( $approach ) || 4 !== $approach || ! is_buddypress() ) {
			return;
		}

		$redirect = false;

		if ( ! empty( $this->rsa_options['page'] ) ) {
			$redirect = get_permalink( $this->rsa_options['page'] );
		}

		bp_core_redirect( $redirect );
	}

	/**
	 * Filter the Restrict Site Access main function and adapt it for our BuddyPress needs
	 *
	 * @since 1.0.0
	 */
	public function allow_bp_registration( $is_restricted ) {
		// Not restricted, do nothing
		if ( empty( $is_restricted ) ) {
			return $is_restricted;
		}

		// Bail if the current ip has access
		if ( $this->current_ip_has_access() ) {
			return true;
		}

		// BuddyPress is resetting the post query, so redirecting is better than editing the query vars like it's
		// done at line 171 of restricted_site_access.php. This is only needed when the option is set to a site's page.
		if ( ! empty( $this->rsa_options['approach'] ) && 4 === $this->rsa_options['approach'] && is_buddypress() ) {
			$this->fix_restricted_site_access_for_buddypress( $this->rsa_options['approach'] );

		// Login screen is the target of this plugin, allow BuddyPress registration and activation
		} elseif ( bp_is_register_page() || bp_is_activation_page() ) {
			$is_restricted = (bool) ! ( empty( $this->rsa_options['approach'] ) || 1 === $this->rsa_options['approach'] );
		}

		return $is_restricted;
	}

	/**
	 * Extra check for submitted registrations
	 *
	 * This is to try to prevent spam registrations
	 *
	 * @since 1.0.0
	 */
	public function validate_js_email() {
		$bp        = buddypress();
		$errors    = new WP_Error();
		$field_key = wp_hash( date( 'YMDH' ) );

		if ( empty( $_POST[ $field_key ] ) || empty( $_POST['signup_email'] ) || $_POST[ $field_key ] !== $_POST['signup_email'] ) {
			$errors->add( 'signup_email', __( 'We were not able to validate your email, please try again.', 'bp-restricted-community' ) );
			$bp->signup->errors['signup_email'] = $errors->errors['signup_email'][0];
		}
	}

	/**
	 * Locate the stylesheet to use for our custom templates
	 *
	 * You can override the one used by the plugin by putting yours
	 * inside yourtheme/css/bp-restricted-community-register.min.css
	 *
	 * @since 1.0.0
	 */
	public function locate_stylesheet( $stylesheet = '' ) {
		if ( empty( $stylesheet ) ) {
			return false;
		}

		$stylesheet_path = bp_locate_template( 'css/' . $stylesheet . $this->minified . '.css' );

		if ( 0 === strpos( $stylesheet_path, $this->plugin_dir ) ) {
			$stylesheet_uri = str_replace( $this->plugin_dir, $this->plugin_url, $stylesheet_path );
		} else {
			$stylesheet_uri = str_replace( WP_CONTENT_DIR, content_url(), $stylesheet_path );
		}

		return apply_filters( 'bp_restricted_community_locate_stylesheet', $stylesheet_uri, $stylesheet );
	}

	/**
	 * Enqueue Needed Scripts for our custom BuddyPress templates
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$style = '';

		if ( true === (bool) $this->use_site_icon ) {
			wp_add_inline_style( 'login', sprintf( '
				.login h1 a {
					background-image: none, url(%s);
				}
			', $this->use_site_icon ) );
		}

		if ( bp_is_register_page() || bp_is_activation_page() ) {
			// Clean up scripts
			foreach ( wp_scripts()->queue as $js_handle ) {
				wp_dequeue_script( $js_handle );
			}

			// Clean up styles
			foreach ( wp_styles()->queue as $css_handle ) {
				wp_dequeue_style( $css_handle );
			}

			// Enqueue style
			wp_enqueue_style( 'bp-restricted-community-register-style', $this->locate_stylesheet( 'bp-restricted-community-register' ), array( 'login' ), $this->version );
			wp_enqueue_script ( 'bp-restricted-community-register' );

			// The register form need some specific stuff
			if ( bp_is_register_page() && 'completed-confirmation' !== bp_get_current_signup_step() ) {
				add_filter( 'bp_xprofile_is_richtext_enabled_for_field', '__return_false' );
				wp_localize_script( 'bp-restricted-community-register', 'bpRestrictCommunity', array( 'field_key' => wp_hash( date( 'YMDH' ) ) ) );

				// Replace BuddyPress's way of setting the password by the WordPress's one.
				add_action( 'bp_account_details_fields', array( $this, 'register_with_wp_pwd_control' ) );
			}

			do_action( 'bp_restricted_community_enqueue_scripts' );

		} elseif ( 'legacy' === bp_get_theme_package_id() && bp_is_active( 'settings' ) && bp_is_user_settings_general() ) {
			wp_dequeue_script( 'bp-legacy-password-verify-password-verify' );
			wp_enqueue_script( 'user-profile' );

			// Remove BuddyPress Password fields.
			wp_add_inline_script( 'user-profile', '
				( function() {
					document.querySelector( \'#settings-form\' ).setAttribute( \'id\', \'your-profile\' );
					document.querySelector( \'#pass1\' ).remove();
					document.querySelector( \'label[for="pass1"] span\' ).remove();
					document.querySelector( \'#pass-strength-result\' ).remove();
					document.querySelector( \'#pass2\' ).remove();
					document.querySelector( \'label[for="pass2"]\' ).remove();
				} )();
			' );

			wp_add_inline_style( 'bp-parent-css', '
				body.settings #buddypress .wp-pwd button {
					padding: 6px;
					margin-top: 0;
					margin-bottom: 3px;
					vertical-align: middle;
				}
				body.buddypress.settings #pass1,
				body.buddypress.settings #pass1-text,
				#buddypress #pass-strength-result {
					width: 16em;
				}

				body.buddypress.settings #pass1-text,
				body.buddypress.settings .pw-weak,
				body.buddypress.settings #pass-strength-result {
					display: none;
				}

				body.buddypress.settings .show-password #pass1-text {
					display: inline-block;
				}

				body.buddypress.settings .show-password #pass1 {
					display: none;
				}

				body.buddypress.settings #your-profile #submit:disabled {
					color: #767676;
					opacity: 0.4;
				}

				body.buddypress.settings.js .wp-pwd,
				body.buddypress.settings.js .user-pass2-wrap {
					display: none;
				}

				body.buddypress.settings.no-js .wp-generate-pw,
				body.buddypress.settings.no-js .wp-cancel-pw,
				body.buddypress.settings.no-js .wp-hide-pw {
					display: none;
				}
			', 'after' );

			// Replace BuddyPress's way of setting the password by the WordPress's one.
			add_action( 'bp_core_general_settings_before_submit', array( $this, 'update_with_wp_pwd_control' ) );
		}
	}

	/**
	 * Use the WordPress control to set the password during registration.
	 *
	 * @since 1.0.0
	 */
	public function register_with_wp_pwd_control() {
		bp_get_template_part( 'members/register-password' );
	}

	/**
	 * Use the WordPress control to update the password from the user's profile.
	 *
	 * @since 1.0.0
	 */
	public function update_with_wp_pwd_control() {
		bp_get_template_part( 'members/single/settings/general-password' );
	}

	/**
	 * Display a message to admin in case config is not as expected
	 *
	 * @since 1.0.0
	 */
	public function admin_warning() {
		$warnings = array();

		if( ! $this->version_check() ) {
			$warnings[] = sprintf( __( '%s requires at least version %s of BuddyPress.', 'bp-restricted-community' ), $this->name, '2.4.0' );
		}

		if ( ! $this->dependency_check() ) {
			$warnings[] = sprintf( __( '%s needs the <a href="%s">Restricted Site Access</a> plugin to be active.', 'bp-restricted-community' ), $this->name, 'https://wordpress.org/plugins/restricted-site-access/' );
		}

		if ( ! empty( $warnings ) ) :
		?>
		<div id="message" class="error">
			<?php foreach ( $warnings as $warning ) : ?>
				<p><?php echo wp_kses_data( $warning ) ; ?>
			<?php endforeach ; ?>
		</div>
		<?php
		endif;
	}

	/**
	 * Loads the translation files
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {
		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/bp-restricted-community/' . $mofile;

		// Look in global /wp-content/languages/bp-restricted-community folder
		load_textdomain( $this->domain, $mofile_global );

		// Look in local /wp-content/plugins/bp-restricted-community/languages/ folder
		load_textdomain( $this->domain, $mofile_local );
	}
}

endif;

// Let's start !
function bp_restricted_community() {
	return BP_Restricted_Community::start();
}
add_action( 'bp_include', 'bp_restricted_community', 9 );

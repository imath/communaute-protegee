<?php
/**
 * Communauté Blindée hooks
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

function communaute_blindee_hooks() {
	if ( communaute_blindee_bp_version_check() && communaute_blindee_dependency_check() && communaute_blindee_required_setup() ) {

		add_action( 'communaute_blindee_init',   'communaute_blindee_register_scripts',        1    );
		add_action( 'communaute_blindee_footer', 'wp_print_footer_scripts',                   20    );
		add_action( 'communaute_blindee_head',   'wp_print_styles',                           20    );

		add_filter( 'site_icon_image_sizes',     'communaute_blindee_login_screen_icon_size', 10, 1 );

		if ( bp_get_signup_allowed() ) {
			// Register the template directory
			add_action( 'bp_register_theme_directory', 'communaute_blindee_register_templates_dir' );

			// Add Registration restrictions by email domain for non ms configs
			if ( ! is_multisite() ) {
				add_action( 'bp_admin_init',         'communaute_blindee_email_restrictions_setting_field'        );
				add_filter( 'whitelist_options',     'communaute_blindee_email_restrictions_add_option'   , 10, 1 );
				add_action( 'admin_enqueue_scripts', 'communaute_blindee_move_restriction_settings'       , 10, 1 );
			}

			// Allow Register & Activate page to be displayed
			add_filter( 'restricted_site_access_is_restricted', 'communaute_blindee_allow_bp_registration', 10, 1 );

			// Add a security check when a user registers
			add_action( 'bp_signup_validate', 'communaute_blindee_validate_js_email' );

			// Neutralize the BuddyPress admin notification about newly regitered users to use a custom one.
			add_filter( 'bp_core_send_user_registration_admin_notification', '__return_false' );
		}

		if ( communaute_blindee_get_site_icon() || ( bp_get_signup_allowed() && communaute_blindee_rsa_approach_for_signups() ) ) {
			foreach ( array( 'login_init', 'communaute_blindee_init', 'bp_enqueue_scripts' ) as $hook ) {
				$priority = 10;

				if ( 'bp_enqueue_scripts' === $hook ) {
					$priority = 40;
				}

				add_action( $hook, 'communaute_blindee_enqueue_scripts', $priority );
			}
		}

	} else {
		require trailingslashit( dirname( __FILE__ ) ) . 'fallback.php';
	}
}
add_action( 'bp_include', 'communaute_blindee_hooks', 9 );

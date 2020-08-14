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

function communaute_protegee_setup_hooks( $cp = null ) {
	add_action( 'wp_head', 'wp_no_robots', 1 );
	add_action( 'communaute_protegee_head', 'wp_no_robots', 1 );

	// Adds a new size to site icon.
	add_filter( 'site_icon_image_sizes', 'communaute_protegee_login_screen_add_icon_size', 10, 1 );

	// Loads CSS & JavaScripts into Plugin templates.
	add_action( 'communaute_protegee_init', 'communaute_protegee_register_scripts', 1 );
	add_action( 'communaute_protegee_footer', 'wp_print_footer_scripts', 20 );
	add_action( 'communaute_protegee_head', 'wp_print_styles', 20 );

	if ( isset( $cp->signup_allowed ) && $cp->signup_allowed ) {
		// Register the template directory.
		add_action( 'bp_register_theme_directory', 'communaute_protegee_register_template_dir' );

		// Add Registration restrictions by email domain for non Multisite configs.
		if ( ! is_multisite() && is_admin() ) {
			add_filter( 'bp_admin_init', 'communaute_protegee_restrictions_setting_field' );
			add_filter( 'allowed_options', 'communaute_protegee_restrictions_add_option', 10, 1 );
			add_action( 'admin_enqueue_scripts', 'communaute_protegee_move_restriction_settings', 10, 1 );
		}

		// Allow Register & Activate page to be displayed.
		add_filter( 'restricted_site_access_is_restricted', 'communaute_protegee_allow_bp_registration', 10, 2 );

		// Add a security check when a user registers.
		add_action( 'bp_signup_validate', 'communaute_protegee_js_validate_email' );
	}

	if ( true === (bool) $cp->use_site_icon || ( $cp->signup_allowed && ( empty( $cp->rsa_options['approach'] ) || 1 === $cp->rsa_options['approach'] ) ) ) {
		add_action( 'login_init', 'communaute_protegee_enqueue_scripts' );
		add_action( 'communaute_protegee_init', 'communaute_protegee_enqueue_scripts' );
		add_action( 'bp_enqueue_scripts', 'communaute_protegee_enqueue_scripts', 40 );
	}

	add_action( 'bp_before_registration_submit_buttons', 'communaute_protegee_privacy_policy_signup_step' );
	add_action( 'bp_custom_signup_steps', 'communaute_protegee_privacy_policy_signup_step' );
}
add_action( 'communaute_protegee_setup_globals', 'communaute_protegee_setup_hooks', 10, 1 );
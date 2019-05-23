<?php
/**
 * Communauté Blindée functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;


function communaute_prive_privacy_step() {
	if ( ! bp_is_current_component( 'register' ) || ! bp_current_action() ) {
		return;
	}

	$page           = get_page_by_path( bp_current_action() );
	$policy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );

	if ( ! is_a( $page, 'WP_Post' ) || $policy_page_id !== (int) $page->ID ) {
		bp_core_redirect( wp_login_url() );
	}

	bp_core_load_template( apply_filters( 'bp_core_template_register', array( 'register', 'registration/register' ) ) );
}
add_action( 'bp_screens', 'communaute_prive_privacy_step' );

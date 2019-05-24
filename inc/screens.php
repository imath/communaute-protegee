<?php
/**
 * Communauté Blindée BuddyPress screen functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;


function communaute_blindee_privacy_step() {
	if ( ! bp_is_current_component( 'register' ) || ! bp_current_action() ) {
		return;
	}

	$page           = get_page_by_path( bp_current_action() );
	$policy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );

	if ( ! is_a( $page, 'WP_Post' ) || $policy_page_id !== (int) $page->ID ) {
		bp_core_redirect( wp_login_url() );
    }

    $bp = buddypress();
    if ( ! isset( $bp->signup ) ) {
        $bp->signup = (object) array( 'step' => null );
    }

    $bp->signup->step = 'privacy-policy';

    do_action( 'communaute_blindee_privacy_step', $page );

	bp_core_load_template( array( 'register', 'registration/register' ) );
}
add_action( 'bp_screens', 'communaute_blindee_privacy_step' );

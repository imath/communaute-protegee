<?php
/**
 * Communauté Blindée functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get email templates
 *
 * @since 1.0.0
 *
 * @return array An associative array containing the email type and the email template data.
 */
function communaute_blindee_get_emails() {
	return apply_filters( 'communaute_blindee_get_emails', array(
		'communaute-blindee-invite' => array(
			'description' => __( 'An invite to join the site', 'communaute-blindee' ),
			'term_id'     => 0,
			'post_title'   => __( '[{{{site.name}}}] Join our site', 'communaute-blindee' ),
			'post_content' => __( "{{{communaute_blindee.content}}}\n\nTo join {{{site.name}}}, please visit: <a href=\"{{{communaute_blindee.url}}}\">{{communaute_blindee.title}}</a>.", 'communaute-blindee' ),
			'post_excerpt' => __( "{{{communaute_blindee.content}}}\n\nTo join {{{site.name}}}, please visit: \n\n{{{communaute_blindee.url}}}.", 'communaute-blindee' ),
		),
		'communaute-blindee-privacy-policy' => array(
			'description' => __( 'A user requested to receive the privacy policy', 'communaute-blindee' ),
			'term_id'     => 0,
			'post_title'   => __( '[{{{site.name}}}] here is our privacy policy', 'communaute-blindee' ),
			'post_content' => __( "{{{communaute_blindee.privacy_policy}}}\n\nTo join {{{site.name}}}, please visit: <a href=\"{{{ommunaute_blindee.url}}}\">{{communaute_blindee.title}}</a>.", 'communaute-blindee' ),
			'post_excerpt' => __( "{{{communaute_blindee.privacy_policy}}}\n\nTo join {{{site.name}}}, please visit: \n\n{{{ommunaute_blindee.url}}}.", 'communaute-blindee' ),
		),
	) );
}

/**
 * Install/Reinstall email templates
 *
 * @since 1.0.0
 */
function communaute_blindee_install_emails() {
	$switched = false;

	// Switch to the root blog, where the email posts live.
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
		$switched = true;
	}

	// Get Emails
	$email_types = communaute_blindee_get_emails();

	// Set email types
	foreach( $email_types as $email_term => $term_args ) {
		if ( term_exists( $email_term, bp_get_email_tax_type() ) ) {
			$email_type = get_term_by( 'slug', $email_term, bp_get_email_tax_type() );

			$email_types[ $email_term ]['term_id'] = $email_type->term_id;
		} else {
			$term = wp_insert_term( $email_term, bp_get_email_tax_type(), array(
				'description' => $term_args['description'],
			) );

			$email_types[ $email_term ]['term_id'] = $term['term_id'];
		}

		// Insert Email templates if needed
		if ( ! empty( $email_types[ $email_term ]['term_id'] ) && ! is_a( bp_get_email( $email_term ), 'BP_Email' ) ) {
			wp_insert_post( array(
				'post_status'  => 'publish',
				'post_type'    => bp_get_email_post_type(),
				'post_title'   => $email_types[ $email_term ]['post_title'],
				'post_content' => $email_types[ $email_term ]['post_content'],
				'post_excerpt' => $email_types[ $email_term ]['post_excerpt'],
				'tax_input'    => array(
					bp_get_email_tax_type() => array( $email_types[ $email_term ]['term_id'] )
				),
			) );
		}
	}

	if ( $switched ) {
		restore_current_blog();
	}
}
add_action( 'bp_core_install_emails', 'communaute_blindee_install_emails' );

function communaute_blindee_update() {
	if ( (int) get_current_blog_id() !== (int) bp_get_root_blog_id() ) {
		return;
	}

	$db_version      = bp_get_option( 'communaute-blindee-version', 0 );
	$current_version = communaute_blindee()->version;

	// First install
	if ( ! $db_version ) {
		// Make sure to install emails only once!
		remove_action( 'bp_core_install_emails', 'communaute_blindee_install_emails' );

		// Install emails
		communaute_blindee_install_emails();
	}

	// Update
	if ( version_compare( $db_version, $current_version, '<' ) ) {
		// Update the db version
		bp_update_option( 'communaute-blindee-version', $current_version );
	}
}
add_action( 'bp_admin_init', 'communaute_blindee_update', 20 );

function communaute_blindee_set_email_content( $content = '' ) {
	// Make sure the Post won't be embed.
	add_filter( 'pre_oembed_result', '__return_false' );
	$content   = apply_filters( 'the_content', $content );
	remove_filter( 'pre_oembed_result', '__return_false' );

	// Make links clickable
	return make_clickable( $content );
}

function communaute_blindee_privacy_policy_email( WP_Post $privacy_page ) {
	if ( ! isset( $_POST['privacy_policy_email'] ) ) {
		return;
	}

	// Check the nonce.
	check_admin_referer( 'send-privacy-policy', '_communaute_blindee_status' );

	$email        = wp_unslash( $_POST['privacy_policy_email'] );
	$is_valid     = bp_core_validate_email_address( $email );
	$redirect     = remove_query_arg( array( '_communaute_blindee_status', '_communaute_blindee_nonce' ), wp_get_referer() );
	$register_url = bp_get_signup_page();

	if ( true !== $is_valid ) {
		bp_core_redirect( add_query_arg( '_communaute_blindee_status', array_keys( $is_valid ), $redirect ) );
	}

	if ( bp_send_email( 'communaute-blindee-privacy-policy', $email, array(
		'tokens' => array(
			'communaute_blindee.privacy_policy' => communaute_blindee_set_email_content( $privacy_page->post_content ),
			'communaute_blindee.url'            => $register_url,
			'communaute_blindee.title'          => __( 'the registration page', 'communaute-blindee' ),
		),
	) ) ) {
		bp_core_redirect( add_query_arg( '_communaute_blindee_status', 'privacy-policy-sent', $register_url ) );
	} else {
		bp_core_redirect( add_query_arg( '_communaute_blindee_status', 'privacy-policy-not-sent', $register_url ) );
	}
}
add_action( 'communaute_blindee_privacy_step', 'communaute_blindee_privacy_policy_email', 10, 1 );

<?php
/**
 * Communauté Blindée functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Loads translation.
 *
 * @since 1.0.0
 */
function communaute_blindee_load_textdomain() {
	$communaute_blindee = communaute_blindee();
	load_plugin_textdomain( communaute_blindee()->domain, false, trailingslashit( basename( communaute_blindee()->plugin_dir ) ) . 'languages' );
}
add_action( 'bp_loaded', 'communaute_blindee_load_textdomain' );

/**
 * Checks BuddyPress version is supported.
 *
 * @since 1.0.0
 */
function communaute_blindee_bp_version_check() {
	// taking no risk
	if ( ! function_exists( 'bp_get_db_version' ) ) {
		return false;
	}

	return communaute_blindee()->required_bpdb_version <= bp_get_db_version();
}

/**
 * Check if the Restricted Site Access plugin is activated.
 *
 * @since 1.0.0
 */
function communaute_blindee_dependency_check() {
	$dependency = class_exists( 'Restricted_Site_Access' );

	if ( $dependency && defined( 'RSA_VERSION' ) ) {
		return version_compare( communaute_blindee()->required_rsa_version, RSA_VERSION, '>=' );
	} else {
		return false;
	}
}

function communaute_blindee_required_setup() {
	$setup_ok = ! bp_get_signup_allowed();

	if ( ! $setup_ok ) {
		$setup_ok = is_multisite() ? true : defined( 'BP_SIGNUPS_SKIP_USER_CREATION' ) && BP_SIGNUPS_SKIP_USER_CREATION;
	}

	return $setup_ok;
}

/**
 * Check if the plugin is activated on the network.
 *
 * @since 1.0.0
 *
 * @return boolean True if active on network. False otherwise.
 */
function communaute_blindee_is_active_on_network() {
	$communaute_blindee = communaute_blindee();

	$network_plugins = get_site_option( 'active_sitewide_plugins', array() );
	return isset( $network_plugins[ $communaute_blindee->basename ] );
}

function communaute_blindee_rsa_approach_for_signups() {
	$communaute_blindee = communaute_blindee();
	return isset( $communaute_blindee->rsa_options['approach'] ) && 1 === $communaute_blindee->rsa_options['approach'];
}

/**
 * Register scripts for the specific templates.
 *
 * @since 1.0.0
 */
function communaute_blindee_register_scripts() {
	$communaute_blindee = communaute_blindee();

	$scripts = apply_filters( 'communaute_blindee_register_scripts', array(
		array(
			'handle' => 'communaute-blindee-register',
			'file'   => $communaute_blindee->plugin_js . "register{$communaute_blindee->minified}.js",
			'deps'   => array( 'jquery', 'user-profile' ),
		),
	) );

	foreach ( (array) $scripts as $script ) {
		wp_register_script( $script['handle'], $script['file'], $script['deps'], $communaute_blindee->version, true );
	}
}

/**
 * Locate the stylesheet to use for our custom templates
 *
 * You can override the one used by the plugin by putting yours
 * inside yourtheme/css/communaute-blindee-register.min.css
 *
 * @since 1.0.0
 *
 * @param string  $stylesheet The stylesheet name.
 * @return string             The stylesheet URI.
 */
function communaute_blindee_locate_stylesheet( $stylesheet = '' ) {
	if ( ! $stylesheet ) {
		return '';
	}

	$communaute_blindee = communaute_blindee();
	$stylesheet_path    = bp_locate_template( 'css/' . $stylesheet . $communaute_blindee->minified . '.css' );

	if ( 0 === strpos( $stylesheet_path, $communaute_blindee->plugin_dir ) ) {
		$stylesheet_uri = str_replace( $communaute_blindee->plugin_dir, $communaute_blindee->plugin_url, $stylesheet_path );
	} else {
		$stylesheet_uri = str_replace( WP_CONTENT_DIR, content_url(), $stylesheet_path );
	}

	return apply_filters( 'communaute_blindee_locate_stylesheet', $stylesheet_uri, $stylesheet );
}

/**
 * Check if the current IP has access the way Restricted Site Access does
 *
 * @since 1.0.0
 */
function communaute_blindee_current_ip_has_access() {
	$retval             = false;
	$communaute_blindee = communaute_blindee();

	if ( ! empty( $communaute_blindee->rsa_options['allowed'] ) ) {
		$remote_ip = Restricted_Site_Access::get_client_ip_address();

		// iterate through the allowed list.
		foreach ( $communaute_blindee->rsa_options['allowed'] as $line ) {
			if ( Restricted_Site_Access::ip_in_range( $remote_ip, $line ) ) {
				$retval = true;
				break;
			}
		}
	}

	return $retval;
}

/**
 * Enqueue Needed Scripts for our custom BuddyPress templates
 *
 * @since 1.0.0
 */
function communaute_blindee_enqueue_scripts() {
	$site_icon = communaute_blindee_get_site_icon();

	if ( true === (bool) $site_icon ) {
		wp_add_inline_style( 'login', sprintf( '
			.login h1 a {
				background-image: none, url(%s);
			}
		', $site_icon ) );
	}

	if ( ! communaute_blindee_current_ip_has_access() && ( bp_is_register_page() || bp_is_activation_page() ) ) {
		$communaute_blindee = communaute_blindee();

		// Clean up styles.
		foreach ( wp_styles()->queue as $css_handle ) {
			wp_dequeue_style( $css_handle );
		}

		// Clean up scripts.
		foreach ( wp_scripts()->queue as $js_handle ) {
			wp_dequeue_script( $js_handle );
		}

		// Enqueue style.
		wp_enqueue_style( 'communaute-blindee-register-style',
			communaute_blindee_locate_stylesheet( 'communaute-blindee-register' ),
			array( 'login' ),
			$communaute_blindee->version
		);

		// Enqueue script.
		wp_enqueue_script( 'communaute-blindee-register' );

		// The register form need some specific stuff
		if ( bp_is_register_page() && 'completed-confirmation' !== bp_get_current_signup_step() ) {
			add_filter( 'bp_xprofile_is_richtext_enabled_for_field', '__return_false' );
			wp_localize_script( 'communaute-blindee-register', 'bpRestrictCommunity', array( 'field_key' => wp_hash( date( 'YMDH' ) ) ) );
		}
	}
}

/**
 * Returns the Plugin template directory if needed.
 *
 * @since 1.0.0
 *
 * @return string|void The Plugin template directory if needed.
 */
function communaute_blindee_templates_dir() {
	if ( ! is_admin() ) {
		if ( ! bp_is_register_page() && ! bp_is_activation_page() ) {
			return;
		}

		// Restrict Site Access is not using the login screen
		if ( ! communaute_blindee_rsa_approach_for_signups() ) {
			return;
		}

		// If an IP is allowed it will get the regular BuddyPress Register/Activate templates
		if ( communaute_blindee_current_ip_has_access() ) {
			return;
		}
	} else {
		global $pagenow;
		if ( 'admin.php' !== $pagenow || ! ( isset( $_GET['page'] ) && 'bp-profile-edit' === $_GET['page'] ) )  {
			return;
		}
	}

	// Use the plugin's templates
	return apply_filters( 'communaute_blindee_templates_dir', communaute_blindee()->templates_dir );
}

/**
 * Register the template dir into BuddyPress template stack
 *
 * @since 1.0.0
 */
function communaute_blindee_register_templates_dir() {
	// After Theme, but before BP Legacy
	bp_register_template_stack( 'communaute_blindee_templates_dir',  13 );
}

/**
 * Filter the Restrict Site Access main function and adapt it for our BuddyPress needs
 *
 * @since 1.0.0
 */
function communaute_blindee_allow_bp_registration( $is_restricted = true ) {
	// Not restricted, do nothing
	if ( ! $is_restricted ) {
		return $is_restricted;
	}

	// Bail if the current ip has access
	if ( communaute_blindee_current_ip_has_access() ) {
		return $is_restricted;
	}

	if ( bp_is_register_page() || bp_is_activation_page() ) {
		$is_restricted = ! communaute_blindee_rsa_approach_for_signups();
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
function communaute_blindee_validate_js_email() {
	$bp        = buddypress();
	$errors    = new WP_Error();
	$field_key = wp_hash( date( 'YMDH' ) );

	if ( empty( $_POST[ $field_key ] ) || empty( $_POST['signup_email'] ) || $_POST[ $field_key ] !== $_POST['signup_email'] ) {
		$errors->add( 'signup_email', __( 'We were not able to validate your email, please try again.', 'communaute-blindee' ) );
		$bp->signup->errors['signup_email'] = $errors->errors['signup_email'][0];
	}
}

/**
 * Get the site icon to replace WordPress login logo
 *
 * @since 1.0.0
 *
 * @return string the URL to the site icon.
 */
function communaute_blindee_get_site_icon() {
	$communaute_blindee = communaute_blindee();

	if ( ! isset( $communaute_blindee->site_icon ) ) {
		$communaute_blindee->site_icon = apply_filters( 'communaute_blindee_get_site_icon', get_site_icon_url( 84 ) );
	}

	return $communaute_blindee->site_icon;
}

/**
 * Add Registration restrictions by email domain
 *
 * @since 1.0.0
 *
 * @param array $icon_sizes The icon sizes.
 * @return array The icon sizes.
 */
function communaute_blindee_login_screen_icon_size( $icon_sizes = array() ) {
	return array_merge( $icon_sizes, array( 84 ) );
}

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
			'post_content' => __( "{{{communaute_blindee.privacy_policy}}}\n\nTo join {{{site.name}}}, please visit: <a href=\"{{{communaute_blindee.url}}}\">{{communaute_blindee.title}}</a>.", 'communaute-blindee' ),
			'post_excerpt' => __( "{{{communaute_blindee.privacy_policy}}}\n\nTo join {{{site.name}}}, please visit: \n\n{{{ommunaute_blindee.url}}}.", 'communaute-blindee' ),
		),
		'communaute-blindee-user-registered' => array(
			'description' => __( 'A user registered to the site', 'communaute-blindee' ),
			'term_id'     => 0,
			'post_title'   => __( '[{{{site.name}}}] New User Registration', 'communaute-blindee' ),
			'post_content' => __( "New user registration on your site {{{site.name}}}:\n\n<ul><li>Username: {{{communaute_blindee.username}}}</li><li>Email: {{{communaute_blindee.user_email}}}</li></ul>", 'communaute-blindee' ),
			'post_excerpt' => __( "New user registration on your site {{{site.name}}}:\n\n- Username: {{{communaute_blindee.username}}}\n- Email: {{{communaute_blindee.user_email}}}", 'communaute-blindee' ),
		),
		'communaute-blindee-user-changed-email' => array(
			'description' => __( 'A user made a request to change their Email', 'communaute-blindee' ),
			'term_id'     => 0,
			'post_title'   => __( '[{{{site.name}}}] Email Change Request', 'communaute-blindee' ),
			'post_content' => __( "You recently requested to have the email address on your account changed.\n\nIf this is correct, please click on <a href=\"{{{communaute_blindee.validate_link}}}\">this link</a> to change it.\n\nYou can safely ignore and delete this email if you do not want to
			take this action.", 'communaute-blindee' ),
			'post_excerpt' => __( "New user registration on your site {{{site.name}}}:\n\nIf this is correct, please click on the following link to change it:\n{{{communaute_blindee.validate_link}}}\n\nYou can safely ignore and delete this email if you do not want to
			take this action.", 'communaute-blindee' ),
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

function communaute_blindee_get_removable_query_args() {
	return array(
		'_communaute_blindee_status',
		'_communaute_blindee_nonce',
	);
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

function communaute_blindee_get_feedback( $code = '' ) {
	$feedbacks = array(
		'invalid' => array(
			'type'    => 'error',
			'message' => __( 'Please check your email address.', 'communaute-blindee' ),
		),
		'domain_banned' => array(
			'type'    => 'error',
			'message' => __( 'Sorry, that email address is not allowed!', 'communaute-blindee' ),
		),
		'domain_not_allowed'      => array(
			'type'    => 'error',
			'message' => __( 'Sorry, that email address is not allowed!', 'communaute-blindee' ),
		),
		'in_use'                  => array(
			'type'    => 'error',
			'message' => __( 'Sorry, that email address is already used!', 'communaute-blindee' ),
		),
		'privacy-policy-sent'     => array(
			'type'    => 'info',
			'message' => __( 'The privacy policy was successfully sent!', 'communaute-blindee' ),
		),
		'privacy-policy-not-sent' => array(
			'type'    => 'error',
			'message' => __( 'Sorry, there was a problem sending the privacy policy. Please try again later.', 'communaute-blindee' ),
		),
		'signup_errors' => array(
			'type'    => 'error',
			'message' => __( 'There was a problem sending your registration request. Please review the problematic fields.', 'communaute-blindee' ),
		),
	);

	if ( $code ) {
		if ( ! isset( $feedbacks[ $code ] ) )  {
			return '';
		}

		return $feedbacks[ $code ];
	}

	return $feedbacks;
}

function communaute_blindee_registration_feedback() {
	$bp     = buddypress();
	$errors = array();
	$infos  = array();

	if ( ! $_GET && isset( $bp->signup->errors ) && $bp->signup->errors ) {
		$feedback = communaute_blindee_get_feedback( 'signup_errors' );

		if ( isset( $feedback['message'] ) ) {
			$errors[] = $feedback['message'];
		}
	} else {
		$qv = wp_parse_args( $_GET, array(
			'_communaute_blindee_status' => false,
		) );

		if ( ! $qv['_communaute_blindee_status'] ) {
			return;
		}

		if ( ! is_array( $qv['_communaute_blindee_status'] ) ) {
			$qv['_communaute_blindee_status'] = (array) $qv['_communaute_blindee_status'];
		}

		foreach ( $qv['_communaute_blindee_status'] as $feedback_key ) {
			$feedback = communaute_blindee_get_feedback( $feedback_key );
			if ( ! isset( $feedback['message'] ) ) {
				continue;
			}

			if ( 'error' === $feedback['type'] ) {
				$errors[] = $feedback['message'];
			} else {
				$infos[] = $feedback['message'];
			}
		}
	}

	if ( $errors ) {
		printf( '<div id="login_error">%1$s</div>%2$s', join( "<br/>", array_map( 'esc_html', $errors ) ), "\n" );
	}

	if ( $infos ) {
		foreach ( $infos as $info ) {
			printf( '<p class="message">%1$s</p>%2$s', esc_html( $info ), "\n" );
		}
	}
}

function communaute_blindee_activation_feedback() {
	$bp = buddypress();

	if ( ! bp_account_was_activated() && ! $bp->template_message ) {
		return;
	}

	if ( ! bp_account_was_activated() ) {
		printf( '<div id="login_error">%1$s</div>%2$s', esc_html( $bp->template_message ), "\n" );

	} else {
		$message = __( 'Your account was activated successfully! You can now log in with the username and password you provided when you signed up.', 'communaute-blindee' );
		if ( isset( $_GET['e'] ) ) {
			$message = __( 'Your account was activated successfully! Your account details have been sent to you in a separate email.', 'communaute-blindee' );
		}

		printf( '<p class="message">%1$s</p>%2$s', esc_html( $message ), "\n" );
	}
}

function communaute_blindee_generate_random_string( $length = 10 ) {
	$chars  = explode( ',', 'a,z,e,r,t,y,u,i,o,p,q,s,d,f,g,h,j,k,l,m,w,x,c,v,b,n' );
	$lchars = count( $chars );
	$return = '';

	for ( $c = 0 ; $c < $length ; $c++ ) {
		$random_c = $chars[ mt_rand( 0, $lchars - 1 ) ];
        $return  .= $random_c;
	}

	return $return;
}

/**
 * @param string $message
 * @param string $key
 * @return string
 */
function communaute_blindee_encrypt( $message, $key = '' ) {
	$nonce = random_bytes( 24 );

	if ( ! $key ) {
		$key = substr( AUTH_SALT, 0, 32 );
	}

	return base64_encode(
		$nonce . sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
			$message,
			$nonce,
			$nonce,
			$key
		)
	);
}

/**
 * @param string $message
 * @param string $key
 * @return string
 */
function communaute_blindee_decrypt( $message, $key = '' ) {
	$decoded    = base64_decode( $message );
	$nonce      = substr( $decoded, 0, 24 );
	$ciphertext = substr( $decoded, 24 );

	if ( ! $key ) {
		$key = substr( AUTH_SALT, 0, 32 );
	}

	return sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
		$ciphertext,
		$nonce,
		$nonce,
		$key
	);
}

function communaute_blindee_not_logged_in_privacy_policy_url( $url = '' ) {
	if ( is_user_logged_in() ) {
		return $url;
	}

	return str_replace( home_url( '/' ), bp_get_signup_page(), $url );
}
add_filter( 'privacy_policy_url', 'communaute_blindee_not_logged_in_privacy_policy_url', 10, 1 );

function communaute_blindee_return_true() {
	return true;
}

function communaute_blindee_return_false() {
	return false;
}

function communaute_blindee_user_registered_notification( $user_data = array() ) {
	if ( ! $user_data['user_login'] || ! $user_data['user_email'] ) {
		return;
	}

	bp_send_email( 'communaute-blindee-user-registered', get_option( 'admin_email' ), array(
		'tokens' => array(
			'communaute_blindee.username'   => $user_data['user_login'],
			'communaute_blindee.user_email' => $user_data['user_email'],
		),
	) );
}

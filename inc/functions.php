<?php
/**
 * Functions.
 *
 * @package   communaute-protegee
 * @subpackage \inc\functions
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function communaute_protegee_login_screen_add_icon_size( $icon_sizes = array() ) {
	return array_merge( $icon_sizes, array( 84 ) );
}

function communaute_protegee_register_scripts() {
	// Get the Plugin's main instance.
	$cp = communaute_protegee();

	$scripts = apply_filters( 'bp_restricted_community_register_scripts',
		array(
			array(
				'handle' => 'bp-restricted-community-register',
				'file'   => $cp->plugin_js . "register{$cp->minified}.js",
				'deps'   => array( 'jquery', 'user-profile' ),
			),
		)
	);

	foreach ( (array) $scripts as $script ) {
		wp_register_script(
			$script['handle'],
			$script['file'],
			$script['deps'],
			$cp->version, true
		);
	}
}

/**
 * Locate the stylesheet to use for our custom templates
 *
 * You can override the one used by the plugin by putting yours
 * inside yourtheme/css/bp-restricted-community-register.min.css
 *
 * @since 1.0.0
 *
 * @param string $stylesheet_name The stylesheet name.
 */
function communaute_protegee_locate_stylesheet( $stylesheet_name = '' ) {
	if ( ! $stylesheet_name ) {
		return false;
	}

	// Get the Plugin's main instance.
	$cp = communaute_protegee();

	$stylesheet_path = bp_locate_template( 'css/' . $stylesheet_name . $cp->minified . '.css' );

	if ( 0 === strpos( $stylesheet_path, $cp->plugin_dir ) ) {
		$stylesheet_uri = str_replace( $cp->plugin_dir, $cp->plugin_url, $stylesheet_path );
	} else {
		$stylesheet_uri = str_replace( WP_CONTENT_DIR, content_url(), $stylesheet_path );
	}

	/**
	 * Filter here to edit the stylesheet URI.
	 *
	 * @since 1.0.0
	 *
	 * @param string $stylesheet_uri The stylesheet URI.
	 * @param string $stylesheet_name The stylesheet name.
	 */
	return apply_filters( 'bp_restricted_community_locate_stylesheet', $stylesheet_uri, $stylesheet_name );
}

/**
 * Check if the current IP has access the way Restricted Site Access does
 *
 * @since 1.0.0
 */
function communaute_protegee_current_ip_has_access() {
	$retval = false;

	// Get the Plugin's main instance.
	$cp = communaute_protegee();

	if ( ! empty( $cp->rsa_options['allowed'] ) ) {
		$remote_ip = Restricted_Site_Access::get_client_ip_address();

		// iterate through the allowed list.
		foreach ( $cp->rsa_options['allowed'] as $line ) {
			if ( Restricted_Site_Access::ip_in_range( $remote_ip, $line ) ) {
				$retval = true;
				break;
			}
		}
	}

	return $retval;
}

/**
 * Returns the Plugin's template location directory.
 *
 * @since 1.0.0
 *
 * @return string The Plugin's template location directory.
 */
function communaute_protegee_template_dir() {
	$template_dir = '';

	// Get the Plugin's main instance.
	$cp = communaute_protegee();

	// Only add our template location directory for user's registration, activation or BP General user's settings.
	if ( ! bp_is_register_page() && ! bp_is_activation_page() && ! bp_is_active( 'settings' ) && ! bp_is_user_settings_general() ) {
		return $template_dir;
	}

	// Only add our template location directory if the login screen is the RSA approach.
	if ( isset( $cp->rsa_options['approach'] ) && 1 !== $cp->rsa_options['approach'] ) {
		return $template_dir;
	}

	// Only add our template location directory if the IP is not allowed.
	if ( communaute_protegee_current_ip_has_access() ) {
		return;
	}

	// Use the Plugin's template location directory.
	$template_dir = $cp->templates_dir;

	/**
	 * Filter here to edit the template location directory to use.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template_dir The template location directory to use.
	 */
	return apply_filters( 'bp_restricted_community_templates_dir', $template_dir );
}

/**
 * Registers the Plugin's template dir into the BuddyPress template locations stack.
 *
 * @since 1.0.0
 */
function communaute_protegee_register_template_dir() {
	// After the active theme, but before the active Template Pack.
	bp_register_template_stack( 'communaute_protegee_template_dir',  13 );
}

/**
 * Use the WordPress control to set the password during registration.
 *
 * @since 1.0.0
 */
function communaute_protegee_set_pwd_control_template() {
	bp_get_template_part( 'members/register-password' );
}

/**
 * Use the WordPress control to edit the password from the user's profile.
 *
 * @since 1.0.0
 */
function communaute_protegee_edit_pwd_control_template() {
	bp_get_template_part( 'members/single/settings/general-password' );
}

function communaute_protegee_enqueue_scripts() {
	$style = '';

	// Get the Plugin's main instance.
	$cp = communaute_protegee();

	if ( true === (bool) $cp->use_site_icon ) {
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
		wp_enqueue_style(
			'bp-restricted-community-register-style',
			communaute_protegee_locate_stylesheet( 'bp-restricted-community-register' ),
			array( 'login' ),
			$cp->version
		);
		wp_enqueue_script( 'bp-restricted-community-register' );

		// The register form need some specific stuff
		if ( bp_is_register_page() && 'completed-confirmation' !== bp_get_current_signup_step() ) {
			add_filter( 'bp_xprofile_is_richtext_enabled_for_field', '__return_false' );
			wp_localize_script( 'bp-restricted-community-register', 'bpRestrictCommunity', array( 'field_key' => wp_hash( date( 'YMDH' ) ) ) );

			/**
			 * Replace BuddyPress's way of setting the password by the WordPress's one
			 * for the Legacy template pack
			 */
			if ( $cp->is_legacy ) {
				add_action( 'bp_account_details_fields', 'communaute_protegee_set_pwd_control_template' );
			}
		}

		do_action( 'bp_restricted_community_enqueue_scripts' );

	} elseif ( $cp->is_legacy && bp_is_active( 'settings' ) && bp_is_user_settings_general() ) {
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
		add_action( 'bp_core_general_settings_before_submit', 'communaute_protegee_edit_pwd_control_template' );
	}
}

/**
 * Filter the Restrict Site Access main function and adapt it for our BuddyPress needs.
 *
 * @since 1.0.0
 *
 * @param boolean $is_restricted True if the access is restricted. False otherwise.
 * @param boolean True if the access is restricted. False otherwise.
 */
function communaute_protegee_allow_bp_registration( $is_restricted = false ) {
	// Not restricted, do nothing.
	if ( ! $is_restricted ) {
		return $is_restricted;
	}

	// Get the Plugin's main instance.
	$cp = communaute_protegee();

	// Bail if the current ip has access
	if ( communaute_protegee_current_ip_has_access() ) {
		return true;
	}

	// Login screen is the target of this plugin, allow BuddyPress registration and activation.
	if ( bp_is_register_page() || bp_is_activation_page() ) {
		$is_restricted = (bool) ! ( empty( $cp->rsa_options['approach'] ) || 1 === $cp->rsa_options['approach'] );
	}

	return $is_restricted;
}

/**
 * Extra check for submitted registrations.
 *
 * Let's try to prevent spam registrations.
 *
 * @since 1.0.0
 */
function communaute_protegee_js_validate_email() {
	$bp        = buddypress();
	$errors    = new WP_Error();
	$field_key = wp_hash( date( 'YMDH' ) );

	$fields = wp_parse_args(
		array_map( 'wp_unslash', $_POST ),
		array(
			'signup_email' => '',
			$field_key     => '',
		)
	);

	if ( ! $fields[ $field_key ] || ! $fields['signup_email'] || $fields[ $field_key ] !== $fields['signup_email'] ) {
		$errors->add( 'signup_email', __( 'We were not able to validate your email, please try again.', 'bp-restricted-community' ) );
		$bp->signup->errors['signup_email'] = $errors->errors['signup_email'][0];
	}
}

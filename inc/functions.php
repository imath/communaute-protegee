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

/**
 * Adds a new size to the Site's icon.
 *
 * @since 1.0.0
 *
 * @param array $icon_sizes The existing icon sizes.
 * @return array The same icon sizes and the plugin's custom one.
 */
function communaute_protegee_login_screen_add_icon_size( $icon_sizes = array() ) {
	return array_merge( $icon_sizes, array( 84 ) );
}

/**
 * Gets the icon path using the requested size or url.
 *
 * @since 1.0.0
 *
 * @param integer $size The icon size.
 * @param string  $url  The icon url.
 * @return string The icon path.
 */
function communaute_protegee_get_icon_path( $size = 0, $url = '' ) {
	if ( ! $size && ! $url ) {
		return '';
	}

	if ( ! $url ) {
		$url = get_site_icon_url( $size, '', bp_get_root_blog_id() );

		if ( ! $url ) {
			return '';
		}
	}

	// Get WordPress uploads directory data.
	$upload_data = wp_get_upload_dir();

	if ( false === strpos( $url, $upload_data['baseurl'] ) ) {
		return '';
	}

	// Set the icon path.
	$path = str_replace( $upload_data['baseurl'], $upload_data['basedir'], $url );

	return realpath( $path );
}

/**
 * Gets a base64 encoded image for the site icon.
 *
 * @since 1.0.0
 *
 * @return string The base64 encoded image for the site icon.
 */
function communaute_protegee_get_base64_site_icon() {
	$site_icon = get_site_icon_url( 84, '', bp_get_root_blog_id() );

	if ( $site_icon ) {
		// Get the base64 site icon.
		$base64_site_icon = bp_get_option( '_communaute_protegee_base64_site_icon', '' );

		if ( ! $base64_site_icon ) {
			$icon_path = communaute_protegee_get_icon_path( 84, $site_icon );
			$icon_mime = wp_get_image_mime( $icon_path );
			$site_icon = sprintf( 'data:%1$s;base64,%2$s', $icon_mime, base64_encode( file_get_contents( $icon_path ) ) ); // phpcs:ignore

			// Update the base64 site icon.
			bp_update_option( '_communaute_protegee_base64_site_icon', $site_icon );
		} else {
			$site_icon = $base64_site_icon;
		}
	}

	return $site_icon;
}

/**
 * Resets the icon meta tags if the uploads directory is restricted.
 *
 * @since 1.0.0
 *
 * @param array $meta_tags The WordPress default icon meta tags.
 * @return array The icon meta tags.
 */
function communaute_protegee_set_site_icon_meta_tags( $meta_tags = array() ) {
	if ( doing_action( 'login_head' ) || doing_action( 'communaute_protegee_head' ) ) {
		// Get the Plugin's main instance.
		$cp = communaute_protegee();

		if ( true === (bool) $cp->use_site_icon && true === (bool) bp_get_option( 'communaute_protegee_uploads_dir_restriction' ) ) {
			$icon_url  = home_url( 'communaute-privee-icon/%s/' );
			$meta_tags = array(
				sprintf( '<link rel="icon" href="%s" sizes="32x32" />', esc_url( sprintf( $icon_url, 32 ) ) ),
				sprintf( '<link rel="icon" href="%s" sizes="192x192" />', esc_url( sprintf( $icon_url, 192 ) ) ),
				sprintf( '<link rel="apple-touch-icon" href="%s" />', esc_url( sprintf( $icon_url, 180 ) ) ),
				sprintf( '<meta name="msapplication-TileImage" content="%s" />', esc_url( sprintf( $icon_url, 270 ) ) ),
			);
		}
	}

	return $meta_tags;
}

/**
 * Deletes the base64 site icon when the regular one has been deleted.
 *
 * @since 1.0.0
 */
function communaute_protegee_update_email_header_logo() {
	bp_delete_option( '_communaute_protegee_base64_site_icon' );
}

/**
 * Registers the plugin JavaScript assets.
 *
 * @since 1.0.0
 */
function communaute_protegee_register_scripts() {
	// Get the Plugin's main instance.
	$cp = communaute_protegee();

	/**
	 * Use this filter to edit the plugin's JavaScript assets.
	 *
	 * @since 1.0.0
	 *
	 * @param array $value Associative array containing the script's handle,
	 *                     file and dependencies.
	 */
	$scripts = apply_filters(
		'communaute_protegee_register_scripts',
		array(
			array(
				'handle' => 'communaute-protegee-register',
				'file'   => $cp->js_url . "register{$cp->minified}.js",
				'deps'   => array( 'jquery', 'user-profile' ),
			),
		)
	);

	foreach ( (array) $scripts as $script ) {
		wp_register_script(
			$script['handle'],
			$script['file'],
			$script['deps'],
			$cp->version,
			true
		);
	}
}

/**
 * Locate the stylesheet to use for our custom templates
 *
 * You can override the one used by the plugin by putting yours
 * inside yourtheme/css/communaute-protegee-register.min.css
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

	if ( 0 === strpos( $stylesheet_path, $cp->dir ) ) {
		$stylesheet_uri = str_replace( $cp->dir, $cp->url, $stylesheet_path );
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
	return apply_filters( 'communaute_protegee_locate_stylesheet', $stylesheet_uri, $stylesheet_name );
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
	return apply_filters( 'communaute_protegee_templates_dir', $template_dir );
}

/**
 * Registers the Plugin's template dir into the BuddyPress template locations stack.
 *
 * @since 1.0.0
 */
function communaute_protegee_register_template_dir() {
	// After the active theme, but before the active Template Pack.
	bp_register_template_stack( 'communaute_protegee_template_dir', 13 );
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

/**
 * Enqueues plugin's JavaScript and CSS assets.
 *
 * @since 1.0.0
 */
function communaute_protegee_enqueue_scripts() {
	$style = '';

	// Get the Plugin's main instance.
	$cp = communaute_protegee();

	if ( true === (bool) $cp->use_site_icon ) {
		wp_add_inline_style(
			'login',
			sprintf(
				'.login h1 a {
					background-image: none, url(%s);
				}',
				esc_attr( communaute_protegee_get_base64_site_icon() )
			)
		);
	}

	if ( bp_is_register_page() || bp_is_activation_page() ) {
		// Clean up scripts.
		foreach ( wp_scripts()->queue as $js_handle ) {
			wp_dequeue_script( $js_handle );
		}

		// Clean up styles.
		foreach ( wp_styles()->queue as $css_handle ) {
			wp_dequeue_style( $css_handle );
		}

		// Enqueue style.
		wp_enqueue_style(
			'communaute-protegee-register-style',
			communaute_protegee_locate_stylesheet( 'communaute-protegee-register' ),
			array( 'login' ),
			$cp->version
		);
		wp_enqueue_script( 'communaute-protegee-register' );

		// The register form need some specific stuff.
		if ( bp_is_register_page() && 'completed-confirmation' !== bp_get_current_signup_step() ) {
			add_filter( 'bp_xprofile_is_richtext_enabled_for_field', '__return_false' );
			wp_localize_script( 'communaute-protegee-register', 'communauteProtegee', array( 'field_key' => wp_hash( gmdate( 'YMDH' ) ) ) );

			/**
			 * Replace BuddyPress's way of setting the password by the WordPress's one
			 * for the Legacy template pack
			 */
			if ( $cp->is_legacy ) {
				add_action( 'bp_account_details_fields', 'communaute_protegee_set_pwd_control_template' );
			}
		}

		/**
		 * Hook here to add custom code once plugin's assets are enqueued.
		 *
		 * @since 1.0.0
		 */
		do_action( 'communaute_protegee_enqueue_scripts' );

	} elseif ( $cp->is_legacy && bp_is_active( 'settings' ) && bp_is_user_settings_general() ) {
		wp_dequeue_script( 'bp-legacy-password-verify-password-verify' );
		wp_enqueue_script( 'user-profile' );

		// Remove BuddyPress Password fields.
		wp_add_inline_script(
			'user-profile',
			'( function() {
				document.querySelector( \'#settings-form\' ).setAttribute( \'id\', \'your-profile\' );
				document.querySelector( \'#pass1\' ).remove();
				document.querySelector( \'label[for="pass1"] span\' ).remove();
				document.querySelector( \'#pass-strength-result\' ).remove();
				document.querySelector( \'#pass2\' ).remove();
				document.querySelector( \'label[for="pass2"]\' ).remove();
			} )();'
		);

		wp_add_inline_style(
			'bp-parent-css',
			'body.settings #buddypress .wp-pwd button {
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
			}',
			'after'
		);

		// Replace BuddyPress's way of setting the password by the WordPress's one.
		add_action( 'bp_core_general_settings_before_submit', 'communaute_protegee_edit_pwd_control_template' );
	}
}

/**
 * Outputs the Site icon image even when the uploads directory is protected.
 *
 * @since 1.0.0
 *
 * @param string $icon_path The path to the icon image to output.
 */
function communaute_protegee_output_icon( $icon_path = '' ) {
	if ( ! $icon_path ) {
		return;
	}

	$icon_mime = wp_get_image_mime( $icon_path );

	if ( in_array( $icon_mime, array( 'image/jpeg', 'image/gif', 'image/png' ), true ) ) {
		status_header( 200 );
		header( 'Cache-Control: cache, must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Length: ' . filesize( $icon_path ) );
		header( 'Content-Disposition: inline; filename=' . wp_basename( $icon_path ) );
		header( 'Content-Type: ' . $icon_mime );

		while ( ob_get_level() > 0 ) {
			ob_end_flush();
		}

		readfile( $icon_path ); // phpcs:ignore
		die();
	}
}

/**
 * Check the main WordPress query to see if we need to output the Site icon image.
 *
 * @since 1.0.0
 *
 * @param WP_Query $query The WP_Query instance
 * @return string HTML Output.
 */
function communaute_protegee_query( $query = null ) {
	// Bail if $query is not the main loop
	if ( ! $query->is_main_query() ) {
		return;
	}

	// Bail if filters are suppressed on this query
	if ( true === $query->get( 'suppress_filters' ) ) {
		return;
	}

	$icon_size = (int) $query->get( 'cp-icon-size' );

	if ( ! $icon_size || ! in_array( $icon_size, array( 32, 84, 192, 180, 270 ), true ) ) {
		return;
	}

	$icon_path = communaute_protegee_get_icon_path( $icon_size );

	return communaute_protegee_output_icon( $icon_path );
}

/**
 * Adds a new rewrite rule for the dynamic output of the Site icon.
 *
 * @since 1.0.0
 */
function communaute_protegee_rewrites() {
	// Icon size
	add_rewrite_tag( '%cp-icon-size%', '([^/]+)' );

	add_rewrite_rule(
		'communaute-protegee-icon/?([0-9]{1,})/?$',
		'index.php?cp-icon-size=$matches[1]',
		'top'
	);
}

/**
 * Filter the Restrict Site Access main function and adapt it for our BuddyPress needs.
 *
 * @since 1.0.0
 *
 * @param boolean $is_restricted True if the access is restricted. False otherwise.
 * @param WP      $wp            The main query objetct.
 * @return boolean True if the access is restricted. False otherwise.
 */
function communaute_protegee_allow_bp_registration( $is_restricted = false, $wp ) {
	// Not restricted, do nothing.
	if ( ! $is_restricted ) {
		return $is_restricted;
	}

	// Get the Plugin's main instance.
	$cp = communaute_protegee();

	// Bail if the current ip has access.
	if ( communaute_protegee_current_ip_has_access() ) {
		return true;
	}

	// Login screen is the target of this plugin, allow BuddyPress registration and activation.
	if ( bp_is_register_page() || bp_is_activation_page() ) {
		$is_restricted = (bool) ! ( empty( $cp->rsa_options['approach'] ) || 1 === $cp->rsa_options['approach'] );
	}

	// Redirect users requesting the privacy page to the form to receive it by email.
	if ( bp_signup_requires_privacy_policy_acceptance() && isset( $wp->query_vars['pagename'] ) ) {
		$url = trailingslashit( home_url( $wp->query_vars['pagename'] ) );

		if ( get_privacy_policy_url() === $url ) {
			bp_core_redirect( trailingslashit( bp_get_signup_page() . $wp->query_vars['pagename'] ) );
		}
	}

	// Check if the request is about the site icon.
	if ( isset( $wp->query_vars['cp-icon-size'] ) && in_array( (int) $wp->query_vars['cp-icon-size'], array( 32, 84, 192, 180, 270 ), true ) ) {
		$icon_size = (int) $wp->query_vars['cp-icon-size'];
		$icon_path = communaute_protegee_get_icon_path( $icon_size );

		if ( ! $icon_path ) {
			return '';
		}

		return communaute_protegee_output_icon( $icon_path );
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
	$field_key = wp_hash( gmdate( 'YMDH' ) );

	$fields = wp_parse_args(
		array_map( 'wp_unslash', $_POST ), // phpcs:ignore
		array(
			'signup_email' => '',
			$field_key     => '',
		)
	);

	if ( ! $fields[ $field_key ] || ! $fields['signup_email'] || $fields[ $field_key ] !== $fields['signup_email'] ) {
		$errors->add( 'signup_email', __( 'Nous n’avons pas été en mesure de valider votre e-mail, merci de réessayer.', 'communaute-protegee' ) );
		$bp->signup->errors['signup_email'] = $errors->errors['signup_email'][0];
	}
}

/**
 * Sets and loads the registration's privacy screen.
 *
 * @since 1.0.0
 */
function communaute_protegee_privacy_step() {
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

	/**
	 * Hook here to run custom code before the privacy policy template is loaded.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $page The privacy page's object.
	 */
	do_action( 'communaute_protegee_privacy_step', $page );

	bp_core_load_template( apply_filters( 'bp_core_template_register', array( 'register', 'registration/register' ) ) );
}
add_action( 'bp_screens', 'communaute_protegee_privacy_step' );

/**
 * Loads a template part when registration needs privacy policy acceptance.
 *
 * @since 1.0.0
 */
function communaute_protegee_privacy_policy_signup_step() {
	if ( ! bp_signup_requires_privacy_policy_acceptance() ) {
		return;
	}

	bp_get_template_part( 'members/register-privacy-policy' );
}

/**
 * Returns the Feedback's type and message according to its code.
 * Returns all feedbacks if no code is provided.
 *
 * @since 1.0.0
 *
 * @param string $code The feedback's code. Optional.
 * @return array The feedback's type and message, or all feedback ones.
 */
function communaute_protegee_get_feedback( $code = '' ) {
	$feedbacks = array(
		'missing_email'           => array(
			'type'    => 'error',
			'message' => __( 'Votre adresse e-mail est requise afin que nous puissions vous envoyer la politique de confidentialité.', 'communaute-protegee' ),
		),
		'unmatching_email'        => array(
			'type'    => 'error',
			'message' => __( 'La vérification de sécurité a échoué. Êtes-vous un humain ?', 'communaute-protegee' ),
		),
		'invalid'                 => array(
			'type'    => 'error',
			'message' => __( 'Merci de vérifier votre adresse e-mail.', 'communaute-protegee' ),
		),
		'domain_banned'           => array(
			'type'    => 'error',
			'message' => __( 'Désolé, cette adresse e-mail n’est pas autorisée !', 'communaute-protegee' ),
		),
		'domain_not_allowed'      => array(
			'type'    => 'error',
			'message' => __( 'Désolé, cette adresse e-mail n’est pas autorisée !', 'communaute-protegee' ),
		),
		'in_use'                  => array(
			'type'    => 'error',
			'message' => __( 'Désolé, cette adresse e-mail est déjà utilisée !', 'communaute-protegee' ),
		),
		'privacy-policy-sent'     => array(
			'type'    => 'info',
			'message' => __( 'La politique de confidentialité a été envoyée avec succès !', 'communaute-protegee' ),
		),
		'privacy-policy-not-sent' => array(
			'type'    => 'error',
			'message' => __( 'Désolé, un problème est survenu lors de l’envoi de la politique de confidentialité. Merci de réessayer ultérieurement.', 'communaute-protegee' ),
		),
	);

	if ( $code ) {
		if ( ! isset( $feedbacks[ $code ] ) ) {
			return '';
		}

		return $feedbacks[ $code ];
	}

	return $feedbacks;
}

/**
 * Displays user feedback for the privacy policy step if needed.
 *
 * @since 1.0.0
 */
function communaute_protegee_privacy_policy_feedback() {
	$bp = buddypress();

	if ( ( ! isset( $bp->signup->step ) || 'privacy-policy' !== $bp->signup->step ) && ! $_GET ) { // phpcs:ignore
		return;
	}

	$qv = array_map(
		'wp_unslash',
		wp_parse_args(
			$_GET, // phpcs:ignore
			array(
				'_communaute_protegee_status' => '',
			)
		)
	);

	if ( ! $qv['_communaute_protegee_status'] ) {
		return;
	}

	$qv['_communaute_protegee_status'] = wp_parse_slug_list( $qv['_communaute_protegee_status'] );

	$errors = array();
	$infos  = array();

	if ( array_intersect( $qv['_communaute_protegee_status'], array( 'domain_banned', 'domain_not_allowed' ) ) ) {
		$unset = array_search( 'domain_banned', $qv['_communaute_protegee_status'], true );

		if ( false !== $unset ) {
			unset( $unset );
		}
	}

	foreach ( $qv['_communaute_protegee_status'] as $feedback_key ) {
		$feedback = communaute_protegee_get_feedback( $feedback_key );
		if ( ! isset( $feedback['message'] ) ) {
			continue;
		}

		if ( 'error' === $feedback['type'] ) {
			$errors[] = $feedback['message'];
		} else {
			$infos[] = $feedback['message'];
		}
	}

	if ( $errors ) {
		printf( '<div id="login_error">%1$s</div>%2$s', join( '<br/>', array_map( 'esc_html', $errors ) ), "\n" );
	}

	if ( $infos ) {
		foreach ( $infos as $info ) {
			printf( '<p class="message">%1$s</p>%2$s', esc_html( $info ), "\n" );
		}
	}
}

/**
 * Removes the unsubscribe link when sending the Privacy policy.
 *
 * @since 1.0.0
 */
function communaute_protegee_hide_unsubscribe_link() {
	printf(
		'<style type="text/css">
			a[href="%s"] { display: none }
		</style>',
		esc_url( wp_login_url() )
	);
}

/**
 * Sends an email containing the privacy policy to the user.
 *
 * @since 1.0.0
 *
 * @param WP_Post $privacy_page The Privacy page's object.
 */
function communaute_protegee_mail_privacy_policy( WP_Post $privacy_page ) {
	if ( ! isset( $_POST['_communaute_protegee_nonce'] ) || ! isset( $privacy_page->post_content ) ) {
		return;
	}

	// Check the nonce.
	check_admin_referer( 'send-privacy-policy', '_communaute_protegee_nonce' );

	// Set the redirect url.
	$redirect = remove_query_arg( array( '_communaute_protegee_status', '_communaute_protegee_nonce' ), wp_get_referer() );

	// Field to prevent spam.
	$field_key = wp_hash( gmdate( 'YMDH' ) );

	if ( ! isset( $_POST['privacy_policy_email'] ) || ! $_POST['privacy_policy_email'] || ! isset( $_POST[ $field_key ] ) ) { // phpcs:ignore
		bp_core_redirect( add_query_arg( '_communaute_protegee_status', 'missing_email', $redirect ) );
	}

	$email      = wp_unslash( $_POST['privacy_policy_email'] ); // phpcs:ignore
	$emailcheck = wp_unslash( $_POST[ $field_key ] ); // phpcs:ignore

	if ( $emailcheck !== $email ) {
		bp_core_redirect( add_query_arg( '_communaute_protegee_status', 'unmatching_email', $redirect ) );
	}

	// Validate the email.
	$is_valid = bp_core_validate_email_address( $email );

	if ( true !== $is_valid ) {
		$status = (array) $is_valid;
		bp_core_redirect( add_query_arg( '_communaute_protegee_status', array_keys( $status ), $redirect ) );
	}

	$register_url = bp_get_signup_page();
	$html_content = communaute_protegee_set_email_content( $privacy_page->post_content );
	$text_content = str_replace( "\n\n", "\n", wp_kses( $html_content, array() ) );

	add_action( 'bp_before_email_footer', 'communaute_protegee_hide_unsubscribe_link', 1 );
	$sent = bp_send_email(
		'communaute-protegee-privacy-policy',
		$email,
		array(
			'tokens' => array(
				'communaute_protegee.privacy_policy'      => $html_content,
				'communaute_protegee.privacy_policy_text' => $text_content,
				'communaute_protegee.url'                 => $register_url,
				'communaute_protegee.title'               => __( 'la page d’inscription', 'communaute-protegee' ),
			),
		)
	);
	remove_action( 'bp_before_email_footer', 'communaute_protegee_hide_unsubscribe_link', 1 );

	if ( $sent ) {
		bp_core_redirect( add_query_arg( '_communaute_protegee_status', 'privacy-policy-sent', $register_url ) );
	} else {
		bp_core_redirect( add_query_arg( '_communaute_protegee_status', 'privacy-policy-not-sent', $register_url ) );
	}
}

/**
 * Remove Embeds from the email's content.
 *
 * @since 1.0.0
 *
 * @param string $content The email's content.
 * @return string The email's content.
 */
function communaute_protegee_set_email_content( $content = '' ) {
	// Make sure the Post won't be embed.
	add_filter( 'pre_oembed_result', '__return_false' );
	$content = apply_filters( 'the_content', $content );
	remove_filter( 'pre_oembed_result', '__return_false' );

	// Make links clickable.
	return make_clickable( $content );
}

/**
 * Get plugin's email templates.
 *
 * @since 1.0.0
 *
 * @return array An associative array containing the email type and the email template data.
 */
function communaute_protegee_get_emails() {
	/**
	 * Use this filter to customize the email templates used by the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param array $value The list of email templates used by the plugin.
	 */
	return apply_filters(
		'communaute_protegee_get_emails',
		array(
			'communaute-protegee-privacy-policy' => array(
				'description'  => __( 'Un utilisateur a demandé à recevoir la politique de confidentialité', 'communaute-protegee' ),
				'term_id'      => 0,
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] voici notre politique de confidentialité', 'communaute-protegee' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "{{{communaute_protegee.privacy_policy}}}\n\nPour rejoindre {{{site.name}}}, merci de consulter : <a href=\"{{{communaute_protegee.url}}}\">{{communaute_protegee.title}}</a>.", 'communaute-protegee' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{communaute_protegee.privacy_policy_text}}\n\nPour rejoindre {{site.name}}, merci de consulter :\n\n{{communaute_protegee.url}}.", 'communaute-protegee' ),
			),
		)
	);
}

/**
 * Checks whether the Admin checked the site icon for BP emails.
 *
 * @since 1.0.0
 *
 * @return boolean True if the site icon has been checked for BP Emails, false otherwise.
 */
function communaute_protegee_email_site_icon_is_checked() {
	$settings = bp_email_get_appearance_settings();

	if ( isset( $settings['site_icon'] ) && ! $settings['site_icon'] ) {
		return false;
	}

	return true;
}

/**
 * Outputs the Site icon into the BP Email template.
 *
 * @since 1.0.0
 */
function communaute_protegee_email_header_logo() {
	if ( ! communaute_protegee()->use_site_icon ) {
		return;
	}

	if ( ! bp_get_option( 'communaute_protegee_uploads_dir_restriction' ) ) {
		$site_icon = get_site_icon_url( 84, '', bp_get_root_blog_id() );
	} else {
		$site_icon = home_url( 'communaute-protegee-icon/84/' );
	}

	if ( $site_icon && communaute_protegee_email_site_icon_is_checked() ) {
		?>
			<a href="<?php echo esc_url( home_url() ); ?>">
				<img src="<?php echo esc_attr( $site_icon ); ?>" alt="Site Icon">
			</a>
			<br>
		<?php
	}
}

/**
 * Sanitizes the BP Email site icon setting.
 *
 * @since 1.0.0
 *
 * @param boolean $setting The value of the setting.
 * @return boolean The sanitized value of the setting.
 */
function communaute_protegee_customize_email_sanitize_setting( $setting = '' ) {
	return (bool) $setting;
}

/**
 * Adds the site icon setting to BP Emails ones.
 *
 * @since 1.0.0
 *
 * @param array $settings The BP Emails settings.
 * @return array The BP Emails settings, including the site logo one.
 */
function communaute_protegee_customize_email_settings( $settings = array() ) {
	if ( ! communaute_protegee()->use_site_icon ) {
		return $settings;
	}

	return array_merge(
		$settings,
		array(
			'bp_email_options[site_icon]' => array(
				'capability'        => 'bp_moderate',
				'default'           => true,
				'sanitize_callback' => 'communaute_protegee_customize_email_sanitize_setting',
				'transport'         => 'refresh',
				'type'              => 'option',
			),
		)
	);
}

/**
 * Adds a control to set whether to use the site icon in BP Emails header or not.
 *
 * @since 1.0.0
 *
 * @param WP_Customize_Manager $wp_customizer The WP Customizer manager object.
 */
function communaute_protegee_customize_email_control( WP_Customize_Manager $wp_customizer ) {
	if ( ! communaute_protegee()->use_site_icon ) {
		return;
	}

	$wp_customizer->add_control(
		'bp_email_header_site_icon',
		array(
			'settings'    => 'bp_email_options[site_icon]',
			'label'       => __( 'Icône du site', 'communaute-protegee' ),
			'description' => __( 'Si coché, l’icône du site est inséré dans l’entête de l’e-mail.', 'communaute-protegee' ),
			'section'     => 'section_bp_mailtpl_header',
			'type'        => 'checkbox',
			'priority'    => 10,
		)
	);
}

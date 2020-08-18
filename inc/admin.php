<?php
/**
 * Administration.
 *
 * @package   communaute-protegee
 * @subpackage \inc\admin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Callback to display the `limited_email_domains` setting field.
 *
 * @since 1.0.0
 */
function communaute_protegee_restrictions_setting_field_callback() {
	$limited_email_domains = get_site_option( 'limited_email_domains' );
	$limited_email_domains = str_replace( ' ', "\n", $limited_email_domains );
	?>
	<textarea name="limited_email_domains" id="limited_email_domains" aria-describedby="limited-email-domains-desc" cols="45" rows="5"><?php echo esc_textarea( '' === $limited_email_domains ? '' : implode( "\n", (array) $limited_email_domains ) ); ?></textarea>
	<p class="description" id="limited-email-domains-desc">
		<?php esc_html_e( 'Si vous souhaitez que les seules personnes autorisées à s’inscrire soient celles disposant d’une adresse e-mail liée à certains noms de domaine. Un domaine par ligne.', 'communaute-protegee' ); ?>
	</p>
	<?php
}

/**
 * Adds a setting field for non multisite configs to limit registrations to defined email domains.
 *
 * @since 1.0.0
 */
function communaute_protegee_restrictions_setting_field() {
	add_settings_field(
		'limited_email_domains',
		__( 'Inscription limitée aux e-mails contenant les domaines', 'communaute-protegee' ),
		'communaute_protegee_restrictions_setting_field_callback',
		'general',
		'default'
	);
}

/**
 * Allow the `limited_email_domains` for non multisite configs
 *
 * @since 1.0.0
 *
 * @param array $allowed_options The WordPress allowed options.
 * @return array The WordPress allowed options, including the
 *               `limited_email_domains` option for non multisite configs.
 */
function communaute_protegee_restrictions_add_option( $allowed_options = array() ) {
	if ( isset( $allowed_options['general'] ) ) {
		$allowed_options['general'] = array_merge(
			$allowed_options['general'],
			array( 'limited_email_domains' )
		);
	}

	return $allowed_options;
}

/**
 * Use JavaScript to move the `limited_email_domains` setting near the WordPress membership one.
 *
 * @since 1.0.0
 *
 * @param string $hook_suffix The suffix used by the dynamic hook.
 */
function communaute_protegee_move_restriction_settings( $hook_suffix = '' ) {
	if ( 'options-general.php' === $hook_suffix ) {
		$wp_scripts = wp_scripts();

		$data  = $wp_scripts->get_data( 'common', 'data' );
		$data .= "\n
if ( 'undefined' !== jQuery ) {
jQuery( '#users_can_register' ).closest( 'tr' ).after( jQuery( '#limited_email_domains' ).closest( 'tr' ) );
}
		";

		$wp_scripts->add_data( 'common', 'data', $data );
	}
}

/**
 * Install/Reinstall email templates
 *
 * @since 1.0.0
 */
function communaute_protegee_install_emails() {
	$switched = false;

	// Switch to the root blog, where the email posts live.
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
		$switched = true;
	}

	// Get Emails.
	$email_types = communaute_protegee_get_emails();

	// Set email types.
	foreach ( $email_types as $email_term => $term_args ) {
		if ( term_exists( $email_term, bp_get_email_tax_type() ) ) {
			$email_type = get_term_by( 'slug', $email_term, bp_get_email_tax_type() );

			$email_types[ $email_term ]['term_id'] = $email_type->term_id;
		} else {
			$term = wp_insert_term(
				$email_term,
				bp_get_email_tax_type(),
				array(
					'description' => $term_args['description'],
				)
			);

			$email_types[ $email_term ]['term_id'] = $term['term_id'];
		}

		// Insert Email templates if needed.
		if ( ! empty( $email_types[ $email_term ]['term_id'] ) && ! is_a( bp_get_email( $email_term ), 'BP_Email' ) ) {
			wp_insert_post(
				array(
					'post_status'  => 'publish',
					'post_type'    => bp_get_email_post_type(),
					'post_title'   => $email_types[ $email_term ]['post_title'],
					'post_content' => $email_types[ $email_term ]['post_content'],
					'post_excerpt' => $email_types[ $email_term ]['post_excerpt'],
					'tax_input'    => array(
						bp_get_email_tax_type() => array( $email_types[ $email_term ]['term_id'] ),
					),
				)
			);
		}
	}

	if ( $switched ) {
		restore_current_blog();
	}
}

/**
 * Updates the plugin when needed.
 *
 * @since 1.0.0
 */
function communaute_protegee_update() {
	if ( (int) get_current_blog_id() !== (int) bp_get_root_blog_id() ) {
		return;
	}

	$db_version      = bp_get_option( 'communaute-protegee-version', 0 );
	$current_version = communaute_protegee()->version;

	// First install.
	if ( ! $db_version ) {
		// Make sure to install emails only once!
		remove_action( 'bp_core_install_emails', 'communaute_protegee_install_emails' );

		// Install emails.
		communaute_protegee_install_emails();
	}

	// Update.
	if ( version_compare( $db_version, $current_version, '<' ) ) {
		// Update the db version.
		bp_update_option( 'communaute-protegee-version', $current_version );
	}
}

/**
 * Gets the absolute path of the Uploads htaccess file.
 *
 * @since 1.0.0
 *
 * @return string The absolute path of the Uploads htaccess file.
 */
function communaute_protegee_get_uploads_htaccess_path() {
	$data = wp_upload_dir();
	$path = trailingslashit( $data['basedir'] );

	return $path . '.htaccess';
}

/**
 * Creates an htaccess file to restrict the access to /wp-content/uploads to logged in users.
 *
 * @since 1.0.0
 */
function communaute_protegee_restrict_uploads_access_for_apache() {
	if ( file_exists( communaute_protegee_get_uploads_htaccess_path() ) ) {
		return;
	}

	// Include admin functions to get access to insert_with_markers().
	require_once ABSPATH . 'wp-admin/includes/misc.php';

	$home = trailingslashit( get_option( 'home' ) );
	$base = wp_parse_url( $home, PHP_URL_PATH );

	// Defining the rule: users need to be logged in to access private media.
	$rules = array(
		'<IfModule mod_rewrite.c>',
		'RewriteEngine On',
		sprintf( 'RewriteBase %s', $base ),
		'RewriteCond %{HTTP_COOKIE} !^.*wordpress_logged_in.*$ [NC]',
		'RewriteRule  .* wp-login.php [NC,L]',
		'</IfModule>',
	);

	// Create the .htaccess file.
	insert_with_markers( communaute_protegee_get_uploads_htaccess_path(), 'Communautée Protégéé', $rules );
}

/**
 * Removes the htaccess file to free the access to /wp-content/uploads.
 *
 * @since 1.0.0
 */
function communaute_protegee_free_uploads_access_for_apache() {
	if ( ! file_exists( communaute_protegee_get_uploads_htaccess_path() ) ) {
		return;
	}

	unlink( communaute_protegee_get_uploads_htaccess_path() );
}

/**
 * Sanitizes the setting to restrict the `/wp-content/uploads` access.
 *
 * NB: the .htaccess file is managed at this step.
 *
 * @since 1.0.0
 *
 * @param boolean $value The value of the setting.
 * @return boolean The sanitized value of the setting.
 */
function communaute_protegee_uploads_dir_restriction_sanitize_callback( $value = false ) {
	$option = (bool) $value;

	if ( $value ) {
		communaute_protegee_restrict_uploads_access_for_apache();
	} else {
		communaute_protegee_free_uploads_access_for_apache();
	}

	return $option;
}

/**
 * Outputs the checkbox to activate the setting to restrict the `/wp-content/uploads` access.
 *
 * @since 1.0.0
 */
function communaute_protegee_uploads_dir_restriction_callback() {
	$option = bp_get_option( 'communaute_protegee_uploads_dir_restriction', false );
	?>
	<input id="communaute-protegee-uploads-dir-restriction" name="communaute_protegee_uploads_dir_restriction" type="checkbox" value="1" <?php checked( $option ); ?> />
	<label for="communaute-protegee-uploads-dir-restriction"><?php esc_html_e( 'Restreindre la visibilité des media téléversés aux utilisateurs connectés.', 'communaute-protegee' ); ?></label>
	<?php
}

/**
 * Registers and add the setting to restrict the `/wp-content/uploads` access.
 *
 * @since 1.0.0
 */
function communaute_protegee_add_uploads_dir_restriction_field() {
	global $is_apache;

	if ( ! bp_is_root_blog() || ! $is_apache ) {
		return;
	}

	register_setting(
		'media',
		'communaute_protegee_uploads_dir_restriction',
		array(
			'type'              => 'boolean',
			'sanitize_callback' => 'communaute_protegee_uploads_dir_restriction_sanitize_callback',
			'show_in_rest'      => false,
			'default'           => false,
		)
	);

	add_settings_field(
		'communaute_protegee_uploads_dir_restriction',
		__( 'Visibilité du répertoire des téléversements', 'communaute-protegee' ),
		'communaute_protegee_uploads_dir_restriction_callback',
		'media',
		'uploads'
	);
}

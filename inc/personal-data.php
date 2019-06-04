<?php
/**
 * Communauté Blindée personal data functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Disable WP/BP Display Name synchronization.
add_filter( 'bp_disable_profile_sync', '__return_true' );

function communaute_blindee_get_encrypted_user_field_ids() {
	global $wpdb;
	$communaute_blindee = communaute_blindee();

	if ( ! isset( $communaute_blindee->encrypted_field_ids ) ) {
		$field_ids = $wpdb->get_col( "
			SELECT object_id FROM {$wpdb->xprofile_fieldmeta}
			WHERE object_type = 'field' AND meta_key = '_communaute_blindee_encrypted_field' AND meta_value = 'user_field'"
		);

		$communaute_blindee->encrypted_field_ids = array_map( 'intval', $field_ids );
	}

	return $communaute_blindee->encrypted_field_ids;
}

function communaute_blindee_xprofile_get_wp_list_table_field_names() {
	global $wpdb;
	$communaute_blindee = communaute_blindee();

	if ( ! isset( $communaute_blindee->wp_list_table_field_names ) ) {
		$tb_prefix    = bp_core_get_table_prefix();
		$x_metatable  =  $tb_prefix . 'bp_xprofile_meta';
		$x_fieldtable =  $tb_prefix . 'bp_xprofile_fields';

		$field_names = $wpdb->get_results( "
			SELECT f.id, f.name FROM {$x_fieldtable} f LEFT JOIN {$x_metatable} m ON( f.id = m.object_id )
			WHERE object_type = 'field' AND meta_key = '_communaute_blindee_wp_list_table_field' ORDER BY f.id ASC"
		);

		$communaute_blindee->wp_list_table_field_names = wp_list_pluck( $field_names, 'name', 'id' );
	}

	return $communaute_blindee->wp_list_table_field_names;
}

function communaute_blindee_xprofile_get_encrypted_specific_field_ids() {
	$communaute_blindee = communaute_blindee();

	if ( ! $communaute_blindee->encrypted_specific_fields ) {
		global $wpdb;
		$x_metatable = bp_core_get_table_prefix() . 'bp_xprofile_meta';

		$specific_fields = $wpdb->get_results( "
			SELECT meta_value, object_id FROM {$x_metatable}
			WHERE object_type = 'field' AND meta_key = '_communaute_blindee_encrypted_field' AND meta_value IN( 'user_login', 'user_email' )"
		);

		$communaute_blindee->encrypted_specific_fields = array_map( 'intval', wp_list_pluck( $specific_fields, 'object_id', 'meta_value' ) );
	}

	return $communaute_blindee->encrypted_specific_fields;
}

function communaute_blindee_xprofile_get_encrypted_specific_field_id( $type = '' ) {
	if ( ! $type ) {
		return 0;
	}

	if ( ! isset( $communaute_blindee->encrypted_specific_fields[ $type ] ) ) {
		global $wpdb;
		$x_metatable        = bp_core_get_table_prefix() . 'bp_xprofile_meta';
		$communaute_blindee = communaute_blindee();

		$communaute_blindee->encrypted_specific_fields[ $type ] = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT object_id FROM {$x_metatable}
				WHERE object_type = 'field' AND meta_key = '_communaute_blindee_encrypted_field' AND meta_value = %s",
				$type
			)
		);
	}

	return $communaute_blindee->encrypted_specific_fields[ $type ];
}

function communaute_blindee_xprofile_get_encrypted_email_field_id() {
	return communaute_blindee_xprofile_get_encrypted_specific_field_id( 'user_email' );
}

function communaute_blindee_xprofile_get_encrypted_login_field_id() {
	return communaute_blindee_xprofile_get_encrypted_specific_field_id( 'user_login' );
}

function communaute_blindee_get_wp_users_fields() {
	$communaute_blindee = communaute_blindee();

	if ( ! isset( $communaute_blindee->wp_users_db_fields ) ) {
		global $wpdb;

		$wp_users                               = $wpdb->get_results( "DESCRIBE $wpdb->users");
		$communaute_blindee->wp_users_db_fields = wp_list_pluck( $wp_users, 'Field' );
	}

	return $communaute_blindee->wp_users_db_fields;
}

function communaute_blindee_get_user_by_query( $db_query = '' ) {
	global $wpdb;

	// This is used by WordPress to get a user see WP_User->get_data_by().
	$regex = "/SELECT \* FROM $wpdb->users WHERE (ID|user_nicename|user_email|user_login) = (.*?) LIMIT 1/";

	if ( preg_match( $regex, $db_query, $matches ) ) {
		// Get the encrypted specific field IDs
		$field_ids = communaute_blindee_xprofile_get_encrypted_specific_field_ids();

		// Do not touch anything if we have no encrypted email field.
		if ( ! $field_ids ) {
			return $db_query;
		}

		// Get the list of the WP Users fields and the xProfile data table name.
		$fields = communaute_blindee_get_wp_users_fields();

		// It's a lost password request.
		if ( did_action( 'login_form_lostpassword' ) ) {
			if ( ! isset( $matches[1] ) || ! $matches[1] || ! isset( $matches[2] ) || ! $matches[2] ) {
				return $db_query;
			}

			$user_id = communaute_blindee_bypass_login_request( array(
				trim( $matches[1], '\'"' ) => trim( $matches[2], '\'"' ),
			) );

			if ( ! $user_id ) {
				add_filter( 'retrieve_password_title', 'communaute_blindee_trace_direct_password_reset', 10, 2 );
				return $db_query;
			}

			$lost_pass_fields = $fields;
			$email_index      = array_search( 'user_email', $lost_pass_fields );
			if ( false === $email_index ) {
				return $db_query;
			}

			$encrypted_email = communaute_blindee_get_user_encrypted_email( $user_id );
			if ( ! $encrypted_email ) {
				return $db_query;
			}

			$lost_pass_fields[ $email_index ] = '"' . communaute_blindee_decrypt( $encrypted_email ) . '" AS user_email';
			$query_fields                     = join( ', ', $lost_pass_fields );

			$db_query = $wpdb->prepare( "SELECT {$query_fields} FROM {$wpdb->users} WHERE ID = %d", $user_id );

			add_filter( 'retrieve_password_message', 'communaute_blindee_password_message', 10, 4 );

		// It's a login request.
		} elseif ( did_action( 'login_form_login' ) ) {
			if ( ! isset( $matches[1] ) || ! $matches[1] || ! isset( $matches[2] ) || ! $matches[2] ) {
				return $db_query;
			}

			$user_id = communaute_blindee_bypass_login_request( array(
				trim( $matches[1], '\'"' ) => trim( $matches[2], '\'"' ),
			) );

			if ( ! $user_id ) {
				add_action( 'wp_login', 'communaute_blindee_trace_direct_login', 10, 1 );

				return $db_query;
			}

			$db_query = $wpdb->prepare( "SELECT * FROM {$wpdb->users} WHERE ID = %d", $user_id );

		// It's a regular request.
		} else {
			$x_datatable = bp_core_get_table_prefix() . 'bp_xprofile_data';

			foreach ( $field_ids as $key_field => $field ) {
				$field_index = array_search( $key_field, $fields );

				if ( false === $field_index ) {
					continue;
				}

				// Override the user email field with a query to get the encrypted email value.
				$fields[ $field_index ] = '( ' . $wpdb->prepare( "SELECT x.value FROM {$x_datatable} x WHERE x.user_id = {$wpdb->users}.ID AND x.field_id = %d", $field ) . ' )  as ' . $key_field;
				$fields[] = "{$wpdb->users}.{$key_field} as " . str_replace( 'user', 'fake', $key_field );
			}

			// Override the user query.
			$db_query = str_replace( 'SELECT * FROM', 'SELECT ' . join( ', ', $fields ) . ' FROM', $db_query );
		}
	}

	return $db_query;
}
add_filter( 'query', 'communaute_blindee_get_user_by_query', 10, 1 );

function communaute_blindee_get_user_encrypted_specific_field( $user_id = 0, $type = '' ) {
	if ( ! $user_id || ! $type ) {
		return null;
	}

	/**
	 *
	 * @todo add some object cache here.
	 *
	 */

	global $wpdb;
	$field_id = communaute_blindee_xprofile_get_encrypted_specific_field_id( $type );

	// Do not touch anything if we have no corresponding encrypted specific field.
	if ( ! $field_id ) {
		return null;
	}

	$x_datatable = bp_core_get_table_prefix() . 'bp_xprofile_data';

	// Returns the encrypted specific field.
	return $wpdb->get_var( $wpdb->prepare( "SELECT value FROM {$x_datatable} WHERE user_id = %d AND field_id = %d", $user_id, $field_id ) );
}

function communaute_blindee_get_user_by_hashed_meta( $field_id = 0, $hashed_value = '' ) {
	if ( ! $hashed_value || ! $field_id ) {
		return null;
	}

	global $wpdb;
	$x_metatable = bp_core_get_table_prefix() . 'bp_xprofile_meta';
	$x_datatable = bp_core_get_table_prefix() . 'bp_xprofile_data';

	// Try to find a match.
	return (int) $wpdb->get_var(
		$wpdb->prepare( "
			SELECT d.user_id FROM {$x_metatable} m LEFT JOIN {$x_datatable} d ON( m.object_id = d.id )
			WHERE m.object_type = 'data' AND m.meta_key = %s AND m.meta_value =%s",
			'_communaute_blindee_hash_' . $field_id,
			$hashed_value
		)
	);
}

function communaute_blindee_get_user_encrypted_email( $user_id = 0 ) {
	return communaute_blindee_get_user_encrypted_specific_field( $user_id, 'user_email' );
}

function communaute_blindee_get_user_encrypted_login( $user_id = 0 ) {
	return communaute_blindee_get_user_encrypted_specific_field( $user_id, 'user_login' );
}

function communaute_blindee_get_userdata( $userdata = null ) {
	if ( ! isset( $userdata->ID ) ) {
		return $userdata;
	}

	$encrypted_specific_fields = communaute_blindee_xprofile_get_encrypted_specific_field_ids();

	// The user was set earlier than our filter
	if ( isset( $encrypted_specific_fields['user_email'] ) ) {
		if ( ! isset( $userdata->fake_email ) || ! $userdata->fake_email ) {
			$userdata->fake_email = $userdata->user_email;
			$userdata->user_email = null;

			// Retry to get the real email.
			$encrypted_email = communaute_blindee_get_user_encrypted_email( $userdata->ID );
			if ( ! is_null( $encrypted_email ) ) {
				$userdata->user_email = $encrypted_email;
			}
		}

		if ( ! $userdata->user_email ) {
			$userdata->user_email = $userdata->fake_email;
		} else {
			$userdata->user_email = communaute_blindee_decrypt( $userdata->user_email );
		}
	}

	if ( isset( $encrypted_specific_fields['user_login'] ) ) {
		if ( ! isset( $userdata->fake_login ) || ! $userdata->fake_login ) {
			$userdata->fake_login = $userdata->user_login;
			$userdata->user_login = null;

			// Retry to get the real email.
			$encrypted_login = communaute_blindee_get_user_encrypted_login( $userdata->ID );
			if ( ! is_null( $encrypted_login ) ) {
				$userdata->user_login = $encrypted_login;
			}
		}

		if ( ! $userdata->user_login ) {
			$userdata->user_login = $userdata->fake_login;
		} else {
			$userdata->user_login = communaute_blindee_decrypt( $userdata->user_login );
		}
	}

	return $userdata;
}
add_filter( 'bp_core_get_core_userdata', 'communaute_blindee_get_userdata', 1, 1 );

function communaute_blindee_set_current_user() {
	global $current_user;
	$current_user->data = communaute_blindee_get_userdata( $current_user->data );
}
add_action( 'set_current_user', 'communaute_blindee_set_current_user' );

function communaute_blindee_set_personal_options( $profileuser ) {
	global $profileuser;
	$profileuser->data = communaute_blindee_get_userdata( $profileuser->data );
}
add_action( 'personal_options', 'communaute_blindee_set_personal_options', 10, 1 );

function communaute_blindee_xprofile_encrypt_update_meta( BP_XProfile_Field $field ) {
	$encrypted_field = bp_xprofile_get_meta( $field->id, 'field', '_communaute_blindee_encrypted_field' );
	$updated_value   = '';

	if ( isset( $_POST['_communaute_blindee_encrypted_field'] ) && in_array( $_POST['_communaute_blindee_encrypted_field'], array(
		'user_field',
		'user_login',
		'user_email',
	), true ) ) {
		$updated_value = $_POST['_communaute_blindee_encrypted_field'];
	}

	if ( $updated_value ) {
		bp_xprofile_update_meta( $field->id, 'field', '_communaute_blindee_encrypted_field', $updated_value );
	} elseif ( $encrypted_field ) {
		bp_xprofile_delete_meta( $field->id, 'field', '_communaute_blindee_encrypted_field' );
	}

	$wp_list_table_field = (int) bp_xprofile_get_meta( $field->id, 'field', '_communaute_blindee_wp_list_table_field' );
	if ( isset( $_POST['_communaute_blindee_wp_list_table_field'] ) ) {
		bp_xprofile_update_meta( $field->id, 'field', '_communaute_blindee_wp_list_table_field', 1 );
	} elseif ( $wp_list_table_field ) {
		bp_xprofile_delete_meta( $field->id, 'field', '_communaute_blindee_wp_list_table_field' );
	}
}
add_action( 'xprofile_fields_saved_field', 'communaute_blindee_xprofile_encrypt_update_meta', 10, 1 );

function communaute_blindee_xprofile_encrypt_metabox( BP_XProfile_Field $field ) {
	$encrypted_field     = bp_xprofile_get_meta( $field->id, 'field', '_communaute_blindee_encrypted_field' );
	$wp_list_table_field = (int) bp_xprofile_get_meta( $field->id, 'field', '_communaute_blindee_wp_list_table_field' );

	if ( ! $encrypted_field ) {
		$encrypted_field = 0;
	}

	$encrypted_specifics = communaute_blindee_xprofile_get_encrypted_specific_field_ids();
	$specific_labels     = array(
		'user_login' => __( 'Encrypted login.', 'communaute-blindee' ),
		'user_email' => __( 'Encrypted email.', 'communaute-blindee' ),
	);
	?>
	<div class="postbox">
		<h2><label for="required"><?php esc_html_e( 'Encryption', 'communaute-blindee' ); ?></label></h2>
		<div class="inside">
			<p class="description"><?php esc_html_e( 'Should the data user will provide this field with be encrypted?', 'communaute-blindee' ); ?></p>
			<ul>
				<li>
					<label for="communaute-blindee-uncrypted-data">
						<input name="_communaute_blindee_encrypted_field" id="communaute-blindee-uncrypted-data" class="widefat" type="radio" value="0" <?php checked( 0, $encrypted_field ); ?>/>
						<?php esc_html_e( 'No encryption.', 'communaute-blindee' ); ?>
					</label>
				</li>
				<li>
					<label for="communaute-blindee-encrypted-data">
						<input name="_communaute_blindee_encrypted_field" id="communaute-blindee-encrypted-data" class="widefat" type="radio" value="user_field" <?php checked( 'user_field', $encrypted_field ); ?>/>
						<?php esc_html_e( 'Encrypted data.', 'communaute-blindee' ); ?>
					</label>
				</li>
				<?php foreach ( $specific_labels as $key_label => $label ) :
					if ( ! isset( $encrypted_specifics[ $key_label ] ) || $encrypted_specifics[ $key_label ] === $field->id ) : ?>
					<li>
						<label for="<?php printf( 'communaute-blindee-encrypted-%s', esc_attr( $key_label ) ); ?>">
							<input name="_communaute_blindee_encrypted_field" id="<?php printf( 'communaute-blindee-encrypted-%s', esc_attr( $key_label ) ); ?>" class="widefat" type="radio" value="<?php echo esc_attr( $key_label ); ?>" <?php checked( isset( $encrypted_specifics[ $key_label ] ) && $field->id === $encrypted_specifics[ $key_label ] ); ?>/>
							<?php echo esc_html( $label ); ?>
						</label>
					</li>
				<?php endif;endforeach ; ?>
			</ul>
			<p class="description"><?php esc_html_e( 'Should this field be used in WordPress Users list tables?', 'communaute-blindee' ); ?></p>
			<p>
				<label for="communaute-blindee-wp-list-table">
					<input name="_communaute_blindee_wp_list_table_field" id="communaute-blindee-wp-list-table" class="widefat" type="checkbox" value="1" <?php checked( 1, $wp_list_table_field ); ?>/>
					<?php esc_html_e( 'Display in WordPress Users list tables.', 'communaute-blindee' ); ?>
				</label>
			</p>
		</div>
	</div>
	<?php
}
add_action( 'xprofile_field_after_sidebarbox', 'communaute_blindee_xprofile_encrypt_metabox', 10, 1 );

function communaute_blindee_get_email_suffix() {
	$suffix = strtolower( $_SERVER['SERVER_NAME'] );
	if ( substr( $suffix, 0, 4 ) == 'www.' ) {
		$suffix = substr( $suffix, 4 );
	}

	return '@' . $suffix;
}

function communaute_blindee_get_fake_email() {
	remove_filter( 'query', 'communaute_blindee_get_user_by_query', 10, 1 );

	$prefix = 'no-reply.';
	$suffix = communaute_blindee_get_email_suffix();

	$email  = $prefix . communaute_blindee_generate_random_string() . $suffix;
	$email_check = email_exists( $email );

	if ( $email_check ) {
		while ( $email_check ) {
			$alt_email = $prefix . communaute_blindee_generate_random_string() . $suffix;
			$email_check = email_exists( $alt_email );
		}
		$email = $alt_email;
	}

	add_filter( 'query', 'communaute_blindee_get_user_by_query', 10, 1 );

	return $email;
}

function communaute_blindee_get_fake_login() {
	remove_filter( 'query', 'communaute_blindee_get_user_by_query', 10, 1 );

	$login = communaute_blindee_generate_random_string( 14 );
	$login_check = username_exists( $login );

	if ( $login_check ) {
		while ( $login_check ) {
			$alt_login = communaute_blindee_generate_random_string( 14 );
			$login_check = username_exists( $alt_login );
		}
		$login = $alt_login;
	}

	add_filter( 'query', 'communaute_blindee_get_user_by_query', 10, 1 );

	return $login;
}

/**
 * Encrypt Profile fieds used during the user registration.
 *
 * @since 1.0.0
 *
 * @param array $args Signup Data.
 * @return array Signup Data.
 */
function communaute_blindee_before_signup_save( $args = array() ) {
	if ( ! isset( $args['meta']['profile_field_ids'] ) ) {
		return $args;
	}

	$profile_field_ids = explode( ',', $args['meta']['profile_field_ids'] );
	$encrypted_fields  = communaute_blindee_get_encrypted_user_field_ids();
	$contains_hash     = array();

	foreach ( $profile_field_ids as $field_id ) {
		if ( ! in_array( (int) $field_id, $encrypted_fields, true ) ) {
			continue;
		}

		if ( isset( $args['meta']['field_' . $field_id] ) ) {
			$args['meta']['field_' . $field_id] = communaute_blindee_encrypt( $args['meta']['field_'  . $field_id] );
		}
	}

	/**
	 * Specific step to encrypt the real login into the login profile field
	 * and to replace the WP_User->user_login with a fake one as its db field
	 * is only 60 chars long.
	 */
	$encrypted_login_field_id = communaute_blindee_xprofile_get_encrypted_login_field_id();

	if ( $encrypted_login_field_id ) {
		// Add it to the list of field IDs.
		$profile_field_ids[]               = $encrypted_login_field_id;
		$args['meta']['profile_field_ids'] = implode( ',', wp_parse_id_list( $profile_field_ids ) );

		// Add a new meta containing the encrypted email.
		$args['meta']['field_' . $encrypted_login_field_id] = communaute_blindee_encrypt( $args['user_login'] );

		// Add new value meta to query login hash (eg: during authentification).
		$args['meta']['field_' . $encrypted_login_field_id . '_hash_meta'] = wp_hash( $args['user_login'] );
		$contains_hash[] = $encrypted_login_field_id;

		/**
		 * @todo check encrypted logins are unique.
		 */

		// Replace the login with a randomized one.
		$args['user_login']= communaute_blindee_get_fake_login();
	}

	/**
	 * Specific step to encrypt the real email into the email profile field
	 * and to replace the WP_User->user_email with a fake one as its db field
	 * is only 100 chars long.
	 */
	$encrypted_email_field_id = communaute_blindee_xprofile_get_encrypted_email_field_id();

	if ( $encrypted_email_field_id ) {
		// Add it to the list of field IDs.
		$profile_field_ids[]               = $encrypted_email_field_id;
		$args['meta']['profile_field_ids'] = implode( ',', wp_parse_id_list( $profile_field_ids ) );

		// Add a new meta containing the encrypted email.
		$args['meta']['field_' . $encrypted_email_field_id] = communaute_blindee_encrypt( $args['user_email'] );

		// Add new value meta to query login hash (eg: during authentification).
		$args['meta']['field_' . $encrypted_email_field_id . '_hash_meta'] = wp_hash( $args['user_email'] );
		$contains_hash[] = $encrypted_email_field_id;

		/**
		 * @todo check encrypted emails are unique.
		 */

		// Replace the email with a randomized one.
		if ( ! $encrypted_login_field_id ) {
			$args['user_email'] = communaute_blindee_get_fake_email();
		} else {
			$args['user_email'] = 'no-reply.' . $args['user_login'] . communaute_blindee_get_email_suffix();
		}
	}

	if ( $contains_hash ) {
		$args['meta']['contains_hash'] = join( ',', $contains_hash );
	}

	return $args;
}
add_filter( 'bp_after_bp_core_signups_add_args_parse_args', 'communaute_blindee_before_signup_save' );

/**
 * @todo Multisite registration.
 */
function communaute_blindee_before_multisite_signup_save( $meta = array() ) {
	if ( ! isset( $meta['profile_field_ids'] ) ) {
		return $meta;
	}

	$encrypted = communaute_blindee_before_signup_save( array( 'meta' => $meta ) );
	return reset( $encrypted );
}
//add_filter( 'signup_user_meta', 'communaute_blindee_before_multisite_signup_save', 10, 1 );

function communaute_blindee_activated_user( $user_id = 0, $key = '', $user = array() ) {
	if ( ! isset( $user['meta']['contains_hash'] ) || ! $user_id ) {
		errorlog( 'un probleme avec les meta: ' . $user['meta']['contains_hash'] . ' ou user_id:' . $user_id . "\n" );
		return;
	}

	$metas = explode( ',', $user['meta']['contains_hash'] );
	foreach ( $metas as $field_id ) {
		if ( ! isset( $user['meta']['field_' . $field_id . '_hash_meta'] ) ) {
			continue;
		}

		$hash          = $user['meta']['field_' . $field_id . '_hash_meta'];
		$field_data_id = BP_XProfile_ProfileData::get_fielddataid_byid( $field_id, $user_id );
		bp_xprofile_update_meta( $field_data_id, 'data', '_communaute_blindee_hash_' . $field_id, $hash );
	}
}
add_action( 'bp_core_activated_user', 'communaute_blindee_activated_user', 10, 3 );

/**
 * Is the current page a page to activate signups ?
 *
 * @since 1.0.0
 *
 * @return boolean True if it's an activation page, false otherwise.
 */
function communaute_blindee_activating_signup() {
	$uri_parts = wp_parse_url( $_SERVER['REQUEST_URI'] );
	$return    = false;

	if ( ! isset( $uri_parts['path'] ) && ! isset( $uri_parts['query'] ) ) {
		return $return;
	}

	// Check front-end activation.
	$activate_url_path = wp_parse_url( bp_get_activation_page(), PHP_URL_PATH );
	if ( false !== strpos( $uri_parts['path'], $activate_url_path ) ) {
		$return = true;

	// Check back-end activation.
	} elseif ( false !== strpos( $uri_parts['path'], '/wp-admin/users.php' ) && isset( $uri_parts['query'] ) ) {
		$query_args = wp_parse_args( $uri_parts['query'], array( 'page' => '' ) );
		$return     = 'bp-signups' === $query_args['page'];
	}

	return $return;
}

function communaute_blindee_get_xprofile_columns() {
	$xprofile_columns = communaute_blindee_xprofile_get_wp_list_table_field_names();

	foreach ( $xprofile_columns as $k_column => $column_name ) {
		$columns['field_' . intval( $k_column ) ] = $column_name;
	}

	return $columns;
}

function communaute_blindee_users_columns( $columns = array() ) {
	$xprofile_columns = communaute_blindee_get_xprofile_columns();
	unset( $columns['name'] );

	if ( communaute_blindee_xprofile_get_encrypted_email_field_id() ) {
		unset( $columns['email'] );
	}

	return array_merge( $columns, $xprofile_columns );
}
add_filter( 'manage_users_columns', 'communaute_blindee_users_columns', 10, 1 );
add_filter( 'manage_users_page_bp-signups_columns', 'communaute_blindee_users_columns', 10, 1 );

function communaute_blindee_users_columns_data( $value = '', $column_name = '', $user = null ) {
	$xprofile_columns = communaute_blindee_get_xprofile_columns();

	if ( ! in_array( $column_name, array_keys( $xprofile_columns ), true ) || ! $user ) {
		return $value;
	}

	$field_id = (int) str_replace( 'field_', '', $column_name );

	if ( is_object( $user ) && isset( $user->meta[ $column_name ] ) ) {
		$value = communaute_blindee_decrypt( $user->meta[ $column_name ] );
	} else {
		$value = xprofile_get_field_data( $field_id, $user, 'comma' );

		if ( in_array( $field_id, communaute_blindee_xprofile_get_encrypted_specific_field_ids(), true ) ) {
			$value = communaute_blindee_decrypt( $value );
		}
	}

	return $value;
}
add_filter( 'manage_users_custom_column', 'communaute_blindee_users_columns_data', 10, 3 );
add_filter( 'bp_members_signup_custom_column', 'communaute_blindee_users_columns_data', 10, 3 );

function communaute_blindee_xprofile_encrypt_data_before_save( $value = '', $id = 0, $reserialize = true, BP_XProfile_ProfileData $profile_data ) {
	if ( ! $value || ! isset( $profile_data->field_id ) || communaute_blindee_activating_signup() ) {
		return $value;
	}

	$encrypted_fields = communaute_blindee_get_encrypted_user_field_ids();

	if ( in_array( (int) $profile_data->field_id, $encrypted_fields, true ) ) {
		$value = communaute_blindee_encrypt( $value );
	}

	return $value;
}
add_filter( 'xprofile_data_value_before_save', 'communaute_blindee_xprofile_encrypt_data_before_save', 2, 4 );

function communaute_blindee_xprofile_decrypt_data( $value = '', $field_id = 0  ) {
	if ( ! $value || ! $field_id ) {
		return $value;
	}

	$encrypted_fields = communaute_blindee_get_encrypted_user_field_ids();

	if ( in_array( (int) $field_id, $encrypted_fields, true ) ) {
		$serialized_value = maybe_serialize( $value );

		if ( is_serialized( $serialized_value ) ) {
			$value = $serialized_value;
		}

		$value = communaute_blindee_decrypt( $value );

		if ( is_serialized( $value ) ) {
			$value = maybe_unserialize( $value );
		}
	}

	return $value;
}
add_filter( 'xprofile_get_field_data', 'communaute_blindee_xprofile_decrypt_data', 0, 2 );

function communaute_blindee_xprofile_loop_decrypt( $has_profile = false  ) {
	if ( ! $has_profile ) {
		return $has_profile;
	}

	global $profile_template;

	if ( isset( $profile_template->groups ) && is_array( $profile_template->groups ) ) {
		foreach ( $profile_template->groups as $kg => $group ) {
			if ( ! isset( $group->fields ) || ! is_array( $group->fields ) ) {
				continue;
			}

			foreach ( $group->fields as $kf => $field ) {
				if ( ! isset( $field->data->value ) || ! $field->data->value || ! isset( $field->id ) ) {
					continue;
				}

				$profile_template->groups[ $kg ]->fields[ $kf ]->data->value = communaute_blindee_xprofile_decrypt_data( $field->data->value, $field->id );
			}
		}
	}

	return $has_profile;
}
add_filter( 'bp_has_profile', 'communaute_blindee_xprofile_loop_decrypt', 0, 1 );

function communaute_blindee_remove_specific_fields_from_front_loops( $args = array() ) {
	$encrypted_specific_ids = communaute_blindee_xprofile_get_encrypted_specific_field_ids();

	if ( ! $encrypted_specific_ids ) {
		return $args;
	}

	return array_merge( $args, array(
		'exclude_fields' => array_values( $encrypted_specific_ids ),
	) );
}
add_filter( 'bp_after_has_profile_parse_args', 'communaute_blindee_remove_specific_fields_from_front_loops', 10, 1 );

function communaute_blindee_bypass_login_request( $args = array() ) {
	$user_id = 0;
	$request = wp_parse_args( $args, array(
		'user_login' => null,
		'user_email' => null,
	) );

	if ( $request['user_login'] ) {
		$field_id = communaute_blindee_xprofile_get_encrypted_login_field_id();
		$hash = wp_hash( $request['user_login'] );
	} else {
		$field_id = communaute_blindee_xprofile_get_encrypted_email_field_id();
		$hash = wp_hash( $request['user_email'] );
	}

	if ( $hash && isset( $field_id ) ) {
		$user_id = communaute_blindee_get_user_by_hashed_meta( $field_id, $hash );
	}

	return $user_id;
}

function communaute_blindee_trace_direct_password_reset( $title = '', $user_login = '' ) {
	// Prevent duplicates
	remove_filter( 'retrieve_password_title', 'communaute_blindee_trace_direct_password_reset', 10, 2 );

	// Trace this in logs (just in case)
	error_log( sprintf( __( 'Direct successful password reset request using %1$s from %2$s', 'communaute-blindee' ), $user_login, $_SERVER['REMOTE_ADDR'] ) );
}

function communaute_blindee_password_message( $message = '', $key = '', $user_login = '', $user_data = null ) {
	// Prevent duplicates
	remove_filter( 'retrieve_password_message', 'communaute_blindee_password_message', 10, 4 );

	$encrypted_login = communaute_blindee_get_user_encrypted_login( $user_data->ID );
	if ( ! $encrypted_login ) {
		return $message;
	}

	// Decrypt the login.
	$login = communaute_blindee_decrypt( $encrypted_login );

	// Use it in the user notification.
	return str_replace( sprintf( __( 'Username: %s' ), $user_login ) . "\r\n\r\n", sprintf( __( 'Username: %s' ), $login ) . "\r\n\r\n", $message );
}

function communaute_blindee_trace_direct_login( $user_login = '' ) {
	// Prevent duplicates
	remove_action( 'wp_login', 'communaute_blindee_trace_direct_login', 10, 1 );

	// Trace this in logs (just in case)
	error_log( sprintf( __( 'Direct successful authentification using %1$s from %2$s', 'communaute-blindee' ), $user_login, $_SERVER['REMOTE_ADDR'] ) );
}

function communaute_blindee_password_change_notification_email( $notification_data = array(), $user = null ) {
	if ( ! isset(  $user->ID ) ) {
		return $notification_data;
	}

	$encrypted_login = communaute_blindee_get_user_encrypted_login( $user->ID );
	if ( ! $encrypted_login ) {
		return $notification_data;
	}

	// Decrypt the login.
	$login = communaute_blindee_decrypt( $encrypted_login );
	if ( isset( $user->fake_login ) && $user->fake_login ) {
		$login .= sprintf( ' (%s)', $user->fake_login );
	}

	$notification_data['message'] = str_replace( sprintf( __( 'Password changed for user: %s' ), $user->user_login ) . "\r\n", sprintf( __( 'Password changed for user: %s' ), $login ) . "\r\n", $notification_data['message'] );

	// Use it in the admin notification.
	return $notification_data;
}
add_filter( 'wp_password_change_notification_email', 'communaute_blindee_password_change_notification_email', 10, 2 );

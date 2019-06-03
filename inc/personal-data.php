<?php
/**
 * Communauté Blindée personal data functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Disable WP/BP Display Name synchronization.
add_filter( 'bp_disable_profile_sync', '__return_true' );

function communaute_blindee_xprofile_get_encrypted_field_ids() {
	global $wpdb;
	$communaute_blindee = communaute_blindee();

	if ( ! isset( $communaute_blindee->encrypted_field_ids ) ) {
		$field_ids = $wpdb->get_col( "
			SELECT object_id FROM {$wpdb->xprofile_fieldmeta}
			WHERE object_type = 'field' AND meta_key = '_communaute_blindee_encrypted_field' AND meta_value = '1'"
		);

		$communaute_blindee->encrypted_field_ids = array_map( 'intval', $field_ids );
	}

	return $communaute_blindee->encrypted_field_ids;
}

function communaute_blindee_xprofile_get_encrypted_field_names() {
	global $wpdb;
	$communaute_blindee = communaute_blindee();

	/**
	 * @todo There should be another field meta to select the fields to
	 * display in WP_Users_List_Tables.
	 */

	if ( ! isset( $communaute_blindee->encrypted_field_names ) ) {
		$tb_prefix    = bp_core_get_table_prefix();
		$x_metatable  =  $tb_prefix . 'bp_xprofile_meta';
		$x_fieldtable =  $tb_prefix . 'bp_xprofile_fields';

		$field_names = $wpdb->get_results( "
			SELECT f.id, f.name FROM {$x_fieldtable} f LEFT JOIN {$x_metatable} m ON( f.id = m.object_id )
			WHERE object_type = 'field' AND meta_key = '_communaute_blindee_encrypted_field' ORDER BY f.id ASC"
		);

		$communaute_blindee->encrypted_field_names = wp_list_pluck( $field_names, 'name', 'id' );
	}

	return $communaute_blindee->encrypted_field_names;
}

function communaute_blindee_xprofile_get_encrypted_email_field_id() {
	global $wpdb;
	$x_metatable        = bp_core_get_table_prefix() . 'bp_xprofile_meta';
	$communaute_blindee = communaute_blindee();

	if ( ! isset( $communaute_blindee->encrypted_email_field_id ) ) {
		$communaute_blindee->encrypted_email_field_id = (int) $wpdb->get_var( "
			SELECT object_id FROM {$x_metatable}
			WHERE object_type = 'field' AND meta_key = '_communaute_blindee_encrypted_field' AND meta_value = '2'"
		);
	}

	return $communaute_blindee->encrypted_email_field_id;
}

function communaute_blindee_get_wp_users_fields() {
	global $wpdb;

	$wp_users = $wpdb->get_results( "DESCRIBE $wpdb->users");

	return wp_list_pluck( $wp_users, 'Field' );
}

function communaute_blindee_get_user_by_query( $db_query = '' ) {
	global $wpdb;

	// This is used by WordPress to get a user see WP_User->get_data_by().
	$regex = "/SELECT \* FROM $wpdb->users WHERE (ID|user_nicename|user_email|user_login) = (.*?) LIMIT 1/";

	if ( preg_match( $regex, $db_query, $matches ) ) {
		$field_id = communaute_blindee_xprofile_get_encrypted_email_field_id();

		// Do not touch anything if we have no encrypted email field.
		if ( ! $field_id ) {
			return $db_query;
		}

		// Get the list of the WP Users fields and the xProfile data table name.
		$fields      = communaute_blindee_get_wp_users_fields();
		$x_datatable = bp_core_get_table_prefix() . 'bp_xprofile_data';

		$user_email_index = array_search( 'user_email', $fields );
		if ( false === $user_email_index ) {
			return $db_query;
		}

		// Override the user email field with a query to get the encrypted email value.
		$fields[ $user_email_index ] = '( ' . $wpdb->prepare( "SELECT x.value FROM {$x_datatable} x WHERE x.user_id = {$wpdb->users}.ID AND x.field_id = %d", $field_id ) . ' )  as user_email';
		$fields[] = "{$wpdb->users}.user_email as fake_email";

		// Override the user query.
		$db_query = str_replace( 'SELECT * FROM', 'SELECT ' . join( ', ', $fields ) . ' FROM', $db_query );
	}

	return $db_query;
}
add_filter( 'query', 'communaute_blindee_get_user_by_query', 10, 1 );

function communaute_blindee_get_user_encrypted_email( $user_id = 0 ) {
	if ( ! $user_id ) {
		return null;
	}

	/**
	 *
	 * @todo add some object cache here.
	 *
	 */

	global $wpdb;
	$field_id = communaute_blindee_xprofile_get_encrypted_email_field_id();

	// Do not touch anything if we have no encrypted email field.
	if ( ! $field_id ) {
		return null;
	}

	$x_datatable = bp_core_get_table_prefix() . 'bp_xprofile_data';

	// Returns the encrypted email.
	return $wpdb->get_var( $wpdb->prepare( "SELECT value FROM {$x_datatable} WHERE user_id = %d AND field_id = %d", $user_id, $field_id ) );
}

function communaute_blindee_get_userdata( $userdata = null ) {
	if ( ! isset( $userdata->ID ) ) {
		return $userdata;
	}

	// The user was earlier than our filter
	if ( ! isset( $userdata->fake_email ) || ! $userdata->fake_email ) {
		// Retry to get the real email.
		$encrypted_email = communaute_blindee_get_user_encrypted_email( $userdata->ID );

		if ( is_null( $encrypted_email ) ) {
			return $userdata;
		}

		$userdata->fake_email = $userdata->user_email;
		$userdata->user_email = $encrypted_email;
	}

	if ( ! $userdata->user_email ) {
		$userdata->user_email = $userdata->fake_email;
	} else {
		$userdata->user_email = communaute_blindee_decrypt( $userdata->user_email );
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
	$encrypted_field = (int) bp_xprofile_get_meta( $field->id, 'field', '_communaute_blindee_encrypted_field' );
	$updated_value = 0;
	if ( isset( $_POST['_communaute_blindee_encrypted_field'] ) ) {
		$updated_value = (int) $_POST['_communaute_blindee_encrypted_field'];
	}

	if ( $updated_value ) {
		bp_xprofile_update_meta( $field->id, 'field', '_communaute_blindee_encrypted_field', $updated_value );
	} elseif ( $encrypted_field ) {
		bp_xprofile_delete_meta( $field->id, 'field', '_communaute_blindee_encrypted_field' );
	}
}
add_action( 'xprofile_fields_saved_field', 'communaute_blindee_xprofile_encrypt_update_meta', 10, 1 );

function communaute_blindee_xprofile_encrypt_metabox( BP_XProfile_Field $field ) {
	$encrypted_field       = (int) bp_xprofile_get_meta( $field->id, 'field', '_communaute_blindee_encrypted_field' );
	$encrypted_email_field = communaute_blindee_xprofile_get_encrypted_email_field_id();
	?>
	<div class="postbox">
		<h2><label for="required"><?php esc_html_e( 'Encryption', 'communaute-blindee' ); ?></label></h2>
		<div class="inside">
			<p class="description"><?php esc_html_e( 'Should the data user will provide this field with be encrypted?', 'communaute-blindee' ); ?></p>
			<ul>
				<li>
					<label for="communaute-blindee-uncrypted-data">
						<input name="_communaute_blindee_encrypted_field" id="communaute-blindee-uncrypted-data" class="widefat" type="radio" value="0" <?php checked( 0, $encrypted_field ); ?>/>
						<?php esc_html_e( 'No encryption.', 'communaute-blindee'); ?>
					</label>
				</li>
				<li>
					<label for="communaute-blindee-encrypted-data">
						<input name="_communaute_blindee_encrypted_field" id="communaute-blindee-encrypted-data" class="widefat" type="radio" value="1" <?php checked( 1, $encrypted_field ); ?>/>
						<?php esc_html_e( 'Encrypted data.', 'communaute-blindee'); ?>
					</label>
				</li>

				<?php
				// There can only be one email field as it overrides WP_User->user_email.
				if ( ! $encrypted_email_field || $field->id === $encrypted_email_field ) : ?>
					<li>
						<label for="communaute-blindee-encrypted-email">
							<input name="_communaute_blindee_encrypted_field" id="communaute-blindee-encrypted-email" class="widefat" type="radio" value="2" <?php checked( 2, $encrypted_field ); ?>/>
							<?php esc_html_e( 'Encrypted email.', 'communaute-blindee'); ?>
						</label>
					</li>
				<?php endif ; ?>
			</ul>
		</div>
	</div>
	<?php
}
add_action( 'xprofile_field_after_sidebarbox', 'communaute_blindee_xprofile_encrypt_metabox', 10, 1 );

function communaute_blindee_get_fake_email() {
	remove_filter( 'query', 'communaute_blindee_get_user_by_query', 10, 1 );

	$prefix = 'no-reply-';
	$suffix = '@fake.email';
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
	$encrypted_fields = communaute_blindee_xprofile_get_encrypted_field_ids();

	foreach ( $profile_field_ids as $field_id ) {
		if ( ! in_array( (int) $field_id, $encrypted_fields, true ) ) {
			continue;
		}

		if ( isset( $args['meta']['field_' . $field_id] ) ) {
			$args['meta']['field_' . $field_id] = communaute_blindee_encrypt( $args['meta']['field_'  . $field_id] );
		}
	}

	/**
	 * Specific step to encrypt the real email into the email profile field
	 * and to replace the WP_User->user_email with a fake one as its db field
	 * is only 100 chars long
	 */
	$encrypted_email_field_id = communaute_blindee_xprofile_get_encrypted_email_field_id();

	if ( $encrypted_email_field_id ) {
		// Add it to the list of field IDs.
		$profile_field_ids[]               = $encrypted_email_field_id;
		$args['meta']['profile_field_ids'] = implode( ',', wp_parse_id_list( $profile_field_ids ) );

		// Add a new meta containing the encrypted email.
		$args['meta']['field_' . $encrypted_email_field_id] = communaute_blindee_encrypt( $args['user_email'] );

		// Replace the email with a randomized one.
		$args['user_email'] = communaute_blindee_get_fake_email();
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

function communaute_blindee_decrypt_signup_objects( $return ) {
	global $bp_members_signup_list_table;

	if ( isset( $bp_members_signup_list_table->items ) && is_array( $bp_members_signup_list_table->items ) ) {
		foreach ( $bp_members_signup_list_table->items as $i => $signup ) {
			if ( isset( $signup->user_name ) ) {
				$bp_members_signup_list_table->items[$i]->user_name = communaute_blindee_decrypt( $signup->user_name );
			}
		}
	}

	return $return;
}
add_filter( 'bp_members_ms_signup_row_actions', 'communaute_blindee_decrypt_signup_objects', 10, 1 );

function communaute_blindee_get_xprofile_columns() {
	$xprofile_columns = communaute_blindee_xprofile_get_encrypted_field_names();

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

	return array_merge( $columns,$xprofile_columns );
}
add_filter( 'manage_users_columns', 'communaute_blindee_users_columns', 10, 1 );

function communaute_blindee_users_columns_data( $value = '', $column_name = '', $user_id = 0 ) {
	$xprofile_columns = communaute_blindee_get_xprofile_columns();

	if ( ! in_array( $column_name, array_keys( $xprofile_columns ), true ) || ! $user_id ) {
		return $value;
	}

	$field_id = (int) str_replace( 'field_', '', $column_name );
	$value    = xprofile_get_field_data( $field_id, $user_id, 'comma' );

	if ( communaute_blindee_xprofile_get_encrypted_email_field_id() === $field_id ) {
		$value    = communaute_blindee_decrypt( $value );
	}

	return $value;
}
add_filter( 'manage_users_custom_column', 'communaute_blindee_users_columns_data', 10, 3 );

function communaute_blindee_xprofile_encrypt_data_before_save( $value = '', $id = 0, $reserialize = true, BP_XProfile_ProfileData $profile_data ) {
	if ( ! $value || ! isset( $profile_data->field_id ) || communaute_blindee_activating_signup() ) {
		return $value;
	}

	$encrypted_fields = communaute_blindee_xprofile_get_encrypted_field_ids();

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

	$encrypted_fields = communaute_blindee_xprofile_get_encrypted_field_ids();

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

function communaute_blindee_remove_email_field_from_front_loops( $args = array() ) {
	$encrypted_email_field_id = communaute_blindee_xprofile_get_encrypted_email_field_id();

	if ( ! $encrypted_email_field_id ) {
		return $args;
	}
	return array_merge( $args, array(
		'exclude_fields' => array( $encrypted_email_field_id ),
	) );
}
add_filter( 'bp_after_has_profile_parse_args', 'communaute_blindee_remove_email_field_from_front_loops', 10, 1 );

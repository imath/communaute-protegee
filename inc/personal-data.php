<?php
/**
 * Communauté Blindée personal data functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Disable WP/BP Display Name synchronization.
add_filter( 'bp_disable_profile_sync', '__return_true' );

function communaute_blindee_xprofile_get_encrypted_fields() {
	global $wpdb;

	$field_ids = $wpdb->get_col( "
		SELECT object_id FROM {$wpdb->xprofile_fieldmeta}
		WHERE object_type = 'field' AND meta_key = '_communaute_blindee_encrypted_field' AND meta_value = '1'"
	);

	return array_map( 'intval', $field_ids );
}

function communaute_blindee_xprofile_encrypt_update_meta( BP_XProfile_Field $field ) {
	$encrypted_field = (int) bp_xprofile_get_meta( $field->id, 'field', '_communaute_blindee_encrypted_field' );

	if ( isset( $_POST['_communaute_blindee_encrypted_field'] ) && ! $encrypted_field ) {
		bp_xprofile_update_meta( $field->id, 'field', '_communaute_blindee_encrypted_field', 1 );
	} elseif ( $encrypted_field && ! isset( $_POST['_communaute_blindee_encrypted_field'] ) ) {
		bp_xprofile_delete_meta( $field->id, 'field', '_communaute_blindee_encrypted_field' );
	}
}
add_action( 'xprofile_fields_saved_field', 'communaute_blindee_xprofile_encrypt_update_meta', 10, 1 );

function communaute_blindee_xprofile_encrypt_metabox( BP_XProfile_Field $field ) {
	$encrypted_field = (int) bp_xprofile_get_meta( $field->id, 'field', '_communaute_blindee_encrypted_field' );
	?>
	<div class="postbox">
		<h2><label for="required"><?php esc_html_e( 'Encryption', 'communaute-blindee' ); ?></label></h2>
		<div class="inside">
			<p class="description"><?php esc_html_e( 'Should the data user will provide this field with be encrypted?', 'communaute-blindee' ); ?></p>
			<label for="communaute-blindee-encrypted">
				<input name="_communaute_blindee_encrypted_field" id="communaute-blindee-encrypted" class="widefat" type="checkbox" value="1" <?php checked( 1, $encrypted_field ); ?>/>
				<?php esc_html_e( 'Yes', 'communaute-blindee'); ?>
			</label>
		</div>
	</div>
	<?php
}
add_action( 'xprofile_field_after_sidebarbox', 'communaute_blindee_xprofile_encrypt_metabox', 10, 1 );

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

function communaute_blindee_xprofile_encrypt_data_before_save( $value = '', $id = 0, $reserialize = true, BP_XProfile_ProfileData $profile_data ) {
	if ( ! $value || ! isset( $profile_data->field_id ) || communaute_blindee_activating_signup() ) {
		return $value;
	}

	$encrypted_fields = communaute_blindee_xprofile_get_encrypted_fields();

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

	$encrypted_fields = communaute_blindee_xprofile_get_encrypted_fields();

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

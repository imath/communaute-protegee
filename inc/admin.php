<?php
/**
 * Communauté Blindée Admin functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Callback to display the setting field
 *
 * @since 1.0.0
 */
function communaute_blindee_limited_email_domains_setting_field() {
	$limited_email_domains = get_site_option( 'limited_email_domains' );
	$limited_email_domains = str_replace( ' ', "\n", $limited_email_domains );

	?>
	<textarea name="limited_email_domains" id="limited_email_domains" aria-describedby="limited-email-domains-desc" cols="45" rows="5"><?php echo esc_textarea( $limited_email_domains == '' ? '' : implode( "\n", (array) $limited_email_domains ) ); ?></textarea>
	<p class="description" id="limited-email-domains-desc">
		<?php _e( 'If you want to limit site registrations to certain domains. One domain per line.', 'bp-restricted-community' ) ?>
	</p>
	<?php
}

/**
 * Add a setting field on non ms configs to limit registrations by email domains
 *
 * @since 1.0.0
 */
function communaute_blindee_email_restrictions_setting_field() {
	add_settings_field(
		'limited_email_domains',
		__( 'Limited Email Registrations', 'communaute-blindee' ),
		'communaute_blindee_limited_email_domains_setting_field',
		'general',
		'default'
	);
}

/**
 * Whitelist the limited_email_domains for non ms configs
 *
 * @since 1.0.0
 */
function communaute_blindee_email_restrictions_add_option( $whitelist_options = array() ) {
	if ( isset( $whitelist_options['general'] ) ) {
		$whitelist_options['general'] = array_merge(
			$whitelist_options['general'],
			array( 'limited_email_domains' )
		);
	}

	return $whitelist_options;
}

/**
 * Use Javascript to move the restriction setting near the Membership one for regulare WordPress
 *
 * @since 1.0.0
 */
function communaute_blindee_move_restriction_settings( $hook_suffix ) {
	if ( 'options-general.php' === $hook_suffix ) {
		wp_add_inline_script( 'common', '
			( function( $ ) {
				$( \'#users_can_register\' ).closest( \'tr\' ).after( $( \'#limited_email_domains\' ).closest( \'tr\' ) );
			} )( jQuery );
		' );
	}
}

function communaute_blindee_user_admin_screens() {
	if ( ! bp_is_active( 'xprofile' ) ) {
		return;
	}

	$action = '';
	$cb     = communaute_blindee();

	if ( isset( $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
	} elseif ( isset( $_GET['newuseremail'] ) ) {
		$action = 'email-confirmation';
	}

	if ( 'email-confirmation' === $action ) {
		$user_id = get_current_user_id();
		$field_id = communaute_blindee_xprofile_get_encrypted_specific_field_id( 'user_email' );
		if ( ! $user_id || ! $field_id ) {
			return;
		}

		$new_email = get_user_meta( $user_id, '_new_email', true );
		if ( $new_email && hash_equals( $new_email['hash'], $_GET['newuseremail'] ) ) {
			// Update the value.
			$email = esc_html( trim( $new_email['newemail'] ) );
			xprofile_set_field_data( $field_id, $user_id, communaute_blindee_encrypt( $email ) );

			// Update the hash.
			$field_data_id = BP_XProfile_ProfileData::get_fielddataid_byid( $field_id, $user_id );
			bp_xprofile_update_meta( $field_data_id, 'data', '_communaute_blindee_hash_' . $field_id, wp_hash( $email ) );

			delete_user_meta( $user_id, '_new_email' );

			// Redirect!
			wp_safe_redirect( add_query_arg( array( 'updated' => 'true' ), self_admin_url( 'profile.php' ) ) );
			exit();
		}
	} elseif ( 'update' === $action ) {
		if ( isset( $_POST['fake_email'] ) && $_POST['fake_email'] ) {

			// Make sure to keep the fake email for WordPress.
			if ( isset( $_POST['email'] ) ) {
				$cb->user_profile_edited_email = $_POST['email'];
				$cb->user_profile_fake_email   = $_POST['fake_email'];

				// Override the $_POST global.
				$_POST['email'] = $_POST['fake_email'];
			}
		}

		// Do not disturb WordPress.
		add_filter( 'communaute_blindee_skip_get_user_by_query', 'communaute_blindee_return_true' );
	}
}
add_action( 'load-profile.php', 'communaute_blindee_user_admin_screens' );
add_action( 'load-user-edit.php', 'communaute_blindee_user_admin_screens' );

function communaute_blindee_user_admin_profile( $user = null ) {
	if ( ! isset( $user->fake_email ) ) {
		return;
	}

	printf( '<input type="hidden" value="%s" name="fake_email" />', esc_attr( $user->fake_email ) );
}
add_action( 'edit_user_profile', 'communaute_blindee_user_admin_profile', 10, 1 );
add_action( 'show_user_profile', 'communaute_blindee_user_admin_profile', 10, 1 );

function communaute_blindee_profile_admin_edit( $user_id = 0 ) {
	if ( ! bp_is_active( 'xprofile' ) || ! $user_id ) {
		return;
	}

	$cb = communaute_blindee();

	if ( ! isset( $cb->user_profile_edited_email ) ) {
		return;
	}

	$no_email_change = (int) $user_id === (int) communaute_blindee_get_user_by_hashed_meta(
		communaute_blindee_xprofile_get_encrypted_email_field_id(),
		wp_hash( $cb->user_profile_edited_email )
	);

	if ( ! $no_email_change ) {
		clean_user_cache( $user_id );
		remove_filter( 'communaute_blindee_skip_get_user_by_query', 'communaute_blindee_return_true' );

		$user = get_user_by( 'ID', $user_id );

		$user->data->user_email = communaute_blindee_decrypt( $user->data->user_email );

		if ( communaute_blindee_xprofile_get_encrypted_specific_field_id( 'user_login' ) ) {
			$user->data->user_login = communaute_blindee_decrypt( $user->data->user_login );
		}

		wp_cache_replace( $user->ID, $user->data, 'users' );

		$_POST['email'] = $cb->user_profile_edited_email;

		add_action( 'personal_options_update','communaute_blindee_user_admin_edit', 12, 1 );
	}
}
add_action( 'personal_options_update', 'communaute_blindee_profile_admin_edit', 8, 1 );

function communaute_blindee_user_admin_edit( $user_id ) {
	$cb = communaute_blindee();
	clean_user_cache( $user_id );

	if ( isset( $cb->user_profile_fake_email ) ) {
		$_POST['email'] = $cb->user_profile_fake_email;
	}

	add_filter( 'communaute_blindee_skip_get_user_by_query', 'communaute_blindee_return_true' );
}

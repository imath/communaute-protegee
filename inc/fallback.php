<?php
/**
 * Communauté Blindée fallback
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Display a message to admin in case config is not as expected
 *
 * @since 1.0.0
 */
function communaute_blindee_fallback_warning() {
	$warnings           = array();
	$communaute_blindee = communaute_blindee();

	if( ! communaute_blindee_bp_version_check() ) {
		$warnings[] = sprintf( esc_html__( '%1$s requires at least version %2$s of BuddyPress.', 'communaute-blindee' ), $communaute_blindee->name, '3.0.0' );
	}

	if ( ! communaute_blindee_dependency_check() ) {
		$warnings[] = sprintf( esc_html__( '%1$s needs the version %2$s of the %3$s plugin to be active.', 'communaute-blindee' ),
			$communaute_blindee->name,
			$communaute_blindee->required_rsa_version,
			'<a href="https://wordpress.org/plugins/restricted-site-access/">Restricted Site Access</a>'
		);
	}

	if ( ! communaute_blindee_required_setup() ) {
		$warnings[] = sprintf( esc_html__( '%1$s needs the %2$s BuddyPress constant to be set to %3$s.', 'communaute-blindee' ),
			$communaute_blindee->name,
			'<code>BP_SIGNUPS_SKIP_USER_CREATION</code>',
			'<code>true</code>'
		);
	}

	if ( $warnings ) :
	?>
	<div id="message" class="error">
		<?php foreach ( $warnings as $warning ) : ?>
			<p><?php echo $warning; ?></p>
		<?php endforeach ; ?>
	</div>
	<?php
	endif;
}
add_action( communaute_blindee_is_active_on_network() ? 'network_admin_notices' : 'admin_notices', 'communaute_blindee_fallback_warning' );

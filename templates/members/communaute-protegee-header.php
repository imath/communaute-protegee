<?php
/**
 * Template to simulate the WordPress login/register screen headers.
 *
 * @package   communaute-protegee
 * @subpackage \templates\members\communaute-protegee-header
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

nocache_headers();
header( 'Content-Type: ' . get_bloginfo( 'html_type' ) . '; charset=' . get_bloginfo( 'charset' ) );

/**
 * Fires when the register & activate pages are initialized.
 */
do_action( 'communaute_protegee_init' );

// Do not display admin bar.
add_filter( 'show_admin_bar', '__return_false' );
?>
<!DOCTYPE html>
<!--[if IE 8]>
	<html xmlns="http://www.w3.org/1999/xhtml" class="ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 8) ]><!-->
	<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php bloginfo( 'charset' ); ?>" />
<title><?php bloginfo( 'name' ); ?> &rsaquo; <?php bp_is_register_page() ? esc_html_e( 'Inscription', 'communaute-protegee' ) : esc_html_e( 'Activation', 'communaute-protegee' ); ?></title>

<?php
// Ensure we're using an absolute URL.
$current_url  = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ); // phpcs:ignore
$filtered_url = remove_query_arg( array( '_communaute_protegee_status', '_communaute_protegee_nonce' ), $current_url );

// Remove some query args from the URL.
?>
<link id="communaute-protegee-canonical" rel="canonical" href="<?php echo esc_url( $filtered_url ); ?>" />
<script>
	if ( window.history.replaceState ) {
		window.history.replaceState( null, null, document.getElementById( 'communaute-protegee-canonical' ).href + window.location.hash );
	}
</script>
<?php
// Load the CSS.
wp_admin_css( 'login', true );

/**
 * Get WordPress Login Head Actions.
 */
do_action( 'login_head' );

/**
 * Use this to run custom actions on BuddyPress register/activate pages.
 */
do_action( 'communaute_protegee_head' );

if ( is_multisite() ) {
	$register_header_url   = network_home_url();
	$register_header_title = get_current_site()->site_name;
} else {
	$register_header_url   = __( 'https://fr.wordpress.org/', 'communaute-protegee' );
	$register_header_title = __( 'Propulsé par WordPress', 'communaute-protegee' );
}

/**
 * Filter link URL of the header logo above register form.
 *
 * @since 1.0.0
 *
 * @param string $register_header_url Login header logo URL.
 */
communaute_protegee()->register_header_url = apply_filters( 'login_headerurl', $register_header_url );

/**
 * Filter the title attribute of the header logo above login form.
 *
 * @since 1.0.0
 *
 * @param string $register_header_title Login header logo title attribute.
 */
communaute_protegee()->register_header_title = apply_filters( 'login_headertitle', $register_header_title );
?>
</head>
	<body class="login wp-core-ui">

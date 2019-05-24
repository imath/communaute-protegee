<?php
/**
 * Template to simulate the WordPress login/register screen headers
 */
nocache_headers();
header('Content-Type: '.get_bloginfo('html_type').'; charset='.get_bloginfo('charset'));

/**
 * Fires when the register & activate pages are initialized.
 */
do_action( 'bp_restricted_community_init' );

// Do not display admin bar
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
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<title><?php bloginfo('name'); ?> &rsaquo; <?php bp_is_register_page() ? esc_html_e( 'Register', 'bp-restricted-community' ) : esc_html_e( 'Activate', 'bp-restricted-community' ); ?></title>

<?php
wp_admin_css( 'login', true );

/**
 * Get WordPress Login Head Actions
 */
do_action( 'login_head' );

/**
 * Use this to run custom actions on BuddyPress register/activate pages
 */
do_action( 'bp_restricted_community_head' );

if ( is_multisite() ) {
	$register_header_url   = network_home_url();
	$register_header_title = get_current_site()->site_name;
} else {
	$register_header_url   = __( 'https://wordpress.org/', 'bp-restricted-community' );
	$register_header_title = __( 'Powered by WordPress', 'bp-restricted-community' );
}

/**
 * Filter link URL of the header logo above register form.
 *
 * @since 1.0.0
 *
 * @param string $register_header_url Login header logo URL.
 */
communaute_blindee()->register_header_url = apply_filters( 'login_headerurl', $register_header_url );

/**
 * Filter the title attribute of the header logo above login form.
 *
 * @since 1.0.0
 *
 * @param string $register_header_title Login header logo title attribute.
 */
communaute_blindee()->register_header_title = apply_filters( 'login_headertitle', $register_header_title );
?>
</head>
	<body class="login wp-core-ui">

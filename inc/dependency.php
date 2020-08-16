<?php
/**
 * Dependency functions.
 *
 * @package   communaute-protegee
 * @subpackage \inc\dependency
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks if the installed BuddyPress has the required DB version.
 *
 * @since 1.0.0
 *
 * @return boolean True if the BuddyPress DB version matches requirement. False otherwise.
 */
function communaute_protegee_bp_version_check() {
	$bp_db_version_required = 12385;

	if ( ! function_exists( 'bp_get_db_version' ) ) {
		return false;
	}

	return $bp_db_version_required <= bp_get_db_version();
}

/**
 * Checks if the installed Restricted Site Access plugin has the required version.
 *
 * @since 1.0.0
 *
 * @return boolean True if the Restricted Site Access version matches requirement. False otherwise.
 */
function communaute_protegee_rsa_version_check() {
	$rsa_dependency = class_exists( 'Restricted_Site_Access' );

	// Make sure Restricted Site Access version is 7.1.0 at least.
	if ( ! $rsa_dependency || ! defined( 'RSA_VERSION' ) ) {
		return false;
	}

	return version_compare( RSA_VERSION, '7.1.0', '>=' );
}

<?php
/**
 * Contains all functionality to make the New User Approve plugin
 * compatible with other plugins.
 */

/*
 * Don't show the error if the s2member plugin is active
 */
function nua_c2member_dismiss_membership_notice( $show_notice ) {
	if ( class_exists( 'c_ws_plugin__s2member_constants' ) ) {
		$show_notice = false;
	}

	return $show_notice;
}
add_filter( 'new_user_approve_show_membership_notice', 'nua_c2member_dismiss_membership_notice' );

/**
 * Log the user out if they register a new account
 * with the WooCommerce registration page.
 */
function nua_woocommerce_new_user_autologout() {
	if ( is_user_logged_in() ) {
		$user_status = pw_new_user_approve()->get_user_status( get_current_user_id() );

		if ( $user_status === 'pending' ){
			wp_logout();

			return wc_get_page_permalink( 'myaccount' );
		}
	}
}
add_filter( 'woocommerce_registration_redirect', 'nua_woocommerce_new_user_autologout' );

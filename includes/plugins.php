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

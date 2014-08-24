<?php
/**
 * Email Tags API for creating Email template tags
 *
 * Email tags are wrapped in { }
 *
 * A few examples:
 *
 * {name}
 * {sitename}
 *
 * To replace tags in content, use: nua_do_email_tags( $content, $name );
 *
 * To add tags, use: nua_add_email_tag( $tag, $description, $func ). Be sure to wrap nua_add_email_tag()
 * in a function hooked to the 'nua_email_tags' action
 *
 * @package     New User Approve
 * @subpackage  Emails
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @author      Barry Kooij
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class NUA_Email_Template_Tags {

	/**
	 * Container for storing all tags
	 */
	private $tags;

	/**
	 * Attributes
	 */
	private $attributes;

	/**
	 * Add an email tag
	 *
	 * @param string   $tag  Email tag to be replace in email
	 * @param callable $func Hook to run when email tag is found
	 */
	public function add( $tag, $description, $func, $context ) {
		if ( is_callable( $func ) ) {
			$this->tags[$tag] = array(
				'tag'         => $tag,
				'description' => $description,
				'func'        => $func,
				'context'     => $context,
			);
		}
	}

	/**
	 * Remove an email tag
	 *
	 * @param string $tag Email tag to remove hook from
	 */
	public function remove( $tag ) {
		unset( $this->tags[$tag] );
	}

	/**
	 * Check if $tag is a registered email tag
	 *
	 * @param string $tag Email tag that will be searched
	 *
	 * @return bool
	 */
	public function email_tag_exists( $tag ) {
		return array_key_exists( $tag, $this->tags );
	}

	/**
	 * Returns a list of all email tags
	 *
	 * @return array
	 */
	public function get_tags() {
		return $this->tags;
	}

	/**
	 * Search content for email tags and filter email tags through their hooks
	 *
	 * @param string $content Content to search for email tags
	 * @param array $attributes Attributes for email customization
	 *
	 * @return string Content with email tags filtered out.
	 */
	public function do_tags( $content, $attributes ) {

		// Check if there is atleast one tag added
		if ( empty( $this->tags ) || ! is_array( $this->tags ) ) {
			return $content;
		}

		$this->attributes = $attributes;

		$new_content = preg_replace_callback( "/{([A-z0-9\-\_]+)}/s", array( $this, 'do_tag' ), $content );

		$this->user_id = null;

		return $new_content;
	}

	/**
	 * Do a specific tag, this function should not be used. Please use edd_do_email_tags instead.
	 *
	 * @param $m message
	 *
	 * @return mixed
	 */
	public function do_tag( $m ) {

		// Get tag
		$tag = $m[1];

		// Return tag if tag not set
		if ( ! $this->email_tag_exists( $tag ) ) {
			return $m[0];
		}

		return call_user_func( $this->tags[$tag]['func'], $this->attributes, $tag );
	}

}

/**
 * Add an email tag
 *
 * @param string   $tag  Email tag to be replace in email
 * @param callable $func Hook to run when email tag is found
 */
function nua_add_email_tag( $tag, $description, $func, $context ) {
	pw_new_user_approve()->email_tags->add( $tag, $description, $func, $context );
}

/**
 * Remove an email tag
 *
 * @param string $tag Email tag to remove hook from
 */
function nua_remove_email_tag( $tag ) {
	pw_new_user_approve()->email_tags->remove( $tag );
}

/**
 * Check if $tag is a registered email tag
 *
 * @param string $tag Email tag that will be searched
 *
 * @return bool
 */
function nua_email_tag_exists( $tag ) {
	return pw_new_user_approve()->email_tags->email_tag_exists( $tag );
}

/**
 * Get all email tags
 *
 * @return array
 */
function nua_get_email_tags() {
	return pw_new_user_approve()->email_tags->get_tags();
}

/**
 * Get a formatted HTML list of all available email tags
 *
 * @return string
 */
function nua_get_emails_tags_list( $context = 'email' ) {
	// The list
	$list = '';

	// Get all tags
	$email_tags = nua_get_email_tags();

	// Check
	if ( count( $email_tags ) > 0 ) {

		// Loop
		foreach ( $email_tags as $email_tag ) {
			if ( in_array( $context, $email_tag['context'] ) ) {
				// Add email tag to list
				$list .= '{' . $email_tag['tag'] . '} - ' . $email_tag['description'] . '<br/>';
			}
		}

	}

	// Return the list
	return $list;
}

/**
 * Search content for email tags and filter email tags through their hooks
 *
 * @param string $content Content to search for email tags
 * @param int $attributes Attributes to customize email messages
 *
 * @return string Content with email tags filtered out.
 */
function nua_do_email_tags( $content, $attributes ) {

	$attributes = apply_filters( 'nua_email_tags_attributes', $attributes );

	// Replace all tags
	$content = pw_new_user_approve()->email_tags->do_tags( $content, $attributes );

	// Return content
	return $content;
}

/**
 * Load email tags
 */
function nua_load_email_tags() {
	do_action( 'nua_add_email_tags' );
}
add_action( 'init', 'nua_load_email_tags', -999 );

/**
 * Add default NUA email template tags
 */
function nua_setup_email_tags() {

	// Setup default tags array
	$email_tags = array(
		array(
			'tag'         => 'username',
			'description' => __( "The user's username on the site as well as the Username label", 'new-user-approve' ),
			'function'    => 'nua_email_tag_username',
			'context'     => array( 'email' ),
		),
		array(
			'tag'         => 'user_email',
			'description' => __( "The user's email address", 'new-user-approve' ),
			'function'    => 'nua_email_tag_user_email',
			'context'     => array( 'email' ),
		),
		array(
			'tag'         => 'sitename',
			'description' => __( 'Your site name', 'new-user-approve' ),
			'function'    => 'nua_email_tag_sitename',
			'context'     => array( 'email', 'login' ),
		),
		array(
			'tag'         => 'site_url',
			'description' => __( 'Your site URL', 'new-user-approve' ),
			'function'    => 'nua_email_tag_siteurl',
			'context'     => array( 'email' ),
		),
		array(
			'tag'         => 'admin_approve_url',
			'description' => __( 'The URL to approve/deny users', 'new-user-approve' ),
			'function'    => 'nua_email_tag_adminurl',
			'context'     => array( 'email' ),
		),
		array(
			'tag'         => 'login_url',
			'description' => __( 'The URL to login to the site', 'new-user-approve' ),
			'function'    => 'nua_email_tag_loginurl',
			'context'     => array( 'email' ),
		),
		array(
			'tag'         => 'password',
			'description' => __( 'Generates the password for the user to add to the email', 'new-user-approve' ),
			'function'    => 'nua_email_tag_password',
			'context'     => array( 'email' ),
		),
	);

	// Apply nua_email_tags filter
	$email_tags = apply_filters( 'nua_email_tags', $email_tags );

	// Add email tags
	foreach ( $email_tags as $email_tag ) {
		nua_add_email_tag( $email_tag['tag'], $email_tag['description'], $email_tag['function'], $email_tag['context'] );
	}

}
add_action( 'nua_add_email_tags', 'nua_setup_email_tags' );

/**
 * Email template tag: username
 * The user's user name on the site
 *
 * @param array $attributes
 *
 * @return string username
 */
function nua_email_tag_username( $attributes ) {
	$username = $attributes['user_login'];

	return sprintf( __( 'Username: %s', 'new-user-approve' ), $username );
}

/**
 * Email template tag: user_email
 * The user's email address
 *
 * @param array $attributes
 *
 * @return string user_email
 */
function nua_email_tag_user_email( $attributes ) {
	return $attributes['user_email'];
}

/**
 * Email template tag: sitename
 * Your site name
 *
 * @param array $attributes
 *
 * @return string sitename
 */
function nua_email_tag_sitename( $attributes ) {
	return get_bloginfo( 'name' );
}

/**
 * Email template tag: site_url
 * Your site URL
 *
 * @param array $attributes
 *
 * @return string site URL
 */
function nua_email_tag_siteurl( $attributes ) {
	return home_url();
}

/**
 * Email template tag: admin_approve_url
 * Your site URL
 *
 * @param array $attributes
 *
 * @return string admin approval URL
 */
function nua_email_tag_adminurl( $attributes ) {
	return $attributes['admin_url'];
}

/**
 * Email template tag: login_url
 * Your site URL
 *
 * @param array $attributes
 *
 * @return string admin approval URL
 */
function nua_email_tag_loginurl( $attributes ) {
	return wp_login_url();
}

/**
 * Email template tag: password
 * Generates the password for the user to add to the email
 *
 * @param array $attributes
 *
 * @return string password label and password
 */
function nua_email_tag_password( $attributes ) {
	$user = $attributes['user'];

	if ( pw_new_user_approve()->do_password_reset( $user->ID ) ) {
		// reset password to know what to send the user
		$new_pass = wp_generate_password( 12, false );

		// store the password
		global $wpdb;
		$data = array( 'user_pass' => md5( $new_pass ), 'user_activation_key' => '', );
		$where = array( 'ID' => $user->ID, );
		$wpdb->update( $wpdb->users, $data, $where, array( '%s', '%s' ), array( '%d' ) );

		// Set up the Password change nag.
		update_user_option( $user->ID, 'default_password_nag', true, true );

		// Set this meta field to track that the password has been reset by
		// the plugin. Don't reset it again unless doing a password reset.
		update_user_meta( $user->ID, 'pw_user_approve_password_reset', time() );

		return sprintf( __( 'Password: %s', 'new-user-approve' ), $new_pass );
	} else {
		return '';
	}
}

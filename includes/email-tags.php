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
	public function add( $tag, $description, $func ) {
		if ( is_callable( $func ) ) {
			$this->tags[$tag] = array(
				'tag'         => $tag,
				'description' => $description,
				'func'        => $func
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
function nua_add_email_tag( $tag, $description, $func ) {
	pw_new_user_approve()->email_tags->add( $tag, $description, $func );
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
function nua_get_emails_tags_list() {
	// The list
	$list = '';

	// Get all tags
	$email_tags = nua_get_email_tags();

	// Check
	if ( count( $email_tags ) > 0 ) {

		// Loop
		foreach ( $email_tags as $email_tag ) {

			// Add email tag to list
			$list .= '{' . $email_tag['tag'] . '} - ' . $email_tag['description'] . '<br/>';

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
			'description' => __( "The user's user name on the site", 'edd' ),
			'function'    => 'nua_email_tag_username'
		),
		/*array(
			'tag'         => 'name',
			'description' => __( "The user's first name", 'new-user-approve' ),
			'function'    => 'edd_email_tag_first_name'
		),
		array(
			'tag'         => 'fullname',
			'description' => __( "The user's full name, first and last", 'new-user-approve' ),
			'function'    => 'edd_email_tag_fullname'
		),
		array(
			'tag'         => 'user_email',
			'description' => __( "The user's email address", 'new-user-approve' ),
			'function'    => 'edd_email_tag_user_email'
		),
		array(
			'tag'         => 'date',
			'description' => __( 'The date of signup', 'new-user-approve' ),
			'function'    => 'edd_email_tag_date'
		),
		array(
			'tag'         => 'sitename',
			'description' => __( 'Your site name', 'new-user-approve' ),
			'function'    => 'edd_email_tag_sitename'
		),*/
	);

	// Apply nua_email_tags filter
	$email_tags = apply_filters( 'nua_email_tags', $email_tags );

	// Add email tags
	foreach ( $email_tags as $email_tag ) {
		nua_add_email_tag( $email_tag['tag'], $email_tag['description'], $email_tag['function'] );
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
	return $attributes['user_login'];
}

/**
 * Email template tag: name
 * The user's first name. If the first name is not available, the username will be returned.
 *
 * @param int $user_id
 *
 * @return string name
 */
function nua_email_tag_first_name( $user_id ) {
	$payment_data = edd_get_payment_meta( $payment_id );
	$email_name   = edd_get_email_names( $payment_data['user_info'] );
	return $email_name['name'];
}

/**
 * Email template tag: fullname
 * The buyer's full name, first and last
 *
 * @param int $payment_id
 *
 * @return string fullname
 */
function edd_email_tag_fullname( $payment_id ) {
	$payment_data = edd_get_payment_meta( $payment_id );
	$email_name   = edd_get_email_names( $payment_data['user_info'] );
	return $email_name['fullname'];
}

/**
 * Email template tag: user_email
 * The buyer's email address
 *
 * @param int $payment_id
 *
 * @return string user_email
 */
function edd_email_tag_user_email( $payment_id ) {
	return edd_get_payment_user_email( $payment_id );
}

/**
 * Email template tag: billing_address
 * The buyer's billing address
 *
 * @param int $payment_id
 *
 * @return string billing_address
 */
function edd_email_tag_billing_address( $payment_id ) {

	$user_info    = edd_get_payment_meta_user_info( $payment_id );
	$user_address = ! empty( $user_info['address'] ) ? $user_info['address'] : array( 'line1' => '', 'line2' => '', 'city' => '', 'country' => '', 'state' => '', 'zip' => '' );

	$return = $user_address['line1'] . "\n";
	if( ! empty( $user_address['line2'] ) ) {
		$return .= $user_address['line2'] . "\n";
	}
	$return .= $user_address['city'] . ' ' . $user_address['zip'] . ' ' . $user_address['state'] . "\n";
	$return .= $user_address['country'];

	return $return;
}

/**
 * Email template tag: date
 * Date of purchase
 *
 * @param int $payment_id
 *
 * @return string date
 */
function edd_email_tag_date( $payment_id ) {
	$payment_data = edd_get_payment_meta( $payment_id );
	return date_i18n( get_option( 'date_format' ), strtotime( $payment_data['date'] ) );
}

/**
 * Email template tag: subtotal
 * Price of purchase before taxes
 *
 * @param int $payment_id
 *
 * @return string subtotal
 */
function edd_email_tag_subtotal( $payment_id ) {
	$subtotal = edd_currency_filter( edd_format_amount( edd_get_payment_subtotal( $payment_id ) ) );
	return html_entity_decode( $subtotal, ENT_COMPAT, 'UTF-8' );
}

/**
 * Email template tag: tax
 * The taxed amount of the purchase
 *
 * @param int $payment_id
 *
 * @return string tax
 */
function edd_email_tag_tax( $payment_id ) {
	$tax = edd_currency_filter( edd_format_amount( edd_get_payment_tax( $payment_id ) ) );
	return html_entity_decode( $tax, ENT_COMPAT, 'UTF-8' );
}

/**
 * Email template tag: price
 * The total price of the purchase
 *
 * @param int $payment_id
 *
 * @return string price
 */
function edd_email_tag_price( $payment_id ) {
	$price = edd_currency_filter( edd_format_amount( edd_get_payment_amount( $payment_id ) ) );
	return html_entity_decode( $price, ENT_COMPAT, 'UTF-8' );
}

/**
 * Email template tag: payment_id
 * The unique ID number for this purchase
 *
 * @param int $payment_id
 *
 * @return int payment_id
 */
function edd_email_tag_payment_id( $payment_id ) {
	return edd_get_payment_number( $payment_id );
}

/**
 * Email template tag: receipt_id
 * The unique ID number for this purchase receipt
 *
 * @param int $payment_id
 *
 * @return string receipt_id
 */
function edd_email_tag_receipt_id( $payment_id ) {
	return edd_get_payment_key( $payment_id );
}

/**
 * Email template tag: payment_method
 * The method of payment used for this purchase
 *
 * @param int $payment_id
 *
 * @return string gateway
 */
function edd_email_tag_payment_method( $payment_id ) {
	return edd_get_gateway_checkout_label( edd_get_payment_gateway( $payment_id ) );
}

/**
 * Email template tag: sitename
 * Your site name
 *
 * @param int $payment_id
 *
 * @return string sitename
 */
function edd_email_tag_sitename( $payment_id ) {
	return get_bloginfo( 'name' );
}

/**
 * Email template tag: receipt_link
 * Adds a link so users can view their receipt directly on your website if they are unable to view it in the browser correctly
 *
 * @param $int payment_id
 *
 * @return string receipt_link
 */
function edd_email_tag_receipt_link( $payment_id ) {
	return sprintf( __( '%1$sView it in your browser.%2$s', 'edd' ), '<a href="' . add_query_arg( array( 'payment_key' => edd_get_payment_key( $payment_id ), 'edd_action' => 'view_receipt' ), home_url() ) . '">', '</a>' );
}

/**
 * Email template tag: discount_codes
 * Adds a list of any discount codes applied to this purchase
 *
 * @param $int payment_id
 * @since 2.0
 * @return string $discount_codes
 */
function edd_email_tag_discount_codes( $payment_id ) {
	$user_info = edd_get_payment_meta_user_info( $payment_id );

	$discount_codes = '';

	if( isset( $user_info['discount'] ) && $user_info['discount'] !== 'none' ) {
		$discount_codes = $user_info['discount'];
	}

	return $discount_codes;
}
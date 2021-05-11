<?php

// No direct access
defined('ABSPATH') or die('Hey, do not do this 😱');

class B4RCP_Payments {

	private static $instance = null; // Instance of this class.

	/**
	 * Constructor, sets up option fields and menus
	 */
	private function __construct() {
		// Hook after payment is complete
		// Note: rcp_update_payment_status doesn't send payment_id
		// Use rcp_update_payment_status_{status} instead
		// Statuses: pending, complete, failed, refunded, abandoned
		add_action( 'rcp_update_payment_status_complete', array( $this, 'create_billingo_document') );
	}

	/**
	 * Creates or returns an instance of this class.
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Creates billingo document (invoice)
	 */
	public function create_billingo_document( $payment_id = 0 ) {
		$payments = new RCP_Payments();
		$payment = $payments->get_payment( $payment_id );

		$user_id = $payment->user_id;
		$subscription = $payment->subscription;
		$amount = $payment->amount;

		// updates billingo partner
		B4RCP_Customers::get_instance()->update_billingo_partner( $user_id );
		// get billingo partner id
		$partner_id = B4RCP_Customers::get_instance()->get_billingo_partner( $user_id );

		$args = array(
			'method' => 'POST',
			'headers' => array(
				'X-API-KEY' => b4rcp_get_api_key(),
			),
			'body' 		=> json_encode( array(
				'partner_id' => $partner_id,
				'block_id' => b4rcp_get_block_id(),
				'type' => 'invoice',
				'fulfillment_date' => date('Y-m-d'),
				'due_date' => date('Y-m-d'),
				'payment_method' => 'bankcard',
				'language' => 'hu',
				'currency' => 'HUF',
				'conversion_rate' => 1,
				'electronic' => true,
				'items' => array(
					array(
						'name' => $subscription,
						'unit_price' => $amount,
						'unit_price_type' => 'gross',
						'quantity' => 1,
						'unit' => 'db',
						'vat' => '27%',
						//'entitlement' => 'AAM',
					),
				),
			)),
		);
		$url = BILLINGO_API_URL .'/documents';

		$response = wp_remote_request( $url, $args );
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if( $response_code != 201 ) {
			B4RCP_Customers::get_instance()->log_partner_connection( $user_id, 'Error while creating billingo document. </br>Response code: '. $response_code .'</br>Response body: '. $response_body );
			if( $response_code == 401) {
				b4rcp_invalidate_connection();
			}
		}

	}

}

return B4RCP_Payments::get_instance();

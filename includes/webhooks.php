<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Paysquad_Webhook {
	public static function init() {
		add_action( 'woocommerce_api_paysquad_webhook', array( __CLASS__, 'paysquad_webhook' ) );
	}

	static function paysquad_webhook() {
		$logger = new WC_Logger();
		if (!isset($_SERVER['HTTP_X_PAYSQUAD_ENVIRONMENT'])) {
			$logger->add('paysquad_webhook', 'No environment in webhook request');
			throw new Exception("Invalid webhook request");
		}

		//Gets the configuration that we should be dealing with here. This is from the webhook header 'Environment'
		$environment = sanitize_text_field($_SERVER['HTTP_X_PAYSQUAD_ENVIRONMENT']);
		$gateway     = WC_Gateway_Paysquad::get_instance();
		$config      = $gateway->get_configuration( $environment );

		$payload = self::validateWebhookAndGetPayload( $config );

		$event      = $payload['eventName'];
		$paysquadId = $payload['paySquadId'];

		$logger = new WC_Logger();
		$logger->add( $environment . '_paysquad_webhook', $event . ' webhook for Paysquad ' . $paysquadId );


		if ( $event == 'paysquad.failed' ) {
			self::handle_paysquad_failed_webhook( $paysquadId, $config, $payload['reason'] );
		} else if ( $event == 'paysquad.succeeded' ) {
			self::handle_paysquad_success_webhook( $paysquadId, $config );
		}


	}

	static function validateWebhookAndGetPayload( $config ) {
		$logger = new WC_Logger();
		$logger->add( 'paysquad_webhook', 'Received webhook' );

		if (!isset($_SERVER['HTTP_X_PAYSQUAD_SIGNATURE'])) {
			$logger->add('paysquad_webhook', 'No signature in webhook request');
			throw new Exception("Invalid webhook request");
		}

		$signature     = sanitize_text_field($_SERVER['HTTP_X_PAYSQUAD_SIGNATURE']);
		$raw_post_data = file_get_contents( 'php://input' );

		$expected_signature = base64_encode( hash_hmac( 'sha256', $raw_post_data, base64_decode( $config->webhook_signing_key ), true ) );

		if ( ! hash_equals( $expected_signature, $signature ) ) {
			$logger->add( 'paysquad_webhook', 'Invalid signature in webhook request' );
			throw new Exception( 'Invalid signature in webhook request');
		}

		$payload = json_decode( $raw_post_data, true );

		if ( ! isset( $payload['paySquadId'] ) ) {
			$logger->add( 'paysquad_webhook', 'No paysquad id in webhook request' );
			throw new Exception( 'Invalid webhook request' );
		}

		return $payload;
	}

	private static function handle_paysquad_failed_webhook( $paysquadId, $config, $reason ) {
		$response_data = PaysquadHelper::getPaysquad( $paysquadId, $config );

		$order_id = $response_data['meta'];
		$order    = wc_get_order( $order_id );

		if ( $order->status == 'On hold' ) {

			$failureReason = 'Unknown error with Paysquad';
			$failureStatus = 'failed';
			switch ( $reason ) {
				case 'Expired':
					$failureReason = 'Paysquad failed to reach the order total.';
					break;
				case 'Abandoned':
					$failureReason = 'The Paysquad anchor payment was not made';
					break;
				case 'Cancelled':
					$failureReason = 'The Paysquad was cancelled';
					$failureStatus = 'cancelled';
					break;
			}

			$order->update_status( $failureReason, $failureStatus );
			wc_increase_stock_levels( $order->get_id() );
			WC()->cart->empty_cart();
		}
	}

	private static function handle_paysquad_success_webhook( $paysquadId, $config ) {

		$logger        = new WC_Logger();
		$response_data = PaysquadHelper::getPaysquad( $paysquadId, $config );

		$order_id = $response_data['meta'];
		$order    = wc_get_order( $order_id );

		//Check Paysquad Status
		$status = $response_data['status'];

		if ( $status == 'Completed' || $status == 'Processing' ) {
			$logger->add( 'paysquad_webhook', 'Paysquad complete webhook status already ' . $status );
		}

		$total = $response_data['total'];


		if ( $total < $order->get_total() * 10 ** wc_get_price_decimals() ) {
			$logger->add( 'paysquad_webhook_price_issue', 'Total less then cart' );
			throw new Exception( 'Total less then cart' );
		}


		$order->payment_complete();
		wc_reduce_stock_levels( $order->get_id() );
		WC()->cart->empty_cart();

		// Add a custom note to the order for reference
		$order->add_order_note(
			'Payment completed Paysquad goal has been meet by contributors.'
		);

		// Optionally, log the successful handling of the webhook
		$logger->add( 'paysquad_succeded_webhook', 'Order ' . $order_id . ' payment completed successfully.' );

	}
}

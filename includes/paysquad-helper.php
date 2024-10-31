<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PaysquadHelper {
	public static function getPaysquad( $paysqaudId, $config ) {
		//$gateway = WC_Paysquad_Gateway::get_instance();
		$token = $config->get_token();

		$api_url = $config->base_url . 'api/merchant/paysquad/' . $paysqaudId;

		// Set the authorization header
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . $token
			)
		);

		// Make the GET request
		$response = wp_remote_get( $api_url, $args );

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code != 200 ) {

			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				error_log( $error_message );
			}

			// $logger->add('paysquad_api_error', 'Failed to Get Paysquad with Id:' . $paySquadId);
			throw new Exception( 'Failed to get Paysquad' );
		}

		$response_body = wp_remote_retrieve_body( $response );

		return json_decode( $response_body, true );
	}

	// from https://github.com/woocommerce/woocommerce-gateway-stripe/blob/d926004e74f2fd675aaa7a9dbbc7b0a6258b4a2e/includes/class-wc-stripe-helper.php#L180
	public static function get_paysquad_amount( $total, $currency = '' ) {
		if ( ! $currency ) {
			$currency = get_woocommerce_currency();
		}

		$currency = strtolower( $currency );

		if ( in_array( $currency, self::no_decimal_currencies(), true ) ) {
			return absint( $total );
		} elseif ( in_array( $currency, self::three_decimal_currencies(), true ) ) {
			$price_decimals = wc_get_price_decimals();
			$amount         = absint( wc_format_decimal( ( (float) $total * 1000 ), $price_decimals ) ); // For three decimal currencies.

			return $amount - ( $amount % 10 ); // Round the last digit down. See https://docs.stripe.com/currencies?presentment-currency=AE#three-decimal
		} else {
			return absint( wc_format_decimal( ( (float) $total * 100 ), wc_get_price_decimals() ) ); // In cents.
		}
	}

	/**
	 * List of currencies supported by Stripe that has no decimals
	 * https://stripe.com/docs/currencies#zero-decimal from https://stripe.com/docs/currencies#presentment-currencies
	 * ugx is an exception and not in this list for being a special cases in Stripe https://stripe.com/docs/currencies#special-cases
	 *
	 * @return array $currencies
	 */
	public static function no_decimal_currencies() {
		return [
			'bif', // Burundian Franc
			'clp', // Chilean Peso
			'djf', // Djiboutian Franc
			'gnf', // Guinean Franc
			'jpy', // Japanese Yen
			'kmf', // Comorian Franc
			'krw', // South Korean Won
			'mga', // Malagasy Ariary
			'pyg', // Paraguayan Guaraní
			'rwf', // Rwandan Franc
			'vnd', // Vietnamese Đồng
			'vuv', // Vanuatu Vatu
			'xaf', // Central African Cfa Franc
			'xof', // West African Cfa Franc
			'xpf', // Cfp Franc
		];
	}

	/**
	 * List of currencies supported by Stripe that has three decimals
	 * https://docs.stripe.com/currencies?presentment-currency=AE#three-decimal
	 *
	 * @return array $currencies
	 */
	private static function three_decimal_currencies() {
		return [
			'bhd', // Bahraini Dinar
			'jod', // Jordanian Dinar
			'kwd', // Kuwaiti Dinar
			'omr', // Omani Rial
			'tnd', // Tunisian Dinar
		];
	}

	/**
	 * Checks Stripe minimum order value authorized per currency
	 */
	public static function get_minimum_amount() {
		// Check order amount
		switch ( get_woocommerce_currency() ) {
			case 'USD':
			case 'CAD':
			case 'EUR':
			case 'CHF':
			case 'AUD':
			case 'SGD':
				$minimum_amount = 50;
				break;
			case 'GBP':
				$minimum_amount = 30;
				break;
			case 'DKK':
				$minimum_amount = 250;
				break;
			case 'NOK':
			case 'SEK':
				$minimum_amount = 300;
				break;
			case 'JPY':
				$minimum_amount = 5000;
				break;
			case 'MXN':
				$minimum_amount = 1000;
				break;
			case 'HKD':
				$minimum_amount = 400;
				break;
			default:
				$minimum_amount = 50;
				break;
		}

		return $minimum_amount;
	}
}

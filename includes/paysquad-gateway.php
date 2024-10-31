<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_Paysquad extends WC_Payment_Gateway {
	private static $instance;
	/**
	 * @var array|array[]
	 */
	public $form_fields;

	/**
	 * @var string Production or Sandbox
	 */
	public $environment;

	/**
	 * @var string
	 */
	public $description;
	/**
	 * @var string
	 */
	public $title;
	/**
	 * @var array|string[]
	 */
	public $supports;
	/**
	 * @var string
	 */
	public $method_description;
	/**
	 * @var string
	 */
	public $method_title;
	/**
	 * @var false
	 */
	public $has_fields;
	/**
	 * @var string
	 */
	public $icon;
	/**
	 * @var string
	 */
	public $id = 'paysquad';

	public function __construct() {
		self::$instance = $this;

//		$this->id = 'paysquad';
		$this->icon               = plugin_dir_url(dirname(__FILE__)) . 'assets/img/paysquad_logo.png';
		$this->has_fields         = false;
		$this->method_title       = 'Paysquad';
		$this->method_description = 'Enable your customers to split their payments with others. <a href="https://www.paysquad.co/business" target="_blank">Find out more</a>.';
		$this->supports           = array( 'products' );
		$this->init_form_fields();
		$this->init_settings();
		$this->title       = 'Paysquad';
		$this->description = 'Pay with friends with Paysquad, the easiest way to share the cost with friends, and avoid the IOUs.';
		$this->enabled     = $this->get_option( 'enabled' );
		$this->environment = $this->get_option( 'environment' );
		$this->validate_currency();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
	}

	public function init_form_fields() {
		$this->form_fields = array(

			'enabled' => array(
				'title'   => 'Enable/Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable Paysquad Payment Gateway',
				'default' => 'yes'
			),

			'environment' => array(
				'title'       => 'Environment',
				'type'        => 'select',
				'description' => 'Choose the Paysquad environment you wish to issue payments for.',
				'default'     => 'sandbox',
				'desc_tip'    => true,
				'options'     => array(
					'sandbox'    => 'Sandbox',
					'production' => 'Production',
				),
			),

			'sandbox_merchant_id'            => array(
				'title'       => 'Merchant ID (Sandbox)',
				'type'        => 'text',
				'description' => 'Enter your merchant ID from the Paysquad sandbox environment.',
				'default'     => '',
				'desc_tip'    => true,
				'class'       => 'paysquad-sandbox-field'
			),
			'sandbox_merchant_secret'        => array(
				'title'       => 'Merchant Secret (Sandbox)',
				'type'        => 'password',
				'description' => 'Enter your merchant secret from the Paysquad sandbox environment.',
				'default'     => '',
				'desc_tip'    => true,
				'class'       => 'paysquad-sandbox-field'
			),
			'sandbox_webhook_signing_key'    => array(
				'title'       => 'Webhook Signing Key (Sandbox)',
				'type'        => 'password',
				'description' => 'Enter your webhook signing key from the Paysquad sandbox environment. This is used to verify that live events are coming from the Paysquad only.',
				'default'     => '',
				'desc_tip'    => true,
				'class'       => 'paysquad-sandbox-field'
			),
			'production_merchant_id'         => array(
				'title'       => 'Merchant ID (Production)',
				'type'        => 'text',
				'description' => 'Enter your Production Merchant ID.',
				'default'     => '',
				'desc_tip'    => true,
				'class'       => 'paysquad-production-field'
			),
			'production_merchant_secret'     => array(
				'title'       => 'Merchant Secret (Production)',
				'type'        => 'password',
				'description' => 'Enter your Production Merchant Secret.',
				'default'     => '',
				'desc_tip'    => true,
				'class'       => 'paysquad-production-field'
			),
			'production_webhook_signing_key' => array(
				'title'       => 'Webhook Signing Key (Production)',
				'type'        => 'password',
				'description' => 'Enter your Production Webhook Signing Key. This is used to verify that live events are coming from the Paysquad Production servers.',
				'default'     => '',
				'desc_tip'    => true,
				'class'       => 'paysquad-production-field',
			),
		);
	}

	public function is_account_partially_onboarded(): bool {
		return !$this->is_configured();
	}

	public function validate_currency() {

		$currencyList = [
			"USD",
			"AED",
			"AFN",
			"ALL",
			"AMD",
			"ANG",
			"AOA",
			"ARS",
			"AUD",
			"AWG",
			"AZN",
			"BAM",
			"BBD",
			"BDT",
			"BGN",
			"BIF",
			"BMD",
			"BND",
			"BOB",
			"BRL",
			"BSD",
			"BWP",
			"BYN",
			"BZD",
			"CAD",
			"CDF",
			"CHF",
			"CLP",
			"CNY",
			"COP",
			"CRC",
			"CVE",
			"CZK",
			"DJF",
			"DKK",
			"DOP",
			"DZD",
			"EGP",
			"ETB",
			"EUR",
			"FJD",
			"FKP",
			"GBP",
			"GEL",
			"GIP",
			"GMD",
			"GNF",
			"GTQ",
			"GYD",
			"HKD",
			"HNL",
			"HTG",
			"HUF",
			"IDR",
			"ILS",
			"INR",
			"ISK",
			"JMD",
			"JPY",
			"KES",
			"KGS",
			"KHR",
			"KMF",
			"KRW",
			"KYD",
			"KZT",
			"LAK",
			"LBP",
			"LKR",
			"LRD",
			"LSL",
			"MAD",
			"MDL",
			"MGA",
			"MKD",
			"MMK",
			"MNT",
			"MOP",
			"MUR",
			"MVR",
			"MWK",
			"MXN",
			"MYR",
			"MZN",
			"NAD",
			"NGN",
			"NIO",
			"NOK",
			"NPR",
			"NZD",
			"PAB",
			"PEN",
			"PGK",
			"PHP",
			"PKR",
			"PLN",
			"PYG",
			"QAR",
			"RON",
			"RSD",
			"RUB",
			"RWF",
			"SAR",
			"SBD",
			"SCR",
			"SEK",
			"SGD",
			"SHP",
			"SLE",
			"SOS",
			"SRD",
			"STD",
			"SZL",
			"THB",
			"TJS",
			"TOP",
			"TRY",
			"TTD",
			"TWD",
			"TZS",
			"UAH",
			"UGX",
			"UYU",
			"UZS",
			"VND",
			"VUV",
			"WST",
			"XAF",
			"XCD",
			"XOF",
			"XPF",
			"YER",
			"ZAR",
			"ZMW"
		];

		$selected_currency = get_woocommerce_currency();

		if ( ! in_array( $selected_currency, $currencyList ) ) {
			wc_add_notice( 'Selected currency is not supported by Paysquad.', 'error' );
			$this->enabled = 'no';
		}
	}

	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		if ($order->get_total() * 100 < PaysquadHelper::get_minimum_amount()) {
			wc_add_notice( 'Payment error: ' . 'You must have a minimum amount in your cart of at least ' . wc_price(PaysquadHelper::get_minimum_amount() / 100) . ' to use Paysquad.', 'error' );

			return array(
				'result'   => 'failure',
				'redirect' => ''
			);
		}

		$logger     = new WC_Logger();
		$order_data = $this->transform_order_data( $order );

		$logger->add( $this->environment . '_paysquad_transformed', wp_json_encode( $order_data ) );

		$config = $this->get_configuration( $this->environment );

		$response = $this->create_paysquad( $order_data, $config );

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code == 401 ) {
			wc_add_notice( 'Payment error: ' . 'Paysquad has not been setup correctly. If you are an admin, check your merchant details', 'error' );

			return array(
				'result'   => 'failure',
				'redirect' => ''
			);
		} else if ( is_wp_error( $response ) ) {
			// Handle error
			$error_message = $response->get_error_message();
			$logger->add( 'paysquad_is_wp_error', $error_message );
			wc_add_notice( 'Payment error: ' . $error_message, 'error' );

			return array(
				'result'   => 'failure',
				'redirect' => ''
			);
		} else {

			// Handle successful response
			$response_body = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $response_body, true );


			if ( isset( $response_data['paySquadId'] ) ) {

				$paySquadId = $response_data['paySquadId'];
				$order->update_meta_data( 'paysquad_id', $paySquadId );
				$order->update_meta_data( 'paysquad_environment', $config->environment ); // The environment that sent the request.

				$contributionLink = $response_data['contributionLink'];

				$order->update_meta_data( 'paysquad_flow_link', $contributionLink );
				$order->update_status( 'on-hold', 'Awaiting Paysquad payment' );

				return array(
					'result'   => 'success',
					'redirect' => $contributionLink
				);


			} else {
				$logger->add( 'paysquad_error', 'paySquadId not found in response' );
				wc_add_notice( 'Payment error: Unknown error', 'error' );

				return array(
					'result'   => 'failure',
					'redirect' => ''
				);
			}

		}


	}

	private function create_paysquad( $order_data, Paysquad_Configuration $config ) {

		// Initialize the logger
		//    $logger = new WC_Logger();
//                $logger->add('paysquad_before_encode', 'Before encode: ' . $beforeencode);
//                $logger->add('paysquad_after_encode', 'Base 64 encoded: ' . $credentials);

		$args = array(
			'body'        => wp_json_encode( $order_data ),
			'headers'     => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Basic ' . $config->get_token()
			),
			'method'      => 'POST',
			'data_format' => 'body'
		);

		// Make the HTTP POST request
		return wp_remote_post( $config->get_create_url(), $args );
	}

	private function transform_order_data( $order ) {
		// Assuming the order has line items
		$items = array();

		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			$items[] = array(
				'id'          => (string) $item_id,
				'description' => $product->get_name(),
				'price'       => PaysquadHelper::get_paysquad_amount($item->get_total()),
				'imageUrl'    => wp_get_attachment_url( $product->get_image_id() )
			);
		}

		$order_data = array(
			'items'    => $items,
			'currency' => $order->get_currency(),
			'total'    => PaysquadHelper::get_paysquad_amount($order->get_total()),
			'meta' => (string) $order->get_id(), // Placeholder for meta information
		);

		return $order_data;
	}

	//Gets the configuration for an environment
	public function get_configuration( $environment ): Paysquad_Configuration {

		if ( strcasecmp($environment, 'Production') === 0 ) {

			return new Paysquad_Configuration(
				$this->get_option( 'production_merchant_id' ),
				$this->get_option( 'production_merchant_secret' ),
				$this->get_option( 'production_webhook_signing_key' ),
				'https://api.paysquad.co/',
				$environment
			);
		} else {
			return new Paysquad_Configuration(
				$this->get_option( 'sandbox_merchant_id' ),
				$this->get_option( 'sandbox_merchant_secret' ),
				$this->get_option( 'sandbox_webhook_signing_key' ),
				'https://api.sandbox.paysquad.co/',
				$environment
			);
		}
	}

	public static function get_instance() {
		return self::$instance;
	}

	public function is_configured() {
		return ! empty( $this->get_option( 'production_merchant_id' ) ) && ! empty( $this->get_option( 'production_merchant_secret' ) == null ) && ! empty( $this->get_option( 'production_webhook_signing_key' ) );
	}
}

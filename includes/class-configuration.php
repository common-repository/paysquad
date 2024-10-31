<?php

class Paysquad_Configuration {
	public $merchant_id;
	public $merchant_secret;
	public $webhook_signing_key;
	public $base_url;
	public $environment;

	public function __construct( $merchant_id, $merchant_secret, $webhook_signing_key, $base_url, $environment ) {
		$this->merchant_id         = $merchant_id;
		$this->merchant_secret     = $merchant_secret;
		$this->webhook_signing_key = $webhook_signing_key;
		$this->base_url            = $base_url;
		$this->environment         = $environment;
	}

	public function get_create_url() {
		return $this->base_url . 'api/merchant/paysquad';
	}

	public function get_token(): string {
		return base64_encode( $this->merchant_id . ':' . $this->merchant_secret );
	}
}

?>


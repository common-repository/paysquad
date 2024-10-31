<?php

class Paysquad_OrdersScheduledAction {
	public static function init() {
		add_action( 'init', array( __CLASS__, 'schedule_paysquad_cart_deletion' ) );
		add_action( 'paysquad_cart_expiry_check', array( __CLASS__, 'check_expired_orders' ) );
	}

	static function schedule_paysquad_cart_deletion() {
		if ( as_has_scheduled_action( 'paysquad_cart_expiry_check' ) === false ) {
			as_schedule_recurring_action( strtotime( 'tomorrow' ), DAY_IN_SECONDS, 'paysquad_cart_expiry_check', array(), '', true );
		}
	}


	static function check_expired_orders() {
		$date_8_days_ago = ( new DateTime() )->modify( '-8 days' )->format( 'Y-m-d' );
		$on_hold_orders  = wc_get_orders( array(
			'limit'          => - 1,
			'status'         => 'on-hold',
			'payment_method' => 'paysquad',
			'date_created'   => '<' . $date_8_days_ago // This needs to be less than after testing
		) );


		foreach ( $on_hold_orders as $order ) {

			$paysquadId          = $order->get_meta( 'paysquad_id' );
			$paysquadEnvironment = $order->get_meta( 'paysquad_environment' );

			$config = WC_Gateway_Paysquad::get_instance()->get_configuration( $paysquadEnvironment );

			if ( ! empty( $paysquadId ) ) {
				$response_data = PaysquadHelper::getPaysquad( $paysquadId, $config );

				//We have failed to get the webhook complete the payment here.
				if ( $response_data['status'] == 'Complete' ) {

					$total      = $response_data['total'];
					$orderTotal = $order->get_total() * 10 ** wc_get_price_decimals(); //TODO Decimals

					if ( $total < $orderTotal ) {
						error_log( 'Paysquad Total' . $total . ' less then cart total ' . $orderTotal );
					}

					$order->payment_complete();
					wc_reduce_stock_levels( $order->get_id() );
					WC()->cart->empty_cart();
					continue;
				}

			}


			wc_increase_stock_levels( $order->get_id() );

			$order->delete( false ); // Trash 'On-Hold' orders that have been created by paysquad that are over 8 days old.
		}
	}
}

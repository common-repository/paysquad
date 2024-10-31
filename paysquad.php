<?php
/*
Plugin Name: Paysquad
Description: Buy now, pay together with Paysquad, the easiest way to share the cost with others, and avoid the IOUs.
Version: 1.0.4
Author: Paysquad Development Team
Author URI: https://paysquad.co
Requires Plugins: woocommerce
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
add_filter('https_ssl_verify', '__return_false');
/**
 * Paysquad payment gateway for WooCommerce.
 */
class WC_Paysquad_Payments {
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'includes' ), 0 );

		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'add_gateway' ) );

		add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'paysquad_gateway_block_support' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'paysquad_admin_scripts' ) );

		add_action( 'admin_notices', array( __CLASS__, 'paysquad_notice' ) );
	}

	public static function includes() {
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once 'includes/paysquad-gateway.php';

			require_once 'includes/webhooks.php';
			require_once 'includes/class-configuration.php';
			require_once 'includes/paysquad-helper.php';
			require_once 'includes/orders-scheduled-action.php';

			WC_Paysquad_Webhook::init();
			Paysquad_OrdersScheduledAction::init();
		}
	}

	public static function add_gateway( $gateways ) {

		$gateways[] = 'WC_Gateway_Paysquad';

		return $gateways;
	}

	public static function paysquad_admin_scripts() {
		wp_enqueue_script( 'paysquad-admin-script', plugin_dir_url( __FILE__ ) . 'js/paysquad-admin.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_style( 'paysquad-admin-style', plugin_dir_url( __FILE__ ) . 'css/paysquad-admin.css', ver: '1.0' );
	}

	/**
	 * Custom function to register a payment method type
	 */
	public static function paysquad_gateway_block_support() {
		// Check if the required class exists
		if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			return;
		}
		// Include the custom Blocks Checkout class
		require_once plugin_dir_path( __FILE__ ) . 'includes/paysquad-block-checkout.php';
		// Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
				// Register an instance of WC_Phonepe_Blocks
				$payment_method_registry->register( new WC_PaySquad_Blocks() );
			}
		);
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_abspath() {
		return trailingslashit( plugin_dir_path( __FILE__ ) );
	}

	public static function paysquad_notice() {
//		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
//			return;
//		}
//
		if ( function_exists( 'get_transient' ) && function_exists( 'delete_transient' ) ) {
			if ( get_transient( 'paysquad-admin-activation-notice' ) ) {
				?>
                <div class="updated notice">
                    <p>Plugin <strong>activated</strong>.</p>
                    <p>Welcome to Paysquad! To get started, enter your merchant details <a
                                href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paysquad' ) ); ?>">here.</a>
                        Not a Paysquad merchant yet? <a href="https://www.paysquad.co/get-started" target="_blank">Contact
                            us</a> to get signed up.</p>
                </div>
				<?php
				if ( array_key_exists( 'activate', $_GET ) && $_GET['activate'] == 'true' ) {
					unset( $_GET['activate'] ); # Prevent the default "Plugin *activated*." notice.
				}
				delete_transient( 'paysquad-admin-activation-notice' );

				return;
			}
		}

		$show_link = true;
		if ( array_key_exists( 'page', $_GET ) && array_key_exists( 'tab', $_GET ) && array_key_exists( 'section', $_GET ) ) {
			if ( $_GET['page'] == 'wc-settings' && $_GET['tab'] == 'checkout' && $_GET['section'] == 'paysquad' ) {
				# Don't show the notice when you're on the Paysquad setup page.
				$show_link = false;
			}
		}

		if ( ! WC_Gateway_Paysquad::get_instance()->is_configured() && WC_Gateway_Paysquad::get_instance()->enabled && $show_link ) {
			?>
            <div class="updated notice">
                <p>Welcome to Paysquad! To get started, enter your merchant details <a
                            href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paysquad' ) ); ?>">here.</a>
                    Not a Paysquad merchant yet? <a href="https://www.paysquad.co/get-started" target="_blank">Contact
                        us</a> to get signed up.</p>
            </div>
			<?php
		}
	}

}

WC_Paysquad_Payments::init();

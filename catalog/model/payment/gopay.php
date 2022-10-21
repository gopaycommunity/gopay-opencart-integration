<?php
namespace Opencart\Catalog\Model\Extension\OpencartGopay\Payment;

class GoPay extends \Opencart\System\Engine\Model {
	public function getMethod( array $address ): array {

		if ( empty( $this->model_setting_setting->getValue( 'payment_gopay_goid' ) ) ||
			empty( $this->model_setting_setting->getValue( 'payment_gopay_client_id' ) ) ||
			empty( $this->model_setting_setting->getValue( 'payment_gopay_client_secret' ) )
		) {
			return [];
		}

		$products = $this->cart->getProducts();
		if ( count( $products ) == 1 && $products[0]['subscription'] ) {
			$payment_methods = $this->model_setting_setting->getValue( 'payment_gopay_payment_methods' );
			if ( $payment_methods && !in_array( 'PAYMENT_CARD', json_decode( $payment_methods ) ) ) {
				return [];
			}
		}

		return $this->is_available() ? [
			'code'       => 'gopay',
			'title'      => $this->model_setting_setting->getValue( 'payment_gopay_title' ),
			'sort_order' => 6
		] : [];
	}

	/**
	 * Is the gateway available based on the restrictions
	 * of countries and shipping methods.
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public function is_available(): bool {

		if ( ! empty( $this->session->data['customer'] ) ) {
			$this->load->model('setting/extension');

			$_config = new \Opencart\System\Engine\Config();
			$_config->addPath( DIR_EXTENSION . 'opencart_gopay/system/config/' );
			$_config->load( 'gopay' );

			$setting = $_config->get( 'gopay_setting' );

			// Check currency matches one of the supported currencies.
			$currency = $this->session->data['currency'];
			if ( empty( $currency ) || ! array_key_exists( $currency, $setting['currencies'] ) ) {
				return false;
			}
			// end check currency.

			// Check countries.
			$country = '';
			if ( ! array_key_exists( 'shipping_address', $this->session->data ) ) {
				if ( array_key_exists( 'billing_address', $this->session->data ) ) {
					$country = $this->session->data['billing_address']['iso_code_3'];
				}
			} else {
				$country = $this->session->data['shipping_address']['iso_code_3'];
			}

			$countries = json_decode( $this->model_setting_setting->getValue( 'payment_gopay_countries' ) );
			if ( !empty( $country ) && ( empty( $countries ) || !in_array( $country, (array) $countries, true ) ) ) {
				return false;
			}
			// end check countries.

			$all_virtual_downloadable = true;
			foreach ( $this->cart->getProducts() as $product ) {
				if ( !$product['download'] ) {
					$all_virtual_downloadable = false;
					break;
				}
			}

			// Check shipping methods.
			if ( $this->cart->hasShipping() && !$all_virtual_downloadable ) {
				$shipping_methods = json_decode( $this->model_setting_setting->getValue( 'payment_gopay_shipping_methods' ) );
				if ( array_key_exists( 'shipping_method', $this->session->data ) &&
					!in_array( explode( '.', $this->session->data['shipping_method'] )[0],
						$shipping_methods ) ) {
					return false;
				}
			}
			// end check shipping methods.
		}

		return true;
	}

	/**
	 * Set customer payment id for subscription
	 * using the order id
	 *
	 * @param int $order_id     order id
	 * @param int $customer_id  customer id
	 *
	 * @since  1.0.0
	 */
	public function set_customer_payment_id( $order_id, $customer_id ): bool {
		$customer_payments   = $this->db->query("SELECT * FROM `" . DB_PREFIX .
			"customer_payment` WHERE `customer_id` = " . (int)$customer_id . " AND type = 'gopay';")->row;
		$customer_payment_id = $customer_payments['customer_payment_id'];

		$this->db->query( "UPDATE `" . DB_PREFIX . "subscription` SET `customer_payment_id` = " .
			(int)$customer_payment_id . " WHERE `order_id` = " . (int)$order_id . ";" );
	}
}
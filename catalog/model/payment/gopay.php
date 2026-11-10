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
			return [];
//			$payment_methods = $this->model_setting_setting->getValue( 'payment_gopay_payment_methods' );
//			if ( $payment_methods && !in_array( 'PAYMENT_CARD', json_decode( $payment_methods ) ) ) {
//				return [];
//			}
		}

		return $this->is_available() ? [
			'code'       => 'gopay',
			'title'      => $this->model_setting_setting->getValue( 'payment_gopay_title' ),
			'sort_order' => 6
		] : [];
	}

	public function getMethods(array $address = []): array {

		if ( empty( $this->model_setting_setting->getValue( 'payment_gopay_goid' ) ) ||
		empty( $this->model_setting_setting->getValue( 'payment_gopay_client_id' ) ) ||
		empty( $this->model_setting_setting->getValue( 'payment_gopay_client_secret' ) )
		) {
			return [];
		}

		$products = $this->cart->getProducts();
		if ( count( $products ) == 1 && current($products)['subscription'] ) {
			return [];
		}

		$method_data = [];

		$option_data['gopay'] = [
			'code' => 'gopay.gopay',
			'name' => $this->model_setting_setting->getValue( 'payment_gopay_title' )
		];

		$method_data = [
			'code'       => 'gopay',
			'name'       => $this->model_setting_setting->getValue( 'payment_gopay_title' ),
			'option'     => $option_data,
			'sort_order' => 6
		];
		
		return $this->is_available() ? $method_data : [];
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
			if ($this->cart->hasShipping() && !$all_virtual_downloadable) {
				$shipping_methods = json_decode($this->model_setting_setting->getValue('payment_gopay_shipping_methods'));

				if (isset($this->session->data['shipping_method'])) {
					$shipping_method = $this->session->data['shipping_method'];

					if (is_string($shipping_method)) {
						$shipping_method_code = explode('.', $shipping_method)[0];
					} 
					elseif (is_array($shipping_method) && isset($shipping_method['code'])) {
						$shipping_method_code = explode('.', $shipping_method['code'])[0];
					} else {
						$shipping_method_code = null;
					}

					if (!empty($shipping_method_code) && !in_array($shipping_method_code, $shipping_methods, true)) {
						return false;
					}
				}
			}
			// end check shipping methods.
		}

		return true;
	}

	/**
	 * Store a GoPay card id for the customer
	 *
	 * Only the GoPay card id is stored, never the card token or the card details.
	 * The insert is idempotent, the same payment notification can be delivered
	 * more than once.
	 *
	 * @param int    $customer_id customer id.
	 * @param string $card_id     GoPay card id.
	 *
	 * @since  1.0.5
	 */
	public function insert_saved_card( int $customer_id, string $card_id ): void {

		if ( !$customer_id || $card_id === '' ) {
			return;
		}

		$this->db->query( "INSERT INTO `" . DB_PREFIX . "gopay_payment_cards` SET `customer_id` = '" .
			(int)$customer_id . "', `card_id` = '" . $this->db->escape( $card_id ) .
			"', `created_at` = NOW() ON DUPLICATE KEY UPDATE `card_id` = `card_id`" );
	}

	/**
	 * Get the card ids saved by the customer, newest first
	 *
	 * @param int $customer_id customer id.
	 *
	 * @return array rows with `card_id`
	 * @since  1.0.5
	 */
	public function get_saved_cards( int $customer_id ): array {

		if ( !$customer_id ) {
			return array();
		}

		return $this->db->query( "SELECT `card_id` FROM `" . DB_PREFIX .
			"gopay_payment_cards` WHERE `customer_id` = '" . (int)$customer_id .
			"' ORDER BY `created_at` DESC, `id` DESC" )->rows;
	}

	/**
	 * Check that the card id is saved for the customer
	 *
	 * Used to verify card ownership before a card id coming from the request
	 * is used for a payment or deleted.
	 *
	 * @param int    $customer_id customer id.
	 * @param string $card_id     GoPay card id.
	 *
	 * @return bool
	 * @since  1.0.5
	 */
	public function has_saved_card( int $customer_id, string $card_id ): bool {

		if ( !$customer_id || $card_id === '' ) {
			return false;
		}

		return $this->db->query( "SELECT 1 FROM `" . DB_PREFIX . "gopay_payment_cards` WHERE `customer_id` = '" .
			(int)$customer_id . "' AND `card_id` = '" . $this->db->escape( $card_id ) . "' LIMIT 1" )->num_rows > 0;
	}

	/**
	 * Check whether the order contains a subscription product
	 *
	 * The subscription table is filled only when the order gets its first status,
	 * order_subscription already when the order is created, so this works at the
	 * time the payment is created.
	 *
	 * @param int $order_id order id.
	 *
	 * @return bool
	 * @since  1.0.5
	 */
	public function has_order_subscription( int $order_id ): bool {

		if ( !$order_id ) {
			return false;
		}

		return $this->db->query( "SELECT 1 FROM `" . DB_PREFIX . "order_subscription` WHERE `order_id` = '" .
			(int)$order_id . "' LIMIT 1" )->num_rows > 0;
	}

	/**
	 * Remove a saved card id of the customer
	 *
	 * The card id is always matched together with the customer id, so a customer
	 * can never remove a card that belongs to somebody else.
	 *
	 * @param int    $customer_id customer id.
	 * @param string $card_id     GoPay card id.
	 *
	 * @return bool whether a card was removed
	 * @since  1.0.5
	 */
	public function delete_customer_card( int $customer_id, string $card_id ): bool {

		if ( !$customer_id || $card_id === '' ) {
			return false;
		}

		$this->db->query( "DELETE FROM `" . DB_PREFIX . "gopay_payment_cards` WHERE `customer_id` = '" .
			(int)$customer_id . "' AND `card_id` = '" . $this->db->escape( $card_id ) . "'" );

		return $this->db->countAffected() > 0;
	}
}
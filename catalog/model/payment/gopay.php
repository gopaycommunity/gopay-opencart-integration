<?php
namespace Opencart\Catalog\Model\Extension\OpencartGopay\Payment;
class GoPay extends \Opencart\System\Engine\Model {
	public function getMethod( array $address ): array {

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
}
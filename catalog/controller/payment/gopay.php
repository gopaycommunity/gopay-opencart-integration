<?php
namespace Opencart\Catalog\Controller\Extension\OpencartGopay\Payment;
class GoPay extends \Opencart\System\Engine\Controller {
	public function index(): string {
		$this->webhook();

		$this->load->language( 'extension/opencart_gopay/payment/gopay' );
		$this->load->model('setting/extension');

		$data['payment_fields']            = $this->payment_fields();
		$data['payment_gopay_description'] = $this->model_setting_setting->getValue( 'payment_gopay_description' );

		$data['language'] = $this->config->get( 'config_language' );

		$test = $this->model_setting_setting->getValue( 'payment_gopay_test' );
		if ( $test ) {
			$data['embed'] = 'https://gw.sandbox.gopay.com/gp-gw/js/embed.js';
		} else {
			$data['embed'] = 'https://gate.gopay.cz/gp-gw/js/embed.js';
		}

		$data['countries'] = $this->db->query( "SELECT country_id FROM `" . DB_PREFIX .
			('country` WHERE `iso_code_3` in ("') .
			implode( '", "', $this->config->get( 'payment_gopay_countries' ) ) . '")' )->rows;

		return $this->load->view( 'extension/opencart_gopay/payment/gopay', $data );
	}

	/**
	 * Payment fields.
	 *
	 * @since  1.0.0
	 */
	private function payment_fields() {
		$enabled_payment_methods = '';
		$checked                 = 'checked="checked"';

        // Only supported by the currency.
        $supported_payment_methods = json_decode( $this->model_setting_setting->getValue( 'payment_gopay_payment_methods_' .
	        $this->session->data['currency'] ), true );
        $supported_banks           = json_decode( $this->model_setting_setting->getValue( 'payment_gopay_banks_' .
	        $this->session->data['currency'] ), true );

        // All selected in the settings page.
        $selected_payment_methods = json_decode( $this->model_setting_setting->getValue( 'payment_gopay_payment_methods' ) );
        $selected_banks           = json_decode( $this->model_setting_setting->getValue( 'payment_gopay_banks' ) );

        // Intersection of all selected and the supported by the currency.
        $payment_methods = array_intersect_key(
            $supported_payment_methods,
            array_flip(
                $selected_payment_methods
            )
        );
        $banks           = array_intersect_key( $supported_banks, array_flip( $selected_banks ) );

        $input =
            '
                <div class="payment_method_oc_gopay_gateway_selection" name="%s">
                <div>
                    <input class="payment_method_oc_gopay_gateway_input" name="gopay_payment_method" type="radio" id="%s" value="%s" %s />
                    <span>%s</span>
                </div>
                <img src="%s" alt="ico" style="height: auto; width: auto; margin-left: auto;"/>
                </div>';

        foreach ( $payment_methods as $payment_method => $payment_method_label_image ) {
            if ( 'BANK_ACCOUNT' === $payment_method ) {
				$simplified_bank_selection = $this->model_setting_setting->getValue( 'payment_gopay_simplified_bank' );
                if ( ! $simplified_bank_selection ) {
                    foreach ( $banks as $bank => $bank_label_image ) {
                        $span = $bank_label_image['label'];
                        $img  = array_key_exists( 'image', $bank_label_image ) ?
                            $bank_label_image['image'] : '';

                        $enabled_payment_methods .= sprintf(
                            $input,
                            $payment_method,
                            $payment_method,
                            $bank,
                            $checked,
                            $span,
                            $img
                        );
                    }
                    continue;
                }
            }

            $span = $payment_method_label_image['label'];
            $img  = array_key_exists( 'image', $payment_method_label_image ) ?
                $payment_method_label_image['image'] : '';

            $enabled_payment_methods .= sprintf(
                $input,
                $payment_method,
                $payment_method,
                $payment_method,
                $checked,
                $span,
                $img
            );

            $checked = '';
        }

		return $enabled_payment_methods;
	}

	/**
	 * Get items info
	 *
	 * @param float $currency_value  currency value.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	private function get_items( float $currency_value ): array
	{

		$items = array();
		foreach ( $this->cart->getProducts() as $item ) {

			if ( $item['tax_class_id'] ) {
				$rates    = $this->tax->getRates( $item['price'], $item['tax_class_id'] );
				$tax_rate = 0;
				if ( $rates ) {
					$tax_rate = array_values( $rates )[0]['rate'];
				}
			}

			$items[] = array(
				'type'        => 'ITEM',
				'name'        => $item['name'],
				'product_url' => $this->url->link( 'product/product', 'language=' .
					$this->config->get('config_language') . '&product_id=' . $item['product_id'] ),
				'amount'      => $item['total'] * $currency_value,
				'count'       => $item['quantity'],
				'vat_rate'    => $tax_rate ? (int)$tax_rate : 0,
			);
		}

		return $items;
	}

	public function create_payment(): void {
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/gopay.php' );
		$this->load->language( 'extension/opencart_gopay/payment/gopay' );
		$this->load->model( 'checkout/order' );

		$data = [];

		if ( !isset( $this->session->data['order_id'] ) ) {
			$data['error']['warning'] = $this->language->get( 'error_order' );
		}

		$order_id             = $this->session->data['order_id'];
		$order                = $this->model_checkout_order->getOrder( $order_id );
		$currency_value       = $this->currency->getValue( $this->session->data['currency'] );
		$gopay_payment_method = $this->request->post['gopay_payment_method'];
		$options              = $this->model_setting_setting->getSetting( 'payment_gopay' );
		$items                = $this->get_items( $currency_value );

		$callback = array(
			'return_url'       => html_entity_decode( $this->url->link( 'extension/opencart_gopay/payment/gopay',
				array( 'language' => $this->config->get( 'config_language' ),
					'gopay-api' => 'oc_gopay_gateway_return',
					'order_id'  => $order_id ) ) ),
			'notification_url' => html_entity_decode( $this->url->link( 'extension/opencart_gopay/payment/gopay',
				array( 'language' => $this->config->get( 'config_language' ),
					'gopay-api' => 'oc_gopay_gateway_notification',
					'order_id'  => $order_id ) ) ),
		);

		$response = \GoPay_API::create_payment(
			$gopay_payment_method,
			$order,
			'',
			$options,
			$items,
			$callback,
			$currency_value,
			$this->session->data
		);

		if ( $response->statusCode != 200 ) {
			$data['failure'] = $this->url->link( 'checkout/failure', 'language=' . $this->config->get('config_language'),
				true );
		} else {
			$this->model_checkout_order->editTransactionId( $order_id, $response->json['id'] );
			$data['gw_url'] = $response->json['gw_url'];
		}

		$this->response->addHeader( 'Content-Type: application/json' );
		$this->response->setOutput( json_encode( $data ) );
	}

	public function webhook() {
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/gopay.php' );
		$this->load->language( 'extension/opencart_gopay/payment/gopay' );

		$gopay_api      = filter_input( INPUT_GET, 'gopay-api' );
		$transaction_id = filter_input( INPUT_GET, 'id' );
		$order_id       = filter_input( INPUT_GET, 'order_id' );

		if ( $gopay_api && $transaction_id ) {
			\GoPay_API::check_payment_status( $transaction_id, $order_id, $this );
		}
	}
}

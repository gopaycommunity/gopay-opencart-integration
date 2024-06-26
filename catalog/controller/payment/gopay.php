<?php
namespace Opencart\Catalog\Controller\Extension\OpencartGopay\Payment;

use Opencart\System\Library\Log;

class GoPay extends \Opencart\System\Engine\Controller {
	public function index(): string {
		$this->webhook();

		$this->load->language( 'extension/opencart_gopay/payment/gopay' );
		$this->load->model( 'setting/extension' );

		$data['payment_fields']            = $this->payment_fields();
		$data['payment_gopay_description'] = $this->model_setting_setting->getValue( 'payment_gopay_description' );

		$data['language'] = $this->config->get( 'config_language' );

		$test = $this->model_setting_setting->getValue( 'payment_gopay_test' );
		if ( $test ) {
			$data['embed'] = 'https://gw.sandbox.gopay.com/gp-gw/js/embed.js';
		} else {
			$data['embed'] = 'https://gate.gopay.cz/gp-gw/js/embed.js';
		}

		if ( isset( $this->session->data['order_id'] ) ) {
			$data['order_id'] = $this->session->data['order_id'];
		}

		$data['countries'] = $this->db->query( "SELECT country_id FROM `" . DB_PREFIX .
			('country` WHERE `iso_code_3` in ("') .
			implode( '", "', $this->config->get( 'payment_gopay_countries' ) ) . '")' )->rows;

		$data['subscription_restriction'] = $this->check_subscription_restriction( $this->cart->getProducts() );

		return $this->load->view( 'extension/opencart_gopay/payment/gopay', $data );
	}

	/**
	 * Check subscription restriction
	 *
	 * @param array $products list of products
	 * @return bool
	 *
	 * @since  1.0.0
	 */
	private function check_subscription_restriction ( array $products ) {
		$number_of_products = count( $products );

		if ( $number_of_products > 1 ) {
			foreach ( $products as $product ) {
				if ( $product['subscription'] ) {
					return true;
				}
			}
		} else {
			if ( current($products)['quantity'] > 1 && current($products)['subscription'] ) {
				return true;
			}
		}

		return  false;
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
	        $this->session->data['currency'] ), true ) ?: array();
        $supported_banks           = json_decode( $this->model_setting_setting->getValue( 'payment_gopay_banks_' .
	        $this->session->data['currency'] ), true ) ?: array();

        // All selected in the settings page.
        $selected_payment_methods = json_decode(
			$this->model_setting_setting->getValue( 'payment_gopay_payment_methods' ) ) ?: array();
        $selected_banks           = json_decode(
			$this->model_setting_setting->getValue( 'payment_gopay_banks' ) ) ?: array();

        // Intersection of all selected and the supported by the currency.
        $payment_methods = array_intersect_key(
            $supported_payment_methods,
            array_flip(
                $selected_payment_methods
            )
        );
        $banks           = array_intersect_key( $supported_banks, array_flip( $selected_banks ) );

		// Check if subscription - only card payment is enabled.
		$products = $this->cart->getProducts();
		if ( current($products)['subscription'] ) {
			if ( array_key_exists( 'PAYMENT_CARD', (array) $payment_methods ) ) {
				$payment_methods = array( 'PAYMENT_CARD' => $payment_methods['PAYMENT_CARD'] );
			} else {
				$payment_methods = array();
			}
		}

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

			$tax_rate = 0;
			if ( $item['tax_class_id'] ) {
				$rates    = $this->tax->getRates( $item['price'], $item['tax_class_id'] );
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

	/**
	 * Calculate subscription end date
	 *
	 * @param array $subscription subscription
	 * @return string|int
	 *
	 * @since  1.0.0
	 */
	private function calculate_subscription_end_date( array $subscription ) {
		$end_date = '';
		if ( $subscription ) {
			if ( $subscription['trial_duration'] == 0 ||
				$subscription['duration'] == 0) {
				$end_date = 0;
			} else {
				$trial_strtotime = 0;
				if ( $subscription['trial_status'] ) {
					$trial_duration  = $subscription['trial_duration'] * $subscription['trial_cycle'];
					$trial_frequency = $subscription['trial_frequency'];
					if ( $trial_frequency == 'semi_month' ) {
						$trial_duration *= 15;
						$trial_frequency = 'day';
					}
					$trial_strtotime = strtotime( '+' . $trial_duration . ' '
						. $trial_frequency . ( $trial_duration > 1 ? 's' : '') );
				}

				$strtotime = $trial_strtotime;
				if ( $subscription['status'] ) {
					$duration  = $subscription['duration'] * $subscription['cycle'];
					$frequency = $subscription['frequency'];
					if ( $frequency == 'semi_month' ) {
						$duration *= 15;
						$frequency = 'day';
					}
					$strtotime = strtotime( '+' . $duration . ' '
						. $frequency . ( $duration > 1 ? 's' : ''),
						$trial_strtotime ? $trial_strtotime : date_timestamp_get( date_create() ) );
				}

				$end_date = date( 'Y-m-d', $strtotime );
			}
		}

		return $end_date;
	}

	public function create_payment(): void {
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/gopay.php' );
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/log.php' );
		
		$this->load->language( 'extension/opencart_gopay/payment/gopay' );
		$this->load->model( 'setting/setting' );
		$this->load->model( 'checkout/order' );

		$data = [];

		if ( !isset( $this->request->get['order_id'] ) ) {
			$data['error']['warning'] = $this->language->get( 'error_order' );
		}

		$order_id       = $this->request->get['order_id'];
		$order          = $this->model_checkout_order->getOrder( $order_id );
		$currency       = $this->session->data['currency'];
		$currency_id    = $this->currency->getId( $currency );
		$currency_value = $this->currency->getValue( $currency );
		$options        = $this->model_setting_setting->getSetting( 'payment_gopay' );
		$items          = $this->get_items( $currency_value );

		// Change currency data
		$this->db->query( "UPDATE `" . DB_PREFIX . "order` SET `currency_id` = '" . (int)$currency_id .
			"', `currency_code` = '" . $this->db->escape( $currency ) .
			"', `currency_value` = '" . (float)$currency_value .
			"', `date_modified` = NOW() WHERE `order_id` = '" . (int)$order_id . "'" );
		// End

		if ( array_key_exists( 'gopay_payment_method', $this->request->post ) ) {
			$gopay_payment_method = $this->request->post['gopay_payment_method'];
		} else {
			$this->load->model( 'account/order' );
			$histories            = $this->model_account_order->getHistories( $order_id );
			$history              = end( $histories );
			$gopay_payment_method = str_replace( 'GoPay payment method = ', '', $history['comment'] );
		}

		$callback = array(
			'return_url'       => html_entity_decode( $this->url->link( 'extension/opencart_gopay/payment/gopay',
				array( 'language' => $this->config->get( 'config_language' ),
					'gopay-api' => 'oc_gopay_gateway_return',
					'order_id'  => $order_id,
					'payment_method' => $gopay_payment_method ) ) ),
			'notification_url' => html_entity_decode( $this->url->link( 'extension/opencart_gopay/payment/gopay',
				array( 'language' => $this->config->get( 'config_language' ),
					'gopay-api' => 'oc_gopay_gateway_notification',
					'order_id'  => $order_id,
					'payment_method' => $gopay_payment_method ) ) ),
		);

		$subscription = $this->db->query( 'SELECT * FROM `' . DB_PREFIX . 'subscription` WHERE order_id = ' .
			(int)$order_id )->row;

		$end_date = $this->calculate_subscription_end_date( $subscription );
		$total    = $this->currency->format( $order['total'] , $this->session->data['currency'], 0, false ) * 100;

		$response = \GoPay_API::create_payment(
			$gopay_payment_method,
			$order,
			$end_date,
			$options,
			$items,
			$callback,
			$total,
			$this->session->data
		);

		// Remove Order id from session to create a new one
		unset( $this->session->data['order_id'] );

		// Save log.
		$log = array(
			'order_id'       => $order_id,
			'transaction_id' => 200 == $response->statusCode ? $response->json['id'] : '0',
			'message'        => 200 == $response->statusCode ? 'Payment created' :
				'Process payment error',
			'log_level'      => 200 == $response->statusCode ? 'INFO' : 'ERROR',
			'log'            => $response,
		);
		\Log::insert_log( $this, $log );

		if ( $response->statusCode != 200 ) {
			$data['failure'] = $this->url->link( 'checkout/failure', 'language=' . $this->config->get('config_language'),
				true );
		} else {
			$this->model_checkout_order->editTransactionId( $order_id, $response->json['id'] );
			$data['gw_url'] = $response->json['gw_url'];
		}

		$data['payment_gopay_inline'] = $options['payment_gopay_inline'];

		$this->response->addHeader( 'Content-Type: application/json' );
		$this->response->setOutput( json_encode( $data ) );
	}

	public function webhook() {
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/gopay.php' );
		$this->load->language( 'extension/opencart_gopay/payment/gopay' );

		$gopay_api            = filter_input( INPUT_GET, 'gopay-api' );
		$transaction_id       = filter_input( INPUT_GET, 'id' );
		$order_id             = filter_input( INPUT_GET, 'order_id' );
		$gopay_payment_method = filter_input( INPUT_GET, 'payment_method' );

		if ( $gopay_api && $transaction_id ) {
			\GoPay_API::check_payment_status( $transaction_id, $order_id, $gopay_payment_method, $this );
		}
	}
}

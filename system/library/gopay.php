<?php
/**
 * Opencart GoPay API
 * Connect to GoPay API using the GoPay's PHP SDK
 *
 * @package   OpenCart GoPay gateway
 * @author    GoPay
 * @link      https://www.gopay.com/
 * @copyright 2022 GoPay
 * @since     1.0.0
 */

// Load GoPay API.
require_once( DIR_EXTENSION . '/opencart_gopay/vendor/autoload.php' );

use GoPay\Http\Response;
use GoPay\Payments;
use Opencart\System\Library\Log;

/**
 * GoPay API connector
 *
 * @since 1.0.0
 */

class GoPay_API {
	/**
	 * GoPay authentication
	 *
	 * @param array $options plugin options.
	 * @return Payments object
	 * @since  1.0.0
	 */
	public static function auth_gopay( $options ): Payments {
		static $urls = [
			true => 'https://gate.gopay.cz/',
			false => 'https://gw.sandbox.gopay.com/'
		];

		return GoPay\payments(
			array(
				'goid'             => $options['payment_gopay_goid'],
				'clientId'         => $options['payment_gopay_client_id'],
				'clientSecret'     => $options['payment_gopay_client_secret'],
				'isProductionMode' => !$options['payment_gopay_test'],
				'scope'            => GoPay\Definition\TokenScope::ALL,
				'language'         => array_key_exists( 'payment_gopay_default_language', $options ) ?
					$options['payment_gopay_default_language'] : 'EN',
				'timeout'          => 30,
				'gatewayUrl' => $urls[!$options['payment_gopay_test']],
			)
		);
	}

	/**
	 * Check payment methods and banks that
	 * are enabled on GoPay account.
	 *
	 * @param string $currency Currency.
	 * @param array  $options  Setting options.
	 * @return array
	 * @since  1.0.0
	 */
	public static function check_enabled_on_gopay( string $currency, $options ): array {
		$gopay   = self::auth_gopay( $options );

		$payment_methods  = array();
		$banks            = array();
		$enabled_payments = $gopay->getPaymentInstruments( $options['payment_gopay_goid'], $currency );

		if ( 200 == $enabled_payments->statusCode && isset( $enabled_payments->json['enabledPaymentInstruments'] ) ) {
			foreach ( $enabled_payments->json['enabledPaymentInstruments'] as $key => $payment_method ) {
				$payment_methods[ $payment_method['paymentInstrument'] ] = array(
					'label' => $payment_method['label']['cs'],
					'image' => $payment_method['image']['normal'],
				);

				if ( 'BANK_ACCOUNT' === $payment_method['paymentInstrument'] ) {
					foreach ( $payment_method['enabledSwifts'] as $bank ) {
						$banks[ $bank['swift'] ] = array(
							'label'   => $bank['label']['cs'],
							'country' => 'OTHERS' !== $bank['swift'] ? substr( $bank['swift'], 4, 2 ) : '',
							'image'   => $bank['image']['normal'],
						);
					}
				}
			}
		}

		return array( $payment_methods, $banks );
	}

	/**
	 * GoPay create payment
	 *
	 * @param ?string  $gopay_payment_method payment method.
	 * @param array    $order                order detail.
	 * @param string   $end_date             the end date of recurrence.
	 * @param array    $options              plugin options.
	 * @param array    $items                list of products.
	 * @param array    $callback             callback links.
	 * @param float    $total                currency value.
	 * @param array    $data                 Customer address.
	 *
	 * @return Response
	 * @since 1.0.0
	 */
	public static function create_payment( ?string $gopay_payment_method, array $order,
	                                       string $end_date, array $options, array $items,
	                                       array $callback, float $total, array $data ) : Response {

		$gopay = \GoPay_API::auth_gopay( $options );

		$_config = new \Opencart\System\Engine\Config();
		$_config->addPath( DIR_EXTENSION . 'opencart_gopay/system/config/' );
		$_config->load( 'gopay' );

		$supported_banks     = $_config->get( 'gopay_setting' )['banks'];
		$country_to_language = $_config->get( 'gopay_setting' )['country_to_language'];
		$languages           = $_config->get( 'gopay_setting' )['languages'];

		# Load banks enabled on GoPay
		$banks_key       = 'payment_gopay_option_banks';
		$supported_banks = ! empty( $options ) && array_key_exists( $banks_key, $options ) &&
		! empty( $options[ $banks_key ] ) ? $options[ $banks_key ] : $supported_banks;
		# End load payment methods and banks enabled on GoPay

		$default_swift = '';
		if ( array_key_exists( $gopay_payment_method, $supported_banks ) ) {
			$default_swift        = $gopay_payment_method;
			$gopay_payment_method = 'BANK_ACCOUNT';
		}

		$is_retry = $options['payment_gopay_payment_retry'];

		$default_payment_instrument = '';
		if ( !empty( $gopay_payment_method ) ) {
			$default_payment_instrument = $gopay_payment_method;
		}

		$contact = array(
			'first_name'   => array_key_exists( 'customer', $data ) ? $data['customer']['firstname'] : $order['shipping_firstname'],
			'last_name'    => array_key_exists( 'customer', $data ) ? $data['customer']['lastname'] : $order['shipping_lastname'],
			'email'        => array_key_exists( 'customer', $data ) ? $data['customer']['email'] : $order['email'],
			'phone_number' => array_key_exists( 'customer', $data ) ? $data['customer']['telephone'] : $order['telephone'],
			'city'         => array_key_exists( 'shipping_address', $data ) ? $data['shipping_address']['city'] : $order['shipping_city'],
			'street'       => array_key_exists( 'shipping_address', $data ) ? $data['shipping_address']['address_1'] : $order['shipping_address_1'],
			'postal_code'  => array_key_exists( 'shipping_address', $data ) ? $data['shipping_address']['postcode'] : $order['shipping_postcode'],
			'country_code' => array_key_exists( 'shipping_address', $data ) ? $data['shipping_address']['iso_code_3'] : $order['shipping_iso_code_3'],
		);

		if ( !empty( $default_payment_instrument ) ) {
			$payer = array(
				'default_payment_instrument'  => $default_payment_instrument,
				'allowed_payment_instruments' => $options['payment_gopay_payment_methods'] ?? array(),
				'allowed_swifts'              => $options['payment_gopay_banks'] ?? array(),
				'contact'                     => $contact,
			);
			if ( ! empty( $default_swift ) ) {
				unset( $payer['allowed_swifts'] );
				$payer['default_swift'] = $default_swift;
			}
		} else {
			$payer = array(
				'contact' => $contact,
			);
		}

		$additional_params = array(
			array(
				'name'  => 'order_id',
				'value' => array_key_exists( 'order_id', $data ) ? $data['order_id'] : $order['order_id'],
			) );

		$language = 'EN';
		if ( array_key_exists( 'shipping_address', $data ) ) {
			$language = $country_to_language[ $data['shipping_address']['iso_code_2'] ];
		} else {
			if ( $order['shipping_iso_code_2'] != '' ) {
				$language = $country_to_language[ $order['shipping_iso_code_2'] ];
			}
		}
		if ( !array_key_exists( $language, $languages ) ) {
			$language = $options['payment_gopay_default_language'];
		}

		$data  = array(
			'payer'             => $payer,
			'amount'            => $total,
			'currency'          => array_key_exists( 'currency', $data ) ? $data['currency'] : $order['currency_code'],
			'order_number'      => array_key_exists( 'order_id', $data ) ? $data['order_id'] : $order['order_id'],
			'order_description' => 'order',
			'items'             => $items,
			'additional_params' => $additional_params,
			'callback'          => $callback,
			'lang'              => $language,
		);

		if ( $end_date !== '' ) {
			$data['recurrence'] = array(
				'recurrence_cycle'      => 'ON_DEMAND',
				'recurrence_date_to'    => $end_date != 0 ? $end_date : date( 'Y-m-d', strtotime( '+5 years' ) ) );
		}

		$response = $gopay->createPayment( $data );

		return self::decode_response( $response );
	}

	/**
	 * Decode GoPay response and add raw body if
	 * different from json property
	 *
	 * @param Response $response
	 *
	 * @since  1.0.0
	 */
	private static function decode_response( Response $response ): Response
	{
		$not_identical = ( json_decode( $response->__toString(), true ) != $response->json ) ||
			( empty( $response->__toString() ) != empty( $response->json ) );

		if ( $not_identical ) {
			$response->{'raw_body'} = filter_var( str_replace(
				'\n',
				' ',
				$response->__toString()
			), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}

		return $response;
	}

	/**
	 * Get order history
	 *
	 * @param int|string $order_id order id.
	 * @param int|string $order_status_id order status id.
	 * @param object $controller GoPay payment controller.
	 *
	 * @return array;
	 *
	 * @since  1.0.0
	 */
	public static function get_order_history( $order_id, $order_status_id, $controller ) {
		$history = $controller->db->query( "SELECT * FROM `" . DB_PREFIX .
			"order_history` WHERE `order_id` = '" . (int)$order_id . "' AND `order_status_id` = '" . $order_status_id .
			"' ORDER BY `order_history_id` DESC" )->row;

		return $history;
	}

	/**
	 * Get last subscription history for id
	 *
	 * @param int|string $subscription_id subscription id.
	 * @param object $controller GoPay payment controller.
	 *
	 * @return array;
	 *
	 * @since  1.0.0
	 */
	public static function get_subscription_history( $subscription_id, $controller ) {
		$history = $controller->db->query( "SELECT * FROM `" . DB_PREFIX .
			"subscription_history` WHERE `subscription_id` = '" . (int)$subscription_id .
			"' ORDER BY `subscription_history_id` DESC" )->row;

		return $history;
	}

	/**
	 * Change subscription history status
	 *
	 * @param int|string $subscription_id subscription id.
	 * @param int|string $subscription_status_id subscription status id.
	 * @param object $controller GoPay payment controller.
	 *
	 * @since  1.0.0
	 */
	public static function update_subscription_history_status( $subscription_status_id, $subscription_id, $controller ) {
		$history = $controller->db->query( "SELECT * FROM `" . DB_PREFIX .
			"subscription_history`  WHERE `subscription_id` = '" .
			(int)$subscription_id . "' ORDER BY `subscription_history_id` DESC" )->row;

		if ( $history ) {
			$controller->db->query( "UPDATE `" . DB_PREFIX .
				"subscription_history` SET `subscription_status_id` = '" . (int)$subscription_status_id .
				"' WHERE `subscription_history_id` = '" . (int)$history['subscription_history_id']  . "'" );
		}
	}

	/**
	 * Update subscription status
	 *
	 * @param int|string $subscription_status_id subscription status id.
	 * @param int|string $subscription_id subscription id.
	 * @param object $controller GoPay payment controller.
	 *
	 * @since  1.0.0
	 */
	public static function update_subscription_status( $subscription_status_id, $subscription_id, $controller ) {
		$controller->db->query( "UPDATE `" . DB_PREFIX .
			"subscription` SET `subscription_status_id` = '" . $subscription_status_id .
			"' WHERE `subscription_id` = '" . (int)$subscription_id . "'" );
	}

	/**
	 * Update trial remaining
	 *
	 * @param int $trial_remaining Trial remaining.
	 * @param int|string $subscription_id Subscription id.
	 * @param object $controller GoPay payment controller.
	 *
	 * @since  1.0.0
	 */
	public static function update_trial_remaining( $trial_remaining, $subscription_id, $controller ) {
		$controller->db->query( "UPDATE `" . DB_PREFIX .
			"subscription` SET `trial_remaining` = '" . (int)$trial_remaining .
			"' WHERE `subscription_id` = '" . (int)$subscription_id . "'" );
	}

	/**
	 * Update remaining
	 *
	 * @param int $remaining Remaining.
	 * @param int|string $subscription_id Subscription id.
	 * @param object $controller GoPay payment controller.
	 *
	 * @since  1.0.0
	 */
	public static function update_remaining( $remaining, $subscription_id, $controller ) {
		$controller->db->query( "UPDATE `" . DB_PREFIX .
			"subscription` SET `remaining` = '" . (int)$remaining .
			"' WHERE `subscription_id` = '" . (int)$subscription_id . "'" );
	}

	/**
	 * Update date next
	 *
	 * @param string $date_next Date next.
	 * @param int|string $subscription_id Subscription id.
	 * @param object $controller GoPay payment controller.
	 *
	 * @since  1.0.0
	 */
	public static function update_date_next( $date_next, $subscription_id, $controller ) {
		$controller->db->query( "UPDATE `" . DB_PREFIX .
			"subscription` SET `date_next` = '" .  $controller->db->escape($date_next) .
			"' WHERE `subscription_id` = '" . (int)$subscription_id . "'" );
	}

	/**
	 * Add subscription history
	 *
	 * @param int $subscription_id Subscription id.
	 * @param int $subscription_status_id Subscription status id.
	 * @param string $comment Comment.
	 * @param bool $notify Notify.
	 * @param object $controller GoPay payment controller.
	 *
	 * @since  1.0.0
	 */
	public static function add_subscription_history( int $subscription_id, int $subscription_status_id,
	                           string $comment, bool $notify, $controller )
	{
		$controller->db->query( "INSERT INTO `" . DB_PREFIX . "subscription_history` SET `subscription_id` = '" .
			(int) $subscription_id . "', `subscription_status_id` = '" . (int) $subscription_status_id .
			"', `comment` = '" . $controller->db->escape( $comment ) . "', `notify` = '" . (int) $notify . "', `date_added` = NOW()" );
	}

	/**
	 * Check payment status
	 *
	 * @param string $gopay_transaction_id GoPay transaction id.
	 * @param string $order_id             Order id.
	 * @param string $gopay_payment_method Payment method.
	 * @param object $controller           GoPay payment controller.
	 *
	 * @since  1.0.0
	 */
	public static function check_payment_status( $gopay_transaction_id, $order_id, $gopay_payment_method, $controller ) {
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/log.php' );
		$controller->load->model( 'checkout/order' );

		$options  = $controller->model_setting_setting->getSetting( 'payment_gopay' );
		$gopay    = self::auth_gopay( $options );
		$response = $gopay->getStatus( $gopay_transaction_id );

		// Save log.
		$log = array(
			'order_id'       => $order_id,
			'transaction_id' => 200 == $response->statusCode ? $response->json['id'] : '0',
			'message'        => 200 == $response->statusCode ? 'Checking payment status' :
				'Error checking payment status',
			'log_level'      => 200 == $response->statusCode ? 'INFO' : 'ERROR',
			'log'            => $response,
		);
		\Log::insert_log( $controller, $log );

		if ( 200 != $response->statusCode ) {
			return;
		}

		$subscription = $controller->db->query( "SELECT * FROM `" . DB_PREFIX .
			"subscription` WHERE `order_id` = '" . (int)$order_id . "'" )->row;

		switch ( $response->json['state'] ) {
			case 'PAID':
			case 'AUTHORIZED':
				$products     = $controller->db->query( "SELECT * FROM `" . DB_PREFIX .
					"order_product` WHERE `order_id` = '" . (int)$order_id . "'" );

				// Check if all products are downloadable.
				$all_virtual_downloadable = true;
				foreach ( $products->rows as $product ) {
					$product_to_download = $controller->db->query( "SELECT * FROM `" . DB_PREFIX .
						"product_to_download` WHERE `product_id` = '". (int)$product['product_id'] . "'" );
					if ( $product_to_download->num_rows == 0 ) {
						$all_virtual_downloadable = false;
						break;
					}
				}

				if ( $subscription ) {
					$subscription_history = self::get_subscription_history( $subscription['subscription_id'], $controller );

					if ( !$subscription_history ) {
						self::add_subscription_history( $subscription['subscription_id'],
							$controller->config->get( 'config_subscription_active_status_id' ),
							'Success: GoPay recurrent payment created', false, $controller );
					} else {
						self::update_subscription_history_status( $controller->config->get( 'config_subscription_active_status_id' ),
							$subscription['subscription_id'], $controller );
					}

					self::update_subscription_status( $controller->config->get( 'config_subscription_active_status_id' ),
						$subscription['subscription_id'], $controller );

					if ( $subscription['trial_status'] && $subscription['trial_remaining'] ) {
						self::update_trial_remaining( $subscription['trial_remaining'] - 1,
							$subscription['subscription_id'], $controller );

						$trial_cycle     = $subscription['trial_cycle'];
						$trial_frequency = $subscription['trial_frequency'];
						if ( $trial_frequency == 'semi_month' ) {
							$trial_cycle    *= 15;
							$trial_frequency = 'day';
						}
						self::update_date_next( date('Y-m-d', strtotime('+' .
							$trial_cycle . ' ' . $trial_frequency ) ),
							$subscription['subscription_id'], $controller );
					} elseif ( $subscription['status'] && $subscription['remaining'] ) {
						self::update_remaining( $subscription['remaining'] - 1,
							$subscription['subscription_id'], $controller );

						$cycle     = $subscription['cycle'];
						$frequency = $subscription['frequency'];
						if ( $frequency == 'semi_month' ) {
							$cycle    *= 15;
							$frequency = 'day';
						}
						self::update_date_next( date('Y-m-d', strtotime('+' .
							$cycle . ' ' . $frequency ) ),
							$subscription['subscription_id'], $controller );
					}
				}

				if ( $all_virtual_downloadable ) {
					if ( !self::get_order_history( $order_id, 5, $controller ) ) {
						$controller->model_checkout_order->addHistory( $order_id, 5, 'GoPay payment method = ' .
							$gopay_payment_method, true );
					}
				} else {
					if ( !self::get_order_history( $order_id, 2, $controller ) ) {
						$controller->model_checkout_order->addHistory( $order_id, 2, 'GoPay payment method = ' .
							$gopay_payment_method, true );
					}
				}
				$controller->cart->clear();
				$controller->response->redirect( $controller->url->link( 'checkout/success', '', 'SSL' ) );

				break;
			case 'PAYMENT_METHOD_CHOSEN':
				if ( $subscription ) {
					self::update_subscription_status( $controller->config->get( 'config_subscription_status_id' ),
						$subscription['subscription_id'], $controller );
					self::update_subscription_history_status( $controller->config->get( 'config_subscription_status_id' ),
						$subscription['subscription_id'], $controller );
				}

				if ( !self::get_order_history( $order_id, 1, $controller ) ) {
					$controller->model_checkout_order->addHistory( $order_id, 1, 'GoPay payment method = ' .
						$gopay_payment_method, true );
				}
				$controller->cart->clear();
				$controller->response->redirect( $controller->url->link( 'checkout/success', '', 'SSL' ) );

				break;
			case 'CREATED':
			case 'TIMEOUTED':
			case 'CANCELED':
				if ( $subscription ) {
					self::update_subscription_status( $controller->config->get( 'config_subscription_failed_status_id' ),
						$subscription['subscription_id'], $controller );
					self::update_subscription_history_status( $controller->config->get( 'config_subscription_failed_status_id' ),
						$subscription['subscription_id'], $controller );
				}

				if ( !self::get_order_history( $order_id, 10, $controller ) ) {
					$controller->model_checkout_order->addHistory( $order_id, 10, 'GoPay payment method = ' .
						$gopay_payment_method, true );
				}
				$controller->response->redirect( $controller->url->link( 'checkout/failure', '', 'SSL' ) );

				break;
			case 'REFUNDED':
				if ( $subscription ) {
					self::update_subscription_status( $controller->config->get( 'config_subscription_canceled_status_id' ),
						$subscription['subscription_id'], $controller );
					self::update_subscription_history_status( $controller->config->get( 'config_subscription_canceled_status_id' ),
						$subscription['subscription_id'], $controller );
				}

				if ( !self::get_order_history( $order_id, 11, $controller ) ) {
					$controller->model_checkout_order->addHistory( $order_id, 11, 'GoPay payment method = ' .
						$gopay_payment_method, true );
				}
				$controller->response->redirect( $controller->url->link( 'checkout/success', '', 'SSL' ) );

				break;
		}
	}

	/**
	 * GoPay create recurrence
	 *
	 * @param int|string $amount amount of the recurrence.
	 * @param object $order order detail.
	 * @param array $items list of items.
	 * @param object $controller GoPay payment controller.
	 *
	 * @return Response
	 * @since 1.0.0
	 */
	public static function create_recurrence( $amount, $order, $items, $controller ): Response {

		$options              = $controller->model_setting_setting->getSetting( 'payment_gopay' );
		$gopay                = self::auth_gopay( $options );

		$data = array(
			'amount'            => $amount * 100,
			'currency'          => $order['currency_code'],
			'order_number'      => $order['order_id'],
			'order_description' => 'subscription',
			'items'             => $items,
			'additional_params' => array(
				array(
					'name'  => 'invoicenumber',
					'value' => $order['order_id'],
				),
			),
		);

		$response = $gopay->createRecurrence( $order['transaction_id'], $data );

		return $response;
	}

	/**
	 * Get status of the transaction
	 *
	 * @param int $transaction_id Transaction id.
	 * @param int $order_id Order id.
	 * @param object $controller GoPay payment controller.
	 *
	 * @since  1.0.0
	 */
	public static function get_status( int $transaction_id, int $order_id, $controller ): Response {
		$options  = $controller->model_setting_setting->getSetting( 'payment_gopay' );
		$gopay    = self::auth_gopay( $options );
		$response = $gopay->getStatus( $transaction_id );

		return $response;
	}

	/**
	 * Refund payment
	 *
	 * @param int    $transaction_id Transaction id.
	 * @param string $amount amount to be refunded.
	 * @param object $controller GoPay payment controller.
	 *
	 * @return Response $response
	 * @since  1.0.0
	 */
	public static function refund_payment( int $transaction_id, string $amount, $controller ): Response {
		$options  = $controller->model_setting_setting->getSetting( 'payment_gopay' );
		$gopay    = self::auth_gopay( $options );
		$response = $gopay->refundPayment( $transaction_id, $amount );

		return $response;
	}
}
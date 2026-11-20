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
	 * @return ?array payment methods and banks, null when GoPay did not answer (a currency
	 *                without payment methods is answered with empty lists)
	 * @since  1.0.0
	 */
	public static function check_enabled_on_gopay( string $currency, $options, string $language_code ): ?array {
		$gopay   = self::auth_gopay( $options );

		$payment_methods  = array();
		$banks            = array();
		$enabled_payments = $gopay->getPaymentInstruments( $options['payment_gopay_goid'], $currency . '?lang=' . $language_code );

		if ( 200 != $enabled_payments->statusCode ) {
			return null;
		}

		if ( isset( $enabled_payments->json['enabledPaymentInstruments'] ) ) {
			// Determine if the specified language code exists in the response
			$paymentInstrument = reset($enabled_payments->json['enabledPaymentInstruments']);
			$language_code = isset($paymentInstrument['label'][$language_code]) ? $language_code : 'cs';

			foreach ( $enabled_payments->json['enabledPaymentInstruments'] as $key => $payment_method ) {
				$payment_methods[ $payment_method['paymentInstrument'] ] = array(
					'label' => $payment_method['label'][$language_code],
					'image' => $payment_method['image']['normal'],
				);

				if ( 'BANK_ACCOUNT' === $payment_method['paymentInstrument'] ) {
					foreach ( $payment_method['enabledSwifts'] as $bank ) {
						$banks[ $bank['swift'] ] = array(
							'label'   => $bank['label'][$language_code],
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
	 * @param bool     $request_card_token   ask GoPay to save the card used for the payment.
	 * @param string   $card_id              GoPay id of a saved card to pay with.
	 *
	 * @return Response
	 * @since 1.0.0
	 */
	public static function create_payment( ?string $gopay_payment_method, array $order,
	                                       string $end_date, array $options, array $items,
	                                       array $callback, float $total, array $data,
	                                       bool $request_card_token = false, string $card_id = '' ) : Response {

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

			$payer = self::add_card_token_params( $payer, $gopay, $gopay_payment_method, $end_date, $options,
				$request_card_token, $card_id );
		} else {
			$payer = array(
				'contact' => $contact,
			);
		}

		$manifest = DIR_EXTENSION . 'opencart_gopay/install.json';

		$version = null;

		if (is_file($manifest)) {
			$manifest_data = json_decode(file_get_contents($manifest), true);
			$version       = $manifest_data['version'] ?? null;
		}

		// Order number, currency and language are always taken from the order, the caller
		// passes the order after the currency of the session was applied to it.
		$additional_params = array(
			array(
				'name'  => 'order_id',
				'value' => $order['order_id'],
			),
			array(
				'name'  => 'gopay_plugin',
				'value' => 'gopay-opencart-' . $version,
			) );

		$language = 'EN';
		if ( $order['shipping_iso_code_2'] != '' ) {
			$language = $country_to_language[ $order['shipping_iso_code_2'] ];
		}
		if ( !array_key_exists( $language, $languages ) ) {
			$language = $options['payment_gopay_default_language'];
		}

		$data  = array(
			'payer'             => $payer,
			'amount'            => $total,
			'currency'          => $order['currency_code'],
			'order_number'      => $order['order_id'],
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

		return $gopay->createPayment( $data );
	}

	/**
	 * Add the card tokenization parameters to the payer
	 *
	 * Either pays with a saved card (allowed_card_token) or asks GoPay to save
	 * the card used for the payment (request_card_token). Only for a card payment
	 * without recurrence and with tokenization enabled; a saved card that cannot
	 * be used falls back to a regular card payment.
	 *
	 * @param array    $payer                payer of the payment.
	 * @param Payments $gopay                authenticated GoPay client.
	 * @param ?string  $gopay_payment_method payment method.
	 * @param string   $end_date             the end date of recurrence, empty without recurrence.
	 * @param array    $options              plugin options.
	 * @param bool     $request_card_token   ask GoPay to save the card.
	 * @param string   $card_id              GoPay id of a saved card to pay with.
	 *
	 * @return array payer
	 * @since  1.0.5
	 */
	private static function add_card_token_params( array $payer, Payments $gopay, ?string $gopay_payment_method,
	                                               string $end_date, array $options,
	                                               bool $request_card_token, string $card_id ): array {

		if ( empty( $options['payment_gopay_card_token'] ) || 'PAYMENT_CARD' !== $gopay_payment_method ||
			$end_date !== '' ) {
			return $payer;
		}

		// Pay with a saved card.
		if ( $card_id !== '' && $card_id !== 'new' &&
			in_array( 'PAYMENT_CARD', (array)( $options['payment_gopay_payment_methods'] ?? array() ), true ) ) {
			try {
				$response = $gopay->getCardDetails( $card_id );
			} catch ( \Exception $e ) {
				$response = null;
			}

			$json = $response && 200 == $response->statusCode && is_array( $response->json ) ?
				$response->json : array();

			if ( ( $json['status'] ?? '' ) === 'ACTIVE' && !empty( $json['card_token'] ) ) {
				$payer['allowed_payment_instruments'] = array( 'PAYMENT_CARD' );
				$payer['allowed_card_token']          = $json['card_token'];

				return $payer;
			}
		}

		// Save the card used for the payment.
		if ( $request_card_token ) {
			$payer['request_card_token'] = true;
		}

		return $payer;
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
	 * True if the order was placed with the GoPay payment method
	 *
	 * OpenCart 4.0.2+ returns the payment method as an array with the code `gopay.gopay`.
	 *
	 * @param array $order order detail.
	 *
	 * @return bool
	 * @since  1.0.6
	 */
	public static function is_gopay_order( array $order ): bool {
		$payment_method = $order['payment_method'] ?? '';
		if ( is_string( $payment_method ) && is_array( json_decode( $payment_method, true ) ) ) {
			$payment_method = json_decode( $payment_method, true );
		}
		$code = is_array( $payment_method ) ? (string)( $payment_method['code'] ?? '' ) : (string)$payment_method;

		return stripos( $code, 'gopay' ) === 0;
	}

	/**
	 * Check payment status
	 *
	 * The order id and the payment id come from the return/notification URL. The order
	 * is changed only if it is a GoPay order and the payment belongs to it (order number
	 * and currency of the payment), otherwise the function returns without a change.
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

		$order_id       = (int)$order_id;
		$transaction_id = (string)$gopay_transaction_id;
		$order          = $order_id > 0 ? $controller->model_checkout_order->getOrder( $order_id ) : array();

		if ( !$order || !self::is_gopay_order( $order ) || !ctype_digit( $transaction_id ) ) {
			return;
		}

		$options  = $controller->model_setting_setting->getSetting( 'payment_gopay' );
		$gopay    = self::auth_gopay( $options );
		$response = $gopay->getStatus( $transaction_id );
		$valid    = 200 == $response->statusCode &&
			(int)( $response->json['order_number'] ?? 0 ) === $order_id &&
			( $response->json['currency'] ?? '' ) === $order['currency_code'];

		// Save log.
		$log = array(
			'order_id'       => $order_id,
			'transaction_id' => (int)$transaction_id,
			'message'        => $valid ? 'Checking payment status' : 'Error checking payment status',
			'log_level'      => $valid ? 'INFO' : 'ERROR',
			'log'            => $response,
		);
		\Log::insert_log( $controller, $log );

		if ( !$valid ) {
			return;
		}

		$subscription = $controller->db->query( "SELECT * FROM `" . DB_PREFIX .
			"subscription` WHERE `order_id` = '" . (int)$order_id . "'" )->row;

		switch ( $response->json['state'] ) {
			case 'PAID':
			case 'AUTHORIZED':
				// Before the redirect below, which ends the request.
				self::save_payment_card( $response, (int)$order_id, $subscription, $controller );

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
	 * Save the card tokenized by a paid payment for the customer of the order
	 *
	 * The order id comes from the return/notification URL, so the card is saved
	 * only when the GoPay payment belongs to that order. The customer is taken
	 * from the order, the notification has no customer session. Failures are
	 * ignored, they must not stop processing of the payment.
	 *
	 * @param Response $response     GoPay payment status.
	 * @param int      $order_id     order id.
	 * @param array    $subscription subscription of the order, empty without one.
	 * @param object   $controller   GoPay payment controller.
	 *
	 * @since  1.0.5
	 */
	private static function save_payment_card( Response $response, int $order_id, array $subscription, $controller ): void {

		$card_id = (string)( $response->json['payer']['card_id'] ?? '' );

		if ( $card_id === '' || $subscription ||
			(string)( $response->json['order_number'] ?? '' ) !== (string)$order_id ) {
			return;
		}

		try {
			$order       = $controller->model_checkout_order->getOrder( $order_id );
			$customer_id = (int)( $order['customer_id'] ?? 0 );

			if ( !$customer_id ) {
				return;
			}

			$controller->load->model( 'extension/opencart_gopay/payment/gopay' );

			// On the first payment the subscription record does not exist yet.
			if ( $controller->model_extension_opencart_gopay_payment_gopay->has_order_subscription( $order_id ) ) {
				return;
			}

			$controller->model_extension_opencart_gopay_payment_gopay->insert_saved_card( $customer_id, $card_id );
		} catch ( \Exception $e ) {
			return;
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

	/**
	 * Get details of the payment cards saved by the customer
	 *
	 * Resolves the stored GoPay card ids into displayable card details.
	 * The card token itself is never part of the returned data. Cards deleted
	 * on GoPay or unknown to GoPay are removed from the customer's saved cards.
	 * All other cards are returned with their status (ACTIVE, SUSPENDED, ...),
	 * which cards to show is up to the caller.
	 *
	 * @param int    $customer_id customer id.
	 * @param object $controller  calling catalog controller (checkout or account).
	 *
	 * @return array `cards` with the card details, `error` true when at least one
	 *               card could not be loaded from GoPay
	 * @since  1.0.5
	 */
	public static function get_card_details( int $customer_id, $controller ): array {

		$result = array(
			'cards' => array(),
			'error' => false,
		);

		if ( !$customer_id ) {
			return $result;
		}

		$controller->load->model( 'setting/setting' );
		$controller->load->model( 'extension/opencart_gopay/payment/gopay' );

		$cards = $controller->model_extension_opencart_gopay_payment_gopay->get_saved_cards( $customer_id );

		if ( !$cards ) {
			return $result;
		}

		$options = $controller->model_setting_setting->getSetting( 'payment_gopay' );

		if ( !self::has_credentials( $options ) ) {
			$result['error'] = true;

			return $result;
		}

		try {
			$gopay = self::auth_gopay( $options );
		} catch ( \Exception $e ) {
			$result['error'] = true;

			return $result;
		}

		foreach ( $cards as $card ) {
			try {
				$response = $gopay->getCardDetails( $card['card_id'] );
			} catch ( \Exception $e ) {
				$result['error'] = true;

				continue;
			}

			$status_code = $response->statusCode ?? 0;
			$json        = is_array( $response->json ) ? $response->json : array();

			// A card deleted on GoPay, or one GoPay does not know (404, e.g. after
			// switching from sandbox to production), is gone - forget it locally.
			if ( 404 == $status_code || ( 200 == $status_code && ( $json['status'] ?? '' ) === 'DELETED' ) ) {
				try {
					$controller->model_extension_opencart_gopay_payment_gopay->delete_customer_card(
						$customer_id, $card['card_id'] );
				} catch ( \Exception $e ) {
					// Retried on the next load.
				}

				continue;
			}

			if ( 200 != $status_code ) {
				$result['error'] = true;

				continue;
			}

			// A card without a status is never treated as usable.
			$result['cards'][] = array(
				'card_id'         => $card['card_id'],
				'status'          => (string)( $json['status'] ?? '' ),
				'real_masked_pan' => (string)( $json['real_masked_pan'] ?? '' ),
				'card_brand'      => (string)( $json['card_brand'] ?? '' ),
				'card_expiration' => str_replace( ' ', '', (string)( $json['card_expiration'] ?? '' ) ),
				'card_art_url'    => (string)( $json['card_art_url'] ?? '' ),
			);
		}

		return $result;
	}

	/**
	 * Delete a payment card on GoPay
	 *
	 * @param string $card_id    GoPay card id.
	 * @param object $controller calling catalog controller (account).
	 *
	 * @return array `deleted` whether the card is gone on GoPay, `error` the
	 *               message from GoPay, empty when GoPay gave no reason
	 * @since  1.0.5
	 */
	public static function delete_payment_card( string $card_id, $controller ): array {

		$result = array(
			'deleted' => false,
			'error'   => '',
		);

		if ( $card_id === '' ) {
			return $result;
		}

		$controller->load->model( 'setting/setting' );
		$options = $controller->model_setting_setting->getSetting( 'payment_gopay' );

		if ( !self::has_credentials( $options ) ) {
			return $result;
		}

		try {
			$gopay    = self::auth_gopay( $options );
			$response = $gopay->deleteCard( $card_id );
		} catch ( \Exception $e ) {
			return $result;
		}

		$status_code = $response->statusCode ?? 0;

		if ( 2 == floor( $status_code / 100 ) || 404 == $status_code ) {
			$result['deleted'] = true;

			return $result;
		}

		$json            = is_array( $response->json ) ? $response->json : array();
		$result['error'] = (string)( $json['errors'][0]['message'] ?? '' );

		return $result;
	}

	/**
	 * Check that saved payment cards are enabled in the store
	 *
	 * GoPay is enabled and card tokenization is switched on. The one condition
	 * for every storefront part of the feature (checkout, payment, account).
	 *
	 * @param object $controller calling catalog controller.
	 *
	 * @return bool
	 * @since  1.0.5
	 */
	public static function is_card_token_enabled( $controller ): bool {
		$controller->load->model( 'setting/setting' );

		return (bool)$controller->model_setting_setting->getValue( 'payment_gopay_status' ) &&
			(bool)$controller->model_setting_setting->getValue( 'payment_gopay_card_token' );
	}

	/**
	 * Check whether OpenCart has the native "Payment Methods" account page
	 *
	 * Since 4.1.0.0 the page asks every payment extension for its content
	 * (extension/<extension>/account/<code>), so the saved cards are listed
	 * there. 4.0.2.x has no such page and gets a page of its own.
	 *
	 * @return bool
	 * @since  1.0.5
	 */
	public static function has_native_payment_methods_page(): bool {
		return version_compare( VERSION, '4.1.0.0', '>=' );
	}

	/**
	 * Check that the GoPay credentials are configured
	 *
	 * auth_gopay() reads them without checking, so the card methods, which
	 * run on storefront pages, verify them first instead of raising warnings.
	 *
	 * @param array $options plugin options.
	 *
	 * @return bool
	 * @since  1.0.5
	 */
	private static function has_credentials( array $options ): bool {
		return !empty( $options['payment_gopay_goid'] ) &&
			!empty( $options['payment_gopay_client_id'] ) &&
			!empty( $options['payment_gopay_client_secret'] );
	}
}

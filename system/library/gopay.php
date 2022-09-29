<?php
/**
 * Opencart GoPay API
 * Connect to GoPay API using the GoPay's PHP SDK
 *
 * @package   WooCommerce GoPay gateway
 * @author    GoPay
 * @link      https://www.gopay.com/
 * @copyright 2022 GoPay
 * @since     1.0.0
 */

// Load GoPay API.
require_once( DIR_EXTENSION . '/opencart_gopay/vendor/autoload.php' );

use GoPay\Http\Response;
use GoPay\Payments;

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

		if ( 200 == $enabled_payments->statusCode ) {
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
	 * @param float    $currency_value       currency value.
	 *
	 * @return Response
	 * @since 1.0.0
	 */
	public static function create_payment( ?string $gopay_payment_method, array $order,
	                                       string $end_date, array $options, array $items,
	                                       array $callback, float $currency_value ) : Response {
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
			'first_name'   => $order['shipping_firstname'],
			'last_name'    => $order['shipping_lastname'],
			'email'        => $order['email'],
			'phone_number' => $order['telephone'],
			'city'         => $order['shipping_city'],
			'street'       => $order['shipping_address_1'],
			'postal_code'  => $order['shipping_postcode'],
			'country_code' => $order['shipping_iso_code_3'],
		);

		if ( !empty( $default_payment_instrument ) ) {
			$payer = array(
				'default_payment_instrument'  => $default_payment_instrument,
				'allowed_payment_instruments' => $options['payment_gopay_payment_methods'],
				//'allowed_swifts'              => $options['payment_gopay_banks'],
				'contact'                     => $contact,
			);
			if ( ! empty( $default_swift ) ) {
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
				'value' => $order['order_id'],
			) );

		$language = $country_to_language[ $order['shipping_iso_code_2'] ];
		if ( !array_key_exists( $language, $languages ) ) {
			$language = $options['payment_gopay_default_language'];
		}

		$total = ( $order['total'] * $currency_value ) * 100;
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

//		if ( !empty( $end_date ) ) {
//			$data['recurrence'] = array(
//				'recurrence_cycle'      => 'ON_DEMAND',
//				'recurrence_date_to'    => $end_date != 0 ? $end_date : date( 'Y-m-d', strtotime( '+5 years' ) ) );
//		}

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
}
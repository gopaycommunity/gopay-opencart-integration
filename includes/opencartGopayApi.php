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

class OpencartGopayApi {
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
}
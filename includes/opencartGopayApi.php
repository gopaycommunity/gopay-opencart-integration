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
}
<?php
namespace Opencart\Admin\Controller\Extension\OpencartGopay\Payment;
use Opencart\System\Library\Log;

class GoPay extends \Opencart\System\Engine\Controller
{
	public function index(): void
	{
		$this->load->language( 'extension/opencart_gopay/payment/gopay' );
		$this->document->setTitle( $this->language->get( 'heading_title' ) );

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get( 'text_home' ),
			'href' => $this->url->link( 'common/dashboard', 'user_token=' . $this->session->data['user_token'] )
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get( 'text_extension' ),
			'href' => $this->url->link( 'marketplace/extension', 'user_token=' . $this->session->data['user_token'] .
				'&type=payment' )
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get( 'heading_title' ),
			'href' => $this->url->link( 'extension/opencart_gopay/payment/gopay', 'user_token=' .
				$this->session->data['user_token'] )
		];

		$data['save'] = $this->url->link( 'extension/opencart_gopay/payment/gopay|save', 'user_token=' .
			$this->session->data['user_token'] );
		$data['back'] = $this->url->link( 'marketplace/extension', 'user_token=' . $this->session->data['user_token']
			. '&type=payment' );

		# Load shipping methods
		$this->load->model('setting/extension');

		$shipping_methods_all     = $this->model_setting_extension->getPaths( '%/admin/controller/shipping/%.php' );
		$shipping_methods_enabled = $this->model_setting_extension->getExtensionsByType( 'shipping' );

		foreach ( $shipping_methods_enabled as $key => $shipping_method_enabled ) {
			$shipping_methods_enabled[ $shipping_method_enabled['code'] ] = $shipping_method_enabled['code'];
			unset($shipping_methods_enabled[ $key ]);
		}

		foreach ($shipping_methods_all as $shipping_method) {
			$extension = substr($shipping_method['path'], 0, strpos($shipping_method['path'], '/'));
			$code = basename($shipping_method['path'], '.php');

			if ( array_key_exists( $code,  $shipping_methods_enabled ) ) {
				$this->load->language('extension/' . $extension . '/shipping/' . $code, $code);
				$shipping_methods[$code] = $this->language->get( $code . '_heading_title' );
			}
		}
		$data['shipping_methods'] = $shipping_methods;
		# End load shipping methods

		# Load countries
		$this->load->model('localisation/country');
		foreach ( $this->model_localisation_country->getCountries() as $country ) {
			$countries[ $country['iso_code_3'] ] = $country['name'];
		}
		$data['countries'] = $countries;
		# End load countries

		# Check GoPay credentials and load options
		$this->process_admin_options();
		$data = array_merge( $data, $this->model_setting_setting->getSetting( 'payment_gopay' ) );
		$data['payment_gopay_test']        = array_key_exists( 'payment_gopay_test', $data ) &&
									is_numeric( $data['payment_gopay_test'] ) ? $data['payment_gopay_test'] : 1;
		if ( ! empty( $this->config->get( 'payment_gopay_goid' ) ) &&
			! empty( $this->config->get( 'payment_gopay_client_id' ) ) &&
			! empty( $this->config->get( 'payment_gopay_client_secret' ) ) ) {
			$data['payment_gopay_title']       = array_key_exists( 'payment_gopay_title', $data ) ?
				$data['payment_gopay_title'] : 'GoPay';
			$data['payment_gopay_description'] = array_key_exists( 'payment_gopay_description', $data ) ?
				$data['payment_gopay_description'] : 'Payment via GoPay gateway';
		}
		# End GoPay load

		$_config = new \Opencart\System\Engine\Config();
		$_config->addPath( DIR_EXTENSION . 'opencart_gopay/system/config/' );
		$_config->load( 'gopay' );

		$data['payment_methods'] = $_config->get( 'gopay_setting' )['payment_methods'];
		$data['banks']           = $_config->get( 'gopay_setting' )['banks'];
		$data['languages']       = $_config->get( 'gopay_setting' )['languages'];

		# Load payment methods and banks enabled on GoPay
		$payment_methods_key     = 'payment_gopay_option_payment_methods';
		$data['payment_methods'] = ! empty( $data ) && array_key_exists( $payment_methods_key, $data ) &&
					! empty( $data[ $payment_methods_key ] ) ?
			array_intersect_key( $data['payment_methods'], $data[ $payment_methods_key ] ) : $data['payment_methods'];

		$banks_key     = 'payment_gopay_option_banks';
		$data['banks'] = ! empty( $data ) && array_key_exists( $banks_key, $data ) &&
		! empty( $data[ $banks_key ] ) ? array_intersect_key( $data['banks'], $data[ $banks_key ] ) : $data['banks'];
		# End load payment methods and banks enabled on GoPay

		$data['header']      = $this->load->controller( 'common/header' );
		$data['column_left'] = $this->load->controller( 'common/column_left' );
		$data['footer']      = $this->load->controller( 'common/footer' );

		$this->response->setOutput( $this->load->view( 'extension/opencart_gopay/payment/gopay', $data ) );
	}

	/**
	 * Execute when installing.
	 *
	 * @since  1.0.0
	 */
	public function install(): void
	{
		// Create event for the menu
		$this->load->model( 'setting/event' );
		$this->load->model( 'extension/opencart_gopay/payment/gopay' );
		$this->model_extension_opencart_gopay_payment_gopay->create_log_table();

		$this->model_setting_event->addEvent(
			array(
				'code'        => 'add_gopay_to_column_left',
				'description' => 'Create GoPay Menu',
				'trigger'     => 'admin/view/common/column_left/before',
				'action'      => 'extension/opencart_gopay/menu/gopay|menus',
				'status'      => 1,
				'sort_order'  => 1,
			)
		);

		// Create event for the catalog order history info
		$this->model_setting_event->addEvent(
			array(
				'code'        => 'change_order_history_info_page',
				'description' => 'Change order history info page',
				'trigger'     => 'catalog/view/account/order|info/after',
				'action'      => 'extension/opencart_gopay/account/gopay|info',
				'status'      => 1,
				'sort_order'  => 1,
			)
		);

		// Create cron for recurrent payment (subscriptions)
		$this->load->model( 'setting/cron' );
		$this->model_setting_cron->addCron( 'recurrent_gopay', 'GoPay recurrent payment (subscriptions)',
			'day', 'extension/opencart_gopay/cron/gopay', 1 );
	}

	/**
	 * Execute while uninstalling.
	 *
	 * @since  1.0.0
	 */
	public function uninstall() {

		$this->load->model( 'setting/event' );
		$this->model_setting_event->deleteEventByCode( 'add_gopay_to_column_left' );
	}

	/**
	 * Save options.
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public function save(): void
	{
		$this->load->language( 'extension/opencart_gopay/payment/gopay' );

		$data = $this->model_setting_setting->getSetting( 'payment_gopay' );
		foreach ( $this->request->post as $key => $value ) {
			$data[ $key ] = $value;
		}

		if ( $this->user->hasPermission( 'modify', 'extension/opencart_gopay/payment/gopay' ) ) {
			$this->load->model( 'setting/setting' );
			$this->model_setting_setting->editSetting( 'payment_gopay', $data );
			$data['success'] = $this->language->get( 'text_success' );
		} else {
			$data['error'] = $this->language->get( 'error_permission' );
		}

		$this->response->addHeader( 'Content-Type: application/json' );
		$this->response->setOutput( json_encode( $data ) );
	}

	/**
	 * Process admin options.
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public function process_admin_options(): bool {
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/gopay.php' );
		$options = $this->model_setting_setting->getSetting( 'payment_gopay' );

		// Check payment methods and banks enabled on GoPay account.
		if ( ! empty( $this->config->get( 'payment_gopay_goid' ) ) &&
			 ! empty( $this->config->get( 'payment_gopay_test' ) ) ) {
			$this->check_enabled_on_gopay();
		}

		// Check credentials (GoID, Client ID and Client Secret).
		if ( empty( $this->config->get( 'payment_gopay_goid' ) ) ||
			empty( $this->config->get( 'payment_gopay_client_id' ) ) ||
			empty( $this->config->get( 'payment_gopay_client_secret' ) )
		) {
			return false;
		} else {
			if ( array_key_exists( 'payment_gopay_test', $options ) ) {
				$gopay = \GoPay_API::auth_gopay( $options );

				$response = $gopay->getPaymentInstruments(
					$this->config->get( 'payment_gopay_goid' ), 'CZK' );

				if ( !$response->hasSucceed() ) {
					if ( array_key_exists( 'errors', $response->json ) &&
						$response->json['errors'][0]['error_name'] == 'INVALID' ) {
						$this->model_setting_setting->editValue( 'payment_gopay', 'payment_gopay_goid', '' );

						return false;
					}
				}

				$response = $gopay->getAuth()->authorize()->response;
				if ( array_key_exists( 'errors', $response->json ) &&
					$response->json['errors'][0]['error_name'] == 'AUTH_WRONG_CREDENTIALS' ) {
					$this->model_setting_setting->editValue( 'payment_gopay', 'payment_gopay_client_id', '' );
					$this->model_setting_setting->editValue( 'payment_gopay', 'payment_gopay_client_secret', '' );

					return false;
				}

			}
		}
		// END.

		return true;
	}

	/**
	 * Check payment methods and banks that
	 * are enabled on GoPay account.
	 *
	 * @since 1.0.0
	 */
	public function check_enabled_on_gopay() {
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/gopay.php' );
		$options = $this->model_setting_setting->getSetting( 'payment_gopay' );

		$payment_methods = array();
		$banks           = array();

		$_config = new \Opencart\System\Engine\Config();
		$_config->addPath( DIR_EXTENSION . 'opencart_gopay/system/config/' );
		$_config->load( 'gopay' );

		$setting = $_config->get( 'gopay_setting' );

		foreach ( $setting['currencies'] as $currency => $value ) {
			$supported       = \GoPay_API::check_enabled_on_gopay( $currency, $options );
			$payment_methods = $payment_methods + $supported[0];
			$banks           = $banks + $supported[1];

			$options['payment_gopay_payment_methods_' . $currency] = $supported[0];
			$options['payment_gopay_banks_' . $currency] = $supported[1];
		}

		if ( ! empty( $payment_methods ) ) {
			$options['payment_gopay_option_payment_methods'] = $payment_methods;
		}
		if ( ! empty( $banks ) ) {
			if ( array_key_exists( 'OTHERS', $banks ) ) {
				// Send 'Others' to the end.
				$other = $banks['OTHERS'];
				unset( $banks['OTHERS'] );
				$banks['OTHERS'] = $other;
			}
			$options['payment_gopay_option_banks'] = $banks;
		}

		$this->model_setting_setting->editSetting( 'payment_gopay', $options );
	}
}
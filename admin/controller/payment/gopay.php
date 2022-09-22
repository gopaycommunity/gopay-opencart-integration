<?php
namespace Opencart\Admin\Controller\Extension\OpencartGopay\Payment;
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

		# GoPay load
		$data['payment_gopay_enabled']          = $this->config->get( 'payment_gopay_enabled' );
		$data['payment_gopay_title']            = $this->config->get( 'payment_gopay_title' );
		$data['payment_gopay_description']      = $this->config->get( 'payment_gopay_description' );
		$data['payment_gopay_goid']             = $this->config->get( 'payment_gopay_goid' );
		$data['payment_gopay_client_id']        = $this->config->get( 'payment_gopay_client_id' );
		$data['payment_gopay_client_secret']    = $this->config->get( 'payment_gopay_client_secret' );
		$data['payment_gopay_test']             = is_numeric( $this->config->get( 'payment_gopay_test' ) ) ?
			$this->config->get( 'payment_gopay_test' ) : 1;
		$data['payment_gopay_default_language'] = $this->config->get( 'payment_gopay_default_language' );
		$data['payment_gopay_shipping_methods'] = $this->config->get( 'payment_gopay_shipping_methods' );
		$data['payment_gopay_countries']        = $this->config->get( 'payment_gopay_countries' );
		$data['payment_gopay_simplified_bank']  = $this->config->get( 'payment_gopay_simplified_bank' );
		$data['payment_gopay_payment_methods']  = $this->config->get( 'payment_gopay_payment_methods' );
		$data['payment_gopay_banks']            = $this->config->get( 'payment_gopay_banks' );
		$data['payment_gopay_payment_retry']    = $this->config->get( 'payment_gopay_payment_retry' );

		$_config = new \Opencart\System\Engine\Config();
		$_config->addPath( DIR_EXTENSION . 'opencart_gopay/system/config/' );
		$_config->load( 'gopay' );

		$data['setting'] = $_config->get( 'gopay_setting' );

		$data['header']      = $this->load->controller( 'common/header' );
		$data['column_left'] = $this->load->controller( 'common/column_left' );
		$data['footer']      = $this->load->controller( 'common/footer' );

		$this->response->setOutput( $this->load->view( 'extension/opencart_gopay/payment/gopay', $data ) );
	}

	public function save(): void
	{
		$this->load->language( 'extension/opencart_gopay/payment/gopay' );

		if ( $this->user->hasPermission( 'modify', 'extension/opencart_gopay/payment/gopay' ) ) {
			$this->load->model( 'setting/setting' );
			$this->model_setting_setting->editSetting( 'payment_gopay', $this->request->post );
			$data['success'] = $this->language->get( 'text_success' );
		} else {
			$data['error'] = $this->language->get( 'error_permission' );
		}

		$this->response->addHeader( 'Content-Type: application/json' );
		$this->response->setOutput( json_encode( $data ) );
	}
}
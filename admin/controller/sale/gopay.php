<?php
namespace Opencart\Admin\Controller\Extension\OpencartGopay\Sale;
use Opencart\System\Library\Log;

class GoPay extends \Opencart\System\Engine\Controller
{
	public function info( &$route, &$data ) {
		$this->load->language( 'extension/opencart_gopay/sale/gopay' );
		$this->load->model( 'sale/order' );

		$data['order_info'] = $this->response->getOutput( 'sale/order|info' );

		if ( array_key_exists( 'order_id', $this->request->get ) ) {
			$order_id = (int)$this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		if ( $order_id ) {
			$order = $this->model_sale_order->getOrder( $order_id );
		}

		$data['user_token'] = $this->session->data['user_token'];

		if ( ( $order['order_status_id'] == 2 || $order['order_status_id'] == 5 ) && $order['payment_method'] == 'GoPay'
		) {
			$data['language'] = $this->config->get( 'config_language' );
			$data['refund']   = $this->language->get( 'refund' );
			$data['order_id'] = $order_id;

			$this->response->setOutput( $this->load->view( 'extension/opencart_gopay/sale/gopay', $data ) );
		} else {
			$this->response->setOutput( $data['order_info'] );
		}
	}
}
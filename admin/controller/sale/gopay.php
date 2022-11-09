<?php
namespace Opencart\Admin\Controller\Extension\OpencartGopay\Sale;
use Opencart\System\Library\Log;

class GoPay extends \Opencart\System\Engine\Controller
{
	public function info( &$route, &$data ) {
		$this->load->language( 'extension/opencart_gopay/sale/gopay' );
		$this->load->model( 'extension/opencart_gopay/sale/gopay' );
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

		$data['order_total']     = round( $order['total'] * $order['currency_value'], 2 );
		$data['refunded_total']  = round( $this->model_extension_opencart_gopay_sale_gopay->getOrderRefundsTotal(
			$order_id ), 2 );
		$data['can_be_refunded'] = round( $data['order_total'] - $data['refunded_total'], 2 );

		$data['total_to_be_refunded_message'] = sprintf( $this->language->get('total_to_be_refunded_message'),
			$data['refunded_total'] );
		$data['refund_gopay_message']         = sprintf( $this->language->get('refund_gopay_message'),
			$data['refunded_total'], $data['order_total'] );

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

	public function process_refund() {
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/gopay.php' );
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/log.php' );
		$this->load->language( 'extension/opencart_gopay/sale/gopay' );
		$this->load->model( 'extension/opencart_gopay/sale/gopay' );

		$input_value = $_POST['input_value'];

		if ( array_key_exists( 'order_id', $this->request->get ) ) {
			$order_id = (int)$this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		if ( $order_id ) {
			$order = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" .
				(int)$order_id . "'")->row;
		}

		$data['refunded'] = false;

		if ( $order ) {
			$transaction_id = $order['transaction_id'];

			$response = \GoPay_API::refund_payment( $transaction_id, round( $input_value, 2 ) * 100, $this );
			$status   = \GoPay_API::get_status( $transaction_id, $order_id, $this );
		}

		$log = array(
			'order_id'       => $order_id,
			'transaction_id' => $transaction_id,
			'message'        => 200 == $status->statusCode ? ( 'PARTIALLY_REFUNDED' === $status->json['state'] ?
				'Payment partially refunded' : 'Payment refunded' ) : 'Payment refund executed',
			'log_level'      => 'INFO',
			'log'            => $status,
		);

		if ( $response->statusCode != 200 ) {
			$log['message']   = 'Process refund error';
			$log['log_level'] = 'ERROR';
			$log['log']       = $response;
		}
		\Log::insert_log( $this, $log );

		if ( isset( $response->json['result'] ) && 'FINISHED' === $response->json['result'] ) {
			$this->model_extension_opencart_gopay_sale_gopay->add_gopay_refund( $order_id, $input_value,
				$order['currency_code'], $order['currency_value'] );

			$data['refunded']             = true;
			$data['order_total']          = round ( $order['total'] * $order['currency_value'], 2 );
			$data['refunded_total']       = round( $this->model_extension_opencart_gopay_sale_gopay
				->getOrderRefundsTotal( $order_id ), 2 );
			$data['refund_gopay_message'] = sprintf( $this->language->get('refund_gopay_message'),
				$data['refunded_total'], $data['order_total'] );
		}

		$this->response->setOutput( json_encode( $data) );
	}
}
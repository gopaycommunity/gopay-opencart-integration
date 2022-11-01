<?php
namespace Opencart\Admin\Controller\Extension\OpencartGopay\Cron;
use Opencart\System\Library\Log;

class GoPay extends \Opencart\System\Engine\Controller
{
	public function index( int $cron_id, string $code, string $cycle, string $date_added, string $date_modified ): void
	{
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/gopay.php' );
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/log.php' );
		$this->load->model( 'sale/subscription' );
		$this->load->language( 'extension/opencart_gopay/cron/gopay' );

		$filter_data = [
			'filter_subscription_status_id' => $this->config->get( 'config_subscription_active_status_id' ),
			'filter_date_next'              => date( 'Y-m-d H:i:s' )
		];

		$subscriptions = $this->model_sale_subscription->getSubscriptions( $filter_data );

		foreach ( $subscriptions as $subscription ) {
			if ( $subscription['trial_status'] && ( !$subscription['trial_duration'] || $subscription['trial_remaining'] ) ) {
				$amount = $subscription['trial_price'];
			} elseif ( !$subscription['duration'] || $subscription['remaining'] ) {
				$amount = $subscription['price'];
			}

			if ( !$amount ) {
				continue;
			}

			$order    = $this->db->query( 'SELECT * FROM `' . DB_PREFIX . 'order` WHERE order_id = ' .
				(int)$subscription['order_id'] )->row;
			$products = $this->db->query( 'SELECT * FROM `' . DB_PREFIX . 'order_product` WHERE order_id = ' .
				(int)$subscription['order_id'] )->rows;
			$items    = $this->get_items( $products, $order['currency_value'] );

			$response = \GoPay_API::create_recurrence( $amount * $order['currency_value'], $order, $items, $this );

			if ( $response->hasSucceed() ) {
				$this->db->query( "INSERT INTO `" . DB_PREFIX . "subscription_transaction` SET `subscription_id` = '"
					. (int)$subscription['subscription_id'] . "', `transaction_id` = '"
					. $response->json['id'] . "', `order_id` = '"
					. $order['order_id'] . "', `description` = '" . $this->db->escape(
						$this->language->get( 'text_success' ) )
					. "', `amount` = '" . (float)$amount . "', `date_added` = NOW()" );

				$subscription_status_id = $this->config->get( 'config_subscription_status_id' );

				$this->model_sale_subscription->addHistory( $subscription['subscription_id'], $subscription_status_id,
					$this->language->get( 'text_success' ), true);
			} else {
				$subscription_status_id = $this->config->get('config_subscription_failed_status_id');

				$this->model_sale_subscription->addHistory( $subscription['subscription_id'], $subscription_status_id,
					$this->language->get( 'error_recurring' ), true);
			}

			$log = array(
				'order_id'       => $order['order_id'],
				'transaction_id' => 200 == $response->statusCode ? $response->json['id'] : 0,
				'message'        => 200 == $response->statusCode ?
					'Recurrence of previously created payment executed' : 'Recurring payment error',
				'log_level'      => 200 == $response->statusCode ? 'INFO' : 'ERROR',
				'log'            => $response,
			);
			\Log::insert_log( $this, $log );

		}

	}

	/**
	 * Get items info
	 *
	 * @param array $products list of products for order
	 * @param float $currency_value  currency value.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	private function get_items( array $products, float $currency_value ): array
	{

		$items = array();
		foreach ( $products as $item ) {

			$items[] = array(
				'type'        => 'ITEM',
				'name'        => $item['name'],
				'product_url' => $this->url->link( 'product/product', 'language=' .
					$this->config->get('config_language') . '&product_id=' . $item['product_id'] ),
				'amount'      => $item['total'] * $currency_value,
				'count'       => $item['quantity'],
				'vat_rate'    => $item['tax'] ? (int)$item['tax'] : 0,
			);
		}

		return $items;
	}
}
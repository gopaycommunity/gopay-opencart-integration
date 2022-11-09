<?php
namespace Opencart\Admin\Model\Extension\OpencartGopay\Sale;

use Opencart\System\Library\Log;

class GoPay extends \Opencart\System\Engine\Model
{
	/**
	 * Create refund table
	 *
	 * @since  1.0.0
	 */
	public function create_refund_table()
	{

		$this->db->query( "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "gopay_refund` (
                `id` bigint(255) NOT NULL AUTO_INCREMENT,
                `order_id` bigint(255) NOT NULL,
                `total_refunded` Double NOT NULL,
                `currency_code` varchar(50) NOT NULL,
                `currency_value` Double NOT NULL,
                PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8;" );
	}

	/**
	 * drop refund table
	 *
	 * @since  1.0.0
	 */
	public function drop_refund_table() {
		$this->db->query( "DROP TABLE IF EXISTS `" . DB_PREFIX . "gopay_refund`;");
	}

	/**
	 * Get order refunds total for products and shipping
	 *
	 * @param int $order_id Order id.
	 * @return float
	 *
	 * @since  1.0.0
	 */
	public function getOrderRefundsTotal(int $order_id): float {
		$query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "gopay_refund` WHERE `order_id` = '" . (int)
			$order_id . "'");

		$total          = 0;
		foreach ( $query->rows as $refunded ) {
			$total += $refunded['total_refunded'];
		}

		return $total;
	}

	/**
	 * Add GoPay refund
	 *
	 * @param int $order_id Order id.
	 * @param float $total_refunded total refunded.
	 * @param string $currency_code Currency code.
	 * @param float $currency_value Currency value.
	 *
	 * @since  1.0.0
	 */
	public function add_gopay_refund( int $order_id, float $total_refunded,
	                                     string $currency_code, float $currency_value ) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "gopay_refund` SET `order_id` = '" .
			(int)$order_id	. "', `total_refunded` = '" . (float)$total_refunded .
			"', `currency_code` = '" . $currency_code .	"', `currency_value` = '" . (float)$currency_value . "'");

	}
}
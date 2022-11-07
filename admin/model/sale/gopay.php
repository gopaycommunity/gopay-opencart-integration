<?php
namespace Opencart\Admin\Model\Extension\OpencartGopay\Sale;

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
                `total_refunded` bigint(255) NOT NULL,
                `total_shipping_refunded` bigint(255) NOT NULL,
                PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8;" );
	}
}
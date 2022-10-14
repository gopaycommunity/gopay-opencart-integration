<?php
namespace Opencart\Admin\Model\Extension\OpencartGopay\Payment;
use Opencart\System\Library\Log;
class GoPay extends \Opencart\System\Engine\Model {
	/**
	 * Create log table
	 *
	 * @since  1.0.0
	 */
	public function create_log_table() {

		$this->db->query( "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "gopay_log` (
                `id` bigint(255) NOT NULL AUTO_INCREMENT,
                `order_id` bigint(255) NOT NULL,
                `transaction_id` bigint(255) NOT NULL,
                `message` varchar(50) NOT NULL,
                `created_at` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                `log_level` varchar(100) NOT NULL,
                `log` JSON NOT NULL,
                CONSTRAINT `order_transaction_state_unique` UNIQUE(`order_id`, `transaction_id`, `message`),
                PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8;" );
	}
}
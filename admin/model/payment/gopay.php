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

	/**
	 * drop log table
	 *
	 * @since  1.0.0
	 */
	public function drop_log_table() {
		$this->db->query( "DROP TABLE IF EXISTS `" . DB_PREFIX . "gopay_log`;");
	}

	/**
	 * Get log data
	 *
	 * @since  1.0.0
	 */
	public function get_log_data() {

		$pagenum          = filter_input( INPUT_POST, 'pagenum' );
		$log_table_filter = filter_input( INPUT_POST, 'log_table_filter' );

		$rows = $this->db->query( sprintf(
			"SELECT COUNT(*) as num_rows FROM %s%s
			WHERE UPPER(CONCAT(order_id, transaction_id, message, created_at, log_level, log))
                REGEXP '[\w\W]*%s[\w\W]*'",
			DB_PREFIX,
			'gopay_log',
			strtoupper( $log_table_filter )
		) )->rows;

		$results_per_page = 20;
		$number_of_rows   = $rows[0]['num_rows'];
		$number_of_pages  = ceil( $number_of_rows / $results_per_page );

		if ( null === $pagenum || false === $pagenum || $pagenum > $number_of_pages ) {
			$pagenum = 1;
		}

		$page_pagination = ( $pagenum - 1 ) * $results_per_page;
		$log_data        = $page_pagination >= 0 ? $this->db->query(
			sprintf(
				"SELECT * FROM %s%s WHERE UPPER(CONCAT(order_id, transaction_id, message, created_at, log_level, log))
                REGEXP '[\w\W]*%s[\w\W]*' ORDER BY created_at DESC LIMIT %d,%d",
				DB_PREFIX,
				'gopay_log',
				strtoupper( $log_table_filter ),
				$page_pagination,
				$results_per_page
			)
		)->rows : array();

		for ( $i = 0; $i < count( $log_data ); $i++ ) {
			$log_data[$i]['log'] = json_decode( $log_data[$i]['log'], true );
		}

		$data = [
			'log_data'         => $log_data,
			'pagenum'          => $pagenum,
			'log_table_filter' => $log_table_filter,
			'number_of_pages'  => $number_of_pages,
		];

		return $data;
	}
}
<?php
/**
 * Opencart GoPay Log
 * Save log into the database
 *
 * @package   OpenCart GoPay gateway
 * @author    GoPay
 * @link      https://www.gopay.com/
 * @copyright 2022 GoPay
 * @since     1.0.0
 */

/**
 * Log
 *
 * @since 1.0.0
 */

class Log {

	/**
	 * Insert log into the database
	 *
	 * @param Object $controller controller object
	 * @param array  $log        Log text.
	 *
	 * @since  1.0.0
	 */
	public static function insert_log( $controller, array $log ) {

		$table_name = "gopay_log";
		$data       = array(
			'order_id'       => $log['order_id'],
			'transaction_id' => $log['transaction_id'],
			'message'        => $log['message'],
			'created_at'     => gmdate( 'Y-m-d H:i:s' ),
			'log_level'      => $log['log_level'],
			'log'            => json_encode( $log['log'] ),
		);
		$where      = "`order_id` = '" . $log['order_id'] .
			"' AND `transaction_id` = '" . $log['transaction_id'] .
			"' AND `message` = '" . $log['message'] . "'";

		$response = $controller->db->query(
			"SELECT * FROM `" . DB_PREFIX . $table_name . "` WHERE " . $where )->row;
		if ( !$response ) {
			$controller->db->query( "INSERT INTO `" . DB_PREFIX . $table_name .
				"` (order_id, transaction_id, message, created_at, log_level, log) VALUES ("
				. $data['order_id'] . ", " . $data['transaction_id'] . ", '" . $data['message'] .
				"', '" . $data['created_at'] . "', '" . $data['log_level'] . "', '" . $data['log'] . "');" );
		} else {
			$controller->db->query( "UPDATE `" . DB_PREFIX . $table_name .
				"` SET order_id = " . $data['order_id'] . ", transaction_id = " . $data['transaction_id'] .
				", message = '" . $data['message'] . "', created_at = '" . $data['created_at'] .
				"', log_level = '" . $data['log_level'] . "', log = '" . $data['log'] . "' WHERE " . $where . ";" );
		}
	}

}
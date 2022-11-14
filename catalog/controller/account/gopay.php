<?php
namespace Opencart\Catalog\Controller\Extension\OpencartGopay\Account;
use Opencart\System\Library\Log;

class GoPay extends \Opencart\System\Engine\Controller
{

	public function info( &$route, &$data ) {
		$this->load->language( 'extension/opencart_gopay/payment/gopay' );
		$this->load->model( 'setting/extension' );
		$this->load->model( 'setting/setting' );
		$this->load->model( 'account/order' );

		if ( !$this->customer->isLogged() || ( !isset( $this->request->get['customer_token']) ||
				!isset( $this->session->data['customer_token'] ) ||
				( $this->request->get['customer_token'] != $this->session->data['customer_token'] ) ) ) {
			$this->session->data['redirect'] = $this->url->link( 'account/order', 'language=' .
				$this->config->get( 'config_language' ) );

			$this->response->redirect( $this->url->link( 'account/login', 'language=' .
				$this->config->get( 'config_language' ) ) );
		}

		$data['order_info'] = $this->response->getOutput( 'account/order|info' );

		if ( array_key_exists( 'order_id', $this->request->get ) ) {
			$order_id = (int)$this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		$order = $this->model_account_order->getOrder( $order_id );

		if ( ( $order['order_status_id'] == 1 || $order['order_status_id'] == 10 ) && $order['payment_method'] == 'GoPay'
		) {

			$data['language'] = $this->config->get( 'config_language' );

			$data['country_restriction_message']      = $this->language->get( 'country_restriction_message' );
			$data['subscription_restriction_message'] = $this->language->get( 'country_restriction_message' );

			$subscription = $this->db->query( 'SELECT * FROM `' . DB_PREFIX . 'subscription` WHERE order_id = ' .
				(int)$order_id )->row;

			$retry = '<h3>Retry payment</h3><div style="border: 1px solid rgb(217,222,226); padding: 10px">' .
				$this->payment_gopay( $subscription ) .
				'</div><div class="d-inline-block';
			$data['order_info'] = str_replace('<div class="d-inline-block', $retry, $data['order_info'] );

			$data['order_id'] = $order_id;
		}

		$this->response->setOutput( $this->load->view( 'extension/opencart_gopay/account/gopay', $data ) );
	}

	/**
	 * Payment fields.
	 *
	 * @param array $subscription list of products
	 *
	 * @since  1.0.0
	 */
	private function payment_fields( $subscription ) {
		$enabled_payment_methods = '';
		$checked                 = 'checked="checked"';

		// Only supported by the currency.
		$supported_payment_methods = json_decode( $this->model_setting_setting->getValue( 'payment_gopay_payment_methods_' .
			$this->session->data['currency'] ), true );
		$supported_banks           = json_decode( $this->model_setting_setting->getValue( 'payment_gopay_banks_' .
			$this->session->data['currency'] ), true );

		// All selected in the settings page.
		$selected_payment_methods = json_decode( $this->model_setting_setting->getValue( 'payment_gopay_payment_methods' ) );
		$selected_banks           = json_decode( $this->model_setting_setting->getValue( 'payment_gopay_banks' ) );

		// Intersection of all selected and the supported by the currency.
		$payment_methods = array_intersect_key(
			$supported_payment_methods,
			array_flip(
				$selected_payment_methods
			)
		);
		$banks           = array_intersect_key( $supported_banks, array_flip( $selected_banks ) );

		// Check if subscription - only card payment is enabled.
		if ( $subscription ) {
			if ( array_key_exists( 'PAYMENT_CARD', (array) $payment_methods ) ) {
				$payment_methods = array( 'PAYMENT_CARD' => $payment_methods['PAYMENT_CARD'] );
			} else {
				$payment_methods = array();
			}
		}

		$input =
			'
                <div class="payment_method_oc_gopay_gateway_selection" name="%s">
                <div>
                    <input class="payment_method_oc_gopay_gateway_input" name="gopay_payment_method" type="radio" id="%s" value="%s" %s />
                    <span>%s</span>
                </div>
                <img src="%s" alt="ico" style="height: auto; width: auto; margin-left: auto;"/>
                </div>';

		foreach ( $payment_methods as $payment_method => $payment_method_label_image ) {
			if ( 'BANK_ACCOUNT' === $payment_method ) {
				$simplified_bank_selection = $this->model_setting_setting->getValue( 'payment_gopay_simplified_bank' );
				if ( ! $simplified_bank_selection ) {
					foreach ( $banks as $bank => $bank_label_image ) {
						$span = $bank_label_image['label'];
						$img  = array_key_exists( 'image', $bank_label_image ) ?
							$bank_label_image['image'] : '';

						$enabled_payment_methods .= sprintf(
							$input,
							$payment_method,
							$payment_method,
							$bank,
							$checked,
							$span,
							$img
						);
					}
					continue;
				}
			}

			$span = $payment_method_label_image['label'];
			$img  = array_key_exists( 'image', $payment_method_label_image ) ?
				$payment_method_label_image['image'] : '';

			$enabled_payment_methods .= sprintf(
				$input,
				$payment_method,
				$payment_method,
				$payment_method,
				$checked,
				$span,
				$img
			);

			$checked = '';
		}

		return $enabled_payment_methods;
	}

	/**
	 * Create payment GoPay.
	 *
	 * @param array $subscription list of products
	 *
	 * @since  1.0.0
	 */
	public function payment_gopay( $subscription ) {
		$retry_payment_method = $this->model_setting_setting->getValue( 'payment_gopay_payment_retry' );

		return "<style>
    .payment_method_oc_gopay_gateway_selection {
        border-bottom: 1px dashed;
        padding: 12px;
        display: flex;
        flex-wrap: wrap;
        position: relative;
        overflow: hidden;
        font-size: 15px;
    }
</style>
<script type=\"text/javascript\" src=\"" . ($this->model_setting_setting->getValue( 'payment_gopay_test' ) ?
			'https://gw.sandbox.gopay.com/gp-gw/js/embed.js' : 'https://gate.gopay.cz/gp-gw/js/embed.js') .
			"\"></script>
<fieldset>
    <p style=\"font-size: 15px;\"><b>" . $this->model_setting_setting->getValue( 'payment_gopay_description' ) .  "</b></p>
    <form id=\"form-gopay\">" .
			( !$retry_payment_method ? $this->payment_fields( $subscription ) : '' )	.
        "<div class=\"d-inline-block pt-2 pd-2 w-100 text-end\">
            <button type=\"submit\" id=\"button-confirm\" class=\"btn btn-primary\">Retry payment</button>
        </div>
    </form>
</fieldset>
";

	}

}
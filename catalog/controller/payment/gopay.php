<?php
namespace Opencart\Catalog\Controller\Extension\OpencartGopay\Payment;
class GoPay extends \Opencart\System\Engine\Controller {
	public function index(): string {
		$this->document->addStyle( 'extension/opencart_gopay/catalog/view/assets/css/payment_methods.css' );
		$this->load->language( 'extension/opencart_gopay/payment/gopay' );
		$this->load->model('setting/extension');

		$data['payment_fields'] = $this->payment_fields();

		return $this->load->view( 'extension/opencart_gopay/payment/gopay', $data );
	}

	/**
	 * Payment fields.
	 *
	 * @since  1.0.0
	 */
	private function payment_fields() {
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

		// Check if Apple pay is available.
//		<script>
//            var applePayAvailable = false;
//            if(window.ApplePaySession && window.ApplePaySession.canMakePayments()) {
//                applePayAvailable = true;
//            }
//
//            var applePay = document.getElementsByName('APPLE_PAY');
//            if (applePay.length !== 0 && !applePayAvailable) {
//                applePay[0].remove();
//            }
//		</script>

		return $enabled_payment_methods;
	}

	public function confirm(): void {
	}
}

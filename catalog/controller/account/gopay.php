<?php
namespace Opencart\Catalog\Controller\Extension\OpencartGopay\Account;
use Opencart\System\Library\Log;

class GoPay extends \Opencart\System\Engine\Controller
{

	public function info( &$route, &$data ) {
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/gopay.php' );
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

		$data['order_info'] = $this->response->getOutput( 'account/order.info' );

		if ( array_key_exists( 'order_id', $this->request->get ) ) {
			$order_id = (int)$this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		// Empty for an order of another customer or a missing one.
		$order = $this->model_account_order->getOrder( $order_id );

		// Pending or failed GoPay order: offer to pay it again.
		if ( $order && in_array( (int)$order['order_status_id'], array( 1, 10 ), true ) &&
			\GoPay_API::is_gopay_order( $order ) ) {

			$data['language'] = $this->config->get( 'config_language' );

			$data['country_restriction_message']      = $this->language->get( 'country_restriction_message' );
			$data['subscription_restriction_message'] = $this->language->get( 'subscription_restriction_message' );

			$subscription = $this->db->query( 'SELECT * FROM `' . DB_PREFIX . 'subscription` WHERE order_id = ' .
				(int)$order_id )->row;

			$retry = '<h3>' . $this->language->get( 'text_retry_payment' ) .
				'</h3><div style="border: 1px solid rgb(217,222,226); padding: 10px">' .
				$this->payment_gopay( $subscription ) . '</div>';

			// Above the "Continue" button, the last "text-end" block of the page in OpenCart 4.0.2 and 4.1.
			$position = strrpos( $data['order_info'], '<div class="text-end' );
			if ( $position !== false ) {
				$data['order_info'] = substr_replace( $data['order_info'], $retry, $position, 0 );
			}

			$data['order_id'] = $order_id;
		}

		$this->response->setOutput( $this->load->view( 'extension/opencart_gopay/account/gopay', $data ) );
	}

	/**
	 * Saved payment cards on the "Payment Methods" account page (OpenCart 4.1+)
	 *
	 * OpenCart calls this for every enabled payment extension and shows the
	 * returned HTML; nothing is shown for null (also when the customer has no
	 * saved card, OpenCart then shows its own "no results" text).
	 *
	 * @return string|null
	 * @since  1.0.5
	 */
	public function index(): ?string {
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/gopay.php' );

		if ( !\GoPay_API::has_native_payment_methods_page() || !\GoPay_API::is_card_token_enabled( $this ) ||
			!$this->customer->isLogged() ) {
			return null;
		}

		$result = \GoPay_API::get_card_details( (int)$this->customer->getId(), $this );
		$cards  = $this->displayed_cards( $result['cards'] );

		if ( !$cards && !$result['error'] ) {
			return null;
		}

		return $this->cards_list( $cards, $result['error'], true );
	}

	/**
	 * Saved payment cards page (OpenCart 4.0.2.x)
	 *
	 * OpenCart 4.0.2.x has no usable "Payment Methods" page, so the cards get
	 * a page of their own, linked from the account page (account_link()).
	 *
	 * @since  1.0.5
	 */
	public function cards(): void {
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/gopay.php' );

		$language = $this->config->get( 'config_language' );
		$token    = $this->session->data['customer_token'] ?? '';

		if ( !\GoPay_API::is_card_token_enabled( $this ) ) {
			$this->response->redirect( $this->url->link( 'account/account', 'language=' . $language .
				( $token ? '&customer_token=' . $token : '' ) ) );
		}

		// One place for the cards - the native page on OpenCart 4.1+.
		if ( \GoPay_API::has_native_payment_methods_page() ) {
			$this->response->redirect( $this->url->link( 'account/payment_method', 'language=' . $language .
				( $token ? '&customer_token=' . $token : '' ) ) );
		}

		if ( !$this->is_customer_session() ) {
			$this->session->data['redirect'] = $this->url->link( 'extension/opencart_gopay/account/gopay.cards',
				'language=' . $language );

			$this->response->redirect( $this->url->link( 'account/login', 'language=' . $language ) );
		}

		$this->load->language( 'account/account' );
		$this->load->language( 'extension/opencart_gopay/account/gopay' );

		$this->document->setTitle( $this->language->get( 'gopay_heading_cards' ) );

		$data['breadcrumbs']   = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get( 'text_home' ),
			'href' => $this->url->link( 'common/home', 'language=' . $language ),
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get( 'text_account' ),
			'href' => $this->url->link( 'account/account', 'language=' . $language . '&customer_token=' . $token ),
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get( 'gopay_heading_cards' ),
			'href' => $this->url->link( 'extension/opencart_gopay/account/gopay.cards', 'language=' . $language .
				'&customer_token=' . $token ),
		);

		$result = \GoPay_API::get_card_details( (int)$this->customer->getId(), $this );

		$data['list'] = $this->cards_list( $this->displayed_cards( $result['cards'] ), $result['error'], false );
		$data['back'] = $this->url->link( 'account/account', 'language=' . $language . '&customer_token=' . $token );

		$data['column_left']    = $this->load->controller( 'common/column_left' );
		$data['column_right']   = $this->load->controller( 'common/column_right' );
		$data['content_top']    = $this->load->controller( 'common/content_top' );
		$data['content_bottom'] = $this->load->controller( 'common/content_bottom' );
		$data['footer']         = $this->load->controller( 'common/footer' );
		$data['header']         = $this->load->controller( 'common/header' );

		$this->response->setOutput( $this->load->view( 'extension/opencart_gopay/account/gopay_cards', $data ) );
	}

	/**
	 * Add the "Payment cards" link to the account page (OpenCart 4.0.2.x)
	 *
	 * Event handler for catalog/controller/account/account/after. The account
	 * page has its links hard coded, so the link is inserted after the address
	 * book link of the rendered page - only in the main content, not in the
	 * Account module of a side column. Nothing changes when the link is not found
	 * (custom theme).
	 *
	 * @param string $route  route of the controller.
	 * @param array  $args   arguments of the controller.
	 * @param mixed  $output output of the controller.
	 *
	 * @since  1.0.5
	 */
	public function account_link( &$route, &$args, &$output ): void {
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/gopay.php' );

		if ( \GoPay_API::has_native_payment_methods_page() || !\GoPay_API::is_card_token_enabled( $this ) ||
			!$this->is_customer_session() ) {
			return;
		}

		$this->load->language( 'extension/opencart_gopay/account/gopay' );

		$query  = 'language=' . $this->config->get( 'config_language' ) . '&customer_token=' . $this->session->data['customer_token'];
		$anchor = '<a href="' . $this->url->link( 'account/address', $query ) . '"';
		$html   = $this->response->getOutput();

		$content  = strpos( $html, 'id="content"' );
		$position = strpos( $html, $anchor, $content === false ? 0 : $content );
		$end      = $position === false ? false : strpos( $html, '</li>', $position );

		if ( $end === false ) {
			return;
		}

		$link = '<li><a href="' . $this->url->link( 'extension/opencart_gopay/account/gopay.cards', $query ) . '">' .
			htmlspecialchars( $this->language->get( 'gopay_text_account_link' ), ENT_QUOTES, 'UTF-8' ) . '</a></li>';

		$this->response->setOutput( substr_replace( $html, $link, $end + strlen( '</li>' ), 0 ) );
	}

	/**
	 * Delete a saved payment card (AJAX, JSON)
	 *
	 * The card is deleted on GoPay first, then locally. Only a card saved for
	 * the logged in customer can be deleted - the ownership is checked before
	 * GoPay is called.
	 *
	 * @since  1.0.5
	 */
	public function delete_card(): void {
		require_once( DIR_EXTENSION . '/opencart_gopay/system/library/gopay.php' );

		$this->load->language( 'extension/opencart_gopay/account/gopay' );

		$language = $this->config->get( 'config_language' );
		$json     = array( 'success' => false );

		if ( !\GoPay_API::is_card_token_enabled( $this ) ) {
			$json['redirect'] = $this->url->link( 'account/account', 'language=' . $language, true );
		} elseif ( !$this->is_customer_session() ) {
			// Back to the cards after the login.
			$this->session->data['redirect'] = $this->url->link( \GoPay_API::has_native_payment_methods_page() ?
				'account/payment_method' : 'extension/opencart_gopay/account/gopay.cards', 'language=' . $language );

			$json['redirect'] = $this->url->link( 'account/login', 'language=' . $language, true );
		} else {
			$customer_id = (int)$this->customer->getId();
			$card_id     = trim( (string)( $this->request->post['card_id'] ?? '' ) );

			$this->load->model( 'extension/opencart_gopay/payment/gopay' );

			if ( !$this->model_extension_opencart_gopay_payment_gopay->has_saved_card( $customer_id, $card_id ) ) {
				$json['message'] = $this->language->get( 'gopay_error_card_delete' );
			} else {
				$result = \GoPay_API::delete_payment_card( $card_id, $this );

				if ( !$result['deleted'] ) {
					// The reason from GoPay as it came, otherwise a generic message.
					$json['message'] = $result['error'] !== '' ? $result['error'] :
						$this->language->get( 'gopay_error_card_delete' );
				} else {
					try {
						$removed = $this->model_extension_opencart_gopay_payment_gopay->delete_customer_card( $customer_id, $card_id );
					} catch ( \Exception $e ) {
						$removed = false;
					}

					if ( $removed ) {
						$json['success'] = true;
						$json['card_id'] = $card_id;
					} else {
						$json['message'] = $this->language->get( 'gopay_error_card_delete' );
					}
				}
			}
		}

		$this->response->addHeader( 'Content-Type: application/json' );
		$this->response->setOutput( json_encode( $json ) );
	}

	/**
	 * Render the saved cards list (shared by index() and cards())
	 *
	 * @param array $cards        cards from displayed_cards().
	 * @param bool  $error        some cards could not be loaded from GoPay.
	 * @param bool  $show_heading heading of the GoPay section (native page).
	 *
	 * @return string
	 * @since  1.0.5
	 */
	private function cards_list( array $cards, bool $error, bool $show_heading ): string {
		$this->load->language( 'extension/opencart_gopay/account/gopay' );

		$data['cards']         = $cards;
		$data['error_load']    = $error && !$cards;
		$data['error_partial'] = $error && $cards;
		$data['show_heading']  = $show_heading;
		$data['delete']        = $this->url->link( 'extension/opencart_gopay/account/gopay.delete_card', 'language=' .
			$this->config->get( 'config_language' ) . '&customer_token=' . ( $this->session->data['customer_token'] ?? '' ) );

		return $this->load->view( 'extension/opencart_gopay/account/gopay_cards_list', $data );
	}

	/**
	 * Cards shown in the account: active ones and suspended ones (those can
	 * only be deleted), with the display flags of the template
	 *
	 * @param array $cards cards from GoPay_API::get_card_details().
	 *
	 * @return array
	 * @since  1.0.5
	 */
	private function displayed_cards( array $cards ): array {
		$displayed = array();

		foreach ( $cards as $card ) {
			if ( !in_array( $card['status'], array( 'ACTIVE', 'SUSPENDED' ), true ) ) {
				continue;
			}

			$card['suspended'] = 'SUSPENDED' === $card['status'];
			$card['short_pan'] = '****' . substr( $card['real_masked_pan'], -4 );
			$displayed[]       = $card;
		}

		return $displayed;
	}

	/**
	 * Check the logged in customer and the customer_token of the request
	 * (account/login.validate exists only since OpenCart 4.1)
	 *
	 * @return bool
	 * @since  1.0.5
	 */
	private function is_customer_session(): bool {
		return $this->customer->isLogged() && isset( $this->request->get['customer_token'] ) &&
			isset( $this->session->data['customer_token'] ) &&
			$this->request->get['customer_token'] === $this->session->data['customer_token'];
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

		// Saved cards only for orders without a subscription. The subscription
		// record exists only after the first order status, order_subscription
		// is created together with the order.
		$this->load->model( 'extension/opencart_gopay/payment/gopay' );
		$card_fields_allowed = !$subscription && !$this->model_extension_opencart_gopay_payment_gopay->has_order_subscription(
			(int)( $this->request->get['order_id'] ?? 0 ) );

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

			// Saved cards / save card choice under the card payment method, shared with the checkout.
			if ( 'PAYMENT_CARD' === $payment_method && $card_fields_allowed ) {
				$card_fields = $this->load->controller( 'extension/opencart_gopay/payment/gopay.card_fields' );

				if ( is_string( $card_fields ) ) {
					$enabled_payment_methods .= $card_fields;
				}
			}

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
            <button type=\"submit\" id=\"button-confirm\" class=\"btn btn-primary\">" .
			$this->language->get( 'button_retry_payment' ) . "</button>
        </div>
    </form>
</fieldset>
";

	}

}
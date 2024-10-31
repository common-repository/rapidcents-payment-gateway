<?php
/**
 * WooCommerce Payment Gateway Class.
 *
 * @link       https://rapidcents.com
 * @since      1.0.0
 *
 * @package    Wc_Rapidcents_Gateway
 * @subpackage Wc_Rapidcents_Gateway/includes
 */

/**
 * WooCommerce Payment Gateway Class.
 *
 * @since      1.0.0
 * @package    Wc_Rapidcents_Gateway
 * @subpackage Wc_Rapidcents_Gateway/includes
 * @author     Rapidcents <support@rapidcents.com>
 */
class WC_Gateway_RapidCents extends WC_Payment_Gateway {

	/**
	 * Constructor for the RapidCents payment gateway.
	 * Initializes the gateway settings, fields, and hooks for saving settings.
	 */
	public function __construct() {
		$this->id                 = 'rapidcents'; // Payment gateway ID.
		$this->icon               = ''; // URL of the icon that will be displayed at checkout.
		$this->supports           = array(
			'products',
			'subscriptions',
                'subscription_cancellation',
                'subscription_suspension',
                'subscription_reactivation',
                'subscription_amount_changes',
                'subscription_date_changes',
                'subscription_payment_method_change',
                'multiple_subscriptions',
                'refunds'
		);
		$this->has_fields         = true; // Custom credit card form.
		$this->method_title       = 'RapidCents';
		$this->method_description = 'RapidCents Payment Gateway';

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user settings.
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled' );
		$this->test_mode   = false;
		$this->api_key     = $this->get_option( 'api_key' );
		$this->show_cards  = $this->get_option( 'show_cards' );
		$this->force_3dsecure  = $this->get_option( 'force_3dsecure' );

		// Save settings.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'rapidcents_subscription_scheduled_payment' ), 10, 2 );
	}

	/**
	 * Get the payment gateway icon.
	 *
	 * @return string HTML for the icon, or empty string if icons are not displayed.
	 */
	public function get_icon() {
		$icon_url = WC_RAPIDCENTS_GATEWAY_PLUGIN_URL . 'public/images/cards-ico.png';
		if ( 'yes' === $this->show_cards ) {
			return '<img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $this->title ) . '" class="rc_cards_icon" />';
		} else {
			return '';
		}
	}

	/**
	 * Initialize form fields for the payment gateway settings.
	 */
	public function init_form_fields() {
		$icon_url          = WC_RAPIDCENTS_GATEWAY_PLUGIN_URL . 'public/images/cards-ico.png';
		$display_image     = '<br><img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $this->title ) . '" style="max-width:200px" />';
		$this->form_fields = array(
			'enabled'         => array(
				'title'       => 'Enable/Disable',
				'label'       => 'Enable RapidCents Payment Gateway',
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'           => array(
				'title'       => 'Title',
				'type'        => 'text',
				'description' => 'This controls the title which the user sees during checkout.',
				'default'     => 'Credit Card (RapidCents)',
				'desc_tip'    => true,
			),
			'description'     => array(
				'title'       => 'Description',
				'type'        => 'textarea',
				'description' => 'This controls the description which the user sees during checkout.',
				'default'     => 'Pay securely using your credit card.',
			),
			'show_cards'      => array(
				'title'       => 'Show Cards',
				'label'       => 'Yes/No ' . $display_image,
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'yes',
			),
			'force_3dsecure'      => array(
				'title'       => 'Force 3D Secure',
				'label'       => 'Enable',
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'yes',
			),
			'rc_connect_data' => array(
				'title' => 'Connect',
				'type'  => 'text',
			),
		);
	}

	/**
	 * Sanatize the post form fields.
	 *
	 * @param array $data The form fields to sanitize.
	 * @return array True if the fields are valid, false otherwise.
	 */
	public function sanitize_post_data( $data = array() ) {
		if ( is_array( $data ) ) {
			// If the data is an array, process each element recursively.
			foreach ( $data as $key => $value ) {
				$data[ $key ] = $this->sanitize_post_data( $value );
			}
		} elseif ( is_string( $data ) ) {
			// If the data is a string, sanitize it.
			$data = sanitize_text_field( $data );
		}
		return $data;
	}
	/**
	 * Validate the payment form fields.
	 *
	 * @return bool True if the fields are valid, false otherwise.
	 */
	public function validate_fields() {
		global $woocommerce, $rc_total_amount, $rc_ddd_data, $rc_posted_data, $force_3dsecure;
		$force_3dsecure = ( 'yes' === $this->force_3dsecure ) ? true : false ;
		$rc_total_amount = $woocommerce->cart->total;

		if ( ! isset( $_POST['rc_gateway_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rc_gateway_nonce'] ) ), 'rc_gateway_nonce' ) ) {
			// Nonce verification failed.
			wc_add_notice( __( 'Security check failed, please try again.', 'woocommerce' ), 'error' );
			return;
		}
		$posted_data = $this->sanitize_post_data( $_POST );

		if ( empty( $posted_data['rapidcents_card_number'] ) ) {
			wc_add_notice( __( 'Card number is required!', 'woocommerce' ), 'error' );
			return false;
		}

		if ( empty( $posted_data['rapidcents_card_expiry_month'] ) || empty( $posted_data['rapidcents_card_expiry_year'] ) ) {
			wc_add_notice( __( 'Card expiration date is required!', 'woocommerce' ), 'error' );
			return false;
		}

		if ( empty( $posted_data['rapidcents_card_cvc'] ) ) {
			wc_add_notice( __( 'Card code (CVC) is required!', 'woocommerce' ), 'error' );
			return false;
		}

		if ( empty( $posted_data['billing_first_name'] ) ) {
			wc_add_notice( __( 'Card first name is required!', 'woocommerce' ), 'error' );
			return false;
		}

		if ( empty( $posted_data['billing_last_name'] ) ) {
			wc_add_notice( __( 'Card last name is required!', 'woocommerce' ), 'error' );
			return false;
		}

		if ( empty( $posted_data['billing_email'] ) ) {
			wc_add_notice( __( 'E-Mail address is required!', 'woocommerce' ), 'error' );
			return false;
		}

		if ( empty( $posted_data['rapidcents_card_ddd'] ) ) {
			$errors = array();
			$verification_url;
			$customer_data = array(
				'first_name' => sanitize_text_field( $posted_data['billing_first_name'] ),
				'last_name'  => sanitize_text_field( $posted_data['billing_last_name'] ),
				'email'      => sanitize_email( $posted_data['billing_email'] ),
			);

			$card_data = array(
				'cardNumber' => sanitize_text_field( $posted_data['rapidcents_card_number'] ),
				'month'      => sanitize_text_field( $posted_data['rapidcents_card_expiry_month'] ),
				'year'       => sanitize_text_field( $posted_data['rapidcents_card_expiry_year'] ),
				'nameOnCard' => sanitize_text_field( $posted_data['billing_first_name'] ) . ' ' . sanitize_text_field( $posted_data['billing_last_name'] ),
				'cvv'        => sanitize_text_field( $posted_data['rapidcents_card_cvc'] ),
			);

			$res = rc_helper()->ddd_verify( $customer_data, $card_data, $verification_url, $errors );
			
			if( $force_3dsecure || isset( $res['type'] ) ){ 

				WC()->session->set( 'rc_ddd_request', $res );

				if ( count( $errors ) ) {
					foreach ( $errors as $err ) {
						wc_add_notice( $err, 'error' );
					}
					return false;
				}

				if ( ! empty( $verification_url ) ) {
					echo wp_json_encode(
						array(
							'result'     => 'validation_success',
							'messages'   => ' ',
							'ddd_verify' => $verification_url,
							'refresh'    => false,
							'reload'     => false,
						)
					);
					exit;
				}
			}
		} elseif ( 'rc_verified' === $posted_data['rapidcents_card_ddd'] ) {
			$res         = WC()->session->get( 'rc_ddd_request' );
			$rc_ddd_data = ( isset( $res['data'] ) && is_array( $res['data'] ) ) ? $res['data'] : false;
		} else {
			$rapidcents_card_ddd = sanitize_text_field( $posted_data['rapidcents_card_ddd'] );
			$rapidcents_card_ddd = stripslashes( $rapidcents_card_ddd );
			$rapidcents_card_ddd = json_decode( $rapidcents_card_ddd, true );

			$res = WC()->session->get( 'rc_ddd_request' );
			if ( isset( $res['data'] ) && is_array( $res['data'] ) ) {
				$rapidcents_card_ddd = array_merge( $res['data'], $rapidcents_card_ddd );
			}
			rc_helper()->log( 'rapidcents_card_ddd', $rapidcents_card_ddd );
			$rapidcents_card_ddd = rc_helper()->get_ddd_data( $rapidcents_card_ddd );
			$rc_ddd_data         = $rapidcents_card_ddd;
		}
			
		$rc_posted_data = $posted_data;

		return true;
	}

	/**
	 * Display the payment fields on the checkout page.
	 */
	public function payment_fields() {
		if ( $this->description ) {
			$d = wpautop( $this->description );
			echo wp_kses_post( $d );
		}

		$nonce = wp_create_nonce( 'rc_gateway_nonce' );
		// Display the custom card form fields.
		?>
		<iframe id="frm-rapidcents-input" frameborder="0" scrolling="no" allowtransparency="true" src="<?php echo esc_html( rc_helper()->get_card_input_url() ); ?>" width="100%" height=2></iframe>
		<div class="rcCCApp">
			<div class="rcCCAppCtl">
				<input type="hidden" name="rc_gateway_nonce" value="<?php echo esc_attr( $nonce ); ?>" />
				<input type="hidden" id="i_rapidcents_card_number" name="rapidcents_card_number" />
				<input type="hidden" id="i_rapidcents_card_expiry_month" name="rapidcents_card_expiry_month" />
				<input type="hidden" id="i_rapidcents_card_expiry_year" name="rapidcents_card_expiry_year" />
				<input type="hidden" id="i_rapidcents_card_cvc" name="rapidcents_card_cvc" />
				<input type="hidden" id="i_rapidcents_card_ddd" name="rapidcents_card_ddd" />
			</div>
		</div>
		<script language="javascript">
		window._rc_card_fill = function(obj){
			jQuery('#i_rapidcents_card_number').val(obj.cardNumber);
			jQuery('#i_rapidcents_card_expiry_month').val(obj.expiryMonth);
			jQuery('#i_rapidcents_card_expiry_year').val(obj.expiryYear);
			jQuery('#i_rapidcents_card_cvc').val(obj.cvc);
		};
		window._rc_ddd_fill = function(obj){
			jQuery('#i_rapidcents_card_ddd').val(obj);
		};
		window._rc_app_height = function(h){
			jQuery("#frm-rapidcents-input").height(h);
		};
		function _rc_card_resize(iHeight){
			jQuery('#frm-rapidcents-input').height(iHeight+5);
		}
		</script>
		<?php
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id The order ID.
	 * @return array|void The result array with 'result' and 'redirect' keys on success, or void on failure.
	 */
	public function process_payment( $order_id ) {
		global $rc_ddd_data, $rc_posted_data;
		$order       = wc_get_order( $order_id );
		$posted_data = $rc_posted_data;
		$card_data   = array(
			'cardNumber' => $posted_data['rapidcents_card_number'],
			'month'      => $posted_data['rapidcents_card_expiry_month'],
			'year'       => $posted_data['rapidcents_card_expiry_year'],
			'nameOnCard' => $posted_data['billing_first_name'] . ' ' . $posted_data['billing_last_name'],
			'cvv'        => $posted_data['rapidcents_card_cvc'],
		);

		if( ! empty( $rc_ddd_data ) )
			$card_data['ddd'] = $rc_ddd_data;

		$response = array();
		$errors   = array();
		if ( rc_helper()->process_order_payment( $order, $card_data, $errors, $response ) ) {

			$order->payment_complete();
			if ( isset( $response['message'] ) ) {
				$order->add_order_note( $response['message'] );
			}

			if ( isset( $response['recordID'] ) ) {
				$order->add_order_note( 'Record ID: ' . $response['recordID'] );
			}
				$order->add_order_note( 'Issuer Reference: ' . $response['details']['issuer_reference'] );
				$order->update_meta_data( '_rapidcents_response', $response );
				if( isset( $card_data['ddd']['threeDSServerTransID']) && ! empty( $card_data['ddd']['threeDSServerTransID'] ) ){
					$order->add_order_note( 'threeDSServerTransID: ' . $card_data['ddd']['threeDSServerTransID'] );
					$order->add_order_note( '3dSecure: Enabled.' );
				}else{
					$order->add_order_note( '3dSecure: Not enabled.' );
				}
				$order->save();
				wc_reduce_stock_levels( $order_id );
				WC()->cart->empty_cart();
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
		} else {
			if ( count( $errors ) ) {
				foreach ( $errors as $err ) {
					wc_add_notice( $err, 'error' );
				}
			} else {
				if( isset( $response['message'] ) ){
					wc_add_notice( $response['message'], 'error' );
				} else {
					wc_add_notice( 'Payment: Declined', 'error' );
				}
			}
				return;
		}
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param float    $amount Amount.
	 * @param WC_Order $order Amount.
	 * @return array|void The result array with 'result' and 'redirect' keys on success, or void on failure.
	 */
	public function process_subscription_payment( $amount, $order ) {
		//$parent_order_id = $order->get_parent_id();
		$parent_order_id = WC_Subscriptions_Renewal_Order::get_parent_order_id( $order->get_id() );
		rc_helper()->log( 'subscription id: ' . $order->get_parent_id() . ' parent id:', $parent_order_id, 'rc_subscription_log' );
		if ( intval( $parent_order_id ) > 0 ) {
			$parent_order = wc_get_order( $parent_order_id );

			$customer_id = $parent_order->get_meta( '_rapidcents_customer_id' );
			$card_id     = $parent_order->get_meta( '_rapidcents_card_id' );
			if ( ! empty( $customer_id ) && ! empty( $card_id ) ) {
				$sale_data               = array();
				$sale_data['amount']     = $amount;
				$sale_data['ip_address'] = rc_helper()->get_user_ip();
				$sale_data['user_agent'] = rc_helper()->get_user_agent();
				$response                = array();
				$errors                  = array();

				$out_res = rc_helper()->process_subscription( $customer_id, $card_id, $sale_data, $errors, $response );

				if ( count( $errors ) ) {

					foreach ( $errors as $err ) {
						$order->add_order_note( 'Error: ' . $err );
					}
					$order->save();

				} elseif ( 0 === count( $errors ) && $out_res ) {
					$order->payment_complete();
					if ( isset( $response['message'] ) ) {
						$order->add_order_note( $response['message'] );
					}

					if ( isset( $response['recordID'] ) ) {
						$order->add_order_note( 'Record ID: ' . $response['recordID'] );
					}
							$order->add_order_note( 'Issuer Reference: ' . $response['details']['issuer_reference'] );
						$order->update_meta_data( '_rapidcents_response', $response );
						$order->add_order_note( 'Subscription payment processed successfully.' );
						$order->save();

					WC_Subscriptions_Manager::process_subscription_payments_on_order( $order->get_id() );
				} else {
					WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );
					//$order->update_status( 'on-hold' );
					$order->add_order_note( 'Subscription payment failed.' );
					if ( count( $errors ) ) {
						foreach ( $errors as $err ) {
							$order->add_order_note( $err );
						}
					} else {
						$order->add_order_note( 'Payment: Declined' );
					}
					$order->save();
					
					return;
				}
			} else {
				WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );
				//$order->update_status( 'on-hold' );
				$order->add_order_note( 'No CC Provided.' );
				$order->save();
			}
		}
	}

	public function rapidcents_subscription_scheduled_payment( $amount_to_charge, $renewal_order ){
		$this->process_subscription_payment($amount_to_charge, $renewal_order);
	}
	/**
	 * Process a refund request.
	 *
	 * @param int        $order_id The order ID.
	 * @param float|null $amount The amount to refund.
	 * @param string     $reason The reason for the refund.
	 * @return bool True if the refund was processed successfully, false otherwise.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}
		$errors     = array();
		$refund_res = array();
		$r          = rc_helper()->process_refund_payment( $order, $amount, $errors, $refund_res );

		if ( ! $r ) {
			return false;
		}

		if ( ! isset( $refund_res['recordID'] ) ) {
			return false;
		}

		if ( count( $errors ) ) {
			foreach ( $errors as $err ) {
				$order->add_order_note( $err );
			}
		}
		// Translators: %s is the amount.
		$refunded_text = sprintf( __( 'Refunded: %s', 'woocommerce' ), sanitize_text_field( $amount ) );
		$order->add_order_note( $refunded_text );

		// Translators: %s is the record ID.
		$refunded_text = sprintf( __( 'Refund Record ID: %s', 'woocommerce' ), sanitize_text_field( $refund_res['recordID'] ) );
		$order->add_order_note( $refunded_text );

		if ( isset( $refund_res['details']['issuer_reference'] ) ) {
			// Translators: %s is the Refund Issuer Reference.
			$refunded_text = sprintf( __( 'Refund Issuer Reference: %s', 'woocommerce' ), sanitize_text_field( $refund_res['details']['issuer_reference'] ) );
			$order->add_order_note( $refunded_text );
		}

		update_post_meta( $order_id, '_transaction_refund_id', sanitize_text_field( $refund_res['recordID'] ) );

		$_transaction_refunds = get_post_meta( $order_id, '_transaction_refunds', array() );
		array_push( $_transaction_refunds, $refund_res );
		update_post_meta( $order_id, '_transaction_refunds', $_transaction_refunds );

		return true;
	}
}
?>

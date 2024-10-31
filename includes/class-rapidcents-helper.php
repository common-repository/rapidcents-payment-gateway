<?php
/**
 * RapidCents Helper Class.
 *
 * @link       https://rapidcents.com
 * @since      1.0.0
 *
 * @package    Wc_Rapidcents_Gateway
 * @subpackage Wc_Rapidcents_Gateway/includes
 */

/**
 * Class RapidCents_Helper
 *
 * Provides helper functions for interacting with the RapidCents payment gateway.
 */
class RapidCents_Helper {

	/**
	 * Constructor for RapidCents_Helper class.
	 */
	public function __construct() {
	}

	/**
	 * Retrieves the settings URL for RapidCents in the WooCommerce admin.
	 *
	 * @return string The settings URL.
	 */
	public function get_settings_url() {
		$url = add_query_arg(
			array(
				'page'    => 'wc-settings',
				'tab'     => 'checkout',
				'section' => 'rapidcents',
			),
			admin_url( 'admin.php' )
		);

		return $url;
	}

	/**
	 * Invokes the token and refreshes it if necessary.
	 *
	 * @param object $r_api_obj The API object.
	 * @param array  $errors Array to hold errors, if any.
	 *
	 * @return string The token.
	 */
	public function invoke_token( &$r_api_obj, &$errors = array() ) {
		$token = $this->get_token();
		$r_api_obj->set_token( $token );
		$token = $r_api_obj->refresh_token( $errors );
		$token = $r_api_obj->get_token();
		$this->update_token( $token );
		return $token;
	}

	/**
	 * Retrieves the server IP address.
	 *
	 * @return string The server IP address.
	 */
	public function get_server_ip() {
		// Retrieve the server IP address from $_SERVER.
		$server_ip = isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '';

		// Validate the IP address.
		$sanitized_server_ip = filter_var( $server_ip, FILTER_VALIDATE_IP );

		// If the IP address is not valid, handle it (optional).
		if ( false === $sanitized_server_ip ) {
			// Handle the invalid IP address case (optional).
			$sanitized_server_ip = '0.0.0.0'; // Default or placeholder value.
		}

		// Return the sanitized IP address.
		return $sanitized_server_ip;
	}

	/**
	 * Retrieves the user agent from the server.
	 *
	 * @return string The user agent.
	 */
	public function get_user_agent() {
		// Retrieve the user agent string from $_SERVER.
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : 'Unknown';

		// Return the sanitized user agent string.
		return $user_agent;
	}

	/**
	 * Retrieves the user IP address, considering different server variables.
	 *
	 * @return string The user IP address.
	 */
	public function get_user_ip() {
		// Initialize IP variable.
		$ip = '';

		// Check if IP is from shared internet.
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) { // Check if IP is passed from a proxy.
			// X-Forwarded-For can be a comma-separated list.
			$ip_list = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			// Take the first IP in the list.
			$ip = trim( $ip_list[0] );
		} else { // Default method.
			$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		}

		// Validate and sanitize the IP address.
		$sanitized_ip = filter_var( $ip, FILTER_VALIDATE_IP );

		// If validation fails, assign a default IP or handle the error.
		if ( false === $sanitized_ip ) {
			$sanitized_ip = '0.0.0.0'; // Default or placeholder value.
		}

		return $sanitized_ip;
	}

	/**
	 * Retrieves the redirect URL for RapidCents.
	 *
	 * @return string The redirect URL.
	 */
	public function get_redirect_url() {
		return get_site_url() . '/wc-rapidcents';
	}

	/**
	 * Retrieves the card input URL for RapidCents.
	 *
	 * @return string The card input URL.
	 */
	public function get_card_input_url() {
		return get_site_url() . '/wc-rapidcents-input';
	}

	/**
	 * Retrieves the API credentials stored in options.
	 *
	 * @return array The API credentials.
	 */
	public function get_api_credentials() {
		return get_option( '_rc_credentials', array() );
	}

	/**
	 * Updates the authorization code in options.
	 *
	 * @param string $code The authorization code.
	 */
	public function update_auth_code( $code ) {
		update_option( '_rc_authorization_code', $code );
	}

	/**
	 * Updates the token in options.
	 *
	 * @param string $token_code The token code.
	 */
	public function update_token( $token_code ) {
		update_option( '_rc_token', $token_code );
	}

	/**
	 * Retrieves the authorization code from options.
	 *
	 * @return string|false The authorization code or false if not set.
	 */
	public function get_auth_code() {
		return get_option( '_rc_authorization_code', false );
	}

	/**
	 * Retrieves the token from options.
	 *
	 * @return string|false The token or false if not set.
	 */
	public function get_token() {
		return get_option( '_rc_token', false );
	}

	/**
	 * Retrieves and optionally clears messages stored in transient.
	 *
	 * @param bool $clear Whether to clear the messages after retrieving.
	 *
	 * @return array The messages.
	 */
	public function get_messages( $clear = true ) {
		$messages        = array();
		$transient_value = get_transient( '_yc_rc_messages' );
		if ( $clear ) {
			delete_transient( '_yc_rc_messages' );
		}
		return is_array( $transient_value ) ? $transient_value : array();
	}

	/**
	 * Adds a message to be stored in transient.
	 *
	 * @param string $message The message to add.
	 * @param string $type The type of the message (e.g., 'success', 'error').
	 */
	public function add_message( $message, $type = 'success' ) {
		if ( ! empty( $message ) && is_user_logged_in() ) {
			$messages = $this->get_messages( false );

			array_push(
				$messages,
				array(
					'message' => $message,
					'type'    => $type,
				)
			);

			$expiration = 3600; // 1 hour expiration time
			set_transient( '_yc_rc_messages', $messages, $expiration );
		}
	}

	/**
	 * Retrieves data related to 3D Secure (3DS) from the provided array.
	 *
	 * @param array $i_array The input array containing 3DS data.
	 *
	 * @return array The extracted 3DS data.
	 */
	public function get_ddd_data( $i_array ) {
		$keys   = array(
			'threeDSMethodURL',
			'threeDSMethodData',
			'acsURL',
			'creq',
			'threeDSServerTransID',
			'transStatus',
			'authenticationValue',
			'eci',
			'version',
			'dsTransID',
			'association',
			'sessionID',
		);
		$result = array();

		// Loop through each key.
		foreach ( $keys as $key ) {
			// Check if the key exists in the input array.
			if ( array_key_exists( $key, $i_array ) ) {
				// Add the key-value pair to the result array.
				$result[ $key ] = $i_array[ $key ];
			}
		}

		return $result;
	}

	/**
	 * Verifies 3D Secure (3DS) authentication with customer and card data.
	 *
	 * @param array  $customer_data Customer data for verification.
	 * @param array  $card_data Card data for verification.
	 * @param string $verification_url URL for 3DS verification.
	 * @param array  $errors Array to hold errors, if any.
	 *
	 * @return array The verification result.
	 */
	public function ddd_verify( $customer_data, $card_data, &$verification_url = '', &$errors = array() ) {
		global $rc_total_amount;
		$r           = array();
		$credentials = $this->get_api_credentials();
		$r_api       = new RapidCents_API( $credentials );
		$token       = $this->invoke_token( $r_api, $errors );

		$res = $r_api->create_customer( $customer_data, $errors );

		$customer_id = '';

		if ( isset( $res['data']['id'] ) ) {
			$customer_id = $res['data']['id'];
		}
		if ( isset( $res['errors']['existing_customer_id'] ) ) {
			$customer_id = $res['errors']['existing_customer_id'][0];
		}

		if ( ! count( $errors ) ) {
			$res = $r_api->init_three_d( $customer_id, $card_data, $errors );
			if ( ! count( $errors ) ) {
				if ( isset( $res['status'] ) ) {
					$r['type'] = $res['status'];
				}
				if ( isset( $res['data'] ) ) {
					$r['data'] = $res['data'];
				}
				if ( isset( $res['data']['threeDSServerTransID'] ) && isset( $res['data']['sessionID'] ) ) {
					$args = array(
						'customer_id'          => $customer_id,
						'threeDSServerTransID' => $res['data']['threeDSServerTransID'],
						'sessionID'            => $res['data']['sessionID'],
						'email'                => $customer_data['email'],
					);
					$res  = $r_api->authenticate_three_d( $args, $card_data, $rc_total_amount, $errors );
					if ( isset( $res['data'] ) ) {
						$r['data']        = array_merge( $r['data'], $res['data'] );
						$verification_url = $this->get_ddd_verification_url( $r );
					}
				}
			}
		}
		return $r;
	}

	/**
	 * Retrieves the 3D Secure (3DS) verification URL.
	 *
	 * @param array $data Data used to generate the verification URL.
	 *
	 * @return string|false The verification URL or false if invalid data.
	 */
	public function get_ddd_verification_url( $data ) {
		$r = false;
		if ( is_array( $data ) ) {
			$qurey = serialize( $data );
			$qurey = base64_encode( $qurey );
			$r     = get_site_url() . '/wc-rapidcents-ddd-auth/?code=' . $qurey;
		}
		return $r;
	}

	/**
	 * Processes a refund payment for an order.
	 *
	 * @param object $order The order object.
	 * @param float  $amount The amount to refund.
	 * @param array  $errors Array to hold errors, if any.
	 * @param array  $res Array to hold the response, if any.
	 *
	 * @return bool Whether the refund was successful.
	 */
	public function process_refund_payment( $order, $amount, &$errors = array(), &$res = array() ) {

		$r = false;

		$_rapidcents_response = $order->get_meta( '_rapidcents_response' );
		if ( isset( $_rapidcents_response['recordID'] ) && ! empty( $_rapidcents_response['recordID'] ) ) {
			$trans_id    = $_rapidcents_response['recordID'];
			$credentials = $this->get_api_credentials();

			$r_api = new RapidCents_API( $credentials );

			$token = $this->invoke_token( $r_api, $errors );

			$res = $r_api->refund( $trans_id, $amount, $errors );
			rc_helper()->log( 'process_refund_payment: ', $res );
			if ( 'Approved' === $res['status'] ) {
					$r = true;
			}
		}
		return $r;
	}

	/**
	 * Processes an order payment.
	 *
	 * @param object $order The order object.
	 * @param array  $card_data Card data for the payment.
	 * @param array  $errors Array to hold errors, if any.
	 * @param array  $res Array to hold the response, if any.
	 *
	 * @return bool Whether the payment was successful.
	 */
	public function process_order_payment( $order, $card_data, &$errors = array(), &$res = array() ) {
		global $rc_ddd_data;

		$credentials = $this->get_api_credentials();

		$r_api = new RapidCents_API( $credentials );

		$token = $this->invoke_token( $r_api, $errors );

		$customer_data               = array();
		$customer_data['first_name'] = $order->get_billing_first_name();
		$customer_data['last_name']  = $order->get_billing_last_name();
		$customer_data['email']      = $order->get_billing_email();
		$res                         = $r_api->create_customer( $customer_data, $errors );

		$customer_id = '';

		if ( isset( $res['data']['id'] ) ) {
			$customer_id = $res['data']['id'];
		}
		if ( isset( $res['errors']['existing_customer_id'] ) ) {
			$customer_id = $res['errors']['existing_customer_id'][0];
		}

		if ( ! empty( $customer_id ) ) {
			$sale_data                     = array();
			$sale_data['customer_id']      = $customer_id;
			$sale_data['invoice_id']       = strval( $order->get_id() );
			$sale_data['amount']           = $order->get_total();
			$sale_data['customer_address'] = array( 'postalCode' => $order->get_billing_postcode() );
			if ( is_array( $rc_ddd_data ) ) {
				$sale_data['ddd'] = $rc_ddd_data;
			}

			$sale_data['ip_address'] = $this->get_user_ip();
			$sale_data['user_agent'] = $this->get_user_agent();

			$card_data['nameOnCard'] = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

			$res = $r_api->credit_sale( $sale_data, $card_data, $errors );

			if ( 'Approved' === $res['status'] ) {
				/* Card Tokenization start */
				$customer              = array(
					'email'     => $order->get_billing_email(),
					'firstName' => $order->get_billing_first_name(),
					'lastName'  => $order->get_billing_last_name(),
				);
				$card_data['saveCard'] = true;
				$card_id               = $r_api->tokenize( $customer_id, $card_data, $customer, $sale_data['customer_address'], $errors );
				if ( ! empty( $card_id ) ) {
					$order->update_meta_data( '_rapidcents_card_id', $card_id );
					$order->update_meta_data( '_rapidcents_customer_id', $customer_id );
					$order->save();
				}
					/* Card Tokenization end */
					$r = true;
			}
		}

		return $r;
	}

	/**
	 * Processes subscription an order payment.
	 *
	 * @param string $customer_id The customer id.
	 * @param string $card_id The card id.
	 * @param array  $sale_data The sale data.
	 * @param array  $errors Array to capture any errors.
	 * @param array  $res Array to hold the response, if any.
	 *
	 * @return bool Whether the payment was successful.
	 */
	public function process_subscription( $customer_id, $card_id, $sale_data, &$errors = array(), &$res = array() ) {
		$credentials = $this->get_api_credentials();

		$r_api = new RapidCents_API( $credentials );

		$token = $this->invoke_token( $r_api, $errors );

		$r = false;
		if ( 0 === count( $errors ) ) {
			$res = $r_api->credit_sale_subscription( $customer_id, $card_id, $sale_data, $errors );

			if ( 'Approved' === $res['status'] ) {

					$r = true;
			}
		}
			return $r;
	}

	/**
	 * Updates the list of businesses from the API.
	 *
	 * @param string $type The type of businesses to retrieve ('live' or 'test').
	 * @param array  $errors Array to hold errors, if any.
	 */
	public function update_businesses( $type = 'live', $errors = array() ) {
		$credentials = $this->get_api_credentials();

		$r_api = new RapidCents_API( $credentials );

		$token = $this->invoke_token( $r_api, $errors );

		$res = $r_api->get_businesses( $errors );

		if ( is_array( $res ) ) {
			update_option( '_rc_businesses_' . $type, $res );
		} else {
			update_option( '_rc_businesses_' . $type, array() );
		}
	}

	/**
	 * Retrieves the list of businesses from options.
	 *
	 * @param string $type The type of businesses to retrieve ('live' or 'test').
	 *
	 * @return array The list of businesses.
	 */
	public function get_businesses( $type = 'live' ) {
		return get_option( '_rc_businesses_' . $type, array() );
	}


	/**
	 * Logs messages related to the RapidCents API.
	 *
	 * @param string $title The title of the log entry.
	 * @param mixed  $output The data to log.
	 * @param string $logger_name The title of the log.
	 */
	public function log( $title, $output, $logger_name = 'rapidcents_api_logs' ) {
		if ( defined( 'WC_RAPIDCENTS_GATEWAY_LOGGING' ) && WC_RAPIDCENTS_GATEWAY_LOGGING ) {
			$logger = new WC_Logger();

			// Sanitize the output before logging.
			$msg = is_array( $output ) ? print_r( $output, true ) : $output;

			// Sanitize title and message for safe logging.
			$title = sanitize_text_field( $title );
			$msg   = sanitize_textarea_field( $msg );

			// Log the message.
			$logger->add( $logger_name, $title . $msg );
		}
	}
}

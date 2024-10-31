<?php
/**
 * RapidCents API Class.
 *
 * @link       https://rapidcents.com
 * @since      1.0.0
 *
 * @package    Wc_Rapidcents_Gateway
 * @subpackage Wc_Rapidcents_Gateway/includes
 */

/**
 * Class RapidCents_API
 *
 * Handles the API integration for RapidCents.
 */
class RapidCents_API {

	/**
	 * API Credentials.
	 *
	 * @var array $credentials Stores the API credentials.
	 */
		private $credentials = array();

		/**
		 * Security code by API.
		 *
		 * @var array $token_data Stores the token data.
		 */
		private $token_data = array();

	/**
	 * Constructor.
	 *
	 * @param array $data API credentials.
	 */
	public function __construct( $data ) {
		$this->credentials = $data;
	}

	/**
	 * Retrieves the API credentials based on the environment (test or live).
	 *
	 * @param array $errors Reference to an array to store errors.
	 * @return array The credentials array.
	 */
	public function get_credentials( &$errors = array() ) {
		$r                  = array();
		$r['base_url']      = '';
		$r['client_id']     = '';
		$r['client_secret'] = '';
		$r['redirect_uri']  = '';
		$r['business_id']   = '';

		if ( isset( $this->credentials['enable_test'] ) && ( 'true' === $this->credentials['enable_test'] || intval( $this->credentials['enable_test'] ) === 1 ) ) {
			if ( ! isset( $this->credentials['test']['client_id'] ) || empty( $this->credentials['test']['client_id'] ) ) {
				array_push( $errors, 'Invalid Client ID' );
			} else {
				$r['client_id'] = $this->credentials['test']['client_id'];
			}

			if ( ! isset( $this->credentials['test']['client_secret'] ) || empty( $this->credentials['test']['client_secret'] ) ) {
				array_push( $errors, 'Invalid Client Secret' );
			} else {
				$r['client_secret'] = $this->credentials['test']['client_secret'];
			}

			if ( ! isset( $this->credentials['test']['business_id'] ) || empty( $this->credentials['test']['business_id'] ) ) {
				array_push( $errors, 'Invalid Business ID' );
			} else {
				$r['business_id'] = $this->credentials['test']['business_id'];
			}

			$r['base_url'] = 'https://uatstage00-api.rapidcents.com';
		} else {
			if ( ! isset( $this->credentials['live']['client_id'] ) || empty( $this->credentials['live']['client_id'] ) ) {
				array_push( $errors, 'Invalid Client ID' );
			} else {
				$r['client_id'] = $this->credentials['live']['client_id'];
			}

			if ( ! isset( $this->credentials['live']['client_secret'] ) || empty( $this->credentials['live']['client_secret'] ) ) {
				array_push( $errors, 'Invalid Client Secret' );
			} else {
				$r['client_secret'] = $this->credentials['live']['client_secret'];
			}

			if ( ! isset( $this->credentials['live']['business_id'] ) || empty( $this->credentials['live']['business_id'] ) ) {
				array_push( $errors, 'Invalid Business ID' );
			} else {
				$r['business_id'] = $this->credentials['live']['business_id'];
			}

			$r['base_url'] = 'https://api.rapidcents.com';
		}

		if ( ! isset( $this->credentials['redirect_uri'] ) || empty( $this->credentials['redirect_uri'] ) ) {
				array_push( $errors, 'Invalid Redirect URI' );
		} else {
				$r['redirect_uri'] = $this->credentials['redirect_uri'];
		}
		return $r;
	}

	/**
	 * Sets the token data.
	 *
	 * @param array $token_data Token data.
	 */
	public function set_token( $token_data ) {
		$this->token_data = $token_data;
	}

	/**
	 * Gets the token data.
	 *
	 * @return array The token data.
	 */
	public function get_token() {
		return $this->token_data;
	}

	/**
	 * Refreshes the access token using the refresh token.
	 *
	 * @param array $errors Reference to an array to store errors.
	 * @return array|false The new token data, or false on failure.
	 */
	public function refresh_token( &$errors = array() ) {
			$credentials   = $this->get_credentials( $errors );
			$refresh_token = ( $this->token_data['refresh_token'] ) ? $this->token_data['refresh_token'] : '';
			$params        = array(
				'grant_type'    => 'refresh_token',
				'client_id'     => $credentials['client_id'],
				'client_secret' => $credentials['client_secret'],
				'redirect_uri'  => $credentials['redirect_uri'],
				'refresh_token' => $refresh_token,
			);

			$url_data = $this->http_post( $credentials['base_url'] . '/oauth/token', $params, $errors );
			rc_helper()->log( 'refresh_token', $url_data );
			$url_data = json_decode( $url_data, true );

			if ( isset( $url_data['error'] ) ) {
				array_push( $errors, $url_data['message'] );
			}

			if ( count( $errors ) ) {
				return false;
			} elseif ( ! count( $errors ) && is_array( $url_data ) && count( $url_data ) > 1 ) {
				if ( isset( $url_data['access_token'] ) ) {
					$this->set_token( $url_data );
				}
				return $url_data;
			}
	}

	/**
	 * Gets the authorization URL for OAuth.
	 *
	 * @param array $errors Reference to an array to store errors.
	 * @return string|null The authorization URL, or null on failure.
	 */
	public function get_authorize_url( &$errors = array() ) {
		$credentials = $this->get_credentials( $errors );
		$params      = array(
			'client_id'     => $credentials['client_id'],
			'redirect_uri'  => $credentials['redirect_uri'],
			'response_type' => 'code',
			'scope'         => '*',
		);
		$auth_url    = $credentials['base_url'] . '/oauth/authorize?' . http_build_query( $params );

		$url_data = $this->http_get( $auth_url, $errors );

		$url_data = json_decode( $url_data, true );
		if ( isset( $url_data['error'] ) ) {
			array_push( $errors, $url_data['message'] );
		}

		if ( count( $errors ) ) {
			return null;
		} else {
			return $auth_url;
		}
	}

	/**
	 * Exchanges the authorization code for an access token.
	 *
	 * @param string $code Authorization code.
	 * @param array  $errors Reference to an array to store errors.
	 * @return array|false The token data, or false on failure.
	 */
	public function get_token_by_code( $code, &$errors = array() ) {
				$credentials = $this->get_credentials( $errors );

				$params = array(
					'grant_type'    => 'authorization_code',
					'client_id'     => $credentials['client_id'],
					'client_secret' => $credentials['client_secret'],
					'redirect_uri'  => $credentials['redirect_uri'],
					'code'          => $code,
					'scope'         => '*',
				);

				$url_data   = $this->http_post( $credentials['base_url'] . '/oauth/token', $params, $errors );
				$ip_address = $this->extract_ip_address( $url_data );
				$url_data   = json_decode( $url_data, true );
				if ( isset( $url_data['error'] ) ) {
					array_push( $errors, $url_data['message'] );
				}

				if ( count( $errors ) ) {
					return false;
				} elseif ( ! count( $errors ) && ! is_array( $url_data ) ) {
					array_push( $errors, 'Invalid IP address.' . $ip_address );
				} elseif ( ! count( $errors ) && is_array( $url_data ) && count( $url_data ) > 1 ) {
					return $url_data;
				}
	}

	/**
	 * Retrieves a list of businesses associated with the account.
	 *
	 * @param string $text Reference to an text to store errors.
	 * @return string|false The ip address, or false on failure.
	 */
	public function extract_ip_address( $text ) {
		$text = wp_strip_all_tags( $text );
		// Regular expression pattern for matching an IPv4 address.
		$ip_pattern = '/\b(?:[0-9]{1,3}\.){3}[0-9]{1,3}\b/';

		// Use preg_match to find the first IP address in the text.
		if ( preg_match( $ip_pattern, $text, $matches ) ) {
			return $matches[0]; // Return the first matched IP address.
		}

		return false; // Return false if no IP address is found.
	}
	/**
	 * Retrieves a list of businesses associated with the account.
	 *
	 * @param array $errors Reference to an array to store errors.
	 * @return array|false The businesses data, or false on failure.
	 */
	public function get_businesses( &$errors = array() ) {
			$credentials = $this->get_credentials( $errors );
			$params      = array();

			$token = $this->get_token();
			$token = isset( $token['access_token'] ) ? $token['access_token'] : '';

			$url_data = $this->http_get( $credentials['base_url'] . '/api/businesses', $errors, $token );

			$url_data = json_decode( $url_data, true );

		if ( isset( $url_data['error'] ) ) {
			array_push( $errors, $url_data['message'] );
		}

		if ( isset( $url_data['errors'] ) && is_array( $url_data['errors'] ) ) {
			foreach ( $url_data['errors'] as $err ) {
				array_push( $errors, $err[0] );
			}
		}

		if ( count( $errors ) ) {
			return false;
		} elseif ( ! count( $errors ) && ! is_array( $url_data ) ) {
			array_push( $errors, 'Invalid or missing parameters.' );
			return false;
		} elseif ( ! count( $errors ) && is_array( $url_data ) && count( $url_data ) > 0 ) {
			return $url_data;
		}
	}

	/**
	 * Creates a refund.
	 *
	 * @param string $customer_id The customer ID.
	 * @param array  $card_data The card data.
	 * @param array  $errors Array to capture any errors.
	 * @return mixed The refund data or false on failure.
	 */
	public function create_refund( $customer_id, $card_data = array(), &$errors = array() ) {
			$credentials = $this->get_credentials( $errors );
			$params      = array();

			$card_data = array(
				'cardNumber' => '4485666666666668',
				'month'      => 2,
				'year'       => 25,
				'nameOnCard' => 'Humayoon Q',
				'cvv'        => '056',
			);

			$params['amount'] = 637;

			$params['customer']   = array(
				'first_name' => 'Humayoon',
				'last_name'  => 'Q',
				'email'      => 'test@yelocommerce.com',
			);
			$params['history_id'] = '3be3bf3f-73af-4101-ba26-f87a9548ed4f';

			$token    = $this->get_token();
			$token    = isset( $token['access_token'] ) ? $token['access_token'] : '';
			$url_data = $this->http_post( $credentials['base_url'] . '/api/' . $credentials['business_id'] . '/return', $params, $errors, $token );

			$url_data = json_decode( $url_data, true );
			if ( isset( $url_data['error'] ) ) {
				array_push( $errors, $url_data['message'] );
			}

			if ( isset( $url_data['errors'] ) && is_array( $url_data['errors'] ) ) {
				foreach ( $url_data['errors'] as $err ) {
					array_push( $errors, $err[0] );
				}
			}

			if ( count( $errors ) ) {
				return false;
			} elseif ( ! count( $errors ) && ! is_array( $url_data ) ) {
				array_push( $errors, 'Invalid or missing parameters.' );
				return false;
			} elseif ( ! count( $errors ) && is_array( $url_data ) && count( $url_data ) > 1 ) {
				return $url_data;
			}
	}

	/**
	 * Processes a tokenization.
	 *
	 * @param array $customer_id The sale data.
	 * @param array $card_data The card data.
	 * @param array $customer The customer details.
	 * @param array $address The customer address.
	 * @param array $errors Array to capture any errors.
	 * @return mixed The sale response data or false on failure.
	 */
	public function tokenize( $customer_id, $card_data, $customer, $address, &$errors = array() ) {
		$r           = false;
		$credentials = $this->get_credentials( $errors );
		if ( isset( $card_data['cardNumber'] ) ) {
			$card_data['cardNumber'] = preg_replace( '/\D/', '', $card_data['cardNumber'] );
		}
		$params               = array();
		$params['customerId'] = $customer_id;
		$params['cardData']   = $card_data;
		$params['customer']   = $customer;
		$params['address']    = $address;

		$token = $this->get_token();
		$token = isset( $token['access_token'] ) ? $token['access_token'] : '';

		$url_data = $this->http_post( $credentials['base_url'] . '/api/' . $credentials['business_id'] . '/tokenize', $params, $errors, $token );

		$url_data = json_decode( $url_data, true );

		if ( $url_data['ok'] && isset( $url_data['data'] ) && isset( $url_data['data']['cardID'] ) && ! empty( $url_data['data']['cardID'] ) ) {
			$r = $url_data['data']['cardID'];
		}

		return $r;
	}

	/**
	 * Processes a credit sale subscription.
	 *
	 * @param string $customer_id The customer id.
	 * @param string $card_id The card id.
	 * @param array  $sale_data The sale data.
	 * @param array  $errors Array to capture any errors.
	 * @return mixed The sale response data or false on failure.
	 */
	public function credit_sale_subscription( $customer_id, $card_id = '', $sale_data = array(), &$errors = array() ) {

		$credentials = $this->get_credentials( $errors );

		$params = array();

		$params['amount']     = $sale_data['amount'];
		$params['cardId']     = $card_id;
		$params['ip_address'] = $sale_data['ip_address'];
		$params['user_agent'] = $sale_data['user_agent'];

		$token = $this->get_token();
		$token = isset( $token['access_token'] ) ? $token['access_token'] : '';
		rc_helper()->log( 'subscription sale: REQUEST', $params );
		$url_data = $this->http_post( $credentials['base_url'] . '/api/' . $credentials['business_id'] . '/customer/' . $customer_id . '/sale', $params, $errors, $token );
		rc_helper()->log( 'subscription sale RESPONSE: ', $url_data );

		$url_data = json_decode( $url_data, true );
		if ( isset( $url_data['error'] ) ) {
			array_push( $errors, $url_data['message'] );
		}

		if ( isset( $url_data['errors'] ) && is_array( $url_data['errors'] ) ) {
			foreach ( $url_data['errors'] as $err ) {
				array_push( $errors, $err[0] );
			}
		}

		if ( count( $errors ) ) {
			return false;
		} elseif ( ! count( $errors ) && ! is_array( $url_data ) ) {
			array_push( $errors, 'Invalid or missing parameters.' );
			return false;
		} elseif ( ! count( $errors ) && is_array( $url_data ) && count( $url_data ) > 1 ) {
			return $url_data;
		}
	}

	/**
	 * Processes a credit sale.
	 *
	 * @param array $sale_data The sale data.
	 * @param array $card_data The card data.
	 * @param array $errors Array to capture any errors.
	 * @return mixed The sale response data or false on failure.
	 */
	public function credit_sale( $sale_data = array(), $card_data = array(), &$errors = array() ) {

				$credentials = $this->get_credentials( $errors );

				$card_number             = preg_replace( '/[^0-9]/', '', $card_data['cardNumber'] );
				$card_number             = str_replace( ' ', '', $card_number );
				$card_data['cardNumber'] = $card_number;

				$params = array();

				$params['invoice_id'] = $sale_data['invoice_id'];
				$params['amount']     = $sale_data['amount'];
				$params['cardData']   = $card_data;
				$params['address']    = $sale_data['customer_address'];
				$params['customerId'] = $sale_data['customer_id'];

		if ( isset( $sale_data['ddd'] ) ) {
			$params['ddd'] = $sale_data['ddd'];
		}

		if ( isset( $sale_data['ddd']['sessionID'] ) ) {
			$params['dddSessionID'] = $sale_data['ddd']['sessionID'];
		}
				$token = $this->get_token();
				$token = isset( $token['access_token'] ) ? $token['access_token'] : '';
				rc_helper()->log( 'sale: REQUEST', $params );
				$url_data = $this->http_post( $credentials['base_url'] . '/api/' . $credentials['business_id'] . '/sale', $params, $errors, $token );
				rc_helper()->log( 'sale RESPONSE: ', $url_data );

				$url_data = json_decode( $url_data, true );
		if ( isset( $url_data['error'] ) ) {
			array_push( $errors, $url_data['message'] );
		}

		if ( isset( $url_data['errors'] ) && is_array( $url_data['errors'] ) ) {
			foreach ( $url_data['errors'] as $err ) {
				array_push( $errors, $err[0] );
			}
		}

		if ( count( $errors ) ) {
			return false;
		} elseif ( ! count( $errors ) && ! is_array( $url_data ) ) {
			array_push( $errors, 'Invalid or missing parameters.' );
			return false;
		} elseif ( ! count( $errors ) && is_array( $url_data ) && count( $url_data ) > 1 ) {
			return $url_data;
		}
	}

	/**
	 * Authenticates 3D Secure.
	 *
	 * @param array $args The authentication arguments.
	 * @param array $card_data The card data.
	 * @param float $amount The amount to authenticate.
	 * @param array $errors Array to capture any errors.
	 * @return mixed The authentication response data or false on failure.
	 */
	public function authenticate_three_d( $args = array(), $card_data = array(), $amount = 1, &$errors = array() ) {
		$credentials = $this->get_credentials( $errors );

			$customer_id              = $args['customer_id'];
			$three_ds_server_trans_id = $args['threeDSServerTransID'];
			$session_id               = $args['sessionID'];
			$card_number              = preg_replace( '/[^0-9]/', '', $card_data['cardNumber'] );
			$card_number              = str_replace( ' ', '', $card_number );
			$card_data['cardNumber']  = $card_number;

			$card_data['month'] = sprintf( '%02d', $card_data['month'] );

			$params = array();

			$params['cardData']             = $card_data;
			$params['threeDSServerTransID'] = $three_ds_server_trans_id;
			$params['amount']               = $amount;
			$params['sessionID']            = $session_id;
			$params['email']                = $args['email'];

			$token = $this->get_token();
			$token = isset( $token['access_token'] ) ? $token['access_token'] : '';

			rc_helper()->log( 'ddd/authenticate REQUEST: ', $params );
			$url_data = $this->http_post( $credentials['base_url'] . '/api/ddd/authenticate', $params, $errors, $token );
			rc_helper()->log( 'ddd/authenticate RESPONSE: ', $url_data );
			$url_data = json_decode( $url_data, true );
		if ( isset( $url_data['error'] ) ) {
			array_push( $errors, $url_data['message'] );
		}

		if ( isset( $url_data['errors'] ) && is_array( $url_data['errors'] ) ) {
			foreach ( $url_data['errors'] as $err ) {
				array_push( $errors, $err[0] );
			}
		}

		if ( count( $errors ) ) {
			return false;
		} elseif ( ! count( $errors ) && ! is_array( $url_data ) ) {
			array_push( $errors, 'Invalid or missing parameters.' );
			return false;
		} elseif ( ! count( $errors ) && is_array( $url_data ) && count( $url_data ) > 1 ) {
			return $url_data;
		}
	}

	/**
	 * Initializes 3D Secure.
	 *
	 * @param string $customer_id The customer ID.
	 * @param array  $card_data The card data.
	 * @param array  $errors Array to capture any errors.
	 * @return mixed The initialization response data or false on failure.
	 */
	public function init_three_d( $customer_id, $card_data = array(), &$errors = array() ) {
			$credentials = $this->get_credentials( $errors );

			$card_number             = preg_replace( '/[^0-9]/', '', $card_data['cardNumber'] );
			$card_number             = str_replace( ' ', '', $card_number );
			$card_data['cardNumber'] = $card_number;

			$params = array();

			$params['cardData'] = $card_data;

			$params['customerId'] = $customer_id;

			$token = $this->get_token();
			$token = isset( $token['access_token'] ) ? $token['access_token'] : '';

			rc_helper()->log( 'ddd/init REQUEST: ', $params );
			$url_data = $this->http_post( $credentials['base_url'] . '/api/ddd/init', $params, $errors, $token );
			rc_helper()->log( 'ddd/init RESPONSE: ', $url_data );

			$url_data = json_decode( $url_data, true );
		if ( isset( $url_data['status'] ) && 'error' === $url_data['status'] ) {
			array_push( $errors, $url_data['message'] );
		}
		if ( isset( $url_data['error'] ) ) {
			array_push( $errors, $url_data['message'] );
		}

		if ( isset( $url_data['errors'] ) && is_array( $url_data['errors'] ) ) {
			foreach ( $url_data['errors'] as $err ) {
				array_push( $errors, $err[0] );
			}
		}

		if ( count( $errors ) ) {
			return false;
		} elseif ( ! count( $errors ) && ! is_array( $url_data ) ) {
			array_push( $errors, 'Invalid or missing parameters.' );
			return false;
		} elseif ( ! count( $errors ) && is_array( $url_data ) && count( $url_data ) > 1 ) {
			return $url_data;
		}
	}

	/**
	 * Verifies a card for a given customer.
	 *
	 * @param string $customer_id The unique identifier for the customer whose card is to be verified.
	 * @param array  $card_data An associative array containing card details for verification.
	 *                          The array should include keys such as 'cardNumber', 'month', 'year',
	 *                          'nameOnCard', and 'cvv'. Defaults to predefined card data.
	 * @param array  $errors An array that will be populated with error messages if the
	 *                        request fails or if there are issues with the response.
	 * @return array|false Returns an associative array with verification details if successful,
	 *                     or `false` if an error occurred.
	 */
	public function card_verification( $customer_id, $card_data = array(), &$errors = array() ) {
			$credentials = $this->get_credentials( $errors );
			$params      = array();

			$card_data          = array(
				'cardNumber' => '4124932222222223',
				'month'      => '01',
				'year'       => '25',
				'nameOnCard' => 'Humayoon Q',
				'cvv'        => '056',
			);
			$params['cardData'] = $card_data;

			$params['customerId'] = $customer_id;

			$token    = $this->get_token();
			$token    = isset( $token['access_token'] ) ? $token['access_token'] : '';
			$url_data = $this->http_post( $credentials['base_url'] . '/api/' . $credentials['business_id'] . '/verification', $params, $errors, $token );

			$url_data = json_decode( $url_data, true );
			if ( isset( $url_data['error'] ) ) {
				array_push( $errors, $url_data['message'] );
			}

			if ( isset( $url_data['errors'] ) && is_array( $url_data['errors'] ) ) {
				foreach ( $url_data['errors'] as $err ) {
					array_push( $errors, $err[0] );
				}
			}

			if ( count( $errors ) ) {
				return false;
			} elseif ( ! count( $errors ) && ! is_array( $url_data ) ) {
				array_push( $errors, 'Invalid or missing parameters.' );
				return false;
			} elseif ( ! count( $errors ) && is_array( $url_data ) && count( $url_data ) > 1 ) {
				return $url_data;
			}
	}

	/**
	 * Processes a refund for a specific transaction.
	 *
	 * @param string $trans_id The unique identifier for the transaction to be refunded.
	 * @param float  $amount The amount to be refunded. Defaults to 0.
	 * @param array  $errors An array that will be populated with error messages if the
	 *                        request fails or if there are issues with the response.
	 * @return array|false Returns an associative array with refund details if successful,
	 *                     or `false` if an error occurred.
	 */
	public function refund( $trans_id, $amount = 0, &$errors = array() ) {
		$credentials = $this->get_credentials( $errors );
		$params      = array();

		$params['amount'] = floatval( $amount );

		$token    = $this->get_token();
		$token    = isset( $token['access_token'] ) ? $token['access_token'] : '';
		$url_data = $this->http_post( $credentials['base_url'] . '/api/' . $credentials['business_id'] . '/transactions/' . $trans_id . '/refund', $params, $errors, $token );
		rc_helper()->log( 'Refund Response: ', $url_data );
		$url_data = json_decode( $url_data, true );
		if ( isset( $url_data['error'] ) ) {
			array_push( $errors, $url_data['message'] );
		}

		if ( ! count( $errors ) && is_array( $url_data ) && count( $url_data ) > 1 ) {
			return $url_data;
		} else {
			return false;
		}
	}

	/**
	 * Creates a new customer by sending their data to the API.
	 *
	 * @param array $customer_data An associative array containing customer details.
	 *                             Expected keys are 'first_name', 'last_name', and 'email'.
	 * @param array $errors An array that will be populated with error messages if the
	 *                       request fails or if there are issues with the response.
	 * @return array|false Returns an associative array with customer data if successful,
	 *                     or `false` if an error occurred.
	 */
	public function create_customer( $customer_data = array(), &$errors = array() ) {
		$credentials = $this->get_credentials( $errors );
		$params      = array();

		$params['firstName'] = $customer_data['first_name'];
		$params['lastName']  = $customer_data['last_name'];
		$params['email']     = $customer_data['email'];

		$token    = $this->get_token();
		$token    = isset( $token['access_token'] ) ? $token['access_token'] : '';
		$url_data = $this->http_post( $credentials['base_url'] . '/api/' . $credentials['business_id'] . '/customers', $params, $errors, $token );

		$url_data = json_decode( $url_data, true );
		if ( isset( $url_data['error'] ) ) {
			array_push( $errors, $url_data['message'] );
		}

		if ( count( $errors ) ) {
			return false;
		} elseif ( ! count( $errors ) && ! is_array( $url_data ) ) {
			array_push( $errors, 'Invalid or missing parameters.' );
			return false;
		} elseif ( ! count( $errors ) && is_array( $url_data ) && count( $url_data ) > 1 ) {
			return $url_data;
		}
	}

	/**
	 * Check for cookies.
	 *
	 * @param array $errors An array to hold error messages.
	 * @return array The response from the POST request.
	 */
	public function http_cookies( &$errors = array() ) {
		$cookies;
		$args     = array(
			'headers' => array(
				'Accept'          => '*/*',
				'User-Agent'      => 'PostmanRuntime/7.40.0',
				'Accept-Encoding' => 'gzip, deflate, br',
				'Connection'      => 'keep-alive',
			),
		);
		$response = wp_remote_get( 'https://uatstage00-api.rapidcents.com/oauth/authorize', $args );

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$r             = $errors;
		} else {
			// Get the response cookies.
			$cookies = wp_remote_retrieve_cookies( $response );
		}
		return $cookies;
	}

	/**
	 * Sends an HTTP POST request.
	 *
	 * @param string      $url The URL to send the POST request to.
	 * @param array       $params The parameters to include in the POST request.
	 * @param array       $errors An array to hold error messages.
	 * @param string|null $bearer_token Optional authorization token.
	 * @return string The response from the POST request.
	 */
	public function http_post( $url, $params = array(), &$errors = array(), $bearer_token = '' ) {

		$cookies = $this->http_cookies();

		$headers = array(
			'Accept'          => '*/*',
			'User-Agent'      => 'PostmanRuntime/7.40.0',
			'Accept-Encoding' => 'gzip, deflate',
			'Connection'      => 'keep-alive',
			'Content-Type'    => 'application/json',
		);
		if ( ! empty( $bearer_token ) ) {
			$headers['Authorization'] = 'Bearer ' . $bearer_token;
		}

		$response = wp_remote_post(
			$url,
			array(
				'method'  => 'POST',
				'body'    => wp_json_encode( $params ),
				'headers' => $headers,
				'timeout' => 45,
				'cookies' => $cookies,
			)
		);

		if ( is_wp_error( $response ) ) {
			// There was an error in the request.
			$error_message = $response->get_error_message();
			array_push( $errors, "Error: $error_message" );
		} else {
			// Get the response body.
			$body               = wp_remote_retrieve_body( $response );
			$rc_session_cookies = wp_remote_retrieve_cookies( $response );
			$data               = $body;
		}
		return $data;
	}

	/**
	 * Sends an HTTP GET request.
	 *
	 * @param string      $url The URL to send the GET request to.
	 * @param array       $errors An array to hold error messages.
	 * @param string|null $bearer_token Optional authorization token.
	 * @return string The response from the GET request.
	 */
	public function http_get( $url, &$errors = array(), $bearer_token = '' ) {
		$headers = array(
			'Accept'          => '*/*',
			'User-Agent'      => 'PostmanRuntime/7.40.0',
			'Accept-Encoding' => 'gzip, deflate',
			'Connection'      => 'keep-alive',
		);
		if ( ! empty( $bearer_token ) ) {
			$headers['Content-Type']  = 'application/json';
			$headers['Authorization'] = 'Bearer ' . $bearer_token;
		}
		$args = array(
			'headers' => $headers,

		);
		$response = wp_remote_get( $url, $args );
		$data     = null;
		if ( is_wp_error( $response ) ) {
			// There was an error in the request.
			$error_message = $response->get_error_message();
		} else {
			// Get the response body.
			$body = wp_remote_retrieve_body( $response );

			$data = $body;
		}
		return $data;
	}
}

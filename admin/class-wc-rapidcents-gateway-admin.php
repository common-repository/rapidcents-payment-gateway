<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://rapidcents.com
 * @since      1.0.0
 *
 * @package    Wc_Rapidcents_Gateway
 * @subpackage Wc_Rapidcents_Gateway/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wc_Rapidcents_Gateway
 * @subpackage Wc_Rapidcents_Gateway/admin
 * @author     Rapidcents <support@rapidcents.com>
 */
class Wc_Rapidcents_Gateway_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_action( 'wp_ajax_admin_rapidcent_authorize', array( $this, 'rapidcent_authorize' ) );

		add_action( 'wp_ajax_rapidcents_receipt', array( $this, 'rapidcents_receipt' ) );

		add_action( 'admin_notices', array( $this, 'admin_messages' ), 9999 );

		add_action( 'admin_footer', array( $this, 'admin_footer' ) );
	}

	/**
	 * Display the RapidCents receipt details.
	 *
	 * @since    1.0.0
	 */
	public function rapidcents_receipt() {
		// Verify nonce to ensure the request is legitimate.
		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['nonce'] ), 'rapidcents_receipt_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check if 'id' is set and is a valid integer.
		if ( ! isset( $_GET['id'] ) || ! is_numeric( $_GET['id'] ) ) {
			wp_die( 'Invalid order ID' );
		}
		$order_id          = intval( $_GET['id'] );
		$rc_order          = wc_get_order( $order_id );
		$rc_order_response = $rc_order->get_meta( '_rapidcents_response' );
		?><div id="woocommerce-order-rapidcents-receipt" class="postbox ">
<div class="postbox-header"><h2 class="hndle ui-sortable-handle">Rapidcents Details</h2></div>
<div class="inside"><div class="order-attribution-metabox">
			<h4>Status</h4>
			<span><?php echo esc_html( $rc_order_response['status'] ); ?></span>
			<h4>Merchant Name</h4>
			<span><?php echo esc_html( $rc_order_response['details']['merchant_name'] ); ?></span>
			<h4>Merchant Address</h4>
			<span><?php echo esc_html( $rc_order_response['details']['merchant_address'] ); ?></span>
			<h4>Merchant Phone</h4>
			<span><?php echo esc_html( $rc_order_response['details']['merchant_number'] ); ?></span>
			<h4>Card</h4>
			<span><?php echo esc_html( $rc_order_response['details']['card'] ); ?></span>
			<h4>Association</h4>
			<span><?php echo esc_html( $rc_order_response['details']['association'] ); ?></span>
			<h4>customer Name</h4>
			<span><?php echo esc_html( $rc_order_response['details']['customer']['name'] ); ?></span>
			<h4>Customer Email</h4>
			<span><?php echo esc_html( $rc_order_response['details']['customer']['email'] ); ?></span>
			<h4>Issuer Reference</h4>
			<span><?php echo esc_html( $rc_order_response['details']['issuer_reference'] ); ?></span>
		</div>
	</div>
	</div>
		<?php
		exit( 0 );
	}

	/**
	 * Output additional content in the admin footer.
	 *
	 * @since    1.0.0
	 */
	public function admin_footer() {
		global $post;
		$screen   = get_current_screen();
		$logo_url = WC_RAPIDCENTS_GATEWAY_PLUGIN_URL . 'public/images/logo.png';

		if ( isset( $screen->post_type ) && 'shop_order' === $screen->post_type ) :
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $rc_order_id       = isset( $post->ID ) ? $post->ID : intval( isset( $_GET['id'] ) ? $_GET['id'] : '' );
			$rc_order_data_url = admin_url( 'admin-ajax.php' ) . '?action=rapidcents_receipt&nonce=' . wp_create_nonce( 'rapidcents_receipt_nonce' ) . '&id=' . $rc_order_id;
			?>
		<script language="javascript">
			jQuery(document).ready(function($) {
                $('button.do-manual-refund').on('click', function(e) {
                    return confirm('Manually refund will not display on rapidcents dashboard.');
                });
            });
			function rc_decodeHtmlEntities(str) {
				var textarea = document.createElement('textarea');
				textarea.innerHTML = str;
				return textarea.value;
			}
			jQuery(function($) {
				var rc_url = '<?php echo esc_js( $rc_order_data_url ); ?>';
				rc_url = rc_decodeHtmlEntities(rc_url);
				$.get(rc_url, function(data) {
					//$('#woocommerce-order-actions.postbox').after(data);
					$('#side-sortables').prepend(data);
				});
			});
			</script>
			<?php endif; ?>
			<script language="javascript">
			jQuery(function($) {
				$('.wc_gateways .wc-payment-gateway-method-name, .wc-order-totals span.description').each(function(index){
					jQuery(this).css('align-items','center');
					var $str = jQuery(this).html(); 
					$str = $str.replace(/\(RapidCents\)/i, '<img src="<?php echo esc_html( $logo_url ); ?>" style="max-height:14px">');
					$str = $str.replace(/\(RapidCents\)/g, '');
					jQuery(this).html($str);
				});
			});
		</script>
		<?php
	}

	/**
	 * Display admin messages.
	 *
	 * @since    1.0.0
	 */
	public function admin_messages() {
			$messages = rc_helper()->get_messages();
		foreach ( $messages as $message ) :
			?>
			<div class="notice notice-<?php echo esc_html( $message['type'] ); ?> is-dismissible notice-alt">
				<p><?php echo esc_html( $message['message'] ); ?></p>
			</div>
			<?php
			endforeach;
	}

	/**
	 * Get the redirect URL for RapidCents.
	 *
	 * @since    1.0.0
	 * @return   string The redirect URL.
	 */
	public function get_redirect_url() {
		return get_site_url() . '/wc-rapidcents';
	}

	/**
	 * Convert string values to boolean values.
	 *
	 * @since    1.0.0
	 * @param    array $i_array The array to be converted.
	 * @return   array The converted array.
	 */
	public function convert_strings_to_booleans( $i_array ) {
		array_walk_recursive(
			$i_array,
			function ( &$value ) {
				if ( 'true' === $value ) {
					$value = true;
				} elseif ( 'false' === $value ) {
					$value = false;
				}
			}
		);
		return $i_array;
	}

	/**
	 * Authorize the RapidCents transaction.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public function rapidcent_authorize() {
		$r           = array();
		$posted_data = array();
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'rca_auth_nonce' ) ) {
			// Nonce verification failed.
			$r['authorized'] = false;
			return wp_send_json( $r );
		} else {
			$posted_data = wp_unslash( $_POST );
		}
		$r['authorized'] = true;
		if ( isset( $_POST['data'] ) ) {
			$data = $posted_data['data'];

			$data['redirect_uri'] = rc_helper()->get_redirect_url();
			$data                 = $this->convert_strings_to_booleans( $data );
			$r_api                = new RapidCents_API( $data );
			$errors               = array();
			$auth_url             = $r_api->get_authorize_url( $errors );

			if ( count( $errors ) ) {
				$r['error_message'] = $errors[0];
				$r['authorized']    = false;
			} else {
				update_option( '_rc_credentials', $data );
				flush_rewrite_rules( true );
			}

			$r['auth_url'] = $auth_url;
		}
		$r['rca_auth_nonce'] = wp_create_nonce( 'rca_auth_nonce' );
		return wp_send_json( $r );
	}

	/**
	 * Get the HTML for the business input field.
	 *
	 * @since    1.0.0
	 * @param    string $type            The type of business input (live or test).
	 * @param    string $element_attrs   Additional attributes for the input element.
	 */
	public function get_business_input( $type = 'live', $element_attrs = '' ) {
		$_businesses = rc_helper()->get_businesses( $type );
		if ( is_array( $_businesses ) && count( $_businesses ) ) {
			?>
		<label>Choose Business: </label>
		<div class="rcfieldCtl">
			<select <?php echo esc_attr( $element_attrs ); ?> >
				<?php
				foreach ( $_businesses as $b ) :
					?>
					<option value="<?php echo esc_html( $b['id'] ); ?>"><?php echo esc_html( $b['legal_name'] ); ?></option><?php endforeach; ?>
			</select>
		</div>
			<?php
		} else {
			?>
		<label>Business ID: </label>
		<div class="rcfieldCtl"><input type="text" <?php echo esc_attr( $element_attrs ); ?> ></div>
																<?php
		}
	}

	/**
	 * Get the HTML for connecting to RapidCents.
	 *
	 * @since    1.0.0
	 * @return   string The HTML for connecting.
	 */
	public function get_connect_html() {
		ob_start();
		include plugin_dir_path( __FILE__ ) . 'partials/connect.php';
		return ob_get_clean();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wc-rapidcents-gateway-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string $hook The current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {

		if ( 'woocommerce_page_wc-settings' === $hook ) {
			// Get the current tab and section from the URL.
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_tab     = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';

			// Check if the tab is 'checkout' and the section is 'rapidcents'.
			if ( 'checkout' === $current_tab && 'rapidcents' === $current_section ) {
				// Enqueue your custom script.
				$rc_credentials = array();
				if ( is_admin() ) {
					$rc_credentials = get_option( '_rc_credentials', array() );
				}
				wp_enqueue_script( $this->plugin_name . '-angularjs', plugin_dir_url( __FILE__ ) . '../public/js/angular/angular.min.js', array( 'jquery' ), $this->version, false );
				wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wc-rapidcents-gateway-admin.js', array( 'jquery' ), $this->version, false );
				wp_localize_script(
					$this->plugin_name,
					'rapidcents',
					array(
						'ajaxurl'         => admin_url( 'admin-ajax.php' ),
						'connect_content' => $this->get_connect_html(),
						'rc_credentials'  => $rc_credentials,
						'rca_auth_nonce'  => wp_create_nonce( 'rca_auth_nonce' ),
					)
				);

			}
		}
	}
}
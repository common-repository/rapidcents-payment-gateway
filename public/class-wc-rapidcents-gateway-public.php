<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://rapidcents.com
 * @since      1.0.0
 *
 * @package    Wc_Rapidcents_Gateway
 * @subpackage Wc_Rapidcents_Gateway/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wc_Rapidcents_Gateway
 * @subpackage Wc_Rapidcents_Gateway/public
 * @author     Rapidcents <support@rapidcents.com>
 */
class Wc_Rapidcents_Gateway_Public {

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
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_wc_rapidcents_endpoint' ) );
		add_action( 'woocommerce_api_rapidcents', array( $this, 'handle_wc_rapidcents_endpoint' ) );
		add_filter( 'woocommerce_gateway_title', array( $this, 'gateway_title' ), 10, 2 );
		add_filter( 'wp_footer', array( $this, 'wp_footer' ), 10, 2 );
	}

	/**
	 * Filter the gateway title.
	 *
	 * This method can be used to modify the title of the payment gateway.
	 *
	 * @since    1.0.0
	 * @param    string $title        The current title of the gateway.
	 * @param    string $gateway_id   The ID of the payment gateway.
	 * @return   string The modified title of the gateway.
	 */
	public function gateway_title( $title, $gateway_id ) {
		return $title;
	}

	/**
	 * Register additional query variables.
	 *
	 * This method adds custom query variables to WordPress's list of recognized query variables.
	 *
	 * @since    1.0.0
	 * @param    array $vars An array of existing query variables.
	 * @return   array The modified array of query variables, including custom ones.
	 */
	public function query_vars( $vars ) {
		$vars[] = 'wc_rapidcents';
		$vars[] = 'wc_rapidcents_input';
		$vars[] = 'wc_rapidcents_ddd';
		$vars[] = 'code';
		$vars[] = 'dddtrans';
		return $vars;
	}

	/**
	 * Initialize the rewrite rules.
	 *
	 * This method adds custom rewrite rules to handle specific query variables and URLs.
	 *
	 * @since    1.0.0
	 */
	public function init() {

		add_rewrite_rule( '^wc-rapidcents/?$', 'index.php?wc_rapidcents=1', 'top' );
		add_rewrite_rule( '^wc-rapidcents-input/?$', 'index.php?wc_rapidcents_input=1', 'top' );
		add_rewrite_rule( '^wc-rapidcents-ddd-auth/?$', 'index.php?wc_rapidcents_ddd=1', 'top' );
	}

	/**
	 * Handle custom endpoint requests for RapidCents.
	 *
	 * This method processes various query variables related to RapidCents and performs
	 * actions such as displaying forms, handling authorization responses, and processing
	 * data from RapidCents.
	 *
	 * @since    1.0.0
	 */
	public function handle_wc_rapidcents_endpoint() {
		global $wp;
		if ( isset( $wp->query_vars['wc_rapidcents_ddd'] ) && ! empty( $wp->query_vars['wc_rapidcents_ddd'] ) ) {

			$code = urldecode( $wp->query_vars['code'] );
			$code = base64_decode( $code );
			$code = unserialize( $code );
			$data = $code['data'];
			if ( isset( $data['transStatus'] ) && isset( $data['token'] ) && isset( $data['customer_id'] ) && isset( $data['card_id'] ) && 'Y' === $data['transStatus'] ) {
				?><script>window.parent.postMessage({"src": "rc_verified"}, "*");</script>
				<?php
			} elseif ( 'DDD_FRICTIONLESS' === $code['type'] ) {
				?>
				<form name="frm" method="POST" action="<?php echo esc_html( $data['acsURL'] ); ?>">
					<input type="hidden" name="creq" value="<?php echo esc_html( $data['creq'] ); ?>">
					<!-- input type="submit" value="submit" -->
				</form>
				<script>window.onload = function() { document.forms["frm"].submit(); };</script>
				<?php } elseif ( 'DDD_INVOKE' === $code['type'] ) { ?>
					<form name="frm" method="POST" action="<?php echo esc_html( $data['acsURL'] ); ?>">
						<input type="hidden" name="creq" value="<?php echo esc_html( $data['creq'] ); ?>">
						<!-- input type="submit" value="submit" -->
					</form>
					<script>window.onload = function() { document.forms["frm"].submit(); };</script>
				<?php
				}
				exit();
		}
		if ( isset( $wp->query_vars['wc_rapidcents_input'] ) && ! empty( $wp->query_vars['wc_rapidcents_input'] ) ) {
			$plugin_asset_url = plugin_dir_url( __FILE__ );
			include plugin_dir_path( __FILE__ ) . 'partials/cc-input.php';
			exit( 0 );
		}
		if ( isset( $wp->query_vars['wc_rapidcents'] ) ) {
			if ( isset( $wp->query_vars['dddtrans'] ) && ! empty( $wp->query_vars['dddtrans'] ) ) {
				$dddtrans = $wp->query_vars['dddtrans'];
				$dddtrans = base64_decode( $dddtrans, true );
				$dddtrans = unserialize( $dddtrans );
				?>
				<form name="frm" method="POST" action="<?php echo esc_html( $dddtrans['threeDSMethodURL'] ); ?>">
				<input type="hidden" name="threeDSMethodData" value="<?php echo esc_html( $dddtrans['threeDSMethodData'] ); ?>"> 
				<input type="submit" value="submit">
			</form>
				<?php
				exit( 0 );
			}
			if ( isset( $wp->query_vars['code'] ) && ! empty( $wp->query_vars['code'] ) && is_user_logged_in() && current_user_can( 'manage_options' ) ) {

				$errors          = array();
					$credentials = get_option( '_rc_credentials', array() );

				$r_api = new RapidCents_API( $credentials );
				$token = $r_api->get_token_by_code( $wp->query_vars['code'], $errors );

				if ( count( $errors ) ) {
					foreach ( $errors as $err ) :
						rc_helper()->add_message( $err, 'error' );
						endforeach;
				} else {
					rc_helper()->update_auth_code( $wp->query_vars['code'] );
					rc_helper()->update_token( $token );
					rc_helper()->add_message( 'RapidCents Authorization Sucessfully.' );
				}

				if ( isset( $credentials['enable_test'] ) && ( 'true' === $credentials['enable_test'] || 1 === intval( $credentials['enable_test'] ) ) ) {
					rc_helper()->update_businesses( 'test' );
				} else {
					rc_helper()->update_businesses( 'live' );
				}

				wp_safe_redirect( rc_helper()->get_settings_url() );
				exit;
			} else {
				echo '-1';
				exit( 0 );
			}
		}
	}

	/**
	 * Generate the HTML for the verification dialog.
	 *
	 * This method returns the HTML markup for a verification dialog, which includes a title and an iframe
	 * for displaying verification content. The dialog is styled with specific classes for customization.
	 *
	 * @since    1.0.0
	 */
	public function get_verification_dialog() {
		?>
		<div class="rc_ddd_container"><div class="rc_ddd_dialog"><div class="rc_ddd_dialog_actions"><div class="rc_ddd_dialog_title">Verification</div><!-- div class="rc_ddd_dialog_close">Close</div --></div><iframe id="rc_verify_frm" src="" style="border:none;"></iframe></div></div>
		<?php
	}

	/**
	 * Output the verification dialog and JavaScript for the checkout page.
	 *
	 * This method is hooked to the `wp_footer` action and is responsible for adding custom HTML and
	 * JavaScript to the footer of the checkout page. It includes:
	 * - A verification dialog HTML.
	 * - JavaScript to handle dynamic updates to payment method labels, manage the verification dialog,
	 *   and process messages from the iframe used for verification.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function wp_footer() {
		if ( is_checkout() ) :
			$logo_url = plugin_dir_url( __FILE__ ) . 'images/logo.png';
			$this->get_verification_dialog();
			?>
		<script language="javascript">

			var rc_add_logo = function(){
				var $str = jQuery('label[for="payment_method_rapidcents"]').html(); 
				$str = $str.replace(/\(RapidCents\)/i, '<img src="<?php echo esc_html( $logo_url ); ?>" width="100">');
				$str = $str.replace(/\(RapidCents\)/g, '');
				jQuery('label[for="payment_method_rapidcents"]').html($str);

			};
			jQuery(function($) {
				$(document.body).on('updated_checkout', function(e,response) {
					rc_add_logo();
				});

				$('td,li').each(function(index){
					var $str = jQuery(this).html(); 
					$str = $str.replace(/\(RapidCents\)/i, '<img src="<?php echo esc_html( $logo_url ); ?>" width="100">');
					$str = $str.replace(/\(RapidCents\)/g, '');
					jQuery(this).html($str);
				});
			});
			window.addEventListener('message', function(event) {
			
			if(event.data.src === 'rc_verified') {
				jQuery('#i_rapidcents_card_ddd').val(event.data.src);
				rc_close_verification();
			}
			if(event.data.src === 'challenge_notify') {
				if(event?.data?.param?.transStatus === "Y") {
					var params = event?.data?.param;
					jQuery('#i_rapidcents_card_ddd').val(JSON.stringify(params));
					document.querySelector('#place_order').click();
					rc_close_verification(); // step 5
				} else if (event?.data?.param?.challengeCancel === "01") { // challenge cancelled
					//window.location.reload()
					rc_close_verification();
				} else { // challenge failed
					//disablePaymentLink();
					rc_close_verification();
				}
				rc_close_verification();
			}
		});
			var rc_ddd_verification_clear = function(){
				jQuery('#i_rapidcents_card_ddd').val('');
			};
			var rc_close_verification = function(){
				jQuery('.rc_ddd_container').removeClass('rc_dd_open');
			};
			var rc_open_verification = function(url){
				jQuery('#rc_verify_frm').attr('src',url);
				jQuery('.rc_ddd_container').addClass('rc_dd_open');
			};
			jQuery(function($) {
				$(document).on('click','.rc_ddd_dialog_close',function(){
						rc_close_verification();
				});
				$(document).ajaxComplete(function(event, xhr, settings) {
					// Check if this is the checkout AJAX request
					if (settings.url.indexOf('?wc-ajax=checkout') !== -1) {
						var response = xhr.responseJSON;
						//console.log('ajax Data:', response);
						// Check if custom data exists in the response
						if (response && response.ddd_verify) {
							rc_open_verification(response.ddd_verify);
							// Handle the custom data as needed
						} else {
							//console.log('No custom data found');
						}
					}
				});
				$(document.body).on('checkout_error', function(e,response) {
					rc_ddd_verification_clear();
				});
				
			});
			
		</script>
			<?php
		endif;
	}
	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wc-rapidcents-gateway-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		if ( is_checkout() || is_cart() ) {
			wp_enqueue_script( $this->plugin_name . '-block-checkout', plugin_dir_url( __FILE__ ) . 'js/block-checkout.js', array( 'wc-blocks-checkout' ), $this->version, false );
		}
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wc-rapidcents-gateway-public.js', array( 'jquery' ), $this->version, false );
	}
}

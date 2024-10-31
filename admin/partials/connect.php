<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://rapidcents.com
 * @since      1.0.0
 *
 * @package    Wc_Rapidcents_Gateway
 * @subpackage Wc_Rapidcents_Gateway/admin/partials
 */

?>
<div class="rcConnectApp" ng-app="rcConnectApp">
	<div class="rcConnectAppCtl" ng-init="init()" ng-controller="rcConnectAppCtl">
		<div class="rcConnectSection">

			<div class="rcfieldContainer rcfieldInline">

				<div class="rcfieldCtl"><input id="rcfldEnableTest" type="checkbox" class="regular-text" ng-model="data.enable_test" autocomplete="off">
				</div>
				<label for="rcfldEnableTest"><strong>Enable Test Mode</strong></label>
			</div>
			<div ng-show="data.enable_test">
			<div class="rcfieldContainer">
				<label>Test Client ID: </label>
				<div class="rcfieldCtl"><input type="text" class="regular-text rcFullText" ng-model="data.test.client_id" ng-disabled="authorizing" autocomplete="off">
				</div>
			</div>

			<div class="rcfieldContainer">
				<label>Test Client Secret: </label>
				<div class="rcfieldCtl"><input type="text" class="regular-text rcFullText" ng-model="data.test.client_secret" ng-disabled="authorizing" autocomplete="off" style="-webkit-text-security: disc;">
				</div>
			</div>
				
			<div class="rcfieldContainer">
				<?php $this->get_business_input( 'test', 'class="regular-text rcFullText" ng-model="data.test.business_id" ng-disabled="authorizing" autocomplete="off"' ); ?>
			</div>
			</div>
			<div ng-hide="data.enable_test">
			<div class="rcfieldContainer">
				<label>Live Client ID: </label>
				<div class="rcfieldCtl"><input type="text" class="regular-text rcFullText" ng-model="data.live.client_id" ng-disabled="authorizing" autocomplete="off">
				</div>
			</div>

			<div class="rcfieldContainer">
				<label>Live Client Secret: </label>
				<div class="rcfieldCtl"><input type="text" class="regular-text rcFullText" ng-model="data.live.client_secret" ng-disabled="authorizing" autocomplete="off" style="-webkit-text-security: disc;">
				</div>
			</div>
				
			<div class="rcfieldContainer">
				<?php $this->get_business_input( 'live', 'class="regular-text rcFullText" ng-model="data.live.business_id" ng-disabled="authorizing" autocomplete="off"' ); ?>
			</div>
			</div>
			<div class="rcfieldContainer">
				<label>Redirect URL: </label>
				<div class="rcfieldCtl"><code><?php echo esc_html( rc_helper()->get_redirect_url() ); ?></code>
				</div>
			</div>
			<div class="rcfieldContainer">
				<label>Server IP Address: </label>
				<div class="rcfieldCtl"><code><?php echo esc_html( rc_helper()->get_server_ip() ); ?></code>
				</div>
			</div>
			<div class="rcConnectActions"><div><button type="button" class="button button-primary" ng-click="authorize()" ng-disabled="authorizing"><?php echo ( rc_helper()->get_token() ) ? 'Re-Authorize' : 'Authorize'; ?></button> <a href="https://help.rapidcents.com/" target="_blank" class="button button-secondary" >Help?</a></div> <img ng-show="authorizing" src="<?php echo esc_url( get_admin_url() . 'images/wpspin_light-2x.gif' ); ?>" /> <div ng-show="error_message" class="rc-notice-error"><p>{{error_message}}</p></div></div>
		</div>
	</div>

</div>

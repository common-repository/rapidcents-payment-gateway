<!DOCTYPE html>
<html>
<head>
	<title>CC Input</title>
    <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
	<script src="<?php echo esc_url( includes_url( 'js/jquery/jquery.js' ) ); ?>"></script>
    <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
	<script src="<?php echo esc_url( $plugin_asset_url ); ?>js/angular/angular.min.js"></script>
    <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
	<link href="<?php echo esc_url( $plugin_asset_url ); ?>css/card-js.min.css" rel="stylesheet" type="text/css" />
    <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
	<script src="<?php echo esc_url( $plugin_asset_url ); ?>js/card-js.js"></script>
    <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
	<script src="<?php echo esc_url( $plugin_asset_url ); ?>js/card-js.jquery.js"></script>
<style>
	body{
		margin:0;
		padding:0;
		background-color:transparent;
	}
	fieldset#wc-rapidcents-cc-form{
		border: 0;
		padding: 0;
		margin: 2px;
	}
	.card-js input, .card-js select{ font-size: 18px; }
	</style>
</head>
<body>

<div id="rcCCInputAppContainer" class="rcCCInputApp" ng-app="rcCCInputApp">
		<div class="rcCCInputAppCtl" ng-init="init()" ng-controller="rcCCInputAppCtl">
			<fieldset id="wc-rapidcents-cc-form" class="card-js wc-credit-card-form wc-payment-form">
				<div class="form-row form-row-wide">
					<label for="rapidcents-card-number"><?php esc_html_e( 'Card Number', 'woocommerce' ); ?> <span class="required">*</span></label>
					<input class="card-number" id="rapidcents-card-number" name="rapidcents_card_number" type="text" placeholder="1234 1234 1234 1234" autocomplete="off" ng-model="card.number" >
				</div>
				<div class="form-row form-row-first">
					<label for="rapidcents-card-expiry"><?php esc_html_e( 'Expiration Date (MM/YY)', 'woocommerce' ); ?> <span class="required">*</span></label>
					<input id="rapidcents-card-expiry" name="rapidcents_card_expiry" type="text" autocomplete="off" placeholder="MM / YY">
					<input type="hidden" name="rapidcents_card_expiry_month" class="expiry-month">
					<input type="hidden" name="rapidcents_card_expiry_year" class="expiry-year">
				</div>
				<div class="form-row form-row-last">
					<label for="rapidcents-card-cvc"><?php esc_html_e( 'Card Code (CVC)', 'woocommerce' ); ?> <span class="required">*</span></label>
					<input class="cvc" id="rapidcents-card-cvc" name="rapidcents_card_cvc" type="text" autocomplete="off" placeholder="CVC">
				</div>
				<div class="clear"></div>
			</fieldset>
		</div>
	</div>
		<script language="javascript">
		jQuery(document).ready(function($){		
			window._rc_card_input = $('#wc-rapidcents-cc-form').CardJs();
		});
		var rcCCInputApp = angular.module( "rcCCInputApp", [] );
	
		rcCCInputApp.controller( "rcCCInputAppCtl", function ( $scope, $http, $window, $location, $timeout, $interval) {
			$scope.card = {};
			$scope.init = function(){

				var count = 0;
				var intervalPromise = $interval(function() {
					//console.log("This message has been updated " + count + " times.");
					//console.log(window._rc_card_input.CardJs('cardNumber'));
					var $obj = {};
					$obj.cardNumber = window._rc_card_input.CardJs('cardNumber');
					$obj.expiryMonth = window._rc_card_input.CardJs('expiryMonth');
					$obj.expiryYear = window._rc_card_input.CardJs('expiryYear');
					$obj.cvc = window._rc_card_input.CardJs('cvc');
					$window.parent._rc_card_fill($obj);
					var formHeight = jQuery('#rcCCInputAppContainer').height();
					$window.parent._rc_card_resize(formHeight);
					count++;
				}, 1000); // 1000 milliseconds = 1 second

			};
		});


		
		</script>

</body>
</html>

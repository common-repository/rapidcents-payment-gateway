(function ( $ ) {
	'use strict';
	$(
		function () {
			jQuery( '.forminp #woocommerce_rapidcents_rc_connect_data' ).after( '<div id="rcConnectContainer" />' );
			jQuery( '#rcConnectContainer' ).html( rapidcents.connect_content );
			window._rapidcents_connect_init();
		}
	);

})( jQuery );

window._rapidcents_connect_init = function () {

	var rcConnectApp = angular.module( "rcConnectApp", [] );

	rcConnectApp.controller(
		"rcConnectAppCtl",
		function ( $scope, $http, $window, $location, $timeout) {
			$http.defaults.headers.post[ 'Content-Type' ] = 'application/x-www-form-urlencoded; charset=UTF-8';
			$scope.credit                                 = 'yelocommerce.com';
			$scope.data                                   = {enable_test:false,live:{client_id:'',client_secret:''},test:{client_id:'',client_secret:''}};
			$scope.authorizing                            = false;
			$scope.error_message                          = false;
			$scope.rca_auth_nonce                         = false;
			$scope.authorize                              = function () {
				$scope.authorizing = true;
				var data           = {
					action: 'admin_rapidcent_authorize',
					data: $scope.data,
					nonce: $scope.rca_auth_nonce,
					t: new Date().getTime()
				};
				$scope.ycHttp(
					data,
					function ( responseData ) {
						// $scope.product = responseData.product;
						// console.log(responseData);
						if (responseData.rca_auth_nonce) {
							$scope.rca_auth_nonce = responseData.rca_auth_nonce;
						}
						if (responseData.error_message) {
							$scope.error_message = responseData.error_message;
						}
						if (responseData.authorized == false) {

							$scope.authorizing = false;
						}
						if (responseData.authorized == true && responseData.auth_url) {
							$window.location.href = responseData.auth_url;
						}

					}
				);
			};
			$scope.init                                   = function () {
				var savedValues = jQuery( '.forminp #woocommerce_rapidcents_rc_connect_data' ).val();
				if (savedValues) {
					savedValues = JSON.parse( savedValues );
					$scope.data = savedValues;
				}
				if (typeof rapidcents.rc_credentials === 'object' && rapidcents.rc_credentials !== null) {
					$scope.data = rapidcents.rc_credentials;
				}
				$scope.rca_auth_nonce = rapidcents.rca_auth_nonce;
			};
			$scope.$watch(
				'data',
				function (newValue, oldValue, scope) {

					jQuery( '.forminp #woocommerce_rapidcents_rc_connect_data' ).val( JSON.stringify( $scope.data ) );

				},
				true
			);

			$scope.param = function (obj) {

				if ( ! angular.isObject( obj ) ) {
					return( ( obj == null ) ? "" : obj.toString() );
				}
				var query = '', name, value, fullSubName, subName, subValue, innerObj, i;

				for (name in obj) {

					value = obj[name];
					if (value instanceof Array) {
						for (i in value) {

							subValue              = value[i];
							fullSubName           = name + '[' + i + ']';
							innerObj              = {};
							innerObj[fullSubName] = subValue;
							query                += $scope.param( innerObj ) + '&';
						}

					} else if (value instanceof Object) {
						for (subName in value) {

							subValue              = value[subName];
							fullSubName           = name + '[' + subName + ']';
							innerObj              = {};
							innerObj[fullSubName] = subValue;
							query                += $scope.param( innerObj ) + '&';
						}
					} else if (value !== undefined && value !== null) {
						query += encodeURIComponent( name ) + '=' + encodeURIComponent( value ) + '&';
					}
				}

				return query.length ? query.substr( 0, query.length - 1 ) : query;
			};

			$scope.ycHttp = function ( data, callBackFunc ) {
				$scope.error_message = false;
				$http.post( rapidcents.ajaxurl, $scope.param( data ) ).then(
					function ( response ) {
						if ( response.data ) {
								callBackFunc( response.data );

						}

					},
					function ( response ) {

						$scope.message = "Service not Exists";
						$scope.loading = false;

					}
				);

			};

		}
	);

	angular.element( document ).ready(
		function () {
			angular.bootstrap( document, ['rcConnectApp'] );
		}
	);

};
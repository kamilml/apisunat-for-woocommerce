(function ($) {
	'use strict';

	jQuery( document ).ready(
		function ($) {
			const apisunat_modal = document.getElementById( 'apisunatModal' );

			const button_save = document.getElementsByName( "save" )

			$( document ).ready(
				function () {
					$( "#post" ).submit(
						function () {
							button_save[0].setAttribute( 'disabled', 'disabled' );
							return true;
						}
					);
				}
			);

			$( document ).on(
				"click",
				"#apisunatModalClose",
				function (e) {
					apisunat_modal.style.display = "none";
				}
			);

			$( document ).on(
				"click",
				".emit_button",
				function (e) {
					e.stopImmediatePropagation();
					e.preventDefault();

					let orderId     = e.target.id;
					let orderStatus = $( this ).attr( "apistatus" );

					if (orderStatus !== 'completed') {
						alert( "La orden debe completarse para poder enviar los datos" );

					} else {

						$( this ).hide();
						$( '#apisunatLoading' + orderId ).show();
						let data = {
							action: 'send_apisunat_order',
							order_value: orderId
						};

						jQuery.post(
							apisunat_ajax_object.ajax_url,
							data,
							async function (response) {
								window.location = document.location.href;
								// $('#apisunatLoading').hide();
							}
						);
					}
				}
			);

			// show/hide for advanced options.
			if ($( "#apisunat_custom_checkout" ).val() === "false") {
				$( '.regular-text.regular-text-advanced' ).hide();
				$( '.regular-text.regular-text-advanced' ).attr( 'required', null )
			}

			$( document ).on(
				"change",
				"#apisunat_custom_checkout",
				function(e) {
					const value = e.target.value;

					if (value === "true") {
						$( '.regular-text.regular-text-advanced' ).show();
						$( '.regular-text.regular-text-advanced' ).attr( 'required', true )
					}
					if (value === "false") {
						$( '.regular-text.regular-text-advanced' ).hide();
						$( '.regular-text.regular-text-advanced' ).attr( 'required', null )
					}
				}
			);
		}
	);

})( jQuery );

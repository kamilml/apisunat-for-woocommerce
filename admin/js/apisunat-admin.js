(function ($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

    jQuery(document).ready(function ($) {
        const apisunat_modal = document.getElementById('apisunatModal');
        const apisunat_span = document.getElementById('apisunatModalClose');
        const apisunat_reason = document.getElementById('apisunat_reason');
        // const apisunat_span = document.getElementsByClassName("apisunatmodal-close")[0];

        const button_save = document.getElementsByName("save")

        $(document).ready(function () {
            $("#post").submit(function () {
                button_save[0].setAttribute('disabled', 'disabled');
                return true;
            });
        });

        $(document).on("click", "#apisunatModalClose", function (e) {
            apisunat_modal.style.display = "none";
        });

        //TODO: concretar el modal
        // window.onclick = function (event) {
        //     if (event.target === apisunat_modal) {
        //         apisunat_modal.style.display = "none";
        //     }
        // }

        //TODO: anular documento
        // $(document).on("click", "#apisunatButton", function (e) {
        //     e.stopImmediatePropagation();
        //     e.preventDefault();
        //     apisunat_modal.style.display = "block";
        // });
        //
        // $(document).on("click", "#apisunat_show_anular", function (e) {
        //     if (apisunat_reason.style.display === "none") {
        //         apisunat_reason.style.display = "block";
        //     } else {
        //         apisunat_reason.style.display = "none";
        //     }
        // });
        //
        // $(document).on("click", "#apisunatAnularData", function (e) {
        //     e.stopImmediatePropagation();
        //     e.preventDefault();
        //
        //     let orderId = $('#orderId').val();
        //
        //     $('#apisunatAnularData').hide();
        //     $('#apisunatLoading2').show();
        //
        //     let data = {
        //         action: 'void_apisunat_order',
        //         order_value: orderId,
        //         reason: $("#apisunat_nular_reason").val(),
        //     };
        //
        //     jQuery.post(apisunat_ajax_object.ajax_url, data, async function (response) {
        //         window.location=document.location.href;
        //         $('#apisunatAnularData').show();
        //         $('#apisunatLoading').hide();
        //     });
        //
        // });

        $(document).on("click", "#apisunatSendData", function (e) {
            e.stopImmediatePropagation();
            e.preventDefault();

            let orderId = $('#orderId').val();
            let orderStatus = $('#orderStatus').val();

            if (orderStatus !== 'completed') {
                alert("La orden debe completarse para poder enviar los datos");

            } else {

                $('#apisunatSendData').hide();
                $('#apisunatLoading').show();
                let data = {
                    action: 'send_apisunat_order',
                    order_value: orderId
                };

                jQuery.post(apisunat_ajax_object.ajax_url, data, async function (response) {
                    window.location=document.location.href;
                    // $('#apisunatSendData').show();
                    $('#apisunatLoading').hide();
                });
            }
        });
    });

})(jQuery);

(function ($) {
    'use strict';

    jQuery(document).ready(function ($) {
        const apisunat_modal = document.getElementById('apisunatModal');
        const apisunat_span = document.getElementById('apisunatModalClose');
        const apisunat_reason = document.getElementById('apisunat_reason');

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

        //TODO: anular documento

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
                    window.location = document.location.href;
                    // $('#apisunatSendData').show();
                    $('#apisunatLoading').hide();
                });
            }
        });
    });

})(jQuery);

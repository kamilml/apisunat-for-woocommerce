(function ($) {
    "use strict";

    jQuery(function ($) {

        $("#billing_apisunat_document_type").on("change", function () {
            let selected_option_type_value = $(this).find(":selected").val();

            // document.querySelectorAll("#billing_document_id_type option").forEach(opt => {
            //     if (selected_option_type_value === '01') {
            //         opt.disabled = opt.value !== "6";
            //     }
            //     if (selected_option_type_value !== '03') {
            //         opt.disabled = opt.value === "6";
            //     }
            //
            // });
        })
    });

    // jQuery(function ($) {
    //   function showHide(selector = "", action = "show") {
    //     if (action == "show")
    //       $(selector).show(200, function () {
    //         $(this).addClass("validate-required");
    //       });
    //     else
    //       $(selector).hide(200, function () {
    //         $(this).removeClass("validate-required");
    //       });
    //     $(selector).removeClass("woocommerce-validated");
    //     $(selector).removeClass(
    //       "woocommerce-invalid woocommerce-invalid-required-field"
    //     );
    //   }

    // $("#documentType").on("change", function () {
    //   var selected_option_type_value = $(this).find(":selected").val();
    //   console.log(selected_option_type_value);

    //   if (selected_option_type_value == "factura") {
    //     showHide("#billing_document_field", "hide");
    //     showHide("#billing_ruc_field");
    //     $("#billing_document").val("1");
    //   } else {
    //     showHide("#billing_document_field");
    //     showHide("#billing_dni_field");
    //     showHide("#billing_ruc_field", "hide");
    //   }

    //   var data = {
    //     action: "change_fields",
    //     tipo: selected_option_type_value,
    //   };
    //   jQuery.post(admin_ajax_object.ajaxurl, data, function () {});
    // });

    // $("#billing_document").on("change", function () {
    //   var selected_option_value = $(this).find(":selected").val();
    //   console.log(selected_option_value);

    //   if (selected_option_value == "1") {
    //     showHide("#billing_carnet_field", "hide");
    //     showHide("#billing_ps_field", "hide");
    //     showHide("#billing_dni_field");
    //   }
    //   if (selected_option_value == "4") {
    //     showHide("#billing_carnet_field");
    //     showHide("#billing_ps_field", "hide");
    //     showHide("#billing_dni_field", "hide");
    //   }
    //   if (selected_option_value == "7") {
    //     showHide("#billing_carnet_field", "hide");
    //     showHide("#billing_ps_field");
    //     showHide("#billing_dni_field", "hide");
    //   }
    // });
    // });
})(jQuery);

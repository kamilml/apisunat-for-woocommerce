<?php

class Apisunat_Public
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private string $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private string $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of the plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct(string $plugin_name, string $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        if (get_option('apisunat_custom_checkout') == "false") {

            add_filter('woocommerce_billing_fields', array($this, 'custom_woocommerce_billing_fields'));
            add_action('woocommerce_after_checkout_validation', array($this, 'apisunat_custom_fields_validate'), 10, 2);
        }

    }


    /**
     * Validate checkout custom fields
     *
     * @param $data
     * @param $errors
     * @return void
     * @since 1.0.0
     */
    function apisunat_custom_fields_validate($data, $errors): void
    {

        if (isset($_POST['billing_apisunat_customer_id_type'])) {

            $pattern = "/^[a-zA-Z\d]{1,15}$/";

            if ($_POST['billing_apisunat_customer_id_type'] == '6') {
                $pattern = "/[12][0567]\d{9}$/";
            }
            if ($_POST['billing_apisunat_customer_id_type'] == '1') {
                $pattern = "/^\d{8}$/";
            }

            if (!preg_match($pattern, $_POST['billing_apisunat_customer_id'])) {
                $errors->add('validation', "<strong>Numero de Documento: </strong> Formato incorrecto.");
            }
        }

        if (isset($_POST['billing_apisunat_document_type'])) {
            if ($_POST['billing_apisunat_document_type'] == '01') {
                if (!$_POST['billing_company']) {
                    $errors->add('validation', "<strong>Nombre de Empresa: </strong> requerido para realizar factura.");
                }
                if ($_POST['billing_apisunat_customer_id_type'] != '6') {
                    $errors->add('validation', "<strong>Tipo de Identificacion: </strong> no admitido para realizar factura.");
                }
            }
            if ($_POST['billing_apisunat_document_type'] == '03') {
                if ((strlen($_POST['billing_first_name']) + strlen($_POST['billing_last_name'])) < 3) {

                    $errors->add('validation', "<strong>Nombre o Apellidos: </strong> Deben contener al menos 3 caracteres para boletas.");
                }
                // if ($_POST['billing_apisunat_customer_id_type'] == '6') {
                //     $errors->add('validation', "<strong>Tipo de Identificacion: </strong> no admitido para realizar Boletas.");
                // }

            }
        }

    }

    /**
     * Declare checkout custom fields
     *
     * @param $fields
     * @return array
     * @since 1.0.0
     */
    function custom_woocommerce_billing_fields($fields): array
    {
        $fields['billing_first_name']['required'] = false;
        $fields['billing_last_name']['required'] = false;

        $fields['billing_apisunat_document_type'] = array(
            'label' => 'TIPO DE DOCUMENTO', // Add custom field label
            'required' => true, // if field is required or not
            'clear' => true, // add clear or not
            'type' => 'select', // add field type
            'class' => array('form-row-wide'), // add class name
            'options' => array(
                '01' => 'FACTURA',
                '03' => 'BOLETA DE VENTA'
            ),
            'priority' => 21,
        );

        $fields['billing_apisunat_customer_id_type'] = array(
            'label' => 'TIPO DE IDENTIFICACIÓN', // Add custom field label
            'required' => true, // if field is required or not
            'clear' => true, // add clear or not
            'type' => 'select', // add field type
            'class' => array('form-row-wide'), // add class name
            'options' => array(
                '6' => 'RUC',
                '1' => 'DNI',
                '7' => 'PASAPORTE',
                'B' => 'OTROS (Doc. Extranjero)',
            ),
            'priority' => 22,
        );

        $fields['billing_apisunat_customer_id'] = array(
            'label' => 'Número del Documento',
            'required' => true,
            'class' => array('form-row-wide'),
            'priority' => 23,
            'placeholder' => 'Número de documento',
        );

        return $fields;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles(): void
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/apisunat-public.css', array(), $this->version);
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts(): void
    {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/apisunat-public.js', array('jquery'), $this->version);
        wp_localize_script($this->plugin_name, 'admin_ajax_object', array('ajaxurl' => admin_url('admin-ajax.php')));
    }
}

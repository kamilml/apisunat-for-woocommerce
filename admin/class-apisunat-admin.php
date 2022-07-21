<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://apisunat.com/
 * @since      1.0.0
 *
 * @package    Apisunat
 * @subpackage Apisunat/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Apisunat
 * @subpackage Apisunat/admin
 * @author     Heikel Villar <heikelvillar@gmail.com>
 */
class Apisunat_Admin {

	const API_WC_URL = 'https://ecommerces-api.apisunat.com/v1.1/woocommerce';
	const API_URL    = 'https://back.apisunat.com';

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
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 * @since    1.0.0
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_action( 'admin_menu', array( $this, 'add_apisunat_admin_menu' ), 10 );
		add_action( 'admin_init', array( $this, 'register_and_build_fields' ) );
		add_action( 'add_meta_boxes', array( $this, 'apisunat_meta_boxes' ) );
		add_action( 'admin_init', array( $this, 'apisunat_forma_envio_facturas' ) );
		add_action( 'wp_ajax_void_apisunat_order', array( $this, 'void_apisunat_order' ), 11, 1 );
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'apisunat_custom_order_column' ), 11 );
		add_action(
			'manage_shop_order_posts_custom_column',
			array(
				$this,
				'apisunat_custom_orders_list_column_content',
			),
			10,
			2
		);
	}

	/**
	 * Add APISUNAT status Column
	 *
	 * @param array $columns Default columns.
	 * @return array
	 * @since    1.0.0
	 */
	public function apisunat_custom_order_column( $columns ): array {
		$reordered_columns = array();

		foreach ( $columns as $key => $column ) {
			$reordered_columns[ $key ] = $column;
			if ( 'order_status' === $key ) {
				$reordered_columns['apisunat_document_files'] = 'Comprobante';
			}
		}
		return $reordered_columns;
	}

	/**
	 * Print APISUNAT bill status in order list column
	 *
	 * @param string $column Table column.
	 * @param string $post_id Order id.
	 * @return void
	 * @since    1.0.0
	 */
	public function apisunat_custom_orders_list_column_content( string $column, string $post_id ): void {
		if ( 'apisunat_document_files' === $column ) {
			$status  = get_post_meta( $post_id, 'apisunat_document_status', true );
			$doc_id  = get_post_meta( $post_id, 'apisunat_document_id', true );
			$estados = array( 'ERROR', 'RECHAZADO', 'EXCEPCION' );

			if ( empty( $status ) ) {
				echo '<small>(<em>no enviado</em>)</small>';
			}

			if ( in_array( $status, $estados, true ) ) {
				echo esc_attr( $status );
			} else {
				$request = wp_remote_get( self::API_URL . '/documents/' . $doc_id . '/getById' );
				$data    = json_decode( wp_remote_retrieve_body( $request ), true );
				$xml     = $data['xml'];

				if ( isset( $xml ) && ! empty( $xml ) ) {
					printf(
						"<a href=https://back.apisunat.com/documents/%s/getPDF/A4/%s.pdf target='_blank' class='button'>PDF</a>",
						esc_attr( get_post_meta( $post_id, 'apisunat_document_id', true ) ),
						esc_attr( get_post_meta( $post_id, 'apisunat_document_filename', true ) )
					);
					printf(
						" <a href=%s target=_blank' class='button'>XML</a>",
						esc_attr( $xml )
					);
				}
			}
		}
	}

	/**
	 * Change APISUNAT status cron schedule function
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function apisunat_check_status_on_schedule(): void {

		$orders = wc_get_orders(
			array(
				'limit'        => -1, // Query all orders.
				'meta_key'     => 'apisunat_document_status', // The postmeta key field.
				'meta_value'   => 'PENDIENTE', // The postmeta key field.
				'meta_compare' => '=', // The comparison argument.
			)
		);

		foreach ( $orders as $order ) {
			if ( $order->meta_exists( 'apisunat_document_id' ) && $order->get_meta( 'apisunat_document_status' ) === 'PENDIENTE' ) {
					$request = wp_remote_get( self::API_URL . '/documents/' . $order->get_meta( 'apisunat_document_id' ) . '/getById' );
					$data    = json_decode( wp_remote_retrieve_body( $request ), true );
					$status  = $data['status'];

					$order->add_order_note( ' El documento se encuentra en estado: ' . $status );
					update_post_meta( $order->get_id(), 'apisunat_document_status', $status );
			}
		}
	}

	/**
	 * Send bill event trigger depend on manual or automatic config
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function apisunat_forma_envio_facturas(): void {
		if ( get_option( 'apisunat_forma_envio' ) === 'auto' ) {
			add_action( 'woocommerce_order_status_completed', array( $this, 'send_apisunat_order' ), 10, 1 );
		}
		add_action( 'wp_ajax_send_apisunat_order', array( $this, 'send_apisunat_order' ), 10, 1 );
	}

	/**
	 * Prepare payload and send info to APISUNAT API
	 *
	 * @param $order_id
	 * @return void
	 * @since    1.0.0
	 */
	public function send_apisunat_order( $order_id ): void {

		$order_idd = isset( $_POST['order_value'] ) ? intval( $_POST['order_value'] ) : $order_id;

		/**
		 * Obtener datos de la orden y el tipo de documento
		 */
		$order = wc_get_order( $order_idd );

		if ( $order->meta_exists( 'apisunat_document_status' ) ) {
			if ( $order->get_meta( 'apisunat_document_status' ) === 'PENDIENTE' || $order->get_meta( 'apisunat_document_status' ) === 'ACEPTADO' ) {
				return;
			}
		}

		$send_data                                = array();
		$send_data['plugin_data']['personaId']    = get_option( 'apisunat_personal_id' );
		$send_data['plugin_data']['personaToken'] = get_option( 'apisunat_personal_token' );

		$send_data['plugin_data']['serie01']       = get_option( 'apisunat_serie_factura' );
		$send_data['plugin_data']['serie03']       = get_option( 'apisunat_serie_boleta' );
		$send_data['plugin_data']['affectation']   = get_option( 'apisunat_tipo_tributo' );
		$send_data['plugin_data']['issueTime']     = get_option( 'apisunat_include_time' );
		$send_data['plugin_data']['shipping_cost'] = get_option( 'apisunat_shipping_cost' );

		$send_data['plugin_data']['debug']            = get_option( 'apisunat_debug_mode' );
		$send_data['plugin_data']['custom_meta_data'] = get_option( 'apisunat_custom_checkout' );

		$_document_type          = get_option( 'apisunat_key_tipo_comprobante' ) ? get_option( 'apisunat_key_tipo_comprobante' ) : '_billing_apisunat_document_type';
		$_document_type_value_01 = get_option( 'apisunat_key_value_factura' ) ? get_option( 'apisunat_key_value_factura' ) : '01';
		$_document_type_value_03 = get_option( 'apisunat_key_value_boleta' ) ? get_option( 'apisunat_key_value_boleta' ) : '03';

		$send_data['plugin_data']['meta_data_mapping']['_billing_apisunat_document_type'] = array(
			'key'      => $_document_type,
			'value_01' => $_document_type_value_01,
			'value_03' => $_document_type_value_03,
		);

		$_customer_id_type         = get_option( 'apisunat_key_tipo_documento' ) ? get_option( 'apisunat_key_tipo_documento' ) : '_billing_apisunat_customer_id_type';
		$_customer_id_type_value_1 = get_option( 'apisunat_key_value_dni' ) ? get_option( 'apisunat_key_value_dni' ) : '1';
		$_customer_id_type_value_6 = get_option( 'apisunat_key_value_ruc' ) ? get_option( 'apisunat_key_value_ruc' ) : '6';
		$_customer_id_type_value_7 = get_option( 'apisunat_key_value_pasaporte' ) ? get_option( 'apisunat_key_value_pasaporte' ) : '7';
		$_customer_id_type_value_b = get_option( 'apisunat_key_value_otros_extranjero' ) ? get_option( 'apisunat_key_value_otros_extranjero' ) : 'B';

		$send_data['plugin_data']['meta_data_mapping']['_billing_apisunat_customer_id_type'] = array(
			'key'     => $_customer_id_type,
			'value_1' => $_customer_id_type_value_1,
			'value_6' => $_customer_id_type_value_6,
			'value_7' => $_customer_id_type_value_7,
			'value_B' => $_customer_id_type_value_b,
		);

		$_apisunat_customer_id = get_option( 'apisunat_key_numero_documento' ) ? get_option( 'apisunat_key_numero_documento' ) : '_billing_apisunat_customer_id';

		$send_data['plugin_data']['meta_data_mapping']['_billing_apisunat_customer_id'] = array(
			'key' => $_apisunat_customer_id,
		);

		$send_data['order_data'] = $order->get_data();

		foreach ( $order->get_items() as $item ) {
			$item_data                 = array(
				'item'    => $item->get_data(),
				'product' => $item->get_product()->get_data(),
			);
			$send_data['items_data'][] = $item_data;
		}

		$args = array(
			'method'  => 'POST',
			'timeout' => 45,
			'body'    => wp_json_encode( $send_data ),
			'headers' => array(
				'content-type' => 'application/json',
			),
		);

		$response = wp_remote_post( self::API_WC_URL, $args );

		// si es un error de WP!
		if ( is_wp_error( $response ) ) {
			$error_response = $response->get_error_message();
			$msg            = $error_response;
		} else {
			$apisunat_response = json_decode( $response['body'], true );
			update_post_meta( $order_idd, 'apisunat_document_status', $apisunat_response['status'] );

			if ( 'ERROR' === $apisunat_response['status'] ) {
				$msg = $apisunat_response['error']['message'];
			} else {
				update_post_meta( $order_idd, 'apisunat_document_id', $apisunat_response['documentId'] );
				update_post_meta( $order_idd, 'apisunat_document_filename', $apisunat_response['fileName'] );

				$msg = 'Los datos se han enviado a APISUNAT';
			}
		}
		$order->add_order_note( $msg );
	}

	/**
	 * Void APISUNAT bill
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function void_apisunat_order(): void {
		if ( isset( $_POST['order_value'] ) ) {
			$order_id = intval( $_POST['order_value'] );

			$order = wc_get_order( $order_id );

			$body = array(
				'personaId'    => get_option( 'apisunat_personal_id' ),
				'personaToken' => get_option( 'apisunat_personal_token' ),
				'documentId'   => $order->get_meta( 'apisunat_document_id' ),
				'reason'       => isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '',
			);

			$args = array(
				'method'  => 'POST',
				'timeout' => 45,
				'body'    => wp_json_encode( $body ),
				'headers' => array(
					'content-type' => 'application/json',
				),
			);

			$response = wp_remote_post( self::API_URL . '/personas/v1/voidBill', $args );

			if ( is_wp_error( $response ) ) {
				$error_response = $response->get_error_message();
				$msg            = $error_response;
			} else {
				$apisunat_response = json_decode( $response['body'], true );

				$msg = $apisunat_response;
			}
			$order->add_order_note( $msg );
		}
	}

	/**
	 * Add APISUNAT meta box
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function apisunat_meta_boxes(): void {
		add_meta_box(
			'woocommerce-order-apisunat',
			__( 'APISUNAT' ),
			array( $this, 'order_meta_box_apisunat' ),
			'shop_order',
			'side'
		);
	}

	/**
	 * Add APISUNAT meta box data
	 *
	 * @param $order_id
	 * @return void
	 * @since    1.0.0
	 */
	public function order_meta_box_apisunat( $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( $order->meta_exists( 'apisunat_document_status' ) ) {
			{
				$option_name = get_option( 'apisunat_key_tipo_comprobante' );

				$tipo = '';

			switch ( $order->get_meta( $option_name ) ) {
				case '01':
					$tipo = 'Factura';
					break;
				case '03':
					$tipo = 'Boleta';
					break;
			}

				$number = explode( '-', $order->get_meta( 'apisunat_document_filename' ) );

				printf( '<p>Status: <strong> %s</strong></p>', esc_attr( $order->get_meta( 'apisunat_document_status' ) ) );

			if ( $order->meta_exists( 'apisunat_document_id' ) ) {
				printf( '<p>Numero %s: <strong> %s</strong></p>', esc_attr( $tipo ), esc_attr( $number[2] ) . '-' . esc_attr( $number[3] ) );
				printf(
					"<p><a href=https://back.apisunat.com/documents/%s/getPDF/A4/%s.pdf target='_blank'>Imprimir</a></p>",
					esc_attr( $order->get_meta( 'apisunat_document_id' ) ),
					esc_attr( $order->get_meta( 'apisunat_document_filename' ) )
				);
			}
			}
		} else {
			echo '<p>No se ha enviado la factura a APISUNAT</p>';
		}

		echo sprintf( '<input type="hidden" id="orderId" name="orderId" value="%s">', esc_attr( $order->get_id() ) );
		echo sprintf( '<input type="hidden" id="orderStatus" name="orderStatus" value="%s">', esc_attr( $order->get_status() ) );

		if ( get_option( 'apisunat_forma_envio' ) === 'auto' ) {
			if ( $order->get_meta( 'apisunat_document_status' ) === 'ERROR' ||
				$order->get_meta( 'apisunat_document_status' ) === 'EXCEPCION' ||
				$order->get_meta( 'apisunat_document_status' ) === 'RECHAZADO' ) {
				echo '<a id="apisunatSendData" class="button-primary">Enviar Comprobante</a> ';
				echo '<div id="apisunatLoading" class="mt-3 mx-auto" style="display:none;">
                        <img src="images/loading.gif" alt="loading"/>
                    </div>';
			}
		} elseif ( get_option( 'apisunat_forma_envio' ) === 'manual' ) {
			if ( ! $order->get_meta( 'apisunat_document_status' ) ||
				$order->get_meta( 'apisunat_document_status' ) === 'ERROR' ||
				$order->get_meta( 'apisunat_document_status' ) === 'EXCEPCION' ||
				$order->get_meta( 'apisunat_document_status' ) === 'RECHAZADO' ) {
				echo '<a id="apisunatSendData" class="button-primary">Enviar Comprobante</a> ';
				echo '<div id="apisunatLoading" class="mt-3 mx-auto" style="display:none;">
                        <img src="images/loading.gif" alt="loading"/>
                    </div>';
			}
		}

		// TODO: preparar anular orden.
		// if ($order->get_meta('apisunat_document_status') == 'ACEPTADO') {
		// echo '<p><a href="#" id="apisunat_show_anular">Anular?</a></p>';
		// echo '<div id="apisunat_reason" style="display: none;">';
		// echo '<textarea rows="5" id="apisunat_nular_reason" placeholder="Razon por la que desea anular" minlength="3" maxlength="100"></textarea>';
		// echo '<a href="#" id="apisunatAnularData" class="button-primary">Anular con NC</a> ';
		// echo '<div id="apisunatLoading2" class="mt-3 mx-auto" style="display:none;">
		// <img src="images/loading.gif"/>
		// </div>';
		// echo '</div>';
		// }.
	}

	/**
	 * APISUNAT menu inside Woocomerce menu
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function add_apisunat_admin_menu(): void {
		add_submenu_page(
			'woocommerce',
			'APISUNAT',
			'APISUNAT',
			'manage_woocommerce',
			'apisunat',
			array( $this, 'display_apisunat_admin_settings' ),
			16
		);
	}

	/**
	 * Display settings
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function display_apisunat_admin_settings(): void {
		require_once 'partials/' . $this->plugin_name . '-admin-display.php';
	}

	/**
	 * Declare sections Fields
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function register_and_build_fields(): void {
		/**
		 * First, we add_settings_section. This is necessary since all future settings must belong to one.
		 * Second, add_settings_field
		 * Third, register_setting
		 */
		add_settings_section(
			'apisunat_general_section',
			'Datos de acceso',
			array( $this, 'apisunat_display_general_account' ),
			'apisunat_general_settings'
		);

		add_settings_section(
			'apisunat_data_section',
			'',
			array( $this, 'apisunat_display_data' ),
			'apisunat_general_settings'
		);

		add_settings_section(
			'apisunat_advanced_section',
			'',
			array( $this, 'apisunat_display_advanced' ),
			'apisunat_general_settings'
		);

		unset( $args );

		$args = array(
			array(
				'title'    => 'personalId: ',
				'type'     => 'input',
				'id'       => 'apisunat_personal_id',
				'name'     => 'apisunat_personal_id',
				'required' => true,
				'class'    => 'regular-text',
				'group'    => 'apisunat_general_settings',
				'section'  => 'apisunat_general_section',
			),
			array(
				'title'    => 'personalToken: ',
				'type'     => 'input',
				'id'       => 'apisunat_personal_token',
				'name'     => 'apisunat_personal_token',
				'required' => true,
				'class'    => 'regular-text',
				'group'    => 'apisunat_general_settings',
				'section'  => 'apisunat_general_section',
			),

			array(
				'title'    => 'Tipo de envío: ',
				'type'     => 'select',
				'name'     => 'apisunat_forma_envio',
				'id'       => 'apisunat_forma_envio',
				'required' => true,
				'options'  => array(
					'manual' => 'MANUAL',
					'auto'   => 'AUTOMATICO',
				),
				'group'    => 'apisunat_general_settings',
				'section'  => 'apisunat_data_section',
			),
			array(
				'title'    => 'Serie - Factura: ',
				'type'     => 'input',
				'name'     => 'apisunat_serie_factura',
				'id'       => 'apisunat_serie_factura',
				'default'  => 'F001',
				'required' => true,
				'pattern'  => '^[F][A-Z\d]{3}$',
				'class'    => 'regular-text',
				'group'    => 'apisunat_general_settings',
				'section'  => 'apisunat_data_section',
			),
			array(
				'title'    => 'Serie - Boleta: ',
				'type'     => 'input',
				'name'     => 'apisunat_serie_boleta',
				'id'       => 'apisunat_serie_boleta',
				'default'  => 'B001',
				'required' => true,
				'pattern'  => '^[B][A-Z\d]{3}$',
				'class'    => 'regular-text',
				'group'    => 'apisunat_general_settings',
				'section'  => 'apisunat_data_section',
			),
			array(
				'title'    => 'Afectación al IGV: ',
				'type'     => 'select',
				'name'     => 'apisunat_tipo_tributo',
				'id'       => 'apisunat_tipo_tributo',
				'required' => true,
				'options'  => array(
					'10' => 'GRAVADO',
					'20' => 'EXONERADO',
				),
				'group'    => 'apisunat_general_settings',
				'section'  => 'apisunat_data_section',
			),
			array(
				'title'    => 'Facturar costo de envío: ',
				'type'     => 'select',
				'name'     => 'apisunat_shipping_cost',
				'id'       => 'apisunat_shipping_cost',
				'required' => true,
				'options'  => array(
					'false' => 'NO',
					'true'  => 'SI',
				),
				'group'    => 'apisunat_general_settings',
				'section'  => 'apisunat_data_section',
			),
			array(
				'title'    => 'Hora de emisión: ',
				'type'     => 'select',
				'name'     => 'apisunat_include_time',
				'id'       => 'apisunat_include_time',
				'required' => true,
				'options'  => array(
					'false' => 'NO',
					'true'  => 'SI',
				),
				'group'    => 'apisunat_general_settings',
				'section'  => 'apisunat_data_section',
			),

			array(
				'title'    => 'Debug (NO ACTIVAR): ',
				'type'     => 'select',
				'name'     => 'apisunat_debug_mode',
				'id'       => 'apisunat_debug_mode',
				'required' => true,
				'options'  => array(
					'false' => 'NO',
					'true'  => 'SI',
				),
				'group'    => 'apisunat_general_settings',
				'section'  => 'apisunat_advanced_section',
			),

			array(
				'title'    => 'Checkout personalizado: ',
				'type'     => 'select',
				'name'     => 'apisunat_custom_checkout',
				'id'       => 'apisunat_custom_checkout',
				'required' => true,
				'options'  => array(
					'false' => 'NO',
					'true'  => 'SI',
				),
				'group'    => 'apisunat_general_settings',
				'section'  => 'apisunat_advanced_section',
			),
			array(
				'title'       => 'key para Tipo de Comprobante: ',
				'type'        => 'input',
				'name'        => 'apisunat_key_tipo_comprobante',
				'id'          => 'apisunat_key_tipo_comprobante',
				'placeholder' => '_billing_apisunat_document_type',
				'required'    => true,
				'class'       => 'regular-text regular-text-advanced',
				'group'       => 'apisunat_general_settings',
				'section'     => 'apisunat_advanced_section',
			),
			array(
				'title'       => 'value para FACTURA: ',
				'type'        => 'input',
				'name'        => 'apisunat_key_value_factura',
				'id'          => 'apisunat_key_value_factura',
				'placeholder' => '01',
				'required'    => true,
				'class'       => 'regular-text regular-text-advanced',
				'group'       => 'apisunat_general_settings',
				'section'     => 'apisunat_advanced_section',
			),
			array(
				'title'       => 'value para BOLETA: ',
				'type'        => 'input',
				'name'        => 'apisunat_key_value_boleta',
				'id'          => 'apisunat_key_value_boleta',
				'placeholder' => '03',
				'required'    => true,
				'class'       => 'regular-text regular-text-advanced',
				'group'       => 'apisunat_general_settings',
				'section'     => 'apisunat_advanced_section',
			),
			array(
				'title'       => 'key para Tipo de Documento: ',
				'type'        => 'input',
				'name'        => 'apisunat_key_tipo_documento',
				'id'          => 'apisunat_key_tipo_documento',
				'placeholder' => '_billing_apisunat_customer_id_type',
				'required'    => true,
				'class'       => 'regular-text regular-text-advanced',
				'group'       => 'apisunat_general_settings',
				'section'     => 'apisunat_advanced_section',
			),
			array(
				'title'       => 'value para DNI: ',
				'type'        => 'input',
				'name'        => 'apisunat_key_value_dni',
				'id'          => 'apisunat_key_value_dni',
				'placeholder' => '1',
				'required'    => true,
				'class'       => 'regular-text regular-text-advanced',
				'group'       => 'apisunat_general_settings',
				'section'     => 'apisunat_advanced_section',
			),
			array(
				'title'       => 'value para RUC: ',
				'type'        => 'input',
				'name'        => 'apisunat_key_value_ruc',
				'id'          => 'apisunat_key_value_ruc',
				'placeholder' => '6',
				'required'    => true,
				'class'       => 'regular-text regular-text-advanced',
				'group'       => 'apisunat_general_settings',
				'section'     => 'apisunat_advanced_section',
			),
			array(
				'title'       => 'value para PASAPORTE: ',
				'type'        => 'input',
				'name'        => 'apisunat_key_value_pasaporte',
				'id'          => 'apisunat_key_value_pasaporte',
				'placeholder' => '7',
				'required'    => true,
				'class'       => 'regular-text regular-text-advanced',
				'group'       => 'apisunat_general_settings',
				'section'     => 'apisunat_advanced_section',
			),
			array(
				'title'       => 'value para OTROS (Doc. Extranjero): ',
				'type'        => 'input',
				'name'        => 'apisunat_key_value_otros_extranjero',
				'id'          => 'apisunat_key_value_otros_extranjero',
				'placeholder' => 'B',
				'required'    => true,
				'class'       => 'regular-text regular-text-advanced',
				'group'       => 'apisunat_general_settings',
				'section'     => 'apisunat_advanced_section',
			),
			array(
				'title'       => 'key para Número de Documento: ',
				'type'        => 'input',
				'name'        => 'apisunat_key_numero_documento',
				'id'          => 'apisunat_key_numero_documento',
				'placeholder' => '_billing_apisunat_customer_id',
				'required'    => true,
				'class'       => 'regular-text regular-text-advanced',
				'group'       => 'apisunat_general_settings',
				'section'     => 'apisunat_advanced_section',
			),
		);
		foreach ( $args as $arg ) {
			add_settings_field(
				$arg['id'],
				$arg['title'],
				array( $this, 'apisunat_render_settings_field' ),
				$arg['group'],
				$arg['section'],
				$arg
			);
			register_setting(
				$arg['group'],
				$arg['id']
			);
		}
	}

	/**
	 * General settings Header
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function apisunat_display_general_account(): void {
		?>
			<h4>Los datos de acceso se obtienen al crear una empresa en <a href="https://apisunat.com/" target="_blank" rel="noopener">APISUNAT.com</a></h4>
			<!-- <hr> -->
		<?php
	}

	/**
	 * Message user settings section
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function apisunat_display_data(): void {
		?>
			<hr>
			<h3>Configuración</h3>
		<?php
	}

	/**
	 * Advanced settings Header
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function apisunat_display_advanced(): void {
		?>
			<hr>
			<h3>Configuración avanzada</h3>
		<?php
	}

	/**
	 * Render html settings fields
	 *
	 * @param array $args Array or args.
	 * @return void
	 * @since    1.0.0
	 */
	public function apisunat_render_settings_field( array $args ): void {
		$required_attr    = $args['required'] ? 'required' : '';
		$pattern_attr     = isset( $args['pattern'] ) ? 'pattern=' . $args['pattern'] : '';
		$palceholder_attr = isset( $args['placeholder'] ) ? 'placeholder=' . $args['placeholder'] : '';
		$default_value    = $args['default'] ?? '';

		switch ( $args['type'] ) {
			case 'input':
				printf(
					'<input type="' . $args['type'] . '" id="' . $args['id'] . '" name="' . $args['name'] . '"class="' . $args['class'] . '"' . $required_attr . ' ' . $palceholder_attr . ' ' . $pattern_attr . '  value="%s" />',
					get_option( $args['id'] ) ? esc_attr( get_option( $args['id'] ) ) : esc_attr( $default_value )
				);
				break;
			case 'number':
				printf(
					'<input type="' . $args['type'] . '" id="' . $args['id'] . '" name="' . $args['name'] . '" min="' . $args['min'] . '" max="' . $args['max'] . '" step="' . $args['step'] . '" value="%s"/>',
					get_option( $args['id'] ) ? esc_attr( get_option( $args['id'] ) ) : ''
				);
				break;
			case 'select':
				$option = get_option( $args['id'] );
				$items  = $args['options'];
				echo sprintf( '<select id="%s" name="%s">', esc_attr( $args['id'] ), esc_attr( $args['id'] ) );
				foreach ( $items as $key => $item ) {
					$selected = ( $option === $key ) ? 'selected="selected"' : '';
					echo sprintf( "<option value='%s' %s>%s</option>", esc_attr( $key ), esc_attr( $selected ), esc_attr( $item ) );
				}
				echo '</select>';
				break;
			default:
				break;
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/apisunat-admin.css', array(), $this->version );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/apisunat-admin.js', array( 'jquery' ), $this->version, true );
		wp_localize_script( $this->plugin_name, 'apisunat_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}
}

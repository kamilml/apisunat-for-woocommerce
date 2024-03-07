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
class Apisunat_Admin
{

	const API_WC_URL        = 'https://ecommerces-api.apisunat.com/v1.2/woocommerce';
	const API_URL           = 'https://back.apisunat.com';
	const META_DATA_MAPPING = array(
		'_billing_apisunat_document_type'    => array(
			'key'      => '_billing_apisunat_document_type',
			'value_01' => '01',
			'value_03' => '03',
		),
		'_billing_apisunat_customer_id_type' => array(
			'key'     => '_billing_apisunat_customer_id_type',
			'value_1' => '1',
			'value_6' => '6',
			'value_7' => '7',
			'value_B' => 'B',
		),
		'_billing_apisunat_customer_id'      => array(
			'key' => '_billing_apisunat_customer_id',
		),
	);


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
	public function __construct(string $plugin_name, string $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_action('admin_menu', array($this, 'add_apisunat_admin_menu'), 10);
		add_action('admin_init', array($this, 'register_and_build_fields'));
		add_action('add_meta_boxes', array($this, 'apisunat_meta_boxes'));
		add_action('admin_init', array($this, 'apisunat_forma_envio_facturas'));
		add_action('wp_ajax_void_apisunat_order', array($this, 'void_apisunat_order'), 11, 1);
		add_filter('manage_edit-shop_order_columns', array($this, 'apisunat_custom_order_column'), 11);
		add_action('manage_shop_order_posts_custom_column', array($this, 'apisunat_custom_orders_list_column_content'), 10, 2);
		add_filter('plugin_action_links_apisunat/apisunat.php', array($this, 'apisunat_settings_link'));
		add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'apisunat_editable_order_meta_billing'));
		add_action('woocommerce_process_shop_order_meta', array($this, 'apisunat_save_general_details'));
		add_action('woocommerce_new_order', array($this, 'apisunat_save_metadata_mapping'), 10, 1);
		add_filter('bulk_actions-edit-shop_order', array($this, 'apisunat_bulk_actions'));
		add_filter('handle_bulk_actions-edit-shop_order', array($this, 'apisunat_bulk_action_handler'), 10, 3);
		add_action('admin_notices', array($this, 'apisunat_bulk_action_handler_admin_notice'));

		add_filter('views_edit-shop_order', array($this, 'custom_order_views'));
		// Aplicar el filtro cuando se procesa la consulta
		add_filter('parse_query', array($this, 'apply_custom_filter'));
		add_action('updated_option', array($this, 'obtener_fecha_al_guardar_forma_envio'), 10, 3);

		if (!function_exists('plugin_log')) {
			function plugin_log($message)
			{
				// // Get WordPress uploads directory.
				// $upload_dir = wp_upload_dir();
				// $upload_dir = $upload_dir['basedir'];
				// // If the entry is array, json_encode.
				// if (is_array($entry)) {
				// 	$entry = json_encode($entry);
				// }
				// // Write the log file.
				// $file  = $upload_dir . '/' . $file . '.log';
				// $file  = fopen($file, $mode);
				// $bytes = fwrite($file, current_time('mysql') . '::' . $entry . "\n");
				// fclose($file);
				// return $bytes;

				if (get_option('apisunat_logtail_token')) {

					$url = 'https://in.logs.betterstack.com';

					$data = array(
						'message' => $message
					);

					$curl_options = array(
						CURLOPT_URL            => $url,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_TIMEOUT        => 30,
						CURLOPT_CUSTOMREQUEST  => 'POST',
						CURLOPT_HTTPHEADER     => array(
							'Content-Type: application/json',
							'Authorization: Bearer ' . get_option('apisunat_logtail_token')
						),
						CURLOPT_POSTFIELDS      => json_encode($data),
						CURLOPT_SSL_VERIFYPEER => false, // Si deseas desactivar la verificación SSL
					);

					$curl = curl_init();
					curl_setopt_array($curl, $curl_options);
					$response = curl_exec($curl);

					if (curl_errno($curl)) {
					}
					curl_close($curl);
				}
			}
		}
	}

	function obtener_fecha_al_guardar_forma_envio($option_name, $old_value, $new_value)
	{
		global $wpdb;

		if ('apisunat_forma_envio' === $option_name) {
			$fecha_actual = current_time('mysql');

			if ('auto' === $new_value) {
				plugin_log("Registro de fecha cambio a automatico: " . $fecha_actual);
				update_option('apisunat_fecha', $fecha_actual);
			}
		}
	}

	public function apisunat_bulk_actions()
	{
		$actions['emit_apisunat']      = __('Emitir CPE');
		return $actions;
	}

	public function apisunat_bulk_action_handler($redirect_to, $action, $post_ids)
	{
		if ('emit_apisunat' !== $action) {
			return $redirect_to;
		}
		$proccessed = 0;

		foreach ($post_ids as $post_id) {
			$order = wc_get_order($post_id);
			if ("completed" === $order->get_data()["status"]) {
				$this->send_apisunat_order($post_id);
				$proccessed++;
			}
		}

		return add_query_arg(
			array(
				'processed_count' => $proccessed,
			),
			$redirect_to
		);
	}

	public function apisunat_bulk_action_handler_admin_notice()
	{
		if (empty($_REQUEST['processed_count'])) return; // Exit

		$count = intval($_REQUEST['processed_count']);

		printf('<div id="message" class="updated fade"><p>' .
			_n(
				'Procesadas %s Orden',
				'Procesadas %s Ordenes',
				$count,
				'write_downloads'
			) . '</p></div>', $count);
	}

	public function custom_order_views($views)
	{
		// $completed_count = $this->count_orders_without_status_meta();

		// $views['completed_nocpe'] = '<a href="edit.php?post_type=shop_order&custom_filter=completed" class="' . (isset($_GET['custom_filter']) && $_GET['custom_filter'] === 'completed' ? 'current' : '') . '">' . esc_html__('Completados sin CPE') . ' (' . $completed_count . ')</a>';
		$views['completed_nocpe'] = '<a href="edit.php?post_type=shop_order&custom_filter=completed" class="' . (isset($_GET['custom_filter']) && $_GET['custom_filter'] === 'completed' ? 'current' : '') . '">' . esc_html__('Completados sin CPE') . '</a>';

		return $views;
	}

	// public function count_orders_without_status_meta()
	// {
	// 	$orders = wc_get_orders(array(
	// 		'limit'        => -1,
	// 		'meta_key'     => 'apisunat_document_status',
	// 		'meta_compare' => 'NOT EXISTS',
	// 		'status' => 'wc-completed',
	// 	));

	// 	return count($orders);
	// }

	public function apply_custom_filter($query)
	{
		global $typenow;

		if ($typenow == 'shop_order' && isset($_GET['custom_filter']) && $_GET['custom_filter'] == 'completed') {
			// Agregar la condición para órdenes completadas sin la meta apisunat_document_status
			$query->query_vars['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => 'apisunat_document_status',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'apisunat_document_status',
					'value'     => array('ACEPTADO', 'PENDIENTE'),
					'compare' => 'NOT IN',
				),
			);
			$query->set('post_status', 'wc-completed');
		}
	}



	/**
	 * Save metadata after create order.
	 *
	 * @param $order_id
	 * @return void
	 */
	public function apisunat_save_metadata_mapping($order_id)
	{
		$metadata = $this->build_meta_data_mapping();
		update_post_meta($order_id, '_billing_apisunat_meta_data_mapping', wp_json_encode($metadata));
	}

	/**
	 * Update billing metadata
	 *
	 * @param WC_Order $order Order data.
	 * @return void
	 */
	public function apisunat_editable_order_meta_billing(WC_Order $order)
	{

		$meta_temp = $order->get_meta('_billing_apisunat_meta_data_mapping');

		$temp = array();

		if ($meta_temp) {
			$temp = json_decode($meta_temp, true);
		} else {
			$temp = self::META_DATA_MAPPING;
		}

		$document_type    = $order->get_meta($temp['_billing_apisunat_document_type']['key']);
		$customer_id_type = $order->get_meta($temp['_billing_apisunat_customer_id_type']['key']);
		$customer_id      = $order->get_meta($temp['_billing_apisunat_customer_id']['key']);

		$document_types = array(
			$temp['_billing_apisunat_document_type']['value_03'] => 'BOLETA DE VENTA',
			$temp['_billing_apisunat_document_type']['value_01'] => 'FACTURA',
		);

		$customer_id_types = array(
			$temp['_billing_apisunat_customer_id_type']['value_1'] => 'DNI',
			$temp['_billing_apisunat_customer_id_type']['value_6'] => 'RUC',
			$temp['_billing_apisunat_customer_id_type']['value_7'] => 'PASAPORTE',
			$temp['_billing_apisunat_customer_id_type']['value_B'] => 'OTROS (Doc. Extranjero)',
		);

		if ($order->meta_exists('_billing_apisunat_meta_data_mapping')) {
?>

			<div class="address">
				<p <?php
					if (!$document_type) {
						echo ' class="none_set"';
					}
					?>>
					<strong>Tipo de CPE:</strong>
					<?php echo $document_types[$document_type] ? esc_html($document_types[$document_type]) : 'No document type selected.'; ?>
				</p>
				<p <?php
					if (!$customer_id_type) {
						echo ' class="none_set"';
					}
					?>>
					<strong>Tipo de Doc. del comprador: </strong>
					<?php echo $customer_id_types[$customer_id_type] ? esc_html($customer_id_types[$customer_id_type]) : 'No customer id type selected.'; ?>

				</p>
				<p <?php
					if (!$customer_id) {
						echo ' class="none_set"';
					}
					?>>
					<strong>Número de Doc. del comprador:</strong>
					<?php echo $customer_id ? esc_html($customer_id) : 'No customer id'; ?>
				</p>
			</div>
		<?php } ?>
		<div class="edit_address">
			<?php
			woocommerce_wp_select(
				array(
					'id'            => '_billing_apisunat_document_type',
					'label'         => 'Tipo de CPE',
					'wrapper_class' => 'form-field-wide',
					'value'         => $document_type,
					'options'       => $document_types,
				)
			);
			woocommerce_wp_select(
				array(
					'id'            => '_billing_apisunat_customer_id_type',
					'label'         => 'Tipo de Doc. del comprador',
					'wrapper_class' => 'form-field-wide',
					'value'         => $customer_id_type,
					'options'       => $customer_id_types,
				)
			);
			woocommerce_wp_text_input(
				array(
					'id'            => '_billing_apisunat_customer_id',
					'label'         => 'Número de Doc. del comprador',
					'value'         => $customer_id,
					'wrapper_class' => 'form-field-wide',
				)
			);
			?>
		</div>
	<?php
	}

	/**
	 * Save updated billing metadata.
	 *
	 * @param $order_id
	 * @return void
	 */
	public function apisunat_save_general_details($order_id)
	{

		$order = wc_get_order($order_id);

		$meta_temp = $order->get_meta('_billing_apisunat_meta_data_mapping');

		$temp = array();

		if ($meta_temp) {
			$temp = json_decode($meta_temp, true);
		} else {
			$temp = self::META_DATA_MAPPING;
		}

		update_post_meta($order_id, $temp['_billing_apisunat_customer_id']['key'], wc_sanitize_textarea(wp_unslash($_POST['_billing_apisunat_customer_id'])));
		update_post_meta($order_id, $temp['_billing_apisunat_customer_id_type']['key'], wc_clean(wp_unslash($_POST['_billing_apisunat_customer_id_type'])));
		update_post_meta($order_id, $temp['_billing_apisunat_document_type']['key'], wc_clean(wp_unslash($_POST['_billing_apisunat_document_type'])));

		update_post_meta($order->get_id(), '_billing_apisunat_meta_data_mapping', wp_json_encode($temp));
	}

	/**
	 * Add settings links.
	 *
	 * @param array $links Plugins links.
	 * @return array
	 */
	public function apisunat_settings_link(array $links): array
	{
		$url           = get_admin_url() . 'admin.php?page=' . $this->plugin_name;
		$settings_link = '<a href="' . $url . '">Configuracion</a>';
		$links[]       = $settings_link;
		return $links;
	}

	/**
	 * Add APISUNAT status Column
	 *
	 * @param array $columns Default columns.
	 * @return array
	 * @since    1.0.0
	 */
	public function apisunat_custom_order_column($columns): array
	{
		$reordered_columns = array();

		foreach ($columns as $key => $column) {
			$reordered_columns[$key] = $column;
			if ('order_status' === $key) {
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
	public function apisunat_custom_orders_list_column_content(string $column, string $post_id): void
	{
		if ('apisunat_document_files' === $column) {
			$order   = wc_get_order($post_id);
			$status  = get_post_meta($post_id, 'apisunat_document_status', true);
			$doc_id  = get_post_meta($post_id, 'apisunat_document_id', true);
			$estados = array('ERROR', 'RECHAZADO', 'EXCEPCION');

			if (empty($status)) {
				$this->boton_emitir($order->get_id(), $order->get_status());
			}

			if (in_array($status, $estados, true)) {
				echo esc_attr($status);

				if ("ERROR" === $status || "EXCEPCION" === $status) {
					echo "&nbsp;";
					$this->boton_emitir($order->get_id(), $order->get_status());
				}
			} else {
				// $request = wp_remote_get( self::API_URL . '/documents/' . $doc_id . '/getById' );
				// $data    = json_decode( wp_remote_retrieve_body( $request ), true );

				if ($doc_id) {
					printf(
						"<a href=https://back.apisunat.com/documents/%s/getPDF/default/%s.pdf target='_blank' class='button'>PDF</a>",
						esc_attr(get_post_meta($post_id, 'apisunat_document_id', true)),
						esc_attr(get_post_meta($post_id, 'apisunat_document_filename', true))
					);
					// printf(
					// 	" <a href=%s target=_blank' class='button'>XML</a>",
					// 	esc_attr( $data['xml'] )
					// );
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
	public function apisunat_check_status_on_schedule(): void
	{
        global $wpdb;
        $table_name = $wpdb->prefix . 'semaphore';
        $result = $wpdb->query($wpdb->prepare("UPDATE $table_name SET is_locked = TRUE WHERE id = 1 AND is_locked = FALSE"));
        sleep ( rand ( 1, 5));

        if ($result > 0) {
            if (get_option('apisunat_forma_envio') === 'auto') {

                if (!get_option('apisunat_fecha')) {
                    update_option('apisunat_fecha', current_time('mysql'));
                }

                $fecha_limite = get_option('apisunat_fecha');

                $orders_completed = wc_get_orders(
                    array(
                        'orderby' => 'id',
                        'order' => 'DESC',
                        'limit' => 60,
                        'status' => 'wc-completed',
                        // 'date_query' => array(
                        // 	array(
                        // 		'after' => date('Y-m-d H:i:s', $fecha_limite), // Formatea la fecha límite.
                        // 	),
                        // ),
                        'date_after' => $fecha_limite,
                        'meta_key' => 'apisunat_document_status',
                        'meta_compare' => 'NOT EXISTS',
                    )
                );
                plugin_log($fecha_limite . ' FECHA LIMITE / Ejecutando apisunat_check_status_on_schedule ' . count($orders_completed) . ' órdenes');

                foreach ($orders_completed as $order) {
                    plugin_log("orders_completed foreach: " . $order->get_id());

                    if ($order->meta_exists('_billing_apisunat_meta_data_mapping')) {
                        plugin_log("send_apisunat_order: " . $order->get_id());
                        $this->send_apisunat_order($order->get_id());

                    }
                }
            }

            $orders = wc_get_orders(
                array(
                    'limit' => -1, // Query all orders.
                    'meta_key' => 'apisunat_document_status', // The postmeta key field.
                    'meta_value' => 'PENDIENTE', // The postmeta key field.
                    'meta_compare' => '=', // The comparison argument.
                )
            );

            foreach ($orders as $order) {
                if ($order->meta_exists('apisunat_document_id') && $order->get_meta('apisunat_document_status') === 'PENDIENTE') {
                    $request = wp_remote_get(self::API_URL . '/documents/' . $order->get_meta('apisunat_document_id') . '/getById');
                    $data = json_decode(wp_remote_retrieve_body($request), true);
                    $status = $data['status'];

                    $order->add_order_note(' El documento se encuentra en estado: ' . $status);
                    update_post_meta($order->get_id(), 'apisunat_document_status', $status);
                }
            }

            $wpdb->query($wpdb->prepare("UPDATE $table_name SET is_locked = FALSE WHERE id = 1"));
        }
	}

	/**
	 * Send bill event trigger depend on manual or automatic config
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function apisunat_forma_envio_facturas(): void
	{
		if (get_option('apisunat_forma_envio') === 'auto') {
			// add_action( 'woocommerce_order_status_completed', array( $this, 'send_apisunat_order' ), 10, 1 );
			add_action('woocommerce_order_status_changed', array($this, 'apisunat_order_status_change'), 10, 3);
		}
		add_action('wp_ajax_send_apisunat_order', array($this, 'send_apisunat_order'), 10, 1);
	}

	/**
	 * Observe order status change
	 *
	 * @param $order_id
	 * @param $old_status
	 * @param $new_status
	 * @return void
	 * @since    1.0.0
	 */
	public function apisunat_order_status_change($order_id, $old_status, $new_status)
	{
		plugin_log('Order: ' . $order_id . ' change Status from: ' . $old_status . ' to: ' . $new_status);
		$order = wc_get_order($order_id);
		$order->add_order_note('Nota 1 - Order: ' . $order_id . ' change Status from: ' . $old_status . ' to: ' . $new_status);

		if ('completed' === $new_status) {
			$this->send_apisunat_order($order_id);
			$order->add_order_note('Nota 2 - Enviando a APISUNAT');
		}
	}

	/**
	 * Prepare payload and send info to APISUNAT API
	 *
	 * @param $order_id
	 * @return void
	 * @since    1.0.0
	 */
	public function send_apisunat_order($order_id): void
	{

		$order_idd = isset($_POST['order_value']) ? intval($_POST['order_value']) : $order_id;

		/**
		 * Obtener datos de la orden y el tipo de documento
		 */
		$order = wc_get_order($order_idd);

		if (get_option('apisunat_no_doc') != 'true') {
			if ($order->meta_exists('_billing_apisunat_meta_data_mapping')) {

				$meta_temp = $order->get_meta('_billing_apisunat_meta_data_mapping');

				if ($meta_temp) {
					$temp = json_decode($meta_temp, true);
				} else {
					$temp = self::META_DATA_MAPPING;
				}

				$_apisunat_customer_id = $temp['_billing_apisunat_customer_id']['key'];

				if (!$order->meta_exists($_apisunat_customer_id) || $order->get_meta($_apisunat_customer_id) === '') {
					$order->add_order_note('Verifique que exista valores de Numeros de Documentos del cliente');

					return;
				}
			} else {
				if (!$order->meta_exists('_billing_apisunat_customer_id') || $order->get_meta('_billing_apisunat_customer_id') === '') {
					$order->add_order_note('Verifique que exista valores de Numeros de Documentos del cliente');

					return;
				}
			}
		}


		if ($order->meta_exists('apisunat_document_status')) {
			if ($order->get_meta('apisunat_document_status') === 'PENDIENTE' || $order->get_meta('apisunat_document_status') === 'ACEPTADO') {
				return;
			}
		}

		$send_data = $this->build_send_data();

		$send_data['order_data'] = $order->get_data();

		foreach ($order->get_items() as $item) {
			$item_data                 = array(
				'item'    => $item->get_data(),
				'product' => $item->get_product()->get_data(),
			);
			$send_data['items_data'][] = $item_data;
		}

		$args = array(
			'method'  => 'POST',
			'timeout' => 30,
			'body'    => wp_json_encode($send_data),
			'headers' => array(
				'content-type' => 'application/json',
			),
		);

		$response = wp_remote_post(self::API_WC_URL, $args);
		//		$response = wp_remote_post( "https://webhook.site/dd7904ca-1546-47f5-a161-074a6eb192b3", $args );
		// plugin_log('Order: ' . $order_id . ' sended to APISUNAT');

		// si es un error de WP!
		if (is_wp_error($response)) {
			$error_response = $response->get_error_message();
			$msg            = $error_response;
			plugin_log('Error: ' . $msg);
		} else {
			$apisunat_response = json_decode($response['body'], true);
			// plugin_log('Apisunat Response: ' . $response['body']);

			if (!isset($apisunat_response['status'])) {
				$msg = wp_json_encode($apisunat_response);
			} else {

				update_post_meta($order_idd, 'apisunat_document_status', $apisunat_response['status']);

				if ('ERROR' === $apisunat_response['status']) {
					$msg = wp_json_encode($apisunat_response['error']);
				} else {
					update_post_meta($order_idd, 'apisunat_document_id', $apisunat_response['documentId']);
					update_post_meta($order_idd, 'apisunat_document_filename', $apisunat_response['fileName']);

					$msg = sprintf(
						"Se emitió el CPE <a href=https://back.apisunat.com/documents/%s/getPDF/default/%s.pdf target='_blank'>%s</a>",
						$apisunat_response['documentId'],
						$apisunat_response['fileName'],
						$this->split_bills_numbers($apisunat_response['fileName'])
					);
				}
			}
			// plugin_log('Apisunat Result: ' . $msg);
		}
		$order->add_order_note($msg);
	}

	/**
	 * Void APISUNAT bill
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function void_apisunat_order()
	{
		if (isset($_POST['order_value'])) {
			$order_id = intval($_POST['order_value']);

			$order       = wc_get_order($order_id);
			$document_id = $order->get_meta('apisunat_document_id');
			$filename    = $order->get_meta('apisunat_document_filename');

			$option_name = get_option('apisunat_key_tipo_comprobante');

			$tipo = '';

			switch ($order->get_meta($option_name)) {
				case '01':
					$tipo = 'Factura';
					break;
				case '03':
					$tipo = 'Boleta de Venta';
					break;
			}

			$number = $this->split_bills_numbers($order->get_meta('apisunat_document_filename'));

			$send_data = $this->build_send_data();

			$this->array_insert($send_data['plugin_data'], 4, array('serie07F' => get_option('apisunat_serie_nc_factura')));
			$this->array_insert($send_data['plugin_data'], 5, array('serie07B' => get_option('apisunat_serie_nc_boleta')));

			if (isset($_POST['reason'])) {
				$reason = sanitize_text_field(wp_unslash($_POST['reason']));
			}

			$send_data['document_data']['reason']         = $reason;
			$send_data['document_data']['documentId']     = $document_id;
			$send_data['document_data']['customer_email'] = $order->get_billing_email();

			$args = array(
				'method'  => 'POST',
				'timeout' => 45,
				'body'    => wp_json_encode($send_data),
				'headers' => array(
					'content-type' => 'application/json',
				),
			);

			$response = wp_remote_post(self::API_WC_URL . '/' . $document_id, $args);

			if (is_wp_error($response)) {
				$error_response = $response->get_error_code();
				$msg            = $error_response;
			} else {
				$apisunat_response = json_decode($response['body'], true);

				if ('PENDIENTE' === $apisunat_response['status']) {
					delete_post_meta($order_id, 'apisunat_document_status');
					delete_post_meta($order_id, 'apisunat_document_id');
					delete_post_meta($order_id, 'apisunat_document_filename');

					$msg = sprintf(
						"Se anuló la %s <a href=https://back.apisunat.com/documents/%s/getPDF/default/%s.pdf target='_blank'>%s</a> con la Nota de Crédito <a href=https://back.apisunat.com/documents/%s/getPDF/default/%s.pdf target='_blank'>%s</a>. Motivo: '%s' ",
						esc_attr($tipo),
						esc_attr($document_id),
						esc_attr($filename),
						$number,
						$apisunat_response['documentId'],
						$apisunat_response['fileName'],
						$this->split_bills_numbers($apisunat_response['fileName']),
						$send_data['document_data']['reason']
					);
				} else {

					$msg = wp_json_encode($apisunat_response['error']);
				}
			}
			$order->add_order_note($msg);
		}
	}

	/**
	 * Extra function
	 *
	 * @param array   $array Input array.
	 * @param integer $position Array position.
	 * @param array   $insert_array Array inserted.
	 * @return void
	 */
	private function array_insert(array &$array, int $position, array $insert_array)
	{
		$first_array = array_splice($array, 0, $position);
		$array       = array_merge($first_array, $insert_array, $array);
	}

	/**
	 * Add APISUNAT meta box
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function apisunat_meta_boxes(): void
	{
		add_meta_box(
			'woocommerce-order-apisunat',
			__('APISUNAT'),
			array($this, 'order_meta_box_apisunat'),
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
	public function order_meta_box_apisunat($order_id): void
	{
		$order = wc_get_order($order_id);
		if ($order->meta_exists('apisunat_document_status')) { {
				$option_name = get_option('apisunat_key_tipo_comprobante');

				$tipo = '';

				switch ($order->get_meta($option_name)) {
					case '01':
						$tipo = 'Factura';
						break;
					case '03':
						$tipo = 'Boleta de Venta';
						break;
				}

				if ($order->meta_exists('apisunat_document_filename')) {

					$number = $this->split_bills_numbers($order->get_meta('apisunat_document_filename'));

					printf('<p>Status: <strong> %s</strong></p>', esc_attr($order->get_meta('apisunat_document_status')));
				}

				if ($order->meta_exists('apisunat_document_id')) {
					echo sprintf(
						"<p>Numero %s: <a href=https://back.apisunat.com/documents/%s/getPDF/default/%s.pdf target='_blank'><strong>%s</strong></a>",
						esc_attr($tipo),
						esc_attr($order->get_meta('apisunat_document_id')),
						esc_attr($order->get_meta('apisunat_document_filename')),
						esc_attr($number)
					);
				}
			}
		} else {
			echo '<p>No se ha enviado la factura a APISUNAT</p>';
		}

		echo sprintf('<input type="hidden" id="orderId" name="orderId" value="%s">', esc_attr($order->get_id()));
		echo sprintf('<input type="hidden" id="orderStatus" name="orderStatus" value="%s">', esc_attr($order->get_status()));

		if (
			!$order->get_meta('apisunat_document_status') ||
			$order->get_meta('apisunat_document_status') === 'ERROR' ||
			$order->get_meta('apisunat_document_status') === 'EXCEPCION' ||
			$order->get_meta('apisunat_document_status') === 'RECHAZADO'
		) {
			$this->boton_emitir($order->get_id(), $order->get_status());
		}

		if ($order->get_meta('apisunat_document_status') === 'ACEPTADO') {
			printf('<p><a href="#" id="apisunat_show_anular" class="button-primary apisunat-button">Anular</a></p>');
			printf('<div id="apisunat_reason" style="display: none;">');
			printf('<textarea rows="5" id="apisunat_anular_reason" placeholder="Razon por la que desea anular" minlength="3" maxlength="100"></textarea>');
			printf('<a href="#" id="apisunatAnularData" class="button-primary">Anular con NC</a> ');
			printf(
				'<div id="apisunatLoading2" class="mt-3 mx-auto" style="display:none;">
		                <img src="images/loading.gif"/>
		                </div>'
			);
			printf('</div>');
		}
	}

	/**
	 * APISUNAT menu inside Woocomerce menu
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function add_apisunat_admin_menu(): void
	{
		add_submenu_page(
			'woocommerce',
			'APISUNAT',
			'APISUNAT',
			'manage_woocommerce',
			'apisunat',
			array($this, 'display_apisunat_admin_settings'),
			16
		);
	}

	/**
	 * Display settings
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function display_apisunat_admin_settings(): void
	{
		require_once 'partials/' . $this->plugin_name . '-admin-display.php';
	}

	/**
	 * Declare sections Fields
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function register_and_build_fields(): void
	{
		/**
		 * First, we add_settings_section. This is necessary since all future settings must belong to one.
		 * Second, add_settings_field
		 * Third, register_setting
		 */
		add_settings_section(
			'apisunat_general_section',
			'Datos de acceso',
			array($this, 'apisunat_display_general_account'),
			'apisunat_general_settings'
		);

		add_settings_section(
			'apisunat_data_section',
			'',
			array($this, 'apisunat_display_data'),
			'apisunat_general_settings'
		);

		add_settings_section(
			'apisunat_advanced_section',
			'',
			array($this, 'apisunat_display_advanced'),
			'apisunat_general_settings'
		);

		unset($args);

		$args = array(
			array(
				'title'    => 'personaId: ',
				'type'     => 'input',
				'id'       => 'apisunat_personal_id',
				'name'     => 'apisunat_personal_id',
				'required' => true,
				'class'    => 'regular-text',
				'group'    => 'apisunat_general_settings',
				'section'  => 'apisunat_general_section',
			),
			array(
				'title'    => 'personaToken: ',
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
				'title'    => 'Permitir CPE sin Documento del comprador: ',
				'type'     => 'select',
				'name'     => 'apisunat_no_doc',
				'id'       => 'apisunat_no_doc',
				'required' => true,
				'options'  => array(
					'false' => 'NO',
					'true'  => 'SI',
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
				'title'    => 'Serie - NC Factura: ',
				'type'     => 'input',
				'name'     => 'apisunat_serie_nc_factura',
				'id'       => 'apisunat_serie_nc_factura',
				'default'  => 'FC01',
				'required' => true,
				'pattern'  => '^[F][C][A-Z\d]{2}$',
				'class'    => 'regular-text',
				'group'    => 'apisunat_general_settings',
				'section'  => 'apisunat_data_section',
			),
			array(
				'title'    => 'Serie - NC Boleta: ',
				'type'     => 'input',
				'name'     => 'apisunat_serie_nc_boleta',
				'id'       => 'apisunat_serie_nc_boleta',
				'default'  => 'BC01',
				'required' => true,
				'pattern'  => '^[B][C][A-Z\d]{2}$',
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
					'30' => 'INAFECTO',
				),
				'group'    => 'apisunat_general_settings',
				'section'  => 'apisunat_data_section',
			),
			array(
				'title'    => 'Factor IGV (solo para GRAVADO): ',
				'type'     => 'select',
				'name'     => 'apisunat_factor_tributo',
				'id'       => 'apisunat_factor_tributo',
				'required' => true,
				'options'  => array(
					'18' => '18',
					'10' => '10',
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
				'title'    => 'Logtail token: ',
				'type'     => 'input',
				'name'     => 'apisunat_logtail_token',
				'id'       => 'apisunat_logtail_token',
				'required' => false,
				'class'    => 'regular-text',
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
				'title'       => 'key para Tipo de CPE: ',
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
				'title'       => 'key para Tipo de Documento del comprador: ',
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
				'title'       => 'key para Número de Documento del comprador: ',
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
		foreach ($args as $arg) {
			add_settings_field(
				$arg['id'],
				$arg['title'],
				array($this, 'apisunat_render_settings_field'),
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
	public function apisunat_display_general_account(): void
	{
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
	public function apisunat_display_data(): void
	{
		echo '<hr>';
		echo '<h3>Configuración</h3>';

		$ultimo_cambio = get_option('apisunat_fecha');
		if ($ultimo_cambio) {
			$fecha_formateada = date('d/m/Y H:i:s', strtotime($ultimo_cambio));
			echo '<small>El último cambio a automático se realizó el: ' . $fecha_formateada . '</small>';
		}
	}

	/**
	 * Advanced settings Header
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function apisunat_display_advanced(): void
	{
		echo '<hr>';
		echo '<h3>Avanzado</h3>';
	}

	/**
	 * Render html settings fields
	 *
	 * @param array $args Array or args.
	 * @return void
	 * @since    1.0.0
	 */
	public function apisunat_render_settings_field(array $args): void
	{
		$required_attr    = $args['required'] ? 'required' : '';
		$pattern_attr     = isset($args['pattern']) ? 'pattern=' . $args['pattern'] : '';
		$palceholder_attr = isset($args['placeholder']) ? 'placeholder=' . $args['placeholder'] : '';
		$default_value    = $args['default'] ?? '';

		switch ($args['type']) {
			case 'input':
				printf(
					'<input type="' . $args['type'] . '" id="' . $args['id'] . '" name="' . $args['name'] . '"class="' . $args['class'] . '"' . $required_attr . ' ' . $palceholder_attr . ' ' . $pattern_attr . '  value="%s" />',
					get_option($args['id']) ? esc_attr(get_option($args['id'])) : esc_attr($default_value)
				);
				break;
			case 'number':
				printf(
					'<input type="' . $args['type'] . '" id="' . $args['id'] . '" name="' . $args['name'] . '" min="' . $args['min'] . '" max="' . $args['max'] . '" step="' . $args['step'] . '" value="%s"/>',
					get_option($args['id']) ? esc_attr(get_option($args['id'])) : ''
				);
				break;
			case 'select':
				$option = get_option($args['id']);
				$items  = $args['options'];
				echo sprintf('<select id="%s" name="%s">', esc_attr($args['id']), esc_attr($args['id']));
				foreach ($items as $key => $item) {
					$selected = ($option == $key) ? 'selected' : '';
					echo sprintf("<option value='%s' %s>%s</option>", esc_attr($key), esc_attr($selected), esc_attr($item));
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
	public function enqueue_styles(): void
	{
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/apisunat-admin.css', array(), $this->version);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void
	{
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/apisunat-admin.js', array('jquery'), $this->version, true);
		wp_localize_script($this->plugin_name, 'apisunat_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
	}

	/**
	 * Show button
	 *
	 * @param null $id Order id.
	 * @param null $status Order status.
	 * @return void
	 */
	public function boton_emitir($id = null, $status = null): void
	{
		$disabled = $status === 'completed' ? '' : 'disabled';
		echo sprintf('<button id="%s" apistatus="%s" class="button-primary emit_button" %s>Emitir CPE</button> ', esc_attr($id), esc_attr($status), esc_attr($disabled),);
		echo sprintf(
			'<div id="apisunatLoading%s" class="mt-3 mx-auto" style="display:none;">
                        <img src="images/loading.gif" alt="loading"/>
                    </div>',
			esc_attr($id)
		);
	}

	/**
	 * Prepare payload data
	 *
	 * @return array
	 */
	public function build_send_data(): array
	{
		$send_data                                = array();
		$send_data['plugin_data']['personaId']    = get_option('apisunat_personal_id');
		$send_data['plugin_data']['personaToken'] = get_option('apisunat_personal_token');
		$send_data['plugin_data']['noDocId'] = get_option('apisunat_no_doc');

		$send_data['plugin_data']['serie01']       = get_option('apisunat_serie_factura');
		$send_data['plugin_data']['serie03']       = get_option('apisunat_serie_boleta');
		$send_data['plugin_data']['affectation']   = get_option('apisunat_tipo_tributo');
		$send_data['plugin_data']['affectation_factor']   = get_option('apisunat_factor_tributo');
		$send_data['plugin_data']['issueTime']     = get_option('apisunat_include_time');
		$send_data['plugin_data']['shipping_cost'] = get_option('apisunat_shipping_cost');

		$send_data['plugin_data']['debug']            = get_option('apisunat_debug_mode');
		$send_data['plugin_data']['custom_meta_data'] = get_option('apisunat_custom_checkout');

		return $send_data;
	}

	/**
	 * Prepare order meta data.
	 *
	 * @return array
	 */
	public static function build_meta_data_mapping(): array
	{

		if (get_option('apisunat_custom_checkout') === 'false') {
			return self::META_DATA_MAPPING;
		}

		$_document_type          = get_option('apisunat_key_tipo_comprobante');
		$_document_type_value_01 = get_option('apisunat_key_value_factura');
		$_document_type_value_03 = get_option('apisunat_key_value_boleta');

		$meta_data_mapping['_billing_apisunat_document_type'] = array(
			'key'      => $_document_type,
			'value_01' => $_document_type_value_01,
			'value_03' => $_document_type_value_03,
		);

		$_customer_id_type         = get_option('apisunat_key_tipo_documento');
		$_customer_id_type_value_1 = get_option('apisunat_key_value_dni');
		$_customer_id_type_value_6 = get_option('apisunat_key_value_ruc');
		$_customer_id_type_value_7 = get_option('apisunat_key_value_pasaporte');
		$_customer_id_type_value_b = get_option('apisunat_key_value_otros_extranjero');

		$meta_data_mapping['_billing_apisunat_customer_id_type'] = array(
			'key'     => $_customer_id_type,
			'value_1' => $_customer_id_type_value_1,
			'value_6' => $_customer_id_type_value_6,
			'value_7' => $_customer_id_type_value_7,
			'value_B' => $_customer_id_type_value_b,
		);

		$_apisunat_customer_id = get_option('apisunat_key_numero_documento');

		$meta_data_mapping['_billing_apisunat_customer_id'] = array(
			'key' => $_apisunat_customer_id,
		);

		return $meta_data_mapping;
	}

	/**
	 * Split filename and get number.
	 *
	 * @param string $filename fileName.
	 * @return string
	 */
	public function split_bills_numbers(string $filename): string
	{
		$split_filename = explode('-', $filename);
		return $split_filename[2] . '-' . $split_filename[3];
	}
}

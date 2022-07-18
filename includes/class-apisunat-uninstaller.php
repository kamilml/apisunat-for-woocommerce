<?php

class Apisunat_Uninstaller {

	/**
	 * Unninstall plugin.
	 *
	 * @since    1.0.0
	 */
	public static function uninstall(): void
    {

        delete_option('apisunat_ruc');
        delete_option('apisunat_personal_id');
        delete_option('apisunat_personal_token');
        delete_option('apisunat_company_name');
        delete_option('apisunat_company_address');
        delete_option('apisunat_serie_factura');
        delete_option('apisunat_serie_boleta');

	}

}

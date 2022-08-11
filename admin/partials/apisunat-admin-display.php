<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://apisunat.com/
 * @since      1.0.0
 *
 * @package    Apisunat
 * @subpackage Apisunat/admin/partials
 */

?>

<div class="wrap">
	<h1>APISUNAT - Facturación Electrónica</h1>
	<hr>

	<?php settings_errors(); ?>

	<form method="POST" action="options.php">
		<?php
			do_settings_sections( 'apisunat_general_settings' );
			settings_fields( 'apisunat_general_settings' );
		?>
		<?php submit_button(); ?>
	</form>
</div>

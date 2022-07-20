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
	<img src="<?php echo esc_attr( plugin_dir_url( __FILE__ ) ); ?>logoMin.png" height="40" width="40" alt="apisunat">

	<h2>APISUNAT</h2>
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

<?php

// Provide a admin area view for the plugin

?>

<div class="wrap">
    <img src="<?php echo plugin_dir_url(__FILE__); ?>logoMin.png" height="40" width="40">

    <h2>APISUNAT</h2>
    <hr>

    <?php settings_errors(); ?>

    <form method="POST" action="options.php">
        <?php
            do_settings_sections('apisunat_general_settings');
            settings_fields('apisunat_general_settings');
        ?>
        <?php submit_button(); ?>
    </form>
</div>

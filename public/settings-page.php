<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form action="options.php" method="post">
        <?php
        // output security fields for the registered setting "wpfa_settings"
        settings_fields( 'wpfa_settings_group' );
        // output setting sections and fields
        do_settings_sections( 'wpfa_settings' );
        // output save settings button
        submit_button( 'Save Settings' );
        ?>
    </form>
</div>
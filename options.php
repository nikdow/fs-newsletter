<?php
/*
 * Signatures options menu item and options page
 */
add_action( 'admin_menu', 'cbdweb_newsletter_menu' );

/** Step 1. */
function cbdweb_newsletter_menu() {
        add_submenu_page( 'edit.php?post_type=fs_newsletter', 'Newsletter Options', 'Options', 'manage_options', basename(__FILE__), 'cbdweb_newsletter_options' );
}

/** Step 3. */
function cbdweb_newsletter_options() {
        if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

            // variables for the field and option names 
            $hidden_field_name = 'CBDWeb_submit_hidden';
            $options_array = array ( 
                array('opt_name'=>'fs_newsletter_sender-name', 'data_field_name'=>'cbdweb_newsletter_sender-name', 
                    'opt_label'=>'Newsletter sender (common name)', 'field_type'=>'text'),
                array('opt_name'=>'fs_newsletter_sender-address', 'data_field_name'=>'cbdweb_newsletter_sender-address', 
                    'opt_label'=>'Newsletter sender (email address)', 'field_type'=>'email'),
                array('opt_name'=>'fs_newsletter_template', 'data_field_name'=>'cbdweb_newsletter_template',
                    'opt_label'=>"HTML template for Newsletters", 'field_type'=>'textarea' ),
            );

            // See if the user has posted us some information
            // If they did, this hidden field will be set to 'Y'
            if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {

                foreach ($options_array as $option_array ) {
                    
                    // Read their posted value
                    $opt_val = stripslashes_deep ( $_POST[ $option_array['data_field_name'] ] );

                    // Save the posted value in the database
                    update_option( $option_array ['opt_name'], $opt_val );
                }

                // Put a settings updated message on the screen

                ?>
                <div class="updated"><p><strong><?php _e('settings saved.' ); ?></strong></p></div>
            <?php }

            // Now display the settings editing screen
            ?>
            <div class="wrap">

            <h2>Newsletter Settings</h2>

            <form name="cbdweb_newsletter_options" id="cbdweb_newsletter_options" method="post" action="">
                <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

                <?php 
                foreach ( $options_array as $option_array ) { 
                    // Read in existing option value from database
                    $opt_val = get_option( $option_array[ 'opt_name' ] );
                    ?>
                    <p><?php _e( $option_array[ 'opt_label' ] );
                        if($option_array[ 'field_type' ] === 'textarea' ) { ?>
                            <textarea name="<?php echo $option_array[ 'data_field_name' ]; ?>"><?php echo $opt_val; ?></textarea>
                        <?php } else { ?>
                            <input type="<?=$option_array[ 'field_type' ]?>" name="<?=$option_array[ 'data_field_name' ]?>" value="<?=$opt_val?>"/>
                        <?php } ?>
                    </p>
                <?php } ?>
                <hr />

                <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
                </p>

            </form>
        </div>
    <?php
}

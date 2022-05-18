<?php
/**
 * Plugin Name: Freestyle Cyclists Newsletter
 * Plugin URI: http://www.cbdweb.net
 * Description: Send email to paid members or petition signatures
 * Version: 1.0
 * Author: Nik Dow, CBDWeb
 * License: GPL2
 */
require_once plugin_dir_path ( __FILE__ ) . 'options.php';
/*
 * Newsletters
 */
function fs_newsletter_enqueue_scripts(  ) { 
    global $post;
    if( $post->post_type !== 'fs_newsletter' ) return;
    wp_register_script( 'angular', "//ajax.googleapis.com/ajax/libs/angularjs/1.2.18/angular.min.js", 'jquery' );
    wp_enqueue_script('angular');
    wp_register_script('newsletter-admin', plugins_url( 'js/newsletter-admin.js' , __FILE__ ), array('jquery', 'angular') );
    wp_localize_script( 'newsletter-admin', '_main',
        array( 'post_url' => admin_url('post.php'),
               'ajax_url' => admin_url('admin-ajax.php'),
        ) 
    );
    wp_enqueue_script( 'newsletter-admin' );
    wp_enqueue_style('newsletter_style', plugins_url( 'css/admin-style.css' , __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'fs_newsletter_enqueue_scripts' );

add_action( 'init', 'create_fs_newsletter' );
function create_fs_newsletter() {
    $labels = array(
        'name' => _x('Newsletters', 'post type general name'),
        'singular_name' => _x('Newsletter', 'post type singular name'),
        'add_new' => _x('Add New', 'events'),
        'add_new_item' => __('Add New Newsletter'),
        'edit_item' => __('Edit Newsletter'),
        'new_item' => __('New Newsletter'),
        'view_item' => __('View Newsletter'),
        'search_items' => __('Search Newsletter'),
        'not_found' =>  __('No newsletters found'),
        'not_found_in_trash' => __('No newsletters found in Trash'),
        'parent_item_colon' => '',
    );
    register_post_type( 'fs_newsletter',
        array(
            'label'=>__('Newsletters'),
            'labels' => $labels,
            'description' => 'Each post is one newsletter.',
            'public' => true,
            'can_export' => true,
            'exclude_from_search' => false,
            'has_archive' => true,
            'show_ui' => true,
            'capability_type' => 'post',
            'menu_icon' => "dashicons-megaphone",
            'hierarchical' => false,
            'rewrite' => false,
            'supports'=> array('title', 'editor' ) ,
            'show_in_nav_menus' => true,
        )
    );
}
/*
 * specify columns in admin view of signatures custom post listing
 */
add_filter ( "manage_edit-fs_newsletter_columns", "fs_newsletter_edit_columns" );
add_action ( "manage_posts_custom_column", "fs_newsletter_custom_columns" );
function fs_newsletter_edit_columns($columns) {
    $columns = array(
        "cb" => "<input type=\"checkbox\" />",
        "title" => "Subject",
        "fs_col_post_type" => "Subscriber type",
        "fs_col_newsletter_type" => "Newsletter type",
        "fs_newsletter_country" => "Country",
        "fs_newsletter_state" => "State",
    );
    return $columns;
}
function fs_newsletter_custom_columns($column) {
    global $post;
    $custom = get_post_custom();
    $newsletter_type = get_post_meta( $post->ID, 'fs_newsletter_newsletter_type' );
    $post_type = $custom["fs_newsletter_post_type"][0];
    $member_active = $custom["fs_newsletter_active"][0] === "y";
    $member_expired = $custom["fs_newsletter_expired"][0] === "n";
    $state = get_post_meta ( $post->ID, "fs_newsletter_state" );
    switch ( $column ) {
        case "title":
            echo $post->post_title;
            break;
        case "fs_col_post_type":
            echo $post_type;
            break;
        case "fs_col_newsletter_type":
            echo $post_type === "members" || ! is_array( $newsletter_type[0] ) ? "&nbsp;" : implode ( ', ', $newsletter_type[0] );
            break;
        case "fs_newsletter_country":
            echo $post_type === "members" ? "&nbsp;" : $custom["fs_newsletter_country"][0];
            break;
        case "fs_newsletter_state":
            echo $post_type === "members" ? "&nbsp;" : 
                    ( $custom["fs_newsletter_country"][0]!=="AU" || ! is_array( $state[0] ) ? '&nbsp;' : implode ( ', ', $state[0] ) );
            break;
    }
}
/*
 * Add fields for admin to edit signature custom post
 */
add_action( 'admin_init', 'fs_newsletter_create' );
function fs_newsletter_create() {
    add_meta_box('fs_newsletter_meta', 'Newsletter', 'fs_newsletter_meta', 'fs_newsletter' );
}
function fs_newsletter_meta() {
    global $post;
    $custom = get_post_custom( $post->ID );
    $meta_country = $custom['fs_newsletter_country'][0];
    $meta_state = get_post_meta( $post->ID, 'fs_newsletter_state' ); // checkboxes stored as arrays
    $meta_post_type = $custom['fs_newsletter_post_type'][0]; 
    $meta_member_active = $custom["fs_newsletter_active"][0];
    $meta_member_expired = $custom["fs_newsletter_expired"][0];
    $meta_newsletter = get_post_meta ( $post->ID, 'fs_newsletter_newsletter_type' );
    
    echo '<input type="hidden" name="fs-newsletter-nonce" id="fs-newsletter-nonce" value="' .
        wp_create_nonce( 'fs-newsletter-nonce' ) . '" />';
    $fs_states = fs_states();
    $ngStates = array();
    foreach($fs_states as $ab => $title ) {
        $ngStates[$ab] = is_array( $meta_state[0] ) && in_array( $ab, $meta_state[0] );
    }
    $ngNewsletter = array();
    foreach ( array('y', 'm') as $ab ) {
        $ngNewsletter[$ab] = is_array( $meta_newsletter[0] ) && in_array ( $ab, $meta_newsletter[0] );
    }
    ?>
    <script type="text/javascript">
        _data = <?=  json_encode( array ( 
            'postType'=>$meta_post_type,
            'member_active' => $custom["fs_newsletter_active"][0] === "y",
            'member_expired' => $custom["fs_newsletter_expired"][0] === "y",
            'country'=>( $meta_country ? $meta_country : "" ),
            'states'=>$ngStates,
            'newsletter'=>$ngNewsletter
        ) ) ?>;
    </script>
    <div class="fs-meta" ng-app="newsletterAdmin" ng-controller="newsletterAdminCtrl">
        <table><tr valign="top"><td width="60%">
            <ul>
                <li><label>Send to which group?</label>
                    <select ng-model="data.postType" name="fs_newsletter_post_type">
                        <option value="">Please select</option>
                        <option value="members">Members</option>
                        <option value="signatures">Signatures</option>
                    </select>
                </li>
                <li ng-hide="data.postType==='signatures'"><label>Financial</label>
                    <input ng-model="data.member_active" name="fs_newsletter_active" type="checkbox" value="y" />
                </li>
                <li ng-hide="data.postType==='signatures'"><label>Expired</label>
                    <input ng-model="data.member_expired" name="fs_newsletter_expired" type="checkbox" value="y" />
                </li>
                <li ng-show="data.postType==='signatures'"><label>Newsletter level</label>
                    <input ng-model="data.newsletter.y" name="fs_newsletter_newsletter_type[]" type="checkbox" value="y" />Occasional 
                </li>
                <li ng-show="data.postType==='signatures'">
                    <label>&nbsp;</label>
                    <input ng-model="data.newsletter.m" name="fs_newsletter_newsletter_type[]" type="checkbox" value="m" />Frequent
                </li>
                <li ng-show="data.postType==='signatures'"><label>Country</label>
                    <select ng-model="data.country" name="fs_newsletter_country">
                        <option value="all">All</option>
                        <?php 
                        $fs_country = fs_country();
                        foreach($fs_country as $ab => $title ) { ?>
                            <option value="<?=$ab;?>"><?php echo $title;?></option>
                        <?php } ?>
                    </select>
                </li>
                <li ng-show="data.postType==='signatures' && country==='AU'"><label>State</label>
                </li>
                <?php 
                foreach($fs_states as $ab => $title ) { ?>
                    <li ng-show="data.postType==='signatures' && data.country==='AU'"><label>&nbsp;</label>
                        <input ng-model="data.states.<?=$ab;?>" name="fs_newsletter_state[]" type="checkbox" value="<?=$ab;?>" />
                        <?php echo $title;?>
                    </li>
                <?php } ?>
            </ul>
        </td>
        <td align="left" width="40%" style='padding-left: 20px;'>
            <ul>
                <li>Test addresses (leave blank to send bulk):</li>
                <li><input class='wide' name='fs_newsletter_test_addresses'/></li>
                <li><button type="button" ng-click="sendNewsletter()">Send newsletter</button></li>
                <li ng-show="showLoading"><img src="<?php echo get_site_url();?>/wp-includes/js/thickbox/loadingAnimation.gif"></li>
                <li ng-show='showProgressNumber'>
                    {{email.count}} sent of {{email.total}}
                </li>
                <li ng-show='showProgressMessage'>
                    {{email.message}}
                </li>
            </ul>
        </td></tr>
    </table>
    <input name='ajax_id' value="<?=$post->ID?>" type="hidden" />
    <?=wp_nonce_field( 'fs_sendNewsletter', 'fs-sendNewsletter', false, false );?>
    <input name='fs_newsletter_send_newsletter' ng-model="fs_newsletter_send_newsletter" type='hidden' />
    <?php    
}

add_action ('save_post', 'save_fs_newsletter');
 
function save_fs_newsletter(){
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    global $post;
    
    if( 'fs_newsletter' === $_POST['post_type'] ) {

    // - still require nonce

        if ( !wp_verify_nonce( $_POST['fs-newsletter-nonce'], 'fs-newsletter-nonce' )) {
            return $post->ID;
        }

        if ( !current_user_can( 'edit_post', $post->ID ))
            return $post->ID;

        // - convert back to unix & update post

        update_post_meta($post->ID, "fs_newsletter_country", $_POST["fs_newsletter_country"] );
        update_post_meta($post->ID, "fs_newsletter_state", $_POST["fs_newsletter_state"] ); // is an array of states
        update_post_meta($post->ID, "fs_newsletter_newsletter_type", $_POST["fs_newsletter_newsletter_type"] ); // is an array of newsletter preference types
        update_post_meta($post->ID, "fs_newsletter_post_type", $_POST["fs_newsletter_post_type"] );
        update_post_meta($post->ID, "fs_newsletter_active", 
            ( isset ( $_POST["fs_newsletter_active"] ) && $_POST["fs_newsletter_active"]==="y" ) ? "y" : "n" );
        update_post_meta($post->ID, "fs_newsletter_expired", 
            ( isset ( $_POST["fs_newsletter_expired"] ) && $_POST["fs_newsletter_expired"]==="y") ? "y" : "n" );
        if( isset( $_POST['fs_newsletter_send_newsletter'] ) && $_POST[ 'fs_newsletter_send_newsletter' ] === '1' ) {
            
            /* try to prevent WP from sending text/plain */
            add_filter( 'wp_mail_content_type', 'set_content_type' );
            function set_content_type( $content_type ){
                    return 'text/html';
            }
            $sendTo = array();
            $test_addresses = $_POST['fs_newsletter_test_addresses'];
            session_write_close (); // avoid session locking blocking progess ajax calls
            update_post_meta($post->ID, "fs_newsletter_progress", json_encode( array ( 'count'=>0, 'total'=>Count( $sendTo ), 'message'=>'querying the database' ) ) );
                    
            if( $test_addresses !== "" ) {
                $addressArray = explode(",", $test_addresses );
                foreach ( $addressArray as $address ) {
                    $sendTo[] = (object) array("name"=>"", "email"=>trim( $address ) );
                }
                
            } else {
               
                $post_type_requested = $_POST["fs_newsletter_post_type"];

                global $wpdb;
                switch ( $post_type_requested ) {
                    case "members":
                        $fs_newsletter_active = isset ( $_POST["fs_newsletter_active"] ) ?
                            $_POST["fs_newsletter_active"]==="y" : false; 
                        $fs_newsletter_expired = isset ( $_POST["fs_newsletter_expired"] ) ?
                            $_POST["fs_newsletter_expired"]==="y" : false;
                        if ( ! ( $fs_newsletter_active || $fs_newsletter_expired ) ) {
                            echo json_encode( array ( "success"=>"no emails sent, no option selected") );
                            die;
                        }
                        $query = 
                            "SELECT u.user_email as email, CONCAT( umf.meta_value, \" \", uml.meta_value ) as name FROM " . $wpdb->users . 
                            " u LEFT JOIN " . $wpdb->usermeta . " ums ON ums.user_id=u.ID AND ums.meta_key='wpfreepp_user_level'" .
                            " LEFT JOIN " . $wpdb->usermeta . " umf ON umf.user_id=u.ID AND umf.meta_key='first_name'" .
                            " LEFT JOIN " . $wpdb->usermeta . " uml ON uml.user_id=u.ID AND uml.meta_key='last_name'" .
                            " LEFT JOIN " . $wpdb->pmpro_memberships_users . " pmpro ON pmpro.user_id=u.ID AND pmpro.status='active'" .
                            " WHERE ums.meta_value=0 AND ums.meta_value IS NOT NULL AND user_email != ''"; // check email empty since fs-signatures plugin allows admin edit to remove email address
                        if ( $fs_newsletter_active !== $fs_newsletter_expired ) {
                            if ( $fs_newsletter_active ) $query .= " AND pmpro.status IS NOT NULL";
                            if ( $fs_newsletter_expired ) $query .= " AND pmpro.status IS NULL";
                        }
                        $sendTo = $wpdb->get_results ( $query );
                        break;
                    case "signatures":
                        $newsletter_type = get_post_meta( $post->ID, 'fs_newsletter_newsletter_type' );
                        $query_in = implode ( '", "', $newsletter_type[0] );
                        $meta_state = get_post_meta( $post->ID, 'fs_newsletter_state' );
                        $state_in = implode ( '", "', $meta_state[0] );
                        $country = $_POST["fs_newsletter_country"];

                        $query = $wpdb->prepare ( 
                            "SELECT p.post_title as name, pme.meta_value as email "
                            . "FROM " . $wpdb->posts . " p" .
                            " LEFT JOIN " . $wpdb->postmeta . " pmc ON pmc.post_id=p.ID AND pmc.meta_key='fs_signature_country'" . 
                            " LEFT JOIN " . $wpdb->postmeta . " pms ON pms.post_id=p.ID AND pms.meta_key='fs_signature_state'" .
                            " LEFT JOIN " . $wpdb->postmeta . " pmn ON pmn.post_id=p.ID AND pmn.meta_key='fs_signature_newsletter'" .
                            " LEFT JOIN " . $wpdb->postmeta . " pme ON pme.post_id=p.ID AND pme.meta_key='fs_signature_email'" .
                            " WHERE p.post_type='fs_signature' AND p.`post_status`='private' AND " .
                            "pmn.meta_value in (\"" . $query_in . "\")" .
                            ($country==="AU" ? " AND pms.meta_value in (\"" . $state_in . "\")" : "") .
                            ($country === "all" ? "" : " AND pmc.meta_value=%s"),
                            $country );
                        $sendTo = $wpdb->get_results ( $query );
                        break;
                }
            }
            update_option('fs_newsletter_query', $query );
            $testing = NEWSLETTER_TEST; // true on dev computer - not the same as test addresses from UI
            $count =0;
            foreach ( $sendTo as $one ) {
                $email = $one->email;
                if ( $testing ) $email = "nik@cbdweb.net";
                $subject = $post->post_title;
                if ( $testing ) $subject .= " - " . $one->email;
                $headers = array();
                $headers[] = 'From: ' . get_option('fs_newsletter_sender-name') . " <" . get_option('fs_newsletter_sender-address') . '>';
                $headers[] = "Content-type: text/html";
                $message = $post->post_content;
                $message = str_replace("\r\n", "<br>\r\n", $message );
                wp_mail( $email, $subject, $message, $headers );
                $count++;
                update_post_meta($post->ID, "fs_newsletter_progress", json_encode( array ( 
                    'count'=>$count, 'total'=>Count( $sendTo ),
                    'message'=>'last email sent: ' . $email,
                ) ) );
                if ( $testing && $count > 5 ) break;
            }
            echo json_encode( array ( "success"=>"completed: " . $count . " emails" ) );
            die;
        }
    }
}

add_action( 'wp_ajax_fs_newsletter_progress', 'fs_newsletter_progress' );
function fs_newsletter_progress() {
    $post_id = $_POST['post_id'];
    echo get_post_meta( $post_id, 'fs_newsletter_progress', true );
    die;
}

add_filter('post_updated_messages', 'fs_newsletter_updated_messages');
 
function fs_newsletter_updated_messages( $messages ) {
 
  global $post, $post_ID;
 
  $messages['fs_newsletter'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('Newsletter updated. <a href="%s">View item</a>'), esc_url( get_permalink($post_ID) ) ),
    2 => __('Custom field updated.'),
    3 => __('Custom field deleted.'),
    4 => __('Newsletter updated.'),
    /* translators: %s: date and time of the revision */
    5 => isset($_GET['revision']) ? sprintf( __('Newsletter restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('Newsletter published. <a href="%s">View Newsletter</a>'), esc_url( get_permalink($post_ID) ) ),
    7 => __('Newsletter saved.'),
    8 => sprintf( __('Newsletter submitted. <a target="_blank" href="%s">Preview newsletter</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
    9 => sprintf( __('Newsletter scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview newsletter</a>'),
      // translators: Publish box date format, see http://php.net/date
      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
    10 => sprintf( __('Newsletter draft updated. <a target="_blank" href="%s">Preview newsletter</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  );
 
  return $messages;
}
/*
 * label for title field on custom posts
 */

add_filter('enter_title_here', 'fs_newsletter_enter_title');
function fs_newsletter_enter_title( $input ) {
    global $post_type;

    if ( 'fs_newsletter' === $post_type ) {
        return __( 'Newsletter (email) subject' );
    }
    return $input;
}

add_filter( 'default_content', 'fs_newsletter_content', 10, 2 );

function fs_newsletter_content( $content, $post ) {

    if( $post->post_type !== "fs_newsletter") {
        return $content;
    }
 
    $template = get_option( 'fs_newsletter_template' );
    
    $today = date( get_option('time_format') );
    $unsubscribe = add_query_arg ( "email", "%email%", get_permalink( get_page_by_title( 'Unsubscribe' ) ) );
    
    $content = str_replace( 
                array( '{today}',
                    '{unsubscribe}',
                    ),
                array( $today,
                    $unsubscribe,
                    ),
                $template );
    return $content;
}
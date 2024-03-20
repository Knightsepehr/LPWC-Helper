<?php
defined( 'ABSPATH' ) || exit( 'No direct script access allowed' );
/** 
 * Plugin Name: LP WC Helper
 * Plugin URI: https://github.com/Knightsepehr/LPWC-Helper/
 * Description: The LP WC Helper WordPress plugin serves as a tool for facilitating the integration between LearnPress and Woocommerce. By introducing a custom field onto the product add/edit page, this plugin enables users to efficiently explore and uncover pertinent details related to LearnPress courses.
 * Version: 1.1
 * Author: SepehrZekavat
 * Author URI: https://SepehrZekavat.ir
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 * Text Domain: lpwchelper
 * Domain Path: /languages
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 3, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

// Load plugin text domain
add_action('plugins_loaded', 'load_my_plugin_textdomain');
function load_my_plugin_textdomain() {
    load_plugin_textdomain('lpwchelper', false, dirname(plugin_basename(__FILE__)) . '/languages');
}


add_action('woocommerce_product_options_general_product_data', 'add_custom_field');
function add_custom_field() {
    global $woocommerce, $post;
    echo '<div class="options_group">';
    woocommerce_wp_text_input(
        array(
            'id' => '_custom_field',
            'label' => __('Course Detail', 'lpwchelper'),
            'desc_tip' => 'true',
            'description' => __('Course ID, Name(slug) or title. ', 'lpwchelper')
        )
    );
    // Select field
    woocommerce_wp_select(
        array(
            'id' => '_custom_select',
            'label' => __('Search By', 'lpwchelper'),
            'desc_tip' => 'true',
            'description' => __('Parameter to search by, with In title you can enter a part of the title', 'lpwchelper'),
            'options' => array(
                'post_name' => __('Slug', 'lpwchelper'),
                'post_title' => __('Title', 'lpwchelper'),
                'ID' => __('Post id', 'lpwchelper'),
                'search' => __('In Title', 'lpwchelper'),
            )
        )
    );
    echo '</div>';
}

// Add a custom button to the product edit page
add_action('woocommerce_product_options_general_product_data', 'add_custom_button');
function add_custom_button() {
    $output = "";
    $output .= '<div class="options_group">';
    $output .= '<button id="custom-button" type="button">Sumbit</button>';
    $output .= '<div id="vscroll">';
    $output .= '</div>';
    $output .= '</div>';
    echo $output;
}

// Add JavaScript for the button click event
add_action('admin_footer', 'custom_button_script');
function custom_button_script() {
    $nonce = wp_create_nonce('my_custom_action_nonce');
    echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            $("#custom-button").click(function() {
                
                // Get the value of the field and the selector
                var fieldValue = $("#_custom_field").val();
                var selectValue = $("#_custom_select").val();

                // Check if the field value is empty
                if (fieldValue.length === 0) {
                    $( "#_custom_field" ).focus();
                    $( "#_custom_field" ).css({ "border": "1px solid red" });
                    setTimeout(console.log("waited 1s"), 1000);
                    $( "#_custom_field" ).css({ "border": "1px solid #8c8f94 " });
                    return;
                }

                var data = {
                    "action": "my_custom_action",
                    "field_value": fieldValue,
                    "select_value": selectValue,
                    "nonce": "' . $nonce . '",
                };
                $("#custom-button").prop("disabled", true);
                $.post(ajaxurl, data, function(response) {
                    $("#vscroll").prepend("<p>" + response + "</p>");
                }).done(function() {
                    setTimeout(
                        function(){
                            $("#custom-button").prop("disabled", false); 
                    
                    }, 2000);

                }).fail(function() {
                    alert("Sorry, there was an error trying to send the request!");
                    setTimeout(function(){$("#custom-button").prop("disabled", false); }, 2000);
                });
            });
        });
    </script>';
}


// Handle the AJAX request
add_action('wp_ajax_my_custom_action', 'handle_custom_action');
function handle_custom_action() {
    check_ajax_referer('my_custom_action_nonce', 'nonce');

    if (!current_user_can('edit_products')) {
        wp_die('
        <div class="alert">
          <strong>Invalid!</strong> You do not have permission to execute this request.
        </div>
        ');
    }
    // Get the value of the field
    $options = ["post_title","post_name","ID","search"];
    $fieldValue = sanitize_text_field($_POST['field_value']);
    $selectValue = sanitize_text_field($_POST['select_value']);

    if (!(in_array($selectValue,$options))){
                    wp_die('
            <div class="alert">
              <strong>Invalid!</strong> This select value is NOT supported.
            </div>
            ');
    }
    
    // Use get_posts to retrieve posts

     if (in_array($selectValue,["ID"])){
         $args = array(
             'post__in' => [intval($fieldValue)],
             'post_type'   => 'lp_course',
             'post_status' => 'publish',
             'numberposts' => 1
         );
     }elseif (in_array($selectValue,["post_name"])){
         $args = array(
             'name' => $fieldValue,
             'post_type'   => 'lp_course',
             'post_status' => 'publish',
             'numberposts' => 1
         );
     }elseif (in_array($selectValue,["post_title"])){
         $args = array(
             'title' => $fieldValue,
             'post_type'   => 'lp_course',
             'post_status' => 'publish',
             'numberposts' => 1
         );
     }elseif (in_array($selectValue,["search"])){
         $args = array(
             's' => $fieldValue,
             'post_type'   => 'lp_course',
             'post_status' => 'publish',
             'numberposts' => 1
         );
     }
     $posts = get_posts($args);
     if (!$posts) {
        wp_die('<div class="alert"><strong>Oopsies!</strong> No courses found.</div>');
    }
    echo '<div class="success">
        <strong>Get Request Successful!</strong> 
    </div>';

    // echo json_encode($posts, JSON_PRETTY_PRINT);
    // Start the table
    $output = '<div class="table-wrapper">';
    $output .= '<table class="fl-table">';
    $output .= '<thead>';
    $output .= '<tr>';
    $output .= '<th>Course PID</th>';
    $output .= '<th>Name/Slug</th>';
    $output .= '<th>Title</th>';
    $output .= '<th>Status</th>';
    $output .= '<th>Comment Status</th>';
    $output .= '<th>Last Modified</th>';
    $output .= '<th>Author ID</th>';
    $output .= '</tr>';
    $output .= '</thead>';
    $output .= '<tbody>';
    // Output each post as a row in the table
    foreach ($posts as $post) {
        $output .= '<tr>';
        $output .= '<td>' . $post->ID . '</td>';
        $output .= '<td>' . $post->post_name . '</td>';
        $output .= '<td>' . $post->post_title . '</td>';
        $output .= '<td>' . $post->post_status . '</td>';
        $output .= '<td>' . $post->comment_status . '</td>';
        $output .= '<td>' . $post->post_modified . '<pr> GMT </pre> </td>';
        $output .= '<td>' . $post->post_author . '</td>';
        $output .= '</tr>';
    }
    $output .= '</tbody>';
    $output .= '</table>';
    $output .= '<p class="cExcerpt">Course  Excerpt :</p>';
    $output .= '<textarea readonly class="pcontent" name="pcontent">';
    $output .= (!empty($post->post_excerpt) ? $post->post_excerpt : 'No Excerpt');
    $output .= '</textarea>';
    $output .= '<p class="cdesc">Course Description :</p>';
    $output .= '<textarea readonly class="pcontent" name="pcontent">';
    $output .= (!empty($post->post_content) ? $post->post_content : 'No Description');
    $output .= '</textarea>';
    $output .= '</div>';
    echo $output;
    wp_die(); // This is required to terminate immediately and return a proper response
}
function enqueue_plugin_css( $hook ) {
    if ( 'edit.php' == $hook | 'post-new.php' == $hook | 'post.php' == $hook) {
        wp_enqueue_style( 'lpwchelper-style', plugin_dir_url( __FILE__ ) . 'assets/style.css', array(), '1.0' );
    } else{
        return;
    }
}
add_action( 'admin_enqueue_scripts', 'enqueue_plugin_css' );
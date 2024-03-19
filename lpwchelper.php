<?php
defined( 'ABSPATH' ) || exit( 'No direct script access allowed' );
/*
Plugin Name: LP WC Helper
Description: LearnPress Woocommerce integration helper , this plugin adds a field to woocommerce product add/edit page that allows searching for LearnPress Courses and their details   
Version: 1.0
Author: MeowMeowKhan
Text Domain: lpwchelperr
Domain Path: /languages
*/
global $wpdb;

// Load plugin text domain
add_action('plugins_loaded', 'load_my_plugin_textdomain');
function load_my_plugin_textdomain() {
    load_plugin_textdomain('lpwchelperr', false, dirname(plugin_basename(__FILE__)) . '/languages');
}


add_action('woocommerce_product_options_general_product_data', 'add_custom_field');
function add_custom_field() {
    global $woocommerce, $post;
    echo '<div class="options_group">';
    woocommerce_wp_text_input(
        array(
            'id' => '_custom_field',
            'label' => __('Course Detail', 'lpwchelperr'),
            'desc_tip' => 'true',
            'description' => __('Enter your Course name or title/slug/id here.', 'lpwchelperr')
        )
    );
    // Select field
    woocommerce_wp_select(
        array(
            'id' => '_custom_select',
            'label' => __('Search By', 'lpwchelperr'),
            'options' => array(
                'post_name' => __('Slug', 'lpwchelperr'),
                'post_title' => __('Title', 'lpwchelperr'),
                'ID' => __('Post id', 'lpwchelperr'),
                'search' => __('Search', 'lpwchelperr'),
            )
        )
    );
    // Checkbox
    // woocommerce_wp_checkbox(
    //     array(
    //         'id' => '_custom_checkbox',
    //         'label' => __('Use SQL Query', 'lpwchelperr')
    //     )
    // );
    echo '</div>';
}

// Save the custom field value
// add_action('woocommerce_process_product_meta', 'save_custom_field');
// function save_custom_field($post_id) {
//     $custom_field_value = $_POST['_custom_field'];
//     $custom_select_value = $_POST['_custom_select'];
//     $custom_checkbox_value = isset($_POST['_custom_checkbox']) ? 'yes' : 'no';
//     if (!empty($custom_field_value)) {
//         update_post_meta($post_id, '_custom_field', esc_attr($custom_field_value));
//     }
//     if (!empty($custom_select_value)) {
//         update_post_meta($post_id, '_custom_select', esc_attr($custom_select_value));
//     }
//     update_post_meta($post_id, '_custom_checkbox', $custom_checkbox_value);
// }

// Add a custom button to the product edit page
add_action('woocommerce_product_options_general_product_data', 'add_custom_button');
function add_custom_button() {
    echo '<div class="options_group">';
    echo '<button id="custom-button" type="button">Sumbit</button>';
    echo '</div>';
}

// Add JavaScript for the button click event
add_action('admin_footer', 'custom_button_script');
function custom_button_script() {
    echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            $("#custom-button").click(function() {
                // Get the value of the field, the select, and the checkbox
                var fieldValue = $("#_custom_field").val();
                var selectValue = $("#_custom_select").val();
                var checkboxValue = $("#_custom_checkbox").is(":checked") ? "yes" : "no";
                // Perform your SQL query here
                // For example:
                var data = {
                    "action": "my_custom_action",
                    "field_value": fieldValue,
                    "select_value": selectValue,
                    "checkbox_value": checkboxValue
                };
                $.post(ajaxurl, data, function(response) {
                    // Display the response under the button
                    $("#custom-button").after("<p>" + response + "</p>");
                });
            });
        });
    </script>';
}

// Handle the AJAX request
add_action('wp_ajax_my_custom_action', 'handle_custom_action');
function handle_custom_action() {
    // Get the value of the field
    $options = ["post_title","post_name","ID","search"];
    $fieldValue = sanitize_text_field($_POST['field_value']);
    $selectValue = sanitize_text_field($_POST['select_value']);
    $checkboxValue = sanitize_text_field($_POST['checkbox_value']);
    
    // Prevent Stupidity from happening...
    if (!(in_array($selectValue,$options))){
                    wp_die('
            <div class="alert">
              <strong>Invalid!</strong> This select value is NOT supported.
            </div>
            ');
    }
    
    // Use get_posts to retrieve posts
    if ($checkboxValue === 'no') {
       echo '<div class="success">
          <strong>Get Request Successful!</strong> 
        </div>';
        if (in_array($selectValue,["ID"])){
            $args = array(
                'post__in' => [intval($fieldValue)],
                'post_type'   => 'lp_course',
                'post_status' => 'publish',
                'numberposts' => 1
            );
        }
        if (in_array($selectValue,["post_name"])){
            $args = array(
                'name' => $fieldValue,
                'post_type'   => 'lp_course',
                'post_status' => 'publish',
                'numberposts' => 1
            );
        }
        if (in_array($selectValue,["post_title"])){
            $args = array(
                'title' => $fieldValue,
                'post_type'   => 'lp_course',
                'post_status' => 'publish',
                'numberposts' => 1
            );
        }
        if (in_array($selectValue,["search"])){
            $args = array(
                's' => $fieldValue,
                'post_type'   => 'lp_course',
                'post_status' => 'publish',
                'numberposts' => 1
            );
        }
        $posts = get_posts($args);
    } else {
        echo '<div class="success">
              <strong>SQL Query Successful!</strong> 
            </div>';
        // Check if selectValue is either 'post_title' or 'post_name' (slug) to prevent SQL injection
        if ($selectValue !== 'post_title' && $selectValue !== 'post_name' && $selectValue !== 'ID') {
            wp_die('
            <div class="alert">
              <strong>Invalid!</strong> This value combination is NOT supported.
            </div>
            ');
        }
        $search_string = _real_escape(strtolower(trim(strip_tags($fieldValue)))); // Replace with your search string
        $search_string = '%' . $wpdb->esc_like($search_string) . '%'; // Adding the wildcards
        
        
        $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}posts WHERE post_type = 'lp_course' AND post_status = 'publish' AND {$selectValue} LIKE %s", $search_string);
        $posts = $wpdb->get_results($query);
        

    }

    // echo json_encode($posts, JSON_PRETTY_PRINT);
    // Start the table
    echo '<div class="table-wrapper">';
    echo '<table class="fl-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Course PID</th>';
    echo '<th>Name/Slug</th>';
    echo '<th>Title</th>';
    echo '<th>Status</th>';
    echo '<th>Comment Status</th>';
    echo '<th>Last Modified</th>';
    echo '<th>Author ID</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    // Output each post as a row in the table
    foreach ($posts as $post) {
        echo '<tr>';
        echo '<td>' . $post->ID . '</td>';
        echo '<td>' . $post->post_name . '</td>';
        echo '<td>' . $post->post_title . '</td>';
        echo '<td>' . $post->post_status . '</td>';
        echo '<td>' . $post->comment_status . '</td>';
        echo '<td>' . $post->post_modified . '<pr> GMT </pre> </td>';
        echo '<td>' . $post->post_author . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '<p class="cExcerpt">Course  Excerpt :</p>';
    echo '<textarea readonly class="pcontent" name="pcontent">';
    echo (!empty($post->post_excerpt) ? $post->post_excerpt : 'No Excerpt');
    echo '</textarea>';
    echo '<p class="cdesc">Course Description :</p>';
    echo '<textarea readonly class="pcontent" name="pcontent">';
    echo (!empty($post->post_content) ? $post->post_content : 'No Description');
    echo '</textarea>';
    echo '</div>';
    wp_die(); // This is required to terminate immediately and return a proper response
}
// Add CSS to the admin head
add_action('admin_head', 'add_custom_css');
function add_custom_css() {
    echo '<style>
    table *{
        box-sizing: border-box;
        -webkit-box-sizing: border-box;
        -moz-box-sizing: border-box;
    }
    table h2{
        text-align: center;
        font-size: 18px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: white;
        padding: 30px 0;
    }
    textarea {
      resize: none;
      width:100%;
    }
    /* Table Styles */

    .table-wrapper{
        margin: 10px 20px 20px;
        box-shadow: 0px 35px 50px rgba( 0, 0, 0, 0.2 );
    }
    .table-excerpt{
        max-width:20%;
    }
    .fl-table {
        border-radius: 5px;
        font-size: 12px;
        font-weight: normal;
        border: none;
        border-collapse: collapse;
        width: 100%;
        max-width: 100%;
        white-space: nowrap;
        background-color: white;
    }

    .fl-table td, .fl-table th,.cdesc {
        text-align: center;
        padding: 8px;
    }

    .fl-table td {
        border-right: 1px solid #f8f8f8;
        font-size: 12px;
    }

    .fl-table thead th,.cdesc {
        color: #ffffff;
        background: #4FC3A1;
    }


    .fl-table thead th:nth-child(odd) {
        color: #ffffff;
        background: #324960;
    }

    .fl-table tr:nth-child(even) {
        background: #F8F8F8;
    }

    /* Responsive */

    @media (max-width: 767px) {
        .fl-table {
            display: block;
            width: 100%;
        }
        .table-wrapper:before{
            content: "Scroll horizontally >";
            display: block;
            text-align: right;
            font-size: 11px;
            color: white;
            padding: 0 0 10px;
        }
        .table-excerpt{
            max-width:100%;
        }
        .fl-table thead, .fl-table tbody, .fl-table thead th {
            display: block;
        }
        .fl-table thead th:last-child{
            border-bottom: none;
        }
        .fl-table thead {
            float: left;
        }
        .fl-table tbody {
            width: auto;
            position: relative;
            overflow-x: auto;
        }
        .fl-table td, .fl-table th {
            padding: 20px .625em .625em .625em;
            height: 60px;
            vertical-align: middle;
            box-sizing: border-box;
            overflow-x: hidden;
            overflow-y: auto;
            width: 120px;
            font-size: 13px;
            text-overflow: ellipsis;
        }
        .fl-table thead th {
            text-align: left;
            border-bottom: 1px solid #f7f7f9;
        }
        .fl-table tbody tr {
            display: table-cell;
        }
        .fl-table tbody tr:nth-child(odd) {
            background: none;
        }
        .fl-table tr:nth-child(even) {
            background: transparent;
        }
        .fl-table tr td:nth-child(odd) {
            background: #F8F8F8;
            border-right: 1px solid #E6E4E4;
        }
        .fl-table tr td:nth-child(even) {
            border-right: 1px solid #E6E4E4;
        }
        .fl-table tbody td {
            display: block;
            text-align: center;
        }

    }
    #custom-button {
    margin:20px;
	background:#324960;
	border-radius:2px;
	border:none;
	display:inline-block;
	cursor:pointer;
	color:#ffffff;
	font-size:16px;
	font-weight:bold;
	padding:8px 18px;
	text-decoration:none;
	text-shadow:0px 1px 0px #cc9f52;
    }
    #custom-button:hover {
    	background:rgb(50, 73, 96,0.6);
    }
    p.cdesc,p.cExcerpt {
        background: #4FC3A1;
        font-weight: bold;
        font-size: 12px;
        margin-bottom: 0;
        text-align: center;
        color: #fff;
    }
    p.cdesc{
        background:#324960;
    }
    textarea.pcontent {
        background: #fff;
        outline: none;
        border: none;
        border-bottom: 1px solid #32496038;
        border-radius: 0px;
    }
    .alert {
  padding: 20px;
  background-color: #f44336;
  color: white;
}
    .success {
  padding: 20px;
  background-color: green;
  color: white;
}
.info{
.alert {
  padding: 20px;
  background-color: lightblue;
  color: white;
}
}

.closebtn {
  margin-left: 15px;
  color: white;
  font-weight: bold;
  float: right;
  font-size: 22px;
  line-height: 20px;
  cursor: pointer;
  transition: 0.3s;
}

.closebtn:hover {
  color: black;
}
    </style>';
}

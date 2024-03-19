<?php
defined( 'ABSPATH' ) || exit( 'No direct script access allowed' );
/*
Plugin Name: LP WC Helper
Description: LearnPress Woocommerce integration helper , this plugin adds a field to woocommerce product add/edit page that allows searching for LearnPress Courses and their details   
Version: 1.1
Author: MeowMeowKhan
Text Domain: lpwchelperr
Domain Path: /languages
*/

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
                // Get the value of the field, the select
                var fieldValue = $("#_custom_field").val();
                var selectValue = $("#_custom_select").val();
                var data = {
                    "action": "my_custom_action",
                    "field_value": fieldValue,
                    "select_value": selectValue,
                    "nonce": "' . $nonce . '",
                };
                $.post(ajaxurl, data, function(response) {
                    $("#vscroll").prepend("<p>" + response + "</p>");
                    
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
    padding: 20px;
    background-color: lightblue;
    color: white;
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
#vscroll{
	overflow-y: auto;
    max-height: 600px;
}
    </style>';
}

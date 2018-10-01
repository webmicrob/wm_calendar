<?php
/**
 * Created by PhpStorm.
 * User: microb
 * Date: 21.11.2017
 * Time: 19:09
 */
/*
Plugin Name: wm-Calendar
Description: My Super Calendar Widget
Author: Webmicro
Version: 0.1
*/

require_once('wmCalendar.php');
require_once('wmCalendarAdmin.php');

add_action( 'init', array( 'wmCalendar', 'createPostType' ) );
add_action( 'add_meta_boxes', array( 'wmCalendarAdmin', 'addMetabox' ) );
add_action( 'save_post', array( 'wmCalendarAdmin', 'saveMetaboxContent' ) );

add_filter( 'manage_edit-' . wmCalendar::POST_TYPE . '_columns' , array( 'wmCalendarAdmin', 'addDateColumn' ));
add_action( 'manage_' . wmCalendar::POST_TYPE . '_posts_custom_column', array( 'wmCalendarAdmin', 'fillDateColumn' ), 10, 2 );
add_filter( 'manage_edit-' . wmCalendar::POST_TYPE . '_sortable_columns', array( 'wmCalendarAdmin', 'sortableDateColumn' ) );

add_shortcode( wmCalendar::SHORTCODE, array( 'wmCalendar', 'replaceShortcode' ) );

add_action( 'wp_enqueue_scripts', array( 'wmCalendar', 'loadFrontendScripts' ) );

add_action('wp_ajax_wmc_load_events', array( 'wmCalendar', 'ajaxLoadEvents' ));
add_action('wp_ajax_nopriv_wmc_load_events', array( 'wmCalendar', 'ajaxLoadEvents' ));





/*
function wmCalendar_install() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'wm_calendar';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
		id int(11) NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL,
		date date NOT NULL,
		time time NOT NULL,
		address varchar(255) DEFAULT '' NOT NULL,
		descr text DEFAULT '' NOT NULL,
		PRIMARY KEY  (id)
	) {$charset_collate};";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // Populate table
    $wpdb->insert($table_name, array(
            'name' => 'test event',
            'date' => current_time( 'mysql' ),
            'time' => '22:00',
            'address' => 'test address',
            'descr' => 'test event description',
        )
    );
}
*/
//register_activation_hook( __FILE__, 'wmCalendar_install' );


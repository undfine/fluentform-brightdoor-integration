<?php
/**
 * Plugin Name: Fluent Forms Brightdoor Integration
 * Description: Used to import and sync contacts with Brightdoor CRM
 * Version:     1.2.1
 * Author:      Dustin Wight
 * Text Domain: ff_brightdoor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action('init', function (){

    if (!defined('FLUENTFORM') ) {
        add_action( 'admin_notices', function(){
            
            $message = '<p>' . esc_html__( 'FluentForm Brightdoor Integration Add-On Requires FluentForm', 'ff_brightdoor' ) . '</p>';

            print_error( $message );
            
        });
        return;
    }
    require plugin_dir_path( __FILE__ ) . 'includes/BrightDoor.php';
    require plugin_dir_path( __FILE__ ) . 'includes/BrightDoorApi.php';
    
    $plugin = new FF_BrightDoor();
});

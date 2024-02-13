<?php
/**
 * Plugin Name: Fluent Forms Brightdoor Integration
 * Description: Used to import and sync contacts with Brightdoor CRM
 * Version:     1.2.4
 * Author:      Dustin Wight
 * Author URI: https://github.com/undfine/fluentform-brightdoor-integration
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
    
    // Check that class doesn't already exist
    if( !class_exists('FF_BrightDoor')){

        require plugin_dir_path( __FILE__ ) . 'includes/BrightDoor.php';
        require plugin_dir_path( __FILE__ ) . 'includes/BrightDoorApi.php';
        
        $plugin = new FF_BrightDoor();
    
    } else {
        add_action( 'admin_notices', function(){
            
            $message = '<p>' . esc_html__( 'The FF Brightdoor Class is already in use, please deactivate or uninstall the conflicting plugin', 'ff_brightdoor' ) . '</p>';
            print_error( $message );
            
        });
    }
});

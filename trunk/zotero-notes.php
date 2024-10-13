<?php
/*
 * Plugin Name: Zotero Notes
 * Description: Simple footnote references using Zotero
 * Version: 1.2.3
 * Author: Janiko  
 * Author URI: http://geba.fr
 * Text Domain: zotero-notes
 * Domain Path: /languages
 * License: GPL2
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Zotero_Notes_Plugin {

    /**
     * Initialisation of the plugin. 
     */

    public function __construct() {

        /** Add settings at activation */
        register_activation_hook( __FILE__, 'zotero_notes_defaults' );   
        
        /** Add translation(s) */
        function zotero_notes_load_plugin_textdomain() {
            load_plugin_textdomain( 'zotero-notes' );
        }
        add_action( 'plugins_loaded', 'zotero_notes_load_plugin_textdomain' );
        
        /**
         * Default settings, at activation
         */
        function zotero_notes_defaults() {    
            $zotero_notes_options = get_option( 'zotero_notes_option_name' );
            if ($zotero_notes_options == false) {
                $res = add_option( 'zotero_notes_option_name', array( 'code_name' => 'zref' ) );
            }
        }
        
        include_once plugin_dir_path( __FILE__ ) . 'class.zotero-notes-referencesList.php';
        include_once plugin_dir_path( __FILE__ ) . 'class.zotero-notes-admin.php';

        new zotero_notes_ReferencesList();
    }

    
}

new Zotero_Notes_Plugin();

<?php

    function zotero_notes_add_help() {
        
        $screen = get_current_screen();
        $screen->add_help_tab( array(
        	'id'      => 'overview',
        	'title'   => esc_attr__('Overview','zotero-notes'),
        	'content' => '<p>'.__('This extension helps you to include footnotes references in your posts, ','zotero-notes').
        	esc_attr__('by using a Zotero Library. All you need is a Zotero account with its API (private) access key and the reference ID. ','zotero-notes').
        	'</p><p>'.
        	esc_attr__('Example of use: ','zotero-notes').
        	'</p><p>'.
        	esc_attr__('<code>...blabla[zref]1A6BE9[/zref] blablabla...</code> ','zotero-notes').
        	'</p><p>'.
        	esc_attr__('You can change in this page the shortcode and the attributes used to construct references. ','zotero-notes').
        	esc_attr__('But you should keep in mind the posts written with the old codes won\'t be parsed anymore! ','zotero-notes').
        	esc_attr__('Consequently, you should only change these settings before using this extension. ','zotero-notes').'</p><p>'.
        	esc_attr__('A footnote will be  added (automatically) at the end of the post.','zotero-notes').
        	'</p>'
        ) );

    }


class ZoteroNotesSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }


    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        //global $zotero_notes_admin;
        
        $zotero_notes_admin = add_options_page(
            'Zotero Notes Settings Admin', 
            'Zotero Notes', 
            'manage_options', 
            'ZoteroNotes-setting-admin', 
            array( $this, 'create_admin_page' )
        );
        
        if ( $zotero_notes_admin )
            add_action( 'load-' . $zotero_notes_admin, 'zotero_notes_add_help' );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Get class property
        $this->options = get_option( 'zotero_notes_option_name' );
        ?>
        <div class="wrap">
            <h1><?php esc_attr__( 'Zotero Notes plugin Page', 'zotero-notes' ) ?></h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'zotero_notes_option_group' );
                do_settings_sections( 'ZoteroNotes-setting-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
        
    }
    

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'zotero_notes_option_group', // Option group
            'zotero_notes_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            esc_attr__( 'Zotero Notes Plugin Settings', 'zotero-notes' ), // Title
            array( $this, 'print_section_info' ), // Callback
            'ZoteroNotes-setting-admin' // Page
        );  

        add_settings_field(
            'code_name', 
            esc_attr__( 'Shortcode Name', 'zotero-notes' ), // Shortcode name
            array( $this, 'code_name_callback' ), // Callback
            'ZoteroNotes-setting-admin', // Page
            'setting_section_id' // Section           
        );      

        add_settings_field(
            'zotero_id', // ID
            esc_attr__( 'Zotero User ID (API)', 'zotero-notes' ), // Zotero's account ID
            array( $this, 'zotero_id_callback' ), // Callback
            'ZoteroNotes-setting-admin', // Page
            'setting_section_id' // Section           
        );     

        add_settings_field(
            'zotero_key', // ID
            esc_attr__( 'Zotero Private Key (API)', 'zotero-notes' ), // Zotero Private Key (API)
            array( $this, 'zotero_key_callback' ), // Callback
            'ZoteroNotes-setting-admin', // Page
            'setting_section_id' // Section           
        );             
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['code_name'] ) ) {
            $new_input['code_name'] = sanitize_key( $input['code_name'] );
            /** Checks if the value has changed */
            $old_options = get_option( 'zotero_notes_option_name' );
            $old_code    = $old_options['code_name'];
            if ( strcmp( $old_code, $new_input['code_name'] ) != 0)
                if ( shortcode_exists( $new_input['code_name'] ) ) {
                    add_settings_error( 'ExistingShortcode', 'ZoteroNotes-e01', 'Shortcode <i>'.$new_input['code_name'].'</i> already set somewhere. Please choose another one.', 'error' );
                    $new_input['code_name'] = sanitize_text_field( $old_code );  // Yes, I'm paranoid
                }
                else
                    add_settings_error( 'ModifiedShortcode', 'ZoteroNotes-w01', 'Shortcode updated and modified. Take care if you already used the old one ('.$old_code.')!', 'updated' );
        }
        
        if( isset( $input['zotero_id'] ) ) {
            $new_input['zotero_id'] = sanitize_text_field( $input['zotero_id'] ); // Should be integer only==>CHECK!
        }
        
        if( isset( $input['zotero_key'] ) ) {
            $new_input['zotero_key'] = sanitize_text_field( $input['zotero_key'] ); // Should be alphanumeric only==>CHECK!
        }
        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print esc_attr__( 'Enter your settings below:', 'zotero-notes' );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function code_name_callback()
    {
        printf(
            '<input type="text" id="code_name" name="zotero_notes_option_name[code_name]" value="%s" maxlength="10"/>',
            isset( $this->options['code_name'] ) ? esc_attr( $this->options['code_name']) : ''
        );
    }
    
    public function zotero_id_callback()
    {
        printf(
            '<input type="text" id="zotero_id" name="zotero_notes_option_name[zotero_id]" value="%s" maxlength="10"/>',
            isset( $this->options['zotero_id'] ) ? esc_attr( $this->options['zotero_id']) : ''
        );
    }
    
    public function zotero_key_callback()
    {
        printf(
            '<input type="text" id="zotero_key" name="zotero_notes_option_name[zotero_key]" value="%s" minlength="8" size="35" maxlength="35"/>',
            isset( $this->options['zotero_key'] ) ? esc_attr( $this->options['zotero_key']) : ''
        );
    }


}

if( is_admin() )
    $my_settings_page = new ZoteroNotesSettingsPage();
<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Zotero_Notes_ReferencesList {
    
    /**
     * Contains the list of all the references we have to handle.
     * 
     * All references are stored in an array used to display all the references at the end of each post.
     * This array has a double entry: the post ID and the name of the reference. The same reference can be 
     * used many times in the same post, but is stored only once.
	 *
	 * Each reference is a Citation object (retrieved from Zotero)
     * 
     * @since 4.6
     * 
     * @var array   $_liste_references      
     * 
     * @see Zotero_Notes_ReferencesList class
     */
     
    private $_post_id;
    private $_liste_references = array();

    /**
     * Gets the Zotero_Notes_ReferencesList object corresponding to the reference name and to the post ID
     * 
     * @param   int     $id     ID of the post we are handling
     * @param   string  $name   Name of the reference
     * 
     * @return  Zotero_Notes_ReferencesList 
     */
     
    function get_reference_by_name( $id, $name ) {

        /** Lookup in the reference list if there's a reference with the provided name for the post #id */
        $list = $this->_liste_references[ $id ];
        $result = null;
        
        if ( $list != null ) {
            foreach ( $list as $ref ) {
                if ( strcmp($ref->get_name(), $name ) == 0 ) {
                    $result = $ref;
                    break;
                }
            }
        }

        return $result;
    }    
    
    /**
     * Adds the reference in the list, if needed. If already in list, increments the usage counter (= the number
     * indicating how many times the references is used in the post).
     * 
     * @param   int                               $the_ID     The post ID
     * @param   Zotero_Notes_ReferencesList       $reference  The reference we are looking at right now
     * 
     * @return  Zotero_Notes_ReferencesList                   The reference (added or updated)
     */
     
    function add_reference( $the_ID, $reference ) {

		if ( empty( $this->_liste_references[ $the_ID ] ) )
			$ref_num = 1;
		else
        	$ref_num = count( $this->_liste_references[ $the_ID ] ) + 1;
        $reference->set_ref_num( $ref_num );
        $this->_liste_references[ $the_ID ][] = $reference;

        return $reference;
    }

    /**
     * What to do with the reference?
     * 
     * If it has a no name, we'll reference that reference (yuk) with its number and treat it (yuk) as unique (= used nowhere else).
     * If it has a name, we can refer to it with that name, that means we can reuse it elsewhere.
     * 
     * @param   Zotero_Notes_ReferencesList  $reference  A Zotero_Notes_ReferencesList object that we'll be added following the rules (see above).
     * 
     * @return  Zotero_Notes_ReferencesList              The reference itself
     */
     
    function handle_reference( $reference ) {

        $the_ID = get_the_ID();
        $current_reference = null;

        /** Does this reference have a name? */
        $reference_name = $reference->get_name();

        if ( $reference_name == "" ) {

            /** If no: let's assume the reference is unique */
            $current_reference = $this->add_reference( $the_ID, $reference );

        } else {

            /** More tricky: we have a reference name; so does it already exists in the list ? */
            $reference_multiple = $this->get_reference_by_name( $the_ID, $reference_name );
            if ( $reference_multiple != null) {

                /** Yes: it exists. So let's increment the counter of usage */
                $reference_multiple->add_count();
                $current_reference = $reference_multiple;
            }
            else {
                
                /** New Reference, with a name. May be used later. */
                $current_reference = $this->add_reference( get_the_ID(), $reference );
            }
        }
        
        /** Now we have to set a name for the HTML anchor we'll use to navigate */
        $current_reference->set_anchor( "zotero-ref-p" . $the_ID . "-r" . $current_reference->get_ref_num() . "-o" . $current_reference->get_occ_nb() );

        return $current_reference;
    }
    
    /**
     * Shortcode handler. 
     */

    function zotero_notes_shortcode( $atts, $content ) {
        
        ob_start();
        $current_reference = $this->handle_reference( new Zotero_Notes_Citation( $atts, $content ) );
        $res = "<sup class='sup-ref-note' id='note-" . esc_html($current_reference->get_anchor()) . "'>";
        $res .= "<a class='sup-ref-note' href='#zotero-ref-p" . esc_html($current_reference->get_post_id()) . "-r" . esc_html($current_reference->get_ref_num()) . "'>" . esc_html($current_reference->get_ref_num()) . "</a></sup>";
        print( wp_kses_post( $res, $allowed_tags ));
        //print( $res );

        return ob_get_clean();
    }
    
    /**
     * Appends a footer containing all necessary references to the current post. This function is a WP content filter.
     * 
     * @param   html    $content    The content of the post (at its current state)
     * 
     * @return  html                The content of the post with the reference table at its bottom 
     * 
     * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/the_content
     */
     
    function zotero_notes_footer( $content ) {
        
        // Check if we're inside the main loop in a single post page.
        /*if ( is_single() && in_the_loop() && is_main_query() ) {
            return "";
        }*/
        if ( is_single() || is_page() ) {
    
            $the_ID = get_the_ID();
			if ( empty( $this->_liste_references[$the_ID] ) )
				$count = 0;
			else
            	$count = count( $this->_liste_references[$the_ID] );
            
            if ( $count > 0 ) {
    
                /** Title */
                $content .= "<h3 id='references-head' class='footer_references'>" . __('References','zotero-notes') . "</h3>";
        
                /** Reference table */
                $liste = $this->_liste_references[$the_ID];
                $content .= "<div id='zotero_notes_footer'><ol>";
                foreach ( $liste as $value ) {
                    $content .= $value->display_html();
                }
                $content .= "</ol></div>";
            }
        }
        
        return $content;
    }


    /**
     * Register and enqueue style sheet.
     */
    public function register_zotero_notes_plugin_styles() {
        $cssdir = plugins_url( 'css/plugin.css', __FILE__ );
    	wp_register_style( 'Zotero_Notes_ReferencesList', $cssdir );
    	wp_enqueue_style( 'Zotero_Notes_ReferencesList' );
    }


    /**
     * Zotero_Notes_ReferencesList Constructor 
     */
     
    public function __construct() {

        include_once plugin_dir_path( __FILE__ ).'class.zotero-notes-citation.php';

        /** Loading texts */
		load_plugin_textdomain( 'zotero-notes', false, dirname( plugin_basename( __FILE__ ) ) . '/languages'  );

        /** Register style sheet */
        add_action( 'wp_enqueue_scripts', array( $this, 'register_zotero_notes_plugin_styles' ) );        
        
        /** get plugin options */
        $zotero_notes_options = get_option( 'zotero_notes_option_name' );
        $shortcode    = $zotero_notes_options[ 'code_name' ];
        
        /** Creating the shortcode with the name provided in the admin page */
        add_shortcode( $shortcode, array( $this, 'zotero_notes_shortcode' ));
        add_filter( 'the_content', array( $this, 'zotero_notes_footer' ), 15 );
    }

}


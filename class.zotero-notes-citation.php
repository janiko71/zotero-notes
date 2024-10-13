<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once('class.zotero-notes-dataCollector.php');

class Zotero_Notes_Citation {
    
    /**
     * 
     * Class that instanciates the references.
     * 
     * @since 4.7.1
     * 
     * Properties (from an attribute of the shortcode)
     * -----
     * @var string  $_name      The name of the reference. It will be used when reusing the reference.
     * 
     * Properties for internal usage
     * -----
     * @var int     $_post_id   The ID of the post using the reference.
     * @var int     $ref_num    Internal reference number. Begins at 1 and is incremented for every NEW reference.
     * @var string  $_anchor    HTML anchor used to navigate to and from the reference
     * @var int     $_occ_nb    Number of uccurrences of the reference
     * 
     * Properties of the reference
     * -----
     * @var string  $_content_id   Content of the reference or document
     * @var string  $_title     Title of the reference or document
     * @var string  $_author    Author
     * @var string  $_date      Optional. Date of the reference or document
     * @var string  $_url       Optional. URL of the reference (a string, but in real it's an url)
     * @var string  $_editor    Optional. Editor
     * @var string  $_read      Optional. Date of consultation (=when the blogger read the reference or document)
     * @var string  $_lang      Optional. Language
     * @var string  $_format    Optional. Format (PDF, Office, etc)
     */
     
    private $_name;

    private $_post_id;
    private $_ref_num;
    private $_anchor;
    private $_occ_nb = 1;

    private $_content_id;
    private $_title;
    private $_author;
    private $_creatorSummary;
    private $_authorList;
    private $_authorCount;    
    private $_date;
    private $_url;
    private $_editor;
    private $_page;
    private $_readon;
    private $_lang;
    private $_format;
    
    /**
     * Gets the post ID
     */
     
    public function get_post_id() {
        return $this->_post_id;
    }    
    
    /**
     * Gets the name of the reference. If it exists, it comes from an attribute 'name' in the shortcode
     */
     
    public function get_name() {
        return $this->_name;
    }
    
    /**
     * Gets the reference number (unique within a post)
     */
     
    public function get_ref_num() {
        return $this->_ref_num;
    }   
    
    /**
     * Gets the counter of usage (=how many times the reference is used in the post)
     */
     
    public function get_occ_nb() {
        return $this->_occ_nb;
    }

    /**
     * Gets the anchor
     */
     
    public function get_anchor() {
        return $this->_anchor;
    }

    /**
     * Sets the reference number
     */
     
    public function set_ref_num( $ref_num ) {
        $this->_ref_num = $ref_num;
    }
    
    /**
     * Sets the HTML anchor
     */
     
    public function set_anchor( $anchor ) {
        $this->_anchor = $anchor;
    }
    
    /**
     * Adds one the number of occurrences
     */
     
    public function add_count() {
        $this->_occ_nb++;
    }
    
    /**
     * Ugly function that displays a letter corresponding to the number of occurence
     * No more than 26. If you use the same reference more than this, you're a copycat.
     */
     
    public function display_letter_level( $cnt ) {

        $res = "";
        $cnt = min( 26, $cnt ); 
        for ($i = 0; $i < $cnt; $i++ )
            $res .= "<a href='#note-zotero-ref-p" . $this->_post_id . "-r" . $this->_ref_num . "-o" . ($i+1) . "'>" . chr(97+$i) . "</a> ";

        return "<sup>" . $res . "</sup>";

    }
    
    /**
     * Displays the HTML for the reference footer
     */
     
    public function display_html() {

        $html = "<div id='footer_zotero'>";
        $html .= "<li>";
        
        $html .= "<span id='zotero-ref-p" . $this->get_post_id() . "-r" . $this->get_ref_num() . "'> ";
        // Multiple refs
        if ( $this->_occ_nb > 1 )
            $html .= "↑ ".$this->display_letter_level($this->_occ_nb);
        else
            $html .= "<a style='border-bottom: 1px solid white;' href='#note-zotero-ref-p".$this->get_post_id()."-r".$this->get_ref_num()."-o1'>↑ </a>";

        // The content_id must match a Zotero Reference, else it will be displayed as is
        if ( isset($this->_title) ) {
            
            /**
             * Language, if exists (2 first char.)
             */
             
            if ( isset($this->_lang)) {
                if ($this->_lang != "") {
                    $html .= " (" . substr($this->_lang, 0, 2) . ") ";
                }
            }
            
            /**
             * A complex part: the author(s)
             */
             
            $author = "";

            if ( isset($this->_author) ) {
                $author = $this->_author.", ";
            } else if ( isset($this->_authorList) ) {
                $authorList = $this->_authorList; 
                $authorCount = count($authorList);
                if ($authorCount == 1) {
                    $author = $authorList[0]['firstName'] . " " . $authorList[0]['lastName'];
                    
                } else if ($authorCount > 1) {
                    if ( isset($this->_creatorSummary) ) {
                        $author = $this->_creatorSummary;
                    } else {
                        for ($i = 0; $i < $authorCount; $i++) {
                            $author = $authorList[$i]['firstName'] . " " . $authorList[$i]['lastName'];
                            $author .= " ".$author.", ";
                        }
                    }
                }
            }

            if ( strcmp( $author, "" ) ) {
                $html .= "<span class='zotero_notes_author'>" . $author . ",</span>";
            }
            
            /**
             * Access URL
             */
             
            if ( isset($this->_url) ) {
                $html .= " &laquo; <a href=".$this->_url.">".$this->_title."</a> &raquo;";
            } else {
                $html .= " &laquo; ".$this->_title." &raquo;";
            }
            
            /**
             * Publisher/editor
             */
             
            if ( isset($this->_editor) ) {
                $editor = $this->_editor;
                if ( strcmp("", $editor) == 0 ) {
                    $editor = wp_parse_url($this->_url, PHP_URL_HOST);
                }
                $html .= " ".__('on','zotero-notes')." <i>".$editor."</i>";
                    
            } 
            
            /**
             * Date of publication
             */
             
            if ( isset($this->_date) ) {
                if ( strcmp("", $this->_date) ) {
                    $html .= ", ".mysql2date( get_option( 'date_format' ), $this->_date);
                }
            }
            
            /**
             * Consultation date
             */
             
            if ( strcmp("", ($this->_readon)) ) {
                $html .= " (".__('read on','zotero-notes')." ".mysql2date( get_option( 'date_format' ), $this->_readon) . ")";
            }
            
            /**
             * Other minor information
             */
             
            if ( isset($this->_page) ) {
                $html .= ", ".$this->_page;
            }


        }
        else {
            //$html .= "#err";
            $html .= $this->_content_id;
        }

        $html .= "</span>";
        $html .= "</li>";
        $html .= "</div>";

        return $html;

    }

    
    /**
     * Constructor
     */
     
    public function __construct( $atts, $content_id ) {

        static $patt = "((date|author|title|format|editor|page|readon|lang)=(.+))";
        static $patt_url = "((url)=((http)(s)?(.+)))";

        // Add post ID
        $this->_post_id = get_the_ID();

        // Has some attribute ?
        if ( isset( $atts['name'] ) ) {
            $this->_name = htmlspecialchars( $atts['name'] );
        }
        
        // What about the content ? Should contain only item id
        $this->_content_id = $content_id;
        
        // Now we populate with Zotero
        $this->options = get_option( 'zotero_notes_option_name' );
        $code_name = $this->options['code_name'];
        $zotero_key = $this->options['zotero_key'];
        $zotero_id = $this->options['zotero_id'];
        
        $meta = new Zotero_Notes_DataCollector($content_id, $zotero_id, $zotero_key);
        
        foreach ($meta as $key => $value){
            $this->$key = $value;
        }

    }
}    
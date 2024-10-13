<?php

//defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Description of dataCollector
 *
 * @author janiko
 */
class Zotero_Notes_DataCollector {
    
    /*
     * This class helps collecting datas from Zotero's citations.
     *
     */
    
    /**
     * API url
     */
    const ZOTERO_API_URL = "https://api.zotero.org";
    const ZOTERO_API_VERSION = "3";

    /**
     * Holds all the values parsed from a page
     *
     */

    public $_author;
    public $_creatorSummary;
    public $_authorList;
    public $_authorCount;
    public $_url;
    public $_date;
    public $_editor;
    public $_format;
    public $_lang;
    public $_readon;
    public $_title;
    public $_itemType;
    public $_isAccessibleForFree;
    
    function __construct($item_id, $zotero_id, $zotero_key) {
        self::fetch($item_id, $zotero_id, $zotero_key);
    }

    
    /**
     * Fetches a Zotero Item for all known values
     * false on error.
     *
     * @param $item_id     Zotero's item ID we are looking for
     * @param $zotero_id   Zotero's user ID
     * @param $zotero_key  Zotero's API key
     * @return result
     */
    private function fetch($item_id, $zotero_id, $zotero_key) {
        
        /**
         * Let's fetch the Zotero reference. If it exists in cache, no need to retrieve it (if not too old).
         */
        
        $curl_url_zotero = self::ZOTERO_API_URL . "/users/" . $zotero_id . "/items/" . $item_id . "?v=" . self::ZOTERO_API_VERSION;
        
        /* Is it in cache? */
        $transient_key = $item_id . ":" . $zotero_id;
        if ( false === ( $zotero_response = get_transient( $transient_key ) ) ) {
            // It wasn't there, so regenerate the data and save the transient
            $zotero_response = self::_my_curl($curl_url_zotero, $zotero_key);
            set_transient( $transient_key, $zotero_response, 12 * HOUR_IN_SECONDS );
        }

        // Then we parse the response to find the information
        $result = self::_parse($zotero_response);
        
        return $result;
        
    }
    
    /**
     * My own curl call
     * 
     * @param type           $URI
     * @param $zotero_key    API Key (for Zotero)
     * @return type string (json ou html)
     */
    
    static private function _my_curl($URI, $zotero_key) {

        $curl = curl_init(filter_var($URI, FILTER_SANITIZE_URL)); // Trust No-One (TNO) ==> filter_input
        $zotero_key = sanitize_text_field( $zotero_key );

        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, filter_input(INPUT_SERVER, 'HTTP_USER_AGENT'));  // TNO
        
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Zotero-API-Key: " . $zotero_key, "Cache-Control: max-age=3600; public"));
       
        $response = curl_exec( $curl );  // TNO

        curl_close($curl);

        return $response;
        
    }

    /**
     * Parses JSON and extracts data.
     *
     * @param $json    JSON to parse
     * @return metadata
     */
    
    private function _parse($json) {
        
        // Trust No One : don't forget to sanitize entry (done after the curl call)
        
        $response = [];

        $zotero_api_response = json_decode($json, true);
        $zotero_data = $zotero_api_response["data"];
        $zotero_meta = $zotero_api_response["meta"];

        /**
         * Here, $zotero_api_response contains a lot of tags with information. 
         * Let's gather the most relevant for our citations.
         */
        
        // Let's find the title
        $this->_title = $zotero_data["title"];

        // Now the date 
        $this->_date = $zotero_data["date"];

        // The URL...
        $this->_url = $zotero_data["url"];

        // Creator summary, if exists
        $this->_creatorSummary = $zotero_meta["creatorSummary"]; 
        
        // The author
        $this->_authorList = $zotero_data["creators"];

        // Language
        $this->_lang = $zotero_data["language"];

        // Item type
        $this->_itemType = $zotero_data["itemType"];

        // ld.isAccessibleForFree
        $this->_isAccessibleForFree = $zotero_data["isAccessibleForFree"];

        // (Last) Access Date
        $this->_readon = $zotero_data["accessDate"];

        // Publisher/editor, depending on media type
        $itemType = $zotero_data["itemType"];
        if (strcmp($itemType, "newspaperArticle") == 0) {
            $this->_editor = $zotero_data["libraryCatalog"];
        } else if (strcmp($itemType, "blogPost") == 0) {
            $this->_editor = $zotero_data["blogTitle"];
        } else if (strcmp($itemType, "webpage") == 0) {
            $this->_editor = $zotero_data["websiteTitle"];
        } else {
            $this->_editor = $zotero_data["publisher"];    
        }

    }
    


    /**
     * Helper method to access attributes directly
     * Example:
     * $meta->title
     *
     * @param $key    Key to fetch from the lookup
     */
    public function __get($key) {
        if (array_key_exists($key, $this->_values)) {
            return $this->_values[$key];
        }

        if ($key === 'schema') {
            foreach (self::$TYPES AS $schema => $types) {
                if (array_search($this->_values['type'], $types)) {
                    return $schema;
                }
            }
        }
    }

    /**
     * Return all the keys found for the object
     *
     * @return array
     */
    public function keys() {
        return array_keys($this->_values);
    }

    /**
     * Helper method to check an attribute exists
     *
     * @param $key
     */
    public function __isset($key) {
        return array_key_exists($key, $this->_values);
    }
    
}

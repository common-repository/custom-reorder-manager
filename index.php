<?php
/**
 * Plugin Name: Custom Reorder Manager
 * Plugin URI:
 * Description: Reorder posts based on post-type and taxonomy
 * Author: Babu K  A
 * Version: 1.2.0
 */

include_once plugin_dir_path(__FILE__) . 'cro.php';

global $cro;

/**
*   Initialize CRO object
*   
*   @type function
*   @param N/A
*   @return N/A
*/
function cro() {

    global $cro;

    if( !isset($cro) ) {
        $cro = new cro();
        $cro->initialize();
    }
}

cro();

/**
*   Get the posts in CRO order by a public function
*   
*   @type function
*   @param string post type
*   @param array arguments
*   @return array
*/
function cro_posts($post_type, $args = array()) {
    
    global $cro;

    if( !isset($cro) ) { 
        $cro = new cro();
    } 

    return $cro->cro_posts($post_type, $args);
}

/**
*   Get the posts based on taxonomy CRO order by a public function
*   
*   @type function
*   @param array array of rquired parameters
*   @param array arguments
*   @return array
*/
function cro_posts_by_taxonomy($taxonomy_set, $args = array()) {
    
    global $cro;

    if( !isset($cro) ) { 
        $cro = new cro();
    } 

    return $cro->cro_posts_by_taxonomy($taxonomy_set, $args);
}
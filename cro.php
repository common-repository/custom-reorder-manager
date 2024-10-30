<?php

/**
* Functions associate CRO settings and meta updates
*/
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! class_exists( 'cro' ) ) :

    class cro {

        /** @var string Plugin version  */
        var $version;

        /** @var array Plugin settings array  */

        var $settings = array();

        /**
        *   __construct
        *
        *   The constructor to make sure CRO initialize once
        *   
        *   @type function
        *   @param N/A
        *   @return N/A
        */
        function __construct() {

            /** Do Nothing  */
        }

        /**
        *   initialize
        *
        *   CRO initializer
        *   
        *   @type function
        *   @param N/A
        *   @return N/A
        */
        function initialize() {

            $this->version = 1.0;

            // Default Settings
            $this->settings = array(

                //file
                'file'          =>  __FILE__,
                'path'          =>  plugin_dir_path(__FILE__),
                'url'           =>  plugin_dir_url(__FILE__),

                //menu slug
                'post'          => 'edit.php',
                'attachment'    => 'upload.php',
                );

            // Action set
            $this->_action_set();
        }

        /**
        *   _action_set
        *
        *   Add all Action hooks
        *   
        *   @type function
        *   @param N/A
        *   @return N/A
        */
        function _action_set() {

            register_deactivation_hook ( $this->settings[ 'file' ], array( $this, 'cro_deactivation' ) );

            add_action( 'admin_menu', array( $this, 'cro_settings' ));
            add_action( 'admin_init', array( $this, 'display_cro_settings_fields' ));
            add_action( 'admin_menu', array( $this, 'set_admin_menu' ));
            add_action( 'admin_enqueue_scripts', array( $this, 'cro_load_assets' ));
            add_action( 'save_post', array( $this, 'set_post_order' ));
            add_action('wp_ajax_cro_update_order', array( $this, 'cro_update_order' ));
        }

        /**
        *   cro_load_assets
        *
        *   Register and Enqueue styles and scripts
        *   
        *   @type function
        *   @param N/A
        *   @return N/A
        */
        function cro_load_assets( $hook ) {

            wp_register_script ( 'cro-js', plugins_url ( '/assets/js/cro.js', __FILE__ ), array (
                    'jquery'
            ), $this->version );
            wp_register_script ( 'sortable-js', plugins_url ( '/assets/js/sortable.js', __FILE__ ), array (
                    'jquery'
            ), $this->version );
            wp_register_style( 'cro-css', plugins_url ( '/assets/css/cro.css', __FILE__ ) );

            wp_enqueue_script( 'cro-js' );
            wp_enqueue_script( 'sortable-js' );
            wp_enqueue_style( 'cro-css' );
        }

        /**
        *   set_admin_menu
        *
        *   Set Reorder sub Menu in selected post types.
        *   
        *   @type function
        *   @param N/A
        *   @return N/A
        */
        function set_admin_menu() {
            
            $cro_post_types = get_option( 'cro_post_types' );
            $cro_taxonomies = get_option( 'cro_taxonomies' );
            $applied_post_types = array();

            if(!empty( $cro_post_types )) {
                foreach( $cro_post_types as $cro_post_type ) {
                    $menu_slug = isset( $this->settings[ $cro_post_type ] ) ? $this->settings[ $cro_post_type ] : 'edit.php?post_type=' . $cro_post_type;
                    $this->_set_default_order( $cro_post_type );
                    add_submenu_page( $menu_slug, 'Re-Order', 'Re-Order', 'manage_options', 'reorder_' . $cro_post_type, array( $this, 'cro_reorder_page' ));
                }

                $applied_post_types = $cro_post_types;
            }

            if(!empty( $cro_taxonomies )) {
                foreach( $cro_taxonomies as $cro_taxonomy ) {
                    $tax_object = get_taxonomy( $cro_taxonomy );
                    $post_types = $tax_object->object_type;
                    if(!empty( $post_types )) {
                        foreach( $post_types as $post_type ) {
                            if( !in_array( $post_type, $applied_post_types )) {
                                $applied_post_types[] = $post_type;
                                $menu_slug = isset( $this->settings[ $post_type ] ) ? $this->settings[ $post_type ] : 'edit.php?post_type=' . $post_type;
                                $this->_set_default_order( $post_type );
                                add_submenu_page( $menu_slug, 'Re-Order', 'Re-Order', 'manage_options', 'reorder_' . $post_type, array( $this, 'cro_reorder_page' ));
                            }
                        }
                    }
                }
            }
        }

        /**
        *   cro_reorder_page
        *
        *   Include Reorder page
        *   
        *   @type function
        *   @param N/A
        *   @return N/A
        */
        function cro_reorder_page() {
            include_once $this->settings[ 'path' ] . 'croPanel.php';
        }

        /**
        *   cro_settings
        *
        *   Add CRO Settings Menu
        *   
        *   @type function
        *   @param N/A
        *   @return N/A
        */
        function cro_settings() {

            add_submenu_page( 'options-general.php', ( 'CRO Settings' ), ( 'CRO Settings' ), 'manage_options', 'cro-settings', array( $this, 'cro_settings_page' ) );
        }

        /**
        *   cro_settings_page
        *
        *   CRO settings page wrapper
        *   
        *   @type function
        *   @param N/A
        *   @return N/A
        */
        function cro_settings_page() {
        ?>
            <div class="wrap cro-wrapper-container cro-wrapper">
                <h1>CRO Settings</h1>
                <div class="cro-settings">
                    <div class="cro-setting-header">
                        <h2> Select the post types you want to re-order.</h2>
                        <i>Public posts are listed below</i>
                    </div>
                    <form method="post" action="options.php">
                        <?php
                            settings_fields( "cro-section" );
                            do_settings_sections( "cro-options" );
                            submit_button(); 
                        ?>          
                    </form>
                <div>
            </div>
        <?php
        }

        /**
        *   display_cro_posttypes
        *
        *   Display public post types
        *   
        *   @type function
        *   @param N/A
        *   @return N/A
        */
        function display_cro_posttypes() {
            
            $cro_post_types = get_option( 'cro_post_types' );
            $cro_post_types = $cro_post_types ? $cro_post_types : array();
            $filter = array( 'public' => true );
            
            foreach ( get_post_types( $filter, 'objects' ) as $post_type ) {
                $checked = '';
                if( in_array( $post_type->name, $cro_post_types ) ) {
                    $checked = 'checked';
                }
            ?>
                <input type="checkbox" name="cro_post_types[]" value="<?php echo $post_type->name?>"  <?php echo $checked; ?>/><?php echo $post_type->label; ?> <br />
            <?php
            }
        }

        /**
        *   display_cro_terms
        *
        *   Display terms
        *   
        *   @type function
        *   @param N/A
        *   @return N/A
        */
        function display_cro_terms() {
            
            $cro_taxonomies = get_option( 'cro_taxonomies' );
            $cro_taxonomies = $cro_taxonomies ? $cro_taxonomies : array();
            $filter = array('public' => true);
            
            foreach ( get_taxonomies( $filter, 'objects' ) as $taxonomy ) {
                $checked = '';
                if(in_array($taxonomy->name, $cro_taxonomies)) {
                    $checked = 'checked';
                }
            ?>
                <input type="checkbox" name="cro_taxonomies[]" value="<?php echo $taxonomy->name?>"  <?php echo $checked; ?>/><?php echo $taxonomy->label; ?> <br />
            <?php
            }
        }

        /**
        *   display_cro_settings_fields
        *
        *   Add Sections and Fields. 
        *   Register all fields to the section.
        *   
        *   @type function
        *   @param N/A
        *   @return N/A
        */
        public function display_cro_settings_fields() {
            
            add_settings_section( "cro-section", null, null, "cro-options" );
            add_settings_field( "cro_post_types", "Select Post types", array( $this, "display_cro_posttypes" ), "cro-options", "cro-section" );
            add_settings_field( "cro_taxonomies", "Select Taxonomies", array( $this, "display_cro_terms" ), "cro-options", "cro-section" );
            register_setting( "cro-section", "cro_post_types" );
            register_setting( "cro-section", "cro_taxonomies" );
        }

        /**
        *   _set_default_order
        *
        *   Set default order to posts
        *   
        *   @type function
        *   @param String post type
        *   @return N/A
        */
        function _set_default_order( $slug ) {

            $args = array(
                'post_type' => array( $slug ),
                'order' => 'ASC',
                'posts_per_page' => -1,
            );

            $cur_posts = get_posts( $args );
            
            if( !empty( $cur_posts ) ) {
                $i = 0;
                foreach( $cur_posts as $cur_post ) {
                    $this->set_post_order( $cur_post->ID, $i );
                    $i++;
                }
            }
        }

        /**
        *   set_post_order
        *
        *   Set order while saving a post
        *   
        *   @type function
        *   @param string post id. Default NULL
        *   @param string order. post order
        *   @return N/A
        */
        function set_post_order( $post_id = NULL, $order = NULL ) {

            $post_id = $post_id ? $post_id : get_post_ID();

            $post_type = get_post_type( $post_id );
            $post_taxonomies = get_post_taxonomies( $post_id );
            
            $cro_post_types = get_option( 'cro_post_types' );
            $cro_taxonomies = get_option( 'cro_taxonomies' );
            
            if(!empty( $cro_post_types ) && in_array( $post_type, $cro_post_types ) ) {
                if ( get_post_meta( $post_id, 'cro_post_order', true ) == '' || get_post_meta( $post_id, 'cro_post_order', true ) === FALSE ) {
                        $cro_order = ( $order !== NULL ) ? $order : wp_count_posts( $post_type )->publish;
                        update_post_meta( $post_id, 'cro_post_order', $cro_order );
                }
            }

            if(!empty( $post_taxonomies )) {
                foreach( $post_taxonomies as $taxonomy ) {
                    if(in_array( $taxonomy, $cro_taxonomies )) {

                        $term_results = wp_get_post_terms( $post_id, $taxonomy );

                        if( !empty( $term_results ) ) {
                            foreach( $term_results as $term_result ) {
                                $post_meta = 'cro_tx_' . $post_type . '_' . $term_result->slug;
                                if ( get_post_meta( $post_id, $post_meta, true ) == '' || get_post_meta( $post_id, $post_meta, true ) === FALSE ) {
                                    $cro_order = ( $order !== NULL ) ? $order : $term_result->count;
                                    update_post_meta( $post_id, $post_meta, $cro_order );
                                }
                            }
                        }
                    }
                }
            }
        }

        /**
        *   cro_update_order
        *
        *   Display post items in a widget
        *   
        *   @type function
        *   @param N/A
        *   @return N/A
        */
        function cro_update_order() {

            $sort_data = esc_sql( $_POST['data'] );
            if( !empty( $sort_data ) ) {
                foreach( $sort_data as $sort_item ) {
                    $post_meta = base64_decode( esc_sql( $sort_item[ 'meta' ] ) );
                    if($post_meta) {
                        $post_id = trim( esc_sql( $sort_item[ 'id' ] ) );
                        $cro_post_order = trim( esc_sql( $sort_item[ 'cro_post_order' ] ) );
                        update_post_meta( $post_id, $post_meta, $cro_post_order );
                    }
                }
            }
            exit();
        }

        /**
        *   cro_deactivation
        *
        *   Delete plugin options
        *   
        *   @type function
        *   @param N/A
        *   @return N/A
        */
        public function cro_deactivation() {

            global $wpdb;

            delete_option( 'cro_post_types' );
            delete_option( 'cro_taxonomies' );

            $table = $wpdb->prefix . 'postmeta';
            $query = "DELETE FROM " . $table. " WHERE  `meta_key` LIKE  'cro_tx%'";
            $wpdb->query( $query );
            $wpdb->delete( $table, array( 'meta_key' => 'cro_post_order' ) );
        }

        /**
        *   cro_posts
        *
        *   Get the posts in CRO order
        *   
        *   @type function
        *   @param string post type
        *   @param array arguments
        *   @return array
        */
        function cro_posts( $post_type, $args = array()) {

            $cro_post_types = get_option( 'cro_post_types');
            if( !empty( $cro_post_types ) && in_array( $post_type, $cro_post_types ) ) {
                
                $args[ 'post_type' ] = $post_type;
                $args[ 'meta_key' ] = 'cro_post_order';
                $args[ 'orderby' ] = 'meta_value_num';
                $args[ 'order' ] = isset( $args[ 'order' ] ) ? $args[ 'order' ] : 'ASC';
                $args[ 'posts_per_page' ] = isset( $args[ 'posts_per_page' ]) ? $args[ 'posts_per_page' ] : '-1';

                return get_posts( $args );
            } else {
                return array();
            }
        }

        /**
        *   cro_posts_by_taxonomy
        *
        *   Get the posts in CRO order by taxonomy
        *   
        *   @type function
        *   @param array array of rquired parameters
        *   @param array arguments
        *   @return array
        */
        function cro_posts_by_taxonomy( $taxonomy_set, $args = array() ) {

            $cro_taxonomies = get_option( 'cro_taxonomies' );
            
            if( !isset( $taxonomy_set[ 'post_type' ] ) ) {
               return array( 'error' => 'post_type parameter required' ); 
            }

            if( !isset( $taxonomy_set[ 'taxonomy' ] ) ) {
               return array( 'error' => 'taxonomy parameter required' ); 
            }

            if( !isset( $taxonomy_set[ 'term' ] ) ) {
               return array( 'error' => 'term parameter required' ); 
            }

            if( !empty( $cro_taxonomies ) && in_array($taxonomy_set[ 'taxonomy' ], $cro_taxonomies ) ) {
                
                $args[ 'post_type' ] = $taxonomy_set[ 'post_type' ];
                $args[ 'meta_key' ] = 'cro_tx_'. $taxonomy_set[ 'post_type' ] . '_' . $taxonomy_set[ 'term' ];
                $args[ 'orderby' ] = 'meta_value_num';
                $args[ $taxonomy_set[ 'taxonomy' ] ] = $taxonomy_set[ 'term' ];
                $args[ 'order' ] = isset($args[ 'order' ]) ? $args['order'] : 'ASC';
                $args[ 'posts_per_page' ] = isset($args[ 'posts_per_page' ]) ? $args[ 'posts_per_page' ] : '-1';

                return get_posts( $args );
            } else {
                return array();
            }
        }
    }
endif;
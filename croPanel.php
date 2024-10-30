<?php

/**
* Function associate with CRO Drag & Drop Panel
*/
if( ! class_exists( 'croPanel' ) ) :

    class croPanel {

        /** @var string post type Slug  */
        var $_slug;

        /** @var Object posts  */
        var $_cro_posts;

        /** @var array sort types  */
        var $_sort_types;

        /** @var string active tab  */
        var $_active_tab;

        /** @var string default tab  */
        var $_default_tab;
        
        /**
        *   __construct
        *
        *   The constructor to make sure croPanel initialize once
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
        *   CRO panel initializer
        *   
        *   @type function
        *   @param N/A
        *   @return N/A
        */
        function initialize() {

            if( !$this->_valid_slug_checker() ) return false;
            
            $this->_display_panel();
        }

        /**
        *   _valid_slug_checker
        *
        *   Check the validity of the slug.
        *   Show mark up in the case of invalid slug
        *   
        *   @type function
        *   @param N/A
        *   @return boolean
        */
        function _valid_slug_checker() {

            $cro_post_types = get_option( 'cro_post_types' );
            $cur_type = isset( $_GET[ 'page' ] )  ? str_replace( 'reorder_', '', $_GET[ 'page' ] ) : '';
            
            if( !empty( $cro_post_types ) && in_array( $cur_type, $cro_post_types ) ) {
                $this->_slug = $cur_type;
                $this->_default_tab = 'by_post_type';
                return true;
            } elseif( $this->_get_post_types_by_valid_taxonomies( $cur_type )) {
                $this->_slug = $cur_type;
                return true;
            } else { 
            ?>
                <div class=right>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><b>Invalid Access ... !!</b> </th>
                            </tr>
                        </thead>
                    </table>
                </div>
            <?php 
                $this->_slug = false;
            }

            return false;
        }

        /**
        *   _get_post_types_by_valid_taxonomies
        *
        *   Get all post types based on valid taxonomies.
        *   
        *   @type function
        *   @param string current post type
        *   @return boolean
        */
        function _get_post_types_by_valid_taxonomies( $cur_type ) {

            $cro_taxonomies = get_option( 'cro_taxonomies' );
            
            if(!empty( $cro_taxonomies )) {
                foreach( $cro_taxonomies as $cro_taxonomy ) {
                    $tax_object = get_taxonomy( $cro_taxonomy );
                    $post_types = $tax_object->object_type;
                    if( !empty( $post_types ) ) {
                        foreach( $post_types as $post_type ) {
                            if( $post_type == $cur_type ) {
                                $this->_default_tab = 'by_' . $cro_taxonomy;
                                return true;
                            }
                        }
                    }
                }
            }

            return false;
        }

        /**
        *   _posts_by_type
        *
        *   Get posts by type
        *   Sort out posts based on meta key and taxonomy-term relation
        *   
        *   @type function
        *   @param string meta key required
        *   @param array argumnets taxonomy - term relation
        *   @return boolean
        */
        function _posts_by_type( $meta_key, $argument = NULL ) {

            if( !$this->_slug || !$meta_key ) return false;

            if( !isset( $this->_sort_types[ $this->_active_tab ] ) ) return false;

            $args = array (
                    'post_type' => $this->_slug,
                    'meta_key' => $meta_key,
                    'orderby' => 'meta_value_num',
                    'order' => 'ASC',
                    'posts_per_page' => - 1
            );

            if( !empty( $argument ) ) {
               $args[ $argument[ 'key' ] ] = $argument[ 'value' ];
            }

            $this->_cro_posts =  get_posts( $args );
        }

        /**
        *   _markup_panel
        *
        *   Get markup of post items
        *   
        *   @type function
        *   @param N/A
        *   @return array
        */
        function _markup_panel() {

            if( !$this->_slug ) return false;

            if( !isset( $this->_sort_types[ $this->_active_tab ] ) ) return false;

            //By post type
            if( $this->_sort_types[ $this->_active_tab ] == 'cro_post_order' ) {
                
                $meta_key = 'cro_post_order';
                $this->_posts_by_type( $meta_key );
                return $this->_markup_list( $meta_key );
            } else {

                // By Taxonomies
                $taxonomy = str_replace( 'by_', '', $this->_active_tab );
                $terms = get_terms ( $taxonomy );

                if (! empty ( $terms ) ) {
                    $result = array( 
                            'status'  => '0' ,
                            'mark_up' => ''
                        );

                    foreach ( $terms as $term ) {

                        $meta_key = 'cro_tx_' . $this->_slug . '_' . $term->slug;
                        $arg = array( 
                                'key' => $taxonomy,
                                'value' => $term->slug,
                            );

                        $this->_posts_by_type( $meta_key, $arg );
        
                        $result[ 'mark_up' ] .= '<h3>' .$term->name. '</h3>';
                        $output = $this->_markup_list( $meta_key );
                        $result[ 'mark_up' ] .= $output[ 'mark_up' ];
                        $result[ 'status' ] = $result[ 'status' ] === '0' ? $output[ 'status' ] : $result[ 'status' ];
                    }
                } else {
                    $result[ 'status' ] = '0';
                    $result[ 'mark_up' ] = '<div> There are no ' . str_replace('_', ' ', $this->_slug ) . ' to display';
                }

                return $result;
            } 
        }

        /**
        *   _markup_list
        *
        *   List out post items
        *   
        *   @type function
        *   @param string $post_meta
        *   @return array
        */
        function _markup_list( $post_meta ) {

            if( !$post_meta || !$this->_cro_posts || empty( $this->_cro_posts ) ) {
                $result[ 'status' ] = '0';
                $result[ 'mark_up' ] = '<div> There are no ' . str_replace( '_', ' ', $this->_slug ) . ' to display';
                return $result;
            }

            $thumb_url = plugin_dir_url( __FILE__ ) . 'assets/img/placeholder.png';
            $output = '<ul class="cro_post_sortable grid" data-action="' . plugin_dir_path( __FILE__ ) . '">';

            foreach ( $this->_cro_posts as $item ) {
                if ( has_post_thumbnail( $item->ID ) ){ 
                    $image = wp_get_attachment_image_src( get_post_thumbnail_id( $item->ID ), 'single-post-thumbnail' ); 
                    $card_url = ( !empty( $image ) && $image[ '0' ] ) ? $image[ '0' ] : $thumb_url;
                } else {
                    $card_url = $thumb_url;
                }

                $order = get_post_meta ( $item->ID, $post_meta, true );
                $output .= '<li id="' . $item->ID . '" itemscope
                            itemtype="http://schema.org/Person"
                            class="cro_wid ui-state-default"
                            data-order="' . $order . '" data-meta="'. base64_encode( $post_meta ).'">
                                <div class="cro_wid_inner">
                                    <img src="' . $card_url . '" />
                                    <div class="cro_wid_overlay" title="' . $item->post_title .'">' . $item->post_title . '</div>
                                    <div itemprop="cro_post_order" class="cro_wid_jobtitle">' . $item->post_title. ' </div>
                                </div>
                        </li>';
            }
            
            $output .= '</ul>';

            $result[ 'status' ] = '1';
            $result[ 'mark_up' ] = $output;

            return $result;
        }

        /**
        *   _tab_markup
        *
        *   Display tab markup
        *   
        *   @type function
        *   @param N/A
        *   @return string
        */
        function _tab_markup() {

            if( !$this->_slug ) return '';

            $this->_sort_types = array();
            $cro_post_types = get_option( 'cro_post_types' ); 
            $cro_taxonomies = get_option( 'cro_taxonomies' );
            $taxonomy_objects = get_object_taxonomies( $this->_slug, 'objects' );

            $type_param = ( $this->_slug == 'post' || $this->_slug == 'attachment' ) ? '' : 'post_type=' . $this->_slug . '&'; 

            $output  = '<h2 class="nav-tab-wrapper">';

            if( !empty( $cro_post_types ) && in_array( $this->_slug, $cro_post_types ) ) { 
                $this->_sort_types[ 'by_post_type' ] = 'cro_post_order';
                $nav_tab_active = $this->_active_tab == 'by_post_type' ? "nav-tab-active" : '';
                $output  .= '<a href="?' . $type_param . 'page=reorder_' . $this->_slug . '&tab=by_post_type" class="nav-tab  ' . $nav_tab_active . '">By Post type</a>';
            }

            if( !empty( $taxonomy_objects ) ) {
                foreach ( $taxonomy_objects as $taxonomy ) { 
                    if( in_array( $taxonomy->name, $cro_taxonomies ) ) { 
                        $nav_tab_active = $this->_active_tab == 'by_' . $taxonomy->name  ? "nav-tab-active" : '';
                        $this->_sort_types[ 'by_' . $taxonomy->name ] = 'cro_tx_' . $taxonomy->name;
                        $output  .= '<a href="?' . $type_param . 'page=reorder_' . $this->_slug . '&tab=by_' . $taxonomy->name . '" class="nav-tab  '. $nav_tab_active . '">By ' . $taxonomy->label .'</a>';
                    }
                }
            }
            
            $output  .= '</h2>';

            return $output;
        }

        /**
        *   _display_panel
        *
        *   Display post items in a panel
        *   
        *   @type function
        *   @param N/A
        *   @return N/A
        */
        function _display_panel() {

            $this->_active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : $this->_default_tab;
            $tab = $this->_tab_markup();
            $result = $this->_markup_panel();
        ?>
            <div class="wrapper cro-wrapper">
                <div class="width100 left">
                    <h1>Custom Reorder Manager<h1>
                    <h2 class="nav-tab-wrapper">
                        <?php echo $tab; ?>
                    </h2>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><b>Drag & Drop the <?php echo str_replace('_', ' ', $this->_slug );?>s for sorting.</b></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo $result[ 'mark_up' ];?></td>
                            </tr>
                            <?php 
                            if($result[ 'status' ] == '1') {
                            ?>
                            <tr>
                                <td>
                                    <a class="button button-primary" id="set_order" data-tab="<?php echo base64_encode($this->_sort_types[ $this->_active_tab ])?>">Save Order</a>
                                    <p class="cro_wid_update_status">
                                        <span class="cro_wid_updating"><img src="<?php echo plugin_dir_url(__FILE__) . 'assets/img/spinner.gif' ?>" class=""/> Saving</span>
                                        <span class="cro_wid_saved"><img src="<?php echo plugin_dir_url(__FILE__) . 'assets/img/check.png' ?>" class=""/> Saved!</span>
                                    </p>
                                </td>
                            </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if( $result[ 'status' ] == '1' ) {
            ?>
            <div class="wrapper cro-code-wrapper">
                <div class="width100 left">
                    <h1>How can I use CRO in a template?</h1>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><b>Use the following function to fetch the posts based on CRO. </b></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <code>

                                        <?php
                                        if( $this->_active_tab == 'by_post_type' ) {
                                        ?>
                                            <b>$<?php echo 'cro_' . $this->_slug; ?> = cro_posts(<i>string $post_type</i>, <i>array $args = null</i>);</b>
                                            </br>
                                            </br>
                                            <b>Parameters #</b></br>
                                            <i><b>$post_type</b> (string) required.</i></br>
                                            <i><b>$args</b> (array) optional. An array of parameters that supports <i>get_posts()</i> function</i>
                                        <?php
                                        } else {
                                        ?>
                                            <b>$<?php echo 'cro_' . $this->_slug; ?> = cro_posts_by_taxonomy(<i>array $cro_params</i>, <i>array $args = null</i>);</b>
                                            </br>
                                            </br>
                                            <b>Parameters #</b>
                                            </br>
                                            <i><b>$cro_params</b> (array) required.</i> See below for available items.
                                            </br>
                                            &nbsp;&nbsp;$cro_params = array( </br>
                                                &nbsp;&nbsp;&nbsp;&nbsp;'post_type' => (string) post type slug, </br>
                                                &nbsp;&nbsp;&nbsp;&nbsp;'taxonomy' => (string) taxonomy slug, </br>
                                                &nbsp;&nbsp;&nbsp;&nbsp;'term' => (string) term slug, </br>
                                            &nbsp;&nbsp;);
                                            </br>
                                            <i><b>$args</b> (array) optional. An array of parameters that supports <i>get_posts()</i> function</i>
                                        <?php } ?>
                                    </code>
                                </td>
                            </tr>
                    </table>
                </div>
            </div>
            <?php
            }
        }
    }

    function cro_panel() {

        $cro_panel = new croPanel();
        $cro_panel->initialize();
    }

    cro_panel();
endif;
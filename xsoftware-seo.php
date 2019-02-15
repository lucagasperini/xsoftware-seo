<?php

/*
Plugin Name: XSoftware SEO
Description: SEO management on wordpress.
Version: 1.0
Author: Luca Gasperini
Author URI: https://xsoftware.it/
Text Domain: xsoftware_seo
*/

if(!defined("ABSPATH")) die;

class xs_seo_plugin
{

        private $default = array (
                'post_types' => [
                        'post',
                        'page'
                ],
                'fields' => [
                        'descr' => [
                                'name' => 'Description',
                                'type' => 'text'
                        ],
                        'keyword' => [
                                'name' => 'Keywords',
                                'type' => 'text'
                        ],
                        'thumb' => [
                                'name' => 'Thumbnail',
                                'type' => 'img'
                        ],
                        'type' => [
                                'name' => 'Type',
                                'type' => 'i'
                        ],
                        'locale' => [
                                'name' => 'Locale',
                                'type' => 'lang'
                        ]
                ]
        );
        
        private $prefix = 'xs_seo_meta_';
        
        private $options = array( );
        
        function __construct()
        {
                add_action('admin_menu', array($this, 'admin_menu'));
                add_action('admin_init', array($this, 'section_menu'));
                add_action('save_post', array($this,'save'), 10, 2 );
                add_action('add_meta_boxes', array($this, 'metaboxes'));
                add_action('wp_head', array($this, 'head'));
                
                $this->options = get_option('xs_options_seo', $this->default);
        }
        
        function metaboxes()
        {
                add_meta_box(
                        'xs_seo_metaboxes', 
                        'XSoftware SEO', 
                        array($this,'metaboxes_print'), 
                        $this->options['post_types'],
                        'advanced',
                        'high'
                );
        }
        
        function metaboxes_print($post)
        {
                $values = get_post_custom( $post->ID );
                
                xs_framework::init_admin_style();
                
                foreach($this->options['fields'] as $key => $single) {
                        $selected[$key] = $single;
                        $selected[$key]['value'] = isset( $values[$this->prefix.$key][0] ) ? $values[$this->prefix.$key][0] : '';
                }
                
                $data = array();
                
                foreach($selected as $key => $single) {
                        switch($single['type']) {
                                case 'img':
                                        $data[$key][0] = $single['name'].':';
                                        $data[$key][1] = xs_framework::create_select_media_gallery([
                                                'src' => $single['value'],
                                                'width' => 150,
                                                'height' => 150,
                                                'alt' => $single['name'],
                                                'id' => $this->prefix.$key,
                                        ]);
                                        break;
                                case 'lang':
                                        $languages = xs_framework::get_available_language();
                
                                        $data[$key][0] = $single['name'].':';
                                        $data[$key][1] = xs_framework::create_select( array(
                                                'name' => $this->prefix.$key, 
                                                'selected' => $single['value'], 
                                                'data' => $languages,
                                                'default' => 'Select a Language'
                                        ));
                                        break;
                                case 'text':
                                        $data[$key][0] = $single['name'].':';
                                        $data[$key][1] = xs_framework::create_textarea( array(
                                                'class' => 'xs_full_width', 
                                                'name' => $this->prefix.$key,
                                                'text' => $single['value']
                                        ));
                                        break;
                                default:
                                        $data[$key][0] = $single['name'].':';
                                        $data[$key][1] = xs_framework::create_input( array(
                                                'class' => 'xs_full_width', 
                                                'name' => $this->prefix.$key,
                                                'value' => $single['value']
                                        ));
                        }
                        
                }
                
                xs_framework::create_table(array('class' => 'xs_full_width', 'data' => $data ));
        }
        
        function save($post_id, $post)
        {
                $post_type = get_post_type($post_id);
                if (!in_array($post_type, $this->options['post_types'])) return;
                
                foreach($this->options['fields'] as $key => $single) {
                        if(isset($_POST[$this->prefix.$key]))
                                update_post_meta( $post_id, $this->prefix.$key, $_POST[$this->prefix.$key] );
                }
        }
        
        function admin_menu()
        {
                add_submenu_page( 'xsoftware', 'XSoftware SEO','SEO', 'manage_options', 'xsoftware_seo', array($this, 'menu_page') );
        }
        
        
        public function menu_page()
        {
                if ( !current_user_can( 'manage_options' ) )  {
                        wp_die( __( 'Exit!' ) );
                }
                
                xs_framework::init_admin_style();
                xs_framework::init_admin_script();
                
                echo '<div class="wrap">';
                
                echo '<form action="options.php" method="post">';

                settings_fields('xs_seo_setting');
                do_settings_sections('xs_seo');

                submit_button( '', 'primary', 'submit', true, NULL );
                echo '</form>';
                
                echo '</div>';
               
        }

        function section_menu()
        {
                register_setting( 'xs_seo_setting', 'xs_options_seo', array($this, 'input') );
                add_settings_section( 'xs_seo_section', 'Settings', array($this, 'show'), 'xs_seo' );
        }

        function show()
        {

        }

        function input($input)
        {
                $current = $this->options;
                return $current;
        }
        
        function head()
        {
                global $post;
                
                $values = get_post_custom( $post->ID );
                
                foreach($this->options['fields'] as $key => $single) {
                        $meta_tags[$key] = isset( $values[$this->prefix.$key][0] ) ? $values[$this->prefix.$key][0] : '';
                }
                
                $permalink = get_permalink($post->ID);
                $title = $post->post_title;
                $name = get_bloginfo( 'name' );
                var_dump($meta_tags);
                
                echo '<meta name="twitter:card" content="summary"/>'; //FORCED
                if(!empty($meta_tags['descr'])) {
                        echo '<meta name="description" content="'.$meta_tags['descr'].'"/>';
                        echo '<meta property="og:description" content="'.$meta_tags['descr'].'" />';
                        echo '<meta name="twitter:description" content="'.$meta_tags['descr'].'" />';
                }
                
                if(!empty($name))
                        echo '<meta property="og:site_name" content="'.$name.'"/>';
                        
                if(!empty($meta_tags['keyword']))
                        echo '<meta name="keywords" content="'.$meta_tags['keyword'].'"/>';
                        
                /*if(isset($meta_tags["sydney_meta_robots"]))
                        echo '<meta name="robots" content="'.$meta_tags["sydney_meta_robots"][0].'"/>';
                      */  
                if(!empty($title)) {
                        echo '<meta property="og:title" content="'.$title.'" />';
                        echo '<meta name="twitter:title" content="'.$title.'" />';
                }
                if(!empty($permalink))
                        echo '<meta property="og:url" content="'.$permalink.'" />';
                        
                if(!empty($meta_tags['type']))
                        echo '<meta property="og:type" content="'.$meta_tags['type'].'" />';
                        
                if(!empty($meta_tags['thumb'])) {
                        list($width, $height) = getimagesize($meta_tags['thumb']);
                        echo '<meta property="og:image" content="'.$meta_tags['thumb'].'" />';
                        echo '<meta name="twitter:image" content="'.$meta_tags['thumb'].'" />';
                        echo '<meta property="og:image:width" content="'.$width.'" />';
                        echo '<meta name="og:image:height" content="'.$height.'" />';
                        
                        
                }
                /*
                if(isset($meta_tags["sydney_meta_og_video"][0]))
                        echo '<meta property="og:video" content="'.$meta_tags["sydney_meta_og_video"][0].'" />';
                        
                if(isset($meta_tags["sydney_meta_og_audio"][0]))
                        echo '<meta property="og:audio" content="'.$meta_tags["sydney_meta_og_audio"][0].'" />';
                */
        }
}

$xs_seo_plugin = new xs_seo_plugin();

?>

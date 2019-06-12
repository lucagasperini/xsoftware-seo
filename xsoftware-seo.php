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

include 'xsoftware-seo-options.php';

if (!class_exists("xs_seo_plugin")) :

class xs_seo_plugin
{
        private $prefix = 'xs_seo_meta_';

        private $options = [];

        function __construct()
        {
                add_action('save_post', [$this,'save'], 10, 2 );
                add_action('add_meta_boxes', [$this, 'metaboxes']);
                add_action('wp_head', [$this, 'head']);

                $this->options = get_option('xs_options_seo');
        }

        function metaboxes()
        {
                add_meta_box(
                        'xs_seo_metaboxes',
                        'XSoftware SEO',
                        [$this,'metaboxes_print'],
                        $this->options['post_type'],
                        'advanced',
                        'high'
                );
        }

        function metaboxes_print($post)
        {
                $values = get_post_custom( $post->ID );

                xs_framework::init_admin_script();
                xs_framework::init_admin_style();
                wp_enqueue_media();

                $data = array();

                foreach($this->options['fields'] as $key => $single) {
                        $tmp['name'] = $this->prefix.$key;
                        $tmp['label'] = $single['name'];
                        $tmp['class'] = 'xs_full_width';
                        $tmp['type'] = $single['type'];
                        $tmp['value'] = isset($values[$this->prefix.$key][0]) ?
                                $values[$this->prefix.$key][0] :
                                '';
                        if($tmp['type'] === 'img')
                                $tmp['id'] = $tmp['name'];

                        $data[] = $tmp;
                }


                xs_framework::html_input_array_to_table(
                        $data,
                        [ 'class' => 'xs_full_width' ]
                );
        }

        function save($post_id, $post)
        {
                $post_type = get_post_type($post_id);
                if (!in_array($post_type, $this->options['post_type'])) return;

                foreach($this->options['fields'] as $key => $single) {
                        if(isset($_POST[$this->prefix.$key]))
                                update_post_meta(
                                        $post_id,
                                        $this->prefix.$key,
                                        $_POST[$this->prefix.$key]
                                );
                }
        }
        /* TODO: Add personal definition for meta */
        function head()
        {
                global $post;

                if(empty($post))
                        return;

                $values = get_post_custom( $post->ID );

                foreach($this->options['fields'] as $key => $single) {
                        $meta_tags[$key] = isset( $values[$this->prefix.$key][0] ) ? $values[$this->prefix.$key][0] : '';
                }

                $permalink = get_permalink($post->ID);
                $title = $post->post_title;
                $name = get_bloginfo( 'name' );

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

        }
}

endif;

$xs_seo_plugin = new xs_seo_plugin();

?>

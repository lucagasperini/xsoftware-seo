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

if (!class_exists("xs_seo_options")) :

class xs_seo_options
{

        private $default = [
                'post_type' => [
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
                                'type' => 'line'
                        ],
                        'locale' => [
                                'name' => 'Locale',
                                'type' => 'lang'
                        ]
                ]
        ];

        private $options = [];

        function __construct()
        {
                add_action('admin_menu', [$this, 'admin_menu']);
                add_action('admin_init', [$this, 'section_menu']);

                $this->options = get_option('xs_options_seo', $this->default);
        }

        function admin_menu()
        {
                add_submenu_page(
                        'xsoftware',
                        'XSoftware SEO',
                        'SEO',
                        'manage_options',
                        'xsoftware_seo',
                        [$this, 'menu_page']
                );
        }


        public function menu_page()
        {
                if ( !current_user_can( 'manage_options' ) )  {
                        wp_die( __( 'Exit!' ) );
                }




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
                register_setting( 'xs_seo_setting', 'xs_options_seo', [$this, 'input'] );
                add_settings_section( 'xs_seo_section', 'Settings', [$this, 'show'], 'xs_seo' );
        }

        function show()
        {
                $tab = xs_framework::create_tabs( [
                        'href' => '?page=xsoftware_seo',
                        'tabs' => [
                                'field' => 'Fields',
                                'post' => 'Post Types'
                        ],
                        'home' => 'field',
                        'name' => 'main_tab'
                ]);

                switch($tab) {
                        case 'field':
                                $this->show_fields();
                                return;
                        case 'post':
                                $this->show_post_type();
                                return;
                }
        }

        function input($input)
        {
                $current = $this->options;

                foreach($input as $key => $value) {
                        if($key == 'post_type')
                                $current[$key] = array_keys($value);
                }

                if(isset($input['fields'])) {
                        $f = $input['fields'];
                        if(
                                isset($f['new']) &&
                                !empty($f['new']['code']) &&
                                !empty($f['new']['name']) &&
                                !empty($f['new']['type'])
                        ) {
                                $code = $f['new']['code'];
                                unset($f['new']['code']);
                                $current['fields'][$code] = $f['new'];
                        }
                        if(!empty($f['delete'])) {
                                unset($current['fields'][$f['delete']]);
                        }
                }

                return $current;
        }

        function show_fields()
        {
                $fields = $this->options['fields'];

                $headers = array('Actions', 'Code', 'Name', 'Type');
                $data = array();
                $types = xs_framework::html_input_array_types();

                foreach($fields as $key => $single) {
                        $data[$key][0] = xs_framework::create_button(array(
                                'name' => 'xs_options_seo[fields][delete]',
                                'class' => 'button-primary',
                                'value' => $key,
                                'text' => 'Remove'
                        ));
                        $data[$key][1] = $key;
                        $data[$key][2] = $single['name'];
                        $data[$key][3] = $types[$single['type']];
                }

                $new[0] = '';
                $new[1] = xs_framework::create_input([
                        'name' => 'xs_options_seo[fields][new][code]'
                ]);
                $new[2] = xs_framework::create_input([
                        'name' => 'xs_options_seo[fields][new][name]'
                ]);
                $new[3] = xs_framework::create_select(array(
                        'name' => 'xs_options_seo[fields][new][type]',
                        'data' => $types
                ));

                $data[] = $new;

                xs_framework::create_table(array(
                        'class' => 'xs_admin_table xs_full_width',
                        'headers' => $headers,
                        'data' => $data
                ));
        }

        function show_post_type()
        {
                echo '<h2>Filter Post Type</h2>';
                // get the information that actually is in the DB
                $options = isset($this->options['post_type']) ? $this->options['post_type'] : '';

                $post_types = get_post_types(['_builtin' => false]); // get all custom post types
                $post_types['post'] = 'post'; // add default post type
                $post_types['page'] = 'page'; // add default post type

                $headers = ['Enable / Disable', 'Post types'];
                $data_table = array();
                foreach($post_types as $post_type) {
                        $data_table[$post_type][0] = xs_framework::create_input_checkbox([
                                'name' => 'xs_options_seo[post_type]['.$post_type.']',
                                'compare' => in_array($post_type, $options)
                        ]);
                        $data_table[$post_type][1] = $post_type;

                }
                xs_framework::create_table([
                        'headers' => $headers,
                        'data' => $data_table,
                        'class' => 'widefat fixed'
                ]);
        }
}

endif;

$xs_seo_options = new xs_seo_options();

?>

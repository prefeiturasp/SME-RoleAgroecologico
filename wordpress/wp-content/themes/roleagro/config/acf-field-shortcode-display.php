<?php

if (!class_exists('ACF_Field_Shortcode_Display')) {
 
    class ACF_Field_Shortcode_Display extends acf_field {

        public $name;
        public $label;
        public $category;
        public $defaults;
       
        function __construct() {
            $this->name = 'shortcode_display';
            $this->label = __('Exibição de Shortcode', 'txtdomain');
            $this->category = 'layout';
            $this->defaults = array(
                'shortcode' => ''
            );
           
            parent::__construct();
        }
       
        function render_field_settings($field) {
            // Configuração do shortcode
            acf_render_field_setting($field, array(
                'label'        => __('Shortcode'),
                'instructions' => __('Insira o shortcode que será executado'),
                'type'         => 'text',
                'name'         => 'shortcode',
            ));
        }
       
        function render_field($field) {
            if (empty($field['shortcode'])) {
                echo '<p>Nenhum shortcode configurado.</p>';
                return;
            }
           
            // Executa o shortcode tanto no admin quanto no front
            echo do_shortcode($field['shortcode']);
        }
       
        function format_value($value, $post_id, $field) {
            // Garante que o shortcode seja executado no front-end também
            return do_shortcode($field['shortcode']);
        }
    }

}
 
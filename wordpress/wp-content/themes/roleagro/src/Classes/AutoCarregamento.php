<?php 

namespace App\Classes;

class AutoCarregamento {
	public function __construct(){

		$this->loadDependencesPublic();
		add_action( 'admin_enqueue_scripts', [$this, 'loadDependencesAdmin'] );
	}

	public function loadDependencesPublic(){
		
		add_action('init', array($this, 'custom_formats_public'));
		
	}
	
	public function loadDependencesAdmin(){
		
		$screen = get_current_screen();
		$post_types = ['post_up', 'post_inscricao', 'post_transporte'];

		if ( in_array( $screen->post_type, $post_types ) ) {
			wp_enqueue_script('jquery-mask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js', ['jquery'], null, true);
			wp_enqueue_script('admin-scripts', get_template_directory_uri() . '/src/Views/assets/admin/js/admin-scripts.js', ['jquery'], null, true);
			wp_enqueue_style('admin-styles', get_template_directory_uri() . '/src/Views/assets/admin/css/admin-styles.css');
		}
	}

    public function custom_formats_public(){

		wp_enqueue_style('fontawesome', 'https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css', array(), '4.7.0', 'all');
		wp_enqueue_style('bootstrap',  VIEWS_PATH . '/assets/lib/bootstrap/css/bootstrap.min.css', array(), '4.1.3', 'all');
		wp_enqueue_script('popper', VIEWS_PATH . '/assets/lib/bootstrap/js/popper.min.js', array('jquery'), '4.1.3', true);
		wp_enqueue_script('bootstrap', VIEWS_PATH . '/assets/lib/bootstrap/js/bootstrap.min.js', array('jquery'), '4.1.3', true);
		
		// Select2
		wp_register_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', [], '4.0.13', true );
		wp_register_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', [], '4.0.13');
		
		// Import Libs
		wp_enqueue_script('jquery-mask', VIEWS_PATH . '/assets/js/lib/jquery.mask.min.js',  '1.14.16', 'all', true);
		wp_enqueue_style('sweetalert',  VIEWS_PATH . '/assets/css/lib/sweetalert2.min.css', array(), '1.0', 'all');
		wp_enqueue_script('sweetalert', VIEWS_PATH . '/assets/js/lib/sweetalert2.all.min.js',  '11.19.1', 'all', true);
		wp_enqueue_style('toastr',  VIEWS_PATH . '/assets/css/lib/toastr.min.css', array(), '1.0', 'all');
		wp_enqueue_script('toastr', VIEWS_PATH . '/assets/js/lib/toastr.min.js',  '1.0', 'all', true);

		wp_enqueue_style('admin',  VIEWS_PATH . '/assets/css/admin.css', array(), '1.0', 'all');
		wp_enqueue_script('roteiro', VIEWS_PATH . '/assets/js/roteiros.js',  '1.0', 'all', true);
		wp_enqueue_style('main',  VIEWS_PATH . '/assets/css/main.css', array(), '1.0', 'all');
		wp_enqueue_script('main', VIEWS_PATH . '/assets/js/main.js',  '1.0', 'all', true);
		
		wp_register_script('admin-main', VIEWS_PATH . '/assets/js/admin-main.js',  '1.0', 'all', true);

		// Swiper CSS & JS
		wp_register_style('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0');
		wp_register_script('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true);

		// RangerPick CSS & JS
		wp_register_style('ranger-pick', VIEWS_PATH . '/assets/lib/rangerpick/css/daterangepicker.css', [], '11.0.0');
		wp_register_script('ranger-pick', VIEWS_PATH . '/assets/lib/rangerpick/js/daterangepicker.min.js', [], '11.0.0', true);

		// Fancybox CSS & JS
		wp_register_style('fancybox', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.0/dist/fancybox/fancybox.css', [], '6.0.0');
		wp_register_script('fancybox', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.0/dist/fancybox/fancybox.umd.js', [], '4.0.0', true);

		// Moment.js
		wp_register_script('moment', 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js', [], '2.29.4', true);
		wp_register_script('moment-locale', 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/locale/pt-br.min.js', ['moment'], '2.30.1', true);
		wp_register_script('moment-tz', 'https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.43/moment-timezone-with-data.min.js', ['moment'], '0.5.43', true);

		// CLNDR.js
		wp_register_script('underscore', 'https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.13.6/underscore-min.js', ['jquery'], '1.13.6', true);
		wp_register_script('clndr', 'https://cdnjs.cloudflare.com/ajax/libs/clndr/1.4.7/clndr.min.js', ['jquery', 'underscore'], '1.4.7', true);

		// Calendario de agendamentos
		wp_register_style('calendario', VIEWS_PATH . '/assets/css/calendario.css', [], '1.0.0');
		wp_register_script('calendario', VIEWS_PATH . '/assets/js/calendario.js', ['clndr'], '1.0.0', true);
		
		//Alpine.js
		wp_register_script('alpine', 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js', [], '3.0.0', true);
		wp_register_script('alpine-mask', 'https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/dist/cdn.min.js', [], '3.0.0', true);
		
		//Agendamento
		wp_enqueue_script('agendamento', VIEWS_PATH . '/assets/js/agendamento.js',  '1.0', 'all', true);
		wp_enqueue_script('admin-agendamento', VIEWS_PATH . '/assets/js/admin-agendamento.js',  '1.0', 'all', true);

		wp_enqueue_script('dietas-alunos', get_template_directory_uri() . '/src/Views/assets/js/dietas-alunos.js', array('jquery'), '1.0', true);

		wp_localize_script('dietas-alunos', 'ajax_params', array(
			'admin_ajax' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('nc_dietas_ue') // Cria um nonce para validação
		));

		wp_register_script('lista-presenca', VIEWS_PATH . '/assets/js/lista-presenca.js',  '1.0', 'all', true);

    }
}
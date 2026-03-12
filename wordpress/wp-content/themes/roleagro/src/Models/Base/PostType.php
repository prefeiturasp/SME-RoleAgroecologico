<?php

namespace App\Models\Base;

use WP_Query;

abstract class PostType {
  
	protected string $post_type;

	public function __construct(string $post_type)
	{
		$this->post_type = $post_type;
	}

	public function all(int $limit = -1)
	{
		return new WP_Query([
		'post_type' => $this->post_type,
		'post_status' => 'publish',
		'posts_per_page' => $limit,
		]);
	}

  	abstract public function getParams(): array;

	public function register(): void
	{
		$p = $this->getParams();

		$slug = sanitize_title($p['key'] ?? $p['slug']);
		$post_type_slug = 'post_' . $slug;
		$visibility = $p['visibility'] ?? 'public';

		// Defaults
		$public = true;
		$publicly_queryable = true;
		$exclude_from_search = false;

		if ($visibility === 'private') {
		$public = false;
		$publicly_queryable = false;
		} elseif ($visibility === 'owner_only') {
		$exclude_from_search = true;
		}
		
		register_post_type($post_type_slug, [
		'labels' => [
			'name'               => sanitize_text_field($p['name']),
			'singular_name'      => sanitize_text_field($p['singular_name']),
			'menu_name'          => sanitize_text_field($p['name']),
			'parent_item_colon'  => __('Pai:'),
			'all_items'          => __('Listar todos'),
			'view_item'          => __('Visualizar'),
			'add_new_item'       => __('Adicionar ') . $p['singular_name'],
			'edit_item'          => __('Editar ') . $p['singular_name'],
			'update_item'        => __('Atualizar ') . $p['singular_name'],
			'search_items'       => __('Pesquisar ') . $p['singular_name'],
			'not_found'          => __('Registro não encontrado'),
			'not_found_in_trash' => __('Nenhum registro na lixeira'),
		],
		'menu_icon'           => !empty($p['dashicon']) ? sanitize_text_field($p['dashicon']) : 'dashicons-admin-post',
		'public'              => $public,
		'publicly_queryable'  => $publicly_queryable,
		'show_ui'             => true,
		'has_archive'         => false,
		'rewrite'             => $publicly_queryable ? ['slug' => sanitize_title($p['rewrite_slug'] ?? $p['singular_name'])] : false,
		'exclude_from_search' => $exclude_from_search,
		'supports'            => $p['supports'] ?? ['title', 'editor', 'thumbnail', 'excerpt'],
		'capability_type'     => $slug,
		'map_meta_cap'        => true,
		]);

		if (!empty($p['taxonomy']) && is_array($p['taxonomy'])) {
		foreach ($p['taxonomy'] as $value) {
			$taxonomy_slug = 'tax_' . $slug . '_' . sanitize_title($value['slug']);

			register_taxonomy(
				$taxonomy_slug,
				$post_type_slug,
				[
					'label'        => sanitize_text_field($value['name']),
					'rewrite'      => [
					'slug' => $slug . '-' . sanitize_title($value['name']),
					],
					'hierarchical' => true,
					'capabilities' => $this->generate_taxonomy_caps( sanitize_title( $value['name'] ) ),
					'meta_box_cb' => $value['meta_box_cb'] ?? true
				]
			);
		}
		}
	}

	private function generate_taxonomy_caps( string $slug ) {
		return [
			'manage_terms' => "manage_{$slug}",
			'edit_terms'   => "edit_{$slug}",
			'delete_terms' => "delete_{$slug}",
			'assign_terms' => "assign_{$slug}",
		];
	}
}

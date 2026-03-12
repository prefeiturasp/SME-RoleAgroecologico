<?php

namespace App\Models;

use App\Models\Base\PostType;

class Roteiro extends PostType {

    public function __construct() {
        parent::__construct('post_roteiro');
    }
    
    public function getParams(): array {
        return [
            'key'            => 'roteiro',
            'slug'           => 'roteiro',
            'name'           => 'Roteiros',
            'singular_name'  => 'roteiro',
            'dashicon'       => 'dashicons-location-alt',
            'supports'       => [ 'title', 'thumbnail' ],
            'visibility'     => 'public',
        ];
    }
}
<?php
/**
 * Theme Core
 *
 * @package WordPress
 */

namespace App\Classes;

use App\Classes\RegisterPostTypes;

class Core {

    public function __construct() {

      add_action('init', [ RegisterPostTypes::class, 'run' ]);


    }
}

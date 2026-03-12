<?php

namespace App\Classes;

use App\Models\Inscricao;
use App\Models\Roteiro;
use App\Models\Transporte;
use App\Models\UnidadesProdutivas;

class RegisterPostTypes {

    public static function run() {
        $postTypes = [
            new Roteiro,
            new UnidadesProdutivas,
            new Inscricao,
            new Transporte
        ];

        foreach ($postTypes as $postType) {
            $postType->register();
        }
    }
}
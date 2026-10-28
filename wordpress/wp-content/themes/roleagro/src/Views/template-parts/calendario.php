<?php

wp_enqueue_script( 'moment' );
wp_enqueue_script( 'calendario' );
wp_localize_script( 'calendario', 'calendarioApi', [
    'disponibilidadeDia' => rest_url( 'agendamento/disponibilidade-dia' ),
] );
wp_enqueue_style( 'calendario' );

?>

<div class="mt-4" id="calendario"><!-- Calendário adicionado via javascript --></div>
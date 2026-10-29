<?php
/**
 * Registrar após carregar a classe EnviaEmail\classes\Envia_Emails.
 * Evento único agendado por vivência e Unidade Produtiva.
 */
add_action( 'verificar_followup_avaliacao_unidade', function ( $vivencia_id, $unidade_id ) {
    $vivencia_id = absint( $vivencia_id );
    $unidade_id = absint( $unidade_id );

    if ( ! $vivencia_id || ! $unidade_id ) {
        return;
    }

    if ( get_post_meta( $vivencia_id, 'status_inscricao', true ) !== 'realizado' ) {
        return;
    }

    if ( ! unidade_pertence_vivencia( $vivencia_id, $unidade_id ) ) {
        return;
    }

    if ( avaliacao_unidade_ja_realizada( $vivencia_id, $unidade_id ) ) {
        return;
    }

    new \EnviaEmail\classes\Envia_Emails(
        $vivencia_id,
        'followup_avaliacao_unidades_produtivas',
        $unidade_id
    );
}, 10, 2 );

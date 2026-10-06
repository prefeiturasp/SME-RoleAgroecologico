<?php

function salvar_participantes_adicionais( $post_id ) {

    /*
     * Ignora options pages, usuários etc.
     */
    if ( ! is_numeric( $post_id ) ) {
        return;
    }

    $post_id = (int) $post_id;

    /*
     * Ignora autosave e revisões.
     */
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    /*
     * OPCIONAL:
     * Restrinja ao CPT correto.
     *
     * Troque "seu_cpt" pelo nome real do post type.
     */
    if ( get_post_type( $post_id ) !== 'post_inscricao' ) {
        return;
    }

    /*
     * Busca o conteúdo atual de dados_acompanhantes.
     *
     * O WordPress já devolve o valor desserializado.
     */
    $acompanhantes = get_post_meta(
        $post_id,
        'dados_acompanhantes',
        true
    );

    if ( ! is_array( $acompanhantes ) ) {
        $acompanhantes = [];
    }

    /*
     * Remove SOMENTE os registros que foram adicionados
     * anteriormente pelo repeater.
     *
     * Os registros originais permanecem intactos.
     */
    $acompanhantes = array_filter(
        $acompanhantes,
        function ( $acompanhante ) {

            if ( ! is_array( $acompanhante ) ) {
                return true;
            }

            return empty( $acompanhante['_participante_adicional'] );
        }
    );

    /*
     * Busca os dados atuais do repeater.
     *
     * Como estamos na prioridade 20 do acf/save_post,
     * o ACF já salvou os valores.
     */
    $participantes = get_field(
        'participantes_adicionais',
        $post_id
    );

    if ( is_array( $participantes ) ) {

        foreach ( $participantes as $participante ) {

            /*
             * Converte o valor do select para o formato
             * esperado em dados_acompanhantes.
             */
            $tipo = '';

            switch ( $participante['tipo'] ?? '' ) {

                case 'educador':
                    $tipo = 'Educador';
                    break;

                case 'externo':
                    $tipo = 'Externo';
                    break;
            }

            /*
             * Acrescenta o participante na estrutura existente.
             */
            $acompanhantes[] = [
                'rf'                   => $participante['rf_cpf'] ?? '',
                'nome'                 => $participante['nome_completo'] ?? '',
                'tipo'                 => $tipo,
                'celular'              => $participante['celular_contato'] ?? '',
                'data_nascimento'      => $participante['data_nascimento'] ?? '',
                'dieta'                => $participante['dieta'] ?? '',
                'necessidades'         => $participante['necessidade'] ?? '',
                'confirmacaoPresenca'  => 1,
                '_participante_adicional' => true,
            ];
        }
    }

    /*
     * Reorganiza os índices:
     *
     * 0, 1, 2, 3...
     */
    $acompanhantes = array_values( $acompanhantes );

    /*
     * Atualiza o meta.
     *
     * NÃO é necessário serialize().
     * O WordPress faz isso automaticamente.
     */
    update_post_meta(
        $post_id,
        'dados_acompanhantes',
        $acompanhantes
    );
}

add_action( 'acf/save_post', 'salvar_participantes_adicionais', 20 );
<?php 

extract( $args ); 

?>

<div class="acf-actions p-2">
    <a class="btn btn-success-admin" id="btn-solicitar-documentos">
        <span class="dashicons dashicons-email"></span>&nbsp;Solicitar documentos
    </a>
</div>

<table class="table">
    <thead>
        <tr>
            <th scope="col"></th>
            <th scope="col">Cód. EOL e Nome do Estudante</th>
            <th scope="col">Turma</th>
            <th scope="col">Deficiência</th>
            <th scope="col">Dieta Especial</th>
            <th scope="col">Documentos Válidos</th>
        </tr>
    </thead>
    <tbody> 
        <?php if ( ! is_array( $arrTurmas ) || count( $arrTurmas ) == 0 ) : ?>
            <tr>
                <td colspan="6" class="text-center">Nenhum estudante encontrado.</td>
            </tr>
        <?php else : ?>
            <?php $i = 1; ?>
            <?php foreach ( $arrTurmas as $turma ) : ?>
                <?php $nomeTurma = $turma['nomeTurma']; ?>
                <?php foreach ( $turma['alunosTurma'] as $aluno ) : 
                    
                    $status_ficha = get_status_ficha_aluno( $aluno['situacaoFicha'] ?? 'nao-enviado' );
                    $desabilita_opcao = !isset( $aluno['situacaoFicha'] ) || $aluno['situacaoFicha'] != 'analise';
                    $marca_opcao = isset( $aluno['situacaoFicha'] ) && $aluno['situacaoFicha'] == 'validado';
                    ?>
                    <tr>
                        <th scope="row">
                            <?php echo esc_html( $i ); ?>
                        </th>
                        <td>
                            <span>
                                <?php echo esc_html( $aluno['codigoAluno'] . ' - ' . $aluno['nomeAluno'] ); ?>
                            </span><br>
                            <small>
                                Termo/Autorização:
                                <span class="text-status <?php echo esc_attr( $status_ficha['classe'] ); ?>">
                                    <?php echo esc_html( $status_ficha['texto'] ); ?>
                                </span>
                            </small>
                        </td>
                        <td>
                            <?php echo esc_html( $nomeTurma ); ?>
                        </td>
                        <td>
                            <?php if($aluno['possuiDeficiencia'] == 1){
                                echo 'Possui deficiência';
                             } ?>
                        </td>
                        <td>
                             <?php 
                             if(isset($aluno['possuiDieta']) && $aluno['possuiDieta'] == 1){

                                if(isset($aluno['uuid_dieta'])){
                                    $url_arquivo_dieta = add_query_arg([
                                        'action' => 'baixar_dieta_aluno',
                                        'uuid' => $aluno['uuid_dieta']
                                    ], admin_url( 'admin-ajax.php' ));
                                } else {
                                    $url_arquivo_dieta = '#';
                                }
                            
                                echo '<a href="'.$url_arquivo_dieta.'"><b>Dieta '.$aluno['classificacaoDieta'].'</b></a>';
                             } ?>
                        </td>
                        <td class="text-center">
                            <input
                                type="checkbox"
                                value="<?php echo esc_attr( $aluno['codigoAluno'] ); ?>"
                                name="documentos_validos[]"
                                id="docs_validos_aluno<?php echo esc_attr( $aluno['codigoAluno'] ); ?>"
                                <?php echo esc_html( $desabilita_opcao ? 'disabled' : '' ); ?>
                                <?php echo checked( $marca_opcao ); ?>
                                >
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
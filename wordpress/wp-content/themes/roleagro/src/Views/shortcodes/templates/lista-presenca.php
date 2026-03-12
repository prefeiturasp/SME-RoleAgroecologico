<?php 

extract( $args ); 

use App\Controllers\AgendamentoController;

$arr = AgendamentoController::verifica_lista_presenca_inscritos($idIsncricao, $arrTurmas, $arrEducadores, $arrAcompanhantes);

$arrTurmas = $arr['arrTurmas'];
$arrEducadores = $arr['arrEducadores'];
$arrAcompanhantes = $arr['arrAcompanhantes'];

?>
<table class="table">
    <thead class="thead-light">
        <tr>
            <th scope="col"></th>
            <th scope="col">Cód. EOL</th>
            <th scope="col">Nome do Estudante</th>
            <th scope="col">Turma</th>
            <th scope="col">Presença</th>
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
                <?php foreach ( $turma['alunosTurma'] as $aluno ) : ?>
                    <tr>
                        <th scope="row"><?= $i.'.'; ?></th>
                        <td><?= esc_html( $aluno['codigoAluno']); ?></td>
                        <td><?= esc_html($aluno['nomeAluno'] ); ?></td>
                        <td><?= esc_html( $nomeTurma ); ?></td>
                        <td><?= isset($aluno['confirmacaoPresenca']) && $aluno['confirmacaoPresenca'] == true ? '<span class="negrito-verde">Presente</span>' : '<span class="negrito-laranja">Ausente</span>'; ?></td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>


<table class="table">
    <thead class="thead-light">
        <tr>
            <th scope="col"></th>
            <th scope="col">RF/CPF dos Acompanhantes</th>
            <th scope="col">Nome do Acompanhante</th>
            <th scope="col">Presença</th>
        </tr>
    </thead>
    <tbody> 
        <?php 
        $arrEduAco = array_merge($arrEducadores, $arrAcompanhantes);
        if ( ! is_array( $arrEduAco ) || count( $arrEduAco ) == 0 ) : 
        ?>
            <tr>
                <td colspan="6" class="text-center">Nenhum estudante encontrado.</td>
            </tr>
        <?php else : ?>
            <?php $i = 1; ?>
                <?php foreach ( $arrEduAco as $eduAc ) : ?>
                    <tr>
                        <th scope="row"><?= $i.'.'; ?></th>
                        <td><?= $eduAc['rf']?></td>
                        <td><?= $eduAc['nome']?></td>
                        <td><?= isset($eduAc['confirmacaoPresenca']) && $eduAc['confirmacaoPresenca'] == true ? '<span class="negrito-verde">Presente</span>' : '<span class="negrito-laranja">Ausente</span>'; ?></td>
                    </tr>
                <?php $i++; endforeach; ?>    
        <?php endif; ?>
    </tbody>
</table>
<?php extract( $args ); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<style>
    body {
        font-family: sans-serif;
        margin: 0;
        padding: 0;
        font-size: 9px;
    }

    /* Cabeçalho verde com imagem e título */
    .header {
        background-color: #246427ff;
        color: white;
        padding: 12px 20px;
        display: table;
        width: 100%;
    }

    .header .logo {
        display: table-cell;
        width: 80px;
        vertical-align: middle;
        padding: 1rem;
    }

    .header .title {
        display: table-cell;
        width: 160px;
        vertical-align: middle;
        padding-left: 10px;
        font-size: 16px;
        font-weight: 500;
    }

    .header .spacer {
        display: table-cell;
        width: auto; /* ocupa o restante do espaço */
    }

    /* Tabela de dados */
    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .table-info thead tr {
        font-weight: bold;
    }

	table.zebra {
		margin-top: 20px;
	}

    table.zebra th, td {
        border: 1px solid #ccc;
        padding: 6px;
        text-align: center;
        word-wrap: break-word;
    }

	table.zebra th {
        background-color: #e0f2f1;
        font-weight: bold;
    }

	table.zebra tr:nth-child(even) td {
        background-color: #f1f8e9;
    }
</style>
</head>
<body>

<!-- Cabeçalho -->
<div class="header">
    <div class="logo">
        <img src="<?php echo esc_url( $logo ); ?>" width="50" alt="Logo">
    </div>
    <div class="title">
      <p><?php echo esc_html( $titulo ); ?></p>
    </div>
	<div class="spacer"></div>
</div>
<table class="table-info">
    <thead>
        <tr>
            <td>Nome da Unidade Educacional:</td>
            <td>Endereço da UE:</td>
            <td>Nome do roteiro:</td>
            <td>Horários:</td>
            <td>Data do Rolê Agrorcológico:</td>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?php echo esc_html( $dados_roteiro['nome_ue'] ?? '-' ); ?></td>
            <td><?php echo esc_html( $dados_roteiro['endereco_ue'] ?? '-' ); ?></td>
            <td><?php echo esc_html( $dados_roteiro['nome_roteiro'] ?? '-' ); ?></td>
            <td><?php echo esc_html( $dados_roteiro['horarios'] ?? '-' ); ?></td>
            <td><?php echo esc_html( $dados_roteiro['data'] ?? '-' ); ?></td>
        </tr>
    </tbody>
    <thead>
        <tr>
            <td>Endereço do rolê:</td>
            <td>Endereço do rolê 2:</td>
            <td>DRE:</td>
            <td>Estudantes autorizados:</td>
            <td>Adultos acompanhantes:</td>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?php echo esc_html( $dados_roteiro['endereco_1'] ); ?></td>
            <td><?php echo esc_html( $dados_roteiro['endereco_2'] ); ?></td>
            <td><?php echo esc_html( $dados_roteiro['dre'] ?? '-' ); ?></td>
            <td><?php echo esc_html( $dados_roteiro['total_alunos'] ?? '-' ); ?></td>
            <td><?php echo esc_html( $dados_roteiro['total_acompanhantes'] ?? '-' ); ?></td>
        </tr>
    </tbody>
</table>

<table class="zebra">
    <thead>
        <tr>
            <th style="text-align: left;"><h2>Estudantes</h2></th>
        </tr>
    </thead>
</table>

<!-- Tabela de alunos -->
<?php if ( $turmas ) : ?>
    <?php foreach ( $turmas as $turma ) : ?>
        <table class="zebra">
            <thead>
                <tr>
                    <th>Presença</th>
                    <th>Código EOL</th>
                    <th width="250">Nome do estudante</th>
                    <th>Turma</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ( $turma['alunosTurma'] as $aluno ) :
                    $data_nascimento = new DateTime(  $aluno['dataNascimento'] );
                    ?>
                    <tr>
                        <td><input type="checkbox"></td>
                        <td><?php echo esc_html( $aluno['codigoAluno'] ?? '-' ); ?></td>
                        <td><?php echo esc_html( $aluno['nomeAluno'] ?? '-' ); ?></td>
                        <td><?php echo esc_html( $turma['nomeTurma'] ?? '-' ); ?></td>
                    </tr>
                    <?php
                endforeach;
                ?>
            </tbody>
        </table>
    <?php endforeach; ?>
<?php endif; ?>

<table class="zebra">
    <thead>
        <tr>
            <th style="text-align: left;"><h2>Educadores / Acompanhantes</h2></th>
        </tr>
    </thead>
</table>

<?php if ( isset( $acompanhantes ) && !empty( $acompanhantes ) ) : ?>
    <table class="zebra">
        <thead>
            <tr>
                <th>Presença</th>
                <th>RF/CPF dos educadores/acompanhantes</th>
                <th width="250">Nome dos educadores/acompanhantes</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ( $acompanhantes as $acompanhante ) :
                ?>
                <tr>
                    <td><input type="checkbox"></td>
                    <td><?php echo esc_html( $acompanhante['rf'] ?? '-' ); ?></td>
                    <td><?php echo esc_html( $acompanhante['nome'] ?? '-' ); ?></td>
                </tr>
                <?php
            endforeach;
            ?>
        </tbody>
    </table>
<?php endif; ?>


<table class="zebra">
    <thead>
        <tr>
            <th style="text-align: left;">Observações do Rolê</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td rowspan="10">
                <br>
                <br>
                <br>
                <br>
            </td>
        </tr>
    </tbody>
</table>


<br>
<p>Documento emitido em: <?php echo esc_html( obter_data_com_timezone( 'd/m/Y H:i:s', 'America/Sao_Paulo' ) ); ?></p>

</body>
</html>

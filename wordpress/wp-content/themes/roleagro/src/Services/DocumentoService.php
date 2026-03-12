<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use ZipArchive;

if ( ! defined( 'ABSPATH' ) ) exit;

class DocumentoService {
    
    private $base_uri;
    private $dompdf;

    public function __construct() {
        $this->base_uri = get_template_directory() . '/src/Views/templates';
        $dompdf_options = new Options();
        $dompdf_options->set('isRemoteEnabled', true);

        $this->dompdf = new Dompdf($dompdf_options);
    }

    public function gerar_pdf_ficha_aluno( int $inscricao_id, array $alunos, $storage = true ) {

        $conteudo = get_field( 'autorizacao_ficha_saude', 'options' );
        $logomarcas = renderizar_rodape_email();
        $template_base = file_get_contents($this->base_uri . '/base-pdf.html');
        $nome_ue = get_field( 'nome_da_unidade_educacional', $inscricao_id );
        $data_roteiro = get_field( 'data_reservada_para_o_roteiro', $inscricao_id );
        $hora_saida = get_field( 'horario_de_saida_da_ue', $inscricao_id );
        $hora_retorno = get_field( 'horario_previsto_de_retorno_a_ue', $inscricao_id );
        $destinos = roteiro_unidades_info( $inscricao_id );
        $ano = date( 'Y' );
        $logo = get_field( 'logo_topo', 'options' );
        $logo = str_replace( 'http://', 'https://', $logo );

        if ( is_array( $destinos ) && !empty( $destinos ) ) {
            $destinos = implode( ', ', wp_list_pluck( $destinos, 'post_title' ) );
        } else {
            $destinos = '-';
        }
    
        $html = '';
    
        foreach ( $alunos as $aluno ) {

            $template = $template_base;
            $template = str_replace( '{{CONTEUDO}}', $conteudo, $template );

            $template = str_replace( '{{ANO}}', $ano, $template );
            $template = str_replace( '{{DATA_AGENDAMENTO}}', date("d/m/Y", strtotime($data_roteiro)), $template );
            $template = str_replace( '{{DESTINOS}}', $destinos, $template );
            $template = str_replace( '{{HORARIO_SAIDA}}', $hora_saida, $template );
            $template = str_replace( '{{HORARIO_RETORNO}}', $hora_retorno, $template );
            $template = str_replace( '{{LOGOS_RODAPE}}', $logomarcas, $template );
            $template = str_replace( '{{LOGO_TOPO}}', $logo, $template );
            $template = str_replace( '{{FAIXA_RODAPE}}', get_template_directory_uri() . '/src/Views/assets/img/faixas.svg', $template );    
            $template = str_replace( '{{UNIDADE_EDUCACIONAL}}', $nome_ue, $template );

            $template = str_replace( '{{NOME_ESTUDANTE}}', $aluno['nomeAluno'], $template );
            $template = str_replace( '{{COD_EOL}}', $aluno['codigoAluno'], $template );
            $template = str_replace( '{{DATA_NASCIMENTO}}', date( 'd/m/Y', strtotime( $aluno['dataNascimento'] ) ), $template );
    
            $html .= $template;
    
        }

        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();

        if ( $storage ) {

            $output = $this->dompdf->output(); 
            return $this->storage( "/{$ano}/autorizacoes/{$inscricao_id}/autorizacoes-alunos.pdf", $output );
            
        } else {

            $nome_aluno = sanitize_title( $alunos[0]['nomeAluno'] );
            $nome_arquivo = "autorizacao-{$nome_aluno}-{$alunos[0]['codigoAluno']}";
            $this->dompdf->stream( $nome_arquivo, ["Attachment" => true] );
            exit;
        }
    }

    public function gerar_lista_participantes( int $inscricao_id ){
        $logo = get_field( 'logo_lista_participantes', 'options' );
        $turmas = get_post_meta( $inscricao_id, 'dados_turmas', true );
        $educadores = get_post_meta( $inscricao_id, 'dados_educadores', true );
        $acompanhantes = get_post_meta( $inscricao_id, 'dados_acompanhantes', true );
        $unidades_roteiro = roteiro_unidades_info( $inscricao_id );
        $acompanhantes_geral = array_merge( $educadores, $acompanhantes );

        // Informações do cabeçalho
        $info = [
            'nome_ue' => get_field( 'nome_da_unidade_educacional', $inscricao_id ),
            'endereco_ue' => '-',
            'nome_roteiro' => get_the_title( $inscricao_id ),
            'horarios' => get_field('horario_de_saida_da_ue', $inscricao_id ) . ' - ' . get_field( 'horario_previsto_de_retorno_a_ue', $inscricao_id ),
            'data' => get_field( 'data_reservada_para_o_roteiro', $inscricao_id ),
            'endereco_1' => '-',
            'endereco_2' => '-',
            'dre' => get_field( 'dre', $inscricao_id ),
            'total_acompanhantes' => count ($educadores ) + count( $acompanhantes ),
            'total_alunos' => array_reduce( $turmas, fn( $total, $turma ) => $total + count( $turma['alunosTurma'] ), 0 )
        ];

        foreach ( $unidades_roteiro as $key => $unidade ) {
            $logradouro = get_field( 'logradouro', $unidade->ID );
            $numero = get_field( 'numero', $unidade->ID ) ?: 's/n';
            $bairro = get_field( 'bairro', $unidade->ID ) ?: '-';
            $info['endereco_' . ( $key + 1 )] = "{$logradouro}, {$numero} - $bairro";
        }

        
        $base_path = get_theme_file_path( "storage/temp/{$inscricao_id}" );
        if ( !file_exists( $base_path ) ) {
            wp_mkdir_p($base_path);
        }

        // Gera o html das listas de participantes
        ob_start();
        get_template_part('src/Views/templates/base-lista-participantes', null, [
            'tipo_lista' => 'produtor',
            'logo' => $logo,
            'titulo' => 'Listagem de estudantes para produtores no Rolê Agroecológico',
            'dados_roteiro' => $info,
            'turmas' => $turmas,
            'acompanhantes' => $acompanhantes_geral,
        ]);
        $html_produtor = ob_get_clean();

        ob_start();
        get_template_part('src/Views/templates/base-lista-participantes', null, [
            'tipo_lista' => 'transporte',
            'logo' => $logo,
            'titulo' => 'Listagem de estudantes para transportes no Rolê Agroecológico',
            'dados_roteiro' => $info,
            'turmas' => $turmas,
            'acompanhantes' => $acompanhantes_geral,
        ]);
        $html_transporte = ob_get_clean();

        // Gera os arquivo .pdf de cada lista
        $arquivo_lista_produtor = $this->gerar_pdf_temp($html_produtor, "{$base_path}/lista-participantes-produtor.pdf");
        $arquivo_lista_transporte = $this->gerar_pdf_temp($html_transporte, "{$base_path}/lista-participantes-transporte.pdf");

        // Cria o arquivo .zip e adiciona os arquivos .pdf para download
        $zip_path = "{$base_path}/lista-participantes.zip";
        $zip = new ZipArchive();

        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Não foi possível criar o arquivo ZIP: {$zip_path}");
        }

        $zip->addFile($arquivo_lista_produtor, 'lista-participantes-produtor.pdf');
        $zip->addFile($arquivo_lista_transporte, 'lista-participantes-transporte.pdf');
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="lista-participantes.zip"');
        header('Content-Length: ' . filesize($zip_path));
        readfile($zip_path);

        // Remove os arquivos temporários
        unlink($arquivo_lista_produtor);
        unlink($arquivo_lista_transporte);
        unlink($zip_path);
        exit;
    }

    /**
     * Gera um PDF temporário a partir de HTML.
     */
    private function gerar_pdf_temp( string $html, string $path ){
        $options = new Options();
        $options->set( 'isRemoteEnabled', true );

        $dompdf = new Dompdf( $options );
        $dompdf->setPaper( 'A4', 'portrait' );
        $dompdf->loadHtml( $html );
        $dompdf->render();
        $output = $dompdf->output();

        wp_mkdir_p( dirname( $path ) );

        file_put_contents( $path, $output );
        return $path;
    }
    
    private function storage( string $path, $file ) {

        $upload_dir = get_theme_file_path( 'storage' );
        $file_path  = $upload_dir . $path;

        if ( ! file_exists( dirname( $file_path ) ) ) {
            wp_mkdir_p( dirname( $file_path ) );
        }

        file_put_contents( $file_path, $file );

        return $file_path;

    }

    public function gerar_planilha_lista_presenca( int $inscricao_id ){

        $logo = site_url('/wp-content/themes/roleagro/src/Views/assets/img/logo-role.png');
        $turmas = get_post_meta( $inscricao_id, 'dados_turmas', true );
        $educadores = get_post_meta( $inscricao_id, 'dados_educadores', true );
        $acompanhantes = get_post_meta( $inscricao_id, 'dados_acompanhantes', true );
        $unidades_roteiro = roteiro_unidades_info( $inscricao_id );
        $acompanhantes_geral = array_merge( $educadores, $acompanhantes );

        $dataVivencia = explode('-', get_field( 'data_reservada_para_o_roteiro', $inscricao_id ));
        $dataVivencia = $dataVivencia[2].'/'.$dataVivencia[1].'/'.$dataVivencia[0];

        // Informações do cabeçalho
        $info = [
            'nome_ue' => get_field( 'nome_da_unidade_educacional', $inscricao_id ),
            'endereco_ue' => '-',
            'nome_roteiro' => get_the_title( $inscricao_id ),
            'horarios' => get_field('horario_de_saida_da_ue', $inscricao_id ) . ' - ' . get_field( 'horario_previsto_de_retorno_a_ue', $inscricao_id ),
            'data' => $dataVivencia,
            'endereco_1' => '-',
            'endereco_2' => '-',
            'dre' => get_field( 'dre', $inscricao_id ),
            'total_acompanhantes' => count ($educadores ) + count( $acompanhantes ),
            'total_alunos' => array_reduce( $turmas, fn( $total, $turma ) => $total + count( $turma['alunosTurma'] ), 0 )
        ];

        foreach ( $unidades_roteiro as $key => $unidade ) {
            $logradouro = get_field( 'logradouro', $unidade->ID );
            $numero = get_field( 'numero', $unidade->ID ) ?: 's/n';
            $bairro = get_field( 'bairro', $unidade->ID ) ?: '-';
            $info['endereco_' . ( $key + 1 )] = "{$logradouro}, {$numero} - $bairro";
        }

        $base_path = get_theme_file_path( "storage/temp/{$inscricao_id}" );
        if ( !file_exists( $base_path ) ) {
            wp_mkdir_p($base_path);
        }

        // Gera o html das listas de presenca
        ob_start();
        get_template_part('src/Views/templates/base-planilha-lista-presenca', null, [
            'tipo_lista' => 'lista-presenca',
            'logo' => $logo,
            'titulo' => 'Listagem de presença no Rolê Agroecológico',
            'dados_roteiro' => $info,
            'turmas' => $turmas,
            'acompanhantes' => $acompanhantes_geral,
        ]);
        $html_lista = ob_get_clean();

        // Gera os arquivo .pdf de cada lista
        $arquivo_lista_presenca = $this->gerar_pdf_temp($html_lista, "{$base_path}/lista-presenca.pdf");

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="lista-presenca.pdf"');
        readfile($arquivo_lista_presenca);

        // Remove os arquivos temporários
        unlink($arquivo_lista_presenca);
        exit;
    }
}

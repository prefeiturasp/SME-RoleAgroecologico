<?php

if (!defined('ABSPATH')) {
    exit;
}

use PhpOffice\PhpSpreadsheet\IOFactory;

class Sorteios_Importador
{
    /**
     * Processa o arquivo enviado.
     *
     * @param array $arquivo Dados do $_FILES.
     *
     * @return array
     *
     * @throws Exception
     */
    public static function processar(array $arquivo)
    {
        if (
            empty($arquivo['tmp_name']) ||
            !isset($arquivo['error'])
        ) {
            throw new Exception(
                'Nenhum arquivo foi enviado.'
            );
        }

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            throw new Exception(
                self::mensagem_erro_upload(
                    $arquivo['error']
                )
            );
        }

        $extensao = strtolower(
            pathinfo(
                $arquivo['name'],
                PATHINFO_EXTENSION
            )
        );

        $extensoes_permitidas = [
            'xlsx',
            'xls',
        ];

        if (
            !in_array(
                $extensao,
                $extensoes_permitidas,
                true
            )
        ) {
            throw new Exception(
                'Formato de arquivo inválido. Envie um arquivo Excel (.xlsx ou .xls).'
            );
        }

        if (!class_exists(IOFactory::class)) {
            throw new Exception(
                'A biblioteca PhpSpreadsheet não está disponível no plugin.'
            );
        }

        /*
        * Diretório de uploads do WordPress.
        */
        $upload_dir = wp_upload_dir();

        if (!empty($upload_dir['error'])) {
            throw new Exception(
                'Não foi possível acessar o diretório de uploads.'
            );
        }

        /*
        * Cria um identificador único para o arquivo temporário.
        *
        * Não utilizamos apenas o nome original para evitar
        * conflito entre uploads simultâneos.
        */
        $identificador = wp_generate_uuid4();

        /*
        * Diretório temporário:
        *
        * uploads/sorteios/temp/{uuid}/
        */
        $diretorio_temporario = trailingslashit(
            $upload_dir['basedir']
        ) .
            'sorteios/temp/' .
            $identificador;

        if (
            !wp_mkdir_p(
                $diretorio_temporario
            )
        ) {
            throw new Exception(
                'Não foi possível criar o diretório temporário do sorteio.'
            );
        }

        /*
        * Mantém o nome original sanitizado.
        */
        $arquivo_nome = sanitize_file_name(
            $arquivo['name']
        );

        $arquivo_temporario = trailingslashit(
            $diretorio_temporario
        ) . $arquivo_nome;

        /*
        * Move o arquivo enviado pelo PHP para um local
        * que continuará existindo nas próximas etapas.
        */
        if (
            !move_uploaded_file(
                $arquivo['tmp_name'],
                $arquivo_temporario
            )
        ) {
            @rmdir(
                $diretorio_temporario
            );

            throw new Exception(
                'Não foi possível preservar temporariamente o arquivo enviado.'
            );
        }

        /*
        * Agora o PhpSpreadsheet lê nossa cópia persistente,
        * e não mais o arquivo temporário do PHP.
        */
        try {

            $spreadsheet = IOFactory::load(
                $arquivo_temporario
            );

        } catch (Throwable $e) {

            wp_delete_file(
                $arquivo_temporario
            );

            @rmdir(
                $diretorio_temporario
            );

            throw new Exception(
                'Não foi possível ler o arquivo Excel. Verifique se o arquivo está íntegro.'
            );
        }

        $worksheet = $spreadsheet->getActiveSheet();

        /*
        * Processa normalmente a planilha.
        */
        $resultado = self::processar_planilha(
            $worksheet,
            $arquivo_nome
        );

        /*
        * Guarda o caminho físico do arquivo.
        *
        * Esse valor será armazenado no transient junto
        * com os demais dados da importação.
        */
        $resultado['arquivo_caminho'] =
            $arquivo_temporario;

        /*
        * Libera a planilha da memória.
        */
        $spreadsheet->disconnectWorksheets();

        unset($spreadsheet);

        return $resultado;
    }

    /**
     * Processa a planilha.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet
     * @param string $arquivo_nome
     *
     * @return array
     */
    private static function processar_planilha($worksheet, $arquivo_nome)
    {
        $highest_row = $worksheet->getHighestDataRow();

        $resultado = [
            'arquivo_nome'      => sanitize_file_name($arquivo_nome),
            'total_linhas'      => 0,
            'linhas_vazias'     => 0,
            'duplicidades'      => 0,
            'unidades_validas'  => 0,
            'erros'             => 0,
            'dres'              => [],
            'dados'             => [],
            'erros_detalhados'  => [],
        ];

        $cies = [];

        /*
         * Consideramos a primeira linha como cabeçalho.
         */
        for ($linha = 2; $linha <= $highest_row; $linha++) {
            $resultado['total_linhas']++;

            $dre = self::normalizar_dre(
                $worksheet->getCell('A' . $linha)->getValue()
            );

            $unidade_bruta = self::normalizar_texto(
                $worksheet->getCell('B' . $linha)->getValue()
            );

            $horta_bruta = self::normalizar_texto(
                $worksheet->getCell('C' . $linha)->getValue()
            );

            /*
             * Linha totalmente vazia.
             */
            if (
                $dre === '' &&
                $unidade_bruta === '' &&
                $horta_bruta === ''
            ) {
                $resultado['linhas_vazias']++;

                $resultado['erros_detalhados'][] = [
                    'linha'   => $linha,
                    'tipo'    => 'linha_vazia',
                    'detalhe' => 'Linha completamente vazia.',
                ];

                continue;
            }

            /*
             * DRE preenchida, mas unidade vazia.
             */
            if (
                $dre !== '' &&
                $unidade_bruta === ''
            ) {
                self::adicionar_erro(
                    $resultado,
                    $linha,
                    'dados_incompletos',
                    'A DRE foi informada, mas a unidade está vazia.'
                );

                continue;
            }

            /*
             * Unidade preenchida, mas DRE vazia.
             */
            if (
                $dre === '' &&
                $unidade_bruta !== ''
            ) {
                self::adicionar_erro(
                    $resultado,
                    $linha,
                    'dados_incompletos',
                    'A unidade foi informada, mas a DRE está vazia.'
                );

                continue;
            }

            /*
             * DRE e unidade vazias já foram tratadas acima.
             * Portanto, a partir daqui ambas precisam existir.
             */
            if (
                $dre === '' ||
                $unidade_bruta === ''
            ) {
                self::adicionar_erro(
                    $resultado,
                    $linha,
                    'dados_incompletos',
                    'DRE e unidade são obrigatórias.'
                );

                continue;
            }

            /*
             * Extrai CIE e nome da unidade.
             */
            $unidade = self::extrair_unidade($unidade_bruta);

            if ($unidade === false) {
                self::adicionar_erro(
                    $resultado,
                    $linha,
                    'formato_unidade',
                    'O campo de unidade deve seguir o formato "CIE - Nome da unidade".'
                );

                continue;
            }

            $cie = $unidade['cie'];
            $nome_unidade = $unidade['nome_unidade'];

            /*
             * Validação básica do CIE.
             *
             * Mantemos como string para não perder zeros à esquerda.
             */
            if (!preg_match('/^\d+$/', $cie)) {
                self::adicionar_erro(
                    $resultado,
                    $linha,
                    'cie_invalido',
                    sprintf(
                        'CIE "%s" possui formato inválido.',
                        $cie
                    )
                );

                continue;
            }

            /*
             * Normalização da horta.
             */
            $horta = self::normalizar_horta($horta_bruta);

            /*
             * Verifica CIE duplicado.
             */
            if (isset($cies[$cie])) {
                $registro_anterior = $cies[$cie];

                /*
                 * Se todos os dados forem compatíveis, podemos
                 * simplesmente ignorar a duplicidade.
                 */
                if (
                    self::registros_compativeis(
                        $registro_anterior,
                        [
                            'dre'           => $dre,
                            'cie'           => $cie,
                            'nome_unidade'  => $nome_unidade,
                            'horta'         => $horta,
                        ]
                    )
                ) {
                    $resultado['duplicidades']++;

                    $resultado['erros_detalhados'][] = [
                        'linha'   => $linha,
                        'tipo'    => 'cie_duplicado',
                        'detalhe' => sprintf(
                            'CIE %s já foi informado anteriormente na linha %d. A linha duplicada foi ignorada.',
                            $cie,
                            $registro_anterior['_linha']
                        ),
                    ];

                    continue;
                }

                /*
                 * Mesmo CIE com informações conflitantes.
                 */
                self::adicionar_erro(
                    $resultado,
                    $linha,
                    'conflito_cie',
                    sprintf(
                        'O CIE %s aparece novamente com dados diferentes da linha %d.',
                        $cie,
                        $registro_anterior['_linha']
                    )
                );

                continue;
            }

            /*
             * Monta o registro normalizado.
             */
            $registro = [
                'dre'           => $dre,
                'cie'           => $cie,
                'nome_unidade'  => $nome_unidade,
                'horta'         => $horta,
                '_linha'        => $linha,
            ];

            $cies[$cie] = $registro;

            /*
             * Adiciona DRE à lista de DREs encontradas.
             */
            if (!in_array($dre, $resultado['dres'], true)) {
                $resultado['dres'][] = $dre;
            }

            $resultado['dados'][] = $registro;
            $resultado['unidades_validas']++;
        }

        /*
         * Ordena as DREs para facilitar a próxima etapa.
         */
        natcasesort($resultado['dres']);
        $resultado['dres'] = array_values($resultado['dres']);

        /*
         * Remove a informação interna de linha dos dados
         * que serão utilizados pelas próximas etapas.
         */
        foreach ($resultado['dados'] as &$registro) {
            unset($registro['_linha']);
        }

        unset($registro);

        /*
         * O contador "erros" representa erros que não são
         * simplesmente duplicidades ou linhas vazias.
         */
        $resultado['erros'] = count(
            array_filter(
                $resultado['erros_detalhados'],
                function ($erro) {
                    return !in_array(
                        $erro['tipo'],
                        [
                            'linha_vazia',
                            'cie_duplicado',
                        ],
                        true
                    );
                }
            )
        );

        return $resultado;
    }

    /**
     * Extrai CIE e nome da unidade.
     *
     * Exemplo:
     * 93688 - EMEF PEDRO ALEIXO, DR.
     *
     * @param string $valor
     *
     * @return array|false
     */
    private static function extrair_unidade($valor)
    {
        $partes = preg_split(
            '/\s*-\s*/u',
            $valor,
            2
        );

        if (count($partes) !== 2) {
            return false;
        }

        $cie = trim($partes[0]);
        $nome = trim($partes[1]);

        if ($cie === '' || $nome === '') {
            return false;
        }

        return [
            'cie'           => $cie,
            'nome_unidade'  => $nome,
        ];
    }

    /**
     * Normaliza a DRE/texto.
     *
     * @param mixed $valor
     *
     * @return string
     */
    private static function normalizar_texto($valor)
    {
        if ($valor === null) {
            return '';
        }

        $valor = (string) $valor;

        $valor = preg_replace(
            '/\s+/u',
            ' ',
            $valor
        );

        return trim($valor);
    }

    /**
     * Normaliza o nome da DRE.
     *
     * @param string $dre
     *
     * @return string
     */
    private static function normalizar_dre($dre)
    {
        $dre = self::normalizar_texto($dre);

        if ($dre === '') {
            return '';
        }

        return mb_strtoupper($dre, 'UTF-8');
    }

    /**
     * Normaliza o campo de horta.
     *
     * @param string $valor
     *
     * @return string
     */
    private static function normalizar_horta($valor)
    {
        $valor = self::normalizar_texto($valor);

        if ($valor === '') {
            return 'Não informado';
        }

        $valor_normalizado = mb_strtolower(
            $valor,
            'UTF-8'
        );

        switch ($valor_normalizado) {
            case 'sim, está ativa no momento':
            case 'sim, em fase de implementação':
                return 'Sim';

            case 'não':
            case 'nao':
            case 'já teve, mas está desativada':
            case 'já teve mas está desativada':
            case 'ja teve, mas esta desativada':
            case 'ja teve mas esta desativada':
                return 'Não';

            default:
                return 'Não informado';
        }
    }

    /**
     * Verifica se dois registros do mesmo CIE são compatíveis.
     *
     * @param array $anterior
     * @param array $novo
     *
     * @return bool
     */
    private static function registros_compativeis(
        array $anterior,
        array $novo
    ) {
        return (
            $anterior['dre'] === $novo['dre'] &&
            $anterior['nome_unidade'] === $novo['nome_unidade'] &&
            $anterior['horta'] === $novo['horta']
        );
    }

    /**
     * Adiciona um erro detalhado.
     *
     * @param array  $resultado
     * @param int    $linha
     * @param string $tipo
     * @param string $detalhe
     *
     * @return void
     */
    private static function adicionar_erro(
        array &$resultado,
        $linha,
        $tipo,
        $detalhe
    ) {
        $resultado['erros_detalhados'][] = [
            'linha'   => $linha,
            'tipo'    => $tipo,
            'detalhe' => $detalhe,
        ];
    }

    /**
     * Traduz erros de upload.
     *
     * @param int $codigo
     *
     * @return string
     */
    private static function mensagem_erro_upload($codigo)
    {
        switch ($codigo) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'O arquivo enviado é muito grande.';

            case UPLOAD_ERR_PARTIAL:
                return 'O upload do arquivo foi interrompido.';

            case UPLOAD_ERR_NO_FILE:
                return 'Nenhum arquivo foi enviado.';

            default:
                return 'Ocorreu um erro durante o upload do arquivo.';
        }
    }
}
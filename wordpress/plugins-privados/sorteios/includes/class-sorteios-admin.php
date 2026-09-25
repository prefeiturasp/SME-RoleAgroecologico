<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sorteios_Admin
{
    private const TRANSIENT_PREFIX = 'sorteios_importacao_';

    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_enqueue_scripts']);

        add_action(
            'admin_post_sorteios_importar_planilha',
            [__CLASS__, 'importar_planilha']
        );

        add_action(
            'admin_post_sorteios_salvar_dres',
            [__CLASS__, 'salvar_dres']
        );

        add_action(
            'admin_post_sorteios_salvar_tipo_horta',
            [__CLASS__, 'salvar_tipo_horta']
        );

        add_action(
            'admin_post_sorteios_realizar',
            [__CLASS__, 'realizar_sorteio']
        );

        add_action(
            'wp_ajax_sorteios_alterar_status',
            [__CLASS__, 'alterar_status']
        );

        add_action(
            'admin_post_sorteios_exportar_resultados',
            [__CLASS__, 'exportar_resultados']
        );

        add_action(
            'admin_post_sorteios_baixar_arquivo',
            [__CLASS__, 'baixar_arquivo_sorteio']
        );

        add_action(
            'admin_post_sorteios_excluir',
            [__CLASS__, 'excluir_sorteio']
        );
    }

    public static function admin_menu()
    {
        add_menu_page(
            'Sorteios',
            'Sorteios',
            'manage_options',
            'sorteios',
            [__CLASS__, 'pagina_sorteios'],
            'dashicons-randomize',
            30
        );

        add_submenu_page(
            'sorteios',
            'Novo Sorteio',
            'Novo Sorteio',
            'manage_options',
            'novo-sorteio',
            [__CLASS__, 'pagina_novo_sorteio']
        );
    }

    public static function admin_enqueue_scripts($hook)
    {
        if (
            $hook !== 'sorteios_page_novo-sorteio' &&
            $hook !== 'toplevel_page_sorteios'
        ) {
            return;
        }

        wp_enqueue_style(
            'sorteios-datatables',
            SORTEIOS_PLUGIN_URL . 'assets/css/dataTables.dataTables.min.css',
            [],
            SORTEIOS_VERSION
        );

        wp_enqueue_style(
            'sorteios-admin',
            SORTEIOS_PLUGIN_URL . 'assets/css/admin.css',
            ['sorteios-datatables'],
            SORTEIOS_VERSION
        );

        wp_enqueue_script(
            'sorteios-datatables',
            SORTEIOS_PLUGIN_URL . 'assets/js/dataTables.min.js',
            ['jquery'],
            SORTEIOS_VERSION,
            true
        );

        wp_enqueue_script(
            'sorteios-admin',
            SORTEIOS_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'sorteios-datatables'],
            SORTEIOS_VERSION,
            true
        );

        wp_localize_script(
            'sorteios-admin',
            'SorteiosAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),

                'nonceAlterarStatus' => wp_create_nonce(
                    'sorteios_alterar_status'
                ),

                'exportUrl' => admin_url(
                    'admin-post.php'
                ),

                'nonceExportar' => wp_create_nonce(
                    'sorteios_exportar_resultados'
                ),
            ]
        );

    }

    public static function exportar_resultados()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'Você não tem permissão para exportar os resultados.'
            );
        }

        check_admin_referer(
            'sorteios_exportar_resultados',
            'nonce'
        );

        $sorteio_id = isset($_POST['sorteio_id'])
            ? absint($_POST['sorteio_id'])
            : 0;

        $unidades_ids = isset($_POST['unidades_ids'])
            && is_array($_POST['unidades_ids'])
            ? array_map(
                'absint',
                $_POST['unidades_ids']
            )
            : [];

        $unidades_ids = array_values(
            array_filter(
                array_unique($unidades_ids)
            )
        );

        if ($sorteio_id <= 0) {
            wp_die(
                'Sorteio inválido.'
            );
        }

        if (empty($unidades_ids)) {
            wp_die(
                'Nenhuma unidade foi selecionada para exportação.'
            );
        }

        $sorteio = self::obter_sorteio(
            $sorteio_id
        );

        if (!$sorteio) {
            wp_die(
                'Sorteio não encontrado.'
            );
        }

        global $wpdb;

        $table_unidades =
            $wpdb->prefix . 'sorteio_unidades';

        /*
        * Cria os placeholders para o IN().
        */
        $placeholders = implode(
            ', ',
            array_fill(
                0,
                count($unidades_ids),
                '%d'
            )
        );

        /*
        * O sorteio_id também é validado.
        *
        * Dessa forma não basta enviar IDs arbitrários:
        * as unidades precisam pertencer ao sorteio.
        */
        $query = $wpdb->prepare(
            "SELECT
                id,
                ordem_sorteio,
                dre,
                cie,
                nome_unidade,
                status
            FROM {$table_unidades}
            WHERE sorteio_id = %d
            AND id IN ({$placeholders})
            ORDER BY dre ASC, ordem_sorteio ASC",
            array_merge(
                [$sorteio_id],
                $unidades_ids
            )
        );

        $unidades = $wpdb->get_results(
            $query
        );

        if (empty($unidades)) {
            wp_die(
                'Nenhum resultado encontrado para exportação.'
            );
        }

        /*
        * Cria a planilha.
        */
        $spreadsheet =
            new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle(
            'Resultado'
        );

        /*
        * Título do sorteio.
        *
        * Ocupa toda a largura da tabela
        * nas duas primeiras linhas.
        */
        $sheet->mergeCells(
            'A1:E2'
        );

        $sheet->setCellValue(
            'A1',
            $sorteio->nome
        );

        /*
        * Estilo do título.
        */
        $sheet
            ->getStyle('A1:E2')
            ->getFont()
            ->setBold(true)
            ->setSize(18);

        $sheet
            ->getStyle('A1:E2')
            ->getFont()
            ->getColor()
            ->setARGB(
                'FFFFFFFF'
            );

        $sheet
            ->getStyle('A1:E2')
            ->getFill()
            ->setFillType(
                \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB(
                'FF1D2327'
            );

        $sheet
            ->getStyle('A1:E2')
            ->getAlignment()
            ->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            );

        /*
        * Altura das linhas do título.
        */
        $sheet
            ->getRowDimension(1)
            ->setRowHeight(22);

        $sheet
            ->getRowDimension(2)
            ->setRowHeight(22);

        /*
        * Cabeçalhos da tabela.
        *
        * A linha 3 fica vazia para separar
        * visualmente o título da tabela.
        */
        $sheet->fromArray(
            [
                [
                    'Ordem',
                    'DRE',
                    'CIE',
                    'Unidade',
                    'Status',
                ]
            ],
            null,
            'A4'
        );

        /*
        * Labels dos status.
        */
        $status_labels = [
            'aguardando_confirmacao' =>
                'Sorteado – Aguardando confirmação',

            'confirmado' =>
                'Sorteado – Confirmado',

            'lista_espera' =>
                'Lista de espera',

            'desistencia' =>
                'Desistência',
        ];

        /*
        * Os dados começam na linha 5.
        */
        $linha = 5;

        foreach ($unidades as $unidade) {

            $status_label =
                $status_labels[$unidade->status]
                ?? $unidade->status;

            /*
            * Ordem.
            */
            $sheet->setCellValue(
                'A' . $linha,
                (int) $unidade->ordem_sorteio
            );

            /*
            * DRE.
            */
            $sheet->setCellValue(
                'B' . $linha,
                $unidade->dre
            );

            /*
            * CIE como texto.
            *
            * Isso evita que o Excel altere códigos
            * ou remova zeros à esquerda.
            */
            $sheet->setCellValueExplicit(
                'C' . $linha,
                (string) $unidade->cie,
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );

            /*
            * Unidade.
            */
            $sheet->setCellValue(
                'D' . $linha,
                $unidade->nome_unidade
            );

            /*
            * Status.
            */
            $sheet->setCellValue(
                'E' . $linha,
                $status_label
            );

            /*
            * Cor da célula de status.
            */
            switch ($unidade->status) {

                case 'aguardando_confirmacao':

                    $cor_fundo = 'FFF8E5';
                    $cor_texto = '996800';

                    break;

                case 'confirmado':

                    $cor_fundo = 'EDFAEF';
                    $cor_texto = '18733C';

                    break;

                case 'lista_espera':

                    $cor_fundo = 'EEF4FF';
                    $cor_texto = '315F9E';

                    break;

                case 'desistencia':

                    $cor_fundo = 'FBEAEA';
                    $cor_texto = 'B32D2E';

                    break;

                default:

                    $cor_fundo = null;
                    $cor_texto = null;

                    break;
            }

            if ($cor_fundo) {
                $sheet
                    ->getStyle('E' . $linha)
                    ->getFill()
                    ->setFillType(
                        \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID
                    )
                    ->getStartColor()
                    ->setARGB(
                        'FF' . $cor_fundo
                    );
            }

            if ($cor_texto) {
                $sheet
                    ->getStyle('E' . $linha)
                    ->getFont()
                    ->getColor()
                    ->setARGB(
                        'FF' . $cor_texto
                    );
            }

            $linha++;
        }

        $ultima_linha = $linha - 1;

        /*
        * Estilo do cabeçalho da tabela.
        */
        $sheet
            ->getStyle('A4:E4')
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle('A4:E4')
            ->getFill()
            ->setFillType(
                \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB(
                'FFDCDCDE'
            );

        /*
        * Altura do cabeçalho.
        */
        $sheet
            ->getRowDimension(4)
            ->setRowHeight(22);

        /*
        * Filtros nativos do Excel.
        */
        $sheet->setAutoFilter(
            'A4:E' . $ultima_linha
        );

        /*
        * Congela o título e o cabeçalho.
        *
        * Ao rolar a planilha, as linhas 1 a 4
        * permanecem visíveis.
        */
        $sheet->freezePane(
            'A5'
        );

        /*
        * Largura das colunas.
        */
        $sheet
            ->getColumnDimension('A')
            ->setWidth(10);

        $sheet
            ->getColumnDimension('B')
            ->setWidth(25);

        $sheet
            ->getColumnDimension('C')
            ->setWidth(15);

        $sheet
            ->getColumnDimension('D')
            ->setWidth(55);

        $sheet
            ->getColumnDimension('E')
            ->setWidth(35);

        /*
        * Alinhamento vertical.
        */
        $sheet
            ->getStyle(
                'A1:E' . $ultima_linha
            )
            ->getAlignment()
            ->setVertical(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            );

        /*
        * Nome do arquivo.
        */
        $nome_arquivo = sprintf(
            'resultado-sorteio-%d-%s.xlsx',
            $sorteio_id,
            wp_date('Y-m-d')
        );

        /*
        * Remove qualquer buffer anterior.
        */
        while (ob_get_level()) {
            ob_end_clean();
        }

        nocache_headers();

        header(
            'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        header(
            'Content-Disposition: attachment; filename="' .
            $nome_arquivo .
            '"'
        );

        header(
            'Cache-Control: max-age=0'
        );

        $writer =
            new \PhpOffice\PhpSpreadsheet\Writer\Xlsx(
                $spreadsheet
            );

        $writer->save(
            'php://output'
        );

        $spreadsheet->disconnectWorksheets();

        unset($spreadsheet);

        exit;
    }

    public static function pagina_sorteios()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'Você não tem permissão para acessar esta página.'
            );
        }

        $sorteio_id = isset($_GET['sorteio'])
            ? absint($_GET['sorteio'])
            : 0;

        if ($sorteio_id > 0) {

            self::renderizar_resultado_sorteio(
                $sorteio_id
            );

            return;
        }

        self::renderizar_lista_sorteios();
    }

    private static function obter_sorteio($sorteio_id)
    {
        global $wpdb;

        $table_sorteios = $wpdb->prefix . 'sorteios';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$table_sorteios}
                WHERE id = %d",
                $sorteio_id
            )
        );
    }

    private static function obter_dres_sorteio($sorteio_id)
    {
        global $wpdb;

        $table_dres = $wpdb->prefix . 'sorteio_dres';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$table_dres}
                WHERE sorteio_id = %d
                ORDER BY dre ASC",
                $sorteio_id
            )
        );
    }

    private static function obter_unidades_sorteio($sorteio_id)
    {
        global $wpdb;

        $table_unidades = $wpdb->prefix . 'sorteio_unidades';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$table_unidades}
                WHERE sorteio_id = %d
                ORDER BY dre ASC, ordem_sorteio ASC",
                $sorteio_id
            )
        );
    }

    private static function obter_historico_sorteio(
        $sorteio_id
    ) {
        global $wpdb;

        $table_historico =
            $wpdb->prefix . 'sorteio_historico';

        $table_unidades =
            $wpdb->prefix . 'sorteio_unidades';

        $table_sorteios =
            $wpdb->prefix . 'sorteios';

        $table_users =
            $wpdb->users;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    h.*,
                    u.nome_unidade,
                    u.cie,
                    wp.display_name AS usuario_nome,
                    s.arquivo_nome,
                    s.arquivo_caminho
                FROM {$table_historico} h

                LEFT JOIN {$table_unidades} u
                    ON u.id = h.unidade_id

                LEFT JOIN {$table_users} wp
                    ON wp.ID = h.usuario_id

                LEFT JOIN {$table_sorteios} s
                    ON s.id = h.sorteio_id

                WHERE h.sorteio_id = %d

                ORDER BY
                    h.created_at DESC,
                    h.id DESC",
                $sorteio_id
            )
        );
    }

    private static function obter_sorteios()
    {
        global $wpdb;

        $table_sorteios = $wpdb->prefix . 'sorteios';
        $table_dres = $wpdb->prefix . 'sorteio_dres';

        return $wpdb->get_results(
            "
            SELECT
                s.id,
                s.nome,
                s.data_sorteio,
                s.usuario_sorteio,
                COUNT(DISTINCT d.dre) AS total_dres,
                SUM(d.total_unidades) AS total_unidades
            FROM {$table_sorteios} s
            LEFT JOIN {$table_dres} d
                ON d.sorteio_id = s.id
            WHERE s.data_sorteio IS NOT NULL
            GROUP BY s.id
            ORDER BY s.data_sorteio DESC
            "
        );
    }

    private static function agrupar_unidades_por_dre($unidades)
    {
        $resultado = [];

        foreach ($unidades as $unidade) {
            if (!isset($resultado[$unidade->dre])) {
                $resultado[$unidade->dre] = [];
            }

            $resultado[$unidade->dre][] = $unidade;
        }

        return $resultado;
    }


    private static function renderizar_tabela_resultado(
    array $unidades,
    int $sorteio_id
    ) {
        $dres = [];

        foreach ($unidades as $unidade) {
            if (!empty($unidade->dre)) {
                $dres[] = $unidade->dre;
            }
        }

        $dres = array_values(
            array_unique($dres)
        );

        sort($dres);
        ?>

        <div class="sorteios-resumo-status">

            <div class="sorteios-resumo-status-item">
                <span class="sorteios-resumo-status-numero numero-aguardando" data-resumo-status="aguardando_confirmacao">
                    0
                </span>

                <span class="sorteios-resumo-status-label">
                    Aguardando confirmação
                </span>
            </div>

            <div class="sorteios-resumo-status-item">
                <span class="sorteios-resumo-status-numero numero-confirm" data-resumo-status="confirmado">
                    0
                </span>

                <span class="sorteios-resumo-status-label">
                    Confirmados
                </span>
            </div>

            <div class="sorteios-resumo-status-item">
                <span class="sorteios-resumo-status-numero numero-espera" data-resumo-status="lista_espera">
                    0
                </span>

                <span class="sorteios-resumo-status-label">
                    Lista de espera
                </span>
            </div>

            <div class="sorteios-resumo-status-item">
                <span class="sorteios-resumo-status-numero numero-desistencia" data-resumo-status="desistencia">
                    0
                </span>

                <span class="sorteios-resumo-status-label">
                    Desistência
                </span>
            </div>

        </div>

        <div class="sorteios-filtros">

            <div class="sorteios-filtro sorteios-filtro-multiselect">

                <label>
                    DRE
                </label>

                <button
                    type="button"
                    class="button sorteios-filtro-toggle"
                    data-filtro="dre"
                >
                    <span class="sorteios-filtro-label">
                        Todas
                    </span>

                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </button>

                <div
                    class="sorteios-filtro-dropdown"
                    data-filtro-dropdown="dre"
                >

                    <div class="sorteios-filtro-acoes">

                        <button
                            type="button"
                            class="button-link sorteios-filtro-todos"
                        >
                            Todos
                        </button>

                        <button
                            type="button"
                            class="button-link sorteios-filtro-nenhum"
                        >
                            Nenhum
                        </button>

                    </div>

                    <div class="sorteios-filtro-opcoes">

                        <?php foreach ($dres as $dre) : ?>

                            <label class="sorteios-filtro-opcao">

                                <input
                                    type="checkbox"
                                    value="<?php echo esc_attr($dre); ?>"
                                    data-filtro-checkbox="dre"
                                    checked
                                >

                                <span>
                                    <?php echo esc_html($dre); ?>
                                </span>

                            </label>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>


            <div class="sorteios-filtro">

                <label for="filtro-nome-unidade">
                    Nome da Unidade
                </label>

                <input
                    type="text"
                    id="filtro-nome-unidade"
                    class="sorteios-filtro-texto"
                    placeholder="Digite o nome da unidade..."
                >

            </div>


            <div class="sorteios-filtro sorteios-filtro-multiselect">

                <label>
                    Status
                </label>

                <button
                    type="button"
                    class="button sorteios-filtro-toggle"
                    data-filtro="status"
                >
                    <span class="sorteios-filtro-label">
                        Todos
                    </span>

                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </button>

                <div
                    class="sorteios-filtro-dropdown"
                    data-filtro-dropdown="status"
                >

                    <div class="sorteios-filtro-acoes">

                        <button
                            type="button"
                            class="button-link sorteios-filtro-todos"
                        >
                            Todos
                        </button>

                        <button
                            type="button"
                            class="button-link sorteios-filtro-nenhum"
                        >
                            Nenhum
                        </button>

                    </div>

                    <div class="sorteios-filtro-opcoes">

                        <label class="sorteios-filtro-opcao">

                            <input
                                type="checkbox"
                                value="aguardando_confirmacao"
                                data-filtro-checkbox="status"
                                checked
                            >

                            <span>
                                Sorteado – Aguardando confirmação
                            </span>

                        </label>

                        <label class="sorteios-filtro-opcao">

                            <input
                                type="checkbox"
                                value="confirmado"
                                data-filtro-checkbox="status"
                                checked
                            >

                            <span>
                                Sorteado – Confirmado
                            </span>

                        </label>

                        <label class="sorteios-filtro-opcao">

                            <input
                                type="checkbox"
                                value="lista_espera"
                                data-filtro-checkbox="status"
                                checked
                            >

                            <span>
                                Lista de espera
                            </span>

                        </label>

                        <label class="sorteios-filtro-opcao">

                            <input
                                type="checkbox"
                                value="desistencia"
                                data-filtro-checkbox="status"
                                checked
                            >

                            <span>
                                Desistência
                            </span>

                        </label>

                    </div>

                </div>

            </div>

            <button
                type="button"
                class="button sorteios-limpar-filtros"
                id="sorteios-limpar-filtros"
            >
                Limpar filtros
            </button>

            <button
                type="button"
                class="button button-primary"
                id="sorteios-exportar-resultados"
                data-sorteio-id="<?php echo esc_attr(
                    $sorteio_id
                ); ?>"
            >
                Exportar resultados
            </button>

        </div>


        <table
            id="tabela-resultado-sorteio"
            class="widefat striped"
        >

            <thead>
                <tr>
                    <th>Ordem</th>
                    <th>DRE</th>
                    <th>Nome da Unidade</th>
                    <th>Status</th>
                    <th>Ação</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($unidades as $unidade) : ?>

                    <tr
                        data-unidade-id="<?php echo esc_attr(
                            $unidade->id
                        ); ?>"
                    >

                        <td>
                            <?php echo esc_html(
                                $unidade->ordem_sorteio
                            ); ?>
                        </td>

                        <td data-coluna="dre">
                            <?php echo esc_html(
                                $unidade->dre
                            ); ?>
                        </td>

                        <td
                            data-coluna="nome"
                            data-cie="<?php echo esc_attr(
                                $unidade->cie
                            ); ?>"
                        >

                            <strong>
                                <?php echo esc_html(
                                    $unidade->nome_unidade
                                ); ?>
                            </strong>

                            <br>

                            <small>
                                CIE:
                                <?php echo esc_html(
                                    $unidade->cie
                                ); ?>
                            </small>

                        </td>

                        <td
                            data-coluna="status"
                            data-search="<?php echo esc_attr(
                                $unidade->status
                            ); ?>"
                            class="sorteios-coluna-status"
                        >
                            <?php
                            $status_labels = [
                                'aguardando_confirmacao' => 'Sorteado – Aguardando confirmação',
                                'confirmado' => 'Sorteado – Confirmado',
                                'lista_espera' => 'Lista de espera',
                                'desistencia' => 'Desistência',
                            ];

                            $status_classes = [
                                'aguardando_confirmacao' => 'aguardando-confirmacao',
                                'confirmado' => 'confirmado',
                                'lista_espera' => 'lista-espera',
                                'desistencia' => 'desistencia',
                            ];

                            $status_label = $status_labels[$unidade->status]
                                ?? $unidade->status;

                            $status_class = $status_classes[$unidade->status]
                                ?? 'desconhecido';
                            ?>

                            <span
                                class="sorteios-status sorteios-status-<?php echo esc_attr(
                                    $status_class
                                ); ?>"
                            >
                                <?php echo esc_html($status_label); ?>
                            </span>
                        </td>

                        <td>

                            <button
                                type="button"
                                class="button sorteios-alterar-status"
                                data-unidade-id="<?php echo esc_attr(
                                    $unidade->id
                                ); ?>"
                                data-status="<?php echo esc_attr(
                                    $unidade->status
                                ); ?>"
                            >
                                Alterar Status
                            </button>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

        <div
            id="modal-alterar-status"
            class="sorteios-modal"
            style="display: none;"
        >
            <div class="sorteios-modal-overlay"></div>

            <div class="sorteios-modal-conteudo">
                <div class="sorteios-modal-cabecalho">
                    <h2>Alterar Status</h2>

                    <button
                        type="button"
                        class="sorteios-modal-fechar"
                        aria-label="Fechar"
                    >
                        &times;
                    </button>
                </div>

                <div class="sorteios-modal-corpo">

                    <input
                        type="hidden"
                        id="sorteios-unidade-id"
                        value=""
                    >

                    <p>
                        Selecione o novo status da unidade:
                    </p>

                    <div class="sorteios-status-opcoes">

                        <label>
                            <input
                                type="radio"
                                name="sorteios-status"
                                value="aguardando_confirmacao"
                            >

                            Sorteado – Aguardando confirmação
                        </label>

                        <label>
                            <input
                                type="radio"
                                name="sorteios-status"
                                value="confirmado"
                            >

                            Sorteado – Confirmado
                        </label>

                        <label>
                            <input
                                type="radio"
                                name="sorteios-status"
                                value="lista_espera"
                            >

                            Lista de espera
                        </label>

                        <label>
                            <input
                                type="radio"
                                name="sorteios-status"
                                value="desistencia"
                            >

                            Desistência
                        </label>

                    </div>

                </div>

                <div class="sorteios-modal-rodape">

                    <button
                        type="button"
                        class="button sorteios-modal-cancelar"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        class="button button-primary sorteios-modal-salvar"
                    >
                        Salvar
                    </button>

                </div>
            </div>
        </div>

        <?php
    }


    private static function renderizar_resultado_sorteio($sorteio_id)
    {
        $aba_ativa = isset($_GET['aba'])
            ? sanitize_key($_GET['aba'])
            : 'resultado';

        $abas_permitidas = [
            'resultado',
            'historico',
        ];

        if (!in_array($aba_ativa, $abas_permitidas, true)) {
            $aba_ativa = 'resultado';
        }

        $sorteio = self::obter_sorteio(
            $sorteio_id
        );

        if (!$sorteio) {
            ?>
            <div class="wrap">
                <h1>Sorteio</h1>

                <div class="notice notice-error">
                    <p>
                        Sorteio não encontrado.
                    </p>
                </div>
            </div>
            <?php

            return;
        }

        /*
        * Os dados de resultado só precisam ser carregados
        * quando a aba Resultado estiver ativa.
        */
        $dres = [];
        $unidades = [];

        if ($aba_ativa === 'resultado') {
            $dres = self::obter_dres_sorteio(
                $sorteio_id
            );

            $unidades = self::obter_unidades_sorteio(
                $sorteio_id
            );

            $total_unidades_aptas = count($unidades);
            $total_dres = count($dres);

            $quantidade_por_dre = !empty($dres)
                ? (int) $dres[0]->quantidade_vagas
                : 0;

            $usuario_sorteio = get_userdata(
                $sorteio->usuario_sorteio
            );

            $nome_usuario_sorteio = $usuario_sorteio
                ? $usuario_sorteio->display_name
                : 'Usuário não identificado';
        }

        ?>
        <div class="wrap sorteios-admin">

            <div class="sorteios-cabecalho">

                <h1>
                    <?php echo esc_html(
                        $sorteio->nome
                    ); ?>
                </h1>

                <p class="sorteios-cabecalho-descricao">
                    Sorteio das unidades educacionais aptas
                    a participar do Rolê Agroecológico,
                    a partir da planilha de hortas enviada
                    pela CODAE.
                </p>

            </div>

            <?php
            self::renderizar_abas_sorteio(
                $sorteio_id,
                $aba_ativa
            );
            ?>            

            <?php if ($aba_ativa === 'resultado') : ?>

                <?php if (isset($_GET['sucesso'])) : ?>

                    <div class="sorteios-mensagem-sucesso">
                        <strong>
                            Sorteio realizado com sucesso.
                        </strong>

                        <?php
                        echo esc_html(
                            sprintf(
                                '%d unidades aptas em %d DREs · %d por DRE · executado em %s às %s por %s.',
                                $total_unidades_aptas,
                                $total_dres,
                                $quantidade_por_dre,
                                mysql2date(
                                    'd/m/Y',
                                    $sorteio->data_sorteio
                                ),
                                mysql2date(
                                    'H:i',
                                    $sorteio->data_sorteio
                                ),
                                $nome_usuario_sorteio
                            )
                        );
                        ?>
                    </div>

                <?php endif; ?>                

                <?php
                self::renderizar_tabela_resultado(
                    $unidades,
                    $sorteio_id
                );
                ?>

                <div class="sorteios-dres-detalhes">

                    <details class="sorteios-colapsavel">

                        <summary class="sorteios-colapsavel-titulo">
                            <span>
                                Resumo por DRE
                            </span>

                            <span class="sorteios-colapsavel-icone"></span>
                        </summary>

                        <div class="sorteios-colapsavel-conteudo">

                            <?php if (empty($dres)) : ?>

                                <p>
                                    Nenhuma DRE encontrada.
                                </p>

                            <?php else : ?>

                                <table class="widefat striped">

                                    <thead>
                                        <tr>
                                            <th>DRE</th>
                                            <th>Unidades aptas</th>
                                            <th>Vagas</th>
                                            <th>Selecionadas</th>
                                            <th>Diferença</th>
                                            <th>Lista de espera</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php foreach ($dres as $dre) : ?>

                                            <?php
                                            $selecionadas = 0;
                                            $espera = 0;

                                            foreach ($unidades as $unidade) {

                                                if (
                                                    $unidade->dre !==
                                                    $dre->dre
                                                ) {
                                                    continue;
                                                }

                                                if (
                                                    $unidade->tipo_resultado ===
                                                    'selecionada'
                                                ) {
                                                    $selecionadas++;
                                                }

                                                if (
                                                    $unidade->tipo_resultado ===
                                                    'reserva'
                                                ) {
                                                    $espera++;
                                                }
                                            }
                                            ?>

                                            <tr>

                                                <td>
                                                    <?php echo esc_html(
                                                        $dre->dre
                                                    ); ?>
                                                </td>

                                                <td>
                                                    <?php echo esc_html(
                                                        $dre->total_unidades
                                                    ); ?>
                                                </td>

                                                <td>
                                                    <?php echo esc_html(
                                                        $dre->quantidade_vagas
                                                    ); ?>
                                                </td>

                                                <td>
                                                    <?php echo esc_html(
                                                        $selecionadas
                                                    ); ?>
                                                </td>                                               

                                                <td>
                                                    <?php echo esc_html(
                                                        $selecionadas - $dre->quantidade_vagas
                                                    ); ?>
                                                </td>

                                                <td>
                                                    <?php echo esc_html(
                                                        $espera
                                                    ); ?>
                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            <?php endif; ?>

                        </div>

                    </details>

                </div>

            <?php elseif ($aba_ativa === 'historico') : ?>

                <?php
                $historico = self::obter_historico_sorteio(
                    $sorteio_id
                );

                self::renderizar_historico_sorteio(
                    $historico
                );
                ?>

            <?php endif; ?>

        </div>
        <?php
    }  

    public static function pagina_novo_sorteio()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'Você não possui permissão para acessar esta página.'
            );
        }

        $etapa_atual = isset($_GET['etapa'])
            ? absint($_GET['etapa'])
            : 1;

        if ($etapa_atual < 1 || $etapa_atual > 4) {
            $etapa_atual = 1;
        }

        $importacao = self::obter_importacao_temporaria();

        ?>

        <div class="wrap sorteios-wrap">

            <div class="sorteios-cabecalho">
                <h1>Novo sorteio</h1>

                <p class="sorteios-cabecalho-descricao">
                    Sorteio das unidades educacionais aptas a participar do Rolê Agroecológico, a partir da planilha de hortas enviada pela CODAE.
                </p>
            </div>

            <nav
                class="sorteios-abas"
                aria-label="Navegação do sorteio"
            >
                <span
                    class="sorteios-aba sorteios-aba-ativa"
                    aria-current="page"
                >
                    Novo sorteio
                </span>

                <span
                    class="sorteios-aba sorteios-aba-desabilitada"
                    aria-disabled="true"
                >
                    Resultado
                </span>

                <span
                    class="sorteios-aba sorteios-aba-desabilitada"
                    aria-disabled="true"
                >
                    Histórico
                </span>
            </nav>

            <div class="sorteios-wizard">

                <?php
                self::renderizar_etapa(
                    1,
                    'Importação da planilha',
                    $etapa_atual === 1,
                    function () use ($importacao) {
                        self::renderizar_etapa_importacao($importacao);
                    },
                    !empty($importacao)
                        ? sprintf(
                            '%s · %d unidades',
                            $importacao['arquivo_nome'],
                            $importacao['unidades_validas']
                        )
                        : ''
                );
                ?>

                <?php
                self::renderizar_etapa(
                    2,
                    'Seleção das DREs',
                    $etapa_atual === 2,
                    function () use ($importacao) {
                        self::renderizar_etapa_dres($importacao);
                    },
                    self::montar_subtitulo_dres($importacao)
                );
                ?>

                <?php

                $tipo_horta = isset($importacao['configuracao']['tipo_horta'])
                    ? $importacao['configuracao']['tipo_horta']
                    : 'Sim';

                $subtitulo_horta = '';

                if ($tipo_horta === 'Sim') {
                    $subtitulo_horta = 'Sim — apenas com horta ativa';
                } elseif ($tipo_horta === 'Não') {
                    $subtitulo_horta = 'Não — sem horta pedagógica ativa';
                }

                self::renderizar_etapa(
                    3,
                    'Tipo de horta',
                    $etapa_atual === 3,
                    function () use ($importacao) {
                        self::renderizar_etapa_horta($importacao);
                    },
                    $subtitulo_horta,
                    'sorteios-horta-subtitulo'
                );
                ?>

                <?php
                self::renderizar_etapa(
                    4,
                    'Quantidade de unidades e sorteio',
                    $etapa_atual === 4,
                    function () use ($importacao) {
                        self::renderizar_etapa_sorteio($importacao);
                    }
                );
                ?>

            </div>

            <?php if (!empty($importacao['erros_detalhados'])) : ?>

                <?php self::renderizar_modal_log($importacao); ?>

            <?php endif; ?>

        </div>

        <?php
    }

    private static function renderizar_etapa(
        $numero,
        $titulo,
        $ativa,
        callable $conteudo,
        $subtitulo = '',
        $subtitulo_id = ''
    ) {
        $classe = $ativa
            ? 'sorteios-etapa ativa'
            : 'sorteios-etapa';

        ?>

        <section class="<?php echo esc_attr($classe); ?>">

            <div class="sorteios-etapa-cabecalho">

                <span class="sorteios-etapa-numero">
                    <?php echo esc_html($numero); ?>
                </span>

                <div class="sorteios-etapa-cabecalho-info">

                    <h2>
                        <?php echo esc_html($titulo); ?>
                    </h2>

                    <?php if ($subtitulo !== '') : ?>

                        <div
                            class="sorteios-etapa-subtitulo"
                            <?php if ($subtitulo_id !== '') : ?>
                                id="<?php echo esc_attr($subtitulo_id); ?>"
                            <?php endif; ?>
                        >
                            <?php echo esc_html($subtitulo); ?>
                        </div>

                    <?php endif; ?>

                </div>

            </div>

            <?php if ($ativa) : ?>

                <div class="sorteios-etapa-conteudo">

                    <?php $conteudo(); ?>

                </div>

            <?php endif; ?>

        </section>

        <?php
    }

    private static function renderizar_etapa_importacao($importacao)
    {
        ?>

        <p>
            Envie a planilha contendo as unidades que poderão participar do sorteio.
        </p>

        <?php if (!$importacao) : ?>

            <div class="sorteios-upload-box">

                <form
                    method="post"
                    action="<?php echo esc_url(
                        admin_url('admin-post.php')
                    ); ?>"
                    enctype="multipart/form-data"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="sorteios_importar_planilha"
                    >

                    <?php wp_nonce_field(
                        'sorteios_importar_planilha',
                        'sorteios_nonce'
                    ); ?>

                    <div class="sorteios-upload-field">

                        <label for="sorteios-arquivo">
                            Arquivo Excel
                        </label>

                        <input
                            type="file"
                            id="sorteios-arquivo"
                            name="arquivo"
                            accept=".xlsx,.xls"
                            required
                        >

                        <p class="description">
                            Envie um arquivo Excel nos formatos
                            .xlsx ou .xls.
                            <br>
                            <a
                                href="<?php echo esc_url(
                                    SORTEIOS_PLUGIN_URL .
                                    'modelo/modelo-importacao.xlsx'
                                ); ?>"
                                download
                            >
                                <strong>Baixe o modelo de planilha</strong>
                            </a>
                        </p>

                    </div>

                    <p>

                        <button
                            type="submit"
                            class="button button-primary"
                        >
                            Importar planilha
                        </button>

                    </p>

                </form>

            </div>

        <?php else : ?>

            <?php self::renderizar_resumo_importacao($importacao); ?>

        <?php endif; ?>

        <?php
    }

    private static function renderizar_resumo_importacao(
        $importacao
    ) {
        $arquivo_nome = $importacao['arquivo_nome'];

        $hortas_ativas = self::contar_hortas_ativas(
            $importacao['dados']
        );

        ?>

        <div class="sorteios-importacao-resumo">

            <div class="notice notice-success inline">

                <p>

                    <strong>
                        Planilha importada com sucesso.
                    </strong>

                    <br>

                    <div class="d-flex justify-content-between">

                        <span>
                            <?php echo esc_html($arquivo_nome); ?>
                            ·
                            <?php echo esc_html(
                                $importacao['total_linhas']
                            ); ?>
                            linhas
                            ·
                            <?php echo esc_html(
                                $importacao['unidades_validas']
                            ); ?>
                            unidades válidas
                            ·
                            <?php echo esc_html(
                                $hortas_ativas
                            ); ?>
                            com horta ativa
                        </span>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary sorteios-trocar-arquivo"
                        >
                            Trocar arquivo
                        </button>

                    </div>

                </p>

            </div>

            <?php
            $resumo_ignoradas = self::montar_resumo_ignoradas(
                $importacao
            );
            ?>

            <?php if ($resumo_ignoradas) : ?>

                <div class="notice notice-warning inline">

                    <p>

                        <?php echo esc_html(
                            $resumo_ignoradas
                        ); ?>

                        <?php if (
                            !empty(
                                $importacao['erros_detalhados']
                            )
                        ) : ?>

                            <button
                                type="button"
                                class="button-link sorteios-abrir-log"
                            >
                                Ver log de importação
                            </button>

                        <?php endif; ?>

                    </p>

                </div>

            <?php endif; ?>

            <?php if (!empty($importacao['erros'])) : ?>

                <div class="notice notice-warning inline">

                    <p>
                        <strong>
                            A planilha possui linhas que não poderão ser utilizadas.
                        </strong>

                        <br>

                        As linhas com erro serão ignoradas caso você continue.
                        Você também pode enviar uma nova planilha corrigida.
                    </p>

                </div>

            <?php endif; ?>

            <?php if (empty($importacao['unidades_validas'])) : ?>

                <div class="notice notice-error inline">

                    <p>
                        <strong>
                            Não foi encontrada nenhuma unidade válida.
                        </strong>

                        <br>

                        Verifique o log de importação e envie uma nova planilha
                        para continuar.
                    </p>

                </div>

            <?php endif; ?>

            

            <div
                id="sorteios-upload-troca"
                class="sorteios-upload-troca"
                style="display: none;"
            >

                <div class="sorteios-upload-box">

                    <form
                        method="post"
                        action="<?php echo esc_url(
                            admin_url('admin-post.php')
                        ); ?>"
                        enctype="multipart/form-data"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="sorteios_importar_planilha"
                        >

                        <?php wp_nonce_field(
                            'sorteios_importar_planilha',
                            'sorteios_nonce'
                        ); ?>

                        <div class="sorteios-upload-field">

                            <label for="sorteios-arquivo-troca">
                                Novo arquivo Excel
                            </label>

                            <input
                                type="file"
                                id="sorteios-arquivo-troca"
                                name="arquivo"
                                accept=".xlsx,.xls"
                                required
                            >

                        </div>

                        <p>

                            <button
                                type="submit"
                                class="button button-primary"
                            >
                                Importar nova planilha
                            </button>

                        </p>

                    </form>

                </div>

            </div>

            <?php if (!empty($importacao['unidades_validas'])) : ?>

                <div class="sorteios-proxima-etapa d-flex justify-content-end">

                    <a
                        href="<?php echo esc_url(
                            add_query_arg(
                                [
                                    'page' => 'novo-sorteio',
                                    'etapa' => 2,
                                    'importacao' => isset($_GET['importacao'])
                                        ? sanitize_text_field(
                                            wp_unslash($_GET['importacao'])
                                        )
                                        : '',
                                ],
                                admin_url('admin.php')
                            )
                        ); ?>"
                        class="button button-primary"
                    >
                        Continuar
                    </a>

                </div>

            <?php endif; ?>

        </div>

        <?php
        
    }

    private static function renderizar_abas_sorteio(
        $sorteio_id,
        $aba_ativa = 'resultado'
    ) {
        $url_novo = admin_url(
            'admin.php?page=novo-sorteio'
        );

        $url_resultado = add_query_arg(
            [
                'page' => 'sorteios',
                'sorteio' => $sorteio_id,
                'aba' => 'resultado',
            ],
            admin_url('admin.php')
        );

        $url_historico = add_query_arg(
            [
                'page' => 'sorteios',
                'sorteio' => $sorteio_id,
                'aba' => 'historico',
            ],
            admin_url('admin.php')
        );
        ?>

        <nav
            class="sorteios-abas"
            aria-label="Navegação do sorteio"
        >
            <a
                href="<?php echo esc_url($url_novo); ?>"
                class="sorteios-aba"
            >
                Novo sorteio
            </a>

            <a
                href="<?php echo esc_url($url_resultado); ?>"
                class="sorteios-aba <?php echo $aba_ativa === 'resultado'
                    ? 'sorteios-aba-ativa'
                    : ''; ?>"
                <?php if ($aba_ativa === 'resultado') : ?>
                    aria-current="page"
                <?php endif; ?>
            >
                Resultado
            </a>

            <a
                href="<?php echo esc_url($url_historico); ?>"
                class="sorteios-aba <?php echo $aba_ativa === 'historico'
                    ? 'sorteios-aba-ativa'
                    : ''; ?>"
                <?php if ($aba_ativa === 'historico') : ?>
                    aria-current="page"
                <?php endif; ?>
            >
                Histórico
            </a>
        </nav>

        <?php
    }

    private static function montar_subtitulo_dres($importacao)
    {
        if (
            empty($importacao['dres']) ||
            !is_array($importacao['dres'])
        ) {
            return '';
        }

        $total_dres = count($importacao['dres']);

        $configuracao = isset($importacao['configuracao'])
            && is_array($importacao['configuracao'])
            ? $importacao['configuracao']
            : [];

        $dres_selecionadas = isset($configuracao['dres'])
            && is_array($configuracao['dres'])
            ? $configuracao['dres']
            : [];

        $total_selecionadas = count($dres_selecionadas);

        if ($total_selecionadas === 0) {
            return sprintf(
                '0 DREs de %d',
                $total_dres
            );
        }

        $nomes = array_slice(
            $dres_selecionadas,
            0,
            3
        );

        $subtitulo = sprintf(
            '%d DRE%s: %s',
            $total_selecionadas,
            $total_selecionadas === 1 ? '' : 's',
            implode(', ', $nomes)
        );

        $restantes = $total_selecionadas - count($nomes);

        if ($restantes > 0) {
            $subtitulo .= sprintf(
                ' +%d',
                $restantes
            );
        }

        return $subtitulo;
    }

    private static function renderizar_etapa_dres($importacao)
    {
        if (empty($importacao)) {
            echo '<div class="notice notice-warning inline">';
            echo '<p>Faça a importação da planilha antes de selecionar as DREs.</p>';
            echo '</div>';
            return;
        }        

        if (!empty($_GET['erro'])) {
            $mensagem_erro = sanitize_text_field(
                wp_unslash($_GET['erro'])
            );

            if ($mensagem_erro !== '') {
                ?>
                <div class="notice notice-error inline">
                    <p>
                        <?php echo esc_html($mensagem_erro); ?>
                    </p>
                </div>
                <?php
            }
        }

        $dres = $importacao['dres'];

        $dres_selecionadas = [];

        if (
            isset($importacao['configuracao']) &&
            !empty($importacao['configuracao']['dres'])
        ) {
            $dres_selecionadas = $importacao['configuracao']['dres'];
        }
        ?>
        <p>
            Selecione as DREs que participarão deste sorteio.
        </p>

        <?php if (empty($dres)) : ?>

            <div class="notice notice-warning inline">
                <p>Nenhuma DRE foi encontrada na planilha.</p>
            </div>

        <?php else : ?>

            <form
                method="post"
                action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
            >
                <input
                    type="hidden"
                    name="action"
                    value="sorteios_salvar_dres"
                >

                <?php wp_nonce_field(
                    'sorteios_salvar_dres',
                    'sorteios_nonce'
                ); ?>

                <?php
                /*
                * O token identifica qual importação temporária
                * estamos utilizando.
                */
                if (!empty($_GET['importacao'])) :
                    ?>
                    <input
                        type="hidden"
                        name="importacao"
                        value="<?php echo esc_attr(
                            sanitize_text_field(
                                wp_unslash($_GET['importacao'])
                            )
                        ); ?>"
                    >
                <?php endif; ?>

                <div class="sorteios-dres-selecao">

                    <div class="sorteios-dres-acoes">

                        <div class="sorteios-dres-acoes-botoes">

                            <button
                                type="button"
                                class="button"
                                id="sorteios-selecionar-todas-dres"
                            >
                                Selecionar todas
                            </button>

                            <button
                                type="button"
                                class="button"
                                id="sorteios-limpar-dres"
                            >
                                Limpar seleção
                            </button>

                        </div>

                        <span
                            class="sorteios-dres-contador"
                            id="sorteios-dres-contador"
                        >
                            0 de <?php echo esc_html(count($dres)); ?> DREs selecionadas
                        </span>

                    </div>

                    <div class="sorteios-dres-lista">

                        <?php foreach ($dres as $dre) : ?>

                            <label class="sorteios-dre-item">

                                <input
                                    type="checkbox"
                                    name="dres[]"
                                    value="<?php echo esc_attr($dre); ?>"
                                    <?php checked(
                                        in_array(
                                            $dre,
                                            $dres_selecionadas,
                                            true
                                        )
                                    ); ?>
                                >

                                <span>
                                    <?php echo esc_html($dre); ?>
                                </span>

                            </label>

                        <?php endforeach; ?>

                    </div>

                </div>

                <div class="sorteios-etapa-acoes">

                    <a
                        href="<?php echo esc_url(
                            admin_url(
                                'admin.php?page=novo-sorteio&etapa=1'
                                . (
                                    !empty($_GET['importacao'])
                                        ? '&importacao=' . rawurlencode(
                                            sanitize_text_field(
                                                wp_unslash($_GET['importacao'])
                                            )
                                        )
                                        : ''
                                )
                            )
                        ); ?>"
                        class="button"
                    >
                        Voltar
                    </a>

                    <button
                        type="submit"
                        class="button button-primary"
                    >
                        Continuar
                    </button>

                </div>

            </form>

        <?php endif; ?>
        <?php
    }

    private static function renderizar_modal_log(
        $importacao
    ) {
        if (
            empty(
                $importacao['erros_detalhados']
            )
        ) {
            return;
        }

        ?>

        <div
            id="sorteios-modal-log"
            class="sorteios-modal"
            style="display: none;"
        >

            <div class="sorteios-modal-overlay"></div>

            <div class="sorteios-modal-content">

                <div class="sorteios-modal-header">

                    <h2>
                        Log de importação
                    </h2>

                    <button
                        type="button"
                        class="sorteios-modal-fechar"
                        aria-label="Fechar"
                    >
                        &times;
                    </button>

                </div>

                <div class="sorteios-modal-body">

                    <table
                        class="widefat striped"
                    >

                        <thead>

                            <tr>
                                <th>Linha</th>
                                <th>Tipo</th>
                                <th>Detalhe</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $importacao['erros_detalhados']
                                as $erro
                            ) : ?>

                                <tr>

                                    <td>
                                        <?php echo esc_html(
                                            $erro['linha']
                                        ); ?>
                                    </td>

                                    <td>
                                        <?php echo esc_html(
                                            self::descricao_tipo_log(
                                                $erro['tipo']
                                            )
                                        ); ?>
                                    </td>

                                    <td>
                                        <?php echo esc_html(
                                            $erro['detalhe']
                                        ); ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

        <?php
    }

    public static function importar_planilha()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'Você não possui permissão para realizar esta ação.'
            );
        }

        if (
            empty($_POST['sorteios_nonce']) ||
            !wp_verify_nonce(
                $_POST['sorteios_nonce'],
                'sorteios_importar_planilha'
            )
        ) {
            wp_die(
                'Falha na validação de segurança.'
            );
        }

        if (empty($_FILES['arquivo'])) {
            self::redirecionar_com_erro(
                'Nenhum arquivo foi enviado.'
            );
        }

        try {

            $resultado = Sorteios_Importador::processar(
                $_FILES['arquivo']
            );

        } catch (Throwable $e) {

            self::redirecionar_com_erro(
                $e->getMessage()
            );
        }

        $token = wp_generate_password(
            32,
            false,
            false
        );

        $transient_key = self::TRANSIENT_PREFIX .
            get_current_user_id() .
            '_' .
            $token;

        set_transient(
            $transient_key,
            $resultado,
            DAY_IN_SECONDS
        );

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'novo-sorteio',
                    'importacao' => $token,
                ],
                admin_url('admin.php')
            )
        );

        exit;
    }

    private static function obter_importacao_temporaria()
    {
        $token = '';

        if (isset($_POST['importacao'])) {
            $token = sanitize_text_field(
                wp_unslash($_POST['importacao'])
            );
        } elseif (isset($_GET['importacao'])) {
            $token = sanitize_text_field(
                wp_unslash($_GET['importacao'])
            );
        }

        if ($token === '') {
            return false;
        }

        $chave = 'sorteios_importacao_' .
            get_current_user_id() .
            '_' .
            $token;

        return get_transient($chave);
    }

    private static function redirecionar_com_erro(
        $mensagem
    ) {
        $url = add_query_arg(
            [
                'page' => 'novo-sorteio',
                'erro' => rawurlencode($mensagem),
            ],
            admin_url('admin.php')
        );

        wp_safe_redirect($url);

        exit;
    }

    private static function redirecionar_etapa_com_erro(
        $etapa,
        $token,
        $mensagem
    ) {
        $url = add_query_arg(
            [
                'page' => 'novo-sorteio',
                'etapa' => absint($etapa),
                'importacao' => $token,
                'erro' => rawurlencode($mensagem),
            ],
            admin_url('admin.php')
        );

        wp_safe_redirect($url);
        exit;
    }

    public static function salvar_dres()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'Você não possui permissão para realizar esta ação.'
            );
        }

        if (
            empty($_POST['sorteios_nonce']) ||
            !wp_verify_nonce(
                $_POST['sorteios_nonce'],
                'sorteios_salvar_dres'
            )
        ) {
            wp_die(
                'Falha na validação de segurança.'
            );
        }

        $token = '';

        if (!empty($_POST['importacao'])) {
            $token = sanitize_text_field(
                wp_unslash($_POST['importacao'])
            );
        }

        if ($token === '') {
            self::redirecionar_com_erro(
                'A importação da planilha não foi identificada.'
            );
        }

        $transient_key = self::TRANSIENT_PREFIX .
            get_current_user_id() .
            '_' .
            $token;

        $importacao = get_transient($transient_key);

        if ($importacao === false) {
            self::redirecionar_com_erro(
                'A importação da planilha expirou. Importe o arquivo novamente.'
            );
        }

        $dres_disponiveis = [];

        if (!empty($importacao['dres'])) {
            $dres_disponiveis = $importacao['dres'];
        }

        $dres_recebidas = [];

        if (
            isset($_POST['dres']) &&
            is_array($_POST['dres'])
        ) {
            $dres_recebidas = array_map(
                function ($dre) {
                    return mb_strtoupper(
                        trim(
                            sanitize_text_field(
                                wp_unslash($dre)
                            )
                        ),
                        'UTF-8'
                    );
                },
                $_POST['dres']
            );
        }

        $dres_recebidas = array_values(
            array_unique($dres_recebidas)
        );

        if (empty($dres_recebidas)) {
            self::redirecionar_etapa_com_erro(
                2,
                $token,
                'Selecione pelo menos uma DRE para continuar.'
            );
        }

        /*
        * Garante que somente DREs existentes na planilha
        * possam ser selecionadas.
        */
        foreach ($dres_recebidas as $dre) {
            if (!in_array($dre, $dres_disponiveis, true)) {
                self::redirecionar_etapa_com_erro(
                    2,
                    $token,
                    'Uma ou mais DREs selecionadas não pertencem à planilha importada.'
                );
            }
        }

        /*
        * Inicializa a configuração caso ainda não exista.
        */
        if (
            !isset($importacao['configuracao']) ||
            !is_array($importacao['configuracao'])
        ) {
            $importacao['configuracao'] = [];
        }

        $importacao['configuracao']['dres'] = $dres_recebidas;

        /*
        * Atualiza o mesmo transient utilizado pela importação.
        */
        set_transient(
            $transient_key,
            $importacao,
            DAY_IN_SECONDS
        );

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'novo-sorteio',
                    'etapa' => 3,
                    'importacao' => $token,
                ],
                admin_url('admin.php')
            )
        );

        exit;
    }

    private static function renderizar_etapa_horta($importacao)
    {
        if (empty($importacao)) {
            echo '<div class="notice notice-warning inline">';
            echo '<p>Faça a importação da planilha antes de continuar.</p>';
            echo '</div>';
            return;
        }        

        $dres_selecionadas = [];

        if (
            isset($importacao['configuracao']['dres']) &&
            is_array($importacao['configuracao']['dres'])
        ) {
            $dres_selecionadas = $importacao['configuracao']['dres'];
        }

        if (empty($dres_selecionadas)) {
            echo '<div class="notice notice-warning inline">';
            echo '<p>Selecione pelo menos uma DRE antes de continuar.</p>';
            echo '</div>';
            return;
        }

        $quantidades_horta = [
            'Sim' => 0,
            'Não' => 0,
            'Não informado' => 0,
        ];

        $total_unidades = 0;

        foreach ($importacao['dados'] as $unidade) {

            if (
                !in_array(
                    $unidade['dre'],
                    $dres_selecionadas,
                    true
                )
            ) {
                continue;
            }

            $total_unidades++;

            if (isset($quantidades_horta[$unidade['horta']])) {
                $quantidades_horta[$unidade['horta']]++;
            }
        }

        $tipo_horta = isset($configuracao['tipo_horta'])
        ? $configuracao['tipo_horta']
        : 'Sim';

        if (
            isset($importacao['configuracao']['tipo_horta']) &&
            is_string($importacao['configuracao']['tipo_horta'])
        ) {
            $tipo_horta = $importacao['configuracao']['tipo_horta'];
        }

        if (!empty($_GET['erro'])) {
            $mensagem_erro = sanitize_text_field(
                wp_unslash($_GET['erro'])
            );

            if ($mensagem_erro !== '') {
                ?>
                <div class="notice notice-error inline">
                    <p>
                        <?php echo esc_html($mensagem_erro); ?>
                    </p>
                </div>
                <?php
            }
        }
        ?>

        <p>
            Selecione o tipo de horta das unidades que participarão do sorteio.
        </p>

        <form
            method="post"
            action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
        >
            <input
                type="hidden"
                name="action"
                value="sorteios_salvar_tipo_horta"
            >

            <?php wp_nonce_field(
                'sorteios_salvar_tipo_horta',
                'sorteios_nonce'
            ); ?>

            <input
                type="hidden"
                name="importacao"
                value="<?php echo esc_attr(
                    isset($_GET['importacao'])
                        ? sanitize_text_field(
                            wp_unslash($_GET['importacao'])
                        )
                        : ''
                ); ?>"
            >

            <div class="sorteios-horta-opcoes">

                <label class="sorteios-horta-opcao">

                    <input
                        type="radio"
                        name="tipo_horta"
                        value="Sim"
                        data-total="<?php echo esc_attr($quantidades_horta['Sim']); ?>"
                        <?php checked($tipo_horta, 'Sim'); ?>
                    >

                    <span>
                        <strong>Sim</strong>
                        <small>Participam apenas unidades que possuem horta ativa.</small>
                    </span>

                </label>

                <label class="sorteios-horta-opcao">

                    <input
                        type="radio"
                        name="tipo_horta"
                        value="Não"
                        data-total="<?php echo esc_attr($quantidades_horta['Não']); ?>"
                        <?php checked($tipo_horta, 'Não'); ?>
                    >

                    <span>
                        <strong>Não</strong>
                        <small>Participam apenas unidades que não possuem horta.</small>
                    </span>

                </label>

                <label class="sorteios-horta-opcao">
                    <input
                        type="radio"
                        name="tipo_horta"
                        value="Todos"
                        data-total="<?php echo esc_attr($total_unidades); ?>"
                        <?php checked($tipo_horta, 'Todos'); ?>
                    >

                    <span>
                        <strong>Todos</strong>
                        <small>Participam todas as unidades, independentemente de possuírem horta ou não.</small>
                    </span>
                </label>

                <div
                    class="sorteios-horta-total"
                    id="sorteios-horta-total"
                >
                    <?php
                    echo esc_html(
                        number_format_i18n(
                            $quantidades_horta[$tipo_horta]
                        )
                    );
                    ?>
                    unidades aptas nas DREs selecionadas
                </div>

            </div>

            <div class="sorteios-etapa-acoes">

                <a
                    href="<?php echo esc_url(
                        add_query_arg(
                            [
                                'page' => 'novo-sorteio',
                                'etapa' => 2,
                                'importacao' => isset($_GET['importacao'])
                                    ? sanitize_text_field(
                                        wp_unslash($_GET['importacao'])
                                    )
                                    : '',
                            ],
                            admin_url('admin.php')
                        )
                    ); ?>"
                    class="button"
                >
                    Voltar
                </a>

                <button
                    type="submit"
                    class="button button-primary"
                >
                    Continuar
                </button>

            </div>

        </form>

        <?php
    }

    private static function contar_hortas_ativas(
        array $dados
    ) {
        $total = 0;

        foreach ($dados as $registro) {

            if (
                isset($registro['horta']) &&
                $registro['horta'] === 'Sim'
            ) {
                $total++;
            }
        }

        return $total;
    }

    public static function salvar_tipo_horta()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'Você não possui permissão para realizar esta ação.'
            );
        }

        if (
            empty($_POST['sorteios_nonce']) ||
            !wp_verify_nonce(
                $_POST['sorteios_nonce'],
                'sorteios_salvar_tipo_horta'
            )
        ) {
            wp_die(
                'Falha na validação de segurança.'
            );
        }

        $token = '';

        if (!empty($_POST['importacao'])) {
            $token = sanitize_text_field(
                wp_unslash($_POST['importacao'])
            );
        }

        if ($token === '') {
            self::redirecionar_com_erro(
                'A importação da planilha não foi identificada.'
            );
        }

        $transient_key = self::TRANSIENT_PREFIX .
            get_current_user_id() .
            '_' .
            $token;

        $importacao = get_transient($transient_key);

        if ($importacao === false) {
            self::redirecionar_com_erro(
                'A importação da planilha expirou. Importe o arquivo novamente.'
            );
        }

        $tipo_horta = '';

        if (isset($_POST['tipo_horta'])) {
            $tipo_horta = sanitize_text_field(
                wp_unslash($_POST['tipo_horta'])
            );
        }

        $tipos_validos = [
            'Todos',
            'Sim',
            'Não',
        ];

        if (!in_array($tipo_horta, $tipos_validos, true)) {
            self::redirecionar_etapa_com_erro(
                3,
                $token,
                'Selecione um tipo de horta para continuar.'
            );
        }

        if (
            !isset($importacao['configuracao']) ||
            !is_array($importacao['configuracao'])
        ) {
            $importacao['configuracao'] = [];
        }

        $importacao['configuracao']['tipo_horta'] = $tipo_horta;

        set_transient(
            $transient_key,
            $importacao,
            DAY_IN_SECONDS
        );

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'novo-sorteio',
                    'etapa' => 4,
                    'importacao' => $token,
                ],
                admin_url('admin.php')
            )
        );

        exit;
    }

    private static function montar_resumo_ignoradas(
    array $importacao
    ) {
        $quantidades = [
            'linha_vazia' => 0,
            'cie_duplicado' => 0,
            'erro' => 0,
        ];

        foreach (
            $importacao['erros_detalhados']
            as $erro
        ) {

            if ($erro['tipo'] === 'linha_vazia') {

                $quantidades['linha_vazia']++;

                continue;
            }

            if ($erro['tipo'] === 'cie_duplicado') {

                $quantidades['cie_duplicado']++;

                continue;
            }

            /*
            * Qualquer outro tipo representa uma linha
            * inválida que não poderá ser utilizada.
            */
            $quantidades['erro']++;
        }

        $total_ignoradas = array_sum(
            $quantidades
        );

        if ($total_ignoradas === 0) {
            return '';
        }

        $partes = [];

        if ($quantidades['linha_vazia'] > 0) {

            $partes[] = sprintf(
                '%d linha%s vazia%s',
                $quantidades['linha_vazia'],
                $quantidades['linha_vazia'] === 1
                    ? ''
                    : 's',
                $quantidades['linha_vazia'] === 1
                    ? ''
                    : 's'
            );
        }

        if ($quantidades['cie_duplicado'] > 0) {

            $partes[] = sprintf(
                '%d CIE%s duplicado%s',
                $quantidades['cie_duplicado'],
                $quantidades['cie_duplicado'] === 1
                    ? ''
                    : 's',
                $quantidades['cie_duplicado'] === 1
                    ? ''
                    : 's'
            );
        }

        if ($quantidades['erro'] > 0) {

            $partes[] = sprintf(
                '%d linha%s com erro',
                $quantidades['erro'],
                $quantidades['erro'] === 1
                    ? ''
                    : 's'
            );
        }

        if (count($partes) > 1) {

            $ultima_parte = array_pop(
                $partes
            );

            $descricao =
                implode(', ', $partes) .
                ' e ' .
                $ultima_parte;

        } else {

            $descricao = $partes[0];
        }

        return sprintf(
            '%d linha%s ignorada%s. %s.',
            $total_ignoradas,
            $total_ignoradas === 1
                ? ''
                : 's',
            $total_ignoradas === 1
                ? ''
                : 's',
            $descricao
        );
    }

    private static function descricao_tipo_log(
        $tipo
    ) {
        $descricoes = [
            'linha_vazia' => 'Linha vazia',
            'cie_duplicado' => 'CIE duplicado',
            'dados_incompletos' => 'Dados incompletos',
            'formato_unidade' => 'Formato da unidade',
            'cie_invalido' => 'CIE inválido',
            'conflito_cie' => 'Conflito de CIE',
        ];

        return isset($descricoes[$tipo])
            ? $descricoes[$tipo]
            : $tipo;
    }

    private static function renderizar_etapa_sorteio($importacao)
    {
        if (empty($importacao)) {
            echo '<div class="notice notice-warning inline">';
            echo '<p>Faça a importação da planilha antes de continuar.</p>';
            echo '</div>';
            return;
        }

        $configuracao = isset($importacao['configuracao'])
            && is_array($importacao['configuracao'])
            ? $importacao['configuracao']
            : [];

        $dres_selecionadas = isset($configuracao['dres'])
            && is_array($configuracao['dres'])
            ? $configuracao['dres']
            : [];

        $tipo_horta = isset($configuracao['tipo_horta'])
            ? $configuracao['tipo_horta']
            : '';

        if (empty($dres_selecionadas)) {
            echo '<div class="notice notice-warning inline">';
            echo '<p>Selecione pelo menos uma DRE antes de continuar.</p>';
            echo '</div>';
            return;
        }

        if ($tipo_horta === '') {
            echo '<div class="notice notice-warning inline">';
            echo '<p>Selecione o tipo de horta antes de continuar.</p>';
            echo '</div>';
            return;
        }

        $quantidade = isset($configuracao['quantidade'])
        ? absint($configuracao['quantidade'])
        : 5;

        /*
        * Quantidade total de DREs selecionadas.
        */
        $total_dres = count($dres_selecionadas);

        /*
        * Conta as unidades aptas considerando:
        *
        * - apenas as DREs selecionadas;
        * - apenas o tipo de horta escolhido.
        */
        $quantidade_disponivel = 0;

        foreach ($importacao['dados'] as $unidade) {

            if (
                !in_array(
                    $unidade['dre'],
                    $dres_selecionadas,
                    true
                )
            ) {
                continue;
            }

            if (
                $tipo_horta !== 'Todos' &&
                $unidade['horta'] !== $tipo_horta
            ) {
                continue;
            }

            $quantidade_disponivel++;
        }

        /*
        * Quantidade total que será solicitada
        * no sorteio.
        *
        * Exemplo:
        * 8 DREs × 5 unidades = 40 unidades.
        */
        $quantidade_solicitada = $total_dres * $quantidade;

        /*
        * Quantidade que ficará em lista de espera
        * quando houver unidades além das vagas solicitadas.
        */
        $quantidade_lista_espera = max(
            0,
            $quantidade_disponivel - $quantidade_solicitada
        );

        if (!empty($_GET['erro'])) {
            $mensagem_erro = sanitize_text_field(
                wp_unslash($_GET['erro'])
            );

            if ($mensagem_erro !== '') {
                ?>
                <div class="notice notice-error inline">
                    <p>
                        <?php echo esc_html($mensagem_erro); ?>
                    </p>
                </div>
                <?php
            }
        }
        ?>

        <p>
            Informe a quantidade de unidades que deverá ser sorteada em cada DRE.
        </p>

        <form
            method="post"
            action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
        >
            <input
                type="hidden"
                name="action"
                value="sorteios_realizar"
            >

            <?php wp_nonce_field(
                'sorteios_realizar',
                'sorteios_nonce'
            ); ?>

            <input
                type="hidden"
                name="importacao"
                value="<?php echo esc_attr(
                    isset($_GET['importacao'])
                        ? sanitize_text_field(
                            wp_unslash($_GET['importacao'])
                        )
                        : ''
                ); ?>"
            >

            <div class="sorteios-quantidade">

                <label for="sorteios-quantidade">
                    Quantidade de unidades por DRE
                </label>

                <input
                    type="number"
                    id="sorteios-quantidade"
                    name="quantidade"
                    value="<?php echo esc_attr($quantidade); ?>"
                    min="1"
                    step="1"
                    required
                >

            </div>

            <div
                class="sorteios-quantidade-resumo"
                id="sorteios-quantidade-resumo"
                data-total-dres="<?php echo esc_attr($total_dres); ?>"
                data-total-disponivel="<?php echo esc_attr($quantidade_disponivel); ?>"
            >
                <?php
                if ($quantidade_disponivel === 0) :
                    ?>

                    Não existem unidades aptas nas DREs selecionadas
                    para o tipo de horta escolhido.

                    <?php
                elseif ($quantidade_disponivel >= $quantidade_solicitada) :
                    ?>

                    Serão sorteadas até
                    <strong>
                        <?php echo esc_html($quantidade_solicitada); ?>
                    </strong>
                    unidades
                    (<?php echo esc_html($total_dres); ?> DREs ×
                    <span class="sorteios-quantidade-atual">
                        <?php echo esc_html($quantidade); ?>
                    </span>).

                    <?php if ($quantidade_lista_espera > 0) : ?>

                        As demais
                        <strong>
                            <?php echo esc_html($quantidade_lista_espera); ?>
                        </strong>
                        entram em lista de espera.

                    <?php else : ?>

                        Não há unidades adicionais em lista de espera.

                    <?php endif; ?>

                    <?php
                else :
                    ?>

                    Serão sorteadas até
                    <strong>
                        <?php echo esc_html($quantidade_solicitada); ?>
                    </strong>
                    unidades
                    (<?php echo esc_html($total_dres); ?> DREs ×
                    <span class="sorteios-quantidade-atual">
                        <?php echo esc_html($quantidade); ?>
                    </span>).
                    Existem apenas
                    <strong>
                        <?php echo esc_html($quantidade_disponivel); ?>
                    </strong>
                    unidades aptas disponíveis.

                    <?php
                endif;
                ?>
            </div>

            <div class="notice notice-info inline sorteios-aviso-sorteio">
                <p>
                    Se alguma DRE tiver menos unidades aptas que o número informado,
                    o sistema sorteia todas as disponíveis e registra a diferença
                    no resumo.
                </p>
            </div>

            <div class="sorteios-etapa-acoes">

                <a
                    href="<?php echo esc_url(
                        add_query_arg(
                            [
                                'page' => 'novo-sorteio',
                                'etapa' => 3,
                                'importacao' => isset($_GET['importacao'])
                                    ? sanitize_text_field(
                                        wp_unslash($_GET['importacao'])
                                    )
                                    : '',
                            ],
                            admin_url('admin.php')
                        )
                    ); ?>"
                    class="button"
                >
                    Voltar
                </a>

                <button
                    type="submit"
                    class="button button-primary"
                >
                    Realizar sorteio
                </button>

            </div>

        </form>

        <?php
    }

    private static function obter_quantidades_elegiveis_por_dre(
        array $dados,
        array $dres,
        $tipo_horta
    ) {
        $quantidades = [];

        foreach ($dres as $dre) {
            $quantidades[$dre] = 0;
        }

        foreach ($dados as $registro) {
            if (
                !isset($registro['dre']) ||
                !in_array(
                    $registro['dre'],
                    $dres,
                    true
                )
            ) {
                continue;
            }

            if (
                !isset($registro['horta']) ||
                $registro['horta'] !== $tipo_horta
            ) {
                continue;
            }

            $quantidades[$registro['dre']]++;
        }

        return $quantidades;
    }

    public static function realizar_sorteio()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'Você não tem permissão para realizar o sorteio.'
            );
        }

        check_admin_referer(
            'sorteios_realizar',
            'sorteios_nonce'
        );

        $importacao = self::obter_importacao_temporaria();

        if (!$importacao) {
            self::redirecionar_com_erro(
                'A sessão de importação expirou. Faça o upload da planilha novamente.'
            );
        }

        $configuracao = isset($importacao['configuracao'])
            && is_array($importacao['configuracao'])
            ? $importacao['configuracao']
            : [];

        $dres_selecionadas = isset($configuracao['dres'])
            && is_array($configuracao['dres'])
            ? $configuracao['dres']
            : [];

        $tipo_horta = isset($configuracao['tipo_horta'])
            ? $configuracao['tipo_horta']
            : '';

        $quantidade = isset($_POST['quantidade'])
            ? absint($_POST['quantidade'])
            : 0;

        if (empty($dres_selecionadas)) {
            self::redirecionar_com_erro(
                'Nenhuma DRE foi selecionada.'
            );
        }

        if (
            !in_array(
                $tipo_horta,
                [
                    'Todos',
                    'Sim',
                    'Não',
                ],
                true
            )
        ) {
            self::redirecionar_com_erro(
                'O tipo de horta selecionado é inválido.'
            );
        }

        if ($quantidade < 1) {
            self::redirecionar_com_erro(
                'A quantidade de unidades por DRE deve ser maior que zero.'
            );
        }

        if (
            empty($importacao['dados']) ||
            !is_array($importacao['dados'])
        ) {
            self::redirecionar_com_erro(
                'Não existem unidades disponíveis para realizar o sorteio.'
            );
        }

        /*
        * Organiza as unidades elegíveis por DRE.
        */
        $unidades_por_dre = [];

        foreach ($dres_selecionadas as $dre) {
            $unidades_por_dre[$dre] = [];
        }

        foreach ($importacao['dados'] as $unidade) {

            if (
                !isset($unidade['dre']) ||
                !in_array(
                    $unidade['dre'],
                    $dres_selecionadas,
                    true
                )
            ) {
                continue;
            }

            /*
            * "Todos" não aplica nenhum filtro sobre a horta.
            */
            if (
                $tipo_horta !== 'Todos' &&
                $unidade['horta'] !== $tipo_horta
            ) {
                continue;
            }

            $unidades_por_dre[$unidade['dre']][] = $unidade;
        }

        /*
        * Verifica se existe pelo menos uma unidade
        * apta para o sorteio.
        */
        $total_unidades_disponiveis = 0;

        foreach ($unidades_por_dre as $unidades) {
            $total_unidades_disponiveis += count($unidades);
        }

        if ($total_unidades_disponiveis === 0) {
            self::redirecionar_com_erro(
                'Não existem unidades aptas nas DREs selecionadas para realizar o sorteio.'
            );
        }

        global $wpdb;

        $table_sorteios = $wpdb->prefix . 'sorteios';
        $table_dres = $wpdb->prefix . 'sorteio_dres';
        $table_unidades = $wpdb->prefix . 'sorteio_unidades';
        $table_historico = $wpdb->prefix . 'sorteio_historico';

        $usuario_id = get_current_user_id();

        $agora = current_time('mysql');

        /*
        * Nome do sorteio.
        */
        $nome_sorteio = sprintf(
            'Sorteio - %s',
            current_time('d/m/Y H:i')
        );

        /*
        * Arquivo utilizado.
        */
        $arquivo_nome = !empty($importacao['arquivo_nome'])
            ? $importacao['arquivo_nome']
            : null;

        $arquivo_origem = !empty($importacao['arquivo_caminho'])
            ? $importacao['arquivo_caminho']
            : '';

        /*
        * Inicia a transação.
        */
        $wpdb->query('START TRANSACTION');

        try {

            /*
            * Cria o registro principal do sorteio.
            */
            $inserido = $wpdb->insert(
                $table_sorteios,
                [
                    'nome' => $nome_sorteio,
                    'arquivo_nome' => $arquivo_nome,
                    'arquivo_caminho' => null,
                    'usuario_criacao' => $usuario_id,
                    'data_criacao' => $agora,
                    'usuario_sorteio' => $usuario_id,
                    'data_sorteio' => $agora,
                    'status' => 'realizado',
                ],
                [
                    '%s',
                    '%s',
                    null,
                    '%d',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                ]
            );

            if (!$inserido) {
                throw new Exception(
                    'Não foi possível criar o registro do sorteio.'
                );
            }

            $sorteio_id = (int) $wpdb->insert_id;

            /*
            * Preserva o arquivo utilizado no sorteio.
            */
            $arquivo_caminho = null;

            if (
                $arquivo_origem !== '' &&
                file_exists($arquivo_origem)
            ) {
                $upload_dir = wp_upload_dir();

                $diretorio_sorteio = trailingslashit(
                    $upload_dir['basedir']
                ) . 'sorteios/' . $sorteio_id;

                if (
                    !wp_mkdir_p($diretorio_sorteio)
                ) {
                    throw new Exception(
                        'Não foi possível criar o diretório do arquivo do sorteio.'
                    );
                }

                $nome_arquivo = sanitize_file_name(
                    $arquivo_nome
                );

                $arquivo_destino = trailingslashit(
                    $diretorio_sorteio
                ) . $nome_arquivo;

                if (
                    !copy(
                        $arquivo_origem,
                        $arquivo_destino
                    )
                ) {
                    throw new Exception(
                        'Não foi possível preservar o arquivo utilizado no sorteio.'
                    );
                }

                $arquivo_caminho = str_replace(
                    trailingslashit($upload_dir['basedir']),
                    '',
                    $arquivo_destino
                );

                $wpdb->update(
                    $table_sorteios,
                    [
                        'arquivo_caminho' => $arquivo_caminho,
                    ],
                    [
                        'id' => $sorteio_id,
                    ],
                    [
                        '%s',
                    ],
                    [
                        '%d',
                    ]
                );
            }

            /*
            * Registra a realização do sorteio.
            */
            $inserido_historico = $wpdb->insert(
                $table_historico,
                [
                    'sorteio_id' => $sorteio_id,
                    'unidade_id' => null,
                    'usuario_id' => $usuario_id,
                    'acao' => 'sorteio_realizado',
                    'campo' => 'quantidade',
                    'valor_anterior' => null,
                    'valor_novo' => (string) $quantidade,
                    'observacao' => sprintf(
                        'Sorteio realizado com %d unidade(s) por DRE. Arquivo utilizado: %s',
                        $quantidade,
                        $arquivo_nome ?: 'Não informado'
                    ),
                    'created_at' => $agora,
                ],
                [
                    '%d',
                    null,
                    '%d',
                    '%s',
                    '%s',
                    null,
                    '%s',
                    '%s',
                    '%s',
                ]
            );

            if (!$inserido_historico) {
                throw new Exception(
                    'Não foi possível registrar o histórico do sorteio.'
                );
            }

            /*
            * Registra as DREs e sorteia as unidades.
            */
            foreach ($unidades_por_dre as $dre => $unidades) {

                $total_unidades = count($unidades);

                /*
                * Registra a DRE.
                */
                $inserido = $wpdb->insert(
                    $table_dres,
                    [
                        'sorteio_id' => $sorteio_id,
                        'dre' => $dre,
                        'total_unidades' => $total_unidades,
                        'quantidade_vagas' => $quantidade,
                    ],
                    [
                        '%d',
                        '%s',
                        '%d',
                        '%d',
                    ]
                );

                if (!$inserido) {
                    throw new Exception(
                        sprintf(
                            'Não foi possível registrar a DRE %s.',
                            $dre
                        )
                    );
                }

                /*
                * Embaralha as unidades daquela DRE.
                */
                shuffle($unidades);

                foreach ($unidades as $indice => $unidade) {

                    $ordem = $indice + 1;

                    if ($ordem <= $quantidade) {
                        $tipo_resultado = 'selecionada';
                        $status = 'aguardando_confirmacao';
                    } else {
                        $tipo_resultado = 'reserva';
                        $status = 'lista_espera';
                    }

                    $inserido = $wpdb->insert(
                        $table_unidades,
                        [
                            'sorteio_id' => $sorteio_id,
                            'dre' => $unidade['dre'],
                            'cie' => $unidade['cie'],
                            'nome_unidade' => $unidade['nome_unidade'],
                            'horta' => $unidade['horta'],
                            'ordem_sorteio' => $ordem,
                            'tipo_resultado' => $tipo_resultado,
                            'status' => $status,
                            'created_at' => $agora,
                            'updated_at' => $agora,
                        ],
                        [
                            '%d',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%d',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                        ]
                    );

                    if (!$inserido) {
                        throw new Exception(
                            sprintf(
                                'Não foi possível registrar a unidade %s da DRE %s.',
                                $unidade['cie'],
                                $dre
                            )
                        );
                    }
                }
            }

            /*
            * Tudo foi gravado com sucesso.
            */
            $wpdb->query('COMMIT');

            /*
            * Remove o arquivo temporário da importação.
            */
            if (
                $arquivo_origem !== '' &&
                file_exists($arquivo_origem)
            ) {
                wp_delete_file($arquivo_origem);
            }

            /*
            * Remove o diretório temporário, se estiver vazio.
            */
            if (
                $arquivo_origem !== '' &&
                is_dir(dirname($arquivo_origem))
            ) {
                @rmdir(dirname($arquivo_origem));
            }

            /*
            * Remove o transient da importação.
            */
            $token = isset($_GET['importacao'])
                ? sanitize_text_field(
                    wp_unslash($_GET['importacao'])
                )
                : '';

            if ($token !== '') {

                delete_transient(
                    'sorteios_importacao_' .
                    $usuario_id .
                    '_' .
                    $token
                );
            }

            wp_safe_redirect(
                add_query_arg(
                    [
                        'page' => 'sorteios',
                        'sorteio' => $sorteio_id,
                        'sucesso' => 1,
                    ],
                    admin_url('admin.php')
                )
            );

            exit;

        } catch (Throwable $e) {

            $wpdb->query('ROLLBACK');

            self::redirecionar_com_erro(
                $e->getMessage()
            );
        }
    }

    public static function alterar_status()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => 'Você não tem permissão para realizar esta ação.'
            ], 403);
        }

        check_ajax_referer(
            'sorteios_alterar_status',
            'nonce'
        );

        $unidade_id = isset($_POST['unidade_id'])
            ? absint($_POST['unidade_id'])
            : 0;

        $novo_status = isset($_POST['status'])
            ? sanitize_key($_POST['status'])
            : '';

        $status_permitidos = [
            'aguardando_confirmacao',
            'confirmado',
            'lista_espera',
            'desistencia',
        ];

        if ($unidade_id <= 0) {
            wp_send_json_error([
                'message' => 'Unidade inválida.'
            ]);
        }

        if (!in_array($novo_status, $status_permitidos, true)) {
            wp_send_json_error([
                'message' => 'Status inválido.'
            ]);
        }

        global $wpdb;

        $table_unidades = $wpdb->prefix . 'sorteio_unidades';

        $unidade = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$table_unidades}
                WHERE id = %d",
                $unidade_id
            )
        );

        if (!$unidade) {
            wp_send_json_error([
                'message' => 'Unidade não encontrada.'
            ]);
        }

        /*
        * Labels dos status.
        */
        $status_labels = [
            'aguardando_confirmacao' => 'Sorteado – Aguardando confirmação',
            'confirmado' => 'Sorteado – Confirmado',
            'lista_espera' => 'Lista de espera',
            'desistencia' => 'Desistência',
        ];

        /*
        * Classes CSS dos status.
        */
        $status_classes = [
            'aguardando_confirmacao' => 'aguardando-confirmacao',
            'confirmado' => 'confirmado',
            'lista_espera' => 'lista-espera',
            'desistencia' => 'desistencia',
        ];

        /*
        * Se o status já for o mesmo, não é necessário
        * atualizar o banco nem registrar histórico.
        */
        if ($unidade->status === $novo_status) {
            wp_send_json_success([
                'message' => 'O status não foi alterado.',
                'status' => $novo_status,
                'label' => $status_labels[$novo_status],
                'statusClass' => $status_classes[$novo_status]
            ]);
        }

        $atualizado = $wpdb->update(
            $table_unidades,
            [
                'status' => $novo_status,
                'updated_at' => current_time('mysql')
            ],
            [
                'id' => $unidade_id
            ],
            [
                '%s',
                '%s'
            ],
            [
                '%d'
            ]
        );

        if ($atualizado === false) {
            wp_send_json_error([
                'message' => 'Não foi possível atualizar o status.'
            ]);
        }

        $table_historico = $wpdb->prefix . 'sorteio_historico';

        $usuario_id = get_current_user_id();

        $wpdb->insert(
            $table_historico,
            [
                'sorteio_id' => $unidade->sorteio_id,
                'unidade_id' => $unidade_id,
                'usuario_id' => $usuario_id,
                'acao' => 'alteracao_status',
                'campo' => 'status',
                'valor_anterior' => $unidade->status,
                'valor_novo' => $novo_status,
                'created_at' => current_time('mysql')
            ],
            [
                '%d',
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            ]
        );

        /*
        * Retorna todas as informações necessárias
        * para atualizar o status visualmente na tabela.
        */
        wp_send_json_success([
            'message' => 'Status atualizado com sucesso.',
            'status' => $novo_status,
            'label' => $status_labels[$novo_status],
            'statusClass' => $status_classes[$novo_status]
        ]);
    }

    private static function renderizar_historico_sorteio(
        array $historico
    ) {
        ?>
        <div class="sorteios-historico">

            <h2>Histórico</h2>

            <?php if (empty($historico)) : ?>

                <p>
                    Nenhum registro encontrado no histórico.
                </p>

            <?php else : ?>

                <table
                    class="widefat striped"
                    id="tabela-historico-sorteio"
                >
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Ação</th>
                            <th>Unidade</th>
                            <th>Detalhes</th>
                            <th>Usuário</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($historico as $registro) : ?>

                            <tr>

                                <td>
                                    <?php
                                    echo esc_html(
                                        mysql2date(
                                            'd/m/Y H:i',
                                            $registro->created_at
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        self::obter_label_acao_historico(
                                            $registro->acao
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php if (
                                        $registro->acao === 'alteracao_status'
                                    ) : ?>

                                        <strong>
                                            <?php
                                            echo esc_html(
                                                $registro->nome_unidade
                                            );
                                            ?>
                                        </strong>

                                        <br>

                                        <small>
                                            CIE:
                                            <?php
                                            echo esc_html(
                                                $registro->cie
                                            );
                                            ?>
                                        </small>

                                    <?php else : ?>

                                        —

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (
                                        $registro->acao === 'sorteio_realizado'
                                    ) : ?>

                                        <?php
                                        echo esc_html(
                                            $registro->observacao
                                        );
                                        ?>

                                        <?php if (
                                            !empty($registro->arquivo_caminho)
                                        ) : ?>

                                            <?php
                                            $url_download = wp_nonce_url(
                                                add_query_arg(
                                                    [
                                                        'action' =>
                                                            'sorteios_baixar_arquivo',

                                                        'sorteio' =>
                                                            $registro->sorteio_id,
                                                    ],
                                                    admin_url(
                                                        'admin-post.php'
                                                    )
                                                ),
                                                'sorteios_baixar_arquivo_' .
                                                $registro->sorteio_id
                                            );
                                            ?>

                                            <br>

                                            <a
                                                href="<?php echo esc_url(
                                                    $url_download
                                                ); ?>"
                                                class="button button-small sorteios-baixar-arquivo"
                                            >
                                                <span
                                                    class="dashicons dashicons-download"
                                                    aria-hidden="true"
                                                ></span>

                                                Baixar arquivo
                                            </a>

                                        <?php endif; ?>

                                    <?php elseif (
                                        $registro->acao === 'alteracao_status'
                                    ) : ?>

                                        <span
                                            class="sorteios-status sorteios-status-<?php echo esc_attr(
                                                self::obter_classe_status(
                                                    $registro->valor_anterior
                                                )
                                            ); ?>"
                                        >
                                            <?php
                                            echo esc_html(
                                                self::obter_label_status(
                                                    $registro->valor_anterior
                                                )
                                            );
                                            ?>
                                        </span>

                                        <span class="sorteios-historico-seta">
                                            &rarr;
                                        </span>

                                        <span
                                            class="sorteios-status sorteios-status-<?php echo esc_attr(
                                                self::obter_classe_status(
                                                    $registro->valor_novo
                                                )
                                            ); ?>"
                                        >
                                            <?php
                                            echo esc_html(
                                                self::obter_label_status(
                                                    $registro->valor_novo
                                                )
                                            );
                                            ?>
                                        </span>

                                    <?php else : ?>

                                        <?php
                                        echo esc_html(
                                            $registro->observacao ?: '—'
                                        );
                                        ?>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $registro->usuario_nome
                                        ?: 'Usuário não identificado'
                                    );
                                    ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>

            <?php endif; ?>

        </div>
        <?php
    }

    private static function obter_label_status($status)
    {
        $labels = [
            'aguardando_confirmacao' => 'Sorteado – Aguardando confirmação',
            'confirmado' => 'Sorteado – Confirmado',
            'lista_espera' => 'Lista de espera',
            'desistencia' => 'Desistência',
        ];

        return $labels[$status] ?? $status;
    }

    private static function obter_label_acao_historico($acao)
    {
        $labels = [
            'sorteio_realizado' => 'Sorteio realizado',
            'alteracao_status' => 'Alteração de status',
        ];

        return $labels[$acao] ?? $acao;
    }

    private static function renderizar_lista_sorteios()
    {
        $sorteios = self::obter_sorteios();
        ?>

        <div class="wrap">

            <h1 class="wp-heading-inline mb-4">
                Sorteios
            </h1>

            <a
                href="<?php echo esc_url(
                    admin_url(
                        'admin.php?page=novo-sorteio'
                    )
                ); ?>"
                class="page-title-action"
            >
                Novo sorteio
            </a>

            <hr class="wp-header-end">

            <?php if (
                isset($_GET['excluido']) &&
                absint($_GET['excluido']) === 1
            ) : ?>

                <div
                    class="notice notice-success is-dismissible"
                >
                    <p>
                        Sorteio excluído com sucesso.
                    </p>
                </div>

            <?php endif; ?>

            <?php if (empty($sorteios)) : ?>

                <div class="notice notice-info">
                    <p>
                        Nenhum sorteio foi realizado até o momento.
                    </p>
                </div>

            <?php else : ?>

                <table class="widefat striped">

                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Data</th>
                            <th>Usuário</th>
                            <th>DREs</th>
                            <th>Unidades</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($sorteios as $sorteio) : ?>

                            <?php
                            $usuario = get_userdata(
                                $sorteio->usuario_sorteio
                            );
                            ?>

                            <tr>

                                <td>
                                    <a
                                        href="<?php echo esc_url(
                                            add_query_arg(
                                                [
                                                    'page' => 'sorteios',
                                                    'sorteio' => $sorteio->id
                                                ],
                                                admin_url('admin.php')
                                            )
                                        ); ?>"
                                        class=""
                                    >
                                        <strong>
                                            <?php echo esc_html(
                                                $sorteio->nome
                                            ); ?>
                                        </strong>
                                    </a>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        mysql2date(
                                            'd/m/Y H:i',
                                            $sorteio->data_sorteio
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $usuario
                                            ? $usuario->display_name
                                            : '-'
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php echo esc_html(
                                        $sorteio->total_dres
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo esc_html(
                                        $sorteio->total_unidades
                                    ); ?>
                                </td>

                                <td>

                                    <a
                                        href="<?php echo esc_url(
                                            add_query_arg(
                                                [
                                                    'page' => 'sorteios',
                                                    'sorteio' => $sorteio->id
                                                ],
                                                admin_url('admin.php')
                                            )
                                        ); ?>"
                                        class="button button-primary"
                                    >
                                        Ver sorteio
                                    </a>

                                    <button
                                        type="button"
                                        class="button sorteios-excluir-sorteio sorteios-excluir-sorteio"
                                        data-sorteio-id="<?php echo esc_attr(
                                            $sorteio->id
                                        ); ?>"
                                        data-sorteio-nome="<?php echo esc_attr(
                                            $sorteio->nome
                                        ); ?>"
                                    >
                                        Excluir
                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

                <div
                    class="sorteios-modal"
                    id="sorteios-modal-excluir"
                    aria-hidden="true"
                >
                    <div class="sorteios-modal-overlay"></div>

                    <div
                        class="sorteios-modal-conteudo"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="sorteios-modal-excluir-titulo"
                    >
                        <h2 id="sorteios-modal-excluir-titulo">
                            Excluir sorteio?
                        </h2>

                        <p>
                            Tem certeza de que deseja excluir
                            <strong id="sorteios-excluir-nome"></strong>?
                        </p>

                        <p>
                            Esta ação excluirá permanentemente o sorteio,
                            seus resultados, histórico e o arquivo utilizado.
                        </p>

                        <form
                            method="post"
                            action="<?php echo esc_url(
                                admin_url('admin-post.php')
                            ); ?>"
                        >
                            <input
                                type="hidden"
                                name="action"
                                value="sorteios_excluir"
                            >

                            <input
                                type="hidden"
                                name="sorteio_id"
                                id="sorteios-excluir-id"
                                value=""
                            >

                            <?php
                            wp_nonce_field(
                                'sorteios_excluir',
                                'sorteios_nonce'
                            );
                            ?>

                            <div class="sorteios-modal-acoes">

                                <button
                                    type="button"
                                    class="button"
                                    id="sorteios-cancelar-exclusao"
                                >
                                    Cancelar
                                </button>

                                <button
                                    type="submit"
                                    class="button sorteios-botao-excluir"
                                >
                                    Excluir sorteio
                                </button>

                            </div>
                        </form>
                    </div>
                </div>

            <?php endif; ?>

        </div>

        <?php
    }

    public static function baixar_arquivo_sorteio()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'Você não tem permissão para baixar este arquivo.'
            );
        }

        $sorteio_id = isset($_GET['sorteio'])
            ? absint($_GET['sorteio'])
            : 0;

        if ($sorteio_id <= 0) {
            wp_die(
                'Sorteio inválido.'
            );
        }

        check_admin_referer(
            'sorteios_baixar_arquivo_' . $sorteio_id
        );

        $sorteio = self::obter_sorteio(
            $sorteio_id
        );

        if (!$sorteio) {
            wp_die(
                'Sorteio não encontrado.'
            );
        }

        if (empty($sorteio->arquivo_caminho)) {
            wp_die(
                'Este sorteio não possui arquivo armazenado.'
            );
        }

        $upload_dir = wp_upload_dir();

        if (!empty($upload_dir['error'])) {
            wp_die(
                'Não foi possível acessar o diretório de uploads.'
            );
        }

        /*
        * Diretório base permitido.
        */
        $base_dir = realpath(
            $upload_dir['basedir']
        );

        /*
        * Monta o caminho físico a partir do caminho
        * relativo armazenado no banco.
        */
        $arquivo = realpath(
            trailingslashit(
                $upload_dir['basedir']
            ) .
            ltrim(
                $sorteio->arquivo_caminho,
                '/\\'
            )
        );

        if (
            !$base_dir ||
            !$arquivo ||
            !is_file($arquivo)
        ) {
            wp_die(
                'O arquivo do sorteio não foi encontrado.'
            );
        }

        /*
        * Segurança:
        * garante que o arquivo resolvido continua
        * dentro do diretório de uploads.
        */
        $base_dir = trailingslashit(
            wp_normalize_path($base_dir)
        );

        $arquivo_normalizado = wp_normalize_path(
            $arquivo
        );

        if (
            strpos(
                $arquivo_normalizado,
                $base_dir
            ) !== 0
        ) {
            wp_die(
                'Caminho de arquivo inválido.'
            );
        }

        /*
        * Nome apresentado ao usuário.
        */
        $nome_arquivo = !empty(
            $sorteio->arquivo_nome
        )
            ? sanitize_file_name(
                $sorteio->arquivo_nome
            )
            : basename($arquivo);

        /*
        * MIME type.
        */
        $tipo_arquivo = wp_check_filetype(
            $nome_arquivo
        );

        $mime_type = !empty(
            $tipo_arquivo['type']
        )
            ? $tipo_arquivo['type']
            : 'application/octet-stream';

        /*
        * Evita conteúdo anterior corrompendo
        * o arquivo enviado ao navegador.
        */
        while (ob_get_level()) {
            ob_end_clean();
        }

        nocache_headers();

        header(
            'Content-Type: ' .
            $mime_type
        );

        header(
            'Content-Disposition: attachment; filename="' .
            $nome_arquivo .
            '"'
        );

        header(
            'Content-Length: ' .
            filesize($arquivo)
        );

        header(
            'X-Content-Type-Options: nosniff'
        );

        readfile(
            $arquivo
        );

        exit;
    }

    public static function excluir_sorteio()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'Você não tem permissão para excluir sorteios.'
            );
        }

        check_admin_referer(
            'sorteios_excluir',
            'sorteios_nonce'
        );

        $sorteio_id = isset($_POST['sorteio_id'])
            ? absint($_POST['sorteio_id'])
            : 0;

        if ($sorteio_id <= 0) {
            wp_die(
                'Sorteio inválido.'
            );
        }

        /*
        * Busca o sorteio antes de iniciar a exclusão.
        *
        * Precisaremos das informações do arquivo
        * depois que o registro for removido.
        */
        $sorteio = self::obter_sorteio(
            $sorteio_id
        );

        if (!$sorteio) {
            wp_die(
                'Sorteio não encontrado.'
            );
        }

        global $wpdb;

        $table_sorteios =
            $wpdb->prefix . 'sorteios';

        $table_dres =
            $wpdb->prefix . 'sorteio_dres';

        $table_unidades =
            $wpdb->prefix . 'sorteio_unidades';

        $table_historico =
            $wpdb->prefix . 'sorteio_historico';

        /*
        * Inicia a transação.
        */
        $wpdb->query(
            'START TRANSACTION'
        );

        try {

            /*
            * Histórico.
            */
            $excluido = $wpdb->delete(
                $table_historico,
                [
                    'sorteio_id' => $sorteio_id,
                ],
                [
                    '%d',
                ]
            );

            if ($excluido === false) {
                throw new Exception(
                    'Não foi possível excluir o histórico do sorteio.'
                );
            }

            /*
            * Unidades.
            */
            $excluido = $wpdb->delete(
                $table_unidades,
                [
                    'sorteio_id' => $sorteio_id,
                ],
                [
                    '%d',
                ]
            );

            if ($excluido === false) {
                throw new Exception(
                    'Não foi possível excluir as unidades do sorteio.'
                );
            }

            /*
            * DREs.
            */
            $excluido = $wpdb->delete(
                $table_dres,
                [
                    'sorteio_id' => $sorteio_id,
                ],
                [
                    '%d',
                ]
            );

            if ($excluido === false) {
                throw new Exception(
                    'Não foi possível excluir as DREs do sorteio.'
                );
            }

            /*
            * Registro principal.
            */
            $excluido = $wpdb->delete(
                $table_sorteios,
                [
                    'id' => $sorteio_id,
                ],
                [
                    '%d',
                ]
            );

            if ($excluido === false) {
                throw new Exception(
                    'Não foi possível excluir o sorteio.'
                );
            }

            /*
            * Como validamos a existência do sorteio antes,
            * esperamos exatamente um registro removido aqui.
            */
            if ($excluido !== 1) {
                throw new Exception(
                    'O sorteio não pôde ser excluído.'
                );
            }

            $wpdb->query(
                'COMMIT'
            );

        } catch (Throwable $e) {

            $wpdb->query(
                'ROLLBACK'
            );

            wp_die(
                esc_html(
                    $e->getMessage()
                )
            );
        }

        /*
        * O banco já foi excluído com sucesso.
        *
        * Agora removemos os arquivos físicos.
        */
        self::excluir_diretorio_sorteio(
            $sorteio_id
        );

        /*
        * Retorna para a listagem.
        */
        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'sorteios',
                    'excluido' => 1,
                ],
                admin_url('admin.php')
            )
        );

        exit;
    }

    private static function excluir_diretorio_sorteio(
        $sorteio_id
    ) {
        $sorteio_id = absint(
            $sorteio_id
        );

        if ($sorteio_id <= 0) {
            return false;
        }

        $upload_dir = wp_upload_dir();

        if (!empty($upload_dir['error'])) {
            return false;
        }

        $base_sorteios = trailingslashit(
            $upload_dir['basedir']
        ) . 'sorteios';

        $diretorio = trailingslashit(
            $base_sorteios
        ) . $sorteio_id;

        /*
        * Se o diretório não existe, não há
        * nada para remover.
        */
        if (!is_dir($diretorio)) {
            return true;
        }

        /*
        * Valida os caminhos reais antes
        * de executar qualquer exclusão.
        */
        $base_real = realpath(
            $base_sorteios
        );

        $diretorio_real = realpath(
            $diretorio
        );

        if (
            !$base_real ||
            !$diretorio_real
        ) {
            return false;
        }

        $base_real = trailingslashit(
            wp_normalize_path(
                $base_real
            )
        );

        $diretorio_real = wp_normalize_path(
            $diretorio_real
        );

        /*
        * O diretório precisa estar dentro
        * de uploads/sorteios/.
        */
        if (
            strpos(
                $diretorio_real,
                $base_real
            ) !== 0
        ) {
            return false;
        }

        /*
        * Neste plugin cada diretório de sorteio
        * contém apenas os arquivos preservados
        * daquele sorteio.
        */
        $arquivos = glob(
            trailingslashit(
                $diretorio_real
            ) . '*'
        );

        if ($arquivos !== false) {

            foreach ($arquivos as $arquivo) {

                if (is_file($arquivo)) {
                    wp_delete_file(
                        $arquivo
                    );
                }
            }
        }

        /*
        * Remove a pasta agora vazia.
        */
        return @rmdir(
            $diretorio_real
        );
    }

    private static function obter_classe_status(
        $status
    ) {
        $classes = [
            'aguardando_confirmacao' => 'aguardando-confirmacao',
            'confirmado' => 'confirmado',
            'lista_espera' => 'lista-espera',
            'desistencia' => 'desistencia',
        ];

        return $classes[$status] ?? 'desconhecido';
    }

}
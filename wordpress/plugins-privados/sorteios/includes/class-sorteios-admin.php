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
        if ($hook !== 'sorteios_page_novo-sorteio') {
            return;
        }

        wp_enqueue_style(
            'sorteios-admin',
            SORTEIOS_PLUGIN_URL . 'assets/css/admin.css',
            [],
            SORTEIOS_VERSION
        );

        wp_enqueue_script(
            'sorteios-admin',
            SORTEIOS_PLUGIN_URL . 'assets/js/admin.js',
            [],
            SORTEIOS_VERSION,
            true
        );
    }

    public static function pagina_sorteios()
    {
        ?>

        <div class="wrap sorteios-wrap">

            <h1>Sorteios</h1>

            <p>
                Gerenciamento de sorteios de unidades escolares.
            </p>

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

            <h1>Novo Sorteio</h1>

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
            Envie a planilha contendo as unidades que poderão
            participar do sorteio.
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

                <div class="notice notice-error inline">

                    <p>
                        A planilha possui erros que precisam
                        ser corrigidos antes de continuar.
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

            <?php if (empty($importacao['erros'])) : ?>

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

        if (!empty($importacao['erros'])) {
            echo '<div class="notice notice-error inline">';
            echo '<p>Corrija os erros da planilha antes de continuar.</p>';
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
        if (empty($_GET['importacao'])) {
            return false;
        }

        $token = sanitize_text_field(
            wp_unslash($_GET['importacao'])
        );

        $key = self::TRANSIENT_PREFIX .
            get_current_user_id() .
            '_' .
            $token;

        $resultado = get_transient($key);

        if ($resultado === false) {
            return false;
        }

        return $resultado;
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

        if (!empty($importacao['erros'])) {
            echo '<div class="notice notice-error inline">';
            echo '<p>Corrija os erros da planilha antes de continuar.</p>';
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
        $quantidades = [];

        foreach (
            $importacao['erros_detalhados']
            as $erro
        ) {

            if (
                !in_array(
                    $erro['tipo'],
                    [
                        'linha_vazia',
                        'cie_duplicado',
                    ],
                    true
                )
            ) {
                continue;
            }

            if (!isset($quantidades[$erro['tipo']])) {
                $quantidades[$erro['tipo']] = 0;
            }

            $quantidades[$erro['tipo']]++;
        }

        if (empty($quantidades)) {
            return '';
        }

        $partes = [];

        if (!empty($quantidades['linha_vazia'])) {

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

        if (!empty($quantidades['cie_duplicado'])) {

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

        if (empty($partes)) {
            return '';
        }

        return sprintf(
            '%d linha%s ignorada%s. %s.',
            array_sum($quantidades),
            array_sum($quantidades) === 1
                ? ''
                : 's',
            array_sum($quantidades) === 1
                ? ''
                : 's',
            implode(' e ', $partes)
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

        if (!empty($importacao['erros'])) {
            echo '<div class="notice notice-error inline">';
            echo '<p>Corrija os erros da planilha antes de continuar.</p>';
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
                'Você não possui permissão para realizar esta ação.'
            );
        }

        if (
            empty($_POST['sorteios_nonce']) ||
            !wp_verify_nonce(
                $_POST['sorteios_nonce'],
                'sorteios_realizar'
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

        $configuracao = isset($importacao['configuracao'])
            && is_array($importacao['configuracao'])
            ? $importacao['configuracao']
            : [];

        $dres = isset($configuracao['dres'])
            && is_array($configuracao['dres'])
            ? $configuracao['dres']
            : [];

        $tipo_horta = isset($configuracao['tipo_horta'])
            ? $configuracao['tipo_horta']
            : '';

        if (empty($dres)) {
            self::redirecionar_etapa_com_erro(
                2,
                $token,
                'Nenhuma DRE foi selecionada.'
            );
        }

        if ($tipo_horta === '') {
            self::redirecionar_etapa_com_erro(
                3,
                $token,
                'Nenhum tipo de horta foi selecionado.'
            );
        }

        $quantidade = isset($_POST['quantidade'])
            ? absint($_POST['quantidade'])
            : 0;

        if ($quantidade < 1) {
            self::redirecionar_etapa_com_erro(
                4,
                $token,
                'Informe uma quantidade válida de unidades.'
            );
        }

        $dados = isset($importacao['dados'])
            && is_array($importacao['dados'])
            ? $importacao['dados']
            : [];

        $unidades_por_dre = [];

        foreach ($dres as $dre) {
            $unidades_por_dre[$dre] = [];
        }

        foreach ($dados as $registro) {
            if (
                !isset($registro['dre']) ||
                !isset($registro['horta'])
            ) {
                continue;
            }

            if (
                !in_array(
                    $registro['dre'],
                    $dres,
                    true
                )
            ) {
                continue;
            }

            if ($registro['horta'] !== $tipo_horta) {
                continue;
            }

            $unidades_por_dre[$registro['dre']][] = $registro;
        }        

        global $wpdb;

        $tabela_sorteios = $wpdb->prefix . 'sorteios';
        $tabela_dres = $wpdb->prefix . 'sorteio_dres';
        $tabela_unidades = $wpdb->prefix . 'sorteio_unidades';
        $tabela_historico = $wpdb->prefix . 'sorteio_historico';

        $agora = current_time('mysql');
        $usuario_id = get_current_user_id();

        $nome_sorteio = sprintf(
            'Sorteio de unidades - %s',
            wp_date(
                'd/m/Y H:i',
                current_time('timestamp')
            )
        );

        try {
            $wpdb->query('START TRANSACTION');

            /*
            * Cria o sorteio.
            */
            $inserido = $wpdb->insert(
                $tabela_sorteios,
                [
                    'nome' => $nome_sorteio,
                    'arquivo_nome' => isset($importacao['arquivo_nome'])
                        ? $importacao['arquivo_nome']
                        : null,
                    'usuario_criacao' => $usuario_id,
                    'data_criacao' => $agora,
                    'usuario_sorteio' => $usuario_id,
                    'data_sorteio' => $agora,
                    'status' => 'realizado',
                ],
                [
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                ]
            );

            if ($inserido === false) {
                throw new Exception(
                    'Não foi possível criar o sorteio.'
                );
            }

            $sorteio_id = (int) $wpdb->insert_id;

            /*
            * Salva as DREs selecionadas.
            */
            foreach ($dres as $dre) {
                $total_unidades = count(
                    $unidades_por_dre[$dre]
                );

                $inserido = $wpdb->insert(
                    $tabela_dres,
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

                if ($inserido === false) {
                    throw new Exception(
                        'Não foi possível salvar as DREs do sorteio.'
                    );
                }
            }

            /*
            * Para cada DRE:
            *
            * 1. Embaralha as unidades.
            * 2. Define uma ordem imutável.
            * 3. As primeiras N são selecionadas.
            * 4. As demais ficam como reserva.
            */
            foreach ($unidades_por_dre as $dre => $unidades) {
                shuffle($unidades);

                foreach ($unidades as $indice => $registro) {
                    $ordem = $indice + 1;

                    $tipo_resultado = $ordem <= $quantidade
                        ? 'selecionada'
                        : 'reserva';

                    $status = $ordem <= $quantidade
                        ? 'pendente'
                        : 'aguardando';

                    $inserido = $wpdb->insert(
                        $tabela_unidades,
                        [
                            'sorteio_id' => $sorteio_id,
                            'dre' => $registro['dre'],
                            'cie' => $registro['cie'],
                            'nome_unidade' => $registro['nome_unidade'],
                            'horta' => $registro['horta'],
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

                    if ($inserido === false) {
                        throw new Exception(
                            'Não foi possível salvar as unidades do sorteio.'
                        );
                    }

                    $unidade_id = (int) $wpdb->insert_id;

                    $wpdb->insert(
                        $tabela_historico,
                        [
                            'sorteio_id' => $sorteio_id,
                            'unidade_id' => $unidade_id,
                            'usuario_id' => $usuario_id,
                            'acao' => 'sorteio_realizado',
                            'campo' => null,
                            'valor_anterior' => null,
                            'valor_novo' => $tipo_resultado,
                            'observacao' => sprintf(
                                'Unidade recebeu a posição %d no sorteio da DRE %s.',
                                $ordem,
                                $dre
                            ),
                            'created_at' => $agora,
                        ],
                        [
                            '%d',
                            '%d',
                            '%d',
                            '%s',
                            null,
                            null,
                            '%s',
                            '%s',
                            '%s',
                        ]
                    );

                    if ($wpdb->last_error) {
                        throw new Exception(
                            'Não foi possível registrar o histórico do sorteio.'
                        );
                    }
                }
            }

            /*
            * Registra a criação do sorteio no histórico geral.
            */
            $wpdb->insert(
                $tabela_historico,
                [
                    'sorteio_id' => $sorteio_id,
                    'unidade_id' => null,
                    'usuario_id' => $usuario_id,
                    'acao' => 'sorteio_realizado',
                    'campo' => 'quantidade',
                    'valor_anterior' => null,
                    'valor_novo' => (string) $quantidade,
                    'observacao' => sprintf(
                        'Sorteio realizado para %d DRE%s.',
                        count($dres),
                        count($dres) === 1 ? '' : 's'
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

            if ($wpdb->last_error) {
                throw new Exception(
                    'Não foi possível registrar o histórico do sorteio.'
                );
            }

            $wpdb->query('COMMIT');

        } catch (Throwable $e) {

            $wpdb->query('ROLLBACK');

            self::redirecionar_etapa_com_erro(
                4,
                $token,
                $e->getMessage()
            );
        }

        /*
        * O sorteio já foi efetivamente realizado.
        * Não precisamos mais manter a configuração temporária.
        */
        delete_transient($transient_key);

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
    }
}
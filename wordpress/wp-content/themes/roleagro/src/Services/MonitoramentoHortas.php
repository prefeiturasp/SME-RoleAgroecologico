<?php
/**
 * MONITORAMENTO DE HORTAS
 * CONFIGURAÇÕES
 */
define('MH_POST_TYPE', 'levantamento_horta');
define('MH_MENU_SLUG', 'monitoramento-hortas');
define('MH_CAPABILITY', 'manage_options');
/**
 * 1. CRIAR CUSTOM POST TYPE
 */
add_action('init', 'mh_register_post_type');
function mh_register_post_type(){
    register_post_type(MH_POST_TYPE, [
        'labels' => [
            'name' => 'Levantamentos',
            'singular_name' => 'Levantamento',
        ],
        'public' => false,
        'show_ui' => false,
        'show_in_menu' => false,
        'show_in_admin_bar' => false,
        'supports' => [
            'title',
            'author',
        ],
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);
}
/**
 * 2. MENU ADMINISTRATIVO
 */
add_action('admin_menu', 'mh_register_admin_menu');
function mh_register_admin_menu(){
    add_menu_page(
        'Monitoramento de Hortas',
        'Monitoramento de Hortas',
        MH_CAPABILITY,
        MH_MENU_SLUG,
        'mh_render_dashboard',
        'dashicons-carrot',
        25
    );
}
/**
 * 3. OBTER PASTA DE UPLOAD
 */
function mh_get_upload_directory(){
    $upload_dir = wp_upload_dir();
    $directory = trailingslashit($upload_dir['basedir']) . 'monitoramento-hortas';
    $url = trailingslashit($upload_dir['baseurl']) . 'monitoramento-hortas';
    /**
     * Criar pasta
     */
    if (!is_dir($directory)) {
        wp_mkdir_p($directory);
    }
    /**
     * Criar index.php para evitar listagem simples
     */
    $index_file = trailingslashit($directory) . 'index.php';
    if (!file_exists($index_file)) {
        file_put_contents(
            $index_file,
            "<?php\n// Silence is golden.\n"
        );
    }
    return [
        'dir' => $directory,
        'url' => $url,
    ];
}
function mh_get_next_version(){
    $upload = mh_get_upload_directory();
    $directory = $upload['dir'];
    if (!is_dir($directory)) {
        return 1;
    }
    $arquivos = glob(trailingslashit($directory) . 'levantamento-*');
    if (empty($arquivos)) {
        return 1;
    }
    $maior_versao = 0;
    foreach ($arquivos as $arquivo) {
        $nome = basename($arquivo);
        if (preg_match('/^levantamento-(\d+)-/', $nome, $matches)) {
            $versao = (int) $matches[1];
            if ($versao > $maior_versao) {
                $maior_versao = $versao;
            }
        }
    }
    return $maior_versao + 1;
}
/**
 * 5. REDIRECIONAR COM ERRO
 */
function mh_redirect_error($message){
    wp_safe_redirect(
        add_query_arg(
            [
                'page' => MH_MENU_SLUG,
                'mh_erro' => rawurlencode($message),
            ],
            admin_url('admin.php')
        )
    );
    exit;
}
/**
 * 6. REDIRECIONAR COM SUCESSO
 */
function mh_redirect_success($post_id){
    wp_safe_redirect(
        add_query_arg(
            [
                'page' => MH_MENU_SLUG,
                'mh_importado' => 1,
                'levantamento' => $post_id,
            ],
            admin_url('admin.php')
        )
    );
    exit;
}
/**
 * 7. PROCESSAR UPLOAD
 */
add_action('admin_post_mh_importar_planilha', 'mh_importar_planilha');
function mh_importar_planilha(){
    if (!current_user_can(MH_CAPABILITY)) {
        wp_die('Você não possui permissão para realizar esta operação.');
    }
    /**
     * Nonce
     */
    if (!isset($_POST['mh_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mh_nonce'])), 'mh_importar_planilha')) {
        wp_die('Falha na validação de segurança.');
    }
    /**
     * Usuário logado
     */
    $usuario = wp_get_current_user();
    if (!$usuario || !$usuario->ID) {
        mh_redirect_error('Não foi possível identificar o usuário logado.');
    }
    $usuario_id = (int) $usuario->ID;
    $responsavel = $usuario->display_name;
    /**
     * Data e hora
     */
    $timestamp_envio = current_time('timestamp');
    $data_hora_envio = wp_date('Y-m-d H:i:s', $timestamp_envio);
    $data_levantamento = wp_date('Y-m-d', $timestamp_envio);
    /**
     * Verificar arquivo
     */
    if (!isset($_FILES['mh_planilha']) || empty($_FILES['mh_planilha']['name'])) {
        mh_redirect_error('Nenhuma planilha foi selecionada.');
    }
    $arquivo = $_FILES['mh_planilha'];
    /**
     * Erro de upload
     */
    if (!isset($arquivo['error']) || $arquivo['error'] !== UPLOAD_ERR_OK) {
        mh_redirect_error('Ocorreu um erro durante o upload da planilha.');
    }
    /**
     * --------------------------------------------------------
     * Tamanho máximo
     * --------------------------------------------------------
     */
    $limite = 5 * 1024 * 1024;
    if ((int) $arquivo['size'] > $limite) {
        mh_redirect_error('A planilha não pode ultrapassar 5 MB.');
    }
    /**
     * --------------------------------------------------------
     * Nome original
     * --------------------------------------------------------
     */
    $nome_original = sanitize_file_name($arquivo['name']);
    /**
     * --------------------------------------------------------
     * Extensão
     * --------------------------------------------------------
     */
    $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
    $extensoes_permitidas = ['xlsx', 'xls', 'csv'];
    if (!in_array($extensao, $extensoes_permitidas, true)) {
        mh_redirect_error('Formato não permitido. Envie uma planilha XLSX, XLS ou CSV.');
    }
    /**
     * --------------------------------------------------------
     * Verificar upload real
     * --------------------------------------------------------
     */
    if (!is_uploaded_file($arquivo['tmp_name'])) {
        mh_redirect_error('O arquivo enviado não é válido.');
    }
    /**
     * --------------------------------------------------------
     * Obter pasta
     * --------------------------------------------------------
     */
    $upload = mh_get_upload_directory();
    if (!is_dir($upload['dir']) || !is_writable($upload['dir'])) {
        mh_redirect_error('A pasta de armazenamento não existe ou não possui permissão de escrita.');
    }
    /**
     * --------------------------------------------------------
     * Próxima versão
     * --------------------------------------------------------
     */
    $versao = mh_get_next_version();
    /**
     * --------------------------------------------------------
     * Nome físico do arquivo
     * --------------------------------------------------------
     */
    $nome_arquivo = sprintf('levantamento-%03d-%s.%s', $versao, wp_date('Y-m-d-H-i-s', $timestamp_envio), $extensao);
    $nome_arquivo = sanitize_file_name($nome_arquivo);
    /**
     * Caminho físico
     */
    $caminho_arquivo = trailingslashit($upload['dir']) . $nome_arquivo;
    /**
     * --------------------------------------------------------
     * Mover arquivo
     * --------------------------------------------------------
     */
    if (!move_uploaded_file($arquivo['tmp_name'], $caminho_arquivo)) {
        mh_redirect_error('Não foi possível armazenar a planilha.');
    }
    $url_arquivo = trailingslashit($upload['url']) . $nome_arquivo;
    /**
     * --------------------------------------------------------
     * Criar registro
     * --------------------------------------------------------
     */
    $titulo = sprintf('Levantamento #%03d - %s', $versao, wp_date('d/m/Y H:i:s', $timestamp_envio));
    $post_id = wp_insert_post([
        'post_type' => MH_POST_TYPE,
        'post_status' => 'publish',
        'post_title' => $titulo,
        'post_author' => $usuario_id,
    ], true);
    /**
     * --------------------------------------------------------
     * Se houver erro, apagar arquivo
     * --------------------------------------------------------
     */
    if (is_wp_error($post_id)) {
        if (file_exists($caminho_arquivo)) {
            unlink($caminho_arquivo);
        }
        mh_redirect_error('Não foi possível criar o registro do levantamento.');
    }
    /**
     * ========================================================
     * SALVAR DADOS
     * ========================================================
     */
    update_post_meta($post_id, 'usuario_responsavel', $usuario_id);
    update_post_meta($post_id, 'responsavel_levantamento', $responsavel);
    update_post_meta($post_id, 'data_levantamento', $data_levantamento);
    update_post_meta($post_id, 'data_hora_envio', $data_hora_envio);
    update_post_meta($post_id, '_mh_timestamp_envio', $timestamp_envio);
    update_post_meta($post_id, 'numero_versao', $versao);
    update_post_meta($post_id, 'nome_arquivo_original', $nome_original);
    update_post_meta($post_id, 'nome_arquivo', $nome_arquivo);
    update_post_meta($post_id, 'extensao_arquivo', $extensao);
    update_post_meta($post_id, 'caminho_arquivo', $caminho_arquivo);
    update_post_meta($post_id, 'url_arquivo', $url_arquivo);
    /**
     * ========================================================
     * ACF
     * ========================================================
     *
     * Caso os campos existam no ACF, também serão preenchidos.
     *
     * ========================================================
     */
    if (function_exists('update_field')) {
        update_field('usuario_responsavel', $usuario_id, $post_id);
        update_field(
            'responsavel_levantamento',
            $responsavel,
            $post_id
        );
        update_field(
            'data_levantamento',
            $data_levantamento,
            $post_id
        );
        update_field(
            'data_hora_envio',
            $data_hora_envio,
            $post_id
        );
        update_field(
            'numero_versao',
            $versao,
            $post_id
        );
        update_field(
            'nome_arquivo_original',
            $nome_original,
            $post_id
        );
        update_field(
            'nome_arquivo',
            $nome_arquivo,
            $post_id
        );
        update_field(
            'extensao_arquivo',
            $extensao,
            $post_id
        );
        update_field(
            'caminho_arquivo',
            $caminho_arquivo,
            $post_id
        );
        update_field(
            'url_arquivo',
            $url_arquivo,
            $post_id
        );
    }
    /**
     * ========================================================
     * MARCAR COMO ATUAL
     * ========================================================
     */
    update_post_meta(
        $post_id,
        '_mh_atual',
        1
    );
    /**
     * --------------------------------------------------------
     * Retirar "Atual" dos demais
     * --------------------------------------------------------
     */
    $anteriores = get_posts([
        'post_type' => MH_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'post__not_in' => [$post_id],
        'fields' => 'ids',
    ]);
    foreach ($anteriores as $anterior_id) {
        delete_post_meta($anterior_id, '_mh_atual');
    }
    /**
     * --------------------------------------------------------
     * Finalizar
     * --------------------------------------------------------
     */
    mh_redirect_success($post_id);
}
/**
 * 8. DOWNLOAD PROTEGIDO
 */
add_action('admin_post_mh_download_planilha', 'mh_download_planilha');
function mh_download_planilha(){
    /**
     * --------------------------------------------------------
     * Permissão
     * --------------------------------------------------------
     */
    if (!current_user_can(MH_CAPABILITY)) {
        wp_die('Você não possui permissão para baixar este arquivo.');
    }
    /**
     * --------------------------------------------------------
     * ID
     * --------------------------------------------------------
     */
    $post_id = isset($_GET['levantamento']) ? absint($_GET['levantamento']) : 0;
    if (!$post_id) {
        wp_die('Levantamento inválido.');
    }
    /**
     * --------------------------------------------------------
     * Nonce
     * --------------------------------------------------------
     */
    if (
        !isset($_GET['_wpnonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(
                wp_unslash($_GET['_wpnonce'])
            ),
            'mh_download_' . $post_id
        )
    ) {
        wp_die('Falha na validação de segurança.');
    }
    /**
     * --------------------------------------------------------
     * Verificar levantamento
     * --------------------------------------------------------
     */
    $post = get_post($post_id);
    if (!$post || $post->post_type !== MH_POST_TYPE) {
        wp_die('Levantamento não encontrado.');
    }
    /**
     * --------------------------------------------------------
     * Caminho
     * --------------------------------------------------------
     */
    $arquivo = get_post_meta($post_id, 'caminho_arquivo', true);
    if (!$arquivo || !file_exists($arquivo)) {
        wp_die('O arquivo da planilha não foi encontrado.');
    }
    /**
     * --------------------------------------------------------
     * Nome para download
     * --------------------------------------------------------
     */
    $nome = get_post_meta($post_id, 'nome_arquivo_original', true);
    if (!$nome) {
        $nome = basename($arquivo);
    }
    /**
     * --------------------------------------------------------
     * Limpar buffers
     * --------------------------------------------------------
     */
    while (ob_get_level()) {
        ob_end_clean();
    }
    /**
     * --------------------------------------------------------
     * Headers
     * --------------------------------------------------------
     */
    $mime = 'application/octet-stream';
    switch (strtolower(pathinfo($arquivo, PATHINFO_EXTENSION))) {
        case 'xlsx':
            $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            break;
        case 'xls':
            $mime = 'application/vnd.ms-excel';
            break;
        case 'csv':
            $mime = 'text/csv';
            break;
    }
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . sanitize_file_name($nome) . '"');
    header('Content-Length: ' . filesize($arquivo));
    header('Cache-Control: private, no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    /**
     * --------------------------------------------------------
     * Enviar arquivo
     * --------------------------------------------------------
     */
    readfile($arquivo);
    exit;
}
/**
 * 9. EXCLUIR LEVANTAMENTO
 */
add_action('admin_post_mh_excluir_levantamento', 'mh_excluir_levantamento');
function mh_excluir_levantamento(){
    /**
     * --------------------------------------------------------
     * Permissão
     * --------------------------------------------------------
     */
    if (!current_user_can(MH_CAPABILITY)) {
        wp_die('Você não possui permissão para excluir levantamentos.');
    }
    /**
     * --------------------------------------------------------
     * ID
     * --------------------------------------------------------
     */
    $post_id = isset($_GET['levantamento']) ? absint($_GET['levantamento']) : 0;
    if (!$post_id) {
        mh_redirect_error('Levantamento inválido.');
    }
    /**
     * --------------------------------------------------------
     * Nonce
     * --------------------------------------------------------
     */
    if (
        !isset($_GET['_wpnonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(
                wp_unslash($_GET['_wpnonce'])
            ),
            'mh_excluir_' . $post_id
        )
    ) {
        wp_die('Falha na validação de segurança.');
    }
    /**
     * --------------------------------------------------------
     * Verificar post
     * --------------------------------------------------------
     */
    $post = get_post($post_id);
    if (!$post || $post->post_type !== MH_POST_TYPE) {
        mh_redirect_error('Levantamento não encontrado.');
    }
    /**
     * --------------------------------------------------------
     * Verificar se é atual
     * --------------------------------------------------------
     */
    $era_atual = get_post_meta($post_id, '_mh_atual', true);
    /**
     * --------------------------------------------------------
     * Caminho do arquivo
     * --------------------------------------------------------
     */
    $arquivo = get_post_meta($post_id, 'caminho_arquivo', true);
    /**
     * --------------------------------------------------------
     * Excluir arquivo físico
     * --------------------------------------------------------
     */
    if ($arquivo && file_exists($arquivo)) {
        unlink($arquivo);
    }
    /**
     * --------------------------------------------------------
     * Excluir registro
     * --------------------------------------------------------
     */
    $resultado = wp_delete_post($post_id, true);
    if (!$resultado) {
        mh_redirect_error('Não foi possível excluir o levantamento.');
    }
    /**
     * ========================================================
     * Se era o atual, selecionar o anterior
     * ========================================================
     */
    if ($era_atual) {
        $novo_atual = get_posts([
            'post_type' => MH_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_key' => 'numero_versao',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'fields' => 'ids',
        ]);
        if (!empty($novo_atual)) {
            update_post_meta($novo_atual[0], '_mh_atual', 1);
        }
    }
    /**
     * --------------------------------------------------------
     * Redirecionar
     * --------------------------------------------------------
     */
    wp_safe_redirect(
        add_query_arg(
            [
                'page' => MH_MENU_SLUG,
                'mh_excluido' => 1,
            ],
            admin_url('admin.php')
        )
    );
    exit;
}
/**
 * 10. DASHBOARD
 */
function mh_render_dashboard(){
    /**
     * --------------------------------------------------------
     * Permissão
     * --------------------------------------------------------
     */
    if (!current_user_can(MH_CAPABILITY)) {
        wp_die(
            'Você não possui permissão para acessar esta área.'
        );
    }
    /**
     * --------------------------------------------------------
     * Usuário
     * --------------------------------------------------------
     */
    $usuario = wp_get_current_user();
    /**
     * --------------------------------------------------------
     * Mensagens
     * --------------------------------------------------------
     */
    ?>
    <div class="wrap">
        <h1>
            Monitoramento de Hortas
        </h1>
        <?php
        if (
            isset($_GET['mh_importado'])
        ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>
                        Levantamento enviado com sucesso!
                    </strong>
                </p>
            </div>
            <?php
        }
        if (
            isset($_GET['mh_excluido'])
        ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>
                        Levantamento excluído com sucesso!
                    </strong>
                </p>
            </div>
            <?php
        }
        if (
            isset($_GET['mh_erro'])
        ) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong>
                        Erro:
                    </strong>
                    <?php
                    echo esc_html(
                        urldecode(
                            wp_unslash(
                                $_GET['mh_erro']
                            )
                        )
                    );
                    ?>
                </p>
            </div>
            <?php
        }
        ?>
        <!-- ==================================================
             FORMULÁRIO
        =================================================== -->
        <div class="card" style="max-width:none;padding:25px;margin-top:20px;">
            <h2 style="margin-top:0;">Novo levantamento</h2>
            <p>
                Envie a planilha atualizada do monitoramento.
                O usuário, data e hora serão registrados
                automaticamente.
            </p>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('mh_importar_planilha', 'mh_nonce'); ?>
                <input type="hidden" name="action" value="mh_importar_planilha">
                <table class="form-table">
                    <!-- ==================================================
                        UPLOAD DA PLANILHA
                    =================================================== -->
                    <tr>
                        <td>
                            <div
                                id="mh-upload-area"
                                class="mh-upload-area"
                            >
                                <!-- ÍCONE -->
                                <div class="mh-upload-icon">
                                    <span class="dashicons dashicons-media-spreadsheet"></span>
                                </div>
                                <!-- TEXTO -->
                                <div class="mh-upload-content">
                                    <h3>
                                        Selecione a planilha do levantamento
                                    </h3>
                                    <p>
                                        Arraste o arquivo para esta área
                                        ou clique para selecionar
                                    </p>
                                    <label
                                        for="mh_planilha"
                                        class="button button-primary mh-upload-button"
                                    >
                                        <span class="dashicons dashicons-upload"></span>
                                        Selecionar planilha
                                    </label>
                                    <input
                                        type="file"
                                        name="mh_planilha"
                                        id="mh_planilha"
                                        accept=".xlsx,.xls,.csv"
                                        required
                                        style="display:none;"
                                    >
                                    <!-- ARQUIVO SELECIONADO -->
                                    <div
                                        id="mh-file-selected"
                                        class="mh-file-selected"
                                        style="display:none;"
                                    >
                                        <span class="dashicons dashicons-media-spreadsheet"></span>
                                        <span id="mh-file-name"></span>
                                        <button
                                            type="button"
                                            id="mh-remove-file"
                                            class="mh-remove-file"
                                            title="Remover arquivo"
                                        >
                                            <span class="dashicons dashicons-no-alt"></span>
                                        </button>
                                    </div>
                                    <div class="mh-upload-info">
                                        <span>
                                            <strong>Formatos:</strong>
                                            XLSX, XLS ou CSV
                                        </span>
                                        <span>
                                            <strong>Tamanho máximo:</strong>
                                            20 MB
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
                <?php
                submit_button(
                    'Enviar levantamento',
                    'primary',
                    'submit',
                    true,
                    [
                        'id' => 'mh-submit',
                    ]
                );
                ?>
            </form>
        </div>
        <!-- ==================================================
             HISTÓRICO
        =================================================== -->
        <div
            class="card"
            style="
                max-width:none;
                padding:25px;
                margin-top:25px;
            "
        >
            <h2 style="margin-top:0;">
                Histórico de levantamentos
            </h2>
            <?php
            /**
             * Buscar levantamentos
             */
            $levantamentos = new WP_Query(
                [
                    'post_type'      => MH_POST_TYPE,
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'meta_key' =>
                        'numero_versao',
                    'orderby' =>
                        'meta_value_num',
                    'order' =>
                        'DESC',
                ]
            );
            if (
                $levantamentos->have_posts()
            ) {
                ?>
                <div style="
                    overflow-x:auto;
                ">
                    <table
                        class="wp-list-table widefat fixed striped"
                    >
                        <thead>
                            <tr>
                                <th>
                                    Versão
                                </th>
                                <th>
                                    Data / Hora
                                </th>
                                <th>
                                    Responsável
                                </th>
                                <th>
                                    Arquivo
                                </th>
                                <th>
                                    Status
                                </th>
                                <th>
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        while (
                            $levantamentos->have_posts()
                        ) {
                            $levantamentos->the_post();
                            $post_id = get_the_ID();
                            /**
                             * Dados
                             */
                            $versao = get_post_meta(
                                $post_id,
                                'numero_versao',
                                true
                            );
                            $data_hora = get_post_meta(
                                $post_id,
                                'data_hora_envio',
                                true
                            );
                            $responsavel = get_post_meta(
                                $post_id,
                                'responsavel_levantamento',
                                true
                            );
                            $nome_arquivo = get_post_meta(
                                $post_id,
                                'nome_arquivo_original',
                                true
                            );
                            $atual = get_post_meta(
                                $post_id,
                                '_mh_atual',
                                true
                            );
                            /**
                             * URL de download protegida
                             */
                            $download_url = wp_nonce_url(
                                add_query_arg(
                                    [
                                        'action' =>
                                            'mh_download_planilha',
                                        'levantamento' =>
                                            $post_id,
                                    ],
                                    admin_url('admin-post.php')
                                ),
                                'mh_download_' . $post_id
                            );
                            /**
                             * URL exclusão
                             */
                            $delete_url = wp_nonce_url(
                                add_query_arg(
                                    [
                                        'action' =>
                                            'mh_excluir_levantamento',
                                        'levantamento' =>
                                            $post_id,
                                    ],
                                    admin_url('admin-post.php')
                                ),
                                'mh_excluir_' . $post_id
                            );
                            ?>
                            <tr>
                                <!-- VERSÃO -->
                                <td>
                                    <strong>
                                        <?php
                                        echo esc_html(
                                            sprintf(
                                                '#%03d',
                                                (int) $versao
                                            )
                                        );
                                        ?>
                                    </strong>
                                </td>
                                <!-- DATA -->
                                <td>
                                    <?php
                                    if ($data_hora) {
                                        $timestamp =
                                            strtotime(
                                                $data_hora
                                            );
                                        if ($timestamp) {
                                            echo esc_html(
                                                wp_date(
                                                    'd/m/Y H:i:s',
                                                    $timestamp
                                                )
                                            );
                                        } else {
                                            echo esc_html(
                                                $data_hora
                                            );
                                        }
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <!-- RESPONSÁVEL -->
                                <td>
                                    <?php
                                    echo esc_html(
                                        $responsavel ?: '—'
                                    );
                                    ?>
                                </td>
                                <!-- ARQUIVO -->
                                <td>
                                    <a
                                        href="<?php echo esc_url(
                                            $download_url
                                        ); ?>"
                                        class="button"
                                    >
                                        <span
                                            class="dashicons dashicons-download"
                                            style="
                                                margin-top:3px;
                                            "
                                        ></span>
                                        Baixar
                                    </a>
                                </td>
                                <!-- STATUS -->
                                <td>
                                    <?php
                                    if ($atual) {
                                        ?>
                                        <span
                                            style="
                                                display:inline-block;
                                                background:#00a32a;
                                                color:#fff;
                                                padding:5px 10px;
                                                border-radius:4px;
                                                font-weight:600;
                                            "
                                        >
                                            Atual
                                        </span>
                                        <?php
                                    } else {
                                        ?>
                                        <span
                                            style="
                                                display:inline-block;
                                                background:#646970;
                                                color:#fff;
                                                padding:5px 10px;
                                                border-radius:4px;
                                            "
                                        >
                                            Histórico
                                        </span>
                                        <?php
                                    }
                                    ?>
                                </td>
                                <!-- AÇÕES -->
                                <td>
                                    <a
                                        href="<?php echo esc_url(
                                            $delete_url
                                        ); ?>"
                                        class="button button-link-delete"
                                        onclick="return confirm(
                                            'Tem certeza que deseja excluir este levantamento?\\n\\nO arquivo também será excluído permanentemente.'
                                        );"
                                    >
                                        <span
                                            class="dashicons dashicons-trash"
                                            style="
                                                margin-top:3px;
                                            "
                                        ></span>
                                        Excluir
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <?php
            } else {
                ?>
                <div
                    style="
                        text-align:center;
                        padding:40px 20px;
                        background:#f6f7f7;
                        border:1px dashed #c3c4c7;
                    "
                >
                    <span
                        class="dashicons dashicons-clipboard"
                        style="
                            font-size:40px;
                            width:40px;
                            height:40px;
                        "
                    ></span>
                    <h3>
                        Nenhum levantamento cadastrado
                    </h3>
                    <p>
                        Envie a primeira planilha utilizando
                        o formulário acima.
                    </p>
                </div>
                <?php
            }
            wp_reset_postdata();
            ?>
        </div>
    </div>
    <style>
        /* ============================================================
        ÁREA DE UPLOAD
        ============================================================ */
        .mh-upload-area {
            position: relative;
            display: flex;
            align-items: center;
            gap: 25px;
            max-width: 850px;
            min-height: 190px;
            padding: 30px 35px;
            border: 2px dashed #c3c4c7;
            border-radius: 12px;
            background: #f8f9fa;
            transition:
                border-color .2s ease,
                background .2s ease,
                box-shadow .2s ease;
        }
        /* Hover */
        .mh-upload-area:hover {
            border-color: #2271b1;
            background: #f6fbff;
        }
        /* Drag over */
        .mh-upload-area.mh-drag-over {
            border-color: #2271b1;
            background: #eef7ff;
            box-shadow:
                0 0 0 4px rgba(34,113,177,.08);
        }
        /* ============================================================
        ÍCONE
        ============================================================ */
        .mh-upload-icon {
            width: 80px;
            height: 80px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: #e8f1f8;
            color: #2271b1;
        }
        .mh-upload-icon .dashicons {
            width: 48px;
            height: 48px;
            font-size: 48px;
        }
        /* ============================================================
        CONTEÚDO
        ============================================================ */
        .mh-upload-content {
            flex: 1;
        }
        .mh-upload-content h3 {
            margin: 0 0 8px;
            font-size: 18px;
            color: #1d2327;
        }
        .mh-upload-content p {
            margin: 0 0 18px;
            color: #646970;
            font-size: 14px;
        }
        /* ============================================================
        BOTÃO
        ============================================================ */
        .mh-upload-button {
            display: inline-flex !important;
            align-items: center;
            gap: 6px;
        }
        .mh-upload-button .dashicons {
            margin-top: 2px;
        }
        /* ============================================================
        INFORMAÇÕES
        ============================================================ */
        .mh-upload-info {
            display: flex;
            gap: 25px;
            margin-top: 15px;
            color: #646970;
            font-size: 12px;
        }
        .mh-upload-info strong {
            color: #50575e;
        }
        /* ============================================================
        ARQUIVO SELECIONADO
        ============================================================ */
        .mh-file-selected {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
            padding: 10px 12px;
            max-width: 500px;
            border: 1px solid #c3c4c7;
            border-radius: 6px;
            background: #fff;
            color: #1d2327;
        }
        .mh-file-selected > .dashicons {
            color: #2271b1;
        }
        #mh-file-name {
            flex: 1;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            font-weight: 600;
        }
        /* ============================================================
        BOTÃO REMOVER
        ============================================================ */
        .mh-remove-file {
            border: 0;
            background: transparent;
            cursor: pointer;
            color: #646970;
            padding: 2px;
        }
        .mh-remove-file:hover {
            color: #d63638;
        }
        /* ============================================================
        RESPONSIVO
        ============================================================ */
        @media (max-width: 700px) {
            .mh-upload-area {
                flex-direction: column;
                text-align: center;
                padding: 25px;
            }
            .mh-upload-info {
                flex-direction: column;
                gap: 5px;
            }
            .mh-file-selected {
                text-align: left;
            }
        }
        </style>
        <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                const uploadArea =
                    document.getElementById(
                        'mh-upload-area'
                    );
                const input =
                    document.getElementById(
                        'mh_planilha'
                    );
                const fileSelected =
                    document.getElementById(
                        'mh-file-selected'
                    );
                const fileName =
                    document.getElementById(
                        'mh-file-name'
                    );
                const removeButton =
                    document.getElementById(
                        'mh-remove-file'
                    );
                if (
                    !uploadArea ||
                    !input
                ) {
                    return;
                }
                /**
                 * ====================================================
                 * MOSTRAR ARQUIVO
                 * ====================================================
                 */
                function showFile(file)
                {
                    if (!file) {
                        return;
                    }
                    fileName.textContent =
                        file.name;
                    fileSelected.style.display =
                        'flex';
                }
                /**
                 * ====================================================
                 * SELECIONAR ARQUIVO
                 * ====================================================
                 */
                input.addEventListener(
                    'change',
                    function () {
                        if (
                            this.files &&
                            this.files.length > 0
                        ) {
                            showFile(
                                this.files[0]
                            );
                        }
                    }
                );
                /**
                 * ====================================================
                 * REMOVER ARQUIVO
                 * ====================================================
                 */
                removeButton.addEventListener(
                    'click',
                    function () {
                        input.value = '';
                        fileSelected.style.display =
                            'none';
                    }
                );
                /**
                 * ====================================================
                 * DRAG ENTER
                 * ====================================================
                 */
                [
                    'dragenter',
                    'dragover'
                ].forEach(
                    function (eventName) {
                        uploadArea.addEventListener(
                            eventName,
                            function (event) {
                                event.preventDefault();
                                event.stopPropagation();
                                uploadArea.classList.add(
                                    'mh-drag-over'
                                );
                            }
                        );
                    }
                );
                /**
                 * ====================================================
                 * DRAG LEAVE
                 * ====================================================
                 */
                [
                    'dragleave',
                    'drop'
                ].forEach(
                    function (eventName) {
                        uploadArea.addEventListener(
                            eventName,
                            function (event) {
                                event.preventDefault();
                                event.stopPropagation();
                                uploadArea.classList.remove(
                                    'mh-drag-over'
                                );
                            }
                        );
                    }
                );
                /**
                 * ====================================================
                 * DROP
                 * ====================================================
                 */
                uploadArea.addEventListener(
                    'drop',
                    function (event) {
                        const files =
                            event.dataTransfer.files;
                        if (
                            files &&
                            files.length > 0
                        ) {
                            input.files =
                                files;
                            showFile(
                                files[0]
                            );
                        }
                    }
                );
                /**
                 * ====================================================
                 * CLIQUE NA ÁREA
                 * ====================================================
                 *
                 * Evita clicar duas vezes no botão.
                 *
                 */
                uploadArea.addEventListener(
                    'click',
                    function (event) {
                        if (
                            event.target.closest(
                                '.mh-upload-button'
                            ) ||
                            event.target.closest(
                                '.mh-remove-file'
                            )
                        ) {
                            return;
                        }
                        input.click();
                    }
                );
            }
        );
        </script>
    <?php
}
<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sorteios_Database
{
    /**
     * Versão atual do banco.
     */
    const DB_VERSION = '1.0.0';

    /**
     * Cria/atualiza as tabelas do plugin.
     */
    public static function ativar()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $table_sorteios = $wpdb->prefix . 'sorteios';
        $table_dres = $wpdb->prefix . 'sorteio_dres';
        $table_unidades = $wpdb->prefix . 'sorteio_unidades';
        $table_historico = $wpdb->prefix . 'sorteio_historico';

        /**
         * Tabela principal dos sorteios.
         */
        $sql_sorteios = "CREATE TABLE {$table_sorteios} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            nome VARCHAR(255) NOT NULL,
            arquivo_nome VARCHAR(255) DEFAULT NULL,

            usuario_criacao BIGINT UNSIGNED NOT NULL,
            data_criacao DATETIME NOT NULL,

            usuario_sorteio BIGINT UNSIGNED DEFAULT NULL,
            data_sorteio DATETIME DEFAULT NULL,

            status VARCHAR(30) NOT NULL DEFAULT 'rascunho',

            PRIMARY KEY (id),

            KEY idx_usuario_criacao (usuario_criacao),
            KEY idx_data_criacao (data_criacao),
            KEY idx_status (status)
        ) {$charset_collate};";

        /**
         * Tabela de DREs.
         */
        $sql_dres = "CREATE TABLE {$table_dres} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            sorteio_id BIGINT UNSIGNED NOT NULL,

            dre VARCHAR(100) NOT NULL,
            total_unidades INT UNSIGNED NOT NULL DEFAULT 0,
            quantidade_vagas INT UNSIGNED NOT NULL DEFAULT 0,

            PRIMARY KEY (id),

            KEY idx_sorteio_id (sorteio_id),
            KEY idx_sorteio_dre (sorteio_id, dre)
        ) {$charset_collate};";

        /**
         * Tabela de unidades.
         */
        $sql_unidades = "CREATE TABLE {$table_unidades} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            sorteio_id BIGINT UNSIGNED NOT NULL,

            dre VARCHAR(100) NOT NULL,
            cie VARCHAR(20) NOT NULL,
            nome_unidade VARCHAR(255) NOT NULL,

            horta VARCHAR(30) NOT NULL DEFAULT 'Não informado',

            ordem_sorteio INT UNSIGNED DEFAULT NULL,
            tipo_resultado VARCHAR(20) DEFAULT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'aguardando',

            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY uk_sorteio_cie (sorteio_id, cie),

            KEY idx_sorteio_id (sorteio_id),
            KEY idx_sorteio_dre (sorteio_id, dre),
            KEY idx_sorteio_ordem (sorteio_id, ordem_sorteio),
            KEY idx_sorteio_status (sorteio_id, status),
            KEY idx_cie (cie)
        ) {$charset_collate};";

        /**
         * Tabela de histórico.
         */
        $sql_historico = "CREATE TABLE {$table_historico} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            sorteio_id BIGINT UNSIGNED NOT NULL,
            unidade_id BIGINT UNSIGNED DEFAULT NULL,

            usuario_id BIGINT UNSIGNED DEFAULT NULL,

            acao VARCHAR(50) NOT NULL,
            campo VARCHAR(50) DEFAULT NULL,

            valor_anterior TEXT DEFAULT NULL,
            valor_novo TEXT DEFAULT NULL,

            observacao TEXT DEFAULT NULL,

            created_at DATETIME NOT NULL,

            PRIMARY KEY (id),

            KEY idx_sorteio_id (sorteio_id),
            KEY idx_unidade_id (unidade_id),
            KEY idx_usuario_id (usuario_id),
            KEY idx_sorteio_data (sorteio_id, created_at)
        ) {$charset_collate};";

        dbDelta($sql_sorteios);
        dbDelta($sql_dres);
        dbDelta($sql_unidades);
        dbDelta($sql_historico);

        update_option('sorteios_db_version', self::DB_VERSION);
    }
}
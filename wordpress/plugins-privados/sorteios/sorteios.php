<?php

/**
 * Plugin Name: Sorteios
 * Description: Gerenciamento de sorteios de unidades escolares.
 * Version: 1.0.0
 * Author: SME
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SORTEIOS_VERSION', '1.0.0');
define('SORTEIOS_DB_VERSION', '1.1.0');
define('SORTEIOS_PLUGIN_FILE', __FILE__);
define('SORTEIOS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SORTEIOS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Autoload do Composer.
 */
$autoload = SORTEIOS_PLUGIN_DIR . 'vendor/autoload.php';

if (file_exists($autoload)) {
    require_once $autoload;
}

add_action('admin_init', function () {

    if (!current_user_can('manage_options')) {
        return;
    }

    if (!isset($_GET['teste_phpspreadsheet'])) {
        return;
    }

    if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
        wp_die(
            'PhpSpreadsheet NÃO está disponível.'
        );
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', 'Teste');
    $sheet->setCellValue('B1', 'PhpSpreadsheet funcionando');

    wp_die(
        '<h1>PhpSpreadsheet funcionando!</h1>' .
        '<p>Classe carregada: <strong>OK</strong></p>' .
        '<p>Objeto Spreadsheet criado: <strong>OK</strong></p>' .
        '<p>Planilha manipulada: <strong>OK</strong></p>'
    );
});

require_once SORTEIOS_PLUGIN_DIR . 'includes/class-sorteios-database.php';
require_once SORTEIOS_PLUGIN_DIR . 'includes/class-sorteios-importador.php';
require_once SORTEIOS_PLUGIN_DIR . 'includes/class-sorteios-admin.php';

register_activation_hook(
    SORTEIOS_PLUGIN_FILE,
    ['Sorteios_Database', 'ativar']
);

add_action(
    'plugins_loaded',
    ['Sorteios_Database', 'atualizar'],
    5
);

function sorteios_iniciar()
{
    Sorteios_Admin::init();
}

add_action('plugins_loaded', 'sorteios_iniciar');
<?php 
/**
 * Plugin Name: Envio de Emails Personalizados - SME
 * Description: Envia e-mails e notificações personalizadas.
 * Version: 1.1
 * Author: Jardeon J M Araujo
 */

 defined('ABSPATH') || die('Ops, acesso negado!');
// Dentro do plugin
require_once plugin_dir_path(__FILE__) . 'src/classes/Envia_Emails.php';


 //require_once 'vendor/autoload.php';

 define('EMAILS_PLUGIN_BASE_URL', WP_PLUGIN_URL . "/" . dirname(plugin_basename(__FILE__)));
 define('EMAILS_PLUGIN_BASE_DIR', WP_PLUGIN_DIR . "/" . dirname(plugin_basename(__FILE__)));

 
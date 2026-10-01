<?php

namespace App\Controllers;

class TermosUsoController
{

    public static function init()
    {
        add_action(
            'wp_ajax_aceitar_termo',
            [self::class, 'aceitar_termo']
        );

        add_action(
            'wp_footer',
            [self::class, 'renderizar_modal']
        );
    }

    /**
     * Verifica se a funcionalidade de aceite está habilitada.
     */
    public static function esta_habilitado(): bool
    {
        return (bool) get_field(
            'habilitar_modal_termo',
            'option'
        );
    }

    /**
     * Retorna a versão atual do termo.
     */
    public static function obter_versao(): string
    {
        return trim(
            (string) get_field(
                'termo_versao',
                'option'
            )
        );
    }

    /**
     * Retorna o texto exibido no modal.
     */
    public static function obter_texto_modal(): string
    {
        return (string) get_field(
            'termo_texto_modal',
            'option'
        );
    }

    /**
     * Retorna o texto exibido ao lado do checkbox.
     */
    public static function obter_texto_aceite(): string
    {
        return trim(
            (string) get_field(
                'termo_texto_aceite',
                'option'
            )
        );
    }

    /**
     * Retorna os dados do documento configurado no ACF.
     */
    public static function obter_documento()
    {
        return get_field(
            'termo_documento',
            'option'
        );
    }

    /**
     * Retorna a versão aceita pelo usuário.
     */
    public static function obter_versao_aceita(
        int $user_id
    ): string {
        return (string) get_user_meta(
            $user_id,
            'termo_aceite_versao',
            true
        );
    }

    /**
     * Retorna a data do último aceite do usuário.
     */
    public static function obter_data_aceite(
        int $user_id
    ): string {
        return (string) get_user_meta(
            $user_id,
            'termo_aceite_data',
            true
        );
    }

    /**
     * Verifica se o usuário já aceitou a versão vigente.
     */
    public static function usuario_aceitou(
        int $user_id
    ): bool {
        if (!$user_id) {
            return false;
        }

        $versao_atual = self::obter_versao();

        if (!$versao_atual) {
            return true;
        }

        $versao_aceita = self::obter_versao_aceita(
            $user_id
        );

        return $versao_aceita === $versao_atual;
    }

    /**
     * Verifica se o usuário possui aceite pendente.
     */
    public static function usuario_precisa_aceitar(
        int $user_id = 0
    ): bool {
        // Visitante não precisa passar pela validação do termo.
        if (!is_user_logged_in()) {
            return false;
        }

        // Permite chamar o método sem informar o ID.
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        if (!self::esta_habilitado()) {
            return false;
        }

        return !self::usuario_aceitou($user_id);
    }

    /**
     * Registra o aceite da versão vigente.
     */
    public static function registrar_aceite(
        int $user_id
    ): bool {
        if (!self::esta_habilitado()) {
            return false;
        }

        if (!$user_id) {
            return false;
        }

        $versao = self::obter_versao();

        if (!$versao) {
            return false;
        }

        update_user_meta(
            $user_id,
            'termo_aceite_versao',
            $versao
        );

        update_user_meta(
            $user_id,
            'termo_aceite_data',
            current_time('mysql')
        );

        return true;
    }

    public static function aceitar_termo()
    {
        check_ajax_referer(
            'aceitar_termo',
            'nonce'
        );

        if (!is_user_logged_in()) {
            wp_send_json_error([
                'mensagem' => 'Usuário não autenticado.',
            ]);
        }

        $aceite = isset($_POST['aceite'])
            ? sanitize_text_field(
                wp_unslash($_POST['aceite'])
            )
            : '';

        if ($aceite !== '1') {
            wp_send_json_error([
                'mensagem' => 'É necessário aceitar os termos.',
            ]);
        }

        $user_id = get_current_user_id();

        $resultado = self::registrar_aceite(
            $user_id
        );

        if (!$resultado) {
            wp_send_json_error([
                'mensagem' => 'Não foi possível registrar o aceite.',
            ]);
        }

        wp_send_json_success([
            'mensagem' => 'Aceite registrado com sucesso.',
        ]);
    }

    public static function renderizar_modal()
    {
        // Não exibe no painel administrativo.
        if (is_admin()) {
            return;
        }

        // Não interfere em requisições AJAX.
        if (wp_doing_ajax()) {
            return;
        }

        // Visitantes não precisam aceitar.
        if (!is_user_logged_in()) {
            return;
        }

        // Só renderiza se houver aceite pendente.
        if (!self::usuario_precisa_aceitar()) {
            return;
        }

        get_template_part(
            'src/Views/template-parts/modal-termo'
        );
    }
}
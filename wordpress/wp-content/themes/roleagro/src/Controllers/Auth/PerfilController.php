<?php

namespace App\Controllers\Auth;

class PerfilController
{
    private const PERFIS = [
        'gestor_ue' => [
            'nome'  => 'Gestor UE',
            'grupo' => 'UE',
        ],
        'administrador_ue' => [
            'nome'  => 'Administrador UE',
            'grupo' => 'UE',
        ],
        'cogestor_ue' => [
            'nome'  => 'Cogestor UE',
            'grupo' => 'DRE',
        ],
        'administrador_codae' => [
            'nome'  => 'Administrador CODAE',
            'grupo' => 'CODAE',
        ],
        'gestor_codae' => [
            'nome'  => 'Gestor CODAE',
            'grupo' => 'CODAE',
        ],
        'supervisao_codae' => [
            'nome'  => 'Supervisão CODAE',
            'grupo' => 'CODAE',
        ],
        'usuario_codae' => [
            'nome'  => 'Usuário CODAE',
            'grupo' => 'CODAE',
        ],
        'gestor_empresa' => [
            'nome'  => 'Gestor Empresa',
            'grupo' => 'Empresa',
        ],
        'administrador_empresa' => [
            'nome'  => 'Administrador Empresa',
            'grupo' => 'Empresa',
        ],
        'agente_empresa' => [
            'nome'  => 'Agente Empresa',
            'grupo' => 'Empresa',
        ],
        'usuario_fiscalizacao' => [
            'nome'  => 'Usuário Fiscalização',
            'grupo' => 'Fiscalização',
        ],
    ];

    private const CAPABILITIES = [
        // Painel
        'acessar_painel_adm',
        'visualizar_dashboard',

        // Usuários
        'visualizar_usuarios',
        'cadastrar_usuarios',
        'editar_usuarios',
        'gerenciar_perfis',

        // Agendamentos
        'realizar_agendamentos',
        'cancelar_agendamentos',
        'aprovar_agendamentos',

        // Autorizações
        'enviar_autorizacoes',
        'visualizar_autorizacoes',

        // Relatórios
        'visualizar_relatorios',
        'exportar_relatorios',

        // Administração
        'visualizar_atualizacoes',
    ];

    public function __construct()
    {
        add_action( 'init', [$this, 'registrar_perfis'] );
        add_action( 'init', [$this, 'registrar_capabilities'] );
    }

    public function registrar_perfis()
    {
        foreach (self::PERFIS as $slug => $perfil) {
            if (get_role($slug)) {
                continue;
            }

            add_role($slug, $perfil['nome'], [
                'read' => true,
            ]);
        }
    }

    public static function obter_perfis_por_grupo($grupo) {
        $perfis = [];

        foreach (self::PERFIS as $slug => $perfil) {
            if ($perfil['grupo'] === $grupo) {
                $perfis[$slug] = $perfil['nome'];
            }
        }

        return $perfis;
    }

    public static function obter_perfis_agrupados() {
        $grupos = [];
        $perfis_sistema = [
            'administrator',
            'editor',
            'author',
            'adm-eol',
            'contributor',
            'subscriber',
        ];

        foreach (self::PERFIS as $slug => $perfil) {
            $grupo = sanitize_title($perfil['grupo']);
            $grupos[$grupo][$slug] = $perfil['nome'];
        }

        foreach ($perfis_sistema as $slug) {
            $role = get_role($slug);

            if ($role) {
                $grupos['sistema'][$slug] = translate_user_role(
                    wp_roles()->roles[$slug]['name']
                );
            }
        }

        return $grupos;
    }

    public function registrar_capabilities()
    {
        $administrador = get_role('administrator');

        if (!$administrador) {
            return;
        }

        foreach (self::CAPABILITIES as $capability) {
            if (!$administrador->has_cap($capability)) {
                $administrador->add_cap($capability);
            }
        }
    }
}
<?php

namespace App\Controllers\Auth;

class UsuarioController
{
    public function __construct() {
        add_filter( 'manage_users_columns', [$this, 'adicionar_coluna_grupo'] );
        add_filter( 'manage_users_custom_column', [$this, 'exibir_coluna_grupo'], 10, 3 );

        add_action( 'admin_head-users.php', [$this, 'adicionar_filtro_grupo'] );
        add_action( 'pre_get_users', [$this, 'filtrar_usuarios_por_grupo'] );

        add_action( 'admin_head-user-edit.php', [$this, 'reposicionar_campo_grupo'] );
        add_action( 'admin_head-user-new.php', [$this, 'reposicionar_campo_grupo'] );
        add_action( 'admin_head-profile.php', [$this, 'reposicionar_campo_grupo'] );

        add_action( 'admin_head-user-edit.php', [$this, 'filtrar_perfis_por_grupo'] );
        add_action('admin_head-user-new.php', [$this, 'filtrar_perfis_por_grupo'] );
        add_action( 'admin_head-profile.php', [$this, 'filtrar_perfis_por_grupo'] );

        add_filter('views_users', [$this, 'filtrar_views_usuarios']);
    }

    public function adicionar_coluna_grupo( array $columns ) {
        $columns['grupo_usuario'] = 'Grupo';

        return $columns;
    }

    public function exibir_coluna_grupo( $output, $column_name, $user_id ) {
        if ( $column_name !== 'grupo_usuario' ) {
            return $output;
        }

        $grupo = get_field( 'grupo_usuario', 'user_' . $user_id );

        if ( empty( $grupo['label'] ) ) {
            return '—';
        }

        return esc_html( $grupo['label'] );
    }

    //Adiciona um seletor de grupo ao lado do campo de busca na página de usuários
    public function adicionar_filtro_grupo() {

        $usuario = wp_get_current_user();

        if ( !in_array('administrator', (array) $usuario->roles, true) && !in_array('gestor_codae', (array) $usuario->roles, true) ) {
            return;
        }

        $grupo_selecionado = isset( $_GET['grupo_usuario'] )
            ? sanitize_text_field( wp_unslash( $_GET['grupo_usuario'] ) )
            : '';

        $grupos = [
            'UE'           => 'UE',
            'DRE'          => 'DRE',
            'CODAE'        => 'CODAE',
            'Empresa'      => 'Empresa',
            'Fiscalização' => 'Fiscalização',
        ];
        ?>

        <script>
            jQuery(function ($) {
                var $searchBox = $('p.search-box').first();

                if (!$searchBox.length) {
                    console.warn('Campo de pesquisa de usuários não encontrado.');
                    return;
                }

                var $select = $('<select>', {
                    id: 'grupo_usuario',
                    name: 'grupo_usuario',
                    'aria-label': 'Filtrar por grupo'
                });

                $select.append(
                    $('<option>', {
                        value: '',
                        text: 'Todos os grupos'
                    })
                );

                <?php foreach ($grupos as $valor => $label): ?>
                    $select.append(
                        $('<option>', {
                            value: <?php echo wp_json_encode($valor); ?>,
                            text: <?php echo wp_json_encode($label); ?>
                        })
                    );
                <?php endforeach; ?>

                $select.val(<?php echo wp_json_encode($grupo_selecionado); ?>);
                $searchBox.find('input[type="submit"]').before($select);
            });
        </script>

        <?php
    }

    // Restringe a listagem dos usuários com base no Grupo
    public function filtrar_usuarios_por_grupo( $query ) {
        global $pagenow;

        if ( !is_admin() || $pagenow !== 'users.php' ) {
            return;
        }

        $usuario = wp_get_current_user();
        $perfis_usuario = (array) $usuario->roles;

        $acesso_total = in_array( 'administrator', $perfis_usuario, true ) || in_array( 'gestor_codae', $perfis_usuario, true );

        // Administrador técnico e Gestor CODAE podem visualizar todos os grupos.
        if ($acesso_total) {

            // Mantém o filtro manual por grupo.
            if ( !empty( $_GET['grupo_usuario'] ) ) {
                $grupo = sanitize_text_field( wp_unslash( $_GET['grupo_usuario'] ) );

                $meta_query = (array) $query->get( 'meta_query' );

                $meta_query[] = [
                    'key'   => 'grupo_usuario',
                    'value' => $grupo,
                ];

                $query->set( 'meta_query', $meta_query );
            }

            return;
        }

        $grupo = get_field( 'grupo_usuario', 'user_' . $usuario->ID );

        if ( empty( $grupo ) ) {
            $query->set( 'include', [0] );
            return;
        }

        // Restringe a listagem ao grupo do usuário logado.
        $meta_query = (array) $query->get('meta_query');

        $meta_query[] = [
            'key'   => 'grupo_usuario',
            'value' => $grupo,
        ];

        $query->set('meta_query', $meta_query);
    }

    // Ajusta a posição do campo grupo para que ele fique sempre no inicio do formulário de usuários
    public function reposicionar_campo_grupo() {
        ?>
        <script>
            jQuery(function ($) {
                var $grupo = $('.acf-field[data-name="grupo_usuario"]');
                var $funcao = $('#role').closest('tr');

                if (!$grupo.length || !$funcao.length) {
                    return;
                }

                var $tabelaGrupo = $grupo.closest('table.form-table');

                if ($tabelaGrupo.length) {
                    $tabelaGrupo.insertBefore($funcao.closest('table'));
                }
            });
        </script>
        <?php
    }

    // Adiciona a funcionalidade do filtro por grupo na busca de usuários
    public function filtrar_perfis_por_grupo() {

        $perfis = PerfilController::obter_perfis_agrupados();
        ?>

        <script>
            jQuery(function ($) {
                var perfis = <?php echo wp_json_encode($perfis); ?>;

                var $grupo = $('.acf-field[data-name="grupo_usuario"] select');
                var $funcao = $('#role');

                if (!$grupo.length || !$funcao.length) {
                    return;
                }

                var opcoesOriginais = $funcao.find('option').clone();

                function atualizarPerfis() {
                    var grupo = $grupo.val();
                    var perfilAtual = $funcao.val();

                    $funcao.empty();

                    if (!grupo || !perfis[grupo]) {
                        $funcao.empty();
                        return;
                    }

                    opcoesOriginais.each(function () {
                        var $opcao = $(this);

                        if (perfis[grupo][$opcao.val()]) {
                            $funcao.append($opcao.clone());
                        }
                    });

                    if ($funcao.find('option').filter(function () {
                        return this.value === perfilAtual;
                    }).length) {
                        $funcao.val(perfilAtual);
                    } else {
                        $funcao.prop('selectedIndex', 0);
                    }
                }

                $grupo.on('change', atualizarPerfis);

                atualizarPerfis();
            });
        </script>

        <?php
    }

    // Limita a exibição dos filtros (views) com base no grupo do usuário
    public function filtrar_views_usuarios( $views ) {
        $usuario = wp_get_current_user();
        $perfis_usuario = (array) $usuario->roles;

        // Administrador e Gestor CODAE visualizam todos os filtros.
        if ( in_array( 'administrator', $perfis_usuario, true ) || in_array( 'gestor_codae', $perfis_usuario, true ) ) {
            return $views;
        }

        $grupo = get_field('grupo_usuario', 'user_' . $usuario->ID);

        if ( empty( $grupo ) ) {
            return [];
        }

        $perfis_permitidos = PerfilController::obter_perfis_por_grupo( $grupo );

        foreach ( $views as $chave => $view ) {
            // Mantém o filtro "Todos".
            if ($chave === 'all') {
                continue;
            }

            // Remove funções que não pertencem ao grupo.
            if (!isset($perfis_permitidos[$chave])) {
                unset($views[$chave]);
            }
        }

        return $views;
    }
}
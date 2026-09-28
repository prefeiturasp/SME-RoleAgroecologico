<?php
use App\Controllers\TermosUsoController;

if (!defined('ABSPATH')) {
    exit;
}

$usuario = wp_get_current_user();
$nome_usuario = $usuario->display_name;

$versao       = TermosUsoController::obter_versao();
$texto_modal  = TermosUsoController::obter_texto_modal();
$texto_aceite = TermosUsoController::obter_texto_aceite();
$documento    = TermosUsoController::obter_documento();

$documento_url = '';

if (
    is_array($documento)
    && !empty($documento['url'])
) {
    $documento_url = $documento['url'];
}
?>

<div
    class="modal fade"
    id="modal-termo-uso"
    data-ajax-url="<?php
        echo esc_url(admin_url('admin-ajax.php'));
    ?>"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-termo-uso-titulo"
    aria-hidden="true"
    data-backdrop="static"
    data-keyboard="false"
>
    <div
        class="modal-dialog modal-dialog-centered"
        role="document"
    >
        <div class="modal-content">

            <div class="modal-header">
                <h5
                    class="modal-title"
                    id="modal-termo-uso-titulo"
                >
                    Olá, <?php echo esc_html($nome_usuario); ?>!
                </h5>
            </div>

            <div class="modal-body">

                <?php if ($texto_modal) : ?>

                    <div class="mb-3">
                        <?php
                        echo wp_kses_post($texto_modal);
                        ?>
                    </div>

                <?php endif; ?>

                <?php if ($documento_url) : ?>

                    <div class="mb-4">
                        <a
                            href="<?php echo esc_url($documento_url); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-link pl-0"
                        >
                            Ler Termos de Uso e Política de Privacidade →
                        </a>
                    </div>

                <?php endif; ?>

                <div class="form-check mr-2">

                    <input
                        class="form-check-input"
                        type="checkbox"
                        value="1"
                        id="aceite-termo"
                    >

                    <label
                        class="form-check-label pl-2"
                        for="aceite-termo"
                    >
                        <?php
                        echo esc_html($texto_aceite);
                        ?>
                    </label>

                </div>

                <?php if ($versao) : ?>

                    <small class="text-muted d-block mt-3">
                        Versão
                        <?php echo esc_html($versao); ?>
                    </small>

                <?php endif; ?>

                <input
                    type="hidden"
                    id="aceite-termo-nonce"
                    value="<?php
                        echo esc_attr(
                            wp_create_nonce('aceitar_termo')
                        );
                    ?>"
                >

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-primary"
                    id="btn-aceitar-termo"
                    disabled
                >
                    Aceitar e continuar
                </button>

            </div>

        </div>
    </div>
</div>
<?php extract($args); ?>

<div class="accordion w-100" id="bloco-accordion-<?php echo esc_html( $index ?? 0 ); ?>">
	<?php foreach ( $itens_accordion as $key => $item ) : ?>
		<div class="card card-duvidas-freq bg-verde-7">
			<div class="card-header bg-verde-8" id="<?php echo esc_html( "heading-{$index}-{$key}" ) ?>">
				<h2
					class="mb-0 collapse-button collapsed"
					data-toggle="collapse"
					data-target="#<?php echo esc_html( "collapse-{$index}-{$key}" ); ?>"
					aria-expanded="true" aria-controls="<?php echo esc_html( "collapse-{$index}-{$key}" ); ?>"
					>
					<button class="btn btn-link btn-titulo-acordion" type="button" style="word-break: break-all;">
						<span><?php echo esc_html( $item['titulo'] ); ?></span>
					</button>
					<i class="fa fa-angle-down icon-duv-freq float-right accordion-icon" aria-hidden="true"></i>
				</h2>
			</div>

			<div
				id="<?php echo esc_html( "collapse-{$index}-{$key}" ); ?>"
				class="collapse"
				aria-labelledby="<?php echo esc_html( "heading-{$index}-{$key}" ) ?>"
				data-parent="#bloco-accordion-<?php echo esc_html( $index ?? 0 ); ?>"
				>
				<div class="card-body">
					<?php echo wp_kses_post( $item['conteudo'] ); ?>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</div>
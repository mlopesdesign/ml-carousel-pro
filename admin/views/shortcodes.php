<div class="wrap mlcp-wrap">
    <?php echo MLCP_Helpers::render_admin_header('mlcp-shortcodes', 'ML Banner Pro', 'Cada grupo já entrega o shortcode pronto para copiar e colar.'); ?>

    <div class="mlcp-card-panel">
        <h2>Shortcode base</h2>
        <div class="mlcp-inline-shortcode mlcp-inline-shortcode--large">
            <code>[ml_carousel group="slug-do-grupo"]</code>
            <button type="button" class="button button-primary mlcp-copy-shortcode" data-shortcode='[ml_carousel group="slug-do-grupo"]'>Copiar</button>
        </div>
        <p>Você também pode sobrescrever largura, altura, autoplay, setas e quantidade visível.</p>
        <div class="mlcp-inline-shortcode mlcp-inline-shortcode--large">
            <code>[ml_carousel group="eventos-home" width="100%" height="360px" desktop="3" tablet="2" mobile="1" autoplay="1" arrows="1"]</code>
            <button type="button" class="button mlcp-copy-shortcode" data-shortcode='[ml_carousel group="eventos-home" width="100%" height="360px" desktop="3" tablet="2" mobile="1" autoplay="1" arrows="1"]'>Copiar</button>
        </div>
    </div>

    <div class="mlcp-card-panel">
        <h2>Template Agenda</h2>
        <p>Mostra o grupo como agenda: data grande, título, subtítulo (horário) e cartaz, em ordem de data. Eventos passados somem sozinhos. Com mais itens que <code>visible</code>, a lista sobe sozinha (pausa ao passar o mouse). O cartaz abre em tela cheia; com link, o título leva ao link.</p>
        <div class="mlcp-inline-shortcode mlcp-inline-shortcode--large">
            <code>[ml_carousel group="eventos" template="agenda" visible="3" interval="4000"]</code>
            <button type="button" class="button mlcp-copy-shortcode" data-shortcode='[ml_carousel group="eventos" template="agenda" visible="3" interval="4000"]'>Copiar</button>
        </div>
        <p>Campo <strong>Data</strong> do banner: AAAA-MM-DD ou DD-MM-AAAA (vazio = usa a data de expiração).</p>
        <p>Opções: <code>visible</code> (linhas visíveis, padrão 3), <code>interval</code> (ms entre cada subida, padrão 4000), <code>accent</code> (cor da data, ex.: <code>accent="#0a6"</code>). Serve para qualquer site.</p>
    </div>

    <div class="mlcp-card-panel">
        <h2>Shortcodes por grupo</h2>
        <?php if ($groups && !is_wp_error($groups)) : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Grupo</th>
                        <th>Slug</th>
                        <th>Shortcode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group) : ?>
                        <?php $shortcode = MLCP_Helpers::get_group_shortcode($group->slug); ?>
                        <tr>
                            <td><?php echo esc_html($group->name); ?></td>
                            <td><code><?php echo esc_html($group->slug); ?></code></td>
                            <td>
                                <div class="mlcp-inline-shortcode">
                                    <code><?php echo esc_html($shortcode); ?></code>
                                    <button type="button" class="button button-small mlcp-copy-shortcode" data-shortcode="<?php echo esc_attr($shortcode); ?>">Copiar</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Nenhum grupo criado ainda.</p>
        <?php endif; ?>
    </div>

    <div class="mlcp-copy-toast" id="mlcp-copy-toast" aria-hidden="true">Shortcode copiado.</div>
</div>

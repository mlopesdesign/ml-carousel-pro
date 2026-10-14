=== ML Banner Pro ===
Contributors: marciolopes
Tags: carousel, banner, slider, shortcode, groups
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.12.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Carrossel profissional com grupos, múltiplos shortcodes, ordenação administrativa, autoplay, analytics e integração com templates modernos.

== Description ==

ML Banner Pro é um plugin comercial de carrossel/banner para WordPress desenvolvido pela ML Lopes Design. Ele oferece:

* Grupos de banners independentes com shortcodes próprios
* Configurações por grupo: layout, dimensões, autoplay, setas, overlay, image fit
* Ordenação manual por drag-and-drop
* Analytics de visualizações e cliques por item com CTR por banner
* Lightbox nativo para slides sem link configurado
* Agendamento de expiração por item
* Suporte a cover/contain por group
* Compatível com Nicepage, Elementor e outros page builders

== Installation ==

1. Faça upload da pasta `ml-carousel-pro` para `/wp-content/plugins/`
2. Ative o plugin em **Plugins > Plugins instalados**
3. Acesse **ML Banner Pro** no menu lateral do WordPress
4. Crie grupos, adicione itens e use o shortcode `[ml_carousel group="nome-do-grupo"]`

== Frequently Asked Questions ==

= Como adiciono um carrossel na página? =

Crie um grupo em **ML Banner Pro > Grupos**, adicione itens com imagens e use o shortcode exibido na coluna "Shortcode" da tabela de grupos ou na tela **Shortcodes**.

= Posso ter múltiplos carrosséis com configurações diferentes? =

Sim. Cada grupo tem suas próprias configurações de layout, dimensões, autoplay, overlay e image fit.

= O que é o modo "Contain"? =

No modo Contain, a imagem é exibida inteira sem corte, ideal para logos ou imagens com proporções variadas.

== Changelog ==

= 1.12.5 =
* Corrigido o arraste que transformava as linhas em blocos vazios com bordas tracejadas
* Removidos placeholder e helper personalizados da tabela
* A ordenação agora usa o comportamento nativo simples do WordPress/jQuery UI, mantendo as linhas visíveis

= 1.12.4 =
* Corrigida a ativação do arraste na tela Itens quando a URL não contém post_status
* Removido o ícone de movimento: agora basta segurar qualquer área livre da linha e arrastar
* Melhorado o retorno visual durante o reposicionamento dos banners

= 1.12.3 =
* A tela Itens agora permite ordenar banners arrastando cada linha para cima ou para baixo
* A nova ordem é salva automaticamente e aplicada ao carrossel sem recarregar a página
* A ordenação preserva com segurança itens fora da página atual e resultados filtrados

= 1.12.2 =
* Ordenação transferida para a tela Itens
* Adicionados controles para mover cada banner para cima ou para baixo
* Removida a aba separada Ordenação

= 1.12.1 =
* Release corretiva com versionamento inédito para atualização segura sobre a versão 1.11.17
* Mantidas integralmente as correções consolidadas de permissões, expiração e Lixeira

= 1.12.0 =
* Consolidação: ao ativar, desativa automaticamente cópias antigas do mesmo ML Banner Pro
* Corrigido: uma única versão ativa controla painel, banners, expiração e Lixeira
* Corrigido: verificação duplicada do atualizador removida
* Corrigido: permissões administrativas mapeadas corretamente
* Mantido: Itens somente publicados, Rascunhos separados e Lixeira (0) sempre visível

= 1.11.17 =
* Corrigido: administradores podem mover, restaurar, excluir definitivamente e esvaziar a Lixeira
* Corrigido: capacidades meta do tipo mlcp_item agora são mapeadas corretamente pelo WordPress
* Mantido: Itens exibe somente publicados; Rascunhos e Lixeira permanecem separados

= 1.11.16 =
* Corrigido: a tela principal Itens mostra exclusivamente banners publicados
* Separado: rascunhos aparecem somente na aba Rascunhos e excluídos somente na aba Lixeira
* Removido: filtro Todos, que misturava estados diferentes na mesma listagem

= 1.11.15 =
* Adicionado: a aba Lixeira permanece sempre visível na tela Itens, inclusive como Lixeira (0)
* Mantido: contador atualizado, restauração, exclusão individual e Esvaziar lixeira

= 1.11.14 =
* Validado: mover para a Lixeira funciona individualmente e em ações em massa pelo fluxo nativo do WordPress
* Migração automática: banners vencidos que estavam em Rascunho passam para a Lixeira
* Preservado: restauração e Esvaziar lixeira usam o fluxo padrão do WordPress

= 1.11.13 =
* Alterado: banners vencidos agora são enviados automaticamente para a Lixeira
* Adicionado: banners vencidos podem ser restaurados ou removidos definitivamente com Esvaziar lixeira

= 1.11.12 =
* Corrigido: banners novos agora iniciam ativos e permanecem visíveis após salvar/publicar
* Preservado: banners existentes marcados manualmente como inativos continuam inativos

= 1.11.11 =
* Hotfix: motor do carousel resistente a conflitos de tema
* front.js: cardStep() com 4 camadas de fallback (DOM, CSS gap, CSS var, hardcoded)
* front.js: boot com retry exponencial (8 tentativas) para temas com visibilidade deferida (AOS, Elementor animations, jQuery show)
* front.js: setas com stopPropagation() para evitar interceptação por handlers do tema
* front.js: window.load re-trigger para carousels com transform=0 no DOMContentLoaded
* front.css: !important em overflow, transform, z-index e pointer-events das setas e viewport para blindar contra resets de tema

PYEOF= 1.11.1 =
* Hotfix: removidos screen IDs ml-banner-pro_page_* indevidos de class-mlcp-assets.php
* Slug tecnico ml-carousel-pro preservado em todos os pontos tecnicos
* ZIP renomeado para ml-carousel-pro-vX.Y.Z padrao correto

= 1.11.0 =
* Analytics consolidado em MLCP_Analytics_Service (storage: mlcp_analytics)
* Migração automática de dados legacy (ml_banner_analytics + post_meta)
* Anti-inflação de views: deduplicação por carouselId:itemId por sessão
* Click tracking: debounce 650ms, captura na fase capture sem bubbling duplicado
* Guards defined() e class_exists() em todos os arquivos
* Updater Core/Updater.php ativo


= 1.10.16 =
* Adicionado: GitHub Updater — atualizações automáticas via WordPress admin a partir de GitHub Releases
* Adicionado: painel de diagnóstico do updater na tela Licença (repositório, versão remota, token, ZIP)
* Melhorado: headers do plugin completos (License, License URI, Tested up to)
* Preparação: estrutura para WordPress.org (readme, .distignore, GitHub Actions)

= 1.10.15 =
* Verificação final de pacote e sincronização de versão obrigatória.

= 1.10.14 =
* Corrigido: mojibake em strings "inválido" e "Cópia" no fluxo de duplicação de itens
* Corrigido: chave duplicada `default_overlay_opacity` em `get_settings()` (conflito string vs int)
* Corrigido: chave duplicada `overlay_opacity` em `get_group_defaults()` (conflito string vs int)
* Corrigido: `save_settings()` não salvava `default_overlay_enabled` — campo adicionado ao flow
* Corrigido: `aria-label` sem `esc_attr()` no elemento âncora do shortcode
* Corrigido: settings view exibia `default_overlay_opacity` como campo de texto livre (agora número 0-100)
* Adicionado: checkbox `default_overlay_enabled` na tela de Configurações globais
* Adicionado: `readme.txt` profissional com changelog completo
* Todos os arquivos verificados como UTF-8 sem BOM

= 1.10.13 =
* Adicionado: ajuste de imagem por grupo (cover/contain) com variável CSS e fallback de cor de fundo
* Corrigido: comportamento de image_fit no frontend para contain (fundo transparente) e cover (fundo escuro)

= 1.10.2 =
* Adicionado: lightbox nativo para slides sem link configurado
* Suporte a teclado (ESC, Enter, Space) e clique fora para fechar

= 1.10.1 =
* Adicionado: sistema de analytics por item (views, cliques, CTR)
* Adicionado: tela Analytics com KPIs globais e reset por item
* Rastreamento com guarda de deduplicação de 1,5s

== Upgrade Notice ==

= 1.10.14 =
Correção de bugs críticos de encoding e comportamento de configurações globais. Atualização recomendada para todos os usuários.

== Screenshots ==

1. Dashboard com visão geral do plugin
2. Tela de grupos com shortcodes prontos
3. Configurações por grupo
4. Analytics com KPIs e CTR por banner

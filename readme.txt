=== ML Banner Pro ===
Contributors: marciolopes
Tags: carousel, banner, slider, shortcode, groups
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.11.12
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

= 1.11.12 =
* Alterado (identidade visual): o painel passa a usar exclusivamente a paleta ML Lopes Design. A pílula da versão era `#eef2ff` / `#1d4ed8` e o badge azul `#eff6ff` / `#c9daf8` / `#245ba8`; ambos migraram para `--mlcp-accent-soft` e `--mlcp-accent`.
* Corrigido: o azul padrão do WordPress vazava nos botões primários, porque o CSS não sobrescrevia `.button-primary`. Agora `.button-primary` e `.button-secondary` são sobrescritos dentro de `.mlcp-wrap`.
* Alterado: os badges de status "pendente" (azul) e "agendado" (roxo) passaram para tons da paleta. Verde, amarelo e vermelho foram mantidos por serem semântica universal de publicado, privado e perigo.
* Limpeza: tons quase duplicados consolidados nos tokens existentes — `#cad9de` e `#d9e7ea` para `--mlcp-border`, `#fbfefe` para `#f6fafb`, `#eef5f7` para `--mlcp-accent-soft`.
* Nenhuma alteração estrutural: apenas valores de cor. Contagem de regras CSS e layout preservados.
* Sincronizado: o cabeçalho do plugin e a `MLCP_VERSION` estavam em 1.10.19 enquanto a release publicada era a v1.11.11. Agora cabeçalho, constante, Stable tag e tag do git apontam para a mesma versão.

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

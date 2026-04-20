=== Asset Control ===
Contributors: yourname
Tags: performance, scripts, styles, assets, optimization, speed, css, javascript
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gerencie quais scripts (JS) e estilos (CSS) são carregados em cada página do seu site WordPress para melhorar o desempenho.

== Description ==

**Asset Control** é um plugin WordPress que permite visualizar, gerenciar e controlar quais scripts e estilos são carregados em cada página do seu site. Isso ajuda a melhorar significativamente o desempenho e a velocidade de carregamento das páginas.

= Funcionalidades Principais =

* **Detector de Recursos por Página**: Visualize todos os scripts e estilos carregados em qualquer página do seu site
* **Gerenciamento Granular**: Ative ou desative recursos específicos para cada página individualmente
* **Interface Intuitiva**: Painel administrativo limpo e fácil de usar
* **Toggle Switch**: Controle rápido de ativação/desativação para cada recurso
* **Busca e Filtros**: Encontre rapidamente os recursos que deseja gerenciar
* **Paginação**: Navegue facilmente por listas longas de recursos
* **Estatísticas em Tempo Real**: Veja quantos recursos estão ativos/desativados
* **Copiar URL**: Copie rapidamente URLs de recursos com um clique
* **Ações em Lote**: Gerencie múltiplos recursos de uma vez
* **Admin Bar Integration**: Acesso rápido da barra de administração

= Como Funciona =

1. Acesse o menu **Asset Control** no painel administrativo
2. Selecione a página que deseja gerenciar
3. Visualize todos os scripts e estilos carregados naquela página
4. Use os toggle switches para ativar/desativar recursos conforme necessário
5. As configurações são salvas automaticamente

= Benefícios =

* **Melhor Performance**: Reduza o tempo de carregamento removendo recursos desnecessários
* **SEO Melhorado**: Páginas mais rápidas melhoram o ranking nos mecanismos de busca
* **Experiência do Usuário**: Sites mais rápidos proporcionam melhor experiência
* **Controle Total**: Decida exatamente quais recursos carregar em cada página

== Installation ==

= Instalação Automática =

1. Acesse o painel administrativo do WordPress
2. Vá para **Plugins > Adicionar Novo**
3. Pesquise por "Asset Control"
4. Clique em **Instalar Agora** e depois em **Ativar**

= Instalação Manual =

1. Baixe o arquivo ZIP do plugin
2. No painel administrativo, vá para **Plugins > Adicionar Novo**
3. Clique em **Enviar Plugin** e selecione o arquivo ZIP
4. Clique em **Instalar Agora** e depois em **Ativar**

= Pós-Instalação =

Após ativar o plugin, você encontrará um novo menu chamado **Asset Control** no painel administrativo.

== Frequently Asked Questions ==

= Este plugin é compatível com meu tema? =

Sim! O Asset Control funciona com qualquer tema WordPress, pois gerencia os recursos no nível do WordPress core.

= Posso reverter as alterações? =

Sim! Você pode reativar qualquer recurso desativado a qualquer momento através do painel do plugin.

= Isso afetará todas as páginas do site? =

Não. As configurações são específicas para cada página. Um recurso desativado em uma página continuará ativo nas outras.

= É seguro desativar scripts e estilos? =

Use com cautela. Desative apenas recursos que você tem certeza que não são necessários para a funcionalidade da página. Recomendamos testar cada página após fazer alterações.

= O plugin funciona com cache? =

Sim! O plugin usa transients do WordPress para cache de dados, melhorando o desempenho.

== Screenshots ==

1. Painel principal do Asset Control mostrando a seleção de páginas
2. Lista de recursos com toggles de ativação/desativação
3. Estatísticas de recursos ativos e desativados
4. Interface responsiva funcionando em dispositivos móveis

== Changelog ==

= 1.0.0 =
* Lançamento inicial do plugin
* Detector de recursos por página
* Gerenciamento de scripts e estilos
* Toggle switches para ativação/desativação
* Busca e filtros
* Paginação
* Ações em lote
* Integração com Admin Bar
* Interface responsiva

== Upgrade Notice ==

= 1.0.0 =
Versão inicial do plugin.

== Additional Information ==

= Desenvolvedores =

O plugin fornece hooks e filtros para personalização:

**Filtros disponíveis:**
* `asset_control_available_pages` - Modifica a lista de páginas disponíveis
* `asset_control_after_dequeue` - Executa ações após recursos serem removidos

**Hooks de ação:**
* `asset_control_after_dequeue` - Disparado após os recursos serem desativados

= Suporte =

Para suporte técnico, visite nosso [fórum de suporte](https://example.com/support).

= Privacidade =

Este plugin não coleta nenhum dado pessoal dos visitantes do site. Todas as configurações são armazenadas localmente no seu servidor.

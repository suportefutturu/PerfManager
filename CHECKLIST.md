# Checklist de Validação Funcional - PerfManager v2.0.0

## ✅ Verificações de Segurança

### PHP 8.2+ e Strict Types
- [ ] Todos os arquivos PHP iniciam com `declare(strict_types=1);`
- [ ] Nenhuma warning de tipo é gerada em PHP 8.2+
- [ ] Type hints são usados em todos os parâmetros e retornos de funções/métodos

### Namespaces PSR-4
- [ ] Classe `PerfManager\Core\Plugin` existe e funciona como singleton
- [ ] Classe `PerfManager\Core\Activation` existe
- [ ] Classe `PerfManager\Core\Deactivation` existe
- [ ] Classe `PerfManager\Core\Uninstall` existe
- [ ] Classe `PerfManager\Admin\REST\AssetsController` existe
- [ ] Classe `PerfManager\Frontend\AssetManager` existe
- [ ] Autoloader PSR-4 carrega todas as classes corretamente

### Nonces e Verificação de Capacidade
- [ ] Endpoint `/pages` verifica `current_user_can('edit_pages')`
- [ ] Endpoint `/assets` verifica `current_user_can('edit_pages')`
- [ ] Endpoint `/toggle-asset` verifica `current_user_can('edit_pages')`
- [ ] Endpoint `/toggle-asset` verifica nonce `X-WP-Nonce` header
- [ ] Menu administrativo requer capacidade `edit_pages`

### SQL Injection Prevention
- [ ] Query no método `get_pages()` usa `$wpdb->prepare()`
- [ ] Todas as queries diretas usam placeholders `%d`, `%s`, `%f`
- [ ] Nenhuma interpolação direta de variáveis em queries SQL

### XSS Prevention (Escaping)
- [ ] Template `dashboard.php` usa `esc_html_e()` para textos
- [ ] Outputs HTML usam `esc_html()` ou `esc_attr()`
- [ ] URLs usam `esc_url()`
- [ ] JavaScript data usa `wp_json_encode()` ou `esc_js()`

### Input Sanitization
- [ ] `page_id` é convertido para `(int)`
- [ ] `handle` usa `sanitize_asset_handle()`
- [ ] `asset_type` usa `validate_asset_type()`
- [ ] `search` usa `sanitize_text_field()`
- [ ] `enabled` é convertido para `(bool)`

## ✅ Verificações de Arquitetura

### Estrutura MVC
- [ ] Controllers em `src/Admin/REST/`
- [ ] Views/Templates em `templates/admin/`
- [ ] Lógica frontend em `src/Frontend/`
- [ ] Core do plugin em `src/Core/`
- [ ] Assets (CSS/JS) em `assets/`

### Separação de Concerns
- [ ] Plugin.php apenas orquestra hooks e inicialização
- [ ] AssetsController apenas lida com requisições REST
- [ ] AssetManager apenas gerencia dequeue no frontend
- [ ] Functions.php contém apenas helpers utilitários

### Padrões de Design
- [ ] Singleton pattern implementado corretamente em Plugin
- [ ] Classes marcadas como `final` quando apropriado
- [ ] Construtores privados onde necessário
- [ ] Prevenção de unserialize em singleton

## ✅ Verificações de Internacionalização

### Text Domain
- [ ] Todas as strings usam `'perfmanager'` como text domain
- [ ] Funções `__()`, `_e()`, `_x()` usadas corretamente
- [ ] Strings traduzíveis não contêm variáveis interpoladas
- [ ] Contextos descritivos adicionados quando necessário

### Strings Traduzíveis
- [ ] "Gerenciador de Scripts e Estilos" traduzível
- [ ] "Assets Manager" traduzível
- [ ] Mensagens de erro traduzíveis
- [ ] Labels da interface traduzíveis
- [ ] Mensagens de loading/sucesso traduzíveis

## ✅ Verificações de Retrocompatibilidade

### Dados Existentes
- [ ] Meta key `_disabled_scripts` mantida
- [ ] Meta key `_disabled_styles` mantida
- [ ] Migration transparente na ativação
- [ ] Uninstall remove dados corretamente

### Hooks e Filters
- [ ] Hook `rest_api_init` registrado
- [ ] Hook `admin_menu` registrado
- [ ] Hook `admin_enqueue_scripts` registrado
- [ ] Hook `wp_enqueue_scripts` registrado (prioridade 100)
- [ ] Hook `plugins_loaded` registrado

## ✅ Verificações Funcionais

### Endpoint GET /pages
- [ ] Retorna lista de páginas publicadas
- [ ] Inclui `id` e `title` para cada página
- [ ] Ordenado por título
- [ ] Requer autenticação e permissão
- [ ] Retorna 403 para usuários sem permissão

### Endpoint GET /assets
- [ ] Valida `page_id` obrigatório
- [ ] Suporta paginação (`page`, `per_page`)
- [ ] Suporta busca (`search`)
- [ ] Retorna scripts e estilos enfileirados
- [ ] Inclui status `enabled` para cada asset
- [ ] Meta dados incluem `total_items`, `total_pages`
- [ ] Simula carregamento da página corretamente

### Endpoint POST /toggle-asset
- [ ] Valida nonce antes de processar
- [ ] Valida `page_id`, `asset_type`, `handle`, `enabled`
- [ ] Atualiza post meta corretamente
- [ ] Adiciona handle quando disabling
- [ ] Remove handle quando enabling
- [ ] Previne duplicatas na lista
- [ ] Retorna sucesso/falha apropriadamente

### Frontend Dequeue
- [ ] Não executa em admin (`is_admin()`)
- [ ] Executa apenas em singular (`is_singular()`)
- [ ] Obtém ID do objeto corretamente
- [ ] Dequeue scripts desabilitados
- [ ] Dequeue styles desabilitados
- [ ] Prioridade 100 (após enqueue padrão)

### Interface Administrativa
- [ ] Menu aparece com ícone dashicons-performance
- [ ] Página renderiza template dashboard.php
- [ ] React app monta no elemento `#perfmanager-app`
- [ ] Dropdown de páginas carrega corretamente
- [ ] Tabela exibe handle, tipo, src, toggle
- [ ] Toggle atualiza estado sem reload
- [ ] Paginação funciona corretamente
- [ ] Busca filtra resultados
- [ ] Copy-to-clipboard funciona
- [ ] Loading state exibido durante fetch
- [ ] Empty state exibido quando sem assets

## ✅ Verificações de Assets

### CSS
- [ ] Arquivo `admin.css` existe
- [ ] Styles são versionados com filemtime
- [ ] Classes seguem convenção BEM (.perfmanager-*)
- [ ] Responsivo (media queries para mobile)
- [ ] Toggle switch estilizado
- [ ] Tabela estilizada
- [ ] Paginação estilizada
- [ ] Loading spinner animado

### JavaScript
- [ ] Arquivo `admin.js` existe
- [ ] Usa wp.element (React)
- [ ] Usa wp.apiFetch para requests
- [ ] Usa wp.i18n para traduções
- [ ] Strict mode habilitado
- [ ] IIFE para escopo isolado
- [ ] Event listeners registrados após DOMContentLoaded
- [ ] Error handling com try/catch
- [ ] Loading states implementados

## ✅ Verificações de Testes

### PHPUnit
- [ ] Testes em namespace `PerfManager\Tests`
- [ ] Estendem `WP_UnitTestCase`
- [ ] setUp/tearDown corretos
- [ ] Testes para funções utilitárias
- [ ] Testes para Plugin singleton
- [ ] Testes para AssetsController endpoints
- [ ] Testes para AssetManager dequeue
- [ ] Testes de permissão/capabilidade
- [ ] Testes de sanitização
- [ ] Testes de escaping

### Cobertura de Testes
- [ ] PluginTest cobre funções helper
- [ ] AssetsControllerTest cobre endpoints REST
- [ ] Testes de segurança (nonce, permissions)
- [ ] Testes de validação de input
- [ ] Testes de integração com WordPress

## ✅ Verificações de Documentação

### README.md
- [ ] Descrição clara do plugin
- [ ] Instruções de instalação
- [ ] Guia de uso básico
- [ ] Documentação de hooks/filters
- [ ] Estrutura de diretórios documentada
- [ ] Seção de segurança detalhada
- [ ] Instruções de teste
- [ ] Changelog completo
- [ ] Guia de contribuição
- [ ] Informações de licença

### Código
- [ ] DocBlocks em todas as classes
- [ ] DocBlocks em todos os métodos públicos
- [ ] @param e @return tags corretas
- [ ] @since tags em novos elementos
- [ ] Comentários explicativos onde necessário

## ✅ Verificações Finais

### Instalação Limpa
- [ ] Plugin ativa sem erros
- [ ] Menu administrativo aparece
- [ ] Assets CSS/JS carregam corretamente
- [ ] REST API routes registradas
- [ ] Nenhum PHP notice/warning/error

### Upgrade de Versão Anterior
- [ ] Dados legados preservados
- [ ] Sem perda de configurações
- [ ] Funcionalidade mantida
- [ ] Rollback possível se necessário

### Desinstalação
- [ ] uninstall.php executado corretamente
- [ ] Post meta removido de todos os posts
- [ ] Opções removidas do wp_options
- [ ] Scheduled hooks removidos
- [ ] Rewrites rules flushadas

---

**Status da Validação:** ⏳ Pendente  
**Validador:** _________________  
**Data:** ___/___/_____  
**Versão WordPress:** _______  
**Versão PHP:** _______

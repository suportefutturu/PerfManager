# LoadLess WP - Análise e Reconstrução do Plugin

## Análise do Código Original

### Funcionalidade Principal
O plugin original "Scripts and Styles Manager" permitia gerenciar scripts e styles carregados em páginas WordPress através de:
- Interface admin com React para visualizar assets
- API REST para listar páginas e assets
- Toggle para habilitar/desabilitar assets por página
- Armazenamento via post meta (`_disabled_scripts`, `_disabled_styles`)

### Falhas de Segurança Identificadas

1. **Permissões Insuficientes**
   - Usava `edit_pages` em vez de `manage_options` para operações críticas
   - Não verificava permissões consistentemente em todos os endpoints

2. **Falta de Validação de Nonce**
   - O endpoint `/toggle-asset` não verificava nonce no callback
   - Apenas o frontend enviava nonce, sem validação no backend

3. **Sanitização Inadequada**
   - Dados da requisição não eram sanitizados antes de usar
   - `sanitize_text_field` não era aplicado aos handles antes de salvar

4. **Escape de Output Ausente**
   - JavaScript inline sem escaping adequado
   - URLs não escapadas com `esc_url()`

5. **Exposição de Informações**
   - Assets do core WordPress expostos desnecessariamente
   - Sem opção para filtrar assets sensíveis

### Más Práticas de Codificação

1. **Arquitetura**
   - Uso excessivo de closures/anônimas em hooks
   - Funções soltas em namespaces em vez de classes
   - Sem padrão de design consistente

2. **Estrutura de Arquivos**
   - Arquivo principal misturava definição de constantes com includes
   - Sem separação clara de responsabilidades

3. **Código Hardcoded**
   - Valores como `per_page: 10` hardcoded no JS
   - Namespace fixo sem flexibilidade

4. **Internacionalização**
   - Text domain inconsistente
   - Strings traduzíveis ausentes em vários lugares

5. **Tratamento de Erros**
   - Sem tratamento adequado de erros na API REST
   - Mensagens de erro não internacionalizadas

6. **Documentação**
   - Comentários insuficientes
   - Sem PHPDoc adequado

---

## Melhorias Implementadas no LoadLess WP

### 1. Arquitetura Orientada a Objetos

```php
// Classe Principal Singleton
final class LoadLessWP {
    private static $instance = null;
    
    public static function get_instance(): LoadLessWP {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

**Benefícios:**
- Instância única garantida
- Prevenção contra clonagem e serialização
- Controle centralizado do ciclo de vida

### 2. Estrutura de Pastas Organizada

```
loadless-wp/
├── loadless-wp.php          # Arquivo principal
├── uninstall.php             # Limpeza na desinstalação
├── readme.txt                # Documentação WordPress.org
├── includes/
│   ├── class-admin.php       # Menu admin e assets
│   ├── class-rest-api.php    # Endpoints REST
│   ├── class-dequeue.php     # Lógica de dequeue
│   ├── class-settings.php    # Settings API
│   ├── class-blocks.php      # Renderização do bloco
│   └── class-shortcodes.php  # Shortcodes
├── blocks/
│   └── asset-manager/
│       ├── block.json        # Definição do bloco Gutenberg
│       ├── index.js          # Script do editor
│       ├── index.css         # Estilos do editor
│       └── style-index.css   # Estilos frontend
└── assets/
    ├── css/admin.css         # Estilos admin
    └── js/admin.js           # Aplicativo React admin
```

### 3. Settings API Completa

Todas as configurações agora são gerenciadas via Settings API:

```php
register_setting(
    'loadless_wp_options_group',
    'loadless_wp_enabled',
    [
        'type'              => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default'           => true,
    ]
);
```

**Opções Configuráveis:**
- `loadless_wp_enabled` - Ativar/desativar funcionalidade
- `loadless_wp_show_core_assets` - Mostrar assets do core
- `loadless_wp_default_view` - Tipo de asset padrão
- `loadless_wp_items_per_page` - Paginação (5-100)
- `loadless_wp_allowed_post_types` - Post types suportados

### 4. Segurança Reforçada

#### Nonce Verification
```php
$nonce = $request->get_header('X-WP-Nonce');
if ($nonce && !wp_verify_nonce($nonce, 'wp_rest')) {
    return new WP_Error('invalid_nonce', __('Security check failed.', 'loadless-wp'));
}
```

#### Capability Checks
```php
public function check_permissions(): bool {
    return current_user_can('manage_options');
}
```

#### Sanitização Rigorosa
```php
$args = [
    'page_id'    => [
        'required'          => true,
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
    ],
    'handle'     => [
        'required'          => true,
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ],
];
```

#### Output Escaping com wp_kses
```php
$allowed_html = [
    'div'    => ['class' => []],
    'a'      => ['href' => [], 'class' => []],
];
return wp_kses(ob_get_clean(), $allowed_html);
```

### 5. Bloco Gutenberg Nativo

**block.json:**
```json
{
  "apiVersion": 3,
  "name": "loadless-wp/asset-manager",
  "title": "LoadLess WP Asset Manager",
  "category": "widgets",
  "supports": {
    "html": false,
    "align": ["wide", "full"],
    "color": {"background": true, "text": true}
  }
}
```

**Renderização Dinâmica:**
- Callback PHP para renderização no frontend
- Compatível com SSR e caching
- Atributos configuráveis via InspectorControls

### 6. Shortcode com Fallback

```php
[loadless_wp]                    // Post atual
[loadless_wp post_id="123"]      // Post específico
[loadless_wp show_link="false"]  // Sem link admin
```

### 7. Internacionalização Completa

```php
__( 'Asset Management', 'loadless-wp' )
esc_html__( 'Manage all assets in admin', 'loadless-wp' )
sprintf(
    _n( '+%d more', '+%d more', $count, 'loadless-wp' ),
    $count
)
```

**Text Domain:** `loadless-wp`
**Domain Path:** `/languages`

### 8. Carregamento Condicional de Assets

```php
public function enqueue_assets(string $hook): void {
    // Only load on plugin pages.
    if (false === strpos($hook, 'loadless-wp')) {
        return;
    }
    
    // Admin JS only on manager page.
    if ('toplevel_page_loadless-wp-manager' === $hook) {
        wp_enqueue_script('loadless-wp-admin', ...);
    }
}
```

### 9. API REST Melhorada

**Endpoints:**
- `GET /loadless-wp/v1/pages` - Lista todas as páginas/posts
- `GET /loadless-wp/v1/assets` - Assets com paginação e filtros
- `POST /loadless-wp/v1/toggle-asset` - Toggle individual
- `POST /loadless-wp/v1/bulk-toggle` - Operações em lote

**Melhorias:**
- Validação rigorosa de parâmetros
- Mensagens de erro internacionalizadas
- Meta dados de paginação na resposta
- Filtro por tipo de asset

### 10. Código Pronto para Produção

**Características:**
- ✅ Sem erros de sintaxe PHP
- ✅ Hooks registrados corretamente
- ✅ Uninstall cleanup completo
- ✅ readme.txt padrão WordPress.org
- ✅ Versionamento de assets (cache busting)
- ✅ Suporte a SCRIPT_DEBUG
- ✅ Compatibilidade com PHP 7.4+
- ✅ Compatibilidade com WordPress 5.8+

---

## Comparação: Antes vs Depois

| Aspecto | Original | LoadLess WP |
|---------|----------|-------------|
| Arquitetura | Funcional | OOP com Singleton |
| Configurações | Hardcoded | Settings API |
| Segurança | Básica | Avançada (nonce + caps + sanitize) |
| Gutenberg | Não suportado | Bloco nativo com block.json |
| Shortcode | Não suportado | `[loadless_wp]` completo |
| i18n | Parcial | Completa com text-domain |
| Estrutura | Plana | Organizada em /includes/ |
| Documentação | Mínima | Completa (readme.txt + PHPDoc) |
| Uninstall | Básico | Limpeza completa |
| Assets | Sempre carrega | Carregamento condicional |

---

## Instalação

1. Extraia `loadless-wp.zip` em `/wp-content/plugins/`
2. Ative o plugin no WordPress
3. Acesse **LoadLess WP > Asset Manager**
4. Configure em **LoadLess WP > Settings**

## Requisitos

- WordPress 5.8 ou superior
- PHP 7.4 ou superior
- Editor Gutenberg (opcional, para uso do bloco)

## Licença

GPL v3 ou posterior

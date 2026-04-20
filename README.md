# PerfManager

**Versão:** 2.0.0  
**Requer PHP:** 8.2+  
**Requer WordPress:** 5.8+  
**Licença:** GPL v3 ou posterior  
**Text Domain:** `perfmanager`

Gerencie scripts e estilos do WordPress por página para otimizar a performance do seu site.

## 📋 Descrição

O **PerfManager** é um plugin moderno e seguro que permite visualizar e controlar quais scripts e estilos são carregados em cada página do seu site WordPress. Identifique e desative assets desnecessários para reduzir o tempo de carregamento e melhorar a experiência do usuário.

### ✨ Funcionalidades

- **Visualização completa**: Veja todos os scripts e estilos carregados em cada página
- **Controle granular**: Ative/desative assets individualmente com um simples toggle
- **Busca e paginação**: Navegue facilmente por grandes listas de assets
- **Copy-to-clipboard**: Copie URLs de assets com um clique
- **Interface responsiva**: Funciona perfeitamente em dispositivos móveis
- **Seguro por padrão**: Nonces, verificação de capacidades e sanitização rigorosa
- **Internacionalizado**: Pronto para tradução (pt_BR, en_US)
- **Retrocompatível**: Mantém dados existentes de versões anteriores

## 🚀 Instalação

### Método 1: Upload Manual

1. Baixe o arquivo ZIP do plugin
2. Acesse **Plugins → Adicionar Novo** no WordPress Admin
3. Clique em **Enviar Plugin** e selecione o arquivo ZIP
4. Clique em **Instalar Agora** e depois **Ativar**

### Método 2: FTP/SFTP

1. Extraia o arquivo ZIP do plugin
2. Faça upload da pasta `perfmanager` para `/wp-content/plugins/`
3. Acesse **Plugins** no WordPress Admin
4. Ative o **PerfManager**

### Pós-instalação

Após ativar, acesse **Assets Manager** no menu lateral do admin para começar a gerenciar seus assets.

## ⚙️ Configuração

O plugin funciona imediatamente após ativação. Não há configurações adicionais necessárias.

### Uso Básico

1. Navegue até **Assets Manager** no menu admin
2. Selecione uma página no dropdown
3. Visualize a lista de scripts e estilos carregados
4. Use os toggles para ativar/desativar assets conforme necessário
5. Os cambios são salvos automaticamente

## 🔧 Hooks e Filtros

### Actions

```php
// Executado quando um asset é atualizado
do_action('perfmanager_asset_updated', int $post_id, string $asset_type, string $handle, bool $enabled);

// Executado na limpeza semanal agendada
add_action('perfmanager_cleanup', 'my_custom_cleanup_function');
```

### Filters

```php
// Modificar capacidade necessária para gerenciar assets
add_filter('perfmanager_required_capability', function($capability) {
    return 'manage_options'; // Padrão: 'edit_pages'
});

// Excluir handles específicos da lista
add_filter('perfmanager_excluded_handles', function($handles) {
    $handles[] = 'jquery-core';
    return $handles;
});

// Modificar query de páginas listadas
add_filter('perfmanager_pages_query_args', function($args) {
    $args['post_status'] = ['publish', 'draft'];
    return $args;
});
```

## 📁 Estrutura de Diretórios

```
perfmanager/
├── perfmanager.php          # Arquivo principal do plugin
├── uninstall.php            # Script de desinstalação
├── includes/
│   └── functions.php        # Funções utilitárias globais
├── src/
│   ├── Core/
│   │   ├── Plugin.php       # Classe singleton principal
│   │   ├── Activation.php   # Lógica de ativação
│   │   ├── Deactivation.php # Lógica de desativação
│   │   └── Uninstall.php    # Lógica de desinstalação
│   ├── Admin/
│   │   └── REST/
│   │       └── AssetsController.php  # Controller REST API
│   └── Frontend/
│       └── AssetManager.php          # Gerenciador de assets frontend
├── templates/
│   └── admin/
│       └── dashboard.php    # Template da página admin
├── assets/
│   ├── css/
│   │   └── admin.css        # Estilos administrativos
│   └── js/
│       └── admin.js         # Script React administrativo
├── tests/                   # Testes unitários PHPUnit
└── languages/               # Arquivos de tradução .pot/.po/.mo
```

## 🔒 Segurança

Este plugin implementa as melhores práticas de segurança do WordPress:

- ✅ **Strict Types**: PHP 8.2+ com tipagem estrita habilitada
- ✅ **Nonces**: Verificação em todas as ações AJAX/REST
- ✅ **Capability Checks**: Verificação de permissões em cada endpoint
- ✅ **SQL Injection Prevention**: `$wpdb->prepare()` em todas as queries
- ✅ **XSS Prevention**: Escaping rigoroso em outputs (`esc_html`, `esc_attr`, `esc_url`)
- ✅ **Input Sanitization**: Sanitização de todas as entradas
- ✅ **Namespaces PSR-4**: Organização moderna de código

## 🧪 Testes

### Requisitos

- PHP 8.2+
- Composer
- PHPUnit 10+
- WordPress Test Suite

### Executar Testes

```bash
# Instalar dependências
composer install

# Executar testes
vendor/bin/phpunit

# Com coverage de código
vendor/bin/phpunit --coverage-html ./coverage
```

### Escrever Novos Testes

Os testes estão localizados em `tests/`. Siga o padrão existente:

```php
<?php

namespace PerfManager\Tests;

use WP_UnitTestCase;
use PerfManager\Core\Plugin;

class ExampleTest extends WP_UnitTestCase 
{
    public function test_example(): void 
    {
        $this->assertTrue(true);
    }
}
```

## 🌐 Internacionalização

O plugin está pronto para tradução. Para contribuir com traduções:

1. Extraia strings traduzíveis:
   ```bash
   wp i18n make-pot . languages/perfmanager.pot
   ```

2. Traduza usando Poedit ou similar
3. Envie o arquivo `.po` compilado

### Idiomas Disponíveis

- Português do Brasil (pt_BR) - Nativo
- Inglês (en_US) - Nativo

## 📝 Changelog

### 2.0.0 (Refatoração Completa)

#### ✨ Melhorias
- **PHP 8.2+**: Código reescrito completamente com strict types
- **Namespaces PSR-4**: Organização em `PerfManager\Core`, `PerfManager\Admin`, `PerfManager\Frontend`
- **Arquitetura MVC**: Separação clara entre controllers, views e models
- **REST API Moderna**: Endpoints seguros com validação rigorosa
- **React Frontend**: Interface administrativa reescrita com React hooks

#### 🔒 Segurança
- Nonces verificados em todas as ações administrativas
- Capability checks em cada endpoint
- `$wpdb->prepare()` em absolutamente todas as queries SQL
- Sanitização e escaping rigorosos em inputs e outputs
- Validação de tipos em parâmetros de API

#### 🏗️ Arquitetura
- Singleton pattern para classe principal
- Classes finais para prevenir herança indesejada
- Separação de concerns (Core, Admin, Frontend)
- Templates isolados em diretório dedicado

#### ♿ Acessibilidade
- Labels ARIA em elementos interativos
- Navegação por teclado suportada
- Contraste de cores adequado

#### 🔄 Retrocompatibilidade
- Dados legados (_disabled_scripts, _disabled_styles) migrados transparentemente
- Mesma estrutura de meta data para compatibilidade

#### 📦 Internacionalização
- Text domain alterado para `perfmanager`
- Todas as strings traduzíveis wrappers com funções dedicadas
- Suporte completo a i18n

#### 🧪 Testabilidade
- Código modular e injetável
- Pronto para testes unitários PHPUnit
- Funções utilitárias testáveis isoladamente

### 1.0.0 (Versão Original)
- Lançamento inicial
- Funcionalidade básica de gerenciamento de assets
- Interface JavaScript vanilla

## 🤝 Guia de Contribuição

### Como Contribuir

1. **Fork** o repositório
2. Crie uma branch para sua feature (`git checkout -b feature/nova-feature`)
3. **Commit** suas mudanças (`git commit -am 'Adiciona nova feature'`)
4. **Push** para a branch (`git push origin feature/nova-feature`)
5. Abra um **Pull Request**

### Padrões de Código

- **PHP**: PSR-12 com strict types
- **JavaScript**: ES6+ com strict mode
- **CSS**: BEM naming convention
- **Commits**: [Conventional Commits](https://www.conventionalcommits.org/)

### Checklist de Pull Request

- [ ] Código segue padrões PSR-12
- [ ] Strict types habilitado
- [ ] Todos os outputs escapados apropriadamente
- [ ] Nonces implementados onde necessário
- [ ] Capability checks presentes
- [ ] Queries SQL preparadas com $wpdb->prepare()
- [ ] Strings internacionalizadas com text domain
- [ ] Testes unitários adicionados/atualizados
- [ ] Documentação atualizada

### Reportar Bugs

Use a aba **Issues** do GitHub. Inclua:

- Versão do WordPress
- Versão do PHP
- Passos para reproduzir
- Comportamento esperado vs. observado
- Screenshots se aplicável

## 📄 Licença

Este plugin é licenciado sob GPL v3 ou posterior.

```
Copyright (C) 2024 Abdelhalim Khouas

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
```

## 📞 Suporte

- **Documentação**: Este arquivo README
- **Issues**: GitHub Issues
- **Email**: abdelhalimkhouas@gmail.com

## ⚠️ Aviso Importante

Desativar assets incorretamente pode quebrar funcionalidades do seu site. Sempre teste em ambiente de desenvolvimento antes de aplicar mudanças em produção.

---

**Desenvolvido com ❤️ para a comunidade WordPress**

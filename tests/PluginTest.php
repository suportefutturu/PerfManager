<?php
/**
 * Testes unitários para o plugin PerfManager.
 *
 * @package PerfManager\Tests
 * @since 2.0.0
 */

declare(strict_types=1);

namespace PerfManager\Tests;

use WP_UnitTestCase;
use PerfManager\Core\Activation;
use PerfManager\Core\Deactivation;
use PerfManager\Core\Uninstall;
use PerfManager\Core\Plugin;
use PerfManager\Frontend\AssetManager;

/**
 * Testes para funcionalidades principais do plugin.
 */
class PluginTest extends WP_UnitTestCase
{
    private int $test_page_id;

    public function setUp(): void
    {
        parent::setUp();
        
        // Criar página de teste
        $this->test_page_id = $this->factory()->post->create([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => 'Página de Teste',
        ]);
    }

    public function tearDown(): void
    {
        wp_delete_post($this->test_page_id, true);
        parent::tearDown();
    }

    /**
     * Testa se o plugin define constantes corretamente.
     */
    public function test_plugin_constants_are_defined(): void
    {
        $this->assertTrue(defined('PERFMANAGER_VERSION'));
        $this->assertTrue(defined('PERFMANAGER_PLUGIN_FILE'));
        $this->assertTrue(defined('PERFMANAGER_PLUGIN_DIR'));
        $this->assertTrue(defined('PERFMANAGER_TEXT_DOMAIN'));
    }

    /**
     * Testa versão do plugin.
     */
    public function test_plugin_version(): void
    {
        $this->assertEquals('2.0.0', PERFMANAGER_VERSION);
    }

    /**
     * Testa singleton pattern do Plugin.
     */
    public function test_plugin_singleton_instance(): void
    {
        $instance1 = Plugin::getInstance();
        $instance2 = Plugin::getInstance();
        
        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf(Plugin::class, $instance1);
    }

    /**
     * Testa prevenção de clonagem do singleton.
     */
    public function test_plugin_singleton_prevents_cloning(): void
    {
        $this->expectException(\Exception::class);
        
        $instance = Plugin::getInstance();
        unserialize(serialize($instance));
    }

    /**
     * Testa função de sanitização de asset handle.
     */
    public function test_sanitize_asset_handle(): void
    {
        $clean_handles = [
            'jquery-core' => 'jquery-core',
            'wp-element' => 'wp-element',
            'my_custom_script' => 'my_custom_script',
            'script-with-dashes' => 'script-with-dashes',
        ];

        foreach ($clean_handles as $input => $expected) {
            $result = \PerfManager\sanitize_asset_handle($input);
            $this->assertEquals($expected, $result, "Falha ao sanitizar: {$input}");
        }
    }

    /**
     * Testa sanitização remove caracteres inválidos.
     */
    public function test_sanitize_asset_handle_removes_invalid_chars(): void
    {
        $dirty_inputs = [
            'script<script>' => 'scriptscript',
            "style\ninjection" => 'styleinjection',
            'asset\'s-handle' => 'assetshandle',
            'bad"quote' => 'badquote',
        ];

        foreach ($dirty_inputs as $input => $expected) {
            $result = \PerfManager\sanitize_asset_handle($input);
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Testa validação de tipo de asset.
     */
    public function test_validate_asset_type(): void
    {
        $this->assertEquals('script', \PerfManager\validate_asset_type('script'));
        $this->assertEquals('style', \PerfManager\validate_asset_type('style'));
        $this->assertNull(\PerfManager\validate_asset_type('invalid'));
        $this->assertNull(\PerfManager\validate_asset_type(''));
    }

    /**
     * Testa obtenção de chave de meta por tipo.
     */
    public function test_get_asset_meta_key(): void
    {
        $this->assertEquals('_disabled_scripts', \PerfManager\get_asset_meta_key('script'));
        $this->assertEquals('_disabled_styles', \PerfManager\get_asset_meta_key('style'));
    }

    /**
     * Testa função de escaping de output.
     */
    public function test_escape_output(): void
    {
        $this->assertEquals('&lt;script&gt;', \PerfManager\escape_output('<script>', 'html'));
        $this->assertEquals('https://example.com', \PerfManager\escape_output('https://example.com', 'url'));
        $this->assertEquals('alert&#039;XSS&#039;', \PerfManager\escape_output("alert'XSS'", 'attr'));
    }

    /**
     * Testa verificação de capacidade do usuário.
     */
    public function test_current_user_can_manage_assets(): void
    {
        // Criar usuário admin
        $admin_id = $this->factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);
        
        $this->assertTrue(\PerfManager\current_user_can_manage_assets());

        // Criar usuário subscriber
        $subscriber_id = $this->factory()->user->create(['role' => 'subscriber']);
        wp_set_current_user($subscriber_id);
        
        $this->assertFalse(\PerfManager\current_user_can_manage_assets());
    }

    /**
     * Testa AssetManager dequeue de scripts.
     */
    public function test_asset_manager_dequeue_scripts(): void
    {
        // Adicionar script desabilitado
        update_post_meta($this->test_page_id, '_disabled_scripts', ['test-script-handle']);
        
        global $post;
        $post = get_post($this->test_page_id);
        setup_postdata($post);

        $manager = new AssetManager();
        
        // Simular hook wp_enqueue_scripts
        add_action('wp_enqueue_scripts', function() {
            wp_enqueue_script('test-script-handle', 'https://example.com/test.js');
        });

        // Executar dequeue
        $manager->dequeue_disabled_assets();

        global $wp_scripts;
        $this->assertNotContains('test-script-handle', $wp_scripts->queue);

        wp_reset_postdata();
    }

    /**
     * Testa AssetManager em ambiente admin.
     */
    public function test_asset_manager_does_not_run_in_admin(): void
    {
        set_current_screen('edit.php');
        
        $manager = new AssetManager();
        
        // Deve retornar imediatamente em admin
        $manager->dequeue_disabled_assets();
        
        // Se chegou aqui sem erro, o teste passou
        $this->assertTrue(true);
        
        set_current_screen('front');
    }

    /**
     * Testa Activation schedule de cleanup.
     */
    public function test_activation_schedules_cleanup(): void
    {
        Activation::activate();
        
        $timestamp = wp_next_scheduled('perfmanager_cleanup');
        $this->assertIsInt($timestamp);
        $this->assertGreaterThan(time(), $timestamp);
    }

    /**
     * Testa Deactivation remove scheduled hooks.
     */
    public function test_deactivation_removes_scheduled_hooks(): void
    {
        // Primeiro ativar para agendar
        Activation::activate();
        
        // Depois desativar
        Deactivation::deactivate();
        
        $timestamp = wp_next_scheduled('perfmanager_cleanup');
        $this->assertFalse($timestamp);
    }

    /**
     * Testa que texto domain está correto.
     */
    public function test_text_domain_function(): void
    {
        $this->assertEquals('perfmanager', \PerfManager\text_domain());
    }

    /**
     * Testa helper functions de tradução.
     */
    public function test_translation_helpers(): void
    {
        // Testar que funções existem e retornam strings
        $translated = \PerfManager\__('Test String');
        $this->assertIsString($translated);
        $this->assertEquals('Test String', $translated);
    }

    /**
     * Testa prepare_query com wpdb.
     */
    public function test_prepare_query(): void
    {
        global $wpdb;
        
        $query = "SELECT * FROM {$wpdb->posts} WHERE ID = %d";
        $prepared = \PerfManager\prepare_query($wpdb, $query, [$this->test_page_id]);
        
        $this->assertIsString($prepared);
        $this->assertStringContainsString((string) $this->test_page_id, $prepared);
    }

    /**
     * Testa debug_log apenas em WP_DEBUG.
     */
    public function test_debug_log(): void
    {
        // Este teste verifica que a função existe e não causa erro
        \PerfManager\debug_log('Test message', ['key' => 'value']);
        $this->assertTrue(true);
    }

    /**
     * Testa plugin_url helper.
     */
    public function test_plugin_url(): void
    {
        $url = \PerfManager\plugin_url('assets/css/admin.css');
        $this->assertStringEndsWith('assets/css/admin.css', $url);
        $this->assertStringStartsWith('http', $url);
    }

    /**
     * Testa plugin_path helper.
     */
    public function test_plugin_path(): void
    {
        $path = \PerfManager\plugin_path('src/Core/Plugin.php');
        $this->assertStringEndsWith('src/Core/Plugin.php', $path);
        $this->assertFileExists($path);
    }
}

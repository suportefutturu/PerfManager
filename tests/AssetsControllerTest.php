<?php
/**
 * Testes unitários para o REST Controller do PerfManager.
 *
 * @package PerfManager\Tests
 * @since 2.0.0
 */

declare(strict_types=1);

namespace PerfManager\Tests;

use WP_UnitTestCase;
use WP_REST_Request;
use WP_Error;
use PerfManager\Admin\REST\AssetsController;

/**
 * Testes para AssetsController REST API.
 */
class AssetsControllerTest extends WP_UnitTestCase
{
    private AssetsController $controller;
    private int $admin_id;
    private int $subscriber_id;
    private int $test_page_id;

    public function setUp(): void
    {
        parent::setUp();

        $this->controller = new AssetsController();

        // Criar usuários de teste
        $this->admin_id = $this->factory()->user->create(['role' => 'administrator']);
        $this->subscriber_id = $this->factory()->user->create(['role' => 'subscriber']);

        // Criar página de teste
        $this->test_page_id = $this->factory()->post->create([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => 'Página Teste API',
        ]);
    }

    public function tearDown(): void
    {
        wp_delete_post($this->test_page_id, true);
        wp_delete_user($this->admin_id);
        wp_delete_user($this->subscriber_id);
        parent::tearDown();
    }

    /**
     * Testa registro de rotas.
     */
    public function test_register_routes(): void
    {
        // Registrar rotas
        $this->controller->register_routes();

        global $wp_rest_server;
        $routes = $wp_rest_server->get_routes();

        $this->assertArrayHasKey('perfmanager/v1/pages', $routes);
        $this->assertArrayHasKey('perfmanager/v1/assets', $routes);
        $this->assertArrayHasKey('perfmanager/v1/toggle-asset', $routes);
    }

    /**
     * Testa permissão para endpoint pages.
     */
    public function test_get_pages_permission_admin(): void
    {
        wp_set_current_user($this->admin_id);

        $request = new WP_REST_Request('GET', '/perfmanager/v1/pages');
        $response = rest_do_request($request);

        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Testa negação de acesso para subscriber.
     */
    public function test_get_pages_permission_subscriber(): void
    {
        wp_set_current_user($this->subscriber_id);

        $request = new WP_REST_Request('GET', '/perfmanager/v1/pages');
        $response = rest_do_request($request);

        $this->assertEquals(403, $response->get_status());
    }

    /**
     * Testa obtenção de páginas publicadas.
     */
    public function test_get_pages_returns_published_pages(): void
    {
        wp_set_current_user($this->admin_id);

        $request = new WP_REST_Request('GET', '/perfmanager/v1/pages');
        $response = rest_do_request($request);
        $data = $response->get_data();

        $this->assertIsArray($data);
        
        // Verificar se nossa página de teste está na lista
        $page_ids = array_column($data, 'id');
        $this->assertContains($this->test_page_id, $page_ids);
    }

    /**
     * Testa estrutura de resposta de páginas.
     */
    public function test_get_pages_response_structure(): void
    {
        wp_set_current_user($this->admin_id);

        $request = new WP_REST_Request('GET', '/perfmanager/v1/pages');
        $response = rest_do_request($request);
        $data = $response->get_data();

        $this->assertNotEmpty($data);
        
        $first_page = $data[0];
        $this->assertArrayHasKey('id', $first_page);
        $this->assertArrayHasKey('title', $first_page);
        $this->assertIsInt($first_page['id']);
        $this->assertIsString($first_page['title']);
    }

    /**
     * Testa validação de page_id no endpoint assets.
     */
    public function test_get_assets_invalid_page_id(): void
    {
        wp_set_current_user($this->admin_id);

        $request = new WP_REST_Request('GET', '/perfmanager/v1/assets');
        $request->set_param('page_id', 99999); // Página inexistente
        $response = rest_do_request($request);

        $this->assertEquals(404, $response->get_status());
    }

    /**
     * Testa endpoint assets com página válida.
     */
    public function test_get_assets_valid_page(): void
    {
        wp_set_current_user($this->admin_id);

        $request = new WP_REST_Request('GET', '/perfmanager/v1/assets');
        $request->set_param('page_id', $this->test_page_id);
        $request->set_param('page', 1);
        $request->set_param('per_page', 10);
        
        $response = rest_do_request($request);

        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
    }

    /**
     * Testa estrutura de meta dados na resposta de assets.
     */
    public function test_get_assets_meta_structure(): void
    {
        wp_set_current_user($this->admin_id);

        $request = new WP_REST_Request('GET', '/perfmanager/v1/assets');
        $request->set_param('page_id', $this->test_page_id);
        $response = rest_do_request($request);
        
        $data = $response->get_data();
        $meta = $data['meta'];

        $this->assertArrayHasKey('total_items', $meta);
        $this->assertArrayHasKey('total_pages', $meta);
        $this->assertArrayHasKey('current_page', $meta);
        $this->assertArrayHasKey('per_page', $meta);
    }

    /**
     * Testa paginação no endpoint assets.
     */
    public function test_get_assets_pagination(): void
    {
        wp_set_current_user($this->admin_id);

        $request = new WP_REST_Request('GET', '/perfmanager/v1/assets');
        $request->set_param('page_id', $this->test_page_id);
        $request->set_param('page', 2);
        $request->set_param('per_page', 5);
        
        $response = rest_do_request($request);
        $data = $response->get_data();

        $this->assertEquals(2, $data['meta']['current_page']);
        $this->assertEquals(5, $data['meta']['per_page']);
    }

    /**
     * Testa busca no endpoint assets.
     */
    public function test_get_assets_search(): void
    {
        wp_set_current_user($this->admin_id);

        $request = new WP_REST_Request('GET', '/perfmanager/v1/assets');
        $request->set_param('page_id', $this->test_page_id);
        $request->set_param('search', 'jquery');
        
        $response = rest_do_request($request);
        $data = $response->get_data();

        // Se houver resultados, devem conter 'jquery' no handle, type ou src
        if (!empty($data['data'])) {
            foreach ($data['data'] as $asset) {
                $match_found = (
                    stripos($asset['handle'], 'jquery') !== false ||
                    stripos($asset['type'], 'jquery') !== false ||
                    stripos($asset['src'], 'jquery') !== false
                );
                $this->assertTrue($match_found, "Asset não corresponde à busca: {$asset['handle']}");
            }
        }
    }

    /**
     * Testa toggle_asset com nonce inválido.
     */
    public function test_toggle_asset_invalid_nonce(): void
    {
        wp_set_current_user($this->admin_id);

        $request = new WP_REST_Request('POST', '/perfmanager/v1/toggle-asset');
        $request->set_header('X-WP-Nonce', 'invalid-nonce');
        $request->set_param('page_id', $this->test_page_id);
        $request->set_param('asset_type', 'script');
        $request->set_param('handle', 'test-handle');
        $request->set_param('enabled', true);
        
        $response = rest_do_request($request);

        $this->assertEquals(403, $response->get_status());
    }

    /**
     * Testa toggle_asset com dados válidos.
     */
    public function test_toggle_asset_valid(): void
    {
        wp_set_current_user($this->admin_id);

        $request = new WP_REST_Request('POST', '/perfmanager/v1/toggle-asset');
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        $request->set_param('page_id', $this->test_page_id);
        $request->set_param('asset_type', 'script');
        $request->set_param('handle', 'test-script-handle');
        $request->set_param('enabled', false);
        
        $response = rest_do_request($request);

        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertContains('test-script-handle', $data['updated_assets']);

        // Verificar se foi salvo no post meta
        $disabled = get_post_meta($this->test_page_id, '_disabled_scripts', true);
        $this->assertContains('test-script-handle', $disabled);
    }

    /**
     * Testa toggle_asset habilitando asset.
     */
    public function test_toggle_asset_enable(): void
    {
        wp_set_current_user($this->admin_id);

        // Primeiro desabilitar
        update_post_meta($this->test_page_id, '_disabled_scripts', ['test-handle']);

        $request = new WP_REST_Request('POST', '/perfmanager/v1/toggle-asset');
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        $request->set_param('page_id', $this->test_page_id);
        $request->set_param('asset_type', 'script');
        $request->set_param('handle', 'test-handle');
        $request->set_param('enabled', true);
        
        $response = rest_do_request($request);

        $this->assertEquals(200, $response->get_status());
        
        // Verificar se foi removido da lista de desabilitados
        $disabled = get_post_meta($this->test_page_id, '_disabled_scripts', true);
        $this->assertNotContains('test-handle', $disabled);
    }

    /**
     * Testa toggle_asset com tipo inválido.
     */
    public function test_toggle_asset_invalid_type(): void
    {
        wp_set_current_user($this->admin_id);

        $request = new WP_REST_Request('POST', '/perfmanager/v1/toggle-asset');
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        $request->set_param('page_id', $this->test_page_id);
        $request->set_param('asset_type', 'invalid-type');
        $request->set_param('handle', 'test-handle');
        $request->set_param('enabled', true);
        
        $response = rest_do_request($request);

        $this->assertEquals(400, $response->get_status());
    }

    /**
     * Testa toggle_asset sem permissão.
     */
    public function test_toggle_asset_no_permission(): void
    {
        wp_set_current_user($this->subscriber_id);

        $request = new WP_REST_Request('POST', '/perfmanager/v1/toggle-asset');
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        $request->set_param('page_id', $this->test_page_id);
        $request->set_param('asset_type', 'script');
        $request->set_param('handle', 'test-handle');
        $request->set_param('enabled', true);
        
        $response = rest_do_request($request);

        $this->assertEquals(403, $response->get_status());
    }

    /**
     * Testa sanitização de handle no toggle.
     */
    public function test_toggle_asset_sanitizes_handle(): void
    {
        wp_set_current_user($this->admin_id);

        $malicious_handle = "test<script>alert('xss')</script>";
        $expected_handle = "testalertxss";

        $request = new WP_REST_Request('POST', '/perfmanager/v1/toggle-asset');
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        $request->set_param('page_id', $this->test_page_id);
        $request->set_param('asset_type', 'script');
        $request->set_param('handle', $malicious_handle);
        $request->set_param('enabled', false);
        
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());

        $disabled = get_post_meta($this->test_page_id, '_disabled_scripts', true);
        $this->assertContains($expected_handle, $disabled);
    }
}

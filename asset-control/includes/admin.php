<?php
/**
 * Admin functionality for Asset Control plugin
 * 
 * @package Asset_Control
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin class for handling admin pages and assets
 */
class Asset_Control_Admin {
    
    /**
     * Menu slug
     */
    const MENU_SLUG = 'asset-control';
    
    /**
     * Initialize admin hooks
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }
    
    /**
     * Add admin menu page
     */
    public static function add_admin_menu() {
        add_menu_page(
            __( 'Asset Control', 'asset-control' ),
            __( 'Asset Control', 'asset-control' ),
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'render_admin_page' ),
            'dashicons-performance',
            80
        );
        
        // Add submenu as duplicate of main page
        add_submenu_page(
            self::MENU_SLUG,
            __( 'Manage Assets', 'asset-control' ),
            __( 'Manage Assets', 'asset-control' ),
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'render_admin_page' )
        );
        
        // Settings submenu
        add_submenu_page(
            self::MENU_SLUG,
            __( 'Settings', 'asset-control' ),
            __( 'Settings', 'asset-control' ),
            'manage_options',
            self::MENU_SLUG . '-settings',
            array( __CLASS__, 'render_settings_page' )
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     * 
     * @param string $hook Current admin hook
     */
    public static function enqueue_assets( $hook ) {
        // Only load on our plugin pages
        if ( strpos( $hook, 'asset-control' ) === false ) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'asset-control-admin',
            ASSET_CONTROL_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ASSET_CONTROL_VERSION
        );
        
        // Enqueue JS
        wp_enqueue_script(
            'asset-control-admin',
            ASSET_CONTROL_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            ASSET_CONTROL_VERSION,
            true
        );
        
        // Localize script with data
        wp_localize_script( 'asset-control-admin', 'assetControlData', array(
            'apiUrl' => rest_url( 'asset-control/v1' ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'strings' => array(
                'confirmBulkAction' => __( 'Are you sure you want to perform this action on selected assets?', 'asset-control' ),
                'copySuccess' => __( 'Copied!', 'asset-control' ),
                'copyError' => __( 'Failed to copy', 'asset-control' ),
                'loading' => __( 'Loading...', 'asset-control' ),
                'noResults' => __( 'No assets found', 'asset-control' ),
                'error' => __( 'An error occurred', 'asset-control' ),
            ),
        ) );
    }
    
    /**
     * Render main admin page
     */
    public static function render_admin_page() {
        ?>
        <div class="wrap asset-control-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <div class="asset-control-content">
                <!-- Page Selection Section -->
                <div class="asset-control-section page-selector-section">
                    <h2><?php _e( 'Select Page', 'asset-control' ); ?></h2>
                    
                    <div class="page-selector-controls">
                        <div class="page-select-wrapper">
                            <label for="asset-control-page-select"><?php _e( 'Choose a page:', 'asset-control' ); ?></label>
                            <select id="asset-control-page-select" class="regular-text">
                                <option value=""><?php _e( 'Loading pages...', 'asset-control' ); ?></option>
                            </select>
                        </div>
                        
                        <div class="url-input-wrapper">
                            <label for="asset-control-url-input"><?php _e( 'Or enter URL:', 'asset-control' ); ?></label>
                            <div class="url-input-group">
                                <input type="text" id="asset-control-url-input" class="regular-text" placeholder="<?php esc_attr_e( 'https://example.com/page/', 'asset-control' ); ?>" />
                                <button type="button" id="asset-control-load-url" class="button">
                                    <?php _e( 'Load', 'asset-control' ); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="asset-control-current-page-info" class="current-page-info" style="display: none;">
                        <span class="page-title"></span>
                        <a href="#" class="view-page-link" target="_blank"><?php _e( 'View Page', 'asset-control' ); ?></a>
                    </div>
                </div>
                
                <!-- Statistics Section -->
                <div class="asset-control-section stats-section">
                    <h2><?php _e( 'Statistics', 'asset-control' ); ?></h2>
                    
                    <div class="stats-grid">
                        <div class="stat-card stat-total">
                            <span class="stat-value" id="stat-total">0</span>
                            <span class="stat-label"><?php _e( 'Total Assets', 'asset-control' ); ?></span>
                        </div>
                        <div class="stat-card stat-scripts">
                            <span class="stat-value" id="stat-scripts">0</span>
                            <span class="stat-label"><?php _e( 'Scripts', 'asset-control' ); ?></span>
                        </div>
                        <div class="stat-card stat-styles">
                            <span class="stat-value" id="stat-styles">0</span>
                            <span class="stat-label"><?php _e( 'Styles', 'asset-control' ); ?></span>
                        </div>
                        <div class="stat-card stat-enabled">
                            <span class="stat-value" id="stat-enabled">0</span>
                            <span class="stat-label"><?php _e( 'Enabled', 'asset-control' ); ?></span>
                        </div>
                        <div class="stat-card stat-disabled">
                            <span class="stat-value" id="stat-disabled">0</span>
                            <span class="stat-label"><?php _e( 'Disabled', 'asset-control' ); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Filters Section -->
                <div class="asset-control-section filters-section">
                    <div class="filters-toolbar">
                        <div class="search-wrapper">
                            <input type="text" id="asset-control-search" class="regular-text" placeholder="<?php esc_attr_e( 'Search assets...', 'asset-control' ); ?>" />
                            <span class="search-icon dashicons dashicons-search"></span>
                        </div>
                        
                        <div class="filter-type-wrapper">
                            <select id="asset-control-filter-type">
                                <option value="all"><?php _e( 'All Types', 'asset-control' ); ?></option>
                                <option value="script"><?php _e( 'Scripts Only', 'asset-control' ); ?></option>
                                <option value="style"><?php _e( 'Styles Only', 'asset-control' ); ?></option>
                            </select>
                        </div>
                        
                        <div class="per-page-wrapper">
                            <label for="asset-control-per-page"><?php _e( 'Per page:', 'asset-control' ); ?></label>
                            <select id="asset-control-per-page">
                                <option value="10">10</option>
                                <option value="20" selected>20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Assets Table Section -->
                <div class="asset-control-section assets-section">
                    <div class="assets-table-wrapper">
                        <table class="wp-list-table widefat fixed striped" id="asset-control-assets-table">
                            <thead>
                                <tr>
                                    <th class="cb-column"><input type="checkbox" id="asset-control-select-all" /></th>
                                    <th class="handle-column"><?php _e( 'Handle', 'asset-control' ); ?></th>
                                    <th class="type-column"><?php _e( 'Type', 'asset-control' ); ?></th>
                                    <th class="src-column"><?php _e( 'Source', 'asset-control' ); ?></th>
                                    <th class="size-column"><?php _e( 'Size', 'asset-control' ); ?></th>
                                    <th class="status-column"><?php _e( 'Status', 'asset-control' ); ?></th>
                                    <th class="actions-column"><?php _e( 'Actions', 'asset-control' ); ?></th>
                                </tr>
                            </thead>
                            <tbody id="asset-control-assets-tbody">
                                <tr class="loading-row">
                                    <td colspan="7" class="text-center">
                                        <span class="spinner is-active"></span>
                                        <?php _e( 'Select a page to view assets', 'asset-control' ); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="tablenav bottom" id="asset-control-pagination" style="display: none;">
                        <div class="tablenav-pages">
                            <span class="displaying-num" id="asset-control-displaying-num"></span>
                            <span class="pagination-links">
                                <button class="button" id="asset-control-first-page" disabled>&laquo;</button>
                                <button class="button" id="asset-control-prev-page" disabled>&lsaquo;</button>
                                <span class="paging-input">
                                    <input type="number" id="asset-control-current-page" class="current-page" value="1" min="1" size="4" />
                                    <span class="total-pages"> / <span id="asset-control-total-pages">1</span></span>
                                </span>
                                <button class="button" id="asset-control-next-page">&rsaquo;</button>
                                <button class="button" id="asset-control-last-page">&raquo;</button>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Empty State -->
                    <div class="empty-state" id="asset-control-empty-state" style="display: none;">
                        <span class="dashicons dashicons-plugins"></span>
                        <p><?php _e( 'No assets found for this page', 'asset-control' ); ?></p>
                    </div>
                </div>
                
                <!-- Bulk Actions -->
                <div class="asset-control-section bulk-actions-section" id="asset-control-bulk-actions" style="display: none;">
                    <div class="bulk-actions-toolbar">
                        <span class="selected-count">
                            <?php 
                            printf( 
                                _n( '%d asset selected', '%d assets selected', 0, 'asset-control' ), 
                                '<span id="asset-control-selected-count">0</span>' 
                            ); 
                            ?>
                        </span>
                        <button type="button" class="button" id="asset-control-bulk-enable">
                            <?php _e( 'Enable Selected', 'asset-control' ); ?>
                        </button>
                        <button type="button" class="button" id="asset-control-bulk-disable">
                            <?php _e( 'Disable Selected', 'asset-control' ); ?>
                        </button>
                        <button type="button" class="button" id="asset-control-clear-selection">
                            <?php _e( 'Clear Selection', 'asset-control' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public static function render_settings_page() {
        ?>
        <div class="wrap asset-control-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields( 'asset_control_settings' );
                do_settings_sections( 'asset_control_settings' );
                submit_button();
                ?>
            </form>
            
            <div class="asset-control-section">
                <h2><?php _e( 'Plugin Information', 'asset-control' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e( 'Version', 'asset-control' ); ?></th>
                        <td><?php echo esc_html( ASSET_CONTROL_VERSION ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Documentation', 'asset-control' ); ?></th>
                        <td><a href="#" target="_blank"><?php _e( 'View Documentation', 'asset-control' ); ?></a></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
}

// Initialize admin
Asset_Control_Admin::init();

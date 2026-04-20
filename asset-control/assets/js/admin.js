/**
 * Asset Control Admin JavaScript
 * 
 * @package Asset_Control
 */

(function($) {
    'use strict';

    /**
     * Main Asset Control App
     */
    var AssetControlApp = {
        
        // Current state
        currentPageId: null,
        currentPageInfo: null,
        currentPage: 1,
        perPage: 20,
        searchQuery: '',
        filterType: 'all',
        selectedAssets: [],
        allAssets: [],
        totalPages: 1,
        
        /**
         * Initialize the app
         */
        init: function() {
            this.cacheElements();
            this.bindEvents();
            this.loadPages();
        },
        
        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.$pageSelect = $('#asset-control-page-select');
            this.$urlInput = $('#asset-control-url-input');
            this.$loadUrlBtn = $('#asset-control-load-url');
            this.$currentPageInfo = $('#asset-control-current-page-info');
            this.$searchInput = $('#asset-control-search');
            this.$filterType = $('#asset-control-filter-type');
            this.$perPage = $('#asset-control-per-page');
            this.$tbody = $('#asset-control-assets-tbody');
            this.$pagination = $('#asset-control-pagination');
            this.$emptyState = $('#asset-control-empty-state');
            this.$bulkActions = $('#asset-control-bulk-actions');
            this.$selectAll = $('#asset-control-select-all');
            this.$selectCount = $('#asset-control-selected-count');
            
            // Stat elements
            this.$statTotal = $('#stat-total');
            this.$statScripts = $('#stat-scripts');
            this.$statStyles = $('#stat-styles');
            this.$statEnabled = $('#stat-enabled');
            this.$statDisabled = $('#stat-disabled');
            
            // Pagination elements
            this.$currentPageInput = $('#asset-control-current-page');
            this.$totalPages = $('#asset-control-total-pages');
            this.$firstPageBtn = $('#asset-control-first-page');
            this.$prevPageBtn = $('#asset-control-prev-page');
            this.$nextPageBtn = $('#asset-control-next-page');
            this.$lastPageBtn = $('#asset-control-last-page');
            this.$displayingNum = $('#asset-control-displaying-num');
            
            // Bulk action buttons
            this.$bulkEnableBtn = $('#asset-control-bulk-enable');
            this.$bulkDisableBtn = $('#asset-control-bulk-disable');
            this.$clearSelectionBtn = $('#asset-control-clear-selection');
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Page selection
            this.$pageSelect.on('change', function() {
                self.selectPage($(this).val());
            });
            
            // URL load
            this.$loadUrlBtn.on('click', function() {
                self.loadFromUrl();
            });
            
            this.$urlInput.on('keypress', function(e) {
                if (e.which === 13) {
                    self.loadFromUrl();
                }
            });
            
            // Search with debounce
            var searchTimeout;
            this.$searchInput.on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    self.searchQuery = self.$searchInput.val();
                    self.currentPage = 1;
                    self.loadAssets();
                }, 500);
            });
            
            // Filter type
            this.$filterType.on('change', function() {
                self.filterType = $(this).val();
                self.currentPage = 1;
                self.loadAssets();
            });
            
            // Per page
            this.$perPage.on('change', function() {
                self.perPage = parseInt($(this).val());
                self.currentPage = 1;
                self.loadAssets();
            });
            
            // Select all
            this.$selectAll.on('change', function() {
                self.toggleSelectAll($(this).prop('checked'));
            });
            
            // Pagination
            this.$firstPageBtn.on('click', function() {
                self.goToPage(1);
            });
            
            this.$prevPageBtn.on('click', function() {
                self.goToPage(self.currentPage - 1);
            });
            
            this.$nextPageBtn.on('click', function() {
                self.goToPage(self.currentPage + 1);
            });
            
            this.$lastPageBtn.on('click', function() {
                self.goToPage(self.totalPages);
            });
            
            this.$currentPageInput.on('change', function() {
                var page = parseInt($(this).val());
                if (page >= 1 && page <= self.totalPages) {
                    self.goToPage(page);
                }
            });
            
            // Bulk actions
            this.$bulkEnableBtn.on('click', function() {
                self.bulkToggle(true);
            });
            
            this.$bulkDisableBtn.on('click', function() {
                self.bulkToggle(false);
            });
            
            this.$clearSelectionBtn.on('click', function() {
                self.clearSelection();
            });
        },
        
        /**
         * Load available pages
         */
        loadPages: function() {
            var self = this;
            
            $.ajax({
                url: assetControlData.apiUrl + '/pages',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', assetControlData.nonce);
                },
                success: function(response) {
                    if (response.success) {
                        self.populatePages(response.data);
                    }
                },
                error: function() {
                    self.$pageSelect.html('<option value="">' + assetControlData.strings.error + '</option>');
                }
            });
        },
        
        /**
         * Populate page selector
         */
        populatePages: function(pages) {
            var self = this;
            var html = '<option value="">' + assetControlData.strings.loading + '</option>';
            
            // Group pages by type
            var grouped = {};
            
            _.each(pages, function(page) {
                var type = page.type || 'other';
                if (!grouped[type]) {
                    grouped[type] = [];
                }
                grouped[type].push(page);
            });
            
            // Build optgroups
            var typeLabels = {
                'front_page': assetControlData.strings.homepage || 'Homepage',
                'blog_page': assetControlData.strings.blogPage || 'Blog Page',
                'page': assetControlData.strings.pages || 'Pages',
                'post': assetControlData.strings.posts || 'Posts'
            };
            
            _.each(grouped, function(items, type) {
                var label = typeLabels[type] || type;
                html += '<optgroup label="' + label + '">';
                
                _.each(items, function(item) {
                    html += '<option value="' + item.id + '">' + self.escapeHtml(item.title) + '</option>';
                });
                
                html += '</optgroup>';
            });
            
            this.$pageSelect.html(html);
        },
        
        /**
         * Select a page and load its assets
         */
        selectPage: function(pageId) {
            if (!pageId) {
                return;
            }
            
            this.currentPageId = pageId;
            this.currentPage = 1;
            this.selectedAssets = [];
            this.updateBulkActions();
            
            // Update page info display
            var selectedOption = this.$pageSelect.find('option:selected');
            var parentOptgroup = selectedOption.parent('optgroup');
            var pageName = parentOptgroup.attr('label') + ': ' + selectedOption.text();
            
            this.$currentPageInfo.find('.page-title').text(pageName);
            this.$currentPageInfo.show();
            
            this.loadAssets();
            this.loadStats();
        },
        
        /**
         * Load from URL input
         */
        loadFromUrl: function() {
            var url = this.$urlInput.val().trim();
            
            if (!url) {
                this.showToast(assetControlData.strings.enterUrl || 'Please enter a URL', 'error');
                return;
            }
            
            // Try to match URL to a known page
            var found = false;
            this.$pageSelect.find('option').each(function() {
                var $option = $(this);
                // This would require storing URLs in data attributes
                // For now, just notify user to select from dropdown
            });
            
            if (!found) {
                this.showToast(assetControlData.strings.selectFromList || 'Please select a page from the list', 'error');
            }
        },
        
        /**
         * Load assets for current page
         */
        loadAssets: function() {
            var self = this;
            
            if (!this.currentPageId) {
                return;
            }
            
            this.showLoading();
            
            $.ajax({
                url: assetControlData.apiUrl + '/assets',
                method: 'GET',
                data: {
                    page_id: this.currentPageId,
                    page: this.currentPage,
                    per_page: this.perPage,
                    search: this.searchQuery,
                    type: this.filterType
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', assetControlData.nonce);
                },
                success: function(response) {
                    if (response.success) {
                        self.allAssets = response.data;
                        self.totalPages = response.meta.total_pages;
                        self.renderAssets(response.data);
                        self.renderPagination(response.meta);
                        self.updateStats(response.meta);
                    } else {
                        self.showError(assetControlData.strings.error);
                    }
                },
                error: function() {
                    self.showError(assetControlData.strings.error);
                }
            });
        },
        
        /**
         * Load statistics
         */
        loadStats: function() {
            var self = this;
            
            if (!this.currentPageId) {
                return;
            }
            
            $.ajax({
                url: assetControlData.apiUrl + '/stats',
                method: 'GET',
                data: {
                    page_id: this.currentPageId
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', assetControlData.nonce);
                },
                success: function(response) {
                    if (response.success) {
                        self.updateStatDisplay(response.data);
                    }
                }
            });
        },
        
        /**
         * Render assets table
         */
        renderAssets: function(assets) {
            var self = this;
            
            if (!assets || assets.length === 0) {
                this.$tbody.html('');
                this.$emptyState.show();
                this.$pagination.hide();
                return;
            }
            
            this.$emptyState.hide();
            
            var html = '';
            
            _.each(assets, function(asset) {
                var isSelected = _.contains(self.selectedAssets, asset.handle + '-' + asset.type);
                var typeClass = asset.type === 'script' ? 'script' : 'style';
                var typeLabel = asset.type === 'script' ? 
                    (assetControlData.strings.script || 'Script') : 
                    (assetControlData.strings.style || 'Style');
                
                html += '<tr data-handle="' + self.escapeHtml(asset.handle) + '" data-type="' + asset.type + '">';
                
                // Checkbox
                html += '<td class="cb-column">';
                html += '<input type="checkbox" class="asset-checkbox" value="' + self.escapeHtml(asset.handle + '-' + asset.type) + '"' + (isSelected ? ' checked' : '') + ' />';
                html += '</td>';
                
                // Handle
                html += '<td class="handle-column">';
                html += '<div class="cell-wrapper">';
                html += '<span class="asset-type-badge ' + typeClass + '">' + self.escapeHtml(typeLabel) + '</span>';
                html += '<strong>' + self.escapeHtml(asset.handle) + '</strong>';
                html += '</div>';
                html += '</td>';
                
                // Type (already shown in handle column as badge)
                
                // Source
                html += '<td class="src-column">';
                html += '<span class="src-url" title="' + self.escapeHtml(asset.full_url || asset.src) + '" data-copy="' + self.escapeHtml(asset.full_url || asset.src) + '">';
                html += self.escapeHtml(asset.src);
                html += '</span>';
                html += '</td>';
                
                // Size
                html += '<td class="size-column">';
                html += '<span class="asset-size">' + self.escapeHtml(asset.size || '-') + '</span>';
                html += '</td>';
                
                // Status toggle
                html += '<td class="status-column">';
                html += '<label class="toggle-switch">';
                html += '<input type="checkbox" class="asset-toggle"' + (asset.enabled ? ' checked' : '') + ' />';
                html += '<span class="toggle-slider"></span>';
                html += '</label>';
                html += '</td>';
                
                // Actions
                html += '<td class="actions-column">';
                html += '<button type="button" class="action-btn copy-btn" title="' + (assetControlData.strings.copyUrl || 'Copy URL') + '">';
                html += '<span class="dashicons dashicons-admin-links"></span>';
                html += '</button>';
                html += '</td>';
                
                html += '</tr>';
            });
            
            this.$tbody.html(html);
            
            // Bind row events
            this.bindRowEvents();
        },
        
        /**
         * Bind events for table rows
         */
        bindRowEvents: function() {
            var self = this;
            
            // Individual checkbox
            this.$tbody.find('.asset-checkbox').on('change', function() {
                self.updateSelection($(this).val(), $(this).prop('checked'));
            });
            
            // Toggle switch
            this.$tbody.find('.asset-toggle').on('change', function() {
                var $row = $(this).closest('tr');
                var handle = $row.data('handle');
                var type = $row.data('type');
                var enabled = $(this).prop('checked');
                
                self.toggleAsset(handle, type, enabled);
            });
            
            // Copy button
            this.$tbody.find('.copy-btn').on('click', function() {
                var $btn = $(this);
                var $src = $btn.closest('tr').find('.src-url');
                var url = $src.data('copy');
                
                self.copyToClipboard(url, $btn);
            });
            
            // Click on source URL to copy
            this.$tbody.find('.src-url').on('click', function() {
                var url = $(this).data('copy');
                var $btn = $(this).closest('tr').find('.copy-btn');
                self.copyToClipboard(url, $btn);
            });
        },
        
        /**
         * Toggle select all
         */
        toggleSelectAll: function(checked) {
            var self = this;
            
            this.$tbody.find('.asset-checkbox').each(function() {
                var $cb = $(this);
                $cb.prop('checked', checked);
                self.updateSelection($cb.val(), checked);
            });
        },
        
        /**
         * Update selection array
         */
        updateSelection: function(value, selected) {
            var index = _.indexOf(this.selectedAssets, value);
            
            if (selected && index === -1) {
                this.selectedAssets.push(value);
            } else if (!selected && index !== -1) {
                this.selectedAssets.splice(index, 1);
            }
            
            this.updateBulkActions();
        },
        
        /**
         * Update bulk actions UI
         */
        updateBulkActions: function() {
            var count = this.selectedAssets.length;
            this.$selectCount.text(count);
            
            if (count > 0) {
                this.$bulkActions.show();
            } else {
                this.$bulkActions.hide();
            }
            
            // Update select all checkbox
            var totalVisible = this.$tbody.find('.asset-checkbox').length;
            var checkedVisible = this.$tbody.find('.asset-checkbox:checked').length;
            this.$selectAll.prop('checked', totalVisible > 0 && checkedVisible === totalVisible);
        },
        
        /**
         * Clear selection
         */
        clearSelection: function() {
            this.selectedAssets = [];
            this.$tbody.find('.asset-checkbox').prop('checked', false);
            this.updateBulkActions();
        },
        
        /**
         * Bulk toggle assets
         */
        bulkToggle: function(enabled) {
            var self = this;
            
            if (this.selectedAssets.length === 0) {
                return;
            }
            
            if (!confirm(assetControlData.strings.confirmBulkAction)) {
                return;
            }
            
            var assets = _.map(this.selectedAssets, function(value) {
                var parts = value.split('-');
                var type = parts.pop();
                var handle = parts.join('-');
                return {
                    handle: handle,
                    type: type,
                    enabled: enabled
                };
            });
            
            $.ajax({
                url: assetControlData.apiUrl + '/bulk-toggle',
                method: 'POST',
                data: {
                    page_id: this.currentPageId,
                    assets: assets
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', assetControlData.nonce);
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast(response.message, 'success');
                        self.loadAssets();
                        self.loadStats();
                        self.clearSelection();
                    }
                },
                error: function() {
                    self.showToast(assetControlData.strings.error, 'error');
                }
            });
        },
        
        /**
         * Toggle single asset
         */
        toggleAsset: function(handle, type, enabled) {
            var self = this;
            
            $.ajax({
                url: assetControlData.apiUrl + '/toggle',
                method: 'POST',
                data: {
                    page_id: this.currentPageId,
                    asset_type: type,
                    handle: handle,
                    enabled: enabled
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', assetControlData.nonce);
                },
                success: function(response) {
                    if (response.success) {
                        self.loadStats();
                    }
                },
                error: function() {
                    // Revert the toggle on error
                    var $row = self.$tbody.find('tr[data-handle="' + handle + '"][data-type="' + type + '"]');
                    $row.find('.asset-toggle').prop('checked', !enabled);
                    self.showToast(assetControlData.strings.error, 'error');
                }
            });
        },
        
        /**
         * Copy to clipboard
         */
        copyToClipboard: function(text, $btn) {
            var self = this;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    self.showCopiedFeedback($btn);
                }).catch(function() {
                    self.fallbackCopy(text, $btn);
                });
            } else {
                this.fallbackCopy(text, $btn);
            }
        },
        
        /**
         * Fallback copy method
         */
        fallbackCopy: function(text, $btn) {
            var $tempInput = $('<textarea>');
            $('body').append($tempInput);
            $tempInput.val(text).select();
            
            try {
                document.execCommand('copy');
                this.showCopiedFeedback($btn);
            } catch (err) {
                this.showToast(assetControlData.strings.copyError, 'error');
            }
            
            $tempInput.remove();
        },
        
        /**
         * Show copied feedback
         */
        showCopiedFeedback: function($btn) {
            $btn.addClass('copied');
            this.showToast(assetControlData.strings.copySuccess, 'success');
            
            setTimeout(function() {
                $btn.removeClass('copied');
            }, 2000);
        },
        
        /**
         * Render pagination
         */
        renderPagination: function(meta) {
            if (meta.total_pages <= 1) {
                this.$pagination.hide();
                return;
            }
            
            this.$pagination.show();
            this.$totalPages.text(meta.total_pages);
            this.$currentPageInput.val(meta.current_page);
            this.$displayingNum.text(meta.total_items + ' items');
            
            // Update button states
            this.$firstPageBtn.prop('disabled', meta.current_page === 1);
            this.$prevPageBtn.prop('disabled', meta.current_page === 1);
            this.$nextPageBtn.prop('disabled', meta.current_page === meta.total_pages);
            this.$lastPageBtn.prop('disabled', meta.current_page === meta.total_pages);
        },
        
        /**
         * Go to specific page
         */
        goToPage: function(page) {
            if (page < 1 || page > this.totalPages) {
                return;
            }
            
            this.currentPage = page;
            this.loadAssets();
        },
        
        /**
         * Update stats display
         */
        updateStats: function(meta) {
            this.$statEnabled.text(meta.enabled_count);
            this.$statDisabled.text(meta.disabled_count);
        },
        
        /**
         * Update stat cards
         */
        updateStatDisplay: function(data) {
            this.$statTotal.text(data.total_assets);
            this.$statScripts.text(data.total_scripts);
            this.$statStyles.text(data.total_styles);
        },
        
        /**
         * Show loading state
         */
        showLoading: function() {
            this.$tbody.html('<tr class="loading-row"><td colspan="7" class="text-center"><span class="spinner is-active"></span> ' + assetControlData.strings.loading + '</td></tr>');
            this.$emptyState.hide();
            this.$pagination.hide();
        },
        
        /**
         * Show error state
         */
        showError: function(message) {
            this.$tbody.html('<tr><td colspan="7" class="text-center">' + (message || assetControlData.strings.error) + '</td></tr>');
            this.$emptyState.hide();
            this.$pagination.hide();
        },
        
        /**
         * Show toast notification
         */
        showToast: function(message, type) {
            type = type || 'info';
            
            var $toast = $('<div class="asset-control-toast ' + type + '">' + this.escapeHtml(message) + '</div>');
            $('body').append($toast);
            
            // Trigger reflow
            $toast[0].offsetHeight;
            
            $toast.addClass('show');
            
            setTimeout(function() {
                $toast.removeClass('show');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 3000);
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            if (!text) {
                return '';
            }
            
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        AssetControlApp.init();
    });
    
})(jQuery);

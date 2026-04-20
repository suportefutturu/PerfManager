/**
 * Script administrativo do PerfManager.
 * Interface React para gerenciamento de assets.
 *
 * @package PerfManager
 * @since 2.0.0
 */

(function() {
    'use strict';

    const { createElement: el, useState, useEffect, useCallback } = wp.element;
    const { __ } = wp.i18n;
    const apiFetch = wp.apiFetch;

    const settings = window.perfmanagerSettings || {};
    const API_URL = settings.apiUrl || '/wp-json/perfmanager/v1';

    /**
     * Componente principal da aplicação.
     */
    function App() {
        const [pages, setPages] = useState([]);
        const [selectedPage, setSelectedPage] = useState(null);
        const [assets, setAssets] = useState([]);
        const [loading, setLoading] = useState(false);
        const [currentPage, setCurrentPage] = useState(1);
        const [totalPages, setTotalPages] = useState(0);
        const [perPage, setPerPage] = useState(10);
        const [searchTerm, setSearchTerm] = useState('');
        const [totalItems, setTotalItems] = useState(0);

        // Carregar lista de páginas
        const loadPages = useCallback(async () => {
            try {
                const response = await apiFetch({ path: `${API_URL}/pages` });
                setPages(response);
                if (response.length > 0 && !selectedPage) {
                    setSelectedPage(response[0].id);
                }
            } catch (error) {
                console.error('Erro ao carregar páginas:', error);
            }
        }, [selectedPage]);

        // Carregar assets da página selecionada
        const loadAssets = useCallback(async () => {
            if (!selectedPage) return;

            setLoading(true);
            try {
                const path = `${API_URL}/assets?page_id=${selectedPage}&page=${currentPage}&per_page=${perPage}&search=${encodeURIComponent(searchTerm)}`;
                const response = await apiFetch({ path });
                
                setAssets(response.data || []);
                setTotalPages(response.meta?.total_pages || 0);
                setTotalItems(response.meta?.total_items || 0);
            } catch (error) {
                console.error('Erro ao carregar assets:', error);
                setAssets([]);
            } finally {
                setLoading(false);
            }
        }, [selectedPage, currentPage, perPage, searchTerm]);

        useEffect(() => {
            loadPages();
        }, [loadPages]);

        useEffect(() => {
            if (selectedPage) {
                loadAssets();
            }
        }, [selectedPage, currentPage, perPage, searchTerm, loadAssets]);

        // Alternar status de um asset
        const toggleAsset = async (assetType, handle, enabled) => {
            if (!selectedPage) return;

            try {
                await apiFetch({
                    path: `${API_URL}/toggle-asset`,
                    method: 'POST',
                    data: {
                        page_id: selectedPage,
                        asset_type: assetType,
                        handle: handle,
                        enabled: enabled,
                    },
                    headers: {
                        'X-WP-Nonce': settings.nonce || '',
                    },
                });

                // Recarregar assets após atualização
                loadAssets();
            } catch (error) {
                console.error('Erro ao atualizar asset:', error);
                alert(settings.i18n?.saveError || __('Erro ao salvar.', 'perfmanager'));
            }
        };

        // Copiar source para clipboard
        const copyToClipboard = async (text) => {
            try {
                await navigator.clipboard.writeText(text);
                showCopyNotice();
            } catch (error) {
                console.error('Erro ao copiar:', error);
            }
        };

        // Mostrar notificação de cópia
        const showCopyNotice = () => {
            const notice = document.createElement('div');
            notice.className = 'perfmanager-copy-notice';
            notice.textContent = 'Copiado!';
            document.body.appendChild(notice);
            
            setTimeout(() => {
                notice.remove();
            }, 2000);
        };

        // Handlers de eventos
        const handlePageChange = (event) => {
            setSelectedPage(parseInt(event.target.value, 10));
            setCurrentPage(1);
        };

        const handleSearchChange = (event) => {
            setSearchTerm(event.target.value);
            setCurrentPage(1);
        };

        const handlePerPageChange = (event) => {
            setPerPage(parseInt(event.target.value, 10));
            setCurrentPage(1);
        };

        const handlePageNav = (page) => {
            if (page >= 1 && page <= totalPages) {
                setCurrentPage(page);
            }
        };

        return el('div', { className: 'perfmanager-content-wrapper' }, [
            // Toolbar
            el('div', { key: 'toolbar', className: 'perfmanager-table-toolbar' }, [
                el('div', { key: 'pages', className: 'perfmanager-pages-select' }, [
                    el('label', { htmlFor: 'perfmanager-page-select' }, __('Selecionar Página:', 'perfmanager')),
                    el('select', {
                        id: 'perfmanager-page-select',
                        value: selectedPage || '',
                        onChange: handlePageChange,
                    }, pages.map(page => 
                        el('option', { key: page.id, value: page.id }, page.title + ' (#' + page.id + ')')
                    )),
                ]),
                el('div', { key: 'search', className: 'perfmanager-search-container' }, [
                    el('input', {
                        type: 'text',
                        placeholder: __('Buscar assets...', 'perfmanager'),
                        value: searchTerm,
                        onChange: handleSearchChange,
                        'aria-label': __('Buscar assets', 'perfmanager'),
                    }),
                ]),
            ]),

            // Tabela de assets
            loading 
                ? el('div', { key: 'loading', className: 'perfmanager-loading' }, [
                    el('span', { className: 'perfmanager-spinner' }),
                    el('span', {}, settings.i18n?.loading || __('Carregando...', 'perfmanager')),
                ])
                : assets.length === 0
                    ? el('div', { key: 'no-results', className: 'perfmanager-no-results' }, [
                        el('h3', {}, settings.i18n?.noAssets || __('Nenhum asset encontrado.', 'perfmanager')),
                        el('p', {}, __('Selecione outra página ou verifique se há scripts/estilos carregados.', 'perfmanager')),
                    ])
                    : [
                        el('table', { key: 'table', className: 'perfmanager-table' }, [
                            el('thead', { key: 'thead' }, [
                                el('tr', { key: 'row' }, [
                                    el('th', { key: 'handle' }, __('Nome do Asset', 'perfmanager')),
                                    el('th', { key: 'type' }, __('Tipo', 'perfmanager')),
                                    el('th', { key: 'src', className: 'column-src' }, __('Source', 'perfmanager')),
                                    el('th', { key: 'enabled' }, __('Ativo', 'perfmanager')),
                                ]),
                            ]),
                            el('tbody', { key: 'tbody' }, assets.map(asset => 
                                el('tr', { key: asset.handle }, [
                                    el('td', { key: 'handle' }, [
                                        el('div', { className: 'cell-wrapper' }, asset.handle),
                                    ]),
                                    el('td', { key: 'type' }, [
                                        el('div', { className: 'cell-wrapper' }, 
                                            asset.type === 'script' ? __('Script', 'perfmanager') : __('Estilo', 'perfmanager')
                                        ),
                                    ]),
                                    el('td', { key: 'src', className: 'column-src' }, [
                                        el('div', { 
                                            className: 'perfmanager-src-wrapper',
                                            title: asset.src,
                                            onClick: () => copyToClipboard(asset.src),
                                        }, asset.src || '-'),
                                    ]),
                                    el('td', { key: 'enabled' }, [
                                        el('div', { className: 'cell-wrapper' }, [
                                            el('label', { className: 'perfmanager-switch' }, [
                                                el('input', {
                                                    type: 'checkbox',
                                                    checked: asset.enabled,
                                                    onChange: (e) => toggleAsset(asset.type, asset.handle, e.target.checked),
                                                }),
                                                el('span', { className: 'perfmanager-slider' }),
                                            ]),
                                        ]),
                                    ]),
                                ])
                            )),
                        ]),

                        // Paginação
                        el('div', { key: 'pagination', className: 'perfmanager-pagination-section' }, [
                            el('div', { className: 'perfmanager-per-page' }, [
                                el('label', { htmlFor: 'perfmanager-per-page' }, __('Itens por página:', 'perfmanager')),
                                el('select', {
                                    id: 'perfmanager-per-page',
                                    value: perPage,
                                    onChange: handlePerPageChange,
                                }, [
                                    el('option', { key: 5, value: 5 }, '5'),
                                    el('option', { key: 10, value: 10 }, '10'),
                                    el('option', { key: 25, value: 25 }, '25'),
                                    el('option', { key: 50, value: 50 }, '50'),
                                ]),
                            ]),
                            el('div', { className: 'perfmanager-pagination-info' }, 
                                __('Página', 'perfmanager') + ` ${currentPage} ${__('de', 'perfmanager')} ${totalPages} (${totalItems} ${__('itens', 'perfmanager')})`
                            ),
                            el('div', { className: 'perfmanager-pagination' }, [
                                el('button', {
                                    key: 'first',
                                    disabled: currentPage === 1,
                                    onClick: () => handlePageNav(1),
                                    'aria-label': __('Primeira página', 'perfmanager'),
                                }, '«'),
                                el('button', {
                                    key: 'prev',
                                    disabled: currentPage === 1,
                                    onClick: () => handlePageNav(currentPage - 1),
                                    'aria-label': __('Página anterior', 'perfmanager'),
                                }, '‹'),
                                el('button', {
                                    key: 'next',
                                    disabled: currentPage === totalPages,
                                    onClick: () => handlePageNav(currentPage + 1),
                                    'aria-label': __('Próxima página', 'perfmanager'),
                                }, '›'),
                                el('button', {
                                    key: 'last',
                                    disabled: currentPage === totalPages,
                                    onClick: () => handlePageNav(totalPages),
                                    'aria-label': __('Última página', 'perfmanager'),
                                }, '»'),
                            ]),
                        ]),
                    ],
        ]);
    }

    // Renderizar aplicação quando DOM estiver pronto
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('perfmanager-app');
        if (container) {
            const root = wp.element.createRoot(container);
            root.render(el(wp.element.StrictMode, {}, el(App, {})));
        }
    });

})();

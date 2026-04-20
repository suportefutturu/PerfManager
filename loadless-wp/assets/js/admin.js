/**
 * Admin JavaScript for LoadLess WP Asset Manager
 *
 * @package LoadLessWP
 * @since 1.0.0
 */

( function( wp, document ) {
    'use strict';

    const { __, sprintf } = wp.i18n;
    const { useState, useEffect, useCallback } = wp.element;
    const { apiFetch } = wp;

    // Main App Component
    function App() {
        const [ pages, setPages ] = useState( [] );
        const [ selectedPage, setSelectedPage ] = useState( null );
        const [ assets, setAssets ] = useState( [] );
        const [ loading, setLoading ] = useState( false );
        const [ error, setError ] = useState( null );
        const [ search, setSearch ] = useState( '' );
        const [ assetType, setAssetType ] = useState( 'all' );
        const [ currentPage, setCurrentPage ] = useState( 1 );
        const [ totalPages, setTotalPages ] = useState( 1 );
        const [ totalItems, setTotalItems ] = useState( 0 );

        const perPage = window.loadlessWPSettings?.perPage || 20;
        const apiUrl = window.loadlessWPSettings?.apiUrl || '/wp-json/loadless-wp/v1';
        const nonce = window.loadlessWPSettings?.nonce || '';

        // Fetch pages on mount
        useEffect( () => {
            fetchPages();
        }, [] );

        // Fetch assets when page or filters change
        useEffect( () => {
            if ( selectedPage ) {
                fetchAssets();
            }
        }, [ selectedPage, currentPage, search, assetType ] );

        const fetchPages = async () => {
            try {
                const response = await apiFetch( {
                    path: `${ apiUrl }/pages`,
                    headers: { 'X-WP-Nonce': nonce }
                } );

                if ( response.success ) {
                    setPages( response.data );
                    if ( response.data.length > 0 && ! selectedPage ) {
                        setSelectedPage( response.data[ 0 ].id );
                    }
                }
            } catch ( err ) {
                setError( err.message || __( 'Failed to load pages.', 'loadless-wp' ) );
            }
        };

        const fetchAssets = async () => {
            setLoading( true );
            setError( null );

            try {
                const queryParams = new URLSearchParams( {
                    page_id: selectedPage,
                    page: currentPage,
                    per_page: perPage,
                    search: search,
                    asset_type: assetType
                } );

                const response = await apiFetch( {
                    path: `${ apiUrl }/assets?${ queryParams.toString() }`,
                    headers: { 'X-WP-Nonce': nonce }
                } );

                if ( response.success ) {
                    setAssets( response.data );
                    setTotalPages( response.meta.total_pages );
                    setTotalItems( response.meta.total_items );
                    setCurrentPage( response.meta.current_page );
                }
            } catch ( err ) {
                setError( err.message || __( 'Failed to load assets.', 'loadless-wp' ) );
            } finally {
                setLoading( false );
            }
        };

        const toggleAsset = async ( handle, assetType, enabled ) => {
            try {
                const response = await apiFetch( {
                    path: `${ apiUrl }/toggle-asset`,
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': nonce,
                        'Content-Type': 'application/json'
                    },
                    data: {
                        page_id: selectedPage,
                        handle: handle,
                        asset_type: assetType,
                        enabled: enabled
                    }
                } );

                if ( response.success ) {
                    // Refresh assets after toggle
                    fetchAssets();
                }
            } catch ( err ) {
                setError( err.message || __( 'Failed to update asset.', 'loadless-wp' ) );
            }
        };

        const handleSearch = useCallback( ( e ) => {
            e.preventDefault();
            setCurrentPage( 1 );
            fetchAssets();
        }, [ search, selectedPage, assetType ] );

        const handlePageChange = ( page ) => {
            if ( page >= 1 && page <= totalPages ) {
                setCurrentPage( page );
            }
        };

        return (
            <div className="loadless-wp-app">
                <h2>{ __( 'Asset Manager', 'loadless-wp' ) }</h2>

                {/* Page Selector */}
                <div className="loadless-wp-page-selector">
                    <label htmlFor="loadless-wp-page-select">
                        { __( 'Select Page:', 'loadless-wp' ) }
                    </label>
                    <select
                        id="loadless-wp-page-select"
                        value={ selectedPage || '' }
                        onChange={ ( e ) => {
                            setSelectedPage( parseInt( e.target.value, 10 ) );
                            setCurrentPage( 1 );
                        } }
                    >
                        <option value="" disabled>
                            { __( 'Choose a page...', 'loadless-wp' ) }
                        </option>
                        { pages.map( ( page ) => (
                            <option key={ page.id } value={ page.id }>
                                { page.title } ({ page.type })
                            </option>
                        ) ) }
                    </select>
                </div>

                {/* Search and Filter Controls */}
                <form onSubmit={ handleSearch } className="loadless-wp-controls">
                    <div className="loadless-wp-search">
                        <input
                            type="search"
                            placeholder={ __( 'Search assets...', 'loadless-wp' ) }
                            value={ search }
                            onChange={ ( e ) => setSearch( e.target.value ) }
                            aria-label={ __( 'Search assets', 'loadless-wp' ) }
                        />
                    </div>
                    <div className="loadless-wp-filter">
                        <select
                            value={ assetType }
                            onChange={ ( e ) => {
                                setAssetType( e.target.value );
                                setCurrentPage( 1 );
                            } }
                            aria-label={ __( 'Filter by asset type', 'loadless-wp' ) }
                        >
                            <option value="all">{ __( 'All Assets', 'loadless-wp' ) }</option>
                            <option value="script">{ __( 'Scripts Only', 'loadless-wp' ) }</option>
                            <option value="style">{ __( 'Styles Only', 'loadless-wp' ) }</option>
                        </select>
                    </div>
                    <button type="submit" className="button">
                        { __( 'Search', 'loadless-wp' ) }
                    </button>
                </form>

                {/* Error Message */}
                { error && (
                    <div className="loadless-wp-error">
                        <p>{ error }</p>
                    </div>
                ) }

                {/* Loading State */}
                { loading && (
                    <div className="loadless-wp-loading">
                        <span className="spinner is-active"></span>
                        <p>{ __( 'Loading assets...', 'loadless-wp' ) }</p>
                    </div>
                ) }

                {/* Assets Table */}
                { ! loading && assets.length > 0 && (
                    <>
                        <p className="loadless-wp-results-info">
                            { sprintf(
                                /* translators: %d: number of items */
                                __( 'Showing %d asset(s)', 'loadless-wp' ),
                                totalItems
                            ) }
                        </p>
                        <table className="loadless-wp-assets-table">
                            <thead>
                                <tr>
                                    <th>{ __( 'Handle', 'loadless-wp' ) }</th>
                                    <th>{ __( 'Type', 'loadless-wp' ) }</th>
                                    <th>{ __( 'Source', 'loadless-wp' ) }</th>
                                    <th>{ __( 'Version', 'loadless-wp' ) }</th>
                                    <th>{ __( 'Status', 'loadless-wp' ) }</th>
                                </tr>
                            </thead>
                            <tbody>
                                { assets.map( ( asset ) => (
                                    <tr key={ asset.handle }>
                                        <td>
                                            <code>{ asset.handle }</code>
                                        </td>
                                        <td>
                                            <span className={ `loadless-wp-badge loadless-wp-badge-${ asset.type }` }>
                                                { asset.type }
                                            </span>
                                        </td>
                                        <td>
                                            <code title={ asset.src }>
                                                { asset.src.length > 50
                                                    ? '...' + asset.src.slice( -47 )
                                                    : asset.src
                                                }
                                            </code>
                                        </td>
                                        <td>{ asset.version || '-' }</td>
                                        <td>
                                            <label className="loadless-wp-toggle">
                                                <input
                                                    type="checkbox"
                                                    checked={ asset.enabled }
                                                    onChange={ ( e ) => {
                                                        if ( window.confirm(
                                                            window.loadlessWPSettings?.strings?.confirmDisable ||
                                                            __( 'Disabling this asset may break functionality. Are you sure?', 'loadless-wp' )
                                                        ) ) {
                                                            toggleAsset(
                                                                asset.handle,
                                                                asset.type,
                                                                e.target.checked
                                                            );
                                                        } else {
                                                            // Reset checkbox if cancelled
                                                            e.target.checked = !e.target.checked;
                                                        }
                                                    } }
                                                    aria-label={ sprintf(
                                                        /* translators: %s: asset handle */
                                                        __( 'Toggle %s', 'loadless-wp' ),
                                                        asset.handle
                                                    ) }
                                                />
                                                <span className="loadless-wp-slider"></span>
                                            </label>
                                        </td>
                                    </tr>
                                ) ) }
                            </tbody>
                        </table>

                        {/* Pagination */}
                        { totalPages > 1 && (
                            <div className="loadless-wp-pagination">
                                <button
                                    className="button"
                                    onClick={ () => handlePageChange( 1 ) }
                                    disabled={ currentPage === 1 }
                                >
                                    { __( 'First', 'loadless-wp' ) }
                                </button>
                                <button
                                    className="button"
                                    onClick={ () => handlePageChange( currentPage - 1 ) }
                                    disabled={ currentPage === 1 }
                                >
                                    { __( 'Previous', 'loadless-wp' ) }
                                </button>
                                <span className="pagination-info">
                                    { sprintf(
                                        /* translators: 1: current page, 2: total pages */
                                        __( 'Page %1$d of %2$d', 'loadless-wp' ),
                                        currentPage,
                                        totalPages
                                    ) }
                                </span>
                                <button
                                    className="button"
                                    onClick={ () => handlePageChange( currentPage + 1 ) }
                                    disabled={ currentPage === totalPages }
                                >
                                    { __( 'Next', 'loadless-wp' ) }
                                </button>
                                <button
                                    className="button"
                                    onClick={ () => handlePageChange( totalPages ) }
                                    disabled={ currentPage === totalPages }
                                >
                                    { __( 'Last', 'loadless-wp' ) }
                                </button>
                            </div>
                        ) }
                    </>
                ) }

                {/* No Assets Message */}
                { ! loading && assets.length === 0 && selectedPage && (
                    <p className="loadless-wp-no-assets">
                        { __( 'No assets found for the selected criteria.', 'loadless-wp' ) }
                    </p>
                ) }
            </div>
        );
    }

    // Render the app
    const root = document.getElementById( 'loadless-wp-manager-root' );
    if ( root ) {
        wp.element.render( <App />, root );
    }

} )( window.wp, document );

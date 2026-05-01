/**
 * WooCommerce Hook Manager – Frontend Debug Overlay
 *
 * Renders hook markers on the front-end that look like the official
 * WooCommerce hook diagrams: dashed-border boxes with a centered hook-name
 * label on the top edge, using Hookpilot's indigo/purple brand palette.
 *
 * Strategy
 * --------
 *  1. "Area" hooks  → inject a full-width dashed box around the relevant
 *     page section (shop loop container, cart, checkout, etc.).
 *  2. "Item" hooks  → inject a slim dashed bar (inline marker) before / after
 *     each product card inside the loop.
 *  3. A floating action button opens a sidebar panel listing every hook that
 *     was successfully mapped on the current page.
 *
 * @package Hookpilot
 * @version 2.0.0
 */

/* global hkpltDebugData, jQuery */
( function ( $ ) {
    'use strict';

    /* ── Guard ─────────────────────────────────────────────────── */
    if ( typeof hkpltDebugData === 'undefined' || ! hkpltDebugData.hooks ) {
        return;
    }

    /* ============================================================
       CONFIG — Hook → DOM mapping
       Each key is a WooCommerce hook name.  The value can be:
         { type: 'area',  selectors: [...], position: 'before'|'after'|'wrap' }
         { type: 'item',  selectors: [...], position: 'before'|'after' }
       ============================================================ */
    var HOOK_MAP = {

        /* ── Global / Page-level ──────────────────────────────── */
        // NOTE: before/after_main_content are deliberately separate;
        // each gets exactly ONE marker at its correct position.
        woocommerce_before_main_content: {
            type: 'area',
            selectors: [ 'main#main', '.site-main', '.main-content', '#content', '#primary', '.woocommerce' ],
            position:  'before'
        },
        woocommerce_after_main_content: {
            type: 'area',
            selectors: [ 'main#main', '.site-main', '.main-content', '#content', '#primary', '.woocommerce' ],
            position:  'after'
        },
        woocommerce_sidebar: {
            type: 'area',
            selectors: [ '.woocommerce-sidebar', 'aside.sidebar', '.widget-area', '#secondary' ],
            position:  'before'
        },

        /* ── Shop / Archive Loop ──────────────────────────────── */
        woocommerce_before_shop_loop: {
            type: 'area',
            selectors: [ 'ul.products', 'div.products', '.products' ],
            position:  'before'
        },
        woocommerce_after_shop_loop: {
            type: 'area',
            selectors: [ 'ul.products', 'div.products', '.products' ],
            position:  'after'
        },

        /* ── Loop Item ────────────────────────────────────────── */
        woocommerce_before_shop_loop_item: {
            type: 'item',
            selectors: [ 'ul.products li.product', 'div.products li.product', '.products li.product' ],
            position:  'before'
        },
        woocommerce_after_shop_loop_item: {
            type: 'item',
            selectors: [ 'ul.products li.product', 'div.products li.product', '.products li.product' ],
            position:  'after'
        },
        woocommerce_before_shop_loop_item_title: {
            type: 'item',
            selectors: [ '.products .woocommerce-loop-product__title', '.products h2.woocommerce-loop-product__title' ],
            position:  'before'
        },
        woocommerce_after_shop_loop_item_title: {
            type: 'item',
            selectors: [ '.products .woocommerce-loop-product__title', '.products h2.woocommerce-loop-product__title' ],
            position:  'after'
        },
        woocommerce_shop_loop_item_title: {
            type: 'item',
            selectors: [ '.products .woocommerce-loop-product__title', '.products h2.woocommerce-loop-product__title' ],
            position:  'before'
        },

        /* ── Single Product ───────────────────────────────────── */
        woocommerce_before_single_product: {
            type: 'area',
            selectors: [ '.single-product div.product', '.single-product .product' ],
            position:  'before'
        },
        woocommerce_after_single_product: {
            type: 'area',
            selectors: [ '.single-product div.product', '.single-product .product' ],
            position:  'after'
        },
        woocommerce_before_single_product_summary: {
            type: 'area',
            selectors: [ '.single-product .summary.entry-summary', '.single-product .summary' ],
            position:  'before'
        },
        woocommerce_single_product_summary: {
            type: 'area',
            selectors: [ '.single-product .summary.entry-summary', '.single-product .summary' ],
            position:  'before'
        },
        woocommerce_after_single_product_summary: {
            type: 'area',
            selectors: [ '.single-product .summary.entry-summary', '.single-product .summary' ],
            position:  'after'
        },
        woocommerce_before_add_to_cart_form: {
            type: 'area',
            selectors: [ 'form.cart', '.single-product .cart' ],
            position:  'before'
        },
        woocommerce_after_add_to_cart_form: {
            type: 'area',
            selectors: [ 'form.cart', '.single-product .cart' ],
            position:  'after'
        },
        woocommerce_before_add_to_cart_button: {
            type: 'area',
            selectors: [ 'button.single_add_to_cart_button', '.single_add_to_cart_button' ],
            position:  'before'
        },
        woocommerce_after_add_to_cart_button: {
            type: 'area',
            selectors: [ 'button.single_add_to_cart_button', '.single_add_to_cart_button' ],
            position:  'after'
        },
        woocommerce_product_thumbnails: {
            type: 'area',
            selectors: [ '.woocommerce-product-gallery', '.single-product .images' ],
            position:  'before'
        },

        /* ── Cart ─────────────────────────────────────────────── */
        woocommerce_before_cart: {
            type: 'area',
            selectors: [ '.woocommerce-cart-form', 'form.woocommerce-cart-form' ],
            position:  'before'
        },
        woocommerce_after_cart: {
            type: 'area',
            selectors: [ '.cart-collaterals', '.woocommerce-cart-form' ],
            position:  'after'
        },
        woocommerce_before_cart_table: {
            type: 'area',
            selectors: [ 'table.shop_table.cart', '.woocommerce-cart-form table' ],
            position:  'before'
        },
        woocommerce_after_cart_table: {
            type: 'area',
            selectors: [ 'table.shop_table.cart', '.woocommerce-cart-form table' ],
            position:  'after'
        },
        woocommerce_before_cart_totals: {
            type: 'area',
            selectors: [ '.cart_totals' ],
            position:  'before'
        },
        woocommerce_after_cart_totals: {
            type: 'area',
            selectors: [ '.cart_totals' ],
            position:  'after'
        },
        woocommerce_before_shipping_calculator: {
            type: 'area',
            selectors: [ '.shipping-calculator-form', '.woocommerce-shipping-calculator' ],
            position:  'before'
        },
        woocommerce_after_shipping_calculator: {
            type: 'area',
            selectors: [ '.shipping-calculator-form', '.woocommerce-shipping-calculator' ],
            position:  'after'
        },
        woocommerce_proceed_to_checkout: {
            type: 'area',
            selectors: [ '.wc-proceed-to-checkout' ],
            position:  'before'
        },
        woocommerce_cart_collaterals: {
            type: 'area',
            selectors: [ '.cart-collaterals' ],
            position:  'before'
        },

        /* ── Checkout ─────────────────────────────────────────── */
        woocommerce_before_checkout_form: {
            type: 'area',
            selectors: [ 'form.checkout', '.woocommerce-checkout form.checkout' ],
            position:  'before'
        },
        woocommerce_after_checkout_form: {
            type: 'area',
            selectors: [ 'form.checkout', '.woocommerce-checkout form.checkout' ],
            position:  'after'
        },
        woocommerce_checkout_billing: {
            type: 'area',
            selectors: [ '.woocommerce-billing-fields', '#customer_details .col-1' ],
            position:  'before'
        },
        woocommerce_checkout_shipping: {
            type: 'area',
            selectors: [ '.woocommerce-shipping-fields', '#customer_details .col-2' ],
            position:  'before'
        },
        woocommerce_checkout_order_review: {
            type: 'area',
            selectors: [ '#order_review', '.woocommerce-checkout-review-order' ],
            position:  'before'
        },

        /* ── Account ──────────────────────────────────────────── */
        woocommerce_before_account_navigation: {
            type: 'area',
            selectors: [ '.woocommerce-MyAccount-navigation' ],
            position:  'before'
        },
        woocommerce_after_account_navigation: {
            type: 'area',
            selectors: [ '.woocommerce-MyAccount-navigation' ],
            position:  'after'
        },
        woocommerce_account_content: {
            type: 'area',
            selectors: [ '.woocommerce-MyAccount-content' ],
            position:  'before'
        },

        /* ── Thank-you ────────────────────────────────────────── */
        woocommerce_before_thankyou: {
            type: 'area',
            selectors: [ '.woocommerce-order', '.woocommerce-thankyou-order-details' ],
            position:  'before'
        },
        woocommerce_thankyou: {
            type: 'area',
            selectors: [ '.woocommerce-order-details' ],
            position:  'before'
        },
        woocommerce_after_thankyou: {
            type: 'area',
            selectors: [ '.woocommerce-customer-details', '.woocommerce-order-details' ],
            position:  'after'
        }
    };

    /* ============================================================
       Helpers
       ============================================================ */

    /**
     * Find the first existing DOM element from a selector list.
     * Returns a jQuery object (may be empty).
     */
    function findFirst( selectors ) {
        for ( var i = 0; i < selectors.length; i++ ) {
            var $el = $( selectors[ i ] ).first();
            if ( $el.length ) {
                return $el;
            }
        }
        return $();
    }

    /**
     * Find ALL matching elements from a selector list (used for loop items).
     */
    function findAll( selectors ) {
        return $( selectors.join( ', ' ) );
    }

    /**
     * Build a full-width area box with a centered label.
     *
     * @param  {string} hookName
     * @return {jQuery}
     */
    function makeAreaBox( hookName ) {
        var $box   = $( '<div class="hkplt-area-box"></div>' );
        var $label = $( '<span class="hkplt-area-label"></span>' ).text( hookName );
        $box.append( $label );
        return $box;
    }

    /**
     * Build a slim inline bar with the hook name centered.
     *
     * @param  {string} hookName
     * @return {jQuery}
     */
    function makeInlineMarker( hookName ) {
        var $bar   = $( '<div class="hkplt-inline-marker"></div>' ).attr( 'data-hook', hookName );
        var $text  = $( '<span class="hkplt-inline-text"></span>' ).text( hookName );
        $bar.append( $text );
        return $bar;
    }

    /**
     * Return a friendly callback count summary.
     *
     * @param  {Array} callbacks
     * @return {string}
     */
    function cbCount( callbacks ) {
        return callbacks ? callbacks.length : 0;
    }

    /* ============================================================
       Core Injection
       ============================================================ */
    var mappedHooks = []; // { name, count } – for panel list

    /**
     * Check if the current page is a WooCommerce page.
     * Uses body classes added by WooCommerce.
     */
    var WC_BODY_CLASSES = [
        'woocommerce',
        'woocommerce-page',
        'single-product',
        'woocommerce-cart',
        'woocommerce-checkout',
        'woocommerce-account',
        'post-type-archive-product',
        'tax-product_cat',
        'tax-product_tag'
    ];

    function isWooCommercePage() {
        // 1. Strict check for official WC page body classes
        for ( var i = 0; i < WC_BODY_CLASSES.length; i++ ) {
            if ( document.body.classList && document.body.classList.contains( WC_BODY_CLASSES[i] ) ) {
                return true;
            }
        }
        
        // 2. Check if there are ANY actual WooCommerce products/elements on the page
        // This ensures hooks display on a homepage using [products] shortcodes 
        // even if the body lacks the specific WC page classes.
        if ( $( '.woocommerce, .woocommerce-page, .products, .product' ).length > 0 ) {
            return true;
        }
        
        return false;
    }

    /**
     * Hooks that should ONLY render on WooCommerce pages (page-level wrappers).
     * These use broad selectors like .site-main that exist on every page,
     * so they must be restricted to WC pages to avoid showing on blogs etc.
     */
    var WC_ONLY_HOOKS = [
        'woocommerce_before_main_content',
        'woocommerce_after_main_content',
        'woocommerce_sidebar'
    ];

    function renderHooks() {
        var hooksData = hkpltDebugData.hooks;
        var isWCPage  = isWooCommercePage();

        $.each( HOOK_MAP, function ( hookName, config ) {
            // Skip page-level hooks on non-WooCommerce pages (e.g. blog)
            if ( !isWCPage && WC_ONLY_HOOKS.indexOf( hookName ) !== -1 ) {
                return; // continue to next hook
            }
            // Only render if the hook actually has callbacks (exists in data).
            var callbacks = hooksData[ hookName ];
            // We still render the placeholder even if there are 0 callbacks,
            // because the hook position on screen is what matters visually.
            // Comment the next line out if you prefer to hide empty hooks.
            // if ( ! callbacks || ! callbacks.length ) return;

            var mapped = false;

            if ( config.type === 'area' ) {
                /* ── Full-width dashed area box ─────────────── */
                var $target = findFirst( config.selectors );
                if ( ! $target.length ) return;

                var $box = makeAreaBox( hookName );

                if ( config.position === 'before' ) {
                    $box.insertBefore( $target );
                    mapped = true;
                } else if ( config.position === 'after' ) {
                    $box.insertAfter( $target );
                    mapped = true;
                }

            } else if ( config.type === 'item' ) {
                /* ── Per-product-card inline bars ─────────── */
                var $items = findAll( config.selectors );
                if ( ! $items.length ) return;

                $items.each( function () {
                    var $item   = $( this );
                    var $marker = makeInlineMarker( hookName );

                    if ( config.position === 'before' ) {
                        $item.prepend( $marker );
                    } else {
                        $item.append( $marker );
                    }
                } );
                mapped = true;
            }

            if ( mapped ) {
                mappedHooks.push( {
                    name:  hookName,
                    count: cbCount( callbacks )
                } );
            }
        } );
    }

    /* ============================================================
       Build Floating Panel — shows active plugin RULES
       ============================================================ */

    /**
     * Human-readable label + colour for each rule status/type.
     */
    var RULE_TYPE_META = {
        disable:        { label: hkpltDebugData.strings.disabled,    color: '#ef4444' },
        priority:       { label: hkpltDebugData.strings.priority,    color: '#f59e0b' },
        wrapper:        { label: hkpltDebugData.strings.wrapper,     color: '#8b5cf6' },
        custom_content: { label: hkpltDebugData.strings.custom_html, color: '#06b6d4' },
        shortcode:      { label: hkpltDebugData.strings.shortcode,   color: '#10b981' },
        active:         { label: hkpltDebugData.strings.active,      color: '#4f46e5' }
    };

    function getRuleTypeMeta( status ) {
        return RULE_TYPE_META[ status ] || { label: status, color: '#6b7280' };
    }

    /**
     * Returns true if a hook's DOM target(s) exist on the current page.
     *
     * Strategy:
     *  1. If the hook is in HOOK_MAP, check whether any of its CSS selectors
     *     match a live element. This is the most accurate test.
     *  2. Fallback: check if the hook has at least one registered callback
     *     in hkpltDebugData.hooks (means WordPress fired/registered it here).
     *
     * @param  {string} hookName
     * @return {boolean}
     */
    function isHookOnCurrentPage( hookName ) {
        if ( HOOK_MAP[ hookName ] ) {
            var selectors = HOOK_MAP[ hookName ].selectors;
            for ( var i = 0; i < selectors.length; i++ ) {
                if ( $( selectors[ i ] ).length ) {
                    return true;
                }
            }
            // Selectors are defined but nothing matched — hook isn't on this page.
            return false;
        }

        // Not in HOOK_MAP: fall back to checking if WP registered any callbacks.
        var callbacks = hkpltDebugData.hooks[ hookName ];
        return !! ( callbacks && callbacks.length );
    }

    function buildPanel() {
        // Filter to rules whose hook is actually present on the current page.
        var allRules = ( hkpltDebugData.rules && hkpltDebugData.rules.length ) ? hkpltDebugData.rules : [];
        var rules    = allRules.filter( function ( rule ) {
            return isHookOnCurrentPage( rule.hook_name );
        } );
        var count   = rules.length;

        var $panel = $(
            '<div id="hkplt-debug-panel">' +
                '<div class="hkplt-panel-header">' +
                    '<span class="hkplt-panel-title">' +
                        '<span class="hkplt-panel-title-icon">⚙️</span>' +
                        hkpltDebugData.strings.panel_title +
                    '</span>' +
                    '<span class="hkplt-panel-count">' + count + ' ' + ( count === 1 ? hkpltDebugData.strings.rule : hkpltDebugData.strings.rules ) + '</span>' +
                '</div>' +
                '<ul class="hkplt-panel-list" id="hkplt-panel-list"></ul>' +
            '</div>'
        );

        var $list = $panel.find( '#hkplt-panel-list' );

        if ( ! count ) {
            $list.append(
                '<li class="hkplt-panel-empty">' +
                    '<span class="hkplt-panel-empty-icon">📋</span>' +
                    '<span>' + hkpltDebugData.strings.no_rules + '</span>' +
                '</li>'
            );
        }

        $.each( rules, function ( i, rule ) {
            var meta     = getRuleTypeMeta( rule.status );
            var subLabel = '';

            if ( rule.callback_name ) {
                subLabel = rule.callback_name;
            } else if ( rule.wrapper_class ) {
                subLabel = '.' + rule.wrapper_class;
            } else if ( rule.wrapper_tag ) {
                subLabel = '<' + rule.wrapper_tag + '>';
            }

            var priorityInfo = '';
            if ( rule.status === 'priority' ) {
                priorityInfo =
                    '<span class="hkplt-rule-priority">' +
                        rule.old_priority + ' → ' + rule.priority +
                    '</span>';
            } else if ( rule.priority ) {
                priorityInfo =
                    '<span class="hkplt-rule-priority">p' + rule.priority + '</span>';
            }

            var $li = $(
                '<li class="hkplt-panel-item">' +
                    '<span class="hkplt-panel-num">' + ( i + 1 ) + '</span>' +
                    '<span class="hkplt-panel-rule-body">' +
                        '<span class="hkplt-panel-hook-name">' + rule.hook_name + '</span>' +
                        ( subLabel
                            ? '<span class="hkplt-rule-sub">' + subLabel + '</span>'
                            : '' ) +
                    '</span>' +
                    '<span class="hkplt-rule-badges">' +
                        priorityInfo +
                        '<span class="hkplt-rule-type-badge" style="background:' + meta.color + '">' +
                            meta.label +
                        '</span>' +
                    '</span>' +
                '</li>'
            );
            $list.append( $li );
        } );

        $( 'body' ).append( $panel );
        return $panel;
    }

    /* ============================================================
       Build Floating Toggle Button
       ============================================================ */
    function buildToggle( $panel ) {
        var $btn = $(
            '<button id="hkplt-debug-toggle-btn" title="Hookpilot Debug Overlay">' +
                '<span class="hkplt-toggle-icon">🔗</span>' +
                '<span class="hkplt-toggle-label">Hookpilot</span>' +
            '</button>'
        );

        $( 'body' ).append( $btn );

        $btn.on( 'click', function () {
            $panel.toggleClass( 'hkplt-panel--open' );
        } );
    }

    /* ============================================================
       Init
       ============================================================ */
    $( document ).ready( function () {
        renderHooks();
        var $panel = buildPanel();
        buildToggle( $panel );
    } );

} )( jQuery );

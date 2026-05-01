/**
 * WooCommerce Hook Manager – Admin Bar Script
 *
 * Handles the debug-mode toggle that appears in the WordPress admin bar
 * on every page (frontend and backend) for logged-in administrators.
 * Updates the admin bar label live via AJAX without a full page reload.
 *
 * @package Hookpilot
 * @version 1.0.0
 */

/* global hkpltBarData, jQuery */
( function ( $ ) {
    'use strict';

    if ( typeof hkpltBarData === 'undefined' ) {
        return;
    }

    $( document ).ready( function () {

        function updateBarLabel( isEnabled ) {
            var $switch = $( '#wp-admin-bar-hkplt-debug-bar .hkplt-ab-switch' );
            var $action = $( '#wp-admin-bar-hkplt-debug-bar-toggle > .ab-item' );

            if ( isEnabled ) {
                $switch.removeClass( 'is-off' ).addClass( 'is-on' );
                $action.text( hkpltBarData.strings.disabled_label );
            } else {
                $switch.removeClass( 'is-on' ).addClass( 'is-off' );
                $action.text( hkpltBarData.strings.enabled_label );
            }
        }

        /**
         * Toggle debug mode via AJAX and update the UI immediately.
         *
         * @param {Event} e Click event.
         */
        function handleToggle( e ) {
            e.preventDefault();

            // Read current state from the live label.
            var currentlyOn = $( '#wp-admin-bar-hkplt-debug-bar .hkplt-ab-switch' ).hasClass( 'is-on' );
            var newState    = currentlyOn ? 0 : 1;

            // Optimistic UI update.
            updateBarLabel( newState );

            $.ajax( {
                url:  hkpltBarData.ajax_url,
                type: 'POST',
                data: {
                    action:  'hkplt_toggle_debug',
                    nonce:   hkpltBarData.nonce,
                    enabled: newState
                },
                success: function ( response ) {
                    if ( response.success ) {
                        // Reload the page to apply/remove the debug overlays.
                        window.location.reload();
                    } else {
                        // Rollback on failure.
                        updateBarLabel( currentlyOn ? 1 : 0 );
                    }
                },
                error: function () {
                    // Rollback on network error.
                    updateBarLabel( currentlyOn ? 1 : 0 );
                }
            } );
        }

        // Bind to the toggle sub-menu item AND the parent admin bar node.
        $( document ).on( 'click', '#wp-admin-bar-hkplt-debug-bar-toggle a', handleToggle );
        $( document ).on( 'click', '#wp-admin-bar-hkplt-debug-bar > .ab-item', handleToggle );

    } );

} )( jQuery );

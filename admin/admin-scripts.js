/**
 * WooCommerce Hook Manager – Admin Scripts
 *
 * Features:
 *  - Hook Inspector AJAX loader
 *  - Add Rule form with dynamic fields
 *  - Edit Rule modal (pre-populated, update via AJAX)
 *  - Card expand/collapse on manager page
 *  - Delete with confirmation
 *  - Debug toggle (settings page)
 *  - Export generation, copy, download
 *  - Shortcode clipboard copy
 *  - Reset all settings
 *
 * @package Hookpilot
 * @version 1.0.0
 */

/* global hkpltData, jQuery */
( function ( $ ) {
    'use strict';

    var hkplt = {

        init: function () {
            hkplt.bindInspector();
            hkplt.bindTypeCards();
            hkplt.bindCallbackLoader();
            hkplt.bindTagPicker();
            hkplt.bindPriorityPresets();
            hkplt.bindWrapperPreview();
            hkplt.bindAddHookForm();
            hkplt.bindEditModal();
            hkplt.bindDeleteButtons();
            hkplt.bindDebugToggle();
            hkplt.bindUninstallToggle();
            hkplt.bindExport();
            hkplt.bindCopyButtons();
            hkplt.bindResetSettings();
            hkplt.bindImportExport();
        },

        /* ----------------------------------------------------------------
         * Utility: show a top notice bar
         * ---------------------------------------------------------------- */
        showNotice: function ( message, type ) {
            var $n = $( '#hkplt-notice' );
            $n.removeClass( 'hkplt-notice--success hkplt-notice--error' )
              .addClass( 'hkplt-notice--' + ( type || 'success' ) )
              .text( message )
              .show();
            setTimeout( function () { $n.fadeOut(); }, 4500 );
        },

        /* ----------------------------------------------------------------
         * Hook Inspector
         * ---------------------------------------------------------------- */
        bindInspector: function () {
            var $btn    = $( '#hkplt-load-inspector' );
            var $wrap   = $( '#hkplt-inspector-table-wrap' );
            var $search = $( '#hkplt-inspector-search' );

            if ( ! $btn.length ) { return; }

            $btn.on( 'click', function () {
                $btn.prop( 'disabled', true ).text( hkpltData.strings.loading );
                $wrap.html( '<p class="hkplt-placeholder">' + hkpltData.strings.loading + '</p>' );

                $.ajax( {
                    url:  hkpltData.ajax_url,
                    type: 'POST',
                    data: { action: 'hkplt_get_hooks', nonce: hkpltData.nonce },
                    success: function ( response ) {
                        $btn.prop( 'disabled', false ).text( hkpltData.strings.reload_hooks );
                        if ( response.success ) {
                            hkplt.renderInspectorTable( response.data );
                        } else {
                            $wrap.html( '<p class="hkplt-placeholder">' + hkplt.escHtml( response.data.message || hkpltData.strings.error ) + '</p>' );
                        }
                    },
                    error: function () {
                        $btn.prop( 'disabled', false ).text( hkpltData.strings.load_hooks );
                        $wrap.html( '<p class="hkplt-placeholder">' + hkpltData.strings.error + '</p>' );
                    }
                } );
            } );

            $( document ).on( 'input', '#hkplt-inspector-search', function () {
                var q = $( this ).val().toLowerCase();
                $( '.hkplt-inspector-hook-section' ).each( function () {
                    $( this ).toggle( ( $( this ).data( 'hook' ) || '' ).indexOf( q ) !== -1 );
                } );
            } );
        },

        renderInspectorTable: function ( data ) {
            var $wrap = $( '#hkplt-inspector-table-wrap' );
            var html  = '';
            var empty = true;

            $.each( data, function ( hookName, callbacks ) {
                if ( ! callbacks.length ) { return; }
                empty = false;

                callbacks.sort( function ( a, b ) { return a.priority - b.priority; } );

                html += '<div class="hkplt-inspector-hook-section" data-hook="' + hookName + '">';
                html += '<div class="hkplt-inspector-hook-name">'
                      + hookName
                      + '<span class="hkplt-hook-type">' + ( callbacks[ 0 ] ? callbacks[ 0 ].type : 'Action' ) + '</span>'
                      + '</div>';
                html += '<table class="wp-list-table widefat fixed striped hkplt-table">'
                      + '<thead><tr><th>Priority</th><th>Callback</th><th>Args</th><th>Source</th></tr></thead><tbody>';

                $.each( callbacks, function ( i, cb ) {
                    html += '<tr>'
                          + '<td><strong>' + parseInt( cb.priority ) + '</strong></td>'
                          + '<td><code>' + hkplt.escHtml( cb.callback ) + '</code></td>'
                          + '<td>' + parseInt( cb.accepted_args ) + '</td>'
                          + '<td>' + hkplt.escHtml( cb.source ) + '</td>'
                          + '</tr>';
                } );

                html += '</tbody></table></div>';
            } );

            $wrap.html( empty ? '<p class="hkplt-placeholder">No registered callbacks found. Ensure WooCommerce is active and a shop/product page has been visited.</p>' : html );
        },

        /* ----------------------------------------------------------------
         * Rule Type Cards (radio buttons styled as cards)
         * ---------------------------------------------------------------- */
        bindTypeCards: function () {
            // Add-rule page: radio cards
            $( document ).on( 'change', '.hkplt-rule-type-radio', function () {
                var val = $( this ).val();
                $( '.hkplt-type-card' ).removeClass( 'is-active' );
                $( this ).closest( '.hkplt-type-card' ).addClass( 'is-active' );
                hkplt.showRuleSections( val, '.hkplt-rule-section' );
            } );

            // Edit modal: select dropdown
            $( document ).on( 'change', '.hkplt-edit-type-select', function () {
                hkplt.showEditSections( $( this ).val() );
            } );

            // Init on page load (default = disable)
            hkplt.showRuleSections( 'disable', '.hkplt-rule-section' );
        },

        showRuleSections: function ( val, ctx ) {
            $( ctx ).hide().find( 'input, select, textarea' ).prop( 'disabled', true );
            var map = {
                disable:        '#section-callback',
                priority:       '#section-priority',
                wrapper:        '#section-wrapper',
                custom_content: '#section-content',
                shortcode:      '#section-shortcode'
            };
            if ( map[ val ] ) {
                $( map[ val ] ).show().find( 'input, select, textarea' ).prop( 'disabled', false );
            }
        },

        showEditSections: function ( val ) {
            $( '.hkplt-edit-section' ).hide().find( 'input, select, textarea' ).prop( 'disabled', true );
            
            // callback section visible for both disable and priority
            if ( val === 'disable' || val === 'priority' ) { 
                $( '#edit-section-callback' ).show().find( 'input, select, textarea' ).prop( 'disabled', false ); 
            }
            if ( val === 'priority' ) { 
                $( '#edit-section-priority' ).show().find( 'input, select, textarea' ).prop( 'disabled', false ); 
            }
            if ( val === 'wrapper' ) { 
                $( '#edit-section-wrapper' ).show().find( 'input, select, textarea' ).prop( 'disabled', false ); 
            }
            if ( val === 'custom_content' ) { 
                $( '#edit-section-content' ).show().find( 'input, select, textarea' ).prop( 'disabled', false ); 
            }
            if ( val === 'shortcode' ) { 
                $( '#edit-section-shortcode' ).show().find( 'input, select, textarea' ).prop( 'disabled', false ); 
            }
        },

        /* ----------------------------------------------------------------
         * AJAX Callback Loader – populates selects when hook is chosen
         * ---------------------------------------------------------------- */
        bindCallbackLoader: function () {
            $( document ).on( 'change', '.hkplt-hook-select', function () {
                var hookName = $( this ).val();
                if ( ! hookName ) { return; }

                var $disable  = $( '#hkplt-callback-select' );
                var $priority = $( '#hkplt-priority-callback-select' );
                var $spin     = $( '#hkplt-cb-spinner' );

                $disable.html( '<option value="">— Loading… —</option>' );
                $priority.html( '<option value="">— Loading… —</option>' );
                $spin.show();

                $.ajax( {
                    url:  hkpltData.ajax_url,
                    type: 'POST',
                    data: { action: 'hkplt_get_hook_callbacks', nonce: hkpltData.nonce, hook_name: hookName },
                    success: function ( response ) {
                        $spin.hide();
                        if ( response.success && response.data.callbacks.length ) {
                            var opts = '<option value="">— Select callback —</option>';
                            $.each( response.data.callbacks, function ( i, cb ) {
                                var label = cb.callback + ' (p' + cb.priority + ( cb.source ? ', ' + cb.source : '' ) + ')';
                                opts += '<option value="' + hkplt.escHtml( cb.callback ) + '" data-priority="' + cb.priority + '">' + hkplt.escHtml( label ) + '</option>';
                            } );
                            $disable.html( opts );
                            $priority.html( opts );
                        } else {
                            var msg = '<option value="">— No callbacks found —</option>';
                            $disable.html( msg );
                            $priority.html( msg );
                        }
                    },
                    error: function () {
                        $spin.hide();
                        var err = '<option value="">— Error loading —</option>';
                        $disable.html( err );
                        $priority.html( err );
                    }
                } );
            } );

            // Auto-fill old priority when callback is selected
            $( document ).on( 'change', '#hkplt-priority-callback-select', function () {
                var prio = $( this ).find( ':selected' ).data( 'priority' );
                if ( prio !== undefined ) {
                    $( '#hkplt-old-priority' ).val( prio );
                }
            } );
        },

        /* ----------------------------------------------------------------
         * HTML Tag visual picker
         * ---------------------------------------------------------------- */
        bindTagPicker: function () {
            $( document ).on( 'click', '.hkplt-tag-btn', function () {
                $( this ).closest( '.hkplt-tag-picker' ).find( '.hkplt-tag-btn' ).removeClass( 'is-active' );
                $( this ).addClass( 'is-active' );
                var tag = $( this ).data( 'tag' );
                $( '#hkplt-wrapper-tag' ).val( tag );
                hkplt.updateWrapperPreview();
            } );
        },

        /* ----------------------------------------------------------------
         * Live wrapper preview
         * ---------------------------------------------------------------- */
        bindWrapperPreview: function () {
            $( document ).on( 'input', '#hkplt-wrapper-class, #hkplt-wrapper-attrs, [name="setting[wrapper_id]"]', function () {
                hkplt.updateWrapperPreview();
            } );
        },

        updateWrapperPreview: function () {
            var tag   = $( '#hkplt-wrapper-tag' ).val() || 'div';
            var cls   = $( '#hkplt-wrapper-class' ).val();
            var id    = $( '[name="setting[wrapper_id]"]' ).val();
            var attrs = $( '#hkplt-wrapper-attrs' ).val();
            var open  = '<' + tag;
            if ( cls )   { open += ' class="' + cls + '"'; }
            if ( id )    { open += ' id="' + id + '"'; }
            if ( attrs ) { open += ' ' + attrs; }
            open += '>';
            $( '#hkplt-wrapper-preview-code' ).text( open + ' … </' + tag + '>' );
        },

        /* ----------------------------------------------------------------
         * Priority quick-pick presets
         * ---------------------------------------------------------------- */
        bindPriorityPresets: function () {
            $( document ).on( 'click', '.hkplt-preset-btn', function () {
                var target = $( this ).data( 'target' );
                var val    = $( this ).data( 'val' );
                $( '#' + target ).val( val );
            } );
        },



        /* ----------------------------------------------------------------
         * Edit Modal
         * ---------------------------------------------------------------- */
        bindEditModal: function () {
            if ( ! $( '#hkplt-edit-modal' ).length ) { return; }

            // Open modal and populate.
            $( document ).on( 'click', '.hkplt-btn-edit', function ( e ) {
                e.stopPropagation();
                var id      = parseInt( $( this ).data( 'id' ) );
                var setting = hkpltData.settings && hkpltData.settings[ id ] ? hkpltData.settings[ id ] : null;

                if ( ! setting ) {
                    hkplt.showNotice( 'Rule data not found. Please refresh the page.', 'error' );
                    return;
                }

                hkplt.populateEditForm( id, setting );
                hkplt.openModal();
            } );

            // Close modal.
            $( document ).on( 'click', '.hkplt-modal__close, .hkplt-modal__backdrop', function () {
                hkplt.closeModal();
            } );

            // ESC key closes.
            $( document ).on( 'keydown', function ( e ) {
                if ( e.key === 'Escape' ) { hkplt.closeModal(); }
            } );

            // Submit edit form.
            $( '#hkplt-edit-save' ).on( 'click', function () {
                hkplt.submitEditForm();
            } );
        },

        openModal: function () {
            $( '#hkplt-edit-modal' ).fadeIn( 150 );
            $( 'body' ).addClass( 'hkplt-modal-open' );
        },

        closeModal: function () {
            $( '#hkplt-edit-modal' ).fadeOut( 150 );
            $( 'body' ).removeClass( 'hkplt-modal-open' );
        },

        populateEditForm: function ( id, s ) {
            $( '#hkplt-edit-id' ).val( id );
            $( '#hkplt-edit-hook-name' ).val( s.hook_name || '' );
            $( '#hkplt-edit-rule-type' ).val( s.status || 'disable' );
            $( '#hkplt-edit-callback' ).val( s.callback_name || '' );
            $( '#hkplt-edit-old-priority' ).val( s.old_priority || 10 );
            
            // Set priority for ALL section priority inputs
            $( '.hkplt-priority-input' ).val( s.priority || 10 );
            
            // Wrapper tag picker
            var tag = s.wrapper_tag || 'div';
            $( '#hkplt-edit-wrapper-tag' ).val( tag );
            $( '#hkplt-edit-modal .hkplt-tag-btn' ).removeClass( 'is-active' ).filter( '[data-tag="' + tag + '"]' ).addClass( 'is-active' );
            
            $( '#hkplt-edit-wrapper-class' ).val( s.wrapper_class || '' );
            $( '#hkplt-edit-wrapper-attrs' ).val( s.wrapper_attrs || '' );
            $( '#hkplt-edit-content' ).val( s.custom_content || '' );
            $( '#hkplt-edit-shortcode' ).val( s.shortcode_content || '' );
            
            // Show correct sections (this also handles disabling the hidden ones)
            hkplt.showEditSections( s.status || 'disable' );
        },

        submitEditForm: function () {
            var $btn     = $( '#hkplt-edit-save' );
            var $spinner = $( '#hkplt-edit-modal .hkplt-spinner' );

            $btn.prop( 'disabled', true );
            $spinner.addClass( 'is-active' );

            var data    = { action: 'hkplt_update_setting', nonce: hkpltData.nonce, setting: {} };
            if ( typeof tinymce !== 'undefined' ) { tinymce.triggerSave(); }
            var formArr = $( '#hkplt-edit-form' ).serializeArray();

            $.each( formArr, function ( i, field ) {
                var m = field.name.match( /setting\[(.+)\]/ );
                if ( m ) { data.setting[ m[ 1 ] ] = field.value; }
            } );

            $.ajax( {
                url:  hkpltData.ajax_url,
                type: 'POST',
                data: data,
                success: function ( response ) {
                    $btn.prop( 'disabled', false );
                    $spinner.removeClass( 'is-active' );

                    if ( response.success ) {
                        // Update local JS cache.
                        var id = parseInt( data.setting.id );
                        if ( hkpltData.settings ) {
                            hkpltData.settings[ id ] = response.data.setting;
                        }
                        hkplt.closeModal();
                        hkplt.showNotice( response.data.message || 'Rule updated.', 'success' );
                        // Refresh the card title badge + hook code.
                        hkplt.refreshCardUI( id, response.data.setting );
                    } else {
                        hkplt.showNotice( response.data.message || hkpltData.strings.error, 'error' );
                    }
                },
                error: function () {
                    $btn.prop( 'disabled', false );
                    $spinner.removeClass( 'is-active' );
                    hkplt.showNotice( hkpltData.strings.error, 'error' );
                }
            } );
        },

        /**
         * Refresh a rule card's visible title after an edit.
         */
        refreshCardUI: function ( id, s ) {
            var $card  = $( '.hkplt-rule-card[data-id="' + id + '"]' );
            var labels = {
                disable:        'Disable Callback',
                priority:       'Change Priority',
                wrapper:        'HTML Wrapper',
                custom_content: 'Custom Content',
                shortcode:      'Shortcode'
            };

            $card.find( 'strong' ).first().text( s.rule_title || labels[ s.status ] || s.status );
            $card.find( '.hkplt-badge' )
                 .attr( 'class', 'hkplt-badge hkplt-badge--' + s.status )
                 .text( labels[ s.status ] || s.status );

            $card.find( '.hkplt-rule-hook-cell code' ).text( s.hook_name );
            $card.find( '.hkplt-rule-priority-cell .pill' ).text( s.priority );
        },

        /* ----------------------------------------------------------------
         * Add Hook Form – submit handler
         * ---------------------------------------------------------------- */
        bindAddHookForm: function () {
            var $form = $( '#hkplt-add-hook-form' );
            if ( ! $form.length ) { return; }

            $form.on( 'submit', function ( e ) {
                e.preventDefault();

                // Validate: hook must be selected
                var hookName = $( '#hkplt-hook-name' ).val();
                if ( ! hookName ) {
                    hkplt.showNotice( 'Please select a hook first.', 'error' );
                    $( '#hkplt-hook-name' ).focus();
                    return;
                }

                var $btn     = $( '#hkplt-save-hook' );
                var $spinner = $form.find( '.hkplt-spinner' );

                $btn.prop( 'disabled', true );
                $spinner.addClass( 'is-active' );

                var data = { action: 'hkplt_save_settings', nonce: hkpltData.nonce, setting: {} };

                if ( typeof tinymce !== 'undefined' ) { tinymce.triggerSave(); }
                $.each( $form.serializeArray(), function ( i, f ) {
                    var m = f.name.match( /setting\[(.+)\]/ );
                    if ( m ) { data.setting[ m[ 1 ] ] = f.value; }
                } );

                $.ajax( {
                    url:  hkpltData.ajax_url,
                    type: 'POST',
                    data: data,
                    success: function ( response ) {
                        $btn.prop( 'disabled', false );
                        $spinner.removeClass( 'is-active' );
                        if ( response.success ) {
                            hkplt.showNotice( response.data.message || hkpltData.strings.saved, 'success' );
                            // Reset form + revert to disable section
                            $form[ 0 ].reset();
                            $( '.hkplt-type-card' ).removeClass( 'is-active' );
                            $( '.hkplt-type-card' ).first().addClass( 'is-active' );
                            $( '.hkplt-rule-section' ).hide();
                            $( '#section-callback' ).show();
                            $( '#hkplt-callback-select' ).html( '<option value="">— Select a hook first —</option>' );
                            $( '#hkplt-wrapper-preview-code' ).text( '<div> … </div>' );
                        } else {
                            hkplt.showNotice( response.data.message || hkpltData.strings.error, 'error' );
                        }
                    },
                    error: function () {
                        $btn.prop( 'disabled', false );
                        $spinner.removeClass( 'is-active' );
                        hkplt.showNotice( hkpltData.strings.error, 'error' );
                    }
                } );
            } );
        },

        /* ----------------------------------------------------------------
         * Delete buttons
         * ---------------------------------------------------------------- */
        bindDeleteButtons: function () {
            $( document ).on( 'click', '.hkplt-btn-delete', function ( e ) {
                e.stopPropagation();
                if ( ! window.confirm( hkpltData.strings.confirm_delete ) ) { return; }

                var id    = parseInt( $( this ).data( 'id' ) );
                var $card = $( '.hkplt-rule-card[data-id="' + id + '"]' );

                $.ajax( {
                    url:  hkpltData.ajax_url,
                    type: 'POST',
                    data: { action: 'hkplt_delete_setting', nonce: hkpltData.nonce, id: id },
                    success: function ( response ) {
                        if ( response.success ) {
                            $card.slideUp( 250, function () { $( this ).remove(); } );
                            hkplt.showNotice( hkpltData.strings.deleted, 'success' );
                            // Remove from local cache.
                            if ( hkpltData.settings ) { delete hkpltData.settings[ id ]; }
                        } else {
                            hkplt.showNotice( response.data.message || hkpltData.strings.error, 'error' );
                        }
                    },
                    error: function () {
                        hkplt.showNotice( hkpltData.strings.error, 'error' );
                    }
                } );
            } );
        },
        /* ----------------------------------------------------------------
         * Debug toggle (Settings page + Sidebar)
         * ---------------------------------------------------------------- */
        bindDebugToggle: function () {
            var $toggles = $( '#hkplt-debug-toggle, .hkplt-debug-sync' );
            if ( ! $toggles.length ) { return; }

            $toggles.on( 'change', function () {
                var enabled = $( this ).is( ':checked' ) ? 1 : 0;
                $toggles.prop( 'checked', enabled === 1 );

                $.post( hkpltData.ajax_url, { 
                    action: 'hkplt_toggle_debug', 
                    nonce: hkpltData.nonce, 
                    enabled: enabled 
                }, function ( response ) {
                    if ( response.success ) {
                        window.location.reload();
                    }
                } );
            } );
        },

        bindUninstallToggle: function () {
            $( document ).on( 'change', '#hkplt-uninstall-cleanup-toggle', function () {
                var enabled = $( this ).is( ':checked' ) ? 1 : 0;
                var $lbl = $( this ).closest( '.hkplt-sidebar-setting' );
                
                $lbl.css( 'opacity', '0.5' );
                
                $.post( hkpltData.ajax_url, { 
                    action: 'hkplt_toggle_uninstall_cleanup', 
                    nonce: hkpltData.nonce, 
                    enabled: enabled 
                }, function( response ) {
                    $lbl.css( 'opacity', '1' );
                    if ( response.success ) {
                        // Optional: show a small toast or notice
                    }
                } );
            } );
        },

        /* ----------------------------------------------------------------
         * Reset all settings
         * ---------------------------------------------------------------- */
        bindResetSettings: function () {
            $( document ).on( 'click', '#hkplt-reset-settings, .hkplt-reset-trigger', function ( e ) {
                e.preventDefault();
                if ( ! window.confirm( 'Delete ALL hook rules? This cannot be undone.' ) ) { return; }
                
                var $btn = $( this );
                $btn.prop( 'disabled', true ).text( 'Resetting...' );

                $.post(
                    hkpltData.ajax_url,
                    { action: 'hkplt_save_settings', nonce: hkpltData.nonce, setting: { hook_name: '', status: 'inactive' } },
                    function () { window.location.reload(); }
                );
            } );
        },
        /* ----------------------------------------------------------------
         * Export Config
         * ---------------------------------------------------------------- */
        bindExport: function () {
            var $genBtn  = $( '#hkplt-generate-export' );
            var $copyBtn = $( '#hkplt-copy-export' );
            var $dlBtn   = $( '#hkplt-download-export' );
            var $output  = $( '#hkplt-export-output' );
            var $code    = $( '#hkplt-export-code' );

            if ( ! $genBtn.length ) { return; }

            $genBtn.on( 'click', function () {
                $genBtn.prop( 'disabled', true ).text( 'Generating…' );
                $.ajax( {
                    url:  hkpltData.ajax_url,
                    type: 'POST',
                    data: { action: 'hkplt_export_config', nonce: hkpltData.nonce },
                    success: function ( response ) {
                        $genBtn.prop( 'disabled', false ).text( 'Regenerate' );
                        if ( response.success ) {
                            $code.text( response.data.code );
                            $output.show();
                            $copyBtn.show();
                            $dlBtn.show();
                        }
                    },
                    error: function () {
                        $genBtn.prop( 'disabled', false ).text( 'Generate Export Code' );
                        hkplt.showNotice( hkpltData.strings.error, 'error' );
                    }
                } );
            } );

            $copyBtn.on( 'click', function () {
                var text = $code.text();
                if ( navigator.clipboard ) {
                    navigator.clipboard.writeText( text ).then( function () { hkplt.showNotice( 'Code copied!', 'success' ); } );
                } else {
                    var $ta = $( '<textarea/>' ).val( text ).appendTo( 'body' ).select();
                    document.execCommand( 'copy' );
                    $ta.remove();
                    hkplt.showNotice( 'Code copied!', 'success' );
                }
            } );

            $dlBtn.on( 'click', function () {
                var url = URL.createObjectURL( new Blob( [ $code.text() ], { type: 'text/plain' } ) );
                var $a  = $( '<a/>' ).attr( { href: url, download: 'hkplt-export.php' } ).appendTo( 'body' );
                $a[ 0 ].click();
                $a.remove();
                URL.revokeObjectURL( url );
            } );
        },

        /* ----------------------------------------------------------------
         * Shortcode clipboard copy
         * ---------------------------------------------------------------- */
        bindCopyButtons: function () {
            // Legacy copy buttons (.hkplt-copy-btn) and new shortcode cards (.hkplt-copy-shortcode)
            $( document ).on( 'click', '.hkplt-copy-btn, .hkplt-copy-shortcode, #hkplt-copy-json', function () {
                var text = ( $( this ).attr( 'id' ) === 'hkplt-copy-json' ) ? $( '#hkplt-export-json' ).val() : ( $( this ).data( 'copy' ) || $( this ).data( 'shortcode' ) || '' );
                if ( ! text ) { return; }
                var $btn = $( this );
                var origHtml = $btn.html();

                if ( navigator.clipboard ) {
                    navigator.clipboard.writeText( text ).then( function () {
                        $btn.text( '✓ Copied!' );
                        setTimeout( function () { $btn.html( origHtml ); }, 1800 );
                    } );
                } else {
                    var $ta = $( '<textarea/>' ).val( text ).appendTo( 'body' ).select();
                    document.execCommand( 'copy' );
                    $ta.remove();
                    $btn.text( '✓ Copied!' );
                    setTimeout( function () { $btn.html( origHtml ); }, 1800 );
                }
            } );
        },

        /* ----------------------------------------------------------------
         * Import / Export JSON
         * ---------------------------------------------------------------- */
        bindImportExport: function () {
            var $importBtn = $( '#hkplt-process-import' );
            if ( ! $importBtn.length ) { return; }

            $importBtn.on( 'click', function () {
                var json = $( '#hkplt-import-json' ).val().trim();
                
                if ( ! json ) {
                    alert( 'Please paste JSON data first.' );
                    return;
                }

                if ( ! window.confirm( 'This will DELETE all current rules and replace them with the imported ones. Continue?' ) ) {
                    return;
                }

                $importBtn.prop( 'disabled', true ).text( 'Importing...' );

                $.ajax( {
                    url:  hkpltData.ajax_url,
                    type: 'POST',
                    data: { 
                        action: 'hkplt_import_json', 
                        nonce:  hkpltData.nonce, 
                        json:   json 
                    },
                    success: function ( response ) {
                        if ( response.success ) {
                            alert( response.data.message );
                            window.location.href = hkpltData.admin_url + 'page=hkplt-manager';
                        } else {
                            alert( 'Error: ' + response.data.message );
                            $importBtn.prop( 'disabled', false ).text( 'Import Rules' );
                        }
                    },
                    error: function () {
                        alert( 'A server error occurred during import.' );
                        $importBtn.prop( 'disabled', false ).text( 'Import Rules' );
                    }
                } );
            } );
        },


        /* ----------------------------------------------------------------
         * HTML escape utility
         * ---------------------------------------------------------------- */
        escHtml: function ( str ) {
            return String( str )
                .replace( /&/g, '&amp;' )
                .replace( /</g, '&lt;' )
                .replace( />/g, '&gt;' )
                .replace( /"/g, '&quot;' );
        }
    };

    $( document ).ready( function () {
        hkplt.init();
    } );

} )( jQuery );

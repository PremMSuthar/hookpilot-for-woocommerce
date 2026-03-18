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
 * @package WHM
 * @version 1.0.0
 */

/* global whmData, jQuery */
( function ( $ ) {
    'use strict';

    var whm = {

        init: function () {
            whm.bindInspector();
            whm.bindTypeCards();
            whm.bindCallbackLoader();
            whm.bindTagPicker();
            whm.bindPriorityPresets();
            whm.bindWrapperPreview();
            whm.bindAddHookForm();
            whm.bindEditModal();
            whm.bindDeleteButtons();
            whm.bindDebugToggle();
            whm.bindUninstallToggle();
            whm.bindExport();
            whm.bindCopyButtons();
            whm.bindResetSettings();
            whm.bindImportExport();
        },

        /* ----------------------------------------------------------------
         * Utility: show a top notice bar
         * ---------------------------------------------------------------- */
        showNotice: function ( message, type ) {
            var $n = $( '#whm-notice' );
            $n.removeClass( 'whm-notice--success whm-notice--error' )
              .addClass( 'whm-notice--' + ( type || 'success' ) )
              .text( message )
              .show();
            setTimeout( function () { $n.fadeOut(); }, 4500 );
        },

        /* ----------------------------------------------------------------
         * Hook Inspector
         * ---------------------------------------------------------------- */
        bindInspector: function () {
            var $btn    = $( '#whm-load-inspector' );
            var $wrap   = $( '#whm-inspector-table-wrap' );
            var $search = $( '#whm-inspector-search' );

            if ( ! $btn.length ) { return; }

            $btn.on( 'click', function () {
                $btn.prop( 'disabled', true ).text( whmData.strings.loading );
                $wrap.html( '<p class="whm-placeholder">' + whmData.strings.loading + '</p>' );

                $.ajax( {
                    url:  whmData.ajax_url,
                    type: 'POST',
                    data: { action: 'whm_get_hooks', nonce: whmData.nonce },
                    success: function ( response ) {
                        $btn.prop( 'disabled', false ).text( whmData.strings.reload_hooks );
                        if ( response.success ) {
                            whm.renderInspectorTable( response.data );
                        } else {
                            $wrap.html( '<p class="whm-placeholder">' + whm.escHtml( response.data.message || whmData.strings.error ) + '</p>' );
                        }
                    },
                    error: function () {
                        $btn.prop( 'disabled', false ).text( whmData.strings.load_hooks );
                        $wrap.html( '<p class="whm-placeholder">' + whmData.strings.error + '</p>' );
                    }
                } );
            } );

            $( document ).on( 'input', '#whm-inspector-search', function () {
                var q = $( this ).val().toLowerCase();
                $( '.whm-inspector-hook-section' ).each( function () {
                    $( this ).toggle( ( $( this ).data( 'hook' ) || '' ).indexOf( q ) !== -1 );
                } );
            } );
        },

        renderInspectorTable: function ( data ) {
            var $wrap = $( '#whm-inspector-table-wrap' );
            var html  = '';
            var empty = true;

            $.each( data, function ( hookName, callbacks ) {
                if ( ! callbacks.length ) { return; }
                empty = false;

                callbacks.sort( function ( a, b ) { return a.priority - b.priority; } );

                html += '<div class="whm-inspector-hook-section" data-hook="' + hookName + '">';
                html += '<div class="whm-inspector-hook-name">'
                      + hookName
                      + '<span class="whm-hook-type">' + ( callbacks[ 0 ] ? callbacks[ 0 ].type : 'Action' ) + '</span>'
                      + '</div>';
                html += '<table class="wp-list-table widefat fixed striped whm-table">'
                      + '<thead><tr><th>Priority</th><th>Callback</th><th>Args</th><th>Source</th></tr></thead><tbody>';

                $.each( callbacks, function ( i, cb ) {
                    html += '<tr>'
                          + '<td><strong>' + parseInt( cb.priority ) + '</strong></td>'
                          + '<td><code>' + whm.escHtml( cb.callback ) + '</code></td>'
                          + '<td>' + parseInt( cb.accepted_args ) + '</td>'
                          + '<td>' + whm.escHtml( cb.source ) + '</td>'
                          + '</tr>';
                } );

                html += '</tbody></table></div>';
            } );

            $wrap.html( empty ? '<p class="whm-placeholder">No registered callbacks found. Ensure WooCommerce is active and a shop/product page has been visited.</p>' : html );
        },

        /* ----------------------------------------------------------------
         * Rule Type Cards (radio buttons styled as cards)
         * ---------------------------------------------------------------- */
        bindTypeCards: function () {
            // Add-rule page: radio cards
            $( document ).on( 'change', '.whm-rule-type-radio', function () {
                var val = $( this ).val();
                $( '.whm-type-card' ).removeClass( 'is-active' );
                $( this ).closest( '.whm-type-card' ).addClass( 'is-active' );
                whm.showRuleSections( val, '.whm-rule-section' );
            } );

            // Edit modal: select dropdown
            $( document ).on( 'change', '.whm-edit-type-select', function () {
                whm.showEditSections( $( this ).val() );
            } );

            // Init on page load (default = disable)
            whm.showRuleSections( 'disable', '.whm-rule-section' );
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
            $( '.whm-edit-section' ).hide().find( 'input, select, textarea' ).prop( 'disabled', true );
            
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
            $( document ).on( 'change', '.whm-hook-select', function () {
                var hookName = $( this ).val();
                if ( ! hookName ) { return; }

                var $disable  = $( '#whm-callback-select' );
                var $priority = $( '#whm-priority-callback-select' );
                var $spin     = $( '#whm-cb-spinner' );

                $disable.html( '<option value="">— Loading… —</option>' );
                $priority.html( '<option value="">— Loading… —</option>' );
                $spin.show();

                $.ajax( {
                    url:  whmData.ajax_url,
                    type: 'POST',
                    data: { action: 'whm_get_hook_callbacks', nonce: whmData.nonce, hook_name: hookName },
                    success: function ( response ) {
                        $spin.hide();
                        if ( response.success && response.data.callbacks.length ) {
                            var opts = '<option value="">— Select callback —</option>';
                            $.each( response.data.callbacks, function ( i, cb ) {
                                var label = cb.callback + ' (p' + cb.priority + ( cb.source ? ', ' + cb.source : '' ) + ')';
                                opts += '<option value="' + whm.escHtml( cb.callback ) + '" data-priority="' + cb.priority + '">' + whm.escHtml( label ) + '</option>';
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
            $( document ).on( 'change', '#whm-priority-callback-select', function () {
                var prio = $( this ).find( ':selected' ).data( 'priority' );
                if ( prio !== undefined ) {
                    $( '#whm-old-priority' ).val( prio );
                }
            } );
        },

        /* ----------------------------------------------------------------
         * HTML Tag visual picker
         * ---------------------------------------------------------------- */
        bindTagPicker: function () {
            $( document ).on( 'click', '.whm-tag-btn', function () {
                $( this ).closest( '.whm-tag-picker' ).find( '.whm-tag-btn' ).removeClass( 'is-active' );
                $( this ).addClass( 'is-active' );
                var tag = $( this ).data( 'tag' );
                $( '#whm-wrapper-tag' ).val( tag );
                whm.updateWrapperPreview();
            } );
        },

        /* ----------------------------------------------------------------
         * Live wrapper preview
         * ---------------------------------------------------------------- */
        bindWrapperPreview: function () {
            $( document ).on( 'input', '#whm-wrapper-class, #whm-wrapper-attrs, [name="setting[wrapper_id]"]', function () {
                whm.updateWrapperPreview();
            } );
        },

        updateWrapperPreview: function () {
            var tag   = $( '#whm-wrapper-tag' ).val() || 'div';
            var cls   = $( '#whm-wrapper-class' ).val();
            var id    = $( '[name="setting[wrapper_id]"]' ).val();
            var attrs = $( '#whm-wrapper-attrs' ).val();
            var open  = '<' + tag;
            if ( cls )   { open += ' class="' + cls + '"'; }
            if ( id )    { open += ' id="' + id + '"'; }
            if ( attrs ) { open += ' ' + attrs; }
            open += '>';
            $( '#whm-wrapper-preview-code' ).text( open + ' … </' + tag + '>' );
        },

        /* ----------------------------------------------------------------
         * Priority quick-pick presets
         * ---------------------------------------------------------------- */
        bindPriorityPresets: function () {
            $( document ).on( 'click', '.whm-preset-btn', function () {
                var target = $( this ).data( 'target' );
                var val    = $( this ).data( 'val' );
                $( '#' + target ).val( val );
            } );
        },



        /* ----------------------------------------------------------------
         * Edit Modal
         * ---------------------------------------------------------------- */
        bindEditModal: function () {
            if ( ! $( '#whm-edit-modal' ).length ) { return; }

            // Open modal and populate.
            $( document ).on( 'click', '.whm-btn-edit', function ( e ) {
                e.stopPropagation();
                var id      = parseInt( $( this ).data( 'id' ) );
                var setting = whmData.settings && whmData.settings[ id ] ? whmData.settings[ id ] : null;

                if ( ! setting ) {
                    whm.showNotice( 'Rule data not found. Please refresh the page.', 'error' );
                    return;
                }

                whm.populateEditForm( id, setting );
                whm.openModal();
            } );

            // Close modal.
            $( document ).on( 'click', '.whm-modal__close, .whm-modal__backdrop', function () {
                whm.closeModal();
            } );

            // ESC key closes.
            $( document ).on( 'keydown', function ( e ) {
                if ( e.key === 'Escape' ) { whm.closeModal(); }
            } );

            // Submit edit form.
            $( '#whm-edit-save' ).on( 'click', function () {
                whm.submitEditForm();
            } );
        },

        openModal: function () {
            $( '#whm-edit-modal' ).fadeIn( 150 );
            $( 'body' ).addClass( 'whm-modal-open' );
        },

        closeModal: function () {
            $( '#whm-edit-modal' ).fadeOut( 150 );
            $( 'body' ).removeClass( 'whm-modal-open' );
        },

        populateEditForm: function ( id, s ) {
            $( '#whm-edit-id' ).val( id );
            $( '#whm-edit-hook-name' ).val( s.hook_name || '' );
            $( '#whm-edit-rule-type' ).val( s.status || 'disable' );
            $( '#whm-edit-callback' ).val( s.callback_name || '' );
            $( '#whm-edit-old-priority' ).val( s.old_priority || 10 );
            
            // Set priority for ALL section priority inputs
            $( '.whm-priority-input' ).val( s.priority || 10 );
            
            // Wrapper tag picker
            var tag = s.wrapper_tag || 'div';
            $( '#whm-edit-wrapper-tag' ).val( tag );
            $( '#whm-edit-modal .whm-tag-btn' ).removeClass( 'is-active' ).filter( '[data-tag="' + tag + '"]' ).addClass( 'is-active' );
            
            $( '#whm-edit-wrapper-class' ).val( s.wrapper_class || '' );
            $( '#whm-edit-wrapper-attrs' ).val( s.wrapper_attrs || '' );
            $( '#whm-edit-content' ).val( s.custom_content || '' );
            $( '#whm-edit-shortcode' ).val( s.shortcode_content || '' );
            
            // Show correct sections (this also handles disabling the hidden ones)
            whm.showEditSections( s.status || 'disable' );
        },

        submitEditForm: function () {
            var $btn     = $( '#whm-edit-save' );
            var $spinner = $( '#whm-edit-modal .whm-spinner' );

            $btn.prop( 'disabled', true );
            $spinner.addClass( 'is-active' );

            var data    = { action: 'whm_update_setting', nonce: whmData.nonce, setting: {} };
            if ( typeof tinymce !== 'undefined' ) { tinymce.triggerSave(); }
            var formArr = $( '#whm-edit-form' ).serializeArray();

            $.each( formArr, function ( i, field ) {
                var m = field.name.match( /setting\[(.+)\]/ );
                if ( m ) { data.setting[ m[ 1 ] ] = field.value; }
            } );

            $.ajax( {
                url:  whmData.ajax_url,
                type: 'POST',
                data: data,
                success: function ( response ) {
                    $btn.prop( 'disabled', false );
                    $spinner.removeClass( 'is-active' );

                    if ( response.success ) {
                        // Update local JS cache.
                        var id = parseInt( data.setting.id );
                        if ( whmData.settings ) {
                            whmData.settings[ id ] = response.data.setting;
                        }
                        whm.closeModal();
                        whm.showNotice( response.data.message || 'Rule updated.', 'success' );
                        // Refresh the card title badge + hook code.
                        whm.refreshCardUI( id, response.data.setting );
                    } else {
                        whm.showNotice( response.data.message || whmData.strings.error, 'error' );
                    }
                },
                error: function () {
                    $btn.prop( 'disabled', false );
                    $spinner.removeClass( 'is-active' );
                    whm.showNotice( whmData.strings.error, 'error' );
                }
            } );
        },

        /**
         * Refresh a rule card's visible title after an edit.
         */
        refreshCardUI: function ( id, s ) {
            var $card  = $( '.whm-rule-card[data-id="' + id + '"]' );
            var labels = {
                disable:        'Disable Callback',
                priority:       'Change Priority',
                wrapper:        'HTML Wrapper',
                custom_content: 'Custom Content',
                shortcode:      'Shortcode'
            };

            $card.find( 'strong' ).first().text( s.rule_title || labels[ s.status ] || s.status );
            $card.find( '.whm-badge' )
                 .attr( 'class', 'whm-badge whm-badge--' + s.status )
                 .text( labels[ s.status ] || s.status );

            $card.find( '.whm-rule-hook-cell code' ).text( s.hook_name );
            $card.find( '.whm-rule-priority-cell .pill' ).text( s.priority );
        },

        /* ----------------------------------------------------------------
         * Add Hook Form – submit handler
         * ---------------------------------------------------------------- */
        bindAddHookForm: function () {
            var $form = $( '#whm-add-hook-form' );
            if ( ! $form.length ) { return; }

            $form.on( 'submit', function ( e ) {
                e.preventDefault();

                // Validate: hook must be selected
                var hookName = $( '#whm-hook-name' ).val();
                if ( ! hookName ) {
                    whm.showNotice( 'Please select a hook first.', 'error' );
                    $( '#whm-hook-name' ).focus();
                    return;
                }

                var $btn     = $( '#whm-save-hook' );
                var $spinner = $form.find( '.whm-spinner' );

                $btn.prop( 'disabled', true );
                $spinner.addClass( 'is-active' );

                var data = { action: 'whm_save_settings', nonce: whmData.nonce, setting: {} };

                if ( typeof tinymce !== 'undefined' ) { tinymce.triggerSave(); }
                $.each( $form.serializeArray(), function ( i, f ) {
                    var m = f.name.match( /setting\[(.+)\]/ );
                    if ( m ) { data.setting[ m[ 1 ] ] = f.value; }
                } );

                $.ajax( {
                    url:  whmData.ajax_url,
                    type: 'POST',
                    data: data,
                    success: function ( response ) {
                        $btn.prop( 'disabled', false );
                        $spinner.removeClass( 'is-active' );
                        if ( response.success ) {
                            whm.showNotice( response.data.message || whmData.strings.saved, 'success' );
                            // Reset form + revert to disable section
                            $form[ 0 ].reset();
                            $( '.whm-type-card' ).removeClass( 'is-active' );
                            $( '.whm-type-card' ).first().addClass( 'is-active' );
                            $( '.whm-rule-section' ).hide();
                            $( '#section-callback' ).show();
                            $( '#whm-callback-select' ).html( '<option value="">— Select a hook first —</option>' );
                            $( '#whm-wrapper-preview-code' ).text( '<div> … </div>' );
                        } else {
                            whm.showNotice( response.data.message || whmData.strings.error, 'error' );
                        }
                    },
                    error: function () {
                        $btn.prop( 'disabled', false );
                        $spinner.removeClass( 'is-active' );
                        whm.showNotice( whmData.strings.error, 'error' );
                    }
                } );
            } );
        },

        /* ----------------------------------------------------------------
         * Delete buttons
         * ---------------------------------------------------------------- */
        bindDeleteButtons: function () {
            $( document ).on( 'click', '.whm-btn-delete', function ( e ) {
                e.stopPropagation();
                if ( ! window.confirm( whmData.strings.confirm_delete ) ) { return; }

                var id    = parseInt( $( this ).data( 'id' ) );
                var $card = $( '.whm-rule-card[data-id="' + id + '"]' );

                $.ajax( {
                    url:  whmData.ajax_url,
                    type: 'POST',
                    data: { action: 'whm_delete_setting', nonce: whmData.nonce, id: id },
                    success: function ( response ) {
                        if ( response.success ) {
                            $card.slideUp( 250, function () { $( this ).remove(); } );
                            whm.showNotice( whmData.strings.deleted, 'success' );
                            // Remove from local cache.
                            if ( whmData.settings ) { delete whmData.settings[ id ]; }
                        } else {
                            whm.showNotice( response.data.message || whmData.strings.error, 'error' );
                        }
                    },
                    error: function () {
                        whm.showNotice( whmData.strings.error, 'error' );
                    }
                } );
            } );
        },
        /* ----------------------------------------------------------------
         * Debug toggle (Settings page + Sidebar)
         * ---------------------------------------------------------------- */
        bindDebugToggle: function () {
            var $toggles = $( '#whm-debug-toggle, .whm-debug-sync' );
            if ( ! $toggles.length ) { return; }

            $toggles.on( 'change', function () {
                var enabled = $( this ).is( ':checked' ) ? 1 : 0;
                $toggles.prop( 'checked', enabled === 1 );

                $.post( whmData.ajax_url, { 
                    action: 'whm_toggle_debug', 
                    nonce: whmData.nonce, 
                    enabled: enabled 
                }, function ( response ) {
                    if ( response.success ) {
                        window.location.reload();
                    }
                } );
            } );
        },

        bindUninstallToggle: function () {
            $( document ).on( 'change', '#whm-uninstall-cleanup-toggle', function () {
                var enabled = $( this ).is( ':checked' ) ? 1 : 0;
                var $lbl = $( this ).closest( '.whm-sidebar-setting' );
                
                $lbl.css( 'opacity', '0.5' );
                
                $.post( whmData.ajax_url, { 
                    action: 'whm_toggle_uninstall_cleanup', 
                    nonce: whmData.nonce, 
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
            $( document ).on( 'click', '#whm-reset-settings, .whm-reset-trigger', function ( e ) {
                e.preventDefault();
                if ( ! window.confirm( 'Delete ALL hook rules? This cannot be undone.' ) ) { return; }
                
                var $btn = $( this );
                $btn.prop( 'disabled', true ).text( 'Resetting...' );

                $.post(
                    whmData.ajax_url,
                    { action: 'whm_save_settings', nonce: whmData.nonce, setting: { hook_name: '', status: 'inactive' } },
                    function () { window.location.reload(); }
                );
            } );
        },
        /* ----------------------------------------------------------------
         * Export Config
         * ---------------------------------------------------------------- */
        bindExport: function () {
            var $genBtn  = $( '#whm-generate-export' );
            var $copyBtn = $( '#whm-copy-export' );
            var $dlBtn   = $( '#whm-download-export' );
            var $output  = $( '#whm-export-output' );
            var $code    = $( '#whm-export-code' );

            if ( ! $genBtn.length ) { return; }

            $genBtn.on( 'click', function () {
                $genBtn.prop( 'disabled', true ).text( 'Generating…' );
                $.ajax( {
                    url:  whmData.ajax_url,
                    type: 'POST',
                    data: { action: 'whm_export_config', nonce: whmData.nonce },
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
                        whm.showNotice( whmData.strings.error, 'error' );
                    }
                } );
            } );

            $copyBtn.on( 'click', function () {
                var text = $code.text();
                if ( navigator.clipboard ) {
                    navigator.clipboard.writeText( text ).then( function () { whm.showNotice( 'Code copied!', 'success' ); } );
                } else {
                    var $ta = $( '<textarea/>' ).val( text ).appendTo( 'body' ).select();
                    document.execCommand( 'copy' );
                    $ta.remove();
                    whm.showNotice( 'Code copied!', 'success' );
                }
            } );

            $dlBtn.on( 'click', function () {
                var url = URL.createObjectURL( new Blob( [ $code.text() ], { type: 'text/plain' } ) );
                var $a  = $( '<a/>' ).attr( { href: url, download: 'whm-export.php' } ).appendTo( 'body' );
                $a[ 0 ].click();
                $a.remove();
                URL.revokeObjectURL( url );
            } );
        },

        /* ----------------------------------------------------------------
         * Shortcode clipboard copy
         * ---------------------------------------------------------------- */
        bindCopyButtons: function () {
            // Legacy copy buttons (.whm-copy-btn) and new shortcode cards (.whm-copy-shortcode)
            $( document ).on( 'click', '.whm-copy-btn, .whm-copy-shortcode, #whm-copy-json', function () {
                var text = ( $( this ).attr( 'id' ) === 'whm-copy-json' ) ? $( '#whm-export-json' ).val() : ( $( this ).data( 'copy' ) || $( this ).data( 'shortcode' ) || '' );
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
            var $importBtn = $( '#whm-process-import' );
            if ( ! $importBtn.length ) { return; }

            $importBtn.on( 'click', function () {
                var json = $( '#whm-import-json' ).val().trim();
                
                if ( ! json ) {
                    alert( 'Please paste JSON data first.' );
                    return;
                }

                if ( ! window.confirm( 'This will DELETE all current rules and replace them with the imported ones. Continue?' ) ) {
                    return;
                }

                $importBtn.prop( 'disabled', true ).text( 'Importing...' );

                $.ajax( {
                    url:  whmData.ajax_url,
                    type: 'POST',
                    data: { 
                        action: 'whm_import_json', 
                        nonce:  whmData.nonce, 
                        json:   json 
                    },
                    success: function ( response ) {
                        if ( response.success ) {
                            alert( response.data.message );
                            window.location.href = whmData.admin_url + 'page=whm-manager';
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
        whm.init();
    } );

} )( jQuery );

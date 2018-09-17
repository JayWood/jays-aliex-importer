// The main javascript file for the plugin
window.comPlugish = window.comPlugish || {};

window.comPlugish.jaysAliex = ( function( window, document, $ ) {
    const app = {
        '$c': {},
        'l10n': window.jays_aliex_i10n || {},
    };

    app.cache = () => {
        app.$c.pageTitles = $( 'a.page-title-action' );
        app.$c.spinner = $( 'span.spinner' );
        app.$c.progress = $( 'progress' );
        app.$c.formTable = $( 'table.form-table' );
        app.$c.wcActions = $( 'div.wc-actions' );
        app.$c.messagesSuccess = $( '#jays-aliex-message' );
    };

    /**
     * Make sure we are on the right page.
     * @returns {bool}
     */
    app.meetsRequirements = () => {
        let admin = $( 'body.wp-admin' );
        if ( ! admin ) {
            return false;
        }

        return admin.hasClass( 'post-type-product' );
    };

    /**
     * Initializes the script.
     * @returns {boolean}
     */
    app.init = () => {
        if ( ! app.meetsRequirements() ) {
            return false;
        }

        app.cache();

        app.addUrlBtn();

        $( 'button.jays-aliex-importer-button' ).on( 'click', app.processImport );
        app.$c.messagesSuccess.find( '.reset-link' ).on( 'click', app.resetForm );
    };

    app.resetForm = ( evt ) => {
        evt.preventDefault();
        app.$c.messagesSuccess.toggle();
        app.$c.formTable.find( '#jays-aliex-url' ).val( '' );
        app.$c.formTable.toggle();
        app.$c.wcActions.toggle();
    };

    /**
     * Processes the import values.
     *
     * @param evt
     */
    app.processImport = ( evt ) => {
        evt.preventDefault();
        let values = $( 'form.woocommerce-exporter' ).serialize();

        app.toggleFormState();

        // Set default progress to 0, in case it's set otherwise.
        app.setProgress(33);

        app.ajax_call( values );
    };

    /**
     * Calls the ajax URL required for processing document imports.
     * @param dataSet
     */
    app.ajax_call = ( dataSet ) => {
        $.ajax({
            url: ajaxurl,
            data: dataSet,
            dataType: 'json',
        }).done( (result) => {

            window.console.log( result );

            if ( ! result.success ) {
                app.toggleFormState();
                return;
            }

            let data = result.data;
            if ( data.is_variable && 1 === data.step ) {
                // Run stage two import
                dataSet += '&product='+data.product;
                app.setProgress(66);
                app.ajax_call( dataSet );
            } else {
                app.setProgress(100);
                setTimeout( () => {
                    window.console.log( 'redirect fires' );
                    app.$c.messagesSuccess.find( '.edit-post-link' ).attr( 'href', data.edit_link.replace(/&amp;/g, '&') );
                    app.toggleSuccess();
                }, 2000 );
            }
        } );
    };

    /**
     * Toggle the success method.
     */
    app.toggleSuccess = () => {
        app.$c.progress.toggle();
        app.$c.messagesSuccess.toggle();
        app.$c.spinner.toggle();
    };

    /**
     * Toggles all form states needed for the import form.
     */
    app.toggleFormState = () => {
        app.$c.spinner.toggle();
        app.$c.formTable.toggle();
        app.$c.progress.toggle();
        app.$c.wcActions.toggle();
    };

    app.setProgress = (progress) => {
      app.$c.progress.val( parseInt( progress ) );
    };

    /**
     * Adds the url button to the admin page if possible.
     */
    app.addUrlBtn = () => {
        if ( 1 > app.$c.pageTitles.length ) {
            return;
        }

        app.$c.pageTitles.last().after( '<a href="' + app.l10n.page_urls.jays_aliex_importer + '" class="page-title-action">' + app.l10n.ui.btn_import_now + '</a>' );
    };

    $( 'document' ).ready( app.init );

    return app;
} )( window, document, jQuery );
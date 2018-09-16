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

        $.ajax({
            url: ajaxurl,
            data: values,
        }).done( (result) => {
            window.console.log( result );
            app.toggleFormState();
        } );
    };

    app.toggleFormState = () => {
        app.$c.spinner.toggle();
        app.$c.formTable.toggle();
        app.$c.progress.toggle();
        app.$c.wcActions.toggle();
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
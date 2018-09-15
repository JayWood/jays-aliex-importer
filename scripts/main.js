// The main javascript file for the plugin
window.comPlugish = window.comPlugish || {};

window.comPlugish.jaysAliex = ( function( window, document, $ ) {
    const app = {
        '$c': {},
        'l10n': window.jays_aliex_i10n || {},
    };

    app.cache = () => {
        app.$c.pageTitles = $( 'a.page-title-action' );
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

    app.init = () => {
        if ( ! app.meetsRequirements() ) {
            return false;
        }

        app.cache();

        app.addUrlBtn()
    };

    app.addUrlBtn = () => {
        app.$c.pageTitles.last().after( '<a href="" class="page-title-action">' + app.l10n.ui.btn_import_now + '</a>' );
    };

    $( 'document' ).ready( app.init );

    return app;
} )( window, document, jQuery );
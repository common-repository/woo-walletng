jQuery( function( $ ) {

    var wc_wallet_submit = false;

    jQuery( '#tbz-wc-wallet-payment-button' ).click( function() {
        return tbzWCWalletPaymentHandler();
    });

    function tbzWCWalletPaymentHandler() {

        if ( wc_wallet_submit ) {
            wc_wallet_submit = false;
            return true;
        }

        var $form            = $( 'form#payment-form, form#order_review' ),
            txnref           = $form.find( 'input.tbz_wc_wallet_txnref' ),
            walletref        = $form.find( 'input.tbz_wc_wallet_walletref' ),
            response_code    = $form.find( 'input.tbz_wc_wallet_response_code' );

        txnref.val( '' );
        walletref.val( '' );
        response_code.val( '' );

        var tbz_wc_wallet_callback = function( response ) {

            $form.append( '<input type="hidden" class="tbz_wc_wallet_txnref" name="tbz_wc_wallet_txnref" value="' + response.data.MerchantRef + '"/>' );
            $form.append( '<input type="hidden" class="tbz_wc_wallet_walletref" name="tbz_wc_wallet_walletref" value="' + response.data.Reference + '"/>' );
            $form.append( '<input type="hidden" class="tbz_wc_wallet_response_code" name="tbz_wc_wallet_response_code" value="' + response.data.ResponseCode + '"/>' );

            wc_wallet_submit = true;

            $form.submit();

            $( 'body' ).block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                },
                css: {
                    cursor: "wait"
                }
            });
        };

        getpaidSetup( {
            publicKey: tbz_wc_wallet_params.public_key,
            reference: tbz_wc_wallet_params.txn_id,
            amount: tbz_wc_wallet_params.amount,
            name: tbz_wc_wallet_params.name,
            email: tbz_wc_wallet_params.email,
            desc: tbz_wc_wallet_params.desc,
            picture: tbz_wc_wallet_params.logo,
            onClose: function() {
                $( this.el ).unblock();
            },
            callback: tbz_wc_wallet_callback
        } );

        return false;

    }

} );
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0">
<link rel="stylesheet"
    href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
    integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T"
    crossorigin="anonymous">
</head>
    <div class="container">
        <h1>sample</h1>

        <!-- div block1 -->
        <h3>deposit to</h3>
        <div id="address"></div>
        <div class="collapse" id="result1">
            <button id="button1" class="btn btn-primary" type="button" disabled>
              <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              承認中...
            </button>
        </div>

        <!-- div block2 -->
        <div class="collapse" id="result2">
            <h3>signedTx</h3><textarea id="signedTx" rows="8" class="form-control"></textarea>
            <h3>status</h3><div id="status"><ul></ul></div>
            <div id="wait2" class="spinner-border text-primary" role="status">
              <span class="sr-only">承認中...</span>
            </div>
        </div>

        <!-- div block3 -->
        <div class="collapse" id="result3">
            <h3>confirmed</h3><div id="confirmed"><ul></ul></div>
            <h3>account</h3><div id="account"><ul></ul></div>
            <h3>namespace</h3><div id="namespace"><ul></ul></div>
            <h3>mosaic</h3><div id="mosaic"><ul></ul></div>
        </div>
    </div>

<script
    src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
    integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
    crossorigin="anonymous">
</script>
<script
    src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
    integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM"
    crossorigin="anonymous">
</script>
<script src="nem2-sdk-0.13.1.js"></script>
<script>$(function() {

const NODE = 'https://catapult-test.opening-line.jp:3001';
const GENERATION_HASH = "453052FDC4EB23BF0D7280C103F7797133A633B68A81986165B76FCE248AB235";

const nem = require("/node_modules/nem2-sdk");
const rxjs = require("/node_modules/rxjs/operators");

const txHttp = new nem.TransactionHttp(NODE);
const nsHttp = new nem.NamespaceHttp(NODE);

nsId = "";
mosaicId = "";
const alice = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
$('#address').text(alice.address.address);

var isInit = true;
const wsEndpoint = NODE.replace('https', 'wss');
const listener = new nem.Listener(wsEndpoint, WebSocket);
listener.open().then(() => {

    listener
    .unconfirmedAdded(alice.address)
    .subscribe(_=> $('#result1').collapse('show'),err => console.error(err));

    listener
    .confirmed(alice.address)
    .subscribe(
        function(_){
            if(isInit){
                $('#button1').prop("disabled", false);
                $('#button1').empty();
                $('#button1').text("送信");
                isInit = false;
            }else{
/*
                $('#wait2').remove();
                $('#result3').collapse('show');
				nsHttp.getNamespace(nsId)
			    .subscribe(_ => console.log(_), err => console.error(err));

				nsHttp.getNamespacesFromAccount(alice.address)
			    .subscribe(_ => {
console.log("alice address");
					console.log(_);

				}, err => console.error(err));

				nsHttp.getNamespacesName([nsId])
			    .subscribe(_ => console.log(_), err => console.error(err));

				nsHttp.getLinkedMosaicId(nsId)
			    .subscribe(_ => console.log(_), err => console.error(err));
*/

            }
        },
        err => console.error(err)
    );
});

function process(){

    //ネームスペース作成
    const namespaceTx = nem.RegisterNamespaceTransaction.createRootNamespace(
        nem.Deadline.create(),
        "xembook2",
        nem.UInt64.fromUint(3),
        nem.NetworkType.MIJIN_TEST
    );
	nsId = namespaceTx.namespaceId;

    //モザイク作成
    const nonce = nem.MosaicNonce.createRandom();
    const mosaicDefTx = nem.MosaicDefinitionTransaction.create(
        nem.Deadline.create(),
        nonce,
        nem.MosaicId.createFromNonce(nonce, alice.publicAccount),
        nem.MosaicProperties.create({
            supplyMutable: true,
            transferable: true,
            divisibility: 0,
            duration: nem.UInt64.fromUint(1)
        }),
        nem.NetworkType.MIJIN_TEST
    );
	mosaicId = mosaicDefTx.mosaicId.toHex();

    //モザイク変更
    const mosaicChangeTx = nem.MosaicSupplyChangeTransaction.create(
        nem.Deadline.create(),
        mosaicDefTx.mosaicId,
        nem.MosaicSupplyType.Increase,
        nem.UInt64.fromUint(1000000),
        nem.NetworkType.MIJIN_TEST
    );

    //モザイクとネームスペースのリンク
    const mosaicAliasTx = nem.AliasTransaction.createForMosaic(
        nem.Deadline.create(),
        nem.AliasActionType.Link,
        namespaceTx.namespaceId,
        mosaicDefTx.mosaicId,
        nem.NetworkType.MIJIN_TEST
    );

    const aggregateTx = nem.AggregateTransaction.createComplete(
        nem.Deadline.create(),
        [
            namespaceTx.toAggregate(alice.publicAccount),
            mosaicDefTx.toAggregate(alice.publicAccount),
            mosaicChangeTx.toAggregate(alice.publicAccount),
            mosaicAliasTx.toAggregate(alice.publicAccount),
        ],
        nem.NetworkType.MIJIN_TEST,
        []
    );
    console.log(aggregateTx);
    const signedTx = alice.sign(aggregateTx,GENERATION_HASH);

    txHttp
    .announce(signedTx)
    .subscribe(_ => console.log(_), err => console.error(err));

    showInfo(NODE,signedTx,alice,mosaicDefTx.mosaicId.toHex(),namespaceTx.namespaceId.toHex());

	listener
    .confirmed(alice.address)
    .subscribe(
        function(_){

                $('#wait2').remove();
                $('#result3').collapse('show');

				nsHttp.getNamespacesFromAccount(alice.address)
				.pipe(

					rxjs.mergeMap((_) => _),
					rxjs.filter((_) => {
						console.log(_);
						if(_.alias.type === 1){　//MosaicAlias
//							console.log(_.alias.mosaicId);
							return true;
						}
					})
				)

			    .subscribe(_ => {
					console.log(_.alias.mosaicId);
					nsHttp.getNamespacesName(_.levels)
					.subscribe(_ => console.log(_), err => console.error(err));


				}, err => console.error(err));

        },
        err => console.error(err)
    );


}

function showInfo(node,signedTx,account,mosaic,namespace){

    const pubkey = account.publicKey ;
    const address = account.address.address ;
    const hash = signedTx.hash

    $('#signedTx').val(signedTx.payload);
    $('#status ul').append(strLi(node,'/transaction/' + hash + '/status' ,hash + '/status'));
    $('#confirmed ul').append(strLi(node,'/transaction/' + hash ,hash ));
    $('#account ul').append(strLi(node,'/account/' + pubkey ,address ));
    $('#account ul').append(strLi(node,'/account/' + pubkey + '/transactions' ,address + '/transactions'));
    $('#mosaic ul').append(strLi(node,'/mosaic/' + mosaic ,mosaic));
    $('#namespace ul').append(strLi(node,'/namespace/' + namespace ,namespace));
}

function strLi(node,href,text){
    return '<li><a target="_blank" href="' + node + href + '">' + text + '</a></li>';
}

$("#button1").click(
    function(){
        process();
        $('#result2').collapse('show');
        return false;
    }
);

})</script>


</body>


</html>

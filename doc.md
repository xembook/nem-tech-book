# はじめに

# 1 開発環境の準備

## 1.1 バンドルファイルの作成
## 1.2 ブラウザを使ったデバッグ手法

# 2 サンプルプログラム基本編
## 2.1 サンプルテンプレート
## 2.2 監視
## 2.3 アグリゲートトランザクション
## 2.4 アグリゲートトランザクション(マルチシグ)

# 3 サンプルプログラム応用編
## 3.1 マルチレベルマルチシグ
## 3.2 保留型アグリゲートトランザクション
## 3.3 アトミックスワップ

# 4 社会実装のヒント
ブロックチェーンをシステムを開発するためには、実社会ならではの実装パターンを把握しておくことが重要です。

## 4.1 所有
### 4.1.1 動作概要
- モザイク「item」を生成しAliceに割り当てます
- Aliceをマルチシグ化し、Bobを連署アカウントに指定します
- BobはAliceをCarolに譲渡し、CarolはBobに代金を支払います

### 4.1.2 所有
モザイク「item」を所有するAliceをマルチシグ化し、Bobを連署アカウントに指定します。
```js
const aggregateTx = nem.AggregateTransaction.createComplete(
    nem.Deadline.create(),
    [
        namespaceTx.toAggregate(alice.publicAccount),
        mosaicDefTx.toAggregate(alice.publicAccount),
        mosaicChangeTx.toAggregate(alice.publicAccount),
        mosaicAliasTx.toAggregate(alice.publicAccount),
        multisigTx.toAggregate(alice.publicAccount),
    ],
    nem.NetworkType.MIJIN_TEST
);
```
2.3と同様にネームスペースで割り当てられたモザイクを生成し、Aliceをマルチシグ化します。
今回は生成したAliceが所有者となります。

### 4.1.3 譲渡
マルチシグの連署アカウントをBobからCarolに変更します。CarolはBobに代金を払います。
```js
//譲渡
const modifyMultisigTx = nem.ModifyMultisigAccountTransaction.create(
    nem.Deadline.create(),
    0,0,
    [
        new nem.MultisigCosignatoryModification(addType,carol),
        new nem.MultisigCosignatoryModification(removeType,bob),
    ],
    nem.NetworkType.MIJIN_TEST
);

//代金支払い
const transferTx = nem.TransferTransaction.create(
    nem.Deadline.create(),
    bob.address,
    [nem.NetworkCurrencyMosaic.createRelative(0)],
    nem.PlainMessage.create('Thank you for sending alice.'),
    nem.NetworkType.MIJIN_TEST
);

//集約
const aggregateTx = nem.AggregateTransaction.createBonded(
    nem.Deadline.create(),
    [
        modifyMultisigTx.toAggregate(alice.publicAccount),
        transferTx.toAggregate(carol.publicAccount),
    ],
    nem.NetworkType.MIJIN_TEST
);
```


## 4.2 認証
### 4.2.1 AliceがBobに監査人シールを送る
```js
const sendSealFromAliceToBobTx = nem.TransferTransaction.create(
    nem.Deadline.create(),
    bob.address,
    [
        new nem.Mosaic(new nem.NamespaceId(NAMESPACE), nem.UInt64.fromUint(1)),
        nem.NetworkCurrencyMosaic.createRelative(1)
    ],
    nem.EmptyMessage,
    nem.NetworkType.MIJIN_TEST
);
```

### 4.2.2 BobがCarolに登録トランザクションを送る
```js
const sendAuthFromBobToCarolTx = nem.TransferTransaction.create(
    nem.Deadline.create(),
    carol.address,
    [nem.NetworkCurrencyMosaic.createRelative(0)],
    nem.EmptyMessage,
    nem.NetworkType.MIJIN_TEST
);

```

### 4.2.3 Carolの署名を検証する
```js
const authTx = $('#authtx').val();
const signed = carol.signData(authTx);

if(carol.publicAccount.verifySignature(authTx, signed)){
  //ここからサーバ側で検証
}

```

### 4.2.4 ログイン認証
```js
const accountHttp = new nem.AccountHttp(NODE);
const txHttp = new nem.TransactionHttp(NODE);
const nsHttp = new nem.NamespaceHttp(NODE);

txHttp.getTransaction(authTx)
.pipe(
    rxjs.mergeMap(_ => _.innerTransactions),
    rxjs.filter(_=> {
        return _.recipient !== undefined 
            && _.recipient.address == carol.publicAccount.address.address
            && _.signer.address.address == bob.publicAccount.address.address ;
    }),
    rxjs.mergeMap(_ => {

        return accountHttp.getAccountInfo(bob.publicAccount.address)
        .pipe(
            rxjs.mergeMap(info => info.mosaics),
        );
    }),
    rxjs.mergeMap(_ => {

        return nsHttp.getNamespace(new nem.NamespaceId(NAMESPACE))
        .pipe(
            rxjs.filter(ns=>{
                return _.id.id.toDTO().toString() ==  ns.alias.mosaicId.toString();
            })
        )
    }),
)
```

## 4.3 トレーサビリティ
### 4.3.1 トランザクション作成
```js
const aggregateTx = nem.AggregateTransaction.createComplete(
    nem.Deadline.create(),
    [
        aliceTx.toAggregate(alice.publicAccount),
        bobTx.toAggregate(bob.publicAccount)
    ],
    nem.NetworkType.MIJIN_TEST,
    []
);
const aliceSignedTx = aggregateTransaction.signWith(alice, GENERATION_HASH);
$('#aliceSignedTx').val(aliceSignedTx.payload);
$('#aliceSignedTxHash').val(aliceSignedTx.hash);
```

### 4.3.2 別環境でBobが署名
```js
const bobSignedTx = nem.CosignatureTransaction.signTransactionPayload(bob, $('#aliceSignedTx').val(), GENERATION_HASH);
$('#bobSignedTxSignature').val(bobSignedTx.signature);
$('#bobSignedTxSigner').val(bobSignedTx.signer);

```

### 4.3.3 署名を集めてトランザクションを再作成
```js
const cosignSignedTxs = [
    new nem.CosignatureSignedTransaction(
        $('#aliceSignedTxHash').val(),
        $('#bobSignedTxSignature').val(),
        $('#bobSignedTxSigner').val()
    )
];
const recreatedTx = nem.TransactionMapping.createFromPayload($('#aliceSignedTx').val());
const signedTx = recreatedTx.signTransactionGivenSignatures(alice, cosignSignedTxs, GENERATION_HASH);
const transactionHttp = new nem.TransactionHttp(NODE);
transactionHttp.announce(signedTx)
```


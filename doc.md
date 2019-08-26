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
以下のサンプルプログラムについて説明します。

- マルチレベルマルチシグ
- 保留型アグリゲートトランザクション
- アトミックスワップ

## 3.1 マルチレベルマルチシグ

- 301_multilevel_multisig.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/301_multilevel_multisig.html
  - デモ
    - https://xembook.github.io/nem-tech-book/301_multilevel_multisig.html

### 3.1.1 アカウント生成
```js
const alice = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const bob   = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const carol = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const dave = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const dave2 = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const ellen = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const frank = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
```

### 3.1.2 マルチシグ組成
```js

const addType = nem.MultisigCosignatoryModificationType.Add;
const removeType = nem.MultisigCosignatoryModificationType.Remove;
const ellenOrFrankMultisigTx = nem.ModifyMultisigAccountTransaction.create(
    nem.Deadline.create(),
    1,1,
    [
        new nem.MultisigCosignatoryModification(addType, ellen),
        new nem.MultisigCosignatoryModification(addType, frank)
    ],
    nem.NetworkType.MIJIN_TEST
);

const bobAndDaveAndCarolMultisigTx = nem.ModifyMultisigAccountTransaction.create(
    nem.Deadline.create(),
    2,2,
    [
        new nem.MultisigCosignatoryModification(addType, bob),
        new nem.MultisigCosignatoryModification(addType, dave),
        new nem.MultisigCosignatoryModification(addType, carol),
    ],
    nem.NetworkType.MIJIN_TEST
);

const aggregateTx = nem.AggregateTransaction.createComplete(
    nem.Deadline.create(),
    [
        ellenOrFrankMultisigTx.toAggregate(dave.publicAccount),
        bobAndDaveAndCarolMultisigTx.toAggregate(alice.publicAccount),
    ],
    nem.NetworkType.MIJIN_TEST,
    []
);

const signedTx =  alice.signTransactionWithCosignatories(
    aggregateTx,
    [bob,carol,dave,ellen,frank],
    GENERATION_HASH,
);
txHttp.announce(signedTx)
```


### 3.1.3 DaveからDave2に連署者を変更
```js
const switchDaveToMultisigTx = nem.ModifyMultisigAccountTransaction.create(
    nem.Deadline.create(),
    0,0,
    [
        new nem.MultisigCosignatoryModification(addType, dave2),
        new nem.MultisigCosignatoryModification(removeType, dave),
    ],
    nem.NetworkType.MIJIN_TEST
);

const aggregateTx2 = nem.AggregateTransaction.createComplete(
    nem.Deadline.create(),
    [
        ellenOrFrankMultisigTx.toAggregate(dave2.publicAccount),
        switchDaveToMultisigTx.toAggregate(alice.publicAccount),
    ],
    nem.NetworkType.MIJIN_TEST,
    []
);

const signedTx2 =  dave2.signTransactionWithCosignatories(
    aggregateTx2,
    [carol,ellen,frank],//bob,daveは必要なし
    GENERATION_HASH,
);

```

# 3.2 保留型アグリゲートトランザクション

- 302_bonded_multisigg.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/302_bonded_multisig.html
  - デモ
    - https://xembook.github.io/nem-tech-book/302_bonded_multisig.html

### 3.2.1 マルチシグ化トランザクションを生成する
```js
const multisigTx = nem.ModifyMultisigAccountTransaction.create(
    nem.Deadline.create(),
    1,1,
    [
        new nem.MultisigCosignatoryModification(addType,bob)
    ],
    nem.NetworkType.MIJIN_TEST
);

const aggregateTx = nem.AggregateTransaction.createBonded(
    nem.Deadline.create(),
    [
        multisigTx.toAggregate(alice.publicAccount),
    ],
    nem.NetworkType.MIJIN_TEST
);

```

### 3.2.2 ロックが承認されたらトランザクションを送信する
```js
const lockTx = nem.HashLockTransaction.create(
    nem.Deadline.create(),
    nem.NetworkCurrencyMosaic.createRelative(10),
    nem.UInt64.fromUint(480),
    signedTx,
    nem.NetworkType.MIJIN_TEST);

const lockSignedTx = alice.sign(lockTx, GENERATION_HASH);
transactionHttp.announce(lockSignedTx)

listener
.confirmed(alice.address)
.pipe(
    rxjs.filter((tx) => tx.transactionInfo !== undefined && tx.transactionInfo.hash === lockSignedTx.hash),
    rxjs.mergeMap(ignored => transactionHttp.announceAggregateBonded(signedTx))
)
```

### 3.2.3 保留されているトランザクションがあれば署名する
```js
accountHttp.aggregateBondedTransactions(alice.publicAccount)
.pipe(
    rxjs.mergeMap(_ => _),
    rxjs.filter((_) => {
        return !_.signedByAccount(bob.publicAccount)
    }),
    rxjs.map(_ => {
        return bob.signCosignatureTransaction(nem.CosignatureTransaction.create(_));
    }),
    rxjs.mergeMap(_ => {
        return transactionHttp.announceAggregateBondedCosignature(_);
    })
)
```

## 3.3 アトミックスワップ
- 303_atomic_swap.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/303_atomic_swap.html
  - デモ
    - https://xembook.github.io/nem-tech-book/303_atomic_swap.html

### 3.3.1 2つのチェーン環境を定義

```js
const alicePublic  = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const alicePrivate = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);

const bobPrivate  = nem.Account.createFromPrivateKey('BB68B933E188D9800A987E3DB055E9C4C05BDE53915308BF62910005A797A94D', nem.NetworkType.MIJIN_TEST);
const bobPublic = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST); //空
$('#address').text(alicePublic.address.address);

//パブリックチェーン想定
const NODE_PUBLIC = "https://catapult-test.opening-line.jp:3001";
const GEN_HASH_PUBLIC = "453052FDC4EB23BF0D7280C103F7797133A633B68A81986165B76FCE248AB235";
const txHttpPublic  = new nem.TransactionHttp(NODE_PUBLIC);
const accountHttpPublic = new nem.AccountHttp(NODE_PUBLIC);

//プライベートチェーン想定
const NODE_PRIVATE = "http://13.231.159.197:3000";
const GEN_HASH_PRIVATE = "FC0A097C9A8ADA831255440873328D68B7561D25D9132B083CC29B7D563A3D32";
const txHttpPrivate = new nem.TransactionHttp(NODE_PRIVATE);
const accountHttpPrivate = new nem.AccountHttp(NODE_PRIVATE);

```

### 3.3.2 パブリックチェーンのAlice資産をロック
```js
const lockTxPublic = nem.SecretLockTransaction.create(
    nem.Deadline.create(),
    nem.NetworkCurrencyMosaic.createRelative(1),
    nem.UInt64.fromUint(96 * 3600 / 15),
    nem.HashType.Op_Sha3_256,
    aliceSecret,
    bobPublic.address,
    nem.NetworkType.MIJIN_TEST
);

const lockTxPublicSigned = alicePublic.sign(lockTxPublic, GEN_HASH_PUBLIC);
txHttpPublic.announce(lockTxPublicSigned)
```

### 3.3.3 プライベートチェーンのBob資産をロック 

プライベートチェーン上のBobの資産をロックし、承認されればAliceがProofトランザクションで取得する。
```js
accountHttpPublic.unconfirmedTransactions(alicePublic.publicAccount)
.pipe(
    rxjs.mergeMap(_ => _),
    rxjs.filter((tx) => {
        return tx.transactionInfo !== undefined && tx.type === nem.TransactionType.SECRET_LOCK 
    }),
    rxjs.map(_ => {

        const lockTxPrivate = nem.SecretLockTransaction.create(
            nem.Deadline.create(),
            nem.NetworkCurrencyMosaic.createRelative(1),
            nem.UInt64.fromUint(84 * 3600 / 15),
            nem.HashType.Op_Sha3_256,
            _.secret,
            alicePrivate.address,
            nem.NetworkType.MIJIN_TEST
        );
        const lockTxPrivateSigned = bobPrivate.sign(lockTxPrivate, GEN_HASH_PRIVATE);
        return txHttpPrivate.announce(lockTxPrivateSigned)
    })
)
```

### 3.3.4 AliceがプライベートのBob資産を取得

```js
listenerPrivate
.confirmed(bobPrivate.address)
.pipe(

    rxjs.filter((tx) => tx.transactionInfo !== undefined && tx.type === nem.TransactionType.SECRET_LOCK ),
    rxjs.mergeMap(_ => {

        const aliceProof = random.toString('hex');
        const proofTxPrivate = nem.SecretProofTransaction.create(
            nem.Deadline.create(),
            nem.HashType.Op_Sha3_256,
            aliceSecret,
            alicePrivate.address,
            aliceProof,
            nem.NetworkType.MIJIN_TEST
        );
       
        const proofTxPrivateSigned = alicePrivate.sign(proofTxPrivate, GEN_HASH_PRIVATE);
        return txHttpPrivate.announce(proofTxPrivateSigned);
    })
)
```

### 3.3.5 BobがパブリックのAlice資産を取得

```js
//TX4:public Lock(alice)->bob by alice's proof
accountHttpPrivate.unconfirmedTransactions(alicePrivate.publicAccount)
.pipe(
    rxjs.mergeMap(_ => _),
    rxjs.filter(tx => {
        console.log(tx);
        return tx.transactionInfo !== undefined && tx.type === nem.TransactionType.SECRET_PROOF;
    }),
    rxjs.mergeMap(_ => {
        const proofTxPublic = nem.SecretProofTransaction.create(
            nem.Deadline.create(),
            nem.HashType.Op_Sha3_256,
            _.secret,
            bobPublic.address,
            _.proof,
            nem.NetworkType.MIJIN_TEST
        );
        const proofTxPublicSigned = bobPublic.sign(proofTxPublic, GEN_HASH_PUBLIC);
        return txHttpPublic.announce(proofTxPublicSigned)
    })
)
```

# 4 社会実装のヒント

ブロックチェーンをシステムを開発するためには、実社会ならではの実装パターンを把握しておくことが重要です。

## 4.1 所有

- 401_handover_multisig.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/401_handover_multisig.html
  - デモ
    - https://xembook.github.io/nem-tech-book/401_handover_multisig.html


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
- 402_auth.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/402_auth.html
  - デモ
    - https://xembook.github.io/nem-tech-book/402_auth.html

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

- 403_aggregate_comp_payload.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/403_aggregate_comp_payload.html
  - デモ
    - https://xembook.github.io/nem-tech-book/403_aggregate_comp_payload.html

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


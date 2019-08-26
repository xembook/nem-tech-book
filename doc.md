# はじめに

こんにちは、XEMBookです。この章ではブラウザではじめるNEMアプリケーション開発について解説してきたいと思います。

# 1 開発環境の準備

## 1.1 バンドルファイルの作成
nem2-sdkはNodeJS用に書かれたライブラリです。ブラウザで利用するためにはブラウザからライブラリを読み込めるようにバンドル化する必要があります。今回はbrowserifyというライブラリを利用してnem2-sdkをバンドル化します。バンドル化にはNode.jsの環境が必要になりますので、必要に応じてインストールしてください。インストール環境が無い人は以下のリポジトリに筆者のバンドルファイルを置いています。

- xembook/nem2-sdk-browserify
  - https://github.com/xembook/nem2-sdk-browserify


Node.jsのインストールを終えたらbrowserify、nem2-sdkをインストールし、バンドルファイルを作成します。

### 1.1.1 browserifyインストール
```sh
npm install browserify
```

### 1.1.2 nem2-sdkインストール
入手可能なバージョンを検索し、指定バージョンをインストールします。今回は執筆時最新バージョンの0.13.1を入れます。依存関係にあるrxjsは自動的にインストールされます。

```sh
npm info nem2-sdk versions
npm install nem2-sdk@0.13.0
```

ブラウザバンドル版を書き出します。書き出す場所によって、JavaScriptからの呼び出し方が異なります。今回はNode.jsが参照するライブラリnode_modulesが配置されているディレクトリと同じ場所で以下のコマンドを実行します。

```sh
 browserify -r ./node_modules/nem2-sdk -r ./node_modules/rxjs/operators -o nem2-sdk-0.13.1.js
```

nem2-sdk-0.13.1.jsというバンドルファイルが作成たと思います。
-r オプションをつけて外部js側からrequireが使えるようにしておきます。

それでは実際にHTMLファイルから読み込んでみましょう。まず、scriptタグでバンドルファイルの場所を指定します。

```html
<script src="nem2-sdk-0.13.1.js"></script>
```

次に 変数にライブラリを読み込みます。-r でオプション指定したパスから ".(ピリオド)"を取った形式で指定します。rxjs/operators　も同時に読み込んでおきましょう。

```js

const nem = require("/node_modules/nem2-sdk");
const rxjs = require("/node_modules/rxjs/operators");

```

これで　`nem.` と指定することで nem2-sdkが提供するクラスが使えるようになりました。



## 1.2 ブラウザを使ったデバッグ手法
今回はchromeブラウザをメインに利用して開発を行います。chromeには便利な開発者向けのデベロッパーツールがありますのでぜひ活用してください。
ここで簡単な操作方法を説明しておきます。以下のHTMLファイルを作成して保存してください。


```html
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<script src="nem2-sdk-0.13.1.js"></script>
<script>
const nem = require("/node_modules/nem2-sdk");
console.log(nem);
</script>
</head>
</html>

```

F12キーを押してコンソールを開きます。
Sourcesタブから表示中のHTMLファイルを選択し、以下コード以降の箇所でブレークポイントを設定します。
ページをリロードし、該当箇所に処理が差し掛かるとデバッグモードになります。

```js
const nem = require("/node_modules/nem2-sdk");
```

### 1.2.1 開発者コンソールを利用して変換する

デバッグモード中にConsoleタブを開きコマンドを入力することで、nem2-sdkの挙動を確認することができます。
以下のように入力値と出力値を区別して読み進めてください。

```
> 入力値
< 出力値
```

#### UInt64形式の数値を10進数数値に変換する

```js
> new nem.UInt64([27174,0]).compact()
< 27174
```

#### UInt64形式のIDをHEX文字列に変換する

```js
> new nem.UInt64([853116887,2007078553]).toHex()
< "77A1969932D987D7"
```

#### UInt64形式のtmiestampを日本時間に変換する
```js
> new Date(new nem.UInt64([1241926107,24]).compact() + Date.UTC(2016, 3, 1, 0, 0, 0, 0))
< Mon Jul 22 2019 19:05:41 GMT+0900 (日本標準時)
```

#### 10進数の数値を UInt64形式に変換する

```js

> nem.UInt64.fromUint(27174).toDTO()
< (2) [27174, 0]
```
#### HEX文字列をUInt64形式に変換する

```js
> nem.UInt64.fromHex("77A1969932D987D7").toDTO()
< (2) [853116887, 2007078553]
```

#### utf-8テキストををHEX文字列に変換

```js
o="";r=nem.Convert.rstr2utf8("日本語でも大丈夫");for (i in r){o+=r.charCodeAt(i).toString(16)}
< "e697a5e69cace8aa9ee381a7e38282e5a4a7e4b888e5a4ab"
```

#### HEX文字列をutf-8テキストに変換

```js
> o="";hex="e697a5e69cace8aa9ee381a7e38282e5a4a7e4b888e5a4ab";for(var i=0;i<hex.length;i+=2){o+= String.fromCharCode(parseInt(hex.substr(i,2),16));}decodeURIComponent(escape(o));
< "日本語でも大丈夫"
```

#### NEMESISブロック時間を取得

```js

> nem.Deadline.timestampNemesisBlock
< 1459468800
```

#### タイムスタンプ取得
```js
> nem.UInt64.fromUint((new Date()).getTime() - nem.Deadline.timestampNemesisBlock * 1000).toDTO();
< (2) [2360861166, 24]
```

#### 公開鍵からアドレス変換
```js

> nem.Address.createFromPublicKey("FF6E61F2A0440FB09CA7A530C0C64A275ADA3A13F60D1EC916D7F1543D7F0574", nem.NetworkType.MIJIN_TEST).address
< "SCAZJP2UPDEMZJZMY3CCUJQGXY7JMDVJ7CRG6ROT"
```

#### アドレスのBase32変換
```js
> nem.Address.createFromEncoded("9019CC9DFFB37ED9142E7937CA375FB65BF1349ED563503D67").address
< "SAM4ZHP7WN7NSFBOPE34UN27WZN7CNE62VRVAPLH"
```

##### アドレスのHEX変換
```js
> nem.Convert.uint8ToHex(nem.RawAddress.stringToAddress("SAM4ZHP7WN7NSFBOPE34UN27WZN7CNE62VRVAPLH"))
< "9019CC9DFFB37ED9142E7937CA375FB65BF1349ED563503D67"
```

# 2 サンプルプログラム基本編
## 2.1 サンプルテンプレート

- 201_sample_template.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/201_sample_template.html
  - デモ
    - https://xembook.github.io/nem-tech-book/201_sample_template.html

- deposit to
  - サンプルプログラムを動かすために必要なXEMを入金先アドレスを表示します。Faucetサービスなどを利用して表示されたアカウントアドレスに送金してください。送金必要額はサンプルプログラムによって異なります。
- 送信ボタン
  - 着金が確認されると送信ボタンが表示されます。このボタンをクリックすることでサンプルプログラムが開始します。
- signedTx
  - トランザクションの署名結果が出力されます。この文字列がノードにアナウンスされます。
- status
  - トランザクションが承認されるまでの間、このリンクで状態を確認することができます。エラーがあった場合もここで確認します。
- confirmed
  - トランサクションが承認された後、このリンクで取り込まれた情報を確認します。
- account
  - トランザクションの承認後、アカウントの状態変化を確認します。

### 2.1.1 body部分
画面に表示される項目順に動作の概要を説明します。画面表示時は入金先アドレスしか表示されず、手順を進めていくうちに確認可能な項目、実行可能な項目が順次表示していきます。

```html
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
</div>
```

### 2.1.2 読み込みスクリプト
nem2-sdkのほかにjqueryを使用します。
```html
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="nem2-sdk-0.13.1.js"></script>
```

### 2.1.3 実装サンプルプログラム
```html
<script>$(function() {

})</script>
```
今回のサンプルプログラムはすべてHTMLファイルの上に記述していきます。
jQueryの表示機能を使用するので、nem2-sdkの処理は上記 `$(function(){　}) `　で囲ってください。

### 2.1.4 固定値の定義
```js
const NODE = 'https://catapult-test.opening-line.jp:3001';
const GENERATION_HASH = "453052FDC4EB23BF0D7280C103F7797133A633B68A81986165B76FCE248AB235";
```
今回使用するノードとそのブロックチェーンが最初に生成したハッシュ値を定義します。

### 2.1.5 nem2-sdk関連モジュールの定義
```js
const nem = require("/node_modules/nem2-sdk");
const rxjs = require("/node_modules/rxjs/operators");
const sha3_256 = require("/node_modules/js-sha3").sha3_256;
```
nem2-sdkで使用するモジュールを読み込みます。

### 2.1.6 アカウント生成
```js
const alice = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
$('#address').text(alice.address.address);
```
サンプルプログラムで使用するアカウントを生成します。必要分を定義してください。
入金確認後に起動するサンプルが多いので、アドレスをコンソール出力しておくと便利です。

### 2.1.7 リスナー準備
```js

const wsEndpoint = NODE.replace('https', 'wss');
const listener = new nem.Listener(wsEndpoint, WebSocket);
let isInit = false;
listener.open().then(() => {

  //ここにリスナーを追加します。
});
```

### 2.1.8 リスナー定義
```js
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
            $('#wait2').remove();
            $('#result3').collapse('show');
        }
    },
    err => console.error(err)
);
```

### 2.1.9 トランザクション処理
```js

function process(){

    const tx = nem.TransferTransaction.create(
        nem.Deadline.create(),
        alice.address,
        [nem.NetworkCurrencyMosaic.createRelative(0)],
        nem.PlainMessage.create('Hello World!'),
        nem.NetworkType.MIJIN_TEST
    );
    const signedTx = alice.sign(tx,GENERATION_HASH);
    const txHttp = new nem.TransactionHttp(NODE);
    txHttp
    .announce(signedTx)
    .subscribe(_ => console.log(_), err => console.error(err));

    showInfo(NODE,signedTx,alice);
}
```
トランザクションの作成、署名、アナウンスを記述

### 2.1.10 結果出力
```js
function showInfo(node,signedTx,account){

    const pubkey = account.publicKey ;
    const address = account.address.address ;
    const hash = signedTx.hash

    $('#signedTx').val(signedTx.payload);
    $('#status ul').append(strLi(node,'/transaction/' + hash + '/status' ,hash + '/status'));
    $('#confirmed ul').append(strLi(node,'/transaction/' + hash ,hash ));
    $('#account ul').append(strLi(node,'/account/' + pubkey ,address ));
    $('#account ul').append(strLi(node,'/account/' + pubkey + '/transactions' ,address + '/transactions'));
}

function strLi(node,href,text){
    return '<li><a target="_blank" href="' + node + href + '">' + text + '</a></li>';
}
```
画面上への出力

### 2.1.11 ボタン定義
```js

$("#button1").click(
    function(){
        process();
        $('#result2').collapse('show');
        return false;
    }
);
```

## 2.2 監視

- 202_listener.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/202_listener.html
  - デモ
    - https://xembook.github.io/nem-tech-book/202_listener.html


### 2.2.1 ブロック監視
```js
listener
.newBlock()
.subscribe(function(_){

    console.log("==new block==");
    console.log(_.height.compact());
    console.log(new Date(_.timestamp.compact() + Date.UTC(2016, 3, 1, 0, 0, 0, 0)));
},
err => console.error(err));

```

### 2.2.2 トランザクションの監視
```js
blockHttp.getBlockTransactions(_.height.compact())
.subscribe((transactions) => {
    console.log("--transaction--");
    for(let transaction of transactions){
        console.log(transaction);

        for(let mosaic of transaction.mosaics){
            $("#table").append("<tr>" 
                +"<td>"+ _.height.compact() + "</td>"
                +"<td>"+ transaction.recipient.address + "</td>"
                +"<td>"+ mosaic.id.toHex() + "</td>"
                +"<td>"+ mosaic.amount.compact() + "</td>"
                + "</tr>"
            );
        }
    }
})
```

### 2.2.3 レシートの監視
```js
blockHttp.getBlockReceipts(_.height.compact())
.subscribe((receipts) => {
    console.log("--receipt--");
    for(let statement of receipts.transactionStatements){
        for(let receipt of statement.receipts){
            console.log(receipt);
        }
    }
    for(let statement of receipts.addressResolutionStatements){
        for(let receipt of statement.receipts){
            console.log(receipt);
        }
    }
    for(let statement of receipts.mosaicResolutionStatements){
        for(let receipt of statement.receipts){
            console.log(receipt);
        }
    }
})
```

### 2.2.4 アカウント監視
```js

//未承認トランザクションの監視
listener
.confirmed(alice.address)
.subscribe(
    function(_){
        console.log("==confirmed transaction(alice)==");
        if(!hasBuilt){
            console.log("[[start sample program]]");
            buildProcess();
            hasBuilt = true;
        }
    },
    err => console.error(err)
);

//承認済みトランザクションの監視
listener
.unconfirmedAdded(alice.address)
.subscribe(
    function(_){
        console.log("--unconfirmed transaction(alice)--");
        console.log(_);
    },
    err => console.error(err)
);
```

## 2.3 アグリゲートトランザクション(モザイク生成)
アグリゲートトランザクションを使うと複数のトランザクションを集約して１つのブロック内で処理を行うことができます。


- 203_ns_mosaic_link_sample.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/203_ns_mosaic_link_sample.html
  - デモ
    - https://xembook.github.io/nem-tech-book/203_ns_mosaic_link_sample.html


今回はネームスペースを作成し、モザイクに割り当てるまでの処理をまとめてみます。

### 2.3.1 ネームスペース作成
```js
    const namespaceTx = nem.RegisterNamespaceTransaction.createRootNamespace(
        nem.Deadline.create(),
        "xembook",
        nem.UInt64.fromUint(1),
        nem.NetworkType.MIJIN_TEST
    );
```

### 2.3.2 モザイク作成
```js
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
```

### 2.3.3 モザイク変更
```js
const mosaicChangeTx = nem.MosaicSupplyChangeTransaction.create(
    nem.Deadline.create(),
    mosaicDefTx.mosaicId,
    nem.MosaicSupplyType.Increase,
    nem.UInt64.fromUint(1000000),
    nem.NetworkType.MIJIN_TEST
);
```

### 2.3.4 モザイクとネームスペースのリンク
```js
const mosaicAliasTx = nem.AliasTransaction.createForMosaic(
    nem.Deadline.create(),
    nem.AliasActionType.Link,
    namespaceTx.namespaceId,
    mosaicDefTx.mosaicId,
    nem.NetworkType.MIJIN_TEST
);
```

### 2.3.5 集約
```js
    const aggregateTransaction = nem.AggregateTransaction.createComplete(
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
```

## 2.4 アグリゲートトランザクション(マルチシグ組成)
- 204_ns_account_link_multisig.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/204_ns_account_link_multisig.html
  - デモ
    - https://xembook.github.io/nem-tech-book/204_ns_account_link_multisig.html

### 2.4.1 ネームスペース作成
```js
const namespaceTx = nem.RegisterNamespaceTransaction.createRootNamespace(
    nem.Deadline.create(),
    "xembook",
    nem.UInt64.fromUint(1),
    nem.NetworkType.MIJIN_TEST
);
```

### 2.4.2 アカウントとネームスペースのリンク
```js
const accountAliasTx = nem.AliasTransaction.createForAddress(
    nem.Deadline.create(),
    nem.AliasActionType.Link,
    namespaceTx.namespaceId,
    alice.address,
    nem.NetworkType.MIJIN_TEST
);

```

### 2.4.3 マルチシグ化
```js
const multisigTx = nem.ModifyMultisigAccountTransaction.create(
    nem.Deadline.create(),
    1,1,
    [
        new nem.MultisigCosignatoryModification(
            nem.MultisigCosignatoryModificationType.Add,
            bob,
        )
    ],
    nem.NetworkType.MIJIN_TEST
);
```
Aliceをマルチシグに変更しBobを連署者とします。

### 2.4.4 集約
```js
const aggregateTransaction = nem.AggregateTransaction.createComplete(
    nem.Deadline.create(),
    [
        namespaceTx.toAggregate(alice.publicAccount),
        accountAliasTx.toAggregate(alice.publicAccount),
        multisigTx.toAggregate(alice.publicAccount),
    ],
    nem.NetworkType.MIJIN_TEST,
    []
);
```

### 2.4.5 署名
```js
const signedTransaction =  alice.signTransactionWithCosignatories(
    aggregateTransaction,
    [bob],
    GENERATION_HASH,
);
```

マルチシグを行うためには 署名者の署名も必要になります。
`signTransactionWithCosignatories` を使用してbobの署名を行います。

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


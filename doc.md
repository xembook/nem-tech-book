# はじめに

こんにちはXEMBookです。この章ではブラウザを通じてNEM/mijinの可能性について触れみたいと思います。ぜひ最後までお付き合いください。

## まず動かしてみたいひとへ
人が新しい技術に触れる時、そのきっかけはいろいろあります。でも「仕様書を見て感動したから」という人はあまりいません。もちろんNEMに使用されている技術は感動するに値する価値がありますが、たいていは「自分の信頼する誰かが勧めていたから」だったり「いつも便利に使ってるものがその技術で作られていたから」だったりするものです。新しい技術を採用する立場にある人も同様です。多くの技術を比較しなければいけないため、その技術習得のために新たなプラットフォームを身につけなければいかず、その労力は計り知れません。NEMテクノロジーの最も優れている点はだれでもすぐにブロックチェーンを安全に使える状態まで、すでに準備されていることです。また、社会実装というキーワードが用いられるようにブロックチェーンはITエンジニア以外の専門家にも注目してほしい技術です。今まで当たり前でなかった煩雑な一連の確認・決定作業が一瞬で終わることが新しいイノベーションを生み出します。この改善のタネはAIの活用と同じく現場にいる人間しか気づかないケースが多くあると感じています。ぜひ、本章のサンプルプログラムを動かして世界に公開された状態が更新されていく興奮を体感してみてください。

## 解説の流れ
- 開発環境の準備
  - バンドルファイルの作成とブラウザを使ったデバッグ手法について説明します
- サンプルプログラム（基礎編）
  - 全てのサンプルプログラムで共通して使うテンプレートの説明、ブロックやアカウントの状態監視、アグリゲートトランザクションについて説明します
- サンプルプログラム（応用編）
  - マルチレベルマルチシグ、保留型アグリゲートトランザクション、アトミックスワップについて説明します
- 社会実装のヒント
  - 所有、認証、トレーサビリティについて説明します

# 6.1 開発環境の準備
この章のサンプルプログラムを動かすための環境について説明します。NEMはREST APIが実装されており、基本的にはインターネットブラウザがあればクライアント側に提供されるすべての機能を実行可能です。そのため、開発には言語を選びません。また想定外のバグが侵入する可能性もなく、セキュリティ障害（脆弱性）が内部から発生することもありません。

## 6.1.1 バンドルファイルの作成
nem2-sdkはNodeJS用に書かれたライブラリです。ブラウザで利用するためにはブラウザからライブラリを読み込めるようにバンドル化する必要があります。今回はbrowserifyというライブラリを利用してnem2-sdkをバンドル化します。バンドル化にはNode.jsの環境が必要になりますので、必要に応じてインストールしてください。インストール環境が無い人は以下のリポジトリに筆者のバンドルファイルをご利用ください。

- xembook/nem2-sdk-browserify
  - https://github.com/xembook/nem2-sdk-browserify

Node.jsのインストールを終えたらbrowserify、nem2-sdkをインストールし、バンドルファイルを作成します。

### browserifyインストール
```sh
npm install browserify
```

### nem2-sdkインストール
入手可能なバージョンを検索し、指定バージョンをインストールします。今回は執筆時最新バージョンの0.13.1を入れます。依存関係にあるrxjsは自動的にインストールされます。

```sh
npm info nem2-sdk versions
npm install nem2-sdk@0.13.1
```

ブラウザバンドル版を書き出します。書き出す場所によって、JavaScriptからの呼び出し方が異なります。今回はNode.jsが参照するライブラリnode_modulesが配置されているディレクトリと同じ場所で以下のコマンドを実行します。
-r オプションをつけて外部js側からrequireが使えるようにしておきます。

```sh
browserify -r ./node_modules/nem2-sdk -r ./node_modules/rxjs/operators -r ./node_modules/js-sha3 -o nem2-sdk-0.13.1.js
```

nem2-sdk-0.13.1.jsというバンドルファイルが作成されました。
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



## 6.1.2 ブラウザを使ったデバッグ手法
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

F12キーを押してコンソールを開きます。Sourcesタブから表示中のHTMLファイルを選択し、以下コード以降の箇所でブレークポイントを設定します。ページをリロードし、該当箇所に処理が差し掛かるとデバッグモードになります。

```js
const nem = require("/node_modules/nem2-sdk");
```

### 開発者コンソールを利用して変換する

デバッグモード中にConsoleタブを開きコマンドを入力することで、nem2-sdkの挙動を確認することができます。入力値と出力値は以下のように区別して読み進めてください。

```
> 入力値
< 出力値
```

#### UInt64形式の数値を10進数数値に変換

```js
> new nem.UInt64([27174,0]).compact()
< 27174
```

#### UInt64形式のIDをHEX文字列に変換

```js
> new nem.UInt64([853116887,2007078553]).toHex()
< "77A1969932D987D7"
```

#### UInt64形式のtmiestampを日本時間に変換
```js
> new Date(new nem.UInt64([1241926107,24]).compact() + Date.UTC(2016, 3, 1, 0, 0, 0, 0))
< Mon Jul 22 2019 19:05:41 GMT+0900 (日本標準時)
```

#### 10進数の数値を UInt64形式に変換

```js

> nem.UInt64.fromUint(27174).toDTO()
< (2) [27174, 0]
```
#### HEX文字列をUInt64形式に変換

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

nem2-sdkが提供する型については、まだ若干のゆれが見られるため今後改善されていく可能性があります。

# 6.2 サンプルプログラム基礎編
サンプルプログラムを通してNEMで発行できるトランザクションの基礎的な部分を解説していきます。アグリゲートトランザクションは欠かせない機能となっており、本節で扱います。

## 6.2.1 サンプルテンプレート
本章であつかう全てのサンプルプログラムの動作的に共通する部分をここでまとめて説明しておきます。以後同様の説明はしませんが、プログラムの記述内容が不明な場合は一度ここに戻って読み直すと疑問点が解決するかもしれません。また、すべての説明にはソースコードとデモページのURLを記載していますので、併せてご参考ください。

- 201_sample_template.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/201_sample_template.html
  - デモ
    - https://xembook.github.io/nem-tech-book/201_sample_template.html

### 基本的な動作概要
画面に表示される項目順に動作の概要を説明します。画面初期表示時は入金先アドレスしか表示されず、手順を進めていくうちに確認可能な項目、実行可能な項目が順次表示していきます。

- deposit to
  - サンプルプログラムを動かすために必要なXEMを入金先アドレスを表示します。Faucetサービスなどを利用して表示されたアカウントアドレスに送金してください。送金必要額はサンプルプログラムによって異なります。
- 送信ボタン
  - 着金が確認されると送信ボタンが表示されます。このボタンをクリックすることでサンプルプログラムが開始します。
- signedTx
  - トランザクションの署名結果が出力されます。この文字列がノードにアナウンスされます。
- status
  - トランザクションが承認されるまでの間、このリンクでノード状態を直接確認することができます。エラーがあった場合もここで確認します。
- confirmed
  - トランサクションが承認された後、このリンクで取り込まれた情報を確認します。
- account
  - トランザクションの承認後、アカウントの状態変化を確認します。

### body部分
ここからはサンプルプログラムのソースコードを部分的に紹介していきます。
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

`class="collapse"` と指定された部分のdivは初期表示時には折りたたまれた状態です。着金やトランザクションの承認が進むたびに、プログラム側で開いていきます。

### 読み込みスクリプト
nem2-sdkのほかにjqueryやbootstrapを使用します。各種マニュアルの指定通りにbodyの閉じタグ直前に記述します。
```html
<script 
    src="https://code.jquery.com/jquery-3.3.1.slim.min.js" 
    integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" 
    crossorigin="anonymous">
</script>
<script 
    src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" 
    integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" 
    crossorigin="anonymous">
</script>
<script 
    src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" 
    integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" 
    crossorigin="anonymous">
</script>
<script src="nem2-sdk-0.13.1.js"></script>
```


### 実装サンプルプログラム
今回のサンプルプログラムはすべてHTMLファイルの上に記述していきます。
jQueryの表示機能を使用するので、nem2-sdkの処理は上記 `$(function(){　}) `　で囲ってください。
```html
<script>$(function() {

})</script>
```



### 2.1.4 固定値の定義
ノードの接続情報やGENERATION_HASH値などを指定します。
```js
const NODE = 'https://catapult-test.opening-line.jp:3001';
const GENERATION_HASH = "453052FDC4EB23BF0D7280C103F7797133A633B68A81986165B76FCE248AB235";
```


### nem2-sdk関連モジュールの定義
nem2-sdkで使用するモジュールを読み込みます。
```js
const nem = require("/node_modules/nem2-sdk");
const rxjs = require("/node_modules/rxjs/operators");
const sha3_256 = require("/node_modules/js-sha3").sha3_256;
```


### アカウント生成
サンプルプログラムで使用するアカウントを生成します。必要分を定義してください。
```js
const alice = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
$('#address').text(alice.address.address);
```


### リスナー準備
nem2-sdkではWebSocketを利用してノードの状態監視を行います。
```js

const wsEndpoint = NODE.replace('https', 'wss');
const listener = new nem.Listener(wsEndpoint, WebSocket);
let isInit = false;
listener.open().then(() => {

  //ここにリスナーを追加します。
});
```
これはブラウザJS特有の記述方法です。


### リスナー定義
さきほど準備したlistenerに対し処理内容を追加してきます。
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
- .unconfirmedAdded
  - 未承認データがaliceアカウントに追加されたときに処理
    - result1 divを開く
- .confirmed
  - 承認済みデータが追加されたときに処理
    - 初回は送信ボタンを表示する。
    - 以降はreult3 divを開く

### ボタン定義
ボタンクリック時の挙動を定義します。
```js

$("#button1").click(
    function(){
        process();
        $('#result2').collapse('show');
        return false;
    }
);
```

### トランザクション処理
トランザクションの定義からアナウンスまでを記述します。
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
- TransferTransaction.create でトランザクションの定義引数には以下のようなものをしていします
  - 締め切り、転送先、単位、メッセージ、ネットワークID
- alice.signでトランザクションに署名を行います
- txHttp.announceで署名されたトランザクションをネットワークにアナウンスします
- subscribeでネットワークからの応答が _ に代入されて返ってきます


### 結果出力
トランザクションの結果を画面上に出力します。
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

## 6.2.2 監視
ブロックチェーン技術を利用した開発で重要なのは、トランザクションの作成とブロックの状態監視です。ブロックの状態を監視できれば、ブロックチェーンをトリガーとして利用することができます。NEMではWebSocketを使って状態監視ができるので利用してみましょう。このサンプルプログラムの挙動はF12を押してConsole.logでご確認ください。

- 202_listener.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/202_listener.html
  - デモ
    - https://xembook.github.io/nem-tech-book/202_listener.html


### ブロック監視
ブロックが生成されるたびに通知を受け取ります。
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

### トランザクションの監視
前述の新しく生成されたブロック内に含まれているトランザクション情報を確認します。
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
- _.height.compact()
  - "_" 部分に新しいブロック情報を指定すると、ブロックの高さが返ります
- subscribeで含まれるトランザクション情報が取れるのでループ処理により以下の単位に分解します
  - トランザクション単位
    - モザイク単位


### レシートの監視
同様に新しく生成されたブロック内に含まれているレシート情報を確認します。取得できるレシート情報は3種類あります。
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


### アカウント監視
aliceアカウントに新しくトランザクションが追加されると通知されます。
```js

//未承認トランザクションの監視
listener
.confirmed(alice.address)
.subscribe(
    function(_){
        console.log("==confirmed transaction(alice)==");
        if(!isInit){
            console.log("[[start sample program]]");
            process();
            isInit = false;
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



## 6.2.3 アグリゲートトランザクション(モザイク生成)
アグリゲートトランザクションを使うと複数のトランザクションを集約して１つのブロック内で処理を行うことができます。

- 203_ns_mosaic_link_sample.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/203_ns_mosaic_link_sample.html
  - デモ
    - https://xembook.github.io/nem-tech-book/203_ns_mosaic_link_sample.html


### 動作概要
ネームスペースを作成し、モザイクに割り当てるまでの処理をまとめます。

### ネームスペース作成
有効期限1ブロックで"xembook"というネームスペースを作成（レンタル）。
```js
const namespaceTx = nem.RegisterNamespaceTransaction.createRootNamespace(
    nem.Deadline.create(),
    "xembook",
    nem.UInt64.fromUint(1),
    nem.NetworkType.MIJIN_TEST
);
```

### モザイク作成
有効期限1ブロックでモザイク作成。
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

### モザイク変更
モザイクの数量を1,000,000に変更。
```js
const mosaicChangeTx = nem.MosaicSupplyChangeTransaction.create(
    nem.Deadline.create(),
    mosaicDefTx.mosaicId,
    nem.MosaicSupplyType.Increase,
    nem.UInt64.fromUint(1000000),
    nem.NetworkType.MIJIN_TEST
);
```

### モザイクとネームスペースのリンク
モザイクにネームスペース"xembook"を割り当てます。
```js
const mosaicAliasTx = nem.AliasTransaction.createForMosaic(
    nem.Deadline.create(),
    nem.AliasActionType.Link,
    namespaceTx.namespaceId,
    mosaicDefTx.mosaicId,
    nem.NetworkType.MIJIN_TEST
);
```

### 集約
アグリゲートトランザクションで集約してネットワークにアナウンスします。
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
const signedTx = alice.sign(aggregateTx,GENERATION_HASH);
txHttp.announce(signedTx)
```
アグリゲートの種類はCompleteでネットワークに通知する前にすべての署名を集める必要がありますが、今回の署名が必要なアカウントはaliceだけなので、普通にsignとannounceでネットワークに通知できます。

## 6.2.4 アグリゲートトランザクション(マルチシグ組成)
もう一つアグリゲートトランザクションの例を見てみましょう。

- 204_ns_account_link_multisig.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/204_ns_account_link_multisig.html
  - デモ
    - https://xembook.github.io/nem-tech-book/204_ns_account_link_multisig.html

### 動作概要
ネームスペースを作成し、アカウントに割り当てたものをマルチシグ化します。

### ネームスペース作成
```js
const namespaceTx = nem.RegisterNamespaceTransaction.createRootNamespace(
    nem.Deadline.create(),
    "xembook",
    nem.UInt64.fromUint(1),
    nem.NetworkType.MIJIN_TEST
);
```

### アカウントとネームスペースのリンク
今回はcreateForAddressを使用します。
```js
const accountAliasTx = nem.AliasTransaction.createForAddress(
    nem.Deadline.create(),
    nem.AliasActionType.Link,
    namespaceTx.namespaceId,
    alice.address,
    nem.NetworkType.MIJIN_TEST
);
```

### マルチシグ化
Aliceをマルチシグ化しBobを連署アカウントに指定します。

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

### 集約

```js
const aggregateTx = nem.AggregateTransaction.createComplete(
    nem.Deadline.create(),
    [
        namespaceTx.toAggregate(alice.publicAccount),
        accountAliasTx.toAggregate(alice.publicAccount),
        multisigTx.toAggregate(alice.publicAccount),
    ],
    nem.NetworkType.MIJIN_TEST,
    []
);
const signedTx =  alice.signTransactionWithCosignatories(
    aggregateTx,
    [bob],
    GENERATION_HASH,
);
```
AggregateTransaction.createCompleteを見るとalicの署名だけで通りそうな気もします。しかし、実際にはマルチシグを行うためには 署名者の署名も必要になります。その場合は `signTransactionWithCosignatories` を使用してbobの署名を行います。

# 6.3 サンプルプログラム応用編
少し複雑なトランザクションに挑戦してみましょう。
## 6.3.1 マルチレベルマルチシグ

- 301_multilevel_multisig.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/301_multilevel_multisig.html
  - デモ
    - https://xembook.github.io/nem-tech-book/301_multilevel_multisig.html

### 動作概要
- Aliceをマルチシグ化し、Bob,Carol,Daveを連署アカウントに指定
- さらにDaveをマルチシグ化し、Ellen,Frankを連署アカウントに指定
- Daveの役割をDave2に譲渡するため、Dave2をマルチシグ化しEllen,Frankを連署アカウントに指定
- Aliceの連署アカウントからDaveを除き、Dave2を追加

### アカウント生成
```js
const alice = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const bob   = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const carol = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const dave = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const dave2 = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const ellen = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const frank = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
```

### マルチシグ組成
Dave2に譲渡する前の状態までアグリゲートトランザクションで一気に作り上げます。
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


### DaveからDave2に連署者を変更
前述のアグリゲートトランザクションが承認された後、以下の連署者変更のトランザクションを投げてみます。
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
Bob,Daveの秘密鍵を必要とせず、連署アカウントの構成を変更することができました。

# 6.3.2 保留型アグリゲートトランザクション
アグリゲートボンデッドトランザクションと呼ばれるものです。ボンデッドの翻訳がなかなか難しいのですが「保税」という意味合いがあるそうです。手続きが複雑になるのでマルチシグ化するという簡単なトランザクションで試してみます。

- 302_bonded_multisigg.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/302_bonded_multisig.html
  - デモ
    - https://xembook.github.io/nem-tech-book/302_bonded_multisig.html

### 動作概要
- マルチシグトランザクションの生成
- ロックトランザクションの通知
- マルチシグトランザクションの通知
- Bobの署名

### マルチシグ化トランザクションを生成する
マルチシグ化には双方の署名が必要なので、アグリゲートトランザクションを作成します。このときにcreateBondedで作成します。
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

### ロックが承認されたらトランザクションを送信する
トランザクションを通知する前にロックトランザクションを送信します。Aliceはまだシングルシグなので通常のsignで動作します。
リスナーを使ってAliceのアカウント状態を監視し、ロックトランザクションが出現次第、本トランザクションを通知します。
```js
const lockTx = nem.HashLockTransaction.create(
    nem.Deadline.create(),
    nem.NetworkCurrencyMosaic.createRelative(10),
    nem.UInt64.fromUint(480),
    signedTx,
    nem.NetworkType.MIJIN_TEST
);

const lockSignedTx = alice.sign(lockTx, GENERATION_HASH);
transactionHttp.announce(lockSignedTx)

listener
.confirmed(alice.address)
.pipe(
    rxjs.filter((tx) => tx.transactionInfo !== undefined && tx.transactionInfo.hash === lockSignedTx.hash),
    rxjs.mergeMap(ignored => txHttp.announceAggregateBonded(signedTx))
)
```
rxjsのpipeを使用するこどとで想定外のトランザクションが入ってきたときに誤動作を防ぐことができます。
またmergeMapの内部で新たなトランザクションを発行するテクニックも覚えておいてください。


### 保留されているトランザクションがあれば署名する
最後にBobの署名が必要になります。アカウント情報から保留状態のトランザクションを検出し、Bobが未署名の場合に連署して再アナウンスを行います。
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
        return txHttp.announceAggregateBondedCosignature(_);
    })
)
```
ここでも複雑なpipe処理を行っています。
- mergeMap
  - transactionの配列をストリーム化
- filter
  - Bobが未署名のトランザクションのみ通す
- map
  - Bobによる署名
- mergeMap
  - 署名結果をアナウンス結果をストリーム化


## 6.3.3 アトミックスワップ
2つのチェーン間でトークン交換をする方法アトミックスワップについて説明します。本当にトークンが飛ぶのではなく、パブリックチェーン上でAliceからBobへトークンを飛ばすと同時にプライベートチェーン上でBobからAliceへトークンを飛ばすといった仕組みです。

- 303_atomic_swap.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/303_atomic_swap.html
  - デモ
    - https://xembook.github.io/nem-tech-book/303_atomic_swap.html

### 2つのチェーン環境を定義

2種類のチェーンを準備して定義します。
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
bobPrivateアカウントはすでに資産があるアカウントを指定しておいてください。

### パブリックチェーンのAlice資産をロック
AliceからBobに向かってシークレットロックトランザクションを投げます。Bobにはまだ届きません。
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

### プライベートチェーンのBob資産をロック 

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

### AliceがプライベートのBob資産を取得

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

### BobがパブリックのAlice資産を取得
最後に、Bobがパブリックチェーン上でロックされいたAliceの送金を引き取ります。
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
Aliceがプライベートチェーン上でBobの資産を受け取るために使った _ .proof が鍵になります。
proof値はunconfirmedですでにばれているので、Aliceが取ろうとした瞬間Bobにも回収する権利が与えられているのがわかります。


# 6.4 社会実装のヒント
## 6.4.1 所有

- 401_handover_multisig.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/401_handover_multisig.html
  - デモ
    - https://xembook.github.io/nem-tech-book/401_handover_multisig.html


### 動作概要
- モザイク「item」を生成しAliceに割り当てます
- Aliceをマルチシグ化し、Bobを連署アカウントに指定します
- BobはAliceをCarolに譲渡し、CarolはBobに代金を支払います

### 所有
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

### 譲渡
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


## 6.4.2 認証
- 402_auth.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/402_auth.html
  - デモ
    - https://xembook.github.io/nem-tech-book/402_auth.html

### AliceがBobに監査人シールを送る
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

### BobがCarolに登録トランザクションを送る
```js
const sendAuthFromBobToCarolTx = nem.TransferTransaction.create(
    nem.Deadline.create(),
    carol.address,
    [nem.NetworkCurrencyMosaic.createRelative(0)],
    nem.EmptyMessage,
    nem.NetworkType.MIJIN_TEST
);

```

### Carolの署名を検証する
```js
const authTx = $('#authtx').val();
const signed = carol.signData(authTx);

if(carol.publicAccount.verifySignature(authTx, signed)){
  //ここからサーバ側で検証
}

```

### ログイン認証
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

## 6.4.3 トレーサビリティ

- 403_aggregate_comp_payload.html
  - ソースコード
    - https://github.com/xembook/nem-tech-book/blob/master/403_aggregate_comp_payload.html
  - デモ
    - https://xembook.github.io/nem-tech-book/403_aggregate_comp_payload.html

### トランザクション作成
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

### 別環境でBobが署名
```js
const bobSignedTx = nem.CosignatureTransaction.signTransactionPayload(bob, $('#aliceSignedTx').val(), GENERATION_HASH);
$('#bobSignedTxSignature').val(bobSignedTx.signature);
$('#bobSignedTxSigner').val(bobSignedTx.signer);

```

### 署名を集めてトランザクションを再作成
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




# ブラウザではじめるNEMアプリケーション開発

## はじめに
こんにちはXEMBookです。この章ではブラウザを通じてNEM/mijinの可能性について触れみたいと思います。ぜひ最後までお付き合いください。

#### まず動かしてみたいひとへ

人が新しい技術に触れる時、そのきっかけはいろいろあります。でも「仕様書を見て感動したから」という人はあまりいません。もちろんNEMに使用されている技術は感動するに値する価値がありますが、きっかけはたいてい「自分の信頼する誰かが勧めていたから」だったり「いつも便利に使ってるものがその技術で作られていたから」だったりするものです。少し興味が出てきたときに、すぐに動かせるものがないとそのよさに気付くことはなかなか難しいでしょう。また、新しい技術を採用する立場にある人にとっては、多くの技術を比較しなければいけないため、その技術習得のために新たなプラットフォームを身につけなければいかず、その労力は計り知れません。NEMテクノロジーの最も優れている点はだれでもすぐにブロックチェーンを安全に使える状態まで、すでに準備されていることです。また社会実装というキーワードが用いられるように、ブロックチェーンはITエンジニア以外の専門家にも注目してほしい技術です。「信用に基づく承認・決済」が一瞬で終わることは、煩雑な手続きの中で発生する信頼の損失に対する予防・保証コストを抑えることができ、運用フローが単純化されることでさらなるイノベーションを生み出す可能性があります。これらの改善のポイントはAIの活用と同じく、現場にいる人間しか気づけないケースも多くあると感じています。ぜひ、本章のサンプルプログラムを動かして世界に公開された情報が自分の手で簡単に更新されていく興奮を体感してみてください。

#### 解説の流れ

- 開発環境の準備
  - バンドルファイルの作成とブラウザを使ったデバッグ手法、ブロックチェーンのモデリング記法
- サンプルプログラム（基礎編）
  - 全てのサンプルプログラムで共通して使うテンプレートの説明、ブロックやアカウントの状態監視、アグリゲートトランザクション
- サンプルプログラム（応用編）
  - マルチレベルマルチシグ、保留型アグリゲートトランザクション、アトミックスワップ
- 社会実装のヒント
  - 所有、認証、トレーサビリティ

NEMが提供する機能については他の章で本格的に説明されていると思うので、本章ではブラウザという誰でも知っているツールと組み合わせた場合の活用の幅の拡張性やブロックチェーンを実社会に適用させるためのモデル表現について重点を置いて説明していきます。文中で紹介するサンプルプログラムは実際に動くデモも含めて全てgithubに公開していますので、ぜひ手を動かしながら読み進めてください。出版後の修正情報など随時掲載予定です。

- ソースコード
    - https://github.com/xembook/nem-tech-book/(各ファイル名.html)
- サンプルデモ
    - https://xembook.github.io/nem-tech-book/(各ファイル名.html)

なお、サンプルプログラムを動かすためには内部で生成されたアカウントへのXEM送金が必要です。利用しようとしているネットワークが公開しているfaucet（蛇口サービス）をご利用するか、ご自身でネットワークを構築されている場合は簡易ウォレットを作成して指定アドレスへ送金してください。

## 開発環境の準備

この章のサンプルプログラムを動かすための環境について説明します。NEMはREST APIが実装されており、HTTPリクエストに対応している言語であればNEMがクライアント側へ提供しているすべての機能を実行可能です。この章ではタイトル通りブラウザがサポートするJavaScriptを使用して解説していきます。

### バンドルファイルの作成

現在もっとも開発の進んでいるNEM2向けライブラリnem2-sdk(nem2-sdk-typescript-javascript)はNode.js用に書かれたライブラリです。これをブラウザで利用するためにはブラウザからライブラリを読み込めるようにバンドル化する必要があります。今回はbrowserifyというライブラリを利用します。バンドル化にはNode.jsの環境が必要になりますので、必要に応じてインストールしてください。インストール環境が無い人は以下のリポジトリのバンドルファイルをご利用ください。

**xembook/nem2-sdk-browserify**   
https://github.com/xembook/nem2-sdk-browserify

#### browserifyとnem2-sdkのインストール

```sh
npm install browserify
npm info nem2-sdk versions
npm install nem2-sdk@0.13.1
```

Node.jsのインストールを終えたらnpmを利用してbrowserify、nem2-sdkをインストールし、バンドルファイルを作成します。infoオプションで入手可能なバージョンを検索し、インストール時に@指定で希望するバージョンをインストールします。今回は執筆時最新バージョンの0.13.1を入れます。依存関係にあるrxjsやjs-sha3等は自動的にインストールされます。

###### バンドルファイル出力

```sh
browserify -r ./node_modules/nem2-sdk \
  -r ./node_modules/rxjs/operators \
  -r ./node_modules/js-sha3 \
  -o nem2-sdk-0.13.1.js
```
ブラウザバンドル版を書き出します。書き出す場所によって、JavaScriptからの呼び出し方が異なるのでご注意ください。今回はNode.jsが参照するライブラリnode_modulesが配置されているディレクトリと同じ場所で以下のコマンドを実行します。-r オプションをつけて外部js側からrequireが使えるようにしておきます。

###### HTMLファイルから外部スクリプト読み込み

```js
<script src="nem2-sdk-0.13.1.js"></script>
```

バンドルファイルが出力されたらHTMLファイルからアクセスできる場所に配置します。scriptタグでバンドルファイルの場所を指定します。

###### 変数へのライブラリ呼び出し
```js
const nem = require("/node_modules/nem2-sdk");
const rxjs = require("/node_modules/rxjs/operators");
```

次に 変数にライブラリを読み込みます。-r でオプション指定したパスから ".(ピリオド)"を取った形式で指定します。rxjs/operators　も同時に読み込んでおきましょう。これで　`nem.` と指定することで nem2-sdkが提供するクラスが使えるようになりました。

### ブラウザを使ったデバッグ手法

今回はGoogle Chromeブラウザを利用した開発を想定します。Chromeには便利な開発者向けのデベロッパーツールがありますのでぜひ活用してください。ここで簡単な操作方法を説明しておきます。以下のHTMLファイルを作成して保存してください。

```html
<!doctype html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <script src="nem2-sdk-0.13.1.js"></script>
        <script>
            const nem = require("/node_modules/nem2-sdk");
            console.log(nem); //ここにブレークポイントを設定
        </script>
    </head>
</html>
```

F12キーを押してコンソールを開きます。Sourcesタブから表示中のHTMLファイルを選択し、以下のコード以降の箇所でブレークポイントを設定します。ページをリロードし、該当箇所に処理が差し掛かるとデバッグモードになります。

```js
const nem = require("/node_modules/nem2-sdk");
```

#### 開発者コンソールを利用して変換する

デバッグモード中にConsoleタブを開きコマンドを入力することで、nem2-sdkの挙動を確認することができます。入力値と出力値は以下のように区別して読み進めてください。

```js
> 入力値
< 出力値
```

おそらく開発中に一度は変換して調べたくなる値について、調べ方を紹介しておきます。

###### UInt64形式の数値を10進数数値に変換

```js
> new nem.UInt64([27174,0]).compact()
< 27174
```

###### UInt64形式のIDをHEX文字列に変換

```js
> new nem.UInt64([853116887,2007078553]).toHex()
< "77A1969932D987D7"
```

###### UInt64形式のtmiestampを日本時間に変換

```js
> new Date(new nem.UInt64([1241926107,24]).compact() 
>           + Date.UTC(2016, 3, 1, 0, 0, 0, 0))
< Mon Jul 22 2019 19:05:41 GMT+0900 (日本標準時)
```

###### 10進数の数値を UInt64形式に変換

```js
> nem.UInt64.fromUint(27174).toDTO()
< (2) [27174, 0]
```

###### HEX文字列をUInt64形式に変換

```js
> nem.UInt64.fromHex("77A1969932D987D7").toDTO()
< (2) [853116887, 2007078553]
```

###### utf-8テキストををHEX文字列に変換

```js
> o="";
> r=nem.Convert.rstr2utf8("日本語でも大丈夫");
> for (i in r){o+=r.charCodeAt(i).toString(16)}
< "e697a5e69cace8aa9ee381a7e38282e5a4a7e4b888e5a4ab"
```

###### HEX文字列をutf-8テキストに変換

```js
> o="";
> hex="e697a5e69cace8aa9ee381a7e38282e5a4a7e4b888e5a4ab";
> for(var i=0;i<hex.length;i+=2){
>  o+= String.fromCharCode(parseInt(hex.substr(i,2),16));
> }
> decodeURIComponent(escape(o));
< "日本語でも大丈夫"
```

###### NEMESISブロック時間を取得

```js
> nem.Deadline.timestampNemesisBlock
< 1459468800
```

###### タイムスタンプ取得

```js
> nem.UInt64.fromUint(
>  (new Date()).getTime() - nem.Deadline.timestampNemesisBlock * 1000
> ).toDTO();
< (2) [2360861166, 24]
```

###### 公開鍵からアドレス変換

```js
> nem.Address.createFromPublicKey(
>  "FF6E61F2A0440FB09CA7A530C0C64A275ADA3A13F60D1EC916D7F1543D7F0574", 
>  nem.NetworkType.MIJIN_TEST).address
< "SCAZJP2UPDEMZJZMY3CCUJQGXY7JMDVJ7CRG6ROT"
```

###### アドレスのBase32変換

```js
> nem.Address.createFromEncoded(
>    "9019CC9DFFB37ED9142E7937CA375FB65BF1349ED563503D67"
> ).address
< "SAM4ZHP7WN7NSFBOPE34UN27WZN7CNE62VRVAPLH"
```

###### アドレスのHEX変換

```js
> nem.Convert.uint8ToHex(
>   nem.RawAddress.stringToAddress("SAM4ZHP7WN7NSFBOPE34UN27WZN7CNE62VRVAPLH")
> )
< "9019CC9DFFB37ED9142E7937CA375FB65BF1349ED563503D67"
```

nem2-sdkが提供する型については、まだ若干のゆれが見られるため今後改善されていく可能性があります。

### モデリング記法
本章ではブロックチェーンをわかりやすく理解するために独自モデリング記法を使用します。この章のみで使用します。

#### 書式
```js
listener(condition){
    (complete|bonded)[
        (amount:namespace.mosaic)account#label
        <-(n-of-m){
            [cosignatories]
        }.transactionType{
            transactionObject
        } => recipient
    ]
}
```

#### 説明
- listener(condition)
  - condition部に監視条件を指定、trueの場合にカッコ内の処理を実施します。
- (complete|bonded)[transactions]
  - アグリゲートトランザクションの種類を選択し、角カッコ内に配列形式でトランザクションを列記します。
- (amount:mosaic)account#label
  - 操作対象のアカウントと作用させるモザイクの種類・数量を指定します。#でアカウントの用途を記述します
- <-(n-of-m){[cosignatories]}
  - マルチシグ構成を表現します。モデリングの内容によってはcosignatoryは省略可能とします。
- .txtype{txObject}
  - トランザクションの種類とオブジェクト構成（必要部のみ）を表記します。
- =>recipient
  - 送金先や連署アカウントなどトランザクションの実行により効果を及ぼすアカウントを指定します。

#### 記述例

###### 所有

```
(1:XEM){Alice}
(1cat.currency){Alice}
```
Aliceが1XEMを所有している状態を表現します。コロンは省略可能とします。モザイクは割り当てられたネームスペース名で表記します。また、ピリオドでサブネームスペースを表現します。

###### 送金トランザクション

```
(1XEM)Alice#User=>Bob#Shop
(1XEM){Alice.transfer=>Bob}
(1XEM){Alice.transfer{message:"Hello!"}=>Bob}
```
AliceからBobへの送金を表現します。Aliceの後にトランザクションの種類、トランザクション情報を追記します。トランザクションの種類が自明であれば省略可能とします。

###### マルチシグ

```
Alice<-(2-of-3){Bob,Carol,Dave}
Alice<-(2-of-3){}
Alice<-(1-of-1){Bob}.transfer=>Carol
Alice<-(1-of-1){Bob.modify=>Carol}
```
マルチシグアカウント(Alice)と連署アカウントの状態を表現します。連署アカウントはモデリング上必要が無ければ省略可能とします。連署アカウントに対してマルチシグ修正トランザクションを表記することで、連署アカウント情報の変更も表現できます。

###### その他トランザクション

```
Alice.changeMosaic{id:mosaicId,amount:1000000}=>Alice
Alice.changeMosaic{id:mosaicId,amount:1000000}
```
送金先・自分以外のアカウントに作用しない状態変更トランザクションは「=>」表記を省略可能とします。

###### アグリゲートトランザクション

```
complete[
    (10XEM){Alice=>Bob},
    (1cat.ticket){Bob=>Alice},
    ...
]

Alice.bonded[
    (1XEM){Alice=>Bob},
    ...
]
```
トランザクションの集約は集約タイプの指定の後に続けてカッコ内に配列表記します。筆頭署名アカウントの情報が必要な場合は集約タイプの前にピリオド付きで表記します。

###### リスナー

```
listener(ボタンクリック){
  (1XEM){Alice=>Bob}
}

listener(Alice.confirmed){
  (1XEM){Alice=>Bob}
}
```

ユーザー側からのアクションやチェーン上の状態変更によるトランザクション発生などの条件をlistenerに続けて丸カッコ内で表記します。

## サンプルプログラム基礎編
NEMで発行できるトランザクションの基礎的な部分についてサンプルプログラムを通して解説していきます。

### サンプルテンプレート
本章で扱う全てのサンプルプログラムの共通する部分をここでまとめて説明しておきます。以後、プログラムの記述内容が不明な場合は一度ここに戻って読み直すと疑問点が解決するかもしれません。また、ソースコードのファイル名を記載していますので、前述したGitHubよりデモを実際に動作させてみて理解を深める参考にしてください。

- ソースコード
  - 201_sample_template.html  

#### 基本的な動作概要
画面に表示される項目順に動作の概要を説明します。画面初期表示時は入金先アドレスしか表示されず、手順を進めていくうちに確認可能な項目、実行可能な項目が順次表示されていきます。

- deposit to
  - サンプルプログラムを動かすために必要なXEMの入金用アドレスが表示されます。Faucetサービスなどを利用して表示されたアドレスへ事前に送金してください。送金必要額はサンプルプログラムによって異なります。
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

#### body部分
ここからはサンプルプログラムのソースコードを部分的に紹介していきます。

```js
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

#### 外部スクリプト読み込み

nem2-sdkのほかにjQueryやbootstrapを使用します。各種マニュアルの指定通りにbodyの閉じタグ直前に記述します。

```js
<script 
  src="https://code.jquery.com/jquery-3.3.1.slim.min.js" 
  integrity="sha384-..." crossorigin="anonymous">
</script>
<script 
  src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" 
  integrity="sha384-..." crossorigin="anonymous">
</script>
<script 
  src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" 
  integrity="sha384-..." crossorigin="anonymous">
</script>
<script src="nem2-sdk-0.13.1.js"></script>
```


#### JavaScriptの実装

```js
<script>$(function() {

})</script>
```

今回のサンプルプログラムはすべてHTMLファイルの上にJavaScriptで記述していきます。jQueryの表示機能を使用するので、nem2-sdkの処理は上記 `$(function(){　}) `　で囲ってください。

#### 固定値の定義

```js
const NODE = 'https://localhost:3001';
const GENERATION_HASH 
    = "453052FDC4EB23BF0D7280C103F7797133A633B68A81986165B76FCE248AB235";
```
ノードの接続情報やGENERATION_HASH値などを指定します。localhost部分は利用するNEMネットワークのノードを指定してください。

#### nem2-sdk関連モジュールの定義

```js
const nem = require("/node_modules/nem2-sdk");
const rxjs = require("/node_modules/rxjs/operators");
const sha3_256 = require("/node_modules/js-sha3").sha3_256;
```
broserifyを使用して作成したバンドルファイルから使用するモジュールを変数に読み込みます。なお、sha3_256は本章ではアトミックスワップの時のみに使用します。

#### アカウント生成

```js
const alice = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
$('#address').text(alice.address.address);
```
サンプルプログラムで使用するアカウントを生成し、画面上に表示します。送信をやりとりする場合は必要な数だけ定義します。よく使用されるアカウント名として、Alice,Bob,Carol,Dave,Ellen,Frankなどがあり、本章でもそれを踏襲します。

#### リスナー準備

```js

const wsEndpoint = NODE.replace('https', 'wss');
const listener = new nem.Listener(wsEndpoint, WebSocket);
let isInit = false;
listener.open().then(() => {

  //ここにリスナーを追加します。
});
```
nem2-sdkではWebSocketを利用してノードの状態監視を行います。listenerの定義について、これはブラウザJS特有の記述方法です。Node.jsで実行する場合は `const listener = new Listener('http://localhost:3000');`
などと指定してください。リスナーの準備が整うとopen()の内部が処理されます。 

#### リスナー登録

```js
listener
.unconfirmedAdded(alice.address)
.subscribe(_=> {
    $('#result1').collapse('show')
},err => console.error(err));

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

リスナーの準備が整うと登録してきます。リスナーの登録はこのように初期に行ってしまう場合もありますが、トランザクションの署名結果の情報が必要な場合もあるので、その時は署名後にリスナーを登録します。上記サンプル内の動作を簡単に説明します。

- listener.unconfirmedAdded
  - 未承認データがAliceアカウントに追加されたときに処理
    - id=result1のdivブロックを表示
- listener.confirmed
  - 承認済みデータがAliceアカウントに追加されたときに処理
    - 初回は送信ボタンを表示する
    - id=result1のdivブロックを表示

#### ボタン定義

```js
$("#button1").click(
    function(){
        process();
        $('#result2').collapse('show');
        return false;
    }
);
```

ボタンクリック時の挙動を定義します。HTMLでは簡単にボタンを設置できるので処理が複雑になる場合や処理のタイミングを少しずらしたい場合に設定しておくと便利です。ここではprocessファンクションを呼び出し、新しくid=result2のdivブロックを表示しています。

#### トランザクション処理

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
    .subscribe(result => console.log(result), err => console.error(err));

    showInfo(NODE,signedTx,alice);
}
```

この部分がサンプルプログラムの中核部分となります。トランザクションの定義からアナウンスまでを記述しています。

- **TransferTransaction.create**
  - トランザクションの作成
- **alice.sign**
    - トランザクションの署名
- **txHttp.announce**
    - 署名されたトランザクションをネットワークにアナウンス
- **subscribe**
    - ネットワークからアナウンス結果を受取

###### モデリング


```js
(0XEM)Alice#User.transfer{message:"Hello World!"} => Alice
```

トランザクション作成部分をモデリング。0XEMをメッセージ「Hello World!」をつけてAliceからAlice（自分）へ転送する、という意味になります。省略すると ``` (0XEM)Alice{message:"..."} => Alice ```となります。


#### 結果出力

```js
function showInfo(node,signedTx,account){

  const pubkey = account.publicKey ;
  const address = account.address.address ;
  const hash = signedTx.hash

  $('#signedTx').val(signedTx.payload);
  $('#status ul').append(
    strLi(node,'/transaction/' + hash + '/status' ,hash + '/status')
  );
  $('#confirmed ul').append(
    strLi(node,'/transaction/' + hash ,hash )
  );
  $('#account ul').append(
    strLi(node,'/account/' + pubkey ,address )
  );
  $('#account ul').append(
    strLi(node,'/account/' + pubkey + '/transactions' ,address + '/transactions')
  );
}

function strLi(node,href,text){
    return '<li><a target="_blank" href="' + node + href + '">' + text + '</a></li>';
}
```
トランザクションの結果を画面上に出力します。このあたりの処理はjQueryの仕様に基づいて書いています。Vue、React、Angularなどお使いの方は各項目変更してお使いください。

### 監視

ブロックチェーン技術を利用した開発で重要なのは、トランザクションの作成とブロックの状態監視です。ブロックの状態を監視できれば、ブロックチェーン内部の状態変化をトリガーとして利用することができます。NEMではWebSocketを使って状態監視ができるので利用してみましょう。このサンプルプログラムの挙動はF12を押してconsole.logでご確認ください。

- ソースコード
  - 202_listener.html

#### ブロック監視

ブロックが生成されるたびに通知を受け取ります。

```js
listener
.newBlock()
.subscribe(function(_){

  console.log("==new block==");
  console.log(_.height.compact());
  console.log(
      new Date(_.timestamp.compact() + Date.UTC(2016, 3, 1, 0, 0, 0, 0))
  );
},
err => console.error(err));

```

#### トランザクションの監視

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


#### レシートの監視

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

#### アカウント監視

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

### アグリゲートトランザクション(モザイク生成)

アグリゲートトランザクションを使うと複数のトランザクションを集約して１つのブロック内で処理を行うことができます。

- ソースコード
  - 203_ns_mosaic_link_sample.html


#### 動作概要
ネームスペースを作成し、モザイクに割り当てるまでの処理をまとめます。

#### モデリング
```
complete[
    Alice.createNamespace{namespace:"xembook"},
    Alice.defineMosaic{id:mosaicId},
    Alice.changeMosaic{id:mosaicId,amount:1000000},
    Alice.linkMosaic{namespace:"xembook",id:mosaicId}
]
```

#### ネームスペース作成

有効期限1ブロックで"xembook"というネームスペースを作成（レンタル）。

```js
const namespaceTx = nem.RegisterNamespaceTransaction.createRootNamespace(
    nem.Deadline.create(),
    "xembook",
    nem.UInt64.fromUint(1),
    nem.NetworkType.MIJIN_TEST
);
```

#### モザイク作成

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

#### モザイク変更

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

#### モザイクとネームスペースのリンク

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

#### 集約

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

### アグリゲートトランザクション(マルチシグ組成)
もう一つアグリゲートトランザクションの例を見てみましょう。

- ソースコード
  - 204_ns_account_link_multisig.html


#### 動作概要
ネームスペースを作成し、アカウントに割り当てたものをマルチシグ化します。

#### モデリング
```
complete(Alice)[
  　Alice.createNamespace{namespace:"xembook"},
  　Alice.linkAccount=>{namespace:"xembook",account:Alice},
  　Alice.addCosignatory=>Bob
]
```

#### ネームスペース作成
```js
const namespaceTx = nem.RegisterNamespaceTransaction.createRootNamespace(
    nem.Deadline.create(),
    "xembook",
    nem.UInt64.fromUint(1),
    nem.NetworkType.MIJIN_TEST
);
```

#### アカウントとネームスペースのリンク

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

#### マルチシグ化

Aliceをマルチシグ化しBobを連署アカウントに指定します。

```js
const multisigTx = nem.ModifyMultisigAccountTransaction.create(
    nem.Deadline.create(),
    1,1,
    [
        new nem.MultisigCosignatoryModification(addType,bob)
    ],
    nem.NetworkType.MIJIN_TEST
);
```

#### 集約
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
AggregateTransaction.createCompleteを見るとaliceの署名だけで通りそうな気もします。しかし、実際にはマルチシグを行うためには Bobの署名も必要になります。その場合は `signTransactionWithCosignatories` を使用してbobの署名を行います。

## サンプルプログラム応用編
少し複雑なトランザクションに挑戦してみましょう。
### マルチレベルマルチシグ

- ソースコード
  - 301_multilevel_multisig.html

#### 動作概要
- Aliceをマルチシグ化し、Bob,Carol,Daveを連署アカウントに指定
- さらにDaveをマルチシグ化し、Ellen,Frankを連署アカウントに指定
- Daveの役割をDave2に譲渡するため、Dave2をマルチシグ化しEllen,Frankを連署アカウントに指定
- Aliceの連署アカウントからDaveを除き、Dave2を追加

#### モデリング
```
Alice<-(2-of-3){
    Bob,
    Carol,
    Dave<-(1-of-2)(Ellen,Frank)
      => Dave2<-(1-of-2)(Ellen,Frank)
}
```
Aliceに3人の連署者を登録し、そのうちの1人（Dave）に対しさらに2人の連署者を登録します。最後にDaveを連署者から除外しDave2に変更します。

#### アカウント生成
```js
const alice = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const bob   = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const carol = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const dave = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const dave2 = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const ellen = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const frank = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
```

#### マルチシグ組成

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

const bobAndCarolAndDaveMultisigTx = nem.ModifyMultisigAccountTransaction.create(
    nem.Deadline.create(),
    2,2,
    [
        new nem.MultisigCosignatoryModification(addType, bob),
        new nem.MultisigCosignatoryModification(addType, carol),
        new nem.MultisigCosignatoryModification(addType, dave),
    ],
    nem.NetworkType.MIJIN_TEST
);

const aggregateTx = nem.AggregateTransaction.createComplete(
    nem.Deadline.create(),
    [
        ellenOrFrankMultisigTx.toAggregate(dave.publicAccount),
        bobAndCarolAndDaveMultisigTx.toAggregate(alice.publicAccount),
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


#### DaveからDave2に連署者を変更

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


### 保留型アグリゲートトランザクション
アグリゲートボンデッドトランザクションと呼ばれるものです。ボンデッドの翻訳がなかなか難しいのですが「保税」という意味合いがあるそうです。手続きが複雑になるのでマルチシグ化するという簡単なトランザクションで試してみます。

- ソースコード
  - 302_bonded_multisigg.html

#### 動作概要
- マルチシグトランザクションの生成
- ロックトランザクションの通知
- マルチシグトランザクションの通知
- Bobの署名

#### モデリング
```
(10XEM)Alice.hashLock
listener(lock.confirmed){
    bonded[
        Alice.addCosignatory=>Bob
    ]
}
```
10XEMのロックトランザクションを通知し、承認されるとアグリゲートボンデッドトランザクション内のマルチシグ化します。

#### マルチシグ化トランザクションを生成する

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

#### ロックが承認されたらトランザクションを送信する

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
    rxjs.filter((tx) => tx.transactionInfo !== undefined 
                && tx.transactionInfo.hash === lockSignedTx.hash),
    rxjs.mergeMap(ignored => txHttp.announceAggregateBonded(signedTx))
)
```
rxjsのpipeを使用することで想定外のトランザクションが入ってきたときに誤動作を防ぐことができます。
またmergeMapの内部で新たなトランザクションを発行するテクニックも覚えておいてください。


#### 保留されているトランザクションがあれば署名する

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


### アトミックスワップ

2つのチェーン間でトークン交換をする方法アトミックスワップについて説明します。本当にチェーンを超えてトークンが記録されるのではなく、パブリックチェーン上でAliceからBobへトークンを移動させると同時にプライベートチェーン上でBobからAliceへトークンを移動させるといった仕組みです。

- ソースコード
  - 303_atomic_swap.html

#### モデリング
```
(10PublicXEM)AlicePublic.SecretLock=>{secret:aliceSecret}
listener(AlicePublicSecretLock.unconfirmed){
    (10PrivateXEM)BobPrivate.SecretLock=>{secret:aliceSecret}
}
listener(BobPrivate.SecretLock.confirmed){
    AlicePrivate.SecretProof=>{proof:aliceProof}
}
listener(AlicePrivate.SecretLock.unconfirmed){
    BobPublic.SecretProof=>{proof:aliceProof}
}
```
#### 2つのチェーン環境を定義

2種類のチェーンを準備して定義します。

```js
const alicePublic  = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);
const alicePrivate = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST);

const bobPrivate  = nem.Account.createFromPrivateKey(
  'BB68B933E188D9800A987E3DB055E9C4C05BDE53915308BF62910005A797A94D', 
  nem.NetworkType.MIJIN_TEST
);
const bobPublic = nem.Account.generateNewAccount(nem.NetworkType.MIJIN_TEST); //空
$('#address').text(alicePublic.address.address);

//パブリックチェーン想定
const NODE_PUBLIC = "https://publichost:3001";
const GEN_HASH_PUBLIC 
    = "453052FDC4EB23BF0D7280C103F7797133A633B68A81986165B76FCE248AB235";
const txHttpPublic  = new nem.TransactionHttp(NODE_PUBLIC);
const accountHttpPublic = new nem.AccountHttp(NODE_PUBLIC);

//プライベートチェーン想定
const NODE_PRIVATE = "http://privatehost:3000";
const GEN_HASH_PRIVATE 
    = "FC0A097C9A8ADA831255440873328D68B7561D25D9132B083CC29B7D563A3D32";
const txHttpPrivate = new nem.TransactionHttp(NODE_PRIVATE);
const accountHttpPrivate = new nem.AccountHttp(NODE_PRIVATE);

```
bobPrivateアカウントはすでに資産があるアカウントを指定しておいてください。

#### パブリックチェーンのAlice資産をロック

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

#### プライベートチェーンのBob資産をロック 

プライベートチェーン上のBobの資産をロックし、承認されればAliceがProofトランザクションで取得する。

```js
accountHttpPublic.unconfirmedTransactions(alicePublic.publicAccount)
.pipe(
    rxjs.mergeMap(_ => _),
    rxjs.filter((tx) => {
        return tx.transactionInfo !== undefined 
        && tx.type === nem.TransactionType.SECRET_LOCK 
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
        const lockTxPrivateSigned 
            = bobPrivate.sign(lockTxPrivate, GEN_HASH_PRIVATE);
        return txHttpPrivate.announce(lockTxPrivateSigned)
    })
)
```

#### AliceがプライベートのBob資産を取得

```js
listenerPrivate
.confirmed(bobPrivate.address)
.pipe(

    rxjs.filter((tx) => tx.transactionInfo !== undefined 
                && tx.type === nem.TransactionType.SECRET_LOCK ),
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
       
        const proofTxPrivateSigned 
            = alicePrivate.sign(proofTxPrivate, GEN_HASH_PRIVATE);
        return txHttpPrivate.announce(proofTxPrivateSigned);
    })
)
```

#### BobがパブリックのAlice資産を取得

最後に、Bobがパブリックチェーン上でロックされたAliceの送金を引き取ります。

```js
accountHttpPrivate.unconfirmedTransactions(alicePrivate.publicAccount)
.pipe(
    rxjs.mergeMap(_ => _),
    rxjs.filter(tx => {
        console.log(tx);
        return tx.transactionInfo !== undefined 
        && tx.type === nem.TransactionType.SECRET_PROOF;
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
        const proofTxPublicSigned 
            = bobPublic.sign(proofTxPublic, GEN_HASH_PUBLIC);
        return txHttpPublic.announce(proofTxPublicSigned)
    })
)
```
Aliceがプライベートチェーン上でBobの資産を受け取るために使った _ .proof が鍵になります。
proof値はunconfirmedですでにばれているので、Aliceが取ろうとした瞬間Bobにも回収する権利が与えられているのがわかります。


## 社会実装のヒント
### 所有

実社会において、資産やリソースは譲渡・共有・貸与されるものであり、その実施には代表者や代理人によって行われることがよくあります。つまり、組織が持つ資産とそれを操作する個人がもつ権限を明確に分離する必要があります。NEMではマルチシグを用いることでそれらの関係を上手にモデリングすることができます。ここでいう所有とは実行権限を持つトークンを所有するのではなく、実行権限を持つアカウントを所有するという意味です。

#### サンプルプログラム
- ソースコード
  - 401_handover_multisig.html

#### 動作概要
- モザイク「item」を生成しAliceに割り当てます
- Aliceをマルチシグ化し、Bobを連署アカウントに指定します
- Bobはitemを所有するAliceをCarolに譲渡し、CarolはBobに代金を支払います

#### モデリング
```
準備
complete[
    Alice.createNamespace{namespace:"item"},
    Alice.linkAccount{namespace:"item",account:Alice},
    Alice.addCosignatory=>Bob,
]

譲渡
complete[
    Alice<-(1-of-1){Bob=>Carol},
    (0XEM){Carol=>Bob}
]

所有確認
Alice<-(1-of-1){Carol}
```

#### ポイント
- Aliceを資産としてマルチシグ化、Bobを権限保持者として連署アカウント指定することでBobがAliceを所有しているとみなします。
- 実社会での権限の構成変更が発生した場合はマルチシグを操作することで秘密鍵を受け渡しすることなく所有関係を変更できます。

#### (準備)モザイク「item」を所有するAliceをマルチシグ化し、Bobを連署アカウントに指定
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

#### マルチシグの連署アカウントをBobからCarolに変更しCarolはBobに代金を払う
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


### 認証

ブロックチェーン技術を使えば、過去に登録したものと同じ秘密鍵を所有しているかを秘密鍵を見せずに証明することができます。ただし、このままでは実社会に適用することはできません。認証には有効期限などサービス提供側のルールで過去の登録を消したい場合もあれば、退会などユーザ側の都合で登録を消す必要もありどちらにも対応しておく必要があります。また、実社会では登録された情報の有効期限は有限であり、一度証明された署名データを流用されてしまう場合も考えられます。また、できるだけ手数料をかけずにブロックチェーンを活用する必要があります。

- ソースコード
  - 402_auth.html

#### 動作概要
Aliceが組織所有のアカウントとします。AliceがCarolをユーザとして認定(登録)し、その後、Carolの申請に対して認証を行います。

- 認定
  - AliceがBobに認定権を付与するためのトークンを送信
  - BobがCarolを認定するためにトランザクション送信
- 申請
  - Carolが認定された当時のトランザクションを署名し、現在のブロックハッシュ値とともに認証申請
- 認証
  - BobにAliceからの認定権が存在することを確認
  - Carolの署名を検証し、当時のトランザクションの受信者と同じアカウントであることを確認
  - 認証申請時のブロックハッシュ値と現在のブロックハッシュ値が等しいことを確認

#### モデリング
```
認定
(1ValidToken){Alice=>Bob}
(0XEM){Bob=>Carol}

認証
Carol.sign{message:"authTx" , hash:lastBlockHash}
```
#### ポイント
- Aliceではなく認定権を付与されたBobがCarolを認定します。Bobから認定権をはく奪すればCarolは無効にできます。
- ユーザごとにBobの役割を持つアカウントが必要な場合は量が多くなるためマルチシグは使用しません。
- 署名データの再利用を防ぐために、申請時には推測不可能な現在のブロックハッシュ値を必要とします。
- 

#### AliceがBobに認定権付与モザイクを送る
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

#### BobがCarolに認定トランザクションを送る
```js
const sendAuthFromBobToCarolTx = nem.TransferTransaction.create(
    nem.Deadline.create(),
    carol.address,
    [nem.NetworkCurrencyMosaic.createRelative(0)],
    nem.EmptyMessage,
    nem.NetworkType.MIJIN_TEST
);
```
認定権を持つBobが送信することでCarolが認定されます。AliceとBobの秘密鍵はシステム運用側で把握しておく必要があります。

#### Carolの署名を検証する
```js
const authTx = $('#authtx').val();
const signed = carol.signData(authTx);

if(carol.publicAccount.verifySignature(authTx, signed)){
  //ここからサーバ側で検証
}

```

#### ログイン認証

Carolの署名が過去にAliceの委託したBobによって承認されたアカウントかどうかの確認を行います。

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
              return _.id.id.toDTO().toString() == ns.alias.mosaicId.toString();
            })
        )
    }),
    rxjs.mergeMap(_ => {
        return chainHttp.getBlockchainHeight()
        .pipe(
            rxjs.mergeMap(chain => {
                return blockHttp.getBlockByHeight(chain.compact())
                .pipe(
                    rxjs.filter(block => {
                      return block.hash === $('#blockhash').val();
                    }
                );
            })
        )

    }),
)
```
以下のような処理をしています。
- サンプルプログラムではアグリゲートトランザクションなのでinnerTransactionで抽出
- 申請時に指定されたトランザクションがBobからCarolに向けて送信されたものかチェック
- Bobのアカウント情報から所有モザイクを調査
- ネームスペース情報からBobの所有モザイクに認定権モザイクが含まれているかを検査（有効期限チェック）
- 申請時に指定されたブロックハッシュと現在のブロックハッシュ値が等しいかチェック


### トレーサビリティ
時系列に認証結果を記録していくことで、ブロックチェーンはトレーサビリティに利用することができます。不特定多数の組織が関与する物流や多くの部品を扱うメーカーなどは、その管理品質を企業の信頼に頼るしかありませんでした。IoTなどの技術が普及し、安全・適法な環境で製造・作業された証明がブロックチェーンで証明できるようになれば、信頼を得るためのコストを大幅に下げることができます。

- ソースコード
  - 403_aggregate_comp_payload.html

#### 動作概要
トレーサビリティについてはさまざまな方法があります。例えばNEM1のプライベートアポスティーユを製造工程ごとに更新していき公証アカウントを調べることでもトレース可能です。今回はNEM2ならではのオフラインで署名を集めて最後にネットワークにアナウンスする方法を紹介します。

- 品質保証トークン(safety)をAliceからBobに送信するトランザクション作成
- 品質保証トークンを持っていないBobからCarolに送信するトランザクション作成
- 上記アグリゲートトランザクションを作成し、Aliceが署名
- 署名結果をBobにテキストで渡し、Bobが連署
- Aliceが2人の連署結果からトランザクションを再作成しネットワークにアナウンス

#### ポイント
- オフラインでも署名が回せるようにシリアライズされた署名に連署を行います。
- 最後のAliceの署名が承認されてはじめてトランザクションとして取り込まれ追跡可能となります。

#### モデリング
```
complete[
    (1safety){Alice.transfer=>(0safety)Bob},
    (1safety){Bob.transfer=>(0safety)Carol}
]
```

#### ポイント
所有safetyモザイク量が0のBobに対し数量1をAliceから送信するトランザクションを作成します。同時に所有量が0のCarolに対しBobから送信するトランザクションを作成します。順序が正しくないとこのトランザクションはネットワーク上で承認されません。

#### トランザクション作成
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
const aliceSignedTx = aggregateTx.signWith(alice, GENERATION_HASH);
$('#aliceSignedTx').val(aliceSignedTx.payload);
$('#aliceSignedTxHash').val(aliceSignedTx.hash);
```

#### オフラインを想定した環境でBobが署名
```js
const bobSignedTx = nem.CosignatureTransaction.signTransactionPayload(
  bob, $('#aliceSignedTx').val(), 
  GENERATION_HASH
);
$('#bobSignedTxSignature').val(bobSignedTx.signature);
$('#bobSignedTxSigner').val(bobSignedTx.signer);
```
署名に必要なパラメータはHTML上からテキストで取得します。これはオフラインであっても、別環境であってもテキストさえ受け渡しできれば署名処理が可能なことを意味します。IoT技術など、常にオンラインであることが保証できない場合も連署アカウントは署名を行うことができます。

#### 署名を集めてトランザクションを再作成
```js
const cosignSignedTxs = [
    new nem.CosignatureSignedTransaction(
        $('#aliceSignedTxHash').val(),
        $('#bobSignedTxSignature').val(),
        $('#bobSignedTxSigner').val()
    )
];
const recreatedTx = nem.TransactionMapping.createFromPayload(
  $('#aliceSignedTx').val()
);
const signedTx = recreatedTx.signTransactionGivenSignatures(
  alice, 
  cosignSignedTxs, 
  GENERATION_HASH
);
txHttp.announce(signedTx);
```
最後にAliceがトランザクションを再作成してノードにアナウンスします。`signTransactionGivenSignatures` で署名済みトランザクションが生成されればあとはいつも通りです。

### さいごに
XEMBookによるブラウザを活用したNEMアプリケーション開発の解説は以上になります。ブロックチェーンNEMを活用したサービスが、みなさんの手でこれからたくさん生み出されることを楽しみにしています。

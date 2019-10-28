# 次世代NEMではじめるブロックチェーンアプリケーション
第３章に記載されている内容の訂正情報と補足説明です。

## 訂正

##### 現在nem2-sdk最新バージョンの0.14.3(fushicho)に随時対応中です。動作の確認が取れたものから下記「デモプログラム」のリストに対応済みと付記していきます。

#### 全般
- ネットワーク手数料に使用されるモザイクがcat.currencyからnem.xemに変更になりました。
  - NetworkCurrencyMosaic は現在 "cat.currency" を指すため使用できません。
- トランザクションに手数料指定が必須になりました。
  - 補足説明にてサンプルを動かすための入金必要額(min値)と実行時必要手数料(max値)を明記します。
- WebSocketのタイムアウト対策ロジックを追加します。

```js
setInterval(function(){listener.newBlock()}, 30000);
```

#### 3.4 サンプルプログラム基礎編
##### 監視
- blockHttp.getBlockReceiptsで取得できるstatementへのアクセス方法が変わりました。
- blockHttp.getBlockTransactionsで取得できるtransaction.recipientアドレスへのアクセス方法が変わりました。

##### アグリゲートトランザクション（モザイク⽣成）
- RegisterNamespaceTransaction -> NamespaceRegistrationTransaction
- MosaicProperties.create -> MosaicId.createFromNonce
- MosaicSupplyType -> MosaicSupplyChangeAction
- AliasActionType -> AliasAction
- (new nem.UInt64(_.alias.mosaicId)).toHex() -> _.alias.mosaicId.toHex()


#### 3.5 サンプルプログラム応用編
##### アトミックスワップ
現在、nem2-sdkのライブラリにバグがあるため実行できません。

#### 著者紹介 
page.217 
- 誤：XEMBookはリリース以降200万プレビューに迫るアクセスを記録。
- 正：XEMBookはリリース以降200万プレビューを超えるアクセスを記録。

## 補足説明

### 接続情報について

以下のノードと蛇口を利用して動作確認することができます。

#### ノード
https://jp5.nemesis.land:3001/
- GENERATION_HASH 17FA4747F5014B50413CCF968749604D728D7065DC504291EEE556899A534CBB

#### 蛇口（faucet） 
http://nf-catapult-testnet.herokuapp.com

### デモプログラム
#### 3.3 開発環境の準備

- ブラウザを使ったデバッグ手法(0.14.1対応済み)
  - https://xembook.github.io/nem-tech-book/101_debug.html

#### 3.4 サンプルプログラム基礎編
- サンプルテンプレート(0.14.1対応済み)
  - https://xembook.github.io/nem-tech-book/201_sample_template.html
  - 入金必要額 0.1XEM以上
  - 実行時必要手数料 0.1XEM以下
　　
- 監視(0.14.0対応済み)
  - https://xembook.github.io/nem-tech-book/202_listener.html

- アグリゲートトランザクション（モザイク⽣成）(0.14.0対応済み)
  - https://xembook.github.io/nem-tech-book/203_ns_mosaic_link_sample.html
  
    
#### 3.5 サンプルプログラム応用編
- マルチレベルマルチシグ(0.14.0対応済み)
  - https://xembook.github.io/nem-tech-book/301_multilevel_multisig.html
  
- 保留型アグリゲートトランザクション(0.14.0対応済み)
  - https://xembook.github.io/nem-tech-book/302_bonded_multisig.html

#### 3.6 社会実装のヒント

- 所有(0.14.3対応済み)
  - https://xembook.github.io/nem-tech-book/401_handover_multisig.html

- 認証(0.14.3対応済み)
  - https://xembook.github.io/nem-tech-book/402_auth.html

- トレーサビリティ(0.14.1対応済み)
  - https://xembook.github.io/nem-tech-book/403_aggregate_comp_payload.html

本書ではsafetyトークンを送っていますが、サンプルプログラムでは簡略化のためxemを送っています。

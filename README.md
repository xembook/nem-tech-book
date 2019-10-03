# 次世代NEMではじめるブロックチェーンアプリケーション
第３章に記載されている内容の訂正情報と補足説明です。

## 訂正

##### 現在nem2-sdk最新バージョンの0.13.2に随時対応中です。動作の確認が取れたものから下記「デモプログラム」のリストに対応済みと付記していきます。

#### 3.4 サンプルプログラム基礎編
##### 監視
- blockHttp.getBlockReceiptsで取得できるstatementへのアクセス方法が変わりました。
- blockHttp.getBlockTransactionsで取得できるtransaction.recipientアドレスへのアクセス方法が変わりました。

#### 著者紹介 
page.217 
- 誤：XEMBookはリリース以降200万プレビューに迫るアクセスを記録。
- 正：XEMBookはリリース以降200万プレビューを超えるアクセスを記録。

## 補足説明

### 接続情報について

以下のノードと蛇口を利用して動作確認することができます。

#### ノード
https://jp5.nemesis.land:3001/
- GENERATION_HASH 9F1979BEBA29C47E59B40393ABB516801A353CFC0C18BC241FEDE41939C907E7


#### 蛇口（faucet） 
http://nfwallet.z31.web.core.windows.net/login
- ※蛇口は現在停止中のようですので、テストウォレットからサンプルプログラムのアカウントに必要量を送金してください。


### デモプログラム
#### 3.3 開発環境の準備

- ブラウザを使ったデバッグ手法(0.13.3対応済み)
  - https://xembook.github.io/nem-tech-book/101_debug.html

#### 3.4 サンプルプログラム基礎編

- 監視(0.13.3対応済み)
  - https://xembook.github.io/nem-tech-book/202_listener.html
  

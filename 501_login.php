<?php
session_start();

//パスワードファイルの場所
$PASSWORD_PATH = $_SERVER['DOCUMENT_ROOT'] . '/../keys.json';

//クリックジャッキング対策
header('X-FRAME-OPTIONS: SAMEORIGIN');

// エラーメッセージの変数を初期化
$error = '';

// 認証済みかどうかのセッション変数を初期化
if (!isset($_SESSION['auth'])) {
	$_SESSION['auth'] = false;
}

if (isset($_POST['userid']) && isset($_POST['password'])) {

	$json = file_get_contents($PASSWORD_PATH);
	if ($json === false) {
		throw new RuntimeException('file not found.');
	}

	$users = json_decode($json, true);

	//jsonファイル解析エラー
	if (json_last_error() !== JSON_ERROR_NONE) {
		throw new RuntimeException(json_last_error_msg());
	}

	if ($users[$_POST['userid']]['hash'] === $_POST['password'] ){

		// セッション固定化攻撃を防ぐため、セッションIDを変更
		session_regenerate_id(true);
		$_SESSION['auth']     = true;
		$_SESSION['level']    = $users[$_POST['userid']]['level'];
	}

	if ($_SESSION['auth'] === false) {
		$error = 'ユーザーIDかパスワードに誤りがあります';
	}
}

if ($_SESSION['auth'] !== true) {
 ?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>認証フォーム</title>
<style>
	.container {
		width: 100%;
		text-align: center;
	}
</style>
</head>

<body>
<div class="container">
<div id="login">
<h1>認証フォーム</h1>

<?php
	if ($error) {
		// エラー文がセットされていれば赤色で表示
		echo '<p style="color:red;">'.$error.'</p>';
	}
?>

<form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="post">

	<dl>
		<dt><label for="userid">ユーザーID:</label></dt>
		<dd><input type="text" name="userid" value=""></dd>
	</dl>

	<dl>
		<dt><label for="password">パスワード</label></dt>
		<dd><input type="password" name="password" id="password" value=""></dd>
	</dl>
	<input type="submit" name="submit" value="ログイン">
</form>
</div>
</div>
</body>
</html>
<?php
	// スクリプトを終了し、認証が必要なページが表示されないようにする
	exit();
}
?>

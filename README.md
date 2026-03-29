# 車の乗りあわせ決定ツール

## 概要

名簿を元にして自動車の乗りあわせを決めるPHP製のWebアプリケーションです。

仕様は、[AGENTS.md](AGENTS.md)に記載しています。

## 最初のセットアップ

ファイル一式をApache/PHPのWebサーバーにアップロードします。そして、以下のファイルを作成します。

`data/admin.json`に下記のようなユーザー名とパスワードを記述します。

```json
{
    "admin1": "admin1-password",
    "admin2": "admin2-password"
}
```

`data/config.php`にファイルを作り、OpenAIのAPIキーを設定すると、スクショからメンバー選択が可能になります。

```php
<?php
$OPENAI_API_KEY = "xxxx";
```

# Webhookによる自動更新（Issue #28）

サーバーではこのリポジトリをGitでcloneし、`main`ブランチをチェックアウトしてください。PHPを実行するユーザーにリポジトリの書き込み権限と、`origin`を読み取るSSH鍵などを用意します。Git、`/bin/sh`、PHPの`proc_open`が必要です。ファイルのアップロードだけで設置した環境では利用できません。

`data/webhook.json`を作成し、十分に長いランダムなSecretを設定します。このファイルは`data/.htaccess`によりWebからのアクセスを禁止します。

```json
{
    "secret": "ここにランダムなSecretを設定"
}
```

GitHubのリポジトリ設定のWebhooksで、Payload URLを`https://設置先/car-pair/update.php`、Content typeを`application/json`とし、上と同じSecretを登録します。イベントはPushイベントだけを選択してください。

`update.php`がPOSTと`X-Hub-Signature-256`の署名を検証します。イベントが`push`、`repository.full_name`が`kujirahand/car-pair`、`ref`が`refs/heads/main`、`deleted`が`false`の場合だけ、設置先の`update.sh`を`/bin/sh`で実行します。GitHubの初回`ping`や他ブランチへのPushでは更新しません。Secret未設定は503、不正な署名は403、更新失敗は500、正常な更新や対象外イベントは200を返します。更新ログはPHPのエラーログに記録し、HTTP応答には内部のパスやGitの出力を含めません。

CLIから直接実行することもできます。どのディレクトリから実行しても、このスクリプトのあるリポジトリを更新します。

```sh
/bin/sh /サーバー上のパス/car-pair/update.sh
```

スクリプトは`origin/main`へのfast-forward更新だけを行います。追跡ファイルの変更、別ブランチ、ローカルコミットの分岐、通信エラーは終了コード1などの非ゼロで停止します。未追跡の名簿・履歴・設定ファイルは削除しません。ただし、更新で追加される追跡ファイルと同じパスの未追跡ファイルがある場合はGitが更新を拒否します。

HTTP応答は更新完了後に返すため、Gitの取得に時間がかかる場合はGitHub側でタイムアウトになることがあります。その場合はPHPのログとサーバーの現在のコミットで更新結果を確認してください。

同時実行はGit管理ディレクトリ内の`car-pair-update.lock`で防止します。強制終了やサーバー停止でロックが残った場合は、更新プロセスが動いていないことを確認してからその空ディレクトリを削除してください。

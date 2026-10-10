#!/bin/sh
set -eu

# Webhookサービスからこのスクリプトを呼び出す。実行場所に依存しない。
script_dir=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd -P)
cd "$script_dir"
branch=main

if [ "$(git rev-parse --show-toplevel)" != "$script_dir" ]; then
    echo '更新先がリポジトリのルートではありません。' >&2
    exit 1
fi

lock_dir=$(git rev-parse --git-path car-pair-update.lock)
if ! mkdir "$lock_dir" 2>/dev/null; then
    echo '別の更新処理が実行中です。' >&2
    exit 1
fi
trap 'rmdir "$lock_dir"' 0
trap 'exit 1' HUP INT TERM

if [ "$(git symbolic-ref --quiet --short HEAD)" != "$branch" ]; then
    echo "更新先ブランチは $branch である必要があります。" >&2
    exit 1
fi
if ! git diff --quiet || ! git diff --cached --quiet; then
    echo '追跡ファイルにローカル変更があるため更新を中止しました。' >&2
    exit 1
fi

# マージコミットや強制リセットを行わず、mainの前進のみを許可する。
git fetch origin "refs/heads/$branch:refs/remotes/origin/$branch"
target="refs/remotes/origin/$branch"
if ! git merge-base --is-ancestor HEAD "$target"; then
    echo 'ローカルの履歴がorigin/mainから分岐しているため更新を中止しました。' >&2
    exit 1
fi
git merge --ff-only "$target"
echo "更新完了: $(git rev-parse --short HEAD)"


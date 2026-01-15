# CRON設定

## 概要
AI自動投稿のCRONジョブをDockerコンテナで管理します。

## ファイル構成
- `Dockerfile`: CRONコンテナのイメージ定義
- `crontab`: CRONジョブの設定ファイル
- `README.md`: このファイル

## CRON実行間隔の変更方法

### 1. crontabファイルを編集
```bash
vim docker/cron/crontab
```

### 2. CRON式を変更
```
# 5分ごと
*/5 * * * * curl -X POST http://nextjs:3000/api/auto-creator/execute-auto >> /var/log/cron.log 2>&1

# 10分ごと
*/10 * * * * curl -X POST http://nextjs:3000/api/auto-creator/execute-auto >> /var/log/cron.log 2>&1

# 30分ごと
*/30 * * * * curl -X POST http://nextjs:3000/api/auto-creator/execute-auto >> /var/log/cron.log 2>&1

# 1時間ごと
0 * * * * curl -X POST http://nextjs:3000/api/auto-creator/execute-auto >> /var/log/cron.log 2>&1
```

### 3. コンテナを再起動
```bash
docker-compose restart cron
```

## ログの確認
```bash
# CRONコンテナのログを確認
docker-compose logs -f cron

# CRONジョブの実行ログを確認
docker exec anke-cron cat /var/log/cron.log
```

## トラブルシューティング

### CRONが実行されない場合
```bash
# コンテナの状態を確認
docker-compose ps

# CRONコンテナに入る
docker exec -it anke-cron sh

# crontabの内容を確認
cat /etc/crontabs/root

# 手動でCRONジョブを実行
curl -X POST http://nextjs:3000/api/auto-creator/execute-auto
```

### 実行間隔を即座に変更したい場合
```bash
# 1. crontabファイルを編集
vim docker/cron/crontab

# 2. CRONコンテナを再ビルド
docker-compose up -d --build cron
```

## CRON式の例

| 実行間隔 | CRON式 |
|---------|--------|
| 5分ごと | `*/5 * * * *` |
| 10分ごと | `*/10 * * * *` |
| 15分ごと | `*/15 * * * *` |
| 30分ごと | `*/30 * * * *` |
| 1時間ごと | `0 * * * *` |
| 2時間ごと | `0 */2 * * *` |
| 毎日午前9時 | `0 9 * * *` |
| 平日午前9時 | `0 9 * * 1-5` |

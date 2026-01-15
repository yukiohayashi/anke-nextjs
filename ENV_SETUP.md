# 環境変数セットアップガイド

## 概要

このドキュメントでは、アプリケーションに必要な環境変数の設定方法を説明します。

## セットアップ手順

### 1. .env.localファイルの作成

```bash
cp .env.example .env.local
```

### 2. 必須環境変数の設定

以下の環境変数は**必ず**設定する必要があります。

#### Supabase設定

```env
NEXT_PUBLIC_SUPABASE_URL=http://127.0.0.1:54321
NEXT_PUBLIC_SUPABASE_ANON_KEY=your-anon-key
SUPABASE_SERVICE_ROLE_KEY=your-service-role-key
```

**取得方法:**
- ローカル開発: Supabaseコンテナから取得
- 本番環境: Supabaseダッシュボード > Settings > API

#### Auth.js設定

```env
NEXTAUTH_URL=http://localhost:3000
NEXTAUTH_SECRET=your-nextauth-secret-min-32-characters
AUTH_URL=http://localhost:3000
```

**NEXTAUTH_SECRETの生成方法:**
```bash
openssl rand -base64 32
```

#### アプリケーションURL

```env
NEXT_PUBLIC_APP_URL=http://localhost:3000
NEXT_PUBLIC_SITE_URL=http://localhost:3000
```

**本番環境では実際のドメインに変更:**
```env
NEXT_PUBLIC_APP_URL=https://your-domain.com
NEXT_PUBLIC_SITE_URL=https://your-domain.com
```

---

## オプション環境変数

以下の環境変数は、該当する機能を使用する場合のみ設定してください。

### SNS OAuth認証

#### LINE OAuth

```env
LINE_CHANNEL_ID=your-line-channel-id
LINE_CHANNEL_SECRET=your-line-channel-secret
```

**取得方法:**
1. [LINE Developers](https://developers.line.biz/)にアクセス
2. プロバイダーとチャネルを作成
3. チャネル基本設定からChannel IDとChannel Secretを取得

#### X (Twitter) OAuth

```env
TWITTER_CLIENT_ID=your-twitter-client-id
TWITTER_CLIENT_SECRET=your-twitter-client-secret
```

**取得方法:**
1. [Twitter Developer Portal](https://developer.twitter.com/)にアクセス
2. アプリを作成
3. OAuth 2.0設定からClient IDとClient Secretを取得

### OpenAI API

```env
OPENAI_API_KEY=your-openai-api-key
```

**取得方法:**
1. [OpenAI Platform](https://platform.openai.com/)にアクセス
2. API Keysセクションで新しいキーを作成

**使用される機能:**
- AI自動タグ付け
- AI投稿生成
- AI投票・コメント機能

### Vercel Cron認証

```env
CRON_SECRET=your-random-secret-key
```

**生成方法:**
```bash
openssl rand -hex 32
```

**用途:**
- Vercel Cronジョブの認証
- 自動投票・コメント・いいね機能

### 管理者メールアドレス

```env
ADMIN_EMAIL=info@anke.jp
```

**用途:**
- お問い合わせ通知
- 通報通知
- ポイント交換申請通知

---

## 環境別設定

### 開発環境

```env
NODE_ENV=development
NEXTAUTH_URL=http://localhost:3000
NEXT_PUBLIC_APP_URL=http://localhost:3000
NEXT_PUBLIC_SITE_URL=http://localhost:3000
NEXT_PUBLIC_SUPABASE_URL=http://127.0.0.1:54321
```

### 本番環境

```env
NODE_ENV=production
NEXTAUTH_URL=https://your-domain.com
NEXT_PUBLIC_APP_URL=https://your-domain.com
NEXT_PUBLIC_SITE_URL=https://your-domain.com
NEXT_PUBLIC_SUPABASE_URL=https://your-project.supabase.co
```

---

## Vercelデプロイ時の設定

Vercelにデプロイする場合、以下の手順で環境変数を設定します:

1. Vercelダッシュボードにアクセス
2. プロジェクト > Settings > Environment Variables
3. 必要な環境変数を追加

**注意:**
- `NEXT_PUBLIC_`で始まる環境変数はクライアントサイドで公開されます
- シークレットキーは必ず安全に管理してください
- 本番環境では必ず実際のドメインを設定してください

---

## トラブルシューティング

### Supabaseに接続できない

- `NEXT_PUBLIC_SUPABASE_URL`が正しいか確認
- Supabaseコンテナが起動しているか確認
- ファイアウォール設定を確認

### 認証が動作しない

- `NEXTAUTH_SECRET`が32文字以上か確認
- `NEXTAUTH_URL`が現在のURLと一致しているか確認
- `AUTH_URL`が設定されているか確認

### メールが送信されない

- SMTP設定が正しいか確認（`/admin/mail/settings`）
- `ADMIN_EMAIL`が設定されているか確認

---

## セキュリティ注意事項

1. **.env.localをGitにコミットしない**
   - `.gitignore`に含まれていることを確認

2. **シークレットキーを共有しない**
   - 環境変数は安全に管理
   - チーム内でも直接共有しない

3. **本番環境では強力なシークレットを使用**
   - ランダム生成されたキーを使用
   - 定期的にローテーション

4. **NEXT_PUBLIC_で始まる変数に注意**
   - クライアントサイドで公開される
   - シークレット情報を含めない

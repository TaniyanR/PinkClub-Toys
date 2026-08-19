# PinkClub-Toys

FANZAの大人のおもちゃに特化したアフィリエイト商品サイトです。[PinkClub-FL](https://github.com/TaniyanR/PinkClub-FL) の公開UI・管理画面・SEO基盤を活かしつつ、動画サイトではなく物販サイトとして構成しています。

## 対象

- 大人のおもちゃ

FANZAの商品情報APIから価格・画像・商品詳細・カテゴリ・メーカー等を取得し、買い物目的で探しやすい導線を優先します。

## 公開サイト構成

- `TOP`
- `カテゴリ`
- `メーカー`
- `ランキング`
- `新着商品`
- 商品検索

動画版にあった女優・サンプル動画・動画向けシリーズはメイン導線から外し、商品画像、価格、カテゴリ、メーカー、レビュー、商品説明、関連商品、FANZA購入リンクを中心にしています。

## 主な機能

- FANZA Affiliate APIの商品取得・保存
- `site=FANZA / service=mono / floor=goods` の大人のおもちゃ取得
- 新着・人気商品のトップ表示
- 商品一覧、キーワード検索、カテゴリ・メーカー絞り込み
- 新着順、人気順、評価順、価格順の並び替え
- カテゴリ一覧・カテゴリ別商品
- メーカー一覧・メーカー別商品
- 人気商品ランキング
- 商品詳細（画像、価格、参考価格、メーカー、カテゴリ、発売日、商品番号、レビュー、商品説明）
- 同カテゴリを優先した関連商品
- FANZA商品ページへのアフィリエイト導線
- Product JSON-LD、OGP、canonical、商品向けサイトマップ
- WordPress風の管理画面
- API認証情報の保存、10件テスト取得、cron自動取得
- 初回セットアップ、DBマイグレーション、ログ表示
- RSS、アクセス解析

## API取得先

| site | service | floor | 用途 |
| --- | --- | --- | --- |
| FANZA | mono | goods | 大人のおもちゃ |

取得先は `config/config.php` の `dmm.catalog_targets` で管理します。DMM/FANZA側のフロア構成が変更された場合は、管理画面の「Floor同期」で取得したコードに合わせて修正してください。

## 物販向け表示で優先する項目

1. 商品画像
2. 商品名
3. 価格・通常価格
4. メーカー
5. カテゴリ
6. レビュー評価・件数（取得できる場合）
7. 発売日
8. 商品説明
9. FANZA商品ページへのアフィリエイトリンク
10. 同カテゴリ・同メーカーの関連商品

## 必要環境

- PHP 8.1以上
- MySQL 8.0またはMariaDB 10.5以上
- PDO MySQL、mbstring、JSON、cURLまたはallow_url_fopen
- Apache / nginx
- cron（自動取得を使う場合）

XAMPPでも動作確認できます。

## セットアップ

1. ファイル一式をサーバーへ配置します。
2. `/public/setup_check.php` を開きます。
3. DBホスト、ポート、DB名、ユーザー名、パスワードを保存します。
4. セットアップを実行します。
5. `/public/login0718.php` からログインします。
6. 管理画面の「商品情報API設定」でAPI IDとアフィリエイトIDを保存します。
7. 「10件テスト取得」で接続と保存を確認します。

初期管理者は `admin` / `password` です。公開前に必ず変更してください。

## 自動取得

公開アクセスではAPI同期を実行しません。次をcronから10分間隔で実行してください。

```bash
php /path/to/PinkClub-Toys/scripts/auto_import.php
```

## 主要URL

- 公開トップ: `/public/`
- 商品一覧: `/public/items.php`
- カテゴリ一覧: `/public/genres.php`
- メーカー一覧: `/public/makers.php`
- 人気ランキング: `/public/rankings.php`
- 管理ログイン: `/public/login0718.php`
- 管理トップ: `/admin/index.php`
- セットアップ確認: `/public/setup_check.php`
- API設定: `/admin/api_items.php`
- Floor同期: `/admin/sync_floors.php`

## 設定とセキュリティ

- DB接続情報やAPI認証情報をGitへコミットしないでください。
- `config.local.php`、ログ、セッション情報は公開しないでください。
- 管理者パスワードを変更し、HTTPSで運用してください。
- 本サイトは成人向けコンテンツを扱います。法令、広告主規約、年齢確認要件を確認してください。

## クレジット

<a href="https://affiliate.dmm.com/api/" target="_blank" rel="nofollow"><img src="https://p.dmm.co.jp/p/affiliate/web_service/r18_135_17.gif" alt="WEB SERVICE BY FANZA" width="135" height="17"></a>

商品情報はDMM/FANZA Affiliate APIを利用します。

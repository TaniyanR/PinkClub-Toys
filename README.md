# PinkClub-Toys

FANZAの大人のおもちゃに特化したアフィリエイト商品サイトです。[PinkClub-FL](https://github.com/TaniyanR/PinkClub-FL) の公開UI・管理画面・SEO基盤を活かしつつ、動画サイトではなく物販サイトとして構成します。

## 対象

- 大人のおもちゃ

FANZAの商品情報APIから価格・画像・商品詳細・カテゴリ・メーカー等を取得し、買い物目的で探しやすい導線を優先します。

## 公開サイトの基本方針

- メイン導線は `TOP / 商品一覧 / カテゴリ / メーカー`
- 女優・サンプル動画を主導線にしない
- 商品画像、価格、カテゴリ、メーカー、商品説明、関連商品を優先
- ジャンルAPIデータは公開画面では「カテゴリ」として扱う
- レーベル・シリーズはAPI実データを確認し、有用な場合のみ補助導線として利用

## 主な機能

- FANZA Affiliate APIの商品取得・保存
- `site=FANZA / service=mono / floor=goods` の大人のおもちゃ取得
- 商品一覧、検索、詳細
- カテゴリ一覧・カテゴリ別商品
- メーカー一覧・メーカー別商品
- 人気商品・関連商品の表示基盤
- WordPress風の管理画面
- API認証情報の保存、10件テスト取得、cron自動取得
- 初回セットアップ、DBマイグレーション、ログ表示
- SEO、OGP、JSON-LD、サイトマップ、RSS、アクセス解析

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

動画固有のサンプル動画・女優一覧・動画向けシリーズ導線は、Toysでは基本機能として扱いません。

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

複数取得先がある版では、実行ごとに次の取得先へ進みます。offsetは取得先ごとに保存されます。

## 主要URL

- 公開トップ: `/public/`
- 商品一覧: `/public/items.php`
- カテゴリ一覧: `/public/genres.php`
- メーカー一覧: `/public/makers.php`
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

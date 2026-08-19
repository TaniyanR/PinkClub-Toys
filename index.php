<?php
declare(strict_types=1);

require_once __DIR__ . '/public/_bootstrap.php';
require_once __DIR__ . '/lib/repository.php';
require_once __DIR__ . '/public/partials/toys_product_ui.php';

$title = 'トップ';
$pageDescription = 'FANZAの大人のおもちゃを新着・人気・カテゴリ・メーカーから探せるPinkClub Toysです。';
$canonicalUrl = public_url('');

$newItems = [];
$popularItems = [];
$genres = [];
$makers = [];
try { $newItems = fetch_items('date_published_desc', 8, 0); } catch (Throwable) { $newItems = []; }
try { $popularItems = fetch_items('popularity_desc', 8, 0); } catch (Throwable) { $popularItems = []; }
try {
    $genres = db()->query('SELECT g.id,g.name,COUNT(DISTINCT ig.item_id) AS item_count FROM genres g INNER JOIN item_genres ig ON ig.dmm_id=g.dmm_id INNER JOIN items i ON i.id=ig.item_id WHERE ' . items_product_source_where('i') . ' GROUP BY g.id,g.name ORDER BY item_count DESC,g.name ASC LIMIT 24')->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable) { $genres = []; }
try {
    $makers = db()->query('SELECT m.id,m.name,COUNT(DISTINCT im.item_id) AS item_count FROM makers m INNER JOIN item_makers im ON im.dmm_id=m.dmm_id INNER JOIN items i ON i.id=im.item_id WHERE ' . items_product_source_where('i') . ' GROUP BY m.id,m.name ORDER BY item_count DESC,m.name ASC LIMIT 24')->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable) { $makers = []; }

require __DIR__ . '/public/partials/header.php';
toys_render_styles();
?>
<section class="pcf-hero">
  <h1>大人のおもちゃを探す</h1>
  <p>新着・人気・カテゴリ・メーカーから商品を探せます。</p>
  <form class="toys-toolbar" method="get" action="<?= e(public_url('items.php')) ?>">
    <label style="flex:1;min-width:220px">商品検索
      <input type="search" name="q" placeholder="商品名・カテゴリ・メーカー">
    </label>
    <button type="submit">検索</button>
  </form>
</section>

<?php if ($newItems !== []): ?>
<section class="toys-section">
  <h2>新着商品</h2>
  <div class="toys-grid"><?php foreach ($newItems as $item): toys_render_product_card($item); endforeach; ?></div>
  <p><a class="toys-button" href="<?= e(public_url('items.php?sort=new')) ?>">新着商品をもっと見る</a></p>
</section>
<?php endif; ?>

<?php if ($popularItems !== []): ?>
<section class="toys-section">
  <h2>人気商品ランキング</h2>
  <div class="toys-grid"><?php foreach ($popularItems as $item): toys_render_product_card($item); endforeach; ?></div>
  <p><a class="toys-button" href="<?= e(public_url('rankings.php')) ?>">ランキングをもっと見る</a></p>
</section>
<?php endif; ?>

<?php if ($genres !== []): ?>
<section class="toys-section">
  <h2>カテゴリから探す</h2>
  <div class="toys-chips">
    <?php foreach ($genres as $genre): ?><a class="toys-chip" href="<?= e(public_url('genre.php?id=' . (int)$genre['id'])) ?>"><?= e((string)$genre['name']) ?>（<?= e(number_format((int)$genre['item_count'])) ?>）</a><?php endforeach; ?>
  </div>
  <p><a href="<?= e(public_url('genres.php')) ?>">カテゴリ一覧を見る</a></p>
</section>
<?php endif; ?>

<?php if ($makers !== []): ?>
<section class="toys-section">
  <h2>メーカーから探す</h2>
  <div class="toys-chips">
    <?php foreach ($makers as $maker): ?><a class="toys-chip" href="<?= e(public_url('maker.php?id=' . (int)$maker['id'])) ?>"><?= e((string)$maker['name']) ?>（<?= e(number_format((int)$maker['item_count'])) ?>）</a><?php endforeach; ?>
  </div>
  <p><a href="<?= e(public_url('makers.php')) ?>">メーカー一覧を見る</a></p>
</section>
<?php endif; ?>

<section class="toys-section">
  <h2>商品を選ぶときのポイント</h2>
  <p>商品ごとに価格、メーカー、カテゴリ、レビュー情報をまとめています。価格や在庫、仕様は変更される場合があるため、購入前にFANZAの商品ページで最新情報をご確認ください。</p>
</section>

<?php require __DIR__ . '/public/partials/footer.php'; ?>

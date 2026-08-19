<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../lib/repository.php';
require_once __DIR__ . '/partials/toys_product_ui.php';

$title = '人気商品ランキング';
$pageDescription = 'PinkClub Toysでよく見られている大人のおもちゃの人気ランキングです。';
$canonicalUrl = public_url('rankings.php');
$items = [];
try { $items = fetch_items('popularity_desc', 40, 0); } catch (Throwable) { $items = []; }

require __DIR__ . '/partials/header.php';
toys_render_styles();
?>
<h1>人気商品ランキング</h1>
<p>商品ページの閲覧数をもとに、人気の商品を掲載しています。</p>
<?php if ($items !== []): ?>
  <div class="toys-grid">
    <?php foreach ($items as $index => $item): ?>
      <div style="position:relative">
        <span style="position:absolute;z-index:2;top:8px;left:8px;padding:5px 9px;border-radius:999px;background:#111;color:#fff;font-weight:900"><?= $index + 1 ?>位</span>
        <?php toys_render_product_card($item); ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="toys-empty">ランキングデータがまだありません。</div>
<?php endif; ?>
<section class="toys-section"><p><a class="toys-button" href="<?= e(public_url('items.php')) ?>">商品一覧を見る</a></p></section>
<?php require __DIR__ . '/partials/footer.php'; ?>

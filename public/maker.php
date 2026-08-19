<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../lib/repository.php';
require_once __DIR__ . '/partials/toys_product_ui.php';

$id = max(1, (int)get('id', 0));
$maker = fetch_maker($id);
if (!$maker) {
    require __DIR__ . '/404.php';
    exit;
}

$page = max(1, (int)get('page', 1));
$perPage = 32;
$offset = ($page - 1) * $perPage;
$name = trim((string)($maker['name'] ?? 'メーカー'));
$title = $name . 'の商品';
$pageDescription = $name . 'の大人のおもちゃを新着順・人気順で紹介します。';
$canonicalUrl = public_url('maker.php?id=' . $id);
$sort = trim((string)get('sort', 'new'));
$orderBy = $sort === 'popular' ? 'i.view_count DESC,i.id DESC' : 'i.release_date DESC,i.id DESC';

$total = 0;
$items = [];
try {
    $count = db()->prepare('SELECT COUNT(DISTINCT i.id) FROM items i INNER JOIN item_makers im ON im.item_id=i.id INNER JOIN makers m ON m.dmm_id=im.dmm_id WHERE m.id=:id AND ' . items_product_source_where('i'));
    $count->execute([':id' => $id]);
    $total = (int)$count->fetchColumn();

    $stmt = db()->prepare('SELECT DISTINCT i.* FROM items i INNER JOIN item_makers im ON im.item_id=i.id INNER JOIN makers m ON m.dmm_id=im.dmm_id WHERE m.id=:id AND ' . items_product_source_where('i') . ' ORDER BY ' . $orderBy . ' LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('Toys maker page failed: ' . $e->getMessage());
}

require __DIR__ . '/partials/header.php';
toys_render_styles();
?>
<nav class="pcf-breadcrumb" aria-label="パンくず"><span class="pcf-breadcrumb__item"><a href="<?= e(public_url('')) ?>">ホーム</a></span><span class="pcf-breadcrumb__item"><a href="<?= e(public_url('makers.php')) ?>">メーカー</a></span><span class="pcf-breadcrumb__item"><?= e($name) ?></span></nav>
<h1><?= e($name) ?>の商品</h1>
<p><?= e($name) ?>の商品を<?= e(number_format($total)) ?>件掲載しています。</p>
<form class="toys-toolbar" method="get" action="<?= e(public_url('maker.php')) ?>">
  <input type="hidden" name="id" value="<?= $id ?>">
  <label>並び順<select name="sort"><option value="new"<?= $sort === 'new' ? ' selected' : '' ?>>新着順</option><option value="popular"<?= $sort === 'popular' ? ' selected' : '' ?>>人気順</option></select></label>
  <button type="submit">並び替える</button>
</form>
<?php if ($items !== []): ?><div class="toys-grid"><?php foreach ($items as $item): toys_render_product_card($item); endforeach; ?></div><?php else: ?><div class="toys-empty">このメーカーの商品はまだありません。</div><?php endif; ?>
<?php $pages=max(1,(int)ceil($total/$perPage)); if($pages>1): ?><nav class="toys-pagination" aria-label="ページ送り"><?php for($p=max(1,$page-2);$p<=min($pages,$page+2);$p++): $href=public_url('maker.php?'.http_build_query(['id'=>$id,'sort'=>$sort,'page'=>$p])); ?><?= $p===$page?'<span class="is-current">'.$p.'</span>':'<a href="'.e($href).'">'.$p.'</a>' ?><?php endfor; ?></nav><?php endif; ?>
<section class="toys-section"><h2><?= e($name) ?>の人気商品</h2><p>このページでは閲覧数をもとに人気順へ切り替えて比較できます。</p></section>
<?php require __DIR__ . '/partials/footer.php'; ?>

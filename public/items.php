<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../lib/repository.php';
require_once __DIR__ . '/partials/toys_product_ui.php';

$title = '商品一覧';
$pageDescription = 'FANZAの大人のおもちゃをカテゴリ・メーカー・価格・人気順から探せる商品一覧です。';
$page = max(1, (int)get('page', 1));
$perPage = max(12, min(60, (int)(app_config()['pagination']['per_page'] ?? 32)));
$offset = ($page - 1) * $perPage;
$q = trim((string)get('q', ''));
$genreId = max(0, (int)get('genre_id', 0));
$makerId = max(0, (int)get('maker_id', 0));
$sort = trim((string)get('sort', 'new'));

$sortMap = [
    'new' => 'items.release_date DESC, items.id DESC',
    'popular' => 'items.view_count DESC, items.id DESC',
    'price_low' => 'CAST(REPLACE(REPLACE(items.price_min_text, ",", ""), "円", "") AS UNSIGNED) ASC, items.id DESC',
    'price_high' => 'CAST(REPLACE(REPLACE(items.price_min_text, ",", ""), "円", "") AS UNSIGNED) DESC, items.id DESC',
    'review' => 'items.review_average DESC, items.review_count DESC, items.id DESC',
];
$orderBy = $sortMap[$sort] ?? $sortMap['new'];

$where = [items_product_source_where('items')];
$params = [];
if ($q !== '') {
    $where[] = '(items.title LIKE :q OR items.category_name LIKE :q OR EXISTS (SELECT 1 FROM item_genres ig WHERE ig.item_id = items.id AND ig.genre_name LIKE :q) OR EXISTS (SELECT 1 FROM item_makers im WHERE im.item_id = items.id AND im.maker_name LIKE :q))';
    $params[':q'] = '%' . $q . '%';
}
if ($genreId > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM item_genres ig INNER JOIN genres g ON g.dmm_id = ig.dmm_id WHERE ig.item_id = items.id AND g.id = :genre_id)';
    $params[':genre_id'] = $genreId;
}
if ($makerId > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM item_makers im INNER JOIN makers m ON m.dmm_id = im.dmm_id WHERE im.item_id = items.id AND m.id = :maker_id)';
    $params[':maker_id'] = $makerId;
}
$whereSql = ' WHERE ' . implode(' AND ', array_filter($where));

$total = 0;
$items = [];
try {
    $countStmt = db()->prepare('SELECT COUNT(*) FROM items' . $whereSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = db()->prepare('SELECT items.* FROM items' . $whereSql . ' ORDER BY ' . $orderBy . ' LIMIT :limit OFFSET :offset');
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('Toys item list failed: ' . $e->getMessage());
}

$genres = [];
$makers = [];
try {
    $genres = db()->query('SELECT g.id,g.name FROM genres g WHERE EXISTS (SELECT 1 FROM item_genres ig INNER JOIN items i ON i.id=ig.item_id WHERE ig.dmm_id=g.dmm_id AND ' . items_product_source_where('i') . ') ORDER BY g.name ASC LIMIT 500')->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable) {
    $genres = [];
}
try {
    $makers = db()->query('SELECT m.id,m.name FROM makers m WHERE EXISTS (SELECT 1 FROM item_makers im INNER JOIN items i ON i.id=im.item_id WHERE im.dmm_id=m.dmm_id AND ' . items_product_source_where('i') . ') ORDER BY m.name ASC LIMIT 500')->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable) {
    $makers = [];
}

$canonicalUrl = public_url('items.php');
require __DIR__ . '/partials/header.php';
toys_render_styles();
?>
<?php if (function_exists('pcf_render_hero')) { pcf_render_hero('商品一覧', 'カテゴリ・メーカー・価格から大人のおもちゃを探せます。'); } else { ?><h1>商品一覧</h1><?php } ?>

<form class="toys-toolbar" method="get" action="<?= e(public_url('items.php')) ?>">
  <label>商品検索
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="商品名・カテゴリ・メーカー">
  </label>
  <label>カテゴリ
    <select name="genre_id">
      <option value="0">すべて</option>
      <?php foreach ($genres as $genre): ?>
        <option value="<?= (int)$genre['id'] ?>"<?= $genreId === (int)$genre['id'] ? ' selected' : '' ?>><?= e((string)$genre['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>メーカー
    <select name="maker_id">
      <option value="0">すべて</option>
      <?php foreach ($makers as $maker): ?>
        <option value="<?= (int)$maker['id'] ?>"<?= $makerId === (int)$maker['id'] ? ' selected' : '' ?>><?= e((string)$maker['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>並び順
    <select name="sort">
      <option value="new"<?= $sort === 'new' ? ' selected' : '' ?>>新着順</option>
      <option value="popular"<?= $sort === 'popular' ? ' selected' : '' ?>>人気順</option>
      <option value="review"<?= $sort === 'review' ? ' selected' : '' ?>>評価順</option>
      <option value="price_low"<?= $sort === 'price_low' ? ' selected' : '' ?>>価格が安い順</option>
      <option value="price_high"<?= $sort === 'price_high' ? ' selected' : '' ?>>価格が高い順</option>
    </select>
  </label>
  <button type="submit">絞り込む</button>
</form>

<p><?= e(number_format($total)) ?>件の商品</p>
<?php if ($items !== []): ?>
  <div class="toys-grid">
    <?php foreach ($items as $item): toys_render_product_card($item); endforeach; ?>
  </div>
<?php else: ?>
  <div class="toys-empty">条件に一致する商品がありません。</div>
<?php endif; ?>

<?php $pages = max(1, (int)ceil($total / $perPage)); ?>
<?php if ($pages > 1): ?>
<nav class="toys-pagination" aria-label="ページ送り">
  <?php
  $baseParams = ['q' => $q, 'genre_id' => $genreId, 'maker_id' => $makerId, 'sort' => $sort];
  $start = max(1, $page - 2);
  $end = min($pages, $page + 2);
  if ($page > 1) {
      echo '<a href="' . e(public_url('items.php') . '?' . http_build_query(array_merge($baseParams, ['page' => $page - 1]))) . '">前へ</a>';
  }
  for ($p = $start; $p <= $end; $p++) {
      $href = public_url('items.php') . '?' . http_build_query(array_merge($baseParams, ['page' => $p]));
      echo $p === $page ? '<span class="is-current">' . $p . '</span>' : '<a href="' . e($href) . '">' . $p . '</a>';
  }
  if ($page < $pages) {
      echo '<a href="' . e(public_url('items.php') . '?' . http_build_query(array_merge($baseParams, ['page' => $page + 1]))) . '">次へ</a>';
  }
  ?>
</nav>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

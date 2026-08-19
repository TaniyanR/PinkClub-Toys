<?php
declare(strict_types=1);

require_once __DIR__ . '/public_ui.php';

function toys_price_text(array $item): string
{
    foreach (['price_min_text', 'list_price_text'] as $key) {
        $value = trim((string)($item[$key] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }
    return '';
}

function toys_affiliate_url(array $item): string
{
    $url = trim((string)($item['affiliate_url'] ?? ''));
    if ($url === '') {
        $url = trim((string)($item['url'] ?? ''));
    }
    return $url;
}

function toys_relation_names(int $itemId, string $type): array
{
    $map = [
        'genres' => ['table' => 'item_genres', 'name' => 'genre_name', 'master' => 'genres'],
        'makers' => ['table' => 'item_makers', 'name' => 'maker_name', 'master' => 'makers'],
    ];
    if (!isset($map[$type]) || $itemId < 1) {
        return [];
    }

    $cfg = $map[$type];
    try {
        $sql = 'SELECT DISTINCT r.' . $cfg['name'] . ' AS name, m.id AS id '
             . 'FROM ' . $cfg['table'] . ' r '
             . 'LEFT JOIN ' . $cfg['master'] . ' m ON m.dmm_id = r.dmm_id '
             . 'WHERE r.item_id = :item_id AND TRIM(COALESCE(r.' . $cfg['name'] . ', "")) <> "" '
             . 'ORDER BY r.' . $cfg['name'] . ' ASC';
        $stmt = db()->prepare($sql);
        $stmt->execute([':item_id' => $itemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable) {
        return [];
    }
}

function toys_render_styles(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    ?>
<style>
.toys-toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin:0 0 18px;padding:14px;border:1px solid #e5e7eb;border-radius:10px;background:#fff}.toys-toolbar label{display:flex;flex-direction:column;gap:5px;font-weight:700;font-size:13px}.toys-toolbar input,.toys-toolbar select{min-height:40px;padding:8px 10px;border:1px solid #cfd4dc;border-radius:7px;background:#fff}.toys-toolbar button,.toys-button{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:9px 16px;border:0;border-radius:7px;background:#e60033;color:#fff!important;font-weight:800;text-decoration:none!important;cursor:pointer}.toys-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}.toys-card{display:flex;flex-direction:column;overflow:hidden;border:1px solid #e2e5ea;border-radius:10px;background:#fff}.toys-card__image{display:block;aspect-ratio:1/1;background:#f7f7f8}.toys-card__image img{width:100%;height:100%;object-fit:contain;display:block}.toys-card__body{display:flex;flex:1;flex-direction:column;gap:8px;padding:12px}.toys-card__title{font-weight:800;line-height:1.45;color:#111;text-decoration:none}.toys-card__meta{font-size:12px;color:#666}.toys-card__price{font-size:18px;font-weight:900;color:#d9002b}.toys-card__list-price{font-size:12px;color:#777;text-decoration:line-through}.toys-card__rating{font-size:13px;color:#8a5a00}.toys-card__actions{margin-top:auto;padding-top:4px}.toys-detail{display:grid;grid-template-columns:minmax(260px,42%) 1fr;gap:28px;align-items:start}.toys-detail__image{border:1px solid #e2e5ea;border-radius:10px;background:#fff;padding:12px}.toys-detail__image img{width:100%;max-height:620px;object-fit:contain;display:block}.toys-detail__info{display:flex;flex-direction:column;gap:12px}.toys-detail__price{font-size:28px;font-weight:900;color:#d9002b}.toys-detail__table{width:100%;border-collapse:collapse}.toys-detail__table th,.toys-detail__table td{padding:10px;border-bottom:1px solid #eceff3;text-align:left;vertical-align:top}.toys-detail__table th{width:110px;color:#555}.toys-chips{display:flex;flex-wrap:wrap;gap:7px}.toys-chip{display:inline-flex;padding:5px 9px;border-radius:999px;background:#f1f3f5;color:#222;text-decoration:none;font-size:13px}.toys-section{margin-top:28px}.toys-section h2{margin-bottom:12px}.toys-pagination{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin:24px 0}.toys-pagination a,.toys-pagination span{padding:8px 11px;border:1px solid #ddd;border-radius:6px;text-decoration:none}.toys-pagination .is-current{background:#111;color:#fff;border-color:#111}.toys-empty{padding:28px;text-align:center;border:1px solid #e5e7eb;border-radius:10px;background:#fff}@media(max-width:900px){.toys-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:640px){.toys-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.toys-detail{grid-template-columns:1fr}.toys-toolbar{align-items:stretch}.toys-toolbar label{width:100%}.toys-detail__price{font-size:24px}}
</style>
<?php
}

function toys_render_product_card(array $item): void
{
    toys_render_styles();
    $id = (int)($item['id'] ?? 0);
    $title = trim((string)($item['title'] ?? '商品'));
    $image = function_exists('pcf_item_image') ? pcf_item_image($item) : trim((string)($item['image_large'] ?? $item['image_small'] ?? ''));
    $price = trim((string)($item['price_min_text'] ?? ''));
    $listPrice = trim((string)($item['list_price_text'] ?? ''));
    $affiliate = toys_affiliate_url($item);
    $reviewAverage = (float)($item['review_average'] ?? 0);
    $reviewCount = (int)($item['review_count'] ?? 0);
    $genres = toys_relation_names($id, 'genres');
    $makers = toys_relation_names($id, 'makers');
    ?>
<article class="toys-card">
  <a class="toys-card__image" href="<?= e(public_url('item.php?id=' . $id)) ?>">
    <?php if ($image !== ''): ?><img src="<?= e($image) ?>" alt="<?= e($title) ?>" loading="lazy" decoding="async"><?php else: ?><span class="toys-empty">画像なし</span><?php endif; ?>
  </a>
  <div class="toys-card__body">
    <a class="toys-card__title" href="<?= e(public_url('item.php?id=' . $id)) ?>"><?= e($title) ?></a>
    <?php if ($makers !== []): ?><div class="toys-card__meta">メーカー：<?= e((string)$makers[0]['name']) ?></div><?php endif; ?>
    <?php if ($genres !== []): ?><div class="toys-card__meta">カテゴリ：<?= e((string)$genres[0]['name']) ?></div><?php endif; ?>
    <?php if ($price !== ''): ?><div class="toys-card__price"><?= e($price) ?></div><?php endif; ?>
    <?php if ($listPrice !== '' && $listPrice !== $price): ?><div class="toys-card__list-price"><?= e($listPrice) ?></div><?php endif; ?>
    <?php if ($reviewCount > 0): ?><div class="toys-card__rating">★ <?= e(number_format($reviewAverage, 1)) ?>（<?= e(number_format($reviewCount)) ?>件）</div><?php endif; ?>
    <div class="toys-card__actions">
      <a class="toys-button" href="<?= e(public_url('item.php?id=' . $id)) ?>">商品詳細</a>
      <?php if ($affiliate !== ''): ?> <a class="toys-button" href="<?= e($affiliate) ?>" target="_blank" rel="sponsored nofollow noopener">FANZAで見る</a><?php endif; ?>
    </div>
  </div>
</article>
<?php
}

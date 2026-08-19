<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../lib/repository.php';
require_once __DIR__ . '/partials/toys_product_ui.php';

function toys_raw_text(mixed $value): string
{
    if (is_string($value) || is_numeric($value)) {
        return trim(strip_tags((string)$value));
    }
    if (!is_array($value)) {
        return '';
    }
    foreach (['description', 'comment', 'text', 'value', 'body', 'caption'] as $key) {
        if (array_key_exists($key, $value)) {
            $text = toys_raw_text($value[$key]);
            if ($text !== '') {
                return $text;
            }
        }
    }
    foreach ($value as $child) {
        $text = toys_raw_text($child);
        if (mb_strlen($text, 'UTF-8') >= 20) {
            return $text;
        }
    }
    return '';
}

$id = max(0, (int)get('id', 0));
$contentId = trim((string)get('content_id', get('cid', '')));
$item = null;
try {
    if ($id > 0) {
        $stmt = db()->prepare('SELECT * FROM items WHERE id = :id AND ' . items_product_source_where('items') . ' LIMIT 1');
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } elseif ($contentId !== '') {
        $item = fetch_item_by_content_id($contentId);
    }
} catch (Throwable) {
    $item = null;
}

if (!$item) {
    require __DIR__ . '/404.php';
    exit;
}

$itemId = (int)$item['id'];
if ($id !== $itemId || $contentId !== '') {
    header('Location: ' . public_url('item.php?id=' . $itemId), true, 301);
    exit;
}

try {
    $stmt = db()->prepare('UPDATE items SET view_count = view_count + 1 WHERE id = :id');
    $stmt->execute([':id' => $itemId]);
} catch (Throwable) {
}

$title = trim((string)($item['title'] ?? '商品詳細'));
$image = function_exists('pcf_item_image') ? pcf_item_image($item) : trim((string)($item['image_large'] ?? $item['image_small'] ?? ''));
$affiliate = toys_affiliate_url($item);
$price = trim((string)($item['price_min_text'] ?? ''));
$listPrice = trim((string)($item['list_price_text'] ?? ''));
$reviewAverage = (float)($item['review_average'] ?? 0);
$reviewCount = (int)($item['review_count'] ?? 0);
$genres = toys_relation_names($itemId, 'genres');
$makers = toys_relation_names($itemId, 'makers');

$raw = [];
if (is_string($item['raw_json'] ?? null) && trim((string)$item['raw_json']) !== '') {
    $decoded = json_decode((string)$item['raw_json'], true);
    if (is_array($decoded)) {
        $raw = $decoded;
    }
}
$description = '';
foreach (['description', 'comment', 'productDescription', 'summary'] as $key) {
    if (isset($raw[$key])) {
        $description = toys_raw_text($raw[$key]);
        if ($description !== '') {
            break;
        }
    }
}
if ($description === '' && isset($raw['iteminfo'])) {
    $description = toys_raw_text($raw['iteminfo']);
}
if ($description !== '' && (str_contains($description, '18歳未満') || str_contains($description, '年齢認証'))) {
    $description = '';
}

$pageDescription = $description !== '' ? mb_substr($description, 0, 150, 'UTF-8') : $title . 'の商品情報、価格、カテゴリ、メーカー、レビューを掲載しています。';
$canonicalUrl = public_url('item.php?id=' . $itemId);
$ogType = 'product';
$ogImage = $image;

$productJson = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $title,
    'url' => $canonicalUrl,
];
if ($image !== '') {
    $productJson['image'] = [$image];
}
if ($description !== '') {
    $productJson['description'] = $pageDescription;
}
if ($reviewCount > 0 && $reviewAverage > 0) {
    $productJson['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => $reviewAverage,
        'reviewCount' => $reviewCount,
    ];
}
$priceDigits = preg_replace('/[^0-9]/', '', $price);
if ($affiliate !== '' && is_string($priceDigits) && $priceDigits !== '') {
    $productJson['offers'] = [
        '@type' => 'Offer',
        'url' => $affiliate,
        'priceCurrency' => 'JPY',
        'price' => (int)$priceDigits,
        'availability' => 'https://schema.org/InStock',
    ];
}
$jsonLd = json_encode($productJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';

$related = [];
try {
    if ($genres !== []) {
        $genreName = (string)$genres[0]['name'];
        $stmt = db()->prepare('SELECT DISTINCT i.* FROM items i INNER JOIN item_genres ig ON ig.item_id=i.id WHERE ig.genre_name=:name AND i.id<>:id AND ' . items_product_source_where('i') . ' ORDER BY i.view_count DESC,i.id DESC LIMIT 8');
        $stmt->execute([':name' => $genreName, ':id' => $itemId]);
        $related = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } elseif ($makers !== []) {
        $makerName = (string)$makers[0]['name'];
        $stmt = db()->prepare('SELECT DISTINCT i.* FROM items i INNER JOIN item_makers im ON im.item_id=i.id WHERE im.maker_name=:name AND i.id<>:id AND ' . items_product_source_where('i') . ' ORDER BY i.view_count DESC,i.id DESC LIMIT 8');
        $stmt->execute([':name' => $makerName, ':id' => $itemId]);
        $related = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Throwable) {
    $related = [];
}

require __DIR__ . '/partials/header.php';
toys_render_styles();
?>
<nav class="pcf-breadcrumb" aria-label="パンくず">
  <span class="pcf-breadcrumb__item"><a href="<?= e(public_url('')) ?>">ホーム</a></span>
  <span class="pcf-breadcrumb__item"><a href="<?= e(public_url('items.php')) ?>">商品一覧</a></span>
  <span class="pcf-breadcrumb__item"><?= e($title) ?></span>
</nav>

<h1><?= e($title) ?></h1>
<section class="toys-detail">
  <div class="toys-detail__image">
    <?php if ($image !== ''): ?><img src="<?= e($image) ?>" alt="<?= e($title) ?>" data-package-image="1"><?php else: ?><div class="toys-empty">画像なし</div><?php endif; ?>
  </div>
  <div class="toys-detail__info">
    <?php if ($price !== ''): ?><div class="toys-detail__price"><?= e($price) ?></div><?php endif; ?>
    <?php if ($listPrice !== '' && $listPrice !== $price): ?><div>参考価格：<s><?= e($listPrice) ?></s></div><?php endif; ?>
    <?php if ($reviewCount > 0): ?><div class="toys-card__rating">★ <?= e(number_format($reviewAverage, 1)) ?>（<?= e(number_format($reviewCount)) ?>件）</div><?php endif; ?>

    <?php if ($affiliate !== ''): ?>
      <a class="toys-button" href="<?= e($affiliate) ?>" target="_blank" rel="sponsored nofollow noopener">FANZAで商品を見る</a>
    <?php endif; ?>

    <table class="toys-detail__table">
      <?php if ($makers !== []): ?><tr><th>メーカー</th><td><div class="toys-chips"><?php foreach ($makers as $maker): ?><?php $mid=(int)($maker['id']??0); ?><a class="toys-chip" href="<?= e($mid>0 ? public_url('maker.php?id='.$mid) : public_url('search.php?q='.rawurlencode((string)$maker['name']))) ?>"><?= e((string)$maker['name']) ?></a><?php endforeach; ?></div></td></tr><?php endif; ?>
      <?php if ($genres !== []): ?><tr><th>カテゴリ</th><td><div class="toys-chips"><?php foreach ($genres as $genre): ?><?php $gid=(int)($genre['id']??0); ?><a class="toys-chip" href="<?= e($gid>0 ? public_url('genre.php?id='.$gid) : public_url('search.php?q='.rawurlencode((string)$genre['name']))) ?>"><?= e((string)$genre['name']) ?></a><?php endforeach; ?></div></td></tr><?php endif; ?>
      <?php if (!empty($item['release_date'])): ?><tr><th>発売日</th><td><?= e(format_date((string)$item['release_date'])) ?></td></tr><?php endif; ?>
      <?php if (!empty($item['product_id'])): ?><tr><th>商品番号</th><td><?= e((string)$item['product_id']) ?></td></tr><?php endif; ?>
    </table>
  </div>
</section>

<?php if ($description !== ''): ?>
<section class="toys-section">
  <h2>商品説明</h2>
  <div class="pcf-list-card"><?= nl2br(e($description)) ?></div>
</section>
<?php endif; ?>

<?php if ($related !== []): ?>
<section class="toys-section">
  <h2>関連商品</h2>
  <div class="toys-grid">
    <?php foreach ($related as $relatedItem): toys_render_product_card($relatedItem); endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="toys-section">
  <h2>商品について</h2>
  <p>価格・在庫・商品仕様は変更される場合があります。購入前にFANZAの商品ページで最新情報をご確認ください。</p>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>

<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/repository.php';

header('Content-Type: application/xml; charset=UTF-8');

function sitemap_e(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function sitemap_url(string $loc, string $changefreq, string $priority): void
{
    echo "  <url>\n";
    echo '    <loc>' . sitemap_e($loc) . "</loc>\n";
    echo '    <changefreq>' . sitemap_e($changefreq) . "</changefreq>\n";
    echo '    <priority>' . sitemap_e($priority) . "</priority>\n";
    echo "  </url>\n";
}

$sources = [
    [
        'from' => 'items entity',
        'path' => 'item.php',
        'where' => items_product_source_where('entity'),
        'priority' => '0.8',
    ],
    [
        'from' => 'genres entity',
        'path' => 'genre.php',
        'where' => "TRIM(COALESCE(entity.name,''))<>'' AND EXISTS (SELECT 1 FROM item_genres r INNER JOIN items i ON i.id=r.item_id WHERE r.dmm_id=entity.dmm_id AND " . items_product_source_where('i') . ')',
        'priority' => '0.7',
    ],
    [
        'from' => 'makers entity',
        'path' => 'maker.php',
        'where' => "TRIM(COALESCE(entity.name,''))<>'' AND EXISTS (SELECT 1 FROM item_makers r INNER JOIN items i ON i.id=r.item_id WHERE r.dmm_id=entity.dmm_id AND " . items_product_source_where('i') . ')',
        'priority' => '0.7',
    ],
];

$staticUrls = [
    [public_url('index.php'), 'daily', '1.0'],
    [public_url('items.php'), 'daily', '0.9'],
    [public_url('rankings.php'), 'daily', '0.8'],
    [public_url('genres.php'), 'weekly', '0.8'],
    [public_url('makers.php'), 'weekly', '0.8'],
];

$perSitemap = 10000;
$counts = [];
$total = count($staticUrls);
foreach ($sources as $idx => $source) {
    try {
        $counts[$idx] = (int)db()->query('SELECT COUNT(*) FROM ' . $source['from'] . ' WHERE ' . $source['where'])->fetchColumn();
    } catch (Throwable) {
        $counts[$idx] = 0;
    }
    $total += $counts[$idx];
}

if ((isset($_GET['index']) && (string)$_GET['index'] === '1') || ($total > $perSitemap && !isset($_GET['part']))) {
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    $pages = max(1, (int)ceil($total / $perSitemap));
    for ($i = 1; $i <= $pages; $i++) {
        echo '  <sitemap><loc>' . sitemap_e(public_url('sitemap.php') . '?part=' . $i) . "</loc></sitemap>\n";
    }
    echo "</sitemapindex>\n";
    exit;
}

$part = max(1, (int)($_GET['part'] ?? 1));
$skip = ($part - 1) * $perSitemap;
$remaining = $perSitemap;

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($staticUrls as $url) {
    if ($skip > 0) { $skip--; continue; }
    if ($remaining <= 0) { break; }
    sitemap_url((string)$url[0], (string)$url[1], (string)$url[2]);
    $remaining--;
}

foreach ($sources as $idx => $source) {
    $sourceCount = $counts[$idx] ?? 0;
    if ($skip >= $sourceCount) { $skip -= $sourceCount; continue; }
    if ($remaining <= 0) { break; }
    $limit = min($remaining, max(0, $sourceCount - $skip));
    if ($limit <= 0) { $skip = 0; continue; }
    try {
        $stmt = db()->prepare('SELECT entity.id FROM ' . $source['from'] . ' WHERE ' . $source['where'] . ' ORDER BY entity.id ASC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $skip, PDO::PARAM_INT);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (int)($row['id'] ?? 0);
            if ($id < 1) { continue; }
            sitemap_url(public_url((string)$source['path']) . '?id=' . $id, 'weekly', (string)$source['priority']);
            $remaining--;
        }
    } catch (Throwable $e) {
        error_log('Toys sitemap failed: ' . $e->getMessage());
    }
    $skip = 0;
}
echo "</urlset>\n";

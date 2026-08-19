<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$params = [];
$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') {
    $params['q'] = $q;
}
$target = public_url('items.php');
if ($params !== []) {
    $target .= '?' . http_build_query($params);
}
header('Location: ' . $target, true, 302);
exit;

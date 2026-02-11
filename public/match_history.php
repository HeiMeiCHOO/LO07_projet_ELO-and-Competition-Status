<?php

// 历史入口：比赛记录查询与筛选。
require __DIR__ . '/../app/bootstrap.php';

// 从 URL 读取 club_id 与筛选条件。
$clubId = (int) ($_GET['club_id'] ?? 0);
if ($clubId === 0) {
    header('Location: index.php');
    exit;
}

$filter = trim($_GET['player'] ?? '');
$filterType = trim($_GET['type'] ?? '');

// 按条件读取历史数据。
$controller = new HistoryController($repo);
$data = $controller->getHistoryData($clubId, $filter, $filterType);
$club = $data['club'];
$matches = $data['matches'];
$filterType = $data['filterType'];

if (! $club) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = 'info';
// 设置页面信息。
$pageTitle = 'History';
$navClubId = $clubId;

// 渲染模板并套用布局。
ob_start();
require __DIR__ . '/../app/views/history.php';
$content = ob_get_clean();
require __DIR__ . '/../app/views/layout.php';

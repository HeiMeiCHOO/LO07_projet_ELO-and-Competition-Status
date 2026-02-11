<?php

// 首页入口：创建俱乐部与列表展示。
require __DIR__ . '/../app/bootstrap.php';

// 构建控制器并准备提示信息。
$controller = new DashboardController($repo);
$message = '';
$messageType = 'info';

// 处理创建俱乐部表单提交。
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 执行创建逻辑。
    $result = $controller->createClub($_POST);
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';

    // 创建成功则跳转到俱乐部页面。
    if (! empty($result['club_id'])) {
        header('Location: club.php?club_id=' . (int) $result['club_id']);
        exit;
    }
}

// 获取渲染数据。
$data = $controller->getDashboardData();
$clubs = $data['clubs'];

$pageTitle = 'Dashboard';
$navClubId = null;

// 渲染模板并套用布局。
ob_start();
require __DIR__ . '/../app/views/dashboard.php';
$content = ob_get_clean();
require __DIR__ . '/../app/views/layout.php';

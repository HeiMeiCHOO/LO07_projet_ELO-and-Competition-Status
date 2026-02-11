<?php

// 成员入口：个人 Elo 详情与曲线。
require __DIR__ . '/../app/bootstrap.php';

// 从 URL 读取 club_id 与 user_id。
$clubId = (int) ($_GET['club_id'] ?? 0);
$userId = (int) ($_GET['user_id'] ?? 0);

if ($clubId === 0 || $userId === 0) {
    header('Location: index.php');
    exit;
}

// 读取成员数据。
$controller = new MemberController($repo);
$data = $controller->getMemberData($clubId, $userId);
$club = $data['club'];
$user = $data['user'];
$membership = $data['membership'];
$history = $data['history'];

if (! $club || ! $user || ! $membership) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = 'info';
// 设置页面信息。
$pageTitle = 'Member';
$navClubId = $clubId;

// 渲染模板并套用布局。
ob_start();
require __DIR__ . '/../app/views/member.php';
$content = ob_get_clean();
require __DIR__ . '/../app/views/layout.php';

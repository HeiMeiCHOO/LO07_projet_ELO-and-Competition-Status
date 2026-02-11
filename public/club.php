<?php

// 俱乐部入口：成员管理与比赛记录。
require __DIR__ . '/../app/bootstrap.php';

// 从 URL 读取 club_id。
$clubId = (int) ($_GET['club_id'] ?? 0);
if ($clubId === 0) {
    header('Location: index.php');
    exit;
}

// 构建控制器并准备提示信息。
$controller = new ClubController($repo, $eloService, $defaultElo);
$message = '';
$messageType = 'info';

// 处理成员添加或比赛记录。
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 根据 action 分发。
    $action = $_POST['action'] ?? '';
    if ($action === 'add_member') {
        $result = $controller->addMember($clubId, $_POST);
    } elseif ($action === 'record_match') {
        $result = $controller->recordMatch($clubId, $_POST);
    } else {
        $result = [
            'success' => false,
            'message' => 'Unknown action.',
        ];
    }

    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
}

// 获取渲染数据。
// 获取渲染数据。
$data = $controller->getClubData($clubId);
$club = $data['club'];
$members = $data['members'];
$matches = $data['matches'];

if (! $club) {
    header('Location: index.php');
    exit;
}

// 设置页面信息。
$pageTitle = 'Club';
$navClubId = $clubId;

// 渲染模板并套用布局。
ob_start();
require __DIR__ . '/../app/views/club.php';
$content = ob_get_clean();
require __DIR__ . '/../app/views/layout.php';

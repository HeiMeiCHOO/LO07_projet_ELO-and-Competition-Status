<?php
/**
 * 锦标赛列表和创建入口。
 * 
 * 处理：
 * GET - 显示锦标赛列表和创建表单
 * POST action=create - 创建新锦标赛
 * POST action=start - 开始锦标赛
 */

require __DIR__ . '/../app/bootstrap.php';

$clubId = (int) ($_GET['club_id'] ?? 0);
if ($clubId <= 0) {
    http_response_code(400);
    exit('Missing club_id');
}

$controller = new TournamentController($repo, new TournamentService($repo, $eloService));
$action = trim($_GET['action'] ?? '');

// POST 请求处理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($action === 'create') {
            // 创建锦标赛
            $tournamentId = $controller->createTournament($clubId, $_POST);
            header('Location: tournament_detail.php?id=' . $tournamentId);
            exit;
        } elseif ($action === 'start') {
            // 开始锦标赛
            $tournamentId = (int) ($_GET['id'] ?? 0);
            $controller->startTournament($tournamentId);
            header('Location: tournament_detail.php?id=' . $tournamentId);
            exit;
        }
    } catch (Throwable $e) {
        http_response_code(400);
        exit('Error: ' . htmlspecialchars($e->getMessage()));
    }
}

// GET 请求 - 显示列表
$data = $controller->getTournamentListData($clubId);

extract($data);
require __DIR__ . '/../app/views/tournament_list.php';

<?php
/**
 * 锦标赛详情入口。
 * 
 * 处理：
 * GET - 显示锦标赛详情、参与者、比赛和排名
 * POST action=record - 记录比赛结果
 */

require __DIR__ . '/../app/bootstrap.php';

$tournamentId = (int) ($_GET['id'] ?? 0);
if ($tournamentId <= 0) {
    http_response_code(400);
    exit('Missing tournament id');
}

$controller = new TournamentController($repo, new TournamentService($repo, $eloService));
$action = trim($_GET['action'] ?? '');

// POST 请求处理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($action === 'record') {
            // 记录比赛结果
            $matchId = (int) ($_POST['match_id'] ?? 0);
            $controller->recordMatchResult($matchId, $_POST);
            header('Location: tournament_detail.php?id=' . $tournamentId);
            exit;
        }
    } catch (Throwable $e) {
        http_response_code(400);
        exit('Error: ' . htmlspecialchars($e->getMessage()));
    }
}

// GET 请求 - 显示详情
$data = $controller->getTournamentData($tournamentId);

extract($data);
require __DIR__ . '/../app/views/tournament_detail.php';

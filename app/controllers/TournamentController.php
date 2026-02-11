<?php
/**
 * TournamentController - 锦标赛控制器。
 * 
 * 处理锦标赛相关的请求：
 * - 列表显示
 * - 创建锦标赛
 * - 开始锦标赛
 * - 查看锦标赛详情
 * - 记录比赛结果
 */

class TournamentController
{
    /**
     * 初始化
     */
    public function __construct(
        private Repository $repo,
        private TournamentService $tournamentService
    ) {
    }

    /**
     * 获取俱乐部锦标赛列表及创建表单数据。
     * 
     * @param int $clubId 俱乐部ID
     * @return array
     */
    public function getTournamentListData(int $clubId): array
    {
        $club = $this->repo->getClub($clubId);
        $tournaments = $this->tournamentService->listTournaments($clubId);
        $members = $this->repo->listClubMembers($clubId);

        return [
            'club' => $club,
            'tournaments' => $tournaments,
            'members' => $members,
        ];
    }

    /**
     * 创建新锦标赛。
     * 
     * @param int $clubId 俱乐部ID
     * @param array $input 表单输入
     * @return int 锦标赛ID
     */
    public function createTournament(int $clubId, array $input): int
    {
        $name = trim($input['name'] ?? '');
        $format = trim($input['format'] ?? 'round-robin');
        $participantIds = $input['participants'] ?? [];

        // 验证
        if (empty($name)) {
            throw new RuntimeException('锦标赛名称不能为空');
        }

        if (empty($participantIds) || !is_array($participantIds)) {
            throw new RuntimeException('请至少选择2名参与者');
        }

        // 确保参与者ID为整数
        $participantIds = array_map('intval', $participantIds);

        return $this->tournamentService->createTournament(
            $clubId,
            $name,
            $format,
            $participantIds
        );
    }

    /**
     * 开始锦标赛。
     * 
     * @param int $tournamentId 锦标赛ID
     */
    public function startTournament(int $tournamentId): void
    {
        $this->tournamentService->startTournament($tournamentId);
    }

    /**
     * 获取锦标赛详情数据。
     * 
     * @param int $tournamentId 锦标赛ID
     * @return array
     */
    public function getTournamentData(int $tournamentId): array
    {
        $tournament = $this->tournamentService->getTournament($tournamentId);
        $club = $this->repo->getClub($tournament['club_id']);
        $participants = $this->tournamentService->getTournamentParticipants($tournamentId);
        $matches = $this->tournamentService->getTournamentMatches($tournamentId);
        $standings = $this->tournamentService->getTournamentStandings($tournamentId);

        return [
            'tournament' => $tournament,
            'club' => $club,
            'participants' => $participants,
            'matches' => $matches,
            'standings' => $standings,
        ];
    }

    /**
     * 记录锦标赛比赛结果。
     * 
     * @param int $matchId 比赛ID
     * @param array $input 表单输入
     */
    public function recordMatchResult(int $matchId, array $input): void
    {
        $result = trim($input['result'] ?? '');

        if (!in_array($result, ['A', 'B', 'D'])) {
            throw new RuntimeException('无效的比赛结果');
        }

        // 获取比赛信息
        $db = $this->repo->getConnection();
        $stmt = $db->prepare('SELECT player_a_id, player_b_id FROM matches WHERE id = :id');
        $stmt->execute([':id' => $matchId]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) {
            throw new RuntimeException('比赛不存在');
        }

        // 确定赢家
        $winnerId = null;
        $isDraw = false;

        if ($result === 'A') {
            $winnerId = $match['player_a_id'];
        } elseif ($result === 'B') {
            $winnerId = $match['player_b_id'];
        } else {
            $isDraw = true;
        }

        $this->tournamentService->completeMatch($matchId, $winnerId, $isDraw);
    }
}

<?php
/**
 * TournamentService - 锦标赛业务逻辑。
 * 
 * 功能：
 * - 创建和管理锦标赛
 * - 自动生成循环赛或淘汰制配对
 * - 跟踪锦标赛进度
 */

class TournamentService
{
    /**
     * Elo K 因子（用于比赛计算）。
     */
    private const ELO_K_FACTOR = 32;

    /**
     * 默认初始 Elo。
     */
    private const DEFAULT_ELO = 1200;

    /**
     * 初始化
     */
    public function __construct(
        private Repository $repo,
        private EloService $eloService
    ) {
    }

    /**
     * 创建新锦标赛。
     * 
     * @param int $clubId 俱乐部ID
     * @param string $name 锦标赛名称
     * @param string $format 格式：'round-robin' 或 'elimination'
     * @param array $participantIds 参与者用户ID数组
     * @return int 锦标赛ID
     */
    public function createTournament(
        int $clubId,
        string $name,
        string $format,
        array $participantIds
    ): int {
        // 验证参与者数量（至少2人）
        if (count($participantIds) < 2) {
            throw new RuntimeException('锦标赛至少需要2名参与者');
        }

        // 验证格式
        if (!in_array($format, ['round-robin', 'elimination'])) {
            throw new RuntimeException('无效的锦标赛格式');
        }

        $db = $this->repo->getConnection();
        $tournamentId = null;

        try {
            $db->beginTransaction();

            // 插入锦标赛记录
            $stmt = $db->prepare('
                INSERT INTO tournaments (club_id, name, format, status, created_at)
                VALUES (:club_id, :name, :format, :status, :created_at)
            ');
            $now = gmdate('c');
            $stmt->execute([
                ':club_id' => $clubId,
                ':name' => $name,
                ':format' => $format,
                ':status' => 'draft',
                ':created_at' => $now,
            ]);
            $tournamentId = (int) $db->lastInsertId();

            // 获取参与者信息并按 Elo 排序（种子排名）
            $participants = [];
            foreach ($participantIds as $userId) {
                $member = $this->repo->getMember($clubId, $userId);
                if (!$member) {
                    throw new RuntimeException("用户 ID {$userId} 不在俱乐部中");
                }
                $participants[] = [
                    'user_id' => $userId,
                    'elo' => (int) $member['current_elo'],
                ];
            }

            // 按 Elo 倒序排列（Elo 高的获得较好的种子）
            usort($participants, fn($a, $b) => $b['elo'] <=> $a['elo']);

            // 添加参与者
            foreach ($participants as $seed => $participant) {
                $stmt = $db->prepare('
                    INSERT INTO tournament_participants (tournament_id, user_id, seed, status)
                    VALUES (:tournament_id, :user_id, :seed, :status)
                ');
                $stmt->execute([
                    ':tournament_id' => $tournamentId,
                    ':user_id' => $participant['user_id'],
                    ':seed' => $seed + 1,
                    ':status' => 'active',
                ]);
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        return $tournamentId;
    }

    /**
     * 开始锦标赛并生成配对。
     * 
     * @param int $tournamentId 锦标赛ID
     */
    public function startTournament(int $tournamentId): void
    {
        $tournament = $this->getTournament($tournamentId);

        if ($tournament['status'] !== 'draft') {
            throw new RuntimeException('锦标赛已开始或已完成，无法重新启动');
        }

        $db = $this->repo->getConnection();

        try {
            $db->beginTransaction();

            // 更新锦标赛状态
            $stmt = $db->prepare('UPDATE tournaments SET status = :status WHERE id = :id');
            $stmt->execute([':status' => 'in-progress', ':id' => $tournamentId]);

            // 生成配对
            if ($tournament['format'] === 'round-robin') {
                $this->generateRoundRobinMatches($tournamentId);
            } else {
                $this->generateEliminationMatches($tournamentId);
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * 生成循环赛配对。
     * 
     * @param int $tournamentId 锦标赛ID
     */
    private function generateRoundRobinMatches(int $tournamentId): void
    {
        $tournament = $this->getTournament($tournamentId);
        $clubId = $tournament['club_id'];
        $participants = $this->getTournamentParticipants($tournamentId);

        $db = $this->repo->getConnection();

        // 循环赛：每个参与者都与其他参与者比赛一次
        $round = 1;
        $participantIds = array_column($participants, 'id');
        $count = count($participantIds);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $playerAId = $participantIds[$i];
                $playerBId = $participantIds[$j];

                // 创建实际比赛
                $matchId = $this->repo->insertMatch(
                    $clubId,
                    $playerAId,
                    $playerBId,
                    null,
                    false,
                    gmdate('c'),
                    'official'
                );

                // 关联到锦标赛
                $stmt = $db->prepare('
                    INSERT INTO tournament_matches (tournament_id, match_id, round)
                    VALUES (:tournament_id, :match_id, :round)
                ');
                $stmt->execute([
                    ':tournament_id' => $tournamentId,
                    ':match_id' => $matchId,
                    ':round' => $round,
                ]);
            }
            $round++;
        }
    }

    /**
     * 生成淘汰制配对（单消）。
     * 
     * @param int $tournamentId 锦标赛ID
     */
    private function generateEliminationMatches(int $tournamentId): void
    {
        $tournament = $this->getTournament($tournamentId);
        $clubId = $tournament['club_id'];
        $participants = $this->getTournamentParticipants($tournamentId);

        $db = $this->repo->getConnection();

        // 按种子排名配对
        $participantIds = array_column($participants, 'id');
        $count = count($participantIds);

        // 第一轮配对（1 vs 最后, 2 vs 倒数第二...）
        $round = 1;
        for ($i = 0; $i < $count / 2; $i++) {
            $playerAId = $participantIds[$i];
            $playerBId = $participantIds[$count - 1 - $i];

            // 创建实际比赛
            $matchId = $this->repo->insertMatch(
                $clubId,
                $playerAId,
                $playerBId,
                null,
                false,
                gmdate('c'),
                'official'
            );

            // 关联到锦标赛
            $stmt = $db->prepare('
                INSERT INTO tournament_matches (tournament_id, match_id, round)
                VALUES (:tournament_id, :match_id, :round)
            ');
            $stmt->execute([
                ':tournament_id' => $tournamentId,
                ':match_id' => $matchId,
                ':round' => $round,
            ]);
        }
    }

    /**
     * 完成比赛并更新锦标赛状态。
     * 
     * @param int $matchId 比赛ID
     * @param int|null $winnerId 胜者用户ID（平局为null）
     * @param bool $isDraw 是否平局
     */
    public function completeMatch(int $matchId, ?int $winnerId, bool $isDraw = false): void
    {
        // 获取比赛信息
        $db = $this->repo->getConnection();
        $stmt = $db->prepare('
            SELECT tm.tournament_id, m.club_id, m.player_a_id, m.player_b_id
            FROM tournament_matches tm
            JOIN matches m ON tm.match_id = m.id
            WHERE m.id = :match_id
        ');
        $stmt->execute([':match_id' => $matchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return; // 不是锦标赛比赛
        }

        $tournamentId = $row['tournament_id'];
        $clubId = $row['club_id'];
        $playerAId = $row['player_a_id'];
        $playerBId = $row['player_b_id'];

        try {
            $db->beginTransaction();

            // 更新比赛结果
            $stmt = $db->prepare('
                UPDATE matches
                SET winner_id = :winner_id, is_draw = :is_draw
                WHERE id = :match_id
            ');
            $stmt->execute([
                ':match_id' => $matchId,
                ':winner_id' => $winnerId,
                ':is_draw' => $isDraw ? 1 : 0,
            ]);

            // 计算 Elo 变化
            $memberA = $this->repo->getMember($clubId, $playerAId);
            $memberB = $this->repo->getMember($clubId, $playerBId);

            $eloABefore = (int) $memberA['current_elo'];
            $eloBBefore = (int) $memberB['current_elo'];

            // 确定结果
            if ($isDraw) {
                $result = 'D';
            } elseif ($winnerId === $playerAId) {
                $result = 'A';
            } else {
                $result = 'B';
            }

            $ratings = $this->eloService->calculate($eloABefore, $eloBBefore, $result);

            // 更新 Elo 历史
            $now = gmdate('c');
            $this->repo->insertEloHistory(
                $matchId,
                $clubId,
                $playerAId,
                $eloABefore,
                (int) $ratings['newA'],
                (int) $ratings['deltaA'],
                $now
            );

            $this->repo->insertEloHistory(
                $matchId,
                $clubId,
                $playerBId,
                $eloBBefore,
                (int) $ratings['newB'],
                (int) $ratings['deltaB'],
                $now
            );

            // 更新成员 Elo
            $this->repo->updateMemberElo($clubId, $playerAId, (int) $ratings['newA']);
            $this->repo->updateMemberElo($clubId, $playerBId, (int) $ratings['newB']);
            $this->repo->incrementMatches($clubId, $playerAId);
            $this->repo->incrementMatches($clubId, $playerBId);

            // 检查锦标赛是否完成
            $this->checkTournamentCompletion($tournamentId);

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * 检查锦标赛是否已完成。
     * 
     * @param int $tournamentId 锦标赛ID
     */
    private function checkTournamentCompletion(int $tournamentId): void
    {
        $db = $this->repo->getConnection();

        // 检查是否所有比赛都已完成
        $stmt = $db->prepare('
            SELECT COUNT(*) as incomplete_count
            FROM tournament_matches tm
            JOIN matches m ON tm.match_id = m.id
            WHERE tm.tournament_id = :tournament_id
            AND m.winner_id IS NULL
            AND m.is_draw = 0
        ');
        $stmt->execute([':tournament_id' => $tournamentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['incomplete_count'] == 0) {
            // 所有比赛已完成，标记锦标赛为已完成
            $stmt = $db->prepare('UPDATE tournaments SET status = :status WHERE id = :id');
            $stmt->execute([':status' => 'completed', ':id' => $tournamentId]);
        }
    }

    /**
     * 获取锦标赛详情。
     * 
     * @param int $tournamentId 锦标赛ID
     * @return array
     */
    public function getTournament(int $tournamentId): array
    {
        $db = $this->repo->getConnection();
        $stmt = $db->prepare('SELECT * FROM tournaments WHERE id = :id');
        $stmt->execute([':id' => $tournamentId]);
        $tournament = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tournament) {
            throw new RuntimeException('锦标赛不存在');
        }

        return $tournament;
    }

    /**
     * 获取锦标赛参与者。
     * 
     * @param int $tournamentId 锦标赛ID
     * @return array
     */
    public function getTournamentParticipants(int $tournamentId): array
    {
        $db = $this->repo->getConnection();
        $stmt = $db->prepare('
            SELECT tp.id, tp.user_id, tp.seed, tp.status, u.username
            FROM tournament_participants tp
            JOIN users u ON tp.user_id = u.id
            WHERE tp.tournament_id = :tournament_id
            ORDER BY tp.seed ASC
        ');
        $stmt->execute([':tournament_id' => $tournamentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取锦标赛比赛列表。
     * 
     * @param int $tournamentId 锦标赛ID
     * @return array
     */
    public function getTournamentMatches(int $tournamentId): array
    {
        $db = $this->repo->getConnection();
        $stmt = $db->prepare('
            SELECT 
                tm.id,
                tm.round,
                m.id as match_id,
                m.player_a_id,
                m.player_b_id,
                m.winner_id,
                m.is_draw,
                ua.username as player_a_name,
                ub.username as player_b_name,
                uw.username as winner_name
            FROM tournament_matches tm
            JOIN matches m ON tm.match_id = m.id
            JOIN users ua ON m.player_a_id = ua.id
            JOIN users ub ON m.player_b_id = ub.id
            LEFT JOIN users uw ON m.winner_id = uw.id
            WHERE tm.tournament_id = :tournament_id
            ORDER BY tm.round ASC, tm.id ASC
        ');
        $stmt->execute([':tournament_id' => $tournamentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取锦标赛排名（按 Elo 或胜率）。
     * 
     * @param int $tournamentId 锦标赛ID
     * @return array
     */
    public function getTournamentStandings(int $tournamentId): array
    {
        $db = $this->repo->getConnection();
        $stmt = $db->prepare('
            SELECT 
                tp.seed,
                u.id,
                u.username,
                tp.status,
                (SELECT COUNT(*) FROM matches m 
                 WHERE m.id IN (SELECT match_id FROM tournament_matches WHERE tournament_id = :tournament_id)
                 AND (m.player_a_id = u.id OR m.player_b_id = u.id)) as matches_played,
                (SELECT COUNT(*) FROM matches m 
                 WHERE m.id IN (SELECT match_id FROM tournament_matches WHERE tournament_id = :tournament_id)
                 AND m.winner_id = u.id) as wins,
                (SELECT COUNT(*) FROM matches m 
                 WHERE m.id IN (SELECT match_id FROM tournament_matches WHERE tournament_id = :tournament_id)
                 AND m.is_draw = 1 AND (m.player_a_id = u.id OR m.player_b_id = u.id)) as draws
            FROM tournament_participants tp
            JOIN users u ON tp.user_id = u.id
            WHERE tp.tournament_id = :tournament_id
            ORDER BY tp.seed ASC
        ');
        $stmt->execute([':tournament_id' => $tournamentId]);
        $standings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 计算排名
        usort($standings, function ($a, $b) {
            $aWins = (int) $a['wins'];
            $bWins = (int) $b['wins'];
            if ($aWins !== $bWins) {
                return $bWins <=> $aWins;
            }
            $aDraws = (int) $a['draws'];
            $bDraws = (int) $b['draws'];
            return $bDraws <=> $aDraws;
        });

        foreach ($standings as &$row) {
            $row['matches_played'] = (int) $row['matches_played'];
            $row['wins'] = (int) $row['wins'];
            $row['draws'] = (int) $row['draws'];
            $row['losses'] = $row['matches_played'] - $row['wins'] - $row['draws'];
            $row['points'] = $row['wins'] * 3 + $row['draws'] * 1; // 象棋积分制
        }

        return $standings;
    }

    /**
     * 获取俱乐部的所有锦标赛。
     * 
     * @param int $clubId 俱乐部ID
     * @return array
     */
    public function listTournaments(int $clubId): array
    {
        $db = $this->repo->getConnection();
        $stmt = $db->prepare('
            SELECT * FROM tournaments WHERE club_id = :club_id
            ORDER BY created_at DESC
        ');
        $stmt->execute([':club_id' => $clubId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

<?php

// 俱乐部页面的业务逻辑：成员、比赛、Elo 更新。
class ClubController
{
    private Repository $repo;
    private EloService $eloService;
    private int $defaultElo;

    public function __construct(Repository $repo, EloService $eloService, int $defaultElo)
    {
        $this->repo = $repo;
        $this->eloService = $eloService;
        $this->defaultElo = $defaultElo;
    }

    // 添加成员到指定俱乐部。
    public function addMember(int $clubId, array $input): array
    {
        // 读取并清理输入。
        $memberName = trim($input['member_name'] ?? '');

        // 基本校验：成员名不能为空。
        if ($memberName === '') {
            return [
                'success' => false,
                'message' => 'Member name is required.',
            ];
        }

        try {
            // 复用已有用户或创建新用户。
            $userId = $this->repo->getOrCreateUser($memberName);
            // 绑定成员到俱乐部，并写入默认 Elo。
            $added = $this->repo->addMemberToClub($clubId, $userId, $this->defaultElo);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to add member: ' . $e->getMessage(),
            ];
        }

        // 已存在时返回提示。
        if (! $added) {
            return [
                'success' => false,
                'message' => 'Member already in club.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Member added.',
        ];
    }

    // 记录比赛并同步更新 Elo 与历史。
    public function recordMatch(int $clubId, array $input): array
    {
        // 解析输入参数。
        $playerA = (int) ($input['player_a'] ?? 0);
        $playerB = (int) ($input['player_b'] ?? 0);
        $result = $input['result'] ?? 'A';
        $playedAt = trim($input['played_at'] ?? '');
        $matchType = trim($input['match_type'] ?? 'friendly');

        // 基本校验：选择不同选手。
        if ($playerA === 0 || $playerB === 0 || $playerA === $playerB) {
            return [
                'success' => false,
                'message' => 'Select two different players.',
            ];
        }

        // 校验结果值是否合法。
        if (! in_array($result, ['A', 'B', 'D'], true)) {
            return [
                'success' => false,
                'message' => 'Invalid match result.',
            ];
        }

        // 校验比赛类型是否合法。
        $validTypes = ['official', 'friendly', 'casual'];
        if (! in_array($matchType, $validTypes, true)) {
            $matchType = 'friendly';
        }

        // 若未提供时间则使用当前 UTC 时间。
        $playedAtValue = $playedAt !== '' ? $playedAt : gmdate('c');

        // 读取当前评分，确保双方均为俱乐部成员。
        $memberA = $this->repo->getMember($clubId, $playerA);
        $memberB = $this->repo->getMember($clubId, $playerB);

        if (! $memberA || ! $memberB) {
            return [
                'success' => false,
                'message' => 'Both players must be members of the club.',
            ];
        }

        // 根据比赛结果计算新的 Elo。
        $ratings = $this->eloService->calculate(
            (int) $memberA['current_elo'],
            (int) $memberB['current_elo'],
            $result
        );

        // 计算胜者与是否平局。
        $winnerId = null;
        $isDraw = false;
        if ($result === 'A') {
            $winnerId = $playerA;
        } elseif ($result === 'B') {
            $winnerId = $playerB;
        } else {
            $isDraw = true;
        }

        try {
            // 事务保证比赛与 Elo 同步更新。
            $this->repo->beginTransaction();

            // 写入比赛与 Elo 变动记录。
            $matchId = $this->repo->insertMatch(
                $clubId,
                $playerA,
                $playerB,
                $winnerId,
                $isDraw,
                $playedAtValue,
                $matchType
            );

            // 记录双方 Elo 变化历史。
            $this->repo->insertEloHistory(
                $matchId,
                $clubId,
                $playerA,
                (int) $memberA['current_elo'],
                (int) $ratings['newA'],
                (int) $ratings['deltaA'],
                $playedAtValue
            );
            $this->repo->insertEloHistory(
                $matchId,
                $clubId,
                $playerB,
                (int) $memberB['current_elo'],
                (int) $ratings['newB'],
                (int) $ratings['deltaB'],
                $playedAtValue
            );

            // 更新成员 Elo 与比赛场次。
            $this->repo->updateMemberElo($clubId, $playerA, (int) $ratings['newA']);
            $this->repo->updateMemberElo($clubId, $playerB, (int) $ratings['newB']);
            $this->repo->incrementMatches($clubId, $playerA);
            $this->repo->incrementMatches($clubId, $playerB);

            $this->repo->commit();
        } catch (Throwable $e) {
            // 异常回滚，避免数据不一致。
            $this->repo->rollBack();

            return [
                'success' => false,
                'message' => 'Failed to record match: ' . $e->getMessage(),
            ];
        }

        return [
            'success' => true,
            'message' => 'Match recorded and Elo updated.',
        ];
    }

    // 汇总俱乐部页面所需数据。
    public function getClubData(int $clubId): array
    {
        return [
            // 俱乐部基本信息、成员列表与近期比赛。
            'club' => $this->repo->getClub($clubId),
            'members' => $this->repo->listClubMembers($clubId),
            'matches' => $this->repo->listRecentMatchesByClub($clubId),
        ];
    }
}

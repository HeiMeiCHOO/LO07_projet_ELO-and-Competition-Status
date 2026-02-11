<?php

// 数据仓储：集中管理数据库读写。
class Repository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // 事务控制。
    public function beginTransaction(): void
    {
        // 保证多表更新一致性。
        $this->db->beginTransaction();
    }

    public function commit(): void
    {
        $this->db->commit();
    }

    public function rollBack(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    // 按用户名查询用户。
    public function getUserByUsername(string $username): ?array
    {
        // 使用预处理语句避免 SQL 注入。
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function getUserById(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    // 新建用户并返回 ID。
    public function createUser(string $username): int
    {
        // 使用 UTC 时间记录创建时间。
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, created_at) VALUES (:username, :created_at)'
        );
        $stmt->execute([
            'username' => $username,
            'created_at' => gmdate('c'),
        ]);

        return (int) $this->db->lastInsertId();
    }

    // 获取用户，若不存在则创建。
    public function getOrCreateUser(string $username): int
    {
        $username = trim($username);
        if ($username === '') {
            throw new InvalidArgumentException('Username is required.');
        }

        $user = $this->getUserByUsername($username);
        if ($user) {
            return (int) $user['id'];
        }

        return $this->createUser($username);
    }

    // 获取所有俱乐部列表。
    public function listClubs(): array
    {
        // 汇总创建者姓名与成员数量。
        $stmt = $this->db->query(
            'SELECT c.*, u.username AS creator_name,
                (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.id) AS member_count
             FROM clubs c
             LEFT JOIN users u ON u.id = c.created_by
             ORDER BY c.created_at DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 创建俱乐部并返回 ID。
    public function createClub(string $name, string $sport, int $creatorId): int
    {
        // 记录创建时间与创建者。
        $stmt = $this->db->prepare(
            'INSERT INTO clubs (name, sport, created_by, created_at)
             VALUES (:name, :sport, :created_by, :created_at)'
        );
        $stmt->execute([
            'name' => $name,
            'sport' => $sport,
            'created_by' => $creatorId,
            'created_at' => gmdate('c'),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function getClub(int $clubId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, u.username AS creator_name
             FROM clubs c
             LEFT JOIN users u ON u.id = c.created_by
             WHERE c.id = :id'
        );
        $stmt->execute(['id' => $clubId]);
        $club = $stmt->fetch(PDO::FETCH_ASSOC);

        return $club ?: null;
    }

    // 添加成员到俱乐部，重复则忽略。
    public function addMemberToClub(int $clubId, int $userId, int $initialElo): bool
    {
        // 使用 INSERT OR IGNORE 避免重复成员。
        $stmt = $this->db->prepare(
            'INSERT OR IGNORE INTO club_members
                (club_id, user_id, current_elo, matches_played, joined_at)
             VALUES (:club_id, :user_id, :current_elo, 0, :joined_at)'
        );
        $stmt->execute([
            'club_id' => $clubId,
            'user_id' => $userId,
            'current_elo' => $initialElo,
            'joined_at' => gmdate('c'),
        ]);

        return $stmt->rowCount() > 0;
    }

    public function getMember(int $clubId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT cm.*, u.username
             FROM club_members cm
             JOIN users u ON u.id = cm.user_id
             WHERE cm.club_id = :club_id AND cm.user_id = :user_id'
        );
        $stmt->execute([
            'club_id' => $clubId,
            'user_id' => $userId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    // 按 Elo 排序列出俱乐部成员。
    public function listClubMembers(int $clubId): array
    {
        // 当前 Elo 越高越靠前。
        $stmt = $this->db->prepare(
            'SELECT u.id AS user_id, u.username, cm.current_elo, cm.matches_played
             FROM club_members cm
             JOIN users u ON u.id = cm.user_id
             WHERE cm.club_id = :club_id
             ORDER BY cm.current_elo DESC, u.username ASC'
        );
        $stmt->execute(['club_id' => $clubId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 写入比赛记录。
    public function insertMatch(
        int $clubId,
        int $playerAId,
        int $playerBId,
        ?int $winnerId,
        bool $isDraw,
        string $playedAt,
        string $matchType = 'friendly'
    ): int {
        // 记录双方、胜者、比赛类型与比赛时间。
        $stmt = $this->db->prepare(
            'INSERT INTO matches
                (club_id, player_a_id, player_b_id, winner_id, is_draw, match_type, played_at)
             VALUES (:club_id, :player_a_id, :player_b_id, :winner_id, :is_draw, :match_type, :played_at)'
        );
        $stmt->execute([
            'club_id' => $clubId,
            'player_a_id' => $playerAId,
            'player_b_id' => $playerBId,
            'winner_id' => $winnerId,
            'is_draw' => $isDraw ? 1 : 0,
            'match_type' => $matchType,
            'played_at' => $playedAt,
        ]);

        return (int) $this->db->lastInsertId();
    }

    // 写入 Elo 变更历史。
    public function insertEloHistory(
        int $matchId,
        int $clubId,
        int $userId,
        int $eloBefore,
        int $eloAfter,
        int $delta,
        string $createdAt
    ): void {
        // 记录比赛前后 Elo 与增量。
        $stmt = $this->db->prepare(
            'INSERT INTO elo_history
                (match_id, club_id, user_id, elo_before, elo_after, delta, created_at)
             VALUES (:match_id, :club_id, :user_id, :elo_before, :elo_after, :delta, :created_at)'
        );
        $stmt->execute([
            'match_id' => $matchId,
            'club_id' => $clubId,
            'user_id' => $userId,
            'elo_before' => $eloBefore,
            'elo_after' => $eloAfter,
            'delta' => $delta,
            'created_at' => $createdAt,
        ]);
    }

    // 更新成员当前 Elo。
    public function updateMemberElo(int $clubId, int $userId, int $newElo): void
    {
        // 仅更新当前俱乐部的记录。
        $stmt = $this->db->prepare(
            'UPDATE club_members
             SET current_elo = :current_elo
             WHERE club_id = :club_id AND user_id = :user_id'
        );
        $stmt->execute([
            'current_elo' => $newElo,
            'club_id' => $clubId,
            'user_id' => $userId,
        ]);
    }

    // 累计成员比赛场次。
    public function incrementMatches(int $clubId, int $userId): void
    {
        // 直接在数据库内累加。
        $stmt = $this->db->prepare(
            'UPDATE club_members
             SET matches_played = matches_played + 1
             WHERE club_id = :club_id AND user_id = :user_id'
        );
        $stmt->execute([
            'club_id' => $clubId,
            'user_id' => $userId,
        ]);
    }

    // 列出最近比赛（用于俱乐部页）。
    public function listRecentMatchesByClub(int $clubId, int $limit = 10): array
    {
        // 关联用户表以展示姓名。
        $stmt = $this->db->prepare(
            'SELECT m.*, ua.username AS player_a_name, ub.username AS player_b_name,
                uw.username AS winner_name
             FROM matches m
             JOIN users ua ON ua.id = m.player_a_id
             JOIN users ub ON ub.id = m.player_b_id
             LEFT JOIN users uw ON uw.id = m.winner_id
             WHERE m.club_id = :club_id
             ORDER BY m.played_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('club_id', $clubId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 列出比赛历史，可按选手名和比赛类型筛选。
    public function listMatches(int $clubId, string $playerFilter = '', string $typeFilter = ''): array
    {
        // 动态拼接筛选条件。
        $playerFilter = trim($playerFilter);
        $typeFilter = trim($typeFilter);
        
        $sql =
            'SELECT m.*, ua.username AS player_a_name, ub.username AS player_b_name,
                uw.username AS winner_name
             FROM matches m
             JOIN users ua ON ua.id = m.player_a_id
             JOIN users ub ON ub.id = m.player_b_id
             LEFT JOIN users uw ON uw.id = m.winner_id
             WHERE m.club_id = :club_id';

        $params = ['club_id' => $clubId];
        
        if ($playerFilter !== '') {
            $sql .= ' AND (ua.username LIKE :filter OR ub.username LIKE :filter)';
            $params['filter'] = '%' . $playerFilter . '%';
        }
        
        if ($typeFilter !== '') {
            $sql .= ' AND m.match_type = :type';
            $params['type'] = $typeFilter;
        }

        $sql .= ' ORDER BY m.played_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 获取成员 Elo 变化历史。
    public function listEloHistory(int $clubId, int $userId): array
    {
        // 按时间顺序用于绘制曲线。
        $stmt = $this->db->prepare(
            'SELECT * FROM elo_history
             WHERE club_id = :club_id AND user_id = :user_id
             ORDER BY created_at ASC'
        );
        $stmt->execute([
            'club_id' => $clubId,
            'user_id' => $userId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 获取成员在俱乐部的所有比赛（作为 player_a 或 player_b）。
    public function listMemberMatches(int $clubId, int $userId): array
    {
        // 获取所有涉及该成员的比赛，包括对手名称与 Elo 变化。
        $stmt = $this->db->prepare(
            'SELECT 
                m.id,
                m.played_at,
                CASE 
                    WHEN m.player_a_id = :user_id THEN ub.username
                    ELSE ua.username
                END AS opponent_name,
                m.player_a_id,
                m.player_b_id,
                m.winner_id,
                m.is_draw,
                eh1.elo_before,
                eh1.elo_after,
                eh1.delta
             FROM matches m
             JOIN users ua ON ua.id = m.player_a_id
             JOIN users ub ON ub.id = m.player_b_id
             JOIN elo_history eh1 ON eh1.match_id = m.id AND eh1.user_id = :user_id
             WHERE m.club_id = :club_id AND (m.player_a_id = :user_id OR m.player_b_id = :user_id)
             ORDER BY m.played_at DESC'
        );
        $stmt->execute([
            'club_id' => $clubId,
            'user_id' => $userId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

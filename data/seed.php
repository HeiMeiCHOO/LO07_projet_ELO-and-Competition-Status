<?php
/**
 * 数据库种子脚本：生成演示数据
 * 
 * 用法：php data/seed.php
 * 
 * 生成内容：
 * - 3 个俱乐部（象棋、足球、篮球）
 * - 约 25 个成员（涵盖所有俱乐部）
 * - 约 145 场比赛记录（含官方赛、友谊赛、随意赛）
 * - Elo 变化历史
 */

require __DIR__ . '/../app/bootstrap.php';

// 清空现有数据（可选）
function clear_data(PDO $db): void
{
    $db->exec('DELETE FROM elo_history');
    $db->exec('DELETE FROM matches');
    $db->exec('DELETE FROM club_members');
    $db->exec('DELETE FROM clubs');
    $db->exec('DELETE FROM users');
    echo "✓ 已清空现有数据\n";
}

// 生成演示数据
function seed_demo_data(PDO $db, Repository $repo, EloService $eloService, int $defaultElo): void
{
    // 定义三个俱乐部的数据（包含比赛类型）
    $clubsData = [
        [
            'name' => '象棋俱乐部',
            'sport' => 'Chess',
            'members' => ['Alice', 'Bob', 'Charlie', 'Diana', 'Eve', 'Frank', 'Grace', 'Henry'],
            'matches' => [
                ['Alice', 'Eve', 'A', 'friendly'],
                ['Bob', 'Diana', 'A', 'official'],
                ['Alice', 'Bob', 'A', 'official'],
                ['Charlie', 'Eve', 'A', 'friendly'],
                ['Bob', 'Charlie', 'A', 'friendly'],
                ['Diana', 'Eve', 'D', 'casual'],
                ['Alice', 'Charlie', 'B', 'official'],
                ['Bob', 'Diana', 'B', 'friendly'],
                ['Eve', 'Alice', 'D', 'casual'],
                ['Charlie', 'Bob', 'B', 'friendly'],
                ['Frank', 'Grace', 'A', 'friendly'],
                ['Henry', 'Alice', 'B', 'official'],
                ['Diana', 'Frank', 'A', 'friendly'],
                ['Grace', 'Bob', 'A', 'casual'],
                ['Alice', 'Henry', 'A', 'official'],
                ['Charlie', 'Frank', 'D', 'friendly'],
                ['Bob', 'Grace', 'A', 'friendly'],
                ['Diana', 'Henry', 'A', 'official'],
                ['Eve', 'Frank', 'B', 'casual'],
                ['Alice', 'Grace', 'A', 'friendly'],
                ['Charlie', 'Diana', 'B', 'official'],
                ['Bob', 'Henry', 'A', 'friendly'],
                ['Frank', 'Alice', 'D', 'casual'],
                ['Grace', 'Charlie', 'A', 'friendly'],
                ['Henry', 'Diana', 'B', 'official'],
                ['Eve', 'Bob', 'A', 'friendly'],
                ['Alice', 'Frank', 'A', 'official'],
                ['Diana', 'Grace', 'A', 'casual'],
                ['Charlie', 'Henry', 'B', 'friendly'],
                ['Bob', 'Frank', 'A', 'official'],
                ['Eve', 'Grace', 'D', 'friendly'],
                ['Alice', 'Diana', 'A', 'casual'],
                ['Charlie', 'Grace', 'A', 'friendly'],
                ['Henry', 'Frank', 'B', 'official'],
                ['Bob', 'Eve', 'A', 'friendly'],
                ['Alice', 'Bob', 'D', 'official'],
                ['Diana', 'Charlie', 'A', 'casual'],
                ['Frank', 'Henry', 'A', 'friendly'],
                ['Grace', 'Alice', 'B', 'official'],
                ['Eve', 'Henry', 'A', 'friendly'],
                ['Bob', 'Charlie', 'A', 'casual'],
                ['Diana', 'Frank', 'D', 'friendly'],
                ['Alice', 'Eve', 'A', 'official'],
                ['Grace', 'Henry', 'B', 'friendly'],
                ['Charlie', 'Frank', 'A', 'casual'],
                ['Bob', 'Diana', 'A', 'official'],
            ],
        ],
        [
            'name' => '足球俱乐部',
            'sport' => 'Football',
            'members' => ['Tom', 'Jerry', 'Mike', 'John', 'Peter', 'David', 'Alex', 'Ryan', 'Chris'],
            'matches' => [
                ['Tom', 'Peter', 'A', 'official'],
                ['Jerry', 'John', 'A', 'friendly'],
                ['Mike', 'David', 'A', 'friendly'],
                ['Tom', 'Jerry', 'D', 'casual'],
                ['John', 'David', 'A', 'official'],
                ['Mike', 'Peter', 'B', 'friendly'],
                ['Jerry', 'David', 'A', 'official'],
                ['Tom', 'John', 'A', 'friendly'],
                ['Peter', 'Mike', 'B', 'casual'],
                ['David', 'Jerry', 'A', 'official'],
                ['Tom', 'Mike', 'A', 'friendly'],
                ['Peter', 'John', 'D', 'friendly'],
                ['Alex', 'Ryan', 'A', 'friendly'],
                ['Chris', 'Tom', 'B', 'official'],
                ['Jerry', 'Alex', 'A', 'friendly'],
                ['Mike', 'Ryan', 'A', 'casual'],
                ['John', 'Chris', 'A', 'official'],
                ['David', 'Alex', 'D', 'friendly'],
                ['Tom', 'Ryan', 'A', 'friendly'],
                ['Peter', 'Chris', 'B', 'official'],
                ['Jerry', 'Ryan', 'A', 'friendly'],
                ['Mike', 'Chris', 'A', 'casual'],
                ['John', 'Alex', 'B', 'friendly'],
                ['David', 'Tom', 'A', 'official'],
                ['Alex', 'Peter', 'A', 'friendly'],
                ['Ryan', 'Jerry', 'D', 'casual'],
                ['Chris', 'Mike', 'A', 'friendly'],
                ['Tom', 'John', 'A', 'official'],
                ['Peter', 'Alex', 'B', 'friendly'],
                ['David', 'Ryan', 'A', 'casual'],
                ['Jerry', 'Chris', 'A', 'official'],
                ['Mike', 'Tom', 'B', 'friendly'],
                ['John', 'Ryan', 'A', 'friendly'],
                ['Peter', 'David', 'D', 'official'],
                ['Alex', 'Chris', 'A', 'casual'],
                ['Tom', 'Alex', 'A', 'friendly'],
                ['Jerry', 'Peter', 'B', 'official'],
                ['Mike', 'John', 'A', 'friendly'],
                ['David', 'Chris', 'A', 'casual'],
                ['Ryan', 'Tom', 'D', 'friendly'],
                ['John', 'Mike', 'A', 'official'],
                ['Peter', 'Jerry', 'A', 'friendly'],
                ['Alex', 'David', 'B', 'casual'],
                ['Chris', 'Ryan', 'A', 'official'],
                ['Jerry', 'Mike', 'A', 'friendly'],
                ['Tom', 'Peter', 'D', 'casual'],
                ['John', 'David', 'A', 'friendly'],
                ['Alex', 'John', 'A', 'official'],
                ['Chris', 'Tom', 'B', 'friendly'],
                ['Ryan', 'Mike', 'A', 'friendly'],
            ],
        ],
        [
            'name' => '篮球俱乐部',
            'sport' => 'Basketball',
            'members' => ['James', 'Kobe', 'LeBron', 'Durant', 'Curry', 'Harden', 'Wade', 'Paul', 'Westbrook'],
            'matches' => [
                ['James', 'Curry', 'A', 'friendly'],
                ['Kobe', 'Harden', 'A', 'official'],
                ['LeBron', 'Durant', 'A', 'friendly'],
                ['James', 'Kobe', 'B', 'casual'],
                ['Durant', 'Curry', 'A', 'friendly'],
                ['Harden', 'LeBron', 'D', 'official'],
                ['Kobe', 'Durant', 'A', 'official'],
                ['Curry', 'Harden', 'A', 'friendly'],
                ['LeBron', 'James', 'B', 'casual'],
                ['James', 'Durant', 'D', 'friendly'],
                ['Kobe', 'Curry', 'B', 'official'],
                ['Harden', 'LeBron', 'A', 'friendly'],
                ['James', 'Harden', 'A', 'official'],
                ['Kobe', 'LeBron', 'A', 'friendly'],
                ['Wade', 'Paul', 'A', 'friendly'],
                ['Westbrook', 'James', 'B', 'official'],
                ['Curry', 'Wade', 'A', 'friendly'],
                ['LeBron', 'Paul', 'A', 'casual'],
                ['Durant', 'Harden', 'D', 'official'],
                ['James', 'Wade', 'A', 'friendly'],
                ['Kobe', 'Paul', 'B', 'casual'],
                ['Curry', 'Westbrook', 'A', 'friendly'],
                ['LeBron', 'Wade', 'A', 'official'],
                ['Durant', 'Paul', 'A', 'friendly'],
                ['Harden', 'Westbrook', 'D', 'casual'],
                ['James', 'Curry', 'B', 'official'],
                ['Kobe', 'Wade', 'A', 'friendly'],
                ['LeBron', 'Paul', 'A', 'friendly'],
                ['Durant', 'Westbrook', 'A', 'casual'],
                ['Harden', 'Paul', 'B', 'official'],
                ['Curry', 'James', 'A', 'friendly'],
                ['Kobe', 'Westbrook', 'A', 'casual'],
                ['LeBron', 'Curry', 'D', 'official'],
                ['James', 'Paul', 'A', 'friendly'],
                ['Wade', 'Westbrook', 'A', 'friendly'],
                ['Harden', 'Wade', 'B', 'official'],
                ['Kobe', 'James', 'A', 'casual'],
                ['LeBron', 'Harden', 'A', 'friendly'],
                ['Durant', 'Curry', 'D', 'official'],
                ['Paul', 'Westbrook', 'A', 'friendly'],
                ['James', 'LeBron', 'A', 'casual'],
                ['Kobe', 'Curry', 'B', 'friendly'],
                ['Wade', 'Durant', 'A', 'official'],
                ['Harden', 'Westbrook', 'A', 'friendly'],
                ['James', 'Durant', 'A', 'casual'],
                ['Curry', 'Paul', 'D', 'official'],
                ['LeBron', 'Westbrook', 'A', 'friendly'],
            ],
        ],
    ];

    // 创建创建者
    $creatorId = $repo->getOrCreateUser('Admin');

    // 遍历每个俱乐部
    foreach ($clubsData as $clubData) {
        $clubName = $clubData['name'];
        $sport = $clubData['sport'];
        $members = $clubData['members'];
        $matches = $clubData['matches'];

        // 创建俱乐部
        $clubId = $repo->createClub($clubName, $sport, $creatorId);
        echo "✓ 创建俱乐部: $clubName (ID: $clubId, 运动: $sport)\n";

        // 添加成员
        $memberIds = [];
        foreach ($members as $name) {
            $userId = $repo->getOrCreateUser($name);
            $repo->addMemberToClub($clubId, $userId, $defaultElo);
            $memberIds[$name] = $userId;
            echo "  ✓ 添加成员: $name (Elo: $defaultElo)\n";
        }

        // 执行比赛
        echo "\n📊 $clubName 比赛记录:\n";
        foreach ($matches as $index => $match) {
            [$playerAName, $playerBName, $result, $matchType] = $match;
            $playerAId = $memberIds[$playerAName];
            $playerBId = $memberIds[$playerBName];

            // 读取当前 Elo
            $memberA = $repo->getMember($clubId, $playerAId);
            $memberB = $repo->getMember($clubId, $playerBId);
            $eloABefore = (int) $memberA['current_elo'];
            $eloBBefore = (int) $memberB['current_elo'];

            // 计算新 Elo
            $ratings = $eloService->calculate($eloABefore, $eloBBefore, $result);

            // 确定赢家
            $winnerId = null;
            $isDraw = false;
            if ($result === 'A') {
                $winnerId = $playerAId;
            } elseif ($result === 'B') {
                $winnerId = $playerBId;
            } else {
                $isDraw = true;
            }

            // 插入比赛记录（模拟时间间隔 2 天）
            $playedAt = gmdate('c', strtotime('-' . (count($matches) - $index) * 2 . ' days'));

            try {
                $repo->beginTransaction();

                $matchId = $repo->insertMatch(
                    $clubId,
                    $playerAId,
                    $playerBId,
                    $winnerId,
                    $isDraw,
                    $playedAt,
                    $matchType
                );

                // 插入 Elo 历史
                $repo->insertEloHistory(
                    $matchId,
                    $clubId,
                    $playerAId,
                    $eloABefore,
                    (int) $ratings['newA'],
                    (int) $ratings['deltaA'],
                    $playedAt
                );

                $repo->insertEloHistory(
                    $matchId,
                    $clubId,
                    $playerBId,
                    $eloBBefore,
                    (int) $ratings['newB'],
                    (int) $ratings['deltaB'],
                    $playedAt
                );

                // 更新成员 Elo
                $repo->updateMemberElo($clubId, $playerAId, (int) $ratings['newA']);
                $repo->updateMemberElo($clubId, $playerBId, (int) $ratings['newB']);
                $repo->incrementMatches($clubId, $playerAId);
                $repo->incrementMatches($clubId, $playerBId);

                $repo->commit();

                // 输出比赛结果
                $resultStr = $result === 'A' ? "$playerAName 胜" : ($result === 'B' ? "$playerBName 胜" : '平局');
                $deltaA = $ratings['deltaA'];
                $deltaB = $ratings['deltaB'];
                $typeLabel = match($matchType) {
                    'official' => '[官方赛]',
                    'casual' => '[随意赛]',
                    default => '[友谊赛]'
                };
                printf(
                    "  比赛 %2d: %s vs %s → %s %s | %s %+d → %d, %s %+d → %d\n",
                    $index + 1,
                    str_pad($playerAName, 8),
                    str_pad($playerBName, 8),
                    $resultStr,
                    $typeLabel,
                    str_pad($playerAName, 8),
                    $deltaA,
                    (int) $ratings['newA'],
                    str_pad($playerBName, 8),
                    $deltaB,
                    (int) $ratings['newB']
                );
            } catch (Throwable $e) {
                $repo->rollBack();
                echo "  ✗ 比赛 " . ($index + 1) . " 失败: " . $e->getMessage() . "\n";
            }
        }

        // 输出最终排名
        echo "\n🏆 $clubName 最终排名:\n";
        $finalMembers = $repo->listClubMembers($clubId);
        foreach ($finalMembers as $idx => $member) {
            printf(
                "  %d. %s - Elo: %4d (参赛: %d 场)\n",
                $idx + 1,
                str_pad($member['username'], 10),
                (int) $member['current_elo'],
                (int) $member['matches_played']
            );
        }

        echo "\n" . str_repeat("─", 60) . "\n\n";
    }
}

echo "\n=== LO07 数据库种子脚本 ===\n\n";

try {
    // 清空现有数据
    clear_data($db);

    // 生成演示数据
    echo "\n📝 生成演示数据...\n";
    seed_demo_data($db, $repo, $eloService, $defaultElo);

    echo "\n✅ 数据生成完成！\n";
    echo "   访问 http://localhost:8001 开始测试\n\n";
} catch (Throwable $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    exit(1);
}

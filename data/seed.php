<?php
/**
 * 数据库种子脚本：生成演示数据
 * 
 * 用法：php data/seed.php
 * 
 * 生成内容：
 * - 1 个俱乐部（象棋俱乐部）
 * - 5 个成员
 * - 10 场比赛记录
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
    // 定义三个俱乐部的数据
    $clubsData = [
        [
            'name' => '象棋俱乐部',
            'sport' => 'Chess',
            'members' => ['Alice', 'Bob', 'Charlie', 'Diana', 'Eve'],
            'matches' => [
                ['Alice', 'Eve', 'A'],
                ['Bob', 'Diana', 'A'],
                ['Alice', 'Bob', 'A'],
                ['Charlie', 'Eve', 'A'],
                ['Bob', 'Charlie', 'A'],
                ['Diana', 'Eve', 'D'],
                ['Alice', 'Charlie', 'B'],
                ['Bob', 'Diana', 'B'],
                ['Eve', 'Alice', 'D'],
                ['Charlie', 'Bob', 'B'],
            ],
        ],
        [
            'name' => '足球俱乐部',
            'sport' => 'Football',
            'members' => ['Tom', 'Jerry', 'Mike', 'John', 'Peter', 'David'],
            'matches' => [
                ['Tom', 'Peter', 'A'],
                ['Jerry', 'John', 'A'],
                ['Mike', 'David', 'A'],
                ['Tom', 'Jerry', 'D'],
                ['John', 'David', 'A'],
                ['Mike', 'Peter', 'B'],
                ['Jerry', 'David', 'A'],
                ['Tom', 'John', 'A'],
                ['Peter', 'Mike', 'B'],
                ['David', 'Jerry', 'A'],
                ['Tom', 'Mike', 'A'],
                ['Peter', 'John', 'D'],
            ],
        ],
        [
            'name' => '篮球俱乐部',
            'sport' => 'Basketball',
            'members' => ['James', 'Kobe', 'LeBron', 'Durant', 'Curry', 'Harden'],
            'matches' => [
                ['James', 'Curry', 'A'],
                ['Kobe', 'Harden', 'A'],
                ['LeBron', 'Durant', 'A'],
                ['James', 'Kobe', 'B'],
                ['Durant', 'Curry', 'A'],
                ['Harden', 'LeBron', 'D'],
                ['Kobe', 'Durant', 'A'],
                ['Curry', 'Harden', 'A'],
                ['LeBron', 'James', 'B'],
                ['James', 'Durant', 'D'],
                ['Kobe', 'Curry', 'B'],
                ['Harden', 'LeBron', 'A'],
                ['James', 'Harden', 'A'],
                ['Kobe', 'LeBron', 'A'],
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
            [$playerAName, $playerBName, $result] = $match;
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
                    $playedAt
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
                printf(
                    "  比赛 %2d: %s vs %s → %s | %s %+d → %d, %s %+d → %d\n",
                    $index + 1,
                    str_pad($playerAName, 8),
                    str_pad($playerBName, 8),
                    $resultStr,
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
    echo "   访问 http://localhost:8000 开始测试\n\n";
} catch (Throwable $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    exit(1);
}

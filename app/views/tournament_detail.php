<?php
/**
 * 锦标赛详情页面。
 * 
 * 显示功能：
 * - 锦标赛基本信息
 * - 参与者列表
 * - 比赛配对和结果
 * - 当前排名
 */

$pageTitle = htmlspecialchars($tournament['name']) . ' - Tournament';
ob_start();
?>

<!-- 页面头部 -->
<header>
    <h1>🏆 <?= htmlspecialchars($tournament['name']) ?></h1>
    <p>
        Club: <strong><?= htmlspecialchars($club['name']) ?></strong> | 
        Format: <strong><?= $tournament['format'] === 'round-robin' ? 'Round-Robin' : 'Single Elimination' ?></strong> | 
        Status: 
        <?php
        $statusLabels = [
            'draft' => ['label' => 'Draft', 'color' => 'var(--amber)'],
            'in-progress' => ['label' => 'In Progress', 'color' => 'var(--green)'],
            'completed' => ['label' => 'Completed', 'color' => 'var(--blue)'],
        ];
        $status = $statusLabels[$tournament['status']] ?? ['label' => 'Unknown', 'color' => 'gray'];
        ?>
        <span style="color: <?= $status['color'] ?>; font-weight: 600;"><?= $status['label'] ?></span>
    </p>
</header>

<!-- 参与者列表 -->
<section class="card">
    <h2>Participants</h2>
    <div class="table-wrapper">
    <table>
        <thead>
            <tr style="background: linear-gradient(135deg, var(--sky), var(--blue));">
                <th>Seed</th>
                <th>Player</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($participants as $participant) : ?>
                <tr>
                    <td><strong><?= $participant['seed'] ?></strong></td>
                    <td><?= htmlspecialchars($participant['username']) ?></td>
                    <td>
                        <?php
                        $pStatusColor = $participant['status'] === 'active' ? 'var(--green)' : 'var(--red)';
                        ?>
                        <span style="color: <?= $pStatusColor ?>;">
                            <?= ucfirst($participant['status']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<!-- 当前排名 -->
<?php if ($tournament['status'] !== 'draft') : ?>
<section class="card">
    <h2>Current Standings</h2>
    <div class="table-wrapper">
    <table>
        <thead>
            <tr style="background: linear-gradient(135deg, var(--green), var(--sky));">
                <th>Rank</th>
                <th>Player</th>
                <th>Played</th>
                <th>Wins</th>
                <th>Draws</th>
                <th>Losses</th>
                <th>Points</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($standings as $rank => $standing) : ?>
                <tr>
                    <td><strong><?= $rank + 1 ?></strong></td>
                    <td><?= htmlspecialchars($standing['username']) ?></td>
                    <td><?= $standing['matches_played'] ?></td>
                    <td style="color: var(--green); font-weight: 600;"><?= $standing['wins'] ?></td>
                    <td style="color: var(--amber);"><?= $standing['draws'] ?></td>
                    <td style="color: var(--red);"><?= $standing['losses'] ?></td>
                    <td><strong><?= $standing['points'] ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?php endif; ?>

<!-- 比赛列表 -->
<?php if ($tournament['status'] !== 'draft') : ?>
<section class="card">
    <h2>Matches</h2>
    <?php if (empty($matches)) : ?>
        <p>No matches have been scheduled yet.</p>
    <?php else : ?>
        <?php
        // 按轮次分组
        $matchesByRound = [];
        foreach ($matches as $match) {
            $matchesByRound[$match['round']][] = $match;
        }
        ?>
        <?php foreach ($matchesByRound as $round => $roundMatches) : ?>
            <h3 style="color: var(--blue); margin-top: 20px;">Round <?= $round ?></h3>
            <div class="table-wrapper">
            <table>
                <thead>
                    <tr style="background: linear-gradient(135deg, var(--purple-light), var(--blue-light));">
                        <th>Player A</th>
                        <th>vs</th>
                        <th>Player B</th>
                        <th>Result</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roundMatches as $match) : ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($match['player_a_name']) ?></strong>
                                <?php if ($match['winner_id'] == $match['player_a_id']) : ?>
                                    <span style="color: var(--green); font-weight: bold;"> ✓</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; color: var(--gray);">vs</td>
                            <td>
                                <strong><?= htmlspecialchars($match['player_b_name']) ?></strong>
                                <?php if ($match['winner_id'] == $match['player_b_id']) : ?>
                                    <span style="color: var(--green); font-weight: bold;"> ✓</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($match['is_draw']) : ?>
                                    <span style="color: var(--amber); font-weight: 600;">Draw</span>
                                <?php elseif ($match['winner_id']) : ?>
                                    <span style="color: var(--green); font-weight: 600;">
                                        <?= htmlspecialchars($match['winner_name']) ?> wins
                                    </span>
                                <?php else : ?>
                                    <span style="color: var(--gray);">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$match['winner_id'] && !$match['is_draw']) : ?>
                                    <form method="post" action="tournament_detail.php?id=<?= $tournament['id'] ?>&action=record" style="display: inline;">
                                        <input type="hidden" name="match_id" value="<?= $match['match_id'] ?>">
                                        <select name="result" required style="font-size: 0.9em; padding: 4px;">
                                            <option value="">Select...</option>
                                            <option value="A"><?= htmlspecialchars($match['player_a_name']) ?> wins</option>
                                            <option value="B"><?= htmlspecialchars($match['player_b_name']) ?> wins</option>
                            <option value="D">Draw</option>
                                        </select>
                                        <button type="submit" style="font-size: 0.9em; padding: 4px 8px;" class="btn-small">Record</button>
                                    </form>
                                <?php else : ?>
                                    <span style="color: var(--green);">✓ Complete</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

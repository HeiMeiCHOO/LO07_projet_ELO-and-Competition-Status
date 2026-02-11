<?php
/**
 * 锦标赛列表页面。
 * 
 * 显示功能：
 * - 俱乐部的所有锦标赛列表
 * - 创建新锦标赛的表单
 */

$pageTitle = 'Tournaments - ' . htmlspecialchars($club['name']);
ob_start();
?>

<!-- 页面头部 -->
<header>
    <h1>🏆 Tournaments</h1>
    <p>Club: <strong><?= htmlspecialchars($club['name']) ?></strong> (<?= htmlspecialchars($club['sport']) ?>)</p>
</header>

<!-- 创建锦标赛表单 -->
<section class="card">
    <h2>Create New Tournament</h2>
    <form method="post" action="tournament.php?club_id=<?= $club['id'] ?>&action=create">
        <label>
            Tournament Name
            <input type="text" name="name" placeholder="e.g., Spring Championship 2026" required>
        </label>

        <label>
            Format
            <select name="format" required>
                <option value="round-robin">Round-Robin (循环赛 - 每人与每人对战)</option>
                <option value="elimination">Single Elimination (淘汰制 - 单败淘汰)</option>
            </select>
        </label>

        <fieldset>
            <legend>Select Participants (至少选择2人)</legend>
            <div style="max-height: 200px; overflow-y: auto; border: 1px solid var(--light-gray); padding: 10px; border-radius: 4px;">
                <?php foreach ($members as $member) : ?>
                    <label style="display: block; margin: 5px 0;">
                        <input type="checkbox" name="participants[]" value="<?= $member['user_id'] ?>">
                        <?= htmlspecialchars($member['username']) ?> (Elo: <?= $member['current_elo'] ?>)
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <button type="submit">Create Tournament</button>
    </form>
</section>

<!-- 锦标赛列表 -->
<section class="card">
    <h2>Tournaments</h2>
    <?php if (empty($tournaments)) : ?>
        <p>No tournaments yet. Create your first tournament above!</p>
    <?php else : ?>
        <table>
            <thead>
                <tr style="background: linear-gradient(135deg, var(--purple), var(--blue));">
                    <th>Name</th>
                    <th>Format</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tournaments as $tournament) : ?>
                    <?php
                    $statusLabels = [
                        'draft' => ['label' => 'Draft', 'color' => 'var(--amber)'],
                        'in-progress' => ['label' => 'In Progress', 'color' => 'var(--green)'],
                        'completed' => ['label' => 'Completed', 'color' => 'var(--blue)'],
                    ];
                    $status = $statusLabels[$tournament['status']] ?? ['label' => 'Unknown', 'color' => 'gray'];

                    $formatLabels = [
                        'round-robin' => 'Round-Robin',
                        'elimination' => 'Elimination',
                    ];
                    $formatLabel = $formatLabels[$tournament['format']] ?? $tournament['format'];

                    $createdDate = new DateTimeImmutable($tournament['created_at']);
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($tournament['name']) ?></strong></td>
                        <td><?= htmlspecialchars($formatLabel) ?></td>
                        <td>
                            <span style="color: <?= $status['color'] ?>; font-weight: 600;">
                                <?= $status['label'] ?>
                            </span>
                        </td>
                        <td><?= $createdDate->format('M d, Y') ?></td>
                        <td>
                            <a href="tournament_detail.php?id=<?= $tournament['id'] ?>" class="btn-link">View Details</a>
                            <?php if ($tournament['status'] === 'draft') : ?>
                                <form method="post" action="tournament.php?club_id=<?= $club['id'] ?>&action=start&id=<?= $tournament['id'] ?>" style="display: inline;">
                                    <button type="submit" onclick="return confirm('Start this tournament? Matches will be generated.');">Start</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

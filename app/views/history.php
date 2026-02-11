<?php // 提示信息区。 ?>
<?php if (! empty($message)) : ?>
    <div class="alert <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<section class="card">
    <h2>Match History - <?php echo htmlspecialchars($club['name']); ?></h2>
    <?php // 按选手名和比赛类型过滤比赛历史。 ?>
    <form method="get" class="form-inline">
        <input type="hidden" name="club_id" value="<?php echo (int) $club['id']; ?>">
        <label>
            Filter by player
            <input type="text" name="player" value="<?php echo htmlspecialchars($filter); ?>" placeholder="Player name">
        </label>
        <label>
            Match type
            <select name="type">
                <option value="">All types</option>
                <option value="official" <?php echo ($filterType === 'official') ? 'selected' : ''; ?>>Official</option>
                <option value="friendly" <?php echo ($filterType === 'friendly') ? 'selected' : ''; ?>>Friendly</option>
                <option value="casual" <?php echo ($filterType === 'casual') ? 'selected' : ''; ?>>Casual</option>
            </select>
        </label>
        <button type="submit">Apply</button>
        <a class="link" href="match_history.php?club_id=<?php echo (int) $club['id']; ?>">Reset</a>
    </form>
</section>

<section class="card">
    <?php if (empty($matches)) : ?>
        <p>No matches found.</p>
    <?php else : ?>
        <?php // 历史比赛表格，包含比赛类型。 ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Players</th>
                    <th>Result</th>
                    <th>Type</th>
                    <th>Played at</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($matches as $match) : ?>
                    <?php
                    // 确定比赛类型的显示标签和颜色。
                    $typeLabel = match($match['match_type'] ?? 'friendly') {
                        'official' => 'Official',
                        'casual' => 'Casual',
                        default => 'Friendly'
                    };
                    $typeColor = match($match['match_type'] ?? 'friendly') {
                        'official' => 'var(--blue)',
                        'casual' => 'var(--amber)',
                        default => 'var(--green)'
                    };
                    ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($match['player_a_name']); ?>
                            vs
                            <?php echo htmlspecialchars($match['player_b_name']); ?>
                        </td>
                        <td>
                            <?php if ((int) $match['is_draw'] === 1) : ?>
                                Draw
                            <?php else : ?>
                                Winner: <?php echo htmlspecialchars($match['winner_name'] ?? ''); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="
                                color: <?php echo $typeColor; ?>;
                                font-weight: 600;
                                font-size: 0.9rem;
                            ">
                                <?php echo $typeLabel; ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            // 格式化时间显示：Feb 11, 13:11
                            $dt = new DateTimeImmutable($match['played_at']);
                            echo $dt->format('M d, H:i');
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php // 提示信息区。 ?>
<?php if (! empty($message)) : ?>
    <div class="alert <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<section class="card">
    <h2>Match History - <?php echo htmlspecialchars($club['name']); ?></h2>
    <?php // 按选手名过滤比赛历史。 ?>
    <form method="get" class="form-inline">
        <input type="hidden" name="club_id" value="<?php echo (int) $club['id']; ?>">
        <label>
            Filter by player
            <input type="text" name="player" value="<?php echo htmlspecialchars($filter); ?>" placeholder="Player name">
        </label>
        <button type="submit">Apply</button>
        <a class="link" href="match_history.php?club_id=<?php echo (int) $club['id']; ?>">Reset</a>
    </form>
</section>

<section class="card">
    <?php if (empty($matches)) : ?>
        <p>No matches found.</p>
    <?php else : ?>
        <?php // 历史比赛表格。 ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Players</th>
                    <th>Result</th>
                    <th>Played at</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($matches as $match) : ?>
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
                        <td><?php echo htmlspecialchars($match['played_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

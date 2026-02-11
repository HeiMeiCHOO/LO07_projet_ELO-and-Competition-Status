<?php // 提示信息区。 ?>
<?php if (! empty($message)) : ?>
    <div class="alert <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<section class="card">
    <h2><?php echo htmlspecialchars($club['name']); ?> (<?php echo htmlspecialchars($club['sport']); ?>)</h2>
    <p>Created by <?php echo htmlspecialchars($club['creator_name'] ?? ''); ?></p>
</section>

<section class="card">
    <h2>Members</h2>
    <?php if (empty($members)) : ?>
        <p>No members yet.</p>
    <?php else : ?>
        <?php // 成员表格，按 Elo 排序。 ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Player</th>
                    <th>Elo</th>
                    <th>Matches</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($members as $member) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($member['username']); ?></td>
                        <td><?php echo (int) $member['current_elo']; ?></td>
                        <td><?php echo (int) $member['matches_played']; ?></td>
                        <td>
                            <a class="link" href="member.php?club_id=<?php echo (int) $club['id']; ?>&user_id=<?php echo (int) $member['user_id']; ?>">
                                View profile
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="grid-2">
    <div class="card">
        <h2>Add Member</h2>
        <?php // 添加成员表单。 ?>
        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="add_member">
            <label>
                Member name
                <input type="text" name="member_name" required>
            </label>
            <button type="submit">Add member</button>
        </form>
    </div>

    <div class="card">
        <h2>Record Match</h2>
        <?php // 记录比赛表单，提交时有确认提示。 ?>
        <form method="post" class="form-grid" data-confirm="Record match and update Elo?">
            <input type="hidden" name="action" value="record_match">
            <label>
                Player A
                <select name="player_a" required>
                    <option value="">Select</option>
                    <?php foreach ($members as $member) : ?>
                        <option value="<?php echo (int) $member['user_id']; ?>">
                            <?php echo htmlspecialchars($member['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Player B
                <select name="player_b" required>
                    <option value="">Select</option>
                    <?php foreach ($members as $member) : ?>
                        <option value="<?php echo (int) $member['user_id']; ?>">
                            <?php echo htmlspecialchars($member['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Result
                <select name="result" required>
                    <option value="A">Player A wins</option>
                    <option value="B">Player B wins</option>
                    <option value="D">Draw</option>
                </select>
            </label>
            <label>
                Match Type
                <select name="match_type">
                    <option value="friendly">Friendly (友谊赛)</option>
                    <option value="official">Official (官方赛)</option>
                    <option value="casual">Casual (随意赛)</option>
                </select>
            </label>
            <label>
                Played at (optional ISO time)
                <input type="text" name="played_at" placeholder="2026-02-10T18:00:00Z">
            </label>
            <button type="submit">Save match</button>
        </form>
    </div>
</section>

<section class="card">
    <h2>Recent Matches</h2>
    <?php if (empty($matches)) : ?>
        <p>No matches yet.</p>
    <?php else : ?>
        <?php // 最近比赛展示，包含比赛类型。 ?>
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
                        <td><?php echo htmlspecialchars($match['played_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php // 提示信息区。 ?>
<?php if (! empty($message)) : ?>
    <div class="alert <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<section class="card">
    <h2><?php echo htmlspecialchars($user['username']); ?> - <?php echo htmlspecialchars($club['name']); ?></h2>
    <p>Current Elo: <?php echo (int) $membership['current_elo']; ?></p>
</section>

<section class="card">
    <h2>Recent Matches</h2>
    <?php if (empty($recent_matches)) : ?>
        <p>No matches yet.</p>
    <?php else : ?>
        <?php // 最近比赛列表（按时间倒序）。 ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>vs</th>
                    <th>Result</th>
                    <th>Elo Change</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recent_matches as $match) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($match['opponent_name']); ?></td>
                        <td>
                            <?php if ((int) $match['is_draw'] === 1) : ?>
                                Draw
                            <?php elseif ((int) $match['winner_id'] === (int) $_GET['user_id']) : ?>
                                <span style="color: var(--green); font-weight: 600;">Win</span>
                            <?php else : ?>
                                <span style="color: var(--red); font-weight: 600;">Loss</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $delta = (int) $match['delta']; ?>
                            <span style="<?php echo $delta > 0 ? 'color: var(--green);' : ($delta < 0 ? 'color: var(--red);' : ''); ?> font-weight: 600;">
                                <?php echo ($delta > 0 ? '+' : '') . $delta; ?>
                            </span>
                            (<?php echo (int) $match['elo_before']; ?> → <?php echo (int) $match['elo_after']; ?>)
                        </td>
                        <td><?php $dt = new DateTime($match['played_at']); echo $dt->format('M d, H:i'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <h2>Elo Evolution</h2>
    <?php if (empty($history)) : ?>
        <p>No history yet.</p>
    <?php else : ?>
        <?php // 折线图容器（宽度更大以容纳更多数据）。 ?>
        <div style="position: relative; height: 350px;">
            <canvas id="eloChart"></canvas>
        </div>
    <?php endif; ?>
</section>

<?php if (! empty($history)) : ?>
    <?php // 使用 Chart.js 渲染 Elo 变化曲线，优化了时间轴显示。 ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        // x 轴为时间，y 轴为 Elo 分数。
        const rawLabels = <?php echo json_encode(array_map(
            static fn($row) => $row['created_at'],
            $history
        )); ?>;
        const eloData = <?php echo json_encode(array_map(
            static fn($row) => (int) $row['elo_after'],
            $history
        )); ?>;

        // 将时间格式转换为更紧凑的格式（Month DD, HH:mm）
        const labels = rawLabels.map(dateStr => {
            const date = new Date(dateStr);
            const month = date.toLocaleString('en-US', { month: 'short' });
            const day = String(date.getDate()).padStart(2, '0');
            const hour = String(date.getHours()).padStart(2, '0');
            const minute = String(date.getMinutes()).padStart(2, '0');
            return `${month} ${day}\n${hour}:${minute}`;
        });

        // 初始化折线图。
        const ctx = document.getElementById('eloChart');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Elo rating',
                    data: eloData,
                    borderColor: '#1f4ea5',
                    backgroundColor: 'rgba(31, 78, 165, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#1f4ea5'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            padding: 15,
                            font: { size: 12 }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0,
                            maxTicksLimit: Math.max(5, Math.ceil(labels.length / 3)),
                            font: { size: 11 },
                            padding: 8
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: false,
                        ticks: {
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    </script>
<?php endif; ?>

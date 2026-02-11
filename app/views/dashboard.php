<?php // 提示信息区。 ?>
<?php if (! empty($message)) : ?>
    <div class="alert <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<section class="card">
    <h2>Create a Club</h2>
    <?php // 创建俱乐部表单：名称、运动与创建者。 ?>
    <form method="post" class="form-grid">
        <label>
            Club name
            <input type="text" name="club_name" required>
        </label>
        <label>
            Sport / game
            <input type="text" name="sport" required>
        </label>
        <label>
            Creator name
            <input type="text" name="creator_name" required>
        </label>
        <button type="submit">Create club</button>
    </form>
</section>

<section class="card">
    <h2>Clubs</h2>
    <?php if (empty($clubs)) : ?>
        <p>No clubs yet. Create the first one.</p>
    <?php else : ?>
        <?php // 俱乐部列表表格。 ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Sport</th>
                    <th>Creator</th>
                    <th>Members</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($clubs as $club) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($club['name']); ?></td>
                        <td><?php echo htmlspecialchars($club['sport']); ?></td>
                        <td><?php echo htmlspecialchars($club['creator_name'] ?? ''); ?></td>
                        <td><?php echo (int) $club['member_count']; ?></td>
                        <td><a class="link" href="club.php?club_id=<?php echo (int) $club['id']; ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

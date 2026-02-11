<?php
// 通用布局模板：头部导航 + 内容区域。
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1 class="site-title">LO07 Competition Tracker</h1>
            <?php // 顶部导航根据是否存在 club_id 显示更多入口。 ?>
            <nav class="site-nav">
                <a href="index.php">Dashboard</a>
                <?php if (! empty($navClubId)) : ?>
                    <a href="club.php?club_id=<?php echo (int) $navClubId; ?>">Club</a>
                    <a href="match_history.php?club_id=<?php echo (int) $navClubId; ?>">History</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="container">
        <?php echo $content; ?>
    </main>
    <script src="assets/app.js"></script>
</body>
</html>

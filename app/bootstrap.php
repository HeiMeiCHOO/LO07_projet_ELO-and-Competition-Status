<?php

// 启动配置与依赖装配。
$config = require __DIR__ . '/config/config.php';

// 加载数据库与领域层依赖。
require __DIR__ . '/config/db.php';
require __DIR__ . '/models/Repository.php';
require __DIR__ . '/services/EloService.php';
// 加载控制器。
require __DIR__ . '/controllers/DashboardController.php';
require __DIR__ . '/controllers/ClubController.php';
require __DIR__ . '/controllers/HistoryController.php';
require __DIR__ . '/controllers/MemberController.php';

// 创建数据库连接与核心服务。
$db = db_connect($config);
$repo = new Repository($db);
$eloService = new EloService($config['elo_k']);
$defaultElo = $config['default_elo'];

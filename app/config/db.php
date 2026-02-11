<?php

// 建立 SQLite 连接并确保表结构存在。
function db_connect(array $config): PDO
{
    $db = new PDO('sqlite:' . $config['db_path']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA foreign_keys = ON');

    ensure_schema($db);

    return $db;
}

// 初始化数据库表结构（幂等）。
function ensure_schema(PDO $db): void
{
    // 用户表：复用用户名作为成员标识。
    // 字段说明：
    // - id: 用户主键，自增。
    // - username: 用户名，唯一。
    // - created_at: 创建时间（ISO 8601）。
    $db->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL
        )'
    );

    // 俱乐部表：包含名称、运动类型与创建者。
    // 字段说明：
    // - id: 俱乐部主键，自增。
    // - name: 俱乐部名称。
    // - sport: 运动/游戏类型。
    // - created_by: 创建者用户 ID。
    // - created_at: 创建时间（ISO 8601）。
    $db->exec(
        'CREATE TABLE IF NOT EXISTS clubs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            sport TEXT NOT NULL,
            created_by INTEGER NOT NULL,
            created_at TEXT NOT NULL,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )'
    );

    // 成员关联表：保存俱乐部成员的当前 Elo 与统计。
    // 字段说明：
    // - id: 关联主键，自增。
    // - club_id: 俱乐部 ID。
    // - user_id: 成员用户 ID。
    // - current_elo: 当前 Elo 分数。
    // - matches_played: 已比赛场次。
    // - joined_at: 加入时间（ISO 8601）。
    $db->exec(
        'CREATE TABLE IF NOT EXISTS club_members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            club_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            current_elo INTEGER NOT NULL,
            matches_played INTEGER NOT NULL DEFAULT 0,
            joined_at TEXT NOT NULL,
            UNIQUE (club_id, user_id),
            FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    // 比赛表：记录双方、胜者/平局与时间。
    // 字段说明：
    // - id: 比赛主键，自增。
    // - club_id: 俱乐部 ID。
    // - player_a_id: 选手 A 用户 ID。
    // - player_b_id: 选手 B 用户 ID。
    // - winner_id: 胜者用户 ID（平局为 NULL）。
    // - is_draw: 是否平局（1/0）。
    // - played_at: 比赛时间（ISO 8601）。
    $db->exec(
        'CREATE TABLE IF NOT EXISTS matches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            club_id INTEGER NOT NULL,
            player_a_id INTEGER NOT NULL,
            player_b_id INTEGER NOT NULL,
            winner_id INTEGER,
            is_draw INTEGER NOT NULL DEFAULT 0,
            played_at TEXT NOT NULL,
            FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
            FOREIGN KEY (player_a_id) REFERENCES users(id),
            FOREIGN KEY (player_b_id) REFERENCES users(id),
            FOREIGN KEY (winner_id) REFERENCES users(id)
        )'
    );

    // Elo 历史表：记录每场比赛对每个成员的 Elo 变化。
    // 字段说明：
    // - id: 历史记录主键，自增。
    // - match_id: 比赛 ID。
    // - club_id: 俱乐部 ID。
    // - user_id: 成员用户 ID。
    // - elo_before: 比赛前 Elo。
    // - elo_after: 比赛后 Elo。
    // - delta: Elo 变化量。
    // - created_at: 记录时间（ISO 8601）。
    $db->exec(
        'CREATE TABLE IF NOT EXISTS elo_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            match_id INTEGER NOT NULL,
            club_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            elo_before INTEGER NOT NULL,
            elo_after INTEGER NOT NULL,
            delta INTEGER NOT NULL,
            created_at TEXT NOT NULL,
            FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
            FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );
}

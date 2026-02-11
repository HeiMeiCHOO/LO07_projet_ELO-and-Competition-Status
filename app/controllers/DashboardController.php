<?php

// 首页控制器：创建俱乐部与列表展示。
class DashboardController
{
    private Repository $repo;

    public function __construct(Repository $repo)
    {
        $this->repo = $repo;
    }

    // 处理创建俱乐部表单。
    public function createClub(array $input): array
    {
        // 读取并清理输入。
        $name = trim($input['club_name'] ?? '');
        $sport = trim($input['sport'] ?? '');
        $creator = trim($input['creator_name'] ?? '');

        // 基本校验：字段不能为空。
        if ($name === '' || $sport === '' || $creator === '') {
            return [
                'success' => false,
                'message' => 'Club name, sport, and creator are required.',
            ];
        }

        try {
            // 复用或创建创建者账号。
            $creatorId = $this->repo->getOrCreateUser($creator);
            // 创建俱乐部。
            $clubId = $this->repo->createClub($name, $sport, $creatorId);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to create club: ' . $e->getMessage(),
            ];
        }

        return [
            'success' => true,
            'message' => 'Club created.',
            'club_id' => $clubId,
        ];
    }

    // 获取首页展示数据。
    public function getDashboardData(): array
    {
        return [
            // 俱乐部列表用于表格展示。
            'clubs' => $this->repo->listClubs(),
        ];
    }
}

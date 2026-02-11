<?php

// 成员页面控制器：个人信息与 Elo 轨迹。
class MemberController
{
    private Repository $repo;

    public function __construct(Repository $repo)
    {
        $this->repo = $repo;
    }

    // 获取成员页面所需数据。
    public function getMemberData(int $clubId, int $userId): array
    {
        return [
            // 俱乐部信息、成员信息与 Elo 变化历史。
            'club' => $this->repo->getClub($clubId),
            'user' => $this->repo->getUserById($userId),
            'membership' => $this->repo->getMember($clubId, $userId),
            'history' => $this->repo->listEloHistory($clubId, $userId),
        ];
    }
}

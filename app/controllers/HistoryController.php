<?php

// 历史记录控制器：读取比赛列表与筛选。
class HistoryController
{
    private Repository $repo;

    public function __construct(Repository $repo)
    {
        $this->repo = $repo;
    }

    // 获取历史页面所需数据。
    public function getHistoryData(int $clubId, string $playerFilter, string $typeFilter = ''): array
    {
        return [
            // 俱乐部基本信息 + 过滤后的比赛列表。
            'club' => $this->repo->getClub($clubId),
            'matches' => $this->repo->listMatches($clubId, $playerFilter, $typeFilter),
            'filter' => $playerFilter,
            'filterType' => $typeFilter,
        ];
    }
}

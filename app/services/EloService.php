<?php

// Elo 评分算法服务。
class EloService
{
    private int $kFactor;

    public function __construct(int $kFactor)
    {
        $this->kFactor = $kFactor;
    }

    // 根据结果计算双方新评分与变动值。
    public function calculate(int $ratingA, int $ratingB, string $result): array
    {
        // 期望胜率（Elo 标准公式）。
        $expectedA = 1 / (1 + pow(10, ($ratingB - $ratingA) / 400));
        $expectedB = 1 - $expectedA;

        // 将比赛结果转换为得分。
        if ($result === 'A') {
            $scoreA = 1.0;
            $scoreB = 0.0;
        } elseif ($result === 'B') {
            $scoreA = 0.0;
            $scoreB = 1.0;
        } else {
            $scoreA = 0.5;
            $scoreB = 0.5;
        }

        // 按 K 系数计算新 Elo。
        $newA = (int) round($ratingA + $this->kFactor * ($scoreA - $expectedA));
        $newB = (int) round($ratingB + $this->kFactor * ($scoreB - $expectedB));

        return [
            'newA' => $newA,
            'newB' => $newB,
            'deltaA' => $newA - $ratingA,
            'deltaB' => $newB - $ratingB,
        ];
    }
}

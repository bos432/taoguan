<?php

namespace app\common\service\setting;

class AccordRuntimeService
{
    public static function memberSummary(int $memberId, array $accordUniques = []): array
    {
        if (empty($accordUniques)) {
            $accordUniques = [
                'user_agreement',
                'privacy_policy',
                'after_sales_policy',
                'disclaimer',
            ];
        }

        $accordList = AccordService::frontendResolveByUniques($accordUniques);
        $accordStatus = AccordAcceptService::statusMemberAccords($memberId, $accordUniques);

        $statusMap = [];
        $acceptedCount = 0;
        foreach ($accordStatus as $item) {
            $unique = '' . ($item['accord_unique'] ?? '');
            if ($unique === '') {
                continue;
            }
            $statusMap[$unique] = $item;
            if (intval($item['accepted'] ?? 0) === 1) {
                $acceptedCount += 1;
            }
        }

        return [
            'required_accord_uniques' => array_values($accordUniques),
            'list' => $accordList,
            'status_list' => $accordStatus,
            'status_map' => $statusMap,
            'accepted_count' => $acceptedCount,
            'pending_count' => max(count($accordUniques) - $acceptedCount, 0),
        ];
    }
}

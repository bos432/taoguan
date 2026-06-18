<?php

namespace app\common\service\system;

use app\common\model\member\MemberOrderDetailedModel;
use app\common\model\member\MemberOrderModel;
use app\common\model\merchant\MerchantModel;

class MerchantPurchaseLimitService
{
    private const DEFAULTS = [
        'enabled' => 1,
        'daily_quantity_limit' => 100,
        'daily_amount_limit' => 50000,
    ];

    private static function normalizeQuantityLimit($value): ?int
    {
        if ($value === '' || $value === null) {
            return null;
        }

        $limit = intval($value);
        return $limit > 0 ? $limit : null;
    }

    private static function normalizeAmountLimit($value): ?float
    {
        if ($value === '' || $value === null) {
            return null;
        }

        $limit = round(floatval($value), 2);
        return $limit > 0 ? $limit : null;
    }

    private static function normalizeConfig(array $data = []): array
    {
        $enabled = intval($data['enabled'] ?? self::DEFAULTS['enabled']) === 1 ? 1 : 0;
        $quantityLimit = self::normalizeQuantityLimit($data['daily_quantity_limit'] ?? self::DEFAULTS['daily_quantity_limit']);
        $amountLimit = self::normalizeAmountLimit($data['daily_amount_limit'] ?? self::DEFAULTS['daily_amount_limit']);

        return [
            'enabled' => $enabled,
            'daily_quantity_limit' => intval($quantityLimit ?? 0),
            'daily_amount_limit' => round(floatval($amountLimit ?? 0), 2),
        ];
    }

    private static function configDir(): string
    {
        return app()->getRootPath()
            . 'private'
            . DIRECTORY_SEPARATOR
            . 'system-config';
    }

    private static function configPath(): string
    {
        return self::configDir() . DIRECTORY_SEPARATOR . 'merchant_purchase_limit.json';
    }

    private static function legacyConfigPath(): string
    {
        return app()->getRootPath()
            . 'runtime'
            . DIRECTORY_SEPARATOR
            . 'config'
            . DIRECTORY_SEPARATOR
            . 'merchant_purchase_limit.json';
    }

    public static function info(): array
    {
        $path = self::readPath();
        if (!is_file($path)) {
            return self::normalizeConfig(self::DEFAULTS);
        }

        $raw = @file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return self::normalizeConfig(self::DEFAULTS);
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return self::normalizeConfig(self::DEFAULTS);
        }

        return self::normalizeConfig($data);
    }

    public static function edit(array $param = []): array
    {
        $data = self::normalizeConfig(array_merge(self::info(), $param));
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($encoded === false || !self::writeConfig($encoded)) {
            exception('商家购买限制配置保存失败');
        }

        return $data;
    }

    private static function configPaths(): array
    {
        return [
            self::configPath(),
            self::legacyConfigPath(),
        ];
    }

    private static function readPath(): string
    {
        $latestPath = '';
        $latestMtime = -1;

        foreach (self::configPaths() as $path) {
            if (is_file($path)) {
                $raw = @file_get_contents($path);
                if ($raw !== false && trim($raw) !== '') {
                    $mtime = @filemtime($path);
                    $mtime = $mtime === false ? 0 : intval($mtime);
                    if ($latestPath === '' || $mtime >= $latestMtime) {
                        $latestPath = $path;
                        $latestMtime = $mtime;
                    }
                }
            }
        }

        return $latestPath !== '' ? $latestPath : self::configPath();
    }

    private static function writeConfig(string $encoded): bool
    {
        $written = false;

        foreach (self::configPaths() as $path) {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if (@file_put_contents($path, $encoded) !== false) {
                $written = true;
            }
        }

        return $written;
    }

    private static function merchantConfigByMemberId(int $memberId): array
    {
        if ($memberId <= 0) {
            return [];
        }

        $merchant = MerchantModel::where('member_id', $memberId)
            ->where('is_disable', 0)
            ->where('is_delete', 0)
            ->field('id,title,auth_state,merchant_purchase_daily_quantity_limit,merchant_purchase_daily_amount_limit')
            ->find();
        if (!$merchant) {
            return [];
        }

        $merchant = $merchant->toArray();
        $global = self::info();

        $merchantQuantityLimit = self::normalizeQuantityLimit($merchant['merchant_purchase_daily_quantity_limit'] ?? null);
        $merchantAmountLimit = self::normalizeAmountLimit($merchant['merchant_purchase_daily_amount_limit'] ?? null);
        $globalQuantityLimit = self::normalizeQuantityLimit($global['daily_quantity_limit'] ?? 0);
        $globalAmountLimit = self::normalizeAmountLimit($global['daily_amount_limit'] ?? 0);

        $quantityLimit = $merchantQuantityLimit ?? ($global['enabled'] ? $globalQuantityLimit : null);
        $amountLimit = $merchantAmountLimit ?? ($global['enabled'] ? $globalAmountLimit : null);
        $enabled = ($merchantQuantityLimit !== null || $merchantAmountLimit !== null)
            || ($global['enabled'] && ($globalQuantityLimit !== null || $globalAmountLimit !== null));

        return [
            'merchant_id' => intval($merchant['id'] ?? 0),
            'merchant_title' => MerchantModel::formatDisplayTitle((string) ($merchant['title'] ?? '')),
            'auth_state' => intval($merchant['auth_state'] ?? 0),
            'daily_quantity_limit' => $quantityLimit,
            'daily_amount_limit' => $amountLimit,
            'enabled' => $enabled ? 1 : 0,
            'global_enabled' => intval($global['enabled'] ?? 0),
            'global_daily_quantity_limit' => intval($global['daily_quantity_limit'] ?? 0),
            'global_daily_amount_limit' => round(floatval($global['daily_amount_limit'] ?? 0), 2),
        ];
    }

    public static function isMerchantLimitedMember(int $memberId): bool
    {
        return !empty(self::merchantConfigByMemberId($memberId));
    }

    public static function todayStats(int $memberId): array
    {
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59');

        $orderIds = MemberOrderModel::where('member_id', $memberId)
            ->where('is_delete', 0)
            ->where('is_disable', 0)
            ->whereBetweenTime('create_time', $start, $end)
            ->column('id');

        $quantity = 0;
        if (!empty($orderIds)) {
            $quantity = intval(
                MemberOrderDetailedModel::whereIn('member_order_id', $orderIds)->sum('quantity')
            );
        }

        $amount = floatval(
            MemberOrderModel::where('member_id', $memberId)
                ->where('is_delete', 0)
                ->where('is_disable', 0)
                ->whereBetweenTime('create_time', $start, $end)
                ->sum('total_price')
        );

        return [
            'date' => date('Y-m-d'),
            'quantity' => $quantity,
            'amount' => round($amount, 2),
        ];
    }

    private static function buildMessage(array $runtime): string
    {
        $parts = [];

        if (intval($runtime['daily_quantity_limit']) > 0) {
            $parts[] = sprintf(
                '今日已购 %d 件，单日件数上限 %d 件',
                intval($runtime['today_quantity']),
                intval($runtime['daily_quantity_limit'])
            );
        }

        if (floatval($runtime['daily_amount_limit']) > 0) {
            $parts[] = sprintf(
                '今日已购金额 %.2f 元，单日金额上限 %.2f 元',
                floatval($runtime['today_amount']),
                floatval($runtime['daily_amount_limit'])
            );
        }

        return implode('；', $parts);
    }

    public static function buildRuntime(int $memberId, int $pendingQuantity = 0, float $pendingAmount = 0): array
    {
        $config = self::merchantConfigByMemberId($memberId);
        $stats = self::todayStats($memberId);

        $isMerchantMember = empty($config) ? 0 : 1;
        $isApprovedMerchant = intval($config['auth_state'] ?? 0) === 1 ? 1 : 0;
        $enabled = $isMerchantMember && $isApprovedMerchant && intval($config['enabled'] ?? 0) === 1 ? 1 : 0;

        $runtime = [
            'enabled' => $enabled,
            'is_merchant_member' => $isMerchantMember,
            'is_approved_merchant' => $isApprovedMerchant,
            'merchant_id' => intval($config['merchant_id'] ?? 0),
            'merchant_title' => MerchantModel::formatDisplayTitle((string) ($config['merchant_title'] ?? '')),
            'daily_quantity_limit' => intval($config['daily_quantity_limit'] ?? 0),
            'daily_amount_limit' => round(floatval($config['daily_amount_limit'] ?? 0), 2),
            'today_quantity' => intval($stats['quantity'] ?? 0),
            'today_amount' => round(floatval($stats['amount'] ?? 0), 2),
            'pending_quantity' => max(0, intval($pendingQuantity)),
            'pending_amount' => round(max(0, floatval($pendingAmount)), 2),
            'remaining_quantity' => 0,
            'remaining_amount' => 0,
            'message' => '',
        ];

        if (!$runtime['enabled']) {
            return $runtime;
        }

        if ($runtime['daily_quantity_limit'] > 0) {
            $runtime['remaining_quantity'] = max($runtime['daily_quantity_limit'] - $runtime['today_quantity'], 0);
        }

        if ($runtime['daily_amount_limit'] > 0) {
            $runtime['remaining_amount'] = max(round($runtime['daily_amount_limit'] - $runtime['today_amount'], 2), 0);
        }

        $runtime['message'] = self::buildMessage($runtime);

        return $runtime;
    }

    public static function assertWithinLimit(int $memberId, int $pendingQuantity = 0, float $pendingAmount = 0): array
    {
        $runtime = self::buildRuntime($memberId, $pendingQuantity, $pendingAmount);
        if (!$runtime['enabled']) {
            return $runtime;
        }

        if ($runtime['daily_quantity_limit'] > 0) {
            $nextQuantity = intval($runtime['today_quantity']) + intval($runtime['pending_quantity']);
            if ($nextQuantity > intval($runtime['daily_quantity_limit'])) {
                exception(sprintf(
                    '该商家账号今日最多购买 %d 件，当前已购 %d 件，本次提交 %d 件',
                    intval($runtime['daily_quantity_limit']),
                    intval($runtime['today_quantity']),
                    intval($runtime['pending_quantity'])
                ));
            }
        }

        if ($runtime['daily_amount_limit'] > 0) {
            $nextAmount = round(floatval($runtime['today_amount']) + floatval($runtime['pending_amount']), 2);
            if ($nextAmount > floatval($runtime['daily_amount_limit'])) {
                exception(sprintf(
                    '该商家账号今日最多购买 %.2f 元，当前已购 %.2f 元，本次提交 %.2f 元',
                    floatval($runtime['daily_amount_limit']),
                    floatval($runtime['today_amount']),
                    floatval($runtime['pending_amount'])
                ));
            }
        }

        return $runtime;
    }
}

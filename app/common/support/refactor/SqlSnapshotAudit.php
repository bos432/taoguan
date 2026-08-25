<?php

declare(strict_types=1);

namespace app\common\support\refactor;

use RuntimeException;

class SqlSnapshotAudit
{
    private const TABLES = [
        'ya_goods',
        'ya_member',
        'ya_member_order',
        'ya_member_order_detailed',
        'ya_member_order_log',
        'ya_merchant',
        'ya_merchant_purchase_ledger',
    ];

    public function audit(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException("SQL snapshot is not readable: {$path}");
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("SQL snapshot cannot be opened: {$path}");
        }

        $schemas = [];
        $rows = [];
        $currentTable = null;
        try {
            while (($line = fgets($handle)) !== false) {
                if (preg_match('/^CREATE TABLE `([^`]+)` \((.*)\) ENGINE=/s', $line, $inlineMatch)) {
                    $inlineTable = $inlineMatch[1];
                    if (in_array($inlineTable, self::TABLES, true)) {
                        $schemas[$inlineTable] = ['columns' => [], 'indexes' => []];
                        foreach ($this->splitSchemaDefinitions($inlineMatch[2]) as $definition) {
                            $this->captureSchemaLine($schemas[$inlineTable], '  ' . $definition);
                        }
                    }
                    continue;
                }
                if (preg_match('/^CREATE TABLE `([^`]+)` \(/', $line, $match)) {
                    $currentTable = in_array($match[1], self::TABLES, true) ? $match[1] : null;
                    if ($currentTable !== null) {
                        $schemas[$currentTable] = ['columns' => [], 'indexes' => []];
                    }
                    continue;
                }

                if ($currentTable !== null) {
                    if (preg_match('/^\) ENGINE=/', $line)) {
                        $currentTable = null;
                        continue;
                    }
                    $this->captureSchemaLine($schemas[$currentTable], $line);
                    continue;
                }

                if (!preg_match('/^INSERT INTO `([^`]+)` VALUES (.*);\s*$/s', $line, $match)) {
                    continue;
                }
                $table = $match[1];
                if (!isset($schemas[$table]) || !in_array($table, self::TABLES, true)) {
                    continue;
                }
                $columns = array_column($schemas[$table]['columns'], 'name');
                foreach ($this->parseTuples($match[2]) as $values) {
                    if (count($columns) !== count($values)) {
                        throw new RuntimeException("Column count mismatch in {$table}: expected " . count($columns) . ', got ' . count($values));
                    }
                    $rows[$table][] = array_combine($columns, $values);
                }
            }
        } finally {
            fclose($handle);
        }

        foreach (self::TABLES as $table) {
            $schemas[$table] ??= ['columns' => [], 'indexes' => []];
            $rows[$table] ??= [];
        }

        return [
            'format_version' => 1,
            'snapshot' => [
                'file' => basename($path),
                'bytes' => filesize($path),
                'sha256' => hash_file('sha256', $path),
                'modified_at' => date('c', (int) filemtime($path)),
            ],
            'schema' => $schemas,
            'diagnostics' => $this->diagnostics($rows, $schemas),
        ];
    }

    private function captureSchemaLine(array &$schema, string $line): void
    {
        if (preg_match('/^\s+`([^`]+)`\s+([^\s,]+)(.*),?\s*$/', $line, $match)) {
            $tail = $match[3];
            preg_match('/DEFAULT\s+((?:\'[^\']*\')|NULL|[^\s,]+)/i', $tail, $default);
            preg_match('/COMMENT\s+\'([^\']*)\'/u', $tail, $comment);
            $schema['columns'][] = [
                'name' => $match[1],
                'type' => strtolower($match[2]),
                'nullable' => stripos($tail, 'NOT NULL') === false,
                'default' => $default[1] ?? null,
                'comment' => $comment[1] ?? '',
            ];
            return;
        }

        if (preg_match('/^\s+(PRIMARY KEY|UNIQUE KEY|KEY)(?:\s+`([^`]+)`)?\s*\(([^)]+)\)/', $line, $match)) {
            preg_match_all('/`([^`]+)`/', $match[3], $columns);
            $schema['indexes'][] = [
                'name' => $match[1] === 'PRIMARY KEY' ? 'PRIMARY' : ($match[2] ?? ''),
                'unique' => $match[1] !== 'KEY',
                'columns' => $columns[1] ?? [],
            ];
        }
    }

    private function splitSchemaDefinitions(string $definitions): array
    {
        $parts = [];
        $part = '';
        $depth = 0;
        $inString = false;
        $escaped = false;
        foreach (str_split($definitions) as $character) {
            if ($inString) {
                $part .= $character;
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === "'") {
                    $inString = false;
                }
                continue;
            }
            if ($character === "'") {
                $inString = true;
            } elseif ($character === '(') {
                $depth++;
            } elseif ($character === ')') {
                $depth--;
            } elseif ($character === ',' && $depth === 0) {
                $parts[] = trim($part);
                $part = '';
                continue;
            }
            $part .= $character;
        }
        if (trim($part) !== '') {
            $parts[] = trim($part);
        }
        return $parts;
    }

    private function parseTuples(string $sql): iterable
    {
        $length = strlen($sql);
        $row = [];
        $field = '';
        $inString = false;
        $escaped = false;
        $inTuple = false;

        for ($index = 0; $index < $length; $index++) {
            $character = $sql[$index];
            if (!$inTuple) {
                if ($character === '(') {
                    $inTuple = true;
                    $row = [];
                    $field = '';
                }
                continue;
            }

            if ($inString) {
                $field .= $character;
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === "'") {
                    $inString = false;
                }
                continue;
            }

            if ($character === "'") {
                $inString = true;
                $field .= $character;
            } elseif ($character === ',') {
                $row[] = $this->normalizeValue($field);
                $field = '';
            } elseif ($character === ')') {
                $row[] = $this->normalizeValue($field);
                yield $row;
                $inTuple = false;
                $field = '';
            } else {
                $field .= $character;
            }
        }
    }

    private function normalizeValue(string $value): mixed
    {
        $value = trim($value);
        if (strcasecmp($value, 'NULL') === 0) {
            return null;
        }
        if (strlen($value) >= 2 && $value[0] === "'" && $value[strlen($value) - 1] === "'") {
            return strtr(substr($value, 1, -1), [
                '\\\\' => '\\',
                "\\'" => "'",
                '\\n' => "\n",
                '\\r' => "\r",
                '\\t' => "\t",
                '\\0' => "\0",
            ]);
        }
        return $value;
    }

    private function diagnostics(array $rows, array $schemas): array
    {
        $orders = $rows['ya_member_order'];
        $details = $rows['ya_member_order_detailed'];
        $logs = $rows['ya_member_order_log'];
        $merchants = $rows['ya_merchant'];
        $members = $rows['ya_member'];
        $goods = $rows['ya_goods'];
        $ledger = $rows['ya_merchant_purchase_ledger'];

        $orderIds = $this->idSet($orders, 'id');
        $detailIds = $this->idSet($details, 'id');
        $memberIds = $this->idSet($members, 'member_id');
        $merchantIds = $this->idSet($merchants, 'id');

        return [
            'table_counts' => array_map(static fn(array $tableRows): int => count($tableRows), $rows),
            'orders' => [
                'active_count' => $this->countWhere($orders, 'is_delete', '0'),
                'paid_count' => $this->countWhere($orders, 'pay_status', '1', true),
                'status_distribution' => $this->distribution($orders, 'status', true),
                'pay_status_distribution' => $this->distribution($orders, 'pay_status', true),
                'refund_status_distribution' => $this->distribution($orders, 'refund_status', true),
                'active_pay_price' => $this->moneySum($orders, 'pay_price', true),
                'paid_pay_price' => $this->moneySum($orders, 'pay_price', true, ['pay_status' => '1']),
                'duplicate_order_numbers' => $this->duplicateCount($orders, 'order_no', true),
            ],
            'order_details' => [
                'quantity' => $this->integerSum($details, 'quantity'),
                'total' => $this->moneySum($details, 'total'),
                'missing_order_count' => $this->missingReferenceCount($details, 'member_order_id', $orderIds),
            ],
            'order_logs' => [
                'role_distribution' => $this->distribution($logs, 'role_type', true),
                'missing_order_count' => $this->missingReferenceCount($logs, 'member_order_id', $orderIds, true),
            ],
            'merchants' => [
                'active_count' => $this->countWhere($merchants, 'is_delete', '0'),
                'auth_distribution' => $this->distribution($merchants, 'auth_state', true),
                'bound_member_count' => count(array_filter($merchants, static fn(array $row): bool => intval($row['member_id'] ?? 0) > 0 && ($row['is_delete'] ?? '0') === '0')),
                'super_count' => $this->countWhere($merchants, 'member_is_super', '1', true),
                'super_without_member_count' => count(array_filter($merchants, static fn(array $row): bool => ($row['is_delete'] ?? '0') === '0' && ($row['member_is_super'] ?? '0') === '1' && intval($row['member_id'] ?? 0) <= 0)),
                'missing_member_binding_count' => $this->missingReferenceCount($merchants, 'member_id', $memberIds, true),
            ],
            'goods' => [
                'active_count' => $this->countWhere($goods, 'is_delete', '0'),
                'status_distribution' => $this->distribution($goods, 'status', true),
                'stock' => $this->integerSum($goods, 'stock', true),
                'missing_merchant_count' => $this->missingReferenceCount($goods, 'merchant_id', $merchantIds, true),
            ],
            'members' => [
                'active_count' => $this->countWhere($members, 'is_delete', '0'),
                'disabled_count' => $this->countWhere($members, 'is_disable', '1', true),
                'super_count' => $this->countWhere($members, 'is_super', '1', true),
            ],
            'purchase_ledger' => [
                'active_count' => $this->countWhere($ledger, 'is_delete', '0'),
                'quantity' => $this->integerSum($ledger, 'quantity', true),
                'total' => $this->moneySum($ledger, 'total', true),
                'source_type_distribution' => $this->distribution($ledger, 'source_type', true),
                'buyer_merchant_count' => count(array_unique(array_filter(array_column($ledger, 'buyer_merchant_id'), static fn(mixed $id): bool => intval($id) > 0))),
                'duplicate_order_detail_count' => $this->duplicateCount($ledger, 'member_order_detailed_id', true),
                'missing_order_count' => $this->missingReferenceCount($ledger, 'member_order_id', $orderIds, true),
                'missing_detail_count' => $this->missingReferenceCount($ledger, 'member_order_detailed_id', $detailIds, true),
            ],
            'index_findings' => $this->indexFindings($schemas),
        ];
    }

    private function countWhere(array $rows, string $field, string $value, bool $activeOnly = false): int
    {
        return count(array_filter($rows, static fn(array $row): bool => (!$activeOnly || ($row['is_delete'] ?? '0') === '0') && (string) ($row[$field] ?? '') === $value));
    }

    private function distribution(array $rows, string $field, bool $activeOnly = false): array
    {
        $result = [];
        foreach ($rows as $row) {
            if ($activeOnly && ($row['is_delete'] ?? '0') !== '0') {
                continue;
            }
            $key = (string) ($row[$field] ?? 'NULL');
            $result[$key] = ($result[$key] ?? 0) + 1;
        }
        ksort($result, SORT_NATURAL);
        $distribution = [];
        foreach ($result as $value => $count) {
            $distribution[] = ['value' => (string) $value, 'count' => $count];
        }
        return $distribution;
    }

    private function moneySum(array $rows, string $field, bool $activeOnly = false, array $where = []): string
    {
        $cents = 0;
        foreach ($rows as $row) {
            if ($activeOnly && ($row['is_delete'] ?? '0') !== '0') {
                continue;
            }
            foreach ($where as $whereField => $whereValue) {
                if ((string) ($row[$whereField] ?? '') !== $whereValue) {
                    continue 2;
                }
            }
            $cents += $this->toCents((string) ($row[$field] ?? '0'));
        }
        return sprintf('%d.%02d', intdiv($cents, 100), abs($cents % 100));
    }

    private function toCents(string $value): int
    {
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '+-');
        [$whole, $decimal] = array_pad(explode('.', $value, 2), 2, '');
        $cents = intval($whole) * 100 + intval(str_pad(substr($decimal, 0, 2), 2, '0'));
        return $negative ? -$cents : $cents;
    }

    private function integerSum(array $rows, string $field, bool $activeOnly = false): int
    {
        $sum = 0;
        foreach ($rows as $row) {
            if (!$activeOnly || ($row['is_delete'] ?? '0') === '0') {
                $sum += intval($row[$field] ?? 0);
            }
        }
        return $sum;
    }

    private function idSet(array $rows, string $field): array
    {
        return array_fill_keys(array_map('strval', array_column($rows, $field)), true);
    }

    private function missingReferenceCount(array $rows, string $field, array $targets, bool $activeOnly = false): int
    {
        $count = 0;
        foreach ($rows as $row) {
            if ($activeOnly && ($row['is_delete'] ?? '0') !== '0') {
                continue;
            }
            $value = (string) ($row[$field] ?? '');
            if ($value !== '' && $value !== '0' && !isset($targets[$value])) {
                $count++;
            }
        }
        return $count;
    }

    private function duplicateCount(array $rows, string $field, bool $activeOnly = false): int
    {
        $counts = [];
        foreach ($rows as $row) {
            if ($activeOnly && ($row['is_delete'] ?? '0') !== '0') {
                continue;
            }
            $value = (string) ($row[$field] ?? '');
            if ($value !== '') {
                $counts[$value] = ($counts[$value] ?? 0) + 1;
            }
        }
        return count(array_filter($counts, static fn(int $count): bool => $count > 1));
    }

    private function indexFindings(array $schemas): array
    {
        $expected = [
            'ya_member_order' => [['order_no'], ['merchant_id', 'pay_time'], ['member_id', 'create_time']],
            'ya_member_order_detailed' => [['member_order_id'], ['goods_id']],
            'ya_member_order_log' => [['member_order_id', 'create_time']],
            'ya_merchant_purchase_ledger' => [['member_order_detailed_id'], ['buyer_merchant_id', 'pay_time'], ['source_merchant_id', 'pay_time']],
        ];
        $findings = [];
        foreach ($expected as $table => $requirements) {
            $indexes = array_column($schemas[$table]['indexes'] ?? [], 'columns');
            foreach ($requirements as $columns) {
                $covered = count(array_filter($indexes, static fn(array $indexColumns): bool => array_slice($indexColumns, 0, count($columns)) === $columns)) > 0;
                $findings[] = ['table' => $table, 'columns' => $columns, 'covered' => $covered];
            }
        }
        return $findings;
    }
}

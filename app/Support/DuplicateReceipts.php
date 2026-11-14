<?php

namespace App\Support;

use App\Models\ExpenseItem;

/**
 * Comprobantes repetidos por (tipo, proveedor, número), dentro de la rendición y contra
 * rendiciones vigentes (se ignoran las rechazadas y eliminadas, que pueden rehacerse).
 */
class DuplicateReceipts
{
    /**
     * @param  array<int|string, array{document_type?: mixed, vendor_name?: mixed, receipt_number?: mixed}>  $items
     * @return array<int|string, list<string>> mensajes por índice de ítem
     */
    public static function find(array $items, ?int $excludeExpenseId = null): array
    {
        $errors = [];
        $seen = [];
        $comboToIndices = [];
        foreach ($items as $idx => $item) {
            $number = trim((string) ($item['receipt_number'] ?? ''));
            if ($number === '') {
                continue;
            }
            $key = self::key((string) ($item['document_type'] ?? ''), (string) ($item['vendor_name'] ?? ''), $number);
            $comboToIndices[$key][] = $idx;
            if (isset($seen[$key])) {
                $errors[$idx] = ['Documento duplicado dentro de la rendición (mismo tipo, proveedor y número).'];
                $errors[$seen[$key]] ??= ['Documento duplicado dentro de la rendición.'];
            } else {
                $seen[$key] = $idx;
            }
        }

        if (empty($comboToIndices)) {
            return $errors;
        }

        $existing = ExpenseItem::query()
            ->select(['document_type', 'vendor_name', 'document_number', 'expense_id'])
            ->whereNotNull('document_number')
            ->whereHas('expense', fn ($q) => $q->where('is_enabled', true)->where('status', '!=', 'rejected'))
            ->when($excludeExpenseId !== null, fn ($q) => $q->where('expense_id', '!=', $excludeExpenseId))
            ->where(function ($q) use ($comboToIndices) {
                foreach (array_keys($comboToIndices) as $key) {
                    [$type, $vendorNorm, $numberNorm] = explode('|', $key);
                    $q->orWhere(fn ($qq) => $qq->where('document_type', $type)
                        ->whereRaw('LOWER(TRIM(vendor_name)) = ?', [$vendorNorm])
                        ->whereRaw('LOWER(TRIM(document_number)) = ?', [$numberNorm]));
                }
            })
            ->with('expense:id,expense_number')
            ->limit(50)
            ->get();

        foreach ($existing as $row) {
            foreach ($comboToIndices[self::key($row->document_type, $row->vendor_name, $row->document_number)] ?? [] as $idx) {
                $errors[$idx][] = 'Documento ya registrado en otra rendición' . ($row->expense ? " ({$row->expense->expense_number})" : '') . ' (mismo tipo, proveedor y número).';
            }
        }

        return $errors;
    }

    private static function key(string $type, string $vendor, string $number): string
    {
        $normVendor = mb_strtolower(trim(preg_replace('/\s+/', ' ', $vendor)), 'UTF-8');
        $normNumber = mb_strtolower(trim($number), 'UTF-8');

        return $type . '|' . $normVendor . '|' . $normNumber;
    }
}

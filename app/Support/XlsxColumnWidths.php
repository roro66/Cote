<?php

namespace App\Support;

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\Entity\Sheet;

/**
 * Calcula el ancho de cada columna según el texto más largo escrito en ella.
 */
final class XlsxColumnWidths
{
    public const MIN_WIDTH = 8;
    public const MAX_WIDTH = 100;

    /** @var array<int,float> índice de columna (1..n) => largo en caracteres */
    private array $lengths = [];

    /**
     * @param array<int,mixed> $values
     * @param array<int,Style> $cellStyles estilos por columna (índice desde 0), p. ej. formato de monto
     */
    public function track(array $values, ?Style $style = null, array $cellStyles = []): void
    {
        $scale = 1.0;
        if ($style !== null) {
            $scale = max(1, $style->fontSize) / 11;
            if ($style->fontBold) {
                $scale *= 1.1;
            }
        }

        foreach (array_values($values) as $i => $value) {
            $format = ($cellStyles[$i] ?? null)?->format ?? $style?->format;
            $length = self::displayLength($value, $format) * $scale;
            $column = $i + 1;
            if ($length > ($this->lengths[$column] ?? 0)) {
                $this->lengths[$column] = $length;
            }
        }
    }

    public function applyTo(Sheet $sheet): void
    {
        foreach ($this->lengths as $column => $length) {
            $sheet->setColumnWidth(self::widthFor($length), $column);
        }
    }

    /**
     * @return array<int,float>
     */
    public function widths(): array
    {
        return array_map(fn (float $length) => self::widthFor($length), $this->lengths);
    }

    public static function widthFor(float $length): float
    {
        return (float) min(self::MAX_WIDTH, max(self::MIN_WIDTH, ceil($length * 1.1) + 2));
    }

    public static function displayLength(mixed $value, ?string $format = null): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if ($value instanceof \DateTimeInterface) {
            // Con formato tipo dd/mm/yyyy hh:mm el texto mide lo mismo que el formato
            return $format ? mb_strlen(str_replace(['\\', '"'], '', explode(';', $format)[0])) : 10;
        }
        if (is_int($value) || is_float($value)) {
            // Excel los muestra con separador de miles (y el signo $ si el formato lo trae)
            $decimals = $format !== null
                ? (str_contains($format, '.00') ? 2 : 0)
                : (is_float($value) && floor($value) != $value ? 2 : 0);
            $value = number_format((float) $value, $decimals, ',', '.') . ($format && str_contains($format, '$') ? '$' : '');
        }

        $lines = preg_split('/\R/u', (string) $value) ?: [''];

        return max(array_map('mb_strlen', $lines));
    }
}

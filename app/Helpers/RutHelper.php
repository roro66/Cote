<?php

namespace App\Helpers;

class RutHelper
{
    /**
     * Calcula el dígito verificador de un RUT
     */
    public static function calculateCheckDigit($rut)
    {
        $rut = (string) $rut;
        $sum = 0;
        $multiplier = 2;
        
        for ($i = strlen($rut) - 1; $i >= 0; $i--) {
            $sum += intval($rut[$i]) * $multiplier;
            $multiplier = $multiplier == 7 ? 2 : $multiplier + 1;
        }
        
        $remainder = $sum % 11;
        $checkDigit = 11 - $remainder;
        
        if ($checkDigit == 11) {
            return '0';
        } elseif ($checkDigit == 10) {
            return 'K';
        } else {
            return (string) $checkDigit;
        }
    }
    
    /**
     * Genera un RUT completo con dígito verificador
     */
    public static function generate($number)
    {
        $checkDigit = self::calculateCheckDigit($number);
        return $number . '-' . $checkDigit;
    }
    
    /**
     * Valida si un RUT es válido
     */
    public static function validate($rut)
    {
        // Limpiar el RUT removiendo puntos y espacios, manteniendo solo números, K y guión
        $rut = self::clean(trim($rut));
        $rut = strtoupper($rut);
        
        if (!preg_match('/^[0-9]{1,8}-[0-9K]$/', $rut)) {
            return false;
        }
        
        $parts = explode('-', $rut);
        $number = $parts[0];
        $checkDigit = $parts[1];
        
        return self::calculateCheckDigit($number) === $checkDigit;
    }
    
    /**
     * Formatea un RUT con puntos y guión
     */
    public static function format($rut)
    {
        $rut = preg_replace('/[^0-9K]/i', '', $rut);
        
        if (strlen($rut) < 2) {
            return $rut;
        }
        
        $checkDigit = substr($rut, -1);
        $number = substr($rut, 0, -1);
        
        return number_format($number, 0, '', '.') . '-' . $checkDigit;
    }
    
    /**
     * Limpia un RUT removiendo formato
     */
    public static function clean($rut)
    {
        return preg_replace('/[^0-9K-]/i', '', strtoupper($rut));
    }
    
    /**
     * Genera RUTs de ejemplo válidos para testing
     */
    public static function generateSampleRuts()
    {
        return [
            self::generate(12345678), // 12345678-5
            self::generate(87654321), // 87654321-4
            self::generate(11111111), // 11111111-1
            self::generate(22222222), // 22222222-2
            self::generate(33333333), // 33333333-3
            self::generate(44444444), // 44444444-4
            self::generate(55555555), // 55555555-5
            self::generate(66666666), // 66666666-6
            self::generate(77777777), // 77777777-7
            self::generate(88888888), // 88888888-8
        ];
    }
}

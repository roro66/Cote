<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeLegacyTransactions extends Command
{
    protected $signature = 'cote:normalize-legacy-transactions {--dry-run : Muestra lo que haría sin aplicar cambios}';

    protected $description = 'Convierte transacciones legacy (payment/adjustment) a transfer, asegurando dirección Tesorería<->Persona';

    public function handle(): int
    {
        $dry = $this->option('dry-run');
        $this->info(($dry ? '[DRY-RUN] ' : '') . 'Normalizando transacciones legacy...');

        $legacy = Transaction::whereIn('type', ['payment', 'adjustment'])->get();
        if ($legacy->isEmpty()) {
            $this->info('No se encontraron transacciones legacy.');
            return self::SUCCESS;
        }

        $count = 0;
        $skipped = 0;

        DB::transaction(function () use ($legacy, $dry, &$count, &$skipped) {
            foreach ($legacy as $tx) {
                $from = Account::find($tx->from_account_id);
                $to = Account::find($tx->to_account_id);

                if (!$from || !$to) {
                    $this->warn("TX {$tx->id} sin cuentas válidas, saltando.");
                    $skipped++;
                    continue;
                }

                // Determinar dirección correcta Tesorería<->Persona
                $newFrom = $from;
                $newTo = $to;

                if ($tx->type === 'payment') {
                    // Intención: egreso desde persona hacia Tesorería
                    if (!($from->type === 'person' && $to->type === 'treasury')) {
                        if ($from->type === 'treasury' && $to->type === 'person') {
                            // Invertir
                            $newFrom = $to;
                            $newTo = $from;
                        } elseif ($from->type === 'person') {
                            // Forzar destino Tesorería
                            $treasury = Account::where('type', 'treasury')->first();
                            if ($treasury) {
                                $newTo = $treasury;
                            }
                        } elseif ($to->type === 'person') {
                            $treasury = Account::where('type', 'treasury')->first();
                            if ($treasury) {
                                $newFrom = $to;
                                $newTo = $treasury;
                            }
                        } else {
                            $this->warn("TX {$tx->id} payment no mapeable (tipos: {$from->type} -> {$to->type}), saltando.");
                            $skipped++;
                            continue;
                        }
                    }
                } elseif ($tx->type === 'adjustment') {
                    // Ajuste: si el ajuste fue positivo hacia persona, será Tesorería->Persona; si no, Persona->Tesorería
                    // Sin info de signo aparte del monto y dirección original, asumimos dirección actual como intención y la corregimos a par válido
                    if ($from->type === 'treasury' && $to->type === 'person') {
                        // OK ya cuadra con regla
                    } elseif ($from->type === 'person' && $to->type === 'treasury') {
                        // OK
                    } elseif ($from->type === 'treasury' && $to->type !== 'person') {
                        $candidate = Account::where('type', 'person')->first();
                        if ($candidate) {
                            $newTo = $candidate;
                        }
                    } elseif ($from->type !== 'person' && $to->type === 'treasury') {
                        $candidate = Account::where('type', 'person')->first();
                        if ($candidate) {
                            $newFrom = $candidate;
                        }
                    } elseif ($from->type === 'person' && $to->type !== 'treasury') {
                        $treasury = Account::where('type', 'treasury')->first();
                        if ($treasury) {
                            $newTo = $treasury;
                        }
                    } elseif ($from->type !== 'treasury' && $to->type === 'person') {
                        $treasury = Account::where('type', 'treasury')->first();
                        if ($treasury) {
                            $newFrom = $treasury;
                        }
                    } else {
                        $this->warn("TX {$tx->id} adjustment no mapeable (tipos: {$from->type} -> {$to->type}), saltando.");
                        $skipped++;
                        continue;
                    }
                }

                $payload = [
                    'type' => 'transfer',
                    'from_account_id' => $newFrom->id,
                    'to_account_id' => $newTo->id,
                ];

                $this->line(sprintf('TX %d (%s) -> transfer %s(%d) -> %s(%d)', $tx->id, $tx->type, $newFrom->type, $newFrom->id, $newTo->type, $newTo->id));

                if (!$dry) {
                    $tx->update($payload);
                }
                $count++;
            }
        });

        $this->info("Convertidas: {$count}, Saltadas: {$skipped}" . ($dry ? ' [DRY-RUN]' : ''));
        return self::SUCCESS;
    }
}

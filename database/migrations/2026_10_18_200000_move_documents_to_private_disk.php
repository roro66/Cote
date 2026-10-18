<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $this->move('public', 'local');
    }

    public function down(): void
    {
        $this->move('local', 'public');
    }

    private function move(string $from, string $to): void
    {
        $source = Storage::disk($from);
        $target = Storage::disk($to);

        DB::table('documents')->orderBy('id')->pluck('file_path')->each(function (?string $path) use ($source, $target) {
            if (! $path || $target->exists($path) || ! $source->exists($path)) {
                return;
            }

            $target->writeStream($path, $source->readStream($path));
            $source->delete($path);
        });
    }
};

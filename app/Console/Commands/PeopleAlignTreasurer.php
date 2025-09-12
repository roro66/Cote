<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Person;
use Illuminate\Support\Facades\Storage;

class PeopleAlignTreasurer extends Command
{
    protected $signature = 'people:align-treasurer {--apply : Apply the changes}';

    protected $description = 'Align people.role_type so only the person related to the User with role treasurer remains tesorero (dry-run by default)';

    public function handle()
    {
        $apply = $this->option('apply');

        $this->info('Finding current Spatie users with role treasurer...');
        $treasurerUser = User::role('treasurer')->first();

        $peopleTesorero = Person::where('role_type', 'tesorero')->get();

        $this->line('People currently marked as tesorero: ' . $peopleTesorero->count());
        $this->line('Users with Spatie role treasurer: ' . (User::role('treasurer')->count()));

        if ($treasurerUser) {
            $this->line('Current treasurer user id: ' . $treasurerUser->id . ' (person_id: ' . ($treasurerUser->person_id ?? 'NULL') . ')');
        } else {
            $this->line('No user currently holds the Spatie role "treasurer".');
        }

        $toChange = [];

        foreach ($peopleTesorero as $p) {
            $shouldBe = 'trabajador';
            if ($treasurerUser && $treasurerUser->person_id && $treasurerUser->person_id == $p->id) {
                $shouldBe = 'tesorero';
            }
            if ($p->role_type !== $shouldBe) {
                $toChange[] = [
                    'person_id' => $p->id,
                    'name' => $p->full_name,
                    'old' => $p->role_type,
                    'new' => $shouldBe,
                ];
            }
        }

        if (empty($toChange)) {
            $this->info('No changes required.');
            return 0;
        }

        $this->table(['person_id', 'name', 'old', 'new'], $toChange);

        if (!$apply) {
            $this->info('Dry-run complete. Run with --apply to perform the updates.');
            return 0;
        }

        // Backup CSV
        $csv = "person_id,name,old,new\n";
        foreach ($toChange as $row) {
            $csv .= implode(',', [
                $row['person_id'],
                '"' . str_replace('"', '""', $row['name']) . '"',
                $row['old'],
                $row['new']
            ]) . "\n";
        }

        $filename = 'people_align_treasurer_backup_' . date('Ymd_His') . '.csv';
        Storage::disk('local')->put($filename, $csv);
        $this->info('Backup written to storage/' . $filename);

        // Apply changes
        foreach ($toChange as $row) {
            $person = Person::find($row['person_id']);
            if ($person) {
                $person->role_type = $row['new'];
                $person->save();
            }
        }

        $this->info('Applied changes to ' . count($toChange) . ' people.');

        return 0;
    }
}

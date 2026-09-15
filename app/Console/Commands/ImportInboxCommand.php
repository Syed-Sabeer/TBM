<?php

namespace App\Console\Commands;

use App\Enums\ImportType;
use App\Models\User;
use App\Services\Imports\StockImportService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Picks up the overnight sheet.
 *
 * It stages and previews, and stops. Applying stays a human decision in the
 * back office, because a mill occasionally sends a truncated file and an
 * unattended apply would publish an empty warehouse to the storefront.
 */
class ImportInboxCommand extends Command
{
    protected $signature = 'tbm:import-inbox {--apply : also apply runs where every row matched}';

    protected $description = 'Stage and preview any mill sheets waiting in the import inbox';

    public function handle(StockImportService $imports): int
    {
        $files = collect(Storage::files('imports/inbox'))
            ->filter(fn (string $path) => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['csv', 'tsv', 'txt'], true));

        if ($files->isEmpty()) {
            $this->info('Nothing waiting in the inbox.');

            return self::SUCCESS;
        }

        $robot = User::staff()->whereHas('roles', fn ($q) => $q->where('name', 'inventory'))->first()
            ?? User::staff()->first();

        if (! $robot) {
            $this->error('No staff account to attribute the run to.');

            return self::FAILURE;
        }

        foreach ($files as $path) {
            $this->line("Staging {$path}");

            $import = $imports->stage(
                new UploadedFile(Storage::path($path), basename($path), null, null, true),
                ImportType::Stock,
                $robot
            );

            $import->forceFill(['was_scheduled' => true])->save();

            $imports->preview($import, $imports->suggestMapping($import));
            $import->refresh();

            $this->info(sprintf('%s — %s', $import->reference, $import->summaryLine()));

            if ($this->option('apply') && $import->rowsNeedingAttention() === 0) {
                $imports->apply($import, $robot);
                $this->info("{$import->reference} applied.");
            }

            Storage::move($path, 'imports/processed/'.basename($path));
        }

        return self::SUCCESS;
    }
}

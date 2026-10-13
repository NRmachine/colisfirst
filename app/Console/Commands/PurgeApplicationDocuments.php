<?php

namespace App\Console\Commands;

use App\Models\DriverApplication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeApplicationDocuments extends Command
{
    protected $signature = 'privacy:purge-documents {--dry-run}';

    protected $description = 'Supprime les justificatifs arrivés au terme de leur durée de conservation';

    public function handle(): int
    {
        $cutoff = now()->subDays(config('legal.documents_days'));
        $count = 0;
        foreach (DriverApplication::where(function ($query) use ($cutoff) {
            $query->where('reviewed_at', '<=', $cutoff)->orWhere(function ($pending) use ($cutoff) {
                $pending->whereNull('reviewed_at')->where('created_at', '<=', $cutoff);
            });
        })->cursor() as $application) {
            if (! $application->documents) {
                continue;
            }
            $count++;
            if ($this->option('dry-run')) {
                continue;
            }
            $remaining = $application->documents;
            foreach ($remaining as $kind => $path) {
                // Conserver les fichiers partagés et ceux des autres utilisateurs.
                if (str_starts_with($path, 'applications/'.$application->user_id.'/') && ! str_contains($path, '..')) {
                    if (Storage::disk(config('filesystems.private_documents'))->exists($path) && ! Storage::disk(config('filesystems.private_documents'))->delete($path)) {
                        continue;
                    }
                    unset($remaining[$kind]);
                }
            }
            $application->update(['documents' => $remaining]);
            if ($application->status === 'pending' && ! $remaining) {
                $application->update(['status' => 'rejected', 'review_note' => 'Dossier expiré après le délai de conservation. Vous pouvez déposer une nouvelle candidature.', 'reviewed_at' => now()]);
            }
        }
        $this->info($count.' dossier(s) concerné(s).');

        return self::SUCCESS;
    }
}

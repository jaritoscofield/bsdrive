<?php

namespace App\Console\Commands;

use App\Models\CompanyFolder;
use App\Models\Company;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class CleanupMissingFolders extends Command
{
    protected $signature = 'cleanup:missing-folders {--company-id=} {--dry-run} {--interactive}';
    protected $description = 'Clean up missing folders from CompanyFolder table that don\'t exist in Google Drive';

    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $companyId = $this->option('company-id');
        $dryRun = $this->option('dry-run');
        $interactive = $this->option('interactive');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('🧹 Cleaning up missing folders...');
        $this->newLine();

        $companyFolders = CompanyFolder::when($companyId, function($query) use ($companyId) {
            return $query->where('company_id', $companyId);
        })->get();

        if ($companyFolders->isEmpty()) {
            $this->warn('No company folders found.');
            return 0;
        }

        $this->info("Checking {$companyFolders->count()} company folders...");
        $this->newLine();

        $toDelete = [];
        $kept = 0;

        foreach ($companyFolders as $cf) {
            $company = Company::find($cf->company_id);
            $companyName = $company ? $company->name : 'Unknown';
            
            try {
                $exists = $this->googleDriveService->fileExists($cf->google_drive_folder_id);
                
                if ($exists) {
                    $this->line("✅ Keeping: {$cf->folder_name} (Company: {$companyName})");
                    $kept++;
                } else {
                    $this->line("❌ Will delete: {$cf->folder_name} (Company: {$companyName}, ID: {$cf->google_drive_folder_id})");
                    $toDelete[] = $cf;
                }
            } catch (\Exception $e) {
                $this->line("⚠️  Error checking: {$cf->folder_name} (Company: {$companyName}) - {$e->getMessage()}");
                $toDelete[] = $cf;
            }
        }

        $this->newLine();
        $this->info("📊 Summary:");
        $this->line("   ✅ Folders to keep: {$kept}");
        $this->line("   ❌ Folders to delete: " . count($toDelete));

        if (empty($toDelete)) {
            $this->info("🎉 No missing folders found. Nothing to clean up!");
            return 0;
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn("This was a dry run. To actually delete the missing folders, run:");
            $this->line("   php artisan cleanup:missing-folders" . ($companyId ? " --company-id={$companyId}" : ""));
            return 0;
        }

        $this->newLine();
        if ($interactive && !$this->confirm("Are you sure you want to delete " . count($toDelete) . " missing folders?")) {
            $this->info("Operation cancelled.");
            return 0;
        }

        $deleted = 0;
        $errors = 0;

        foreach ($toDelete as $cf) {
            try {
                $company = Company::find($cf->company_id);
                $companyName = $company ? $company->name : 'Unknown';
                
                $cf->delete();
                $this->line("🗑️  Deleted: {$cf->folder_name} (Company: {$companyName})");
                $deleted++;
            } catch (\Exception $e) {
                $this->error("❌ Error deleting {$cf->folder_name}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->info("🎉 Cleanup completed!");
        $this->line("   ✅ Successfully deleted: {$deleted}");
        if ($errors > 0) {
            $this->line("   ❌ Errors: {$errors}");
        }

        return 0;
    }
} 
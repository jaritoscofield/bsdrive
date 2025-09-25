<?php

namespace App\Console\Commands;

use App\Models\CompanyFolder;
use App\Models\Company;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class VerifyGoogleDriveFolders extends Command
{
    protected $signature = 'verify:google-drive-folders {--company-id=}';
    protected $description = 'Verify which folders in CompanyFolder table actually exist in Google Drive';

    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $companyId = $this->option('company-id');

        $this->info('🔍 Verifying Google Drive folder existence...');
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

        $existing = 0;
        $missing = 0;
        $missingFolders = [];

        foreach ($companyFolders as $cf) {
            $company = Company::find($cf->company_id);
            $companyName = $company ? $company->name : 'Unknown';
            
            try {
                $exists = $this->googleDriveService->fileExists($cf->google_drive_folder_id);
                
                if ($exists) {
                    $this->line("✅ {$cf->folder_name} (Company: {$companyName}, ID: {$cf->google_drive_folder_id})");
                    $existing++;
                } else {
                    $this->line("❌ {$cf->folder_name} (Company: {$companyName}, ID: {$cf->google_drive_folder_id}) - NOT FOUND");
                    $missing++;
                    $missingFolders[] = [
                        'id' => $cf->id,
                        'name' => $cf->folder_name,
                        'company' => $companyName,
                        'google_drive_id' => $cf->google_drive_folder_id
                    ];
                }
            } catch (\Exception $e) {
                $this->line("⚠️  {$cf->folder_name} (Company: {$companyName}, ID: {$cf->google_drive_folder_id}) - ERROR: {$e->getMessage()}");
                $missing++;
                $missingFolders[] = [
                    'id' => $cf->id,
                    'name' => $cf->folder_name,
                    'company' => $companyName,
                    'google_drive_id' => $cf->google_drive_folder_id,
                    'error' => $e->getMessage()
                ];
            }
        }

        $this->newLine();
        $this->info("📊 Summary:");
        $this->line("   ✅ Existing folders: {$existing}");
        $this->line("   ❌ Missing folders: {$missing}");

        if (!empty($missingFolders)) {
            $this->newLine();
            $this->warn("Missing folders that should be cleaned up:");
            foreach ($missingFolders as $missing) {
                $this->line("   - {$missing['name']} (Company: {$missing['company']}, ID: {$missing['google_drive_id']})");
            }
            
            $this->newLine();
            $this->info("To clean up missing folders, run:");
            $this->line("   php artisan cleanup:missing-folders");
        }

        return 0;
    }
} 
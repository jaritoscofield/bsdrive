<?php

namespace App\Console\Commands;

use App\Models\Folder;
use App\Models\CompanyFolder;
use App\Models\Company;
use Illuminate\Console\Command;

class CheckFolderTables extends Command
{
    protected $signature = 'check:folder-tables {--company-id=}';
    protected $description = 'Check the contents of Folder and CompanyFolder tables';

    public function handle()
    {
        $companyId = $this->option('company-id');

        $this->info('📊 Checking Folder and CompanyFolder tables...');
        $this->newLine();

        // Check Folders table
        $this->info('📁 Folders table:');
        $folders = Folder::when($companyId, function($query) use ($companyId) {
            return $query->where('company_id', $companyId);
        })->get(['id', 'name', 'company_id', 'active', 'google_drive_id']);

        if ($folders->isEmpty()) {
            $this->warn('   No folders found in Folders table');
        } else {
            foreach ($folders as $folder) {
                $company = Company::find($folder->company_id);
                $companyName = $company ? $company->name : 'Unknown';
                $status = $folder->active ? '✅ Active' : '❌ Inactive';
                $this->line("   {$folder->id} - {$folder->name} (Company: {$companyName}, Google Drive ID: {$folder->google_drive_id}, {$status})");
            }
        }

        $this->newLine();

        // Check CompanyFolders table
        $this->info('🏢 CompanyFolders table:');
        $companyFolders = CompanyFolder::when($companyId, function($query) use ($companyId) {
            return $query->where('company_id', $companyId);
        })->get(['id', 'company_id', 'google_drive_folder_id', 'folder_name', 'active']);

        if ($companyFolders->isEmpty()) {
            $this->warn('   No company folders found in CompanyFolders table');
        } else {
            foreach ($companyFolders as $cf) {
                $company = Company::find($cf->company_id);
                $companyName = $company ? $company->name : 'Unknown';
                $status = $cf->active ? '✅ Active' : '❌ Inactive';
                $this->line("   {$cf->id} - {$cf->folder_name} (Company: {$companyName}, Google Drive ID: {$cf->google_drive_folder_id}, {$status})");
            }
        }

        $this->newLine();

        // Check for mismatches
        $this->info('🔍 Checking for mismatches...');
        
        $folderIds = $folders->pluck('google_drive_id')->filter()->toArray();
        $companyFolderIds = $companyFolders->pluck('google_drive_folder_id')->toArray();
        
        $this->line("   Folders table has " . count($folderIds) . " Google Drive IDs");
        $this->line("   CompanyFolders table has " . count($companyFolderIds) . " Google Drive IDs");
        
        $missingInCompanyFolders = array_diff($folderIds, $companyFolderIds);
        $missingInFolders = array_diff($companyFolderIds, $folderIds);
        
        if (!empty($missingInCompanyFolders)) {
            $this->warn("   Google Drive IDs in Folders but not in CompanyFolders: " . implode(', ', $missingInCompanyFolders));
        }
        
        if (!empty($missingInFolders)) {
            $this->warn("   Google Drive IDs in CompanyFolders but not in Folders: " . implode(', ', $missingInFolders));
        }
        
        if (empty($missingInCompanyFolders) && empty($missingInFolders)) {
            $this->info("   ✅ No mismatches found");
        }

        return 0;
    }
} 
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;

class TestGoogleDriveConnection extends Command
{
    protected $signature = 'google:test-connection';
    protected $description = 'Test Google Drive connection and list files';

    public function handle()
    {
        $this->info('Testing Google Drive connection...');

        try {
            $googleDriveService = new GoogleDriveService();

            // Test if we can get the service
            $this->info('✓ GoogleDriveService instantiated');

            // Try to list files
            $this->info('Attempting to list files...');

            // Check if we have OAuth tokens
            if (session()->has('google_access_token')) {
                $this->info('✓ OAuth tokens found in session');
            } else {
                $this->warn('⚠ No OAuth tokens found in session');
                $this->info('You need to authenticate first at: http://localhost:8000/auth/google');
                return 1;
            }

            // Try to get files
            $files = $googleDriveService->listFiles();

            if ($files) {
                $this->info('✓ Successfully retrieved files from Google Drive');
                $this->info('Found ' . count($files) . ' files/folders:');

                foreach ($files as $file) {
                    $type = isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder' ? '📁' : '📄';
                    $this->line("  {$type} {$file['name']} (ID: {$file['id']})");
                }
            } else {
                $this->warn('⚠ No files found or error occurred');
            }

        } catch (\Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}

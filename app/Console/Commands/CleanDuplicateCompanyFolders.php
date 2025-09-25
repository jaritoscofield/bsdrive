<?php

namespace App\Console\Commands;

use App\Models\CompanyFolder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicateCompanyFolders extends Command
{
    protected $signature = 'clean:duplicate-company-folders';
    protected $description = 'Remove registros duplicados da tabela company_folders';

    public function handle()
    {
        $this->info('🧹 Iniciando limpeza de registros duplicados...');

        try {
            // Encontrar registros duplicados
            $duplicates = DB::table('company_folders')
                ->select('company_id', 'google_drive_folder_id', DB::raw('COUNT(*) as count'))
                ->groupBy('company_id', 'google_drive_folder_id')
                ->having('count', '>', 1)
                ->get();

            if ($duplicates->isEmpty()) {
                $this->info('✅ Nenhum registro duplicado encontrado!');
                return 0;
            }

            $this->info("📊 Encontrados " . $duplicates->count() . " grupos de registros duplicados");

            $totalRemoved = 0;

            foreach ($duplicates as $duplicate) {
                // Manter apenas o registro mais antigo (primeiro criado)
                $recordsToKeep = DB::table('company_folders')
                    ->where('company_id', $duplicate->company_id)
                    ->where('google_drive_folder_id', $duplicate->google_drive_folder_id)
                    ->orderBy('created_at', 'asc')
                    ->limit(1)
                    ->pluck('id');

                // Remover registros duplicados (mantendo apenas o primeiro)
                $removed = DB::table('company_folders')
                    ->where('company_id', $duplicate->company_id)
                    ->where('google_drive_folder_id', $duplicate->google_drive_folder_id)
                    ->whereNotIn('id', $recordsToKeep)
                    ->delete();

                $totalRemoved += $removed;

                $this->line("   🗑️ Removidos {$removed} registros duplicados para empresa {$duplicate->company_id} e pasta {$duplicate->google_drive_folder_id}");
            }

            $this->info('');
            $this->info("✅ Limpeza concluída! Total de registros removidos: {$totalRemoved}");

        } catch (\Exception $e) {
            $this->error("❌ Erro durante a limpeza: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
} 
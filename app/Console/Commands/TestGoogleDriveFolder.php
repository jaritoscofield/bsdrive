<?php

namespace App\Console\Commands;

use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class TestGoogleDriveFolder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:google-drive-folder {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa a criação de pasta no Google Drive';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $name = $this->argument('name');
            $this->info("Iniciando teste de criação de pasta: {$name}");
            
            $service = new GoogleDriveService();
            $this->info("Serviço GoogleDriveService criado com sucesso.");
            
            $folder = $service->createFolder($name, null);
            $this->info("Pasta criada com sucesso!");
            $this->info("ID: " . $folder['id']);
            $this->info("Nome: " . $folder['name']);
            
        } catch (\Exception $e) {
            $this->error("Erro: " . $e->getMessage());
            $this->error("Arquivo: " . $e->getFile() . " linha: " . $e->getLine());
        }
    }
}

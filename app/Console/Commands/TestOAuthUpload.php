<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;

class TestOAuthUpload extends Command
{
    protected $signature = 'google:test-upload {file?}';
    protected $description = 'Testa upload com OAuth para resolver problema de quota';

    public function handle()
    {
        $this->info('🧪 TESTE DE UPLOAD COM OAUTH');
        $this->line('');

        $driveService = app(GoogleDriveService::class);

        // Verificar se OAuth está configurado
        $tokenPath = storage_path('app/google_oauth_token.json');
        if (!file_exists($tokenPath)) {
            $this->error('❌ OAuth não configurado!');
            $this->line('');
            $this->info('📋 Para configurar:');
            $this->line('1. Acesse: ' . url('/google-setup'));
            $this->line('2. Configure Client ID e Secret no .env');
            $this->line('3. Clique em "Autorizar Google Drive"');
            return 1;
        }

        $this->info('✅ OAuth Token encontrado');
        
        // Testar conexão
        try {
            $this->info('🔍 Testando conexão...');
            
            // Criar arquivo de teste se não fornecido
            $filePath = $this->argument('file');
            if (!$filePath) {
                $testContent = "Teste de upload OAuth - " . date('Y-m-d H:i:s');
                $filePath = storage_path('app/test_oauth_upload.txt');
                file_put_contents($filePath, $testContent);
                $this->line("📝 Arquivo de teste criado: {$filePath}");
            }

            if (!file_exists($filePath)) {
                $this->error("❌ Arquivo não encontrado: {$filePath}");
                return 1;
            }

            // Tentar upload
            $this->info('📤 Tentando upload...');
            $result = $driveService->uploadFile(
                $filePath, 
                basename($filePath), 
                null, // pasta raiz
                mime_content_type($filePath)
            );

            $this->info('✅ Upload realizado com sucesso!');
            $this->line("📁 File ID: {$result->getId()}");
            $this->line("📄 Nome: {$result->getName()}");
            $this->line("🔗 Link: https://drive.google.com/file/d/{$result->getId()}/view");
            
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erro no upload:');
            $this->line($e->getMessage());
            
            if (strpos($e->getMessage(), 'quota') !== false) {
                $this->line('');
                $this->info('💡 SOLUÇÃO:');
                $this->line('Este erro indica que o Service Account não tem quota.');
                $this->line('O OAuth deveria resolver isso automaticamente.');
                $this->line('Verifique se o OAuth está funcionando em: ' . url('/google-setup'));
            }
            
            return 1;
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GoogleDriveSetup extends Command
{
    protected $signature = 'google:setup';
    protected $description = 'Guia para configurar Google Drive OAuth';

    public function handle()
    {
        $this->info('🔧 CONFIGURAÇÃO GOOGLE DRIVE OAUTH');
        $this->line('');

        // Verificar configurações atuais
        $this->info('📋 CONFIGURAÇÕES ATUAIS:');
        $this->line('Client ID: ' . (config('services.google.client_id') ? '✅ Configurado' : '❌ Não configurado'));
        $this->line('Client Secret: ' . (config('services.google.client_secret') ? '✅ Configurado' : '❌ Não configurado'));
        $this->line('Redirect URI: ' . config('services.google.redirect_uri'));
        $this->line('');

        // Instruções
        $this->info('⚙️ INSTRUÇÕES DE CONFIGURAÇÃO:');
        $this->line('');
        
        $this->info('1. Google Cloud Console:');
        $this->line('   → https://console.cloud.google.com/');
        $this->line('   → APIs & Services → Credentials');
        $this->line('   → Create Credentials → OAuth 2.0 Client IDs');
        $this->line('   → Web application');
        $this->line('');
        
        $this->info('2. Authorized redirect URIs:');
        $this->line('   → Adicione esta URL: ' . url('/google/callback'));
        $this->line('');
        
        $this->info('3. Configure no .env:');
        $this->line('   GOOGLE_CLIENT_ID=seu_client_id_aqui');
        $this->line('   GOOGLE_CLIENT_SECRET=seu_client_secret_aqui');
        $this->line('   GOOGLE_REDIRECT_URI=' . url('/google/callback'));
        $this->line('');
        
        $this->info('4. Limpar cache:');
        $this->line('   php artisan config:clear');
        $this->line('');
        
        $this->info('5. Acessar página de configuração:');
        $this->line('   → ' . url('/google-setup'));
        $this->line('');

        // Verificar se pode fazer setup automático
        if ($this->confirm('Deseja configurar automaticamente? (precisa do Client ID e Secret)')) {
            $this->setupInteractive();
        }

        $this->info('✅ Configuração concluída!');
    }

    private function setupInteractive()
    {
        $clientId = $this->ask('Cole o Client ID do Google:');
        $clientSecret = $this->secret('Cole o Client Secret do Google:');

        if ($clientId && $clientSecret) {
            $envPath = base_path('.env');
            $envContent = file_get_contents($envPath);

            // Atualizar ou adicionar configurações
            $updates = [
                'GOOGLE_CLIENT_ID' => $clientId,
                'GOOGLE_CLIENT_SECRET' => $clientSecret,
                'GOOGLE_REDIRECT_URI' => url('/google/callback')
            ];

            foreach ($updates as $key => $value) {
                if (strpos($envContent, $key . '=') !== false) {
                    $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
                } else {
                    $envContent .= "\n{$key}={$value}";
                }
            }

            file_put_contents($envPath, $envContent);
            
            $this->info('✅ Configurações salvas no .env');
            
            // Limpar cache
            $this->call('config:clear');
            $this->info('✅ Cache limpo');
        }
    }
}

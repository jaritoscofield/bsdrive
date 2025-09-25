<?php

namespace App\Console\Commands;

use App\Jobs\AutoSyncGoogleDriveJob;
use Illuminate\Console\Command;

class StartCompleteAutoSync extends Command
{
    protected $signature = 'auto-sync:complete';
    protected $description = 'Inicia o sistema completo de sincronização automática';

    public function handle()
    {
        $this->info('🚀 Iniciando sistema completo de sincronização automática...');
        $this->info('');
        
        // 1. Executar sincronização inicial
        $this->info('📡 1. Executando sincronização inicial...');
        $this->call('sync:auto-google-drive');
        $this->info('✅ Sincronização inicial concluída');
        $this->info('');

        // 2. Iniciar job de monitoramento contínuo
        $this->info('🔄 2. Iniciando monitoramento contínuo...');
        AutoSyncGoogleDriveJob::dispatch();
        $this->info('✅ Job de monitoramento agendado');
        $this->info('');

        // 3. Instruções para o usuário
        $this->info('📋 3. Configuração do sistema:');
        $this->info('   • O sistema verifica novas pastas a cada 5-10 minutos');
        $this->info('   • Pastas criadas via interface são detectadas instantaneamente');
        $this->info('   • Pastas criadas diretamente no Google Drive são detectadas em até 10 minutos');
        $this->info('');

        $this->info('🔧 4. Comandos úteis:');
        $this->info('   • Verificar logs: tail -f storage/logs/laravel.log');
        $this->info('   • Verificar jobs: php artisan queue:work');
        $this->info('   • Parar sistema: php artisan queue:restart');
        $this->info('   • Sincronização manual: php artisan sync:auto-google-drive');
        $this->info('');

        $this->info('🎯 5. Como usar:');
        $this->info('   • Crie pastas no Google Drive (qualquer método)');
        $this->info('   • Elas aparecerão automaticamente na lista de pastas da empresa');
        $this->info('   • Atribua aos usuários via interface de gerenciamento');
        $this->info('');

        $this->info('✅ Sistema automático iniciado com sucesso!');
        $this->info('💡 Agora você pode criar pastas e elas serão sincronizadas automaticamente!');
    }
} 
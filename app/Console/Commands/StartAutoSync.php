<?php

namespace App\Console\Commands;

use App\Jobs\AutoSyncGoogleDriveJob;
use Illuminate\Console\Command;

class StartAutoSync extends Command
{
    protected $signature = 'auto-sync:start';
    protected $description = 'Inicia o sistema automático de sincronização do Google Drive';

    public function handle()
    {
        $this->info('🚀 Iniciando sistema automático de sincronização...');
        $this->info('📡 O sistema irá verificar novas pastas automaticamente');
        $this->info('⏰ Intervalo: 5-10 minutos entre verificações');
        $this->info('💡 Para parar: php artisan queue:restart');
        $this->info('');

        // Executar primeira verificação imediatamente
        AutoSyncGoogleDriveJob::dispatch();

        $this->info('✅ Job de sincronização agendado com sucesso!');
        $this->info('📊 Para verificar logs: tail -f storage/logs/laravel.log');
        $this->info('🔄 Para verificar jobs: php artisan queue:work');
        $this->info('');
        $this->info('🎯 Agora você pode criar pastas no Google Drive e elas aparecerão automaticamente!');
    }
} 
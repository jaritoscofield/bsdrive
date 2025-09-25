<?php

require_once 'vendor/autoload.php';

use App\Services\GoogleDriveService;

// Configurar Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Testando Google Drive Service...\n\n";

try {
    $service = new GoogleDriveService();
    
    echo "✅ Serviço instanciado com sucesso\n";
    
    // Testar se está autenticado
    if ($service->isValidAuthentication()) {
        echo "✅ Autenticação válida\n";
        
        // Testar listagem de arquivos
        try {
            $files = $service->listFiles(null, 'files(id,name)', null);
            echo "✅ Listagem de arquivos funcionando. Total: " . count($files) . " arquivos\n";
        } catch (Exception $e) {
            echo "❌ Erro na listagem: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Autenticação inválida ou token expirado\n";
        echo "🔗 URL para nova autenticação: " . $service->forceReauth() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

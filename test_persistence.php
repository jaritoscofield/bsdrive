<?php

require_once 'vendor/autoload.php';

use App\Services\GoogleDriveService;

$app = require_once 'bootstrap/app.php';
$app->boot();

try {
    echo "=== Teste de Persistência de Pastas no Google Drive ===\n";
    
    $service = new GoogleDriveService();
    
    // Criar pasta
    echo "1. Criando pasta de teste...\n";
    $folder = $service->createFolder('Teste Persistencia - ' . date('Y-m-d H:i:s'), null);
    echo "   ✓ Pasta criada com ID: " . $folder['id'] . "\n";
    echo "   ✓ Nome: " . $folder['name'] . "\n";
    
    // Aguardar 5 segundos
    echo "\n2. Aguardando 5 segundos...\n";
    sleep(5);
    
    // Verificar se a pasta ainda existe
    echo "\n3. Verificando se a pasta ainda existe...\n";
    try {
        $folderCheck = $service->getFolder($folder['id']);
        echo "   ✓ Pasta ainda existe!\n";
        echo "   ✓ Nome: " . $folderCheck['name'] . "\n";
        echo "   ✓ ID: " . $folderCheck['id'] . "\n";
    } catch (Exception $e) {
        echo "   ✗ ERRO: Pasta não encontrada! " . $e->getMessage() . "\n";
    }
    
    // Listar pastas na raiz para confirmar
    echo "\n4. Listando pastas na raiz...\n";
    $files = $service->listFiles(null, 'files(id,name,mimeType,parents)');
    $folders = array_filter($files, function($file) {
        return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
    });
    
    echo "   Total de pastas encontradas: " . count($folders) . "\n";
    foreach ($folders as $f) {
        if (strpos($f['name'], 'Teste') !== false) {
            echo "   - " . $f['name'] . " (ID: " . $f['id'] . ")\n";
        }
    }
    
    echo "\n=== Teste Concluído ===\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

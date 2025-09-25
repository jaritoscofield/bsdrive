<?php

// Teste para verificar se há conflitos de classe
require_once __DIR__ . '/vendor/autoload.php';

echo "✅ Testando carregamento das classes...\n";

try {
    // Tentar instanciar o GoogleDriveFoldersController
    $controller = new \App\Http\Controllers\GoogleDriveFoldersController(
        new \App\Services\GoogleDriveService()
    );
    echo "✅ GoogleDriveFoldersController carregado com sucesso!\n";
} catch (\Exception $e) {
    echo "❌ Erro ao carregar GoogleDriveFoldersController: " . $e->getMessage() . "\n";
}

try {
    // Tentar instanciar o GoogleDriveFolderController (singular)
    $controller2 = new \App\Http\Controllers\GoogleDriveFolderController(
        new \App\Services\GoogleDriveService()
    );
    echo "✅ GoogleDriveFolderController carregado com sucesso!\n";
} catch (\Exception $e) {
    echo "❌ Erro ao carregar GoogleDriveFolderController: " . $e->getMessage() . "\n";
}

echo "🎯 Teste concluído!\n";

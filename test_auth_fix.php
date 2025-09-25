<?php

require_once 'vendor/autoload.php';

// Simular configuração Laravel
$_ENV['GOOGLE_CLIENT_ID'] = '';
$_ENV['GOOGLE_CLIENT_SECRET'] = '';
$_ENV['GOOGLE_REDIRECT_URI'] = '/google/callback';

echo "🔍 Teste de Configuração de Autenticação\n";
echo "=====================================\n\n";

// Verificar se o arquivo service account existe
$serviceAccountFile = __DIR__ . '/storage/app/copper-tracker-465119-f5-2566d7129a2c.json';
echo "📁 Arquivo Service Account: ";
if (file_exists($serviceAccountFile)) {
    echo "✅ EXISTE\n";
    echo "   Caminho: $serviceAccountFile\n";
    
    // Verificar se o arquivo é válido JSON
    $content = file_get_contents($serviceAccountFile);
    $json = json_decode($content, true);
    if ($json) {
        echo "   Formato: ✅ JSON válido\n";
        echo "   Projeto: " . ($json['project_id'] ?? 'N/A') . "\n";
        echo "   Cliente: " . substr($json['client_email'] ?? 'N/A', 0, 30) . "...\n";
    } else {
        echo "   Formato: ❌ JSON inválido\n";
    }
} else {
    echo "❌ NÃO EXISTE\n";
}

echo "\n";

// Verificar token OAuth
$oauthTokenFile = __DIR__ . '/storage/app/google_oauth_token.json';
echo "🔑 Token OAuth: ";
if (file_exists($oauthTokenFile)) {
    echo "✅ EXISTE (não necessário mais)\n";
} else {
    echo "❌ NÃO EXISTE (ok, usando Service Account)\n";
}

echo "\n";

echo "🎯 Configuração Recomendada:\n";
echo "- Sistema deve usar APENAS Service Account\n";
echo "- Não deve forçar login OAuth individual\n";
echo "- Conta Google é única para todo o sistema\n";
echo "\n";

echo "✅ Correções aplicadas:\n";
echo "- DashboardController: removida verificação OAuth\n";
echo "- GoogleDriveFoldersController: removida verificação OAuth\n";
echo "- GoogleDriveService: prioriza Service Account\n";
echo "- Dashboard view: removido link para autenticação Google\n";
echo "\n";

echo "🚀 O sistema agora deve funcionar sem forçar login no Google!\n";

?>

<!DOCTYPE html>
<html>
<head>
    <title>Teste Google Drive Token</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .status { padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Teste de Conectividade Google Drive</h1>
    
    <?php
    require_once '../vendor/autoload.php';
    
    // Configurar Laravel
    $app = require_once '../bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    try {
        $service = new App\Services\GoogleDriveService();
        
        echo '<div class="status success">✅ Serviço Google Drive instanciado com sucesso</div>';
        
        // Verificar se há token
        $tokenPath = '../storage/app/google_oauth_token.json';
        if (file_exists($tokenPath)) {
            $token = json_decode(file_get_contents($tokenPath), true);
            echo '<div class="status success">✅ Token OAuth encontrado</div>';
            echo '<p><strong>Criado em:</strong> ' . date('d/m/Y H:i:s', $token['created'] ?? time()) . '</p>';
            
            if (isset($token['expires_in'])) {
                $expiresAt = ($token['created'] ?? time()) + $token['expires_in'];
                $isExpired = time() > $expiresAt;
                
                if ($isExpired) {
                    echo '<div class="status warning">⚠️ Token expirado em ' . date('d/m/Y H:i:s', $expiresAt) . '</div>';
                } else {
                    echo '<div class="status success">✅ Token válido até ' . date('d/m/Y H:i:s', $expiresAt) . '</div>';
                }
            }
        } else {
            echo '<div class="status error">❌ Nenhum token OAuth encontrado</div>';
        }
        
        // Testar autenticação
        if ($service->isValidAuthentication()) {
            echo '<div class="status success">✅ Autenticação válida - API funcionando</div>';
            
            // Testar listagem
            try {
                $files = $service->listFiles(null, 'files(id,name)', null);
                echo '<div class="status success">✅ Listagem de arquivos funcionando (' . count($files) . ' arquivos encontrados)</div>';
            } catch (Exception $e) {
                echo '<div class="status error">❌ Erro na listagem: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
        } else {
            echo '<div class="status error">❌ Autenticação inválida</div>';
            echo '<p><a href="' . $service->forceReauth() . '" class="btn">🔑 Autenticar novamente</a></p>';
        }
        
    } catch (Exception $e) {
        echo '<div class="status error">❌ Erro: ' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
    ?>
    
    <hr>
    <p><a href="/dashboard" class="btn">📊 Ir para Dashboard</a></p>
    <p><a href="/google/auth" class="btn">🔑 Autenticar Google Drive</a></p>
</body>
</html>

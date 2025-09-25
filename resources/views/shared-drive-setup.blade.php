<!DOCTYPE html>
<html>
<head>
    <title>Configuração - Drive Pessoal</title>
    <style>
        body {         <li><a href="/google-drive">🗂️ BSDrive</a></li>ont-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .step { background: #f5f5f5; padding: 15px; margin: 10px 0; border-left: 4px solid #007cba; }
        .error { background: #ffebee; border-left: 4px solid #f44336; }
        .success { background: #e8f5e8; border-left: 4px solid #4caf50; }
        .info { background: #e3f2fd; border-left: 4px solid #2196f3; }
        .code { background: #2d2d2d; color: #f8f8f2; padding: 10px; border-radius: 4px; font-family: monospace; }
        .important { color: #d32f2f; font-weight: bold; }
    </style>
</head>
<body>
    <h1>✅ SHARED DRIVES DESABILITADOS</h1>
    
    <div class="success">
        <h3>🔧 Configuração Atual:</h3>
        <p><strong>O sistema foi configurado para NÃO usar Shared Drives</strong></p>
        <p>Todos os uploads serão feitos para o <span class="important">Drive Pessoal</span> da Service Account</p>
    </div>

    <div class="info">
        <h3>📋 O que isso significa:</h3>
        <ul>
            <li>✅ Não precisa criar ou configurar Shared Drives</li>
            <li>✅ Não precisa definir GOOGLE_SHARED_DRIVE_ID</li>
            <li>✅ Funciona diretamente com o Drive da Service Account</li>
            <li>⚠️ Limite de storage depende da conta da Service Account</li>
        </ul>
    </div>

    <div class="step">
        <h3>📂 Como funciona agora:</h3>
        <ul>
            <li>Uploads vão direto para o BSDrive da Service Account</li>
            <li>Pastas são criadas no drive pessoal</li>
            <li>Não há dependência de Shared Drives</li>
        </ul>
    </div>

    <div class="info">
        <h3>� Se quiser REATIVAR Shared Drives:</h3>
        <p>Edite o arquivo <code>app/Services/GoogleDriveService.php</code> e descomente as linhas relacionadas ao <code>sharedDriveId</code></p>
    </div>

    <hr>
    
    <div class="step">
        <h3>🏠 Voltar</h3>
        <a href="{{ url('/') }}">← Voltar para o Dashboard</a>
    </div>

</body>
</html>
        <h3>1️⃣ Acesse o BSDrive</h3>
        <p>Vá para: <a href="https://drive.google.com" target="_blank">https://drive.google.com</a></p>
    </div>

    <div class="step">
        <h3>2️⃣ Criar Shared Drive</h3>
        <ul>
            <li>Clique em <strong>"Novo"</strong> (canto superior esquerdo)</li>
            <li>Selecione <strong>"Shared drive"</strong></li>
            <li>Nome sugerido: <strong>"BSDriver Files"</strong></li>
            <li>Clique em <strong>"Criar"</strong></li>
        </ul>
    </div>

    <div class="step">
        <h3>3️⃣ Adicionar Service Account</h3>
        <ul>
            <li>Dentro do Shared Drive, clique em <strong>"Manage members"</strong> (ícone de pessoas)</li>
            <li>Clique em <strong>"Add members"</strong></li>
            <li>Adicione este email:</li>
        </ul>
        <div class="code">copper-tracker-465119-f5@copper-tracker-465119-f5.iam.gserviceaccount.com</div>
        <ul>
            <li>Defina permissão como <strong>"Content manager"</strong></li>
            <li>Clique em <strong>"Send"</strong></li>
        </ul>
    </div>

    <div class="step">
        <h3>4️⃣ Copiar ID do Shared Drive</h3>
        <ul>
            <li>Na URL do Shared Drive, copie a parte após <code>/drive/folders/</code></li>
            <li>Exemplo: <code>https://drive.google.com/drive/folders/<strong>0BxYc1234567890</strong></code></li>
            <li>O ID seria: <strong>0BxYc1234567890</strong></li>
        </ul>
    </div>

    <div class="step">
        <h3>5️⃣ Configurar no Sistema</h3>
        <p>Adicione no arquivo <code>.env</code>:</p>
        <div class="code">GOOGLE_SHARED_DRIVE_ID=SEU_ID_COPIADO_AQUI</div>
        <p><strong>Exemplo:</strong></p>
        <div class="code">GOOGLE_SHARED_DRIVE_ID=0BxYc1234567890</div>
    </div>

    <div class="step">
        <h3>6️⃣ Reiniciar Servidor</h3>
        <p>No terminal, pare e inicie o servidor novamente</p>
    </div>

    <hr>
    
    <h2>🔍 Links Úteis:</h2>
    <ul>
        <li><a href="/google-drive">� Google Drive</a></li>
        <li><a href="/dashboard">🏠 Voltar ao Dashboard</a></li>
    </ul>

    <div class="success">
        <h3>💡 Depois de configurar:</h3>
        <p>O sistema funcionará perfeitamente e todos os uploads irão para o Shared Drive!</p>
    </div>
</body>
</html>

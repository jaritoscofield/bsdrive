<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração BSDrive</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-900">
                    🔧 Configuração BSDrive OAuth
                </h1>
                <p class="text-gray-600 mt-2">Configure OAuth para resolver problema de quota</p>
            </div>

            <div class="p-6">
                <!-- Status da Autenticação -->
                <div class="mb-8">
                    <h2 class="text-lg font-semibold mb-4">📊 Status da Autenticação</h2>
                    <div id="auth-status" class="p-4 rounded-lg border">
                        <div class="animate-pulse">Verificando status...</div>
                    </div>
                </div>

                <!-- Instruções de Setup -->
                <div class="mb-8">
                    <h2 class="text-lg font-semibold mb-4">⚙️ Instruções de Setup</h2>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <h3 class="font-medium text-blue-900 mb-3">1. Configure no Google Cloud Console:</h3>
                        <ol class="list-decimal list-inside space-y-2 text-blue-800 ml-4">
                            <li>Acesse <a href="https://console.cloud.google.com/" target="_blank" class="underline font-medium">Google Cloud Console</a></li>
                            <li>Selecione seu projeto ou crie um novo</li>
                            <li>Vá em "APIs & Services" → "Credentials"</li>
                            <li>Clique "Create Credentials" → "OAuth 2.0 Client IDs"</li>
                            <li>Escolha "Web application"</li>
                            <li>Em "Authorized redirect URIs", adicione:
                                <code class="block bg-white p-2 mt-2 rounded border">{{ url('/google/callback') }}</code>
                            </li>
                            <li>Copie o Client ID e Client Secret</li>
                        </ol>

                        <h3 class="font-medium text-blue-900 mb-3 mt-6">2. Configure as variáveis no .env:</h3>
                        <div class="bg-white p-4 rounded border font-mono text-sm">
                            <div class="text-gray-600"># BSDrive OAuth</div>
                            <div>GOOGLE_CLIENT_ID=seu_client_id_aqui</div>
                            <div>GOOGLE_CLIENT_SECRET=seu_client_secret_aqui</div>
                            <div>GOOGLE_REDIRECT_URI={{ url('/google/callback') }}</div>
                        </div>

                        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                            <strong class="text-yellow-800">⚠️ Importante:</strong>
                            <span class="text-yellow-700">Após configurar o .env, execute <code>php artisan config:clear</code></span>
                        </div>
                    </div>
                </div>

                <!-- Ações -->
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold">🎯 Ações</h2>
                    
                    <div class="flex flex-wrap gap-4">
                        <button id="authorize-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            🔐 Autorizar BSDrive
                        </button>
                        
                        <button id="revoke-btn" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            🗑️ Remover Autorização
                        </button>
                        
                        <a href="/dashboard" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors inline-block">
                            🏠 Voltar ao Dashboard
                        </a>
                    </div>
                </div>

                <!-- Logs -->
                <div class="mt-8">
                    <h2 class="text-lg font-semibold mb-4">📝 Como verificar logs</h2>
                    <div class="bg-gray-50 border rounded-lg p-4">
                        <p class="text-gray-600">Para ver logs em tempo real, execute no terminal:</p>
                        <code class="block bg-gray-800 text-green-400 p-2 mt-2 rounded">tail -f storage/logs/laravel.log | grep EMERGENCY</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const authStatusDiv = document.getElementById('auth-status');
        const authorizeBtn = document.getElementById('authorize-btn');
        const revokeBtn = document.getElementById('revoke-btn');

        // Verificar status da autenticação
        function checkAuthStatus() {
            fetch('/google/status')
                .then(response => response.json())
                .then(data => {
                    if (data.authenticated) {
                        authStatusDiv.innerHTML = `
                            <div class="flex items-center text-green-800 bg-green-100 p-4 rounded">
                                <span class="text-2xl mr-3">✅</span>
                                <div>
                                    <div class="font-medium">BSDrive Autenticado</div>
                                    <div class="text-sm">${data.message}</div>
                                </div>
                            </div>
                        `;
                    } else {
                        authStatusDiv.innerHTML = `
                            <div class="flex items-center text-yellow-800 bg-yellow-100 p-4 rounded">
                                <span class="text-2xl mr-3">⚠️</span>
                                <div>
                                    <div class="font-medium">BSDrive Não Autenticado</div>
                                    <div class="text-sm">${data.message}</div>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    authStatusDiv.innerHTML = `
                        <div class="flex items-center text-red-800 bg-red-100 p-4 rounded">
                            <span class="text-2xl mr-3">❌</span>
                            <div>
                                <div class="font-medium">Erro ao verificar status</div>
                                <div class="text-sm">${error.message}</div>
                            </div>
                        </div>
                    `;
                });
        }

        // Autorizar BSDrive
        authorizeBtn.addEventListener('click', function() {
            window.location.href = '/google/auth';
        });

        // Remover autorização
        revokeBtn.addEventListener('click', function() {
            if (confirm('Tem certeza que deseja remover a autorização do BSDrive?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/google/revoke';
                form.innerHTML = '@csrf';
                document.body.appendChild(form);
                form.submit();
            }
        });

        // Verificar status inicial
        checkAuthStatus();

        // Atualizar status a cada 30 segundos
        setInterval(checkAuthStatus, 30000);
    });
    </script>
</body>
</html>

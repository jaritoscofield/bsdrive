@extends('layouts.dashboard')

@section('title', 'BSDrive - BSDrive')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <    <!-- Explorador do BSDrive -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Explorador do BSDrive</h3>class="text-3xl font-bold text-gray-900 mb-2">BSDrive</h1>
        <p class="text-gray-600">Gerencie a integração com o BSDrive</p>
    </div>

    <!-- Status da Conexão -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Status da Conexão</h2>
        <div class="flex items-center space-x-4">
            <div id="connection-status" class="flex items-center">
                <div class="w-3 h-3 bg-gray-400 rounded-full mr-2"></div>
                <span class="text-gray-600">Verificando conexão...</span>
            </div>
            @if(!session('google_drive_token'))
                <a href="{{ route('google-drive.auth') }}" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm">
                    Conectar BSDrive
                </a>
            @else
                <span class="text-green-600 text-sm">✓ Conectado ao BSDrive</span>
            @endif
        </div>
    </div>

    <!-- Estatísticas de Sincronização -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Arquivos</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $syncedFiles }}/{{ $totalFiles }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pastas</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $syncedFolders }}/{{ $totalFolders }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Última Sincronização</p>
                    <p class="text-lg font-semibold text-gray-900" id="last-sync">-</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Status</p>
                    <p class="text-lg font-semibold text-gray-900" id="sync-status">Ativo</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Gerenciamento de Pastas -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Gerenciamento de Pastas</h2>
        <p class="text-gray-600 mb-4">Gerencie as pastas do BSDrive diretamente pela interface.</p>
        
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('folders.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                </svg>
                Ver Pastas
            </a>
            <a href="{{ route('folders.create') }}" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Criar Pasta
            </a>
        </div>
    </div>

    <!-- Ações de Sincronização -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Sincronização Manual -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Sincronização Manual</h3>

            <div class="space-y-4">
                <button id="sync-company" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-md font-medium">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Sincronizar Empresa
                </button>

                <div class="border-t pt-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Sincronizar Item Específico</h4>
                    <div class="space-y-2">
                        <input type="text" id="folder-id" placeholder="ID da pasta" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <button id="sync-folder" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm">
                            Sincronizar Pasta
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Importação do BSDrive -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Importação do BSDrive</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ID da Pasta do BSDrive</label>
                    <input type="text" id="import-folder-id" placeholder="Ex: 1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pasta Local (opcional)</label>
                    <select id="local-folder-id" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="">Raiz</option>
                        @foreach(\App\Models\Folder::notDeleted()->byCompany(auth()->user()->company_id)->get() as $folder)
                            <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button id="import-from-drive" class="w-full bg-purple-500 hover:bg-purple-600 text-white px-4 py-3 rounded-md font-medium">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                    </svg>
                    Importar do BSDrive
                </button>
            </div>
        </div>
    </div>

    <!-- Explorador do Google Drive -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Explorador do Google Drive</h3>

        <div class="mb-4">
            <input type="text" id="explorer-folder-id" placeholder="ID da pasta (deixe vazio para raiz)" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
            <button id="explore-folder" class="mt-2 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm">
                Explorar
            </button>
        </div>

        <div id="explorer-results" class="border border-gray-200 rounded-md p-4 min-h-32">
            <p class="text-gray-500 text-center">Digite um ID de pasta e clique em "Explorar" para ver o conteúdo</p>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div id="confirm-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4" id="modal-title">Confirmar Ação</h3>
            <p class="text-gray-600 mb-6" id="modal-message">Tem certeza que deseja executar esta ação?</p>
            <div class="flex justify-end space-x-3">
                <button id="modal-cancel" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">Cancelar</button>
                <button id="modal-confirm" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">Confirmar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Testar conexão
    document.getElementById('test-connection').addEventListener('click', function() {
        testConnection();
    });

    // Sincronizar empresa
    document.getElementById('sync-company').addEventListener('click', function() {
        showConfirmModal(
            'Sincronizar Empresa',
            'Tem certeza que deseja sincronizar toda a empresa com o BSDrive? Esta operação pode demorar alguns minutos.',
            () => syncCompany()
        );
    });

    // Sincronizar pasta específica
    document.getElementById('sync-folder').addEventListener('click', function() {
        const folderId = document.getElementById('folder-id').value;
        if (!folderId) {
            alert('Digite o ID da pasta');
            return;
        }
        showConfirmModal(
            'Sincronizar Pasta',
            'Tem certeza que deseja sincronizar esta pasta com o BSDrive?',
            () => syncFolder(folderId)
        );
    });

    // Importar do BSDrive
    document.getElementById('import-from-drive').addEventListener('click', function() {
        const folderId = document.getElementById('import-folder-id').value;
        if (!folderId) {
            alert('Digite o ID da pasta do BSDrive');
            return;
        }
        showConfirmModal(
            'Importar do BSDrive',
            'Tem certeza que deseja importar os dados do BSDrive? Esta operação pode demorar alguns minutos.',
            () => importFromDrive(folderId)
        );
    });

    // Explorar pasta
    document.getElementById('explore-folder').addEventListener('click', function() {
        const folderId = document.getElementById('explorer-folder-id').value;
        exploreFolder(folderId);
    });

    // Modal
    document.getElementById('modal-cancel').addEventListener('click', hideConfirmModal);
    document.getElementById('modal-confirm').addEventListener('click', function() {
        if (window.pendingAction) {
            window.pendingAction();
        }
        hideConfirmModal();
    });

    // Testar conexão inicial
    testConnection();
});

function testConnection() {
    const statusElement = document.getElementById('connection-status');
    const button = document.getElementById('test-connection');

    statusElement.innerHTML = '<div class="w-3 h-3 bg-yellow-400 rounded-full mr-2"></div><span class="text-gray-600">Testando...</span>';
    button.disabled = true;
    button.textContent = 'Testando...';

    fetch('{{ route("google-drive.test-connection") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusElement.innerHTML = '<div class="w-3 h-3 bg-green-400 rounded-full mr-2"></div><span class="text-green-600">Conectado</span>';
            } else {
                statusElement.innerHTML = '<div class="w-3 h-3 bg-red-400 rounded-full mr-2"></div><span class="text-red-600">Erro na conexão</span>';
            }
        })
        .catch(error => {
            statusElement.innerHTML = '<div class="w-3 h-3 bg-red-400 rounded-full mr-2"></div><span class="text-red-600">Erro na conexão</span>';
        })
        .finally(() => {
            button.disabled = false;
            button.textContent = 'Testar Conexão';
        });
}

function syncCompany() {
    showLoading('Sincronizando empresa...');

    fetch('{{ route("google-drive.sync-company") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showSuccess(data.message);
            setTimeout(() => location.reload(), 2000);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        showError('Erro ao sincronizar empresa');
    });
}

function syncFolder(folderId) {
    showLoading('Sincronizando pasta...');

    fetch(`{{ route("google-drive.sync-folder", ":folder") }}`.replace(':folder', folderId), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showSuccess(data.message);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        showError('Erro ao sincronizar pasta');
    });
}

function importFromDrive(folderId) {
    const localFolderId = document.getElementById('local-folder-id').value;
    showLoading('Importando do BSDrive...');

    fetch('{{ route("google-drive.import") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            google_drive_folder_id: folderId,
            local_folder_id: localFolderId || null
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showSuccess(data.message);
            setTimeout(() => location.reload(), 2000);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        showError('Erro ao importar do BSDrive');
    });
}

function exploreFolder(folderId) {
    const resultsElement = document.getElementById('explorer-results');
    resultsElement.innerHTML = '<p class="text-gray-500 text-center">Carregando...</p>';

    const params = new URLSearchParams();
    if (folderId) {
        params.append('folder_id', folderId);
    }

    fetch(`{{ route("google-drive.list-files") }}?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayExplorerResults(data.data);
            } else {
                resultsElement.innerHTML = `<p class="text-red-500 text-center">${data.message}</p>`;
            }
        })
        .catch(error => {
            resultsElement.innerHTML = '<p class="text-red-500 text-center">Erro ao carregar dados</p>';
        });
}

function displayExplorerResults(files) {
    const resultsElement = document.getElementById('explorer-results');

    if (files.length === 0) {
        resultsElement.innerHTML = '<p class="text-gray-500 text-center">Nenhum arquivo encontrado</p>';
        return;
    }

    let html = '<div class="space-y-2">';
    files.forEach(file => {
        const icon = file.mimeType === 'application/vnd.google-apps.folder' ? '📁' : '📄';
        const type = file.mimeType === 'application/vnd.google-apps.folder' ? 'Pasta' : 'Arquivo';
        html += `
            <div class="flex items-center justify-between p-2 border border-gray-200 rounded">
                <div class="flex items-center">
                    <span class="mr-2">${icon}</span>
                    <span class="text-sm">${file.name}</span>
                    <span class="ml-2 text-xs text-gray-500">(${type})</span>
                </div>
                <span class="text-xs text-gray-400">${file.id}</span>
            </div>
        `;
    });
    html += '</div>';

    resultsElement.innerHTML = html;
}

function showConfirmModal(title, message, action) {
    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-message').textContent = message;
    window.pendingAction = action;
    document.getElementById('confirm-modal').classList.remove('hidden');
}

function hideConfirmModal() {
    document.getElementById('confirm-modal').classList.add('hidden');
    window.pendingAction = null;
}

function showLoading(message) {
    // Implementar loading
    console.log('Loading:', message);
}

function hideLoading() {
    // Implementar hide loading
    console.log('Hide loading');
}

function showSuccess(message) {
    alert('Sucesso: ' + message);
}

function showError(message) {
    alert('Erro: ' + message);
}
</script>
@endpush

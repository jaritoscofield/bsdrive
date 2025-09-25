@extends('layouts.dashboard')

@section('title', 'Pastas do Google Drive - BSDrive')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-neutral-900 mb-2">📁 Pastas do Google Drive</h2>
                <p class="text-neutral-600">Visualize todas as pastas e seus IDs para configuração e gerenciamento.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('dashboard') }}" class="bg-neutral-500 hover:bg-neutral-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    ← Voltar
                </a>
                <button onclick="location.reload()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    🔄 Atualizar
                </button>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="bg-white rounded-lg shadow-sm border mb-6 p-4">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input 
                    type="text" 
                    id="searchFolders" 
                    placeholder="🔍 Buscar pastas..." 
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    onkeyup="filterFolders()"
                >
            </div>
            <div>
                <button onclick="copyAllIds()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    📋 Copiar Todos os IDs
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <span class="text-green-600 mr-3">✅</span>
                <p class="text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <span class="text-red-600 mr-3">❌</span>
                <p class="text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    @if(session('warning'))
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <span class="text-yellow-600 mr-3">⚠️</span>
                <p class="text-yellow-800">{{ session('warning') }}</p>
            </div>
        </div>
    @endif

    <!-- Folders Tree -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Estrutura Hierárquica -->
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="p-4 border-b bg-neutral-50">
                <h3 class="text-lg font-semibold text-neutral-900">🌳 Estrutura Hierárquica</h3>
                <p class="text-sm text-neutral-600 mt-1">Pastas organizadas por níveis: <span id="totalFolders">{{ $totalFolders ?? count($allFolders) }}</span></p>
            </div>
            
            <div class="p-4 max-h-96 overflow-y-auto">
                @if(empty($allFolders))
                    <div class="text-center py-8">
                        <div class="text-neutral-400 text-4xl mb-2">📂</div>
                        <p class="text-neutral-600">Nenhuma pasta encontrada na estrutura hierárquica.</p>
                    </div>
                @else
                    <div id="foldersContainer">
                        @foreach($allFolders as $folder)
                            @include('google-drive.partials.folder-item', ['folder' => $folder])
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Lista Completa (Flat) -->
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="p-4 border-b bg-blue-50">
                <h3 class="text-lg font-semibold text-neutral-900">📋 Lista Completa de Pastas</h3>
                <p class="text-sm text-neutral-600 mt-1">Todas as pastas: <span id="totalFoldersFlat">{{ $totalFoldersFlat ?? 0 }}</span></p>
            </div>
            
            <div class="p-4 max-h-96 overflow-y-auto">
                @if(empty($allFoldersFlat))
                    <div class="text-center py-8">
                        <div class="text-neutral-400 text-4xl mb-2">📂</div>
                        <p class="text-neutral-600">Nenhuma pasta encontrada.</p>
                    </div>
                @else
                    <div id="flatFoldersContainer">
                        @foreach($allFoldersFlat as $folder)
                            @include('google-drive.partials.folder-item-flat', ['folder' => $folder])
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Statistics -->
    @if(!empty($allFolders) || !empty($allFoldersFlat))
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600">Hierárquicas</p>
                    <p class="text-2xl font-bold text-blue-600" id="totalFoldersCount">{{ $totalFolders ?? count($allFolders) }}</p>
                </div>
                <div class="bg-blue-100 rounded-lg p-3">
                    <span class="text-blue-600 text-xl">🌳</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600">Total Completo</p>
                    <p class="text-2xl font-bold text-green-600">{{ $totalFoldersFlat ?? 0 }}</p>
                </div>
                <div class="bg-green-100 rounded-lg p-3">
                    <span class="text-green-600 text-xl">�</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600">Pastas Raiz</p>
                    <p class="text-2xl font-bold text-purple-600">{{ count($allFolders) }}</p>
                </div>
                <div class="bg-purple-100 rounded-lg p-3">
                    <span class="text-purple-600 text-xl">🏠</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600">Níveis de Profundidade</p>
                    <p class="text-2xl font-bold text-orange-600">{{ $maxLevel ?? 1 }}</p>
                </div>
                <div class="bg-orange-100 rounded-lg p-3">
                    <span class="text-orange-600 text-xl">📊</span>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
// Função para filtrar pastas
function filterFolders() {
    const searchTerm = document.getElementById('searchFolders').value.toLowerCase();
    const folderItems = document.querySelectorAll('.folder-item, .folder-item-flat');
    let visibleCount = 0;

    folderItems.forEach(item => {
        const folderName = item.querySelector('.folder-name').textContent.toLowerCase();
        const folderId = item.querySelector('.folder-id').textContent.toLowerCase();
        
        if (folderName.includes(searchTerm) || folderId.includes(searchTerm)) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    // Atualizar contadores
    document.getElementById('totalFolders').textContent = visibleCount;
    if (document.getElementById('totalFoldersFlat')) {
        document.getElementById('totalFoldersFlat').textContent = visibleCount;
    }
}

// Função para copiar ID
function copyId(id, button) {
    navigator.clipboard.writeText(id).then(() => {
        const originalText = button.textContent;
        button.textContent = '✅';
        button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        button.classList.add('bg-green-600');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('bg-green-600');
            button.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }, 2000);
    });
}

// Função para copiar todos os IDs
function copyAllIds() {
    const ids = [];
    document.querySelectorAll('.folder-id').forEach(el => {
        ids.push(el.textContent);
    });
    
    // Remover duplicatas
    const uniqueIds = [...new Set(ids)];
    
    const idsText = uniqueIds.join('\n');
    navigator.clipboard.writeText(idsText).then(() => {
        alert(`✅ ${uniqueIds.length} IDs únicos copiados para a área de transferência!`);
    });
}

// Função para expandir/recolher pastas (apenas na view hierárquica)
function toggleFolder(button) {
    const item = button.closest('.folder-item');
    const children = item.querySelector('.folder-children');
    
    if (children) {
        if (children.style.display === 'none' || !children.style.display) {
            children.style.display = 'block';
            button.textContent = '📂';
        } else {
            children.style.display = 'none';
            button.textContent = '📁';
        }
    }
}
</script>
@endsection

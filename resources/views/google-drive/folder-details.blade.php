@extends('layouts.dashboard')

@section('title', 'Detalhes da Pasta - ' . ($folder['name'] ?? 'Pasta'))

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-neutral-900 mb-2">📁 {{ $folder['name'] ?? 'Pasta sem nome' }}</h2>
                <p class="text-neutral-600">Detalhes e conteúdo da pasta selecionada.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('google-folders.index') }}" class="bg-neutral-500 hover:bg-neutral-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    ← Voltar para Lista
                </a>
                <button onclick="copyId('{{ $folder['id'] }}', this)" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    📋 Copiar ID
                </button>
            </div>
        </div>
    </div>

    <!-- Folder Info Card -->
    <div class="bg-white rounded-lg shadow-sm border mb-6">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-neutral-900 mb-4">ℹ️ Informações da Pasta</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-2">Nome</label>
                    <p class="text-neutral-900 font-medium">{{ $folder['name'] ?? 'Sem nome' }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-2">ID da Pasta</label>
                    <code class="bg-neutral-100 px-3 py-2 rounded text-sm font-mono block">{{ $folder['id'] }}</code>
                </div>
                
                @if(isset($folder['parents']) && !empty($folder['parents']))
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-2">Pasta Pai</label>
                    <code class="bg-neutral-100 px-3 py-2 rounded text-sm font-mono block">{{ $folder['parents'][0] }}</code>
                </div>
                @endif
                
                @if(isset($folder['createdTime']))
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-2">Data de Criação</label>
                    <p class="text-neutral-900">{{ \Carbon\Carbon::parse($folder['createdTime'])->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</p>
                </div>
                @endif
                
                @if(isset($folder['modifiedTime']))
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-2">Última Modificação</label>
                    <p class="text-neutral-900">{{ \Carbon\Carbon::parse($folder['modifiedTime'])->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</p>
                </div>
                @endif
                
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-2">Total de Itens</label>
                    <p class="text-neutral-900 font-medium">{{ count($subfolders) + count($documents) }} itens</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600">Subpastas</p>
                    <p class="text-2xl font-bold text-blue-600">{{ count($subfolders) }}</p>
                </div>
                <div class="bg-blue-100 rounded-lg p-3">
                    <span class="text-blue-600 text-xl">📁</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600">Arquivos</p>
                    <p class="text-2xl font-bold text-green-600">{{ count($documents) }}</p>
                </div>
                <div class="bg-green-100 rounded-lg p-3">
                    <span class="text-green-600 text-xl">📄</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600">Total</p>
                    <p class="text-2xl font-bold text-purple-600">{{ count($subfolders) + count($documents) }}</p>
                </div>
                <div class="bg-purple-100 rounded-lg p-3">
                    <span class="text-purple-600 text-xl">📊</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Subfolders Section -->
    @if(!empty($subfolders))
    <div class="bg-white rounded-lg shadow-sm border mb-6">
        <div class="p-4 border-b bg-neutral-50">
            <h3 class="text-lg font-semibold text-neutral-900">📁 Subpastas ({{ count($subfolders) }})</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 gap-4">
                @foreach($subfolders as $subfolder)
                <div class="flex items-center justify-between p-4 bg-neutral-50 rounded-lg hover:bg-neutral-100 transition-colors">
                    <div class="flex items-center flex-1">
                        <span class="text-2xl mr-4">📁</span>
                        <div class="flex-1">
                            <h4 class="font-medium text-neutral-900">{{ $subfolder['name'] ?? 'Sem nome' }}</h4>
                            <p class="text-sm text-neutral-600">ID: <code class="bg-white px-2 py-1 rounded">{{ $subfolder['id'] }}</code></p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button 
                            onclick="copyId('{{ $subfolder['id'] }}', this)"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition-colors"
                        >
                            📋 Copiar ID
                        </button>
                        <a 
                            href="{{ route('google-folders.show', $subfolder['id']) }}"
                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm transition-colors"
                        >
                            👁️ Ver
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Files Section -->
    @if(!empty($documents))
    <div class="bg-white rounded-lg shadow-sm border">
        <div class="p-4 border-b bg-neutral-50">
            <h3 class="text-lg font-semibold text-neutral-900">📄 Arquivos ({{ count($documents) }})</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 gap-4">
                @foreach($documents as $document)
                <div class="flex items-center justify-between p-4 bg-neutral-50 rounded-lg hover:bg-neutral-100 transition-colors">
                    <div class="flex items-center flex-1">
                        <span class="text-2xl mr-4">
                            @php
                                $mimeType = $document['mimeType'] ?? '';
                                if (strpos($mimeType, 'image') !== false) echo '🖼️';
                                elseif (strpos($mimeType, 'pdf') !== false) echo '📕';
                                elseif (strpos($mimeType, 'document') !== false || strpos($mimeType, 'text') !== false) echo '📝';
                                elseif (strpos($mimeType, 'spreadsheet') !== false) echo '📊';
                                elseif (strpos($mimeType, 'presentation') !== false) echo '📊';
                                elseif (strpos($mimeType, 'video') !== false) echo '🎥';
                                elseif (strpos($mimeType, 'audio') !== false) echo '🎵';
                                else echo '📄';
                            @endphp
                        </span>
                        <div class="flex-1">
                            <h4 class="font-medium text-neutral-900">{{ $document['name'] ?? 'Sem nome' }}</h4>
                            <div class="text-sm text-neutral-600">
                                <p>ID: <code class="bg-white px-2 py-1 rounded">{{ $document['id'] }}</code></p>
                                @if(isset($document['size']))
                                    <p>Tamanho: {{ number_format($document['size'] / 1024, 2) }} KB</p>
                                @endif
                                @if(isset($document['modifiedTime']))
                                    <p>Modificado: {{ \Carbon\Carbon::parse($document['modifiedTime'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button 
                            onclick="copyId('{{ $document['id'] }}', this)"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition-colors"
                        >
                            📋 Copiar ID
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Empty State -->
    @if(empty($subfolders) && empty($documents))
    <div class="bg-white rounded-lg shadow-sm border">
        <div class="p-12 text-center">
            <div class="text-neutral-400 text-6xl mb-4">📂</div>
            <h3 class="text-lg font-medium text-neutral-900 mb-2">Pasta Vazia</h3>
            <p class="text-neutral-600">Esta pasta não contém arquivos ou subpastas.</p>
        </div>
    </div>
    @endif
</div>

<script>
function copyId(id, button) {
    navigator.clipboard.writeText(id).then(() => {
        const originalText = button.textContent;
        button.textContent = '✅ Copiado!';
        button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        button.classList.add('bg-green-600');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('bg-green-600');
            button.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }, 2000);
    });
}
</script>
@endsection

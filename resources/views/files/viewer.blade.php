@extends('layouts.dashboard')

@section('title', 'Visualizar Arquivo - ' . $fileName)

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 mb-2">{{ $fileName }}</h1>
                <div class="flex items-center space-x-4 text-sm text-neutral-600">
                    <span>📄 {{ $fileSize }}</span>
                    <span>🎯 {{ $mimeType }}</span>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ $downloadUrl }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download
                </a>
                <button onclick="history.back()" 
                        class="inline-flex items-center px-4 py-2 bg-neutral-600 hover:bg-neutral-700 text-white rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Voltar
                </button>
            </div>
        </div>
    </div>

    <!-- Viewer Container -->
    <div class="bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden">
        <div id="file-viewer" class="min-h-96">
            <!-- Loading -->
            <div id="loading" class="flex items-center justify-center h-96">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    <p class="text-neutral-600">Carregando arquivo...</p>
                </div>
            </div>

            <!-- Content will be loaded here -->
            <div id="content" class="hidden"></div>

            <!-- Error -->
            <div id="error" class="hidden p-8 text-center">
                <div class="text-red-600 mb-4">
                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold mb-2">Erro ao carregar arquivo</h3>
                    <p id="error-message" class="text-neutral-600"></p>
                </div>
                <button onclick="loadFile()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    Tentar novamente
                </button>
            </div>

            <!-- Unsupported File Type -->
            <div id="unsupported" class="hidden p-8 text-center">
                <div class="text-neutral-600 mb-4">
                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold mb-2">Tipo de arquivo não suportado</h3>
                    <p class="mb-4">Este tipo de arquivo não pode ser visualizado diretamente no navegador.</p>
                    <a href="{{ $downloadUrl }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3"></path>
                        </svg>
                        Fazer Download
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const fileId = '{{ $fileId }}';
const viewType = '{{ $viewType }}';
const fileName = '{{ $fileName }}';

function showLoading() {
    document.getElementById('loading').classList.remove('hidden');
    document.getElementById('content').classList.add('hidden');
    document.getElementById('error').classList.add('hidden');
    document.getElementById('unsupported').classList.add('hidden');
}

function showContent() {
    document.getElementById('loading').classList.add('hidden');
    document.getElementById('content').classList.remove('hidden');
    document.getElementById('error').classList.add('hidden');
    document.getElementById('unsupported').classList.add('hidden');
}

function showError(message) {
    document.getElementById('loading').classList.add('hidden');
    document.getElementById('content').classList.add('hidden');
    document.getElementById('error').classList.remove('hidden');
    document.getElementById('unsupported').classList.add('hidden');
    document.getElementById('error-message').textContent = message;
}

function showUnsupported() {
    document.getElementById('loading').classList.add('hidden');
    document.getElementById('content').classList.add('hidden');
    document.getElementById('error').classList.add('hidden');
    document.getElementById('unsupported').classList.remove('hidden');
}

function loadFile() {
    showLoading();

    if (viewType === 'download') {
        showUnsupported();
        return;
    }

    fetch(`/files/${fileId}/content`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showError(data.error);
                return;
            }

            const contentDiv = document.getElementById('content');
            
            switch (data.type) {
                case 'text':
                    contentDiv.innerHTML = `
                        <div class="p-6">
                            <div class="bg-neutral-50 rounded-lg overflow-hidden">
                                <div class="bg-neutral-100 px-4 py-2 border-b border-neutral-200 text-sm font-medium text-neutral-700">
                                    📄 ${fileName}
                                </div>
                                <pre class="p-4 overflow-auto text-sm font-mono whitespace-pre-wrap max-h-96">${escapeHtml(data.content)}</pre>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'image':
                    contentDiv.innerHTML = `
                        <div class="p-6 text-center">
                            <div class="inline-block bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden">
                                <img src="${data.url}" alt="${fileName}" class="max-w-full max-h-96 block" style="max-height: 600px;">
                            </div>
                            <p class="mt-4 text-sm text-neutral-600">${fileName}</p>
                        </div>
                    `;
                    break;
                    
                case 'iframe':
                    contentDiv.innerHTML = `
                        <div class="h-screen max-h-screen">
                            <iframe src="${data.url}" 
                                    class="w-full h-full border-0" 
                                    frameborder="0"
                                    allowfullscreen>
                            </iframe>
                        </div>
                    `;
                    break;
                    
                default:
                    showUnsupported();
                    return;
            }
            
            showContent();
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Erro ao carregar arquivo');
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Carregar arquivo quando a página carregar
document.addEventListener('DOMContentLoaded', loadFile);
</script>
@endsection

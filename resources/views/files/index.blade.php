@extends('layouts.dashboard')


@section('title', 'Arquivos')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Arquivos</h1>
                <p class="text-neutral-600">Gerencie seus arquivos e documentos do BSDrive</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('files.create') }}" 
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-neutral-900 border border-transparent rounded-lg hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500 transition-colors"
                   onclick="console.log('Enviar Arquivos clicked - navigating to files.create');">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Enviar Arquivos
                </a>
                <button onclick="toggleUploadStatus()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span id="statusButtonText">Status Upload</span>
                </button>
                <a href="{{ route('folders.create') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 transition-colors">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                    </svg>
                    Nova Pasta
                </a>
            </div>
        </div>
    </div>

    <!-- Upload Status Panel -->
    <div id="uploadStatusPanel" class="mb-6 bg-white rounded-lg shadow-sm border border-neutral-200 hidden">
        <div class="p-4 border-b border-neutral-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-neutral-900">Status de Upload em Tempo Real</h3>
                <div class="flex items-center space-x-2">
                    <div class="flex items-center space-x-2">
                        <div id="updateIndicator" class="hidden">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                        </div>
                        <span id="lastUpdate" class="text-sm text-neutral-500">Última atualização: --</span>
                    </div>
                    <button onclick="refreshUploadStatus()" class="p-1 text-neutral-400 hover:text-neutral-600 transition-colors" title="Atualizar agora">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="p-4">
            <!-- System Status -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-neutral-50 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-neutral-900">BSDrive</h4>
                            <p class="text-xs text-neutral-500">Configuração do Google Drive</p>
                        </div>
                        <div id="bsdriveStatus" class="flex items-center">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                            <span class="text-sm text-neutral-600">Verificando...</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-neutral-50 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-neutral-900">Service Account</h4>
                            <p class="text-xs text-neutral-500">Arquivo de credenciais</p>
                        </div>
                        <div id="serviceAccountStatus" class="flex items-center">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                            <span class="text-sm text-neutral-600">Verificando...</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-neutral-50 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-neutral-900">Uploads Ativos</h4>
                            <p class="text-xs text-neutral-500">Processos em andamento</p>
                        </div>
                        <div id="activeUploadsStatus" class="flex items-center">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                            <span class="text-sm text-neutral-600">Verificando...</span>
                        </div>
                    </div>
                    <div id="activeUploadsNotification" class="hidden mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-700">
                        <div class="flex items-center">
                            <div class="animate-pulse w-2 h-2 bg-blue-500 rounded-full mr-2"></div>
                            <span>Uploads em andamento - atualizando automaticamente...</span>
                        </div>
                        <div class="mt-2">
                            <div class="w-full bg-blue-200 rounded-full h-2">
                                <div id="uploadProgressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Uploads -->
            <div class="mb-6">
                <h4 class="text-sm font-medium text-neutral-900 mb-3">Uploads Recentes</h4>
                <div id="recentUploads" class="space-y-2">
                    <div class="text-center py-4">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                        <p class="text-sm text-neutral-500 mt-2">Carregando uploads recentes...</p>
                    </div>
                </div>
            </div>

            <!-- System Logs -->
            <div>
                <h4 class="text-sm font-medium text-neutral-900 mb-3">Logs do Sistema</h4>
                <div id="systemLogs" class="bg-neutral-50 rounded-lg p-3 max-h-40 overflow-y-auto">
                    <div class="text-center py-4">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                        <p class="text-sm text-neutral-500 mt-2">Carregando logs...</p>
                    </div>
                </div>
            </div>
            
            <!-- Estatísticas em Tempo Real -->
            <div class="mt-6">
                <h4 class="text-sm font-medium text-neutral-900 mb-3">Estatísticas</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-neutral-50 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-blue-600" id="totalFiles">--</div>
                        <div class="text-xs text-neutral-500">Total de Arquivos</div>
                    </div>
                    <div class="bg-neutral-50 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-green-600" id="totalSize">--</div>
                        <div class="text-xs text-neutral-500">Tamanho Total</div>
                    </div>
                    <div class="bg-neutral-50 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-purple-600" id="todayUploads">--</div>
                        <div class="text-xs text-neutral-500">Uploads Hoje</div>
                    </div>
                    <div class="bg-neutral-50 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-orange-600" id="syncStatus">--</div>
                        <div class="text-xs text-neutral-500">Status Sync</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg" role="alert">
            <div class="flex items-center">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg" role="alert">
            <div class="flex items-center mb-2">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <strong>Erro(s) encontrado(s):</strong>
            </div>
            <ul class="list-disc list-inside ml-7">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('warning'))
        <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg" role="alert">
            <div class="flex items-center">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                {{ session('warning') }}
            </div>
        </div>
    @endif

    <!-- Filters and Search -->
    <div class="bg-white rounded-lg shadow-sm border border-neutral-200 mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('files.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-neutral-700 mb-2">Buscar</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="Buscar por nome..."
                                   class="block w-full pl-10 pr-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500">
                        </div>
                    </div>

                    <!-- Folder Filter -->
                    <div>
                        <label for="folder_id" class="block text-sm font-medium text-neutral-700 mb-2">Pasta</label>
                        <select id="folder_id"
                                name="folder_id"
                                class="block w-full px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500">
                            <option value="">Todas as pastas</option>
                            @foreach($folders as $folder)
                                <option value="{{ $folder['id'] }}" {{ request('folder_id') == $folder['id'] ? 'selected' : '' }}>
                                    {{ $folder['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-neutral-700 mb-2">Tipo</label>
                        <select id="type"
                                name="type"
                                class="block w-full px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500">
                            <option value="">Todos os tipos</option>
                            <option value="images" {{ request('type') === 'images' ? 'selected' : '' }}>Imagens</option>
                            <option value="documents" {{ request('type') === 'documents' ? 'selected' : '' }}>Documentos</option>
                            <option value="videos" {{ request('type') === 'videos' ? 'selected' : '' }}>Vídeos</option>
                            <option value="audio" {{ request('type') === 'audio' ? 'selected' : '' }}>Áudio</option>
                            <option value="archives" {{ request('type') === 'archives' ? 'selected' : '' }}>Arquivos</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-neutral-900 border border-transparent rounded-lg hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500 transition-colors">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Filtrar
                        </button>
                        <a href="{{ route('files.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 transition-colors">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Limpar
                        </a>
                    </div>

                    <!-- Sort -->
                    <div class="flex items-center space-x-2">
                        <label for="sort_by" class="text-sm font-medium text-neutral-700">Ordenar por:</label>
                        <select id="sort_by"
                                name="sort_by"
                                class="text-sm border border-neutral-300 rounded-md px-2 py-1 focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500">
                            <option value="modifiedTime" {{ request('sort_by', 'modifiedTime') === 'modifiedTime' ? 'selected' : '' }}>Data</option>
                            <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Nome</option>
                            <option value="size" {{ request('sort_by') === 'size' ? 'selected' : '' }}>Tamanho</option>
                        </select>
                        <select id="sort_order"
                                name="sort_order"
                                class="text-sm border border-neutral-300 rounded-md px-2 py-1 focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500">
                            <option value="desc" {{ request('sort_order', 'desc') === 'desc' ? 'selected' : '' }}>Decrescente</option>
                            <option value="asc" {{ request('sort_order') === 'asc' ? 'selected' : '' }}>Crescente</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Files Grid -->
    @if(count($files) > 0)
        <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
            <!-- Bulk Actions -->
            <div class="p-4 border-b border-neutral-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center">
                            <input type="checkbox" id="select-all" class="h-4 w-4 text-neutral-600 focus:ring-neutral-500 border-neutral-300 rounded">
                            <span class="ml-2 text-sm text-neutral-700">Selecionar todos</span>
                        </label>
                        <button id="bulk-delete-btn" class="hidden inline-flex items-center px-3 py-1.5 text-sm font-medium text-red-700 bg-red-100 border border-red-200 rounded-md hover:bg-red-200 transition-colors">
                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Excluir Selecionados
                        </button>
                    </div>
                    <div class="text-sm text-neutral-500">
                        {{ count($files) }} arquivo(s) encontrado(s)
                    </div>
                </div>
            </div>

            <!-- Files List -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($files as $file)
                    <div class="group relative bg-neutral-50 rounded-lg border border-neutral-200 hover:border-neutral-300 transition-all duration-200 hover:shadow-md">
                        <!-- File Icon -->
                        <div class="p-6 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-lg mb-4
                                @if(strpos($file['mimeType'], 'image/') === 0) bg-blue-100 text-blue-600
                                @elseif(strpos($file['mimeType'], 'video/') === 0) bg-purple-100 text-purple-600
                                @elseif(strpos($file['mimeType'], 'audio/') === 0) bg-green-100 text-green-600
                                @elseif(in_array($file['mimeType'], ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'text/plain'])) bg-yellow-100 text-yellow-600
                                @elseif(in_array($file['mimeType'], ['application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed', 'application/gzip', 'application/x-tar'])) bg-red-100 text-red-600
                                @else bg-neutral-100 text-neutral-600
                                @endif">
                                @if(strpos($file['mimeType'], 'image/') === 0)
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                @elseif(strpos($file['mimeType'], 'video/') === 0)
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                @elseif(strpos($file['mimeType'], 'audio/') === 0)
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                    </svg>
                                @elseif(in_array($file['mimeType'], ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'text/plain']))
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                @elseif(in_array($file['mimeType'], ['application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed', 'application/gzip', 'application/x-tar']))
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                    </svg>
                                @else
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                @endif
                            </div>

                            <!-- File Info -->
                            <div class="space-y-2">
                                <h3 class="text-sm font-medium text-neutral-900 truncate" title="{{ $file['name'] }}">
                                    {{ $file['name'] }}
                                </h3>
                                <p class="text-xs text-neutral-500">{{ $file['size'] ? number_format($file['size'] / 1024, 1) . ' KB' : 'N/A' }}</p>
                                <p class="text-xs text-neutral-500">{{ strtoupper(pathinfo($file['name'], PATHINFO_EXTENSION)) }}</p>
                                @if(isset($file['parents']) && count($file['parents']) > 0)
                                    <p class="text-xs text-neutral-500">📁 BSDrive</p>
                                @endif
                            </div>
                        </div>

                        <!-- Checkbox for bulk actions -->
                        <div class="absolute top-2 left-2">
                            <input type="checkbox"
                                   class="file-checkbox h-4 w-4 text-neutral-600 focus:ring-neutral-500 border-neutral-300 rounded"
                                   value="{{ $file['id'] }}">
                        </div>

                        <!-- Quick Actions -->
                        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <div class="flex items-center space-x-1">
                                <!-- Visualizar arquivo (todos os tipos) -->
                                <a href="{{ route('files.view', $file['id']) }}"
                                       target="_blank"
                                       class="p-1 text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 rounded transition-colors"
                                       title="Visualizar">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                <button onclick="downloadFile('{{ $file['id'] }}', '{{ $file['name'] }}')"
                                        class="p-1 text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 rounded transition-colors"
                                        title="Baixar (Redirect)">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </button>
                                <a href="{{ route('files.download', $file['id']) }}"
                                   class="p-1 text-blue-600 hover:text-blue-900 hover:bg-blue-100 rounded transition-colors"
                                   title="Baixar (Direto)">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                    </svg>
                                </a>
                                <button onclick="testDownload('{{ $file['id'] }}', '{{ $file['name'] }}')"
                                        class="p-1 text-green-600 hover:text-green-900 hover:bg-green-100 rounded transition-colors"
                                        title="Testar Download">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </button>
                                <button onclick="debugDownload('{{ $file['id'] }}', '{{ $file['name'] }}')"
                                        class="p-1 text-purple-600 hover:text-purple-900 hover:bg-purple-100 rounded transition-colors"
                                        title="Debug Download">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- File Actions -->
                        <div class="p-4 border-t border-neutral-200 bg-white rounded-b-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('files.view', $file['id']) }}"
                                       class="text-sm text-blue-600 hover:text-blue-800 transition-colors font-medium">
                                        👁️ Ver
                                    </a>
                                    <a href="{{ route('files.show', $file['id']) }}"
                                       class="text-sm text-neutral-600 hover:text-neutral-900 transition-colors">
                                        Detalhes
                                    </a>
                                    <a href="{{ route('files.download', $file['id']) }}"
                                       class="text-sm text-green-600 hover:text-green-800 transition-colors font-medium">
                                        📥 Baixar
                                    </a>
                                    <a href="{{ route('files.edit', $file['id']) }}"
                                       class="text-sm text-neutral-600 hover:text-neutral-900 transition-colors">
                                        Editar
                                    </a>
                                </div>
                                <button onclick="openDeleteModal('{{ $file['id'] }}', '{{ $file['name'] }}')"
                                        class="text-sm text-red-600 hover:text-red-800 transition-colors">
                                    Excluir
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
            <div class="p-12 text-center">
                <svg class="h-16 w-16 text-neutral-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <h3 class="text-lg font-medium text-neutral-900 mb-2">Nenhum arquivo encontrado</h3>
                <p class="text-neutral-500 mb-6">
                    @if(request('search') || request('folder_id') || request('type'))
                        Tente ajustar os filtros ou
                    @endif
                    Comece enviando seu primeiro arquivo para o BSDrive.
                </p>
                <a href="{{ route('files.create') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-neutral-900 border border-transparent rounded-lg hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500 transition-colors">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Enviar Primeiro Arquivo
                </a>
            </div>
        </div>
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-neutral-200">
                <div class="flex items-center">
                    <div class="h-10 w-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-neutral-900">Confirmar Exclusão</h3>
                        <p class="text-sm text-neutral-500">Esta ação não pode ser desfeita</p>
                    </div>
                </div>
                <button onclick="closeDeleteModal()" class="text-neutral-400 hover:text-neutral-600 transition-colors">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <div class="flex items-start">
                    <div class="h-12 w-12 bg-red-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-base font-medium text-neutral-900 mb-2">Excluir Arquivo</h4>
                        <p class="text-sm text-neutral-600 mb-4">
                            Tem certeza que deseja excluir o arquivo <span class="font-semibold text-neutral-900" id="fileName"></span>?
                        </p>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-red-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-red-800">Atenção</p>
                                    <p class="text-sm text-red-700 mt-1">
                                        Esta ação irá excluir permanentemente o arquivo do BSDrive.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end space-x-3 p-6 border-t border-neutral-200 bg-neutral-50 rounded-b-lg">
                <button onclick="closeDeleteModal()" class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 transition-colors">
                    Cancelar
                </button>
                <form id="deleteForm" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Excluir Arquivo
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Delete Form -->
<form id="bulkDeleteForm" method="POST" action="{{ route('files.bulk-delete') }}" class="hidden">
    @csrf
    <input type="hidden" name="file_ids" id="bulkDeleteIds">
</form>

<!-- Bulk Delete Confirmation Modal -->
<div id="bulkDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full transform transition-all duration-300 scale-95 opacity-0" id="bulkModalContent">
            <div class="flex items-center justify-between p-6 border-b border-neutral-200">
                <div class="flex items-center">
                    <div class="h-10 w-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-neutral-900">Confirmar Exclusão em Massa</h3>
                        <p class="text-sm text-neutral-500">Esta ação não pode ser desfeita</p>
                    </div>
                </div>
                <button onclick="closeBulkDeleteModal()" class="text-neutral-400 hover:text-neutral-600 transition-colors">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                <div class="flex items-start">
                    <div class="h-12 w-12 bg-red-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-base font-medium text-neutral-900 mb-2">Excluir Arquivos Selecionados</h4>
                        <p class="text-sm text-neutral-600 mb-4">
                            Tem certeza que deseja excluir <span class="font-semibold text-neutral-900" id="bulkFileCount"></span> arquivo(s) selecionado(s)?
                        </p>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-red-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-red-800">Atenção</p>
                                    <p class="text-sm text-red-700 mt-1">
                                        Esta ação irá excluir permanentemente os arquivos do BSDrive.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end space-x-3 p-6 border-t border-neutral-200 bg-neutral-50 rounded-b-lg">
                <button onclick="closeBulkDeleteModal()" class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 transition-colors">
                    Cancelar
                </button>
                <button type="button" onclick="confirmBulkDelete()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Excluir Arquivos
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bulk actions
    const selectAllCheckbox = document.getElementById('select-all');
    const fileCheckboxes = document.querySelectorAll('.file-checkbox');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');
    const bulkDeleteIds = document.getElementById('bulkDeleteIds');

    // Limpar intervalo quando a página for fechada
    window.addEventListener('beforeunload', function() {
        stopStatusUpdates();
    });

    // Select all functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            fileCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkDeleteButton();
        });
    }

    // Individual checkbox functionality
    fileCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkDeleteButton();

            // Update select all checkbox
            if (selectAllCheckbox) {
                const checkedBoxes = document.querySelectorAll('.file-checkbox:checked');
                const totalBoxes = fileCheckboxes.length;
                selectAllCheckbox.checked = checkedBoxes.length === totalBoxes;
                selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < totalBoxes;
            }
        });
    });

    // Bulk delete functionality
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            const checkedBoxes = document.querySelectorAll('.file-checkbox:checked');
            const fileIds = Array.from(checkedBoxes).map(checkbox => checkbox.value);
            
            const bulkFileCount = document.getElementById('bulkFileCount');
            if (bulkFileCount) {
                bulkFileCount.textContent = fileIds.length;
            }
            
            const bulkDeleteModal = document.getElementById('bulkDeleteModal');
            if (bulkDeleteModal) {
                bulkDeleteModal.classList.remove('hidden');
                setTimeout(() => {
                    const bulkModalContent = document.getElementById('bulkModalContent');
                    if (bulkModalContent) {
                        bulkModalContent.classList.remove('scale-95', 'opacity-0');
                        bulkModalContent.classList.add('scale-100', 'opacity-100');
                    }
                }, 10);
            }
        });
    }

    // Fechar modal ao clicar fora
    const bulkDeleteModal = document.getElementById('bulkDeleteModal');
    if (bulkDeleteModal) {
        bulkDeleteModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeBulkDeleteModal();
            }
        });
    }

    // Close modal when clicking outside
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    }

    function updateBulkDeleteButton() {
        const checkedBoxes = document.querySelectorAll('.file-checkbox:checked');
        if (bulkDeleteBtn) {
            if (checkedBoxes.length > 0) {
                bulkDeleteBtn.classList.remove('hidden');
                bulkDeleteBtn.textContent = `Excluir ${checkedBoxes.length} Selecionado${checkedBoxes.length > 1 ? 's' : ''}`;
            } else {
                bulkDeleteBtn.classList.add('hidden');
            }
        }
    }

    // Expose functions globally
    window.updateBulkDeleteButton = updateBulkDeleteButton;
});

// Global functions that need to be accessible from HTML
function closeBulkDeleteModal() {
    const bulkModalContent = document.getElementById('bulkModalContent');
    if (bulkModalContent) {
        bulkModalContent.classList.remove('scale-100', 'opacity-100');
        bulkModalContent.classList.add('scale-95', 'opacity-0');
    }
    
    setTimeout(() => {
        const bulkDeleteModal = document.getElementById('bulkDeleteModal');
        if (bulkDeleteModal) {
            bulkDeleteModal.classList.add('hidden');
        }
    }, 300);
}

function confirmBulkDelete() {
    const checkedBoxes = document.querySelectorAll('.file-checkbox:checked');
    const fileIds = Array.from(checkedBoxes).map(checkbox => checkbox.value);
    const bulkDeleteIds = document.getElementById('bulkDeleteIds');
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');
    
    if (bulkDeleteIds && bulkDeleteForm) {
        bulkDeleteIds.value = JSON.stringify(fileIds);
        bulkDeleteForm.submit();
    }
}

// Delete modal functionality
function openDeleteModal(fileId, fileName) {
    const fileNameElement = document.getElementById('fileName');
    const deleteForm = document.getElementById('deleteForm');
    const deleteModal = document.getElementById('deleteModal');
    
    if (fileNameElement) {
        fileNameElement.textContent = fileName;
    }
    if (deleteForm) {
        deleteForm.action = `/files/${fileId}`;
    }
    if (deleteModal) {
        deleteModal.classList.remove('hidden');
        
        // Animate modal
        setTimeout(() => {
            const modalContent = document.getElementById('modalContent');
            if (modalContent) {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }
        }, 10);
    }
}

function closeDeleteModal() {
    const modalContent = document.getElementById('modalContent');
    if (modalContent) {
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
    }

    setTimeout(() => {
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.classList.add('hidden');
        }
    }, 300);
}

// Função para testar download com logs
function testDownload(fileId, fileName) {
    console.log('Testing download for file:', fileId, fileName);
    
    // Fazer requisição AJAX para obter o link de download
    fetch(`/files/${fileId}/download-direct`)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Download response data:', data);
            
            if (data.success && data.download_link) {
                console.log('Opening download link:', data.download_link);
                
                // Criar um link temporário e clicar nele
                const link = document.createElement('a');
                link.href = data.download_link;
                link.download = data.file_name || 'download';
                link.target = '_blank';
                
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                console.log('Download link clicked successfully');
            } else {
                console.error('Invalid response data:', data);
                alert('Erro na resposta do servidor: ' + (data.error || 'Dados inválidos'));
            }
        })
        .catch(error => {
            console.error('Download test error:', error);
            alert('Erro ao testar download: ' + error.message);
        });
}

// Função melhorada para download direto via link
function downloadFile(fileId, fileName) {
    console.log('Downloading file:', fileId, fileName);
    
    // Abrir diretamente o link de download em nova aba
    const downloadUrl = `/files/${fileId}/download`;
    console.log('Opening download URL:', downloadUrl);
    
    window.open(downloadUrl, '_blank');
}

// Função para mostrar notificação de upload em andamento
function showUploadNotification() {
    const notification = document.createElement('div');
    notification.id = 'uploadNotification';
    notification.innerHTML = `
        <div class="fixed top-4 right-4 bg-blue-500 text-white px-6 py-4 rounded-lg shadow-lg z-50">
            <div class="flex items-center">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-3"></div>
                <span>Enviando arquivo(s) para o BSDrive...</span>
            </div>
        </div>
    `;
    document.body.appendChild(notification);
}

// Função para esconder notificação de upload
function hideUploadNotification() {
    const notification = document.getElementById('uploadNotification');
    if (notification) {
        notification.remove();
    }
}

// Função para mostrar notificação de upload concluído
function showUploadSuccessNotification() {
    const notification = document.createElement('div');
    notification.id = 'uploadSuccessNotification';
    notification.innerHTML = `
        <div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50">
            <div class="flex items-center">
                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Upload concluído com sucesso!</span>
            </div>
        </div>
    `;
    document.body.appendChild(notification);
    
    // Remover após 5 segundos
    setTimeout(() => {
        if (notification) {
            notification.remove();
        }
    }, 5000);
}

// Função para atualizar barra de progresso
function updateUploadProgress(progress) {
    const progressBar = document.getElementById('uploadProgressBar');
    if (progressBar) {
        progressBar.style.width = `${progress}%`;
        
        // Mudar cor baseado no progresso
        if (progress >= 100) {
            progressBar.classList.remove('bg-blue-600');
            progressBar.classList.add('bg-green-600');
        } else if (progress >= 50) {
            progressBar.classList.remove('bg-blue-600');
            progressBar.classList.add('bg-yellow-600');
        }
    }
}

// Função para atualizar estatísticas
function updateStatistics(stats) {
    const totalFiles = document.getElementById('totalFiles');
    const totalSize = document.getElementById('totalSize');
    const todayUploads = document.getElementById('todayUploads');
    const syncStatus = document.getElementById('syncStatus');
    
    if (totalFiles) totalFiles.textContent = stats.total_files || '0';
    if (totalSize) totalSize.textContent = stats.total_size_formatted || '0 MB';
    if (todayUploads) todayUploads.textContent = stats.today_uploads || '0';
    if (syncStatus) syncStatus.textContent = `${stats.sync_percentage || 0}%`;
}

// Interceptar submissões de formulário de upload
document.addEventListener('DOMContentLoaded', function() {
    const uploadForms = document.querySelectorAll('form[action*="files"]');
    uploadForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Verificar se há arquivos sendo enviados
            const fileInputs = form.querySelectorAll('input[type="file"]');
            let hasFiles = false;
            
            fileInputs.forEach(input => {
                if (input.files && input.files.length > 0) {
                    hasFiles = true;
                }
            });
            
            if (hasFiles) {
                console.log('Upload started - showing notification and status panel');
                showUploadNotification();
                
                // Mostrar painel de status automaticamente se não estiver visível
                if (!isStatusPanelVisible) {
                    toggleUploadStatus();
                }
                
                // Iniciar atualizações mais frequentes durante upload
                if (statusUpdateInterval) {
                    clearInterval(statusUpdateInterval);
                    statusUpdateInterval = setInterval(() => {
                        if (isStatusPanelVisible) {
                            refreshUploadStatus();
                        }
                    }, 2000); // Atualizar a cada 2 segundos durante upload
                }
                
                // Simular progresso de upload
                let progress = 0;
                const progressInterval = setInterval(() => {
                    progress += Math.random() * 15;
                    if (progress > 90) progress = 90; // Parar em 90% até confirmar conclusão
                    updateUploadProgress(progress);
                }, 500);
                
                // Armazenar intervalo de progresso para limpeza posterior
                window.progressInterval = progressInterval;
            }
        });
    });
    
    // Auto-hide success/error messages after 5 seconds
    const alertMessages = document.querySelectorAll('[role="alert"]');
    alertMessages.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

// Função para debug do download - mostra informações detalhadas
function debugDownload(fileId, fileName) {
    console.log('Debug download for file:', fileId, fileName);
    
    fetch(`/files/${fileId}/test-download`)
        .then(response => {
            console.log('Debug response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Debug response data:', data);
            
            // Criar modal ou alert com informações
            const info = JSON.stringify(data, null, 2);
            
            // Criar div para mostrar informações
            const infoDiv = document.createElement('div');
            infoDiv.innerHTML = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white p-6 rounded-lg max-w-2xl max-h-96 overflow-auto">
                        <h3 class="text-lg font-bold mb-4">Debug Download - ${fileName}</h3>
                        <pre class="text-sm bg-gray-100 p-4 rounded overflow-auto">${info}</pre>
                        <button onclick="this.closest('div').remove()" 
                                class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            Fechar
                        </button>
                        ${data.download_link ? `
                        <button onclick="window.open('${data.download_link}', '_blank')" 
                                class="mt-4 ml-2 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                            Tentar Download
                        </button>
                        ` : ''}
                    </div>
                </div>
            `;
            
            document.body.appendChild(infoDiv);
        })
        .catch(error => {
            console.error('Debug download error:', error);
            alert('Erro no debug: ' + error.message);
        });
}

// Variáveis globais para controle do status em tempo real
let statusUpdateInterval = null;
let isStatusPanelVisible = false;

// Função para alternar visibilidade do painel de status
function toggleUploadStatus() {
    const panel = document.getElementById('uploadStatusPanel');
    const buttonText = document.getElementById('statusButtonText');
    
    if (panel.classList.contains('hidden')) {
        // Mostrar painel e iniciar atualizações
        panel.classList.remove('hidden');
        buttonText.textContent = 'Ocultar Status';
        isStatusPanelVisible = true;
        startStatusUpdates();
    } else {
        // Ocultar painel e parar atualizações
        panel.classList.add('hidden');
        buttonText.textContent = 'Status Upload';
        isStatusPanelVisible = false;
        stopStatusUpdates();
    }
}

// Função para iniciar atualizações automáticas
function startStatusUpdates() {
    if (statusUpdateInterval) {
        clearInterval(statusUpdateInterval);
    }
    
    // Primeira atualização imediata
    refreshUploadStatus();
    
    // Atualizações a cada 5 segundos
    statusUpdateInterval = setInterval(() => {
        if (isStatusPanelVisible) {
            refreshUploadStatus();
        }
    }, 5000);
}

// Função para parar atualizações automáticas
function stopStatusUpdates() {
    if (statusUpdateInterval) {
        clearInterval(statusUpdateInterval);
        statusUpdateInterval = null;
    }
}

// Função para atualizar o status de upload
function refreshUploadStatus() {
    console.log('Refreshing upload status...');
    
    // Mostrar indicador de atualização
    const updateIndicator = document.getElementById('updateIndicator');
    if (updateIndicator) {
        updateIndicator.classList.remove('hidden');
    }
    
    fetch('/files/upload-status')
        .then(response => response.json())
        .then(data => {
            console.log('Upload status data:', data);
            updateStatusDisplay(data);
        })
        .catch(error => {
            console.error('Upload status error:', error);
            updateStatusDisplay({ error: error.message });
        })
        .finally(() => {
            // Ocultar indicador de atualização
            if (updateIndicator) {
                updateIndicator.classList.add('hidden');
            }
        });
}

// Função para atualizar a exibição do status
function updateStatusDisplay(data) {
    const lastUpdate = document.getElementById('lastUpdate');
    const now = new Date();
    lastUpdate.textContent = `Última atualização: ${now.toLocaleTimeString()}`;
    
    if (data.error) {
        // Mostrar erro
        updateStatusItem('bsdriveStatus', 'error', 'Erro de conexão');
        updateStatusItem('serviceAccountStatus', 'error', 'Erro de conexão');
        updateStatusItem('activeUploadsStatus', 'error', 'Erro de conexão');
        return;
    }
    
    // Verificar se há uploads ativos e ajustar intervalo de atualização
    const activeUploads = data.active_uploads || 0;
    const previousActiveUploads = window.previousActiveUploads || 0;
    
    // Se havia uploads ativos e agora não há, mostrar notificação de sucesso
    if (previousActiveUploads > 0 && activeUploads === 0) {
        showUploadSuccessNotification();
        hideUploadNotification(); // Ocultar notificação de upload em andamento
        
        // Completar barra de progresso e limpar intervalo
        updateUploadProgress(100);
        if (window.progressInterval) {
            clearInterval(window.progressInterval);
            window.progressInterval = null;
        }
    }
    
    // Armazenar valor atual para próxima verificação
    window.previousActiveUploads = activeUploads;
    
    if (activeUploads === 0 && statusUpdateInterval) {
        // Se não há uploads ativos, voltar ao intervalo normal (5 segundos)
        clearInterval(statusUpdateInterval);
        statusUpdateInterval = setInterval(() => {
            if (isStatusPanelVisible) {
                refreshUploadStatus();
            }
        }, 5000);
    }
    
    // Atualizar status do BSDrive
    updateStatusItem('bsdriveStatus', 
        data.google_drive_configured ? 'success' : 'error',
        data.google_drive_configured ? 'Configurado' : 'Não configurado'
    );
    
    // Atualizar status do Service Account
    updateStatusItem('serviceAccountStatus',
        data.service_account_file_exists ? 'success' : 'error',
        data.service_account_file_exists ? 'Arquivo existe' : 'Arquivo não encontrado'
    );
    
    // Atualizar status de uploads ativos
    updateStatusItem('activeUploadsStatus',
        activeUploads > 0 ? 'warning' : 'success',
        activeUploads > 0 ? `${activeUploads} ativo(s)` : 'Nenhum ativo'
    );
    
    // Mostrar/ocultar notificação de uploads ativos
    const notification = document.getElementById('activeUploadsNotification');
    if (notification) {
        if (activeUploads > 0) {
            notification.classList.remove('hidden');
        } else {
            notification.classList.add('hidden');
        }
    }
    
    // Atualizar uploads recentes
    updateRecentUploads(data.recent_uploads || []);
    
    // Atualizar logs do sistema
    updateSystemLogs(data.recent_logs || []);
    
    // Verificar se há novos arquivos (comparar com a lista anterior)
    const currentUploads = data.recent_uploads || [];
    const previousUploads = window.previousUploads || [];
    
    if (currentUploads.length > previousUploads.length) {
        // Há novos uploads - atualizar mais frequentemente
        if (statusUpdateInterval) {
            clearInterval(statusUpdateInterval);
            statusUpdateInterval = setInterval(() => {
                if (isStatusPanelVisible) {
                    refreshUploadStatus();
                }
            }, 1000); // Atualizar a cada 1 segundo quando há novos uploads
        }
    }
    
    // Armazenar lista atual para próxima comparação
    window.previousUploads = currentUploads;
    
    // Atualizar estatísticas
    updateStatistics(data.statistics || {});
}

// Função para atualizar um item de status específico
function updateStatusItem(elementId, status, text) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    // Remover classes anteriores
    element.innerHTML = '';
    
    let icon, colorClass;
    
    switch (status) {
        case 'success':
            icon = `<svg class="h-4 w-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>`;
            colorClass = 'text-green-600';
            break;
        case 'error':
            icon = `<svg class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>`;
            colorClass = 'text-red-600';
            break;
        case 'warning':
            icon = `<svg class="h-4 w-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>`;
            colorClass = 'text-yellow-600';
            break;
        default:
            icon = `<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>`;
            colorClass = 'text-neutral-600';
    }
    
    element.innerHTML = `
        <div class="flex items-center">
            ${icon}
            <span class="text-sm ${colorClass}">${text}</span>
                            </div>
    `;
}

// Função para atualizar uploads recentes
function updateRecentUploads(uploads) {
    const container = document.getElementById('recentUploads');
    if (!container) return;
    
    if (uploads.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <svg class="h-8 w-8 text-neutral-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <p class="text-sm text-neutral-500">Nenhum upload recente</p>
            </div>
        `;
        return;
    }
    
    const uploadsHtml = uploads.map(upload => {
        const statusIcon = upload.status === 'completed' ? 
            '<svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' :
            upload.status === 'failed' ?
            '<svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>' :
            '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>';
        
        const statusColor = upload.status === 'completed' ? 'text-green-600' : 
                           upload.status === 'failed' ? 'text-red-600' : 'text-blue-600';
        
        return `
            <div class="flex items-center justify-between p-3 bg-neutral-50 rounded-lg">
                <div class="flex items-center space-x-3">
                    ${statusIcon}
                            <div>
                        <p class="text-sm font-medium text-neutral-900">${upload.filename}</p>
                        <p class="text-xs text-neutral-500">${upload.size || 'N/A'} • ${upload.timestamp}</p>
                            </div>
                        </div>
                <span class="text-xs ${statusColor} font-medium">${upload.status}</span>
                        </div>
        `;
    }).join('');
    
    container.innerHTML = uploadsHtml;
}

// Função para atualizar logs do sistema
function updateSystemLogs(logs) {
    const container = document.getElementById('systemLogs');
    if (!container) return;
    
    if (logs.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <p class="text-sm text-neutral-500">Nenhum log disponível</p>
                </div>
            `;
        return;
    }
    
    const logsHtml = logs.map(log => `
        <div class="text-xs text-neutral-600 mb-1">
            <span class="text-neutral-400">[${log.timestamp}]</span> ${log.message}
        </div>
    `).join('');
    
    container.innerHTML = logsHtml;
    
    // Auto-scroll para o final
    container.scrollTop = container.scrollHeight;
}

// Função para verificar status de uploads (mantida para compatibilidade)
function checkUploadStatus() {
    if (!isStatusPanelVisible) {
        toggleUploadStatus();
    }
    refreshUploadStatus();
}
</script>
@endsection

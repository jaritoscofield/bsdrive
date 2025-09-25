@extends('layouts.dashboard')

@section('title', 'Dashboard - BSDrive')

@section('content')
@php
    use Carbon\Carbon;
    Carbon::setLocale('pt_BR');
    setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'Portuguese_Brazil.1252');
    $user = Auth::user();
    $totalFiles = 0;
    $totalFolders = 0;
    $totalSize = 0;
    $lastAccess = null;
    $status = 'Ativo';
    $recentActivities = [];
    $hasGoogleDriveError = false;

    $accessibleFolderIds = $user->getAccessibleFolderIds();
    if (!empty($accessibleFolderIds) && !isset($hasError)) {
        try {
            $driveService = app(\App\Services\GoogleDriveService::class);
            $allFiles = [];
            $allFolders = [];
            foreach (
                array_filter($accessibleFolderIds, fn($id) => !empty($id) && $id !== '.' && $id !== '') as $folderId
            ) {
                $files = $driveService->listFiles($folderId, 'files(id,name,mimeType,size,createdTime,modifiedTime,parents)');
                foreach ($files as $file) {
                    if (isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder') {
                        $allFolders[] = $file;
                        $recentActivities[] = [
                            'type' => 'folder',
                            'name' => $file['name'] ?? '-',
                            'action' => 'Pasta criada',
                            'date' => $file['createdTime'] ?? null,
                            'icon' => 'folder',
                        ];
                    } else {
                        $allFiles[] = $file;
                        $recentActivities[] = [
                            'type' => 'file',
                            'name' => $file['name'] ?? '-',
                            'action' => 'Arquivo enviado',
                            'date' => $file['createdTime'] ?? null,
                            'icon' => 'file',
                        ];
                    }
                }
            }
            $totalFiles = count($allFiles);
            $totalFolders = count($allFolders);
            // Último acesso: arquivo mais recente
            if (!empty($allFiles)) {
                usort($allFiles, function($a, $b) {
                    return strtotime($b['modifiedTime']) <=> strtotime($a['modifiedTime']);
                });
                $lastAccess = $allFiles[0]['modifiedTime'];
            }
            // Ordenar atividades por data (mais recente primeiro)
            usort($recentActivities, function($a, $b) {
                return strtotime($b['date']) <=> strtotime($a['date']);
            });
            // Limitar para mostrar só as 5 mais recentes
            $recentActivities = array_slice($recentActivities, 0, 5);
        } catch (\Exception $e) {
            $hasGoogleDriveError = true;
            Log::error('Erro no dashboard blade: ' . $e->getMessage());
        }
    }
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return number_format($bytes, $precision, ',', '.') . ' ' . $units[$pow];
    }
@endphp
<div class="p-6">
    <!-- Welcome Section -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-neutral-900 mb-2">Bem-vindo, {{ auth()->user()->name }}!</h2>
                <p class="text-neutral-600">Gerencie seus arquivos e pastas de forma segura e organizada.</p>
            </div>
            @if(auth()->user()->role === 'admin_sistema')
            <div>
                <a href="{{ route('google.setup') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center">
                    <span class="mr-2">🔧</span>
                    Configurar BSDrive
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- Google Drive Error Alert -->
    @if(isset($hasError))
    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-center">
            <span class="text-yellow-600 mr-3">⚠️</span>
            <div>
                <h3 class="text-yellow-800 font-medium">Problemas Temporários</h3>
                <p class="text-yellow-700 text-sm mt-1">
                    Algumas funcionalidades podem estar temporariamente indisponíveis. Tente recarregar a página.
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total de Arquivos -->
        <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
            <svg class="w-7 h-7 text-blue-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <div class="text-xl font-bold text-neutral-900">{{ number_format($totalFiles) }}</div>
            <div class="text-neutral-500 mt-1 text-xs font-semibold">Total de Arquivos</div>
        </div>
        <!-- Total de Pastas -->
        <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
            <svg class="w-7 h-7 text-green-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-5l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
            <div class="text-xl font-bold text-neutral-900">{{ number_format($totalFolders) }}</div>
            <div class="text-neutral-500 mt-1 text-xs font-semibold">Total de Pastas</div>
        </div>
        <!-- Espaço Utilizado -->
        <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
            <svg class="w-7 h-7 text-yellow-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12a6 6 0 1112 0 6 6 0 01-12 0z"/>
            </svg>
            <div class="text-xl font-bold text-neutral-900">{{ formatBytes($totalSize) }}</div>
            <div class="text-neutral-500 mt-1 text-xs font-semibold">Espaço Utilizado</div>
        </div>
        <!-- Último Acesso -->
        <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
            <svg class="w-7 h-7 text-purple-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"/>
            </svg>
            <div class="text-lg font-bold text-neutral-900">
                @if($lastAccess)
                    {{ Carbon::parse($lastAccess)->timezone(config('app.timezone'))->diffForHumans(null, null, false, 2) }}
                @else
                    Nunca
                @endif
            </div>
            <div class="text-neutral-500 mt-1 text-xs font-semibold">Último Acesso</div>
        </div>
    </div>
    <div class="flex items-center space-x-4 mb-8">
        <span class="inline-block px-3 py-1 rounded-full bg-green-100 text-green-800 text-xs font-semibold">Status: {{ $status }}</span>
    </div>

    <!-- Notificação sobre Shared Drives -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            @if(auth()->user()->role === 'admin_sistema')
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">
                    ✅ Shared Drives Desabilitados
                </h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>O sistema foi configurado para <strong>não usar Shared Drives</strong>. Todos os uploads são feitos diretamente para o Drive Pessoal da Service Account.</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Quick Actions Card -->
        <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-6">
            <h3 class="text-lg font-semibold text-neutral-900 mb-4">Ações Rápidas</h3>
            <div class="space-y-3">
                @if(auth()->user()->role === 'admin_sistema')
                    <a href="#" class="flex items-center p-3 rounded-lg border border-neutral-200 hover:bg-neutral-50 transition-colors">
                        <div class="h-8 w-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-neutral-900">Criar Nova Empresa</p>
                            <p class="text-sm text-neutral-600">Adicionar uma nova empresa ao sistema</p>
                        </div>
                    </a>
                @endif

                @if(in_array(auth()->user()->role, ['admin_sistema', 'admin_empresa']))
                    <a href="#" class="flex items-center p-3 rounded-lg border border-neutral-200 hover:bg-neutral-50 transition-colors">
                        <div class="h-8 w-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-neutral-900">Gerenciar Usuários</p>
                            <p class="text-sm text-neutral-600">Adicionar ou editar usuários</p>
                        </div>
                    </a>
                @endif

                <!-- Link para visualizar pastas do Google Drive - APENAS para usuário ID = 1 -->
                @if(auth()->id() === 1)
                <a href="{{ route('google-folders.index') }}" class="flex items-center p-3 rounded-lg border border-neutral-200 hover:bg-neutral-50 transition-colors">
                    <div class="h-8 w-8 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                        <span class="text-yellow-600 text-lg">📁</span>
                    </div>
                    <div>
                        <p class="font-medium text-neutral-900">Visualizar Pastas do Google Drive</p>
                        <p class="text-sm text-neutral-600">Ver todas as pastas e seus IDs (Admin Only)</p>
                    </div>
                </a>
                @endif

                @if(in_array(auth()->user()->role, ['admin_empresa', 'usuario']))
                    <a href="#" class="flex items-center p-3 rounded-lg border border-neutral-200 hover:bg-neutral-50 transition-colors">
                        <div class="h-8 w-8 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-neutral-900">Enviar Arquivo</p>
                            <p class="text-sm text-neutral-600">Fazer upload de novos arquivos</p>
                        </div>
                    </a>
                @endif

                <a href="#" class="flex items-center p-3 rounded-lg border border-neutral-200 hover:bg-neutral-50 transition-colors">
                    <div class="h-8 w-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-neutral-900">Buscar Arquivos</p>
                        <p class="text-sm text-neutral-600">Encontrar arquivos rapidamente</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-lg font-semibold text-neutral-900 mb-4">Atividade Recente</h2>
            <ul class="divide-y divide-neutral-200">
                @forelse($recentActivities as $activity)
                    <li class="py-3 flex items-center space-x-4">
                        @if($activity['icon'] === 'folder')
                            <svg class="w-5 h-5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-5l-2-2H5a2 2 0 00-2 2z"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        @endif
                        <div class="flex-1">
                            <div class="font-medium text-neutral-900">{{ $activity['name'] }}</div>
                            <div class="text-xs text-neutral-500">{{ $activity['action'] }}</div>
                        </div>
                        <div class="text-xs text-neutral-400">
                            {{ $activity['date'] ? Carbon::parse($activity['date'])->timezone(config('app.timezone'))->diffForHumans(null, null, false, 2) : '' }}
                        </div>
                    </li>
                @empty
                    <li class="py-3 text-neutral-500">Nenhuma atividade recente encontrada.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection

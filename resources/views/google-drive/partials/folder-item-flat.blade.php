<div class="folder-item-flat border border-neutral-200 rounded-lg p-3 mb-2 hover:bg-neutral-50 transition-colors">
    <div class="flex items-center justify-between">
        <div class="flex items-center flex-1">
            <span class="mr-3 text-lg">
                @if($folder['level'] == 0)
                    🏠
                @elseif($folder['level'] == 1)
                    📁
                @elseif($folder['level'] == 2)
                    📂
                @else
                    📄
                @endif
            </span>

            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <h4 class="folder-name font-medium text-neutral-900">
                        {{ $folder['name'] }}
                    </h4>
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                        Nível {{ $folder['level'] }}
                    </span>
                </div>
                
                <div class="text-xs text-neutral-600">
                    <p><strong>ID:</strong> 
                        <code class="folder-id bg-neutral-100 px-1 rounded font-mono">{{ $folder['id'] }}</code>
                    </p>
                    @if(isset($folder['path']))
                        <p><strong>Caminho:</strong> {{ $folder['path'] }}</p>
                    @endif
                    @if($folder['createdTime'])
                        <p><strong>Criada:</strong> {{ \Carbon\Carbon::parse($folder['createdTime'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-1 ml-3">
            <button 
                onclick="copyId('{{ $folder['id'] }}', this)"
                class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs font-medium transition-colors"
                title="Copiar ID"
            >
                📋
            </button>
            
            <a 
                href="{{ route('google-folders.show', $folder['id']) }}"
                class="bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-xs font-medium transition-colors"
                title="Ver detalhes"
            >
                👁️
            </a>
        </div>
    </div>
</div>

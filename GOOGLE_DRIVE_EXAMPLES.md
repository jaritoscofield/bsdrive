# Exemplos de Uso - Integração Google Drive

## Exemplos de Comandos cURL

### 1. Listar Arquivos de uma Pasta Específica

```bash
curl --location 'https://www.googleapis.com/drive/v3/files?q=%2711lq8_FNe7sqkAWlmrR-u3GkJZIuQsE_l%27+in+parents&key=SUA_API_KEY&fields=files(id%2Cname%2CmimeType)'
```

**Explicação:**
- `q='11lq8_FNe7sqkAWlmrR-u3GkJZIuQsE_l' in parents`: Busca arquivos dentro da pasta com ID especificado
- `key=SUA_API_KEY`: Sua chave da API do Google
- `fields=files(id,name,mimeType)`: Retorna apenas os campos especificados

### 2. Listar Todos os Arquivos

```bash
curl --location 'https://www.googleapis.com/drive/v3/files?key=SUA_API_KEY&fields=files(id,name,mimeType,size,createdTime,modifiedTime,parents)'
```

### 3. Buscar Arquivo por ID

```bash
curl --location 'https://www.googleapis.com/drive/v3/files/ARQUIVO_ID?key=SUA_API_KEY&fields=id,name,mimeType,size,webContentLink'
```

### 4. Criar Nova Pasta

```bash
curl --location 'https://www.googleapis.com/drive/v3/files' \
--header 'Authorization: Bearer SEU_TOKEN' \
--header 'Content-Type: application/json' \
--data '{
  "name": "Nova Pasta",
  "mimeType": "application/vnd.google-apps.folder"
}'
```

### 5. Fazer Upload de Arquivo

```bash
curl --location 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart' \
--header 'Authorization: Bearer SEU_TOKEN' \
--form 'metadata={"name":"arquivo.txt","parents":["PASTA_ID"]};type=application/json' \
--form 'file=@"/caminho/para/arquivo.txt"'
```

## Exemplos de Uso no Sistema

### 1. Sincronização Manual via Interface Web

1. Acesse `/google-drive` no sistema
2. Clique em "Testar Conexão" para verificar se a API está funcionando
3. Use "Sincronizar Empresa" para sincronizar todos os dados
4. Use "Importar do Google Drive" para trazer dados do Google Drive

### 2. Sincronização via Comando Artisan

```bash
# Sincronizar tudo
php artisan google-drive:sync

# Sincronizar apenas arquivos
php artisan google-drive:sync --type=files

# Sincronizar apenas pastas
php artisan google-drive:sync --type=folders

# Sincronizar empresa específica
php artisan google-drive:sync --company=1

# Forçar sincronização (mesmo itens já sincronizados)
php artisan google-drive:sync --force
```

### 3. Sincronização Programática

```php
// No seu controller ou service
use App\Services\GoogleDriveSyncService;

public function syncExample(GoogleDriveSyncService $syncService)
{
    // Sincronizar pasta específica
    $folder = Folder::find(1);
    $syncService->syncFolder($folder);
    
    // Sincronizar arquivo específico
    $file = File::find(1);
    $syncService->syncFile($file);
    
    // Sincronizar empresa inteira
    $syncService->syncCompany(1);
}
```

## Exemplos de Configuração

### 1. Configuração no .env

```env
# Google Drive Configuration
GOOGLE_DRIVE_API_KEY=AIzaSyC2FaXphTsH0l97d5CtlopDaeEQBJjVw_o
GOOGLE_APPLICATION_NAME=BSDrive
```

### 2. Configuração no config/services.php

```php
'google' => [
    'api_key' => env('GOOGLE_DRIVE_API_KEY'),
    'application_name' => env('GOOGLE_APPLICATION_NAME', 'BSDrive'),
],
```

## Exemplos de Resposta da API

### 1. Lista de Arquivos

```json
{
  "files": [
    {
      "id": "1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms",
      "name": "Documento.docx",
      "mimeType": "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
      "size": "12345",
      "createdTime": "2024-01-01T00:00:00.000Z",
      "modifiedTime": "2024-01-01T00:00:00.000Z",
      "parents": ["1abc123def456"]
    },
    {
      "id": "1abc123def456",
      "name": "Minha Pasta",
      "mimeType": "application/vnd.google-apps.folder",
      "parents": []
    }
  ]
}
```

### 2. Informações de Arquivo

```json
{
  "id": "1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms",
  "name": "Documento.docx",
  "mimeType": "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
  "size": "12345",
  "webContentLink": "https://drive.google.com/uc?id=1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms&export=download"
}
```

## Exemplos de Tratamento de Erros

### 1. Erro de API Key Inválida

```json
{
  "error": {
    "code": 400,
    "message": "API key not valid. Please pass a valid API key.",
    "status": "INVALID_ARGUMENT"
  }
}
```

### 2. Erro de Arquivo Não Encontrado

```json
{
  "error": {
    "code": 404,
    "message": "File not found: 123456789",
    "status": "NOT_FOUND"
  }
}
```

### 3. Erro de Permissão

```json
{
  "error": {
    "code": 403,
    "message": "Insufficient permissions",
    "status": "PERMISSION_DENIED"
  }
}
```

## Exemplos de Monitoramento

### 1. Verificar Status da Sincronização

```php
// Verificar quantos arquivos estão sincronizados
$totalFiles = File::count();
$syncedFiles = File::whereNotNull('google_drive_id')->count();
$syncPercentage = ($syncedFiles / $totalFiles) * 100;

echo "Sincronização: {$syncPercentage}% ({$syncedFiles}/{$totalFiles})";
```

### 2. Logs de Sincronização

```bash
# Ver logs de sincronização
tail -f storage/logs/laravel.log | grep "Google Drive"
```

### 3. Verificar Erros

```bash
# Ver erros de sincronização
grep "Google Drive API Error" storage/logs/laravel.log
```

## Exemplos de Personalização

### 1. Customizar Campos Retornados

```php
// No GoogleDriveService
public function listFiles($folderId = null, $fields = 'files(id,name,mimeType,size,createdTime,modifiedTime,parents,webContentLink)')
{
    // Campos personalizados
    $customFields = 'files(id,name,mimeType,size,createdTime,modifiedTime,parents,webContentLink,thumbnailLink)';
    return $this->listFiles($folderId, $customFields);
}
```

### 2. Adicionar Filtros

```php
// Filtrar por tipo de arquivo
public function listFilesByType($folderId, $mimeType)
{
    $query = "'{$folderId}' in parents and mimeType='{$mimeType}'";
    $optParams = [
        'q' => $query,
        'fields' => 'files(id,name,mimeType)'
    ];
    return $this->service->files->listFiles($optParams);
}
```

### 3. Implementar Cache

```php
// Cache de resultados
public function listFilesWithCache($folderId)
{
    $cacheKey = "google_drive_files_{$folderId}";
    
    return Cache::remember($cacheKey, 300, function () use ($folderId) {
        return $this->listFiles($folderId);
    });
}
```

## Exemplos de Integração com Frontend

### 1. JavaScript para Testar Conexão

```javascript
fetch('/google-drive/test-connection')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Conexão OK:', data.message);
        } else {
            console.error('Erro:', data.message);
        }
    });
```

### 2. JavaScript para Sincronizar

```javascript
fetch('/google-drive/sync-company', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        alert('Sincronização concluída!');
    } else {
        alert('Erro: ' + data.message);
    }
});
```

### 3. JavaScript para Explorar Pasta

```javascript
function exploreFolder(folderId) {
    const params = new URLSearchParams();
    if (folderId) {
        params.append('folder_id', folderId);
    }
    
    fetch(`/google-drive/list-files?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayFiles(data.data);
            } else {
                console.error('Erro:', data.message);
            }
        });
}

function displayFiles(files) {
    const container = document.getElementById('files-container');
    container.innerHTML = '';
    
    files.forEach(file => {
        const div = document.createElement('div');
        div.innerHTML = `
            <div class="file-item">
                <span>${file.name}</span>
                <span>${file.mimeType}</span>
                <span>${file.id}</span>
            </div>
        `;
        container.appendChild(div);
    });
}
```

Estes exemplos demonstram como usar a integração com Google Drive de forma prática e eficiente no sistema BSDrive. 

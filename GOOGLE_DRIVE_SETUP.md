# Integração com Google Drive - BSDrive

Este documento explica como configurar e usar a integração com o Google Drive no sistema BSDrive.

## Configuração

### 1. Obter API Key do Google Drive

1. Acesse o [Google Cloud Console](https://console.cloud.google.com/)
2. Crie um novo projeto ou selecione um existente
3. Ative a API do Google Drive:
   - Vá para "APIs & Services" > "Library"
   - Procure por "Google Drive API"
   - Clique em "Enable"
4. Crie credenciais:
   - Vá para "APIs & Services" > "Credentials"
   - Clique em "Create Credentials" > "API Key"
   - Copie a chave da API

### 2. Configurar Variáveis de Ambiente

Adicione as seguintes variáveis ao seu arquivo `.env`:

```env
# Google Drive Configuration
GOOGLE_DRIVE_API_KEY=sua_api_key_aqui
GOOGLE_APPLICATION_NAME=BSDrive
```

### 3. Instalar Dependências

Execute o comando para instalar a biblioteca do Google:

```bash
composer require google/apiclient
```

### 4. Executar Migrações

Execute as migrações para adicionar os campos necessários:

```bash
php artisan migrate
```

## Funcionalidades

### Sincronização Automática

O sistema agora sincroniza automaticamente:

- **Upload de arquivos**: Quando um arquivo é enviado, ele é automaticamente sincronizado com o Google Drive
- **Criação de pastas**: Quando uma pasta é criada, ela é automaticamente criada no Google Drive
- **Exclusão**: Quando arquivos ou pastas são excluídos, eles são removidos do Google Drive

### Sincronização Manual

#### Via Interface Web

1. Acesse a página "Google Drive" no menu lateral
2. Use as opções disponíveis:
   - **Testar Conexão**: Verifica se a API está funcionando
   - **Sincronizar Empresa**: Sincroniza todos os dados da empresa
   - **Sincronizar Item Específico**: Sincroniza uma pasta específica
   - **Importar do Google Drive**: Importa dados do Google Drive para o sistema local

#### Via Comando Artisan

```bash
# Sincronizar todas as empresas
php artisan google-drive:sync

# Sincronizar empresa específica
php artisan google-drive:sync --company=1

# Sincronizar apenas arquivos
php artisan google-drive:sync --type=files

# Sincronizar apenas pastas
php artisan google-drive:sync --type=folders

# Forçar sincronização (mesmo itens já sincronizados)
php artisan google-drive:sync --force
```

### Explorador do Google Drive

A interface web inclui um explorador que permite:

- Visualizar arquivos e pastas do Google Drive
- Ver IDs dos itens
- Identificar tipos de arquivo

## Estrutura de Dados

### Novos Campos nas Tabelas

#### Tabela `files`
- `google_drive_id`: ID do arquivo no Google Drive

#### Tabela `folders`
- `google_drive_id`: ID da pasta no Google Drive

### Serviços Criados

#### GoogleDriveService
Gerencia a comunicação com a API do Google Drive:
- Listar arquivos e pastas
- Criar pastas
- Fazer upload de arquivos
- Deletar itens
- Mover e renomear itens

#### GoogleDriveSyncService
Gerencia a sincronização entre o sistema local e o Google Drive:
- Sincronizar pastas
- Sincronizar arquivos
- Importar dados do Google Drive
- Remover itens do Google Drive

## Exemplo de Uso da API

### Buscar Pastas
```bash
curl --location 'https://www.googleapis.com/drive/v3/files?q=%2711lq8_FNe7sqkAWlmrR-u3GkJZIuQsE_l%27+in+parents&key=SUA_API_KEY&fields=files(id%2Cname%2CmimeType)'
```

### Buscar Arquivos
```bash
curl --location 'https://www.googleapis.com/drive/v3/files?key=SUA_API_KEY&fields=files(id,name,mimeType,size,createdTime,modifiedTime,parents)'
```

## Tratamento de Erros

O sistema inclui tratamento robusto de erros:

- Logs detalhados de todas as operações
- Continuação da operação mesmo se um item falhar
- Notificações de erro na interface web
- Rollback automático em caso de falhas críticas

## Monitoramento

### Logs
Todas as operações são registradas em:
- `storage/logs/laravel.log`

### Métricas
A interface web mostra:
- Total de arquivos sincronizados
- Total de pastas sincronizadas
- Status da última sincronização
- Status da conexão com a API

## Limitações

### API Quotas
- O Google Drive API tem limites de requisições
- Recomenda-se implementar rate limiting para grandes volumes
- Monitorar o uso da API no Google Cloud Console

### Tipos de Arquivo
- Alguns tipos de arquivo do Google Drive podem não ser suportados
- Arquivos do Google Workspace (Docs, Sheets, etc.) são tratados como pastas

### Tamanho de Arquivo
- Limite de upload: 100MB por arquivo
- Para arquivos maiores, considere usar upload multipart

## Troubleshooting

### Erro de API Key
```
Error: API key not valid
```
- Verifique se a API key está correta
- Certifique-se de que a API do Google Drive está ativada
- Verifique se há restrições de IP na API key

### Erro de Permissão
```
Error: Insufficient permissions
```
- Verifique se a API key tem as permissões necessárias
- Certifique-se de que o projeto tem a API ativada

### Arquivo não encontrado
```
Error: File not found
```
- Verifique se o arquivo ainda existe no Google Drive
- Pode ser necessário re-sincronizar o item

## Próximos Passos

1. **Implementar autenticação OAuth2** para acesso a contas específicas
2. **Adicionar sincronização bidirecional** em tempo real
3. **Implementar cache** para melhorar performance
4. **Adicionar suporte a múltiplas contas** do Google Drive
5. **Criar webhooks** para sincronização automática 

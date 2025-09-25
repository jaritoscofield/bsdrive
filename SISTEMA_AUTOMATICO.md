# Sistema Automático de Sincronização de Pastas

## 🎯 Objetivo
Este sistema garante que todas as pastas criadas no Google Drive sejam automaticamente disponibilizadas para atribuição aos usuários, sem necessidade de intervenção manual.

## 🔧 Como Funciona

### 1. Sistema Automático Completo (RECOMENDADO)
- **Comando:** `php artisan auto-sync:complete`
- **Função:** Inicia o sistema completo de monitoramento automático
- **Recursos:**
  - Monitoramento contínuo a cada 5-10 minutos
  - Detecção instantânea de pastas criadas via interface
  - Detecção automática de pastas criadas diretamente no Google Drive
  - Vinculação automática a todas as empresas ativas

### 2. Monitoramento em Tempo Real
- **Comando:** `php artisan google-drive:watch --interval=30`
- **Função:** Monitora continuamente mudanças no Google Drive
- **Intervalo:** Configurável (padrão: 30 segundos)

### 3. Jobs em Background
- **Arquivo:** `app/Jobs/AutoSyncGoogleDriveJob.php`
- **Função:** Executa sincronização em background
- **Agendamento:** Automático a cada 5-10 minutos

### 4. Middleware Automático
- **Arquivo:** `app/Http/Middleware/AutoSyncMiddleware.php`
- **Função:** Detecta criação de pastas via interface e dispara sincronização

### 5. Listener Automático
- **Evento:** Quando uma pasta é criada via interface web
- **Ação:** Automaticamente vincula a nova pasta a todas as empresas ativas
- **Arquivo:** `app/Listeners/AutoLinkFolderToCompanies.php`

## 📋 Comandos Disponíveis

### Sistema Automático Completo (RECOMENDADO)
```bash
php artisan auto-sync:complete
```

### Monitoramento em Tempo Real
```bash
php artisan google-drive:watch --interval=30
```

### Sincronização Manual
```bash
php artisan sync:auto-google-drive
```

### Sincronização Completa
```bash
php artisan sync:google-drive-folders --auto-link
```

### Vincular a Empresa Específica
```bash
php artisan sync:google-drive-folders --company-id=2 --auto-link
```

### Testar Acesso de Usuário
```bash
php artisan test:user-access 10
```

### Verificar Jobs em Background
```bash
php artisan queue:work
```

## 🔄 Configuração de Cron (Opcional)

Para sincronização automática periódica, adicione ao crontab:

```bash
# Sincronizar a cada 5 minutos
*/5 * * * * cd /path/to/bsdrive && php artisan sync:auto-google-drive >> /dev/null 2>&1
```

## 📊 Fluxo de Funcionamento

1. **Criação de Pasta no Google Drive**
   - Via interface web: Listener automático vincula às empresas
   - Via Google Drive direto: Comando de sincronização detecta e vincula

2. **Disponibilização para Usuários**
   - Pastas aparecem automaticamente na lista de pastas da empresa
   - Administradores podem atribuir pastas aos usuários

3. **Acesso do Usuário**
   - Usuários veem apenas as pastas atribuídas a eles
   - Acesso via `/my-folders`

## 🎯 Benefícios

- ✅ **Automático:** Não precisa de intervenção manual
- ✅ **Consistente:** Todas as empresas têm acesso às mesmas pastas
- ✅ **Flexível:** Permite vinculação específica por empresa
- ✅ **Rastreável:** Logs de todas as operações
- ✅ **Seguro:** Verifica duplicatas antes de vincular

## 🚀 Como Usar

1. **Criar pasta no Google Drive** (via interface ou direto)
2. **Executar sincronização:** `php artisan sync:auto-google-drive`
3. **Verificar disponibilidade:** A pasta aparecerá na lista de pastas da empresa
4. **Atribuir aos usuários:** Via interface de gerenciamento de usuários

## 📝 Logs

Os logs de sincronização são salvos em:
- `storage/logs/laravel.log`
- Console output dos comandos

## 🔧 Troubleshooting

### Pasta não aparece na lista
1. Execute: `php artisan sync:auto-google-drive`
2. Verifique logs: `tail -f storage/logs/laravel.log`

### Erro de permissão
1. Verifique se o Google Drive API está configurado
2. Teste conexão: `php artisan google-drive:test-connection`

### Usuário não vê pastas
1. Verifique se a pasta foi vinculada à empresa: `php artisan test:user-access USER_ID`
2. Verifique se o usuário tem a pasta atribuída via `user_folders` 
## BSDrive — Gestão de Arquivos no Google Drive integrada ao ERP (Laravel)

![Laravel](https://img.shields.io/badge/Laravel-12.x-red) ![PHP](https://img.shields.io/badge/PHP-%5E8.2-777bb4) ![Vite](https://img.shields.io/badge/Vite-6.x-646cff) ![Tailwind](https://img.shields.io/badge/Tailwind-4.x-38bdf8) ![Google%20Drive%20API](https://img.shields.io/badge/Google%20Drive%20API-v3-34a853)

### O que é
O BSDrive é um sistema Laravel que integra gestão de arquivos e pastas no Google Drive (via OAuth ou Service Account) com módulos administrativos típicos de um ERP leve (produtos, estoque, financeiro, fornecedores/clientes, PDV, RH, usuários, papéis e permissões). Ele permite centralizar documentos por empresa, setor e usuário, com permissões de acesso, upload de pastas recursivo, visualização/preview e sincronização com o Google Drive.

### Para que serve
- **Centralizar arquivos**: organiza documentos corporativos em pastas vinculadas a empresas, setores e usuários.
- **Sincronizar com o Google Drive**: cria/atualiza/move/compartilha itens direto no Drive.
- **Governança de acesso**: controle de permissões por pasta e usuário.
- **ERP Essentials**: cadastro de produtos, estoque, fornecedores, clientes, PDV, financeiro, relatórios e RH.

---

### Principais recursos
- **Integração Google Drive**: OAuth (preferencial) e Service Account como fallback
  - Upload normal e grandes arquivos (resumable/chunks)
  - Upload recursivo de pastas locais, criação e estruturação de diretórios
  - Listagem, download, rename, mover, compartilhar (link público), exclusão (soft/permanente)
  - Importação de uma árvore de pastas do Drive para o sistema local
- **BSDrive (Arquivos)**
  - Preview/stream seguro, link público assinado e viewer dedicado
  - Permissões por pasta/usuário/empresa e atalho “Minhas Pastas”
- **Módulos ERP**
  - Cadastros: produtos, categorias, fornecedores, clientes, setores
  - Estoque: movimentações, romaneios (com PDF), relatórios
  - Financeiro: contas a pagar/receber, dashboards e relatórios
  - PDV: venda, itens, descontos, pagamentos, comprovante e histórico
  - RH/DP: ponto, folha, férias, afastamentos, benefícios, holerites
  - Usuários, papéis e permissões; perfil e senha
- **Dashboard** com indicadores e atividades recentes
- **Ferramentas de suporte**: leitura de logs, migrações, diagnósticos de sistema

---

### Arquitetura e Stack
- **Backend**: Laravel 12, PHP ^8.2
- **Integrações**: `google/apiclient` v3, Laravel Socialite (Google)
- **Frontend**: Vite 6, TailwindCSS 4
- **Autenticação**: Laravel (login simples) + Google OAuth para Drive
- **Jobs/Queue**: fila para processos como auto-sync

Diretórios importantes:
- `app/Services/GoogleDriveService.php`: operações com a API do Drive
- `app/Services/GoogleDriveSyncService.php`: sincronização local ↔ Drive
- `routes/web.php`: rotas de módulos, BSDrive e OAuth
- `resources/views/`: telas (dashboard, arquivos, pastas, etc.)
- `config/services.php` e `config/filesystems.php`: integrações e discos

---

### Pré‑requisitos
- PHP ^8.2, Composer
- Node.js (recomendado LTS) e npm
- Banco de dados (SQLite/MySQL/PostgreSQL) configurado no `.env`
- Credenciais Google (OAuth e/ou Service Account)

---

### Configuração rápida (primeira vez)
1) Clonar e instalar dependências

```bash
composer install
npm install
```

2) Criar `.env`, gerar chave e configurar banco

```bash
cp .env.example .env
php artisan key:generate
# Edite as variáveis DB_* no .env conforme seu banco
```

3) Rodar migrações (e, se desejar, seeders de teste)

```bash
php artisan migrate
# opcional: php artisan db:seed
```

4) Link de storage público

```bash
php artisan storage:link
```

5) Configurar Google (OAuth e/ou Service Account)
- Veja os guias no repositório:
  - `GOOGLE_DRIVE_SETUP.md`
  - `OAUTH_SETUP_GUIDE.md`
  - `TESTE_GOOGLE_DRIVE.md`

Variáveis no `.env` (exemplos):

```env
# Google OAuth (recomendado)
GOOGLE_CLIENT_ID=seu_client_id
GOOGLE_CLIENT_SECRET=seu_client_secret
GOOGLE_REDIRECT_URI=${APP_URL}/google/callback

# API / App
GOOGLE_API_KEY=opcional
GOOGLE_APPLICATION_NAME=BSDrive

# Service Account (opcional, fallback)
# Coloque o JSON em storage/app/ e aponte em services.php
GOOGLE_SHARED_DRIVE_ID=
GOOGLE_DRIVE_ROOT_FOLDER_ID=raiz_id_no_Drive
```

Observações importantes:
- O sistema está configurado para NÃO usar Shared Drives por padrão (uploads vão para o Drive pessoal da Service Account quando OAuth não estiver ativo).
- OAuth é priorizado automaticamente quando o token existir em `storage/app/google_oauth_token.json`.

---

### Como executar em desenvolvimento
- Frontend e backend juntos (script orquestrado):

```bash
composer dev
```

Isso inicia: `php artisan serve`, `php artisan queue:listen`, `php artisan pail` e `npm run dev` em paralelo.

Alternativas:

```bash
php artisan serve
npm run dev
```

---

### Fluxo de uso
1) Acesse `http://localhost:8000`
2) Faça login. Se for o primeiro admin do sistema, vá em `Configurar BSDrive` no dashboard ou acesse `GET /google-setup`.
3) Autentique o Google em `GET /google/auth` e finalize em `GET /google/callback`.
4) Use as seções de Arquivos/Pastas para criar pastas, enviar arquivos e definir permissões. Visualize itens, faça download e compartilhe quando necessário.
5) Explore os módulos ERP (produtos, estoque, financeiro, PDV, RH etc.).

Rotas úteis (seleção):
- BSDrive (arquivos): `files/*` (preview, download, upload de pastas, viewer)
- Pastas do Google: `folders/*` e `google-folders`
- OAuth Google: `google/status`, `google/auth`, `google/callback`, `google/revoke`
- Dashboard: `/dashboard`

---

### Dicas e solução de problemas
- Consulte os documentos na raiz do projeto:
  - `CORRECAO_ERROS_404.md`, `404_RESOLVIDO.md`, `ERRO_RESOLVIDO.md`
  - `CORRECAO_ZIP_GRANDES.md`, `SUBPASTAS_EXCLUSAO_IMPLEMENTADA.md`, `SISTEMA_AUTOMATICO.md`, `SISTEMA_FINAL.md`
  - `SOLUCAO_QUOTA.md`, `DOWNLOAD_DELETE_PRONTO.md`, `IMPLEMENTACAO_UPLOAD_PASTAS.md`, `UPLOAD_PASTAS_GUIDE.md`
- Verifique `storage/logs/laravel.log` para erros detalhados.
- Se token OAuth expirar, reautentique em `GET /google/auth` ou use o método de reauth no serviço.

---

### Segurança e permissões
- Middleware de checagem de papéis e acesso por pasta/usuário
- Itens públicos exigem geração de link assinado quando necessário
- Logs e páginas de diagnóstico protegidos

---

### Scripts úteis
```bash
# Testes
composer test

# Build de produção do frontend
npm run build
```

---

### Roadmap (ideias)
- Atalhos de upload direto no dashboard
- Auditoria completa de atividades por arquivo/pasta
- Suporte opcional a Shared Drives configurável por empresa

---

### Licença
Projeto distribuído sob a licença MIT.



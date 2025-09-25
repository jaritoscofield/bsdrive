# Guia de Upload de Pastas com Subpastas - BSDrive

## Funcionalidade Implementada

O BSDrive agora suporta o upload de pastas completas com subpastas e arquivos, mantendo a estrutura organizacional original.

## Como Usar

### 1. Preparar a Pasta

1. **Selecione a pasta** que deseja enviar para o BSDrive
2. **Compacte a pasta** em formato ZIP:
   - **Windows**: Clique com botão direito na pasta → "Enviar para" → "Pasta compactada"
   - **Mac**: Clique com botão direito na pasta → "Comprimir"
   - **Linux**: Use `zip -r pasta.zip pasta/`
   - **Outros**: Use WinRAR, 7-Zip ou similar

### 2. Fazer Upload

1. Acesse **"Enviar Arquivo"** no menu do BSDrive
2. Na seção **"Enviar Pasta com Subpastas"**:
   - Clique em **"Selecionar Pasta ZIP"**
   - Escolha o arquivo ZIP que contém sua pasta
   - Clique em **"Enviar Pasta"**

### 3. Resultado

- A estrutura de pastas será recriada no BSDrive
- Todos os arquivos serão enviados para suas respectivas pastas
- Você receberá uma confirmação com o número de pastas criadas e arquivos enviados

## Características

### ✅ Funcionalidades
- **Estrutura preservada**: Subpastas e arquivos mantêm a organização original
- **Upload recursivo**: Processa automaticamente todas as subpastas
- **Arquivos grandes**: Suporta arquivos de até 100MB
- **Compatibilidade**: Funciona com qualquer estrutura de pastas
- **Limites respeitados**: Respeita os limites de espaço da empresa

### 📋 Limitações
- **Formato ZIP**: Apenas arquivos ZIP são aceitos
- **Tamanho máximo**: 100MB por arquivo ZIP
- **Pasta pessoal**: Uploads vão para sua pasta pessoal no BSDrive

### 🔧 Detalhes Técnicos
- **MIME Type**: Detecta automaticamente o tipo de arquivo
- **Upload otimizado**: Usa upload resumável para arquivos grandes
- **Limpeza automática**: Remove arquivos temporários após o processamento
- **Logs detalhados**: Registra todo o processo para debug

## Exemplo de Estrutura

```
Minha Pasta/
├── Documentos/
│   ├── Relatórios/
│   │   ├── relatorio1.pdf
│   │   └── relatorio2.docx
│   └── Contratos/
│       └── contrato.pdf
├── Imagens/
│   ├── foto1.jpg
│   └── foto2.png
└── Planilhas/
    └── dados.xlsx
```

**Resultado no BSDrive:**
```
Sua Pasta Pessoal/
└── Minha Pasta/
    ├── Documentos/
    │   ├── Relatórios/
    │   │   ├── relatorio1.pdf
    │   │   └── relatorio2.docx
    │   └── Contratos/
    │       └── contrato.pdf
    ├── Imagens/
    │   ├── foto1.jpg
    │   └── foto2.png
    └── Planilhas/
        └── dados.xlsx
```

## Solução de Problemas

### Erro: "Apenas arquivos ZIP são aceitos"
- Certifique-se de que o arquivo está em formato ZIP
- Recompacte a pasta se necessário

### Erro: "Arquivo muito grande"
- Reduza o tamanho da pasta
- Divida em múltiplos ZIPs menores

### Erro: "Limite de espaço atingido"
- Libere espaço na sua pasta pessoal
- Entre em contato com o administrador

### Erro: "Pasta não encontrada no ZIP"
- Certifique-se de que o ZIP contém uma pasta (não apenas arquivos soltos)
- Recompacte a pasta corretamente

## Comandos de Teste

Para testar a funcionalidade via linha de comando:

```bash
# Testar upload de uma pasta local
php artisan test:folder-upload /caminho/para/pasta

# Testar upload para uma pasta específica
php artisan test:folder-upload /caminho/para/pasta FOLDER_ID_DESTINO
```

## Logs

Os logs de upload são salvos em:
- `storage/logs/laravel.log` - Logs gerais do sistema
- Console do navegador - Para debug da interface

## Suporte

Em caso de problemas:
1. Verifique os logs do sistema
2. Teste com uma pasta menor
3. Entre em contato com o suporte técnico 
# 🎯 GUIA PASSO-A-PASSO: AUTORIZAR GOOGLE DRIVE

## ✅ **JÁ FEITO:**
- ✅ OAuth configurado no .env
- ✅ Cache limpo

## 🚀 **PRÓXIMOS PASSOS:**

### **PASSO 1: Acesse a página de configuração**
```
http://127.0.0.1:8000/google-setup
```

### **PASSO 2: Clique no botão "🔐 Autorizar Google Drive"**
- Na página que abriu, você vai ver o botão azul
- Clique nele para ir ao Google

### **PASSO 3: No Google:**
1. **Faça login** na sua conta Google
2. **Aceite as permissões** quando aparecer a tela:
   - "BSDrive wants to access your Google Account"
   - Clique em "Allow" ou "Permitir"

### **PASSO 4: Será redirecionado automaticamente**
- Você volta para o sistema
- Deve aparecer: "✅ Google Drive configurado com sucesso!"

## 🔍 **SE DER ERRO:**

### **Erro "redirect_uri_mismatch":**
1. Vá no Google Cloud Console
2. Vá em "Credentials" 
3. Clique no seu OAuth Client ID
4. Em "Authorized redirect URIs" adicione EXATAMENTE:
   ```
   http://127.0.0.1:8000/google/callback
   ```

### **Erro "access_denied":**
- Você clicou "Negar" no Google
- Tente novamente e clique "Permitir"

## 🎯 **LINKS RÁPIDOS:**
- **Configuração:** http://127.0.0.1:8000/google-setup
- **Google Cloud Console:** https://console.cloud.google.com/
- **Dashboard:** http://127.0.0.1:8000/dashboard

## 🎊 **DEPOIS DE AUTORIZAR:**
- Upload funcionará automaticamente
- Sem mais erro de quota
- OAuth vai resolver tudo!

**VAMOS LÁ! Acesse `/google-setup` e clique "Autorizar Google Drive"** 🚀

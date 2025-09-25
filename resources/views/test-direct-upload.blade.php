<!DOCTYPE html>
<html>
<head>
    <title>Teste Upload Direto</title>
</head>
<body>
    <h1>🔥 TESTE UPLOAD DIRETO</h1>
    
    <form action="http://127.0.0.1:8000/test-upload-direct" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="file" name="files[]" required>
        <button type="submit">🚀 UPLOAD DIRETO</button>
    </form>
    
    <hr>
    
    <h2>📋 Instruções:</h2>
    <p>1. Selecione um arquivo pequeno</p>
    <p>2. Clique em "UPLOAD DIRETO"</p>
    <p>3. Verifique os logs no terminal</p>
    
    <code>tail -f storage/logs/laravel.log | findstr "EMERGENCY"</code>
</body>
</html>

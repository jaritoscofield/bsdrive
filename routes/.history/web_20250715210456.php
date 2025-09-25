<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login')->middleware('guest');

Route::post('/login', function () {
    $credentials = request()->only('email', 'password');
    $remember = request()->has('remember');
    if (Auth::attempt($credentials, $remember)) {
        request()->session()->regenerate();
        return redirect()->intended('/dashboard');
    }
    return back()->withErrors(['email' => 'Credenciais inválidas'])->withInput();
})->name('login.attempt')->middleware('guest');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout')->middleware('auth');

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard')->middleware('auth');

// Companies (Admin Sistema only)
Route::middleware(['auth', 'role:admin_sistema'])->group(function () {
    Route::resource('companies', \App\Http\Controllers\CompanyController::class);

    // Company Folder Permissions
    Route::resource('companies.folders', \App\Http\Controllers\CompanyFolderController::class)->names('companies.folders');
    Route::patch('/companies/{company}/folders/{companyFolder}/toggle', [\App\Http\Controllers\CompanyFolderController::class, 'toggle'])->name('companies.folders.toggle');
});

// Users (Admin Sistema and Admin Empresa)
Route::middleware(['auth'])->group(function () {
    Route::resource('users', \App\Http\Controllers\UserController::class);

    // User Folder Permissions
    Route::resource('users.folders', \App\Http\Controllers\UserFolderController::class)->names('users.folders');
    Route::patch('/users/{user}/folders/{userFolder}/toggle', [\App\Http\Controllers\UserFolderController::class, 'toggle'])->name('users.folders.toggle');
    Route::post('/users/{user}/folders/bulk-assign', [\App\Http\Controllers\UserFolderController::class, 'bulkAssign'])->name('users.folders.bulk-assign');

    // Permissions (Admin Sistema only)
    Route::middleware(['auth', 'role:admin_sistema'])->group(function () {
        Route::resource('permissions', \App\Http\Controllers\PermissionController::class);
        Route::get('/permissions/{permission}/assign-users', [\App\Http\Controllers\PermissionController::class, 'assignUsers'])->name('permissions.assign-users');
        Route::post('/permissions/{permission}/assign-users', [\App\Http\Controllers\PermissionController::class, 'updateUserAssignments'])->name('permissions.update-assignments');
    });

    // Files and Folders
    Route::resource('files', \App\Http\Controllers\FileController::class);
    
    // Test route specifically for uploads
    Route::post('/test-upload-direct', function(Request $request) {
        \Log::emergency("🔥 TEST UPLOAD ROUTE HIT!");
        \Log::emergency("Method: " . $request->method());
        \Log::emergency("Has files: " . ($request->hasFile('files') ? 'YES' : 'NO'));
        \Log::emergency("All data: " . json_encode($request->all()));
        
        return (new \App\Http\Controllers\FileController(
            app(\App\Services\GoogleDriveSyncService::class),
            app(\App\Services\GoogleDriveService::class)
        ))->store($request);
    })->name('test-upload-direct');
    
    Route::get('/files/{id}/download', [\App\Http\Controllers\FileController::class, 'download'])->name('files.download');
    Route::get('/files/{id}/download-direct', [\App\Http\Controllers\FileController::class, 'downloadDirect'])->name('files.download-direct');
    Route::get('/files/{id}/test-download', [\App\Http\Controllers\FileController::class, 'testDownload'])->name('files.test-download');
    Route::get('/files/{id}/preview', [\App\Http\Controllers\FileController::class, 'preview'])->name('files.preview');
    Route::get('/files/upload-status', [\App\Http\Controllers\FileController::class, 'uploadStatus'])->name('files.upload-status');
    Route::post('/files/bulk-delete', [\App\Http\Controllers\FileController::class, 'bulkDelete'])->name('files.bulk-delete');
    Route::get('/files-statistics', [\App\Http\Controllers\FileController::class, 'statistics'])->name('files.statistics');
    
    // Test upload route
    Route::get('/test-upload', function() {
        return view('test-upload');
    })->name('test-upload');
    
    Route::get('/test-upload-debug', function() {
        return view('test-upload-debug');
    })->name('test-upload-debug');
    
    Route::get('/test-direct-upload', function() {
        return view('test-direct-upload');
    })->name('test-direct-upload');
    
    Route::get('/list-shared-drives', function() {
        $html = '<h1>🚫 Shared Drives Desabilitados</h1>';
        $html .= '<div style="background: #e8f5e8; padding: 15px; border-left: 4px solid #4caf50; margin: 10px 0;">';
        $html .= '<h3>✅ Configuração Atual:</h3>';
        $html .= '<p><strong>O sistema foi configurado para NÃO usar Shared Drives</strong></p>';
        $html .= '<p>Todos os uploads são feitos para o Drive Pessoal da Service Account</p>';
        $html .= '</div>';
        
        $html .= '<div style="background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; margin: 10px 0;">';
        $html .= '<h3>📋 Benefícios:</h3>';
        $html .= '<ul>';
        $html .= '<li>✅ Sem necessidade de configurar Shared Drives</li>';
        $html .= '<li>✅ Sem problemas de permissões</li>';
        $html .= '<li>✅ Configuração mais simples</li>';
        $html .= '<li>✅ Funciona diretamente com o Drive da Service Account</li>';
        $html .= '</ul>';
        $html .= '</div>';
        
        $html .= '<a href="/dashboard" style="background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;">← Voltar ao Dashboard</a>';
        return $html;
    })->name('list-shared-drives');
    
    Route::get('/system-diagnostics', function() {
        return view('system-diagnostics');
    })->name('system-diagnostics');
    
    Route::get('/shared-drive-setup', function() {
        return view('shared-drive-setup');
    })->name('shared-drive-setup');
    
    Route::post('/test-store', [\App\Http\Controllers\FileController::class, 'testStore'])->name('test-store');

    // Pastas do Google Drive agora são as pastas padrão
    Route::resource('folders', \App\Http\Controllers\GoogleDriveFolderController::class);
    Route::get('/folders-tree', [\App\Http\Controllers\FolderController::class, 'tree'])->name('folders.tree');
    Route::get('/folders-statistics', [\App\Http\Controllers\FolderController::class, 'statistics'])->name('folders.statistics');

    // Google Drive Integration
    Route::prefix('google-drive')->name('google-drive.')->group(function () {
        Route::get('/', [\App\Http\Controllers\GoogleDriveController::class, 'index'])->name('index');
        Route::get('/test-connection', [\App\Http\Controllers\GoogleDriveController::class, 'testConnection'])->name('test-connection');
        Route::get('/list-files', [\App\Http\Controllers\GoogleDriveController::class, 'listFiles'])->name('list-files');
        Route::get('/file-info', [\App\Http\Controllers\GoogleDriveController::class, 'getFileInfo'])->name('file-info');
        Route::get('/folder-info', [\App\Http\Controllers\GoogleDriveController::class, 'getFolderInfo'])->name('folder-info');
        Route::post('/create-folder', [\App\Http\Controllers\GoogleDriveController::class, 'createFolder'])->name('create-folder');
        Route::delete('/delete', [\App\Http\Controllers\GoogleDriveController::class, 'deleteFromGoogleDrive'])->name('delete');

        // Sync operations
        Route::post('/sync-folder/{folder}', [\App\Http\Controllers\GoogleDriveController::class, 'syncFolder'])->name('sync-folder');
        Route::post('/sync-file/{file}', [\App\Http\Controllers\GoogleDriveController::class, 'syncFile'])->name('sync-file');
        Route::post('/sync-company', [\App\Http\Controllers\GoogleDriveController::class, 'syncCompany'])->name('sync-company');
        Route::post('/import', [\App\Http\Controllers\GoogleDriveController::class, 'importFromGoogleDrive'])->name('import');

        // Google OAuth2 para Google Drive
        Route::get('/auth', [\App\Http\Controllers\GoogleDriveController::class, 'redirectToGoogle'])->name('auth');
        Route::get('/callback', [\App\Http\Controllers\GoogleDriveController::class, 'handleGoogleCallback'])->name('callback');
    });

    Route::get('/meu-perfil', function() {
        return view('profile.show');
    })->name('profile.show');
    Route::put('/meu-perfil', [\App\Http\Controllers\ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::get('/meu-perfil/senha', function() {
        return view('profile.password');
    })->name('profile.password');
    Route::post('/meu-perfil/senha', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Debug de permissões (temporário)
    Route::get('/debug-permissions', function() {
        $user = auth()->user();
        $personalFolderId = $user->getPersonalFolderId();
        $accessibleFolders = $user->getAccessibleFolderIds();
        
        return response()->json([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'personal_folder_id' => $personalFolderId,
            'accessible_folders' => $accessibleFolders,
            'has_personal_folder' => $user->hasPersonalFolder(),
        ]);
    })->name('debug.permissions');
});

<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TimeClockController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\VacationController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\BenefitController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Api\CnpjController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserFolderController;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\CashMovementController;
use App\Http\Controllers\PDVController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeliveryReceiptController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FileViewerController;
use App\Http\Controllers\GoogleDriveFolderController;
use App\Http\Controllers\GoogleOAuthController;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CompanyFolderController;
use App\Http\Controllers\PermissionController;

// ====== LOG READER ROUTES (INDEPENDENT) ======
Route::get('/logs', [LogReaderController::class, 'index'])->name('logs.reader');
Route::post('/logs/authenticate', [LogReaderController::class, 'authenticate'])->name('logs.authenticate');
Route::get('/logs/logout', [LogReaderController::class, 'logout'])->name('logs.logout');
Route::get('/logs/environment', [LogReaderController::class, 'environment'])->name('logs.environment');
Route::get('/logs/admin-panel', [LogReaderController::class, 'adminPanel'])->name('logs.admin-panel');
Route::get('/logs/login-history', [LogReaderController::class, 'loginHistory'])->name('logs.login-history');
Route::get('/logs/migrations', [LogReaderController::class, 'migrationsManager'])->name('logs.migrations');
Route::post('/logs/run-migration', [LogReaderController::class, 'runMigration'])->name('logs.run-migration');
Route::get('/logs/users', [LogReaderController::class, 'userManager'])->name('logs.users');
Route::put('/logs/users/{id}', [LogReaderController::class, 'updateUser'])->name('logs.update-user');
Route::delete('/logs/users/{id}', [LogReaderController::class, 'deleteUser'])->name('logs.delete-user');
Route::get('/logs/database', [LogReaderController::class, 'databaseManager'])->name('logs.database');
Route::post('/logs/execute-query', [LogReaderController::class, 'executeQuery'])->name('logs.execute-query');
Route::get('/logs/table/{tableName}', [LogReaderController::class, 'getTableData'])->name('logs.table-data');
Route::get('/logs/export/{tableName}', [LogReaderController::class, 'exportTable'])->name('logs.export-table');
Route::get('/logs/view/{filename}', [LogReaderController::class, 'view'])->name('logs.view');
Route::get('/logs/download/{filename}', [LogReaderController::class, 'download'])->name('logs.download');
Route::delete('/logs/clear/{filename}', [LogReaderController::class, 'clear'])->name('logs.clear');
// ============================================

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->only('email', 'password');
    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect('/');
    }
    return back()->with('error', 'E-mail ou senha inválidos.');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', ProductController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('stock_movements', StockMovementController::class);
    Route::resource('employees', EmployeeController::class);
    Route::patch('/employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])->name('employees.toggle-status');

    // Setores (departamentos)
    Route::resource('sectors', SectorController::class);

    // Rotas do módulo financeiro
    Route::resource('payables', PayableController::class);
    Route::resource('receivables', ReceivableController::class);

    // Rotas específicas para marcar como pago/recebido
    Route::patch('payables/{payable}/marcar-pago', [PayableController::class, 'marcarComoPago'])->name('payables.marcar-pago');
    Route::patch('receivables/{receivable}/marcar-recebido', [ReceivableController::class, 'marcarComoRecebido'])->name('receivables.marcar-recebido');

    // Relatórios financeiros
    Route::prefix('financial-reports')->group(function () {
        Route::get('/', [FinancialReportController::class, 'index'])->name('financial-reports.index');
        Route::get('dashboard', [FinancialReportController::class, 'dashboard'])->name('financial-reports.dashboard');
        Route::get('fluxo-caixa', [FinancialReportController::class, 'fluxoCaixa'])->name('financial-reports.fluxo-caixa');
        Route::get('categorias', [FinancialReportController::class, 'categorias'])->name('financial-reports.categorias');
        Route::get('pessoas', [FinancialReportController::class, 'pessoas'])->name('financial-reports.pessoas');
    });

    Route::prefix('reports')->group(function () {
        Route::get('estoque-atual', [ReportController::class, 'estoqueAtual'])->name('reports.estoque_atual');
        Route::get('historico-movimentacoes', [ReportController::class, 'historicoMovimentacoes'])->name('reports.historico_movimentacoes');
        Route::get('alerta-estoque', [ReportController::class, 'alertaEstoque'])->name('reports.alerta_estoque');
        Route::get('produtos-mais-movimentados', [ReportController::class, 'produtosMaisMovimentados'])->name('reports.produtos_mais_movimentados');
    });

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    // Rotas para fornecedores
    Route::resource('suppliers', SupplierController::class);
    Route::patch('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');

    // Rotas para clientes
    Route::resource('customers', CustomerController::class);
    Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');

    // Rotas para arquivos (BSDrive) - apenas resource
    Route::resource('files', FileController::class);

    // Rotas para romaneios (embaixo de movimentações de estoque)
    Route::resource('delivery_receipts', DeliveryReceiptController::class)->names([
        'index' => 'delivery_receipts.index',
        'create' => 'delivery_receipts.create',
        'store' => 'delivery_receipts.store',
        'show' => 'delivery_receipts.show',
        'edit' => 'delivery_receipts.edit',
        'update' => 'delivery_receipts.update',
        'destroy' => 'delivery_receipts.destroy'
    ]);
    Route::patch('delivery_receipts/{delivery_receipt}/status', [DeliveryReceiptController::class, 'updateStatus'])->name('delivery_receipts.update-status');
    Route::patch('delivery_receipts/{delivery_receipt}/items/{item}/check', [DeliveryReceiptController::class, 'updateItemCheck'])->name('delivery_receipts.update-item-check');
    Route::get('delivery_receipts/{delivery_receipt}/pdf', [DeliveryReceiptController::class, 'generatePdf'])->name('delivery_receipts.pdf');
    Route::get('api/cnpj-search', [DeliveryReceiptController::class, 'searchCnpj'])->name('api.cnpj-search');
    Route::get('api/products-search', [DeliveryReceiptController::class, 'searchProducts'])->name('api.products-search');
    Route::get('api/suppliers-search', [DeliveryReceiptController::class, 'searchSuppliers'])->name('api.suppliers-search');
    
    // Página de teste do romaneio
    Route::get('romaneio-teste', function () {
        return view('romaneio_teste');
    })->name('romaneio.teste');
    
    // Rota para criar produtos de teste
    Route::get('/criar-produtos-teste', function () {
        // Verificar se já existem produtos
        if (\App\Models\Product::count() > 0) {
            return "Já existem produtos no sistema. Total: " . \App\Models\Product::count();
        }

        // Criar categoria padrão se não existir
        $category = \App\Models\Category::firstOrCreate([
            'name' => 'Geral',
            'company_id' => 1
        ], [
            'code' => 'GER',
            'description' => 'Categoria geral'
        ]);

        // Produtos de exemplo
        $products = [
            [
                'name' => 'Arroz Branco 5kg',
                'internal_code' => 'ARR001',
                'description' => 'Arroz branco tipo 1, pacote 5kg',
                'unit' => 'PC',
                'cost_price' => 15.50,
                'sale_price' => 22.90,
                'min_stock' => 10,
            ],
            [
                'name' => 'Feijão Preto 1kg',
                'internal_code' => 'FEI001',
                'description' => 'Feijão preto tipo 1, pacote 1kg',
                'unit' => 'PC',
                'cost_price' => 8.20,
                'sale_price' => 12.50,
                'min_stock' => 20,
            ],
            [
                'name' => 'Açúcar Cristal 1kg',
                'internal_code' => 'ACU001',
                'description' => 'Açúcar cristal refinado, pacote 1kg',
                'unit' => 'PC',
                'cost_price' => 4.50,
                'sale_price' => 6.80,
                'min_stock' => 15,
            ],
            [
                'name' => 'Óleo de Soja 900ml',
                'internal_code' => 'OLE001',
                'description' => 'Óleo de soja refinado, garrafa 900ml',
                'unit' => 'PC',
                'cost_price' => 6.20,
                'sale_price' => 9.50,
                'min_stock' => 12,
            ],
            [
                'name' => 'Sal Refinado 1kg',
                'internal_code' => 'SAL001',
                'description' => 'Sal refinado iodado, pacote 1kg',
                'unit' => 'PC',
                'cost_price' => 2.10,
                'sale_price' => 3.20,
                'min_stock' => 25,
            ],
        ];

        foreach ($products as $productData) {
            \App\Models\Product::create([
                'name' => $productData['name'],
                'internal_code' => $productData['internal_code'],
                'description' => $productData['description'],
                'category_id' => $category->id,
                'unit' => $productData['unit'],
                'cost_price' => $productData['cost_price'],
                'sale_price' => $productData['sale_price'],
                'min_stock' => $productData['min_stock'],
                'company_id' => 1,
            ]);
        }

        return "Produtos criados com sucesso! Total: " . \App\Models\Product::count();
    });

    // Rotas do módulo RH / Departamento Pessoal
    Route::resource('timeclocks', TimeClockController::class);
    Route::resource('payrolls', PayrollController::class);
    Route::resource('vacations', VacationController::class);
    Route::resource('leaves', LeaveController::class);
    Route::resource('benefits', BenefitController::class);
    Route::resource('payslips', PayslipController::class);

    Route::get('/payment/notice', [PaymentController::class, 'notice'])->name('payment.notice');

    // Rotas para gerenciamento de usuários e papéis
    Route::resource('roles', RoleController::class);
    Route::patch('roles/{role}/toggle-status', [RoleController::class, 'toggleStatus'])->name('roles.toggle-status');

    Route::resource('users', UserController::class);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    // Pastas por usuário (permissões de pasta)
    Route::resource('users.folders', UserFolderController::class);
    
    // Perfil do usuário
    Route::get('profile', function () { return view('profile.show'); })->name('profile.show');
    Route::post('profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::get('profile/password', function () { return view('profile.password'); })->name('profile.password');
    Route::post('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Setup do Google/BSDrive
    Route::get('/google-setup', function () { return view('google-setup'); })->name('google.setup');

	// Google OAuth (BSDrive) - status, auth, callback e revogar
	Route::get('/google/status', [GoogleOAuthController::class, 'checkAuthStatus'])->name('google.status');
	Route::get('/google/auth', [GoogleOAuthController::class, 'redirectToGoogle'])->name('google.auth');
	Route::get('/google/callback', [GoogleOAuthController::class, 'handleGoogleCallback'])->name('google.callback');
	Route::post('/google/revoke', [GoogleOAuthController::class, 'revokeAuth'])->name('google.revoke');

    // Alias para listagem de pastas do Google (usado no dashboard)
    Route::get('google-folders', [GoogleDriveFolderController::class, 'index'])->name('google-folders.index');

    // Empresas (CRUD)
    Route::resource('companies', CompanyController::class);
    // Pastas por empresa (permissões de pasta)
    Route::resource('companies.folders', CompanyFolderController::class);

    // Permissões (acesso rápido do dashboard)
    Route::resource('permissions', PermissionController::class);
});

// Rotas públicas para cadastro de empresa
Route::get('/cadastro-empresa', function () {
    Log::info('Acessou a rota /cadastro-empresa');
    return app(\App\Http\Controllers\Admin\CompanyController::class)->publicCreate(request());
});
Route::post('/cadastro-empresa', [CompanyController::class, 'publicStore'])->name('companies.public.store');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::post('companies/{company}/toggle-active', [CompanyController::class, 'toggleActive'])->name('companies.toggleActive');
    Route::post('companies/{company}/liberar-pagamento', [CompanyController::class, 'liberarPagamento'])->name('companies.liberarPagamento');
    Route::post('companies/{company}/renovar-trial', [CompanyController::class, 'renovarTrial'])->name('companies.renovarTrial');
    Route::get('companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
});

// Rota da API para buscar dados do CNPJ
Route::get('/api/cnpj/{cnpj}', [CnpjController::class, 'search']);

// Rotas do Caixa
Route::prefix('caixa')->middleware(['auth'])->group(function () {
    Route::get('/', [\App\Http\Controllers\CashRegisterController::class, 'index'])->name('caixa.index');
    Route::post('/abrir', [\App\Http\Controllers\CashRegisterController::class, 'open'])->name('caixa.open');
    Route::get('/{id}', [\App\Http\Controllers\CashRegisterController::class, 'show'])->name('caixa.show');
    Route::post('/{id}/fechar', [\App\Http\Controllers\CashRegisterController::class, 'close'])->name('caixa.close');
    Route::get('/{id}/relatorio', [\App\Http\Controllers\CashRegisterController::class, 'report'])->name('caixa.report');
    // Movimentações
    Route::get('/{id}/movimentacoes', [\App\Http\Controllers\CashMovementController::class, 'index'])->name('caixa.movements');
    Route::post('/{id}/movimentacoes', [\App\Http\Controllers\CashMovementController::class, 'store'])->name('caixa.movements.store');
});

// Rotas do PDV
Route::prefix('pdv')->middleware(['auth'])->group(function () {
    Route::get('/', [\App\Http\Controllers\PDVController::class, 'index'])->name('pdv.index');
    Route::get('/full', [\App\Http\Controllers\PDVController::class, 'index'])->name('pdv.full');
    Route::post('/iniciar', [\App\Http\Controllers\PDVController::class, 'startSale'])->name('pdv.start');
    Route::post('/item', [\App\Http\Controllers\PDVController::class, 'addItem'])->name('pdv.addItem');
    Route::delete('/item/{itemId}', [\App\Http\Controllers\PDVController::class, 'removeItem'])->name('pdv.removeItem');
    Route::post('/desconto', [\App\Http\Controllers\PDVController::class, 'applyDiscount'])->name('pdv.discount');
    Route::post('/pagamento', [\App\Http\Controllers\PDVController::class, 'addPayment'])->name('pdv.addPayment');
    Route::post('/finalizar', [\App\Http\Controllers\PDVController::class, 'finalize'])->name('pdv.finalize');
    Route::get('/venda/{id}/comprovante', [\App\Http\Controllers\PDVController::class, 'receipt'])->name('pdv.receipt');
    Route::get('/historico', [\App\Http\Controllers\PDVController::class, 'history'])->name('pdv.history');
    Route::post('/consulta-preco', [\App\Http\Controllers\PDVController::class, 'priceLookup'])->name('pdv.priceLookup');
});

Route::post('/pdv/finalizar-nf', [\App\Http\Controllers\PDVController::class, 'finalizeWithInvoice'])->middleware(['auth'])->name('pdv.finalizeWithInvoice');
Route::post('/pdv/finalizar-sem-nf', [\App\Http\Controllers\PDVController::class, 'finalizeWithoutInvoice'])->middleware(['auth'])->name('pdv.finalizeWithoutInvoice');
Route::post('/pdv/finalizar', [\App\Http\Controllers\PDVController::class, 'finalize'])->middleware(['auth'])->name('pdv.finalize');
Route::get('/pdv/cupom/{id}', [\App\Http\Controllers\PDVController::class, 'cupom'])->middleware(['auth'])->name('pdv.cupom');
Route::get('/pdv/romaneio/{id}', [\App\Http\Controllers\PDVController::class, 'romaneio'])->middleware(['auth'])->name('pdv.romaneio');
Route::post('/pdv/cancelar', [\App\Http\Controllers\PDVController::class, 'cancelSale'])->middleware(['auth'])->name('pdv.cancelSale');

// Rotas de Orçamentos
Route::get('/quotes', [QuoteController::class, 'index'])->name('quotes.index');
Route::get('/quotes/create', [QuoteController::class, 'create'])->name('quotes.create');
Route::post('/quotes', [QuoteController::class, 'store'])->name('quotes.store');
Route::get('/quotes/{quote}', [QuoteController::class, 'show'])->name('quotes.show');
Route::post('/quotes/{quote}/convert', [QuoteController::class, 'convertToSale'])->name('quotes.convert');
Route::get('/quotes/{quote}/pdf', [QuoteController::class, 'generatePdf'])->name('quotes.pdf');

// Rota temporária para recalcular saldo do caixa
Route::get('/debug/recalcular-caixa/{id}', [PDVController::class, 'recalculateCashRegister'])->name('debug.recalcular');

// Rota temporária para testar busca de fornecedores
Route::get('/debug/test-suppliers', function(Request $request) {
    $search = $request->get('search', '');
    $cleanSearch = preg_replace('/[^0-9]/', '', $search);
    
    $suppliers = App\Models\Supplier::where('company_id', 8) // Usando company_id fixo para teste
        ->where(function($query) use ($search, $cleanSearch) {
            $query->where('name', 'like', '%' . $search . '%')
                  ->orWhere('cnpj', 'like', '%' . $search . '%')
                  ->orWhere('cnpj', 'like', '%' . $cleanSearch . '%');
        })
        ->where('status', 'ativo')
        ->orderBy('name')
        ->limit(10)
        ->get();
    
    return response()->json([
        'search' => $search,
        'cleanSearch' => $cleanSearch,
        'count' => $suppliers->count(),
        'suppliers' => $suppliers
    ]);
});

// Rotas específicas para arquivos (BSDrive) - DEVEM VIR ANTES da rota genérica files/{path}
Route::middleware(['auth'])->group(function () {
    Route::get('files/{id}/preview', [FileController::class, 'preview'])->name('files.preview');
    Route::get('files/{id}/view-image', [FileController::class, 'viewImage'])->name('files.view-image');
    Route::get('files/{id}/download', [FileController::class, 'download'])->name('files.download');
    Route::post('files/bulk-delete', [FileController::class, 'bulkDelete'])->name('files.bulk-delete');
    Route::post('files/upload-folder', [FileController::class, 'uploadFolder'])->name('files.upload-folder');
    // Viewer routes
    Route::get('files/{id}/view', [FileViewerController::class, 'view'])->name('files.view');
    Route::get('files/{id}/content', [FileViewerController::class, 'getContent'])->name('files.content');
    // Public signed stream for Office viewer
    Route::get('files/{id}/public-stream', [FileController::class, 'publicStream'])
        ->withoutMiddleware(['auth'])
        ->name('files.public-stream');
});

// Rotas para pastas (Google Drive)
Route::middleware(['auth'])->group(function () {
    // Index e atalho "Minhas Pastas"
    Route::get('folders', [GoogleDriveFolderController::class, 'index'])->name('folders.index');
    Route::get('my-folders', [GoogleDriveFolderController::class, 'index'])->name('my-folders.index');

    // CRUD básico sobre pastas do Google Drive
    Route::get('folders/create', [GoogleDriveFolderController::class, 'create'])->name('folders.create');
    Route::post('folders', [GoogleDriveFolderController::class, 'store'])->name('folders.store');
    Route::get('folders/{id}', [GoogleDriveFolderController::class, 'show'])->name('folders.show');
    Route::get('folders/{id}/edit', [GoogleDriveFolderController::class, 'edit'])->name('folders.edit');
    Route::put('folders/{id}', [GoogleDriveFolderController::class, 'update'])->name('folders.update');
    Route::patch('folders/{id}', [GoogleDriveFolderController::class, 'update']);
    Route::delete('folders/{id}', [GoogleDriveFolderController::class, 'destroy'])->name('folders.destroy');
});

// Rota para servir arquivos de imagem
Route::get('files/{path}', function ($path) {
    $filePath = public_path('uploads/' . $path);
    
    if (!file_exists($filePath)) {
        abort(404);
    }
    
    $mimeType = mime_content_type($filePath);
    
    return response()->file($filePath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000'
    ]);
})->where('path', '.*')->name('files.serve');

// Rotas específicas para romaneios (AJAX)
Route::post('delivery-receipts/{deliveryReceipt}/items/{item}/toggle', [App\Http\Controllers\DeliveryReceiptController::class, 'toggleItem'])
    ->name('delivery_receipts.toggle_item');
Route::post('delivery-receipts/{deliveryReceipt}/finalize', [App\Http\Controllers\DeliveryReceiptController::class, 'finalize'])
    ->name('delivery_receipts.finalize');

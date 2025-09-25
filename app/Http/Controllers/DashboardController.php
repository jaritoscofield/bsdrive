<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    public function index()
    {
        try {
            $user = Auth::user();
            
            // O sistema usa uma conta Google única configurada via Service Account
            // Não é necessário forçar autenticação OAuth individual para cada usuário
            // A conta do Google Drive é única e configurada pelo administrador
            
            return view('dashboard');
            
        } catch (\Exception $e) {
            Log::error('❌ Erro no dashboard: ' . $e->getMessage());
            
            // Em caso de erro, mostrar o dashboard com indicação de erro
            // mas não forçar login no Google
            session()->flash('error', 'Erro ao carregar algumas funcionalidades. Tente novamente.');
            return view('dashboard')->with('hasError', true);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\Request;

class GoogleOAuthController extends Controller
{
    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Redirecionar para Google OAuth
     */
    public function redirectToGoogle()
    {
        try {
            $authUrl = $this->googleDriveService->getAuthUrl();
            \Log::emergency('🔗 Redirecionando para OAuth Google: ' . $authUrl);
            return redirect($authUrl);
        } catch (\Exception $e) {
            \Log::emergency('❌ Erro ao gerar URL OAuth: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Erro ao configurar OAuth: ' . $e->getMessage()]);
        }
    }

    /**
     * Processar callback do Google OAuth
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $code = $request->get('code');
            
            if (!$code) {
                throw new \Exception('Código de autorização não recebido');
            }

            \Log::emergency('🔄 Processando callback OAuth com código: ' . substr($code, 0, 20) . '...');
            
            $success = $this->googleDriveService->handleAuthCallback($code);
            
            if ($success) {
                \Log::emergency('✅ OAuth configurado com sucesso!');
                return redirect()->route('dashboard')->with('success', '✅ Google Drive configurado com sucesso! Agora você pode fazer uploads sem Shared Drive.');
            } else {
                throw new \Exception('Falha ao processar autorização');
            }
            
        } catch (\Exception $e) {
            \Log::emergency('❌ Erro no callback OAuth: ' . $e->getMessage());
            return redirect()->route('dashboard')->withErrors(['error' => 'Erro na autorização: ' . $e->getMessage()]);
        }
    }

    /**
     * Verificar status da autenticação
     */
    public function checkAuthStatus()
    {
        $isAuthenticated = $this->googleDriveService->isAuthenticated();
        
        return response()->json([
            'authenticated' => $isAuthenticated,
            'message' => $isAuthenticated 
                ? 'Google Drive está autenticado via OAuth' 
                : 'Google Drive precisa ser autorizado'
        ]);
    }

    /**
     * Remover autenticação OAuth
     */
    public function revokeAuth()
    {
        try {
            $tokenPath = storage_path('app/google_oauth_token.json');
            if (file_exists($tokenPath)) {
                unlink($tokenPath);
                \Log::emergency('🗑️  Token OAuth removido');
            }
            
            return redirect()->route('dashboard')->with('success', 'Autorização Google Drive removida com sucesso');
        } catch (\Exception $e) {
            \Log::emergency('❌ Erro ao remover autorização: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Erro ao remover autorização: ' . $e->getMessage()]);
        }
    }
}

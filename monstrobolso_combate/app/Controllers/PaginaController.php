<?php
namespace Maremon\Controllers;
use Maremon\Core\Sessao;
use Maremon\Core\Conexao;
use Maremon\Models\ContaRepository;

final class PaginaController {
    private const PAGINAS = ['index','equipe','historico','inventario','pokedex','escolher_inicial','login','cadastro'];
    public function mostrar(string $pagina): void {
        if (!in_array($pagina, self::PAGINAS, true)) { http_response_code(404); return; }
        Sessao::iniciar();
        $publica = in_array($pagina, ['login','cadastro'], true);
        if (!$publica && empty($_SESSION['pescador_id'])) {
            header('Location: login.php'); return;
        }
        if (in_array($pagina, ['index','escolher_inicial'], true)) {
            $possui = (new ContaRepository(Conexao::abrir()))->possuiPescado((int) $_SESSION['pescador_id']);
            if ($pagina === 'index' && !$possui) { header('Location: escolher_inicial.php'); return; }
            if ($pagina === 'escolher_inicial' && $possui) { header('Location: index.php'); return; }
        }
        if ($pagina === 'escolher_inicial') {
            $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
            $token = $_SESSION['csrf_token'];
        }
        require MAREMON_ROOT . '/app/Views/' . $pagina . '.php';
    }
}

<?php
namespace Maremon\Controllers\Http;

use PDO;
use Throwable;

final class ListarItensCombateAction {
    public function executar(): void {
        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store, private");

        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();

        $combate = $_SESSION["combate"] ?? null;

        if (
            $_SERVER["REQUEST_METHOD"] !== "GET" ||
            !is_array($combate) ||
            !($combate["ativo"] ?? false) ||
            (int) ($combate["pescador_id"] ?? 0) !== $pescadorId
        ) {
            http_response_code(409);
            echo json_encode([
                "sucesso" => false,
                "erro" => "Combate não disponível."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $stmt = (new \Maremon\Models\InventarioRepository($pdo))->listarItensBatalha(["id" => $pescadorId]);
            $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = (new \Maremon\Models\InventarioRepository($pdo))->listarAlvosBatalha(["id" => $pescadorId]);
            $pescados = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                                        
            $idsEquipe = array_map("intval", array_keys($combate["equipe"] ?? []));
            $pescados = array_values(array_filter(
                $pescados,
                static fn(array $p): bool => in_array((int) $p["id"], $idsEquipe, true)
            ));

            foreach ($pescados as &$pescado) {
                if (isset($combate["equipe"][(int) $pescado["id"]])) {
                    $membro = $combate["equipe"][(int) $pescado["id"]];
                    $pescado["hp_atual"] = $membro["hp"];
                    $pescado["status"] = $membro["status"] ?? "normal";
                }
                if ((int) $pescado["id"] === (int) $combate["jogador"]["id"]) {
                    $pescado["hp_atual"] = $combate["jogador"]["hp"];
                    $pescado["status"] = $combate["jogador"]["status"] ?? "normal";
                }
            }
            unset($pescado);

            echo json_encode([
                "sucesso" => true,
                "itens" => $itens,
                "pescados" => $pescados,
                "csrf_token" => $combate["csrf_acao"] ?? null
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            error_log((string) $e);
            http_response_code(500);
            echo json_encode([
                "sucesso" => false,
                "erro" => "Erro ao abrir a mochila."
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

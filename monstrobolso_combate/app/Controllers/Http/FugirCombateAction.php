<?php
namespace Maremon\Controllers\Http;

use Throwable;

final class FugirCombateAction {
    public function executar(): void {
        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store, private");

        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            echo json_encode([
                "sucesso" => false,
                "erro" => "Método não permitido."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $combate = $_SESSION["combate"] ?? null;

        if (
            !is_array($combate) ||
            (int) ($combate["pescador_id"] ?? 0) !== $pescadorId
        ) {
            http_response_code(403);
            echo json_encode([
                "sucesso" => false,
                "erro" => "Combate inválido."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!($combate["ativo"] ?? false)) {
            http_response_code(409);
            echo json_encode([
                "sucesso" => false,
                "erro" => "Este combate já foi encerrado."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $entrada = json_decode(file_get_contents("php://input"), true);
        \Maremon\Core\TokenCombate::exigirTokenCombate($combate, is_array($entrada) ? $entrada : [], "csrf_acao");

        try {
            $pdo->beginTransaction();

            // Preservar HP e condições de todos os integrantes da batalha.
            \Maremon\Services\EquipeCombate::salvar($pdo, $pescadorId, $combate);

            \Maremon\Models\LogCombateRepository::atualizarLogCombate(
                $pdo,
                (int) $combate["log_id"],
                $pescadorId,
                "fuga",
                max(0, (int) $combate["turno"] - 1),
                0
            );

            $pdo->commit();
            $_SESSION["combate"]["ativo"] = false;

            echo json_encode([
                "sucesso" => true,
                "mensagem" => "Você fugiu do combate."
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());
            http_response_code(500);

            echo json_encode([
                "sucesso" => false,
                "erro" => "Não foi possível registrar a fuga."
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

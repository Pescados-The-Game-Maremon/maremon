<?php
namespace Maremon\Controllers\Http;

use Throwable;
use Maremon\Controllers\CombateController;
use Maremon\Exceptions\JogoException;

final class ExecutarTurnoAction {
    public function executar(): void {
        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store, private");

        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->responderTurno([
                "sucesso" => false,
                "erro" => "Utilize POST."
            ], 405);
        }

        $dados = json_decode(file_get_contents("php://input"), true);

        if (!is_array($dados)) {
            $this->responderTurno([
                "sucesso" => false,
                "erro" => "Dados inválidos."
            ], 400);
        }

        $combate = $_SESSION["combate"] ?? null;

        if (
            !is_array($combate) ||
            !($combate["ativo"] ?? false) ||
            (int) ($combate["pescador_id"] ?? 0) !== $pescadorId
        ) {
            $this->responderTurno([
                "sucesso" => false,
                "erro" => "Combate inválido ou encerrado. Inicie outro encontro."
            ], 409);
        }

        \Maremon\Core\TokenCombate::exigirTokenCombate($combate, $dados, "csrf_acao");

        $ataqueId = filter_var($dados["ataque_id"] ?? null, FILTER_VALIDATE_INT);

        if (!$ataqueId || $ataqueId < 1) {
            $this->responderTurno([
                "sucesso" => false,
                "erro" => "Ataque inválido."
            ], 400);
        }

        try {
            $controller = new CombateController($pdo);
            $resultado = $controller->atacar($pescadorId, $combate, (int) $ataqueId);

                                                                                  
            $_SESSION["combate"] = $combate;
            $this->responderTurno($resultado);

        } catch (JogoException $e) {
            $this->responderTurno([
                "sucesso" => false,
                "erro" => $e->getMessage()
            ], 400);

        } catch (Throwable $e) {
            error_log((string) $e);
            $this->responderTurno([
                "sucesso" => false,
                "erro" => "Não foi possível executar o turno."
            ], 500);
        }
    }

    private function responderTurno(array $dados, int $status = 200): never
    {
        http_response_code($status);
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

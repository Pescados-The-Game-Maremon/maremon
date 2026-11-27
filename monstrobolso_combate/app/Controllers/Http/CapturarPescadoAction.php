<?php
namespace Maremon\Controllers\Http;

use Throwable;
use Exception;

final class CapturarPescadoAction {
    public function executar(): void {
        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store, private");

        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->responder([
                "sucesso" => false,
                "erro" => "Utilize POST."
            ], 405);
        }

        $entrada = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!is_array($entrada)) {
            $this->responder([
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
            $this->responder([
                "sucesso" => false,
                "erro" => "Combate inválido ou encerrado."
            ], 403);
        }

        \Maremon\Core\TokenCombate::exigirTokenCombate($combate, $entrada, "csrf_captura");

                                                                                            
        $nomeInimigo = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) ($combate["inimigo"]["nome"] ?? "")));
        if (in_array($nomeInimigo, ["umibozu", "umizobu"], true)) {
            $this->responder([
                "sucesso" => false,
                "erro" => "Umi Bozu não pode ser capturado."
            ], 403);
        }

        if (!empty($combate["boss_ordem"])) {
            $this->responder([
                "sucesso" => false,
                "erro" => "Chefes não podem ser capturados: derrote-o para liberar a próxima isca e o DNA."
            ], 403);
        }

        if ($combate["captura_tentada"] ?? false) {
            $this->responder([
                "sucesso" => false,
                "erro" => "Você já tentou capturar neste combate."
            ], 409);
        }

        $jogador = $combate["jogador"];
        $inimigo = $combate["inimigo"];

        if (
            (int) ($jogador["pescador_id"] ?? 0) !== $pescadorId ||
            (int) $jogador["hp"] <= 0 ||
            (int) $inimigo["hp"] <= 0
        ) {
            $this->responder([
                "sucesso" => false,
                "erro" => "Não é possível capturar agora."
            ], 400);
        }

        try {
            $pdo->beginTransaction();

                                                       
            $stmt = (new \Maremon\Models\CombateDadosRepository($pdo))->bloquearContaCaptura([
                "id" => $pescadorId
            ]);

            if (!$stmt->fetch()) {
                throw new Exception("Jogador não encontrado.");
            }

                                                                 
            $stmt = (new \Maremon\Models\CombateDadosRepository($pdo))->verificarCapturaExistente([
                "pescador_id" => $pescadorId,
                "pescado_id" => (int) $jogador["id"]
            ]);

            if (!$stmt->fetchColumn()) {
                $pdo->rollBack();

                $this->responder([
                    "sucesso" => false,
                    "erro" => "Seu Pescado não foi encontrado."
                ], 403);
            }

                                                           
            $stmt = (new \Maremon\Models\CombateDadosRepository($pdo))->verificarCapturaExistente([
                "pescador_id" => $pescadorId,
                "pescado_id" => (int) $inimigo["id"]
            ]);

            if ($stmt->fetchColumn()) {
                $pdo->rollBack();

                $this->responder([
                    "sucesso" => false,
                    "erro" => "Você já possui esta espécie."
                ], 409);
            }

            $hpAtual = (int) $inimigo["hp"];
            $hpMaximo = max(1, (int) $inimigo["hp_max"]);

                                                      
            $chance = 25 + 50 * (
                1 - $hpAtual / $hpMaximo
            );

            $sorteio = random_int(1, 100);

            $capturou = $sorteio <= $chance;

            if (!$capturou) {
                $pdo->rollBack();

                $_SESSION["combate"]["captura_tentada"] = true;

                $this->responder([
                    "sucesso" => true,
                    "capturou" => false,
                    "captura_tentada" => true,
                    "mensagem" => "O Pescado escapou! Continue o combate."
                ]);
            }

                                             
            $stmt = (new \Maremon\Models\CombateDadosRepository($pdo))->registrarCaptura([
                "pescador_id" => $pescadorId,
                "pescado_id" => (int) $inimigo["id"],
                "nivel" => (int) $inimigo["nivel"],
                "hp_atual" => $hpAtual,
                "status" => $inimigo["status"] ?? "normal",
                "status_turnos" => (int)($inimigo["status_turnos"] ?? 0)
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception(
                    "A espécie já pertence ao jogador."
                );
            }

                                                        
            $stmt = (new \Maremon\Models\CombateDadosRepository($pdo))->atualizarPescadoAposCaptura([
                "hp" => (int) $jogador["hp"],
                "status" => $jogador["status"] ?? "normal",
                "status_turnos" => (int)($jogador["status_turnos"] ?? 0),
                "pescador_id" => $pescadorId,
                "pescado_id" => (int) $jogador["id"]
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception(
                    "Não foi possível atualizar o HP."
                );
            }

            \Maremon\Models\LogCombateRepository::atualizarLogCombate(
            $pdo,
            (int) $combate["log_id"],
            $pescadorId,
            "captura",
            max(0, (int) $combate["turno"] - 1),
            0
        );

            $pdo->commit();

                                               
            $_SESSION["combate"]["ativo"] = false;
            $_SESSION["combate"]["captura_tentada"] = true;

            $this->responder([
                "sucesso" => true,
                "capturou" => true,
                "pescado" => $inimigo["nome"],
                "mensagem" => $inimigo["nome"]
                    . " foi capturado com sucesso!"
            ]);

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $this->responder([
                "sucesso" => false,
                "erro" => "Não foi possível concluir a captura."
            ], 500);
        }
    }

    private function responder($dados, $codigo = 200) {
        http_response_code($codigo);

        echo json_encode(
            $dados,
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }
}

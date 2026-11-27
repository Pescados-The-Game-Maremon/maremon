<?php
namespace Maremon\Controllers\Http;

use PDO;
use Throwable;
use Maremon\Exceptions\JogoException;
use Maremon\Models\CombateRepository;
use Maremon\Services\CombateMotor;
use Maremon\Services\EquipeCombate;

final class TrocarPescadoCombateAction {
    public function executar(): void {
        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store, private");

        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->saidaTroca(["sucesso" => false, "erro" => "Utilize POST."], 405);
        }

        $dados = json_decode(file_get_contents("php://input"), true);
        $combate = $_SESSION["combate"] ?? null;

        if (!is_array($dados) || !is_array($combate)
            || !($combate["ativo"] ?? false)
            || (int) ($combate["pescador_id"] ?? 0) !== $pescadorId) {
            $this->saidaTroca(["sucesso" => false, "erro" => "Combate inválido."], 409);
        }

        \Maremon\Core\TokenCombate::exigirTokenCombate($combate, $dados, "csrf_acao");
        $novoId = filter_var($dados["pescado_id"] ?? null, FILTER_VALIDATE_INT);

        if (!$novoId || !isset($combate["equipe"][(int) $novoId])) {
            $this->saidaTroca(["sucesso" => false, "erro" => "Escolha um integrante da sua equipe."], 400);
        }

        EquipeCombate::sincronizar($combate);

        if ((int) $novoId === (int) $combate["jogador"]["id"]
            || (int) $combate["equipe"][(int) $novoId]["hp"] <= 0) {
            $this->saidaTroca(["sucesso" => false, "erro" => "Escolha outro Pescado com HP."], 400);
        }

        $forcada = EquipeCombate::exigeTroca($combate);
        $anterior = $combate["jogador"]["nome"];
        $combate["jogador"] = $combate["equipe"][(int) $novoId];
        $eventos = [];
        $moedas = 0;
        $expGanha = 0;

        try {
            $pdo->beginTransaction();

            if (!$forcada) {
                // A troca voluntária consome o turno; o inimigo responde.
                $repo = new CombateRepository($pdo);
                $ataques = $repo->ataques(
                    (int) $combate["inimigo"]["id"],
                    (int) $combate["inimigo"]["nivel"]
                );

                if (!$ataques) {
                    throw new JogoException("O Pescado inimigo não possui ataques.");
                }

                $ataque = $ataques[random_int(0, count($ataques) - 1)];
                $motor = new CombateMotor();
                $eventos = $motor->contraAtaque(
                    $combate["jogador"],
                    $combate["inimigo"],
                    $ataque
                );
            }

            EquipeCombate::sincronizar($combate);
            $resultado = EquipeCombate::resultado($combate);
            $expGanha = $resultado === "vitoria" ? 35 : 0;
            $moedas = $resultado === "vitoria" ? 25 : 0;

            if ($expGanha > 0) {
                $stmt = (new \Maremon\Models\CombateDadosRepository($pdo))->bloquearProgressaoTroca([
                    "jogador" => $pescadorId,
                    "pescado" => (int) $novoId
                ]);
                $progresso = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$progresso) {
                    throw new JogoException("Pescado não encontrado.");
                }

                $nivel = \Maremon\Services\ExperienciaService::calcularNivel((int) $progresso["nivel"],
                    (int) $progresso["exp"] + $expGanha);
                $ganhos = $nivel - (int) $progresso["nivel"];
                $combate["jogador"]["nivel"] = $nivel;
                $combate["jogador"]["hp_max"] += 2 * $ganhos;
                foreach (["atk", "def", "spe"] as $campo) {
                    $combate["jogador"][$campo] += $ganhos;
                }
                $combate["jogador"]["hp"] = min(
                    (int) $combate["jogador"]["hp_max"],
                    (int) $combate["jogador"]["hp"] + 2 * $ganhos
                );
                $stmt = (new \Maremon\Models\CombateDadosRepository($pdo))->salvarProgressaoTroca([
                    "nivel" => $nivel,
                    "exp" => (int) $progresso["exp"] + $expGanha,
                    "jogador" => $pescadorId,
                    "pescado" => (int) $novoId
                ]);
                $stmt = (new \Maremon\Models\CombateDadosRepository($pdo))->creditarMoedasTroca(["moedas" => $moedas, "id" => $pescadorId]);
            }

            EquipeCombate::salvar($pdo, $pescadorId, $combate);
            \Maremon\Models\LogCombateRepository::atualizarLogCombate(
                $pdo,
                (int) $combate["log_id"],
                $pescadorId,
                $resultado === "continuar" ? "ativo" : $resultado,
                (int) $combate["turno"],
                $moedas
            );
            $pdo->commit();

            if ($resultado !== "continuar") {
                $combate["ativo"] = false;
            }

            if (!$forcada) {
                $combate["turno"]++;
            }

            $_SESSION["combate"] = $combate;

            $this->saidaTroca([
                "sucesso" => true,
                "mensagem" => $anterior . " foi substituído por "
                    . $combate["jogador"]["nome"] . "!"
                    . ($forcada ? "" : " O inimigo respondeu."),
                "jogador" => $combate["jogador"],
                "inimigo" => $combate["inimigo"],
                "equipe" => EquipeCombate::membros($combate),
                "troca_obrigatoria" => EquipeCombate::exigeTroca($combate),
                "eventos" => $eventos,
                "resultado" => $resultado,
                "turno" => $combate["turno"],
                "exp_ganha" => $expGanha,
                "dinheiro_ganho" => $moedas
            ]);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log((string) $e);
            $this->saidaTroca([
                "sucesso" => false,
                "erro" => $e instanceof JogoException
                    ? $e->getMessage() : "Não foi possível trocar de Pescado."
            ], $e instanceof JogoException ? 400 : 500);
        }
    }

    private function saidaTroca(array $dados, int $status = 200): never
    {
        http_response_code($status);
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

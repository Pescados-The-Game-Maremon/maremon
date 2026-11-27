<?php
namespace Maremon\Controllers\Http;

use Throwable;
use Exception;

final class SelecionarInicialAction {
    public function executar(): void {
        header("Content-Type: application/json; charset=utf-8");

        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->responder(false, "Método não permitido.", 405);
        }

        $dados = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!is_array($dados)) {
            $this->responder(false, "Dados inválidos.", 400);
        }

        $tokenRecebido = $dados["csrf_token"] ?? null;
        $tokenSessao = $_SESSION["csrf_token"] ?? null;

        if (
            !is_string($tokenRecebido) ||
            !is_string($tokenSessao) ||
            !hash_equals($tokenSessao, $tokenRecebido)
        ) {
            $this->responder(false, "Solicitação não autorizada.", 403);
        }

        $pescadoEscolhido = $dados["pescado"] ?? null;

        $iniciaisPermitidos = [
            "Trilobita",
            "Anomalocaris",
            "Hallucigenia"
        ];

        if (
            !is_string($pescadoEscolhido) ||
            !in_array($pescadoEscolhido, $iniciaisPermitidos, true)
        ) {
            $this->responder(false, "Pescado inicial inválido.", 400);
        }

        try {
            $pdo->beginTransaction();

                                                           
                                                
            $stmt = (new \Maremon\Models\EquipeDadosRepository($pdo))->bloquearContaInicial([
                "pescador_id" => $pescadorId
            ]);

            if (!$stmt->fetch()) {
                throw new Exception("Jogador não encontrado.");
            }

                                                           
            $stmt = (new \Maremon\Models\EquipeDadosRepository($pdo))->contarPescados([
                "pescador_id" => $pescadorId
            ]);

            if ((int) $stmt->fetchColumn() > 0) {
                $pdo->rollBack();

                $this->responder(
                    false,
                    "Você já possui um Pescado inicial.",
                    409
                );
            }

                                                    
            $stmt = (new \Maremon\Models\EquipeDadosRepository($pdo))->buscarEspecieInicial([
                "nome" => $pescadoEscolhido
            ]);

            $especiesEncontradas = $stmt->fetchAll();

            if (count($especiesEncontradas) !== 1) {
                throw new Exception(
                    "Espécie não encontrada ou cadastrada em duplicidade."
                );
            }

            $pescado = $especiesEncontradas[0];

            $stmt = (new \Maremon\Models\EquipeDadosRepository($pdo))->inserirInicial([
                "pescador_id" => $pescadorId,
                "pescado_id" => (int) $pescado["id"],
                "nivel" => 5,
                "exp" => 0,
                "hp_atual" => (int) $pescado["hp"]
            ]);

                                                    
            $pdo->commit();

                                                          
            unset($_SESSION["csrf_token"]);

            $this->responder(
                true,
                "Você escolheu " . $pescadoEscolhido . "!"
            );

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $this->responder(
                false,
                "Não foi possível salvar seu Pescado inicial.",
                500
            );
        }
    }

    private function responder($sucesso, $mensagem, $codigo = 200) {
        http_response_code($codigo);

        echo json_encode([
            "sucesso" => $sucesso,
            $sucesso ? "mensagem" : "erro" => $mensagem
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

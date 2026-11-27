<?php
namespace Maremon\Core;


final class TokenCombate {
       
                                                                               
                                                                     
                                                                           
       
    public static function tokenCombateValido(array $combate, array $entrada, string $campo): bool
    {
        $chaveSessao = $campo === "csrf_captura" ? "csrf_captura" : "csrf_acao";
        $recebido = $entrada[$campo] ?? null;
        $esperado = $combate[$chaveSessao] ?? null;

        return is_string($recebido)
            && is_string($esperado)
            && strlen($esperado) === 64
            && hash_equals($esperado, $recebido);
    }

    public static function exigirTokenCombate(array $combate, array $entrada, string $campo): void
    {
                                                                              
        $idRecebido = filter_var($entrada["combate_id"] ?? null, FILTER_VALIDATE_INT);
        $idEsperado = (int) ($combate["log_id"] ?? 0);

        if (!$idRecebido || $idRecebido !== $idEsperado) {
            http_response_code(409);
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode([
                "sucesso" => false,
                "codigo" => "COMBATE_SUBSTITUIDO",
                "erro" => "Este combate foi substituído. Inicie um novo encontro."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (self::tokenCombateValido($combate, $entrada, $campo)) {
            return;
        }

                                                                          
                                                                                   
        http_response_code(403);
        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store, private");

        echo json_encode([
            "sucesso" => false,
            "codigo" => "TOKEN_DESATUALIZADO",
            "erro" => "A autorização do combate expirou. Atualize a batalha."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

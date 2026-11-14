
<?php

header("Content-Type: application/json; charset=utf-8");

session_start();
require_once __DIR__ . "/conexao.php";

function responder($dados, $codigo = 200) {
    http_response_code($codigo);

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

function calcularDano($atacante, $defensor, $poder) {
    $atk = max(1, (int) $atacante["atk"]);
    $def = max(1, (int) $defensor["def"]);

    return max(
        1,
        (int) floor(($atk * $poder) / ($def * 5))
    );
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder([
        "sucesso" => false,
        "erro" => "Utilize uma requisição POST."
    ], 405);
}

if (
    !isset($_SESSION["combate"]) ||
    !($_SESSION["combate"]["ativo"] ?? false)
) {
    responder([
        "sucesso" => false,
        "erro" => "Nenhum combate ativo."
    ], 400);
}

$entrada = json_decode(file_get_contents("php://input"), true);
$ataqueId = filter_var(
    $entrada["ataque_id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$ataqueId || $ataqueId < 1) {
    responder([
        "sucesso" => false,
        "erro" => "Ataque inválido."
    ], 400);
}

try {
    $combate = &$_SESSION["combate"];

    $jogador = &$combate["jogador"];
    $inimigo = &$combate["inimigo"];

    // Buscar os ataques disponíveis para cada espécie.
    $sql = "
        SELECT
            a.id,
            a.nome,
            a.poder,
            a.precisao
        FROM public.pescado_ataques pa
        JOIN public.ataques a
            ON a.id = pa.ataque_id
        WHERE pa.pescado_id = :pescado_id
        ORDER BY a.id
        LIMIT 4
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        "pescado_id" => $jogador["id"]
    ]);

    $ataquesJogador = $stmt->fetchAll();

    $stmt->execute([
        "pescado_id" => $inimigo["id"]
    ]);

    $ataquesInimigo = $stmt->fetchAll();

    $ataqueEscolhido = null;

    foreach ($ataquesJogador as $ataque) {
        if ((int) $ataque["id"] === $ataqueId) {
            $ataqueEscolhido = $ataque;
            break;
        }
    }

    if (!$ataqueEscolhido) {
        responder([
            "sucesso" => false,
            "erro" => "Este ataque não está disponível para seu Pescado."
        ], 400);
    }

    if (count($ataquesInimigo) === 0) {
        responder([
            "sucesso" => false,
            "erro" => "O inimigo não possui ataques cadastrados."
        ], 400);
    }

    $ataqueInimigo = $ataquesInimigo[
        random_int(0, count($ataquesInimigo) - 1)
    ];

    $jogadorPrimeiro =
        (int) $jogador["spe"] >= (int) $inimigo["spe"];

    $ordem = $jogadorPrimeiro
        ? [
            ["jogador", $ataqueEscolhido],
            ["inimigo", $ataqueInimigo]
        ]
        : [
            ["inimigo", $ataqueInimigo],
            ["jogador", $ataqueEscolhido]
        ];

    $eventos = [];

    foreach ($ordem as [$lado, $ataque]) {
        if ($jogador["hp"] <= 0 || $inimigo["hp"] <= 0) {
            break;
        }

        $atacante = $lado === "jogador"
            ? $jogador
            : $inimigo;

        $defensor = &$inimigo;

        if ($lado === "inimigo") {
            $defensor = &$jogador;
        }

        $acertou = random_int(1, 100)
            <= (int) $ataque["precisao"];

        $dano = 0;

        if ($acertou) {
            $dano = calcularDano(
                $atacante,
                $defensor,
                (int) $ataque["poder"]
            );

            $defensor["hp"] = max(
                0,
                (int) $defensor["hp"] - $dano
            );
        }

        $eventos[] = [
            "atacante" => $atacante["nome"],
            "ataque" => $ataque["nome"],
            "acertou" => $acertou,
            "dano" => $dano
        ];

        unset($defensor);
    }

    
if ($inimigo["hp"] <= 0) {
    $resultado = "vitoria";
} elseif ($jogador["hp"] <= 0) {
    $resultado = "derrota";
} else {
    $resultado = "continuar";
}

// Experiência temporária por vitória
$expGanha = $resultado === "vitoria" ? 10 : 0;

// Salvar HP e experiência no PostgreSQL
$sqlSalvar = "
    UPDATE public.pescador_pescado
    SET
        hp_atual = :hp,
        exp = COALESCE(exp, 0) + :exp
    WHERE pescador_id = :pescador_id
      AND pescado_id = :pescado_id
";

$stmtSalvar = $pdo->prepare($sqlSalvar);

$stmtSalvar->execute([
    "hp" => (int) $jogador["hp"],
    "exp" => $expGanha,
    "pescador_id" => (int) $jogador["pescador_id"],
    "pescado_id" => (int) $jogador["id"]
]);

if ($stmtSalvar->rowCount() !== 1) {
    throw new Exception(
        "Não foi possível salvar o Pescado do jogador."
    );
}

// Finalizar a batalha depois de salvar
if ($resultado !== "continuar") {
    $combate["ativo"] = false;
}

$combate["turno"]++;


    responder([
        "sucesso" => true,
        "eventos" => $eventos,
        "jogador" => $jogador,
        "inimigo" => $inimigo,
        "resultado" => $resultado,
        "exp_ganha" => $expGanha,
        "turno" => $combate["turno"]
    ]);

} catch (Throwable $e) {
    error_log($e->getMessage());

    responder([
        "sucesso" => false,
        "erro" => "Não foi possível executar o turno."
    ], 500);
}

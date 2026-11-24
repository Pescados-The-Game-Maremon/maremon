<?php

function experienciaParaNivel(int $nivel): int {
    if ($nivel <= 5) {
        return 0;
    }

    return 10 * (($nivel - 1) ** 2 - 16);
}


function calcularNivel(
    int $nivelAtual,
    int $experiencia
): int {

    $nivel = max(5, min(100, $nivelAtual));
    $experiencia = max(0, $experiencia);

    while (
        $nivel < 100 &&
        $experiencia >= experienciaParaNivel($nivel + 1)
    ) {
        $nivel++;
    }

    return $nivel;
}


function calcularAtributos(
    array $pescado,
    int $nivel
): array {

    $nivel = max(5, min(100, $nivel));

    $bonus = $nivel - 5;

    return [
        "hp_max" => max(
            1,
            (int) $pescado["hp_base"] + ($bonus * 2)
        ),

        "atk" => max(
            1,
            (int) $pescado["atk_base"] + $bonus
        ),

        "def" => max(
            1,
            (int) $pescado["def_base"] + $bonus
        ),

        "spe" => max(
            1,
            (int) $pescado["spe_base"] + $bonus
        )
    ];
}


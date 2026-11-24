<?php

namespace Maremon\Services;

use Maremon\Domain\Pescado;
use Maremon\Domain\TipoFactory;

final class CombateMotor
{
    public function multiplicador(string $tipoAtaque, string $tipoDefensor): float
    {
        $ataque = new Pescado(["tipo" => $tipoAtaque]);
        $defensor = TipoFactory::criar($tipoDefensor);

        return $ataque->tipo()->multiplicadorContra($defensor);
    }

    public function atacar(array &$atacante, array &$defensor, array $ataque): array
    {
        $nome = (string) $ataque["nome"];
        $bloqueio = StatusService::podeAgir($atacante);

        if ($bloqueio !== null) {
            return [
                "atacante" => $atacante["nome"],
                "ataque" => $nome,
                "acertou" => false,
                "dano" => 0,
                "mensagem" => $bloqueio
            ];
        }

        $precisao = max(0, min(100, (int) $ataque["precisao"]));
        $acertou = random_int(1, 100) <= $precisao;
        $dano = 0;
        $multiplicador = 1.0;
        $mensagem = "";

        if ($acertou) {
            $poder = max(0, (int) $ataque["poder"]);

            if ($poder > 0) {
                $multiplicador = $this->multiplicador(
                    (string) $ataque["tipo"],
                    (string) $defensor["tipo"]
                );

                $escudo = ($defensor["escudo"] ?? false) ? 0.65 : 1.0;
                $dano = (new DanoService())->calcular(
                    (int) $atacante["atk"],
                    (int) $defensor["def"],
                    $poder,
                    $multiplicador,
                    $escudo
                );

                $defensor["hp"] = max(0, (int) $defensor["hp"] - $dano);
                $defensor["escudo"] = false;
            }

            if ($nome === "Escudo Luminoso") {
                $atacante["escudo"] = true;
                $mensagem = $atacante["nome"] . " ergueu um escudo protetor!";
            } else {
                $mensagem = $atacante["nome"] . " usou " . $nome
                    . " e causou " . $dano . " de dano!";
            }

            if ($multiplicador > 1.0) {
                $mensagem .= " Super efetivo (1,3×)!";
            }

            $efeito = StatusService::aplicarAtaque($defensor, $ataque);

            if ($efeito !== null) {
                $mensagem .= " " . $efeito;
            }
        } else {
            $mensagem = $atacante["nome"] . " errou " . $nome . "!";
        }

        return [
            "atacante" => $atacante["nome"],
            "ataque" => $nome,
            "acertou" => $acertou,
            "dano" => $dano,
            "multiplicador" => $multiplicador,
            "mensagem" => $mensagem
        ];
    }

    public function executarTurno(
        array &$jogador,
        array &$inimigo,
        array $ataqueJogador,
        array $ataqueInimigo
    ): array {
        $eventos = [];

        $ordem = (int) $jogador["spe"] >= (int) $inimigo["spe"]
            ? [["jogador", $ataqueJogador], ["inimigo", $ataqueInimigo]]
            : [["inimigo", $ataqueInimigo], ["jogador", $ataqueJogador]];

        foreach ($ordem as [$lado, $ataque]) {
            if ((int) $jogador["hp"] <= 0 || (int) $inimigo["hp"] <= 0) {
                break;
            }

            if ($lado === "jogador") {
                $eventos[] = $this->atacar($jogador, $inimigo, $ataque);
            } else {
                $eventos[] = $this->atacar($inimigo, $jogador, $ataque);
            }
        }

        return array_merge($eventos, $this->efeitosFimDeTurno($jogador, $inimigo));
    }

    public function contraAtaque(array &$jogador, array &$inimigo, array $ataqueInimigo): array
    {
        $eventos = [];

        if ((int) $inimigo["hp"] > 0 && (int) $jogador["hp"] > 0) {
            $eventos[] = $this->atacar($inimigo, $jogador, $ataqueInimigo);
        }

        return array_merge($eventos, $this->efeitosFimDeTurno($jogador, $inimigo));
    }

    private function efeitosFimDeTurno(array &$jogador, array &$inimigo): array
    {
        $eventos = [];

        foreach (["jogador", "inimigo"] as $lado) {
            if ($lado === "jogador") {
                $alvo = &$jogador;
            } else {
                $alvo = &$inimigo;
            }

            $mensagem = StatusService::finalTurno($alvo);

            if ($mensagem !== null) {
                $eventos[] = [
                    "mensagem" => $mensagem,
                    "dano" => 0,
                    "acertou" => true,
                    "tipo" => "status"
                ];
            }

            unset($alvo);
        }

        return $eventos;
    }

    public static function resultado(array $jogador, array $inimigo): string
    {
        if ((int) $inimigo["hp"] <= 0 && (int) $jogador["hp"] > 0) {
            return "vitoria";
        }

        if ((int) $jogador["hp"] <= 0) {
            return "derrota";
        }

        return "continuar";
    }
}

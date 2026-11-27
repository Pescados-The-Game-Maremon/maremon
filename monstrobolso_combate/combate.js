// A interface envia ações; o PHP valida os tokens e resolve as regras da batalha.
const API_COMBATE = "php/";

let combateOcupado = false;
let combateIniciado = false;
let tokenCaptura = null;
let tokenAcao = null;
let combateId = null;
let capturaTentada = false;
let retornoPendente = false;
let equipeEmCombate = [];
let trocaObrigatoria = false;
let pescadoAtivoId = null;

async function requisitarCombate(arquivo, opcoes = {}, podeSincronizar = true) {
    const resposta = await fetch(API_COMBATE + arquivo, {
        credentials: "same-origin",
        cache: "no-store",
        ...opcoes
    });

    const texto = await resposta.text();
    let dados;

    try {
        dados = JSON.parse(texto);
    } catch (erro) {
        console.error("Resposta inesperada do PHP:", arquivo, resposta.status);
        throw new Error("O servidor retornou uma resposta inválida. Confira o log do Apache.");
    }

    // A ação rejeitada não alterou o banco nem a batalha. Recuperamos os
    // tokens da mesma sessão autenticada e permitimos só uma nova tentativa.
    if (
        resposta.status === 403 &&
        dados.codigo === "TOKEN_DESATUALIZADO" &&
        podeSincronizar &&
        opcoes.body
    ) {
        await sincronizarTokensCombate();

        const corpo = JSON.parse(opcoes.body);

        if (Object.prototype.hasOwnProperty.call(corpo, "csrf_acao")) {
            corpo.csrf_acao = tokenAcao;
        }

        if (Object.prototype.hasOwnProperty.call(corpo, "csrf_captura")) {
            corpo.csrf_captura = tokenCaptura;
        }

        if (Object.prototype.hasOwnProperty.call(corpo, "csrf_token")) {
            corpo.csrf_token = tokenAcao;
        }

        return requisitarCombate(arquivo, {
            ...opcoes,
            body: JSON.stringify(corpo)
        }, false);
    }

    if (!resposta.ok || !dados.sucesso) {
        throw new Error(dados.erro || `Erro no combate (HTTP ${resposta.status}).`);
    }

    return dados;
}

async function sincronizarTokensCombate() {
    const dados = await requisitarCombate("estado_combate.php", {}, false);

    if (
        typeof dados.csrf_acao !== "string" ||
        typeof dados.csrf_captura !== "string"
    ) {
        throw new Error("O combate precisa ser reiniciado para atualizar a sessão.");
    }

    if (combateId !== null && Number(dados.combate_id) !== combateId) {
        throw new Error("Outro combate foi iniciado. Abra uma nova batalha.");
    }

    combateId = Number(dados.combate_id);
    tokenAcao = dados.csrf_acao;
    tokenCaptura = dados.csrf_captura;
    capturaTentada = Boolean(dados.captura_tentada);
}

function mensagemCombate(texto) {
    const elemento = document.getElementById("mensagem_combate");

    if (elemento) {
        elemento.textContent = texto;
    }
}

function bloquearAtaques(bloquear) {
    document.querySelectorAll("#ataques_combate button").forEach(botao => {
        botao.disabled = bloquear;
    });

    const botaoItens = document.getElementById("botao_itens_combate");

    if (botaoItens) {
        botaoItens.disabled = bloquear || trocaObrigatoria;
    }

    const botaoTroca = document.getElementById("botao_trocar_combate");

    if (botaoTroca) {
        botaoTroca.disabled = bloquear;
    }

    for (const id of ["botao_lutar", "botao_fugir"]) {
        const botao = document.getElementById(id);

        if (botao) {
            botao.disabled = bloquear;
        }
    }
}

// A navegação visual usa os painéis existentes sem alterar a sessão PHP.
function exibirSubmenuCombate(painelAberto = null) {
    const ids = [
        "menu_ataques_combate",
        "troca_combate",
        "itens_combate",
        "painel_tipos"
    ];

    for (const id of ids) {
        const painel = document.getElementById(id);

        if (painel) {
            painel.hidden = id !== painelAberto;
        }
    }

    const principal = document.getElementById("menu_principal_combate");

    if (principal) {
        principal.hidden = painelAberto !== null;
    }

    const botaoTipos = document.getElementById("botao_tipos");

    if (botaoTipos) {
        botaoTipos.setAttribute(
            "aria-expanded",
            String(painelAberto === "painel_tipos")
        );
    }
}

function mostrarAtaquesCombate() {
    if (!combateIniciado || combateOcupado || trocaObrigatoria) {
        return;
    }

    exibirSubmenuCombate("menu_ataques_combate");
}

function fecharSubmenuCombate() {
    if (trocaObrigatoria) {
        return;
    }

    exibirSubmenuCombate();
}

function fecharTrocaCombate() {
    if (trocaObrigatoria) {
        mensagemCombate("Escolha outro Pescado para continuar a batalha.");
        return;
    }

    exibirSubmenuCombate();
}

// Animação cosmética universal para qualquer ataque ou contra-ataque.
// O PHP continua sendo a única fonte de verdade para dano e condições.
async function animarEventosCombate(eventos, nomeJogadorAntes) {
    if (!Array.isArray(eventos) ||
        window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        return;
    }

    for (const evento of eventos) {
        if (!evento.atacante || !evento.ataque) {
            continue;
        }

        const lado = evento.atacante === nomeJogadorAntes
            ? "jogador"
            : "inimigo";

        await animarGolpeMaremon(lado, Number(evento.dano) > 0);
    }
}

async function animarGolpeMaremon(lado, acertou) {
    const atacante = document.getElementById("sprite_" + lado);
    const defensor = document.getElementById(
        lado === "jogador" ? "sprite_inimigo" : "sprite_jogador"
    );

    if (!atacante || !defensor) {
        return;
    }

    atacante.classList.remove("animando_ataque");
    void atacante.offsetWidth; // Reinicia a animação em ataques consecutivos.
    atacante.classList.add("animando_ataque");

    if (acertou) {
        window.setTimeout(() => {
            // A cena pode ter mudado durante uma captura ou fuga.
            if (!atacante.isConnected || !defensor.isConnected) {
                return;
            }

            defensor.classList.remove("recebendo_impacto");
            void defensor.offsetWidth;
            defensor.classList.add("recebendo_impacto");

            const interfaceCombate = document.getElementById("interface_combate");

            if (interfaceCombate) {
                const flash = elementoHTML("<span></span>");
                flash.className = "flash_golpe " + (
                    lado === "jogador"
                        ? "flash_golpe_alvo_inimigo"
                        : "flash_golpe_alvo_jogador"
                );
                flash.setAttribute("aria-hidden", "true");
                interfaceCombate.appendChild(flash);
                window.setTimeout(() => flash.remove(), 310);
            }
        }, 155);
    }

    await new Promise(resolve => window.setTimeout(resolve, 390));
    atacante.classList.remove("animando_ataque");
    defensor.classList.remove("recebendo_impacto");
}

async function abrirCombate() {
    if (combateOcupado) {
        return;
    }

    combateOcupado = true;
    combateIniciado = false;
    capturaTentada = false;
    retornoPendente = false;
    tokenCaptura = null;
    tokenAcao = null;
    combateId = null;
    equipeEmCombate = [];
    trocaObrigatoria = false;
    pescadoAtivoId = null;


    const boia = document.getElementById("botao_boia");
    const aviso = document.getElementById("aviso_pesca");
    if (boia) boia.disabled = true;
    if (aviso) { aviso.hidden = false; aviso.textContent = "Procurando Pescado..."; }
    let inicioConfirmado = false;
    try {
        const dados = await requisitarCombate("iniciar_combate.php");
        inicioConfirmado = true;

        combateId = Number(dados.combate_id);
        tokenCaptura = dados.csrf_captura;
        tokenAcao = dados.csrf_acao;
        capturaTentada = Boolean(dados.captura_tentada);

        if (!tokenAcao || !tokenCaptura) {
            await sincronizarTokensCombate();
        }

        // A cena só abre depois que os sprites estão prontos.
        await prepararSpritesCombate([
            dados.jogador,
            dados.inimigo,
            ...(dados.equipe || [])
        ]);

        trocarfundo("fundo_combate");
        goTo("cena_combate");
        exibirSubmenuCombate();

        atualizarInterfaceCombate(dados);
        combateIniciado = true;
        await carregarAtaques();
        mensagemCombate(
            (dados.boss_ordem
                ? `⚠ CHEFE ${dados.inimigo.nome} apareceu! Tipo DRK, HP ×5. `
                : `Um ${dados.inimigo.nome} selvagem apareceu! `) +
            (equipeEmCombate.length < 2
                ? "A equipe está com apenas um integrante. Para habilitar a troca, " +
                  "organize mais Pescados na tela Minha Equipe e inicie outro combate."
                : `${equipeEmCombate.length} Pescados prontos para esta batalha.`)
        );
    } catch (erro) {
        if (!inicioConfirmado) {
            // O servidor recusou o encontro: permaneça na varanda.
            if (aviso?.isConnected) { aviso.hidden = false; aviso.textContent = erro.message; }
        } else {
            mensagemCombate(erro.message);
        }
    } finally {
        combateOcupado = false;
        if (boia?.isConnected) boia.disabled = false;
        if (inicioConfirmado) {
            atualizarBotaoCaptura();
            atualizarBotoesAcao();
        }
    }
}

function atualizarInterfaceCombate(dados) {
    if (dados.boss_ordem !== undefined) {
        const faixa = document.getElementById("aviso_boss");
        if (faixa) {
            faixa.hidden = !dados.boss_ordem;
            if (dados.boss_ordem) {
                faixa.textContent = `CHEFE · ${dados.inimigo?.nome || "MAREMON"} · DRK · HP ×5`;
            }
        }
        const capturar = document.getElementById("botao_capturar");
        if (capturar) capturar.dataset.boss = dados.boss_ordem ? "1" : "0";
    }
    if (Array.isArray(dados.equipe)) {
        equipeEmCombate = dados.equipe;
    }

    if (typeof dados.troca_obrigatoria === "boolean") {
        trocaObrigatoria = dados.troca_obrigatoria;
    }

    if (dados.jogador) {
        pescadoAtivoId = Number(dados.jogador.id);
    }

    renderizarEquipeCombate();
    atualizarPescado("jogador", dados.jogador);
    atualizarPescado("inimigo", dados.inimigo);

    if (typeof atualizarSprite === "function") {
        atualizarSprite("jogador", dados.jogador);
        atualizarSprite("inimigo", dados.inimigo);
    }
}

function atualizarPescado(lado, pescado) {
    const nome = document.getElementById("nome_" + lado);
    const hp = document.getElementById("hp_" + lado);
    const barra = document.getElementById("barra_" + lado);
    const painel = document.getElementById("painel_" + lado);

    if (!nome || !hp || !barra || !painel || !pescado) {
        return;
    }

    nome.textContent = pescado.nome;
    hp.textContent = `${pescado.hp}/${pescado.hp_max}`;
    painel.dataset.tipo = String(pescado.tipo || "LGT").toUpperCase();

    const nivel = document.getElementById("nivel_" + lado);

    if (nivel) {
        nivel.textContent = pescado.nivel;
    }

    const percentual = Math.max(
        0,
        Math.min(100, Number(pescado.hp) / Math.max(1, Number(pescado.hp_max)) * 100)
    );

    barra.style.width = percentual + "%";
    barra.style.background = percentual > 50
        ? "#46D6A1"
        : percentual > 20
            ? "#F6BD57"
            : "#F16B75";

    const status = document.getElementById("status_" + lado);

    if (status) {
        const estado = pescado.status || "normal";
        status.textContent = estado === "normal" ? "" : estado.toUpperCase();
        status.hidden = estado === "normal";
    }
}

async function carregarAtaques() {
    if (trocaObrigatoria) {
        document.getElementById("ataques_combate")?.replaceChildren();
        abrirTrocaCombate();
        return;
    }

    const dados = await requisitarCombate("buscar_ataques.php");
    const lista = document.getElementById("ataques_combate");

    if (!lista) {
        return;
    }

    lista.replaceChildren();

    if (!dados.ataques.length) {
        throw new Error("Este Pescado não possui ataques cadastrados.");
    }

    for (const ataque of dados.ataques) {
        const botao = elementoHTML("<button></button>");
        const nome = elementoHTML("<span></span>");
        const detalhes = elementoHTML("<small></small>");

        botao.type = "button";
        botao.className = "botao_ataque";
        nome.textContent = ataque.nome;
        detalhes.textContent = `${ataque.tipo} · PP ${ataque.pp_atual}/${ataque.pp_max}` +
            (ataque.status_aplicado
                ? ` · ${ataque.chance_status}% ${ataque.status_aplicado}`
                : "");

        botao.append(nome, detalhes);
        botao.disabled = Number(ataque.pp_atual) <= 0;
        botao.addEventListener("click", () => executarAtaque(ataque.id));
        lista.appendChild(botao);
    }
}

function textoEventos(eventos) {
    return (eventos || [])
        .map(evento => evento.mensagem || `${evento.atacante} usou ${evento.ataque}.`)
        .join(" ");
}

function encerrarVisual(resultado, texto, espera = 2500) {
    combateIniciado = false;
    tokenCaptura = null;
    tokenAcao = null;

    bloquearAtaques(true);
    atualizarBotaoCaptura();
    fecharItensCombate();
    exibirSubmenuCombate();
    mensagemCombate(texto);

    if (!retornoPendente) {
        retornoPendente = true;
        window.setTimeout(() => voltarParaPescando(), espera);
    }
}

async function executarAtaque(ataqueId) {
    if (combateOcupado || !combateIniciado || trocaObrigatoria) {
        return;
    }

    combateOcupado = true;
    bloquearAtaques(true);
    atualizarBotaoCaptura();

    try {
        const dados = await requisitarCombate("executar_turno.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                ataque_id: ataqueId,
                combate_id: combateId,
                csrf_acao: tokenAcao
            })
        });

        const nomeAtivoAntes = document.getElementById("nome_jogador")?.textContent;
        await animarEventosCombate(dados.eventos, nomeAtivoAntes);
        atualizarInterfaceCombate(dados);
        let texto = textoEventos(dados.eventos);

        if (dados.resultado === "vitoria") {
            texto += ` Vitória! ${dados.jogador.nome} recebeu ${dados.exp_ganha} EXP` +
                ` e você ganhou ${dados.dinheiro_ganho} moedas.`;
            if (dados.boss_vitoria?.primeira_vitoria) {
                texto += ` Chefe derrotado! DNA ${dados.boss_vitoria.dna_nome} liberado na loja.`;
                if (dados.boss_vitoria.proxima_isca) {
                    texto += ` Isca ${dados.boss_vitoria.proxima_isca} desbloqueada!`;
                }
            }
        } else if (dados.resultado === "derrota") {
            texto += " Todos os integrantes da equipe foram derrotados.";
        }

        if (dados.niveis_ganhos > 0) {
            texto += ` Subiu do nível ${dados.nivel_anterior} para ${dados.nivel_novo}!`;
        }

        if (dados.resultado !== "continuar") {
            encerrarVisual(dados.resultado, texto, dados.boss_vitoria ? 5500 : 2500);
        } else {
            mensagemCombate(texto);
            await carregarAtaques();
            if (trocaObrigatoria) {
                mensagemCombate(texto + " Escolha outro Pescado para continuar!");
            }
        }
    } catch (erro) {
        mensagemCombate(erro.message);
    } finally {
        combateOcupado = false;

        if (combateIniciado) {
            bloquearAtaques(false);
        }

        atualizarBotaoCaptura();
        atualizarBotoesAcao();
    }
}

async function fugirCombate() {
    if (combateOcupado || !combateIniciado) {
        return;
    }

    combateOcupado = true;
    bloquearAtaques(true);
    atualizarBotaoCaptura();

    try {
        await requisitarCombate("fugir_combate.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ combate_id: combateId, csrf_acao: tokenAcao })
        });

        combateIniciado = false;
        tokenCaptura = null;
        tokenAcao = null;
        capturaTentada = false;
        voltarParaPescando();
    } catch (erro) {
        mensagemCombate(erro.message);
    } finally {
        combateOcupado = false;

        if (combateIniciado) {
            bloquearAtaques(false);
        }

        atualizarBotaoCaptura();
        atualizarBotoesAcao();
    }
}

function voltarParaPescando() {
    trocarfundo("varanda");
    goTo("cena_varanda");
    if (typeof carregarIscas === "function") carregarIscas();
}

function atualizarBotaoCaptura() {
    const botao = document.getElementById("botao_capturar");

    if (botao) {
        botao.disabled = !combateIniciado || combateOcupado || capturaTentada || trocaObrigatoria || botao.dataset.boss === "1";
        botao.title = botao.dataset.boss === "1" ? "Chefes não podem ser capturados." : "Capturar Pescado";
    }
}

function atualizarBotoesAcao() {
    const indisponivel = !combateIniciado || combateOcupado;

    for (const id of ["botao_itens_combate", "botao_lutar", "botao_fugir"]) {
        const botao = document.getElementById(id);

        if (botao) {
            botao.disabled = indisponivel || (id !== "botao_fugir" && trocaObrigatoria);
        }
    }

    renderizarEquipeCombate();
}

async function capturarPescado() {
    if (combateOcupado || !combateIniciado || capturaTentada) {
        return;
    }

    combateOcupado = true;
    bloquearAtaques(true);
    atualizarBotaoCaptura();

    try {
        const dados = await requisitarCombate("capturar_pescado.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ combate_id: combateId, csrf_captura: tokenCaptura })
        });

        capturaTentada = Boolean(dados.captura_tentada);

        if (dados.capturou) {
            encerrarVisual("captura", dados.mensagem);
        } else {
            mensagemCombate(dados.mensagem);
        }
    } catch (erro) {
        mensagemCombate(erro.message);
    } finally {
        combateOcupado = false;

        if (combateIniciado) {
            bloquearAtaques(false);
        }

        atualizarBotaoCaptura();
        atualizarBotoesAcao();
    }
}

function fecharItensCombate() {
    exibirSubmenuCombate();
}

async function abrirItensCombate() {
    if (combateOcupado || !combateIniciado || trocaObrigatoria) {
        return;
    }

    const painel = document.getElementById("itens_combate");

    if (!painel) {
        return;
    }

    if (!painel.hidden) {
        fecharItensCombate();
        return;
    }

    try {
        const dados = await requisitarCombate("listar_itens_combate.php");

        // Um GET antigo não deve apagar um token correto da sessão.
        if (typeof dados.csrf_token === "string") {
            tokenAcao = dados.csrf_token;
        }

        const campoItem = document.getElementById("item_batalha");
        const campoPescado = document.getElementById("alvo_batalha");
        campoItem.replaceChildren();
        campoPescado.replaceChildren();

        for (const item of dados.itens) {
            const opcao = elementoHTML("<option></option>");
            opcao.value = item.id;
            opcao.textContent = `${item.nome} ×${item.quantidade}`;
            campoItem.appendChild(opcao);
        }

        for (const pescado of dados.pescados) {
            const opcao = elementoHTML("<option></option>");
            opcao.value = pescado.id;
            opcao.textContent = `${pescado.nome} · HP ${pescado.hp_atual}/${pescado.hp_max} · ${pescado.status}`;
            campoPescado.appendChild(opcao);
        }

        exibirSubmenuCombate("itens_combate");

        const botao = document.getElementById("botao_usar_combate");
        botao.disabled = dados.itens.length === 0 || dados.pescados.length === 0;

        if (!dados.itens.length) {
            mensagemCombate("Sua mochila está vazia.");
        }
    } catch (erro) {
        mensagemCombate(erro.message);
    }
}

async function usarItemNoCombate() {
    if (combateOcupado || !combateIniciado || trocaObrigatoria) {
        return;
    }

    const itemId = Number(document.getElementById("item_batalha").value);
    const pescadoId = Number(document.getElementById("alvo_batalha").value);

    if (!itemId || !pescadoId) {
        return;
    }

    combateOcupado = true;
    bloquearAtaques(true);
    atualizarBotaoCaptura();

    try {
        const dados = await requisitarCombate("usar_item_combate.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                item_id: itemId,
                pescado_id: pescadoId,
                combate_id: combateId,
                csrf_token: tokenAcao
            })
        });

        fecharItensCombate();
        const nomeAtivoAntes = document.getElementById("nome_jogador")?.textContent;
        await animarEventosCombate(dados.eventos, nomeAtivoAntes);
        atualizarInterfaceCombate(dados);

        let texto = dados.mensagem + " " + textoEventos(dados.eventos);

        if (dados.resultado === "vitoria") {
            texto += ` Vitória! +${dados.exp_ganha} EXP e +${dados.dinheiro_ganho} moedas.`;
            if (dados.boss_vitoria?.primeira_vitoria) {
                texto += ` DNA ${dados.boss_vitoria.dna_nome} liberado na loja!`;
                if (dados.boss_vitoria.proxima_isca) {
                    texto += ` Isca ${dados.boss_vitoria.proxima_isca} desbloqueada!`;
                }
            }
        } else if (dados.resultado === "derrota") {
            texto += " Todos os integrantes da equipe foram derrotados.";
        }

        if (dados.resultado !== "continuar") {
            encerrarVisual(dados.resultado, texto, dados.boss_vitoria ? 5500 : 2500);
        } else {
            mensagemCombate(texto);
            await carregarAtaques();
        }
    } catch (erro) {
        mensagemCombate(erro.message);
    } finally {
        combateOcupado = false;

        if (combateIniciado) {
            bloquearAtaques(false);
        }

        atualizarBotaoCaptura();
        atualizarBotoesAcao();
    }
}

function mostrarTipos() {
    const painel = document.getElementById("painel_tipos");

    if (!painel) {
        return;
    }

    exibirSubmenuCombate(painel.hidden ? "painel_tipos" : null);
}

// Trocas são validadas no servidor; o cliente nunca escolhe integrantes
// que não estejam registrados na equipe da sessão autenticada.
function renderizarEquipeCombate() {
    const botaoTroca = document.getElementById("botao_trocar_combate");

    if (botaoTroca) {
        const disponiveis = equipeEmCombate.filter(
            p => !p.ativo && Number(p.hp) > 0
        );

        // Não esconder o diagnóstico quando não há reserva: o botão
        // continua acessível e informa como corrigir a formação.
        botaoTroca.disabled = !combateIniciado || combateOcupado;
        botaoTroca.title = disponiveis.length
            ? `Trocar para um dos ${disponiveis.length} Pescados disponíveis`
            : "Nenhum outro integrante com HP. Configure a equipe antes da batalha.";
    }
}

function abrirTrocaCombate() {
    if (!combateIniciado || (combateOcupado && !trocaObrigatoria)) {
        return;
    }

    const disponiveis = equipeEmCombate.filter(
        p => !p.ativo && Number(p.hp) > 0
    );

    if (disponiveis.length === 0) {
        mensagemCombate(
            equipeEmCombate.length < 2
                ? "A batalha foi iniciada com apenas um Pescado. " +
                  "Antes do próximo combate, abra Minha Equipe e organize " +
                  "outros Pescados nas posições 2 e 3."
                : "Nenhum outro integrante da equipe está com HP. " +
                  "Use Revive ou visite o centro de cura."
        );
        return;
    }

    const painel = document.getElementById("troca_combate");
    const lista = document.getElementById("opcoes_troca_combate");

    if (!painel || !lista) {
        return;
    }

    if (!trocaObrigatoria && !painel.hidden) {
        exibirSubmenuCombate();
        return;
    }

    lista.replaceChildren();

    for (const pescado of disponiveis) {
        const botao = elementoHTML("<button></button>");
        botao.type = "button";
        botao.textContent = `${pescado.nome} · Nv. ${pescado.nivel} · HP ${pescado.hp}/${pescado.hp_max}`;
        botao.addEventListener("click", () => trocarPescadoCombate(pescado.id));
        lista.appendChild(botao);
    }

    exibirSubmenuCombate("troca_combate");
}

async function trocarPescadoCombate(pescadoId) {
    if (!combateIniciado || combateOcupado) {
        return;
    }

    combateOcupado = true;
    bloquearAtaques(true);
    atualizarBotaoCaptura();

    try {
        const dados = await requisitarCombate("trocar_pescado_combate.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                pescado_id: Number(pescadoId),
                combate_id: combateId,
                csrf_acao: tokenAcao
            })
        });

        exibirSubmenuCombate();

        // Na troca, o novo integrante precisa aparecer antes do contra-ataque.
        if (dados.jogador && typeof atualizarSprite === "function") {
            atualizarSprite("jogador", dados.jogador);
        }

        await animarEventosCombate(dados.eventos, dados.jogador?.nome);
        atualizarInterfaceCombate(dados);

        const texto = dados.mensagem + " " + textoEventos(dados.eventos);

        if (dados.resultado !== "continuar") {
            encerrarVisual(dados.resultado, texto, dados.boss_vitoria ? 5500 : 2500);
        } else {
            mensagemCombate(texto + (trocaObrigatoria ? " Escolha outro Pescado!" : ""));
            await carregarAtaques();
        }
    } catch (erro) {
        mensagemCombate(erro.message);
    } finally {
        combateOcupado = false;

        if (combateIniciado) {
            bloquearAtaques(trocaObrigatoria);
        }

        atualizarBotaoCaptura();
        atualizarBotoesAcao();
        renderizarEquipeCombate();
    }
}

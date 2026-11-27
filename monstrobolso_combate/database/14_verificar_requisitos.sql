WITH esperado(nome, efeito, valor) AS (VALUES
    ('Potion', 'curar_hp', 20), ('Super Potion', 'curar_hp', 50),
    ('Hyper Potion', 'curar_hp', 200), ('Max Potion', 'curar_total', 0),
    ('Full Restore', 'restaurar_tudo', 0), ('Antidote', 'curar_veneno', 0),
    ('Burn Heal', 'curar_queimadura', 0), ('Ice Heal', 'curar_congelamento', 0),
    ('Awakening', 'curar_sono', 0), ('Paralyze Heal', 'curar_paralisia', 0),
    ('Full Heal', 'curar_status', 0), ('Revive', 'reviver', 50)
)
SELECT e.nome, i.preco, i.efeito, i.valor_efeito,
       CASE WHEN i.id IS NOT NULL AND i.efeito=e.efeito AND i.valor_efeito=e.valor
            THEN 'OK' ELSE 'REVISAR' END AS verificacao
FROM esperado e LEFT JOIN public.vw_catalogo_maremon i ON lower(i.nome)=lower(e.nome)
ORDER BY e.nome;

SELECT table_name FROM information_schema.views
WHERE table_schema='public' AND table_name IN
('vw_equipe_maremon','vw_historico_maremon','vw_catalogo_maremon') ORDER BY table_name;

SELECT table_name FROM information_schema.tables
WHERE table_schema='public' AND table_name IN
('pescador','combates_log','loja_itens','loja_compras','pescador_itens','evolucoes')
ORDER BY table_name;

SELECT COUNT(*) AS especies FROM public.pescado;
SELECT COUNT(*) AS rotas_evolutivas FROM public.evolucoes;
SELECT COUNT(*) AS registros_de_batalha FROM public.vw_historico_maremon;
SELECT COUNT(*) AS produtos FROM public.vw_catalogo_maremon;
SELECT COUNT(*) AS pescados_dos_jogadores FROM public.vw_equipe_maremon;

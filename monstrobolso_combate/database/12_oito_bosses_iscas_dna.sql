
SET lock_timeout = '5s';

ALTER TABLE public.pescador
    ADD COLUMN IF NOT EXISTS isca_atual SMALLINT NOT NULL DEFAULT 1;

INSERT INTO public.pescado (nome, tipo)
SELECT novo.nome, novo.tipo
FROM (VALUES
    ('Boto_Rosa', 'PUR'),
    ('Kelpie', 'DRK'),
    ('Karkinos', 'DRK'),
    ('Monstro_do_Lago_Ness', 'DRK'),
    ('Cipactli', 'DRK'),
    ('Genbu', 'DRK'),
    ('Namazu', 'BLU'),
    ('Lusca', 'DRK')
) AS novo(nome, tipo)
WHERE NOT EXISTS (
    SELECT 1 FROM public.pescado p WHERE LOWER(p.nome) = LOWER(novo.nome)
);

INSERT INTO public.stats (pescado_id, hp, atk, def, spe)
SELECT p.id, base.hp, base.atk, base.def, base.spe
FROM (VALUES
    ('Boto_Rosa', 65, 18, 16, 20),
    ('Kelpie', 78, 22, 19, 24),
    ('Karkinos', 105, 25, 32, 12),
    ('Monstro_do_Lago_Ness', 115, 29, 26, 21),
    ('Cipactli', 125, 34, 28, 20),
    ('Genbu', 140, 32, 40, 13),
    ('Namazu', 150, 38, 35, 12),
    ('Lusca', 180, 43, 35, 19)
) AS base(nome, hp, atk, def, spe)
JOIN public.pescado p ON LOWER(p.nome) = LOWER(base.nome)
WHERE NOT EXISTS (
    SELECT 1 FROM public.stats s WHERE s.pescado_id = p.id
);

CREATE TABLE IF NOT EXISTS public.bosses (
    ordem SMALLINT PRIMARY KEY CHECK (ordem BETWEEN 1 AND 8),
    nome VARCHAR(100) NOT NULL,
    isca VARCHAR(100) NOT NULL,
    pescado_id INTEGER NOT NULL REFERENCES public.pescado(id),
    dna_nome VARCHAR(50) NOT NULL UNIQUE,
    sprite VARCHAR(180) NOT NULL
);

INSERT INTO public.bosses (ordem, nome, isca, pescado_id, dna_nome, sprite)
SELECT b.ordem, b.nome, b.isca, p.id, b.dna_nome, b.sprite
FROM (VALUES
    (1, 'Boto-cor-de-rosa', 'Isca Rosa', 'Boto_Rosa', 'dna_boto_rosa', 'assets/bosses/boto_rosa.png'),
    (2, 'Kelpie', 'Isca do Kelpie', 'Kelpie', 'dna_kelpie', 'assets/bosses/kelpie_highres.png'),
    (3, 'Karkinos', 'Isca do Caranguejo', 'Karkinos', 'dna_karkinos', 'assets/bosses/karkinos_highres.png'),
    (4, 'Monstro do Lago Ness', 'Isca do Lago', 'Monstro_do_Lago_Ness', 'dna_monstro_do_lago_ness', 'assets/bosses/ness_highres.png'),
    (5, 'Cipactli', 'Isca Ancestral', 'Cipactli', 'dna_cipactli', 'assets/bosses/cipactli_highres.png'),
    (6, 'Genbu', 'Isca da Tartaruga', 'Genbu', 'dna_genbu', 'assets/bosses/genbu_highres.png'),
    (7, 'Namazu', 'Isca Sísmica', 'Namazu', 'dna_namazu', 'assets/bosses/namazu_highres.png'),
    (8, 'Lusca', 'Isca Abissal', 'Lusca', 'dna_lusca', 'assets/bosses/lusca_highres.png')
) AS b(ordem, nome, isca, especie, dna_nome, sprite)
JOIN public.pescado p ON p.nome = b.especie
WHERE TRUE
ON CONFLICT (ordem) DO UPDATE
SET nome = EXCLUDED.nome,
    isca = EXCLUDED.isca,
    pescado_id = EXCLUDED.pescado_id,
    dna_nome = EXCLUDED.dna_nome,
    sprite = EXCLUDED.sprite;

CREATE TABLE IF NOT EXISTS public.boss_vitorias (
    pescador_id INTEGER NOT NULL REFERENCES public.pescador(id) ON DELETE CASCADE,
    boss_ordem SMALLINT NOT NULL REFERENCES public.bosses(ordem),
    derrotado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (pescador_id, boss_ordem)
);

INSERT INTO public.loja_itens
    (nome, descricao, categoria, preco, efeito, valor_efeito)
SELECT b.dna_nome,
       'DNA de ' || b.nome || '. Material de evolução futura.',
       'evolucao',
       350 + (b.ordem - 1) * 100,
       'dna_boss',
       0
FROM public.bosses b
WHERE TRUE
ON CONFLICT (nome) DO NOTHING;

INSERT INTO public.pescado_ataques
    (pescado_id, ataque_id, nivel_necessario, slot)
SELECT p.id, a.id, 1, golpe.slot
FROM public.bosses b
JOIN public.pescado p ON p.id = b.pescado_id
JOIN (VALUES
    (1, 'Mordida Abissal'),
    (2, 'Sombra do Naufrágio'),
    (3, 'Pulso Obscuro'),
    (4, 'Maré Negra')
) AS golpe(slot, nome)
    ON TRUE
JOIN public.ataques a ON a.nome = golpe.nome
WHERE NOT EXISTS (
    SELECT 1
    FROM public.pescado_ataques pa
    WHERE pa.pescado_id = p.id
      AND (pa.slot = golpe.slot OR pa.ataque_id = a.id)
);

SELECT
    b.ordem,
    b.nome AS chefe,
    p.nome AS especie,
    p.tipo AS tipo_normal,
    'DRK' AS tipo_no_encontro_boss,
    s.hp AS hp_base,
    COUNT(DISTINCT pa.ataque_id) AS golpes,
    b.dna_nome
FROM public.bosses b
JOIN public.pescado p ON p.id = b.pescado_id
JOIN public.stats s ON s.pescado_id = p.id
LEFT JOIN public.pescado_ataques pa ON pa.pescado_id = p.id
GROUP BY b.ordem, b.nome, p.nome, p.tipo, s.hp, b.dna_nome
ORDER BY b.ordem;


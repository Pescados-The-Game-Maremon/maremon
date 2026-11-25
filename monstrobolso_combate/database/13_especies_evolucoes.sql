-- MAREMON 13: 51 espécies e 21 evoluções, sem requisito de nível.
-- Execute no banco Maremon após a etapa 12 (chefes e DNAs).
-- Faça backup. Um bloco DO é atômico; sem BEGIN/COMMIT de transação externos.
-- Não renomeia espécies existentes nem substitui atributos/ataques configurados.
DO $migracao$
DECLARE
 r RECORD; v_id INTEGER; v_count INTEGER; v_pedra INTEGER; v_item INTEGER;
 v_origem INTEGER; v_destino INTEGER; v_tipo TEXT;
BEGIN
 CREATE TABLE IF NOT EXISTS public.maremon_especies (
   codigo TEXT PRIMARY KEY,
   pescado_id INTEGER NOT NULL UNIQUE REFERENCES public.pescado(id),
   sprite TEXT NOT NULL
 );
 CREATE TABLE IF NOT EXISTS public.evolucoes (
   id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
   pescado_origem_id INTEGER NOT NULL REFERENCES public.pescado(id),
   pescado_destino_id INTEGER NOT NULL REFERENCES public.pescado(id),
   item_necessario_id INTEGER NOT NULL REFERENCES public.loja_itens(id),
   quantidade_item INTEGER NOT NULL DEFAULT 1 CHECK (quantidade_item >= 1),
   estagio_destino SMALLINT NOT NULL DEFAULT 1 CHECK (estagio_destino BETWEEN 1 AND 2),
   CHECK (pescado_origem_id <> pescado_destino_id),
   UNIQUE (pescado_origem_id, pescado_destino_id)
 );
 ALTER TABLE public.evolucoes DROP COLUMN IF EXISTS nivel_necessario;
 ALTER TABLE public.evolucoes ALTER COLUMN item_necessario_id SET NOT NULL;
 CREATE INDEX IF NOT EXISTS idx_evolucoes_origem ON public.evolucoes(pescado_origem_id);
 IF (SELECT COUNT(*) FROM public.ataques) < 4 THEN
   RAISE EXCEPTION 'Cadastre pelo menos quatro ataques antes da etapa 13.';
 END IF;
 -- Preservar bônus do sistema antigo nos exemplares já existentes; novos não acumulam bônus duplo.
 IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema='public' AND table_name='pescador_pescado' AND column_name='bonus_evolucao_legado') THEN
   ALTER TABLE public.pescador_pescado ADD COLUMN bonus_evolucao_legado INTEGER NOT NULL DEFAULT 0;
   UPDATE public.pescador_pescado SET bonus_evolucao_legado=estagio_evolucao;
 END IF;
 -- A pedra que já existe na loja mantém seu ID, preço e quantidade.
 SELECT id INTO v_pedra FROM public.loja_itens WHERE efeito='evoluir' ORDER BY id LIMIT 1;
 IF v_pedra IS NULL THEN
   INSERT INTO public.loja_itens(nome,descricao,categoria,preco,efeito,valor_efeito)
   VALUES ('pedra_evolutiva','Transforma espécies compatíveis.','evolucao',200,'evoluir',0)
   ON CONFLICT (nome) DO UPDATE SET efeito='evoluir'
   RETURNING id INTO v_pedra;
 END IF;
 FOR r IN SELECT * FROM (VALUES
    ('Trilobita', 'LGT', 45, 14, 18, 12, 'assets/pescados/Trilobita.png', '', 0),
    ('Carnifibita', 'LGT', 85, 30, 30, 22, 'assets/pescados/carnifibita.png', '', 0),
    ('Anomalocaris', 'LGT', 45, 18, 12, 17, 'assets/pescados/Anomalocaris.png', '', 0),
    ('Aniquilocaris', 'LGT', 82, 35, 22, 30, 'assets/pescados/aniquilocaris.png', '', 0),
    ('Hallucigenia', 'LGT', 42, 14, 14, 20, 'assets/pescados/Hallucigenia.png', '', 0),
    ('Hallucinado', 'LGT', 80, 29, 25, 34, 'assets/pescados/Hallucinado.png', '', 0),
    ('Peixe_Pescador', 'DRK', 50, 19, 14, 12, 'assets/pescados/peixe_pescador.png', '', 0),
    ('Pescador_de_Gente', 'DRK', 90, 33, 25, 20, 'assets/pescados/pescador_de_gente.png', '', 0),
    ('Peixe_Palhaco', 'RED', 42, 14, 12, 20, 'assets/pescados/peixe_palhaco.png', '', 0),
    ('Bobo_dos_Mares', 'RED', 78, 30, 24, 31, 'assets/pescados/bobo_dos_mares.png', '', 0),
    ('Peixe_Bolha', 'PUR', 55, 12, 21, 8, 'assets/pescados/peixe_bolha.png', '', 0),
    ('Homem_Bolha', 'PUR', 95, 29, 33, 14, 'assets/pescados/homem_bolha.png', '', 0),
    ('Caranguejo_Azul', 'BLU', 56, 19, 24, 10, 'assets/pescados/caranguejo_azul.png', '', 0),
    ('Karkinos', 'DRK', 105, 25, 32, 12, 'assets/bosses/karkinos_highres.png', '', 3),
    ('Pirarucu', 'RED', 65, 22, 18, 15, 'assets/pescados/pirarucu.png', '', 0),
    ('Poraque', 'YEL', 50, 24, 13, 21, 'assets/pescados/poraque.png', '', 0),
    ('Narval', 'BLU', 65, 23, 18, 20, 'assets/pescados/narval.png', '', 0),
    ('Tubarao_Martelo', 'BLU', 70, 25, 18, 23, 'assets/pescados/tubarao_martelo.png', '', 0),
    ('Orca', 'DRK', 75, 27, 20, 24, 'assets/pescados/orca.png', '', 0),
    ('Arraia', 'BLU', 52, 17, 18, 22, 'assets/pescados/arraia.png', '', 0),
    ('Baiacu', 'GRN', 54, 16, 24, 12, 'assets/pescados/baiacu.png', '', 0),
    ('Amargama', 'GRN', 100, 31, 36, 17, 'assets/pescados/amargama.png', '', 0),
    ('Boto', 'BLU', 58, 18, 15, 25, 'assets/pescados/boto.png', '', 0),
    ('Boto_Rosa', 'PUR', 65, 18, 16, 20, 'assets/bosses/boto_rosa.png', '', 1),
    ('Truta_Arco_Iris', 'BLU', 48, 17, 13, 23, 'assets/pescados/truta_arco_iris.png', '', 0),
    ('Monstro_do_Lago_Ness', 'DRK', 115, 29, 26, 21, 'assets/bosses/ness_highres.png', 'Nessie', 4),
    ('Bagre', 'BLU', 55, 19, 20, 13, 'assets/pescados/bagre.png', '', 0),
    ('Namazu', 'BLU', 150, 38, 35, 12, 'assets/bosses/namazu_highres.png', '', 7),
    ('Jacare', 'GRN', 70, 25, 23, 14, 'assets/pescados/jacare.png', '', 0),
    ('Cipactli', 'DRK', 125, 34, 28, 20, 'assets/bosses/cipactli_highres.png', '', 5),
    ('Cavalo_Marinho', 'BLU', 42, 14, 15, 23, 'assets/pescados/cavalo_marinho.png', '', 0),
    ('Kelpie', 'DRK', 78, 22, 19, 24, 'assets/bosses/kelpie_highres.png', '', 2),
    ('Tubarao_Raposa', 'BLU', 68, 24, 17, 27, 'assets/pescados/tubarao_raposa.png', '', 0),
    ('Lusca', 'DRK', 180, 43, 35, 19, 'assets/bosses/lusca_highres.png', '', 8),
    ('Tartaruga', 'GRN', 70, 17, 29, 9, 'assets/pescados/tartaruga.png', '', 0),
    ('Genbu', 'DRK', 140, 32, 40, 13, 'assets/bosses/genbu_highres.png', '', 6),
    ('Agua_Viva', 'PUR', 45, 18, 12, 20, 'assets/pescados/agua_viva.png', '', 0),
    ('Hafgufa', 'BLU', 105, 34, 25, 26, 'assets/pescados/hafgufa_highres.png', 'Hagfufa', 0),
    ('Koi_Yin', 'DRK', 48, 20, 15, 21, 'assets/pescados/koi_yin.png', '', 0),
    ('Koi_Yang', 'LGT', 48, 20, 15, 21, 'assets/pescados/koi_yang.png', '', 0),
    ('Carpas_Siameses', 'LGT', 90, 33, 29, 28, 'assets/pescados/carpas_siameses.png', 'Carpas_Siamesas', 0),
    ('Polvo', 'PUR', 58, 23, 17, 18, 'assets/pescados/polvo.png', '', 0),
    ('Lula', 'RED', 55, 24, 15, 25, 'assets/pescados/lula.png', '', 0),
    ('Kraken', 'DRK', 125, 40, 28, 25, 'assets/pescados/kraken.png', '', 0),
    ('Baleia_Azul', 'BLU', 95, 22, 27, 12, 'assets/pescados/baleia_azul.png', '', 0),
    ('Bake_Kujira', 'DRK', 150, 37, 36, 19, 'assets/pescados/bake_kujira_highres.png', '', 0),
    ('Cachalote', 'BLU', 90, 28, 25, 15, 'assets/pescados/baleia_cachalote.png', 'Baleia_Cachalote', 0),
    ('Moby_Dick', 'BLU', 145, 39, 35, 20, 'assets/pescados/moby_dick_highres.png', '', 0),
    ('Salmao', 'RED', 48, 18, 14, 22, 'assets/pescados/salmao.png', '', 0),
    ('Leviata', 'DRK', 150, 42, 32, 25, 'assets/pescados/leviathan_highres.png', 'Leviathan', 0),
    ('Umi_Bozu', 'DRK', 95, 30, 28, 16, 'assets/pescados/umi_bozu.png', '', 0)
 ) AS dados(codigo,tipo,hp,atk,def,spe,sprite,apelido,boss_ordem)
 LOOP
   v_id := NULL;
   IF r.boss_ordem > 0 THEN
     SELECT pescado_id INTO v_id FROM public.bosses WHERE ordem=r.boss_ordem;
     IF v_id IS NULL THEN RAISE EXCEPTION 'Execute a etapa 12: chefe % ausente',r.boss_ordem; END IF;
   ELSE
     SELECT pescado_id INTO v_id FROM public.maremon_especies WHERE codigo=r.codigo;
     IF v_id IS NULL THEN
       -- Normaliza acentos, espaços, hífens e underscores para localizar espécies existentes.
       SELECT COUNT(*),MIN(id) INTO v_count,v_id FROM public.pescado
       WHERE regexp_replace(translate(lower(nome),'áàâãäéèêëíìîïóòôõöúùûüç','aaaaaeeeeiiiiooooouuuuc'),'[^a-z0-9]','','g') IN (
         regexp_replace(lower(r.codigo),'[^a-z0-9]','','g'),
         regexp_replace(lower(r.apelido),'[^a-z0-9]','','g')
       ) AND nome IS NOT NULL AND trim(nome)<>'';
       IF v_count > 1 THEN RAISE EXCEPTION 'Espécie ambígua: % (mais de um ID existente)',r.codigo; END IF;
     END IF;
     IF v_id IS NULL THEN
       INSERT INTO public.pescado(nome,tipo) VALUES(r.codigo,r.tipo) RETURNING id INTO v_id;
     END IF;
   END IF;
   INSERT INTO public.maremon_especies(codigo,pescado_id,sprite) VALUES(r.codigo,v_id,r.sprite)
   ON CONFLICT(codigo) DO UPDATE SET pescado_id=EXCLUDED.pescado_id,sprite=EXCLUDED.sprite;
   IF NOT EXISTS (SELECT 1 FROM public.stats WHERE pescado_id=v_id) THEN
     INSERT INTO public.stats(pescado_id,hp,atk,def,spe) VALUES(v_id,r.hp,r.atk,r.def,r.spe);
   END IF;
   -- Somente espécies sem nenhum ataque recebem quatro golpes provisórios.
   IF NOT EXISTS (SELECT 1 FROM public.pescado_ataques WHERE pescado_id=v_id) THEN
     SELECT tipo INTO v_tipo FROM public.pescado WHERE id=v_id;
     INSERT INTO public.pescado_ataques(pescado_id,ataque_id,nivel_necessario,slot)
     SELECT v_id,g.id,1,row_number() OVER (ORDER BY g.prioridade,g.poder,g.id)::SMALLINT
     FROM (SELECT id,poder,CASE WHEN tipo=v_tipo THEN 0 ELSE 1 END AS prioridade
           FROM public.ataques ORDER BY prioridade,poder,id LIMIT 4) g;
   END IF;
 END LOOP;
 FOR r IN SELECT * FROM (VALUES
    ('Trilobita', 'Carnifibita', 0),
    ('Anomalocaris', 'Aniquilocaris', 0),
    ('Hallucigenia', 'Hallucinado', 0),
    ('Peixe_Pescador', 'Pescador_de_Gente', 0),
    ('Peixe_Palhaco', 'Bobo_dos_Mares', 0),
    ('Peixe_Bolha', 'Homem_Bolha', 0),
    ('Caranguejo_Azul', 'Karkinos', 3),
    ('Baiacu', 'Amargama', 0),
    ('Boto', 'Boto_Rosa', 1),
    ('Truta_Arco_Iris', 'Monstro_do_Lago_Ness', 4),
    ('Bagre', 'Namazu', 7),
    ('Jacare', 'Cipactli', 5),
    ('Cavalo_Marinho', 'Kelpie', 2),
    ('Tubarao_Raposa', 'Lusca', 8),
    ('Tartaruga', 'Genbu', 6),
    ('Agua_Viva', 'Hafgufa', 0),
    ('Koi_Yang', 'Carpas_Siameses', 0),
    ('Lula', 'Kraken', 0),
    ('Baleia_Azul', 'Bake_Kujira', 0),
    ('Cachalote', 'Moby_Dick', 0),
    ('Salmao', 'Leviata', 0)
 ) AS rotas(origem,destino,boss_ordem)
 LOOP
   SELECT pescado_id INTO STRICT v_origem FROM public.maremon_especies WHERE codigo=r.origem;
   SELECT pescado_id INTO STRICT v_destino FROM public.maremon_especies WHERE codigo=r.destino;
   v_item := v_pedra;
   IF r.boss_ordem > 0 THEN
     SELECT i.id INTO v_item FROM public.bosses b JOIN public.loja_itens i ON i.nome=b.dna_nome WHERE b.ordem=r.boss_ordem AND i.efeito='dna_boss';
     IF v_item IS NULL THEN RAISE EXCEPTION 'DNA ausente na loja: chefe %',r.boss_ordem; END IF;
   END IF;
   INSERT INTO public.evolucoes(pescado_origem_id,pescado_destino_id,item_necessario_id,quantidade_item,estagio_destino)
   VALUES(v_origem,v_destino,v_item,1,1)
   ON CONFLICT(pescado_origem_id,pescado_destino_id) DO NOTHING;
 END LOOP;
 -- Atualizar descrição dos DNAs, sem alterar valores ou desbloqueios.
 UPDATE public.loja_itens i SET descricao='DNA de '||b.nome||'. Evolui uma espécie compatível.'
 FROM public.bosses b WHERE i.nome=b.dna_nome AND i.efeito='dna_boss';
 -- A forma evoluída usa os próprios atributos base, sem bônus duplicado.
 CREATE OR REPLACE VIEW public.vw_equipe_maremon AS
 SELECT j.id AS jogador_id,j.usuario,p.id AS pescado_id,p.nome,p.tipo,
 pp.nivel,pp.exp,pp.status,pp.status_turnos,pp.estagio_evolucao,
 pp.selecionado,pp.equipe_slot,pp.hp_atual,
 s.hp+2*GREATEST(pp.nivel-5,0)+10*pp.bonus_evolucao_legado AS hp_max,
 s.atk+GREATEST(pp.nivel-5,0)+3*pp.bonus_evolucao_legado AS atk,
 s.def+GREATEST(pp.nivel-5,0)+3*pp.bonus_evolucao_legado AS def,
 s.spe+GREATEST(pp.nivel-5,0)+3*pp.bonus_evolucao_legado AS spe
 FROM public.pescador_pescado pp JOIN public.pescador j ON j.id=pp.pescador_id
 JOIN public.pescado p ON p.id=pp.pescado_id JOIN public.stats s ON s.pescado_id=pp.pescado_id;
END;
$migracao$;

-- Conferência: 51 espécies mapeadas, 21 rotas nesta lista (ou mais se você já tinha outras rotas).
SELECT COUNT(*) AS especies_mapeadas FROM public.maremon_especies;
SELECT p.nome AS origem,d.nome AS destino,i.nome AS item,e.quantidade_item
FROM public.evolucoes e JOIN public.pescado p ON p.id=e.pescado_origem_id
JOIN public.pescado d ON d.id=e.pescado_destino_id JOIN public.loja_itens i ON i.id=e.item_necessario_id
ORDER BY p.nome,d.nome;
SELECT m.codigo,p.nome,p.tipo,COUNT(DISTINCT pa.ataque_id) AS golpes,
 COUNT(DISTINCT pa.ataque_id) FILTER (WHERE pa.nivel_necessario<=5) AS golpes_no_nivel_5
FROM public.maremon_especies m JOIN public.pescado p ON p.id=m.pescado_id
LEFT JOIN public.pescado_ataques pa ON pa.pescado_id=p.id
GROUP BY m.codigo,p.nome,p.tipo ORDER BY m.codigo;

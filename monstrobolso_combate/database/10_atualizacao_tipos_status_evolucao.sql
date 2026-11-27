BEGIN;
ALTER TABLE public.pescador_pescado ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'normal';
ALTER TABLE public.pescador_pescado ADD COLUMN IF NOT EXISTS status_turnos INTEGER NOT NULL DEFAULT 0;
ALTER TABLE public.pescador_pescado ADD COLUMN IF NOT EXISTS estagio_evolucao INTEGER NOT NULL DEFAULT 0;
ALTER TABLE public.pescador_pescado ADD COLUMN IF NOT EXISTS equipe_slot SMALLINT;
ALTER TABLE public.ataques ADD COLUMN IF NOT EXISTS status_aplicado VARCHAR(20);
ALTER TABLE public.ataques ADD COLUMN IF NOT EXISTS chance_status INTEGER NOT NULL DEFAULT 0;
ALTER TABLE public.ataques ADD COLUMN IF NOT EXISTS duracao_status INTEGER NOT NULL DEFAULT 3;
DO $$ BEGIN
 IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname='ck_maremon_status') THEN
  ALTER TABLE public.pescador_pescado ADD CONSTRAINT ck_maremon_status CHECK (status IN ('normal','envenenado','queimado','congelado','dormindo','paralisado'));
 END IF;
 IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname='ck_maremon_status_turnos') THEN
  ALTER TABLE public.pescador_pescado ADD CONSTRAINT ck_maremon_status_turnos CHECK (status_turnos BETWEEN 0 AND 20);
 END IF;
 IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname='ck_maremon_evolucao') THEN
  ALTER TABLE public.pescador_pescado ADD CONSTRAINT ck_maremon_evolucao CHECK (estagio_evolucao BETWEEN 0 AND 2);
 END IF;
 IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname='ck_maremon_slot') THEN
  ALTER TABLE public.pescador_pescado ADD CONSTRAINT ck_maremon_slot CHECK (equipe_slot BETWEEN 1 AND 4);
 END IF;
 IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname='ck_maremon_ataque_status') THEN
  ALTER TABLE public.ataques ADD CONSTRAINT ck_maremon_ataque_status CHECK (status_aplicado IS NULL OR status_aplicado IN ('envenenado','queimado','congelado','dormindo','paralisado'));
 END IF;
 IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname='ck_maremon_chance_status') THEN
  ALTER TABLE public.ataques ADD CONSTRAINT ck_maremon_chance_status CHECK (chance_status BETWEEN 0 AND 100);
 END IF;
 IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname='ck_maremon_duracao_status') THEN
  ALTER TABLE public.ataques ADD CONSTRAINT ck_maremon_duracao_status CHECK (duracao_status BETWEEN 1 AND 10);
 END IF;
END $$;
CREATE UNIQUE INDEX IF NOT EXISTS ux_maremon_slots
 ON public.pescador_pescado (pescador_id,equipe_slot) WHERE equipe_slot IS NOT NULL;
UPDATE public.pescador_pescado SET equipe_slot=1 WHERE selecionado=TRUE AND equipe_slot IS NULL;
UPDATE public.ataques SET status_aplicado = 'envenenado', chance_status=35, duracao_status=3 WHERE LOWER(nome)=LOWER('Sombra');
UPDATE public.ataques SET status_aplicado = 'queimado', chance_status=35, duracao_status=3 WHERE LOWER(nome)=LOWER('Faísca Vermelha');
UPDATE public.ataques SET status_aplicado = 'congelado', chance_status=25, duracao_status=2 WHERE LOWER(nome)=LOWER('Onda Azul');
UPDATE public.ataques SET status_aplicado = 'dormindo', chance_status=25, duracao_status=2 WHERE LOWER(nome)=LOWER('Clarão');
UPDATE public.ataques SET status_aplicado = 'paralisado', chance_status=30, duracao_status=3 WHERE LOWER(nome)=LOWER('Raio de Luz');
CREATE OR REPLACE VIEW public.vw_equipe_maremon AS
 SELECT j.id AS jogador_id,j.usuario,p.id AS pescado_id,p.nome,p.tipo,
 pp.nivel,pp.exp,pp.status,pp.status_turnos,pp.estagio_evolucao,
 pp.selecionado,pp.equipe_slot,pp.hp_atual,
 s.hp+2*GREATEST(pp.nivel-5,0)+10*pp.estagio_evolucao AS hp_max,
 s.atk+GREATEST(pp.nivel-5,0)+3*pp.estagio_evolucao AS atk,
 s.def+GREATEST(pp.nivel-5,0)+3*pp.estagio_evolucao AS def,
 s.spe+GREATEST(pp.nivel-5,0)+3*pp.estagio_evolucao AS spe
 FROM public.pescador_pescado pp
 JOIN public.pescador j ON j.id=pp.pescador_id
 JOIN public.pescado p ON p.id=pp.pescado_id
 JOIN public.stats s ON s.pescado_id=pp.pescado_id;
CREATE OR REPLACE VIEW public.vw_historico_maremon AS
 SELECT l.id,l.pescador_id,j.usuario,l.resultado,l.turnos,l.dinheiro_ganho,
 p.nome AS pescado_jogador,i.nome AS pescado_inimigo,l.iniciado_em,l.finalizado_em
 FROM public.combates_log l
 JOIN public.pescador j ON j.id=l.pescador_id
 JOIN public.pescado p ON p.id=l.jogador_pescado_id
 JOIN public.pescado i ON i.id=l.inimigo_pescado_id;
CREATE OR REPLACE VIEW public.vw_catalogo_maremon AS
 SELECT id,nome,descricao,categoria,preco,efeito,valor_efeito FROM public.loja_itens;
COMMIT;

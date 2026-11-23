-- Executar no pgAdmin depois de fazer backup. Não modifica ataques, HP ou níveis.
-- A quarta posição antiga vira reserva.
UPDATE public.pescador_pescado
SET equipe_slot = NULL
WHERE equipe_slot = 4;

ALTER TABLE public.pescador_pescado
DROP CONSTRAINT IF EXISTS ck_maremon_slot;

ALTER TABLE public.pescador_pescado
ADD CONSTRAINT ck_maremon_slot
CHECK (equipe_slot IS NULL OR equipe_slot BETWEEN 1 AND 3);

-- Conferência da equipe de cada conta.
SELECT j.usuario, p.nome, pp.equipe_slot
FROM public.pescador_pescado pp
JOIN public.pescador j ON j.id = pp.pescador_id
JOIN public.pescado p ON p.id = pp.pescado_id
WHERE pp.equipe_slot BETWEEN 1 AND 3
ORDER BY j.usuario, pp.equipe_slot;

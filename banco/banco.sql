CREATE TABLE public.monfisher (
    id integer NOT NULL,
    nome character varying
);


ALTER TABLE public.monfisher OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 24608)
-- Name: monfisher_pescado; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.monfisher_pescado (
    monfisher_id integer NOT NULL,
    pescado_id integer NOT NULL,
    nivel integer
);


ALTER TABLE public.monfisher_pescado OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 24601)
-- Name: monfishers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.monfishers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.monfishers_id_seq OWNER TO postgres;

--
-- TOC entry 4824 (class 0 OID 0)
-- Dependencies: 219
-- Name: monfishers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.monfishers_id_seq OWNED BY public.monfisher.id;


--
-- TOC entry 215 (class 1259 OID 24579)
-- Name: pescado; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pescado (
    id integer NOT NULL,
    nome character varying,
    tipo character varying,
    "desc" character varying
);


ALTER TABLE public.pescado OWNER TO postgres;

--
-- TOC entry 216 (class 1259 OID 24582)
-- Name: pescado_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pescado_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pescado_id_seq OWNER TO postgres;

--
-- TOC entry 4825 (class 0 OID 0)
-- Dependencies: 216
-- Name: pescado_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pescado_id_seq OWNED BY public.pescado.id;


--
-- TOC entry 223 (class 1259 OID 24626)
-- Name: pescador; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pescador (
    id integer NOT NULL,
    nome character varying,
    dinheiro integer
);


ALTER TABLE public.pescador OWNER TO postgres;

--
-- TOC entry 222 (class 1259 OID 24625)
-- Name: pescador_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pescador_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pescador_id_seq OWNER TO postgres;

--
-- TOC entry 4826 (class 0 OID 0)
-- Dependencies: 222
-- Name: pescador_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pescador_id_seq OWNED BY public.pescador.id;


--
-- TOC entry 224 (class 1259 OID 24634)
-- Name: pescador_pescado; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pescador_pescado (
    pescador_id integer NOT NULL,
    pescado_id integer NOT NULL,
    exp integer,
    nivel integer
);


ALTER TABLE public.pescador_pescado OWNER TO postgres;

--
-- TOC entry 218 (class 1259 OID 24592)
-- Name: stats; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.stats (
    id integer NOT NULL,
    hp integer,
    atk integer,
    def integer,
    spe integer,
    pescado_id integer
);


ALTER TABLE public.stats OWNER TO postgres;

--
-- TOC entry 217 (class 1259 OID 24591)
-- Name: stats_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.stats_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.stats_id_seq OWNER TO postgres;

--
-- TOC entry 4827 (class 0 OID 0)
-- Dependencies: 217
-- Name: stats_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.stats_id_seq OWNED BY public.stats.id;


--
-- TOC entry 4659 (class 2604 OID 24605)
-- Name: monfisher id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.monfisher ALTER COLUMN id SET DEFAULT nextval('public.monfishers_id_seq'::regclass);


--
-- TOC entry 4657 (class 2604 OID 24583)
-- Name: pescado id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pescado ALTER COLUMN id SET DEFAULT nextval('public.pescado_id_seq'::regclass);


--
-- TOC entry 4660 (class 2604 OID 24629)
-- Name: pescador id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pescador ALTER COLUMN id SET DEFAULT nextval('public.pescador_id_seq'::regclass);


--
-- TOC entry 4658 (class 2604 OID 24595)
-- Name: stats id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stats ALTER COLUMN id SET DEFAULT nextval('public.stats_id_seq'::regclass);


--
-- TOC entry 4666 (class 2606 OID 24624)
-- Name: monfisher_pescado monfisher_pescado_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.monfisher_pescado
    ADD CONSTRAINT monfisher_pescado_pkey PRIMARY KEY (monfisher_id, pescado_id);


--
-- TOC entry 4664 (class 2606 OID 24612)
-- Name: monfisher monfishers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.monfisher
    ADD CONSTRAINT monfishers_pkey PRIMARY KEY (id);


--
-- TOC entry 4662 (class 2606 OID 24590)
-- Name: pescado pescado_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pescado
    ADD CONSTRAINT pescado_pkey PRIMARY KEY (id);


--
-- TOC entry 4670 (class 2606 OID 24638)
-- Name: pescador_pescado pescador_pescado_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pescador_pescado
    ADD CONSTRAINT pescador_pescado_pkey PRIMARY KEY (pescador_id, pescado_id);


--
-- TOC entry 4668 (class 2606 OID 24633)
-- Name: pescador pescador_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pescador
    ADD CONSTRAINT pescador_pkey PRIMARY KEY (id);


--
-- TOC entry 4672 (class 2606 OID 24613)
-- Name: monfisher_pescado monfisher_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.monfisher_pescado
    ADD CONSTRAINT monfisher_id FOREIGN KEY (monfisher_id) REFERENCES public.monfisher(id) NOT VALID;


--
-- TOC entry 4671 (class 2606 OID 24596)
-- Name: stats pescado_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stats
    ADD CONSTRAINT pescado_id FOREIGN KEY (pescado_id) REFERENCES public.pescado(id) NOT VALID;


--
-- TOC entry 4673 (class 2606 OID 24618)
-- Name: monfisher_pescado pescado_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.monfisher_pescado
    ADD CONSTRAINT pescado_id FOREIGN KEY (pescado_id) REFERENCES public.pescado(id) NOT VALID;


--
-- TOC entry 4674 (class 2606 OID 24644)
-- Name: pescador_pescado pescado_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pescador_pescado
    ADD CONSTRAINT pescado_id FOREIGN KEY (pescado_id) REFERENCES public.pescado(id) NOT VALID;


--
-- TOC entry 4675 (class 2606 OID 24639)
-- Name: pescador_pescado pescador_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pescador_pescado
    ADD CONSTRAINT pescador_id FOREIGN KEY (pescador_id) REFERENCES public.pescador(id) NOT VALID;


-- Completed on 2026-10-02 14:17:00

--
-- PostgreSQL database dump complete
--


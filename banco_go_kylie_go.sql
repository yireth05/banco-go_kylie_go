--
-- PostgreSQL database dump
--

\restrict i9K2AjpXSQaPM1dSyC39FnGO7uFFdDjwptoEICcy48c53Zz0VEbqcPTTMWZcljK

-- Dumped from database version 18.6 (Ubuntu 18.6-0ubuntu0.26.04.1)
-- Dumped by pg_dump version 18.6 (Ubuntu 18.6-0ubuntu0.26.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: fn_auditar_transaccion(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_auditar_transaccion() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO bitacora_transacciones (operacion, id_transaccion, detalle)
        VALUES (TG_OP, NEW.id_transaccion, 'nuevo=' || to_jsonb(NEW)::text);
    ELSIF TG_OP = 'UPDATE' THEN
        INSERT INTO bitacora_transacciones (operacion, id_transaccion, detalle)
        VALUES (TG_OP, NEW.id_transaccion,
                'antes=' || to_jsonb(OLD)::text || ' | despues=' || to_jsonb(NEW)::text);
    ELSE  -- DELETE
        INSERT INTO bitacora_transacciones (operacion, id_transaccion, detalle)
        VALUES (TG_OP, OLD.id_transaccion, 'borrado=' || to_jsonb(OLD)::text);
    END IF;
    RETURN NULL;  -- en AFTER el valor de retorno se ignora
END;
$$;


ALTER FUNCTION public.fn_auditar_transaccion() OWNER TO postgres;

--
-- Name: fn_validar_transaccion(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_validar_transaccion() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF NEW.monto_transaccion IS NULL OR NEW.monto_transaccion <= 0 THEN
        RAISE EXCEPTION 'Error: el monto de la transacción debe ser positivo (recibido: %)',
              NEW.monto_transaccion USING ERRCODE = 'BK010';
    END IF;

    IF NEW.fecha_transaccion IS NULL THEN
        NEW.fecha_transaccion := CURRENT_TIMESTAMP;
    END IF;

    IF NEW.fecha_transaccion::date > CURRENT_DATE THEN
        RAISE EXCEPTION 'Error: la fecha de la transacción (%) es posterior a hoy',
              NEW.fecha_transaccion::date USING ERRCODE = 'BK011';
    END IF;

    RETURN NEW;   -- BEFORE row-level: debe devolver NEW
END;
$$;


ALTER FUNCTION public.fn_validar_transaccion() OWNER TO postgres;

--
-- Name: realizar_transaccion(character varying, character varying, integer, integer, numeric, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.realizar_transaccion(p_id_cliente character varying, p_num_cuenta character varying, p_cod_tipo integer, p_cod_forma integer, p_monto numeric, p_id_cajero integer DEFAULT NULL::integer, p_id_banca_virtual integer DEFAULT NULL::integer) RETURNS integer
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_nomb_tipo  VARCHAR(50);
    v_saldo      NUMERIC(15,2);
    v_id_trans   INT;
BEGIN
    -- 1) Validar existencia de códigos ANTES de cualquier búsqueda completa o UPDATE
    IF NOT EXISTS (SELECT 1 FROM clientes WHERE id_cliente = p_id_cliente) THEN
        RAISE EXCEPTION 'El cliente % no existe', p_id_cliente USING ERRCODE = 'BK002';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM cuentas WHERE num_cuenta = p_num_cuenta) THEN
        RAISE EXCEPTION 'La cuenta % no existe', p_num_cuenta USING ERRCODE = 'BK001';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM cuentas
                   WHERE num_cuenta = p_num_cuenta AND id_cliente = p_id_cliente) THEN
        RAISE EXCEPTION 'La cuenta % no pertenece al cliente %', p_num_cuenta, p_id_cliente
              USING ERRCODE = 'BK007';
    END IF;

    SELECT nomb_tipo_transaccion INTO v_nomb_tipo
      FROM tipo_transacciones WHERE cod_tipo_transaccion = p_cod_tipo;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'El tipo de transacción % no existe', p_cod_tipo USING ERRCODE = 'BK003';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM formas_transacciones WHERE cod_forma_transaccion = p_cod_forma) THEN
        RAISE EXCEPTION 'La forma de transacción % no existe', p_cod_forma USING ERRCODE = 'BK004';
    END IF;

    -- 2) Origen: exactamente uno (cajero o banca virtual)
    IF (p_id_cajero IS NULL AND p_id_banca_virtual IS NULL)
       OR (p_id_cajero IS NOT NULL AND p_id_banca_virtual IS NOT NULL) THEN
        RAISE EXCEPTION 'Debe indicar cajero o banca virtual (solo uno)' USING ERRCODE = 'BK006';
    END IF;

    -- 3) Monto positivo (el trigger BEFORE también lo valida)
    IF p_monto IS NULL OR p_monto <= 0 THEN
        RAISE EXCEPTION 'El monto debe ser mayor que cero' USING ERRCODE = 'BK010';
    END IF;

    -- 4) Bloquear la fila de la cuenta para evitar condiciones de carrera
    SELECT saldo INTO v_saldo FROM cuentas WHERE num_cuenta = p_num_cuenta FOR UPDATE;

    -- 5) Aplicar la operación
    IF v_nomb_tipo = 'Retiro' THEN
        IF v_saldo >= p_monto THEN
            UPDATE cuentas SET saldo = saldo - p_monto WHERE num_cuenta = p_num_cuenta;
        ELSE
            RAISE EXCEPTION 'Saldo insuficiente: disponible %, solicitado %', v_saldo, p_monto
                  USING ERRCODE = 'BK005';
        END IF;
    ELSE  -- Depósito
        UPDATE cuentas SET saldo = saldo + p_monto WHERE num_cuenta = p_num_cuenta;
    END IF;

    -- 6) Registrar la transacción
    INSERT INTO transacciones (monto_transaccion, num_cuenta_origen, cod_tipo_transaccion,
                               cod_forma_transaccion, id_cajero, id_banca_virtual)
    VALUES (p_monto, p_num_cuenta, p_cod_tipo, p_cod_forma, p_id_cajero, p_id_banca_virtual)
    RETURNING id_transaccion INTO v_id_trans;

    RETURN v_id_trans;
END;
$$;


ALTER FUNCTION public.realizar_transaccion(p_id_cliente character varying, p_num_cuenta character varying, p_cod_tipo integer, p_cod_forma integer, p_monto numeric, p_id_cajero integer, p_id_banca_virtual integer) OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: banca_virtual; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.banca_virtual (
    id_banca_virtual integer NOT NULL,
    plataforma character varying(50) NOT NULL,
    ip_origen character varying(45),
    CONSTRAINT ck_banca_virtual_plat CHECK (((plataforma)::text = ANY ((ARRAY['App Móvil'::character varying, 'Web'::character varying])::text[])))
);


ALTER TABLE public.banca_virtual OWNER TO postgres;

--
-- Name: banca_virtual_id_banca_virtual_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.banca_virtual_id_banca_virtual_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.banca_virtual_id_banca_virtual_seq OWNER TO postgres;

--
-- Name: banca_virtual_id_banca_virtual_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.banca_virtual_id_banca_virtual_seq OWNED BY public.banca_virtual.id_banca_virtual;


--
-- Name: bitacora_transacciones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bitacora_transacciones (
    id_bitacora integer NOT NULL,
    usuario_bd character varying(100) DEFAULT CURRENT_USER NOT NULL,
    fecha_operacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    operacion character varying(10) NOT NULL,
    id_transaccion integer,
    detalle text,
    CONSTRAINT ck_bitacora_op CHECK (((operacion)::text = ANY ((ARRAY['INSERT'::character varying, 'UPDATE'::character varying, 'DELETE'::character varying])::text[])))
);


ALTER TABLE public.bitacora_transacciones OWNER TO postgres;

--
-- Name: bitacora_transacciones_id_bitacora_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.bitacora_transacciones_id_bitacora_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.bitacora_transacciones_id_bitacora_seq OWNER TO postgres;

--
-- Name: bitacora_transacciones_id_bitacora_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.bitacora_transacciones_id_bitacora_seq OWNED BY public.bitacora_transacciones.id_bitacora;


--
-- Name: cajeros; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cajeros (
    id_cajero integer NOT NULL,
    ubicacion character varying(150) NOT NULL,
    estado character varying(20) DEFAULT 'Activo'::character varying NOT NULL,
    id_sucursal integer NOT NULL,
    CONSTRAINT ck_cajeros_estado CHECK (((estado)::text = ANY ((ARRAY['Activo'::character varying, 'Inactivo'::character varying, 'En mantenimiento'::character varying])::text[])))
);


ALTER TABLE public.cajeros OWNER TO postgres;

--
-- Name: ciudades; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ciudades (
    id_ciudad integer NOT NULL,
    nomb_ciudad character varying(100) NOT NULL
);


ALTER TABLE public.ciudades OWNER TO postgres;

--
-- Name: clientes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.clientes (
    id_cliente character varying(20) NOT NULL,
    nomb_cli character varying(100) NOT NULL,
    apelli_cli character varying(100) NOT NULL,
    direccion character varying(150),
    telefono character varying(20),
    correo character varying(100),
    fecha_registro date DEFAULT CURRENT_DATE NOT NULL,
    CONSTRAINT ck_clientes_fecha CHECK ((fecha_registro <= CURRENT_DATE))
);


ALTER TABLE public.clientes OWNER TO postgres;

--
-- Name: cuentas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cuentas (
    num_cuenta character varying(20) NOT NULL,
    saldo numeric(15,2) DEFAULT 0.00 NOT NULL,
    fecha_apertura date DEFAULT CURRENT_DATE NOT NULL,
    id_cliente character varying(20) NOT NULL,
    cod_tipo_cuenta integer NOT NULL,
    CONSTRAINT ck_cuentas_fecha CHECK ((fecha_apertura <= CURRENT_DATE)),
    CONSTRAINT ck_cuentas_saldo CHECK ((saldo >= (0)::numeric))
);


ALTER TABLE public.cuentas OWNER TO postgres;

--
-- Name: formas_transacciones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.formas_transacciones (
    cod_forma_transaccion integer NOT NULL,
    nomb_forma_transaccion character varying(50) NOT NULL
);


ALTER TABLE public.formas_transacciones OWNER TO postgres;

--
-- Name: prestamos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prestamos (
    id_prestamo character varying(20) NOT NULL,
    monto_aprobado numeric(15,2) NOT NULL,
    saldo_pendiente numeric(15,2) NOT NULL,
    tasa_interes numeric(5,2) NOT NULL,
    cuotas_aprobadas integer NOT NULL,
    fecha_aprobacion date DEFAULT CURRENT_DATE NOT NULL,
    id_cliente character varying(20) NOT NULL,
    CONSTRAINT ck_prestamos_cuotas CHECK ((cuotas_aprobadas > 0)),
    CONSTRAINT ck_prestamos_fecha CHECK ((fecha_aprobacion <= CURRENT_DATE)),
    CONSTRAINT ck_prestamos_monto CHECK ((monto_aprobado > (0)::numeric)),
    CONSTRAINT ck_prestamos_saldo CHECK (((saldo_pendiente >= (0)::numeric) AND (saldo_pendiente <= monto_aprobado))),
    CONSTRAINT ck_prestamos_tasa CHECK ((tasa_interes >= (0)::numeric))
);


ALTER TABLE public.prestamos OWNER TO postgres;

--
-- Name: sucursales; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sucursales (
    id_sucursal integer NOT NULL,
    nomb_sucursal character varying(100) NOT NULL,
    direccion_sucursal character varying(150) NOT NULL,
    id_ciudad integer NOT NULL
);


ALTER TABLE public.sucursales OWNER TO postgres;

--
-- Name: tipo_cuentas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tipo_cuentas (
    cod_tipo_cuenta integer NOT NULL,
    nomb_tipo_cuenta character varying(50) NOT NULL
);


ALTER TABLE public.tipo_cuentas OWNER TO postgres;

--
-- Name: tipo_transacciones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tipo_transacciones (
    cod_tipo_transaccion integer NOT NULL,
    nomb_tipo_transaccion character varying(50) NOT NULL,
    CONSTRAINT ck_tipo_transacciones_nomb CHECK (((nomb_tipo_transaccion)::text = ANY ((ARRAY['Depósito'::character varying, 'Retiro'::character varying])::text[])))
);


ALTER TABLE public.tipo_transacciones OWNER TO postgres;

--
-- Name: transacciones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.transacciones (
    id_transaccion integer NOT NULL,
    fecha_transaccion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    monto_transaccion numeric(15,2) NOT NULL,
    num_cuenta_origen character varying(20) NOT NULL,
    cod_tipo_transaccion integer NOT NULL,
    cod_forma_transaccion integer NOT NULL,
    id_cajero integer,
    id_banca_virtual integer,
    CONSTRAINT ck_transacciones_fecha CHECK (((fecha_transaccion)::date <= CURRENT_DATE)),
    CONSTRAINT ck_transacciones_monto CHECK ((monto_transaccion > (0)::numeric)),
    CONSTRAINT ck_transacciones_origen CHECK (((((id_cajero IS NOT NULL))::integer + ((id_banca_virtual IS NOT NULL))::integer) = 1))
);


ALTER TABLE public.transacciones OWNER TO postgres;

--
-- Name: transacciones_id_transaccion_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.transacciones_id_transaccion_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.transacciones_id_transaccion_seq OWNER TO postgres;

--
-- Name: transacciones_id_transaccion_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.transacciones_id_transaccion_seq OWNED BY public.transacciones.id_transaccion;


--
-- Name: banca_virtual id_banca_virtual; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.banca_virtual ALTER COLUMN id_banca_virtual SET DEFAULT nextval('public.banca_virtual_id_banca_virtual_seq'::regclass);


--
-- Name: bitacora_transacciones id_bitacora; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacora_transacciones ALTER COLUMN id_bitacora SET DEFAULT nextval('public.bitacora_transacciones_id_bitacora_seq'::regclass);


--
-- Name: transacciones id_transaccion; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transacciones ALTER COLUMN id_transaccion SET DEFAULT nextval('public.transacciones_id_transaccion_seq'::regclass);


--
-- Data for Name: banca_virtual; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.banca_virtual (id_banca_virtual, plataforma, ip_origen) FROM stdin;
1	App Móvil	190.25.10.15
2	Web	181.54.22.101
3	App Móvil	186.80.7.33
\.


--
-- Data for Name: bitacora_transacciones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bitacora_transacciones (id_bitacora, usuario_bd, fecha_operacion, operacion, id_transaccion, detalle) FROM stdin;
1	postgres	2026-11-02 08:22:28.879086	INSERT	1	nuevo={"id_cajero": 1, "id_transaccion": 1, "id_banca_virtual": null, "fecha_transaccion": "2026-11-02T08:22:28.879086", "monto_transaccion": 1500000.00, "num_cuenta_origen": "AH-0001", "cod_tipo_transaccion": 1, "cod_forma_transaccion": 1}
2	postgres	2026-11-02 08:22:28.895568	INSERT	2	nuevo={"id_cajero": null, "id_transaccion": 2, "id_banca_virtual": 2, "fecha_transaccion": "2026-11-02T08:22:28.895568", "monto_transaccion": 800000.00, "num_cuenta_origen": "CO-0002", "cod_tipo_transaccion": 1, "cod_forma_transaccion": 3}
3	postgres	2026-11-02 08:22:28.903043	INSERT	3	nuevo={"id_cajero": 2, "id_transaccion": 3, "id_banca_virtual": null, "fecha_transaccion": "2026-11-02T08:22:28.903043", "monto_transaccion": 600000.00, "num_cuenta_origen": "AH-0003", "cod_tipo_transaccion": 1, "cod_forma_transaccion": 1}
4	postgres	2026-11-02 08:22:28.909937	INSERT	4	nuevo={"id_cajero": null, "id_transaccion": 4, "id_banca_virtual": 1, "fecha_transaccion": "2026-11-02T08:22:28.909937", "monto_transaccion": 2200000.00, "num_cuenta_origen": "NO-0004", "cod_tipo_transaccion": 1, "cod_forma_transaccion": 3}
5	postgres	2026-11-02 08:22:28.916543	INSERT	5	nuevo={"id_cajero": 3, "id_transaccion": 5, "id_banca_virtual": null, "fecha_transaccion": "2026-11-02T08:22:28.916543", "monto_transaccion": 350000.00, "num_cuenta_origen": "AH-0005", "cod_tipo_transaccion": 1, "cod_forma_transaccion": 2}
6	postgres	2026-11-02 08:22:28.921704	INSERT	6	nuevo={"id_cajero": 5, "id_transaccion": 6, "id_banca_virtual": null, "fecha_transaccion": "2026-11-02T08:22:28.921704", "monto_transaccion": 1000000.00, "num_cuenta_origen": "CO-0006", "cod_tipo_transaccion": 1, "cod_forma_transaccion": 1}
7	postgres	2026-11-02 08:22:28.927287	INSERT	7	nuevo={"id_cajero": 1, "id_transaccion": 7, "id_banca_virtual": null, "fecha_transaccion": "2026-11-02T08:22:28.927287", "monto_transaccion": 300000.00, "num_cuenta_origen": "AH-0001", "cod_tipo_transaccion": 2, "cod_forma_transaccion": 1}
8	postgres	2026-11-02 08:22:28.932761	INSERT	8	nuevo={"id_cajero": null, "id_transaccion": 8, "id_banca_virtual": 3, "fecha_transaccion": "2026-11-02T08:22:28.932761", "monto_transaccion": 450000.00, "num_cuenta_origen": "NO-0004", "cod_tipo_transaccion": 2, "cod_forma_transaccion": 3}
\.


--
-- Data for Name: cajeros; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cajeros (id_cajero, ubicacion, estado, id_sucursal) FROM stdin;
1	Centro Comercial Primavera	Activo	1
2	Parque Los Libertadores	Activo	1
3	Barzal Plaza	Activo	2
4	Plaza Principal Acacías	En mantenimiento	3
5	Terminal Granada	Activo	4
\.


--
-- Data for Name: ciudades; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.ciudades (id_ciudad, nomb_ciudad) FROM stdin;
1	Villavicencio
2	Acacías
3	Granada
4	Bogotá
\.


--
-- Data for Name: clientes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.clientes (id_cliente, nomb_cli, apelli_cli, direccion, telefono, correo, fecha_registro) FROM stdin;
1006123456	Laura	Gómez Rojas	Cra 33 # 20-15	3101234567	laura.gomez@mail.com	2023-03-10
1006987654	Andrés	Pardo Medina	Cl 40 # 25-60	3157654321	andres.pardo@mail.com	2022-11-05
40345678	María	Quintero Díaz	Cra 22 # 8-44	3009876543	maria.quintero@mail.com	2021-07-19
86012345	Jhon	Castro Ríos	Cl 12 # 30-18	3204567890	jhon.castro@mail.com	2024-01-22
1121987123	Yireth	Moreno Silva	Cra 45 # 33-27	3126543210	yireth.moreno@mail.com	2024-06-03
\.


--
-- Data for Name: cuentas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cuentas (num_cuenta, saldo, fecha_apertura, id_cliente, cod_tipo_cuenta) FROM stdin;
CO-0002	800000.00	2023-04-02	1006123456	2
AH-0003	600000.00	2022-11-05	1006987654	1
AH-0005	350000.00	2024-01-22	86012345	1
CO-0006	1000000.00	2024-06-03	1121987123	2
AH-0001	1200000.00	2023-03-10	1006123456	1
NO-0004	1750000.00	2021-07-19	40345678	3
\.


--
-- Data for Name: formas_transacciones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.formas_transacciones (cod_forma_transaccion, nomb_forma_transaccion) FROM stdin;
1	Efectivo
2	Cheque
3	Transferencia
4	Tarjeta débito
\.


--
-- Data for Name: prestamos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.prestamos (id_prestamo, monto_aprobado, saldo_pendiente, tasa_interes, cuotas_aprobadas, fecha_aprobacion, id_cliente) FROM stdin;
PR-001	10000000.00	7500000.00	1.80	24	2024-02-15	1006123456
PR-002	5000000.00	5000000.00	2.10	12	2025-03-01	1006987654
PR-003	25000000.00	18200000.00	1.50	60	2023-09-12	40345678
\.


--
-- Data for Name: sucursales; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sucursales (id_sucursal, nomb_sucursal, direccion_sucursal, id_ciudad) FROM stdin;
1	Sucursal Centro	Cra 31 # 38-20	1
2	Sucursal Barzal	Cl 15 # 42-10	1
3	Sucursal Acacías	Cra 15 # 12-30	2
4	Sucursal Granada	Cl 8 # 14-05	3
\.


--
-- Data for Name: tipo_cuentas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tipo_cuentas (cod_tipo_cuenta, nomb_tipo_cuenta) FROM stdin;
1	Ahorros
2	Corriente
3	Nómina
\.


--
-- Data for Name: tipo_transacciones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tipo_transacciones (cod_tipo_transaccion, nomb_tipo_transaccion) FROM stdin;
1	Depósito
2	Retiro
\.


--
-- Data for Name: transacciones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.transacciones (id_transaccion, fecha_transaccion, monto_transaccion, num_cuenta_origen, cod_tipo_transaccion, cod_forma_transaccion, id_cajero, id_banca_virtual) FROM stdin;
1	2026-11-02 08:22:28.879086	1500000.00	AH-0001	1	1	1	\N
2	2026-11-02 08:22:28.895568	800000.00	CO-0002	1	3	\N	2
3	2026-11-02 08:22:28.903043	600000.00	AH-0003	1	1	2	\N
4	2026-11-02 08:22:28.909937	2200000.00	NO-0004	1	3	\N	1
5	2026-11-02 08:22:28.916543	350000.00	AH-0005	1	2	3	\N
6	2026-11-02 08:22:28.921704	1000000.00	CO-0006	1	1	5	\N
7	2026-11-02 08:22:28.927287	300000.00	AH-0001	2	1	1	\N
8	2026-11-02 08:22:28.932761	450000.00	NO-0004	2	3	\N	3
\.


--
-- Name: banca_virtual_id_banca_virtual_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.banca_virtual_id_banca_virtual_seq', 3, true);


--
-- Name: bitacora_transacciones_id_bitacora_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.bitacora_transacciones_id_bitacora_seq', 8, true);


--
-- Name: transacciones_id_transaccion_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.transacciones_id_transaccion_seq', 8, true);


--
-- Name: banca_virtual pk_banca_virtual; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.banca_virtual
    ADD CONSTRAINT pk_banca_virtual PRIMARY KEY (id_banca_virtual);


--
-- Name: bitacora_transacciones pk_bitacora; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacora_transacciones
    ADD CONSTRAINT pk_bitacora PRIMARY KEY (id_bitacora);


--
-- Name: cajeros pk_cajeros; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cajeros
    ADD CONSTRAINT pk_cajeros PRIMARY KEY (id_cajero);


--
-- Name: ciudades pk_ciudades; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ciudades
    ADD CONSTRAINT pk_ciudades PRIMARY KEY (id_ciudad);


--
-- Name: clientes pk_clientes; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT pk_clientes PRIMARY KEY (id_cliente);


--
-- Name: cuentas pk_cuentas; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cuentas
    ADD CONSTRAINT pk_cuentas PRIMARY KEY (num_cuenta);


--
-- Name: formas_transacciones pk_formas_transacciones; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.formas_transacciones
    ADD CONSTRAINT pk_formas_transacciones PRIMARY KEY (cod_forma_transaccion);


--
-- Name: prestamos pk_prestamos; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prestamos
    ADD CONSTRAINT pk_prestamos PRIMARY KEY (id_prestamo);


--
-- Name: sucursales pk_sucursales; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sucursales
    ADD CONSTRAINT pk_sucursales PRIMARY KEY (id_sucursal);


--
-- Name: tipo_cuentas pk_tipo_cuentas; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipo_cuentas
    ADD CONSTRAINT pk_tipo_cuentas PRIMARY KEY (cod_tipo_cuenta);


--
-- Name: tipo_transacciones pk_tipo_transacciones; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipo_transacciones
    ADD CONSTRAINT pk_tipo_transacciones PRIMARY KEY (cod_tipo_transaccion);


--
-- Name: transacciones pk_transacciones; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transacciones
    ADD CONSTRAINT pk_transacciones PRIMARY KEY (id_transaccion);


--
-- Name: ciudades uq_ciudades_nomb; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ciudades
    ADD CONSTRAINT uq_ciudades_nomb UNIQUE (nomb_ciudad);


--
-- Name: clientes uq_clientes_correo; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT uq_clientes_correo UNIQUE (correo);


--
-- Name: formas_transacciones uq_formas_transacciones_nomb; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.formas_transacciones
    ADD CONSTRAINT uq_formas_transacciones_nomb UNIQUE (nomb_forma_transaccion);


--
-- Name: tipo_cuentas uq_tipo_cuentas_nomb; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipo_cuentas
    ADD CONSTRAINT uq_tipo_cuentas_nomb UNIQUE (nomb_tipo_cuenta);


--
-- Name: tipo_transacciones uq_tipo_transacciones_nomb; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipo_transacciones
    ADD CONSTRAINT uq_tipo_transacciones_nomb UNIQUE (nomb_tipo_transaccion);


--
-- Name: idx_cuentas_cliente; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_cuentas_cliente ON public.cuentas USING btree (id_cliente);


--
-- Name: idx_transacciones_cuenta; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_transacciones_cuenta ON public.transacciones USING btree (num_cuenta_origen);


--
-- Name: transacciones trg_auditar_transaccion; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_auditar_transaccion AFTER INSERT OR DELETE OR UPDATE ON public.transacciones FOR EACH ROW EXECUTE FUNCTION public.fn_auditar_transaccion();


--
-- Name: transacciones trg_validar_transaccion; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_validar_transaccion BEFORE INSERT OR UPDATE ON public.transacciones FOR EACH ROW EXECUTE FUNCTION public.fn_validar_transaccion();


--
-- Name: cajeros fk_cajeros_sucursal; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cajeros
    ADD CONSTRAINT fk_cajeros_sucursal FOREIGN KEY (id_sucursal) REFERENCES public.sucursales(id_sucursal);


--
-- Name: cuentas fk_cuentas_cliente; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cuentas
    ADD CONSTRAINT fk_cuentas_cliente FOREIGN KEY (id_cliente) REFERENCES public.clientes(id_cliente);


--
-- Name: cuentas fk_cuentas_tipo; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cuentas
    ADD CONSTRAINT fk_cuentas_tipo FOREIGN KEY (cod_tipo_cuenta) REFERENCES public.tipo_cuentas(cod_tipo_cuenta);


--
-- Name: prestamos fk_prestamos_cliente; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prestamos
    ADD CONSTRAINT fk_prestamos_cliente FOREIGN KEY (id_cliente) REFERENCES public.clientes(id_cliente);


--
-- Name: sucursales fk_sucursales_ciudad; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sucursales
    ADD CONSTRAINT fk_sucursales_ciudad FOREIGN KEY (id_ciudad) REFERENCES public.ciudades(id_ciudad);


--
-- Name: transacciones fk_transacciones_banca; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transacciones
    ADD CONSTRAINT fk_transacciones_banca FOREIGN KEY (id_banca_virtual) REFERENCES public.banca_virtual(id_banca_virtual);


--
-- Name: transacciones fk_transacciones_cajero; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transacciones
    ADD CONSTRAINT fk_transacciones_cajero FOREIGN KEY (id_cajero) REFERENCES public.cajeros(id_cajero);


--
-- Name: transacciones fk_transacciones_cuenta; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transacciones
    ADD CONSTRAINT fk_transacciones_cuenta FOREIGN KEY (num_cuenta_origen) REFERENCES public.cuentas(num_cuenta);


--
-- Name: transacciones fk_transacciones_forma; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transacciones
    ADD CONSTRAINT fk_transacciones_forma FOREIGN KEY (cod_forma_transaccion) REFERENCES public.formas_transacciones(cod_forma_transaccion);


--
-- Name: transacciones fk_transacciones_tipo; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transacciones
    ADD CONSTRAINT fk_transacciones_tipo FOREIGN KEY (cod_tipo_transaccion) REFERENCES public.tipo_transacciones(cod_tipo_transaccion);


--
-- PostgreSQL database dump complete
--

\unrestrict i9K2AjpXSQaPM1dSyC39FnGO7uFFdDjwptoEICcy48c53Zz0VEbqcPTTMWZcljK



\restrict ZZBxj25gIK00ZsjVLfHgFcdRqetBUS6xwYE82ZBV8ERruelWBNI3CkU5hZRLoKW
SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

ALTER TABLE IF EXISTS ONLY public.transacciones DROP CONSTRAINT IF EXISTS fk_transacciones_tipo;
ALTER TABLE IF EXISTS ONLY public.transacciones DROP CONSTRAINT IF EXISTS fk_transacciones_forma;
ALTER TABLE IF EXISTS ONLY public.transacciones DROP CONSTRAINT IF EXISTS fk_transacciones_cuenta;
ALTER TABLE IF EXISTS ONLY public.transacciones DROP CONSTRAINT IF EXISTS fk_transacciones_cajero;
ALTER TABLE IF EXISTS ONLY public.transacciones DROP CONSTRAINT IF EXISTS fk_transacciones_banca;
ALTER TABLE IF EXISTS ONLY public.sucursales DROP CONSTRAINT IF EXISTS fk_sucursales_ciudad;
ALTER TABLE IF EXISTS ONLY public.prestamos DROP CONSTRAINT IF EXISTS fk_prestamos_cliente;
ALTER TABLE IF EXISTS ONLY public.cuentas DROP CONSTRAINT IF EXISTS fk_cuentas_tipo;
ALTER TABLE IF EXISTS ONLY public.cuentas DROP CONSTRAINT IF EXISTS fk_cuentas_cliente;
ALTER TABLE IF EXISTS ONLY public.cajeros_automaticos DROP CONSTRAINT IF EXISTS fk_cajeros_sucursal;
DROP TRIGGER IF EXISTS trg_validar_transaccion ON public.transacciones;
DROP TRIGGER IF EXISTS trg_auditar_transaccion ON public.transacciones;
DROP INDEX IF EXISTS public.idx_transacciones_cuenta;
DROP INDEX IF EXISTS public.idx_cuentas_cliente;
ALTER TABLE IF EXISTS ONLY public.tipo_transacciones DROP CONSTRAINT IF EXISTS uq_tipo_transacciones_nomb;
ALTER TABLE IF EXISTS ONLY public.tipo_cuentas DROP CONSTRAINT IF EXISTS uq_tipo_cuentas_nomb;
ALTER TABLE IF EXISTS ONLY public.formas_transacciones DROP CONSTRAINT IF EXISTS uq_formas_transacciones_nomb;
ALTER TABLE IF EXISTS ONLY public.clientes DROP CONSTRAINT IF EXISTS uq_clientes_correo;
ALTER TABLE IF EXISTS ONLY public.ciudades DROP CONSTRAINT IF EXISTS uq_ciudades_nomb;
ALTER TABLE IF EXISTS ONLY public.transacciones DROP CONSTRAINT IF EXISTS pk_transacciones;
ALTER TABLE IF EXISTS ONLY public.tipo_transacciones DROP CONSTRAINT IF EXISTS pk_tipo_transacciones;
ALTER TABLE IF EXISTS ONLY public.tipo_cuentas DROP CONSTRAINT IF EXISTS pk_tipo_cuentas;
ALTER TABLE IF EXISTS ONLY public.sucursales DROP CONSTRAINT IF EXISTS pk_sucursales;
ALTER TABLE IF EXISTS ONLY public.prestamos DROP CONSTRAINT IF EXISTS pk_prestamos;
ALTER TABLE IF EXISTS ONLY public.formas_transacciones DROP CONSTRAINT IF EXISTS pk_formas_transacciones;
ALTER TABLE IF EXISTS ONLY public.cuentas DROP CONSTRAINT IF EXISTS pk_cuentas;
ALTER TABLE IF EXISTS ONLY public.clientes DROP CONSTRAINT IF EXISTS pk_clientes;
ALTER TABLE IF EXISTS ONLY public.ciudades DROP CONSTRAINT IF EXISTS pk_ciudades;
ALTER TABLE IF EXISTS ONLY public.cajeros_automaticos DROP CONSTRAINT IF EXISTS pk_cajeros;
ALTER TABLE IF EXISTS ONLY public.bitacora_transacciones DROP CONSTRAINT IF EXISTS pk_bitacora;
ALTER TABLE IF EXISTS ONLY public.banca_virtual DROP CONSTRAINT IF EXISTS pk_banca_virtual;
ALTER TABLE IF EXISTS ONLY public.empleados DROP CONSTRAINT IF EXISTS empleados_pkey;
ALTER TABLE IF EXISTS public.transacciones ALTER COLUMN id_transaccion DROP DEFAULT;
ALTER TABLE IF EXISTS public.bitacora_transacciones ALTER COLUMN id_bitacora DROP DEFAULT;
ALTER TABLE IF EXISTS public.banca_virtual ALTER COLUMN id_banca_virtual DROP DEFAULT;
DROP VIEW IF EXISTS public.vista_movimientos_clientes;
DROP SEQUENCE IF EXISTS public.transacciones_id_transaccion_seq;
DROP TABLE IF EXISTS public.transacciones;
DROP TABLE IF EXISTS public.tipo_transacciones;
DROP TABLE IF EXISTS public.tipo_cuentas;
DROP TABLE IF EXISTS public.sucursales;
DROP TABLE IF EXISTS public.prestamos;
DROP TABLE IF EXISTS public.formas_transacciones;
DROP TABLE IF EXISTS public.empleados;
DROP TABLE IF EXISTS public.cuentas;
DROP TABLE IF EXISTS public.clientes;
DROP TABLE IF EXISTS public.ciudades;
DROP TABLE IF EXISTS public.cajeros_automaticos;
DROP SEQUENCE IF EXISTS public.bitacora_transacciones_id_bitacora_seq;
DROP TABLE IF EXISTS public.bitacora_transacciones;
DROP SEQUENCE IF EXISTS public.banca_virtual_id_banca_virtual_seq;
DROP TABLE IF EXISTS public.banca_virtual;
DROP FUNCTION IF EXISTS public.realizar_transaccion(p_id_cliente character varying, p_num_cuenta character varying, p_cod_tipo integer, p_cod_forma integer, p_monto numeric, p_id_cajero integer, p_id_banca_virtual integer);
DROP FUNCTION IF EXISTS public.fn_validar_transaccion();
DROP FUNCTION IF EXISTS public.fn_auditar_transaccion();

--FUNCION AUDITAR TRASCCION

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
    ELSE 
        INSERT INTO bitacora_transacciones (operacion, id_transaccion, detalle)
        VALUES (TG_OP, OLD.id_transaccion, 'borrado=' || to_jsonb(OLD)::text);
    END IF;
    RETURN NULL;  
$$;

ALTER FUNCTION public.fn_auditar_transaccion() OWNER TO postgres;
--FUNCION VALIDAR TRANSACCION


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

    RETURN NEW;  
END;
$$;


ALTER FUNCTION public.fn_validar_transaccion() OWNER TO postgres;

--FUNCION REALIZAR TRANSACCION

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

--CREACION TABLAS
CREATE TABLE public.banca_virtual (
    id_banca_virtual integer NOT NULL,
    plataforma character varying(50) NOT NULL,
    ip_origen character varying(45),
    CONSTRAINT ck_banca_virtual_plat CHECK (((plataforma)::text = ANY (ARRAY[('App Móvil'::character varying)::text, ('Web'::character varying)::text])))
);


ALTER TABLE public.banca_virtual OWNER TO postgres;



CREATE SEQUENCE public.banca_virtual_id_banca_virtual_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.banca_virtual_id_banca_virtual_seq OWNER TO postgres;

ALTER SEQUENCE public.banca_virtual_id_banca_virtual_seq OWNED BY public.banca_virtual.id_banca_virtual;
CREATE TABLE public.bitacora_transacciones (
    id_bitacora integer NOT NULL,
    usuario_bd character varying(100) DEFAULT CURRENT_USER NOT NULL,
    fecha_operacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    operacion character varying(10) NOT NULL,
    id_transaccion integer,
    detalle text,
    CONSTRAINT ck_bitacora_op CHECK (((operacion)::text = ANY (ARRAY[('INSERT'::character varying)::text, ('UPDATE'::character varying)::text, ('DELETE'::character varying)::text])))
);


ALTER TABLE public.bitacora_transacciones OWNER TO postgres;

CREATE SEQUENCE public.bitacora_transacciones_id_bitacora_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.bitacora_transacciones_id_bitacora_seq OWNER TO postgres;


ALTER SEQUENCE public.bitacora_transacciones_id_bitacora_seq OWNED BY public.bitacora_transacciones.id_bitacora;


CREATE TABLE public.cajeros_automaticos (
    id_cajero integer NOT NULL,
    ubicacion character varying(150) NOT NULL,
    estado character varying(20) DEFAULT 'Activo'::character varying NOT NULL,
    id_sucursal integer NOT NULL,
    CONSTRAINT ck_cajeros_estado CHECK (((estado)::text = ANY (ARRAY[('Activo'::character varying)::text, ('Inactivo'::character varying)::text, ('En mantenimiento'::character varying)::text])))
);


ALTER TABLE public.cajeros_automaticos OWNER TO postgres;

CREATE TABLE public.ciudades (
    id_ciudad integer NOT NULL,
    nomb_ciudad character varying(100) NOT NULL
);


ALTER TABLE public.ciudades OWNER TO postgres;


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

CREATE TABLE public.empleados (
    id_empleado character varying(20) NOT NULL,
    nombres character varying(100) NOT NULL,
    apellidos character varying(100) NOT NULL,
    rol character varying(50) NOT NULL,
    correo character varying(100)
);


ALTER TABLE public.empleados OWNER TO postgres;

CREATE TABLE public.formas_transacciones (
    cod_forma_transaccion integer NOT NULL,
    nomb_forma_transaccion character varying(50) NOT NULL
);


ALTER TABLE public.formas_transacciones OWNER TO postgres;

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

CREATE TABLE public.sucursales (
    id_sucursal integer NOT NULL,
    nomb_sucursal character varying(100) NOT NULL,
    direccion_sucursal character varying(150) NOT NULL,
    id_ciudad integer NOT NULL
);


ALTER TABLE public.sucursales OWNER TO postgres;


CREATE TABLE public.tipo_cuentas (
    cod_tipo_cuenta integer NOT NULL,
    nomb_tipo_cuenta character varying(50) NOT NULL
);


ALTER TABLE public.tipo_cuentas OWNER TO postgres;


CREATE TABLE public.tipo_transacciones (
    cod_tipo_transaccion integer NOT NULL,
    nomb_tipo_transaccion character varying(50) NOT NULL,
    CONSTRAINT ck_tipo_transacciones_nomb CHECK (nomb_tipo_transaccion IN ('Depósito', 'Retiro', 'Abono a Préstamo'))
);

ALTER TABLE public.tipo_transacciones OWNER TO postgres;


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


CREATE SEQUENCE public.transacciones_id_transaccion_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.transacciones_id_transaccion_seq OWNER TO postgres;

ALTER SEQUENCE public.transacciones_id_transaccion_seq OWNED BY public.transacciones.id_transaccion;

CREATE VIEW public.vista_movimientos_clientes AS
 SELECT (((c.nomb_cli)::text || ' '::text) || (c.apelli_cli)::text) AS cliente,
    tp.nomb_tipo_transaccion AS operacion,
    t.monto_transaccion AS monto,
    t.fecha_transaccion AS fecha
   FROM (((public.transacciones t
     JOIN public.cuentas cu ON (((t.num_cuenta_origen)::text = (cu.num_cuenta)::text)))
     JOIN public.clientes c ON (((cu.id_cliente)::text = (c.id_cliente)::text)))
     JOIN public.tipo_transacciones tp ON ((t.cod_tipo_transaccion = tp.cod_tipo_transaccion)));


ALTER TABLE public.vista_movimientos_clientes OWNER TO postgres;

ALTER TABLE ONLY public.banca_virtual ALTER COLUMN id_banca_virtual SET DEFAULT nextval('public.banca_virtual_id_banca_virtual_seq'::regclass);


ALTER TABLE ONLY public.bitacora_transacciones ALTER COLUMN id_bitacora SET DEFAULT nextval('public.bitacora_transacciones_id_bitacora_seq'::regclass);


ALTER TABLE ONLY public.transacciones ALTER COLUMN id_transaccion SET DEFAULT nextval('public.transacciones_id_transaccion_seq'::regclass);

COPY public.banca_virtual (id_banca_virtual, plataforma, ip_origen) FROM stdin;
1	App Móvil	190.25.10.15
2	Web	181.54.22.101
3	App Móvil	186.80.7.33
\.


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


COPY public.cajeros_automaticos (id_cajero, ubicacion, estado, id_sucursal) FROM stdin;
1	Centro Comercial Primavera	Activo	1
2	Parque Los Libertadores	Activo	1
3	Barzal Plaza	Activo	2
4	Plaza Principal Acacías	En mantenimiento	3
5	Terminal Granada	Activo	4
\.

COPY public.ciudades (id_ciudad, nomb_ciudad) FROM stdin;
1	Villavicencio
2	Acacías
3	Granada
4	Bogotá
\.


COPY public.clientes (id_cliente, nomb_cli, apelli_cli, direccion, telefono, correo, fecha_registro) FROM stdin;
1006123456	Laura	Gómez Rojas	Cra 33 # 20-15	3101234567	laura.gomez@mail.com	2023-03-10
1006987654	Andrés	Pardo Medina	Cl 40 # 25-60	3157654321	andres.pardo@mail.com	2022-11-05
40345678	María	Quintero Díaz	Cra 22 # 8-44	3009876543	maria.quintero@mail.com	2021-07-19
86012345	Jhon	Castro Ríos	Cl 12 # 30-18	3204567890	jhon.castro@mail.com	2024-01-22
1121987123	Yireth	Moreno Silva	Cra 45 # 33-27	3126543210	yireth.moreno@mail.com	2024-06-03
1001001001	Carlos	Gómez Pérez	Cl 12 # 4-50	3101234561	carlos.gomez@mail.com	2024-01-10
1001001002	Ana	Silva Rojas	Cra 8 # 14-22	3101234562	ana.silva@mail.com	2024-02-11
1001001003	Luis	Pérez Castro	Cl 20 # 10-15	3101234563	luis.perez@mail.com	2024-03-12
1001001004	Marta	Ramírez López	Cra 5 # 8-10	3101234564	marta.ramirez@mail.com	2024-04-13
1001001005	Pedro	Sánchez Díaz	Cl 30 # 20-5	3101234565	pedro.sanchez@mail.com	2024-05-14
1001001006	Elena	Castro Ruiz	Cra 12 # 22-10	3101234566	elena.castro@mail.com	2024-06-15
1001001007	Miguel	Rojas Silva	Cl 45 # 30-20	3101234567	miguel.rojas@mail.com	2024-07-16
1001001008	Sofia	Mendoza Cruz	Cra 15 # 40-30	3101234568	sofia.mendoza@mail.com	2024-08-17
1001001009	Diego	Ortiz Ríos	Cl 50 # 10-50	3101234569	diego.ortiz@mail.com	2024-09-18
1001001010	Lucia	Ríos Vargas	Cra 20 # 50-40	3101234570	lucia.rios@mail.com	2024-10-19
1001001011	Andrés	Herrera Guzmán	Cl 60 # 20-10	3101234571	andres.herrera@mail.com	2024-11-20
1001001012	Paula	Vargas Navarro	Cra 25 # 60-20	3101234572	paula.vargas@mail.com	2024-12-21
1001001013	Javier	Guzmán Salazar	Cl 70 # 30-15	3101234573	javier.guzman@mail.com	2025-01-22
1001001014	Gabriela	Navarro Gómez	Cra 30 # 70-30	3101234574	gabriela.navarro@mail.com	2025-02-23
1001001015	Fernando	Salazar Ortiz	Cl 80 # 40-25	3101234575	fernando.salazar@mail.com	2025-03-24
\.


COPY public.cuentas (num_cuenta, saldo, fecha_apertura, id_cliente, cod_tipo_cuenta) FROM stdin;
CO-0002	800000.00	2023-04-02	1006123456	2
AH-0003	600000.00	2022-11-05	1006987654	1
AH-0005	350000.00	2024-01-22	86012345	1
CO-0006	1000000.00	2024-06-03	1121987123	2
AH-0001	1200000.00	2023-03-10	1006123456	1
NO-0004	1750000.00	2021-07-19	40345678	3
\.



COPY public.empleados (id_empleado, nombres, apellidos, rol, correo) FROM stdin;
2001001001	Julian	Castillo	cajero	julian.c@bancovillavicencio.com
2001001002	Carolina	Mendez	cajero	carolina.m@bancovillavicencio.com
2001001003	Esteban	Rios	cajero	esteban.r@bancovillavicencio.com
2001001004	Natalia	Guzman	cajero	natalia.g@bancovillavicencio.com
2001001005	Sebastian	Mejia	cajero	sebastian.m@bancovillavicencio.com
2001001006	Daniela	Osorio	cajero	daniela.o@bancovillavicencio.com
2001001007	Camilo	Suarez	cajero	camilo.s@bancovillavicencio.com
2001001008	Tatiana	Velez	cajero	tatiana.v@bancovillavicencio.com
2001001009	Nicolas	Bermudez	cajero	nicolas.b@bancovillavicencio.com
2001001010	Valeria	Jaramillo	cajero	valeria.j@bancovillavicencio.com
9009009001	Yireth	Moreno	administrador	admin.yireth@bancovillavicencio.com
9009009002	Monica	Salazar	administrador	admin.monica@bancovillavicencio.com
9009009003	Roberto	Castano	administrador	admin.roberto@bancovillavicencio.com
9009009004	Diana	Perez	administrador	admin.diana@bancovillavicencio.com
9009009005	Felipe	Andres	administrador	admin.felipe@bancovillavicencio.com
9009009006	Victoria	Giraldo	administrador	admin.victoria@bancovillavicencio.com
9009009007	Juan	David	administrador	admin.juan@bancovillavicencio.com
9009009008	Patricia	Vega	administrador	admin.patricia@bancovillavicencio.com
9009009009	Hector	Salamanca	administrador	admin.hector@bancovillavicencio.com
9009009010	Paola	Andrea	administrador	admin.paola@bancovillavicencio.com
\.


COPY public.formas_transacciones (cod_forma_transaccion, nomb_forma_transaccion) FROM stdin;
1	Efectivo
2	Cheque
3	Transferencia
4	Tarjeta débito
\.


COPY public.prestamos (id_prestamo, monto_aprobado, saldo_pendiente, tasa_interes, cuotas_aprobadas, fecha_aprobacion, id_cliente) FROM stdin;
PR-001	10000000.00	7500000.00	1.80	24	2024-02-15	1006123456
PR-002	5000000.00	5000000.00	2.10	12	2025-03-01	1006987654
PR-003	25000000.00	18200000.00	1.50	60	2023-09-12	40345678
\.


COPY public.sucursales (id_sucursal, nomb_sucursal, direccion_sucursal, id_ciudad) FROM stdin;
1	Sucursal Centro	Cra 31 # 38-20	1
2	Sucursal Barzal	Cl 15 # 42-10	1
3	Sucursal Acacías	Cra 15 # 12-30	2
4	Sucursal Granada	Cl 8 # 14-05	3
\.


COPY public.tipo_cuentas (cod_tipo_cuenta, nomb_tipo_cuenta) FROM stdin;
1	Ahorros
2	Corriente
3	Nómina
\.



COPY public.tipo_transacciones (cod_tipo_transaccion, nomb_tipo_transaccion) FROM stdin;
1	Depósito
2	Retiro
\.
INSERT INTO public.tipo_transacciones (cod_tipo_transaccion, nomb_tipo_transaccion) 
VALUES (3, 'Abono a Préstamo') 
ON CONFLICT DO NOTHING;



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

SELECT pg_catalog.setval('public.banca_virtual_id_banca_virtual_seq', 3, true);


SELECT pg_catalog.setval('public.bitacora_transacciones_id_bitacora_seq', 8, true);


SELECT pg_catalog.setval('public.transacciones_id_transaccion_seq', 8, true);


ALTER TABLE ONLY public.empleados
    ADD CONSTRAINT empleados_pkey PRIMARY KEY (id_empleado);


ALTER TABLE ONLY public.banca_virtual
    ADD CONSTRAINT pk_banca_virtual PRIMARY KEY (id_banca_virtual);


ALTER TABLE ONLY public.bitacora_transacciones
    ADD CONSTRAINT pk_bitacora PRIMARY KEY (id_bitacora);

ALTER TABLE ONLY public.cajeros_automaticos
    ADD CONSTRAINT pk_cajeros PRIMARY KEY (id_cajero);


ALTER TABLE ONLY public.ciudades
    ADD CONSTRAINT pk_ciudades PRIMARY KEY (id_ciudad);


ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT pk_clientes PRIMARY KEY (id_cliente);


ALTER TABLE ONLY public.cuentas
    ADD CONSTRAINT pk_cuentas PRIMARY KEY (num_cuenta);


ALTER TABLE ONLY public.formas_transacciones
    ADD CONSTRAINT pk_formas_transacciones PRIMARY KEY (cod_forma_transaccion);


ALTER TABLE ONLY public.prestamos
    ADD CONSTRAINT pk_prestamos PRIMARY KEY (id_prestamo);

ALTER TABLE ONLY public.sucursales
    ADD CONSTRAINT pk_sucursales PRIMARY KEY (id_sucursal);


ALTER TABLE ONLY public.tipo_cuentas
    ADD CONSTRAINT pk_tipo_cuentas PRIMARY KEY (cod_tipo_cuenta);


ALTER TABLE ONLY public.tipo_transacciones
    ADD CONSTRAINT pk_tipo_transacciones PRIMARY KEY (cod_tipo_transaccion);


ALTER TABLE ONLY public.transacciones
    ADD CONSTRAINT pk_transacciones PRIMARY KEY (id_transaccion);

ALTER TABLE ONLY public.ciudades
    ADD CONSTRAINT uq_ciudades_nomb UNIQUE (nomb_ciudad);


ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT uq_clientes_correo UNIQUE (correo);

ALTER TABLE ONLY public.formas_transacciones
    ADD CONSTRAINT uq_formas_transacciones_nomb UNIQUE (nomb_forma_transaccion);


ALTER TABLE ONLY public.tipo_cuentas
    ADD CONSTRAINT uq_tipo_cuentas_nomb UNIQUE (nomb_tipo_cuenta);



ALTER TABLE ONLY public.tipo_transacciones
    ADD CONSTRAINT uq_tipo_transacciones_nomb UNIQUE (nomb_tipo_transaccion);


CREATE INDEX idx_cuentas_cliente ON public.cuentas USING btree (id_cliente);


CREATE INDEX idx_transacciones_cuenta ON public.transacciones USING btree (num_cuenta_origen);



CREATE TRIGGER trg_auditar_transaccion AFTER INSERT OR DELETE OR UPDATE ON public.transacciones FOR EACH ROW EXECUTE FUNCTION public.fn_auditar_transaccion();


CREATE TRIGGER trg_validar_transaccion BEFORE INSERT OR UPDATE ON public.transacciones FOR EACH ROW EXECUTE FUNCTION public.fn_validar_transaccion();


ALTER TABLE ONLY public.cajeros_automaticos
    ADD CONSTRAINT fk_cajeros_sucursal FOREIGN KEY (id_sucursal) REFERENCES public.sucursales(id_sucursal);

ALTER TABLE ONLY public.cuentas
    ADD CONSTRAINT fk_cuentas_cliente FOREIGN KEY (id_cliente) REFERENCES public.clientes(id_cliente);

ALTER TABLE ONLY public.cuentas
    ADD CONSTRAINT fk_cuentas_tipo FOREIGN KEY (cod_tipo_cuenta) REFERENCES public.tipo_cuentas(cod_tipo_cuenta);

ALTER TABLE ONLY public.prestamos
    ADD CONSTRAINT fk_prestamos_cliente FOREIGN KEY (id_cliente) REFERENCES public.clientes(id_cliente);

ALTER TABLE ONLY public.sucursales
    ADD CONSTRAINT fk_sucursales_ciudad FOREIGN KEY (id_ciudad) REFERENCES public.ciudades(id_ciudad);


ALTER TABLE ONLY public.transacciones
    ADD CONSTRAINT fk_transacciones_banca FOREIGN KEY (id_banca_virtual) REFERENCES public.banca_virtual(id_banca_virtual);

ALTER TABLE ONLY public.transacciones
    ADD CONSTRAINT fk_transacciones_cajero FOREIGN KEY (id_cajero) REFERENCES public.cajeros_automaticos(id_cajero);

ALTER TABLE ONLY public.transacciones
    ADD CONSTRAINT fk_transacciones_cuenta FOREIGN KEY (num_cuenta_origen) REFERENCES public.cuentas(num_cuenta);

ALTER TABLE ONLY public.transacciones
    ADD CONSTRAINT fk_transacciones_forma FOREIGN KEY (cod_forma_transaccion) REFERENCES public.formas_transacciones(cod_forma_transaccion);


ALTER TABLE ONLY public.transacciones
    ADD CONSTRAINT fk_transacciones_tipo FOREIGN KEY (cod_tipo_transaccion) REFERENCES public.tipo_transacciones(cod_tipo_transaccion);


GRANT USAGE ON SCHEMA public TO grup_cajeros;
GRANT USAGE ON SCHEMA public TO grup_clientes;


GRANT ALL ON FUNCTION public.fn_auditar_transaccion() TO grup_admins;


GRANT ALL ON FUNCTION public.fn_validar_transaccion() TO grup_admins;


GRANT ALL ON FUNCTION public.realizar_transaccion(p_id_cliente character varying, p_num_cuenta character varying, p_cod_tipo integer, p_cod_forma integer, p_monto numeric, p_id_cajero integer, p_id_banca_virtual integer) TO grup_admins;
GRANT ALL ON FUNCTION public.realizar_transaccion(p_id_cliente character varying, p_num_cuenta character varying, p_cod_tipo integer, p_cod_forma integer, p_monto numeric, p_id_cajero integer, p_id_banca_virtual integer) TO grup_cajeros;
GRANT ALL ON FUNCTION public.realizar_transaccion(p_id_cliente character varying, p_num_cuenta character varying, p_cod_tipo integer, p_cod_forma integer, p_monto numeric, p_id_cajero integer, p_id_banca_virtual integer) TO grup_clientes;


GRANT ALL ON TABLE public.banca_virtual TO grup_admins;
GRANT SELECT ON TABLE public.banca_virtual TO grup_cajeros;


GRANT ALL ON SEQUENCE public.banca_virtual_id_banca_virtual_seq TO grup_admins;
GRANT SELECT,USAGE ON SEQUENCE public.banca_virtual_id_banca_virtual_seq TO grup_cajeros;


GRANT ALL ON TABLE public.bitacora_transacciones TO grup_admins;
GRANT SELECT ON TABLE public.bitacora_transacciones TO grup_cajeros;


GRANT ALL ON SEQUENCE public.bitacora_transacciones_id_bitacora_seq TO grup_admins;
GRANT SELECT,USAGE ON SEQUENCE public.bitacora_transacciones_id_bitacora_seq TO grup_cajeros;


GRANT ALL ON TABLE public.cajeros_automaticos TO grup_admins;
GRANT SELECT ON TABLE public.cajeros_automaticos TO grup_cajeros;



GRANT ALL ON TABLE public.ciudades TO grup_admins;
GRANT SELECT ON TABLE public.ciudades TO grup_cajeros;



GRANT ALL ON TABLE public.clientes TO grup_admins;
GRANT SELECT,INSERT,UPDATE ON TABLE public.clientes TO grup_cajeros;
GRANT SELECT ON TABLE public.clientes TO grup_clientes;

GRANT ALL ON TABLE public.cuentas TO grup_admins;
GRANT SELECT,INSERT,UPDATE ON TABLE public.cuentas TO grup_cajeros;
GRANT SELECT ON TABLE public.cuentas TO grup_clientes;


GRANT ALL ON TABLE public.formas_transacciones TO grup_admins;
GRANT SELECT ON TABLE public.formas_transacciones TO grup_cajeros;


GRANT ALL ON TABLE public.prestamos TO grup_admins;
GRANT SELECT,INSERT,UPDATE ON TABLE public.prestamos TO grup_cajeros;


GRANT ALL ON TABLE public.sucursales TO grup_admins;
GRANT SELECT ON TABLE public.sucursales TO grup_cajeros;


GRANT ALL ON TABLE public.tipo_cuentas TO grup_admins;
GRANT SELECT ON TABLE public.tipo_cuentas TO grup_cajeros;
GRANT SELECT ON TABLE public.tipo_cuentas TO grup_clientes;


GRANT ALL ON TABLE public.tipo_transacciones TO grup_admins;
GRANT SELECT ON TABLE public.tipo_transacciones TO grup_cajeros;
GRANT SELECT ON TABLE public.tipo_transacciones TO grup_clientes;


GRANT ALL ON TABLE public.transacciones TO grup_admins;
GRANT SELECT ON TABLE public.transacciones TO grup_cajeros;
GRANT SELECT ON TABLE public.transacciones TO grup_clientes;


GRANT ALL ON SEQUENCE public.transacciones_id_transaccion_seq TO grup_admins;
GRANT SELECT,USAGE ON SEQUENCE public.transacciones_id_transaccion_seq TO grup_cajeros;
GRANT SELECT,USAGE ON SEQUENCE public.transacciones_id_transaccion_seq TO grup_clientes;


\unrestrict ZZBxj25gIK00ZsjVLfHgFcdRqetBUS6xwYE82ZBV8ERruelWBNI3CkU5hZRLoKW


\restrict EweoF6ExLYW8EVT8ZbgctdixqL6420EOCkiIDl3tJ8aQKMn57yyDgIQM9o6PQWM

SET default_transaction_read_only = off;

SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;

-- Roles
--

CREATE ROLE "1001001001";
ALTER ROLE "1001001001" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:nUPyEcdgyC0tcTd5VHuejA==$r9h/s33JzSJIRibvIt7JoDKsmwFW9MqpvtkhWRS5o1c=:pGT58QS5cDjLAGWWI+JiF6m9Yl8NogPv++0Ix1hR9UU=';
CREATE ROLE "1001001002";
ALTER ROLE "1001001002" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:tAMofIKfvTNMvwRFx3sieg==$jmETImcoyWYkGvU7Pjr/BHCzHLO0N2JHSGNbc+dL9DU=:T7FsaaEK9uh8Yd/RkeJ3YTvRJnL3VihWdcmd2pHFZO4=';
CREATE ROLE "1001001003";
ALTER ROLE "1001001003" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:Wo40a6UvcWdTT5PAsXkWiQ==$whEyuAgznP2/ajbsE1tPtnnudqxb6alBG6GggOhVzto=:osGyCXIEfzCfX44l0+TgYVKVL5xDSQTvdlPLYzgSEtM=';
CREATE ROLE "1001001004";
ALTER ROLE "1001001004" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:J+MPvW4UoeeMhoW9VQZFQw==$L2zjFBjVKM/BdBMglRAhseAl46JhcdrQVIsHD7vJ1eI=:btup8uBE9WPVN4X8jLt72BzeUOazY35TA+icCckq34g=';
CREATE ROLE "1001001005";
ALTER ROLE "1001001005" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:UTJ7puZTD6KvOaP6Lqy18w==$spYcDylEJ/kKwyPHYKUCvWW//nBVpVUaGncmJAxHyT8=:iihhs1aYckfltdCHb00/5c5GaLgffFVe5+eigjyy2bg=';
CREATE ROLE "1001001006";
ALTER ROLE "1001001006" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:Lr57LwR9ZEToV8M2ztYcCQ==$Xs/GaHtNVSaJEBcMbygq6xfrfBXljK0fJDk01y0YU24=:xMiYljx61yenGF/Nmyp02c7a01mMlekknFXK1LLIlX8=';
CREATE ROLE "1001001007";
ALTER ROLE "1001001007" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:MEUidm156FuL3CSVRAqgVA==$bfv7eRj34ytYUk5bFbzPqQvYagQwyTjXt41zS0UsgnE=:y77UYdrHd+VTMTU6/N8gnpRJ5Fbpuyr7AHs8OkVtPuI=';
CREATE ROLE "1001001008";
ALTER ROLE "1001001008" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:3vGn5lZBTi1+PwG1c6rPgA==$LmCJUqTOlTDfp4a79AO9kdZ87iyR8STiE9L86hMqGkw=:9Ww8kk7cmiAyJXBaQDZ+w32+unveI4QvXVRXsWUABnc=';
CREATE ROLE "1001001009";
ALTER ROLE "1001001009" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:EZUeY+VxVVFBvqo7zSEuXg==$DRxUoAPAb8y6uUMRQJ2ENsTpwLhoCDSgjpRyzEBP28k=:vbOfKavqdXvyy4A5bv7kRfbQbYVILcTv8Mz1/wTIdYg=';
CREATE ROLE "1001001010";
ALTER ROLE "1001001010" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:NmjyXreYFOj9aQR+hlVKeQ==$vR12zJAE8Fym7xkxJC0Df6CXqDlSjW+8/g7Tt2dtIpY=:jhP2R6WugiVr+u7Nfjto5uXqzShKjI+pliQzot5yVbA=';
CREATE ROLE "1001001011";
ALTER ROLE "1001001011" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:bMlW/6uQkyYPYxd22sSKcA==$bb/CuwoQz+ICFp2RogZ3Cxrk316NWIUdAUp6lEMeZwc=:VLQe1yAmLNQ7k7nFm7b9QN+YftJAR+Z81tmlBjJjv/8=';
CREATE ROLE "1001001012";
ALTER ROLE "1001001012" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:wRacZea8lwfwEPzrplPIYA==$UJsIax4EuCeqr6kLwTrtchcLyyPTTXfByMo2jL2Rkp0=:VzIqS5WIQo9uQBMcw6ZH4sqYlct+VtzxqTCNfn/4xXs=';
CREATE ROLE "1001001013";
ALTER ROLE "1001001013" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:P0SoJDMHzQeSOhK7a38ddA==$dp1bZrVBLvlbf7CYQdZ7QdW5rpIvws4cIv8mIq850fI=:4hBRtPkKK4Pm39z84taVu+Dy+tyHHEUTmrT2UXs83M4=';
CREATE ROLE "1001001014";
ALTER ROLE "1001001014" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:t3ZhkJXegjEUqlKYsd2Tdw==$OMskeqjh0Aa2a/qExpvzeu1EStrt+BuG/4zOlpcLaa0=:sKq4O59gkF3qDUzM1ACbg3J1iolF/NDquDpWxIm4yFY=';
CREATE ROLE "1001001015";
ALTER ROLE "1001001015" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:KYewJ9XWX8RvSXJqJKQO4Q==$k38Q75VakBkG9Z8sApEniZQOyo+7PeJtLYbhNFYpqfw=:OVtBosP7h4UWEruNqjqiOqPjLsbwBLl9CmpTT545pWo=';
CREATE ROLE "1006123456";
ALTER ROLE "1006123456" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:xs6QxjvXy8n74m+oD/X0vw==$ZW9pJ6kU7BSGB1p0nL38cPVw6BMKiNABoBVFNLgzj8o=:ZuyjldqOgKVI/5iv0F8K02Qw6SdPhZb9CrwYUI8yOMw=';
CREATE ROLE "1006987654";
ALTER ROLE "1006987654" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:4mm+O0mFzfSoasKNZbr4yw==$g4QcK7YSfSCG2EIK0TnkFW1kJJXr7s3JgtMZO17Josw=:6C4jBDc9UOtFUoGWJWoa0DXkzvJaprgV6ynTLj6MbTY=';
CREATE ROLE "1121987123";
ALTER ROLE "1121987123" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:TQ0o8aehbSLmKdqsityQ+Q==$jZIwbopRolVktF/wOBldQq/v37ph1Hv8jhnQQd5z7ow=:9nLe49JknGlF+I6DbaePA7ZJcmCmUyoQeca3gUlq8as=';
CREATE ROLE "2001001001";
ALTER ROLE "2001001001" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:mlN+HEsf8n0lWSsV/GQ62g==$MvR5DZNrl6NA2zzf5+Dt7ZJyURawPNchKAGn55lVbZQ=:oWdQDexP5kqzd1LHadOc6edq9qizhkl+dD2M8Zc71SA=';
CREATE ROLE "2001001002";
ALTER ROLE "2001001002" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:iztc1/ZyKwRuAuIssyIrmw==$3E0dfBGdRbJfJ7um19Y1ctrBe7mPgA2dos2aYMKKTYQ=:FQOQEK/AAeWqNOfs/JWC+mS79C7IMzQE28VVewl+pdo=';
CREATE ROLE "2001001003";
ALTER ROLE "2001001003" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:pXoBHkMO3pBTU+P9wKjpmA==$MNNBP73oVHO8LZb00vRpSqQfF2f9mvRRnjoAegE/S/c=:lfYtfLGnwKASbi3WXu5o/us1QpsJENAgupdEtB6f/Eo=';
CREATE ROLE "2001001004";
ALTER ROLE "2001001004" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:s3K5U9Z2ftBjBIBeHcoT6g==$mfh4fhtzxAo+J2cdcTNRIhM/oChqz8EB5P683vQms3A=:qL+jEmrlDi0QXq7+P1NVbF+9kGh0pxyFIGdR+6NITYI=';
CREATE ROLE "2001001005";
ALTER ROLE "2001001005" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:RgeW0ZIIMSnSdl/kvEwWPA==$/kSv4pdAelQbY1/x5g+NNMEXKEIt9BxXlveoJp6zY0w=:cPQAky3tbQOt+75ghR0po7+AUZEwHwc8pNTofcKDYZE=';
CREATE ROLE "2001001006";
ALTER ROLE "2001001006" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:BLH7Q4Z5Hle/ODBHrssDfA==$2LSWVHNq1Vc9ZEBA94/JrpcNUGHw0BVZnokAyK5mMhg=:Ypu4lBsXmfhAYj1/+18OoieY9T50ErdR+jkaAJZ3I9g=';
CREATE ROLE "2001001007";
ALTER ROLE "2001001007" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:FYWUVGLEq7I9CBIe2xLLdw==$H4Z97hEQ3wp8HgO0U+BNZr8gf6PV7V0jq7aetq96kis=:0+dgRgXNhkRAHv2901qdkr7LJL7Wr0I7mpALsk0q/Jc=';
CREATE ROLE "2001001008";
ALTER ROLE "2001001008" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:yzy3FYlEPXytXmld/Wv0Rw==$4FJuk3hDvYXDKx74MzlcjDwCVU/qM8W39ee1ojhGPzw=:kC/iZ7D97lHv2W7CMsJIQHuyl6T0lJjsdBLvljZ2ANU=';
CREATE ROLE "2001001009";
ALTER ROLE "2001001009" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:+WO/ZvqoAlmAd2Jriu6NXQ==$jnHyNz1FSrgYaAYip3eVvAIjbr+8FoG6AxbPX4jYyjk=:CxWVcffLmUcCV1Gg0bkA0LnwpOOUZkt1ebEah+2XeUI=';
CREATE ROLE "2001001010";
ALTER ROLE "2001001010" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:M+nkFE5aErwioLj36llxog==$a6hb7HOj/hK//m1cItMx81rKLxcVkHTFB3LIX0HwOlE=:7lRoSP3Z+QBSzW/6qq+2vv4vNczcQt6P0GOpaP6Gshw=';
CREATE ROLE "40345678";
ALTER ROLE "40345678" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:SrVMfHLQqjWLMF3KsJHN6Q==$rMCIq3q3Ej5FSSLfeozKAhQDwPN5hxv++b+FqzJ3/X8=:AmTSw+HJotXQUSpP+a3pznHiSSOkF0SJlEhPk0SRHhY=';
CREATE ROLE "86012345";
ALTER ROLE "86012345" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:5Rkd9zu5WsjCG5F2DnQCuQ==$Qqfj98+JUi6NdcNZO8aDW165q+Lg1gaaKcictEXcCrw=:mJHjXer/ZaWhyrrHVzXxZurpvCOtzTccAuRo2MfGb+M=';
CREATE ROLE "9009009001";
ALTER ROLE "9009009001" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:frqCIyLJYEskRtRGof+LJg==$bvm2joYs3MQMO8l9YCjCqYktoenG7Yz4kXgVnvRy3gA=:lw/P497mVv6q3hL7jJ1uOkjPM8nS1HZDS/rufqIZkes=';
CREATE ROLE "9009009002";
ALTER ROLE "9009009002" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:ta+GgYN/F77sTT+KUSr7dA==$3vvilFdaGJrQlmwRna99k25eKkQqv4HHOqzdz0lj6jI=:MFODtjsRfQrgUPoG4TBi1Cejj6WvKrbiosy4t1Z1ays=';
CREATE ROLE "9009009003";
ALTER ROLE "9009009003" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:RAMmUPR6ZoGd54Yds1ZKfg==$CsVxFuxQ7w1Gryfxm3uG+W6gwou0KuUdspCkzKLsjr4=:xGnREYrsK/DkMsewX9bsD/x86xQIRH3BfsPzB3ayDaw=';
CREATE ROLE "9009009004";
ALTER ROLE "9009009004" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:KF3qKr2+yPpYDvOIwTPSiA==$z8x04/9O7/XvqOHdiBx4Ou7EHaR0n+7XeqlntHTJOFk=:4Ss3jVf9NPYWsk2ZbQJuMwHYL3zt/IAX5f5xifX3fjA=';
CREATE ROLE "9009009005";
ALTER ROLE "9009009005" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:n1bukfnafHYp9W6MUcla5w==$zzMJ2o/PFOhRfbdtcW+HWYjmmesYEuznsQE6QzK//jY=:vN+h4jREYOlp19qDnTR6Z7/4MIh3Z//Xp2p4w9O+TJs=';
CREATE ROLE "9009009006";
ALTER ROLE "9009009006" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:EwZLpOmoF79b4NQ4ETvCeA==$7YeNDNX/ljLX2mmW9U43uiRChcVwCAxIjA4jkCccYoc=:H8gdGyQ39OlJd98SkisS5dW1r0GFGm2steqy3I0Clys=';
CREATE ROLE "9009009007";
ALTER ROLE "9009009007" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:rqwEzLPaRUyzITBJCJ9I0A==$yhiZ9eRw1/KS/Qvx/zJHSgenANw/Mn7xp/+FhSg7sss=:5TxaSQ6A6ozFWy+pQ2YPY4dErN9r+eunnoDmxrGVpmk=';
CREATE ROLE "9009009008";
ALTER ROLE "9009009008" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:HkjufK2K8dXc31ye9Brr9A==$PwcLmpU2Q+iFnXF05CJsZa8OZI1H278LLIpPTM2KzSg=:82CWEjDvKgJ+tjCCFyUkKHUIO1roOaA6CZVPLn10tiY=';
CREATE ROLE "9009009009";
ALTER ROLE "9009009009" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:rMXUyBOpTRF/8/9oEem5Rw==$wAZ96aKm5SR+6Ogs8y10GNUbjzHJGM2CKtuU1rgZIBw=:aLCSihkU2mPO4ri7VvbYEuo3seDR7U2SBOB0R2iFPjc=';
CREATE ROLE "9009009010";
ALTER ROLE "9009009010" WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:VaxUEHNX/56abUAE5bIOKg==$uSJVuNo+mM4ElfRQuB/O67EbxGubOMVjWsRFrDfYD1k=:+zUqOo2e/No2rDVeWK9hZz1BlaCrdT+UM5QaLQx/PXQ=';
CREATE ROLE administrador;
ALTER ROLE administrador WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:d8Bv3vOTCnAc4q3L0zLFBA==$VGQ0LvT/EdfZrhQg6kssQz/0aUoZ+bE6sgit8qzD7ok=:JVzLvIL07u8O9lbB89HWStHOll+QI4Fnjf6Cg9lXsaA=';
CREATE ROLE cajero;
ALTER ROLE cajero WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:MnB1Lp20O18hoBg70ECuYg==$lQvof1W/MdA5myC9drqkyCCVmPT2YJU0g6CKFwbifYU=:BfzurdWGCc+0cohTpiE2/YrkNnx2KHhw74trpts6jOM=';
CREATE ROLE cliente;
ALTER ROLE cliente WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:0BW0wrrZ22xnOCE4DpjQmw==$2vevZr6mOXVDKUVwL2Dhep6ZWrVI+RzF4S0Dys5QHQ8=:ecvYEPilAeILxbCn8hwVELcKR9ssdVeaIYvkBcriaSw=';
CREATE ROLE grup_admins;
ALTER ROLE grup_admins WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB NOLOGIN NOREPLICATION NOBYPASSRLS;
CREATE ROLE grup_cajeros;
ALTER ROLE grup_cajeros WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB NOLOGIN NOREPLICATION NOBYPASSRLS;
CREATE ROLE grup_clientes;
ALTER ROLE grup_clientes WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB NOLOGIN NOREPLICATION NOBYPASSRLS;
CREATE ROLE postgres;
ALTER ROLE postgres WITH SUPERUSER INHERIT CREATEROLE CREATEDB LOGIN REPLICATION BYPASSRLS PASSWORD 'SCRAM-SHA-256$4096:9bND9UvOFmCrBCx8Tkm1mg==$KwDNJefTet53eaE4QJurmb+f86XT40aeR820T+jqgBo=:v8QrWiEcErQ+4jXarHkx37qmktBX/81WHzDq3aToJVo=';

--ROles de usuuarios

GRANT grup_admins TO "9009009001" GRANTED BY postgres;
GRANT grup_admins TO "9009009002" GRANTED BY postgres;
GRANT grup_admins TO "9009009003" GRANTED BY postgres;
GRANT grup_admins TO "9009009004" GRANTED BY postgres;
GRANT grup_admins TO "9009009005" GRANTED BY postgres;
GRANT grup_admins TO "9009009006" GRANTED BY postgres;
GRANT grup_admins TO "9009009007" GRANTED BY postgres;
GRANT grup_admins TO "9009009008" GRANTED BY postgres;
GRANT grup_admins TO "9009009009" GRANTED BY postgres;
GRANT grup_admins TO "9009009010" GRANTED BY postgres;
GRANT grup_admins TO administrador GRANTED BY postgres;
GRANT grup_cajeros TO "2001001001" GRANTED BY postgres;
GRANT grup_cajeros TO "2001001002" GRANTED BY postgres;
GRANT grup_cajeros TO "2001001003" GRANTED BY postgres;
GRANT grup_cajeros TO "2001001004" GRANTED BY postgres;
GRANT grup_cajeros TO "2001001005" GRANTED BY postgres;
GRANT grup_cajeros TO "2001001006" GRANTED BY postgres;
GRANT grup_cajeros TO "2001001007" GRANTED BY postgres;
GRANT grup_cajeros TO "2001001008" GRANTED BY postgres;
GRANT grup_cajeros TO "2001001009" GRANTED BY postgres;
GRANT grup_cajeros TO "2001001010" GRANTED BY postgres;
GRANT grup_cajeros TO cajero GRANTED BY postgres;
GRANT grup_clientes TO "1001001001" GRANTED BY postgres;
GRANT grup_clientes TO "1001001002" GRANTED BY postgres;
GRANT grup_clientes TO "1001001003" GRANTED BY postgres;
GRANT grup_clientes TO "1001001004" GRANTED BY postgres;
GRANT grup_clientes TO "1001001005" GRANTED BY postgres;
GRANT grup_clientes TO "1001001006" GRANTED BY postgres;
GRANT grup_clientes TO "1001001007" GRANTED BY postgres;
GRANT grup_clientes TO "1001001008" GRANTED BY postgres;
GRANT grup_clientes TO "1001001009" GRANTED BY postgres;
GRANT grup_clientes TO "1001001010" GRANTED BY postgres;
GRANT grup_clientes TO "1001001011" GRANTED BY postgres;
GRANT grup_clientes TO "1001001012" GRANTED BY postgres;
GRANT grup_clientes TO "1001001013" GRANTED BY postgres;
GRANT grup_clientes TO "1001001014" GRANTED BY postgres;
GRANT grup_clientes TO "1001001015" GRANTED BY postgres;
GRANT grup_clientes TO "1006123456" GRANTED BY postgres;
GRANT grup_clientes TO "1006987654" GRANTED BY postgres;
GRANT grup_clientes TO "1121987123" GRANTED BY postgres;
GRANT grup_clientes TO "40345678" GRANTED BY postgres;
GRANT grup_clientes TO "86012345" GRANTED BY postgres;
GRANT grup_clientes TO cliente GRANTED BY postgres;


\unrestrict EweoF6ExLYW8EVT8ZbgctdixqL6420EOCkiIDl3tJ8aQKMn57yyDgIQM9o6PQWM

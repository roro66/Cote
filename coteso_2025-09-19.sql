--
-- PostgreSQL database dump
--

\restrict ywi7cGekjKhUkUgJS0LpNEJCArE7kcXmsf7ylrQFbU8C3spUkYeO8xplzOzHus0

-- Dumped from database version 17.6
-- Dumped by pg_dump version 17.6

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
-- Name: validate_transaction_accounts(); Type: FUNCTION; Schema: public; Owner: admin
--

CREATE FUNCTION public.validate_transaction_accounts() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    from_type TEXT;
    to_type TEXT;
BEGIN
    IF NEW.from_account_id IS NULL OR NEW.to_account_id IS NULL THEN
        RAISE EXCEPTION 'from_account_id y to_account_id no pueden ser nulos';
    END IF;

    SELECT type INTO from_type FROM accounts WHERE id = NEW.from_account_id;
    SELECT type INTO to_type FROM accounts WHERE id = NEW.to_account_id;

    IF from_type IS NULL OR to_type IS NULL THEN
        RAISE EXCEPTION 'Cuentas inválidas para la transacción';
    END IF;

    IF NOT ((from_type = 'treasury' AND to_type = 'person') OR (from_type = 'person' AND to_type = 'treasury')) THEN
        RAISE EXCEPTION 'Solo se permiten transferencias entre Tesorería y cuentas personales';
    END IF;

    RETURN NEW;
END;
$$;


ALTER FUNCTION public.validate_transaction_accounts() OWNER TO admin;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: account_types; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.account_types (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.account_types OWNER TO admin;

--
-- Name: account_types_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.account_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.account_types_id_seq OWNER TO admin;

--
-- Name: account_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.account_types_id_seq OWNED BY public.account_types.id;


--
-- Name: accounts; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.accounts (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    person_id bigint,
    balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    notes text,
    is_enabled boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_fondeo boolean DEFAULT false NOT NULL,
    is_protected boolean DEFAULT false NOT NULL,
    CONSTRAINT accounts_treasury_person_null CHECK ((NOT (((type)::text = 'treasury'::text) AND (person_id IS NOT NULL)))),
    CONSTRAINT accounts_type_check CHECK (((type)::text = ANY ((ARRAY['treasury'::character varying, 'person'::character varying])::text[])))
);


ALTER TABLE public.accounts OWNER TO admin;

--
-- Name: accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.accounts_id_seq OWNER TO admin;

--
-- Name: accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.accounts_id_seq OWNED BY public.accounts.id;


--
-- Name: activity_log; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.activity_log (
    id bigint NOT NULL,
    log_name character varying(255),
    description text NOT NULL,
    subject_type character varying(255),
    subject_id bigint,
    causer_type character varying(255),
    causer_id bigint,
    properties json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    event character varying(255),
    batch_uuid uuid
);


ALTER TABLE public.activity_log OWNER TO admin;

--
-- Name: activity_log_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.activity_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.activity_log_id_seq OWNER TO admin;

--
-- Name: activity_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.activity_log_id_seq OWNED BY public.activity_log.id;


--
-- Name: banks; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.banks (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT banks_type_check CHECK (((type)::text = ANY ((ARRAY['banco'::character varying, 'tarjeta_prepago'::character varying, 'cooperativa'::character varying])::text[])))
);


ALTER TABLE public.banks OWNER TO admin;

--
-- Name: banks_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.banks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.banks_id_seq OWNER TO admin;

--
-- Name: banks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.banks_id_seq OWNED BY public.banks.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO admin;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO admin;

--
-- Name: documents; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.documents (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    file_path character varying(255) NOT NULL,
    mime_type character varying(255) NOT NULL,
    file_size integer NOT NULL,
    document_type character varying(255) NOT NULL,
    expense_item_id bigint,
    uploaded_by bigint NOT NULL,
    is_enabled boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT documents_document_type_check CHECK (((document_type)::text = ANY ((ARRAY['boleta'::character varying, 'factura'::character varying, 'guia_despacho'::character varying, 'ticket'::character varying, 'vale'::character varying, 'other'::character varying])::text[])))
);


ALTER TABLE public.documents OWNER TO admin;

--
-- Name: documents_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.documents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.documents_id_seq OWNER TO admin;

--
-- Name: documents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.documents_id_seq OWNED BY public.documents.id;


--
-- Name: expense_categories; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.expense_categories (
    id bigint NOT NULL,
    code character varying(255),
    name character varying(255) NOT NULL,
    description text,
    is_enabled boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.expense_categories OWNER TO admin;

--
-- Name: expense_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.expense_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.expense_categories_id_seq OWNER TO admin;

--
-- Name: expense_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.expense_categories_id_seq OWNED BY public.expense_categories.id;


--
-- Name: expense_items; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.expense_items (
    id bigint NOT NULL,
    expense_id bigint NOT NULL,
    document_type character varying(255) NOT NULL,
    document_number character varying(255),
    vendor_name character varying(255) NOT NULL,
    description text NOT NULL,
    amount numeric(15,2) NOT NULL,
    expense_date date NOT NULL,
    category character varying(255),
    is_enabled boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    expense_category_id bigint,
    CONSTRAINT expense_items_document_type_check CHECK (((document_type)::text = ANY ((ARRAY['boleta'::character varying, 'factura'::character varying, 'guia_despacho'::character varying, 'ticket'::character varying, 'vale'::character varying])::text[])))
);


ALTER TABLE public.expense_items OWNER TO admin;

--
-- Name: expense_items_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.expense_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.expense_items_id_seq OWNER TO admin;

--
-- Name: expense_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.expense_items_id_seq OWNED BY public.expense_items.id;


--
-- Name: expenses; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.expenses (
    id bigint NOT NULL,
    expense_number character varying(255) NOT NULL,
    account_id bigint NOT NULL,
    submitted_by bigint NOT NULL,
    total_amount numeric(15,2) NOT NULL,
    description text NOT NULL,
    expense_date date NOT NULL,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    reviewed_by bigint,
    submitted_at timestamp(0) without time zone,
    reviewed_at timestamp(0) without time zone,
    rejection_reason text,
    is_enabled boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT expenses_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'submitted'::character varying, 'reviewed'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


ALTER TABLE public.expenses OWNER TO admin;

--
-- Name: expenses_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.expenses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.expenses_id_seq OWNER TO admin;

--
-- Name: expenses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.expenses_id_seq OWNED BY public.expenses.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO admin;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO admin;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO admin;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO admin;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO admin;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: media; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.media (
    id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL,
    uuid uuid,
    collection_name character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    file_name character varying(255) NOT NULL,
    mime_type character varying(255),
    disk character varying(255) NOT NULL,
    conversions_disk character varying(255),
    size bigint NOT NULL,
    manipulations json NOT NULL,
    custom_properties json NOT NULL,
    generated_conversions json NOT NULL,
    responsive_images json NOT NULL,
    order_column integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.media OWNER TO admin;

--
-- Name: media_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.media_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.media_id_seq OWNER TO admin;

--
-- Name: media_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.media_id_seq OWNED BY public.media.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO admin;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO admin;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: model_has_permissions; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.model_has_permissions (
    permission_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


ALTER TABLE public.model_has_permissions OWNER TO admin;

--
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.model_has_roles (
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


ALTER TABLE public.model_has_roles OWNER TO admin;

--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO admin;

--
-- Name: people; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.people (
    id bigint NOT NULL,
    first_name character varying(255) NOT NULL,
    last_name character varying(255) NOT NULL,
    rut character varying(255) NOT NULL,
    email character varying(255),
    phone character varying(255),
    account_number character varying(255),
    address text,
    role_type character varying(50) NOT NULL,
    is_enabled boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    bank_id bigint,
    account_type_id bigint,
    is_protected boolean DEFAULT false NOT NULL,
    CONSTRAINT people_role_type_check CHECK (((role_type)::text = ANY ((ARRAY['tesorero'::character varying, 'trabajador'::character varying])::text[])))
);


ALTER TABLE public.people OWNER TO admin;

--
-- Name: people_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.people_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.people_id_seq OWNER TO admin;

--
-- Name: people_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.people_id_seq OWNED BY public.people.id;


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.permissions OWNER TO admin;

--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.permissions_id_seq OWNER TO admin;

--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: person_bank_accounts; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.person_bank_accounts (
    id bigint NOT NULL,
    person_id bigint NOT NULL,
    bank_id bigint,
    account_type_id bigint,
    account_number character varying(255),
    alias character varying(255),
    is_default boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.person_bank_accounts OWNER TO admin;

--
-- Name: person_bank_accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.person_bank_accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.person_bank_accounts_id_seq OWNER TO admin;

--
-- Name: person_bank_accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.person_bank_accounts_id_seq OWNED BY public.person_bank_accounts.id;


--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.role_has_permissions (
    permission_id bigint NOT NULL,
    role_id bigint NOT NULL
);


ALTER TABLE public.role_has_permissions OWNER TO admin;

--
-- Name: roles; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.roles OWNER TO admin;

--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_seq OWNER TO admin;

--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO admin;

--
-- Name: transactions; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.transactions (
    id bigint NOT NULL,
    transaction_number character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    from_account_id bigint NOT NULL,
    to_account_id bigint NOT NULL,
    amount numeric(15,2) NOT NULL,
    description text NOT NULL,
    notes text,
    created_by bigint NOT NULL,
    approved_by bigint,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    approved_at timestamp(0) without time zone,
    is_enabled boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_transactions_type CHECK (((type)::text = 'transfer'::text)),
    CONSTRAINT transactions_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying, 'completed'::character varying])::text[]))),
    CONSTRAINT transactions_type_check CHECK (((type)::text = ANY ((ARRAY['transfer'::character varying, 'payment'::character varying, 'adjustment'::character varying])::text[])))
);


ALTER TABLE public.transactions OWNER TO admin;

--
-- Name: transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.transactions_id_seq OWNER TO admin;

--
-- Name: transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.transactions_id_seq OWNED BY public.transactions.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: admin
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_enabled boolean DEFAULT true NOT NULL,
    person_id bigint
);


ALTER TABLE public.users OWNER TO admin;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: admin
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO admin;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: admin
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: account_types id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.account_types ALTER COLUMN id SET DEFAULT nextval('public.account_types_id_seq'::regclass);


--
-- Name: accounts id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.accounts ALTER COLUMN id SET DEFAULT nextval('public.accounts_id_seq'::regclass);


--
-- Name: activity_log id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.activity_log ALTER COLUMN id SET DEFAULT nextval('public.activity_log_id_seq'::regclass);


--
-- Name: banks id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.banks ALTER COLUMN id SET DEFAULT nextval('public.banks_id_seq'::regclass);


--
-- Name: documents id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.documents ALTER COLUMN id SET DEFAULT nextval('public.documents_id_seq'::regclass);


--
-- Name: expense_categories id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.expense_categories ALTER COLUMN id SET DEFAULT nextval('public.expense_categories_id_seq'::regclass);


--
-- Name: expense_items id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.expense_items ALTER COLUMN id SET DEFAULT nextval('public.expense_items_id_seq'::regclass);


--
-- Name: expenses id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.expenses ALTER COLUMN id SET DEFAULT nextval('public.expenses_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: media id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.media ALTER COLUMN id SET DEFAULT nextval('public.media_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: people id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.people ALTER COLUMN id SET DEFAULT nextval('public.people_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: person_bank_accounts id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.person_bank_accounts ALTER COLUMN id SET DEFAULT nextval('public.person_bank_accounts_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: transactions id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.transactions ALTER COLUMN id SET DEFAULT nextval('public.transactions_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: account_types; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.account_types (id, name, description, is_active, created_at, updated_at) FROM stdin;
1	Cuenta Corriente	Cuenta bancaria que permite realizar múltiples operaciones como depósitos, giros y transferencias sin límite	t	2025-09-16 12:06:28	2025-09-16 12:06:28
2	Cuenta Vista	Cuenta de depósito simple que permite ahorrar dinero con acceso inmediato a los fondos	t	2025-09-16 12:06:28	2025-09-16 12:06:28
3	Cuenta de Ahorro	Cuenta diseñada para el ahorro con posibles beneficios en tasas de interés	t	2025-09-16 12:06:28	2025-09-16 12:06:28
4	Cuenta RUT	Cuenta básica gratuita asociada al RUT, ideal para recibir sueldos y realizar operaciones básicas	t	2025-09-16 12:06:28	2025-09-16 12:06:28
5	Chequera Electrónica	Cuenta corriente que opera completamente de forma digital, sin chequeras físicas	t	2025-09-16 12:06:28	2025-09-16 12:06:28
\.


--
-- Data for Name: accounts; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.accounts (id, name, type, person_id, balance, notes, is_enabled, created_at, updated_at, is_fondeo, is_protected) FROM stdin;
4	María García	person	2	0.00	\N	t	2025-09-16 12:06:28	2025-09-16 12:06:28	f	f
5	Luis Rodríguez	person	3	0.00	\N	t	2025-09-16 12:06:28	2025-09-16 12:06:28	f	f
6	Ana López	person	4	0.00	\N	t	2025-09-16 12:06:28	2025-09-16 12:06:28	f	f
8	Jamarcus Torp Quitzon	person	6	0.00	\N	t	2025-09-16 12:06:28	2025-09-16 12:06:28	f	f
12	Marge Roob Wehner	person	10	0.00	\N	t	2025-09-16 12:06:28	2025-09-16 12:06:28	f	f
14	Lurline Larson Dach	person	12	0.00	\N	t	2025-09-16 12:06:28	2025-09-16 12:06:28	f	f
15	Ibrahim O'Conner Casper	person	13	0.00	\N	t	2025-09-16 12:06:28	2025-09-16 12:06:28	f	f
16	Amos Lakin Crona	person	14	0.00	\N	t	2025-09-16 12:06:28	2025-09-16 12:06:28	f	f
21	Julianne Schmeler Volkman	person	19	0.00	\N	t	2025-09-16 12:06:28	2025-09-16 12:06:28	f	f
23	Katlynn Grimes Hilpert	person	21	0.00	\N	t	2025-09-16 12:06:28	2025-09-16 12:06:28	f	f
25	Otha McLaughlin Cruickshank	person	23	0.00	\N	t	2025-09-16 12:06:28	2025-09-16 12:06:28	f	f
28	Madonna Dooley Paucek	person	26	0.00	\N	t	2025-09-16 12:06:28	2025-09-16 12:06:28	f	f
29	Abagail Ebert Ortiz	person	27	0.00	\N	t	2025-09-16 12:06:28	2025-09-16 12:06:28	f	f
31	Destiny Farrell Turner	person	29	0.00	\N	t	2025-09-16 12:06:28	2025-09-16 12:06:28	f	f
32	Chloe Weimann McLaughlin	person	30	0.00	\N	t	2025-09-16 12:06:28	2025-09-16 12:06:28	f	f
1	Tesorería	treasury	\N	299615000.00	Cuenta central de tesorería	t	2025-09-16 12:06:27	2025-09-16 15:26:38	f	t
3	Carlos Mendoza	person	1	198544.00	\N	t	2025-09-16 12:06:28	2025-09-17 11:47:42	f	f
30	Mireya Terry Kihn	person	28	-221849.00	\N	t	2025-09-16 12:06:28	2025-09-16 15:15:48	f	f
24	Charles Swaniawski Williamson	person	22	-18330.00	\N	t	2025-09-16 12:06:28	2025-09-16 15:15:53	f	f
19	Cesar Marks Denesik	person	17	-31626.00	\N	t	2025-09-16 12:06:28	2025-09-16 15:15:59	f	f
11	Vinnie Schamberger Reichert	person	9	-214182.00	\N	t	2025-09-16 12:06:28	2025-09-16 15:16:09	f	f
10	Raegan Auer O'Hara	person	8	-75930.00	\N	t	2025-09-16 12:06:28	2025-09-16 15:16:24	f	f
18	Yoshiko Wiegand McClure	person	16	-342683.00	\N	t	2025-09-16 12:06:28	2025-09-16 15:16:27	f	f
22	Claude Simonis Marvin	person	20	-183248.00	\N	t	2025-09-16 12:06:28	2025-09-16 15:16:30	f	f
17	Hank Bradtke Stehr	person	15	-259034.00	\N	t	2025-09-16 12:06:28	2025-09-16 15:16:34	f	f
33	Tesorero Sistema	person	31	-380640.00	\N	t	2025-09-16 14:54:24	2025-09-16 15:16:44	f	f
20	Estella Harvey Witting	person	18	-465798.00	\N	t	2025-09-16 12:06:28	2025-09-16 15:16:46	f	f
26	Frederick Simonis Abernathy	person	24	-206960.00	\N	t	2025-09-16 12:06:28	2025-09-16 15:16:51	f	f
7	Pedro Sánchez	person	5	-208134.00	\N	t	2025-09-16 12:06:28	2025-09-16 15:16:53	f	f
9	Mina Langosh Legros	person	7	-11507.00	\N	t	2025-09-16 12:06:28	2025-09-16 15:16:55	f	f
27	Alexa Christiansen Wiegand	person	25	-126773.00	\N	t	2025-09-16 12:06:28	2025-09-16 15:16:57	f	f
13	Mallory Moore White	person	11	-453958.00	\N	t	2025-09-16 12:06:28	2025-09-16 15:16:59	f	f
2	Fondeo del Sistema	person	31	9999699999999.00	Cuenta institucional para fondear Tesorería en entornos de prueba	t	2025-09-16 12:06:27	2025-09-16 15:26:27	f	t
\.


--
-- Data for Name: activity_log; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.activity_log (id, log_name, description, subject_type, subject_id, causer_type, causer_id, properties, created_at, updated_at, event, batch_uuid) FROM stdin;
1	default	created	App\\Models\\User	1	\N	\N	{"attributes":{"name":"Administrador","email":"admin@coteso.com","is_enabled":true}}	2025-09-16 12:06:27	2025-09-16 12:06:27	created	\N
2	default	created	App\\Models\\User	2	\N	\N	{"attributes":{"name":"Tesorero Principal","email":"tesorero@coteso.com","is_enabled":true}}	2025-09-16 12:06:27	2025-09-16 12:06:27	created	\N
3	default	created	App\\Models\\Account	1	\N	\N	{"attributes":{"id":1,"name":"Tesorer\\u00eda","type":"treasury","person_id":null,"balance":"0.00","notes":"Cuenta central de tesorer\\u00eda","is_enabled":true,"created_at":"2025-09-16T12:06:27.000000Z","updated_at":"2025-09-16T12:06:27.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:27	2025-09-16 12:06:27	created	\N
4	default	created	App\\Models\\Account	2	\N	\N	{"attributes":{"id":2,"name":"Fondeo del Sistema","type":"person","person_id":null,"balance":"100000000.00","notes":"Cuenta institucional para fondear Tesorer\\u00eda en entornos de prueba","is_enabled":true,"created_at":"2025-09-16T12:06:27.000000Z","updated_at":"2025-09-16T12:06:27.000000Z","is_fondeo":true,"is_protected":false}}	2025-09-16 12:06:27	2025-09-16 12:06:27	created	\N
5	default	created	App\\Models\\Person	1	\N	\N	{"attributes":{"id":1,"first_name":"Carlos","last_name":"Mendoza","rut":"12345678-5","email":"carlos.mendoza@coteso.com","phone":"987654321","account_number":"123456789","address":null,"role_type":"tesorero","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":1,"account_type_id":1,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
6	default	created	App\\Models\\Person	2	\N	\N	{"attributes":{"id":2,"first_name":"Mar\\u00eda","last_name":"Garc\\u00eda","rut":"23456789-6","email":"maria.garcia@coteso.com","phone":"987654322","account_number":"987654321","address":null,"role_type":"trabajador","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":30,"account_type_id":4,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
7	default	created	App\\Models\\Person	3	\N	\N	{"attributes":{"id":3,"first_name":"Luis","last_name":"Rodr\\u00edguez","rut":"34567890-7","email":"luis.rodriguez@coteso.com","phone":"987654323","account_number":"555666777","address":null,"role_type":"trabajador","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":7,"account_type_id":2,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
8	default	created	App\\Models\\Person	4	\N	\N	{"attributes":{"id":4,"first_name":"Ana","last_name":"L\\u00f3pez","rut":"45678901-8","email":"ana.lopez@coteso.com","phone":"987654324","account_number":"111222333","address":null,"role_type":"tesorero","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":1,"account_type_id":1,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
9	default	created	App\\Models\\Person	5	\N	\N	{"attributes":{"id":5,"first_name":"Pedro","last_name":"S\\u00e1nchez","rut":"56789012-9","email":"pedro.sanchez@coteso.com","phone":"987654325","account_number":"444555666","address":null,"role_type":"trabajador","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":30,"account_type_id":4,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
10	default	created	App\\Models\\Person	6	\N	\N	{"attributes":{"id":6,"first_name":"Jamarcus","last_name":"Torp Quitzon","rut":"22894821-7","email":"candice46@example.net","phone":"206.636.5605","account_number":null,"address":"42984 Klocko Via Apt. 152\\nWiegandhaven, IL 53876-7840","role_type":"trabajador","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
11	default	created	App\\Models\\Person	7	\N	\N	{"attributes":{"id":7,"first_name":"Mina","last_name":"Langosh Legros","rut":"6603425-9","email":"stokes.golda@example.org","phone":null,"account_number":null,"address":"71550 Ruthie Mountain\\nNew Reina, OK 58151-4920","role_type":"tesorero","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
12	default	created	App\\Models\\Person	8	\N	\N	{"attributes":{"id":8,"first_name":"Raegan","last_name":"Auer O'Hara","rut":"9977891-1","email":"kcollier@example.net","phone":"(626) 656-7898","account_number":"7877501679","address":"715 Mollie Station Suite 995\\nDonniebury, MO 53862","role_type":"trabajador","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
13	default	created	App\\Models\\Person	9	\N	\N	{"attributes":{"id":9,"first_name":"Vinnie","last_name":"Schamberger Reichert","rut":"20913661-9","email":"nicola63@example.org","phone":"628-233-9657","account_number":"5160182963","address":"1876 Breitenberg Ford Apt. 152\\nNorth Michaelshire, AR 55921-0765","role_type":"trabajador","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
14	default	created	App\\Models\\Person	10	\N	\N	{"attributes":{"id":10,"first_name":"Marge","last_name":"Roob Wehner","rut":"7850853-1","email":"kilback.thad@example.com","phone":null,"account_number":"2182238183","address":null,"role_type":"tesorero","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
15	default	created	App\\Models\\Person	11	\N	\N	{"attributes":{"id":11,"first_name":"Mallory","last_name":"Moore White","rut":"19950429-0","email":"fern72@example.com","phone":null,"account_number":"6514533727","address":null,"role_type":"tesorero","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
16	default	created	App\\Models\\Person	12	\N	\N	{"attributes":{"id":12,"first_name":"Lurline","last_name":"Larson Dach","rut":"21808848-1","email":"makenzie.windler@example.net","phone":"+1-650-999-5164","account_number":null,"address":null,"role_type":"tesorero","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
17	default	created	App\\Models\\Person	13	\N	\N	{"attributes":{"id":13,"first_name":"Ibrahim","last_name":"O'Conner Casper","rut":"4319428-3","email":"brekke.pablo@example.org","phone":"520.647.7231","account_number":"4036688494","address":"668 Dare Ridge\\nEast Paris, NC 88398","role_type":"tesorero","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
184	default	updated	App\\Models\\Expense	23	\N	\N	{"attributes":{"total_amount":"213079.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
18	default	created	App\\Models\\Person	14	\N	\N	{"attributes":{"id":14,"first_name":"Amos","last_name":"Lakin Crona","rut":"1886590-4","email":"emmerich.tomasa@example.net","phone":"+1.716.427.0709","account_number":"1104019812","address":"236 Jovany Curve\\nMcLaughlinfurt, DC 79886-0301","role_type":"tesorero","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
19	default	created	App\\Models\\Person	15	\N	\N	{"attributes":{"id":15,"first_name":"Hank","last_name":"Bradtke Stehr","rut":"16617387-6","email":"connelly.omer@example.org","phone":null,"account_number":"5843582539","address":null,"role_type":"trabajador","is_enabled":false,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
20	default	created	App\\Models\\Person	16	\N	\N	{"attributes":{"id":16,"first_name":"Yoshiko","last_name":"Wiegand McClure","rut":"13735076-9","email":"dicki.orion@example.com","phone":null,"account_number":null,"address":"29147 Jaskolski Throughway Apt. 640\\nSchimmelshire, CA 11876","role_type":"trabajador","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
21	default	created	App\\Models\\Person	17	\N	\N	{"attributes":{"id":17,"first_name":"Cesar","last_name":"Marks Denesik","rut":"14236378-K","email":"berenice40@example.net","phone":"+1-848-939-8971","account_number":"3944707123","address":"843 Deborah Greens\\nSouth Reannaside, WI 09549","role_type":"tesorero","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
22	default	created	App\\Models\\Person	18	\N	\N	{"attributes":{"id":18,"first_name":"Estella","last_name":"Harvey Witting","rut":"5661188-6","email":"jkirlin@example.org","phone":"1-228-669-6079","account_number":"3126883391","address":"6211 Lowe Fields Apt. 691\\nWehnermouth, MS 02078","role_type":"trabajador","is_enabled":false,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
23	default	created	App\\Models\\Person	19	\N	\N	{"attributes":{"id":19,"first_name":"Julianne","last_name":"Schmeler Volkman","rut":"2187860-K","email":"johnston.janice@example.net","phone":"(669) 524-4079","account_number":"8096280366","address":"60742 Welch Field Apt. 471\\nWest Jedediahmouth, TX 34472-2862","role_type":"tesorero","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
24	default	created	App\\Models\\Person	20	\N	\N	{"attributes":{"id":20,"first_name":"Claude","last_name":"Simonis Marvin","rut":"11227362-K","email":"juanita97@example.com","phone":"+1-475-409-9803","account_number":"1498854978","address":"82547 Fanny Brook Suite 869\\nLacyfurt, OR 74712-8926","role_type":"tesorero","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
25	default	created	App\\Models\\Person	21	\N	\N	{"attributes":{"id":21,"first_name":"Katlynn","last_name":"Grimes Hilpert","rut":"8294464-8","email":"elbert44@example.net","phone":"(541) 369-3205","account_number":null,"address":null,"role_type":"trabajador","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
26	default	created	App\\Models\\Person	22	\N	\N	{"attributes":{"id":22,"first_name":"Charles","last_name":"Swaniawski Williamson","rut":"19179066-9","email":"zbreitenberg@example.org","phone":"+1 (986) 971-3620","account_number":null,"address":"3011 Walker Skyway Apt. 773\\nPort Burdettestad, MD 01832-0070","role_type":"trabajador","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
27	default	created	App\\Models\\Person	23	\N	\N	{"attributes":{"id":23,"first_name":"Otha","last_name":"McLaughlin Cruickshank","rut":"24472587-2","email":"macey.kub@example.org","phone":"830.223.1815","account_number":"4340173747","address":null,"role_type":"tesorero","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
28	default	created	App\\Models\\Person	24	\N	\N	{"attributes":{"id":24,"first_name":"Frederick","last_name":"Simonis Abernathy","rut":"23946289-8","email":"stiedemann.jerel@example.org","phone":"828.596.1430","account_number":null,"address":"8708 Dach Rue Suite 143\\nMurphyside, IN 77349-4143","role_type":"tesorero","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
29	default	created	App\\Models\\Person	25	\N	\N	{"attributes":{"id":25,"first_name":"Alexa","last_name":"Christiansen Wiegand","rut":"3922628-6","email":"ahmed73@example.org","phone":"956-636-9466","account_number":"3256564170","address":"68767 Desmond Land\\nJacobiborough, MI 66157-7363","role_type":"tesorero","is_enabled":false,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
30	default	created	App\\Models\\Person	26	\N	\N	{"attributes":{"id":26,"first_name":"Madonna","last_name":"Dooley Paucek","rut":"18409333-2","email":"alexanne.collier@example.org","phone":"(239) 364-9633","account_number":"8733957499","address":null,"role_type":"trabajador","is_enabled":false,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
31	default	created	App\\Models\\Person	27	\N	\N	{"attributes":{"id":27,"first_name":"Abagail","last_name":"Ebert Ortiz","rut":"4807040-K","email":"lesly.gutmann@example.com","phone":"+1-480-409-4202","account_number":"5268300464","address":"62345 Mraz Mountains\\nCamilachester, DC 88889","role_type":"tesorero","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
32	default	created	App\\Models\\Person	28	\N	\N	{"attributes":{"id":28,"first_name":"Mireya","last_name":"Terry Kihn","rut":"6649640-6","email":"alta.ryan@example.net","phone":"678-250-5124","account_number":"1847843178","address":null,"role_type":"trabajador","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
33	default	created	App\\Models\\Person	29	\N	\N	{"attributes":{"id":29,"first_name":"Destiny","last_name":"Farrell Turner","rut":"19055630-1","email":"kendall.jakubowski@example.net","phone":null,"account_number":"8769471914","address":"313 Botsford Pike\\nPort Carrollhaven, UT 63240-6137","role_type":"tesorero","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
34	default	created	App\\Models\\Person	30	\N	\N	{"attributes":{"id":30,"first_name":"Chloe","last_name":"Weimann McLaughlin","rut":"6235032-6","email":"ferne81@example.net","phone":"1-323-496-6706","account_number":"4175466317","address":"15443 Frieda Trail Suite 724\\nLake Fanniemouth, PA 33730","role_type":"tesorero","is_enabled":false,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
35	default	created	App\\Models\\Account	3	\N	\N	{"attributes":{"id":3,"name":"Carlos Mendoza","type":"person","person_id":1,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
36	default	created	App\\Models\\Account	4	\N	\N	{"attributes":{"id":4,"name":"Mar\\u00eda Garc\\u00eda","type":"person","person_id":2,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
37	default	created	App\\Models\\Account	5	\N	\N	{"attributes":{"id":5,"name":"Luis Rodr\\u00edguez","type":"person","person_id":3,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
38	default	created	App\\Models\\Account	6	\N	\N	{"attributes":{"id":6,"name":"Ana L\\u00f3pez","type":"person","person_id":4,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
39	default	created	App\\Models\\Account	7	\N	\N	{"attributes":{"id":7,"name":"Pedro S\\u00e1nchez","type":"person","person_id":5,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
40	default	created	App\\Models\\Account	8	\N	\N	{"attributes":{"id":8,"name":"Jamarcus Torp Quitzon","type":"person","person_id":6,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
41	default	created	App\\Models\\Account	9	\N	\N	{"attributes":{"id":9,"name":"Mina Langosh Legros","type":"person","person_id":7,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
42	default	created	App\\Models\\Account	10	\N	\N	{"attributes":{"id":10,"name":"Raegan Auer O'Hara","type":"person","person_id":8,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
43	default	created	App\\Models\\Account	11	\N	\N	{"attributes":{"id":11,"name":"Vinnie Schamberger Reichert","type":"person","person_id":9,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
44	default	created	App\\Models\\Account	12	\N	\N	{"attributes":{"id":12,"name":"Marge Roob Wehner","type":"person","person_id":10,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
45	default	created	App\\Models\\Account	13	\N	\N	{"attributes":{"id":13,"name":"Mallory Moore White","type":"person","person_id":11,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
46	default	created	App\\Models\\Account	14	\N	\N	{"attributes":{"id":14,"name":"Lurline Larson Dach","type":"person","person_id":12,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
47	default	created	App\\Models\\Account	15	\N	\N	{"attributes":{"id":15,"name":"Ibrahim O'Conner Casper","type":"person","person_id":13,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
48	default	created	App\\Models\\Account	16	\N	\N	{"attributes":{"id":16,"name":"Amos Lakin Crona","type":"person","person_id":14,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
49	default	created	App\\Models\\Account	17	\N	\N	{"attributes":{"id":17,"name":"Hank Bradtke Stehr","type":"person","person_id":15,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
50	default	created	App\\Models\\Account	18	\N	\N	{"attributes":{"id":18,"name":"Yoshiko Wiegand McClure","type":"person","person_id":16,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
51	default	created	App\\Models\\Account	19	\N	\N	{"attributes":{"id":19,"name":"Cesar Marks Denesik","type":"person","person_id":17,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
52	default	created	App\\Models\\Account	20	\N	\N	{"attributes":{"id":20,"name":"Estella Harvey Witting","type":"person","person_id":18,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
53	default	created	App\\Models\\Account	21	\N	\N	{"attributes":{"id":21,"name":"Julianne Schmeler Volkman","type":"person","person_id":19,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
54	default	created	App\\Models\\Account	22	\N	\N	{"attributes":{"id":22,"name":"Claude Simonis Marvin","type":"person","person_id":20,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
55	default	created	App\\Models\\Account	23	\N	\N	{"attributes":{"id":23,"name":"Katlynn Grimes Hilpert","type":"person","person_id":21,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
56	default	created	App\\Models\\Account	24	\N	\N	{"attributes":{"id":24,"name":"Charles Swaniawski Williamson","type":"person","person_id":22,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
57	default	created	App\\Models\\Account	25	\N	\N	{"attributes":{"id":25,"name":"Otha McLaughlin Cruickshank","type":"person","person_id":23,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
58	default	created	App\\Models\\Account	26	\N	\N	{"attributes":{"id":26,"name":"Frederick Simonis Abernathy","type":"person","person_id":24,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
59	default	created	App\\Models\\Account	27	\N	\N	{"attributes":{"id":27,"name":"Alexa Christiansen Wiegand","type":"person","person_id":25,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
60	default	created	App\\Models\\Account	28	\N	\N	{"attributes":{"id":28,"name":"Madonna Dooley Paucek","type":"person","person_id":26,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
61	default	created	App\\Models\\Account	29	\N	\N	{"attributes":{"id":29,"name":"Abagail Ebert Ortiz","type":"person","person_id":27,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
62	default	created	App\\Models\\Account	30	\N	\N	{"attributes":{"id":30,"name":"Mireya Terry Kihn","type":"person","person_id":28,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
63	default	created	App\\Models\\Account	31	\N	\N	{"attributes":{"id":31,"name":"Destiny Farrell Turner","type":"person","person_id":29,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
64	default	created	App\\Models\\Account	32	\N	\N	{"attributes":{"id":32,"name":"Chloe Weimann McLaughlin","type":"person","person_id":30,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
65	default	created	App\\Models\\Transaction	1	\N	\N	{"attributes":{"id":1,"transaction_number":"TXN-2025-001","type":"transfer","from_account_id":1,"to_account_id":2,"amount":"500000.00","description":"Transferencia para gastos de cuadrilla Norte","notes":"Fondos para materiales y vi\\u00e1ticos","created_by":1,"approved_by":1,"status":"approved","approved_at":"2025-09-16T12:06:28.000000Z","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
66	default	created	App\\Models\\Transaction	2	\N	\N	{"attributes":{"id":2,"transaction_number":"TXN-2025-002","type":"transfer","from_account_id":1,"to_account_id":3,"amount":"300000.00","description":"Transferencia para gastos de cuadrilla Sur","notes":"Fondos para proyecto espec\\u00edfico","created_by":1,"approved_by":null,"status":"pending","approved_at":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
67	default	created	App\\Models\\Expense	1	\N	\N	{"attributes":{"id":1,"expense_number":"RND-2025-001","account_id":1,"submitted_by":1,"total_amount":"85000.00","description":"Rendici\\u00f3n de gastos - Cuadrilla Norte","expense_date":"2025-09-13T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-15T12:06:28.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
68	default	created	App\\Models\\ExpenseItem	1	\N	\N	{"attributes":{"id":1,"expense_id":1,"document_type":"factura","document_number":"001-00100","vendor_name":"Copec","description":"Combustible","amount":"45000.00","expense_date":"2025-08-18T00:00:00.000000Z","category":"combustible","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","expense_category_id":null}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
69	default	created	App\\Models\\ExpenseItem	2	\N	\N	{"attributes":{"id":2,"expense_id":1,"document_type":"boleta","document_number":"002-00200","vendor_name":"Ferreter\\u00eda Los Andes","description":"Materiales de construcci\\u00f3n","amount":"40000.00","expense_date":"2025-08-31T00:00:00.000000Z","category":"materiales","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","expense_category_id":null}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
70	default	created	App\\Models\\Expense	2	\N	\N	{"attributes":{"id":2,"expense_number":"RND-2025-002","account_id":2,"submitted_by":2,"total_amount":"120000.00","description":"Rendici\\u00f3n de gastos - Cuadrilla Sur","expense_date":"2025-09-09T00:00:00.000000Z","status":"approved","reviewed_by":1,"submitted_at":"2025-09-10T12:06:28.000000Z","reviewed_at":"2025-09-11T12:06:28.000000Z","rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
71	default	created	App\\Models\\ExpenseItem	3	\N	\N	{"attributes":{"id":3,"expense_id":2,"document_type":"factura","document_number":"003-00301","vendor_name":"Sodimac","description":"Herramientas","amount":"80000.00","expense_date":"2025-08-20T00:00:00.000000Z","category":"herramientas","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","expense_category_id":null}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
72	default	created	App\\Models\\ExpenseItem	4	\N	\N	{"attributes":{"id":4,"expense_id":2,"document_type":"ticket","document_number":null,"vendor_name":"Restaurant El Buen Sabor","description":"Vi\\u00e1ticos","amount":"40000.00","expense_date":"2025-09-09T00:00:00.000000Z","category":"viaticos","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","expense_category_id":null}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
73	default	created	App\\Models\\User	3	\N	\N	{"attributes":{"name":"Tesorero Sistema","email":"treasurer@coteso.local","is_enabled":true}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
74	default	created	App\\Models\\Person	31	\N	\N	{"attributes":{"id":31,"first_name":"Tesorero","last_name":"Sistema","rut":"6895247","email":"treasurer.person@coteso.local","phone":null,"account_number":null,"address":null,"role_type":"tesorero","is_enabled":true,"created_at":"2025-09-16T12:06:28.000000Z","updated_at":"2025-09-16T12:06:28.000000Z","bank_id":null,"account_type_id":null,"is_protected":true}}	2025-09-16 12:06:28	2025-09-16 12:06:28	created	\N
75	default	updated	App\\Models\\User	3	\N	\N	{"attributes":{"is_enabled":true},"old":{"is_enabled":null}}	2025-09-16 12:06:28	2025-09-16 12:06:28	updated	\N
76	default	updated	App\\Models\\Account	1	\N	\N	{"attributes":{"updated_at":"2025-09-16T12:06:28.000000Z","is_protected":true},"old":{"updated_at":"2025-09-16T12:06:27.000000Z","is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	updated	\N
77	default	updated	App\\Models\\Account	2	\N	\N	{"attributes":{"updated_at":"2025-09-16T12:06:28.000000Z","is_protected":true},"old":{"updated_at":"2025-09-16T12:06:27.000000Z","is_protected":false}}	2025-09-16 12:06:28	2025-09-16 12:06:28	updated	\N
78	default	created	App\\Models\\Account	33	\N	\N	{"attributes":{"id":33,"name":"Tesorero Sistema","type":"person","person_id":31,"balance":"0.00","notes":null,"is_enabled":true,"created_at":"2025-09-16T14:54:24.000000Z","updated_at":"2025-09-16T14:54:24.000000Z","is_fondeo":false,"is_protected":false}}	2025-09-16 14:54:24	2025-09-16 14:54:24	created	\N
79	default	created	App\\Models\\Expense	3	\N	\N	{"attributes":{"id":3,"expense_number":"RND-2025-000003","account_id":3,"submitted_by":1,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Carlos Mendoza","expense_date":"2025-02-03T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-02-03T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
80	default	created	App\\Models\\ExpenseItem	5	\N	\N	{"attributes":{"id":5,"expense_id":3,"document_type":"ticket","document_number":"D-2213","vendor_name":"Proveedor mendoza","description":"Gasto 0 para Carlos","amount":"10940.00","expense_date":"2025-02-03T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":8}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
81	default	created	App\\Models\\ExpenseItem	6	\N	\N	{"attributes":{"id":6,"expense_id":3,"document_type":"ticket","document_number":"D-6246","vendor_name":"Proveedor mendoza","description":"Gasto 1 para Carlos","amount":"47027.00","expense_date":"2025-02-03T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":9}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
82	default	created	App\\Models\\ExpenseItem	7	\N	\N	{"attributes":{"id":7,"expense_id":3,"document_type":"ticket","document_number":"D-3156","vendor_name":"Proveedor mendoza","description":"Gasto 2 para Carlos","amount":"22409.00","expense_date":"2025-02-03T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":2}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
83	default	updated	App\\Models\\Expense	3	\N	\N	{"attributes":{"total_amount":"80376.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
84	default	created	App\\Models\\Expense	4	\N	\N	{"attributes":{"id":4,"expense_number":"RND-2025-000004","account_id":3,"submitted_by":1,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Carlos Mendoza","expense_date":"2024-09-14T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-09-14T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
85	default	created	App\\Models\\ExpenseItem	8	\N	\N	{"attributes":{"id":8,"expense_id":4,"document_type":"ticket","document_number":"D-2155","vendor_name":"Proveedor mendoza","description":"Gasto 0 para Carlos","amount":"96793.00","expense_date":"2024-09-14T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":4}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
86	default	created	App\\Models\\ExpenseItem	9	\N	\N	{"attributes":{"id":9,"expense_id":4,"document_type":"ticket","document_number":"D-4803","vendor_name":"Proveedor mendoza","description":"Gasto 1 para Carlos","amount":"114032.00","expense_date":"2024-09-14T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":8}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
87	default	updated	App\\Models\\Expense	4	\N	\N	{"attributes":{"total_amount":"210825.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
88	default	updated	App\\Models\\Expense	4	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
89	default	created	App\\Models\\Expense	5	\N	\N	{"attributes":{"id":5,"expense_number":"RND-2025-000005","account_id":4,"submitted_by":2,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Mar\\u00eda Garc\\u00eda","expense_date":"2024-10-27T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-10-27T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
90	default	created	App\\Models\\ExpenseItem	10	\N	\N	{"attributes":{"id":10,"expense_id":5,"document_type":"ticket","document_number":"D-4847","vendor_name":"Proveedor garcia","description":"Gasto 0 para Mar\\u00eda","amount":"96987.00","expense_date":"2024-10-27T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":10}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
91	default	created	App\\Models\\ExpenseItem	11	\N	\N	{"attributes":{"id":11,"expense_id":5,"document_type":"ticket","document_number":"D-1072","vendor_name":"Proveedor garcia","description":"Gasto 1 para Mar\\u00eda","amount":"99462.00","expense_date":"2024-10-27T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":2}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
92	default	updated	App\\Models\\Expense	5	\N	\N	{"attributes":{"total_amount":"196449.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
93	default	updated	App\\Models\\Expense	5	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
94	default	created	App\\Models\\Expense	6	\N	\N	{"attributes":{"id":6,"expense_number":"RND-2025-000006","account_id":4,"submitted_by":2,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Mar\\u00eda Garc\\u00eda","expense_date":"2025-07-22T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-07-22T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
95	default	created	App\\Models\\ExpenseItem	12	\N	\N	{"attributes":{"id":12,"expense_id":6,"document_type":"ticket","document_number":"D-9918","vendor_name":"Proveedor garcia","description":"Gasto 0 para Mar\\u00eda","amount":"19899.00","expense_date":"2025-07-22T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":3}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
96	default	created	App\\Models\\ExpenseItem	13	\N	\N	{"attributes":{"id":13,"expense_id":6,"document_type":"ticket","document_number":"D-7844","vendor_name":"Proveedor garcia","description":"Gasto 1 para Mar\\u00eda","amount":"26932.00","expense_date":"2025-07-22T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":8}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
97	default	updated	App\\Models\\Expense	6	\N	\N	{"attributes":{"total_amount":"46831.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
98	default	updated	App\\Models\\Expense	6	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
99	default	created	App\\Models\\Expense	7	\N	\N	{"attributes":{"id":7,"expense_number":"RND-2025-000007","account_id":5,"submitted_by":3,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Luis Rodr\\u00edguez","expense_date":"2025-02-05T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-02-05T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
100	default	created	App\\Models\\ExpenseItem	14	\N	\N	{"attributes":{"id":14,"expense_id":7,"document_type":"ticket","document_number":"D-2031","vendor_name":"Proveedor rodrigue","description":"Gasto 0 para Luis","amount":"114041.00","expense_date":"2025-02-05T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":1}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
101	default	updated	App\\Models\\Expense	7	\N	\N	{"attributes":{"total_amount":"114041.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
102	default	updated	App\\Models\\Expense	7	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
103	default	created	App\\Models\\Expense	8	\N	\N	{"attributes":{"id":8,"expense_number":"RND-2025-000008","account_id":6,"submitted_by":4,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Ana L\\u00f3pez","expense_date":"2024-12-03T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-12-03T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
104	default	created	App\\Models\\ExpenseItem	15	\N	\N	{"attributes":{"id":15,"expense_id":8,"document_type":"ticket","document_number":"D-2746","vendor_name":"Proveedor lopez","description":"Gasto 0 para Ana","amount":"2280.00","expense_date":"2024-12-03T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":3}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
105	default	created	App\\Models\\ExpenseItem	16	\N	\N	{"attributes":{"id":16,"expense_id":8,"document_type":"ticket","document_number":"D-1647","vendor_name":"Proveedor lopez","description":"Gasto 1 para Ana","amount":"76265.00","expense_date":"2024-12-03T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":3}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
106	default	updated	App\\Models\\Expense	8	\N	\N	{"attributes":{"total_amount":"78545.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
107	default	created	App\\Models\\Expense	9	\N	\N	{"attributes":{"id":9,"expense_number":"RND-2025-000009","account_id":7,"submitted_by":5,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Pedro S\\u00e1nchez","expense_date":"2024-08-31T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-08-31T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
108	default	created	App\\Models\\ExpenseItem	17	\N	\N	{"attributes":{"id":17,"expense_id":9,"document_type":"ticket","document_number":"D-8026","vendor_name":"Proveedor sanchez","description":"Gasto 0 para Pedro","amount":"67462.00","expense_date":"2024-08-31T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":10}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
109	default	created	App\\Models\\ExpenseItem	18	\N	\N	{"attributes":{"id":18,"expense_id":9,"document_type":"ticket","document_number":"D-3592","vendor_name":"Proveedor sanchez","description":"Gasto 1 para Pedro","amount":"70912.00","expense_date":"2024-08-31T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":10}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
110	default	created	App\\Models\\ExpenseItem	19	\N	\N	{"attributes":{"id":19,"expense_id":9,"document_type":"ticket","document_number":"D-2496","vendor_name":"Proveedor sanchez","description":"Gasto 2 para Pedro","amount":"69760.00","expense_date":"2024-08-31T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":10}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
111	default	updated	App\\Models\\Expense	9	\N	\N	{"attributes":{"total_amount":"208134.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
112	default	created	App\\Models\\Expense	10	\N	\N	{"attributes":{"id":10,"expense_number":"RND-2025-000010","account_id":7,"submitted_by":5,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Pedro S\\u00e1nchez","expense_date":"2025-07-12T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-07-12T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
113	default	created	App\\Models\\ExpenseItem	20	\N	\N	{"attributes":{"id":20,"expense_id":10,"document_type":"ticket","document_number":"D-4452","vendor_name":"Proveedor sanchez","description":"Gasto 0 para Pedro","amount":"67844.00","expense_date":"2025-07-12T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":9}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
114	default	updated	App\\Models\\Expense	10	\N	\N	{"attributes":{"total_amount":"67844.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
115	default	updated	App\\Models\\Expense	10	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
116	default	created	App\\Models\\Expense	11	\N	\N	{"attributes":{"id":11,"expense_number":"RND-2025-000011","account_id":8,"submitted_by":6,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Jamarcus Torp Quitzon","expense_date":"2025-01-01T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-01-01T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
117	default	created	App\\Models\\ExpenseItem	21	\N	\N	{"attributes":{"id":21,"expense_id":11,"document_type":"ticket","document_number":"D-8788","vendor_name":"Proveedor torp-qui","description":"Gasto 0 para Jamarcus","amount":"64879.00","expense_date":"2025-01-01T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":6}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
118	default	created	App\\Models\\ExpenseItem	22	\N	\N	{"attributes":{"id":22,"expense_id":11,"document_type":"ticket","document_number":"D-7049","vendor_name":"Proveedor torp-qui","description":"Gasto 1 para Jamarcus","amount":"118256.00","expense_date":"2025-01-01T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":5}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
119	default	updated	App\\Models\\Expense	11	\N	\N	{"attributes":{"total_amount":"183135.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
120	default	updated	App\\Models\\Expense	11	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
121	default	created	App\\Models\\Expense	12	\N	\N	{"attributes":{"id":12,"expense_number":"RND-2025-000012","account_id":9,"submitted_by":7,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Mina Langosh Legros","expense_date":"2024-09-07T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-09-07T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
122	default	created	App\\Models\\ExpenseItem	23	\N	\N	{"attributes":{"id":23,"expense_id":12,"document_type":"ticket","document_number":"D-5532","vendor_name":"Proveedor langosh-","description":"Gasto 0 para Mina","amount":"11507.00","expense_date":"2024-09-07T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":9}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
123	default	updated	App\\Models\\Expense	12	\N	\N	{"attributes":{"total_amount":"11507.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
124	default	created	App\\Models\\Expense	13	\N	\N	{"attributes":{"id":13,"expense_number":"RND-2025-000013","account_id":10,"submitted_by":8,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Raegan Auer O'Hara","expense_date":"2025-06-04T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-06-04T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
125	default	created	App\\Models\\ExpenseItem	24	\N	\N	{"attributes":{"id":24,"expense_id":13,"document_type":"ticket","document_number":"D-3533","vendor_name":"Proveedor auer-oha","description":"Gasto 0 para Raegan","amount":"47746.00","expense_date":"2025-06-04T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":7}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
126	default	created	App\\Models\\ExpenseItem	25	\N	\N	{"attributes":{"id":25,"expense_id":13,"document_type":"ticket","document_number":"D-8825","vendor_name":"Proveedor auer-oha","description":"Gasto 1 para Raegan","amount":"105551.00","expense_date":"2025-06-04T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":9}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
127	default	created	App\\Models\\ExpenseItem	26	\N	\N	{"attributes":{"id":26,"expense_id":13,"document_type":"ticket","document_number":"D-8031","vendor_name":"Proveedor auer-oha","description":"Gasto 2 para Raegan","amount":"71730.00","expense_date":"2025-06-04T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":10}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
128	default	updated	App\\Models\\Expense	13	\N	\N	{"attributes":{"total_amount":"225027.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
129	default	updated	App\\Models\\Expense	13	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
130	default	created	App\\Models\\Expense	14	\N	\N	{"attributes":{"id":14,"expense_number":"RND-2025-000014","account_id":10,"submitted_by":8,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Raegan Auer O'Hara","expense_date":"2025-04-05T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-04-05T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
131	default	created	App\\Models\\ExpenseItem	27	\N	\N	{"attributes":{"id":27,"expense_id":14,"document_type":"ticket","document_number":"D-9269","vendor_name":"Proveedor auer-oha","description":"Gasto 0 para Raegan","amount":"75930.00","expense_date":"2025-04-05T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":2}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
132	default	updated	App\\Models\\Expense	14	\N	\N	{"attributes":{"total_amount":"75930.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
133	default	created	App\\Models\\Expense	15	\N	\N	{"attributes":{"id":15,"expense_number":"RND-2025-000015","account_id":10,"submitted_by":8,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Raegan Auer O'Hara","expense_date":"2025-06-07T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-06-07T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
134	default	created	App\\Models\\ExpenseItem	28	\N	\N	{"attributes":{"id":28,"expense_id":15,"document_type":"ticket","document_number":"D-8609","vendor_name":"Proveedor auer-oha","description":"Gasto 0 para Raegan","amount":"109616.00","expense_date":"2025-06-07T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":1}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
135	default	created	App\\Models\\ExpenseItem	29	\N	\N	{"attributes":{"id":29,"expense_id":15,"document_type":"ticket","document_number":"D-1786","vendor_name":"Proveedor auer-oha","description":"Gasto 1 para Raegan","amount":"78699.00","expense_date":"2025-06-07T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":9}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
136	default	created	App\\Models\\ExpenseItem	30	\N	\N	{"attributes":{"id":30,"expense_id":15,"document_type":"ticket","document_number":"D-8135","vendor_name":"Proveedor auer-oha","description":"Gasto 2 para Raegan","amount":"118482.00","expense_date":"2025-06-07T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":6}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
137	default	created	App\\Models\\ExpenseItem	31	\N	\N	{"attributes":{"id":31,"expense_id":15,"document_type":"ticket","document_number":"D-5907","vendor_name":"Proveedor auer-oha","description":"Gasto 3 para Raegan","amount":"37789.00","expense_date":"2025-06-07T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":8}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
138	default	updated	App\\Models\\Expense	15	\N	\N	{"attributes":{"total_amount":"344586.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
139	default	updated	App\\Models\\Expense	15	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
140	default	created	App\\Models\\Expense	16	\N	\N	{"attributes":{"id":16,"expense_number":"RND-2025-000016","account_id":11,"submitted_by":9,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Vinnie Schamberger Reichert","expense_date":"2025-07-01T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-07-01T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
141	default	created	App\\Models\\ExpenseItem	32	\N	\N	{"attributes":{"id":32,"expense_id":16,"document_type":"ticket","document_number":"D-7518","vendor_name":"Proveedor schamber","description":"Gasto 0 para Vinnie","amount":"119383.00","expense_date":"2025-07-01T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":1}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
142	default	created	App\\Models\\ExpenseItem	33	\N	\N	{"attributes":{"id":33,"expense_id":16,"document_type":"ticket","document_number":"D-7712","vendor_name":"Proveedor schamber","description":"Gasto 1 para Vinnie","amount":"94799.00","expense_date":"2025-07-01T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":6}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
143	default	updated	App\\Models\\Expense	16	\N	\N	{"attributes":{"total_amount":"214182.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
144	default	created	App\\Models\\Expense	17	\N	\N	{"attributes":{"id":17,"expense_number":"RND-2025-000017","account_id":11,"submitted_by":9,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Vinnie Schamberger Reichert","expense_date":"2025-04-19T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-04-19T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
145	default	created	App\\Models\\ExpenseItem	34	\N	\N	{"attributes":{"id":34,"expense_id":17,"document_type":"ticket","document_number":"D-3634","vendor_name":"Proveedor schamber","description":"Gasto 0 para Vinnie","amount":"34867.00","expense_date":"2025-04-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":1}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
146	default	created	App\\Models\\ExpenseItem	35	\N	\N	{"attributes":{"id":35,"expense_id":17,"document_type":"ticket","document_number":"D-8562","vendor_name":"Proveedor schamber","description":"Gasto 1 para Vinnie","amount":"12080.00","expense_date":"2025-04-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":5}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
185	default	updated	App\\Models\\Expense	23	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
147	default	created	App\\Models\\ExpenseItem	36	\N	\N	{"attributes":{"id":36,"expense_id":17,"document_type":"ticket","document_number":"D-4378","vendor_name":"Proveedor schamber","description":"Gasto 2 para Vinnie","amount":"14170.00","expense_date":"2025-04-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":4}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
148	default	created	App\\Models\\ExpenseItem	37	\N	\N	{"attributes":{"id":37,"expense_id":17,"document_type":"ticket","document_number":"D-3355","vendor_name":"Proveedor schamber","description":"Gasto 3 para Vinnie","amount":"40103.00","expense_date":"2025-04-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":6}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
149	default	updated	App\\Models\\Expense	17	\N	\N	{"attributes":{"total_amount":"101220.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
150	default	updated	App\\Models\\Expense	17	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
151	default	created	App\\Models\\Expense	18	\N	\N	{"attributes":{"id":18,"expense_number":"RND-2025-000018","account_id":11,"submitted_by":9,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Vinnie Schamberger Reichert","expense_date":"2025-06-13T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-06-13T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
152	default	created	App\\Models\\ExpenseItem	38	\N	\N	{"attributes":{"id":38,"expense_id":18,"document_type":"ticket","document_number":"D-3296","vendor_name":"Proveedor schamber","description":"Gasto 0 para Vinnie","amount":"109644.00","expense_date":"2025-06-13T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":1}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
153	default	created	App\\Models\\ExpenseItem	39	\N	\N	{"attributes":{"id":39,"expense_id":18,"document_type":"ticket","document_number":"D-8345","vendor_name":"Proveedor schamber","description":"Gasto 1 para Vinnie","amount":"69695.00","expense_date":"2025-06-13T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":1}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
154	default	created	App\\Models\\ExpenseItem	40	\N	\N	{"attributes":{"id":40,"expense_id":18,"document_type":"ticket","document_number":"D-7049","vendor_name":"Proveedor schamber","description":"Gasto 2 para Vinnie","amount":"110697.00","expense_date":"2025-06-13T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":4}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
155	default	created	App\\Models\\ExpenseItem	41	\N	\N	{"attributes":{"id":41,"expense_id":18,"document_type":"ticket","document_number":"D-8050","vendor_name":"Proveedor schamber","description":"Gasto 3 para Vinnie","amount":"100936.00","expense_date":"2025-06-13T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":5}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
156	default	created	App\\Models\\ExpenseItem	42	\N	\N	{"attributes":{"id":42,"expense_id":18,"document_type":"ticket","document_number":"D-3064","vendor_name":"Proveedor schamber","description":"Gasto 4 para Vinnie","amount":"35593.00","expense_date":"2025-06-13T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":8}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
157	default	updated	App\\Models\\Expense	18	\N	\N	{"attributes":{"total_amount":"426565.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
158	default	updated	App\\Models\\Expense	18	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
159	default	created	App\\Models\\Expense	19	\N	\N	{"attributes":{"id":19,"expense_number":"RND-2025-000019","account_id":12,"submitted_by":10,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Marge Roob Wehner","expense_date":"2025-06-26T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-06-26T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
160	default	created	App\\Models\\ExpenseItem	43	\N	\N	{"attributes":{"id":43,"expense_id":19,"document_type":"ticket","document_number":"D-3828","vendor_name":"Proveedor roob-weh","description":"Gasto 0 para Marge","amount":"87757.00","expense_date":"2025-06-26T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":1}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
161	default	created	App\\Models\\ExpenseItem	44	\N	\N	{"attributes":{"id":44,"expense_id":19,"document_type":"ticket","document_number":"D-4713","vendor_name":"Proveedor roob-weh","description":"Gasto 1 para Marge","amount":"36090.00","expense_date":"2025-06-26T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":1}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
162	default	updated	App\\Models\\Expense	19	\N	\N	{"attributes":{"total_amount":"123847.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
163	default	updated	App\\Models\\Expense	19	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
164	default	created	App\\Models\\Expense	20	\N	\N	{"attributes":{"id":20,"expense_number":"RND-2025-000020","account_id":13,"submitted_by":11,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Mallory Moore White","expense_date":"2025-03-01T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-03-01T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
165	default	created	App\\Models\\ExpenseItem	45	\N	\N	{"attributes":{"id":45,"expense_id":20,"document_type":"ticket","document_number":"D-1826","vendor_name":"Proveedor moore-wh","description":"Gasto 0 para Mallory","amount":"1684.00","expense_date":"2025-03-01T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":5}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
166	default	created	App\\Models\\ExpenseItem	46	\N	\N	{"attributes":{"id":46,"expense_id":20,"document_type":"ticket","document_number":"D-1586","vendor_name":"Proveedor moore-wh","description":"Gasto 1 para Mallory","amount":"66928.00","expense_date":"2025-03-01T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":8}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
167	default	updated	App\\Models\\Expense	20	\N	\N	{"attributes":{"total_amount":"68612.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
168	default	updated	App\\Models\\Expense	20	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
169	default	created	App\\Models\\Expense	21	\N	\N	{"attributes":{"id":21,"expense_number":"RND-2025-000021","account_id":13,"submitted_by":11,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Mallory Moore White","expense_date":"2024-12-03T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-12-03T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
170	default	created	App\\Models\\ExpenseItem	47	\N	\N	{"attributes":{"id":47,"expense_id":21,"document_type":"ticket","document_number":"D-9086","vendor_name":"Proveedor moore-wh","description":"Gasto 0 para Mallory","amount":"118341.00","expense_date":"2024-12-03T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":4}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
171	default	created	App\\Models\\ExpenseItem	48	\N	\N	{"attributes":{"id":48,"expense_id":21,"document_type":"ticket","document_number":"D-5373","vendor_name":"Proveedor moore-wh","description":"Gasto 1 para Mallory","amount":"91565.00","expense_date":"2024-12-03T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":2}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
172	default	created	App\\Models\\ExpenseItem	49	\N	\N	{"attributes":{"id":49,"expense_id":21,"document_type":"ticket","document_number":"D-2842","vendor_name":"Proveedor moore-wh","description":"Gasto 2 para Mallory","amount":"70449.00","expense_date":"2024-12-03T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":9}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
173	default	updated	App\\Models\\Expense	21	\N	\N	{"attributes":{"total_amount":"280355.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
174	default	created	App\\Models\\Expense	22	\N	\N	{"attributes":{"id":22,"expense_number":"RND-2025-000022","account_id":13,"submitted_by":11,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Mallory Moore White","expense_date":"2025-06-02T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-06-02T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
175	default	created	App\\Models\\ExpenseItem	50	\N	\N	{"attributes":{"id":50,"expense_id":22,"document_type":"ticket","document_number":"D-4780","vendor_name":"Proveedor moore-wh","description":"Gasto 0 para Mallory","amount":"65121.00","expense_date":"2025-06-02T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":4}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
176	default	created	App\\Models\\ExpenseItem	51	\N	\N	{"attributes":{"id":51,"expense_id":22,"document_type":"ticket","document_number":"D-8082","vendor_name":"Proveedor moore-wh","description":"Gasto 1 para Mallory","amount":"5060.00","expense_date":"2025-06-02T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":8}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
177	default	created	App\\Models\\ExpenseItem	52	\N	\N	{"attributes":{"id":52,"expense_id":22,"document_type":"ticket","document_number":"D-2372","vendor_name":"Proveedor moore-wh","description":"Gasto 2 para Mallory","amount":"103422.00","expense_date":"2025-06-02T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":7}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
178	default	updated	App\\Models\\Expense	22	\N	\N	{"attributes":{"total_amount":"173603.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
179	default	created	App\\Models\\Expense	23	\N	\N	{"attributes":{"id":23,"expense_number":"RND-2025-000023","account_id":14,"submitted_by":12,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Lurline Larson Dach","expense_date":"2024-12-24T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-12-24T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
180	default	created	App\\Models\\ExpenseItem	53	\N	\N	{"attributes":{"id":53,"expense_id":23,"document_type":"ticket","document_number":"D-8212","vendor_name":"Proveedor larson-d","description":"Gasto 0 para Lurline","amount":"15718.00","expense_date":"2024-12-24T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":4}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
181	default	created	App\\Models\\ExpenseItem	54	\N	\N	{"attributes":{"id":54,"expense_id":23,"document_type":"ticket","document_number":"D-2070","vendor_name":"Proveedor larson-d","description":"Gasto 1 para Lurline","amount":"6004.00","expense_date":"2024-12-24T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":10}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
182	default	created	App\\Models\\ExpenseItem	55	\N	\N	{"attributes":{"id":55,"expense_id":23,"document_type":"ticket","document_number":"D-6131","vendor_name":"Proveedor larson-d","description":"Gasto 2 para Lurline","amount":"72544.00","expense_date":"2024-12-24T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":10}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
183	default	created	App\\Models\\ExpenseItem	56	\N	\N	{"attributes":{"id":56,"expense_id":23,"document_type":"ticket","document_number":"D-4294","vendor_name":"Proveedor larson-d","description":"Gasto 3 para Lurline","amount":"118813.00","expense_date":"2024-12-24T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":1}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
186	default	created	App\\Models\\Expense	24	\N	\N	{"attributes":{"id":24,"expense_number":"RND-2025-000024","account_id":15,"submitted_by":13,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Ibrahim O'Conner Casper","expense_date":"2025-08-19T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-08-19T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
187	default	created	App\\Models\\ExpenseItem	57	\N	\N	{"attributes":{"id":57,"expense_id":24,"document_type":"ticket","document_number":"D-2022","vendor_name":"Proveedor oconner-","description":"Gasto 0 para Ibrahim","amount":"79402.00","expense_date":"2025-08-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":4}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
188	default	created	App\\Models\\ExpenseItem	58	\N	\N	{"attributes":{"id":58,"expense_id":24,"document_type":"ticket","document_number":"D-4957","vendor_name":"Proveedor oconner-","description":"Gasto 1 para Ibrahim","amount":"58371.00","expense_date":"2025-08-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":4}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
189	default	created	App\\Models\\ExpenseItem	59	\N	\N	{"attributes":{"id":59,"expense_id":24,"document_type":"ticket","document_number":"D-4841","vendor_name":"Proveedor oconner-","description":"Gasto 2 para Ibrahim","amount":"100603.00","expense_date":"2025-08-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":5}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
190	default	created	App\\Models\\ExpenseItem	60	\N	\N	{"attributes":{"id":60,"expense_id":24,"document_type":"ticket","document_number":"D-5752","vendor_name":"Proveedor oconner-","description":"Gasto 3 para Ibrahim","amount":"101403.00","expense_date":"2025-08-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":7}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
191	default	updated	App\\Models\\Expense	24	\N	\N	{"attributes":{"total_amount":"339779.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
192	default	updated	App\\Models\\Expense	24	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
193	default	created	App\\Models\\Expense	25	\N	\N	{"attributes":{"id":25,"expense_number":"RND-2025-000025","account_id":16,"submitted_by":14,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Amos Lakin Crona","expense_date":"2024-12-07T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-12-07T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
194	default	created	App\\Models\\ExpenseItem	61	\N	\N	{"attributes":{"id":61,"expense_id":25,"document_type":"ticket","document_number":"D-6056","vendor_name":"Proveedor lakin-cr","description":"Gasto 0 para Amos","amount":"100220.00","expense_date":"2024-12-07T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":10}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
195	default	updated	App\\Models\\Expense	25	\N	\N	{"attributes":{"total_amount":"100220.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
196	default	updated	App\\Models\\Expense	25	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
197	default	created	App\\Models\\Expense	26	\N	\N	{"attributes":{"id":26,"expense_number":"RND-2025-000026","account_id":16,"submitted_by":14,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Amos Lakin Crona","expense_date":"2025-04-02T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-04-02T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
198	default	created	App\\Models\\ExpenseItem	62	\N	\N	{"attributes":{"id":62,"expense_id":26,"document_type":"ticket","document_number":"D-9862","vendor_name":"Proveedor lakin-cr","description":"Gasto 0 para Amos","amount":"71808.00","expense_date":"2025-04-02T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":9}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
199	default	updated	App\\Models\\Expense	26	\N	\N	{"attributes":{"total_amount":"71808.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
200	default	updated	App\\Models\\Expense	26	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
201	default	created	App\\Models\\Expense	27	\N	\N	{"attributes":{"id":27,"expense_number":"RND-2025-000027","account_id":16,"submitted_by":14,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Amos Lakin Crona","expense_date":"2024-09-08T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-09-08T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
202	default	created	App\\Models\\ExpenseItem	63	\N	\N	{"attributes":{"id":63,"expense_id":27,"document_type":"ticket","document_number":"D-9363","vendor_name":"Proveedor lakin-cr","description":"Gasto 0 para Amos","amount":"43486.00","expense_date":"2024-09-08T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":9}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
203	default	created	App\\Models\\ExpenseItem	64	\N	\N	{"attributes":{"id":64,"expense_id":27,"document_type":"ticket","document_number":"D-3018","vendor_name":"Proveedor lakin-cr","description":"Gasto 1 para Amos","amount":"115281.00","expense_date":"2024-09-08T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":10}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
258	default	updated	App\\Models\\Expense	36	\N	\N	{"attributes":{"total_amount":"152951.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
204	default	created	App\\Models\\ExpenseItem	65	\N	\N	{"attributes":{"id":65,"expense_id":27,"document_type":"ticket","document_number":"D-3745","vendor_name":"Proveedor lakin-cr","description":"Gasto 2 para Amos","amount":"66978.00","expense_date":"2024-09-08T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":1}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
205	default	created	App\\Models\\ExpenseItem	66	\N	\N	{"attributes":{"id":66,"expense_id":27,"document_type":"ticket","document_number":"D-6041","vendor_name":"Proveedor lakin-cr","description":"Gasto 3 para Amos","amount":"51216.00","expense_date":"2024-09-08T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":5}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
206	default	updated	App\\Models\\Expense	27	\N	\N	{"attributes":{"total_amount":"276961.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
207	default	updated	App\\Models\\Expense	27	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
208	default	created	App\\Models\\Expense	28	\N	\N	{"attributes":{"id":28,"expense_number":"RND-2025-000028","account_id":16,"submitted_by":14,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Amos Lakin Crona","expense_date":"2024-09-03T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-09-03T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
209	default	created	App\\Models\\ExpenseItem	67	\N	\N	{"attributes":{"id":67,"expense_id":28,"document_type":"ticket","document_number":"D-3368","vendor_name":"Proveedor lakin-cr","description":"Gasto 0 para Amos","amount":"42156.00","expense_date":"2024-09-03T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":3}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
210	default	created	App\\Models\\ExpenseItem	68	\N	\N	{"attributes":{"id":68,"expense_id":28,"document_type":"ticket","document_number":"D-2666","vendor_name":"Proveedor lakin-cr","description":"Gasto 1 para Amos","amount":"111543.00","expense_date":"2024-09-03T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":6}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
211	default	created	App\\Models\\ExpenseItem	69	\N	\N	{"attributes":{"id":69,"expense_id":28,"document_type":"ticket","document_number":"D-3632","vendor_name":"Proveedor lakin-cr","description":"Gasto 2 para Amos","amount":"65113.00","expense_date":"2024-09-03T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":5}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
212	default	created	App\\Models\\ExpenseItem	70	\N	\N	{"attributes":{"id":70,"expense_id":28,"document_type":"ticket","document_number":"D-4887","vendor_name":"Proveedor lakin-cr","description":"Gasto 3 para Amos","amount":"97308.00","expense_date":"2024-09-03T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":8}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
213	default	created	App\\Models\\ExpenseItem	71	\N	\N	{"attributes":{"id":71,"expense_id":28,"document_type":"ticket","document_number":"D-3958","vendor_name":"Proveedor lakin-cr","description":"Gasto 4 para Amos","amount":"18201.00","expense_date":"2024-09-03T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":8}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
214	default	updated	App\\Models\\Expense	28	\N	\N	{"attributes":{"total_amount":"334321.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
215	default	created	App\\Models\\Expense	29	\N	\N	{"attributes":{"id":29,"expense_number":"RND-2025-000029","account_id":17,"submitted_by":15,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Hank Bradtke Stehr","expense_date":"2024-12-02T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-12-02T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
216	default	created	App\\Models\\ExpenseItem	72	\N	\N	{"attributes":{"id":72,"expense_id":29,"document_type":"ticket","document_number":"D-2887","vendor_name":"Proveedor bradtke-","description":"Gasto 0 para Hank","amount":"59754.00","expense_date":"2024-12-02T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":10}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
217	default	updated	App\\Models\\Expense	29	\N	\N	{"attributes":{"total_amount":"59754.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
218	default	updated	App\\Models\\Expense	29	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
219	default	created	App\\Models\\Expense	30	\N	\N	{"attributes":{"id":30,"expense_number":"RND-2025-000030","account_id":17,"submitted_by":15,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Hank Bradtke Stehr","expense_date":"2025-07-29T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-07-29T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
220	default	created	App\\Models\\ExpenseItem	73	\N	\N	{"attributes":{"id":73,"expense_id":30,"document_type":"ticket","document_number":"D-5832","vendor_name":"Proveedor bradtke-","description":"Gasto 0 para Hank","amount":"30983.00","expense_date":"2025-07-29T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":10}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
221	default	created	App\\Models\\ExpenseItem	74	\N	\N	{"attributes":{"id":74,"expense_id":30,"document_type":"ticket","document_number":"D-3299","vendor_name":"Proveedor bradtke-","description":"Gasto 1 para Hank","amount":"101225.00","expense_date":"2025-07-29T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":5}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
259	default	updated	App\\Models\\Expense	36	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
222	default	created	App\\Models\\ExpenseItem	75	\N	\N	{"attributes":{"id":75,"expense_id":30,"document_type":"ticket","document_number":"D-3983","vendor_name":"Proveedor bradtke-","description":"Gasto 2 para Hank","amount":"49140.00","expense_date":"2025-07-29T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":1}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
223	default	created	App\\Models\\ExpenseItem	76	\N	\N	{"attributes":{"id":76,"expense_id":30,"document_type":"ticket","document_number":"D-6026","vendor_name":"Proveedor bradtke-","description":"Gasto 3 para Hank","amount":"57474.00","expense_date":"2025-07-29T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":6}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
224	default	updated	App\\Models\\Expense	30	\N	\N	{"attributes":{"total_amount":"238822.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
225	default	updated	App\\Models\\Expense	30	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
226	default	created	App\\Models\\Expense	31	\N	\N	{"attributes":{"id":31,"expense_number":"RND-2025-000031","account_id":17,"submitted_by":15,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Hank Bradtke Stehr","expense_date":"2025-02-11T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-02-11T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
227	default	created	App\\Models\\ExpenseItem	77	\N	\N	{"attributes":{"id":77,"expense_id":31,"document_type":"ticket","document_number":"D-9947","vendor_name":"Proveedor bradtke-","description":"Gasto 0 para Hank","amount":"21609.00","expense_date":"2025-02-11T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":4}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
228	default	created	App\\Models\\ExpenseItem	78	\N	\N	{"attributes":{"id":78,"expense_id":31,"document_type":"ticket","document_number":"D-5407","vendor_name":"Proveedor bradtke-","description":"Gasto 1 para Hank","amount":"50516.00","expense_date":"2025-02-11T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":5}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
229	default	created	App\\Models\\ExpenseItem	79	\N	\N	{"attributes":{"id":79,"expense_id":31,"document_type":"ticket","document_number":"D-4397","vendor_name":"Proveedor bradtke-","description":"Gasto 2 para Hank","amount":"72784.00","expense_date":"2025-02-11T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":2}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
230	default	created	App\\Models\\ExpenseItem	80	\N	\N	{"attributes":{"id":80,"expense_id":31,"document_type":"ticket","document_number":"D-5756","vendor_name":"Proveedor bradtke-","description":"Gasto 3 para Hank","amount":"8467.00","expense_date":"2025-02-11T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":4}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
231	default	created	App\\Models\\ExpenseItem	81	\N	\N	{"attributes":{"id":81,"expense_id":31,"document_type":"ticket","document_number":"D-2812","vendor_name":"Proveedor bradtke-","description":"Gasto 4 para Hank","amount":"105658.00","expense_date":"2025-02-11T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":7}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
232	default	updated	App\\Models\\Expense	31	\N	\N	{"attributes":{"total_amount":"259034.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
233	default	created	App\\Models\\Expense	32	\N	\N	{"attributes":{"id":32,"expense_number":"RND-2025-000032","account_id":18,"submitted_by":16,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Yoshiko Wiegand McClure","expense_date":"2025-03-04T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-03-04T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
234	default	created	App\\Models\\ExpenseItem	82	\N	\N	{"attributes":{"id":82,"expense_id":32,"document_type":"ticket","document_number":"D-9520","vendor_name":"Proveedor wiegand-","description":"Gasto 0 para Yoshiko","amount":"100264.00","expense_date":"2025-03-04T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":5}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
235	default	updated	App\\Models\\Expense	32	\N	\N	{"attributes":{"total_amount":"100264.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
236	default	created	App\\Models\\Expense	33	\N	\N	{"attributes":{"id":33,"expense_number":"RND-2025-000033","account_id":18,"submitted_by":16,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Yoshiko Wiegand McClure","expense_date":"2024-12-28T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-12-28T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
237	default	created	App\\Models\\ExpenseItem	83	\N	\N	{"attributes":{"id":83,"expense_id":33,"document_type":"ticket","document_number":"D-8666","vendor_name":"Proveedor wiegand-","description":"Gasto 0 para Yoshiko","amount":"16193.00","expense_date":"2024-12-28T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":4}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
238	default	created	App\\Models\\ExpenseItem	84	\N	\N	{"attributes":{"id":84,"expense_id":33,"document_type":"ticket","document_number":"D-1441","vendor_name":"Proveedor wiegand-","description":"Gasto 1 para Yoshiko","amount":"99873.00","expense_date":"2024-12-28T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":9}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
239	default	created	App\\Models\\ExpenseItem	85	\N	\N	{"attributes":{"id":85,"expense_id":33,"document_type":"ticket","document_number":"D-7503","vendor_name":"Proveedor wiegand-","description":"Gasto 2 para Yoshiko","amount":"88831.00","expense_date":"2024-12-28T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":6}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
240	default	created	App\\Models\\ExpenseItem	86	\N	\N	{"attributes":{"id":86,"expense_id":33,"document_type":"ticket","document_number":"D-3980","vendor_name":"Proveedor wiegand-","description":"Gasto 3 para Yoshiko","amount":"27968.00","expense_date":"2024-12-28T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":5}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
241	default	created	App\\Models\\ExpenseItem	87	\N	\N	{"attributes":{"id":87,"expense_id":33,"document_type":"ticket","document_number":"D-5840","vendor_name":"Proveedor wiegand-","description":"Gasto 4 para Yoshiko","amount":"46125.00","expense_date":"2024-12-28T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":3}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
242	default	updated	App\\Models\\Expense	33	\N	\N	{"attributes":{"total_amount":"278990.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
243	default	updated	App\\Models\\Expense	33	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:33.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
244	default	created	App\\Models\\Expense	34	\N	\N	{"attributes":{"id":34,"expense_number":"RND-2025-000034","account_id":18,"submitted_by":16,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Yoshiko Wiegand McClure","expense_date":"2025-09-14T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-14T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
245	default	created	App\\Models\\ExpenseItem	88	\N	\N	{"attributes":{"id":88,"expense_id":34,"document_type":"ticket","document_number":"D-6181","vendor_name":"Proveedor wiegand-","description":"Gasto 0 para Yoshiko","amount":"48410.00","expense_date":"2025-09-14T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":6}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
246	default	created	App\\Models\\ExpenseItem	89	\N	\N	{"attributes":{"id":89,"expense_id":34,"document_type":"ticket","document_number":"D-4707","vendor_name":"Proveedor wiegand-","description":"Gasto 1 para Yoshiko","amount":"10023.00","expense_date":"2025-09-14T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":3}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
247	default	created	App\\Models\\ExpenseItem	90	\N	\N	{"attributes":{"id":90,"expense_id":34,"document_type":"ticket","document_number":"D-3446","vendor_name":"Proveedor wiegand-","description":"Gasto 2 para Yoshiko","amount":"9066.00","expense_date":"2025-09-14T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":4}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
248	default	created	App\\Models\\ExpenseItem	91	\N	\N	{"attributes":{"id":91,"expense_id":34,"document_type":"ticket","document_number":"D-2311","vendor_name":"Proveedor wiegand-","description":"Gasto 3 para Yoshiko","amount":"65858.00","expense_date":"2025-09-14T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":7}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
249	default	created	App\\Models\\ExpenseItem	92	\N	\N	{"attributes":{"id":92,"expense_id":34,"document_type":"ticket","document_number":"D-6185","vendor_name":"Proveedor wiegand-","description":"Gasto 4 para Yoshiko","amount":"109062.00","expense_date":"2025-09-14T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":10}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
250	default	updated	App\\Models\\Expense	34	\N	\N	{"attributes":{"total_amount":"242419.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
251	default	created	App\\Models\\Expense	35	\N	\N	{"attributes":{"id":35,"expense_number":"RND-2025-000035","account_id":19,"submitted_by":17,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Cesar Marks Denesik","expense_date":"2025-08-05T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-08-05T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
252	default	created	App\\Models\\ExpenseItem	93	\N	\N	{"attributes":{"id":93,"expense_id":35,"document_type":"ticket","document_number":"D-3251","vendor_name":"Proveedor marks-de","description":"Gasto 0 para Cesar","amount":"31626.00","expense_date":"2025-08-05T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":4}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
253	default	updated	App\\Models\\Expense	35	\N	\N	{"attributes":{"total_amount":"31626.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	updated	\N
254	default	created	App\\Models\\Expense	36	\N	\N	{"attributes":{"id":36,"expense_number":"RND-2025-000036","account_id":19,"submitted_by":17,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Cesar Marks Denesik","expense_date":"2024-11-21T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-11-21T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
255	default	created	App\\Models\\ExpenseItem	94	\N	\N	{"attributes":{"id":94,"expense_id":36,"document_type":"ticket","document_number":"D-9982","vendor_name":"Proveedor marks-de","description":"Gasto 0 para Cesar","amount":"100238.00","expense_date":"2024-11-21T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":2}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
256	default	created	App\\Models\\ExpenseItem	95	\N	\N	{"attributes":{"id":95,"expense_id":36,"document_type":"ticket","document_number":"D-3282","vendor_name":"Proveedor marks-de","description":"Gasto 1 para Cesar","amount":"28065.00","expense_date":"2024-11-21T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":1}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
257	default	created	App\\Models\\ExpenseItem	96	\N	\N	{"attributes":{"id":96,"expense_id":36,"document_type":"ticket","document_number":"D-2933","vendor_name":"Proveedor marks-de","description":"Gasto 2 para Cesar","amount":"24648.00","expense_date":"2024-11-21T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z","expense_category_id":3}}	2025-09-16 15:04:33	2025-09-16 15:04:33	created	\N
260	default	created	App\\Models\\Expense	37	\N	\N	{"attributes":{"id":37,"expense_number":"RND-2025-000037","account_id":19,"submitted_by":17,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Cesar Marks Denesik","expense_date":"2025-01-12T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-01-12T15:04:33.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:33.000000Z","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
261	default	created	App\\Models\\ExpenseItem	97	\N	\N	{"attributes":{"id":97,"expense_id":37,"document_type":"ticket","document_number":"D-6231","vendor_name":"Proveedor marks-de","description":"Gasto 0 para Cesar","amount":"61900.00","expense_date":"2025-01-12T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":3}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
262	default	created	App\\Models\\ExpenseItem	98	\N	\N	{"attributes":{"id":98,"expense_id":37,"document_type":"ticket","document_number":"D-4282","vendor_name":"Proveedor marks-de","description":"Gasto 1 para Cesar","amount":"50522.00","expense_date":"2025-01-12T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":5}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
263	default	created	App\\Models\\ExpenseItem	99	\N	\N	{"attributes":{"id":99,"expense_id":37,"document_type":"ticket","document_number":"D-9351","vendor_name":"Proveedor marks-de","description":"Gasto 2 para Cesar","amount":"85464.00","expense_date":"2025-01-12T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":4}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
264	default	updated	App\\Models\\Expense	37	\N	\N	{"attributes":{"total_amount":"197886.00","updated_at":"2025-09-16T15:04:34.000000Z"},"old":{"total_amount":"0.00","updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
265	default	created	App\\Models\\Expense	38	\N	\N	{"attributes":{"id":38,"expense_number":"RND-2025-000038","account_id":19,"submitted_by":17,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Cesar Marks Denesik","expense_date":"2025-02-18T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-02-18T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
266	default	created	App\\Models\\ExpenseItem	100	\N	\N	{"attributes":{"id":100,"expense_id":38,"document_type":"ticket","document_number":"D-1490","vendor_name":"Proveedor marks-de","description":"Gasto 0 para Cesar","amount":"67825.00","expense_date":"2025-02-18T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":9}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
267	default	created	App\\Models\\ExpenseItem	101	\N	\N	{"attributes":{"id":101,"expense_id":38,"document_type":"ticket","document_number":"D-2095","vendor_name":"Proveedor marks-de","description":"Gasto 1 para Cesar","amount":"55532.00","expense_date":"2025-02-18T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":1}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
268	default	created	App\\Models\\ExpenseItem	102	\N	\N	{"attributes":{"id":102,"expense_id":38,"document_type":"ticket","document_number":"D-8628","vendor_name":"Proveedor marks-de","description":"Gasto 2 para Cesar","amount":"44902.00","expense_date":"2025-02-18T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":3}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
269	default	created	App\\Models\\ExpenseItem	103	\N	\N	{"attributes":{"id":103,"expense_id":38,"document_type":"ticket","document_number":"D-5207","vendor_name":"Proveedor marks-de","description":"Gasto 3 para Cesar","amount":"78131.00","expense_date":"2025-02-18T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":7}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
270	default	updated	App\\Models\\Expense	38	\N	\N	{"attributes":{"total_amount":"246390.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
271	default	updated	App\\Models\\Expense	38	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
272	default	created	App\\Models\\Expense	39	\N	\N	{"attributes":{"id":39,"expense_number":"RND-2025-000039","account_id":20,"submitted_by":18,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Estella Harvey Witting","expense_date":"2025-01-24T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-01-24T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
273	default	created	App\\Models\\ExpenseItem	104	\N	\N	{"attributes":{"id":104,"expense_id":39,"document_type":"ticket","document_number":"D-5135","vendor_name":"Proveedor harvey-w","description":"Gasto 0 para Estella","amount":"19412.00","expense_date":"2025-01-24T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":9}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
274	default	updated	App\\Models\\Expense	39	\N	\N	{"attributes":{"total_amount":"19412.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
275	default	created	App\\Models\\Expense	40	\N	\N	{"attributes":{"id":40,"expense_number":"RND-2025-000040","account_id":20,"submitted_by":18,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Estella Harvey Witting","expense_date":"2025-08-31T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-08-31T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
276	default	created	App\\Models\\ExpenseItem	105	\N	\N	{"attributes":{"id":105,"expense_id":40,"document_type":"ticket","document_number":"D-4491","vendor_name":"Proveedor harvey-w","description":"Gasto 0 para Estella","amount":"16056.00","expense_date":"2025-08-31T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":8}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
314	default	updated	App\\Models\\Expense	46	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
277	default	created	App\\Models\\ExpenseItem	106	\N	\N	{"attributes":{"id":106,"expense_id":40,"document_type":"ticket","document_number":"D-5660","vendor_name":"Proveedor harvey-w","description":"Gasto 1 para Estella","amount":"32399.00","expense_date":"2025-08-31T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":10}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
278	default	created	App\\Models\\ExpenseItem	107	\N	\N	{"attributes":{"id":107,"expense_id":40,"document_type":"ticket","document_number":"D-3927","vendor_name":"Proveedor harvey-w","description":"Gasto 2 para Estella","amount":"66553.00","expense_date":"2025-08-31T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":1}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
279	default	created	App\\Models\\ExpenseItem	108	\N	\N	{"attributes":{"id":108,"expense_id":40,"document_type":"ticket","document_number":"D-6655","vendor_name":"Proveedor harvey-w","description":"Gasto 3 para Estella","amount":"6721.00","expense_date":"2025-08-31T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":2}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
280	default	created	App\\Models\\ExpenseItem	109	\N	\N	{"attributes":{"id":109,"expense_id":40,"document_type":"ticket","document_number":"D-3363","vendor_name":"Proveedor harvey-w","description":"Gasto 4 para Estella","amount":"97779.00","expense_date":"2025-08-31T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":3}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
281	default	updated	App\\Models\\Expense	40	\N	\N	{"attributes":{"total_amount":"219508.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
282	default	created	App\\Models\\Expense	41	\N	\N	{"attributes":{"id":41,"expense_number":"RND-2025-000041","account_id":20,"submitted_by":18,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Estella Harvey Witting","expense_date":"2025-01-04T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-01-04T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
283	default	created	App\\Models\\ExpenseItem	110	\N	\N	{"attributes":{"id":110,"expense_id":41,"document_type":"ticket","document_number":"D-5063","vendor_name":"Proveedor harvey-w","description":"Gasto 0 para Estella","amount":"8227.00","expense_date":"2025-01-04T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":1}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
284	default	created	App\\Models\\ExpenseItem	111	\N	\N	{"attributes":{"id":111,"expense_id":41,"document_type":"ticket","document_number":"D-9891","vendor_name":"Proveedor harvey-w","description":"Gasto 1 para Estella","amount":"118027.00","expense_date":"2025-01-04T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":4}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
285	default	created	App\\Models\\ExpenseItem	112	\N	\N	{"attributes":{"id":112,"expense_id":41,"document_type":"ticket","document_number":"D-2131","vendor_name":"Proveedor harvey-w","description":"Gasto 2 para Estella","amount":"32799.00","expense_date":"2025-01-04T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":4}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
286	default	created	App\\Models\\ExpenseItem	113	\N	\N	{"attributes":{"id":113,"expense_id":41,"document_type":"ticket","document_number":"D-9482","vendor_name":"Proveedor harvey-w","description":"Gasto 3 para Estella","amount":"32701.00","expense_date":"2025-01-04T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":10}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
287	default	created	App\\Models\\ExpenseItem	114	\N	\N	{"attributes":{"id":114,"expense_id":41,"document_type":"ticket","document_number":"D-3204","vendor_name":"Proveedor harvey-w","description":"Gasto 4 para Estella","amount":"35124.00","expense_date":"2025-01-04T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":6}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
288	default	updated	App\\Models\\Expense	41	\N	\N	{"attributes":{"total_amount":"226878.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
289	default	created	App\\Models\\Expense	42	\N	\N	{"attributes":{"id":42,"expense_number":"RND-2025-000042","account_id":20,"submitted_by":18,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Estella Harvey Witting","expense_date":"2024-09-18T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-09-18T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
290	default	created	App\\Models\\ExpenseItem	115	\N	\N	{"attributes":{"id":115,"expense_id":42,"document_type":"ticket","document_number":"D-6637","vendor_name":"Proveedor harvey-w","description":"Gasto 0 para Estella","amount":"1047.00","expense_date":"2024-09-18T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":9}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
291	default	updated	App\\Models\\Expense	42	\N	\N	{"attributes":{"total_amount":"1047.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
292	default	updated	App\\Models\\Expense	42	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
293	default	created	App\\Models\\Expense	43	\N	\N	{"attributes":{"id":43,"expense_number":"RND-2025-000043","account_id":21,"submitted_by":19,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Julianne Schmeler Volkman","expense_date":"2025-02-22T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-02-22T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
294	default	created	App\\Models\\ExpenseItem	116	\N	\N	{"attributes":{"id":116,"expense_id":43,"document_type":"ticket","document_number":"D-2284","vendor_name":"Proveedor schmeler","description":"Gasto 0 para Julianne","amount":"115246.00","expense_date":"2025-02-22T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":6}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
295	default	created	App\\Models\\ExpenseItem	117	\N	\N	{"attributes":{"id":117,"expense_id":43,"document_type":"ticket","document_number":"D-9678","vendor_name":"Proveedor schmeler","description":"Gasto 1 para Julianne","amount":"47652.00","expense_date":"2025-02-22T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":5}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
296	default	created	App\\Models\\ExpenseItem	118	\N	\N	{"attributes":{"id":118,"expense_id":43,"document_type":"ticket","document_number":"D-1860","vendor_name":"Proveedor schmeler","description":"Gasto 2 para Julianne","amount":"46832.00","expense_date":"2025-02-22T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":5}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
297	default	created	App\\Models\\ExpenseItem	119	\N	\N	{"attributes":{"id":119,"expense_id":43,"document_type":"ticket","document_number":"D-3672","vendor_name":"Proveedor schmeler","description":"Gasto 3 para Julianne","amount":"96710.00","expense_date":"2025-02-22T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":9}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
298	default	created	App\\Models\\ExpenseItem	120	\N	\N	{"attributes":{"id":120,"expense_id":43,"document_type":"ticket","document_number":"D-4908","vendor_name":"Proveedor schmeler","description":"Gasto 4 para Julianne","amount":"7063.00","expense_date":"2025-02-22T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":2}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
299	default	updated	App\\Models\\Expense	43	\N	\N	{"attributes":{"total_amount":"313503.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
300	default	updated	App\\Models\\Expense	43	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
301	default	created	App\\Models\\Expense	44	\N	\N	{"attributes":{"id":44,"expense_number":"RND-2025-000044","account_id":22,"submitted_by":20,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Claude Simonis Marvin","expense_date":"2025-02-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-02-16T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
302	default	created	App\\Models\\ExpenseItem	121	\N	\N	{"attributes":{"id":121,"expense_id":44,"document_type":"ticket","document_number":"D-5551","vendor_name":"Proveedor simonis-","description":"Gasto 0 para Claude","amount":"111598.00","expense_date":"2025-02-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":10}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
303	default	created	App\\Models\\ExpenseItem	122	\N	\N	{"attributes":{"id":122,"expense_id":44,"document_type":"ticket","document_number":"D-3533","vendor_name":"Proveedor simonis-","description":"Gasto 1 para Claude","amount":"6008.00","expense_date":"2025-02-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":3}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
304	default	created	App\\Models\\ExpenseItem	123	\N	\N	{"attributes":{"id":123,"expense_id":44,"document_type":"ticket","document_number":"D-9057","vendor_name":"Proveedor simonis-","description":"Gasto 2 para Claude","amount":"65642.00","expense_date":"2025-02-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":3}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
305	default	updated	App\\Models\\Expense	44	\N	\N	{"attributes":{"total_amount":"183248.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
306	default	created	App\\Models\\Expense	45	\N	\N	{"attributes":{"id":45,"expense_number":"RND-2025-000045","account_id":22,"submitted_by":20,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Claude Simonis Marvin","expense_date":"2025-01-10T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-01-10T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
307	default	created	App\\Models\\ExpenseItem	124	\N	\N	{"attributes":{"id":124,"expense_id":45,"document_type":"ticket","document_number":"D-2764","vendor_name":"Proveedor simonis-","description":"Gasto 0 para Claude","amount":"89996.00","expense_date":"2025-01-10T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":9}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
308	default	created	App\\Models\\ExpenseItem	125	\N	\N	{"attributes":{"id":125,"expense_id":45,"document_type":"ticket","document_number":"D-1974","vendor_name":"Proveedor simonis-","description":"Gasto 1 para Claude","amount":"101217.00","expense_date":"2025-01-10T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":4}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
309	default	updated	App\\Models\\Expense	45	\N	\N	{"attributes":{"total_amount":"191213.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
310	default	updated	App\\Models\\Expense	45	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
311	default	created	App\\Models\\Expense	46	\N	\N	{"attributes":{"id":46,"expense_number":"RND-2025-000046","account_id":22,"submitted_by":20,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Claude Simonis Marvin","expense_date":"2024-12-02T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-12-02T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
312	default	created	App\\Models\\ExpenseItem	126	\N	\N	{"attributes":{"id":126,"expense_id":46,"document_type":"ticket","document_number":"D-4297","vendor_name":"Proveedor simonis-","description":"Gasto 0 para Claude","amount":"73613.00","expense_date":"2024-12-02T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":3}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
313	default	updated	App\\Models\\Expense	46	\N	\N	{"attributes":{"total_amount":"73613.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
315	default	created	App\\Models\\Expense	47	\N	\N	{"attributes":{"id":47,"expense_number":"RND-2025-000047","account_id":23,"submitted_by":21,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Katlynn Grimes Hilpert","expense_date":"2025-07-27T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-07-27T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
316	default	created	App\\Models\\ExpenseItem	127	\N	\N	{"attributes":{"id":127,"expense_id":47,"document_type":"ticket","document_number":"D-8124","vendor_name":"Proveedor grimes-h","description":"Gasto 0 para Katlynn","amount":"94521.00","expense_date":"2025-07-27T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":4}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
317	default	created	App\\Models\\ExpenseItem	128	\N	\N	{"attributes":{"id":128,"expense_id":47,"document_type":"ticket","document_number":"D-4182","vendor_name":"Proveedor grimes-h","description":"Gasto 1 para Katlynn","amount":"4420.00","expense_date":"2025-07-27T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":8}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
318	default	updated	App\\Models\\Expense	47	\N	\N	{"attributes":{"total_amount":"98941.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
319	default	updated	App\\Models\\Expense	47	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
320	default	created	App\\Models\\Expense	48	\N	\N	{"attributes":{"id":48,"expense_number":"RND-2025-000048","account_id":23,"submitted_by":21,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Katlynn Grimes Hilpert","expense_date":"2024-10-19T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-10-19T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
321	default	created	App\\Models\\ExpenseItem	129	\N	\N	{"attributes":{"id":129,"expense_id":48,"document_type":"ticket","document_number":"D-8333","vendor_name":"Proveedor grimes-h","description":"Gasto 0 para Katlynn","amount":"96034.00","expense_date":"2024-10-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":2}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
322	default	created	App\\Models\\ExpenseItem	130	\N	\N	{"attributes":{"id":130,"expense_id":48,"document_type":"ticket","document_number":"D-7562","vendor_name":"Proveedor grimes-h","description":"Gasto 1 para Katlynn","amount":"95856.00","expense_date":"2024-10-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":1}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
323	default	updated	App\\Models\\Expense	48	\N	\N	{"attributes":{"total_amount":"191890.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
324	default	created	App\\Models\\Expense	49	\N	\N	{"attributes":{"id":49,"expense_number":"RND-2025-000049","account_id":24,"submitted_by":22,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Charles Swaniawski Williamson","expense_date":"2025-08-19T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-08-19T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
325	default	created	App\\Models\\ExpenseItem	131	\N	\N	{"attributes":{"id":131,"expense_id":49,"document_type":"ticket","document_number":"D-8866","vendor_name":"Proveedor swaniaws","description":"Gasto 0 para Charles","amount":"18330.00","expense_date":"2025-08-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":5}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
326	default	updated	App\\Models\\Expense	49	\N	\N	{"attributes":{"total_amount":"18330.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
327	default	created	App\\Models\\Expense	50	\N	\N	{"attributes":{"id":50,"expense_number":"RND-2025-000050","account_id":25,"submitted_by":23,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Otha McLaughlin Cruickshank","expense_date":"2025-07-24T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-07-24T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
328	default	created	App\\Models\\ExpenseItem	132	\N	\N	{"attributes":{"id":132,"expense_id":50,"document_type":"ticket","document_number":"D-3873","vendor_name":"Proveedor mclaughl","description":"Gasto 0 para Otha","amount":"112377.00","expense_date":"2025-07-24T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":10}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
329	default	created	App\\Models\\ExpenseItem	133	\N	\N	{"attributes":{"id":133,"expense_id":50,"document_type":"ticket","document_number":"D-8689","vendor_name":"Proveedor mclaughl","description":"Gasto 1 para Otha","amount":"12535.00","expense_date":"2025-07-24T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":9}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
330	default	created	App\\Models\\ExpenseItem	134	\N	\N	{"attributes":{"id":134,"expense_id":50,"document_type":"ticket","document_number":"D-5536","vendor_name":"Proveedor mclaughl","description":"Gasto 2 para Otha","amount":"32443.00","expense_date":"2025-07-24T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":10}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
331	default	created	App\\Models\\ExpenseItem	135	\N	\N	{"attributes":{"id":135,"expense_id":50,"document_type":"ticket","document_number":"D-5735","vendor_name":"Proveedor mclaughl","description":"Gasto 3 para Otha","amount":"84814.00","expense_date":"2025-07-24T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":6}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
332	default	updated	App\\Models\\Expense	50	\N	\N	{"attributes":{"total_amount":"242169.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
333	default	updated	App\\Models\\Expense	50	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
334	default	created	App\\Models\\Expense	51	\N	\N	{"attributes":{"id":51,"expense_number":"RND-2025-000051","account_id":25,"submitted_by":23,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Otha McLaughlin Cruickshank","expense_date":"2025-08-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-08-16T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
335	default	created	App\\Models\\ExpenseItem	136	\N	\N	{"attributes":{"id":136,"expense_id":51,"document_type":"ticket","document_number":"D-7050","vendor_name":"Proveedor mclaughl","description":"Gasto 0 para Otha","amount":"103835.00","expense_date":"2025-08-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":3}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
336	default	created	App\\Models\\ExpenseItem	137	\N	\N	{"attributes":{"id":137,"expense_id":51,"document_type":"ticket","document_number":"D-9788","vendor_name":"Proveedor mclaughl","description":"Gasto 1 para Otha","amount":"84620.00","expense_date":"2025-08-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":4}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
337	default	created	App\\Models\\ExpenseItem	138	\N	\N	{"attributes":{"id":138,"expense_id":51,"document_type":"ticket","document_number":"D-6830","vendor_name":"Proveedor mclaughl","description":"Gasto 2 para Otha","amount":"30994.00","expense_date":"2025-08-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":8}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
338	default	created	App\\Models\\ExpenseItem	139	\N	\N	{"attributes":{"id":139,"expense_id":51,"document_type":"ticket","document_number":"D-9248","vendor_name":"Proveedor mclaughl","description":"Gasto 3 para Otha","amount":"85426.00","expense_date":"2025-08-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":6}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
339	default	updated	App\\Models\\Expense	51	\N	\N	{"attributes":{"total_amount":"304875.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
340	default	updated	App\\Models\\Expense	51	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
341	default	created	App\\Models\\Expense	52	\N	\N	{"attributes":{"id":52,"expense_number":"RND-2025-000052","account_id":25,"submitted_by":23,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Otha McLaughlin Cruickshank","expense_date":"2024-11-25T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-11-25T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
342	default	created	App\\Models\\ExpenseItem	140	\N	\N	{"attributes":{"id":140,"expense_id":52,"document_type":"ticket","document_number":"D-5711","vendor_name":"Proveedor mclaughl","description":"Gasto 0 para Otha","amount":"18955.00","expense_date":"2024-11-25T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":6}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
343	default	updated	App\\Models\\Expense	52	\N	\N	{"attributes":{"total_amount":"18955.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
344	default	updated	App\\Models\\Expense	52	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
345	default	created	App\\Models\\Expense	53	\N	\N	{"attributes":{"id":53,"expense_number":"RND-2025-000053","account_id":25,"submitted_by":23,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Otha McLaughlin Cruickshank","expense_date":"2025-06-20T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-06-20T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
346	default	created	App\\Models\\ExpenseItem	141	\N	\N	{"attributes":{"id":141,"expense_id":53,"document_type":"ticket","document_number":"D-8716","vendor_name":"Proveedor mclaughl","description":"Gasto 0 para Otha","amount":"27817.00","expense_date":"2025-06-20T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":8}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
347	default	created	App\\Models\\ExpenseItem	142	\N	\N	{"attributes":{"id":142,"expense_id":53,"document_type":"ticket","document_number":"D-7636","vendor_name":"Proveedor mclaughl","description":"Gasto 1 para Otha","amount":"6041.00","expense_date":"2025-06-20T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":5}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
348	default	updated	App\\Models\\Expense	53	\N	\N	{"attributes":{"total_amount":"33858.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
349	default	updated	App\\Models\\Expense	53	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
350	default	created	App\\Models\\Expense	54	\N	\N	{"attributes":{"id":54,"expense_number":"RND-2025-000054","account_id":26,"submitted_by":24,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Frederick Simonis Abernathy","expense_date":"2024-08-26T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-08-26T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
351	default	created	App\\Models\\ExpenseItem	143	\N	\N	{"attributes":{"id":143,"expense_id":54,"document_type":"ticket","document_number":"D-2865","vendor_name":"Proveedor simonis-","description":"Gasto 0 para Frederick","amount":"50049.00","expense_date":"2024-08-26T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":7}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
352	default	created	App\\Models\\ExpenseItem	144	\N	\N	{"attributes":{"id":144,"expense_id":54,"document_type":"ticket","document_number":"D-3362","vendor_name":"Proveedor simonis-","description":"Gasto 1 para Frederick","amount":"110386.00","expense_date":"2024-08-26T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":1}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
353	default	created	App\\Models\\ExpenseItem	145	\N	\N	{"attributes":{"id":145,"expense_id":54,"document_type":"ticket","document_number":"D-2939","vendor_name":"Proveedor simonis-","description":"Gasto 2 para Frederick","amount":"7194.00","expense_date":"2024-08-26T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":2}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
354	default	created	App\\Models\\ExpenseItem	146	\N	\N	{"attributes":{"id":146,"expense_id":54,"document_type":"ticket","document_number":"D-5284","vendor_name":"Proveedor simonis-","description":"Gasto 3 para Frederick","amount":"39331.00","expense_date":"2024-08-26T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":7}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
355	default	updated	App\\Models\\Expense	54	\N	\N	{"attributes":{"total_amount":"206960.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
356	default	created	App\\Models\\Expense	55	\N	\N	{"attributes":{"id":55,"expense_number":"RND-2025-000055","account_id":26,"submitted_by":24,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Frederick Simonis Abernathy","expense_date":"2025-01-11T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-01-11T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
357	default	created	App\\Models\\ExpenseItem	147	\N	\N	{"attributes":{"id":147,"expense_id":55,"document_type":"ticket","document_number":"D-9382","vendor_name":"Proveedor simonis-","description":"Gasto 0 para Frederick","amount":"61965.00","expense_date":"2025-01-11T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":6}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
358	default	updated	App\\Models\\Expense	55	\N	\N	{"attributes":{"total_amount":"61965.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
359	default	updated	App\\Models\\Expense	55	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
360	default	created	App\\Models\\Expense	56	\N	\N	{"attributes":{"id":56,"expense_number":"RND-2025-000056","account_id":27,"submitted_by":25,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Alexa Christiansen Wiegand","expense_date":"2025-02-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-02-16T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
361	default	created	App\\Models\\ExpenseItem	148	\N	\N	{"attributes":{"id":148,"expense_id":56,"document_type":"ticket","document_number":"D-4128","vendor_name":"Proveedor christia","description":"Gasto 0 para Alexa","amount":"22854.00","expense_date":"2025-02-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":3}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
362	default	created	App\\Models\\ExpenseItem	149	\N	\N	{"attributes":{"id":149,"expense_id":56,"document_type":"ticket","document_number":"D-7419","vendor_name":"Proveedor christia","description":"Gasto 1 para Alexa","amount":"92831.00","expense_date":"2025-02-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":4}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
363	default	updated	App\\Models\\Expense	56	\N	\N	{"attributes":{"total_amount":"115685.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
364	default	updated	App\\Models\\Expense	56	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
365	default	created	App\\Models\\Expense	57	\N	\N	{"attributes":{"id":57,"expense_number":"RND-2025-000057","account_id":27,"submitted_by":25,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Alexa Christiansen Wiegand","expense_date":"2024-09-21T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-09-21T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
366	default	created	App\\Models\\ExpenseItem	150	\N	\N	{"attributes":{"id":150,"expense_id":57,"document_type":"ticket","document_number":"D-2957","vendor_name":"Proveedor christia","description":"Gasto 0 para Alexa","amount":"63339.00","expense_date":"2024-09-21T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":10}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
367	default	created	App\\Models\\ExpenseItem	151	\N	\N	{"attributes":{"id":151,"expense_id":57,"document_type":"ticket","document_number":"D-3756","vendor_name":"Proveedor christia","description":"Gasto 1 para Alexa","amount":"25552.00","expense_date":"2024-09-21T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":5}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
368	default	created	App\\Models\\ExpenseItem	152	\N	\N	{"attributes":{"id":152,"expense_id":57,"document_type":"ticket","document_number":"D-3082","vendor_name":"Proveedor christia","description":"Gasto 2 para Alexa","amount":"37882.00","expense_date":"2024-09-21T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":9}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
369	default	updated	App\\Models\\Expense	57	\N	\N	{"attributes":{"total_amount":"126773.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
406	default	updated	App\\Models\\Expense	63	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
370	default	created	App\\Models\\Expense	58	\N	\N	{"attributes":{"id":58,"expense_number":"RND-2025-000058","account_id":28,"submitted_by":26,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Madonna Dooley Paucek","expense_date":"2024-09-19T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-09-19T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
371	default	created	App\\Models\\ExpenseItem	153	\N	\N	{"attributes":{"id":153,"expense_id":58,"document_type":"ticket","document_number":"D-6888","vendor_name":"Proveedor dooley-p","description":"Gasto 0 para Madonna","amount":"93721.00","expense_date":"2024-09-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":9}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
372	default	created	App\\Models\\ExpenseItem	154	\N	\N	{"attributes":{"id":154,"expense_id":58,"document_type":"ticket","document_number":"D-4741","vendor_name":"Proveedor dooley-p","description":"Gasto 1 para Madonna","amount":"42090.00","expense_date":"2024-09-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":6}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
373	default	created	App\\Models\\ExpenseItem	155	\N	\N	{"attributes":{"id":155,"expense_id":58,"document_type":"ticket","document_number":"D-2772","vendor_name":"Proveedor dooley-p","description":"Gasto 2 para Madonna","amount":"80911.00","expense_date":"2024-09-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":3}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
374	default	created	App\\Models\\ExpenseItem	156	\N	\N	{"attributes":{"id":156,"expense_id":58,"document_type":"ticket","document_number":"D-2298","vendor_name":"Proveedor dooley-p","description":"Gasto 3 para Madonna","amount":"89603.00","expense_date":"2024-09-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":3}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
375	default	updated	App\\Models\\Expense	58	\N	\N	{"attributes":{"total_amount":"306325.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
376	default	updated	App\\Models\\Expense	58	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
377	default	created	App\\Models\\Expense	59	\N	\N	{"attributes":{"id":59,"expense_number":"RND-2025-000059","account_id":28,"submitted_by":26,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Madonna Dooley Paucek","expense_date":"2025-05-26T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-05-26T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
378	default	created	App\\Models\\ExpenseItem	157	\N	\N	{"attributes":{"id":157,"expense_id":59,"document_type":"ticket","document_number":"D-2981","vendor_name":"Proveedor dooley-p","description":"Gasto 0 para Madonna","amount":"89077.00","expense_date":"2025-05-26T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":4}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
379	default	updated	App\\Models\\Expense	59	\N	\N	{"attributes":{"total_amount":"89077.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
380	default	updated	App\\Models\\Expense	59	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
381	default	created	App\\Models\\Expense	60	\N	\N	{"attributes":{"id":60,"expense_number":"RND-2025-000060","account_id":28,"submitted_by":26,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Madonna Dooley Paucek","expense_date":"2025-04-02T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-04-02T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
382	default	created	App\\Models\\ExpenseItem	158	\N	\N	{"attributes":{"id":158,"expense_id":60,"document_type":"ticket","document_number":"D-7619","vendor_name":"Proveedor dooley-p","description":"Gasto 0 para Madonna","amount":"117702.00","expense_date":"2025-04-02T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":8}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
383	default	created	App\\Models\\ExpenseItem	159	\N	\N	{"attributes":{"id":159,"expense_id":60,"document_type":"ticket","document_number":"D-7853","vendor_name":"Proveedor dooley-p","description":"Gasto 1 para Madonna","amount":"28217.00","expense_date":"2025-04-02T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":1}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
384	default	created	App\\Models\\ExpenseItem	160	\N	\N	{"attributes":{"id":160,"expense_id":60,"document_type":"ticket","document_number":"D-7290","vendor_name":"Proveedor dooley-p","description":"Gasto 2 para Madonna","amount":"27095.00","expense_date":"2025-04-02T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":5}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
385	default	created	App\\Models\\ExpenseItem	161	\N	\N	{"attributes":{"id":161,"expense_id":60,"document_type":"ticket","document_number":"D-8131","vendor_name":"Proveedor dooley-p","description":"Gasto 3 para Madonna","amount":"21894.00","expense_date":"2025-04-02T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":7}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
386	default	updated	App\\Models\\Expense	60	\N	\N	{"attributes":{"total_amount":"194908.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
387	default	updated	App\\Models\\Expense	60	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
444	default	updated	App\\Models\\Expense	71	\N	\N	{"attributes":{"total_amount":"18404.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
388	default	created	App\\Models\\Expense	61	\N	\N	{"attributes":{"id":61,"expense_number":"RND-2025-000061","account_id":29,"submitted_by":27,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Abagail Ebert Ortiz","expense_date":"2025-07-11T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-07-11T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
389	default	created	App\\Models\\ExpenseItem	162	\N	\N	{"attributes":{"id":162,"expense_id":61,"document_type":"ticket","document_number":"D-1317","vendor_name":"Proveedor ebert-or","description":"Gasto 0 para Abagail","amount":"31989.00","expense_date":"2025-07-11T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":6}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
390	default	created	App\\Models\\ExpenseItem	163	\N	\N	{"attributes":{"id":163,"expense_id":61,"document_type":"ticket","document_number":"D-3544","vendor_name":"Proveedor ebert-or","description":"Gasto 1 para Abagail","amount":"78775.00","expense_date":"2025-07-11T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":5}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
391	default	created	App\\Models\\ExpenseItem	164	\N	\N	{"attributes":{"id":164,"expense_id":61,"document_type":"ticket","document_number":"D-3619","vendor_name":"Proveedor ebert-or","description":"Gasto 2 para Abagail","amount":"73698.00","expense_date":"2025-07-11T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":9}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
392	default	created	App\\Models\\ExpenseItem	165	\N	\N	{"attributes":{"id":165,"expense_id":61,"document_type":"ticket","document_number":"D-3460","vendor_name":"Proveedor ebert-or","description":"Gasto 3 para Abagail","amount":"59962.00","expense_date":"2025-07-11T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":2}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
393	default	updated	App\\Models\\Expense	61	\N	\N	{"attributes":{"total_amount":"244424.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
394	default	updated	App\\Models\\Expense	61	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
395	default	created	App\\Models\\Expense	62	\N	\N	{"attributes":{"id":62,"expense_number":"RND-2025-000062","account_id":29,"submitted_by":27,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Abagail Ebert Ortiz","expense_date":"2024-11-25T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-11-25T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
396	default	created	App\\Models\\ExpenseItem	166	\N	\N	{"attributes":{"id":166,"expense_id":62,"document_type":"ticket","document_number":"D-8924","vendor_name":"Proveedor ebert-or","description":"Gasto 0 para Abagail","amount":"34986.00","expense_date":"2024-11-25T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":8}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
397	default	created	App\\Models\\ExpenseItem	167	\N	\N	{"attributes":{"id":167,"expense_id":62,"document_type":"ticket","document_number":"D-5954","vendor_name":"Proveedor ebert-or","description":"Gasto 1 para Abagail","amount":"36909.00","expense_date":"2024-11-25T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":10}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
398	default	updated	App\\Models\\Expense	62	\N	\N	{"attributes":{"total_amount":"71895.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
399	default	created	App\\Models\\Expense	63	\N	\N	{"attributes":{"id":63,"expense_number":"RND-2025-000063","account_id":29,"submitted_by":27,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Abagail Ebert Ortiz","expense_date":"2024-10-12T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-10-12T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
400	default	created	App\\Models\\ExpenseItem	168	\N	\N	{"attributes":{"id":168,"expense_id":63,"document_type":"ticket","document_number":"D-2815","vendor_name":"Proveedor ebert-or","description":"Gasto 0 para Abagail","amount":"20067.00","expense_date":"2024-10-12T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":1}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
401	default	created	App\\Models\\ExpenseItem	169	\N	\N	{"attributes":{"id":169,"expense_id":63,"document_type":"ticket","document_number":"D-9051","vendor_name":"Proveedor ebert-or","description":"Gasto 1 para Abagail","amount":"66236.00","expense_date":"2024-10-12T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":6}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
402	default	created	App\\Models\\ExpenseItem	170	\N	\N	{"attributes":{"id":170,"expense_id":63,"document_type":"ticket","document_number":"D-8858","vendor_name":"Proveedor ebert-or","description":"Gasto 2 para Abagail","amount":"1636.00","expense_date":"2024-10-12T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":4}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
403	default	created	App\\Models\\ExpenseItem	171	\N	\N	{"attributes":{"id":171,"expense_id":63,"document_type":"ticket","document_number":"D-4101","vendor_name":"Proveedor ebert-or","description":"Gasto 3 para Abagail","amount":"3251.00","expense_date":"2024-10-12T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":7}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
404	default	created	App\\Models\\ExpenseItem	172	\N	\N	{"attributes":{"id":172,"expense_id":63,"document_type":"ticket","document_number":"D-2523","vendor_name":"Proveedor ebert-or","description":"Gasto 4 para Abagail","amount":"24068.00","expense_date":"2024-10-12T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":4}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
405	default	updated	App\\Models\\Expense	63	\N	\N	{"attributes":{"total_amount":"115258.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
407	default	created	App\\Models\\Expense	64	\N	\N	{"attributes":{"id":64,"expense_number":"RND-2025-000064","account_id":29,"submitted_by":27,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Abagail Ebert Ortiz","expense_date":"2024-11-13T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-11-13T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
408	default	created	App\\Models\\ExpenseItem	173	\N	\N	{"attributes":{"id":173,"expense_id":64,"document_type":"ticket","document_number":"D-6929","vendor_name":"Proveedor ebert-or","description":"Gasto 0 para Abagail","amount":"40222.00","expense_date":"2024-11-13T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":3}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
409	default	updated	App\\Models\\Expense	64	\N	\N	{"attributes":{"total_amount":"40222.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
410	default	updated	App\\Models\\Expense	64	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
411	default	created	App\\Models\\Expense	65	\N	\N	{"attributes":{"id":65,"expense_number":"RND-2025-000065","account_id":30,"submitted_by":28,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Mireya Terry Kihn","expense_date":"2025-04-14T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-04-14T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
412	default	created	App\\Models\\ExpenseItem	174	\N	\N	{"attributes":{"id":174,"expense_id":65,"document_type":"ticket","document_number":"D-5838","vendor_name":"Proveedor terry-ki","description":"Gasto 0 para Mireya","amount":"56412.00","expense_date":"2025-04-14T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":10}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
413	default	created	App\\Models\\ExpenseItem	175	\N	\N	{"attributes":{"id":175,"expense_id":65,"document_type":"ticket","document_number":"D-9270","vendor_name":"Proveedor terry-ki","description":"Gasto 1 para Mireya","amount":"94487.00","expense_date":"2025-04-14T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":5}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
414	default	updated	App\\Models\\Expense	65	\N	\N	{"attributes":{"total_amount":"150899.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
415	default	updated	App\\Models\\Expense	65	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
416	default	created	App\\Models\\Expense	66	\N	\N	{"attributes":{"id":66,"expense_number":"RND-2025-000066","account_id":30,"submitted_by":28,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Mireya Terry Kihn","expense_date":"2025-07-12T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-07-12T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
417	default	created	App\\Models\\ExpenseItem	176	\N	\N	{"attributes":{"id":176,"expense_id":66,"document_type":"ticket","document_number":"D-6634","vendor_name":"Proveedor terry-ki","description":"Gasto 0 para Mireya","amount":"36078.00","expense_date":"2025-07-12T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":1}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
418	default	created	App\\Models\\ExpenseItem	177	\N	\N	{"attributes":{"id":177,"expense_id":66,"document_type":"ticket","document_number":"D-6059","vendor_name":"Proveedor terry-ki","description":"Gasto 1 para Mireya","amount":"42072.00","expense_date":"2025-07-12T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":5}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
419	default	updated	App\\Models\\Expense	66	\N	\N	{"attributes":{"total_amount":"78150.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
420	default	updated	App\\Models\\Expense	66	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
421	default	created	App\\Models\\Expense	67	\N	\N	{"attributes":{"id":67,"expense_number":"RND-2025-000067","account_id":30,"submitted_by":28,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Mireya Terry Kihn","expense_date":"2025-01-09T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-01-09T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
422	default	created	App\\Models\\ExpenseItem	178	\N	\N	{"attributes":{"id":178,"expense_id":67,"document_type":"ticket","document_number":"D-5403","vendor_name":"Proveedor terry-ki","description":"Gasto 0 para Mireya","amount":"38336.00","expense_date":"2025-01-09T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":7}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
423	default	created	App\\Models\\ExpenseItem	179	\N	\N	{"attributes":{"id":179,"expense_id":67,"document_type":"ticket","document_number":"D-4693","vendor_name":"Proveedor terry-ki","description":"Gasto 1 para Mireya","amount":"11051.00","expense_date":"2025-01-09T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":10}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
424	default	updated	App\\Models\\Expense	67	\N	\N	{"attributes":{"total_amount":"49387.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
425	default	created	App\\Models\\Expense	68	\N	\N	{"attributes":{"id":68,"expense_number":"RND-2025-000068","account_id":30,"submitted_by":28,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Mireya Terry Kihn","expense_date":"2025-08-19T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-08-19T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
426	default	created	App\\Models\\ExpenseItem	180	\N	\N	{"attributes":{"id":180,"expense_id":68,"document_type":"ticket","document_number":"D-5422","vendor_name":"Proveedor terry-ki","description":"Gasto 0 para Mireya","amount":"65401.00","expense_date":"2025-08-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":7}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
427	default	created	App\\Models\\ExpenseItem	181	\N	\N	{"attributes":{"id":181,"expense_id":68,"document_type":"ticket","document_number":"D-4716","vendor_name":"Proveedor terry-ki","description":"Gasto 1 para Mireya","amount":"86941.00","expense_date":"2025-08-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":4}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
428	default	created	App\\Models\\ExpenseItem	182	\N	\N	{"attributes":{"id":182,"expense_id":68,"document_type":"ticket","document_number":"D-4823","vendor_name":"Proveedor terry-ki","description":"Gasto 2 para Mireya","amount":"69507.00","expense_date":"2025-08-19T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":2}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
429	default	updated	App\\Models\\Expense	68	\N	\N	{"attributes":{"total_amount":"221849.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
430	default	created	App\\Models\\Expense	69	\N	\N	{"attributes":{"id":69,"expense_number":"RND-2025-000069","account_id":31,"submitted_by":29,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Destiny Farrell Turner","expense_date":"2024-12-27T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-12-27T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
431	default	created	App\\Models\\ExpenseItem	183	\N	\N	{"attributes":{"id":183,"expense_id":69,"document_type":"ticket","document_number":"D-6344","vendor_name":"Proveedor farrell-","description":"Gasto 0 para Destiny","amount":"11800.00","expense_date":"2024-12-27T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":3}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
432	default	updated	App\\Models\\Expense	69	\N	\N	{"attributes":{"total_amount":"11800.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
433	default	updated	App\\Models\\Expense	69	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
434	default	created	App\\Models\\Expense	70	\N	\N	{"attributes":{"id":70,"expense_number":"RND-2025-000070","account_id":31,"submitted_by":29,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Destiny Farrell Turner","expense_date":"2024-11-07T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-11-07T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
435	default	created	App\\Models\\ExpenseItem	184	\N	\N	{"attributes":{"id":184,"expense_id":70,"document_type":"ticket","document_number":"D-8842","vendor_name":"Proveedor farrell-","description":"Gasto 0 para Destiny","amount":"6224.00","expense_date":"2024-11-07T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":7}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
436	default	created	App\\Models\\ExpenseItem	185	\N	\N	{"attributes":{"id":185,"expense_id":70,"document_type":"ticket","document_number":"D-9919","vendor_name":"Proveedor farrell-","description":"Gasto 1 para Destiny","amount":"110622.00","expense_date":"2024-11-07T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":9}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
437	default	created	App\\Models\\ExpenseItem	186	\N	\N	{"attributes":{"id":186,"expense_id":70,"document_type":"ticket","document_number":"D-5548","vendor_name":"Proveedor farrell-","description":"Gasto 2 para Destiny","amount":"76391.00","expense_date":"2024-11-07T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":2}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
438	default	created	App\\Models\\ExpenseItem	187	\N	\N	{"attributes":{"id":187,"expense_id":70,"document_type":"ticket","document_number":"D-8905","vendor_name":"Proveedor farrell-","description":"Gasto 3 para Destiny","amount":"83713.00","expense_date":"2024-11-07T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":10}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
439	default	created	App\\Models\\ExpenseItem	188	\N	\N	{"attributes":{"id":188,"expense_id":70,"document_type":"ticket","document_number":"D-4484","vendor_name":"Proveedor farrell-","description":"Gasto 4 para Destiny","amount":"83802.00","expense_date":"2024-11-07T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":2}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
440	default	updated	App\\Models\\Expense	70	\N	\N	{"attributes":{"total_amount":"360752.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
441	default	created	App\\Models\\Expense	71	\N	\N	{"attributes":{"id":71,"expense_number":"RND-2025-000071","account_id":32,"submitted_by":30,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Chloe Weimann McLaughlin","expense_date":"2025-06-25T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-06-25T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
442	default	created	App\\Models\\ExpenseItem	189	\N	\N	{"attributes":{"id":189,"expense_id":71,"document_type":"ticket","document_number":"D-6165","vendor_name":"Proveedor weimann-","description":"Gasto 0 para Chloe","amount":"9245.00","expense_date":"2025-06-25T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":6}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
443	default	created	App\\Models\\ExpenseItem	190	\N	\N	{"attributes":{"id":190,"expense_id":71,"document_type":"ticket","document_number":"D-7549","vendor_name":"Proveedor weimann-","description":"Gasto 1 para Chloe","amount":"9159.00","expense_date":"2025-06-25T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":5}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
445	default	updated	App\\Models\\Expense	71	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
446	default	created	App\\Models\\Expense	72	\N	\N	{"attributes":{"id":72,"expense_number":"RND-2025-000072","account_id":33,"submitted_by":31,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Tesorero Sistema","expense_date":"2025-01-15T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-01-15T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
447	default	created	App\\Models\\ExpenseItem	191	\N	\N	{"attributes":{"id":191,"expense_id":72,"document_type":"ticket","document_number":"D-3684","vendor_name":"Proveedor sistema","description":"Gasto 0 para Tesorero","amount":"76588.00","expense_date":"2025-01-15T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":6}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
448	default	created	App\\Models\\ExpenseItem	192	\N	\N	{"attributes":{"id":192,"expense_id":72,"document_type":"ticket","document_number":"D-3098","vendor_name":"Proveedor sistema","description":"Gasto 1 para Tesorero","amount":"21690.00","expense_date":"2025-01-15T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":1}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
449	default	updated	App\\Models\\Expense	72	\N	\N	{"attributes":{"total_amount":"98278.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
450	default	created	App\\Models\\Expense	73	\N	\N	{"attributes":{"id":73,"expense_number":"RND-2025-000073","account_id":33,"submitted_by":31,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Tesorero Sistema","expense_date":"2025-02-23T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-02-23T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
451	default	created	App\\Models\\ExpenseItem	193	\N	\N	{"attributes":{"id":193,"expense_id":73,"document_type":"ticket","document_number":"D-5195","vendor_name":"Proveedor sistema","description":"Gasto 0 para Tesorero","amount":"29429.00","expense_date":"2025-02-23T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":6}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
452	default	created	App\\Models\\ExpenseItem	194	\N	\N	{"attributes":{"id":194,"expense_id":73,"document_type":"ticket","document_number":"D-1220","vendor_name":"Proveedor sistema","description":"Gasto 1 para Tesorero","amount":"30409.00","expense_date":"2025-02-23T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":5}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
453	default	created	App\\Models\\ExpenseItem	195	\N	\N	{"attributes":{"id":195,"expense_id":73,"document_type":"ticket","document_number":"D-4987","vendor_name":"Proveedor sistema","description":"Gasto 2 para Tesorero","amount":"11483.00","expense_date":"2025-02-23T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":10}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
454	default	updated	App\\Models\\Expense	73	\N	\N	{"attributes":{"total_amount":"71321.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
455	default	updated	App\\Models\\Expense	73	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:04:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
456	default	created	App\\Models\\Expense	74	\N	\N	{"attributes":{"id":74,"expense_number":"RND-2025-000074","account_id":33,"submitted_by":31,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Tesorero Sistema","expense_date":"2024-08-26T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2024-08-26T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
457	default	created	App\\Models\\ExpenseItem	196	\N	\N	{"attributes":{"id":196,"expense_id":74,"document_type":"ticket","document_number":"D-6487","vendor_name":"Proveedor sistema","description":"Gasto 0 para Tesorero","amount":"73364.00","expense_date":"2024-08-26T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":1}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
458	default	created	App\\Models\\ExpenseItem	197	\N	\N	{"attributes":{"id":197,"expense_id":74,"document_type":"ticket","document_number":"D-3469","vendor_name":"Proveedor sistema","description":"Gasto 1 para Tesorero","amount":"56456.00","expense_date":"2024-08-26T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":8}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
459	default	updated	App\\Models\\Expense	74	\N	\N	{"attributes":{"total_amount":"129820.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
460	default	created	App\\Models\\Expense	75	\N	\N	{"attributes":{"id":75,"expense_number":"RND-2025-000075","account_id":33,"submitted_by":31,"total_amount":"0.00","description":"Rendici\\u00f3n autom\\u00e1tica para Tesorero Sistema","expense_date":"2025-01-09T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-01-09T15:04:34.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
461	default	created	App\\Models\\ExpenseItem	198	\N	\N	{"attributes":{"id":198,"expense_id":75,"document_type":"ticket","document_number":"D-1255","vendor_name":"Proveedor sistema","description":"Gasto 0 para Tesorero","amount":"112741.00","expense_date":"2025-01-09T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":10}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
462	default	created	App\\Models\\ExpenseItem	199	\N	\N	{"attributes":{"id":199,"expense_id":75,"document_type":"ticket","document_number":"D-6033","vendor_name":"Proveedor sistema","description":"Gasto 1 para Tesorero","amount":"117024.00","expense_date":"2025-01-09T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":5}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
463	default	created	App\\Models\\ExpenseItem	200	\N	\N	{"attributes":{"id":200,"expense_id":75,"document_type":"ticket","document_number":"D-3955","vendor_name":"Proveedor sistema","description":"Gasto 2 para Tesorero","amount":"52597.00","expense_date":"2025-01-09T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:04:34.000000Z","updated_at":"2025-09-16T15:04:34.000000Z","expense_category_id":5}}	2025-09-16 15:04:34	2025-09-16 15:04:34	created	\N
464	default	updated	App\\Models\\Expense	75	\N	\N	{"attributes":{"total_amount":"282362.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:04:34	2025-09-16 15:04:34	updated	\N
465	default	created	App\\Models\\Expense	77	\N	\N	{"attributes":{"id":77,"expense_number":"RND-2025-000076","account_id":3,"submitted_by":1,"total_amount":"0.00","description":"Rendici\\u00f3n generada para gr\\u00e1ficos","expense_date":"2025-09-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-16T15:14:00.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
466	default	created	App\\Models\\ExpenseItem	201	\N	\N	{"attributes":{"id":201,"expense_id":77,"document_type":"ticket","document_number":"JLDOQU","vendor_name":"Proveedor Seed","description":"Item seed","amount":"48681.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":10}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
467	default	created	App\\Models\\ExpenseItem	202	\N	\N	{"attributes":{"id":202,"expense_id":77,"document_type":"ticket","document_number":"ZKB6GY","vendor_name":"Proveedor Seed","description":"Item seed","amount":"15916.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":5}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
468	default	updated	App\\Models\\Expense	77	\N	\N	{"attributes":{"total_amount":"64597.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
469	default	updated	App\\Models\\Expense	77	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:14:00.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
470	default	created	App\\Models\\Expense	78	\N	\N	{"attributes":{"id":78,"expense_number":"RND-2025-000077","account_id":5,"submitted_by":3,"total_amount":"0.00","description":"Rendici\\u00f3n generada para gr\\u00e1ficos","expense_date":"2025-09-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-16T15:14:00.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
471	default	created	App\\Models\\ExpenseItem	203	\N	\N	{"attributes":{"id":203,"expense_id":78,"document_type":"ticket","document_number":"SQG2K2","vendor_name":"Proveedor Seed","description":"Item seed","amount":"42665.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":3}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
472	default	created	App\\Models\\ExpenseItem	204	\N	\N	{"attributes":{"id":204,"expense_id":78,"document_type":"ticket","document_number":"DBVPAN","vendor_name":"Proveedor Seed","description":"Item seed","amount":"28942.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":8}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
473	default	updated	App\\Models\\Expense	78	\N	\N	{"attributes":{"total_amount":"71607.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
474	default	updated	App\\Models\\Expense	78	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:14:00.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
475	default	created	App\\Models\\Expense	79	\N	\N	{"attributes":{"id":79,"expense_number":"RND-2025-000078","account_id":6,"submitted_by":4,"total_amount":"0.00","description":"Rendici\\u00f3n generada para gr\\u00e1ficos","expense_date":"2025-09-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-16T15:14:00.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
476	default	created	App\\Models\\ExpenseItem	205	\N	\N	{"attributes":{"id":205,"expense_id":79,"document_type":"ticket","document_number":"HAPLCN","vendor_name":"Proveedor Seed","description":"Item seed","amount":"35882.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":4}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
477	default	created	App\\Models\\ExpenseItem	206	\N	\N	{"attributes":{"id":206,"expense_id":79,"document_type":"ticket","document_number":"PDAWQF","vendor_name":"Proveedor Seed","description":"Item seed","amount":"20661.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":9}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
478	default	updated	App\\Models\\Expense	79	\N	\N	{"attributes":{"total_amount":"56543.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
479	default	updated	App\\Models\\Expense	79	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:14:00.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
480	default	created	App\\Models\\Expense	80	\N	\N	{"attributes":{"id":80,"expense_number":"RND-2025-000079","account_id":8,"submitted_by":6,"total_amount":"0.00","description":"Rendici\\u00f3n generada para gr\\u00e1ficos","expense_date":"2025-09-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-16T15:14:00.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
481	default	created	App\\Models\\ExpenseItem	207	\N	\N	{"attributes":{"id":207,"expense_id":80,"document_type":"ticket","document_number":"3RGWIA","vendor_name":"Proveedor Seed","description":"Item seed","amount":"48357.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":3}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
482	default	updated	App\\Models\\Expense	80	\N	\N	{"attributes":{"total_amount":"48357.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
483	default	updated	App\\Models\\Expense	80	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:14:00.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
484	default	created	App\\Models\\Expense	81	\N	\N	{"attributes":{"id":81,"expense_number":"RND-2025-000080","account_id":9,"submitted_by":7,"total_amount":"0.00","description":"Rendici\\u00f3n generada para gr\\u00e1ficos","expense_date":"2025-09-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-16T15:14:00.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
485	default	created	App\\Models\\ExpenseItem	208	\N	\N	{"attributes":{"id":208,"expense_id":81,"document_type":"ticket","document_number":"XYPPMD","vendor_name":"Proveedor Seed","description":"Item seed","amount":"21046.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":3}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
486	default	updated	App\\Models\\Expense	81	\N	\N	{"attributes":{"total_amount":"21046.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
487	default	updated	App\\Models\\Expense	81	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:14:00.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
488	default	created	App\\Models\\Expense	82	\N	\N	{"attributes":{"id":82,"expense_number":"RND-2025-000081","account_id":13,"submitted_by":11,"total_amount":"0.00","description":"Rendici\\u00f3n generada para gr\\u00e1ficos","expense_date":"2025-09-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-16T15:14:00.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
489	default	created	App\\Models\\ExpenseItem	209	\N	\N	{"attributes":{"id":209,"expense_id":82,"document_type":"ticket","document_number":"WEXQOF","vendor_name":"Proveedor Seed","description":"Item seed","amount":"40717.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":5}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
490	default	created	App\\Models\\ExpenseItem	210	\N	\N	{"attributes":{"id":210,"expense_id":82,"document_type":"ticket","document_number":"LUXZGG","vendor_name":"Proveedor Seed","description":"Item seed","amount":"25861.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":1}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
491	default	updated	App\\Models\\Expense	82	\N	\N	{"attributes":{"total_amount":"66578.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
492	default	updated	App\\Models\\Expense	82	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:14:00.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
493	default	created	App\\Models\\Expense	83	\N	\N	{"attributes":{"id":83,"expense_number":"RND-2025-000082","account_id":14,"submitted_by":12,"total_amount":"0.00","description":"Rendici\\u00f3n generada para gr\\u00e1ficos","expense_date":"2025-09-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-16T15:14:00.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
494	default	created	App\\Models\\ExpenseItem	211	\N	\N	{"attributes":{"id":211,"expense_id":83,"document_type":"ticket","document_number":"LB3XLZ","vendor_name":"Proveedor Seed","description":"Item seed","amount":"46504.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":4}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
495	default	created	App\\Models\\ExpenseItem	212	\N	\N	{"attributes":{"id":212,"expense_id":83,"document_type":"ticket","document_number":"VLLI6I","vendor_name":"Proveedor Seed","description":"Item seed","amount":"43908.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":8}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
496	default	updated	App\\Models\\Expense	83	\N	\N	{"attributes":{"total_amount":"90412.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
497	default	updated	App\\Models\\Expense	83	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:14:00.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
498	default	created	App\\Models\\Expense	84	\N	\N	{"attributes":{"id":84,"expense_number":"RND-2025-000083","account_id":18,"submitted_by":16,"total_amount":"0.00","description":"Rendici\\u00f3n generada para gr\\u00e1ficos","expense_date":"2025-09-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-16T15:14:00.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
499	default	created	App\\Models\\ExpenseItem	213	\N	\N	{"attributes":{"id":213,"expense_id":84,"document_type":"ticket","document_number":"5ITFQ1","vendor_name":"Proveedor Seed","description":"Item seed","amount":"21612.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":9}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
500	default	updated	App\\Models\\Expense	84	\N	\N	{"attributes":{"total_amount":"21612.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
501	default	updated	App\\Models\\Expense	84	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:14:00.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
502	default	created	App\\Models\\Expense	85	\N	\N	{"attributes":{"id":85,"expense_number":"RND-2025-000084","account_id":19,"submitted_by":17,"total_amount":"0.00","description":"Rendici\\u00f3n generada para gr\\u00e1ficos","expense_date":"2025-09-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-16T15:14:00.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
523	default	updated	App\\Models\\Expense	89	\N	\N	{"attributes":{"total_amount":"69334.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
503	default	created	App\\Models\\ExpenseItem	214	\N	\N	{"attributes":{"id":214,"expense_id":85,"document_type":"ticket","document_number":"UTW37Q","vendor_name":"Proveedor Seed","description":"Item seed","amount":"29710.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":3}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
504	default	created	App\\Models\\ExpenseItem	215	\N	\N	{"attributes":{"id":215,"expense_id":85,"document_type":"ticket","document_number":"DL9GLP","vendor_name":"Proveedor Seed","description":"Item seed","amount":"44919.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":3}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
505	default	updated	App\\Models\\Expense	85	\N	\N	{"attributes":{"total_amount":"74629.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
506	default	updated	App\\Models\\Expense	85	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:14:00.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
507	default	created	App\\Models\\Expense	86	\N	\N	{"attributes":{"id":86,"expense_number":"RND-2025-000085","account_id":21,"submitted_by":19,"total_amount":"0.00","description":"Rendici\\u00f3n generada para gr\\u00e1ficos","expense_date":"2025-09-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-16T15:14:00.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
508	default	created	App\\Models\\ExpenseItem	216	\N	\N	{"attributes":{"id":216,"expense_id":86,"document_type":"ticket","document_number":"5TCOYL","vendor_name":"Proveedor Seed","description":"Item seed","amount":"13658.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":3}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
509	default	updated	App\\Models\\Expense	86	\N	\N	{"attributes":{"total_amount":"13658.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
510	default	updated	App\\Models\\Expense	86	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:14:00.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
511	default	created	App\\Models\\Expense	87	\N	\N	{"attributes":{"id":87,"expense_number":"RND-2025-000086","account_id":22,"submitted_by":20,"total_amount":"0.00","description":"Rendici\\u00f3n generada para gr\\u00e1ficos","expense_date":"2025-09-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-16T15:14:00.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
512	default	created	App\\Models\\ExpenseItem	217	\N	\N	{"attributes":{"id":217,"expense_id":87,"document_type":"ticket","document_number":"XTZU67","vendor_name":"Proveedor Seed","description":"Item seed","amount":"4741.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":8}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
513	default	updated	App\\Models\\Expense	87	\N	\N	{"attributes":{"total_amount":"4741.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
514	default	updated	App\\Models\\Expense	87	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:14:00.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
515	default	created	App\\Models\\Expense	88	\N	\N	{"attributes":{"id":88,"expense_number":"RND-2025-000087","account_id":24,"submitted_by":22,"total_amount":"0.00","description":"Rendici\\u00f3n generada para gr\\u00e1ficos","expense_date":"2025-09-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-16T15:14:00.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
516	default	created	App\\Models\\ExpenseItem	218	\N	\N	{"attributes":{"id":218,"expense_id":88,"document_type":"ticket","document_number":"FHKVYN","vendor_name":"Proveedor Seed","description":"Item seed","amount":"37979.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":1}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
517	default	created	App\\Models\\ExpenseItem	219	\N	\N	{"attributes":{"id":219,"expense_id":88,"document_type":"ticket","document_number":"JT3TKP","vendor_name":"Proveedor Seed","description":"Item seed","amount":"4028.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":8}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
518	default	updated	App\\Models\\Expense	88	\N	\N	{"attributes":{"total_amount":"42007.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
519	default	updated	App\\Models\\Expense	88	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:14:00.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
520	default	created	App\\Models\\Expense	89	\N	\N	{"attributes":{"id":89,"expense_number":"RND-2025-000088","account_id":26,"submitted_by":24,"total_amount":"0.00","description":"Rendici\\u00f3n generada para gr\\u00e1ficos","expense_date":"2025-09-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-16T15:14:00.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
521	default	created	App\\Models\\ExpenseItem	220	\N	\N	{"attributes":{"id":220,"expense_id":89,"document_type":"ticket","document_number":"5JWXZP","vendor_name":"Proveedor Seed","description":"Item seed","amount":"31599.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":3}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
522	default	created	App\\Models\\ExpenseItem	221	\N	\N	{"attributes":{"id":221,"expense_id":89,"document_type":"ticket","document_number":"X9DPTE","vendor_name":"Proveedor Seed","description":"Item seed","amount":"37735.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":6}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
524	default	updated	App\\Models\\Expense	89	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:14:00.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
525	default	created	App\\Models\\Expense	90	\N	\N	{"attributes":{"id":90,"expense_number":"RND-2025-000089","account_id":31,"submitted_by":29,"total_amount":"0.00","description":"Rendici\\u00f3n generada para gr\\u00e1ficos","expense_date":"2025-09-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-16T15:14:00.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
526	default	created	App\\Models\\ExpenseItem	222	\N	\N	{"attributes":{"id":222,"expense_id":90,"document_type":"ticket","document_number":"FDDOKX","vendor_name":"Proveedor Seed","description":"Item seed","amount":"30102.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":2}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
527	default	created	App\\Models\\ExpenseItem	223	\N	\N	{"attributes":{"id":223,"expense_id":90,"document_type":"ticket","document_number":"CMNADA","vendor_name":"Proveedor Seed","description":"Item seed","amount":"9520.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":9}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
528	default	updated	App\\Models\\Expense	90	\N	\N	{"attributes":{"total_amount":"39622.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
529	default	updated	App\\Models\\Expense	90	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:14:00.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
530	default	created	App\\Models\\Expense	91	\N	\N	{"attributes":{"id":91,"expense_number":"RND-2025-000090","account_id":33,"submitted_by":31,"total_amount":"0.00","description":"Rendici\\u00f3n generada para gr\\u00e1ficos","expense_date":"2025-09-16T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-16T15:14:00.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
531	default	created	App\\Models\\ExpenseItem	224	\N	\N	{"attributes":{"id":224,"expense_id":91,"document_type":"ticket","document_number":"OEPTIV","vendor_name":"Proveedor Seed","description":"Item seed","amount":"40867.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":9}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
532	default	created	App\\Models\\ExpenseItem	225	\N	\N	{"attributes":{"id":225,"expense_id":91,"document_type":"ticket","document_number":"OLJITK","vendor_name":"Proveedor Seed","description":"Item seed","amount":"11120.00","expense_date":"2025-09-16T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-16T15:14:00.000000Z","updated_at":"2025-09-16T15:14:00.000000Z","expense_category_id":7}}	2025-09-16 15:14:00	2025-09-16 15:14:00	created	\N
533	default	updated	App\\Models\\Expense	91	\N	\N	{"attributes":{"total_amount":"51987.00"},"old":{"total_amount":"0.00"}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
534	default	updated	App\\Models\\Expense	91	\N	\N	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:14:00.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null}}	2025-09-16 15:14:00	2025-09-16 15:14:00	updated	\N
535	default	updated	App\\Models\\Expense	1	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:15:28.000000Z","updated_at":"2025-09-16T15:15:28.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:15:28	2025-09-16 15:15:28	updated	\N
536	default	updated	App\\Models\\Account	1	App\\Models\\User	1	{"attributes":{"balance":"-85000.00","updated_at":"2025-09-16T15:15:28.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:15:28	2025-09-16 15:15:28	updated	\N
537	default	updated	App\\Models\\Expense	40	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:15:37.000000Z","updated_at":"2025-09-16T15:15:37.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:15:37	2025-09-16 15:15:37	updated	\N
538	default	updated	App\\Models\\Account	20	App\\Models\\User	1	{"attributes":{"balance":"-219508.00","updated_at":"2025-09-16T15:15:37.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:15:37	2025-09-16 15:15:37	updated	\N
539	default	updated	App\\Models\\Expense	34	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:15:43.000000Z","updated_at":"2025-09-16T15:15:43.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:15:43	2025-09-16 15:15:43	updated	\N
540	default	updated	App\\Models\\Account	18	App\\Models\\User	1	{"attributes":{"balance":"-242419.00","updated_at":"2025-09-16T15:15:43.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:15:43	2025-09-16 15:15:43	updated	\N
541	default	updated	App\\Models\\Expense	68	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:15:48.000000Z","updated_at":"2025-09-16T15:15:48.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:15:48	2025-09-16 15:15:48	updated	\N
542	default	updated	App\\Models\\Account	30	App\\Models\\User	1	{"attributes":{"balance":"-221849.00","updated_at":"2025-09-16T15:15:48.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:15:48	2025-09-16 15:15:48	updated	\N
543	default	updated	App\\Models\\Expense	49	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:15:53.000000Z","updated_at":"2025-09-16T15:15:53.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:15:53	2025-09-16 15:15:53	updated	\N
544	default	updated	App\\Models\\Account	24	App\\Models\\User	1	{"attributes":{"balance":"-18330.00","updated_at":"2025-09-16T15:15:53.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:15:53	2025-09-16 15:15:53	updated	\N
568	default	updated	App\\Models\\Account	20	App\\Models\\User	1	{"attributes":{"balance":"-465798.00","updated_at":"2025-09-16T15:16:46.000000Z"},"old":{"balance":"-238920.00","updated_at":"2025-09-16T15:16:36.000000Z"}}	2025-09-16 15:16:46	2025-09-16 15:16:46	updated	\N
545	default	updated	App\\Models\\Expense	35	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:15:59.000000Z","updated_at":"2025-09-16T15:15:59.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:15:59	2025-09-16 15:15:59	updated	\N
546	default	updated	App\\Models\\Account	19	App\\Models\\User	1	{"attributes":{"balance":"-31626.00","updated_at":"2025-09-16T15:15:59.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:15:59	2025-09-16 15:15:59	updated	\N
547	default	updated	App\\Models\\Expense	16	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:16:09.000000Z","updated_at":"2025-09-16T15:16:09.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:16:09	2025-09-16 15:16:09	updated	\N
548	default	updated	App\\Models\\Account	11	App\\Models\\User	1	{"attributes":{"balance":"-214182.00","updated_at":"2025-09-16T15:16:09.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:16:09	2025-09-16 15:16:09	updated	\N
549	default	updated	App\\Models\\Expense	22	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:16:16.000000Z","updated_at":"2025-09-16T15:16:16.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:16:16	2025-09-16 15:16:16	updated	\N
550	default	updated	App\\Models\\Account	13	App\\Models\\User	1	{"attributes":{"balance":"-173603.00","updated_at":"2025-09-16T15:16:16.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:16:16	2025-09-16 15:16:16	updated	\N
551	default	updated	App\\Models\\Expense	14	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:16:24.000000Z","updated_at":"2025-09-16T15:16:24.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:16:24	2025-09-16 15:16:24	updated	\N
552	default	updated	App\\Models\\Account	10	App\\Models\\User	1	{"attributes":{"balance":"-75930.00","updated_at":"2025-09-16T15:16:24.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:16:24	2025-09-16 15:16:24	updated	\N
553	default	updated	App\\Models\\Expense	32	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:16:27.000000Z","updated_at":"2025-09-16T15:16:27.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:16:27	2025-09-16 15:16:27	updated	\N
554	default	updated	App\\Models\\Account	18	App\\Models\\User	1	{"attributes":{"balance":"-342683.00","updated_at":"2025-09-16T15:16:27.000000Z"},"old":{"balance":"-242419.00","updated_at":"2025-09-16T15:15:43.000000Z"}}	2025-09-16 15:16:27	2025-09-16 15:16:27	updated	\N
555	default	updated	App\\Models\\Expense	44	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:16:30.000000Z","updated_at":"2025-09-16T15:16:30.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:16:30	2025-09-16 15:16:30	updated	\N
556	default	updated	App\\Models\\Account	22	App\\Models\\User	1	{"attributes":{"balance":"-183248.00","updated_at":"2025-09-16T15:16:30.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:16:30	2025-09-16 15:16:30	updated	\N
557	default	updated	App\\Models\\Expense	31	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:16:34.000000Z","updated_at":"2025-09-16T15:16:34.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:16:34	2025-09-16 15:16:34	updated	\N
558	default	updated	App\\Models\\Account	17	App\\Models\\User	1	{"attributes":{"balance":"-259034.00","updated_at":"2025-09-16T15:16:34.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:16:34	2025-09-16 15:16:34	updated	\N
559	default	updated	App\\Models\\Expense	39	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:16:36.000000Z","updated_at":"2025-09-16T15:16:36.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:16:36	2025-09-16 15:16:36	updated	\N
560	default	updated	App\\Models\\Account	20	App\\Models\\User	1	{"attributes":{"balance":"-238920.00","updated_at":"2025-09-16T15:16:36.000000Z"},"old":{"balance":"-219508.00","updated_at":"2025-09-16T15:15:37.000000Z"}}	2025-09-16 15:16:36	2025-09-16 15:16:36	updated	\N
561	default	updated	App\\Models\\Expense	3	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:16:38.000000Z","updated_at":"2025-09-16T15:16:38.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:16:38	2025-09-16 15:16:38	updated	\N
562	default	updated	App\\Models\\Account	3	App\\Models\\User	1	{"attributes":{"balance":"-80376.00","updated_at":"2025-09-16T15:16:38.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:16:38	2025-09-16 15:16:38	updated	\N
563	default	updated	App\\Models\\Expense	75	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:16:41.000000Z","updated_at":"2025-09-16T15:16:41.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:16:41	2025-09-16 15:16:41	updated	\N
564	default	updated	App\\Models\\Account	33	App\\Models\\User	1	{"attributes":{"balance":"-282362.00","updated_at":"2025-09-16T15:16:41.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T14:54:24.000000Z"}}	2025-09-16 15:16:41	2025-09-16 15:16:41	updated	\N
565	default	updated	App\\Models\\Expense	72	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:16:44.000000Z","updated_at":"2025-09-16T15:16:44.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:16:44	2025-09-16 15:16:44	updated	\N
566	default	updated	App\\Models\\Account	33	App\\Models\\User	1	{"attributes":{"balance":"-380640.00","updated_at":"2025-09-16T15:16:44.000000Z"},"old":{"balance":"-282362.00","updated_at":"2025-09-16T15:16:41.000000Z"}}	2025-09-16 15:16:44	2025-09-16 15:16:44	updated	\N
567	default	updated	App\\Models\\Expense	41	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:16:46.000000Z","updated_at":"2025-09-16T15:16:46.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:16:46	2025-09-16 15:16:46	updated	\N
569	default	updated	App\\Models\\Expense	54	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:16:51.000000Z","updated_at":"2025-09-16T15:16:51.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:16:51	2025-09-16 15:16:51	updated	\N
570	default	updated	App\\Models\\Account	26	App\\Models\\User	1	{"attributes":{"balance":"-206960.00","updated_at":"2025-09-16T15:16:51.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:16:51	2025-09-16 15:16:51	updated	\N
571	default	updated	App\\Models\\Expense	9	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:16:53.000000Z","updated_at":"2025-09-16T15:16:53.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:16:53	2025-09-16 15:16:53	updated	\N
572	default	updated	App\\Models\\Account	7	App\\Models\\User	1	{"attributes":{"balance":"-208134.00","updated_at":"2025-09-16T15:16:53.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:16:53	2025-09-16 15:16:53	updated	\N
573	default	updated	App\\Models\\Expense	12	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:16:55.000000Z","updated_at":"2025-09-16T15:16:55.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:16:55	2025-09-16 15:16:55	updated	\N
574	default	updated	App\\Models\\Account	9	App\\Models\\User	1	{"attributes":{"balance":"-11507.00","updated_at":"2025-09-16T15:16:55.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:16:55	2025-09-16 15:16:55	updated	\N
575	default	updated	App\\Models\\Expense	57	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:16:57.000000Z","updated_at":"2025-09-16T15:16:57.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:34.000000Z"}}	2025-09-16 15:16:57	2025-09-16 15:16:57	updated	\N
576	default	updated	App\\Models\\Account	27	App\\Models\\User	1	{"attributes":{"balance":"-126773.00","updated_at":"2025-09-16T15:16:57.000000Z"},"old":{"balance":"0.00","updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:16:57	2025-09-16 15:16:57	updated	\N
577	default	updated	App\\Models\\Expense	21	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-16T15:16:59.000000Z","updated_at":"2025-09-16T15:16:59.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-16T15:04:33.000000Z"}}	2025-09-16 15:16:59	2025-09-16 15:16:59	updated	\N
578	default	updated	App\\Models\\Account	13	App\\Models\\User	1	{"attributes":{"balance":"-453958.00","updated_at":"2025-09-16T15:16:59.000000Z"},"old":{"balance":"-173603.00","updated_at":"2025-09-16T15:16:16.000000Z"}}	2025-09-16 15:16:59	2025-09-16 15:16:59	updated	\N
579	default	updated	App\\Models\\Account	2	App\\Models\\User	1	{"attributes":{"person_id":31,"updated_at":"2025-09-16T15:19:58.000000Z","is_fondeo":false},"old":{"person_id":null,"updated_at":"2025-09-16T12:06:28.000000Z","is_fondeo":true}}	2025-09-16 15:19:58	2025-09-16 15:19:58	updated	\N
580	default	updated	App\\Models\\Account	2	App\\Models\\User	1	{"attributes":{"balance":"9999999999999.00","updated_at":"2025-09-16T15:25:45.000000Z"},"old":{"balance":"100000000.00","updated_at":"2025-09-16T15:19:58.000000Z"}}	2025-09-16 15:25:45	2025-09-16 15:25:45	updated	\N
581	default	created	App\\Models\\Transaction	386	App\\Models\\User	1	{"attributes":{"id":386,"transaction_number":"TXN-2025-003","type":"transfer","from_account_id":2,"to_account_id":1,"amount":"300000000.00","description":"dfdfgdfgdfg","notes":null,"created_by":1,"approved_by":null,"status":"pending","approved_at":null,"is_enabled":true,"created_at":"2025-09-16T15:26:17.000000Z","updated_at":"2025-09-16T15:26:17.000000Z"}}	2025-09-16 15:26:17	2025-09-16 15:26:17	created	\N
582	default	updated	App\\Models\\Transaction	386	App\\Models\\User	1	{"attributes":{"approved_by":1,"status":"approved","approved_at":"2025-09-16T15:26:27.000000Z","updated_at":"2025-09-16T15:26:27.000000Z"},"old":{"approved_by":null,"status":"pending","approved_at":null,"updated_at":"2025-09-16T15:26:17.000000Z"}}	2025-09-16 15:26:27	2025-09-16 15:26:27	updated	\N
583	default	updated	App\\Models\\Account	2	App\\Models\\User	1	{"attributes":{"balance":"9999699999999.00","updated_at":"2025-09-16T15:26:27.000000Z"},"old":{"balance":"9999999999999.00","updated_at":"2025-09-16T15:25:45.000000Z"}}	2025-09-16 15:26:27	2025-09-16 15:26:27	updated	\N
584	default	updated	App\\Models\\Account	1	App\\Models\\User	1	{"attributes":{"balance":"299915000.00","updated_at":"2025-09-16T15:26:27.000000Z"},"old":{"balance":"-85000.00","updated_at":"2025-09-16T15:15:28.000000Z"}}	2025-09-16 15:26:27	2025-09-16 15:26:27	updated	\N
585	default	updated	App\\Models\\Transaction	2	App\\Models\\User	1	{"attributes":{"approved_by":1,"status":"approved","approved_at":"2025-09-16T15:26:38.000000Z","updated_at":"2025-09-16T15:26:38.000000Z"},"old":{"approved_by":null,"status":"pending","approved_at":null,"updated_at":"2025-09-16T12:06:28.000000Z"}}	2025-09-16 15:26:38	2025-09-16 15:26:38	updated	\N
586	default	updated	App\\Models\\Account	1	App\\Models\\User	1	{"attributes":{"balance":"299615000.00","updated_at":"2025-09-16T15:26:38.000000Z"},"old":{"balance":"299915000.00","updated_at":"2025-09-16T15:26:27.000000Z"}}	2025-09-16 15:26:38	2025-09-16 15:26:38	updated	\N
587	default	updated	App\\Models\\Account	3	App\\Models\\User	1	{"attributes":{"balance":"219624.00","updated_at":"2025-09-16T15:26:38.000000Z"},"old":{"balance":"-80376.00","updated_at":"2025-09-16T15:16:38.000000Z"}}	2025-09-16 15:26:38	2025-09-16 15:26:38	updated	\N
588	default	created	App\\Models\\Expense	92	App\\Models\\User	1	{"attributes":{"id":92,"expense_number":"RND-2025-000091","account_id":3,"submitted_by":1,"total_amount":"21080.00","description":"p1","expense_date":"2025-09-17T00:00:00.000000Z","status":"submitted","reviewed_by":null,"submitted_at":"2025-09-17T11:47:23.000000Z","reviewed_at":null,"rejection_reason":null,"is_enabled":true,"created_at":"2025-09-17T11:47:23.000000Z","updated_at":"2025-09-17T11:47:23.000000Z"}}	2025-09-17 11:47:23	2025-09-17 11:47:23	created	\N
589	default	created	App\\Models\\ExpenseItem	226	App\\Models\\User	1	{"attributes":{"id":226,"expense_id":92,"document_type":"boleta","document_number":"5564456","vendor_name":"hhh","description":"almuerzo","amount":"12500.00","expense_date":"2025-09-17T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-17T11:47:23.000000Z","updated_at":"2025-09-17T11:47:23.000000Z","expense_category_id":2}}	2025-09-17 11:47:23	2025-09-17 11:47:23	created	\N
590	default	created	App\\Models\\Document	1	App\\Models\\User	1	{"attributes":{"id":1,"name":"WhatsApp Image 2025-09-16 at 10.43.13.jpeg","file_path":"expenses\\/92\\/items\\/226\\/WhatsApp Image 2025-09-16 at 10.43.13.jpeg","mime_type":"image\\/jpeg","file_size":186117,"document_type":"boleta","expense_item_id":226,"uploaded_by":1,"is_enabled":true,"created_at":"2025-09-17T11:47:23.000000Z","updated_at":"2025-09-17T11:47:23.000000Z"}}	2025-09-17 11:47:23	2025-09-17 11:47:23	created	\N
591	default	created	App\\Models\\ExpenseItem	227	App\\Models\\User	1	{"attributes":{"id":227,"expense_id":92,"document_type":"boleta","document_number":"12123","vendor_name":"sdfsd","description":"p1","amount":"8580.00","expense_date":"2025-09-17T00:00:00.000000Z","category":null,"is_enabled":true,"created_at":"2025-09-17T11:47:23.000000Z","updated_at":"2025-09-17T11:47:23.000000Z","expense_category_id":1}}	2025-09-17 11:47:23	2025-09-17 11:47:23	created	\N
592	default	created	App\\Models\\Document	2	App\\Models\\User	1	{"attributes":{"id":2,"name":"WhatsApp Image 2025-09-11 at 13.08.13.jpeg","file_path":"expenses\\/92\\/items\\/227\\/WhatsApp Image 2025-09-11 at 13.08.13.jpeg","mime_type":"image\\/jpeg","file_size":164076,"document_type":"boleta","expense_item_id":227,"uploaded_by":1,"is_enabled":true,"created_at":"2025-09-17T11:47:23.000000Z","updated_at":"2025-09-17T11:47:23.000000Z"}}	2025-09-17 11:47:23	2025-09-17 11:47:23	created	\N
593	default	updated	App\\Models\\Expense	92	App\\Models\\User	1	{"attributes":{"status":"approved","reviewed_by":1,"reviewed_at":"2025-09-17T11:47:42.000000Z","updated_at":"2025-09-17T11:47:42.000000Z"},"old":{"status":"submitted","reviewed_by":null,"reviewed_at":null,"updated_at":"2025-09-17T11:47:23.000000Z"}}	2025-09-17 11:47:42	2025-09-17 11:47:42	updated	\N
594	default	updated	App\\Models\\Account	3	App\\Models\\User	1	{"attributes":{"balance":"198544.00","updated_at":"2025-09-17T11:47:42.000000Z"},"old":{"balance":"219624.00","updated_at":"2025-09-16T15:26:38.000000Z"}}	2025-09-17 11:47:42	2025-09-17 11:47:42	updated	\N
\.


--
-- Data for Name: banks; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.banks (id, name, code, type, is_active, created_at, updated_at) FROM stdin;
1	Banco de Chile	001	banco	t	2025-09-16 12:06:27	2025-09-16 12:06:27
2	Banco Internacional	009	banco	t	2025-09-16 12:06:27	2025-09-16 12:06:27
3	Scotiabank Chile	014	banco	t	2025-09-16 12:06:27	2025-09-16 12:06:27
4	Banco de Crédito e Inversiones	016	banco	t	2025-09-16 12:06:27	2025-09-16 12:06:27
5	Banco Bice	028	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
6	HSBC Bank (Chile)	031	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
7	Banco Santander Chile	037	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
8	Banco Itaú Chile	039	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
9	Banco Security	049	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
10	Banco Falabella	051	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
11	Deutsche Bank (Chile)	052	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
12	Banco Ripley	053	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
13	Rabobank Chile	054	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
14	Banco Consorcio	055	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
15	Banco Penta	056	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
16	Banco París	057	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
17	Banco BTG Pactual Chile	059	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
18	China Construction Bank	060	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
19	Coopeuch	COOP001	cooperativa	t	2025-09-16 12:06:28	2025-09-16 12:06:28
20	Detacoop	COOP002	cooperativa	t	2025-09-16 12:06:28	2025-09-16 12:06:28
21	Oriencoop	COOP003	cooperativa	t	2025-09-16 12:06:28	2025-09-16 12:06:28
22	Capual	COOP004	cooperativa	t	2025-09-16 12:06:28	2025-09-16 12:06:28
23	Ahorrocoop	COOP005	cooperativa	t	2025-09-16 12:06:28	2025-09-16 12:06:28
24	Multicaja	PREP001	tarjeta_prepago	t	2025-09-16 12:06:28	2025-09-16 12:06:28
25	TenpoCard	PREP002	tarjeta_prepago	t	2025-09-16 12:06:28	2025-09-16 12:06:28
26	Mach (BCI)	PREP003	tarjeta_prepago	t	2025-09-16 12:06:28	2025-09-16 12:06:28
27	Junaeb	PREP004	tarjeta_prepago	t	2025-09-16 12:06:28	2025-09-16 12:06:28
28	Fintual	PREP005	tarjeta_prepago	t	2025-09-16 12:06:28	2025-09-16 12:06:28
29	Klap	PREP006	tarjeta_prepago	t	2025-09-16 12:06:28	2025-09-16 12:06:28
30	Banco del Estado de Chile	012	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
31	Banco Corpbanca	027	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
32	Banco do Brasil S.A.	017	banco	t	2025-09-16 12:06:28	2025-09-16 12:06:28
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: documents; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.documents (id, name, file_path, mime_type, file_size, document_type, expense_item_id, uploaded_by, is_enabled, created_at, updated_at) FROM stdin;
1	WhatsApp Image 2025-09-16 at 10.43.13.jpeg	expenses/92/items/226/WhatsApp Image 2025-09-16 at 10.43.13.jpeg	image/jpeg	186117	boleta	226	1	t	2025-09-17 11:47:23	2025-09-17 11:47:23
2	WhatsApp Image 2025-09-11 at 13.08.13.jpeg	expenses/92/items/227/WhatsApp Image 2025-09-11 at 13.08.13.jpeg	image/jpeg	164076	boleta	227	1	t	2025-09-17 11:47:23	2025-09-17 11:47:23
\.


--
-- Data for Name: expense_categories; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.expense_categories (id, code, name, description, is_enabled, created_at, updated_at) FROM stdin;
1	PEA	Peaje	Gastos en peajes de vehículos	t	2025-09-16 14:55:08	2025-09-16 14:55:08
2	ALI	Alimentación	Comidas, viandas, refrigerios	t	2025-09-16 14:55:08	2025-09-16 14:55:08
3	VUL	Vulcanización	Reparación de neumáticos y servicios relacionados	t	2025-09-16 14:55:08	2025-09-16 14:55:08
4	INS	Insumos y Materiales	Materiales menores, suministros	t	2025-09-16 14:55:08	2025-09-16 14:55:08
5	HER	Herramientas	Compra o reparación de herramientas	t	2025-09-16 14:55:08	2025-09-16 14:55:08
6	COM	Combustible	Gasolina, diésel, etc.	t	2025-09-16 14:55:08	2025-09-16 14:55:08
7	HOS	Hospedaje	Hoteles y alojamiento	t	2025-09-16 14:55:08	2025-09-16 14:55:08
8	VIA	Viáticos	Gastos de viaje y movilidad	t	2025-09-16 14:55:08	2025-09-16 14:55:08
9	SER	Servicios	Servicios contratados (electricidad, agua, etc.)	t	2025-09-16 14:55:08	2025-09-16 14:55:08
10	MTC	Mantenimiento	Mantenimiento de equipos y vehículos	t	2025-09-16 14:55:08	2025-09-16 14:55:08
\.


--
-- Data for Name: expense_items; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.expense_items (id, expense_id, document_type, document_number, vendor_name, description, amount, expense_date, category, is_enabled, created_at, updated_at, expense_category_id) FROM stdin;
1	1	factura	001-00100	Copec	Combustible	45000.00	2025-08-18	combustible	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N
2	1	boleta	002-00200	Ferretería Los Andes	Materiales de construcción	40000.00	2025-08-31	materiales	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N
3	2	factura	003-00301	Sodimac	Herramientas	80000.00	2025-08-20	herramientas	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N
4	2	ticket	\N	Restaurant El Buen Sabor	Viáticos	40000.00	2025-09-09	viaticos	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N
5	3	ticket	D-2213	Proveedor mendoza	Gasto 0 para Carlos	10940.00	2025-02-03	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	8
6	3	ticket	D-6246	Proveedor mendoza	Gasto 1 para Carlos	47027.00	2025-02-03	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	9
7	3	ticket	D-3156	Proveedor mendoza	Gasto 2 para Carlos	22409.00	2025-02-03	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	2
8	4	ticket	D-2155	Proveedor mendoza	Gasto 0 para Carlos	96793.00	2024-09-14	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	4
9	4	ticket	D-4803	Proveedor mendoza	Gasto 1 para Carlos	114032.00	2024-09-14	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	8
10	5	ticket	D-4847	Proveedor garcia	Gasto 0 para María	96987.00	2024-10-27	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	10
11	5	ticket	D-1072	Proveedor garcia	Gasto 1 para María	99462.00	2024-10-27	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	2
12	6	ticket	D-9918	Proveedor garcia	Gasto 0 para María	19899.00	2025-07-22	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	3
13	6	ticket	D-7844	Proveedor garcia	Gasto 1 para María	26932.00	2025-07-22	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	8
14	7	ticket	D-2031	Proveedor rodrigue	Gasto 0 para Luis	114041.00	2025-02-05	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	1
15	8	ticket	D-2746	Proveedor lopez	Gasto 0 para Ana	2280.00	2024-12-03	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	3
16	8	ticket	D-1647	Proveedor lopez	Gasto 1 para Ana	76265.00	2024-12-03	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	3
17	9	ticket	D-8026	Proveedor sanchez	Gasto 0 para Pedro	67462.00	2024-08-31	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	10
18	9	ticket	D-3592	Proveedor sanchez	Gasto 1 para Pedro	70912.00	2024-08-31	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	10
19	9	ticket	D-2496	Proveedor sanchez	Gasto 2 para Pedro	69760.00	2024-08-31	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	10
20	10	ticket	D-4452	Proveedor sanchez	Gasto 0 para Pedro	67844.00	2025-07-12	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	9
21	11	ticket	D-8788	Proveedor torp-qui	Gasto 0 para Jamarcus	64879.00	2025-01-01	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	6
22	11	ticket	D-7049	Proveedor torp-qui	Gasto 1 para Jamarcus	118256.00	2025-01-01	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	5
23	12	ticket	D-5532	Proveedor langosh-	Gasto 0 para Mina	11507.00	2024-09-07	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	9
24	13	ticket	D-3533	Proveedor auer-oha	Gasto 0 para Raegan	47746.00	2025-06-04	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	7
25	13	ticket	D-8825	Proveedor auer-oha	Gasto 1 para Raegan	105551.00	2025-06-04	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	9
26	13	ticket	D-8031	Proveedor auer-oha	Gasto 2 para Raegan	71730.00	2025-06-04	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	10
27	14	ticket	D-9269	Proveedor auer-oha	Gasto 0 para Raegan	75930.00	2025-04-05	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	2
28	15	ticket	D-8609	Proveedor auer-oha	Gasto 0 para Raegan	109616.00	2025-06-07	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	1
29	15	ticket	D-1786	Proveedor auer-oha	Gasto 1 para Raegan	78699.00	2025-06-07	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	9
30	15	ticket	D-8135	Proveedor auer-oha	Gasto 2 para Raegan	118482.00	2025-06-07	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	6
31	15	ticket	D-5907	Proveedor auer-oha	Gasto 3 para Raegan	37789.00	2025-06-07	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	8
32	16	ticket	D-7518	Proveedor schamber	Gasto 0 para Vinnie	119383.00	2025-07-01	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	1
33	16	ticket	D-7712	Proveedor schamber	Gasto 1 para Vinnie	94799.00	2025-07-01	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	6
34	17	ticket	D-3634	Proveedor schamber	Gasto 0 para Vinnie	34867.00	2025-04-19	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	1
35	17	ticket	D-8562	Proveedor schamber	Gasto 1 para Vinnie	12080.00	2025-04-19	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	5
36	17	ticket	D-4378	Proveedor schamber	Gasto 2 para Vinnie	14170.00	2025-04-19	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	4
37	17	ticket	D-3355	Proveedor schamber	Gasto 3 para Vinnie	40103.00	2025-04-19	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	6
38	18	ticket	D-3296	Proveedor schamber	Gasto 0 para Vinnie	109644.00	2025-06-13	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	1
39	18	ticket	D-8345	Proveedor schamber	Gasto 1 para Vinnie	69695.00	2025-06-13	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	1
40	18	ticket	D-7049	Proveedor schamber	Gasto 2 para Vinnie	110697.00	2025-06-13	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	4
41	18	ticket	D-8050	Proveedor schamber	Gasto 3 para Vinnie	100936.00	2025-06-13	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	5
42	18	ticket	D-3064	Proveedor schamber	Gasto 4 para Vinnie	35593.00	2025-06-13	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	8
43	19	ticket	D-3828	Proveedor roob-weh	Gasto 0 para Marge	87757.00	2025-06-26	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	1
44	19	ticket	D-4713	Proveedor roob-weh	Gasto 1 para Marge	36090.00	2025-06-26	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	1
45	20	ticket	D-1826	Proveedor moore-wh	Gasto 0 para Mallory	1684.00	2025-03-01	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	5
46	20	ticket	D-1586	Proveedor moore-wh	Gasto 1 para Mallory	66928.00	2025-03-01	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	8
47	21	ticket	D-9086	Proveedor moore-wh	Gasto 0 para Mallory	118341.00	2024-12-03	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	4
48	21	ticket	D-5373	Proveedor moore-wh	Gasto 1 para Mallory	91565.00	2024-12-03	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	2
49	21	ticket	D-2842	Proveedor moore-wh	Gasto 2 para Mallory	70449.00	2024-12-03	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	9
50	22	ticket	D-4780	Proveedor moore-wh	Gasto 0 para Mallory	65121.00	2025-06-02	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	4
51	22	ticket	D-8082	Proveedor moore-wh	Gasto 1 para Mallory	5060.00	2025-06-02	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	8
52	22	ticket	D-2372	Proveedor moore-wh	Gasto 2 para Mallory	103422.00	2025-06-02	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	7
53	23	ticket	D-8212	Proveedor larson-d	Gasto 0 para Lurline	15718.00	2024-12-24	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	4
54	23	ticket	D-2070	Proveedor larson-d	Gasto 1 para Lurline	6004.00	2024-12-24	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	10
55	23	ticket	D-6131	Proveedor larson-d	Gasto 2 para Lurline	72544.00	2024-12-24	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	10
56	23	ticket	D-4294	Proveedor larson-d	Gasto 3 para Lurline	118813.00	2024-12-24	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	1
57	24	ticket	D-2022	Proveedor oconner-	Gasto 0 para Ibrahim	79402.00	2025-08-19	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	4
58	24	ticket	D-4957	Proveedor oconner-	Gasto 1 para Ibrahim	58371.00	2025-08-19	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	4
59	24	ticket	D-4841	Proveedor oconner-	Gasto 2 para Ibrahim	100603.00	2025-08-19	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	5
60	24	ticket	D-5752	Proveedor oconner-	Gasto 3 para Ibrahim	101403.00	2025-08-19	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	7
61	25	ticket	D-6056	Proveedor lakin-cr	Gasto 0 para Amos	100220.00	2024-12-07	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	10
62	26	ticket	D-9862	Proveedor lakin-cr	Gasto 0 para Amos	71808.00	2025-04-02	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	9
63	27	ticket	D-9363	Proveedor lakin-cr	Gasto 0 para Amos	43486.00	2024-09-08	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	9
64	27	ticket	D-3018	Proveedor lakin-cr	Gasto 1 para Amos	115281.00	2024-09-08	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	10
65	27	ticket	D-3745	Proveedor lakin-cr	Gasto 2 para Amos	66978.00	2024-09-08	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	1
66	27	ticket	D-6041	Proveedor lakin-cr	Gasto 3 para Amos	51216.00	2024-09-08	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	5
67	28	ticket	D-3368	Proveedor lakin-cr	Gasto 0 para Amos	42156.00	2024-09-03	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	3
68	28	ticket	D-2666	Proveedor lakin-cr	Gasto 1 para Amos	111543.00	2024-09-03	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	6
69	28	ticket	D-3632	Proveedor lakin-cr	Gasto 2 para Amos	65113.00	2024-09-03	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	5
70	28	ticket	D-4887	Proveedor lakin-cr	Gasto 3 para Amos	97308.00	2024-09-03	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	8
71	28	ticket	D-3958	Proveedor lakin-cr	Gasto 4 para Amos	18201.00	2024-09-03	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	8
72	29	ticket	D-2887	Proveedor bradtke-	Gasto 0 para Hank	59754.00	2024-12-02	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	10
73	30	ticket	D-5832	Proveedor bradtke-	Gasto 0 para Hank	30983.00	2025-07-29	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	10
74	30	ticket	D-3299	Proveedor bradtke-	Gasto 1 para Hank	101225.00	2025-07-29	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	5
75	30	ticket	D-3983	Proveedor bradtke-	Gasto 2 para Hank	49140.00	2025-07-29	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	1
76	30	ticket	D-6026	Proveedor bradtke-	Gasto 3 para Hank	57474.00	2025-07-29	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	6
77	31	ticket	D-9947	Proveedor bradtke-	Gasto 0 para Hank	21609.00	2025-02-11	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	4
78	31	ticket	D-5407	Proveedor bradtke-	Gasto 1 para Hank	50516.00	2025-02-11	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	5
79	31	ticket	D-4397	Proveedor bradtke-	Gasto 2 para Hank	72784.00	2025-02-11	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	2
80	31	ticket	D-5756	Proveedor bradtke-	Gasto 3 para Hank	8467.00	2025-02-11	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	4
81	31	ticket	D-2812	Proveedor bradtke-	Gasto 4 para Hank	105658.00	2025-02-11	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	7
82	32	ticket	D-9520	Proveedor wiegand-	Gasto 0 para Yoshiko	100264.00	2025-03-04	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	5
83	33	ticket	D-8666	Proveedor wiegand-	Gasto 0 para Yoshiko	16193.00	2024-12-28	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	4
84	33	ticket	D-1441	Proveedor wiegand-	Gasto 1 para Yoshiko	99873.00	2024-12-28	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	9
85	33	ticket	D-7503	Proveedor wiegand-	Gasto 2 para Yoshiko	88831.00	2024-12-28	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	6
86	33	ticket	D-3980	Proveedor wiegand-	Gasto 3 para Yoshiko	27968.00	2024-12-28	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	5
87	33	ticket	D-5840	Proveedor wiegand-	Gasto 4 para Yoshiko	46125.00	2024-12-28	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	3
88	34	ticket	D-6181	Proveedor wiegand-	Gasto 0 para Yoshiko	48410.00	2025-09-14	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	6
89	34	ticket	D-4707	Proveedor wiegand-	Gasto 1 para Yoshiko	10023.00	2025-09-14	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	3
90	34	ticket	D-3446	Proveedor wiegand-	Gasto 2 para Yoshiko	9066.00	2025-09-14	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	4
91	34	ticket	D-2311	Proveedor wiegand-	Gasto 3 para Yoshiko	65858.00	2025-09-14	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	7
92	34	ticket	D-6185	Proveedor wiegand-	Gasto 4 para Yoshiko	109062.00	2025-09-14	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	10
93	35	ticket	D-3251	Proveedor marks-de	Gasto 0 para Cesar	31626.00	2025-08-05	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	4
94	36	ticket	D-9982	Proveedor marks-de	Gasto 0 para Cesar	100238.00	2024-11-21	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	2
95	36	ticket	D-3282	Proveedor marks-de	Gasto 1 para Cesar	28065.00	2024-11-21	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	1
96	36	ticket	D-2933	Proveedor marks-de	Gasto 2 para Cesar	24648.00	2024-11-21	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33	3
97	37	ticket	D-6231	Proveedor marks-de	Gasto 0 para Cesar	61900.00	2025-01-12	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	3
98	37	ticket	D-4282	Proveedor marks-de	Gasto 1 para Cesar	50522.00	2025-01-12	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	5
99	37	ticket	D-9351	Proveedor marks-de	Gasto 2 para Cesar	85464.00	2025-01-12	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	4
100	38	ticket	D-1490	Proveedor marks-de	Gasto 0 para Cesar	67825.00	2025-02-18	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	9
101	38	ticket	D-2095	Proveedor marks-de	Gasto 1 para Cesar	55532.00	2025-02-18	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	1
102	38	ticket	D-8628	Proveedor marks-de	Gasto 2 para Cesar	44902.00	2025-02-18	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	3
103	38	ticket	D-5207	Proveedor marks-de	Gasto 3 para Cesar	78131.00	2025-02-18	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	7
104	39	ticket	D-5135	Proveedor harvey-w	Gasto 0 para Estella	19412.00	2025-01-24	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	9
105	40	ticket	D-4491	Proveedor harvey-w	Gasto 0 para Estella	16056.00	2025-08-31	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	8
106	40	ticket	D-5660	Proveedor harvey-w	Gasto 1 para Estella	32399.00	2025-08-31	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	10
107	40	ticket	D-3927	Proveedor harvey-w	Gasto 2 para Estella	66553.00	2025-08-31	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	1
108	40	ticket	D-6655	Proveedor harvey-w	Gasto 3 para Estella	6721.00	2025-08-31	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	2
109	40	ticket	D-3363	Proveedor harvey-w	Gasto 4 para Estella	97779.00	2025-08-31	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	3
110	41	ticket	D-5063	Proveedor harvey-w	Gasto 0 para Estella	8227.00	2025-01-04	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	1
111	41	ticket	D-9891	Proveedor harvey-w	Gasto 1 para Estella	118027.00	2025-01-04	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	4
112	41	ticket	D-2131	Proveedor harvey-w	Gasto 2 para Estella	32799.00	2025-01-04	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	4
113	41	ticket	D-9482	Proveedor harvey-w	Gasto 3 para Estella	32701.00	2025-01-04	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	10
114	41	ticket	D-3204	Proveedor harvey-w	Gasto 4 para Estella	35124.00	2025-01-04	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	6
115	42	ticket	D-6637	Proveedor harvey-w	Gasto 0 para Estella	1047.00	2024-09-18	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	9
116	43	ticket	D-2284	Proveedor schmeler	Gasto 0 para Julianne	115246.00	2025-02-22	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	6
117	43	ticket	D-9678	Proveedor schmeler	Gasto 1 para Julianne	47652.00	2025-02-22	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	5
118	43	ticket	D-1860	Proveedor schmeler	Gasto 2 para Julianne	46832.00	2025-02-22	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	5
119	43	ticket	D-3672	Proveedor schmeler	Gasto 3 para Julianne	96710.00	2025-02-22	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	9
120	43	ticket	D-4908	Proveedor schmeler	Gasto 4 para Julianne	7063.00	2025-02-22	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	2
121	44	ticket	D-5551	Proveedor simonis-	Gasto 0 para Claude	111598.00	2025-02-16	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	10
122	44	ticket	D-3533	Proveedor simonis-	Gasto 1 para Claude	6008.00	2025-02-16	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	3
123	44	ticket	D-9057	Proveedor simonis-	Gasto 2 para Claude	65642.00	2025-02-16	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	3
124	45	ticket	D-2764	Proveedor simonis-	Gasto 0 para Claude	89996.00	2025-01-10	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	9
125	45	ticket	D-1974	Proveedor simonis-	Gasto 1 para Claude	101217.00	2025-01-10	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	4
126	46	ticket	D-4297	Proveedor simonis-	Gasto 0 para Claude	73613.00	2024-12-02	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	3
127	47	ticket	D-8124	Proveedor grimes-h	Gasto 0 para Katlynn	94521.00	2025-07-27	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	4
128	47	ticket	D-4182	Proveedor grimes-h	Gasto 1 para Katlynn	4420.00	2025-07-27	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	8
129	48	ticket	D-8333	Proveedor grimes-h	Gasto 0 para Katlynn	96034.00	2024-10-19	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	2
130	48	ticket	D-7562	Proveedor grimes-h	Gasto 1 para Katlynn	95856.00	2024-10-19	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	1
131	49	ticket	D-8866	Proveedor swaniaws	Gasto 0 para Charles	18330.00	2025-08-19	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	5
132	50	ticket	D-3873	Proveedor mclaughl	Gasto 0 para Otha	112377.00	2025-07-24	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	10
133	50	ticket	D-8689	Proveedor mclaughl	Gasto 1 para Otha	12535.00	2025-07-24	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	9
134	50	ticket	D-5536	Proveedor mclaughl	Gasto 2 para Otha	32443.00	2025-07-24	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	10
135	50	ticket	D-5735	Proveedor mclaughl	Gasto 3 para Otha	84814.00	2025-07-24	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	6
136	51	ticket	D-7050	Proveedor mclaughl	Gasto 0 para Otha	103835.00	2025-08-16	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	3
137	51	ticket	D-9788	Proveedor mclaughl	Gasto 1 para Otha	84620.00	2025-08-16	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	4
138	51	ticket	D-6830	Proveedor mclaughl	Gasto 2 para Otha	30994.00	2025-08-16	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	8
139	51	ticket	D-9248	Proveedor mclaughl	Gasto 3 para Otha	85426.00	2025-08-16	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	6
140	52	ticket	D-5711	Proveedor mclaughl	Gasto 0 para Otha	18955.00	2024-11-25	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	6
141	53	ticket	D-8716	Proveedor mclaughl	Gasto 0 para Otha	27817.00	2025-06-20	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	8
142	53	ticket	D-7636	Proveedor mclaughl	Gasto 1 para Otha	6041.00	2025-06-20	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	5
143	54	ticket	D-2865	Proveedor simonis-	Gasto 0 para Frederick	50049.00	2024-08-26	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	7
144	54	ticket	D-3362	Proveedor simonis-	Gasto 1 para Frederick	110386.00	2024-08-26	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	1
145	54	ticket	D-2939	Proveedor simonis-	Gasto 2 para Frederick	7194.00	2024-08-26	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	2
146	54	ticket	D-5284	Proveedor simonis-	Gasto 3 para Frederick	39331.00	2024-08-26	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	7
147	55	ticket	D-9382	Proveedor simonis-	Gasto 0 para Frederick	61965.00	2025-01-11	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	6
148	56	ticket	D-4128	Proveedor christia	Gasto 0 para Alexa	22854.00	2025-02-16	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	3
149	56	ticket	D-7419	Proveedor christia	Gasto 1 para Alexa	92831.00	2025-02-16	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	4
150	57	ticket	D-2957	Proveedor christia	Gasto 0 para Alexa	63339.00	2024-09-21	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	10
151	57	ticket	D-3756	Proveedor christia	Gasto 1 para Alexa	25552.00	2024-09-21	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	5
152	57	ticket	D-3082	Proveedor christia	Gasto 2 para Alexa	37882.00	2024-09-21	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	9
153	58	ticket	D-6888	Proveedor dooley-p	Gasto 0 para Madonna	93721.00	2024-09-19	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	9
154	58	ticket	D-4741	Proveedor dooley-p	Gasto 1 para Madonna	42090.00	2024-09-19	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	6
155	58	ticket	D-2772	Proveedor dooley-p	Gasto 2 para Madonna	80911.00	2024-09-19	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	3
156	58	ticket	D-2298	Proveedor dooley-p	Gasto 3 para Madonna	89603.00	2024-09-19	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	3
157	59	ticket	D-2981	Proveedor dooley-p	Gasto 0 para Madonna	89077.00	2025-05-26	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	4
158	60	ticket	D-7619	Proveedor dooley-p	Gasto 0 para Madonna	117702.00	2025-04-02	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	8
159	60	ticket	D-7853	Proveedor dooley-p	Gasto 1 para Madonna	28217.00	2025-04-02	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	1
160	60	ticket	D-7290	Proveedor dooley-p	Gasto 2 para Madonna	27095.00	2025-04-02	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	5
161	60	ticket	D-8131	Proveedor dooley-p	Gasto 3 para Madonna	21894.00	2025-04-02	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	7
162	61	ticket	D-1317	Proveedor ebert-or	Gasto 0 para Abagail	31989.00	2025-07-11	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	6
163	61	ticket	D-3544	Proveedor ebert-or	Gasto 1 para Abagail	78775.00	2025-07-11	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	5
164	61	ticket	D-3619	Proveedor ebert-or	Gasto 2 para Abagail	73698.00	2025-07-11	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	9
165	61	ticket	D-3460	Proveedor ebert-or	Gasto 3 para Abagail	59962.00	2025-07-11	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	2
166	62	ticket	D-8924	Proveedor ebert-or	Gasto 0 para Abagail	34986.00	2024-11-25	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	8
167	62	ticket	D-5954	Proveedor ebert-or	Gasto 1 para Abagail	36909.00	2024-11-25	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	10
168	63	ticket	D-2815	Proveedor ebert-or	Gasto 0 para Abagail	20067.00	2024-10-12	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	1
169	63	ticket	D-9051	Proveedor ebert-or	Gasto 1 para Abagail	66236.00	2024-10-12	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	6
170	63	ticket	D-8858	Proveedor ebert-or	Gasto 2 para Abagail	1636.00	2024-10-12	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	4
171	63	ticket	D-4101	Proveedor ebert-or	Gasto 3 para Abagail	3251.00	2024-10-12	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	7
172	63	ticket	D-2523	Proveedor ebert-or	Gasto 4 para Abagail	24068.00	2024-10-12	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	4
173	64	ticket	D-6929	Proveedor ebert-or	Gasto 0 para Abagail	40222.00	2024-11-13	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	3
174	65	ticket	D-5838	Proveedor terry-ki	Gasto 0 para Mireya	56412.00	2025-04-14	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	10
175	65	ticket	D-9270	Proveedor terry-ki	Gasto 1 para Mireya	94487.00	2025-04-14	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	5
176	66	ticket	D-6634	Proveedor terry-ki	Gasto 0 para Mireya	36078.00	2025-07-12	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	1
177	66	ticket	D-6059	Proveedor terry-ki	Gasto 1 para Mireya	42072.00	2025-07-12	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	5
178	67	ticket	D-5403	Proveedor terry-ki	Gasto 0 para Mireya	38336.00	2025-01-09	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	7
179	67	ticket	D-4693	Proveedor terry-ki	Gasto 1 para Mireya	11051.00	2025-01-09	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	10
180	68	ticket	D-5422	Proveedor terry-ki	Gasto 0 para Mireya	65401.00	2025-08-19	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	7
181	68	ticket	D-4716	Proveedor terry-ki	Gasto 1 para Mireya	86941.00	2025-08-19	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	4
182	68	ticket	D-4823	Proveedor terry-ki	Gasto 2 para Mireya	69507.00	2025-08-19	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	2
183	69	ticket	D-6344	Proveedor farrell-	Gasto 0 para Destiny	11800.00	2024-12-27	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	3
184	70	ticket	D-8842	Proveedor farrell-	Gasto 0 para Destiny	6224.00	2024-11-07	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	7
185	70	ticket	D-9919	Proveedor farrell-	Gasto 1 para Destiny	110622.00	2024-11-07	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	9
186	70	ticket	D-5548	Proveedor farrell-	Gasto 2 para Destiny	76391.00	2024-11-07	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	2
187	70	ticket	D-8905	Proveedor farrell-	Gasto 3 para Destiny	83713.00	2024-11-07	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	10
188	70	ticket	D-4484	Proveedor farrell-	Gasto 4 para Destiny	83802.00	2024-11-07	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	2
189	71	ticket	D-6165	Proveedor weimann-	Gasto 0 para Chloe	9245.00	2025-06-25	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	6
190	71	ticket	D-7549	Proveedor weimann-	Gasto 1 para Chloe	9159.00	2025-06-25	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	5
191	72	ticket	D-3684	Proveedor sistema	Gasto 0 para Tesorero	76588.00	2025-01-15	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	6
192	72	ticket	D-3098	Proveedor sistema	Gasto 1 para Tesorero	21690.00	2025-01-15	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	1
193	73	ticket	D-5195	Proveedor sistema	Gasto 0 para Tesorero	29429.00	2025-02-23	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	6
194	73	ticket	D-1220	Proveedor sistema	Gasto 1 para Tesorero	30409.00	2025-02-23	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	5
195	73	ticket	D-4987	Proveedor sistema	Gasto 2 para Tesorero	11483.00	2025-02-23	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	10
196	74	ticket	D-6487	Proveedor sistema	Gasto 0 para Tesorero	73364.00	2024-08-26	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	1
197	74	ticket	D-3469	Proveedor sistema	Gasto 1 para Tesorero	56456.00	2024-08-26	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	8
198	75	ticket	D-1255	Proveedor sistema	Gasto 0 para Tesorero	112741.00	2025-01-09	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	10
199	75	ticket	D-6033	Proveedor sistema	Gasto 1 para Tesorero	117024.00	2025-01-09	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	5
200	75	ticket	D-3955	Proveedor sistema	Gasto 2 para Tesorero	52597.00	2025-01-09	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34	5
201	77	ticket	JLDOQU	Proveedor Seed	Item seed	48681.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	10
202	77	ticket	ZKB6GY	Proveedor Seed	Item seed	15916.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	5
203	78	ticket	SQG2K2	Proveedor Seed	Item seed	42665.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	3
204	78	ticket	DBVPAN	Proveedor Seed	Item seed	28942.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	8
205	79	ticket	HAPLCN	Proveedor Seed	Item seed	35882.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	4
206	79	ticket	PDAWQF	Proveedor Seed	Item seed	20661.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	9
207	80	ticket	3RGWIA	Proveedor Seed	Item seed	48357.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	3
208	81	ticket	XYPPMD	Proveedor Seed	Item seed	21046.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	3
209	82	ticket	WEXQOF	Proveedor Seed	Item seed	40717.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	5
210	82	ticket	LUXZGG	Proveedor Seed	Item seed	25861.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	1
211	83	ticket	LB3XLZ	Proveedor Seed	Item seed	46504.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	4
212	83	ticket	VLLI6I	Proveedor Seed	Item seed	43908.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	8
213	84	ticket	5ITFQ1	Proveedor Seed	Item seed	21612.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	9
214	85	ticket	UTW37Q	Proveedor Seed	Item seed	29710.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	3
215	85	ticket	DL9GLP	Proveedor Seed	Item seed	44919.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	3
216	86	ticket	5TCOYL	Proveedor Seed	Item seed	13658.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	3
217	87	ticket	XTZU67	Proveedor Seed	Item seed	4741.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	8
218	88	ticket	FHKVYN	Proveedor Seed	Item seed	37979.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	1
219	88	ticket	JT3TKP	Proveedor Seed	Item seed	4028.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	8
220	89	ticket	5JWXZP	Proveedor Seed	Item seed	31599.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	3
221	89	ticket	X9DPTE	Proveedor Seed	Item seed	37735.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	6
222	90	ticket	FDDOKX	Proveedor Seed	Item seed	30102.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	2
223	90	ticket	CMNADA	Proveedor Seed	Item seed	9520.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	9
224	91	ticket	OEPTIV	Proveedor Seed	Item seed	40867.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	9
225	91	ticket	OLJITK	Proveedor Seed	Item seed	11120.00	2025-09-16	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00	7
226	92	boleta	5564456	hhh	almuerzo	12500.00	2025-09-17	\N	t	2025-09-17 11:47:23	2025-09-17 11:47:23	2
227	92	boleta	12123	sdfsd	p1	8580.00	2025-09-17	\N	t	2025-09-17 11:47:23	2025-09-17 11:47:23	1
\.


--
-- Data for Name: expenses; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.expenses (id, expense_number, account_id, submitted_by, total_amount, description, expense_date, status, reviewed_by, submitted_at, reviewed_at, rejection_reason, is_enabled, created_at, updated_at) FROM stdin;
2	RND-2025-002	2	2	120000.00	Rendición de gastos - Cuadrilla Sur	2025-09-09	approved	1	2025-09-10 12:06:28	2025-09-11 12:06:28	\N	t	2025-09-16 12:06:28	2025-09-16 12:06:28
41	RND-2025-000041	20	18	226878.00	Rendición automática para Estella Harvey Witting	2025-01-04	approved	1	2025-01-04 15:04:34	2025-09-16 15:16:46	\N	t	2025-09-16 15:04:34	2025-09-16 15:16:46
4	RND-2025-000004	3	1	210825.00	Rendición automática para Carlos Mendoza	2024-09-14	approved	1	2024-09-14 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
5	RND-2025-000005	4	2	196449.00	Rendición automática para María García	2024-10-27	approved	1	2024-10-27 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
18	RND-2025-000018	11	9	426565.00	Rendición automática para Vinnie Schamberger Reichert	2025-06-13	approved	1	2025-06-13 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
6	RND-2025-000006	4	2	46831.00	Rendición automática para María García	2025-07-22	approved	1	2025-07-22 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
7	RND-2025-000007	5	3	114041.00	Rendición automática para Luis Rodríguez	2025-02-05	approved	1	2025-02-05 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
8	RND-2025-000008	6	4	78545.00	Rendición automática para Ana López	2024-12-03	submitted	\N	2024-12-03 15:04:33	\N	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
21	RND-2025-000021	13	11	280355.00	Rendición automática para Mallory Moore White	2024-12-03	approved	1	2024-12-03 15:04:33	2025-09-16 15:16:59	\N	t	2025-09-16 15:04:33	2025-09-16 15:16:59
10	RND-2025-000010	7	5	67844.00	Rendición automática para Pedro Sánchez	2025-07-12	approved	1	2025-07-12 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
19	RND-2025-000019	12	10	123847.00	Rendición automática para Marge Roob Wehner	2025-06-26	approved	1	2025-06-26 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
11	RND-2025-000011	8	6	183135.00	Rendición automática para Jamarcus Torp Quitzon	2025-01-01	approved	1	2025-01-01 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
13	RND-2025-000013	10	8	225027.00	Rendición automática para Raegan Auer O'Hara	2025-06-04	approved	1	2025-06-04 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
32	RND-2025-000032	18	16	100264.00	Rendición automática para Yoshiko Wiegand McClure	2025-03-04	approved	1	2025-03-04 15:04:33	2025-09-16 15:16:27	\N	t	2025-09-16 15:04:33	2025-09-16 15:16:27
26	RND-2025-000026	16	14	71808.00	Rendición automática para Amos Lakin Crona	2025-04-02	approved	1	2025-04-02 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
15	RND-2025-000015	10	8	344586.00	Rendición automática para Raegan Auer O'Hara	2025-06-07	approved	1	2025-06-07 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
22	RND-2025-000022	13	11	173603.00	Rendición automática para Mallory Moore White	2025-06-02	approved	1	2025-06-02 15:04:33	2025-09-16 15:16:16	\N	t	2025-09-16 15:04:33	2025-09-16 15:16:16
20	RND-2025-000020	13	11	68612.00	Rendición automática para Mallory Moore White	2025-03-01	approved	1	2025-03-01 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
17	RND-2025-000017	11	9	101220.00	Rendición automática para Vinnie Schamberger Reichert	2025-04-19	approved	1	2025-04-19 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
14	RND-2025-000014	10	8	75930.00	Rendición automática para Raegan Auer O'Hara	2025-04-05	approved	1	2025-04-05 15:04:33	2025-09-16 15:16:24	\N	t	2025-09-16 15:04:33	2025-09-16 15:16:24
23	RND-2025-000023	14	12	213079.00	Rendición automática para Lurline Larson Dach	2024-12-24	approved	1	2024-12-24 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
39	RND-2025-000039	20	18	19412.00	Rendición automática para Estella Harvey Witting	2025-01-24	approved	1	2025-01-24 15:04:34	2025-09-16 15:16:36	\N	t	2025-09-16 15:04:34	2025-09-16 15:16:36
24	RND-2025-000024	15	13	339779.00	Rendición automática para Ibrahim O'Conner Casper	2025-08-19	approved	1	2025-08-19 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
27	RND-2025-000027	16	14	276961.00	Rendición automática para Amos Lakin Crona	2024-09-08	approved	1	2024-09-08 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
25	RND-2025-000025	16	14	100220.00	Rendición automática para Amos Lakin Crona	2024-12-07	approved	1	2024-12-07 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
28	RND-2025-000028	16	14	334321.00	Rendición automática para Amos Lakin Crona	2024-09-03	submitted	\N	2024-09-03 15:04:33	\N	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
29	RND-2025-000029	17	15	59754.00	Rendición automática para Hank Bradtke Stehr	2024-12-02	approved	1	2024-12-02 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
31	RND-2025-000031	17	15	259034.00	Rendición automática para Hank Bradtke Stehr	2025-02-11	approved	1	2025-02-11 15:04:33	2025-09-16 15:16:34	\N	t	2025-09-16 15:04:33	2025-09-16 15:16:34
30	RND-2025-000030	17	15	238822.00	Rendición automática para Hank Bradtke Stehr	2025-07-29	approved	1	2025-07-29 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
33	RND-2025-000033	18	16	278990.00	Rendición automática para Yoshiko Wiegand McClure	2024-12-28	approved	1	2024-12-28 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
35	RND-2025-000035	19	17	31626.00	Rendición automática para Cesar Marks Denesik	2025-08-05	approved	1	2025-08-05 15:04:33	2025-09-16 15:15:59	\N	t	2025-09-16 15:04:33	2025-09-16 15:15:59
16	RND-2025-000016	11	9	214182.00	Rendición automática para Vinnie Schamberger Reichert	2025-07-01	approved	1	2025-07-01 15:04:33	2025-09-16 15:16:09	\N	t	2025-09-16 15:04:33	2025-09-16 15:16:09
36	RND-2025-000036	19	17	152951.00	Rendición automática para Cesar Marks Denesik	2024-11-21	approved	1	2024-11-21 15:04:33	2025-09-16 15:04:33	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:33
37	RND-2025-000037	19	17	197886.00	Rendición automática para Cesar Marks Denesik	2025-01-12	submitted	\N	2025-01-12 15:04:33	\N	\N	t	2025-09-16 15:04:33	2025-09-16 15:04:34
38	RND-2025-000038	19	17	246390.00	Rendición automática para Cesar Marks Denesik	2025-02-18	approved	1	2025-02-18 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
3	RND-2025-000003	3	1	80376.00	Rendición automática para Carlos Mendoza	2025-02-03	approved	1	2025-02-03 15:04:33	2025-09-16 15:16:38	\N	t	2025-09-16 15:04:33	2025-09-16 15:16:38
34	RND-2025-000034	18	16	242419.00	Rendición automática para Yoshiko Wiegand McClure	2025-09-14	approved	1	2025-09-14 15:04:33	2025-09-16 15:15:43	\N	t	2025-09-16 15:04:33	2025-09-16 15:15:43
9	RND-2025-000009	7	5	208134.00	Rendición automática para Pedro Sánchez	2024-08-31	approved	1	2024-08-31 15:04:33	2025-09-16 15:16:53	\N	t	2025-09-16 15:04:33	2025-09-16 15:16:53
1	RND-2025-001	1	1	85000.00	Rendición de gastos - Cuadrilla Norte	2025-09-13	approved	1	2025-09-15 12:06:28	2025-09-16 15:15:28	\N	t	2025-09-16 12:06:28	2025-09-16 15:15:28
42	RND-2025-000042	20	18	1047.00	Rendición automática para Estella Harvey Witting	2024-09-18	approved	1	2024-09-18 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
56	RND-2025-000056	27	25	115685.00	Rendición automática para Alexa Christiansen Wiegand	2025-02-16	approved	1	2025-02-16 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
43	RND-2025-000043	21	19	313503.00	Rendición automática para Julianne Schmeler Volkman	2025-02-22	approved	1	2025-02-22 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
75	RND-2025-000075	33	31	282362.00	Rendición automática para Tesorero Sistema	2025-01-09	approved	1	2025-01-09 15:04:34	2025-09-16 15:16:41	\N	t	2025-09-16 15:04:34	2025-09-16 15:16:41
45	RND-2025-000045	22	20	191213.00	Rendición automática para Claude Simonis Marvin	2025-01-10	approved	1	2025-01-10 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
46	RND-2025-000046	22	20	73613.00	Rendición automática para Claude Simonis Marvin	2024-12-02	approved	1	2024-12-02 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
47	RND-2025-000047	23	21	98941.00	Rendición automática para Katlynn Grimes Hilpert	2025-07-27	approved	1	2025-07-27 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
48	RND-2025-000048	23	21	191890.00	Rendición automática para Katlynn Grimes Hilpert	2024-10-19	submitted	\N	2024-10-19 15:04:34	\N	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
44	RND-2025-000044	22	20	183248.00	Rendición automática para Claude Simonis Marvin	2025-02-16	approved	1	2025-02-16 15:04:34	2025-09-16 15:16:30	\N	t	2025-09-16 15:04:34	2025-09-16 15:16:30
50	RND-2025-000050	25	23	242169.00	Rendición automática para Otha McLaughlin Cruickshank	2025-07-24	approved	1	2025-07-24 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
58	RND-2025-000058	28	26	306325.00	Rendición automática para Madonna Dooley Paucek	2024-09-19	approved	1	2024-09-19 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
51	RND-2025-000051	25	23	304875.00	Rendición automática para Otha McLaughlin Cruickshank	2025-08-16	approved	1	2025-08-16 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
52	RND-2025-000052	25	23	18955.00	Rendición automática para Otha McLaughlin Cruickshank	2024-11-25	approved	1	2024-11-25 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
53	RND-2025-000053	25	23	33858.00	Rendición automática para Otha McLaughlin Cruickshank	2025-06-20	approved	1	2025-06-20 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
57	RND-2025-000057	27	25	126773.00	Rendición automática para Alexa Christiansen Wiegand	2024-09-21	approved	1	2024-09-21 15:04:34	2025-09-16 15:16:57	\N	t	2025-09-16 15:04:34	2025-09-16 15:16:57
59	RND-2025-000059	28	26	89077.00	Rendición automática para Madonna Dooley Paucek	2025-05-26	approved	1	2025-05-26 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
55	RND-2025-000055	26	24	61965.00	Rendición automática para Frederick Simonis Abernathy	2025-01-11	approved	1	2025-01-11 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
65	RND-2025-000065	30	28	150899.00	Rendición automática para Mireya Terry Kihn	2025-04-14	approved	1	2025-04-14 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
60	RND-2025-000060	28	26	194908.00	Rendición automática para Madonna Dooley Paucek	2025-04-02	approved	1	2025-04-02 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
74	RND-2025-000074	33	31	129820.00	Rendición automática para Tesorero Sistema	2024-08-26	submitted	\N	2024-08-26 15:04:34	\N	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
61	RND-2025-000061	29	27	244424.00	Rendición automática para Abagail Ebert Ortiz	2025-07-11	approved	1	2025-07-11 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
62	RND-2025-000062	29	27	71895.00	Rendición automática para Abagail Ebert Ortiz	2024-11-25	submitted	\N	2024-11-25 15:04:34	\N	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
66	RND-2025-000066	30	28	78150.00	Rendición automática para Mireya Terry Kihn	2025-07-12	approved	1	2025-07-12 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
63	RND-2025-000063	29	27	115258.00	Rendición automática para Abagail Ebert Ortiz	2024-10-12	approved	1	2024-10-12 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
64	RND-2025-000064	29	27	40222.00	Rendición automática para Abagail Ebert Ortiz	2024-11-13	approved	1	2024-11-13 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
67	RND-2025-000067	30	28	49387.00	Rendición automática para Mireya Terry Kihn	2025-01-09	submitted	\N	2025-01-09 15:04:34	\N	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
72	RND-2025-000072	33	31	98278.00	Rendición automática para Tesorero Sistema	2025-01-15	approved	1	2025-01-15 15:04:34	2025-09-16 15:16:44	\N	t	2025-09-16 15:04:34	2025-09-16 15:16:44
71	RND-2025-000071	32	30	18404.00	Rendición automática para Chloe Weimann McLaughlin	2025-06-25	approved	1	2025-06-25 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
69	RND-2025-000069	31	29	11800.00	Rendición automática para Destiny Farrell Turner	2024-12-27	approved	1	2024-12-27 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
70	RND-2025-000070	31	29	360752.00	Rendición automática para Destiny Farrell Turner	2024-11-07	submitted	\N	2024-11-07 15:04:34	\N	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
54	RND-2025-000054	26	24	206960.00	Rendición automática para Frederick Simonis Abernathy	2024-08-26	approved	1	2024-08-26 15:04:34	2025-09-16 15:16:51	\N	t	2025-09-16 15:04:34	2025-09-16 15:16:51
73	RND-2025-000073	33	31	71321.00	Rendición automática para Tesorero Sistema	2025-02-23	approved	1	2025-02-23 15:04:34	2025-09-16 15:04:34	\N	t	2025-09-16 15:04:34	2025-09-16 15:04:34
78	RND-2025-000077	5	3	71607.00	Rendición generada para gráficos	2025-09-16	approved	1	2025-09-16 15:14:00	2025-09-16 15:14:00	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00
77	RND-2025-000076	3	1	64597.00	Rendición generada para gráficos	2025-09-16	approved	1	2025-09-16 15:14:00	2025-09-16 15:14:00	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00
79	RND-2025-000078	6	4	56543.00	Rendición generada para gráficos	2025-09-16	approved	1	2025-09-16 15:14:00	2025-09-16 15:14:00	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00
80	RND-2025-000079	8	6	48357.00	Rendición generada para gráficos	2025-09-16	approved	1	2025-09-16 15:14:00	2025-09-16 15:14:00	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00
81	RND-2025-000080	9	7	21046.00	Rendición generada para gráficos	2025-09-16	approved	1	2025-09-16 15:14:00	2025-09-16 15:14:00	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00
82	RND-2025-000081	13	11	66578.00	Rendición generada para gráficos	2025-09-16	approved	1	2025-09-16 15:14:00	2025-09-16 15:14:00	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00
68	RND-2025-000068	30	28	221849.00	Rendición automática para Mireya Terry Kihn	2025-08-19	approved	1	2025-08-19 15:04:34	2025-09-16 15:15:48	\N	t	2025-09-16 15:04:34	2025-09-16 15:15:48
83	RND-2025-000082	14	12	90412.00	Rendición generada para gráficos	2025-09-16	approved	1	2025-09-16 15:14:00	2025-09-16 15:14:00	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00
84	RND-2025-000083	18	16	21612.00	Rendición generada para gráficos	2025-09-16	approved	1	2025-09-16 15:14:00	2025-09-16 15:14:00	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00
85	RND-2025-000084	19	17	74629.00	Rendición generada para gráficos	2025-09-16	approved	1	2025-09-16 15:14:00	2025-09-16 15:14:00	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00
86	RND-2025-000085	21	19	13658.00	Rendición generada para gráficos	2025-09-16	approved	1	2025-09-16 15:14:00	2025-09-16 15:14:00	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00
87	RND-2025-000086	22	20	4741.00	Rendición generada para gráficos	2025-09-16	approved	1	2025-09-16 15:14:00	2025-09-16 15:14:00	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00
88	RND-2025-000087	24	22	42007.00	Rendición generada para gráficos	2025-09-16	approved	1	2025-09-16 15:14:00	2025-09-16 15:14:00	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00
89	RND-2025-000088	26	24	69334.00	Rendición generada para gráficos	2025-09-16	approved	1	2025-09-16 15:14:00	2025-09-16 15:14:00	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00
90	RND-2025-000089	31	29	39622.00	Rendición generada para gráficos	2025-09-16	approved	1	2025-09-16 15:14:00	2025-09-16 15:14:00	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00
91	RND-2025-000090	33	31	51987.00	Rendición generada para gráficos	2025-09-16	approved	1	2025-09-16 15:14:00	2025-09-16 15:14:00	\N	t	2025-09-16 15:14:00	2025-09-16 15:14:00
40	RND-2025-000040	20	18	219508.00	Rendición automática para Estella Harvey Witting	2025-08-31	approved	1	2025-08-31 15:04:34	2025-09-16 15:15:37	\N	t	2025-09-16 15:04:34	2025-09-16 15:15:37
49	RND-2025-000049	24	22	18330.00	Rendición automática para Charles Swaniawski Williamson	2025-08-19	approved	1	2025-08-19 15:04:34	2025-09-16 15:15:53	\N	t	2025-09-16 15:04:34	2025-09-16 15:15:53
12	RND-2025-000012	9	7	11507.00	Rendición automática para Mina Langosh Legros	2024-09-07	approved	1	2024-09-07 15:04:33	2025-09-16 15:16:55	\N	t	2025-09-16 15:04:33	2025-09-16 15:16:55
92	RND-2025-000091	3	1	21080.00	p1	2025-09-17	approved	1	2025-09-17 11:47:23	2025-09-17 11:47:42	\N	t	2025-09-17 11:47:23	2025-09-17 11:47:42
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: media; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.media (id, model_type, model_id, uuid, collection_name, name, file_name, mime_type, disk, conversions_disk, size, manipulations, custom_properties, generated_conversions, responsive_images, order_column, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2025_08_21_153637_create_permission_tables	1
5	2025_08_21_153647_create_media_table	1
6	2025_08_21_153653_create_activity_log_table	1
7	2025_08_21_153654_add_event_column_to_activity_log_table	1
8	2025_08_21_153655_add_batch_uuid_column_to_activity_log_table	1
9	2025_08_21_153704_create_people_table	1
10	2025_08_21_153710_create_accounts_table	1
11	2025_08_21_153720_create_transactions_table	1
12	2025_08_21_153730_create_expenses_table	1
13	2025_08_21_153740_create_expense_items_table	1
14	2025_08_21_153750_create_documents_table	1
15	2025_08_21_153800_add_is_enabled_to_users_table	1
16	2025_08_22_145711_update_people_role_type_column	1
17	2025_08_24_133035_create_banks_table	1
18	2025_08_24_133044_create_account_types_table	1
19	2025_08_24_133246_add_bank_and_account_type_to_people_table	1
20	2025_08_26_000001_add_transaction_constraints	1
21	2025_08_26_000100_add_treasury_account_constraints	1
22	2025_08_26_230500_add_unique_index_expense_items_doc_combo	1
23	2025_08_31_120000_create_person_bank_accounts_table	1
24	2025_09_05_120000_create_expense_categories_table	1
25	2025_09_10_000100_add_is_fondeo_to_accounts	1
26	2025_09_10_120000_add_is_protected_to_people_and_accounts	2
\.


--
-- Data for Name: model_has_permissions; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.model_has_permissions (permission_id, model_type, model_id) FROM stdin;
\.


--
-- Data for Name: model_has_roles; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.model_has_roles (role_id, model_type, model_id) FROM stdin;
1	App\\Models\\User	1
2	App\\Models\\User	2
2	App\\Models\\User	3
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: people; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.people (id, first_name, last_name, rut, email, phone, account_number, address, role_type, is_enabled, created_at, updated_at, bank_id, account_type_id, is_protected) FROM stdin;
1	Carlos	Mendoza	12345678-5	carlos.mendoza@coteso.com	987654321	123456789	\N	tesorero	t	2025-09-16 12:06:28	2025-09-16 12:06:28	1	1	f
2	María	García	23456789-6	maria.garcia@coteso.com	987654322	987654321	\N	trabajador	t	2025-09-16 12:06:28	2025-09-16 12:06:28	30	4	f
3	Luis	Rodríguez	34567890-7	luis.rodriguez@coteso.com	987654323	555666777	\N	trabajador	t	2025-09-16 12:06:28	2025-09-16 12:06:28	7	2	f
4	Ana	López	45678901-8	ana.lopez@coteso.com	987654324	111222333	\N	tesorero	t	2025-09-16 12:06:28	2025-09-16 12:06:28	1	1	f
5	Pedro	Sánchez	56789012-9	pedro.sanchez@coteso.com	987654325	444555666	\N	trabajador	t	2025-09-16 12:06:28	2025-09-16 12:06:28	30	4	f
6	Jamarcus	Torp Quitzon	22894821-7	candice46@example.net	206.636.5605	\N	42984 Klocko Via Apt. 152\nWiegandhaven, IL 53876-7840	trabajador	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
7	Mina	Langosh Legros	6603425-9	stokes.golda@example.org	\N	\N	71550 Ruthie Mountain\nNew Reina, OK 58151-4920	tesorero	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
8	Raegan	Auer O'Hara	9977891-1	kcollier@example.net	(626) 656-7898	7877501679	715 Mollie Station Suite 995\nDonniebury, MO 53862	trabajador	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
9	Vinnie	Schamberger Reichert	20913661-9	nicola63@example.org	628-233-9657	5160182963	1876 Breitenberg Ford Apt. 152\nNorth Michaelshire, AR 55921-0765	trabajador	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
10	Marge	Roob Wehner	7850853-1	kilback.thad@example.com	\N	2182238183	\N	tesorero	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
11	Mallory	Moore White	19950429-0	fern72@example.com	\N	6514533727	\N	tesorero	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
12	Lurline	Larson Dach	21808848-1	makenzie.windler@example.net	+1-650-999-5164	\N	\N	tesorero	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
13	Ibrahim	O'Conner Casper	4319428-3	brekke.pablo@example.org	520.647.7231	4036688494	668 Dare Ridge\nEast Paris, NC 88398	tesorero	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
14	Amos	Lakin Crona	1886590-4	emmerich.tomasa@example.net	+1.716.427.0709	1104019812	236 Jovany Curve\nMcLaughlinfurt, DC 79886-0301	tesorero	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
15	Hank	Bradtke Stehr	16617387-6	connelly.omer@example.org	\N	5843582539	\N	trabajador	f	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
16	Yoshiko	Wiegand McClure	13735076-9	dicki.orion@example.com	\N	\N	29147 Jaskolski Throughway Apt. 640\nSchimmelshire, CA 11876	trabajador	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
17	Cesar	Marks Denesik	14236378-K	berenice40@example.net	+1-848-939-8971	3944707123	843 Deborah Greens\nSouth Reannaside, WI 09549	tesorero	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
18	Estella	Harvey Witting	5661188-6	jkirlin@example.org	1-228-669-6079	3126883391	6211 Lowe Fields Apt. 691\nWehnermouth, MS 02078	trabajador	f	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
19	Julianne	Schmeler Volkman	2187860-K	johnston.janice@example.net	(669) 524-4079	8096280366	60742 Welch Field Apt. 471\nWest Jedediahmouth, TX 34472-2862	tesorero	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
20	Claude	Simonis Marvin	11227362-K	juanita97@example.com	+1-475-409-9803	1498854978	82547 Fanny Brook Suite 869\nLacyfurt, OR 74712-8926	tesorero	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
21	Katlynn	Grimes Hilpert	8294464-8	elbert44@example.net	(541) 369-3205	\N	\N	trabajador	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
22	Charles	Swaniawski Williamson	19179066-9	zbreitenberg@example.org	+1 (986) 971-3620	\N	3011 Walker Skyway Apt. 773\nPort Burdettestad, MD 01832-0070	trabajador	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
23	Otha	McLaughlin Cruickshank	24472587-2	macey.kub@example.org	830.223.1815	4340173747	\N	tesorero	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
24	Frederick	Simonis Abernathy	23946289-8	stiedemann.jerel@example.org	828.596.1430	\N	8708 Dach Rue Suite 143\nMurphyside, IN 77349-4143	tesorero	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
25	Alexa	Christiansen Wiegand	3922628-6	ahmed73@example.org	956-636-9466	3256564170	68767 Desmond Land\nJacobiborough, MI 66157-7363	tesorero	f	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
26	Madonna	Dooley Paucek	18409333-2	alexanne.collier@example.org	(239) 364-9633	8733957499	\N	trabajador	f	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
27	Abagail	Ebert Ortiz	4807040-K	lesly.gutmann@example.com	+1-480-409-4202	5268300464	62345 Mraz Mountains\nCamilachester, DC 88889	tesorero	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
28	Mireya	Terry Kihn	6649640-6	alta.ryan@example.net	678-250-5124	1847843178	\N	trabajador	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
29	Destiny	Farrell Turner	19055630-1	kendall.jakubowski@example.net	\N	8769471914	313 Botsford Pike\nPort Carrollhaven, UT 63240-6137	tesorero	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
30	Chloe	Weimann McLaughlin	6235032-6	ferne81@example.net	1-323-496-6706	4175466317	15443 Frieda Trail Suite 724\nLake Fanniemouth, PA 33730	tesorero	f	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	f
31	Tesorero	Sistema	6895247	treasurer.person@coteso.local	\N	\N	\N	tesorero	t	2025-09-16 12:06:28	2025-09-16 12:06:28	\N	\N	t
\.


--
-- Data for Name: permissions; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.permissions (id, name, guard_name, created_at, updated_at) FROM stdin;
1	people.view	web	2025-09-16 12:06:27	2025-09-16 12:06:27
2	people.create	web	2025-09-16 12:06:27	2025-09-16 12:06:27
3	people.edit	web	2025-09-16 12:06:27	2025-09-16 12:06:27
4	people.delete	web	2025-09-16 12:06:27	2025-09-16 12:06:27
5	teams.view	web	2025-09-16 12:06:27	2025-09-16 12:06:27
6	teams.create	web	2025-09-16 12:06:27	2025-09-16 12:06:27
7	teams.edit	web	2025-09-16 12:06:27	2025-09-16 12:06:27
8	teams.delete	web	2025-09-16 12:06:27	2025-09-16 12:06:27
9	accounts.view	web	2025-09-16 12:06:27	2025-09-16 12:06:27
10	accounts.create	web	2025-09-16 12:06:27	2025-09-16 12:06:27
11	accounts.edit	web	2025-09-16 12:06:27	2025-09-16 12:06:27
12	accounts.delete	web	2025-09-16 12:06:27	2025-09-16 12:06:27
13	transactions.view	web	2025-09-16 12:06:27	2025-09-16 12:06:27
14	transactions.create	web	2025-09-16 12:06:27	2025-09-16 12:06:27
15	transactions.edit	web	2025-09-16 12:06:27	2025-09-16 12:06:27
16	transactions.approve	web	2025-09-16 12:06:27	2025-09-16 12:06:27
17	transactions.delete	web	2025-09-16 12:06:27	2025-09-16 12:06:27
18	expenses.view	web	2025-09-16 12:06:27	2025-09-16 12:06:27
19	expenses.create	web	2025-09-16 12:06:27	2025-09-16 12:06:27
20	expenses.edit	web	2025-09-16 12:06:27	2025-09-16 12:06:27
21	expenses.review	web	2025-09-16 12:06:27	2025-09-16 12:06:27
22	expenses.approve	web	2025-09-16 12:06:27	2025-09-16 12:06:27
23	expenses.delete	web	2025-09-16 12:06:27	2025-09-16 12:06:27
24	reports.view	web	2025-09-16 12:06:27	2025-09-16 12:06:27
25	reports.export	web	2025-09-16 12:06:27	2025-09-16 12:06:27
26	system.configure	web	2025-09-16 12:06:27	2025-09-16 12:06:27
\.


--
-- Data for Name: person_bank_accounts; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.person_bank_accounts (id, person_id, bank_id, account_type_id, account_number, alias, is_default, is_active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: role_has_permissions; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.role_has_permissions (permission_id, role_id) FROM stdin;
1	1
2	1
3	1
4	1
5	1
6	1
7	1
8	1
9	1
10	1
11	1
12	1
13	1
14	1
15	1
16	1
17	1
18	1
19	1
20	1
21	1
22	1
23	1
24	1
25	1
26	1
1	2
2	2
3	2
5	2
6	2
7	2
9	2
10	2
11	2
13	2
14	2
15	2
16	2
18	2
19	2
20	2
21	2
22	2
24	2
25	2
1	3
5	3
9	3
13	3
18	3
19	3
20	3
24	3
18	4
19	4
\.


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.roles (id, name, guard_name, created_at, updated_at) FROM stdin;
1	boss	web	2025-09-16 12:06:27	2025-09-16 12:06:27
2	treasurer	web	2025-09-16 12:06:27	2025-09-16 12:06:27
3	team_leader	web	2025-09-16 12:06:27	2025-09-16 12:06:27
4	team_member	web	2025-09-16 12:06:27	2025-09-16 12:06:27
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
2b1Z9Jhc6wUnuO3XcmyNpa5jBQEHJavAeJU9TO5o	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiQUNJTG1reWxCMlVmNTVpbkkwMENFbEQwVkZPRkV6QUhDalRFQ2pYZiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758288575
YCI74Ob3um5UuECohPCccHI9gFM7rjgOCWUVRp9Y	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiMlhSQnNnWER5RklxdzdrazNDQnRxR0tNWEtFQWRsSWhSY0FBQmxRYyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758290825
UISsG0QVc1z9HD89O1jVoG99cd8JkJ6pAO5wuxzE	\N	204.76.203.212	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiSlpUYk4xVmZzaTFyNVRpQ1dkSndYdFNRVVBEdHBrZ1ZUY0tKSXdtZiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758293759
SCh5wMrfGIjKN7gK0ECk3p8ggDmJWuUR9746GOit	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiZUlnck5pVzVHa2x6VFdreWhneGdleTJVajlJQnU0YzE2RVR0dDdmdSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758296027
IVVjJ56nh5qarXUyTkflpN636Y9QE1H9zcXzKJQN	\N	204.76.203.212	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiY25mcWtwellxNjJZaFpDQnpuWklvclRyOEFnYjVqSDBuV0tjbEdORCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758285641
TcKijz5FOBVf9f1NRD64L51HglBcpwtNUV6s3FZx	\N	204.76.203.206	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiUXRVSk9nZ2wzdFMwU25EdVNmTGE3SXBBOFQzRDhjNk9Ja3hCUkJDcSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758288405
FWQbrcrjvIjhBmTI7eNxpxh5z490UB11ewQDgClz	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiSVAxUTVSbmZNZndpVjZmN3kzaUh1UE1XdEhhZG05QjBUU3hoRFdNSyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758288625
d4ZrnGPqkGfJxfaPoHyqa1pXuks663mf7DCuHMsb	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiUFFuWkNNVzQxVWY5eE1HNk1SejRKUUlQOVlTOVpoZnU2SUtDdXZ4UCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758291203
cjGby3hh8WGGPmELdkD8GJSLJry2LnNgltzUGQZA	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiRExmeWJSYktiNHdWOGc3UHRlT28zSDR6UjZMTjNiOEVxMEVLWTJCbCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758293795
tz8vMPPHcAwlcCNtUwKYHsPla3YjWtIVuVUsCrWe	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiTnNzaU5VcUlzU3VCZHhhTEFIWnBJd1EzWnlhRzFha1U0UHQ4WDdBbiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758296415
zfEYm1UCynVhZkhNpCNIuRFIl0WzPMyBDJNJEihJ	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiQzh3OHVSNTdUQnY3cnFsV2Z1dVNIdU9GY2dhbTAzT2pjaHRWSkczUCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758286004
wBbFxZn4Pj4cX66FVZaFiaLoYnHM4H88SeTPpCru	\N	192.159.99.180		YTozOntzOjY6Il90b2tlbiI7czo0MDoianEyMmVyZjh4VW40OTZhdVhrUGZtandZeVpUeHhHcE5RNk9sczk4MiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHA6Ly8xNzkuNTYuMjQuMTQ5Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758288814
Eb4fGvNNrchMru7c9ATpTPp6JGDJfivZUReVRvzD	\N	176.65.148.40	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiZDZXZVBEaFpaQTQ2U1J6Z3N6QmZ4TWVRU0JNZU9iVnZmRmxqdUpnRSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758291263
2uf8iltvkSJA9GltQ1PmjixYJkunc8xKx8MQlj1Q	\N	154.84.184.122	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiMXBoVXBzUDNyMGlUYllLNEZzbFZMNmM2c2JDWXhSUld1ZXRyRnJzWSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758293942
DIberW0eImVcOB9lunMmYqJ8HsFaYfhoi5nOyi5m	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiSHdSRnhBYThqZTJHeVRlTU5KQzFpaUlyTjVFYjRJeDZ4RmoybzV1eSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758296780
Hcc4CY6xkAvDZp85KuSC6ZjiKU97hOjfsLufu5UL	\N	45.135.193.100		YTozOntzOjY6Il90b2tlbiI7czo0MDoiS2dXMm1Sa0VtTUYwSnc1N3JBa0V6ODdGbFEyaU5lVng4N0hrSjgyZyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHA6Ly8xNzkuNTYuMjQuMTQ5Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758317218
d57YRbX6B8MWwHDDHK16Jai2VhV0MIpDsmsu4Xda	\N	204.76.203.206	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiQ2pPSjVOVWx0R2xldlQxbnN5YW9BYnFETUY3UWlCTUpuMnpKOUxIaCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758286198
FkmRbCrxotl31VzckNUE4hai45mAlN6vU8DvGVg4	\N	149.50.103.48	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiejNTcFBwWUhmOVdBYWwwNVNHWmlOYndXQlFYaHMxdUlJU0JBVjN3biI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758288870
FHptimbXvJxfDleod0B7qmYHUb5bu0H12RxX7VeN	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiUlZ4T3cyRDF3QkdBNERqWXMwOXYwR3YzVEJpV1RoVk5lT1lETXFWVSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758291579
ZKUA18Kobx2vkuf3PnGEIxqYJPVu4hApEVlgrl9E	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiOGh5WjFvRDI3aU9UdVludTJwSG5rOFZpNWxvWklTZTNMSlZZWXg4MSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758294175
346UY0NXewK9Oi6VOHwkNs7CzrzcZhMVARFeJQYG	\N	151.235.208.169	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/601.7.7 (KHTML, like Gecko) Version/9.1.2 Safari/601.7.7	YTozOntzOjY6Il90b2tlbiI7czo0MDoid0JQWm5GbGpxOGEzeUlXQmZWUG9YVDg0ZjRZVmEwMTdRRE9mTXVyRyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHA6Ly8xNzkuNTYuMjQuMTQ5Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758296842
lLCaRwjCBd1k9mhAlVS3r7qXwJs9pO9gcoRpIikw	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiVVJtWGgxQ2lhdzh4bVV1dXMxUU1wN3NEdlF5TXZBYzdsdG95WGQxYyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758286368
xmrxxxJGlduuIXuB6nVpmp4kUkYK9ACa3g8HqmBD	\N	204.76.203.212	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoialM5ckd0SFRUWUtQUUI4Z1NTcTRycHBvclJkRVhjV2VuTnVXNUtpVyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758288924
o2GxrLo7yK00992GFnivleNCMtuBEBeQX7FrEN6h	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiYjhhV2tlZlNrcGZwa0liSklPUlZxb0lXRzdUVHNscWFUbVpiNDZ3QyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758291943
Op8gyyxrVhGkjhQ8nKpRQ8RzUNj28xyR4HWeKaI8	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoidm1FVTV2MmNMNnhweEs1b2cwbXNTTndKYjhJVU9iOVdzZE9PeEdUZiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758294543
B6SQwhWL5AgGqOY6IDuS9f9uuJoQ6JZoHdQsaQZd	\N	204.76.203.212	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoibVhBbjk3V2NvWXp4T3FjblNmQXFNYThJYVpHVzk3Tld2VzRLZThSNCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758297060
tTZkUy2AnOp7JWjpEZBtBsg5d79cn7QsQ3zF7TSY	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiZWxXQndudmdwczNYMUVHaUFjZG5yQU9oMzc1ZUFtMlZvazBHMHhaUSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758286745
TxPxxkjnLkIKcfheauAeWxykj6uHyBftNSTo6Cjj	1	172.19.0.1	Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0	YTo1OntzOjY6Il90b2tlbiI7czo0MDoiQ2RFYWNOb3NCTnVFdlV5MzBLM1JPSWNMQUhIYWlXYVdTU0tsR3lmUyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly9sb2NhbGhvc3QvYWNjb3VudHMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjM6InVybCI7YTowOnt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9	1758317233
PHAYBqQDwehLq2hPBMLJYsZXC1OTEcT51W1iIMN5	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiTDgweVEyVlp4MDBKOTlBMGNPN3JtTHFPVEJPSEhHYmp4WXB6eDU4diI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758289001
0jLhz7jxcxLqBVYN5bJwj54aUwY5E3RAPZC9rrGq	\N	154.84.184.122	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiMnRwYVZsb2I5akRvVHB0QVBqMHJrTGszZ2lWeEpsdlM1bXFKMWVvcCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758292165
2wTuE946qfwDWtW5g8ZFwwvuEsA057p6aYeh5Ltk	\N	204.76.203.206	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoia1FWYktRSEdGMHRpQ2x6U3JNSExDNEdWZ0tqYUJ1QzRsbzJ4V1QzYiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758294813
q1sXWwPhiDmfYQjW0uRpdOLs1L83t1hVf3Urovm3	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiZ1Q5REtzc211V0JiazRGZ0paaU1vejZDd3dBWWpmRk5lTHpXckRkRSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758297138
mxuTNKwDfwyoGgimMKRcJFkegH0J59RFESkEUCZQ	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiZ05YTHBYZ1RpV3JlMVl0TDlUZjVGY0JLajZjUlJOdjl2T01Ha282cyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758317404
f3fvpMfOAWQQ9NJKFZ7OAJ9518XxUaz6ccg0x3oN	\N	45.156.129.57	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36 	YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ2x2eXlUWU0wbjhHazhHRFl6aTd5MFZ0ZjZNejN0RU44Y2I4ZFNKRCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHA6Ly8xNzkuNTYuMjQuMTQ5Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758285159
NzSU6RaqBULVJmhuz8EZtYJD41HWT6v0pWzsLuOG	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiMURLd2pQaWVDOGRjeEFmejVmR3FneDduaUdMSlNYYWFoSXNCV21LaCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758287125
bAp3dhMBFNSbH0s3BlziDxNGRY8HGlHVvwBf4CHt	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiWmZRaHJacTJxZXBYekprQUFWOTdPTW1EVFQ2amtvNlV1MktkQWg2aSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758289347
AZmUrUZMezxniAJgxkNpZVzSAUcCchB6UwhoMnt4	\N	204.76.203.212	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiZUpSVnAyV1ptWDRZdGFiM3lkSm96T0lnNmRoWld0c1ZnRTZualRnUCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758292255
xMa0Wo5Kek3uAYL6Mx3LJ9nmGGclppidTjMg2g62	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoibGVPMnVHeVAzSEFNUDhzQUpBbk1NUzY5cVIwNGJBNEFVZElnN3lPRyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758294935
Zu6JfVFF2zmqbxEDZit4WaxuQ5CIomWlKUvsL75V	\N	45.156.129.57	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36 	YTo0OntzOjY6Il90b2tlbiI7czo0MDoibXFrSEVsNHA5NWg4WHFwaGg2Yms3VVY1dUNkTWhWNU1OM1VuS1REZiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMDoiaHR0cDovLzE3OS41Ni4yNC4xNDkvZGFzaGJvYXJkIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xNzkuNTYuMjQuMTQ5L2Rhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1758285159
2u8Yz1Q3i0RsABvhA0dHw9Bk5YpMa1s97L8DHMti	\N	204.76.203.212	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiNlFhWE9TNFczalVaWUl0MHdXQTg5ekIyWEIzUVJKOGhnQjZySTNZNyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758287313
XI49GEQKwwqPmC4xAUaPuiUvAJqv62ZPydpRpT7z	\N	154.84.184.122	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoia2VVeHk4d3FSWWpOR2tYQUcxUE9WdUF0bjRKYkFBV0lDYTMyU3JFbyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758289566
QC1whBRuxW8ZovZB0f61rQuGMaIy1OfE9v4L8bh6	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiNmxUVFg2eXNuaThLeEFhOWl6VnpNYWdSYnRscHBlMUUxazUwTTRzMyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758292315
bYC7gAMJQsb0rAEagbR7nEFeXNHu1jeAJuHiqhiI	\N	43.157.174.69	Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1	YTozOntzOjY6Il90b2tlbiI7czo0MDoiQnlaN3VjREpWRVlmemQ5NW00c0ltaWdnMlNPYnlscmtsRVNtb1pOTyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHA6Ly8xNzkuNTYuMjQuMTQ5Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758294993
jrtZERcs7HNfyS286lZ6VnBxbos6duXJ5Me8SV3C	\N	45.156.129.135	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36 	YTozOntzOjY6Il90b2tlbiI7czo0MDoiSmRxYUVTa1VVNjVkSjlHUDBWakpvRk1sRG1wUWJsN0NGdmR5eWNlUCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly8xNzkuNTYuMjQuMTQ5L2xvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758285160
NF1drySlMM3JWAWfxNCQKIEJ69pritUgG5PYkbVk	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoibjB5NndFRGVydlFoZUhmU291eU4xN1VWOVlQdm1OdzJKR2xDaVVrNSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758287491
97LXXjKSPv4OSdi1WUBr9Nf1Pbr0AFzHKVTIWFgq	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoibUJHdU03SmZQOEtNRTd3WmVjQUdRbkNKZUhPeDRheGdQY3k3MW1jMiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758289713
tAs8k0LMSzR4a7b5pg4KZG6VsmIVsnfqlfNPEeox	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiblhNWXlMajk2dnRtejZvU0JmUmxqaTc5MDNRc0hSQUpkck13NTFLUCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758292686
uqO3DtdEcu6tbrmwwkKzYDupXqG50hB9DbmKFk68	\N	43.157.174.69	Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1	YTo0OntzOjY6Il90b2tlbiI7czo0MDoib0Rkc081dzE4VW9QTXFDdGJacXVVYXloNXVMY0hUREZWNDVVWHdMWiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMDoiaHR0cDovLzE3OS41Ni4yNC4xNDkvZGFzaGJvYXJkIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xNzkuNTYuMjQuMTQ5L2Rhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1758294993
fAnFAGmq5r0rlYvwWgKoKsWMemZK1i2CNMp38Lv4	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoicmdxOWhaRlZkVElBSVFPMzFTTE9xWk9seEdUUFRRMlk2NTRTMTd6cSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758285277
m93bcylrlZCE3IcsH7NNQRG37ch03m2vGwWhVcEz	\N	20.171.8.182	Mozilla/5.0 zgrab/0.x	YTozOntzOjY6Il90b2tlbiI7czo0MDoid2FQSk9ZZ0RhbEZ0bFhjZldIdGdIekxRNUdPaVh4R2ZQVXBGaW9iNyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHA6Ly8xNzkuNTYuMjQuMTQ5Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758287720
tg6MhHhxSSo27iBG55CWA4PIT601ZlClTIahRJtX	\N	204.76.203.18		YTozOntzOjY6Il90b2tlbiI7czo0MDoicm5zY2RIQ1dpd29PWnZSeG1od3gySldOa2plRU5CTWZQUWNuWkpCZiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHA6Ly8xNzkuNTYuMjQuMTQ5Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758289990
yQV9rmWHzFbRYWprJ02IdLnzvFpvtvd0jjrOW1Gs	\N	45.142.193.144	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiSHZOWEI4d3Jpb3FZQnRPN1ZPdE1zbDVUTjIxV3FGTDZ1NlNCSG5UeSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758292746
WEp1HDUPUpdZq8vm1JXRSBfxcTtOSYH3lsirHc3k	\N	43.157.174.69	Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1	YTozOntzOjY6Il90b2tlbiI7czo0MDoiZHl1ck9vMHJhSW00SWtvSmRWZllrNVhaNWVOR2NxdFBRakltb0lKTyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly8xNzkuNTYuMjQuMTQ5L2xvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758294993
b5S6GT6MxHQSuTz50L6mybHPLP2MHw1E0EVEmOSj	\N	154.84.184.122	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiMFZvTVZaQ1R0b3R4Mmp5VXFDeEMzUW1RaFpxeFd6ZzFOeHdRWlFvTyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758285304
YgwVTP3l5Irj0kM3gc5qTtPmjiYETAtaI1e5ghSa	\N	91.224.92.17		YTozOntzOjY6Il90b2tlbiI7czo0MDoiamhWemFuNkJROEY5dmpwQkpEa0xTRzhNSjQ0a1BZaDFxdTA4MlZIYSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHA6Ly8xNzkuNTYuMjQuMTQ5Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758287774
KUoJ9etE3glaZ98yueeLfYBl0ud1S6RHLsg3IOQu	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiaml5bjFuaXNxRmZZSkJGUVBmeUhKTnhPSnUxbmoxcXZZWTVmQVNQRyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758290103
Cs9GulonMXdHskgRKGPh3NTmdOJQWtgtKaK0WB7N	\N	204.76.203.206	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiRUVHSVVqS1lQdVNXQ3NvVFFScUNjamhUUWZqbWlSY1g1YUxlbVh4dSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758292902
xB3AhxO6qQ1xLucy0aMfs8Fb030BrVmvriuqVYpm	\N	45.142.193.144	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiME1XYVVjZ2Z1MG9EWDVnMVhneXQya29RVVpUcVZnM0VlQk42SFhMZiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758295166
uQjuWbNmQMcHc2fualbTJDnCDKnsJ70m1vtDzSRl	\N	43.153.119.119	Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1	YTozOntzOjY6Il90b2tlbiI7czo0MDoiQW9UbkFtSG41QThScGJMc3E2bjV3MWRlRTVBZllIU0FPazFrVlNqSCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHA6Ly8xNzkuNTYuMjQuMTQ5Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758285315
zSajHg46KkIqbZs2xUoc8ZcKbD6GmtI1VjirBuxR	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoieUc5SDB0bUlrY3dTdjVYa0JWeGN6VldjU051a1FacUxtUTk3U1hxSSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758287857
FoaKH7awISYF00ezIPId3KOcjIyxIP9sbLt6iVnG	\N	204.76.203.206	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiN0JnT3dGUUdqR01yUlhVVmREQ3ZMTVUxMnJJNmMwWlZJQlZvN2ZJViI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758290446
xp0fPjVwdhqRB7F3rk941rRnjnopRYLSTEwNCFzA	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoicUkzS0xUOWozQWdjblpPMzlDaU90ZDNFNUpSUGxjMXFrRERycmhrRCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758293072
40tzlMgi9RjBttFEqWC8pdy0qoeiw1DbkClMVyIN	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoibm12MnE4cnkyMlVraWhLZTJaSG0wQkN4eEVLTVpxaHFySkpkZkFwbyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758295293
D5Pcsgsfck4D0JzilOhTvunFHbtyiJdkZk87fK9U	\N	43.153.119.119	Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZU1rbVRXM3VRVFVBQ0FqY3NRUHVaOUVmTE5KemxTeDlHdXN1QXhFQiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMDoiaHR0cDovLzE3OS41Ni4yNC4xNDkvZGFzaGJvYXJkIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xNzkuNTYuMjQuMTQ5L2Rhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1758285316
qMllawG87EuDrusVB3uRWoYfvJn3YoZEiViLWABJ	\N	45.142.193.144	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoia1JkeWlaTG9kNXZTRlFzblZxYWtQWEFzVzRNUnZ5d3hOa0Y2b09uNyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758288157
CKk0JHjFLc8hYtIi1DrjlTqdIxSGD22xqbftVf84	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiZ2NFNzlsYkljeVVoYmpCTU84VTN3UERXc0IwOThqRDdZNlRJUWdFUCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758290452
nIdL8kHED6WOc3YQWfYaEou4gxuvRaNd5PLuyuV9	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiS3FiZ2o1WnhNUERWMEJlZW5aNlppR1RHWThyTDRhdFFOcE5DbTdUaCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758293237
lRTwvEfUOXAxDeGyawCAlABPsNcHjjlWGO30KFQp	\N	204.76.203.212	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiWXRhVmxIRkNZNnBCU2Q3ZERUaVJpZWRtSTBTTmMyanRjWUV2aWRHZyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758295417
Ip7Lc6t022lWrYknfyhjxeMzC2pnH5VFw0ye0yeE	\N	43.153.119.119	Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1	YTozOntzOjY6Il90b2tlbiI7czo0MDoiN2U3VldFVmEwcGpURXVodThVMzlYN2hNamJ2SmQyUjVFRkpKdW1hayI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly8xNzkuNTYuMjQuMTQ5L2xvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758285317
qu20nobjB8MYDHve22gbH5VBlHAIxw3g1JA2C42s	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiRmRYYlRmVEJQdFNwNTlpYmFRZlhDSHF0Qm9tbms1YUhqWDlBRUJucyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758288227
42hnLHYI23zMPGuMvFTb2GGtZ1z8r3AKiOiCI6wD	\N	204.76.203.212	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiYVpPdm5NSzNjZDlyUzlWOVMzRDNCcUJBUFJNYTlwZzJCV1B5NTRUSyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758290547
ADjYBYdEFkVIO71hFkT0dDzEJMBccJfojEqUcVIB	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiNnZITFl6S1dIWGk0R25CbE01MUtaV0RzRFN1Mld6Sk45cnowRWE0USI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758293430
t5EIxLk3nwL2TT0dWjC5mCIHuiiOYfLE1Bl9UQKe	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiVVpjclo0VVYyUTUwWG43YVJoZjRJNjdraXA1OUZLcVNYU2NRRk9GWSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758295657
r9f8ZJXxIlQrckiNXZDCOuIPmeMFUHVjLWcMPGka	\N	204.76.203.219	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiZUZCcEVYNGNYcFBxbTl3WHdMUzFoMTNTY1dFcmE5M2d6YWl2OFlwcCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758285631
td6mcGRmyX9kzH1ZQ7tVXe23cLGgGGbI19ahLwhA	\N	154.84.184.122	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46	YToyOntzOjY6Il90b2tlbiI7czo0MDoiNUw5NmRFZ3J4VDhYaDQyRUFVUlNIQ1h1eHNrb0s4TnN2ZGc1SWZidCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1758288364
\.


--
-- Data for Name: transactions; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.transactions (id, transaction_number, type, from_account_id, to_account_id, amount, description, notes, created_by, approved_by, status, approved_at, is_enabled, created_at, updated_at) FROM stdin;
1	TXN-2025-001	transfer	1	2	500000.00	Transferencia para gastos de cuadrilla Norte	Fondos para materiales y viáticos	1	1	approved	2025-09-16 12:06:28	t	2025-09-16 12:06:28	2025-09-16 12:06:28
4	TST-1758034767283	transfer	1	3	12345.00	Test insert	\N	1	1	approved	2025-09-16 14:59:27	t	2025-09-16 14:59:27	2025-09-16 14:59:27
5	FTX-1-CFRJ3F175803507321	transfer	1	3	177932.00	Adelanto Tesorería a Carlos Mendoza	\N	1	1	approved	2024-10-02 15:04:33	t	2024-10-02 15:04:33	2024-10-02 15:04:33
6	FTX-1-HN9HBZ175803507390	transfer	1	3	141348.00	Adelanto Tesorería a Carlos Mendoza	\N	1	1	approved	2025-05-15 15:04:33	t	2025-05-15 15:04:33	2025-05-15 15:04:33
7	FTX-1-ZZDR9D175803507364	transfer	1	3	90594.00	Adelanto Tesorería a Carlos Mendoza	\N	1	1	approved	2025-04-15 15:04:33	t	2025-04-15 15:04:33	2025-04-15 15:04:33
8	FTX-1-JRQ9GC175803507373	transfer	1	3	105806.00	Adelanto Tesorería a Carlos Mendoza	\N	1	1	approved	2025-06-22 15:04:33	t	2025-06-22 15:04:33	2025-06-22 15:04:33
9	FTX-1-G9AGLF175803507373	transfer	1	3	267523.00	Adelanto Tesorería a Carlos Mendoza	\N	1	1	approved	2024-10-06 15:04:33	t	2024-10-06 15:04:33	2024-10-06 15:04:33
10	FTX-1-GNW3ZS175803507351	transfer	1	3	202123.00	Adelanto Tesorería a Carlos Mendoza	\N	1	1	approved	2025-05-12 15:04:33	t	2025-05-12 15:04:33	2025-05-12 15:04:33
11	FTX-2-GVN8UI175803507364	transfer	1	4	168181.00	Adelanto Tesorería a María García	\N	1	1	approved	2025-08-29 15:04:33	t	2025-08-29 15:04:33	2025-08-29 15:04:33
12	FTX-2-IN5MIE175803507398	transfer	1	4	263574.00	Adelanto Tesorería a María García	\N	1	1	approved	2025-03-19 15:04:33	t	2025-03-19 15:04:33	2025-03-19 15:04:33
13	FTX-2-E1OYIG175803507357	transfer	1	4	258575.00	Adelanto Tesorería a María García	\N	1	1	approved	2024-11-26 15:04:33	t	2024-11-26 15:04:33	2024-11-26 15:04:33
14	FTX-3-LRQDVG175803507397	transfer	1	5	148490.00	Adelanto Tesorería a Luis Rodríguez	\N	1	1	approved	2025-05-22 15:04:33	t	2025-05-22 15:04:33	2025-05-22 15:04:33
15	FTX-3-PGKOHG175803507327	transfer	1	5	85221.00	Adelanto Tesorería a Luis Rodríguez	\N	1	1	approved	2024-11-28 15:04:33	t	2024-11-28 15:04:33	2024-11-28 15:04:33
16	FTX-3-CXOM84175803507377	transfer	1	5	21754.00	Adelanto Tesorería a Luis Rodríguez	\N	1	1	approved	2025-07-23 15:04:33	t	2025-07-23 15:04:33	2025-07-23 15:04:33
17	FTX-3-8LYOH9175803507391	transfer	1	5	111545.00	Adelanto Tesorería a Luis Rodríguez	\N	1	1	approved	2025-05-30 15:04:33	t	2025-05-30 15:04:33	2025-05-30 15:04:33
18	FTX-4-J5WPVN175803507325	transfer	1	6	242535.00	Adelanto Tesorería a Ana López	\N	1	1	approved	2024-11-22 15:04:33	t	2024-11-22 15:04:33	2024-11-22 15:04:33
19	FTX-4-NH2EEE175803507376	transfer	1	6	118273.00	Adelanto Tesorería a Ana López	\N	1	1	approved	2025-02-02 15:04:33	t	2025-02-02 15:04:33	2025-02-02 15:04:33
20	FTX-4-LEJAZN175803507387	transfer	1	6	224993.00	Adelanto Tesorería a Ana López	\N	1	1	approved	2025-04-13 15:04:33	t	2025-04-13 15:04:33	2025-04-13 15:04:33
21	FTX-4-LRPJTJ175803507355	transfer	1	6	143803.00	Adelanto Tesorería a Ana López	\N	1	1	approved	2025-06-22 15:04:33	t	2025-06-22 15:04:33	2025-06-22 15:04:33
22	RTX-4-PNTGUL175803507369	transfer	6	1	30445.00	Devolución a Tesorería por Ana López	\N	1	1	approved	2025-07-30 15:04:33	t	2025-07-30 15:04:33	2025-07-30 15:04:33
23	FTX-5-QXN6NQ175803507376	transfer	1	7	279670.00	Adelanto Tesorería a Pedro Sánchez	\N	1	1	approved	2025-05-05 15:04:33	t	2025-05-05 15:04:33	2025-05-05 15:04:33
24	FTX-5-L802OU175803507375	transfer	1	7	141464.00	Adelanto Tesorería a Pedro Sánchez	\N	1	1	approved	2024-11-09 15:04:33	t	2024-11-09 15:04:33	2024-11-09 15:04:33
25	FTX-5-2FGEIR175803507397	transfer	1	7	244650.00	Adelanto Tesorería a Pedro Sánchez	\N	1	1	approved	2024-10-01 15:04:33	t	2024-10-01 15:04:33	2024-10-01 15:04:33
26	RTX-5-AUFGBQ175803507354	transfer	7	1	47877.00	Devolución a Tesorería por Pedro Sánchez	\N	1	1	approved	2025-09-14 15:04:33	t	2025-09-14 15:04:33	2025-09-14 15:04:33
27	FTX-6-THXOSF175803507316	transfer	1	8	83015.00	Adelanto Tesorería a Jamarcus Torp Quitzon	\N	1	1	approved	2025-08-27 15:04:33	t	2025-08-27 15:04:33	2025-08-27 15:04:33
28	FTX-6-J8DMWQ175803507352	transfer	1	8	37770.00	Adelanto Tesorería a Jamarcus Torp Quitzon	\N	1	1	approved	2025-06-16 15:04:33	t	2025-06-16 15:04:33	2025-06-16 15:04:33
29	FTX-6-R9V5AA175803507352	transfer	1	8	118951.00	Adelanto Tesorería a Jamarcus Torp Quitzon	\N	1	1	approved	2025-07-08 15:04:33	t	2025-07-08 15:04:33	2025-07-08 15:04:33
30	RTX-6-ITN51N175803507391	transfer	8	1	46622.00	Devolución a Tesorería por Jamarcus Torp Quitzon	\N	1	1	approved	2025-08-09 15:04:33	t	2025-08-09 15:04:33	2025-08-09 15:04:33
31	FTX-7-STSAIZ175803507380	transfer	1	9	298806.00	Adelanto Tesorería a Mina Langosh Legros	\N	1	1	approved	2025-03-21 15:04:33	t	2025-03-21 15:04:33	2025-03-21 15:04:33
32	FTX-7-UABB1S175803507328	transfer	1	9	170373.00	Adelanto Tesorería a Mina Langosh Legros	\N	1	1	approved	2024-09-20 15:04:33	t	2024-09-20 15:04:33	2024-09-20 15:04:33
33	FTX-7-YMUUNM175803507373	transfer	1	9	175007.00	Adelanto Tesorería a Mina Langosh Legros	\N	1	1	approved	2024-12-16 15:04:33	t	2024-12-16 15:04:33	2024-12-16 15:04:33
34	FTX-7-HJJONK175803507393	transfer	1	9	181094.00	Adelanto Tesorería a Mina Langosh Legros	\N	1	1	approved	2025-07-28 15:04:33	t	2025-07-28 15:04:33	2025-07-28 15:04:33
35	FTX-7-RPJ6M9175803507335	transfer	1	9	124449.00	Adelanto Tesorería a Mina Langosh Legros	\N	1	1	approved	2025-06-03 15:04:33	t	2025-06-03 15:04:33	2025-06-03 15:04:33
36	FTX-7-OZODC6175803507388	transfer	1	9	214408.00	Adelanto Tesorería a Mina Langosh Legros	\N	1	1	approved	2025-04-19 15:04:33	t	2025-04-19 15:04:33	2025-04-19 15:04:33
37	FTX-7-FOYIQO175803507378	transfer	1	9	175935.00	Adelanto Tesorería a Mina Langosh Legros	\N	1	1	approved	2025-08-08 15:04:33	t	2025-08-08 15:04:33	2025-08-08 15:04:33
38	FTX-8-AJCCOV175803507348	transfer	1	10	243962.00	Adelanto Tesorería a Raegan Auer O'Hara	\N	1	1	approved	2025-09-05 15:04:33	t	2025-09-05 15:04:33	2025-09-05 15:04:33
39	FTX-8-AACFIY175803507351	transfer	1	10	40753.00	Adelanto Tesorería a Raegan Auer O'Hara	\N	1	1	approved	2025-04-16 15:04:33	t	2025-04-16 15:04:33	2025-04-16 15:04:33
40	FTX-8-4UCWSS175803507394	transfer	1	10	53632.00	Adelanto Tesorería a Raegan Auer O'Hara	\N	1	1	approved	2025-09-05 15:04:33	t	2025-09-05 15:04:33	2025-09-05 15:04:33
41	RTX-8-B8Y8JP175803507354	transfer	10	1	28140.00	Devolución a Tesorería por Raegan Auer O'Hara	\N	1	1	approved	2025-02-27 15:04:33	t	2025-02-27 15:04:33	2025-02-27 15:04:33
42	FTX-9-PFOJKI175803507326	transfer	1	11	261590.00	Adelanto Tesorería a Vinnie Schamberger Reichert	\N	1	1	approved	2025-03-31 15:04:33	t	2025-03-31 15:04:33	2025-03-31 15:04:33
43	FTX-9-NGTLGD175803507372	transfer	1	11	211770.00	Adelanto Tesorería a Vinnie Schamberger Reichert	\N	1	1	approved	2025-02-08 15:04:33	t	2025-02-08 15:04:33	2025-02-08 15:04:33
44	FTX-9-ZGQ3OP175803507395	transfer	1	11	172734.00	Adelanto Tesorería a Vinnie Schamberger Reichert	\N	1	1	approved	2025-03-25 15:04:33	t	2025-03-25 15:04:33	2025-03-25 15:04:33
45	FTX-9-X2FVQF175803507335	transfer	1	11	109817.00	Adelanto Tesorería a Vinnie Schamberger Reichert	\N	1	1	approved	2025-04-15 15:04:33	t	2025-04-15 15:04:33	2025-04-15 15:04:33
46	FTX-10-G5BLQW175803507356	transfer	1	12	266420.00	Adelanto Tesorería a Marge Roob Wehner	\N	1	1	approved	2024-12-04 15:04:33	t	2024-12-04 15:04:33	2024-12-04 15:04:33
47	FTX-10-O775W0175803507352	transfer	1	12	78838.00	Adelanto Tesorería a Marge Roob Wehner	\N	1	1	approved	2025-01-03 15:04:33	t	2025-01-03 15:04:33	2025-01-03 15:04:33
48	FTX-10-XJKZ3V175803507379	transfer	1	12	87342.00	Adelanto Tesorería a Marge Roob Wehner	\N	1	1	approved	2024-09-10 15:04:33	t	2024-09-10 15:04:33	2024-09-10 15:04:33
49	RTX-10-PVIKYL175803507326	transfer	12	1	40437.00	Devolución a Tesorería por Marge Roob Wehner	\N	1	1	approved	2025-09-16 15:04:33	t	2025-09-16 15:04:33	2025-09-16 15:04:33
50	FTX-11-EOMTLJ175803507329	transfer	1	13	252821.00	Adelanto Tesorería a Mallory Moore White	\N	1	1	approved	2025-01-15 15:04:33	t	2025-01-15 15:04:33	2025-01-15 15:04:33
51	FTX-11-ESH5OH175803507338	transfer	1	13	185747.00	Adelanto Tesorería a Mallory Moore White	\N	1	1	approved	2025-09-03 15:04:33	t	2025-09-03 15:04:33	2025-09-03 15:04:33
52	FTX-11-VM6FTZ175803507389	transfer	1	13	236560.00	Adelanto Tesorería a Mallory Moore White	\N	1	1	approved	2025-03-20 15:04:33	t	2025-03-20 15:04:33	2025-03-20 15:04:33
53	FTX-11-6MHQUI175803507319	transfer	1	13	246886.00	Adelanto Tesorería a Mallory Moore White	\N	1	1	approved	2024-12-13 15:04:33	t	2024-12-13 15:04:33	2024-12-13 15:04:33
54	FTX-11-HXQMBX175803507399	transfer	1	13	25854.00	Adelanto Tesorería a Mallory Moore White	\N	1	1	approved	2025-04-30 15:04:33	t	2025-04-30 15:04:33	2025-04-30 15:04:33
55	FTX-11-GGUTLC175803507321	transfer	1	13	113909.00	Adelanto Tesorería a Mallory Moore White	\N	1	1	approved	2025-01-26 15:04:33	t	2025-01-26 15:04:33	2025-01-26 15:04:33
56	FTX-11-SXDFJ4175803507376	transfer	1	13	280944.00	Adelanto Tesorería a Mallory Moore White	\N	1	1	approved	2025-06-25 15:04:33	t	2025-06-25 15:04:33	2025-06-25 15:04:33
57	RTX-11-RT2YTI175803507397	transfer	13	1	11612.00	Devolución a Tesorería por Mallory Moore White	\N	1	1	approved	2025-03-15 15:04:33	t	2025-03-15 15:04:33	2025-03-15 15:04:33
58	FTX-12-W4SOOB175803507329	transfer	1	14	217673.00	Adelanto Tesorería a Lurline Larson Dach	\N	1	1	approved	2024-09-30 15:04:33	t	2024-09-30 15:04:33	2024-09-30 15:04:33
59	FTX-12-P3H06M175803507370	transfer	1	14	192116.00	Adelanto Tesorería a Lurline Larson Dach	\N	1	1	approved	2025-07-22 15:04:33	t	2025-07-22 15:04:33	2025-07-22 15:04:33
60	FTX-12-F1P2UM175803507398	transfer	1	14	146819.00	Adelanto Tesorería a Lurline Larson Dach	\N	1	1	approved	2024-09-26 15:04:33	t	2024-09-26 15:04:33	2024-09-26 15:04:33
61	FTX-12-ZM0SU9175803507342	transfer	1	14	22826.00	Adelanto Tesorería a Lurline Larson Dach	\N	1	1	approved	2025-04-07 15:04:33	t	2025-04-07 15:04:33	2025-04-07 15:04:33
62	FTX-12-U41GWC175803507353	transfer	1	14	149208.00	Adelanto Tesorería a Lurline Larson Dach	\N	1	1	approved	2024-11-08 15:04:33	t	2024-11-08 15:04:33	2024-11-08 15:04:33
63	FTX-13-7MI7BX175803507360	transfer	1	15	78046.00	Adelanto Tesorería a Ibrahim O'Conner Casper	\N	1	1	approved	2025-02-14 15:04:33	t	2025-02-14 15:04:33	2025-02-14 15:04:33
64	FTX-13-QH7AEX175803507376	transfer	1	15	295165.00	Adelanto Tesorería a Ibrahim O'Conner Casper	\N	1	1	approved	2024-12-10 15:04:33	t	2024-12-10 15:04:33	2024-12-10 15:04:33
65	FTX-13-KJ4N4G175803507394	transfer	1	15	260284.00	Adelanto Tesorería a Ibrahim O'Conner Casper	\N	1	1	approved	2025-09-09 15:04:33	t	2025-09-09 15:04:33	2025-09-09 15:04:33
66	RTX-13-28ROKJ175803507337	transfer	15	1	41961.00	Devolución a Tesorería por Ibrahim O'Conner Casper	\N	1	1	approved	2025-07-03 15:04:33	t	2025-07-03 15:04:33	2025-07-03 15:04:33
67	FTX-14-ZI8D4J175803507351	transfer	1	16	213203.00	Adelanto Tesorería a Amos Lakin Crona	\N	1	1	approved	2024-10-07 15:04:33	t	2024-10-07 15:04:33	2024-10-07 15:04:33
68	FTX-14-6MLY95175803507361	transfer	1	16	220575.00	Adelanto Tesorería a Amos Lakin Crona	\N	1	1	approved	2025-06-04 15:04:33	t	2025-06-04 15:04:33	2025-06-04 15:04:33
69	FTX-14-WC2L8F175803507314	transfer	1	16	246763.00	Adelanto Tesorería a Amos Lakin Crona	\N	1	1	approved	2025-02-19 15:04:33	t	2025-02-19 15:04:33	2025-02-19 15:04:33
70	FTX-14-HRZK3L175803507341	transfer	1	16	59799.00	Adelanto Tesorería a Amos Lakin Crona	\N	1	1	approved	2025-07-19 15:04:33	t	2025-07-19 15:04:33	2025-07-19 15:04:33
71	FTX-14-B3XZI1175803507380	transfer	1	16	177804.00	Adelanto Tesorería a Amos Lakin Crona	\N	1	1	approved	2024-12-23 15:04:33	t	2024-12-23 15:04:33	2024-12-23 15:04:33
72	FTX-14-KUD9S3175803507358	transfer	1	16	98359.00	Adelanto Tesorería a Amos Lakin Crona	\N	1	1	approved	2025-09-08 15:04:33	t	2025-09-08 15:04:33	2025-09-08 15:04:33
73	RTX-14-YJCLGO175803507374	transfer	16	1	7190.00	Devolución a Tesorería por Amos Lakin Crona	\N	1	1	approved	2025-06-01 15:04:33	t	2025-06-01 15:04:33	2025-06-01 15:04:33
74	FTX-15-A9FX3A175803507360	transfer	1	17	266409.00	Adelanto Tesorería a Hank Bradtke Stehr	\N	1	1	approved	2024-11-02 15:04:33	t	2024-11-02 15:04:33	2024-11-02 15:04:33
75	FTX-15-GGCSBD175803507384	transfer	1	17	152358.00	Adelanto Tesorería a Hank Bradtke Stehr	\N	1	1	approved	2025-04-03 15:04:33	t	2025-04-03 15:04:33	2025-04-03 15:04:33
76	FTX-15-KSH2KN175803507379	transfer	1	17	163980.00	Adelanto Tesorería a Hank Bradtke Stehr	\N	1	1	approved	2025-03-10 15:04:33	t	2025-03-10 15:04:33	2025-03-10 15:04:33
77	FTX-15-XGLPWM175803507353	transfer	1	17	72163.00	Adelanto Tesorería a Hank Bradtke Stehr	\N	1	1	approved	2025-04-27 15:04:33	t	2025-04-27 15:04:33	2025-04-27 15:04:33
78	FTX-16-0PPL1G175803507310	transfer	1	18	156111.00	Adelanto Tesorería a Yoshiko Wiegand McClure	\N	1	1	approved	2024-11-21 15:04:33	t	2024-11-21 15:04:33	2024-11-21 15:04:33
79	FTX-16-46CV2G175803507343	transfer	1	18	104449.00	Adelanto Tesorería a Yoshiko Wiegand McClure	\N	1	1	approved	2025-09-05 15:04:33	t	2025-09-05 15:04:33	2025-09-05 15:04:33
80	FTX-16-BTDYMF175803507328	transfer	1	18	256906.00	Adelanto Tesorería a Yoshiko Wiegand McClure	\N	1	1	approved	2025-06-08 15:04:33	t	2025-06-08 15:04:33	2025-06-08 15:04:33
81	FTX-16-AJXTHR175803507367	transfer	1	18	274979.00	Adelanto Tesorería a Yoshiko Wiegand McClure	\N	1	1	approved	2025-03-06 15:04:33	t	2025-03-06 15:04:33	2025-03-06 15:04:33
82	FTX-16-P0XZ8M175803507331	transfer	1	18	37702.00	Adelanto Tesorería a Yoshiko Wiegand McClure	\N	1	1	approved	2025-06-03 15:04:33	t	2025-06-03 15:04:33	2025-06-03 15:04:33
83	FTX-16-VY4JFG175803507375	transfer	1	18	121620.00	Adelanto Tesorería a Yoshiko Wiegand McClure	\N	1	1	approved	2025-03-02 15:04:33	t	2025-03-02 15:04:33	2025-03-02 15:04:33
84	FTX-16-5YAOJ0175803507334	transfer	1	18	210203.00	Adelanto Tesorería a Yoshiko Wiegand McClure	\N	1	1	approved	2025-05-21 15:04:33	t	2025-05-21 15:04:33	2025-05-21 15:04:33
85	RTX-16-DDOEBZ175803507395	transfer	18	1	7951.00	Devolución a Tesorería por Yoshiko Wiegand McClure	\N	1	1	approved	2025-07-14 15:04:33	t	2025-07-14 15:04:33	2025-07-14 15:04:33
86	FTX-17-84KZYQ175803507399	transfer	1	19	191407.00	Adelanto Tesorería a Cesar Marks Denesik	\N	1	1	approved	2025-03-26 15:04:33	t	2025-03-26 15:04:33	2025-03-26 15:04:33
87	FTX-17-DRYZEV175803507376	transfer	1	19	69023.00	Adelanto Tesorería a Cesar Marks Denesik	\N	1	1	approved	2025-02-18 15:04:33	t	2025-02-18 15:04:33	2025-02-18 15:04:33
88	FTX-17-ZMUZK7175803507372	transfer	1	19	269812.00	Adelanto Tesorería a Cesar Marks Denesik	\N	1	1	approved	2025-09-16 15:04:33	t	2025-09-16 15:04:33	2025-09-16 15:04:33
89	FTX-17-81VOLN175803507348	transfer	1	19	36399.00	Adelanto Tesorería a Cesar Marks Denesik	\N	1	1	approved	2024-12-26 15:04:33	t	2024-12-26 15:04:33	2024-12-26 15:04:33
90	FTX-17-YK2WSS175803507328	transfer	1	19	238968.00	Adelanto Tesorería a Cesar Marks Denesik	\N	1	1	approved	2024-09-27 15:04:33	t	2024-09-27 15:04:33	2024-09-27 15:04:33
91	FTX-18-C2QFWI175803507442	transfer	1	20	83218.00	Adelanto Tesorería a Estella Harvey Witting	\N	1	1	approved	2025-08-06 15:04:34	t	2025-08-06 15:04:34	2025-08-06 15:04:34
92	FTX-18-3HXUKD175803507478	transfer	1	20	95772.00	Adelanto Tesorería a Estella Harvey Witting	\N	1	1	approved	2025-09-14 15:04:34	t	2025-09-14 15:04:34	2025-09-14 15:04:34
93	FTX-18-Q8WELY175803507421	transfer	1	20	27216.00	Adelanto Tesorería a Estella Harvey Witting	\N	1	1	approved	2025-01-15 15:04:34	t	2025-01-15 15:04:34	2025-01-15 15:04:34
94	FTX-18-UYR9CO175803507479	transfer	1	20	156841.00	Adelanto Tesorería a Estella Harvey Witting	\N	1	1	approved	2024-11-11 15:04:34	t	2024-11-11 15:04:34	2024-11-11 15:04:34
95	FTX-18-WMKJAP175803507459	transfer	1	20	100071.00	Adelanto Tesorería a Estella Harvey Witting	\N	1	1	approved	2025-05-22 15:04:34	t	2025-05-22 15:04:34	2025-05-22 15:04:34
96	FTX-19-HNRRU5175803507454	transfer	1	21	123845.00	Adelanto Tesorería a Julianne Schmeler Volkman	\N	1	1	approved	2025-02-25 15:04:34	t	2025-02-25 15:04:34	2025-02-25 15:04:34
97	FTX-19-J0PPUD175803507482	transfer	1	21	93193.00	Adelanto Tesorería a Julianne Schmeler Volkman	\N	1	1	approved	2025-02-11 15:04:34	t	2025-02-11 15:04:34	2025-02-11 15:04:34
98	FTX-19-NGTZJQ175803507484	transfer	1	21	35868.00	Adelanto Tesorería a Julianne Schmeler Volkman	\N	1	1	approved	2025-02-27 15:04:34	t	2025-02-27 15:04:34	2025-02-27 15:04:34
99	RTX-19-YBFP0M175803507495	transfer	21	1	31091.00	Devolución a Tesorería por Julianne Schmeler Volkman	\N	1	1	approved	2025-05-10 15:04:34	t	2025-05-10 15:04:34	2025-05-10 15:04:34
100	FTX-20-UGNMEH175803507442	transfer	1	22	254282.00	Adelanto Tesorería a Claude Simonis Marvin	\N	1	1	approved	2024-10-30 15:04:34	t	2024-10-30 15:04:34	2024-10-30 15:04:34
101	FTX-20-HE2W32175803507419	transfer	1	22	228091.00	Adelanto Tesorería a Claude Simonis Marvin	\N	1	1	approved	2024-12-31 15:04:34	t	2024-12-31 15:04:34	2024-12-31 15:04:34
102	FTX-20-SOUWKT175803507484	transfer	1	22	256491.00	Adelanto Tesorería a Claude Simonis Marvin	\N	1	1	approved	2025-01-24 15:04:34	t	2025-01-24 15:04:34	2025-01-24 15:04:34
103	FTX-20-QDYMXU175803507456	transfer	1	22	132011.00	Adelanto Tesorería a Claude Simonis Marvin	\N	1	1	approved	2024-09-06 15:04:34	t	2024-09-06 15:04:34	2024-09-06 15:04:34
104	FTX-20-CYQMJR175803507443	transfer	1	22	128757.00	Adelanto Tesorería a Claude Simonis Marvin	\N	1	1	approved	2024-12-14 15:04:34	t	2024-12-14 15:04:34	2024-12-14 15:04:34
105	FTX-20-AAIE9S175803507444	transfer	1	22	57404.00	Adelanto Tesorería a Claude Simonis Marvin	\N	1	1	approved	2024-11-12 15:04:34	t	2024-11-12 15:04:34	2024-11-12 15:04:34
106	FTX-20-ERST2N175803507445	transfer	1	22	264231.00	Adelanto Tesorería a Claude Simonis Marvin	\N	1	1	approved	2024-11-15 15:04:34	t	2024-11-15 15:04:34	2024-11-15 15:04:34
107	FTX-21-RIHD6E175803507430	transfer	1	23	25809.00	Adelanto Tesorería a Katlynn Grimes Hilpert	\N	1	1	approved	2025-07-08 15:04:34	t	2025-07-08 15:04:34	2025-07-08 15:04:34
108	FTX-21-E3LPMS175803507430	transfer	1	23	153455.00	Adelanto Tesorería a Katlynn Grimes Hilpert	\N	1	1	approved	2025-07-27 15:04:34	t	2025-07-27 15:04:34	2025-07-27 15:04:34
109	FTX-21-91IMBT175803507483	transfer	1	23	274991.00	Adelanto Tesorería a Katlynn Grimes Hilpert	\N	1	1	approved	2024-11-08 15:04:34	t	2024-11-08 15:04:34	2024-11-08 15:04:34
110	FTX-21-XLWWBH175803507499	transfer	1	23	47242.00	Adelanto Tesorería a Katlynn Grimes Hilpert	\N	1	1	approved	2025-01-27 15:04:34	t	2025-01-27 15:04:34	2025-01-27 15:04:34
111	FTX-21-U5TMHR175803507494	transfer	1	23	256199.00	Adelanto Tesorería a Katlynn Grimes Hilpert	\N	1	1	approved	2024-10-23 15:04:34	t	2024-10-23 15:04:34	2024-10-23 15:04:34
112	FTX-21-OWWXMR175803507428	transfer	1	23	176377.00	Adelanto Tesorería a Katlynn Grimes Hilpert	\N	1	1	approved	2025-07-20 15:04:34	t	2025-07-20 15:04:34	2025-07-20 15:04:34
113	FTX-21-BR61ML175803507496	transfer	1	23	188637.00	Adelanto Tesorería a Katlynn Grimes Hilpert	\N	1	1	approved	2024-11-27 15:04:34	t	2024-11-27 15:04:34	2024-11-27 15:04:34
114	FTX-22-GSIASN175803507484	transfer	1	24	41073.00	Adelanto Tesorería a Charles Swaniawski Williamson	\N	1	1	approved	2024-09-03 15:04:34	t	2024-09-03 15:04:34	2024-09-03 15:04:34
115	FTX-22-XNWTEA175803507416	transfer	1	24	165509.00	Adelanto Tesorería a Charles Swaniawski Williamson	\N	1	1	approved	2024-12-28 15:04:34	t	2024-12-28 15:04:34	2024-12-28 15:04:34
116	FTX-22-QDO2WA175803507464	transfer	1	24	257288.00	Adelanto Tesorería a Charles Swaniawski Williamson	\N	1	1	approved	2025-04-20 15:04:34	t	2025-04-20 15:04:34	2025-04-20 15:04:34
117	FTX-22-IKZBGR175803507416	transfer	1	24	100614.00	Adelanto Tesorería a Charles Swaniawski Williamson	\N	1	1	approved	2025-03-07 15:04:34	t	2025-03-07 15:04:34	2025-03-07 15:04:34
118	FTX-22-3BOMZF175803507428	transfer	1	24	92118.00	Adelanto Tesorería a Charles Swaniawski Williamson	\N	1	1	approved	2025-08-04 15:04:34	t	2025-08-04 15:04:34	2025-08-04 15:04:34
119	FTX-22-MKCG5I175803507467	transfer	1	24	267895.00	Adelanto Tesorería a Charles Swaniawski Williamson	\N	1	1	approved	2025-06-10 15:04:34	t	2025-06-10 15:04:34	2025-06-10 15:04:34
120	FTX-23-BMDMPZ175803507435	transfer	1	25	146119.00	Adelanto Tesorería a Otha McLaughlin Cruickshank	\N	1	1	approved	2025-07-05 15:04:34	t	2025-07-05 15:04:34	2025-07-05 15:04:34
121	FTX-23-SK9BM0175803507448	transfer	1	25	135494.00	Adelanto Tesorería a Otha McLaughlin Cruickshank	\N	1	1	approved	2025-01-26 15:04:34	t	2025-01-26 15:04:34	2025-01-26 15:04:34
122	FTX-23-KQDBJ1175803507437	transfer	1	25	282249.00	Adelanto Tesorería a Otha McLaughlin Cruickshank	\N	1	1	approved	2025-09-04 15:04:34	t	2025-09-04 15:04:34	2025-09-04 15:04:34
123	FTX-23-H6ZI7X175803507491	transfer	1	25	46697.00	Adelanto Tesorería a Otha McLaughlin Cruickshank	\N	1	1	approved	2025-09-02 15:04:34	t	2025-09-02 15:04:34	2025-09-02 15:04:34
124	FTX-23-3EQ0DR175803507416	transfer	1	25	162867.00	Adelanto Tesorería a Otha McLaughlin Cruickshank	\N	1	1	approved	2025-08-27 15:04:34	t	2025-08-27 15:04:34	2025-08-27 15:04:34
125	FTX-24-ZUNKZU175803507478	transfer	1	26	99789.00	Adelanto Tesorería a Frederick Simonis Abernathy	\N	1	1	approved	2024-12-07 15:04:34	t	2024-12-07 15:04:34	2024-12-07 15:04:34
126	FTX-24-MAINMT175803507428	transfer	1	26	177333.00	Adelanto Tesorería a Frederick Simonis Abernathy	\N	1	1	approved	2024-08-23 15:04:34	t	2024-08-23 15:04:34	2024-08-23 15:04:34
127	FTX-24-NA2PSK175803507469	transfer	1	26	226280.00	Adelanto Tesorería a Frederick Simonis Abernathy	\N	1	1	approved	2025-09-12 15:04:34	t	2025-09-12 15:04:34	2025-09-12 15:04:34
128	FTX-24-SI5QZE175803507443	transfer	1	26	272714.00	Adelanto Tesorería a Frederick Simonis Abernathy	\N	1	1	approved	2025-04-01 15:04:34	t	2025-04-01 15:04:34	2025-04-01 15:04:34
129	FTX-24-5LGDYR175803507424	transfer	1	26	269969.00	Adelanto Tesorería a Frederick Simonis Abernathy	\N	1	1	approved	2025-03-01 15:04:34	t	2025-03-01 15:04:34	2025-03-01 15:04:34
130	FTX-24-FEIGEC175803507427	transfer	1	26	229388.00	Adelanto Tesorería a Frederick Simonis Abernathy	\N	1	1	approved	2025-07-19 15:04:34	t	2025-07-19 15:04:34	2025-07-19 15:04:34
131	RTX-24-BOCZ1B175803507444	transfer	26	1	6474.00	Devolución a Tesorería por Frederick Simonis Abernathy	\N	1	1	approved	2025-09-02 15:04:34	t	2025-09-02 15:04:34	2025-09-02 15:04:34
132	FTX-25-FCBB5T175803507479	transfer	1	27	67457.00	Adelanto Tesorería a Alexa Christiansen Wiegand	\N	1	1	approved	2025-07-29 15:04:34	t	2025-07-29 15:04:34	2025-07-29 15:04:34
133	FTX-25-GNKWEG175803507450	transfer	1	27	218378.00	Adelanto Tesorería a Alexa Christiansen Wiegand	\N	1	1	approved	2025-04-04 15:04:34	t	2025-04-04 15:04:34	2025-04-04 15:04:34
134	FTX-25-C60WES175803507473	transfer	1	27	177765.00	Adelanto Tesorería a Alexa Christiansen Wiegand	\N	1	1	approved	2025-01-11 15:04:34	t	2025-01-11 15:04:34	2025-01-11 15:04:34
135	FTX-25-NQ3RGX175803507418	transfer	1	27	247999.00	Adelanto Tesorería a Alexa Christiansen Wiegand	\N	1	1	approved	2025-08-15 15:04:34	t	2025-08-15 15:04:34	2025-08-15 15:04:34
136	RTX-25-QDKNCJ175803507481	transfer	27	1	19376.00	Devolución a Tesorería por Alexa Christiansen Wiegand	\N	1	1	approved	2025-06-04 15:04:34	t	2025-06-04 15:04:34	2025-06-04 15:04:34
137	FTX-26-T3O5IX175803507479	transfer	1	28	149830.00	Adelanto Tesorería a Madonna Dooley Paucek	\N	1	1	approved	2024-08-29 15:04:34	t	2024-08-29 15:04:34	2024-08-29 15:04:34
138	FTX-26-ZJBAVF175803507458	transfer	1	28	200480.00	Adelanto Tesorería a Madonna Dooley Paucek	\N	1	1	approved	2025-08-11 15:04:34	t	2025-08-11 15:04:34	2025-08-11 15:04:34
139	FTX-26-ULINJI175803507455	transfer	1	28	52816.00	Adelanto Tesorería a Madonna Dooley Paucek	\N	1	1	approved	2024-11-21 15:04:34	t	2024-11-21 15:04:34	2024-11-21 15:04:34
140	FTX-26-WGFN0N175803507485	transfer	1	28	109232.00	Adelanto Tesorería a Madonna Dooley Paucek	\N	1	1	approved	2025-09-15 15:04:34	t	2025-09-15 15:04:34	2025-09-15 15:04:34
141	FTX-26-6OCGAI175803507448	transfer	1	28	241926.00	Adelanto Tesorería a Madonna Dooley Paucek	\N	1	1	approved	2025-07-26 15:04:34	t	2025-07-26 15:04:34	2025-07-26 15:04:34
142	RTX-26-VYBSVJ175803507421	transfer	28	1	24767.00	Devolución a Tesorería por Madonna Dooley Paucek	\N	1	1	approved	2025-05-08 15:04:34	t	2025-05-08 15:04:34	2025-05-08 15:04:34
143	FTX-27-B8QP6P175803507475	transfer	1	29	143019.00	Adelanto Tesorería a Abagail Ebert Ortiz	\N	1	1	approved	2025-06-09 15:04:34	t	2025-06-09 15:04:34	2025-06-09 15:04:34
144	FTX-27-AJNBO1175803507476	transfer	1	29	94797.00	Adelanto Tesorería a Abagail Ebert Ortiz	\N	1	1	approved	2025-08-19 15:04:34	t	2025-08-19 15:04:34	2025-08-19 15:04:34
145	FTX-27-EOKT4S175803507442	transfer	1	29	243529.00	Adelanto Tesorería a Abagail Ebert Ortiz	\N	1	1	approved	2025-08-05 15:04:34	t	2025-08-05 15:04:34	2025-08-05 15:04:34
146	FTX-28-B0XB11175803507465	transfer	1	30	213071.00	Adelanto Tesorería a Mireya Terry Kihn	\N	1	1	approved	2025-01-19 15:04:34	t	2025-01-19 15:04:34	2025-01-19 15:04:34
147	FTX-28-6J3KWC175803507414	transfer	1	30	230013.00	Adelanto Tesorería a Mireya Terry Kihn	\N	1	1	approved	2025-06-30 15:04:34	t	2025-06-30 15:04:34	2025-06-30 15:04:34
148	FTX-28-OLTQZY175803507483	transfer	1	30	92481.00	Adelanto Tesorería a Mireya Terry Kihn	\N	1	1	approved	2024-12-24 15:04:34	t	2024-12-24 15:04:34	2024-12-24 15:04:34
149	FTX-28-38L3SX175803507480	transfer	1	30	135375.00	Adelanto Tesorería a Mireya Terry Kihn	\N	1	1	approved	2025-03-30 15:04:34	t	2025-03-30 15:04:34	2025-03-30 15:04:34
150	FTX-29-FOVDA8175803507492	transfer	1	31	23306.00	Adelanto Tesorería a Destiny Farrell Turner	\N	1	1	approved	2024-12-27 15:04:34	t	2024-12-27 15:04:34	2024-12-27 15:04:34
151	FTX-29-AM8IIZ175803507479	transfer	1	31	35906.00	Adelanto Tesorería a Destiny Farrell Turner	\N	1	1	approved	2024-11-25 15:04:34	t	2024-11-25 15:04:34	2024-11-25 15:04:34
152	FTX-29-LNXPAQ175803507459	transfer	1	31	142541.00	Adelanto Tesorería a Destiny Farrell Turner	\N	1	1	approved	2024-10-01 15:04:34	t	2024-10-01 15:04:34	2024-10-01 15:04:34
153	FTX-29-CVMHRD175803507475	transfer	1	31	94709.00	Adelanto Tesorería a Destiny Farrell Turner	\N	1	1	approved	2024-09-30 15:04:34	t	2024-09-30 15:04:34	2024-09-30 15:04:34
154	RTX-29-SOITFZ175803507495	transfer	31	1	5361.00	Devolución a Tesorería por Destiny Farrell Turner	\N	1	1	approved	2025-06-12 15:04:34	t	2025-06-12 15:04:34	2025-06-12 15:04:34
155	FTX-30-ENKOJF175803507490	transfer	1	32	38219.00	Adelanto Tesorería a Chloe Weimann McLaughlin	\N	1	1	approved	2024-10-25 15:04:34	t	2024-10-25 15:04:34	2024-10-25 15:04:34
156	FTX-30-CPJKJC175803507478	transfer	1	32	174706.00	Adelanto Tesorería a Chloe Weimann McLaughlin	\N	1	1	approved	2024-10-30 15:04:34	t	2024-10-30 15:04:34	2024-10-30 15:04:34
157	FTX-30-PMDLXP175803507436	transfer	1	32	72525.00	Adelanto Tesorería a Chloe Weimann McLaughlin	\N	1	1	approved	2025-07-02 15:04:34	t	2025-07-02 15:04:34	2025-07-02 15:04:34
158	FTX-30-VUL0JE175803507434	transfer	1	32	187318.00	Adelanto Tesorería a Chloe Weimann McLaughlin	\N	1	1	approved	2025-06-15 15:04:34	t	2025-06-15 15:04:34	2025-06-15 15:04:34
159	FTX-31-SJL1RX175803507480	transfer	1	33	104089.00	Adelanto Tesorería a Tesorero Sistema	\N	1	1	approved	2025-06-28 15:04:34	t	2025-06-28 15:04:34	2025-06-28 15:04:34
160	FTX-31-YEJVKG175803507447	transfer	1	33	65083.00	Adelanto Tesorería a Tesorero Sistema	\N	1	1	approved	2024-11-08 15:04:34	t	2024-11-08 15:04:34	2024-11-08 15:04:34
161	FTX-31-QJBKAP175803507451	transfer	1	33	290275.00	Adelanto Tesorería a Tesorero Sistema	\N	1	1	approved	2024-12-19 15:04:34	t	2024-12-19 15:04:34	2024-12-19 15:04:34
162	FTX-31-JNPD65175803507479	transfer	1	33	289747.00	Adelanto Tesorería a Tesorero Sistema	\N	1	1	approved	2025-07-06 15:04:34	t	2025-07-06 15:04:34	2025-07-06 15:04:34
163	ENS-3-BFRKYO-175803527336	transfer	1	3	90615.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-03-07 15:07:53	t	2025-03-07 15:07:53	2025-03-07 15:07:53
164	ENS-3-TCNVMX-175803527351	transfer	3	1	169819.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-04-07 15:07:53	t	2025-04-07 15:07:53	2025-04-07 15:07:53
165	ENS-3-6SZXMM-175803527352	transfer	1	3	66503.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-04-05 15:07:53	t	2024-04-05 15:07:53	2024-04-05 15:07:53
166	ENS-3-CGICIO-175803527353	transfer	3	1	208405.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-12-19 15:07:53	t	2024-12-19 15:07:53	2024-12-19 15:07:53
167	ENS-4-6SSLKA-175803527362	transfer	1	4	149683.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-12-29 15:07:53	t	2024-12-29 15:07:53	2024-12-29 15:07:53
168	ENS-4-STUU4R-175803527386	transfer	4	1	157237.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-07-07 15:07:53	t	2024-07-07 15:07:53	2024-07-07 15:07:53
169	ENS-4-CX3GE3-175803527366	transfer	1	4	2410.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-06-23 15:07:53	t	2025-06-23 15:07:53	2025-06-23 15:07:53
170	ENS-4-WGL8C4-175803527398	transfer	4	1	222394.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-10-19 15:07:53	t	2023-10-19 15:07:53	2023-10-19 15:07:53
171	ENS-4-2UONN7-175803527340	transfer	1	4	129643.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-08-17 15:07:53	t	2025-08-17 15:07:53	2025-08-17 15:07:53
172	ENS-4-9KI5RM-175803527352	transfer	4	1	210588.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-11-29 15:07:53	t	2023-11-29 15:07:53	2023-11-29 15:07:53
173	ENS-4-X52QTI-175803527355	transfer	1	4	210725.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-06-22 15:07:53	t	2024-06-22 15:07:53	2024-06-22 15:07:53
174	ENS-4-UE5LPR-175803527399	transfer	4	1	43047.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-01-09 15:07:53	t	2024-01-09 15:07:53	2024-01-09 15:07:53
175	ENS-4-7DPMUZ-175803527338	transfer	1	4	200570.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-04-26 15:07:53	t	2025-04-26 15:07:53	2025-04-26 15:07:53
176	ENS-5-XGGN3L-175803527373	transfer	1	5	228840.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-05-19 15:07:53	t	2024-05-19 15:07:53	2024-05-19 15:07:53
177	ENS-5-M8ECV7-175803527374	transfer	5	1	11268.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-07-22 15:07:53	t	2024-07-22 15:07:53	2024-07-22 15:07:53
178	ENS-5-ZOL4OR-175803527316	transfer	1	5	16381.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-07-12 15:07:53	t	2024-07-12 15:07:53	2024-07-12 15:07:53
179	ENS-5-TF4Y0Y-175803527396	transfer	5	1	240151.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-01-27 15:07:53	t	2025-01-27 15:07:53	2025-01-27 15:07:53
180	ENS-5-XIVOGC-175803527339	transfer	1	5	53751.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-02-07 15:07:53	t	2025-02-07 15:07:53	2025-02-07 15:07:53
181	ENS-5-YYOD0F-175803527393	transfer	5	1	225647.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-06-18 15:07:53	t	2024-06-18 15:07:53	2024-06-18 15:07:53
182	ENS-5-38ZLTB-175803527335	transfer	1	5	195599.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-04-18 15:07:53	t	2024-04-18 15:07:53	2024-04-18 15:07:53
183	ENS-5-X4WBB4-175803527372	transfer	5	1	213784.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-05-18 15:07:53	t	2025-05-18 15:07:53	2025-05-18 15:07:53
184	ENS-6-WS70TF-175803527331	transfer	1	6	16332.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-12-17 15:07:53	t	2023-12-17 15:07:53	2023-12-17 15:07:53
185	ENS-6-VL9QLP-175803527334	transfer	6	1	28969.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-11-14 15:07:53	t	2023-11-14 15:07:53	2023-11-14 15:07:53
186	ENS-6-0XKIYU-175803527376	transfer	1	6	80858.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-02-02 15:07:53	t	2025-02-02 15:07:53	2025-02-02 15:07:53
187	ENS-6-2BKJGG-175803527359	transfer	6	1	35018.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-08-24 15:07:53	t	2024-08-24 15:07:53	2024-08-24 15:07:53
188	ENS-6-CRAKEV-175803527321	transfer	1	6	183726.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-03-06 15:07:53	t	2024-03-06 15:07:53	2024-03-06 15:07:53
189	ENS-6-DTXXM8-175803527382	transfer	6	1	141472.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-12-31 15:07:53	t	2024-12-31 15:07:53	2024-12-31 15:07:53
190	ENS-6-JZT4S7-175803527348	transfer	1	6	12177.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-05-12 15:07:53	t	2024-05-12 15:07:53	2024-05-12 15:07:53
191	ENS-7-X0YNLV-175803527348	transfer	1	7	198812.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-07-18 15:07:53	t	2025-07-18 15:07:53	2025-07-18 15:07:53
192	ENS-7-EDRN1M-175803527389	transfer	7	1	151804.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-08-02 15:07:53	t	2025-08-02 15:07:53	2025-08-02 15:07:53
193	ENS-7-ZDVQT8-175803527310	transfer	1	7	182330.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-07-13 15:07:53	t	2025-07-13 15:07:53	2025-07-13 15:07:53
194	ENS-7-IVKVR8-175803527373	transfer	7	1	115155.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-03-28 15:07:53	t	2024-03-28 15:07:53	2024-03-28 15:07:53
195	ENS-7-ALMG5L-175803527331	transfer	1	7	147772.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-01-26 15:07:53	t	2024-01-26 15:07:53	2024-01-26 15:07:53
196	ENS-7-OOJU78-175803527313	transfer	7	1	86720.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-11-14 15:07:53	t	2024-11-14 15:07:53	2024-11-14 15:07:53
197	ENS-7-YUFG6U-175803527316	transfer	1	7	148120.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-03-20 15:07:53	t	2024-03-20 15:07:53	2024-03-20 15:07:53
198	ENS-7-SBF22N-175803527321	transfer	7	1	195010.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-10-19 15:07:53	t	2023-10-19 15:07:53	2023-10-19 15:07:53
199	ENS-8-V7HA1F-175803527360	transfer	1	8	95327.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-04-23 15:07:53	t	2024-04-23 15:07:53	2024-04-23 15:07:53
200	ENS-8-JYTP9K-175803527379	transfer	8	1	55830.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-12-31 15:07:53	t	2024-12-31 15:07:53	2024-12-31 15:07:53
201	ENS-8-ZYVYLX-175803527376	transfer	1	8	217290.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-12-12 15:07:53	t	2023-12-12 15:07:53	2023-12-12 15:07:53
202	ENS-8-R3TBSP-175803527398	transfer	8	1	133951.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-01-16 15:07:53	t	2025-01-16 15:07:53	2025-01-16 15:07:53
203	ENS-8-NAGIMP-175803527351	transfer	1	8	215745.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-09-11 15:07:53	t	2025-09-11 15:07:53	2025-09-11 15:07:53
204	ENS-8-NRX54D-175803527332	transfer	8	1	160848.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-06-19 15:07:53	t	2024-06-19 15:07:53	2024-06-19 15:07:53
205	ENS-8-1ZHUPJ-175803527322	transfer	1	8	241246.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-02-24 15:07:53	t	2025-02-24 15:07:53	2025-02-24 15:07:53
206	ENS-8-PVYO6N-175803527382	transfer	8	1	2713.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-12-21 15:07:53	t	2023-12-21 15:07:53	2023-12-21 15:07:53
207	ENS-9-CBHJRE-175803527325	transfer	1	9	31776.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-06-02 15:07:53	t	2025-06-02 15:07:53	2025-06-02 15:07:53
208	ENS-9-8YLGHF-175803527392	transfer	9	1	221123.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-02-17 15:07:53	t	2025-02-17 15:07:53	2025-02-17 15:07:53
209	ENS-9-C7QS6W-175803527343	transfer	1	9	223220.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-10-14 15:07:53	t	2023-10-14 15:07:53	2023-10-14 15:07:53
210	ENS-9-E8LFSL-175803527356	transfer	9	1	40612.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-01-07 15:07:53	t	2024-01-07 15:07:53	2024-01-07 15:07:53
211	ENS-9-TGU0HD-175803527323	transfer	1	9	106711.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-08-15 15:07:53	t	2025-08-15 15:07:53	2025-08-15 15:07:53
212	ENS-10-AFPHYH-175803527345	transfer	1	10	74382.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-08-29 15:07:53	t	2024-08-29 15:07:53	2024-08-29 15:07:53
213	ENS-10-DM5RVC-175803527372	transfer	10	1	2705.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-09-20 15:07:53	t	2024-09-20 15:07:53	2024-09-20 15:07:53
214	ENS-10-VFBOHS-175803527355	transfer	1	10	225861.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-11-04 15:07:53	t	2024-11-04 15:07:53	2024-11-04 15:07:53
215	ENS-10-XDVNAY-175803527382	transfer	10	1	32942.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-12-23 15:07:53	t	2023-12-23 15:07:53	2023-12-23 15:07:53
216	ENS-10-TCKVOL-175803527394	transfer	1	10	245786.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-09-05 15:07:53	t	2024-09-05 15:07:53	2024-09-05 15:07:53
217	ENS-10-A2LCD1-175803527396	transfer	10	1	21788.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-08-21 15:07:53	t	2024-08-21 15:07:53	2024-08-21 15:07:53
218	ENS-10-OKYUSC-175803527327	transfer	1	10	65461.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-09-16 15:07:53	t	2024-09-16 15:07:53	2024-09-16 15:07:53
219	ENS-10-DIBVJP-175803527334	transfer	10	1	49378.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-05-06 15:07:53	t	2024-05-06 15:07:53	2024-05-06 15:07:53
220	ENS-11-8C5JXL-175803527357	transfer	1	11	88247.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-04-04 15:07:53	t	2025-04-04 15:07:53	2025-04-04 15:07:53
221	ENS-11-L6RDUV-175803527337	transfer	11	1	40227.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-02-18 15:07:53	t	2025-02-18 15:07:53	2025-02-18 15:07:53
222	ENS-11-NMFLFH-175803527382	transfer	1	11	183142.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-06-14 15:07:53	t	2024-06-14 15:07:53	2024-06-14 15:07:53
223	ENS-11-DPZYTD-175803527314	transfer	11	1	237230.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-07-18 15:07:53	t	2025-07-18 15:07:53	2025-07-18 15:07:53
224	ENS-11-JWBUEM-175803527324	transfer	1	11	13590.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-07-28 15:07:53	t	2024-07-28 15:07:53	2024-07-28 15:07:53
225	ENS-11-MHHVH0-175803527364	transfer	11	1	133315.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-05-18 15:07:53	t	2024-05-18 15:07:53	2024-05-18 15:07:53
226	ENS-11-ZII4JI-175803527342	transfer	1	11	54225.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-01-26 15:07:53	t	2025-01-26 15:07:53	2025-01-26 15:07:53
227	ENS-11-ACFPD2-175803527315	transfer	11	1	74194.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-02-02 15:07:53	t	2025-02-02 15:07:53	2025-02-02 15:07:53
228	ENS-12-GDP98B-175803527330	transfer	1	12	6857.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-03-04 15:07:53	t	2025-03-04 15:07:53	2025-03-04 15:07:53
229	ENS-12-GLARJH-175803527319	transfer	12	1	74361.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-02-08 15:07:53	t	2024-02-08 15:07:53	2024-02-08 15:07:53
230	ENS-12-KVNWGI-175803527383	transfer	1	12	138632.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-02-11 15:07:53	t	2024-02-11 15:07:53	2024-02-11 15:07:53
231	ENS-12-2K0QDH-175803527347	transfer	12	1	74931.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-01-09 15:07:53	t	2025-01-09 15:07:53	2025-01-09 15:07:53
232	ENS-12-BHS8PY-175803527341	transfer	1	12	75836.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-06-25 15:07:53	t	2025-06-25 15:07:53	2025-06-25 15:07:53
233	ENS-12-YZIHJV-175803527325	transfer	12	1	108427.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-01-04 15:07:53	t	2025-01-04 15:07:53	2025-01-04 15:07:53
234	ENS-12-ZHZBAC-175803527351	transfer	1	12	150867.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-09-05 15:07:53	t	2024-09-05 15:07:53	2024-09-05 15:07:53
235	ENS-12-XLVFGL-175803527330	transfer	12	1	16917.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-02-05 15:07:53	t	2025-02-05 15:07:53	2025-02-05 15:07:53
236	ENS-13-HLNTOS-175803527368	transfer	1	13	216427.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-02-23 15:07:53	t	2025-02-23 15:07:53	2025-02-23 15:07:53
237	ENS-13-AGHMMF-175803527329	transfer	13	1	248441.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-09-02 15:07:53	t	2024-09-02 15:07:53	2024-09-02 15:07:53
238	ENS-13-ER5KZR-175803527336	transfer	1	13	52935.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-01-18 15:07:53	t	2025-01-18 15:07:53	2025-01-18 15:07:53
239	ENS-13-Y76B8T-175803527383	transfer	13	1	165662.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-08-07 15:07:53	t	2025-08-07 15:07:53	2025-08-07 15:07:53
240	ENS-14-RXIRUD-175803527335	transfer	1	14	147475.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-01-17 15:07:53	t	2025-01-17 15:07:53	2025-01-17 15:07:53
241	ENS-14-L5CTMP-175803527371	transfer	14	1	81073.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-03-07 15:07:53	t	2025-03-07 15:07:53	2025-03-07 15:07:53
242	ENS-14-JOI7J3-175803527360	transfer	1	14	149871.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-12-01 15:07:53	t	2024-12-01 15:07:53	2024-12-01 15:07:53
243	ENS-14-KZOEZE-175803527313	transfer	14	1	191299.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-08-05 15:07:53	t	2025-08-05 15:07:53	2025-08-05 15:07:53
244	ENS-14-5HFTXO-175803527327	transfer	1	14	247136.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-09-15 15:07:53	t	2025-09-15 15:07:53	2025-09-15 15:07:53
245	ENS-14-OBH1QE-175803527361	transfer	14	1	198900.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-11-24 15:07:53	t	2023-11-24 15:07:53	2023-11-24 15:07:53
246	ENS-14-R2GPVC-175803527370	transfer	1	14	144043.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-10-20 15:07:53	t	2023-10-20 15:07:53	2023-10-20 15:07:53
247	ENS-15-QTI3TC-175803527339	transfer	1	15	199185.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-02-19 15:07:53	t	2025-02-19 15:07:53	2025-02-19 15:07:53
248	ENS-15-0ZHPMC-175803527376	transfer	15	1	146144.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-10-29 15:07:53	t	2024-10-29 15:07:53	2024-10-29 15:07:53
249	ENS-15-VGKPLD-175803527316	transfer	1	15	232108.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-03-14 15:07:53	t	2024-03-14 15:07:53	2024-03-14 15:07:53
250	ENS-15-HGB5HU-175803527358	transfer	15	1	68808.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-06-09 15:07:53	t	2025-06-09 15:07:53	2025-06-09 15:07:53
251	ENS-15-S5IGIU-175803527348	transfer	1	15	55442.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-09-14 15:07:53	t	2025-09-14 15:07:53	2025-09-14 15:07:53
252	ENS-15-JYCJQH-175803527384	transfer	15	1	173765.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-07-02 15:07:53	t	2024-07-02 15:07:53	2024-07-02 15:07:53
253	ENS-15-WR39CY-175803527379	transfer	1	15	13263.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-04-07 15:07:53	t	2025-04-07 15:07:53	2025-04-07 15:07:53
254	ENS-15-J5Z24N-175803527345	transfer	15	1	133683.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-10-03 15:07:53	t	2024-10-03 15:07:53	2024-10-03 15:07:53
255	ENS-16-PRAPOV-175803527373	transfer	1	16	72964.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-03-11 15:07:53	t	2025-03-11 15:07:53	2025-03-11 15:07:53
256	ENS-16-KUZ6VD-175803527391	transfer	16	1	241452.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-11-11 15:07:53	t	2024-11-11 15:07:53	2024-11-11 15:07:53
257	ENS-16-9E2FNU-175803527350	transfer	1	16	166621.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-01-16 15:07:53	t	2024-01-16 15:07:53	2024-01-16 15:07:53
258	ENS-16-KDUMPU-175803527329	transfer	16	1	215570.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-05-28 15:07:53	t	2024-05-28 15:07:53	2024-05-28 15:07:53
259	ENS-16-GPFJ42-175803527432	transfer	1	16	112699.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-12-13 15:07:54	t	2024-12-13 15:07:54	2024-12-13 15:07:54
260	ENS-17-BZWSRS-175803527486	transfer	1	17	213101.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-09-17 15:07:54	t	2024-09-17 15:07:54	2024-09-17 15:07:54
261	ENS-17-005ED4-175803527468	transfer	17	1	63126.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-11-12 15:07:54	t	2023-11-12 15:07:54	2023-11-12 15:07:54
262	ENS-17-D2AXYJ-175803527423	transfer	1	17	95752.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-04-23 15:07:54	t	2024-04-23 15:07:54	2024-04-23 15:07:54
263	ENS-17-0SI8KZ-175803527429	transfer	17	1	202062.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-12-27 15:07:54	t	2023-12-27 15:07:54	2023-12-27 15:07:54
264	ENS-17-2Y3YMJ-175803527438	transfer	1	17	144189.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-11-02 15:07:54	t	2024-11-02 15:07:54	2024-11-02 15:07:54
265	ENS-17-FVYLRX-175803527416	transfer	17	1	5163.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-02-29 15:07:54	t	2024-02-29 15:07:54	2024-02-29 15:07:54
266	ENS-17-9A8WHI-175803527410	transfer	1	17	227775.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-02-04 15:07:54	t	2024-02-04 15:07:54	2024-02-04 15:07:54
267	ENS-17-EQV254-175803527490	transfer	17	1	71279.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-12-16 15:07:54	t	2023-12-16 15:07:54	2023-12-16 15:07:54
268	ENS-18-UXBUH8-175803527484	transfer	1	18	79633.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-05-16 15:07:54	t	2025-05-16 15:07:54	2025-05-16 15:07:54
269	ENS-18-D9IKBL-175803527441	transfer	18	1	105441.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-12-12 15:07:54	t	2024-12-12 15:07:54	2024-12-12 15:07:54
270	ENS-18-TWAZYA-175803527436	transfer	1	18	94141.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-07-01 15:07:54	t	2025-07-01 15:07:54	2025-07-01 15:07:54
271	ENS-18-1WIPBN-175803527491	transfer	18	1	155219.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-09-16 15:07:54	t	2024-09-16 15:07:54	2024-09-16 15:07:54
272	ENS-19-EZ9ULB-175803527448	transfer	1	19	76308.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-07-10 15:07:54	t	2024-07-10 15:07:54	2024-07-10 15:07:54
273	ENS-19-3JCBWP-175803527498	transfer	19	1	187069.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-04-15 15:07:54	t	2025-04-15 15:07:54	2025-04-15 15:07:54
274	ENS-19-XBOHAD-175803527436	transfer	1	19	245727.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-10-09 15:07:54	t	2024-10-09 15:07:54	2024-10-09 15:07:54
275	ENS-19-HF9U5U-175803527474	transfer	19	1	43889.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-03-22 15:07:54	t	2024-03-22 15:07:54	2024-03-22 15:07:54
276	ENS-19-FUGTCC-175803527444	transfer	1	19	23861.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-03-06 15:07:54	t	2025-03-06 15:07:54	2025-03-06 15:07:54
277	ENS-19-5JEM5O-175803527431	transfer	19	1	191519.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-09-14 15:07:54	t	2025-09-14 15:07:54	2025-09-14 15:07:54
278	ENS-19-HOADKD-175803527478	transfer	1	19	81348.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-05-21 15:07:54	t	2024-05-21 15:07:54	2024-05-21 15:07:54
279	ENS-20-SNAJJT-175803527474	transfer	1	20	178573.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-03-22 15:07:54	t	2025-03-22 15:07:54	2025-03-22 15:07:54
280	ENS-20-MPF943-175803527444	transfer	20	1	50991.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-01-23 15:07:54	t	2025-01-23 15:07:54	2025-01-23 15:07:54
281	ENS-20-T9H0MI-175803527489	transfer	1	20	85263.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-09-09 15:07:54	t	2024-09-09 15:07:54	2024-09-09 15:07:54
282	ENS-20-XLA00K-175803527466	transfer	20	1	162718.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-02-25 15:07:54	t	2024-02-25 15:07:54	2024-02-25 15:07:54
283	ENS-20-JLINGN-175803527427	transfer	1	20	87232.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-05-24 15:07:54	t	2025-05-24 15:07:54	2025-05-24 15:07:54
284	ENS-20-4RIVEW-175803527469	transfer	20	1	182454.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-11-10 15:07:54	t	2024-11-10 15:07:54	2024-11-10 15:07:54
285	ENS-20-NTBYTX-175803527467	transfer	1	20	1353.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-12-15 15:07:54	t	2024-12-15 15:07:54	2024-12-15 15:07:54
286	ENS-21-20BFVL-175803527452	transfer	1	21	78604.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-06-21 15:07:54	t	2024-06-21 15:07:54	2024-06-21 15:07:54
287	ENS-21-WVXCGI-175803527410	transfer	21	1	53932.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-07-15 15:07:54	t	2024-07-15 15:07:54	2024-07-15 15:07:54
288	ENS-21-HL90HS-175803527459	transfer	1	21	236418.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-07-20 15:07:54	t	2024-07-20 15:07:54	2024-07-20 15:07:54
289	ENS-21-118GF2-175803527489	transfer	21	1	148640.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-11-23 15:07:54	t	2023-11-23 15:07:54	2023-11-23 15:07:54
290	ENS-21-QUAALT-175803527437	transfer	1	21	92863.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-02-18 15:07:54	t	2025-02-18 15:07:54	2025-02-18 15:07:54
291	ENS-21-A6NESG-175803527452	transfer	21	1	21051.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-06-29 15:07:54	t	2024-06-29 15:07:54	2024-06-29 15:07:54
292	ENS-21-SFBUPB-175803527434	transfer	1	21	166355.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-09-04 15:07:54	t	2025-09-04 15:07:54	2025-09-04 15:07:54
293	ENS-21-0W8N5H-175803527458	transfer	21	1	141246.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-10-01 15:07:54	t	2023-10-01 15:07:54	2023-10-01 15:07:54
294	ENS-22-MDKDBU-175803527447	transfer	1	22	198135.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-02-23 15:07:54	t	2025-02-23 15:07:54	2025-02-23 15:07:54
295	ENS-22-OBPFB1-175803527455	transfer	22	1	182381.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-04-28 15:07:54	t	2024-04-28 15:07:54	2024-04-28 15:07:54
296	ENS-22-EUV36J-175803527473	transfer	1	22	40598.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-02-01 15:07:54	t	2025-02-01 15:07:54	2025-02-01 15:07:54
297	ENS-22-IOEEBM-175803527435	transfer	22	1	132088.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-12-27 15:07:54	t	2024-12-27 15:07:54	2024-12-27 15:07:54
298	ENS-22-UUGPHN-175803527479	transfer	1	22	193433.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-03-18 15:07:54	t	2024-03-18 15:07:54	2024-03-18 15:07:54
299	ENS-23-WC5FHN-175803527494	transfer	1	23	212531.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-04-15 15:07:54	t	2025-04-15 15:07:54	2025-04-15 15:07:54
300	ENS-23-OVYPBP-175803527456	transfer	23	1	180698.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-12-29 15:07:54	t	2024-12-29 15:07:54	2024-12-29 15:07:54
301	ENS-23-YT7PUN-175803527473	transfer	1	23	185389.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-09-10 15:07:54	t	2025-09-10 15:07:54	2025-09-10 15:07:54
302	ENS-23-I1XBPF-175803527445	transfer	23	1	107780.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-11-07 15:07:54	t	2024-11-07 15:07:54	2024-11-07 15:07:54
303	ENS-23-BICUSB-175803527469	transfer	1	23	204104.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-12-15 15:07:54	t	2024-12-15 15:07:54	2024-12-15 15:07:54
304	ENS-24-5F00EN-175803527461	transfer	1	24	15665.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-05-13 15:07:54	t	2024-05-13 15:07:54	2024-05-13 15:07:54
305	ENS-24-8GNEEC-175803527467	transfer	24	1	88170.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-06-27 15:07:54	t	2024-06-27 15:07:54	2024-06-27 15:07:54
306	ENS-24-YM2EMO-175803527422	transfer	1	24	224875.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-04-06 15:07:54	t	2025-04-06 15:07:54	2025-04-06 15:07:54
307	ENS-24-AFBCFS-175803527466	transfer	24	1	229515.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-08-19 15:07:54	t	2025-08-19 15:07:54	2025-08-19 15:07:54
308	ENS-24-OQKO3R-175803527448	transfer	1	24	34383.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-06-09 15:07:54	t	2025-06-09 15:07:54	2025-06-09 15:07:54
309	ENS-24-FWAUX1-175803527483	transfer	24	1	217653.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-03-09 15:07:54	t	2025-03-09 15:07:54	2025-03-09 15:07:54
310	ENS-25-0PRXIC-175803527435	transfer	1	25	12804.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-04-22 15:07:54	t	2024-04-22 15:07:54	2024-04-22 15:07:54
311	ENS-25-FEBHWV-175803527485	transfer	25	1	239719.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-02-15 15:07:54	t	2024-02-15 15:07:54	2024-02-15 15:07:54
312	ENS-25-MCSMOU-175803527426	transfer	1	25	183798.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-07-11 15:07:54	t	2024-07-11 15:07:54	2024-07-11 15:07:54
313	ENS-25-0VNVBZ-175803527466	transfer	25	1	203037.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-06-27 15:07:54	t	2024-06-27 15:07:54	2024-06-27 15:07:54
314	ENS-25-UBQLAQ-175803527485	transfer	1	25	181992.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-05-15 15:07:54	t	2024-05-15 15:07:54	2024-05-15 15:07:54
315	ENS-25-5MGDOR-175803527430	transfer	25	1	174179.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-01-16 15:07:54	t	2025-01-16 15:07:54	2025-01-16 15:07:54
316	ENS-25-BUKJ84-175803527480	transfer	1	25	163549.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-04-14 15:07:54	t	2025-04-14 15:07:54	2025-04-14 15:07:54
317	ENS-26-56508O-175803527493	transfer	1	26	48266.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-10-10 15:07:54	t	2024-10-10 15:07:54	2024-10-10 15:07:54
318	ENS-26-AX8DF3-175803527475	transfer	26	1	208703.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-08-27 15:07:54	t	2025-08-27 15:07:54	2025-08-27 15:07:54
319	ENS-26-7OWRBW-175803527451	transfer	1	26	23981.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-04-20 15:07:54	t	2024-04-20 15:07:54	2024-04-20 15:07:54
320	ENS-26-GL9QG3-175803527479	transfer	26	1	237226.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-11-25 15:07:54	t	2023-11-25 15:07:54	2023-11-25 15:07:54
321	ENS-26-FJMDFZ-175803527425	transfer	1	26	208548.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-08-16 15:07:54	t	2024-08-16 15:07:54	2024-08-16 15:07:54
322	ENS-27-LBWBWE-175803527414	transfer	1	27	163358.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-05-09 15:07:54	t	2024-05-09 15:07:54	2024-05-09 15:07:54
323	ENS-27-0JQRJU-175803527496	transfer	27	1	131936.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-10-11 15:07:54	t	2023-10-11 15:07:54	2023-10-11 15:07:54
324	ENS-27-ZVL1CK-175803527456	transfer	1	27	30920.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-02-27 15:07:54	t	2024-02-27 15:07:54	2024-02-27 15:07:54
325	ENS-27-IXTXDO-175803527460	transfer	27	1	166953.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-06-24 15:07:54	t	2024-06-24 15:07:54	2024-06-24 15:07:54
326	ENS-27-3KFOQH-175803527499	transfer	1	27	160101.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-09-18 15:07:54	t	2024-09-18 15:07:54	2024-09-18 15:07:54
327	ENS-27-YU90MM-175803527423	transfer	27	1	114716.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-06-27 15:07:54	t	2024-06-27 15:07:54	2024-06-27 15:07:54
328	ENS-27-NHDKU6-175803527483	transfer	1	27	52631.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-09-01 15:07:54	t	2025-09-01 15:07:54	2025-09-01 15:07:54
329	ENS-28-SJMTF4-175803527452	transfer	1	28	113218.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-10-22 15:07:54	t	2024-10-22 15:07:54	2024-10-22 15:07:54
330	ENS-28-0A74CT-175803527493	transfer	28	1	33254.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-05-19 15:07:54	t	2025-05-19 15:07:54	2025-05-19 15:07:54
331	ENS-28-KFTCCE-175803527488	transfer	1	28	133238.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-05-14 15:07:54	t	2025-05-14 15:07:54	2025-05-14 15:07:54
332	ENS-28-F5JFPH-175803527474	transfer	28	1	141231.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-03-16 15:07:54	t	2025-03-16 15:07:54	2025-03-16 15:07:54
333	ENS-28-A6C9OT-175803527414	transfer	1	28	167791.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-10-31 15:07:54	t	2024-10-31 15:07:54	2024-10-31 15:07:54
334	ENS-28-OGU1BA-175803527488	transfer	28	1	52013.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-12-29 15:07:54	t	2023-12-29 15:07:54	2023-12-29 15:07:54
335	ENS-29-1O3RQW-175803527462	transfer	1	29	125128.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-02-23 15:07:54	t	2025-02-23 15:07:54	2025-02-23 15:07:54
336	ENS-29-QCLDVE-175803527478	transfer	29	1	163800.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-03-20 15:07:54	t	2025-03-20 15:07:54	2025-03-20 15:07:54
337	ENS-29-P0ZAXU-175803527422	transfer	1	29	41184.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-06-07 15:07:54	t	2024-06-07 15:07:54	2024-06-07 15:07:54
338	ENS-29-EE3D9D-175803527448	transfer	29	1	89774.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-05-27 15:07:54	t	2025-05-27 15:07:54	2025-05-27 15:07:54
339	ENS-29-3CWH4T-175803527454	transfer	1	29	206288.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-04-18 15:07:54	t	2024-04-18 15:07:54	2024-04-18 15:07:54
340	ENS-29-IQGZB7-175803527484	transfer	29	1	239744.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-08-19 15:07:54	t	2024-08-19 15:07:54	2024-08-19 15:07:54
341	ENS-29-VAY5M2-175803527491	transfer	1	29	68843.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-11-07 15:07:54	t	2023-11-07 15:07:54	2023-11-07 15:07:54
342	ENS-29-5MWLDH-175803527442	transfer	29	1	144300.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-08-26 15:07:54	t	2024-08-26 15:07:54	2024-08-26 15:07:54
343	ENS-29-RBONUI-175803527495	transfer	1	29	18574.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-01-18 15:07:54	t	2024-01-18 15:07:54	2024-01-18 15:07:54
344	ENS-30-R5Q5K0-175803527487	transfer	1	30	228336.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-04-12 15:07:54	t	2024-04-12 15:07:54	2024-04-12 15:07:54
345	ENS-30-FQJHEV-175803527492	transfer	30	1	188321.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-10-17 15:07:54	t	2024-10-17 15:07:54	2024-10-17 15:07:54
346	ENS-30-YNJM0F-175803527451	transfer	1	30	42258.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-11-05 15:07:54	t	2024-11-05 15:07:54	2024-11-05 15:07:54
347	ENS-30-0YKMRW-175803527494	transfer	30	1	51755.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-05-20 15:07:54	t	2025-05-20 15:07:54	2025-05-20 15:07:54
348	ENS-30-W5E7AP-175803527441	transfer	1	30	244149.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-11-01 15:07:54	t	2023-11-01 15:07:54	2023-11-01 15:07:54
349	ENS-30-3VACNP-175803527482	transfer	30	1	37468.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-02-07 15:07:54	t	2025-02-07 15:07:54	2025-02-07 15:07:54
350	ENS-30-IEZNXP-175803527434	transfer	1	30	10561.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-03-25 15:07:54	t	2024-03-25 15:07:54	2024-03-25 15:07:54
351	ENS-30-4BPQA3-175803527462	transfer	30	1	134523.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-12-20 15:07:54	t	2024-12-20 15:07:54	2024-12-20 15:07:54
352	ENS-31-MIDLZO-175803527428	transfer	1	31	225304.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-11-22 15:07:54	t	2024-11-22 15:07:54	2024-11-22 15:07:54
353	ENS-31-IOTFGU-175803527420	transfer	31	1	148161.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-07-24 15:07:54	t	2025-07-24 15:07:54	2025-07-24 15:07:54
354	ENS-31-SHYXFC-175803527424	transfer	1	31	40176.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-10-25 15:07:54	t	2024-10-25 15:07:54	2024-10-25 15:07:54
355	ENS-31-EMLCV4-175803527434	transfer	31	1	124629.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-05-14 15:07:54	t	2024-05-14 15:07:54	2024-05-14 15:07:54
356	ENS-31-5GG7G4-175803527481	transfer	1	31	15484.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-05-26 15:07:54	t	2025-05-26 15:07:54	2025-05-26 15:07:54
357	ENS-31-QRULEF-175803527434	transfer	31	1	88036.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-03-13 15:07:54	t	2024-03-13 15:07:54	2024-03-13 15:07:54
358	ENS-31-M1F9VS-175803527416	transfer	1	31	38045.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-07-22 15:07:54	t	2025-07-22 15:07:54	2025-07-22 15:07:54
359	ENS-32-7X8QQU-175803527455	transfer	1	32	33273.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-07-02 15:07:54	t	2024-07-02 15:07:54	2024-07-02 15:07:54
360	ENS-32-JH57JX-175803527475	transfer	32	1	157044.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-08-04 15:07:54	t	2025-08-04 15:07:54	2025-08-04 15:07:54
361	ENS-32-UR3MVL-175803527483	transfer	1	32	124983.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-08-04 15:07:54	t	2025-08-04 15:07:54	2025-08-04 15:07:54
362	ENS-32-7EJVIO-175803527417	transfer	32	1	53509.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-11-23 15:07:54	t	2024-11-23 15:07:54	2024-11-23 15:07:54
363	ENS-32-AHRBJD-175803527485	transfer	1	32	48927.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-06-11 15:07:54	t	2024-06-11 15:07:54	2024-06-11 15:07:54
364	ENS-32-LPPCQD-175803527430	transfer	32	1	243589.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-02-17 15:07:54	t	2024-02-17 15:07:54	2024-02-17 15:07:54
365	ENS-32-0N7KBW-175803527418	transfer	1	32	118559.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-11-30 15:07:54	t	2023-11-30 15:07:54	2023-11-30 15:07:54
366	ENS-32-EL3QX7-175803527442	transfer	32	1	220038.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-01-19 15:07:54	t	2024-01-19 15:07:54	2024-01-19 15:07:54
367	ENS-2-FDHVK8-175803527453	transfer	1	2	20433.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-01-13 15:07:54	t	2025-01-13 15:07:54	2025-01-13 15:07:54
368	ENS-2-PM3PPJ-175803527420	transfer	2	1	193271.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-07-05 15:07:54	t	2024-07-05 15:07:54	2024-07-05 15:07:54
369	ENS-2-L7OUZY-175803527429	transfer	1	2	181845.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-03-24 15:07:54	t	2025-03-24 15:07:54	2025-03-24 15:07:54
370	ENS-2-OXE4C9-175803527445	transfer	2	1	188173.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-12-19 15:07:54	t	2024-12-19 15:07:54	2024-12-19 15:07:54
371	ENS-2-KNJH7L-175803527433	transfer	1	2	1754.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-04-12 15:07:54	t	2024-04-12 15:07:54	2024-04-12 15:07:54
372	ENS-2-QGPK5X-175803527428	transfer	2	1	107378.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-05-04 15:07:54	t	2025-05-04 15:07:54	2025-05-04 15:07:54
373	ENS-2-PYAX5L-175803527431	transfer	1	2	28804.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-09-01 15:07:54	t	2025-09-01 15:07:54	2025-09-01 15:07:54
374	ENS-2-4N6YTB-175803527470	transfer	2	1	204628.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-04-21 15:07:54	t	2025-04-21 15:07:54	2025-04-21 15:07:54
375	ENS-2-5BHOJK-175803527448	transfer	1	2	72272.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-03-22 15:07:54	t	2024-03-22 15:07:54	2024-03-22 15:07:54
376	ENS-2-C0IHZ3-175803527485	transfer	2	1	102725.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-09-27 15:07:54	t	2023-09-27 15:07:54	2023-09-27 15:07:54
377	ENS-2-P42VYI-175803527497	transfer	1	2	206573.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-06-23 15:07:54	t	2024-06-23 15:07:54	2024-06-23 15:07:54
378	ENS-33-W2E2GP-175803527416	transfer	1	33	5224.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-10-20 15:07:54	t	2024-10-20 15:07:54	2024-10-20 15:07:54
379	ENS-33-0W1L0B-175803527447	transfer	33	1	228219.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-02-24 15:07:54	t	2025-02-24 15:07:54	2025-02-24 15:07:54
380	ENS-33-UPM6SR-175803527465	transfer	1	33	27407.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-05-30 15:07:54	t	2024-05-30 15:07:54	2024-05-30 15:07:54
381	ENS-33-PTCQOC-175803527446	transfer	33	1	134394.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-09-15 15:07:54	t	2025-09-15 15:07:54	2025-09-15 15:07:54
382	ENS-33-J6IVTZ-175803527417	transfer	1	33	151404.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-05-15 15:07:54	t	2025-05-15 15:07:54	2025-05-15 15:07:54
383	ENS-33-FLLLDS-175803527453	transfer	33	1	187129.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2023-11-09 15:07:54	t	2023-11-09 15:07:54	2023-11-09 15:07:54
384	ENS-33-KUBYGH-175803527431	transfer	1	33	185919.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2024-09-05 15:07:54	t	2024-09-05 15:07:54	2024-09-05 15:07:54
385	ENS-33-ZHS5LU-175803527497	transfer	33	1	141878.00	Ensanchamiento automático de transacciones	\N	1	1	approved	2025-01-10 15:07:54	t	2025-01-10 15:07:54	2025-01-10 15:07:54
386	TXN-2025-003	transfer	2	1	300000000.00	dfdfgdfgdfg	\N	1	1	approved	2025-09-16 15:26:27	t	2025-09-16 15:26:17	2025-09-16 15:26:27
2	TXN-2025-002	transfer	1	3	300000.00	Transferencia para gastos de cuadrilla Sur	Fondos para proyecto específico	1	1	approved	2025-09-16 15:26:38	t	2025-09-16 12:06:28	2025-09-16 15:26:38
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: admin
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, is_enabled, person_id) FROM stdin;
1	Administrador	admin@coteso.com	2025-09-16 12:06:27	$2y$12$QiVqQB6/yCfWhi.LRmG7p.i5BE8runuQHf1pFuRB1ko.rKFj/VfJi	\N	2025-09-16 12:06:27	2025-09-16 12:06:27	t	\N
2	Tesorero Principal	tesorero@coteso.com	2025-09-16 12:06:27	$2y$12$WWIkI2oHId6Gawds4pAaN.rv2ZPq5HshEtYajOI165bL3oMt84jZa	\N	2025-09-16 12:06:27	2025-09-16 12:06:27	t	\N
3	Tesorero Sistema	treasurer@coteso.local	\N	$2y$12$bUTGnk4QYHHak3wh7FzLo.QrSuYVa2ucu0z8yrA6CUhTalZQXv8A2	\N	2025-09-16 12:06:28	2025-09-16 12:06:28	t	31
\.


--
-- Name: account_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.account_types_id_seq', 5, true);


--
-- Name: accounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.accounts_id_seq', 33, true);


--
-- Name: activity_log_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.activity_log_id_seq', 594, true);


--
-- Name: banks_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.banks_id_seq', 32, true);


--
-- Name: documents_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.documents_id_seq', 2, true);


--
-- Name: expense_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.expense_categories_id_seq', 10, true);


--
-- Name: expense_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.expense_items_id_seq', 227, true);


--
-- Name: expenses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.expenses_id_seq', 92, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: media_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.media_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.migrations_id_seq', 26, true);


--
-- Name: people_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.people_id_seq', 31, true);


--
-- Name: permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.permissions_id_seq', 26, true);


--
-- Name: person_bank_accounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.person_bank_accounts_id_seq', 1, false);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.roles_id_seq', 4, true);


--
-- Name: transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.transactions_id_seq', 386, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: admin
--

SELECT pg_catalog.setval('public.users_id_seq', 3, true);


--
-- Name: account_types account_types_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.account_types
    ADD CONSTRAINT account_types_pkey PRIMARY KEY (id);


--
-- Name: accounts accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (id);


--
-- Name: activity_log activity_log_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.activity_log
    ADD CONSTRAINT activity_log_pkey PRIMARY KEY (id);


--
-- Name: banks banks_code_unique; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.banks
    ADD CONSTRAINT banks_code_unique UNIQUE (code);


--
-- Name: banks banks_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.banks
    ADD CONSTRAINT banks_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: documents documents_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_pkey PRIMARY KEY (id);


--
-- Name: expense_categories expense_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.expense_categories
    ADD CONSTRAINT expense_categories_pkey PRIMARY KEY (id);


--
-- Name: expense_items expense_items_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.expense_items
    ADD CONSTRAINT expense_items_pkey PRIMARY KEY (id);


--
-- Name: expenses expenses_expense_number_unique; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_expense_number_unique UNIQUE (expense_number);


--
-- Name: expenses expenses_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: media media_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.media
    ADD CONSTRAINT media_pkey PRIMARY KEY (id);


--
-- Name: media media_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.media
    ADD CONSTRAINT media_uuid_unique UNIQUE (uuid);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (permission_id, model_id, model_type);


--
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (role_id, model_id, model_type);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: people people_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.people
    ADD CONSTRAINT people_pkey PRIMARY KEY (id);


--
-- Name: people people_rut_unique; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.people
    ADD CONSTRAINT people_rut_unique UNIQUE (rut);


--
-- Name: permissions permissions_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: person_bank_accounts person_bank_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.person_bank_accounts
    ADD CONSTRAINT person_bank_accounts_pkey PRIMARY KEY (id);


--
-- Name: role_has_permissions role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id);


--
-- Name: roles roles_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: transactions transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (id);


--
-- Name: transactions transactions_transaction_number_unique; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_transaction_number_unique UNIQUE (transaction_number);


--
-- Name: person_bank_accounts uniq_personal_account_combo; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.person_bank_accounts
    ADD CONSTRAINT uniq_personal_account_combo UNIQUE (person_id, bank_id, account_type_id, account_number);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: accounts_one_fondeo; Type: INDEX; Schema: public; Owner: admin
--

CREATE UNIQUE INDEX accounts_one_fondeo ON public.accounts USING btree (is_fondeo) WHERE (is_fondeo = true);


--
-- Name: accounts_one_treasury; Type: INDEX; Schema: public; Owner: admin
--

CREATE UNIQUE INDEX accounts_one_treasury ON public.accounts USING btree (type) WHERE ((type)::text = 'treasury'::text);


--
-- Name: activity_log_log_name_index; Type: INDEX; Schema: public; Owner: admin
--

CREATE INDEX activity_log_log_name_index ON public.activity_log USING btree (log_name);


--
-- Name: causer; Type: INDEX; Schema: public; Owner: admin
--

CREATE INDEX causer ON public.activity_log USING btree (causer_type, causer_id);


--
-- Name: expense_items_unique_doc_combo; Type: INDEX; Schema: public; Owner: admin
--

CREATE UNIQUE INDEX expense_items_unique_doc_combo ON public.expense_items USING btree (document_type, lower(TRIM(BOTH FROM vendor_name)), lower(TRIM(BOTH FROM document_number))) WHERE (document_number IS NOT NULL);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: admin
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: media_model_type_model_id_index; Type: INDEX; Schema: public; Owner: admin
--

CREATE INDEX media_model_type_model_id_index ON public.media USING btree (model_type, model_id);


--
-- Name: media_order_column_index; Type: INDEX; Schema: public; Owner: admin
--

CREATE INDEX media_order_column_index ON public.media USING btree (order_column);


--
-- Name: model_has_permissions_model_id_model_type_index; Type: INDEX; Schema: public; Owner: admin
--

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: admin
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: admin
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: admin
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: subject; Type: INDEX; Schema: public; Owner: admin
--

CREATE INDEX subject ON public.activity_log USING btree (subject_type, subject_id);


--
-- Name: transactions trg_validate_transaction_accounts; Type: TRIGGER; Schema: public; Owner: admin
--

CREATE TRIGGER trg_validate_transaction_accounts BEFORE INSERT OR UPDATE ON public.transactions FOR EACH ROW EXECUTE FUNCTION public.validate_transaction_accounts();


--
-- Name: accounts accounts_person_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_person_id_foreign FOREIGN KEY (person_id) REFERENCES public.people(id);


--
-- Name: documents documents_expense_item_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_expense_item_id_foreign FOREIGN KEY (expense_item_id) REFERENCES public.expense_items(id);


--
-- Name: documents documents_uploaded_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_uploaded_by_foreign FOREIGN KEY (uploaded_by) REFERENCES public.users(id);


--
-- Name: expense_items expense_items_expense_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.expense_items
    ADD CONSTRAINT expense_items_expense_category_id_foreign FOREIGN KEY (expense_category_id) REFERENCES public.expense_categories(id) ON DELETE SET NULL;


--
-- Name: expense_items expense_items_expense_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.expense_items
    ADD CONSTRAINT expense_items_expense_id_foreign FOREIGN KEY (expense_id) REFERENCES public.expenses(id) ON DELETE CASCADE;


--
-- Name: expenses expenses_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id);


--
-- Name: expenses expenses_reviewed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_reviewed_by_foreign FOREIGN KEY (reviewed_by) REFERENCES public.users(id);


--
-- Name: expenses expenses_submitted_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_submitted_by_foreign FOREIGN KEY (submitted_by) REFERENCES public.people(id);


--
-- Name: model_has_permissions model_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: model_has_roles model_has_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: people people_account_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.people
    ADD CONSTRAINT people_account_type_id_foreign FOREIGN KEY (account_type_id) REFERENCES public.account_types(id) ON DELETE SET NULL;


--
-- Name: people people_bank_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.people
    ADD CONSTRAINT people_bank_id_foreign FOREIGN KEY (bank_id) REFERENCES public.banks(id) ON DELETE SET NULL;


--
-- Name: person_bank_accounts person_bank_accounts_account_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.person_bank_accounts
    ADD CONSTRAINT person_bank_accounts_account_type_id_foreign FOREIGN KEY (account_type_id) REFERENCES public.account_types(id) ON DELETE SET NULL;


--
-- Name: person_bank_accounts person_bank_accounts_bank_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.person_bank_accounts
    ADD CONSTRAINT person_bank_accounts_bank_id_foreign FOREIGN KEY (bank_id) REFERENCES public.banks(id) ON DELETE SET NULL;


--
-- Name: person_bank_accounts person_bank_accounts_person_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.person_bank_accounts
    ADD CONSTRAINT person_bank_accounts_person_id_foreign FOREIGN KEY (person_id) REFERENCES public.people(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: transactions transactions_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id);


--
-- Name: transactions transactions_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: transactions transactions_from_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_from_account_id_foreign FOREIGN KEY (from_account_id) REFERENCES public.accounts(id);


--
-- Name: transactions transactions_to_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_to_account_id_foreign FOREIGN KEY (to_account_id) REFERENCES public.accounts(id);


--
-- Name: users users_person_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: admin
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_person_id_foreign FOREIGN KEY (person_id) REFERENCES public.people(id);


--
-- PostgreSQL database dump complete
--

\unrestrict ywi7cGekjKhUkUgJS0LpNEJCArE7kcXmsf7ylrQFbU8C3spUkYeO8xplzOzHus0


CREATE TABLE public.usuarios
(
    codigo serial NOT NULL,
    nome_completo character varying,
    email character varying,
    ativo boolean NOT NULL DEFAULT true,
    ip_cadastro character varying,
    google_id character varying,
    saldo numeric,
    momento_cadastro timestamp with time zone,
    PRIMARY KEY (codigo)
);

CREATE TABLE public.consultas_creci
(
    codigo serial NOT NULL,
    creci character varying NOT NULL,
    usuario_codigo integer NOT NULL,
    data_cadastro timestamp with time zone,
    data_finalizacao timestamp with time zone,
    situacao character varying,
    mensagem_erro character varying,
    mensagem_sucesso character varying,
    PRIMARY KEY (codigo)
);

CREATE TABLE IF NOT EXISTS creci
(
    id serial NOT NULL,
    creci_id character varying NOT NULL,
    creci_completo character varying,
    creci_estado character varying,
    nome_completo character varying,
    nome_fantasia character varying,
    atualizado_em timestamp with time zone NOT NULL DEFAULT now(),
    situacao character varying,
    estado character varying,
    usuario_codigo integer,
    cidade character varying,
    numero_documento character varying,
    PRIMARY KEY (id),
    momento timestamp with time zone NOT NULL DEFAULT now()
);

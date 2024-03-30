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
    cidade character varying,
    numero_documento character varying,
    PRIMARY KEY (id),
    momento timestamp with time zone NOT NULL DEFAULT now()
);
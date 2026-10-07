-- 1) Novas colunas na tabela usuario
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS is_admin BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS ativo BOOLEAN NOT NULL DEFAULT TRUE;
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS data_cadastro TIMESTAMPTZ NOT NULL DEFAULT NOW();

-- 2) Histórico de logins (sucesso e falha)
CREATE TABLE IF NOT EXISTS login_log (
    id_login      SERIAL PRIMARY KEY,
    id_usuario    INT REFERENCES usuario(id_usuario) ON DELETE SET NULL,
    email_tentado VARCHAR(150),
    sucesso       BOOLEAN NOT NULL,
    motivo        VARCHAR(50),
    dispositivo   VARCHAR(100),
    data_hora     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 3) Solicitações dos usuários
CREATE TABLE IF NOT EXISTS solicitacao (
    id_solicitacao SERIAL PRIMARY KEY,
    id_usuario     INT NOT NULL REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    pedido         VARCHAR(200) NOT NULL,
    situacao       VARCHAR(20) NOT NULL DEFAULT 'pendente',
    data_hora      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

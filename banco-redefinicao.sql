CREATE TABLE IF NOT EXISTS redefinicoes_senha (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT UNSIGNED NOT NULL,
  token_hash  CHAR(64) NOT NULL,
  expira_em   DATETIME NOT NULL,
  usado       TINYINT(1) NOT NULL DEFAULT 0,
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_token (token_hash),
  KEY idx_usuario (usuario_id, criado_em)
  -- Se usuarios.id for INT UNSIGNED, descomente:
  -- , FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

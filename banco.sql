-- =====================================================
-- banco.sql — Estrutura do banco de dados Meu Pedaço
-- Execute este arquivo no seu MySQL/MariaDB:
--   mysql -u root -p < banco.sql
-- =====================================================

CREATE DATABASE IF NOT EXISTS meupedaco
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE meupedaco;

-- ─── USUÁRIOS ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome       VARCHAR(100)        NOT NULL,
  email      VARCHAR(150)        NOT NULL UNIQUE,
  senha      VARCHAR(255)        NOT NULL,
  criado_em  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── POSTS ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS posts (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT UNSIGNED        NOT NULL,
  texto       TEXT                NOT NULL,
  bairro      VARCHAR(100)        NOT NULL DEFAULT 'Santo André',
  imagem_url  VARCHAR(500)        NULL,
  criado_em   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CURTIDAS ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS curtidas (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT UNSIGNED  NOT NULL,
  post_id     INT UNSIGNED  NOT NULL,
  criado_em   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unica_curtida (usuario_id, post_id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (post_id)    REFERENCES posts(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── COMENTÁRIOS ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS comentarios (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id     INT UNSIGNED  NOT NULL,
  usuario_id  INT UNSIGNED  NOT NULL,
  texto       TEXT          NOT NULL,
  criado_em   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (post_id)    REFERENCES posts(id)    ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── PONTOS DO MAPA ──────────────────────────────────
CREATE TABLE IF NOT EXISTS pontos_mapa (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT UNSIGNED   NOT NULL,
  nome        VARCHAR(150)   NOT NULL,
  descricao   TEXT           NULL,
  latitude    DECIMAL(10,7)  NOT NULL,
  longitude   DECIMAL(10,7)  NOT NULL,
  categoria   VARCHAR(80)    NOT NULL DEFAULT 'Cultura',
  criado_em   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── DADOS INICIAIS (pontos do mapa já existentes no home.js) ──
INSERT IGNORE INTO usuarios (id, nome, email, senha) VALUES
  (1, 'Admin', 'admin@meupedaco.com.br', '$2y$10$placeholder_troque_a_senha');

INSERT IGNORE INTO pontos_mapa (usuario_id, nome, descricao, latitude, longitude, categoria) VALUES
  (1, 'Praça IV Centenário',  'Coração do centro de Santo André. Ponto de encontro histórico.',         -23.6638, -46.5322, 'Praça'),
  (1, 'Mercado Municipal',    'Famoso mercadão com produtos frescos e muita história. Desde 1960.',      -23.6700, -46.5280, 'Comércio'),
  (1, 'Parque Celso Daniel',  'Grande área verde e de lazer. Ideal para caminhadas e piqueniques.',      -23.6590, -46.5400, 'Parque'),
  (1, 'Vila Luzita',          'Bairro tradicional com muita história e cultura local.',                  -23.6750, -46.5350, 'Bairro'),
  (1, 'Jardim Cristiane',     'Bairro residencial com forte senso de comunidade.',                       -23.6500, -46.5200, 'Bairro');

CREATE TABLE IF NOT EXISTS usuarios (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(120) NOT NULL,
  login VARCHAR(60) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  perfil ENUM('Administrador','Gerente','Atendente','Sapateiro') NOT NULL DEFAULT 'Administrador',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  token_recuperacao VARCHAR(255) NULL,
  token_expira_em DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_usuarios_login (login),
  KEY idx_usuarios_token (token_recuperacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clientes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(160) NOT NULL,
  cpf VARCHAR(14) NULL,
  telefone VARCHAR(30) NOT NULL,
  email VARCHAR(120) NULL,
  endereco TEXT NULL,
  observacoes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_clientes_telefone (telefone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auditoria (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id BIGINT UNSIGNED NULL,
  acao VARCHAR(120) NOT NULL,
  tabela VARCHAR(80) NULL,
  registro_id BIGINT UNSIGNED NULL,
  dados_antes JSON NULL,
  dados_depois JSON NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_auditoria_usuario (usuario_id),
  KEY idx_auditoria_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_tentativas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  login VARCHAR(60) NOT NULL,
  ip VARCHAR(45) NULL,
  attempts INT NOT NULL DEFAULT 0,
  first_attempt_at DATETIME NOT NULL,
  last_attempt_at DATETIME NOT NULL,
  locked_until DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_login_tentativas_login (login),
  KEY idx_login_tentativas_locked_until (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS configuracoes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  chave VARCHAR(80) NOT NULL,
  valor TEXT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_config_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS empresa (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(160) NOT NULL,
  cnpj VARCHAR(30) NULL,
  endereco TEXT NULL,
  telefone VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  logo_path VARCHAR(255) NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sequenciais (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo ENUM('orcamento','ordem_servico','recibo') NOT NULL,
  ano INT NOT NULL,
  ultimo_numero INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uk_sequenciais_tipo_ano (tipo, ano)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orcamentos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  numero VARCHAR(20) NOT NULL,
  cliente_id BIGINT UNSIGNED NOT NULL,
  valor_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  desconto DECIMAL(12,2) NOT NULL DEFAULT 0,
  valor_final DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('Aguardando','Aprovado','Reprovado','Expirado','Convertido') NOT NULL DEFAULT 'Aguardando',
  validade DATE NULL,
  observacoes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_orcamentos_numero (numero),
  KEY idx_orcamentos_cliente (cliente_id),
  KEY idx_orcamentos_status (status),
  CONSTRAINT fk_orcamentos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_orcamentos_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ordens_servico (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  numero VARCHAR(20) NOT NULL,
  orcamento_id BIGINT UNSIGNED NULL,
  cliente_id BIGINT UNSIGNED NOT NULL,
  sapateiro_id BIGINT UNSIGNED NULL,
  data_entrada DATE NOT NULL,
  prazo_entrega DATE NOT NULL,
  data_conclusao DATE NULL,
  valor_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('Recebido','Em reparo','Aguardando retirada','Entregue','Cancelado','Atrasado') NOT NULL DEFAULT 'Recebido',
  localizacao VARCHAR(100) NULL,
  observacoes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_os_numero (numero),
  KEY idx_os_cliente (cliente_id),
  KEY idx_os_status (status),
  KEY idx_os_prazo (prazo_entrega),
  KEY idx_os_sapateiro (sapateiro_id),
  CONSTRAINT fk_os_orcamento FOREIGN KEY (orcamento_id) REFERENCES orcamentos(id) ON DELETE SET NULL,
  CONSTRAINT fk_os_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_os_sapateiro FOREIGN KEY (sapateiro_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_os_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sapatos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  orcamento_id BIGINT UNSIGNED NULL,
  os_id BIGINT UNSIGNED NULL,
  categoria VARCHAR(60) NOT NULL,
  cor VARCHAR(60) NULL,
  modelo VARCHAR(120) NULL,
  tipo_servico VARCHAR(120) NOT NULL,
  marca VARCHAR(60) NULL,
  valor DECIMAL(12,2) NOT NULL DEFAULT 0,
  material VARCHAR(120) NULL,
  observacoes TEXT NULL,
  fotos JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sapatos_orcamento (orcamento_id),
  KEY idx_sapatos_os (os_id),
  CONSTRAINT fk_sapatos_orcamento FOREIGN KEY (orcamento_id) REFERENCES orcamentos(id) ON DELETE CASCADE,
  CONSTRAINT fk_sapatos_os FOREIGN KEY (os_id) REFERENCES ordens_servico(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS links_publicos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  token VARCHAR(64) NOT NULL,
  tipo ENUM('orcamento','ordem_servico','recibo') NOT NULL,
  referencia_id BIGINT UNSIGNED NOT NULL,
  data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  data_expiracao DATETIME NOT NULL,
  acessos INT NOT NULL DEFAULT 0,
  ultimo_acesso DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_links_token (token),
  KEY idx_links_tipo_ref (tipo, referencia_id),
  KEY idx_links_expiracao (data_expiracao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pagamentos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  os_id BIGINT UNSIGNED NOT NULL,
  caixa_id BIGINT UNSIGNED NULL,
  parcela_numero INT NOT NULL DEFAULT 1,
  valor DECIMAL(12,2) NOT NULL,
  vencimento DATE NULL,
  data_pagamento DATE NULL,
  forma_pagamento ENUM('Dinheiro','PIX','Cartão Débito','Cartão Crédito') NULL,
  status ENUM('Pendente','Pago','Atrasado') NOT NULL DEFAULT 'Pendente',
  observacoes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_pagamentos_os (os_id),
  KEY idx_pagamentos_caixa (caixa_id),
  KEY idx_pagamentos_status (status),
  CONSTRAINT fk_pagamentos_os FOREIGN KEY (os_id) REFERENCES ordens_servico(id) ON DELETE CASCADE,
  CONSTRAINT fk_pagamentos_caixa FOREIGN KEY (caixa_id) REFERENCES caixa(id) ON DELETE SET NULL,
  CONSTRAINT fk_pagamentos_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS despesas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  descricao VARCHAR(255) NOT NULL,
  categoria ENUM('Materiais','Aluguel','Energia','Água','Telefone/Internet','Salários','Impostos','Manutenção','Outras') NOT NULL,
  valor DECIMAL(12,2) NOT NULL,
  vencimento DATE NULL,
  data_pagamento DATE NULL,
  forma_pagamento ENUM('Dinheiro','PIX','Cartão Débito','Cartão Crédito','Boleto','Transferência') NULL,
  recorrente TINYINT(1) NOT NULL DEFAULT 0,
  periodicidade VARCHAR(20) NULL,
  comprovante_path VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_despesas_categoria (categoria),
  KEY idx_despesas_data (created_at),
  CONSTRAINT fk_despesas_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS caixa (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  data DATE NOT NULL,
  saldo_inicial DECIMAL(12,2) NOT NULL DEFAULT 0,
  receitas DECIMAL(12,2) NOT NULL DEFAULT 0,
  despesas DECIMAL(12,2) NOT NULL DEFAULT 0,
  retiradas DECIMAL(12,2) NOT NULL DEFAULT 0,
  saldo_esperado DECIMAL(12,2) NOT NULL DEFAULT 0,
  saldo_real DECIMAL(12,2) NULL,
  diferenca DECIMAL(12,2) NULL,
  observacoes TEXT NULL,
  responsavel_abertura BIGINT UNSIGNED NULL,
  responsavel_fechamento BIGINT UNSIGNED NULL,
  data_abertura DATETIME NULL,
  data_fechamento DATETIME NULL,
  status ENUM('Aberto','Fechado') NOT NULL DEFAULT 'Aberto',
  PRIMARY KEY (id),
  KEY idx_caixa_data (data),
  CONSTRAINT fk_caixa_abertura FOREIGN KEY (responsavel_abertura) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_caixa_fechamento FOREIGN KEY (responsavel_fechamento) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS caixa_movimentacoes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  caixa_id BIGINT UNSIGNED NOT NULL,
  tipo ENUM('Abertura','Retirada','Fechamento','Ajuste') NOT NULL,
  valor DECIMAL(12,2) NULL,
  motivo VARCHAR(255) NULL,
  meta_json TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_caixa_mov_caixa (caixa_id),
  KEY idx_caixa_mov_created_at (created_at),
  CONSTRAINT fk_caixa_mov_caixa FOREIGN KEY (caixa_id) REFERENCES caixa(id) ON DELETE CASCADE,
  CONSTRAINT fk_caixa_mov_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS recibos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  numero VARCHAR(20) NOT NULL,
  os_id BIGINT UNSIGNED NOT NULL,
  cliente_id BIGINT UNSIGNED NOT NULL,
  valor_total DECIMAL(12,2) NOT NULL,
  forma_pagamento VARCHAR(50) NULL,
  garantia_dias INT NOT NULL DEFAULT 30,
  termos TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_recibos_numero (numero),
  KEY idx_recibos_os (os_id),
  CONSTRAINT fk_recibos_os FOREIGN KEY (os_id) REFERENCES ordens_servico(id) ON DELETE RESTRICT,
  CONSTRAINT fk_recibos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_recibos_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS empresa (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(160) NOT NULL DEFAULT 'Minha Sapataria',
  cnpj VARCHAR(18) NULL,
  endereco TEXT NULL,
  telefone VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  logo_url VARCHAR(255) NULL,
  primary_color VARCHAR(7) NULL DEFAULT '#008bcd',
  termos_recibo TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserir dados padrão da empresa (serão inseridos pela aplicação)

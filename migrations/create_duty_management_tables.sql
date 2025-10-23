-- Migration: Criar tabelas para gerenciamento de oficiais de serviço
-- Banco de dados: marinha_papem
-- Data: 2025-10-23
--
-- IMPORTANTE: Este script deve ser executado no banco de dados 'marinha_papem'
-- Comando: psql -U postgres -d marinha_papem -f migrations/create_duty_management_tables.sql

-- ============================================================================
-- Tabela: duty_assignments
-- Descrição: Armazena as atribuições de oficiais de serviço
-- ============================================================================

CREATE TABLE IF NOT EXISTS duty_assignments (
    id SERIAL PRIMARY KEY,
    officer_name VARCHAR(255),
    officer_rank VARCHAR(100),
    master_name VARCHAR(255),
    master_rank VARCHAR(100),
    valid_from TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT at_least_one_officer CHECK (
        officer_name IS NOT NULL OR master_name IS NOT NULL
    )
);

-- Índices para melhorar performance
CREATE INDEX IF NOT EXISTS idx_duty_assignments_valid_from ON duty_assignments(valid_from DESC);
CREATE INDEX IF NOT EXISTS idx_duty_assignments_updated_at ON duty_assignments(updated_at DESC);

-- Comentários
COMMENT ON TABLE duty_assignments IS 'Armazena as atribuições de oficiais de serviço (Oficial de Serviço e Contramestre)';
COMMENT ON COLUMN duty_assignments.officer_name IS 'Nome do oficial de serviço';
COMMENT ON COLUMN duty_assignments.officer_rank IS 'Posto/graduação do oficial de serviço';
COMMENT ON COLUMN duty_assignments.master_name IS 'Nome do contramestre';
COMMENT ON COLUMN duty_assignments.master_rank IS 'Posto/graduação do contramestre';
COMMENT ON COLUMN duty_assignments.valid_from IS 'Data/hora de início da validade';
COMMENT ON COLUMN duty_assignments.updated_at IS 'Data/hora da última atualização';

-- ============================================================================
-- Tabela: military_personnel
-- Descrição: Armazena dados de militares disponíveis para seleção
-- ============================================================================

CREATE TABLE IF NOT EXISTS military_personnel (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    rank VARCHAR(100),
    type VARCHAR(50) NOT NULL,
    specialty VARCHAR(100),
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT valid_type CHECK (type IN ('officer', 'master'))
);

-- Índices para melhorar performance
CREATE INDEX IF NOT EXISTS idx_military_personnel_type ON military_personnel(type);
CREATE INDEX IF NOT EXISTS idx_military_personnel_name ON military_personnel(name);
CREATE INDEX IF NOT EXISTS idx_military_personnel_status ON military_personnel(status);

-- Comentários
COMMENT ON TABLE military_personnel IS 'Cadastro de militares disponíveis para atribuição de serviço';
COMMENT ON COLUMN military_personnel.name IS 'Nome completo do militar';
COMMENT ON COLUMN military_personnel.rank IS 'Posto/graduação do militar';
COMMENT ON COLUMN military_personnel.type IS 'Tipo de militar: officer (oficial) ou master (praça/contramestre)';
COMMENT ON COLUMN military_personnel.specialty IS 'Especialidade do militar (ex: IM, T, AA, etc)';
COMMENT ON COLUMN military_personnel.status IS 'Status do militar (active, inactive, etc)';

-- ============================================================================
-- Dados de exemplo (opcional - pode ser removido em produção)
-- ============================================================================

-- Inserir alguns oficiais de exemplo baseados nos dados existentes
INSERT INTO military_personnel (name, rank, type, specialty, status)
VALUES
    ('KLEBER', 'CF (IM)', 'officer', 'IM', 'active'),
    ('COSENDEY', 'CF (IM)', 'officer', 'IM', 'active'),
    ('PAULA BALLARD', 'CF (T)', 'officer', 'T', 'active'),
    ('ROGÉRIO R.', 'CC (T)', 'officer', 'T', 'active'),
    ('REJANE AMARAL', 'CC (T)', 'officer', 'T', 'active'),
    ('ELAINE A.', 'CC (T)', 'officer', 'T', 'active'),
    ('CAMILA', 'CC (IM)', 'officer', 'IM', 'active'),
    ('AZEVEDO', 'CC (IM)', 'officer', 'IM', 'active'),
    ('REGINA GRISI', 'CC (IM)', 'officer', 'IM', 'active'),
    ('WÍLLAM', 'CT (T)', 'officer', 'T', 'active')
ON CONFLICT DO NOTHING;

-- Inserir alguns praças de exemplo
INSERT INTO military_personnel (name, rank, type, specialty, status)
VALUES
    ('DUTRA LIMA', 'CT (IM)', 'master', 'IM', 'active'),
    ('THIAGO SILVA', 'CT (IM)', 'master', 'IM', 'active'),
    ('YAGO', 'CT (IM)', 'master', 'IM', 'active'),
    ('MATEUS BARBOSA', 'CC (T)', 'master', 'T', 'active'),
    ('NATHALIA D.', 'CT (QC-IM)', 'master', 'QC-IM', 'active')
ON CONFLICT DO NOTHING;

-- ============================================================================
-- Verificação final
-- ============================================================================

-- Mostrar tabelas criadas
SELECT
    table_name,
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = t.table_name) as column_count
FROM information_schema.tables t
WHERE table_schema = 'public'
    AND table_name IN ('duty_assignments', 'military_personnel')
ORDER BY table_name;

-- Mostrar contagem de registros
SELECT
    'duty_assignments' as table_name,
    COUNT(*) as record_count
FROM duty_assignments
UNION ALL
SELECT
    'military_personnel' as table_name,
    COUNT(*) as record_count
FROM military_personnel;

-- Mensagem final
SELECT 'Tabelas criadas com sucesso!' as status;

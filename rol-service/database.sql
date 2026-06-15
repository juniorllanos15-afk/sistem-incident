-- En PostgreSQL, la base de datos debe crearse primero,
-- CREATE DATABASE service_rol_db;

CREATE TABLE IF NOT EXISTS rol (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    state SMALLINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_rol_updated_at
BEFORE UPDATE ON rol
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

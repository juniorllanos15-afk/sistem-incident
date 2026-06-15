-- Nota: En PostgreSQL, la base de datos debe crearse primero,
-- por ejemplo desde la interfaz de pgAdmin, o usando SQL,
-- pero no dentro del mismo script con las tablas sin estar conectados a ella.

CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    status SMALLINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Para emular el "ON UPDATE CURRENT_TIMESTAMP" de MySQL,
-- PostgreSQL requiere usar una función y un trigger:

CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_categories_updated_at
BEFORE UPDATE ON categories
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

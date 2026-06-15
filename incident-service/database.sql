-- Nota: En PostgreSQL, la base de datos debe crearse primero,
-- por ejemplo desde la interfaz de pgAdmin, o usando SQL,
-- pero no dentro del mismo script con las tablas sin estar conectados a ella.

CREATE TABLE IF NOT EXISTS incident (
    id SERIAL PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    state SMALLINT DEFAULT 1,
    status SMALLINT DEFAULT 1,
    category_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    date_incident TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ubication VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
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

CREATE TRIGGER update_incident_updated_at
BEFORE UPDATE ON incident
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

CREATE TABLE IF NOT EXISTS incident_detail (
    id SERIAL PRIMARY KEY,
    incident_id INTEGER NOT NULL,
    description TEXT,
    state SMALLINT DEFAULT 1,
    status SMALLINT DEFAULT 1,
    user_id INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_incident FOREIGN KEY (incident_id) REFERENCES incident(id) ON DELETE CASCADE
);

CREATE TRIGGER update_incident_detail_updated_at
BEFORE UPDATE ON incident_detail
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

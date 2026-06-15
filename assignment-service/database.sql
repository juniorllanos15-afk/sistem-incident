-- assignment-service/database.sql
-- CREATE DATABASE service_assignment_db;

CREATE TABLE IF NOT EXISTS assignments (
    id SERIAL PRIMARY KEY,
    incident_id INT NOT NULL,
    assigned_by INT NOT NULL,
    assignment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    priority VARCHAR(50),
    state_assignments SMALLINT DEFAULT 1,
    status SMALLINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS assignments_detail (
    id SERIAL PRIMARY KEY,
    assignments_id INT REFERENCES assignments(id) ON DELETE CASCADE,
    incident_id INT NOT NULL,
    technician_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

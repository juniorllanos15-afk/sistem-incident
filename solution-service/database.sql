-- solution-service/database.sql
-- CREATE DATABASE service_solution_db;

CREATE TABLE IF NOT EXISTS solutions (
    id SERIAL PRIMARY KEY,
    incident_id INT NOT NULL,
    incident_detail_id INT NOT NULL,
    assignments_detail_id INT NOT NULL,
    solution TEXT NOT NULL,
    date_solutions TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

<?php
// assignment-service/models/Assignment.php

class Assignment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $sql = "SELECT a.*, i.title as incident_title 
                FROM assignments a 
                JOIN assignments i ON a.incident_id = i.id 
                WHERE a.status = 1 ORDER BY a.id DESC";
        // Nota: En un entorno de microservicios real, no haríamos JOIN con una tabla de otro servicio directamente si están en DB distintas.
        // Pero como estamos en un entorno simplificado, asumiremos que el frontend hará el "join" visual o que el API Gateway provee los datos.
        // Dado que incidentes están en service_incident_db y asignaciones en service_assignment_db, NO podemos hacer JOIN.
        
        $stmt = $this->pdo->query("SELECT * FROM assignments WHERE status = 1 ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function create($data) {
        try {
            $this->pdo->beginTransaction();

            $sql = "INSERT INTO assignments (incident_id, assigned_by, priority, state_assignments) 
                    VALUES (:incident_id, :assigned_by, :priority, :state_assignments)";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->execute([
                'incident_id' => $data['incident_id'],
                'assigned_by' => $data['assigned_by'],
                'priority' => $data['priority'],
                'state_assignments' => isset($data['state_assignments']) ? $data['state_assignments'] : 1
            ]);

            $assignmentId = $this->pdo->lastInsertId();

            if (isset($data['technicians']) && is_array($data['technicians'])) {
                $detailSql = "INSERT INTO assignments_detail (assignments_id, incident_id, technician_id) 
                             VALUES (:assignments_id, :incident_id, :technician_id)";
                $detailStmt = $this->pdo->prepare($detailSql);

                foreach ($data['technicians'] as $techId) {
                    $detailStmt->execute([
                        'assignments_id' => $assignmentId,
                        'incident_id' => $data['incident_id'],
                        'technician_id' => $techId
                    ]);
                }
            }

            $this->pdo->commit();
            return $assignmentId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error in Assignment::create: " . $e->getMessage());
            return false;
        }
    }

    public function getDetails($assignmentId) {
        $stmt = $this->pdo->prepare("SELECT * FROM assignments_detail WHERE assignments_id = :id");
        $stmt->execute(['id' => $assignmentId]);
        return $stmt->fetchAll();
    }

    public function getByTechnician($techId) {
        $stmt = $this->pdo->prepare("SELECT * FROM assignments_detail WHERE technician_id = :id ORDER BY id DESC");
        $stmt->execute(['id' => $techId]);
        return $stmt->fetchAll();
    }

    public function getByIncident($incidentId) {
        $stmt = $this->pdo->prepare("SELECT DISTINCT technician_id FROM assignments_detail WHERE incident_id = :id");
        $stmt->execute(['id' => $incidentId]);
        return $stmt->fetchAll();
    }
}
?>

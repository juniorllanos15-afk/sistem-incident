<?php
// solution-service/models/Solution.php

class Solution {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function save($data) {
        $sql = "INSERT INTO solutions (incident_id, incident_detail_id, assignments_detail_id, solution) 
                VALUES (:incident_id, :incident_detail_id, :assignments_detail_id, :solution)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'incident_id' => $data['incident_id'],
            'incident_detail_id' => $data['incident_detail_id'],
            'assignments_detail_id' => $data['assignments_detail_id'],
            'solution' => $data['solution']
        ]);
    }

    public function getSolutionsByIncident($incidentId) {
        $stmt = $this->pdo->prepare("SELECT * FROM solutions WHERE incident_id = :id");
        $stmt->execute(['id' => $incidentId]);
        return $stmt->fetchAll();
    }

    public function checkAlreadySolved($detailId) {
        $stmt = $this->pdo->prepare("SELECT 1 FROM solutions WHERE incident_detail_id = :id");
        $stmt->execute(['id' => $detailId]);
        return $stmt->fetch() ? true : false;
    }
}
?>

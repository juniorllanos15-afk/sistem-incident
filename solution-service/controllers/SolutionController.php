<?php
// solution-service/controllers/SolutionController.php

class SolutionController {
    private $model;

    public function __construct($model) {
        $this->model = $model;
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['solutions']) || !is_array($data['solutions'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Solutions array is required']);
            return;
        }

        $successCount = 0;
        foreach ($data['solutions'] as $sol) {
            // Verificar si ya existe solución para este detalle para no duplicar
            if ($this->model->checkAlreadySolved($sol['incident_detail_id'])) {
                continue;
            }

            if ($this->model->save($sol)) {
                $successCount++;
            }
        }

        echo json_encode([
            'success' => true, 
            'message' => "$successCount solutions recorded",
            'solved_details' => $this->model->getSolutionsByIncident($data['incident_id'])
        ]);
    }

    public function list() {
        $incidentId = isset($_GET['incident_id']) ? intval($_GET['incident_id']) : null;
        if ($incidentId) {
            $data = $this->model->getSolutionsByIncident($incidentId);
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Incident ID required']);
        }
    }
}
?>

<?php
// assignment-service/controllers/AssignmentController.php

class AssignmentController {
    private $model;

    public function __construct($model) {
        $this->model = $model;
    }

    public function index() {
        $data = $this->model->getAll();
        echo json_encode(['success' => true, 'data' => $data]);
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['incident_id']) || !isset($data['assigned_by'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Incomplete data']);
            return;
        }

        $id = $this->model->create($data);

        if ($id) {
            $technicianIds = isset($data['technicians']) && is_array($data['technicians'])
                ? array_map('intval', $data['technicians'])
                : [];

            $this->syncIncidentState($data['incident_id'], 2, $technicianIds);

            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Assignment created successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create assignment']);
        }
    }

    /**
     * Sincroniza el estado de la incidencia en el microservicio correspondiente
     */
    private function syncIncidentState($incidentId, $state, array $technicianIds = []) {
        $url = "http://localhost:8004/index.php?action=changeState";
        $payload = json_encode([
            'id' => $incidentId,
            'state' => $state,
            'technician_ids' => $technicianIds
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function getDetails() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        if ($id) {
            $details = $this->model->getDetails($id);
            echo json_encode(['success' => true, 'data' => $details]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Assignment ID required']);
        }
    }

    public function listByTechnician() {
        $id = isset($_GET['technician_id']) ? intval($_GET['technician_id']) : null;
        if ($id) {
            $data = $this->model->getByTechnician($id);
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Technician ID required']);
        }
    }

    public function listByIncident() {
        $id = isset($_GET['incident_id']) ? intval($_GET['incident_id']) : null;
        if ($id) {
            $data = $this->model->getByIncident($id);
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Incident ID required']);
        }
    }
}
?>

<?php
// controllers/IncidentController.php

class IncidentController
{
    private $incidentRepo;

    public function __construct(IncidentRepositoryInterface $incidentRepo)
    {
        $this->incidentRepo = $incidentRepo;
    }

    public function index()
    {
        $incidents = $this->incidentRepo->getAllActive();
        echo json_encode(['success' => true, 'data' => $incidents]);
    }

    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['name']) && !empty(trim($data['name']))) {
            $preparedData = [
                'title' => trim($data['name']),
                'description' => isset($data['description']) ? trim($data['description']) : '',
                'date_incident' => !empty($data['date_incident']) ? $data['date_incident'] : date('Y-m-d H:i:s'),
                'latitude' => !empty($data['latitude']) ? $data['latitude'] : null,
                'longitude' => !empty($data['longitude']) ? $data['longitude'] : null,
                'ubication' => isset($data['ubication']) ? trim($data['ubication']) : '',
                'state' => isset($data['state']) ? intval($data['state']) : 1,
                'user_id' => isset($data['user_id']) ? intval($data['user_id']) : 1,
                'category_id' => isset($data['category_id']) && !empty($data['category_id']) ? intval($data['category_id']) : 1,
                'details' => isset($data['details']) ? $data['details'] : []
            ];

            $insertedId = $this->incidentRepo->create($preparedData);

            if ($insertedId) {
                echo json_encode(['success' => true, 'id' => $insertedId, 'message' => 'Incident created successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create incident']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Incident title is required']);
        }
    }

    public function update()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['id']) && isset($data['name']) && !empty(trim($data['name']))) {
            $id = intval($data['id']);

            $preparedData = [
                'title' => trim($data['name']),
                'description' => isset($data['description']) ? trim($data['description']) : '',
                'date_incident' => !empty($data['date_incident']) ? $data['date_incident'] : date('Y-m-d H:i:s'),
                'latitude' => !empty($data['latitude']) ? $data['latitude'] : null,
                'longitude' => !empty($data['longitude']) ? $data['longitude'] : null,
                'ubication' => isset($data['ubication']) ? trim($data['ubication']) : '',
                'state' => isset($data['state']) ? intval($data['state']) : 1,
                'category_id' => isset($data['category_id']) && !empty($data['category_id']) ? intval($data['category_id']) : 1,
                'details' => isset($data['details']) ? $data['details'] : []
            ];

            $success = $this->incidentRepo->update($id, $preparedData);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Incident updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update incident']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Incident ID and title are required']);
        }
    }

    public function getDetails()
    {
        $id = isset($_GET['incident_id']) ? intval($_GET['incident_id']) : null;
        if ($id) {
            $details = $this->incidentRepo->getDetailsByIncident($id);
            echo json_encode(['success' => true, 'data' => $details]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'ID de incidencia requerido']);
        }
    }

    public function changeState()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['id']) && isset($data['state'])) {
            $id = intval($data['id']);
            $state = intval($data['state']);

            $success = $this->incidentRepo->updateState($id, $state);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update status']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'ID and state are required']);
        }
    }

    public function destroy()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['id'])) {
            $id = intval($data['id']);

            $success = $this->incidentRepo->disable($id);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Incident disabled successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to disable incident']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Incident ID is required']);
        }
    }
}
?>
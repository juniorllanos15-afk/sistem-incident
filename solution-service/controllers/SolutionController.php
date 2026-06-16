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

        $incidentId = isset($data['incident_id']) ? intval($data['incident_id']) : null;
        if (!$incidentId && !empty($data['solutions']) && isset($data['solutions'][0]['incident_id'])) {
            $incidentId = intval($data['solutions'][0]['incident_id']);
        }

        if (!$incidentId) {
            http_response_code(400);
            echo json_encode(['error' => 'Incident ID is required']);
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

        // Verificar si todos los detalles de la incidencia tienen solución
        $detailsUrl = "http://localhost:8004/index.php?action=details&incident_id=" . $incidentId;
        $response = @file_get_contents($detailsUrl);
        if ($response !== false) {
            $resData = json_decode($response, true);
            if ($resData && $resData['success'] && is_array($resData['data'])) {
                $incidentDetails = $resData['data'];
                if (!empty($incidentDetails)) {
                    $savedSolutions = $this->model->getSolutionsByIncident($incidentId);
                    $solvedDetailIds = array_column($savedSolutions, 'incident_detail_id');

                    $allSolved = true;
                    foreach ($incidentDetails as $detail) {
                        if (!in_array($detail['id'], $solvedDetailIds)) {
                            $allSolved = false;
                            break;
                        }
                    }

                    if ($allSolved) {
                        // Cambiar el estado de la incidencia a "Finalizado" (3)
                        $this->syncIncidentState($incidentId, 3);
                    }
                }
            }
        }

        echo json_encode([
            'success' => true, 
            'message' => "$successCount solutions recorded",
            'solved_details' => $this->model->getSolutionsByIncident($incidentId)
        ]);
    }

    private function syncIncidentState($incidentId, $state) {
        $url = "http://localhost:8004/index.php?action=changeState";
        $payload = json_encode(['id' => $incidentId, 'state' => $state]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
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

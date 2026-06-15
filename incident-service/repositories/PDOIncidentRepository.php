<?php
// repositories/PDOIncidentRepository.php
require_once 'IncidentRepositoryInterface.php';

class PDOIncidentRepository implements IncidentRepositoryInterface
{
    private $pdo;
    private $observers = [];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // --- Implementación de los métodos de Observer ---
    public function attach(IncidentObserverInterface $observer)
    {
        $this->observers[] = $observer;
    }

    public function detach(IncidentObserverInterface $observer)
    {
        $this->observers = array_filter($this->observers, function ($obs) use ($observer) {
            return $obs !== $observer;
        });
    }

    public function notify(string $event, array $data)
    {
        foreach ($this->observers as $observer) {
            $observer->update($event, $data);
        }
    }

    public function getAllActive()
    {
        $stmt = $this->pdo->query("SELECT id, title, description, status, state, date_incident, latitude, longitude, ubication, category_id, user_id, created_at FROM incident WHERE status = 1 ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function create(array $data)
    {
        try {
            $this->pdo->beginTransaction();

            // Separar detalles de la data principal
            $details = isset($data['details']) ? $data['details'] : [];
            $mainData = $data;
            unset($mainData['details']);

            $sql = "INSERT INTO incident (title, description, date_incident, latitude, longitude, ubication, state, user_id, category_id) 
                    VALUES (:title, :description, :date_incident, :latitude, :longitude, :ubication, :state, :user_id, :category_id)";

            $stmt = $this->pdo->prepare($sql);
            if (!$stmt->execute($mainData)) {
                throw new Exception("Error al insertar la incidencia principal");
            }

            $incidentId = $this->pdo->lastInsertId();

            // Insertar detalles si existen
            if (!empty($details)) {
                $detailSql = "INSERT INTO incident_detail (incident_id, description, user_id) 
                              VALUES (:incident_id, :description, :user_id)";
                $detailStmt = $this->pdo->prepare($detailSql);

                foreach ($details as $detail) {
                    $detailStmt->execute([
                        'incident_id' => $incidentId,
                        'description' => $detail['description'],
                        'user_id' => $data['user_id']
                    ]);
                }
            }

            $this->pdo->commit();

            // Notificar a los observadores
            $data['id'] = $incidentId;
            $this->notify('incident.created', $data);

            return $incidentId;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error in PDOIncidentRepository::create: " . $e->getMessage());
            return false;
        }
    }

    public function getDetailsByIncident($incidentId)
    {
        $stmt = $this->pdo->prepare("SELECT id, description, user_id, created_at FROM incident_detail WHERE incident_id = :id ORDER BY id ASC");
        $stmt->execute(['id' => $incidentId]);
        return $stmt->fetchAll();
    }

    public function update($id, array $data)
    {
        try {
            $this->pdo->beginTransaction();

            // Separar detalles de la data principal
            $details = isset($data['details']) ? $data['details'] : [];
            $mainData = $data;
            unset($mainData['details']);

            $sql = "UPDATE incident SET title = :title, description = :description, date_incident = :date_incident, 
                    latitude = :latitude, longitude = :longitude, ubication = :ubication, state = :state, category_id = :category_id 
                    WHERE id = :id";
            $mainData['id'] = $id;
            $stmt = $this->pdo->prepare($sql);

            if (!$stmt->execute($mainData)) {
                throw new Exception("Error al actualizar la incidencia principal");
            }

            // Sincronizar detalles: Borrar antiguos para esta incidencia
            $deleteSql = "DELETE FROM incident_detail WHERE incident_id = :id";
            $this->pdo->prepare($deleteSql)->execute(['id' => $id]);

            // Insertar la nueva lista de detalles
            if (!empty($details)) {
                $detailSql = "INSERT INTO incident_detail (incident_id, description, user_id) 
                              VALUES (:incident_id, :description, :user_id)";
                $detailStmt = $this->pdo->prepare($detailSql);

                foreach ($details as $detail) {
                    $detailStmt->execute([
                        'incident_id' => $id,
                        'description' => $detail['description'],
                        'user_id' => isset($data['user_id']) ? $data['user_id'] : 1
                    ]);
                }
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error in PDOIncidentRepository::update: " . $e->getMessage());
            return false;
        }
    }

    public function updateState($id, $state)
    {
        $stmt = $this->pdo->prepare("SELECT user_id FROM incident WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $incident = $stmt->fetch();

        $stmt = $this->pdo->prepare("UPDATE incident SET state = :state WHERE id = :id");
        $success = $stmt->execute(['state' => $state, 'id' => $id]);
        if ($success) {
            $this->notify('incident.state_changed', [
                'id' => $id,
                'state' => $state,
                'user_id' => $incident ? $incident['user_id'] : null
            ]);
        }
        return $success;
    }

    public function disable($id)
    {
        // Soft delete by setting status to 0
        $stmt = $this->pdo->prepare("UPDATE incident SET status = 0 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>

<?php
// repositories/IncidentRepositoryInterface.php
require_once __DIR__ . '/../observer/IncidentObserverInterface.php';

interface IncidentRepositoryInterface
{
    public function getAllActive();
    public function create(array $data);
    public function getDetailsByIncident($incidentId);
    public function update($id, array $data);
    public function updateState($id, $state);
    public function disable($id);

    // Métodos del patrón Observer (Sujeto)
    public function attach(IncidentObserverInterface $observer);
    public function detach(IncidentObserverInterface $observer);
    public function notify(string $event, array $data);
}
?>

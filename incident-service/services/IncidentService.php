<?php

require_once __DIR__ . '/../observer/EventManager.php';

class IncidentService
{
    private IncidentRepositoryInterface $repo;
    private EventManager $eventManager;

    public function __construct(IncidentRepositoryInterface $repo, EventManager $eventManager)
    {
        $this->repo = $repo;
        $this->eventManager = $eventManager;
    }

    public function getEventManager(): EventManager
    {
        return $this->eventManager;
    }

    public function getAllActive()
    {
        return $this->repo->getAllActive();
    }

    public function findById($id)
    {
        return $this->repo->findById($id);
    }

    public function create(array $data)
    {
        $id = $this->repo->create($data);
        if ($id) {
            $data['id'] = $id;
            $this->eventManager->dispatch('incident.created', $data);
        }
        return $id;
    }

    public function update($id, array $data)
    {
        $success = $this->repo->update($id, $data);
        if ($success) {
            $data['id'] = $id;
            $this->eventManager->dispatch('incident.updated', $data);
        }
        return $success;
    }

    public function changeState($id, $state, array $technicianIds = [])
    {
        $incident = $this->repo->findById($id);
        $success = $this->repo->updateState($id, $state, $technicianIds);
        if ($success) {
            $this->eventManager->dispatch('incident.state_changed', [
                'id' => $id,
                'state' => $state,
                'user_id' => $incident ? $incident['user_id'] : null,
                'technician_ids' => $technicianIds
            ]);
        }
        return $success;
    }

    public function getDetailsByIncident($incidentId)
    {
        return $this->repo->getDetailsByIncident($incidentId);
    }

    public function disable($id)
    {
        return $this->repo->disable($id);
    }
}

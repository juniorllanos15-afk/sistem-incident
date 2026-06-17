<?php

interface IncidentRepositoryInterface
{
    public function getAllActive();
    public function findById($id);
    public function create(array $data);
    public function getDetailsByIncident($incidentId);
    public function update($id, array $data);
    public function updateState($id, $state, array $technicianIds = []);
    public function disable($id);
}

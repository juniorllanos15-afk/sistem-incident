<?php
// repositories/MemoryIncidentRepository.php
require_once 'IncidentRepositoryInterface.php';

class MemoryIncidentRepository implements IncidentRepositoryInterface
{
    private $incidents = [];
    private $observers = [];

    public function __construct()
    {
        // Datos de prueba iniciales
        $this->incidents = [
            1 => [
                'id' => 1,
                'title' => 'Corte de Fibra Óptica',
                'description' => 'Corte de cable de fibra principal en la avenida central.',
                'status' => 1,
                'state' => 1,
                'date_incident' => date('Y-m-d H:i:s'),
                'latitude' => -16.500000,
                'longitude' => -68.150000,
                'ubication' => 'Av. Central Nro 456',
                'category_id' => 2,
                'user_id' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'details' => [
                    [
                        'id' => 1,
                        'description' => 'Equipo de técnicos despachado al lugar.',
                        'user_id' => 1,
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                ]
            ],
            2 => [
                'id' => 2,
                'title' => 'Caída de Servidor DNS',
                'description' => 'El servidor DNS secundario no responde a las solicitudes.',
                'status' => 1,
                'state' => 2,
                'date_incident' => date('Y-m-d H:i:s'),
                'latitude' => -16.510000,
                'longitude' => -68.160000,
                'ubication' => 'Data Center Zona Sur',
                'category_id' => 1,
                'user_id' => 2,
                'created_at' => date('Y-m-d H:i:s'),
                'details' => []
            ]
        ];
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
        // Retornar solo los que tienen status = 1 (activos)
        $active = [];
        foreach ($this->incidents as $incident) {
            if ($incident['status'] === 1) {
                $active[] = $incident;
            }
        }
        return $active;
    }

    public function create(array $data)
    {
        $id = count($this->incidents) + 1;
        
        $newIncident = [
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => 1,
            'state' => $data['state'],
            'date_incident' => $data['date_incident'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'ubication' => $data['ubication'],
            'category_id' => $data['category_id'],
            'user_id' => $data['user_id'],
            'created_at' => date('Y-m-d H:i:s'),
            'details' => []
        ];

        // Mapear detalles si vienen en la data
        if (!empty($data['details'])) {
            foreach ($data['details'] as $detailIndex => $detail) {
                $newIncident['details'][] = [
                    'id' => $detailIndex + 1,
                    'description' => $detail['description'],
                    'user_id' => $data['user_id'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
        }

        $this->incidents[$id] = $newIncident;

        // Notificar a los observadores
        $this->notify('incident.created', $newIncident);

        return $id;
    }

    public function getDetailsByIncident($incidentId)
    {
        return $this->incidents[$incidentId]['details'] ?? [];
    }

    public function update($id, array $data)
    {
        if (!isset($this->incidents[$id])) {
            return false;
        }

        // Actualizar datos de la incidencia principal
        $this->incidents[$id]['title'] = $data['title'];
        $this->incidents[$id]['description'] = $data['description'];
        $this->incidents[$id]['date_incident'] = $data['date_incident'];
        $this->incidents[$id]['latitude'] = $data['latitude'];
        $this->incidents[$id]['longitude'] = $data['longitude'];
        $this->incidents[$id]['ubication'] = $data['ubication'];
        $this->incidents[$id]['state'] = $data['state'];
        $this->incidents[$id]['category_id'] = $data['category_id'];

        // Sincronizar detalles
        if (isset($data['details'])) {
            $this->incidents[$id]['details'] = [];
            foreach ($data['details'] as $detailIndex => $detail) {
                $this->incidents[$id]['details'][] = [
                    'id' => $detailIndex + 1,
                    'description' => $detail['description'],
                    'user_id' => $data['user_id'] ?? 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
        }

        return true;
    }

    public function updateState($id, $state)
    {
        if (isset($this->incidents[$id])) {
            $this->incidents[$id]['state'] = $state;

            // Notificar a los observadores
            $this->notify('incident.state_changed', ['id' => $id, 'state' => $state]);

            return true;
        }
        return false;
    }

    public function disable($id)
    {
        if (isset($this->incidents[$id])) {
            $this->incidents[$id]['status'] = 0;
            return true;
        }
        return false;
    }
}
?>

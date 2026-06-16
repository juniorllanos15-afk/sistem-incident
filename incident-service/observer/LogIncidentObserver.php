<?php

require_once 'IncidentObserverInterface.php';

class LogIncidentObserver implements IncidentObserverInterface
{
    public function update(string $event, array $data): void
    {
        $message = "[" . date('Y-m-d H:i:s') . "] EVENTO: '$event' | Incidencia ID: " . ($data['id'] ?? 'N/A') . " - " . ($data['title'] ?? '') . PHP_EOL;
        file_put_contents(__DIR__ . '/../incidents.log', $message, FILE_APPEND);
    }
}

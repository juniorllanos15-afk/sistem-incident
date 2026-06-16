<?php

require_once 'IncidentObserverInterface.php';

class EventManager
{
    private array $listeners = [];

    public function attach(string $event, IncidentObserverInterface $observer): void
    {
        $this->listeners[$event][] = $observer;
    }

    public function detach(string $event, IncidentObserverInterface $observer): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }
        $this->listeners[$event] = array_filter(
            $this->listeners[$event],
            fn($obs) => $obs !== $observer
        );
    }

    public function dispatch(string $event, array $data): void
    {
        foreach ($this->listeners[$event] ?? [] as $observer) {
            $observer->update($event, $data);
        }
        foreach ($this->listeners['*'] ?? [] as $observer) {
            $observer->update($event, $data);
        }
    }

    public function getListeners(): array
    {
        return $this->listeners;
    }
}

<?php
// observer/IncidentObserverInterface.php

interface IncidentObserverInterface
{
    public function update(string $event, array $data);
}
?>

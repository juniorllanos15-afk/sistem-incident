<?php

require_once 'IncidentObserverInterface.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailIncidentObserver implements IncidentObserverInterface
{
    public function update(string $event, array $data): void
    {
        match ($event) {
            'incident.created' => $this->handleCreated($data),
            'incident.updated' => $this->handleUpdated($data),
            'incident.state_changed' => $this->handleStateChanged($data),
            default => error_log("EMAIL: Evento desconocido '$event'")
        };
    }

    private function handleCreated(array $data): void
    {
        $emails = $this->getAdminEmails();
        if (empty($emails)) {
            error_log("EMAIL: No se encontraron correos de administradores para notificar");
            return;
        }

        foreach ($emails as $email) {
            $this->sendEmail($email, function (PHPMailer $mail) use ($data) {
                $mail->Subject = "Nueva incidencia creada: " . ($data['title'] ?? '');
                $mail->Body = "Se ha registrado una nueva incidencia.\n\n"
                    . "Título: " . ($data['title'] ?? '') . "\n"
                    . "Descripción: " . ($data['description'] ?? '') . "\n"
                    . "Ubicación: " . ($data['ubication'] ?? '') . "\n"
                    . "ID de incidencia: " . ($data['id'] ?? 'N/A');
            });
        }
    }

    private function handleUpdated(array $data): void
    {
        $emails = $this->getAdminEmails();
        if (empty($emails)) {
            error_log("EMAIL: No se encontraron correos de administradores para notificar");
            return;
        }

        foreach ($emails as $email) {
            $this->sendEmail($email, function (PHPMailer $mail) use ($data) {
                $mail->Subject = "Incidencia #" . ($data['id'] ?? 'N/A') . " actualizada";
                $mail->Body = "Se ha actualizado una incidencia.\n\n"
                    . "Título: " . ($data['title'] ?? '') . "\n"
                    . "Descripción: " . ($data['description'] ?? '') . "\n"
                    . "Ubicación: " . ($data['ubication'] ?? '') . "\n"
                    . "ID de incidencia: " . ($data['id'] ?? 'N/A');
            });
        }
    }

    private function handleStateChanged(array $data): void
    {
        $state = (int) ($data['state'] ?? 0);

        match ($state) {
            2 => $this->handleAssignment($data),
            3 => $this->handleResolution($data),
            default => error_log("EMAIL: Evento state_changed con state=$state no requiere notificación por correo")
        };
    }

    private function handleAssignment(array $data): void
    {
        $incidentId = $data['id'] ?? null;
        $emails = [];

        $technicianIds = $data['technician_ids'] ?? [];
        if (!empty($technicianIds)) {
            foreach ($technicianIds as $techId) {
                $email = $this->getUserEmail((int) $techId);
                if ($email) {
                    $emails[] = $email;
                }
            }
        } else {
            $emails = $this->getTechnicianEmailsByIncident($incidentId);
        }

        if (empty($emails)) {
            error_log("EMAIL: No se encontraron técnicos asignados para la incidencia #$incidentId");
            return;
        }

        foreach ($emails as $email) {
            $this->sendEmail($email, function (PHPMailer $mail) use ($data) {
                $mail->Subject = "Se te ha asignado la incidencia #" . ($data['id'] ?? 'N/A');
                $mail->Body = "Hola, se te ha asignado una nueva incidencia para resolver.\n\n"
                    . "ID de incidencia: " . ($data['id'] ?? 'N/A') . "\n"
                    . "Estado actual: En Proceso\n\n"
                    . "Por favor ingresa al sistema para ver los detalles y registrar tu solución.";
            });
        }
    }

    private function handleResolution(array $data): void
    {
        $userId = $data['user_id'] ?? null;
        if (!$userId) {
            error_log("EMAIL: No se pudo enviar correo de finalización, falta user_id");
            return;
        }

        $userEmail = $this->getUserEmail($userId);
        if (!$userEmail) {
            error_log("EMAIL: No se pudo obtener email para user_id=$userId");
            return;
        }

        $this->sendEmail($userEmail, function (PHPMailer $mail) use ($data) {
            $mail->Subject = "Tu incidencia #" . ($data['id'] ?? 'N/A') . " ha sido resuelta";
            $mail->Body = "Buenas noticias, tu incidencia ha sido resuelta exitosamente.\n\n"
                . "ID de incidencia: " . ($data['id'] ?? 'N/A') . "\n"
                . "Estado actual: Finalizado\n\n"
                . "Puedes ingresar al sistema para revisar la solución registrada.";
        });
    }

    private function sendEmail(string $to, callable $configureMail): void
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;

            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(false);

            $configureMail($mail);

            $mail->send();
            error_log("EMAIL: Correo enviado exitosamente a $to");
        } catch (Exception $e) {
            error_log("EMAIL ERROR: " . $mail->ErrorInfo);
        }
    }

    private function httpGet(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $response === '') {
            error_log("EMAIL: Error en HTTP GET a $url - " . ($error ?: 'respuesta vacía'));
            return null;
        }

        return $response;
    }

    private function getAdminEmails(): array
    {
        $rolUrl = ROL_SERVICE_URL . '?action=list';
        $rolResponse = $this->httpGet($rolUrl);
        if ($rolResponse === null) {
            return [];
        }

        $rolData = json_decode($rolResponse, true);
        $adminRolId = null;
        if ($rolData && $rolData['success'] && is_array($rolData['data'])) {
            foreach ($rolData['data'] as $rol) {
                if (isset($rol['name']) && strtolower(trim($rol['name'])) === 'administrador') {
                    $adminRolId = (int) $rol['id'];
                    break;
                }
            }
        }

        if ($adminRolId === null) {
            error_log("EMAIL: No se encontró el rol 'Administrador' en el microservicio de roles");
            return [];
        }

        $userUrl = USER_SERVICE_URL . '?action=list';
        $userResponse = $this->httpGet($userUrl);
        if ($userResponse === null) {
            return [];
        }

        $userData = json_decode($userResponse, true);
        $adminEmails = [];
        if ($userData && $userData['success'] && is_array($userData['data'])) {
            foreach ($userData['data'] as $user) {
                if (isset($user['rol_id']) && (int) $user['rol_id'] === $adminRolId && isset($user['email'])) {
                    $adminEmails[] = $user['email'];
                }
            }
        }

        return $adminEmails;
    }

    private function getUserEmail(int $userId): ?string
    {
        $url = USER_SERVICE_URL . '?action=get&id=' . $userId;
        $response = $this->httpGet($url);
        if ($response === null) {
            return null;
        }

        $data = json_decode($response, true);
        if ($data && $data['success']) {
            return $data['data']['email'] ?? null;
        }

        return null;
    }

    private function getTechnicianEmailsByIncident($incidentId): array
    {
        if (!$incidentId) {
            error_log("EMAIL: getTechnicianEmailsByIncident - falta incidentId");
            return [];
        }

        $url = ASSIGNMENT_SERVICE_URL . '?action=by-incident&incident_id=' . $incidentId;
        $response = $this->httpGet($url);
        if ($response === null) {
            return [];
        }

        $assignData = json_decode($response, true);
        if (!$assignData || !$assignData['success'] || empty($assignData['data'])) {
            error_log("EMAIL: No se encontraron asignaciones para la incidencia #$incidentId");
            return [];
        }

        $techEmails = [];
        foreach ($assignData['data'] as $row) {
            $techId = $row['technician_id'] ?? null;
            if (!$techId) {
                continue;
            }

            $email = $this->getUserEmail((int) $techId);
            if ($email) {
                $techEmails[] = $email;
            }
        }

        return $techEmails;
    }
}

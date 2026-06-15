<?php
// observer/EmailIncidentObserver.php
require_once 'IncidentObserverInterface.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailIncidentObserver implements IncidentObserverInterface
{
    public function update(string $event, array $data): void
    {
        $userId = $data['user_id'] ?? null;
        if (!$userId) {
            error_log("EMAIL: No se pudo enviar correo, falta user_id");
            return;
        }

        $userEmail = $this->getUserEmail($userId);

        file_put_contents(__DIR__ . '/../incidents.log', 'useremail: ' . $userEmail, FILE_APPEND);
        if (!$userEmail) {
            error_log("EMAIL: No se pudo obtener email para user_id=$userId");
            return;
        }

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
            $mail->addAddress($userEmail);
            $mail->isHTML(false);

            if ($event === 'incident.created') {
                $mail->Subject = "Nueva incidencia creada: " . ($data['title'] ?? '');
                $mail->Body = "Se ha registrado una nueva incidencia.\n\n"
                    . "Título: " . ($data['title'] ?? '') . "\n"
                    . "Descripción: " . ($data['description'] ?? '') . "\n"
                    . "Ubicación: " . ($data['ubication'] ?? '') . "\n"
                    . "ID de incidencia: " . ($data['id'] ?? 'N/A');
            } elseif ($event === 'incident.updated') {
                $mail->Subject = "Incidencia #" . ($data['id'] ?? 'N/A') . " actualizada";
                $mail->Body = "Se ha actualizado una incidencia.\n\n"
                    . "Título: " . ($data['title'] ?? '') . "\n"
                    . "Descripción: " . ($data['description'] ?? '') . "\n"
                    . "Ubicación: " . ($data['ubication'] ?? '') . "\n"
                    . "ID de incidencia: " . ($data['id'] ?? 'N/A');
            } elseif ($event === 'incident.state_changed') {
                $stateNames = [1 => 'Pendiente', 2 => 'En Proceso', 3 => 'Finalizado'];
                $stateLabel = $stateNames[$data['state'] ?? 1] ?? 'Desconocido';
                $mail->Subject = "Estado de incidencia #" . ($data['id'] ?? 'N/A') . " actualizado";
                $mail->Body = "La incidencia #" . ($data['id'] ?? 'N/A')
                    . " ha cambiado su estado a: " . $stateLabel;
            }

            $mail->send();

            file_put_contents(__DIR__ . '/../incidents.log', "EMAIL: Correo enviado exitosamente a $userEmail para el evento '$event'", FILE_APPEND);
            error_log("EMAIL: Correo enviado exitosamente a $userEmail para el evento '$event'");

        } catch (Exception $e) {
            error_log("EMAIL ERROR: " . $mail->ErrorInfo);
        }
    }

    private function getUserEmail(int $userId): ?string
    {
        $url = USER_SERVICE_URL . '?action=get&id=' . $userId;
        $response = @file_get_contents($url);
        if ($response === false) {
            error_log("EMAIL: Error al consultar user-service (user_id=$userId)");
            return null;
        }
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            return $data['data']['email'] ?? null;
        }
        return null;
    }
}

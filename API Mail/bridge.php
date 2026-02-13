<?php
require_once __DIR__ . '/config.php';

class MessengerBridge
{
    private $db;

    public function __construct()
    {
        $this->db = new PDO('sqlite:' . MS_DB_FILE);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initDB();
    }

    private function initDB()
    {
        $query = "CREATE TABLE IF NOT EXISTS reports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT,
            serie TEXT,
            equipo TEXT,
            servicio TEXT,
            texto TEXT,
            imagen_path TEXT,
            status TEXT DEFAULT 'Pendiente',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->exec($query);
    }

    public function procesarReporteInterno($data)
    {
        $stmt = $this->db->prepare("INSERT INTO reports (email, serie, equipo, servicio, texto, imagen_path) 
                                    VALUES (:email, :serie, :equipo, :servicio, :texto, :imagen_path)");
        $stmt->execute([
            ':email'    => $data['email'],
            ':serie'    => $data['serie'],
            ':equipo'   => $data['equipo'],
            ':servicio' => $data['servicio'],
            ':texto'    => $data['texto'],
            ':imagen_path' => $data['imagen_path'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Simulación de envío de correo de feedback técnico.
     */
    public function enviarFeedback($email, $otId, $serie, $detalleTecnico = "El equipo ha sido reparado y se encuentra operativo.")
    {
        $logEntry = "[" . date('Y-m-d H:i:s') . "] NOTIFICACIÓN ENVIADA -> To: $email | OT: $otId | Serie: $serie | Status: OPERATIVO\n";
        file_put_contents(MS_LOG_FILE, $logEntry, FILE_APPEND);

        // Simulación de éxito
        return true;
    }
}

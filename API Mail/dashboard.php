<?php

/**
 * API Mail/dashboard.php
 * Vista administrativa para revisar reportes del Mensajero.
 * Ubicación: Diseño/API Mail/
 */

require_once __DIR__ . '/config.php';

try {
    $db = new PDO('sqlite:' . MS_DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $reports = $db->query("SELECT * FROM reports ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $reports = [];
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Mensajero | HEC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            color: white;
            padding: 2rem;
        }

        .card {
            background: #1e293b;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #334155;
        }

        h1 {
            color: #3b82f6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th,
        td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #334155;
        }

        th {
            color: #94a3b8;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
        }

        .status {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .status-pendiente {
            background: #f59e0b22;
            color: #f59e0b;
            border: 1px solid #f59e0b44;
        }

        img {
            border-radius: 0.5rem;
            cursor: pointer;
            transition: transform 0.2s;
        }

        img:hover {
            transform: scale(3);
            z-index: 100;
            position: relative;
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>Bandeja de Entrada - Mensajero</h1>
        <p>Reportes aislados del personal de servicio.</p>

        <?php if (isset($error)): ?>
            <p style="color: #ef4444;"><?php echo $error; ?></p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Servicio</th>
                    <th>Equipo / Serie</th>
                    <th>Falla</th>
                    <th>Evidencia</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $r): ?>
                    <tr>
                        <td>#<?php echo $r['id']; ?></td>
                        <td style="font-size: 0.8rem;"><?php echo $r['created_at']; ?></td>
                        <td style="font-weight: bold;"><?php echo $r['servicio']; ?></td>
                        <td>
                            <?php echo $r['equipo']; ?><br>
                            <small style="color: #3b82f6; font-family: monospace;"><?php echo $r['serie']; ?></small>
                        </td>
                        <td style="max-width: 300px; font-size: 0.9rem;"><?php echo $r['texto']; ?></td>
                        <td>
                            <?php if ($r['imagen_path']): ?>
                                <img src="uploads/<?php echo $r['imagen_path']; ?>" width="50" height="50">
                            <?php else: ?>
                                <span style="color: #475569;">Sin foto</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="status status-pendiente"><?php echo $r['status']; ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #475569; padding: 3rem;">No hay reportes pendientes.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>
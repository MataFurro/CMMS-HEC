<?php
// pages/service_request.php - Incident Report Interface (Standalone)

// Security Check
if (!defined('APP_NAME')) {
    // If accessed directly, verify session or define app name to prevent errors if config not included
    // But since this is included via index.php, APP_NAME should be defined.
    // If standalone access is needed, we'd need require_once '../config.php';
}

// Session Check (Must be logged in)
if (!isset($_SESSION['user_id'])) {
    header("Location: ?page=login");
    exit;
}

// ── Backend Logic ──
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';

// Paths
$msDbPath = __DIR__ . '/../API Mail/database/messenger.db';
$uploadDir = __DIR__ . '/../API Mail/uploads/';

// Ensure Upload Directory Exists
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// State Variables
$success = false;
$error = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize & Retrieve Inputs
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $location = $_POST['location'] ?? ''; // e.g. 'manufacturing-a'
    $equipment = $_POST['equipment'] ?? '';
    $serial = $_POST['serial_number'] ?? '';
    $description = $_POST['description'] ?? '';

    // Map Location Value to Readable Name (Optional, or store as is)
    // For now, storing the value as selected.

    // 2. File Upload Handling
    $imagePath = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileName = $_FILES['photo']['name'];
        $fileSize = $_FILES['photo']['size'];
        $fileType = $_FILES['photo']['type'];

        $allowedFileTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($fileType, $allowedFileTypes)) {
            // Generate unique name
            $newFileName = md5(time() . $fileName) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $imagePath = $newFileName;
            } else {
                $error = "Error al mover el archivo subido.";
            }
        } else {
            $error = "Tipo de archivo no permitido. Solo JPG, PNG y GIF.";
        }
    }

    // 3. Database Insertion
    if (!$error) {
        try {
            if (!file_exists($msDbPath)) {
                throw new Exception("Error crítico: Base de datos no encontrada.");
            }

            $db = new PDO('sqlite:' . $msDbPath);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Schema: table 'reports'
            // Columns: id, email, serie, equipo, servicio, texto, imagen_path, status, created_at

            $stmt = $db->prepare("INSERT INTO reports (email, serie, equipo, servicio, texto, imagen_path, status, created_at) VALUES (:email, :serie, :equipo, :servicio, :texto, :imagen, 'Pendiente', DATETIME('now'))");

            $stmt->execute([
                ':email' => $email,
                ':serie' => $serial,
                ':equipo' => $equipment,
                ':servicio' => $location,
                ':texto' => $description,
                ':imagen' => $imagePath
            ]);

            $success = true;

            // Clear inputs on success
            $email = $location = $equipment = $serial = $description = '';
        } catch (Exception $e) {
            $error = "Error de base de datos: " . $e->getMessage();
        }
    }
} else {
    // Pre-fill email if logged in
    $email = $_SESSION['user_email'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es" class="dark">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <!-- Tailwind via CDN for simplicity in this specific module -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#137fec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#0a0f14",
                        "medical-dark": "#0f172a",
                        "medical-surface": "#1e293b",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .custom-dashed {
            background-image: url("data:image/svg+xml,%3csvg width='100%25' height='100%25' xmlns='http://www.w3.org/2000/svg'%3e%3crect width='100%25' height='100%25' fill='none' rx='8' ry='8' stroke='%23135bec66' stroke-width='2' stroke-dasharray='8%2c 8' stroke-dashoffset='0' stroke-linecap='square'/%3e%3c/svg%3e");
        }

        /* Custom file input styling logic */
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
    </style>
    <title>Industrial Ops | Incident Report</title>
</head>

<body class="bg-background-light dark:bg-background-dark min-h-screen flex items-center justify-center p-4 md:p-8 font-display">

    <!-- Main Card Container -->
    <div class="max-w-2xl w-full bg-white dark:bg-medical-surface rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden relative">

        <!-- Logic Feedback Overlays (Simple Toast Injection) -->
        <?php if ($success): ?>
            <div class="absolute top-0 left-0 w-full bg-green-500 text-white p-3 text-center text-sm font-bold z-50 animate-pulse">
                ✓ Reporte enviado exitosamente a mantenimiento.
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="absolute top-0 left-0 w-full bg-red-500 text-white p-3 text-center text-sm font-bold z-50">
                ⚠ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Header Section -->
        <div class="p-8 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-3 mb-6">
                <!-- Back Button for context if needed, though stand-alone usually doesn't have one -->
                <button onclick="window.history.back()" class="w-10 h-10 rounded-lg hover:bg-white/5 flex items-center justify-center text-slate-400 trasition-colors" title="Volver">
                    <span class="material-icons">arrow_back</span>
                </button>

                <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center text-white">
                    <span class="material-icons text-2xl">build</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Industrial Ops</span>
                    <span class="text-lg font-bold text-slate-900 dark:text-white leading-none">Global Network</span>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Quick Incident Report</h1>
            <p class="text-slate-500 dark:text-slate-400 leading-relaxed">
                Por favor provea detalles sobre la falla del equipo. Mantenimiento será notificado inmediatamente.
            </p>
        </div>

        <!-- Form Section -->
        <form class="p-8 space-y-6" method="POST" enctype="multipart/form-data">

            <!-- Email Input -->
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300" for="email">
                    Reporter Email <span class="text-primary">*</span>
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">
                        <span class="material-icons text-lg">alternate_email</span>
                    </div>
                    <input
                        class="block w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-slate-400"
                        id="email" name="email" placeholder="name@company.com" required type="email" value="<?= htmlspecialchars($email ?? '') ?>" />
                </div>
            </div>

            <!-- Location Dropdown -->
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300" for="location">
                    Location / Department <span class="text-primary">*</span>
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">
                        <span class="material-icons text-lg">location_on</span>
                    </div>
                    <select
                        class="block w-full pl-11 pr-10 py-3 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all appearance-none cursor-pointer"
                        id="location" name="location" required>
                        <option disabled selected value="">Select location...</option>
                        <option value="manufacturing-a">Manufacturing Floor - Wing A</option>
                        <option value="manufacturing-b">Manufacturing Floor - Wing B</option>
                        <option value="warehouse-logistics">Warehouse & Logistics</option>
                        <option value="quality-control">Quality Control Lab</option>
                        <option value="r-d-facility">R&D Facility</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                        <span class="material-icons">expand_more</span>
                    </div>
                </div>
            </div>

            <!-- Equipment Field -->
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300" for="equipment">
                    Equipo
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">
                        <span class="material-icons text-lg">medical_services</span>
                    </div>
                    <input
                        class="block w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-slate-400"
                        id="equipment" name="equipment" placeholder="Nombre del equipo (Ej. Ventilador Mecánico)" type="text" value="<?= htmlspecialchars($equipment ?? '') ?>" />
                </div>
            </div>

            <!-- Serial Number Field -->
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300" for="serial_number">
                    N° de Serie <span class="text-primary">*</span>
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">
                        <span class="material-icons text-lg">qr_code</span>
                    </div>
                    <input
                        class="block w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-slate-400"
                        id="serial_number" name="serial_number" placeholder="Ingrese el número de serie" required type="text" value="<?= htmlspecialchars($serial ?? '') ?>" />
                </div>
            </div>

            <!-- Description Textarea -->
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300" for="description">
                    Description of the Failure <span class="text-primary">*</span>
                </label>
                <textarea
                    class="block w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-slate-400 resize-none"
                    id="description" name="description" placeholder="Describe error codes, visible damage, or unusual sounds..."
                    required rows="4"><?= htmlspecialchars($description ?? '') ?></textarea>
            </div>

            <!-- Upload Zone -->
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">
                    Equipment Photos
                </label>
                <div class="custom-dashed rounded-lg p-8 flex flex-col items-center justify-center gap-3 bg-primary/5 hover:bg-primary/[0.08] transition-colors cursor-pointer group file-input-wrapper">
                    <input type="file" name="photo" accept="image/*" onchange="document.getElementById('file-label').textContent = this.files[0].name">
                    <div class="w-12 h-12 bg-white dark:bg-slate-800 rounded-full shadow-sm flex items-center justify-center text-primary transition-transform group-hover:scale-110">
                        <span class="material-icons">photo_camera</span>
                    </div>
                    <div class="text-center pointer-events-none">
                        <p class="text-sm font-medium text-slate-900 dark:text-white" id="file-label">Click or drag images here</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">PNG, JPG up to 10MB</p>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button
                class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 px-6 rounded-lg flex items-center justify-center gap-2 transition-all transform active:scale-[0.98] shadow-lg shadow-primary/20"
                type="submit">
                <span class="material-icons text-xl">send</span>
                <span>SEND TO MAINTENANCE</span>
            </button>
        </form>

        <!-- Footer -->
        <div class="px-8 py-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-green-500"></div>
                <span class="text-[10px] font-mono font-medium text-slate-500 dark:text-slate-400 tracking-tighter uppercase">
                    SECURE INDUSTRIAL COMMUNICATION PROTOCOL V4.2
                </span>
            </div>
            <div class="flex items-center gap-6">
                <a class="text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-primary transition-colors" href="#">Emergency Support</a>
                <a class="text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-primary transition-colors" href="?page=messenger_requests">Recent Reports</a>
            </div>
        </div>
    </div>

    <!-- Background Elements -->
    <div class="fixed top-0 left-0 w-full h-full -z-10 pointer-events-none overflow-hidden">
        <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/5 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[30%] h-[30%] bg-primary/5 rounded-full blur-[80px]"></div>
    </div>
</body>

</html>
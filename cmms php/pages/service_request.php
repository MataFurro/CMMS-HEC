<?php
// pages/service_request.php - Incident Report Interface (Standalone)

// Security Check
if (!defined('APP_NAME')) {
    // If accessed directly, verify session or define app name to prevent errors if config not included
}

// Session Check (Must be logged in)
if (!isset($_SESSION['user_id'])) {
    header("Location: ?page=login");
    exit;
}

// ── Backend Logic ──
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/../Backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

// Paths
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
    if (!verifyCsrfToken()) {
        $error = "Token de seguridad inválido. Recargue e intente de nuevo.";
    } else {
        // 1. Sanitize & Retrieve Inputs
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $location = $_POST['location'] ?? ''; // e.g. 'manufacturing-a'
        $equipment = $_POST['equipment'] ?? '';
        $serial = $_POST['serial_number'] ?? '';
        $description = $_POST['description'] ?? '';

        // 2. File Upload Handling
        $imagePath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['photo']['tmp_name'];
            $fileName = $_FILES['photo']['name'];
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

        // 3. Database Insertion (MySQL)
        if (!$error) {
            try {
                $db = DatabaseService::getInstance();

                // Schema: table 'messenger_reports' (MySQL)
                // Connector: asset_id (from serial_number input, now acting as ID)
                $stmt = $db->prepare("INSERT INTO messenger_reports (email, asset_id, asset_name, texto, imagen_path, status, created_at) VALUES (:email, :asset_id, :asset_name, :texto, :imagen, 'En Curso', NOW())");

                $stmt->execute([
                    ':email' => $email,
                    ':asset_id' => $serial, // El ID/Serie es el conector
                    ':asset_name' => $equipment, // El nombre del equipo para referencia rápida
                    ':texto' => $description,
                    ':imagen' => $imagePath
                ]);

                $success = true;

                // Clear inputs on success
                $email = $location = $equipment = $serial = $description = '';
            } catch (Exception $e) {
                $error = "Error de base de datos MySQL: " . $e->getMessage();
            }
        }
    }
} else {
    // Pre-fill email if logged in
    $email = $_SESSION['user_email'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#10b981",
                        "background-light": "#f1f5f9",
                        "background-dark": "#06090f",
                        "medical-dark": "#0b0f1a",
                        "medical-surface": "var(--medical-surface)",
                        "border-dark": "var(--border-color)",
                        "text-main": "#0f172a",
                        "text-muted": "#64748b"
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }

        const applyTheme = (theme) => {
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        };
        applyTheme(localStorage.getItem('theme') || 'dark');
    </script>
    <style>
        :root {
            --medical-surface: #ffffff;
            --border-color: #e2e8f0;
            --input-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        .dark {
            --medical-surface: #0b0f1a;
            --border-color: #1e293b;
            --input-bg: #0f172a;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
        }

        .custom-dashed {
            background-image: url("data:image/svg+xml,%3csvg width='100%25' height='100%25' xmlns='http://www.w3.org/2000/svg'%3e%3crect width='100%25' height='100%25' fill='none' rx='8' ry='8' stroke='%2310b98166' stroke-width='2' stroke-dasharray='8%2c 8' stroke-dashoffset='0' stroke-linecap='square'/%3e%3c/svg%3e");
        }

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

        .toast-success {
            background: #059669;
            color: white;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.4);
        }

        .toast-error {
            background: #dc2626;
            color: white;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
        }
    </style>
    <title>BioCMMS | Reporte de Incidentes</title>
</head>

<body class="bg-background-light dark:bg-background-dark min-h-screen flex items-center justify-center p-4 md:p-8 font-display transition-colors duration-300">

    <!-- Main Card Container -->
    <div class="max-w-2xl w-full bg-medical-surface rounded-xl shadow-xl border border-border-dark overflow-hidden relative transition-all duration-300">

        <!-- Logic Feedback Overlays -->
        <?php if ($success): ?>
            <div class="absolute top-0 left-0 w-full toast-success p-4 text-center text-sm font-bold z-50">
                <span class="flex items-center justify-center gap-2">
                    <span class="material-icons text-lg">check_circle</span>
                    Reporte enviado exitosamente al Departamento de Bioingeniería.
                </span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="absolute top-0 left-0 w-full toast-error p-4 text-center text-sm font-bold z-50">
                <span class="flex items-center justify-center gap-2">
                    <span class="material-icons text-lg">error</span>
                    <?= htmlspecialchars($error) ?>
                </span>
            </div>
        <?php endif; ?>

        <!-- Header Section -->
        <div class="p-8 border-b border-border-dark">
            <div class="flex items-center gap-3 mb-6">
                <!-- Back Button -->
                <?php
                $backUrl = ($_SESSION['user_role'] ?? '') === ROLE_USER ? '?page=login&action=logout' : '?page=dashboard';
                $backTitle = ($_SESSION['user_role'] ?? '') === ROLE_USER ? 'Cerrar Sesión' : 'Volver al Dashboard';
                ?>
                <a href="<?= $backUrl ?>"
                    class="w-10 h-10 rounded-lg bg-background-light dark:bg-medical-dark flex items-center justify-center text-text-muted hover:border hover:border-emerald-500/30 transition-all active:scale-95"
                    title="<?= $backTitle ?>">
                    <span class="material-icons">arrow_back</span>
                </a>

                <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center text-primary">
                    <span class="material-icons text-2xl">medical_services</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-bold uppercase tracking-wider text-text-muted">BioCMMS v4.5</span>
                    <span class="text-lg font-bold text-text-main leading-none">Gestión de Tecnología Médica</span>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-text-main mb-2">Reporte Rápido de Incidentes</h1>
            <p class="text-text-muted leading-relaxed">
                Complete los detalles sobre la falla del equipo. El equipo de Bioingeniería será notificado de inmediato.
            </p>
        </div>

        <!-- Form Section -->
        <form class="p-8 space-y-6" method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>

            <!-- Email Input -->
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-text-muted transition-colors" for="email">
                    Email del Solicitante <span class="text-primary">*</span>
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-text-muted/60 group-focus-within:text-primary transition-colors">
                        <span class="material-icons text-lg">alternate_email</span>
                    </div>
                    <input
                        class="block w-full pl-11 pr-4 py-3 bg-[var(--input-bg)] border-[var(--border-color)] rounded-lg text-text-main focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-text-muted/40 font-medium"
                        id="email" name="email" placeholder="nombre@hospital.cl"
                        required type="email" value="<?= htmlspecialchars($email ?? '') ?>" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Location Dropdown -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-text-muted" for="location">
                        Departamento / Ubicación <span class="text-primary">*</span>
                    </label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-text-muted/60 group-focus-within:text-primary transition-colors">
                            <span class="material-icons text-lg">location_on</span>
                        </div>
                        <?php $locations = getAllLocations(); ?>
                        <select
                            class="block w-full pl-11 pr-10 py-3 bg-[var(--input-bg)] border-[var(--border-color)] rounded-lg text-text-main focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all appearance-none cursor-pointer font-medium"
                            id="location" name="location" required>
                            <option disabled selected value="">Seleccione ubicación...</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-text-muted/60">
                            <span class="material-icons">expand_more</span>
                        </div>
                    </div>
                </div>

                <!-- Serial Number Field -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-text-muted" for="serial_number">
                        N° de Serie / ID <span class="text-primary">*</span>
                    </label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-text-muted/60 group-focus-within:text-primary transition-colors">
                            <span class="material-icons text-lg">qr_code</span>
                        </div>
                        <input
                            class="block w-full pl-11 pr-4 py-3 bg-[var(--input-bg)] border-[var(--border-color)] rounded-lg text-text-main focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-text-muted/40 font-medium"
                            id="serial_number" name="serial_number" placeholder="Ej: 72C0076C"
                            required type="text" value="<?= htmlspecialchars($serial ?? '') ?>" />
                    </div>
                </div>
            </div>

            <!-- Equipment Field -->
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-text-muted" for="equipment">
                    Nombre del Equipo
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-text-muted/60 group-focus-within:text-primary transition-colors">
                        <span class="material-icons text-lg">biotech</span>
                    </div>
                    <input
                        class="block w-full pl-11 pr-4 py-3 bg-[var(--input-bg)] border-[var(--border-color)] rounded-lg text-text-main focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-text-muted/40 font-medium"
                        id="equipment" name="equipment" placeholder="Ej: Monitor Multiparámetro"
                        type="text" value="<?= htmlspecialchars($equipment ?? '') ?>" />
                </div>
            </div>

            <!-- Description Textarea -->
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-text-muted" for="description">
                    Descripción de la Falla <span class="text-primary">*</span>
                </label>
                <textarea
                    class="block w-full px-4 py-3 bg-[var(--input-bg)] border-[var(--border-color)] rounded-lg text-text-main focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-text-muted/40 resize-none font-medium"
                    id="description" name="description" placeholder="Detalle el problema, códigos de error o ruidos..."
                    required rows="4"><?= htmlspecialchars($description ?? '') ?></textarea>
            </div>

            <!-- Upload Zone -->
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-text-muted">
                    Fotos de la Falla
                </label>
                <div class="custom-dashed rounded-lg p-8 flex flex-col items-center justify-center gap-3 bg-primary/5 hover:bg-primary/[0.08] transition-all cursor-pointer group file-input-wrapper border border-transparent hover:border-primary/20">
                    <input type="file" name="photo" accept="image/*" onchange="document.getElementById('file-label').textContent = this.files[0] ? this.files[0].name : 'Haga clic para subir imágenes'">
                    <div class="w-12 h-12 bg-medical-surface rounded-full shadow-sm flex items-center justify-center text-primary transition-transform group-hover:scale-110">
                        <span class="material-icons">photo_camera</span>
                    </div>
                    <div class="text-center pointer-events-none">
                        <p class="text-sm font-medium text-text-main" id="file-label">Toque para seleccionar imágenes</p>
                        <p class="text-xs text-text-muted mt-1">PNG, JPG hasta 10MB</p>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button
                class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 px-6 rounded-lg flex items-center justify-center gap-2 transition-all transform active:scale-[0.98] shadow-lg shadow-primary/20"
                type="submit">
                <span class="material-icons text-xl">send</span>
                <span>ENVIAR SOLICITUD</span>
            </button>
        </form>

        <!-- Footer -->
        <div class="px-8 py-6 bg-background-light dark:bg-medical-dark border-t border-border-dark flex flex-col md:flex-row justify-between items-center gap-4 transition-colors">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                <span class="text-[10px] font-mono font-medium text-text-muted tracking-tighter uppercase">
                    PROTOCOLO DE GESTIÓN BIOMÉDICA V4.5 | HEC
                </span>
            </div>
            <div class="flex items-center gap-6">
                <a class="text-xs font-semibold text-text-muted hover:text-primary transition-colors" href="#">Soporte Técnico</a>
                <a class="text-xs font-semibold text-text-muted hover:text-primary transition-colors" href="?page=messenger_requests">Mis Reportes</a>
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
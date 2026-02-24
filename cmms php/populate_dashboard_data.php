<?php

/**
 * populate_dashboard_data.php
 * ─────────────────────────────────────────────────────────────────
 * Poblamiento de datos para el Dashboard BioCMMS.
 * - Clasifica equipos como "Monitoreo" o "No Monitoreo" (campo riesgo_ge)
 * - Asigna costos reales en CLP
 * - Asigna criticidad: CRITICAL (Crítico), RELEVANT (Relevante), LOW (No Aplica)
 * - Genera OTs históricas conectadas a activos reales
 *
 * USO: http://localhost/cmms%20php/populate_dashboard_data.php
 * ─────────────────────────────────────────────────────────────────
 */
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/Backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

$mode  = $_GET['mode'] ?? 'preview';
$isRun = ($mode === 'run');
$db    = DatabaseService::getInstance();

echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<title>Poblamiento · BioCMMS</title>
<style>
  body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:2rem;line-height:1.7}
  h1{color:#3b82f6;border-bottom:1px solid #1e293b;padding-bottom:.5rem}
  h2{color:#94a3b8;margin-top:2rem;font-size:.85rem;text-transform:uppercase;letter-spacing:2px}
  .ok{color:#10b981}.warn{color:#f59e0b}.err{color:#ef4444}.fade{color:#475569}
  .box{background:#1e293b;border:1px solid #334155;border-radius:8px;padding:1rem;margin:1rem 0}
  .btn{display:inline-block;padding:.75rem 2rem;background:#3b82f6;color:#fff;border-radius:8px;
       text-decoration:none;font-weight:bold;margin-right:1rem}
  .btn-grey{background:#475569}
  table{width:100%;border-collapse:collapse;font-size:.78rem}
  th{background:#1e293b;color:#94a3b8;padding:.4rem .6rem;text-align:left;font-size:.7rem}
  td{padding:.4rem .6rem;border-bottom:1px solid #1e293b}
  tr:hover td{background:#1e293b}
  .tag-mon{background:#1e40af22;color:#60a5fa;border:1px solid #1e40af;
           border-radius:4px;padding:.1rem .4rem;font-size:.7rem}
  .tag-nomon{background:#1e293b;color:#64748b;border:1px solid #334155;
             border-radius:4px;padding:.1rem .4rem;font-size:.7rem}
  .tag-crit{color:#ef4444}.tag-rel{color:#f59e0b}.tag-na{color:#475569}
</style></head><body>';

echo "<h1>🏥 BioCMMS · Poblamiento de Datos</h1>";
echo "<p class='fade'>Modo: <strong>" .
    ($isRun ? "<span class='ok'>EJECUCIÓN</span>" : "<span class='warn'>PREVIEW – sin cambios aún</span>") .
    "</strong></p>";

// ── 1. Cargar activos ─────────────────────────────────────────────
$assets = $db->query(
    "SELECT id, name, brand, model, location, status, criticality,
     acquisition_cost, useful_life_pct, total_useful_life,
     purchased_year, under_maintenance_plan, riesgo_ge
     FROM assets ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

$total = count($assets);
if ($total === 0) {
    echo "<p class='err'>⚠️ Sin activos en BD. Importa el inventario primero.</p></body></html>";
    exit;
}

// ═══════════════════════════════════════════════════════════════
// TABLA DE COSTOS Y CLASIFICACIÓN POR TIPO DE EQUIPO
// ─────────────────────────────────────────────────────────────
// Formato: 'keyword' => [costo_base_CLP, vida_años, es_monitoreo, criticidad]
//   criticidad: CRITICAL | RELEVANT | LOW
// ═══════════════════════════════════════════════════════════════
$catalogoEquipos = [
    // ── MONITOREO · CRÍTICO ──────────────────────────────────────
    'ventilador'              => [4_800_000,  10, true,  'CRITICAL'],
    'desfibrilador'           => [3_200_000,   8, true,  'CRITICAL'],
    'monitor cardio'          => [5_200_000,   8, true,  'CRITICAL'],
    'monitor de signos'       => [4_800_000,   8, true,  'CRITICAL'],
    'monitor signos vitales'  => [4_800_000,   8, true,  'CRITICAL'],
    'monitor multiparametro'  => [5_500_000,   8, true,  'CRITICAL'],
    'monitor multiparámetro'  => [5_500_000,   8, true,  'CRITICAL'],
    'oxímetro'                => [380_000,     5, true,  'CRITICAL'],
    'oximetro'                => [380_000,     5, true,  'CRITICAL'],
    'pulsioxímetro'           => [420_000,     5, true,  'CRITICAL'],
    'pulsioximetro'           => [420_000,     5, true,  'CRITICAL'],
    'ecmo'                    => [95_000_000, 12, true,  'CRITICAL'],
    'incubadora'              => [8_500_000,  10, true,  'CRITICAL'],
    'cuna térmica'            => [5_200_000,  10, true,  'CRITICAL'],
    'cuna termica'            => [5_200_000,  10, true,  'CRITICAL'],
    'monitor de presión'      => [2_800_000,   7, true,  'CRITICAL'],
    'monitor de presion'      => [2_800_000,   7, true,  'CRITICAL'],
    'bomba de infusión'       => [1_500_000,   7, true,  'CRITICAL'],
    'bomba de infusion'       => [1_500_000,   7, true,  'CRITICAL'],
    'bomba de jeringa'        => [980_000,     7, true,  'CRITICAL'],
    'infusor'                 => [1_200_000,   7, true,  'CRITICAL'],

    // ── MONITOREO · RELEVANTE ────────────────────────────────────
    'monitor'                 => [4_200_000,   7, true,  'RELEVANT'],
    'electrocardio'           => [3_800_000,  10, true,  'RELEVANT'],
    'electrocardiografo'      => [3_800_000,  10, true,  'RELEVANT'],
    'ecógrafo'                => [28_000_000, 10, true,  'RELEVANT'],
    'ecografo'                => [28_000_000, 10, true,  'RELEVANT'],
    'ultrasonido'             => [22_000_000, 10, true,  'RELEVANT'],
    'arco en c'               => [85_000_000, 12, true,  'RELEVANT'],
    'rayos x'                 => [65_000_000, 15, true,  'RELEVANT'],
    'tensiómetro'             => [280_000,     7, true,  'RELEVANT'],
    'tensiometro'             => [280_000,     7, true,  'RELEVANT'],
    'termómetro'              => [180_000,     5, true,  'RELEVANT'],
    'termometro'              => [180_000,     5, true,  'RELEVANT'],
    'glucómetro'              => [95_000,      5, true,  'RELEVANT'],
    'glucometro'              => [95_000,      5, true,  'RELEVANT'],
    'glicómetro'              => [95_000,      5, true,  'RELEVANT'],

    // ── NO MONITOREO · CRÍTICO ───────────────────────────────────
    'anestesia'               => [18_000_000, 12, false, 'CRITICAL'],
    'equipo de anestesia'     => [18_000_000, 12, false, 'CRITICAL'],
    'hemodiálisis'            => [25_000_000, 10, false, 'CRITICAL'],
    'marcapaso'               => [8_500_000,  10, false, 'CRITICAL'],

    // ── NO MONITOREO · RELEVANTE ─────────────────────────────────
    'autoclave'               => [6_500_000,  15, false, 'RELEVANT'],
    'esterilizador'           => [5_800_000,  12, false, 'RELEVANT'],
    'electrobisturí'          => [4_200_000,   8, false, 'RELEVANT'],
    'electrobisturi'          => [4_200_000,   8, false, 'RELEVANT'],
    'laparoscopio'            => [45_000_000, 12, false, 'RELEVANT'],
    'endoscopio'              => [32_000_000, 10, false, 'RELEVANT'],
    'colposcop'               => [12_000_000, 10, false, 'RELEVANT'],
    'microscopio'             => [4_200_000,  10, false, 'RELEVANT'],
    'centrifuga'              => [2_800_000,  10, false, 'RELEVANT'],
    'centrífuga'              => [2_800_000,  10, false, 'RELEVANT'],
    'láser'                   => [35_000_000, 10, false, 'RELEVANT'],
    'laser'                   => [35_000_000, 10, false, 'RELEVANT'],
    'litotriptor'             => [120_000_000, 15, false, 'RELEVANT'],
    'lámpara quirúrgica'      => [8_500_000,  15, false, 'RELEVANT'],
    'lampara quirurgica'      => [8_500_000,  15, false, 'RELEVANT'],
    'lámpara'                 => [3_200_000,  10, false, 'RELEVANT'],
    'lampara'                 => [3_200_000,  10, false, 'RELEVANT'],
    'mesa quirúrgica'         => [18_000_000, 15, false, 'RELEVANT'],
    'mesa quirurgica'         => [18_000_000, 15, false, 'RELEVANT'],
    'fototerapia'             => [1_800_000,   8, false, 'RELEVANT'],
    'aspirador'               => [1_200_000,   8, false, 'RELEVANT'],
    'nebulizador'             => [380_000,     7, false, 'RELEVANT'],
    'otoscopio'               => [280_000,     8, false, 'RELEVANT'],
    'oxígeno'                 => [850_000,    10, false, 'RELEVANT'],
    'oxigeno'                 => [850_000,    10, false, 'RELEVANT'],

    // ── NO MONITOREO · NO APLICA ─────────────────────────────────
    'camilla'                 => [1_800_000,  10, false, 'LOW'],
    'cama'                    => [3_200_000,  15, false, 'LOW'],
    'silla de ruedas'         => [580_000,    10, false, 'LOW'],
    'silla'                   => [480_000,    10, false, 'LOW'],
    'báscula'                 => [380_000,    10, false, 'LOW'],
    'bascula'                 => [380_000,    10, false, 'LOW'],
    'tallímetro'              => [280_000,    10, false, 'LOW'],
    'tallimetro'              => [280_000,    10, false, 'LOW'],
];

function clasificarEquipo(string $name, string $location): array
{
    global $catalogoEquipos;
    $n   = mb_strtolower($name);
    $loc = mb_strtolower($location);

    foreach ($catalogoEquipos as $kw => [$costo, $vida, $esMonitoreo, $crit]) {
        if (str_contains($n, $kw)) {
            return [$costo, $vida, $esMonitoreo, $crit];
        }
    }

    // Por ubicación
    $zonasCriticas = ['uci', 'uti', 'quirófano', 'quirofano', 'urgencias', 'reanimación'];
    foreach ($zonasCriticas as $z) {
        if (str_contains($loc, $z)) {
            return [2_800_000, 10, false, 'RELEVANT'];
        }
    }

    // Genérico
    return [850_000, 10, false, 'LOW'];
}

// ── 2. Calcular actualizaciones ───────────────────────────────────
$updates = [];
$yearNow = (int)date('Y');

foreach ($assets as $asset) {
    $seed  = abs(crc32($asset['id']));
    [$costoBase, $vidaTotal, $esMonitoreo, $criticidad] =
        clasificarEquipo($asset['name'], $asset['location'] ?? '');

    // Variar costo ±20%
    $factor      = 0.80 + ($seed % 40) / 100;
    $costoFinal  = (int)round($costoBase * $factor);
    $valorRepos  = (int)round($costoFinal * 1.35);

    // Vida útil
    $pyr       = $asset['purchased_year'] ?: ($yearNow - 2 - ($seed % 7));
    $yearsUsed = max(0, min($yearNow - $pyr, $vidaTotal));
    $vidaPct   = $vidaTotal > 0 ? max(5, round((($vidaTotal - $yearsUsed) / $vidaTotal) * 100)) : 50;
    $yearsRem  = $vidaTotal - $yearsUsed;

    // Clasificación: "Monitoreo" o "No Monitoreo" en campo riesgo_ge
    $claseMonitoreo = $esMonitoreo ? 'Monitoreo' : 'No Monitoreo';

    $updates[] = [
        'id'               => $asset['id'],
        'costo'            => $costoFinal,
        'valor_repos'      => $valorRepos,
        'vida_pct'         => $vidaPct,
        'vida_total'       => $vidaTotal,
        'years_rem'        => max(0, $yearsRem),
        'pyr'              => $pyr,
        'criticality'      => $criticidad,
        'riesgo_ge'        => $claseMonitoreo,
        'under_mp'         => 1,
        'es_monitoreo'     => $esMonitoreo,
        'costo_anterior'   => $asset['acquisition_cost'],
    ];
}

// ── 3. Preview table ──────────────────────────────────────────────
$critLabel = ['CRITICAL' => 'Crítico', 'RELEVANT' => 'Relevante', 'LOW' => 'No Aplica'];
$critClass  = ['CRITICAL' => 'tag-crit', 'RELEVANT' => 'tag-rel', 'LOW' => 'tag-na'];

echo "<h2>1. Equipos · clasificación y costos calculados</h2>";
echo "<p class='fade'>Total: <strong>$total equipo" . ($total !== 1 ? 's' : '') . "</strong></p>";
echo "<div class='box'><table><thead><tr>
  <th>ID</th><th>Nombre</th><th>Clasificación</th>
  <th>Criticidad</th><th>Costo Anterior</th><th>Costo Nuevo</th><th>Vida Útil</th>
</tr></thead><tbody>";

foreach ($updates as $u) {
    $tag  = $u['es_monitoreo']
        ? "<span class='tag-mon'>📡 Monitoreo</span>"
        : "<span class='tag-nomon'>🔧 No Monitoreo</span>";
    $cl   = $critClass[$u['criticality']] ?? 'tag-na';
    $lbl  = $critLabel[$u['criticality']] ?? $u['criticality'];
    $prev = number_format($u['costo_anterior'], 0, ',', '.');
    $new  = number_format($u['costo'], 0, ',', '.');

    echo "<tr>
      <td class='fade'>{$u['id']}</td>
      <td><strong>{$u['id']}</strong></td>
      <td>$tag</td>
      <td><span class='$cl'>$lbl</span></td>
      <td class='fade'>$ $prev</td>
      <td class='ok'>$ $new CLP</td>
      <td>{$u['vida_pct']}%</td>
    </tr>";
}
echo "</tbody></table></div>";

// ── 4. OTs sin asignar ───────────────────────────────────────────
$otCount         = $db->query("SELECT COUNT(*) FROM work_orders")->fetchColumn();
$idsConOT        = $db->query("SELECT DISTINCT asset_id FROM work_orders")->fetchAll(PDO::FETCH_COLUMN);
$idsConOTSet     = array_flip($idsConOT);
$assetsSinOT     = array_filter($assets, fn($a) => !isset($idsConOTSet[$a['id']]));
$totalNuevasOts  = count($assetsSinOT) * 3; // ~3 OTs por activo

echo "<h2>2. Órdenes de Trabajo</h2>";
echo "<div class='box'>
  <span class='ok'>✓ OTs existentes: <strong>$otCount</strong></span> &nbsp;|&nbsp;
  <span class='warn'>Activos sin OT: <strong>" . count($assetsSinOT) . "</strong></span> &nbsp;|&nbsp;
  <span class='fade'>Se generarán ~<strong>$totalNuevasOts</strong> OTs nuevas</span>
</div>";

// ═══════════════════════════════════════════════════════════════
// 5. EJECUCIÓN
// ═══════════════════════════════════════════════════════════════
if ($isRun) {
    echo "<h2>3. Ejecutando...</h2><div class='box'>";
    $cntAssets = 0;
    $cntOTs    = 0;

    $techId = $db->query(
        "SELECT u.id FROM users u INNER JOIN technicians t ON t.user_id = u.id LIMIT 1"
    )->fetchColumn();

    $db->beginTransaction();
    try {
        // Actualizar activos
        $stmtA = $db->prepare("UPDATE assets SET
            acquisition_cost       = :costo,
            useful_life_pct        = :vpct,
            total_useful_life      = :vtotal,
            years_remaining        = :yrem,
            purchased_year         = :pyr,
            criticality            = :crit,
            riesgo_ge              = :rge,
            under_maintenance_plan = :ump
            WHERE id = :id");

        foreach ($updates as $u) {
            $stmtA->execute([
                ':costo'  => $u['costo'],
                ':vpct'   => $u['vida_pct'],
                ':vtotal' => $u['vida_total'],
                ':yrem'   => $u['years_rem'],
                ':pyr'    => $u['pyr'],
                ':crit'   => $u['criticality'],
                ':rge'    => $u['riesgo_ge'],
                ':ump'    => $u['under_mp'],
                ':id'     => $u['id'],
            ]);
            $cntAssets++;
        }

        echo "<span class='ok'>✅ Activos actualizados: <strong>$cntAssets</strong></span><br>";

        // Generar OTs
        $stmtOT = $db->prepare("INSERT IGNORE INTO work_orders
            (id, asset_id, type, status, assigned_tech_id, created_date, completed_date, priority, observations, duration_hours)
            VALUES (:id,:aid,:type,:status,:tech,:cd,:cpd,:pri,:obs,:dur)");

        $tiposOT   = ['Preventiva', 'Correctiva', 'Preventiva', 'Calibracion'];
        $statusOT  = ['Terminada',  'Terminada',  'En Proceso', 'Terminada'];
        $prioOT    = ['Alta',       'Media',      'Alta',       'Baja'];

        foreach ($assetsSinOT as $asset) {
            $s    = abs(crc32($asset['id']));
            $nOTs = 2 + ($s % 4);

            for ($i = 0; $i < $nOTs; $i++) {
                $idx  = ($s + $i) % count($tiposOT);
                $yr   = 2024 + (($s + $i) % 2);
                $mo   = 1 + (($s * ($i + 1)) % 12);
                $dy   = 1 + (($s + $i * 3) % 28);
                $date = sprintf('%04d-%02d-%02d', $yr, $mo, $dy);
                $comp = ($statusOT[$idx] === 'Terminada')
                    ? date('Y-m-d', strtotime($date . ' +5 days')) : null;

                $oid = 'OT-' . $yr . '-' . str_pad(($s + $i * 7) % 9999, 4, '0', STR_PAD_LEFT);

                $stmtOT->execute([
                    ':id'     => $oid,
                    ':aid'    => $asset['id'],
                    ':type'   => $tiposOT[$idx],
                    ':status' => $statusOT[$idx],
                    ':tech'   => $techId ?: null,
                    ':cd'     => $date,
                    ':cpd'    => $comp,
                    ':pri'    => $prioOT[$idx],
                    ':obs'    => ucfirst($tiposOT[$idx]) . ' de equipo: ' . $asset['name'],
                    ':dur'    => 1.5 + ($s % 8),
                ]);
                $cntOTs++;
            }
        }

        echo "<span class='ok'>✅ OTs generadas: <strong>$cntOTs</strong></span><br>";
        $db->commit();
        echo "<br><strong class='ok'>🎉 COMPLETADO — Datos listos para el Dashboard.</strong>";
    } catch (\Exception $e) {
        $db->rollBack();
        echo "<span class='err'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
    echo "</div>";
    echo "<p>
      <a class='btn' href='?page=dashboard'>🏠 Dashboard</a>
      <a class='btn' href='?page=family_analysis'>📊 Clasificación por Clase</a>
    </p>";
} else {
    echo "<h2>3. ¿Ejecutar?</h2><div class='box'>
      <p class='warn'>⚠️ Actualizará <strong>$total activos</strong> con costos, clasificación (Monitoreo/No Monitoreo) y criticidad (Crítico/Relevante/No Aplica).</p>
      <p class='fade'>✔ Los costos varían ±20% por equipo para mayor realismo.</p>
      <p class='fade'>✔ Genera OTs para activos sin historial (INSERT IGNORE, sin duplicados).</p>
    </div>
    <a class='btn' href='populate_dashboard_data.php?mode=run'>🚀 Ejecutar ahora</a>
    <a class='btn btn-grey' href='?page=dashboard'>Cancelar</a>";
}

echo "</body></html>";

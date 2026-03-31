<?php
// Quick validation: count what the fixed parser would produce
ini_set('memory_limit', '512M');

function parseXlsxFixed($fp)
{
    $zip = new ZipArchive();
    $zip->open($fp);
    $ss = [];
    if ($sd = $zip->getFromName('xl/sharedStrings.xml')) {
        $xml = new SimpleXMLElement($sd);
        foreach ($xml->si as $si) {
            // FIXED: iterate ALL rich-text runs
            if (isset($si->r)) {
                $text = '';
                foreach ($si->r as $r) $text .= (string)($r->t ?? '');
                $ss[] = $text;
            } else {
                $ss[] = (string)($si->t ?? '');
            }
        }
    }
    $rows = [];
    if ($sd = $zip->getFromName('xl/worksheets/sheet1.xml')) {
        $xml = new SimpleXMLElement($sd);
        foreach ($xml->sheetData->row as $row) {
            $cur = [];
            foreach ($row->c as $cell) {
                $v = (string)$cell->v;
                if ((string)$cell['t'] === 's') $v = $ss[(int)$v] ?? '';
                $ref = (string)$cell['r'];
                $ci = 0;
                for ($i = 0; $i < strlen($ref); $i++) {
                    if (ctype_alpha($ref[$i])) $ci = $ci * 26 + (ord($ref[$i]) - 64);
                    else break;
                }
                $cur[$ci - 1] = $v;
            }
            if (!empty($cur)) {
                $mx = max(array_keys($cur));
                for ($i = 0; $i <= $mx; $i++) if (!isset($cur[$i])) $cur[$i] = '';
                ksort($cur);
                $rows[] = array_values($cur);
            }
        }
    }
    $zip->close();
    return $rows;
}

function parseXlsxOld($fp)
{
    $zip = new ZipArchive();
    $zip->open($fp);
    $ss = [];
    if ($sd = $zip->getFromName('xl/sharedStrings.xml')) {
        $xml = new SimpleXMLElement($sd);
        foreach ($xml->si as $si) {
            // OLD buggy code:
            $ss[] = (string)($si->t ?? $si->r->t ?? "");
        }
    }
    $rows = [];
    if ($sd = $zip->getFromName('xl/worksheets/sheet1.xml')) {
        $xml = new SimpleXMLElement($sd);
        foreach ($xml->sheetData->row as $row) {
            $cur = [];
            foreach ($row->c as $cell) {
                $v = (string)$cell->v;
                if ((string)$cell['t'] === 's') $v = $ss[(int)$v] ?? '';
                $ref = (string)$cell['r'];
                $ci = 0;
                for ($i = 0; $i < strlen($ref); $i++) {
                    if (ctype_alpha($ref[$i])) $ci = $ci * 26 + (ord($ref[$i]) - 64);
                    else break;
                }
                $cur[$ci - 1] = $v;
            }
            if (!empty($cur)) {
                $mx = max(array_keys($cur));
                for ($i = 0; $i <= $mx; $i++) if (!isset($cur[$i])) $cur[$i] = '';
                ksort($cur);
                $rows[] = array_values($cur);
            }
        }
    }
    $zip->close();
    return $rows;
}

$fp = 'C:/Users/star_/OneDrive/Escritorio/Prueba 2.xlsx';

// --- OLD ---
$old = parseXlsxOld($fp);
$oldH = array_shift($old);
$oldNonEmpty = 0;
$nameColOld = 4; // known from diagnostics
foreach ($old as $r) {
    if (!empty(array_filter($r)) && !empty(trim($r[$nameColOld] ?? ''))) $oldNonEmpty++;
}

// --- FIXED ---
$new = parseXlsxFixed($fp);
$newH = array_shift($new);
$newNonEmpty = 0;
foreach ($new as $r) {
    if (!empty(array_filter($r)) && !empty(trim($r[$nameColOld] ?? ''))) $newNonEmpty++;
}

echo "=== PARSER COMPARISON ===\n";
echo "OLD parser: $oldNonEmpty rows with valid name (col4)\n";
echo "NEW parser: $newNonEmpty rows with valid name (col4)\n";
echo "Difference: " . ($newNonEmpty - $oldNonEmpty) . " rows recovered\n\n";

// Show a few cells that differ between old and new
$diffCount = 0;
foreach ($new as $i => $nr) {
    if (!isset($old[$i])) continue;
    $or = $old[$i];
    for ($c = 0; $c < min(count($nr), count($or)); $c++) {
        if ($nr[$c] !== $or[$c] && $diffCount < 5) {
            echo "Row " . ($i + 2) . " col $c: OLD='" . substr($or[$c], 0, 40) . "' → NEW='" . substr($nr[$c], 0, 40) . "'\n";
            $diffCount++;
        }
    }
}
echo "\nTotal differing cells: $diffCount (showing first 5)\n";

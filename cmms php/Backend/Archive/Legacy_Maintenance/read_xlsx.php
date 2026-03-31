<?php
$file = 'c:\\Users\\star_\\OneDrive\\Escritorio\\Prueba 2.xlsx';
$zip = new ZipArchive;
if($zip->open($file) === true){
    echo "Archivo XLSX abierto correctamente.\n";
    $ss=[];
    if($x=$zip->getFromName('xl/sharedStrings.xml')){
        $xml = simplexml_load_string($x);
        foreach($xml->si as $s) {
            $ss[]=(string)$s->t;
        }
    }
    if($y=$zip->getFromName('xl/worksheets/sheet1.xml')){
        $xml = simplexml_load_string($y);
        $firstRow = $xml->sheetData->row[0];
        $h=[];
        foreach($firstRow->c as $c){
            $v=(string)$c->v;
            if((string)$c['t']=='s') $v=$ss[(int)$v];
            $h[]=$v;
        }
        echo "CABECERAS EN XLSX:\n";
        print_r($h);
    } else {
        echo "No se encontro sheet1 en el XLSX\n";
    }
    $zip->close();
} else {
    echo "Falló la apertura del archivo XLSX\n";
}
?>

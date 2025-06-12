<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Colonnes attendues
$headers = [
    'nom', 'prenom', 'email', 'telephone', 'date_naissance', 'date_embauche', 'poste', 'statut'
];

// Créer un nouveau classeur
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('100 Employés');

// Mettre en forme les en-têtes (gras, centré)
$sheet->getStyle('A1:H1')->getFont()->setBold(true);
$sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Écrire les en-têtes
$col = 1;
foreach ($headers as $header) {
    $sheet->setCellValueByColumnAndRow($col++, 1, $header);
}

// Générer 100 employés fictifs
$count = 0;
for ($i = 1; $i <= 100; $i++) {
    $row = $i + 1;
    $sheet->setCellValueByColumnAndRow(1, $row, "Nom$i");
    $sheet->setCellValueByColumnAndRow(2, $row, "Prenom$i");
    $sheet->setCellValueByColumnAndRow(3, $row, "employe$i@example.com");
    $sheet->setCellValueByColumnAndRow(4, $row, "0600000" . str_pad($i, 3, '0', STR_PAD_LEFT));
    $sheet->setCellValueByColumnAndRow(5, $row, date('Y-m-d', strtotime("-" . (20 + ($i % 20)) . " years")));
    $sheet->setCellValueByColumnAndRow(6, $row, date('Y-m-d', strtotime("-" . ($i % 10) . " years")));
    $sheet->setCellValueByColumnAndRow(7, $row, "Poste" . (($i % 5) + 1));
    $sheet->setCellValueByColumnAndRow(8, $row, "actif");
    $count++;
    
    // Afficher progression tous les 10 employés
    if ($i % 10 == 0) {
        echo "Génération en cours: $i employés créés\n";
    }
}

// Ajuster la largeur des colonnes
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Supprimer l'ancien fichier s'il existe
$filepath = __DIR__ . '/storage/app/modele_employes_100.xlsx';
if (file_exists($filepath)) {
    unlink($filepath);
    echo "Ancien fichier supprimé.\n";
}

// Sauvegarder le nouveau fichier
$writer = new Xlsx($spreadsheet);
$writer->save($filepath);

// Vérifier que le fichier a bien été créé
if (file_exists($filepath)) {
    // Charger le fichier pour vérifier le nombre de lignes
    $verifySpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
    $verifySheet = $verifySpreadsheet->getActiveSheet();
    $highestRow = $verifySheet->getHighestRow();
    $actualRows = $highestRow - 1; // Soustraire l'en-tête
    
    echo "✅ Fichier généré: $filepath\n";
    echo "✅ Nombre de lignes générées: $count\n";
    echo "✅ Nombre de lignes dans le fichier: $actualRows (hors en-tête)\n";
    
    if ($actualRows != 100) {
        echo "⚠️ ATTENTION: Le nombre de lignes ne correspond pas à 100!\n";
    } else {
        echo "✅ SUCCÈS: Le fichier contient bien 100 employés.\n";
    }
} else {
    echo "❌ ERREUR: Le fichier n'a pas pu être créé!\n";
} 
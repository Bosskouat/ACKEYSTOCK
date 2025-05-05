<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

try {
    $db = Database::getInstance();
    
    // Vérifier si la table existe
    $tableExists = $db->query("SHOW TABLES LIKE 'categories'")->rowCount() > 0;
    echo "Table categories existe: " . ($tableExists ? "Oui" : "Non") . "\n";

    if (!$tableExists) {
        // Créer la table si elle n'existe pas
        $db->exec("CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(255) NOT NULL,
            description TEXT
        )");
        echo "Table categories créée\n";
    }

    // Insérer des données de test
    $categories = [
        ['nom' => 'Informatique', 'description' => 'Matériel informatique et accessoires'],
        ['nom' => 'Bureautique', 'description' => 'Fournitures de bureau'],
        ['nom' => 'Mobilier', 'description' => 'Mobilier de bureau'],
        ['nom' => 'Papeterie', 'description' => 'Articles de papeterie']
    ];

    $stmt = $db->prepare("INSERT INTO categories (nom, description) VALUES (?, ?)");
    
    foreach ($categories as $cat) {
        $stmt->execute([$cat['nom'], $cat['description']]);
        echo "Catégorie ajoutée: {$cat['nom']}\n";
    }

    echo "Insertion terminée avec succès\n";

} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
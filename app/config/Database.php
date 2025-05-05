<?php
namespace App\Config;

class Database {
    private static $instance = null;
    private $connection = null;

    private $host = 'localhost';
    private $dbname = 'ackeystock';
    private $username = 'root'; // Vérifiez que c'est le bon utilisateur
    private $password = ''; // Vérifiez que c'est le bon mot de passe
    private $charset = 'utf8mb4';

    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $this->connection = new \PDO($dsn, $this->username, $this->password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ]);
            
            // Sélectionner explicitement la base de données
            $this->connection->exec("USE {$this->dbname}");
            
        } catch(\PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }
}
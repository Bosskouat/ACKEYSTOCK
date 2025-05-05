-- Création de la base
CREATE DATABASE IF NOT EXISTS ackeystock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ackeystock;

-- Table utilisateurs
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100),
    email VARCHAR(150) UNIQUE,
    mot_de_passe VARCHAR(255),
    role ENUM('admin', 'gestionnaire', 'employe'),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table fournisseurs
CREATE TABLE fournisseurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100),
    email VARCHAR(150),
    telephone VARCHAR(20),
    adresse TEXT
);

-- Table catégories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) UNIQUE,
    description TEXT
);

-- Table produits
CREATE TABLE produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150),
    quantite INT,
    seuil INT,
    description TEXT,
    categorie_id INT,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Table commandes
CREATE TABLE IF NOT EXISTS commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(50) NOT NULL,
    date_commande DATETIME NOT NULL,
    fournisseur_id INT NOT NULL,
    statut ENUM('en_attente', 'livree') NOT NULL DEFAULT 'en_attente',
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id)
);

-- Table commande_produits
CREATE TABLE IF NOT EXISTS commande_produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    nom_produit VARCHAR(255) NOT NULL,
    quantite INT NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id)
);

-- Entrées de stock
CREATE TABLE IF NOT EXISTS entrees_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    fournisseur_id INT NOT NULL,
    quantite INT NOT NULL,
    date_entree DATE NOT NULL,
    reference VARCHAR(50) NULL,
    FOREIGN KEY (produit_id) REFERENCES produits(id),
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id)
);

-- Sorties de stock
CREATE TABLE sorties_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    motif VARCHAR(150),
    date_sortie DATE NOT NULL,
    FOREIGN KEY (produit_id) REFERENCES produits(id)
);

-- Notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT,
    type ENUM('info', 'succes', 'alerte'),
    utilisateur_id INT,
    date_notif DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

-- Journal d’actions
CREATE TABLE journal_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT,
    action TEXT,
    cible VARCHAR(100),
    date_action DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

-- Messages de contact
CREATE TABLE messages_contact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100),
    email VARCHAR(150),
    message TEXT,
    date_message DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reinitialisations_mdp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT,
    token VARCHAR(191) UNIQUE,
    expiration DATETIME,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- Table des alertes
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    lu BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (produit_id) REFERENCES produits(id)
);


-- 🔽 Données de base

-- Utilisateurs
INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES
('Jean Dupont', 'admin@ackeystock.com', 'adminhash', 'admin'),
('Sophie Martin', 'gestionnaire@ackeystock.com', 'gesthash', 'gestionnaire'),
('Kevin Lopez', 'kevin@ackeystock.com', 'employehash', 'employe'),
('Laura Marchand', 'laura@carrefour.com', 'gestionnairehash', 'gestionnaire'),
('Benoît Lefebvre', 'benoit@carrefour.com', 'employehash', 'employe');

-- Fournisseurs
INSERT INTO fournisseurs (nom, email, telephone, adresse) VALUES
('Fournisseur A', 'contact@fourna.com', '+33 1 45 00 00 01', '10 rue des Marchés, Paris'),
('Fournisseur B', 'contact@fournb.com', '+33 1 45 00 00 02', '25 avenue Lyon, Lyon'),
('Nestlé France', 'contact@nestle.fr', '+33 1 40 00 00 01', 'Avenue Nestlé, Issy-les-Moulineaux'),
('Unilever Logistique', 'logistique@unilever.fr', '+33 1 45 23 11 90', 'Zone industrielle, Lille');

-- Catégories
INSERT INTO categories (nom, description) VALUES
('Papeterie', 'Fournitures de bureau'),
('Informatique', 'Matériel informatique et accessoires'),
('Bureautique', 'Fournitures de bureau'),
('Mobilier', 'Mobilier de bureau'),
('Boissons', 'Sodas, jus et eaux'),
('Produits laitiers', 'Lait, yaourts, beurre'),
('Épicerie salée', 'Pâtes, riz, conserves');

-- Produits
INSERT INTO produits (nom, quantite, seuil, description, categorie_id) VALUES
('Stylo BIC', 120, 20, 'Stylo à bille bleu', 1),
('Clavier Logitech', 15, 5, 'Clavier sans fil', 2),
('Coca-Cola 1.5L', 350, 50, 'Bouteille plastique', 3),
('Eau minérale Evian 1L', 500, 100, 'Bouteille verre consignée', 3),
('Riz Basmati 1kg', 220, 40, 'Sachet longue conservation', 5),
('Yaourt nature x12', 180, 30, 'Pack de 12 pots - marque Carrefour', 4);

-- Commandes
INSERT INTO commandes (reference, date_commande, statut, fournisseur_id) VALUES
('CMD001', '2024-12-01', 'livree', 1),
('CMD002', '2025-01-15', 'en_attente', 2),
('CMD003', '2025-03-05', 'livree', 3),
('CMD004', '2025-03-10', 'en_attente', 4);

-- Commandes produits
INSERT INTO commandes_produits (commande_id, produit_id, quantite) VALUES
(1, 1, 100),
(2, 2, 5),
(3, 3, 200),
(3, 4, 300),
(4, 5, 100),
(4, 6, 150);

-- Entrées stock
INSERT INTO entrees_stock (produit_id, fournisseur_id, quantite, date_entree) VALUES
(1, 1, 100, '2024-12-02'),
(2, 2, 5, '2025-01-16'),
(3, 3, 200, '2025-03-06'),
(4, 3, 300, '2025-03-06'),
(5, 4, 100, '2025-03-11');

-- Sorties stock
INSERT INTO sorties_stock (produit_id, quantite, motif, date_sortie) VALUES
(1, 15, 'Consommation interne', '2025-01-20'),
(3, 25, 'Vente directe', '2025-03-12'),
(4, 40, 'Vente directe', '2025-03-12'),
(5, 10, 'Consommation interne', '2025-03-12');

-- Notifications
INSERT INTO notifications (message, type, utilisateur_id) VALUES
('Stock critique détecté pour Clavier Logitech', 'alerte', 2),
('Nouvelle commande CMD002 enregistrée', 'info', 1),
('Stock faible : Riz Basmati', 'alerte', 1),
('Commande CMD004 en attente de validation', 'info', 2);

-- Journal d’actions
INSERT INTO journal_actions (utilisateur_id, action, cible) VALUES
(1, 'Ajout d\'une commande CMD002', 'commandes'),
(2, 'Modification du stock produit ID=2', 'produits'),
(1, 'Ajout d\'un produit Coca-Cola 1.5L', 'produits'),
(3, 'Validation de la commande CMD003', 'commandes');

-- Contact
INSERT INTO messages_contact (nom, email, message) VALUES
('Alice Duval', 'alice@example.com', 'Bonjour, je souhaite poser une question sur les délais de livraison.');

-- Réinitialisation MDP
INSERT INTO reinitialisations_mdp (utilisateur_id, token, expiration) VALUES
(2, 'abc123tokentest', '2025-06-01 23:59:59');

-- Vérifier l'existence des tables
SHOW TABLES;

-- Vérifier le contenu des tables
SELECT COUNT(*) as nb_sorties FROM sorties_stock;
SELECT COUNT(*) as nb_entrees FROM entrees_stock;
SELECT COUNT(*) as nb_produits FROM produits;

-- Vérifier les mouvements du mois en cours
SELECT s.*, p.nom as produit_nom 
FROM sorties_stock s
LEFT JOIN produits p ON s.produit_id = p.id
WHERE MONTH(s.date_sortie) = MONTH(CURRENT_DATE())
AND YEAR(s.date_sortie) = YEAR(CURRENT_DATE());

-- Vérifier les produits sous seuil d'alerte
SELECT p.*, c.nom as categorie_nom 
FROM produits p 
LEFT JOIN categories c ON p.categorie_id = c.id 
WHERE p.quantite <= p.seuil;

-- Corrige tous les rôles en base de données
UPDATE utilisateurs SET role = 'admin' WHERE role IN ('Admin', 'ADMIN', ' admin ', 'administrator');
UPDATE utilisateurs SET role = 'gestionnaire' WHERE role IN ('Gestionnaire', 'GESTIONNAIRE', ' gestionnaire ', 'manager');
UPDATE utilisateurs SET role = 'employe' WHERE role IN ('Employe', 'EMPLOYE', ' employe ', 'employee', 'Employé', 'employé');

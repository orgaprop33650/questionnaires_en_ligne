CREATE DATABASE IF NOT EXISTS questionnaires_en_ligne
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE questionnaires_en_ligne;

-- Comptes formateurs
CREATE TABLE formateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Quiz créés par les formateurs
CREATE TABLE questionnaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formateur_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (formateur_id) REFERENCES formateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Questions d'un questionnaire
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    questionnaire_id INT NOT NULL,
    texte TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    type ENUM('qcm','vrai_faux','libre') NOT NULL DEFAULT 'qcm',
    duree_secondes INT NOT NULL DEFAULT 30,
    ordre INT NOT NULL DEFAULT 0,
    FOREIGN KEY (questionnaire_id) REFERENCES questionnaires(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Choix pour QCM / vrai_faux
CREATE TABLE reponses_possibles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    texte VARCHAR(500) NOT NULL,
    est_correcte TINYINT NOT NULL DEFAULT 0,
    ordre INT NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sessions live
CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    questionnaire_id INT NOT NULL,
    code_acces VARCHAR(6) NOT NULL UNIQUE,
    statut ENUM('attente','en_cours','terminee') NOT NULL DEFAULT 'attente',
    question_active_id INT DEFAULT NULL,
    question_demarree_a DATETIME DEFAULT NULL,
    date_debut DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_fin DATETIME DEFAULT NULL,
    FOREIGN KEY (questionnaire_id) REFERENCES questionnaires(id) ON DELETE CASCADE,
    FOREIGN KEY (question_active_id) REFERENCES questions(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Stagiaires connectés à une session
CREATE TABLE participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    pseudo VARCHAR(50) NOT NULL,
    date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Réponses des participants
CREATE TABLE reponses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participant_id INT NOT NULL,
    question_id INT NOT NULL,
    reponse_possible_id INT DEFAULT NULL,
    texte_libre TEXT DEFAULT NULL,
    date_reponse DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reponse (participant_id, question_id),
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (reponse_possible_id) REFERENCES reponses_possibles(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insérer un formateur de test (mot de passe: admin123)
INSERT INTO formateurs (nom, email, mot_de_passe) VALUES
('Formateur Test', 'admin@test.com', '$2y$10$cbcnBkEPvUTNnskEJjzXP.13RVrnn1qLhonhDUnWDfwQ8Kai03Xeq');

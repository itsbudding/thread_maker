<?php

class Database{

	private static ?PDO $connexion = null;

	public static function connexion(): PDO{
		if(self::$connexion === null){
			$dossier = __DIR__."/../data";
			if(!is_dir($dossier)){
				mkdir($dossier, 0770, true);
			}

			$pdo = new PDO("sqlite:".$dossier."/thread_maker.sqlite");
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			$pdo->exec("PRAGMA foreign_keys = ON;");

			self::migrer($pdo);
			self::$connexion = $pdo;
		}
		return self::$connexion;
	}

	private static function migrer(PDO $pdo): void{
		$pdo->exec("
			CREATE TABLE IF NOT EXISTS familles (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				nom TEXT NOT NULL,
				slug TEXT NOT NULL UNIQUE,
				logo TEXT
			)
		");

		$pdo->exec("
			CREATE TABLE IF NOT EXISTS comptes (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				famille_id INTEGER NOT NULL REFERENCES familles(id) ON DELETE CASCADE,
				reseau TEXT NOT NULL CHECK(reseau IN ('mastodon', 'pixelfed', 'bluesky', 'instagram')),
				identifiant TEXT NOT NULL,
				instance_url TEXT,
				jeton_chiffre TEXT NOT NULL,
				cree_le TEXT NOT NULL DEFAULT (datetime('now'))
			)
		");

		$pdo->exec("
			CREATE TABLE IF NOT EXISTS publications (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				compte_id INTEGER REFERENCES comptes(id) ON DELETE SET NULL,
				reseau TEXT NOT NULL,
				statut TEXT NOT NULL CHECK(statut IN ('succes', 'erreur')),
				message_erreur TEXT,
				id_externe TEXT,
				cle_idempotence TEXT,
				cree_le TEXT NOT NULL DEFAULT (datetime('now'))
			)
		");
		self::ajouterColonneSiAbsente($pdo, "publications", "cle_idempotence", "TEXT");

		$pdo->exec("
			CREATE TABLE IF NOT EXISTS publications_planifiees (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				compte_id INTEGER NOT NULL REFERENCES comptes(id) ON DELETE CASCADE,
				reseau TEXT NOT NULL,
				texte TEXT NOT NULL,
				images TEXT,
				images_alt TEXT,
				date_prevue TEXT NOT NULL,
				statut TEXT NOT NULL DEFAULT 'en_attente' CHECK(statut IN ('en_attente', 'publiee', 'erreur', 'annulee')),
				message_erreur TEXT,
				cree_le TEXT NOT NULL DEFAULT (datetime('now'))
			)
		");
	}

	// Migration additive simple : ajoute une colonne à une table existante si elle n'y est pas déjà
	// (permet de faire évoluer le schéma sans perdre les données d'une base déjà en place).
	private static function ajouterColonneSiAbsente(PDO $pdo, string $table, string $colonne, string $type): void{
		foreach($pdo->query("PRAGMA table_info($table)")->fetchAll() as $colonneExistante){
			if($colonneExistante["name"] === $colonne){
				return;
			}
		}
		$pdo->exec("ALTER TABLE $table ADD COLUMN $colonne $type");
	}

}

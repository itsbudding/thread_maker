<?php

// Authentification par mot de passe partagé (session PHP) + protection CSRF minimale.
class Auth{

	public static function demarrerSession(): void{
		if(session_status() !== PHP_SESSION_ACTIVE){
			session_set_cookie_params([
				"httponly" => true,
				"samesite" => "Lax",
			]);
			session_start();
		}
	}

	public static function estConnecte(): bool{
		self::demarrerSession();
		return isset($_SESSION["connecte"]) && $_SESSION["connecte"] === true;
	}

	public static function exigerConnexion(): void{
		if(!self::estConnecte()){
			$retour = $_SERVER["REQUEST_URI"] ?? "./index.php";
			header("Location: ./login.php?retour=".urlencode($retour));
			exit;
		}
	}

	public static function connecter(string $motDePasse): bool{
		$empreinte = getenv("APP_PASSWORD_HASH");
		if($empreinte === false || $empreinte === "" || !password_verify($motDePasse, $empreinte)){
			return false;
		}

		self::demarrerSession();
		session_regenerate_id(true);
		$_SESSION["connecte"] = true;
		return true;
	}

	public static function deconnecter(): void{
		self::demarrerSession();
		$_SESSION = [];
		session_destroy();
	}

	public static function jetonCsrf(): string{
		self::demarrerSession();
		if(!isset($_SESSION["jeton_csrf"])){
			$_SESSION["jeton_csrf"] = bin2hex(random_bytes(32));
		}
		return $_SESSION["jeton_csrf"];
	}

	public static function verifierCsrf(?string $jeton): bool{
		self::demarrerSession();
		return isset($_SESSION["jeton_csrf"]) && is_string($jeton) && hash_equals($_SESSION["jeton_csrf"], $jeton);
	}

}

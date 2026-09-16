<?php

// Chiffrement symétrique des jetons d'API stockés en base (libsodium, inclus nativement en PHP).
class Crypto{

	private static function cle(): string{
		$cleBase64 = getenv("APP_ENCRYPTION_KEY");
		if($cleBase64 === false || $cleBase64 === ""){
			throw new RuntimeException("APP_ENCRYPTION_KEY n'est pas définie.");
		}

		$cle = base64_decode($cleBase64, true);
		if($cle === false || strlen($cle) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES){
			throw new RuntimeException(
				"APP_ENCRYPTION_KEY invalide : elle doit être une clé de ".
				SODIUM_CRYPTO_SECRETBOX_KEYBYTES." octets encodée en base64."
			);
		}

		return $cle;
	}

	public static function chiffrer(string $texteClair): string{
		$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$chiffre = sodium_crypto_secretbox($texteClair, $nonce, self::cle());
		return base64_encode($nonce.$chiffre);
	}

	public static function dechiffrer(string $valeurChiffree): string{
		$decode = base64_decode($valeurChiffree, true);
		if($decode === false || strlen($decode) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES){
			throw new RuntimeException("Valeur chiffrée invalide.");
		}

		$nonce = substr($decode, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$chiffre = substr($decode, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

		$texteClair = sodium_crypto_secretbox_open($chiffre, $nonce, self::cle());
		if($texteClair === false){
			throw new RuntimeException("Impossible de déchiffrer la valeur (clé invalide ou donnée corrompue).");
		}

		return $texteClair;
	}

}

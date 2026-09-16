<?php

interface PublisherInterface{

	// $compte contient au minimum : reseau, identifiant, instance_url, jeton (déchiffré).
	public function publier(array $compte, string $texte, ?string $cheminImage): PublishResult;

}

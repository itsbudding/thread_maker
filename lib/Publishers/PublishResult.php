<?php

class PublishResult{

	public function __construct(
		public readonly bool $succes,
		public readonly ?string $idExterne = null,
		public readonly ?string $messageErreur = null
	){}

}

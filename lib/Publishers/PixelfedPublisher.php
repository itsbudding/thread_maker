<?php

require_once __DIR__."/MastodonPublisher.php";

// Pixelfed expose une API compatible Mastodon (statuses/media) : même client, sous-classé
// uniquement pour garder un type distinct par réseau dans publier.php.
class PixelfedPublisher extends MastodonPublisher{}

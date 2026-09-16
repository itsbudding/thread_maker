<?php

require_once __DIR__."/lib/bootstrap.php";

Auth::deconnecter();
header("Location: ./login.php");
exit;

<?php

	isset($_POST["txta"]) ? $texte = $_POST["txta"] : $texte = "";
	echo "<pre>"; print_r($texte); echo "</pre>"; 
	// $texte = "";
	
	
	// $_POST['txta'] = nl2br($_POST['txta']);
	// $_POST['txta'] = str_replace("<br />", "¤", $_POST['txta']);
	
	// $_POST['txta'] = str_replace(array("\r\n", "\n"), "¤", $_POST['txta']);
	// $texte = "azerty\n\nazerty";
	// $texte = "azerty".PHP_EOL.PHP_EOL."azerty";
	
	// echo "<pre>"; print_r($_POST['txta'], 280); echo "</pre>"; 
	echo "<pre>"; print_r(str_split($texte, 280)); echo "</pre>"; 
	
	// if(isset($texte)) print_r($texte);
	
	// $prefixe = "";
	// echo "--".trim(trim($prefixe)." ")." ..."."--<br/>";
	// echo "--".mb_strlen("🧵⬇️")."--<br/>";
	
	// echo "<pre>$content</pre>";
	echo "strlen : ".strlen($texte)."<br/>";
	echo "mb_strlen : ".mb_strlen($texte, "UTF-8")."<br/>";
	echo "grapheme_strlen : ".grapheme_strlen($texte)."<br/>";
	// echo "grapheme_strlen \r\r: ".grapheme_strlen("\r\r")."<br/>";
	// echo "grapheme_strlen php_eol : ".grapheme_strlen(PHP_EOL.PHP_EOL)."<br/>";
	echo "iconv_strlen : ".iconv_strlen($texte, "UTF-8")."<br/>";
	// echo "nb \n : ".substr_count( $texte, "\n\r" )."<br/>";
	// echo "mb_strlen - nb \n : ".(mb_strlen($texte) - (int)(1.5 * substr_count( $texte, "\n\r" )));
	echo "<hr/>";
	
	

?>

<html>
<body>

<form action="./test.php" method="POST">
	<textarea id="txta" name="txta" rows="10" cols="50"><?php echo $texte ?></textarea><br/>
	<button type="submit">Soumettre</button>
</form>

</body>
</html>
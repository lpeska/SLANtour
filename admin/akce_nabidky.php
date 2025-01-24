<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>

	<meta http-equiv="Content-Type" content="text/html; charset=windows-1250"/> 		
	<meta name="copyright" content="&copy; Slantour"/>
	<meta http-equiv= "pragma" content="no-cache" />
	<meta name="robots" content="noindex,noFOLLOW" />
	<link rel="stylesheet" type="text/css" href="styly.css" media="all"/>
</head>
<body>
<h1>Administrace systému RSCK - tvorba obrazku</h1>

<form action="nabidky_zpracovani.php" method="post" enctype="multipart/form-data">
  Select a file to upload for processing<br>
  <input type="file" name="podklad" value="Podkladový obrázek"> co nejblíže poměru šířka/výška 5:2<br>
  Velikost slevy/akce<input type="text" name="sleva" value=""> zobrazí se v bublině vpravo nahoře<br>        
  Velikost slevy/akce<input type="text" name="text" value=""> zobrazí se dole pod obrázkem<br>         
  <input type="submit" value="Vytvořit">
</form>

</body>
</html>
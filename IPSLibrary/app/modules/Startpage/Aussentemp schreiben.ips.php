<?

 //Fügen Sie hier Ihren Skriptquellcode ein

$temperatur=GetValue(11477);
$innentemperatur=GetValue(21157);
$bilderverzeichnis=IPS_GetKernelDir()."webfront\\user\\pictures\\";

$file=array();
$handle=opendir ($bilderverzeichnis);
echo "Verzeichnisinhalt:<br>";
$i=0;
while ($datei = readdir ($handle))
	{
	$i++;
 	$file[$i]=$datei;
	}
closedir($handle);

print_r($file);

$wert='<!DOCTYPE html >
<html lang="de">

<head>

  <meta charset="UTF-8"/>
  <title>Design über CSS pur - Beispiel</title>

  <style type="text/css">

	html, body {
     font: 100% Arial, Helvetica, sans-serif;
	}

   steuerung {
	  color:red;
     background-color: #c1c1c1;
	  height:200px;
	  font-size: 148px;
   }
   
	innen {
	  color:black;
     background-color: #f1f1f1;
	  height:100px;
	  font-size: 48px;
   }

	infotext {
	  color:white;
	  height:100px;
	  font-size: 12px;
   }
   
  </style>
  
</head>
<body>

<table border="1">
	   <tr>
		  <td> <infotext>Bilder auf: '.$bilderverzeichnis.$file[7].'</infotext> </td>
  		  <td> <infotext>Bilder auf: '.$bilderverzeichnis.$file[7].'</infotext> </td>
		</tr>
	   <tr>
		  <td> <img src="user/pictures/'.$file[7].'" width="480" height="270" alt="Heute" align="center"> </td>
		  <td> <img src="user/pictures/'.$file[8].'" width="480" height="270" alt="Heute" align="center"> </td>
		</tr>
</table>

<table border="0" height="220px" bgcolor="#c1c1c1" cellspacing="10">
		  	<tr>
		    <th>Bild</th>
		    <th>Aussentemperatur</th>
		    <th>Tabelle</th>
		    <th>Bild</th>
		  	</tr>
		  	<tr>
		    <td><img src="user/icons/Start/Aussenthermometer.jpg" alt="Aussentemperatur"></td>
			 <td> <table border="0" bgcolor="#f1f1f1">
			   <tr>
				  <td> <img src="'.GetValue(29961).'" alt="Heute" align="center"> </td>
				</tr>
			   <tr>
				  <td> <img src="'.GetValue(57563).'" alt="Morgen" align="center"> </td>
				</tr>
			   <tr>
				  <td> <img src="'.GetValue(31348).'" alt="Über" align="center"> </td>
				</tr>
			 </table> </td>
			 <td> <steuerung>
			   '.$temperatur.'
			 </steuerung> </td>
			 <td> <table border="0" bgcolor="#f1f1f1">
			   <tr> <td> <img src="user/icons/Start/FHZ.png" alt="Innentemperatur">  </td> </tr>
			 	<tr> <td> <innen> Innen </innen> </td> </tr>
				<tr> <td> <innen>'.$innentemperatur.'</innen> </td> </tr>
		    </table> </td>
		  	</tr>
</table>

</body>
</html>
';

SetValue(36678,$wert);


$wert='<!DOCTYPE html >
<html lang="de">

<head>

  <meta charset="UTF-8"/>
  <title>Design über CSS pur - Beispiel</title>

  <style type="text/css">

   kopf {
     background-color: red;
	  height:120px;
   }

   strg {
	  height:280px;
	  color:white;
	  font-size: 8em;
	  font-style: italic;
   }

  </style>

</head>
<body>

	<h1>Tabelle mit Gitternetzlinien</h1>

<table border="1">
  	<tr>
    <th></th>
    <th>Aussentemperatur</th>
    <th>M&uuml;nchen</th>
  	</tr>
  	<tr>
    <td><img src="user/icons/Start/Aussenthermometer.jpg" alt="Tanzmaus"></td>
    <td><strg>'.$temperatur.'</strg></td>
    <td>Bierdampf</td>
  	</tr>
  	<tr>
    <td>Buletten</td>
    <td>Frikadellen</td>
    <td>Fleischpflanzerl</td>
  	</tr>
</table>


<kopf>Säulenbauten des 20. Jahrhunderts</kopf>


<p><img src="user/icons/Start/Aussenthermometer.jpg" alt="Tanzmaus"></p>

<div id="strg">
Temperatur ist '.$temperatur.'
</div>

<div id="schatten">
Hier kommt der Schatten später
</div>

<div id="inhalt">
Der eigentliche Inhaltsbereich
</div>

</body>
</html>
';

SetValue(34569,$wert);

?>

<?

 //Fügen Sie hier Ihren Skriptquellcode ein


/********************************************* CONFIG *******************************************************/

//Include_once(IPS_GetKernelDir()."../IPS-Config/AllgemeineDefinitionen.php");
Include_once(IPS_GetKernelDir()."scripts\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');

$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');
IPS_SetScriptTimer($_IPS['SELF'], 8*60);  /* wenn keine Veränderung einer Variablen trotzdem updaten */

$temperatur=temperatur();
$innentemperatur=innentemperatur();
$bilderverzeichnis=IPS_GetKernelDir()."webfront\\user\\pictures\\";

$StartPageTypeID = CreateVariableByName($parentid, "Startpagetype", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */

$variableIdHTML  = CreateVariable("Uebersicht", 3 /*String*/,  $parentid, 40, '~HTMLBox', null,null,"");

$vid = @IPS_GetVariableIDByName("SwitchScreen",$parentid);

/******************************************* INIT ******************************************************/

$file=array();
$handle=opendir ($bilderverzeichnis);
//echo "Verzeichnisinhalt:<br>";
$i=0;
while ($datei = readdir ($handle))
	{
	$i++;
 	$file[$i]=$datei;
	}
closedir($handle);
$maxcount=count($file);
$showfile=rand(3,$maxcount-1);
//echo $maxcount."  ".$showfile."\n";;
//print_r($file);

/**************************************** PROGRAM *********************************************************/


 if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);

	switch ($_IPS['VALUE'])
		{
		case "3":  /* Bildschirmschoner */
			SetValue($StartPageTypeID,1);
			break;


		case "2":  /* Wetterstation */
			SetValue($StartPageTypeID,2);
			break;

		case "1":  /* Full Screen ein */
		case "0":  /* Full Screen aus */

			IPS_ExecuteEX("c:/Scripts/nircmd.exe", "sendkeypress F11", false, false, 1);

			break;
		}
	}

SetValue($variableIdHTML,StartPageWrite(GetValue($StartPageTypeID)));


/**************************************** FUNCTIONS *********************************************************/

function StartPageWrite($PageType)
	{
	
	global $temperatur, $innentemperatur, $file, $showfile;

$todayID = @IPS_GetObjectIDByName("Program",0);
$todayID = @IPS_GetObjectIDByName("IPSLibrary",$todayID);
$todayID = @IPS_GetObjectIDByName("data",$todayID);
$todayID = @IPS_GetObjectIDByName("modules",$todayID);
$todayID = @IPS_GetObjectIDByName("Weather",$todayID);
$todayID = @IPS_GetObjectIDByName("IPSWeatherForcastAT",$todayID);
$today = GetValue(@IPS_GetObjectIDByName("TodayIcon",$todayID));
$todayTempMin = GetValue(@IPS_GetObjectIDByName("TodayTempMin",$todayID));
$todayTempMax = GetValue(@IPS_GetObjectIDByName("TodayTempMax",$todayID));
$tomorrow = GetValue(@IPS_GetObjectIDByName("TomorrowIcon",$todayID));
$tomorrowTempMin = GetValue(@IPS_GetObjectIDByName("TomorrowTempMin",$todayID));
$tomorrowTempMax = GetValue(@IPS_GetObjectIDByName("TomorrowTempMax",$todayID));
$tomorrow1 = GetValue(@IPS_GetObjectIDByName("Tomorrow1Icon",$todayID));
$tomorrow1TempMin = GetValue(@IPS_GetObjectIDByName("Tomorrow1TempMin",$todayID));
$tomorrow1TempMax = GetValue(@IPS_GetObjectIDByName("Tomorrow1TempMax",$todayID));
$tomorrow2 = GetValue(@IPS_GetObjectIDByName("Tomorrow2Icon",$todayID));
$tomorrow2TempMin = GetValue(@IPS_GetObjectIDByName("Tomorrow2TempMin",$todayID));
$tomorrow2TempMax = GetValue(@IPS_GetObjectIDByName("Tomorrow2TempMax",$todayID));


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
	  color:black;
     background-color: #c1c1c1;
	  font-size: 12em;
   }

	innen {
	  color:black;
     background-color: #ffffff;
	  height:100px;
	  font-size: 80px;
   }

	aussen {
	  color:black;
     background-color: #c1c1c1;
	  height:100px;
	  font-size: 80px;
   }

	temperatur {
	  color:black;
	  height:100px;
	  font-size: 28px;
	  align:center;
   }

	#temp td {
		background-color:#ffefef;
	}


	infotext {
	  color:white;
	  height:100px;
	  font-size: 12px;
   }

  </style>

</head>
<body>
';
if ($PageType==2)
{
$wert.='

<table <table border="0" height="220px" bgcolor="#c1c1c1" cellspacing="10">

  	<tr>
     <td>
		<table border="0" bgcolor="#f1f1f1">
			   <tr>
				  <td align="center"> <img src="'.$today.'" alt="Heute" > </td>
				</tr>
			   <tr>
				  <td align="center"> <img src="'.$tomorrow.'" alt="Heute" > </td>
				</tr>
			   <tr>
				  <td align="center"> <img src="'.$tomorrow1.'" alt="Heute" > </td>
				</tr>
			 </table>
     </td>
    <td><img src="user/icons/Start/Aussenthermometer.jpg" alt="Aussentemperatur"></td>
    <td><strg>'.number_format($temperatur, 1, ",", "" ).'°C</strg></td>
  	 <td> <table border="0" bgcolor="#ffffff" cellspacing="5" > <tablestyle>
	   <tr> <td> <img src="user/icons/Start/FHZ.png" alt="Innentemperatur">  </td> </tr>
		<tr> <td align="center"> <innen>'.number_format($innentemperatur, 1, ",", "" ).'°C</innen> </td> </tr>
    </tablestyle> </table> </td>
  	</tr>
</table>

';
}
else
{
$wert.='

<table border="0" cellspacing="10">
<tr>
   <td>
		<img src="user/pictures/'.$file[$showfile].'" width="67%" height="67%" alt="Heute" align="center">
   </td>
   <td>
		<table border="0" bgcolor="#f1f1f1">
	   		<tr>
   		     <td> <img src="user/icons/Start/Aussenthermometer.jpg" alt="Aussentemperatur"></td>
				  <td> <img src="user/icons/Start/FHZ.png" alt="Innentemperatur">  </td>
				</tr>
				<tr>
   			   <td><aussen>'.number_format($temperatur, 1, ",", "" ).'°C</aussen></td>
					<td align="center"> <innen>'.number_format($innentemperatur, 1, ",", "" ).'°C</innen> </td>
				</tr>
			   <tr id="temp">
				  <td> <table>
					  <tr> <td> <temperatur>'.number_format($todayTempMin, 1, ",", "" ).'°C</temperatur></td> </tr>
  					  <tr> <td><temperatur>'.number_format($todayTempMax, 1, ",", "" ).'°C</temperatur></td> </tr>
  					  </table>
				  </td>
				  <td align="center"> <img src="'.$today.'" alt="Heute" > </td>
				</tr>
			   <tr id="temp">
				  <td> <table>
					  <tr> <td> <temperatur>'.number_format($tomorrowTempMin, 1, ",", "" ).'°C</temperatur></td> </tr>
  					  <tr> <td><temperatur>'.number_format($tomorrowTempMax, 1, ",", "" ).'°C</temperatur></td> </tr>
  					  </table>
				  </td>
				  <td align="center"> <img src="'.$tomorrow.'" alt="Heute" > </td>
				</tr>
			   <tr id="temp">
				  <td> <table>
					  <tr> <td> <temperatur>'.number_format($tomorrow1TempMin, 1, ",", "" ).'°C</temperatur></td> </tr>
  					  <tr> <td><temperatur>'.number_format($tomorrow1TempMax, 1, ",", "" ).'°C</temperatur></td> </tr>
  					  </table>
				  </td>
				  <td align="center"> <img src="'.$tomorrow1.'" alt="Heute" > </td>
				</tr>
			   <tr id="temp">
				  <td> <table>
					  <tr> <td style="background-color:#efefef;right:50px;"> <temperatur>'.number_format($tomorrow2TempMin, 1, ",", "" ).'°C</temperatur></td> </tr>
  					  <tr> <td><temperatur>'.number_format($tomorrow2TempMax, 1, ",", "" ).'°C</temperatur></td> </tr>
  					  </table>
				  <td align="center"> <img src="'.$tomorrow2.'" alt="Heute" > </td>
				</tr>
			 </table>
   </td>
</tr>
</table>

';
}
$wert.='
</body>
</html>
';

return $wert;

}

?>

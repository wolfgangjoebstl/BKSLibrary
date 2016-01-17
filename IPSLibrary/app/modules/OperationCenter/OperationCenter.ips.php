<?

/***********************************************************************

Sprachsteuerung

***********************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");

/******************************************************

				INIT

*************************************************************/

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager)) {
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('OperationCenter',$repository);
}

$installedModules = $moduleManager->GetInstalledModules();
$inst_modules="\nInstallierte Module:\n";
foreach ($installedModules as $name=>$modules)
	{
	$inst_modules.=str_pad($name,30)." ".$modules."\n";
	}
echo $inst_modules."\n\n";

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
$scriptId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.OperationCenter'));
echo "Category App ID:".$CategoryIdApp."\n";
echo "Category Script ID:".$scriptId."\n\n";

$scriptIdOperationCenter   = IPS_GetScriptIDByName('OperationCenter', $CategoryIdApp);

if (isset ($installedModules["IPSLight"])) { echo "Modul IPSLight ist installiert.\n"; } else { echo "Modul IPSLight ist NICHT installiert.\n"; break; }
if (isset ($installedModules["IPSPowerControl"])) { echo "Modul IPSPowerControl ist installiert.\n"; } else { echo "Modul IPSPowerControl ist NICHT installiert.\n"; break;}
if (isset ($installedModules["IPSCam"]))
	{
	echo "Modul IPSCam ist installiert.\n";
	echo "   Timer 150 Sekunden aktivieren um Camfiles wegzuschlichten.\n";

	$tim2ID = @IPS_GetEventIDByName("MoveCamFiles", $_IPS['SELF']);
	if ($tim2ID==false)
		{
		$tim2ID = IPS_CreateEvent(1);
		IPS_SetParent($tim2ID, $_IPS['SELF']);
		IPS_SetName($tim2ID, "MoveCamFiles");
		IPS_SetEventCyclic($tim2ID,2,1,0,0,1,150);      /* alle 150 sec */
		//IPS_SetEventCyclicTimeFrom($tim1ID,2,10,0);  /* immer um 02:10 */
		}
	else
	   {
	   //echo "   Event bereits angelegt. Timer 150 sec aktivieren.\n";
  		IPS_SetEventActive($tim2ID,true);
  		}
	}
else
	{
	echo "Modul IPSCam ist NICHT installiert.\n";
	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Execute")
	{
	/* von der Konsole aus gestartet */

/********************************************************
   Install
**********************************************************/

	$WebCamWZ_LetzteBewegungID = CreateVariableByName($CategoryIdData, "WebcamWZ_letzteBewegung", 3);
	$WebCam_PhotoCountID = CreateVariableByName($CategoryIdData, "Webcam_PhotoCount", 1);
  	$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
	AC_SetLoggingStatus($archiveHandlerID,$WebCam_PhotoCountID,true);
	AC_SetAggregationType($archiveHandlerID,$WebCam_PhotoCountID,0);      /* normaler Wwert */
	IPS_ApplyChanges($archiveHandlerID);
	echo "Letztes Foto aus der Webcam vom ".GetValue($WebCamWZ_LetzteBewegungID).".\n";
	echo "Bereits ".GetValue($WebCam_PhotoCountID)." Fotos von der Webcam erstellt.\n";

/********************************************************
   Zusammenräumen
**********************************************************/


if (isset($installedModules["IPSLight"])==true)
	{  /* das IPSLight Webfront ausblenden, es bleibt nur die Glühlampe stehen */
	$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');
	$pos=strpos($WFC10_Path,"OperationCenter");
	$ipslight_Path=substr($WFC10_Path,0,$pos)."IPSLight";
	$categoryId_WebFront = CreateCategoryPath($ipslight_Path);
   IPS_SetPosition($categoryId_WebFront,998);
   IPS_SetHidden($categoryId_WebFront,true);
	echo "Administrator Webfront IPSLight auf : ".$ipslight_Path." mit OID : ".$categoryId_WebFront."\n";
	}

if (isset($installedModules["IPSPowerControl"])==true)
	{  /* das IPSPower<Control Webfront ausblenden, es bleibt nur die Glühlampe stehen */
	$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');
	$pos=strpos($WFC10_Path,"OperationCenter");
	$ipslight_Path=substr($WFC10_Path,0,$pos)."IPSPowerControl";
	$categoryId_WebFront = CreateCategoryPath($ipslight_Path);
   IPS_SetPosition($categoryId_WebFront,997);
   IPS_SetHidden($categoryId_WebFront,true);
	echo "Administrator Webfront IPSPowerControl auf : ".$ipslight_Path." mit OID : ".$categoryId_WebFront."\n";
	}



	
/********************************************************
   Externe Ip Adresse immer ermitteln
**********************************************************/

	$url="http://whatismyipaddress.com/";  //gesperrt da html 1.1
	//$url="http://www.whatismyip.com/";  //gesperrt
	//$url="http://whatismyip.org/"; // java script
	//$url="http://www.myipaddress.com/show-my-ip-address/"; // check auf computerzugriffe
	//$url="http://www.ip-adress.com/"; //gesperrt

	/* ab und zu gibt es auch bei der whatismyipaddress url timeouts, 30sek maximum timeout */
	/* d.h. Timeout: Server wird nicht erreicht
			Zustand false: kein Internet
	*/


	//curl  ifconfig.co
	
	/* gets the data from a URL */

	//$result=file_get_contents($url);
	$result=get_data($url);

	//echo $result;

	/* letzte Alternative ist die Webcam selbst */

	echo "\n";
	if ($result==false)
		{
		echo "Whatismyipaddress reagiert nicht. Ip Adresse anders ermitteln.\n";
		}
	else
	   {
		$pos_start=strpos($result,"whatismyipaddress.com/ip")+25;
		$subresult=substr($result,$pos_start,20);
		$pos_length=strpos($subresult,"\"");
		$subresult=substr($subresult,0,$pos_length);
	   echo "Whatismyipaddress liefert : ".$subresult."\n";
	   }
	   
	   
	   
/********************************************************
   Eigene Ip Adresse immer ermitteln
**********************************************************/

$ipall=""; $hostname="unknown"; $lookforgateway=false;
//exec('ipconfig /all',$catch);   /* braucht ein MSDOS Befehl manchmal laenger als 30 Sekunden zum abarbeiten ? */
exec('ipconfig',$catch);   /* ohne all ist es eigentlich ausreichend Information, doppelte Eintraege werden vermieden */

$ipports=array();

foreach($catch as $line)
   	{
		if (strlen($line)>2)
		   {
			echo $line."\n<br>";
			if (substr($line,0,1)!=" ")
				{
				echo "-------------------> Ueberschrift \n";
				$portname=substr($line,0,strpos($line,":"));
				}
   		if(preg_match('/IPv4-Adresse/i',$line))
	   		{
				//echo "Ausgabe catch :".$line."\n<br>";
   	   	list($t,$ip) = explode(':',$line);
      		$result = extractIPaddress($ip);
      		$ipports[$result]["Name"]=$portname;
	         $ipall=$ipall." ".$result;
	         $lookforgateway=true;
		      /* if(ip2long($ip > 0))
				   {
      		   $ipports[]=$ip;
         		$ipall=$ipall." ".$ip;
		         $status2=true;
					$pos=strpos($ipall,"(");  // bevorzugt eliminieren
					$ipall=trim(substr($ipall,0,$pos));
   	   	   }  */
	      	}
	      if ($lookforgateway==true)
				{
				if(preg_match('/Standardgateway/i',$line))
	   			{
					//echo "Ausgabe catch :".$line."\n<br>";
   		   	list($t,$gw) = explode(':',$line);
      			$gw = extractIPaddress($gw);
      			$ipports[$result]["Gateway"]=$gw;
	         	$lookforgateway=false;
					}
				}
   		if(preg_match('/Hostname/i',$line))
	   		{
	   		list($t,$hostname) = explode(':',$line);
	      	$hostname = trim($hostname);
				}
			}  /* ende strlen */
	  }
	if ($ipall == "") {$ipall="unknown";}

	echo "\n";
	echo "Hostname ist          : ".$hostname."\n";
	echo "Eigene IP Adresse ist : ".$ipall."\n";
	echo "\n";

	foreach ($ipports as $ip => $data)
		{
		//echo "IP Adresse ".$ip." und im Longformat : ".ip2long($ip)."\n";
		printf("Port %s hat IP Adresse %s und Gateway %s Ip Adresse im Longformat : %u\n", $data["Name"],$ip,$data["Gateway"],ip2long($ip));
		}

/********************************************************
   nun die Webcam zusammenraeumen
**********************************************************/

/* Zusammenraeumen ftp Server ist schon implementiert */

$WebCamWZ_LetzteBewegungID = CreateVariableByName($CategoryIdData, "WebcamWZ_letzteBewegung", 3);

$verzeichnis = "D:\\FTP-Folder\\lbg70\\";
$count=100;
//echo "<ol>";

// Text, ob ein Verzeichnis angegeben wurde
if ( is_dir ( $verzeichnis ))
{
    // öffnen des Verzeichnisses
    if ( $handle = opendir($verzeichnis) )
    {
        /* einlesen der Verzeichnisses
			nur count mal Eintraege
        */
        while ((($file = readdir($handle)) !== false) and ($count > 0))
        {
				$dateityp=filetype( $verzeichnis.$file );
            if ($dateityp == "file")
            {
					$count-=1;
					$unterverzeichnis=date("Ymd", filectime($verzeichnis.$file));
					$letztesfotodatumzeit=date("d.m.Y H:i", filectime($verzeichnis.$file));
            	if (is_dir($verzeichnis.$unterverzeichnis))
            	{
            	}
            	else
					{
            		mkdir($verzeichnis.$unterverzeichnis);
            	}
            	rename($verzeichnis.$file,$verzeichnis.$unterverzeichnis."\\".$file);
            	echo "Datei: ".$verzeichnis.$unterverzeichnis."\\".$file."\n";
		  		   SetValue($WebCamWZ_LetzteBewegungID,$letztesfotodatumzeit);
            }
        }
        closedir($handle);
    }
}

/********************************************************
   Sys Uptime ermitteln
**********************************************************/

$IPS_UpTimeID = CreateVariableByName($CategoryIdData, "IPS_UpTime", 1);
IPS_SetVariableCustomProfile($IPS_UpTimeID,"~UnixTimestamp");
SetValue($IPS_UpTimeID,IPS_GetUptime());


/********************************************************
   Über eigene Ip Adresse auf Gateway Adresse schliessen
**********************************************************/

/* vorerst lassen wir es haendisch, spaeter kann man es auch aus ipconfig ableiten
	Gateway kann mit tracert 8.8.8.8 rausgefunden werden, die ersten zeilen sind die bekannten Gateways

*/

if (false)
	{
	$url="http://10.0.1.201/userRpm/StatusRpm.htm";  	/* gets the data from a URL */

	/*  $result=file_get_contents($url) geht leider nicht, passwort Eingabe, Browserchecks etc  */
	$ch = curl_init($url);
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);           // return web page
	curl_setopt($ch, CURLOPT_USERPWD, "admin:cloudg06");
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_HEADER, false);                    // don't return headers
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);          // follow redirects, wichtig da die Root adresse automatisch umgeleitet wird
   curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko"); // who am i
   curl_setopt($ch, CURLOPT_ENCODING, "");       // handle all encodings
   curl_setopt($ch, CURLOPT_AUTOREFERER, true);     // set referer on redirect
	//curl_setopt($ch, CURLOPT_REFERER, $url);  /* wichtig damit TP-Link weiss wo er die Daten hinschicken soll, Autoreferer funktioniert aber besser, siehe oben */
   curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);      // timeout on connect
   curl_setopt($ch, CURLOPT_TIMEOUT, 120);      // timeout on response
   curl_setopt($ch, CURLOPT_MAXREDIRS, 10);       // stop after 10 redirects

	/*   
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => "LOOKUPADDRESS=".$argument1,  */

	$data = curl_exec($ch);

	/* Curl Debug Funktionen */

	echo "Channel :".$ch."\n";
  	$err     = curl_errno( $ch );
   $errmsg  = curl_error( $ch );
   $header  = curl_getinfo( $ch );

	echo "Fehler ".$err." von ";
	print_r($errmsg);
	echo "\n";
	echo "Header ";
	print_r($header);
	echo "\n##################################################################################################\n";


	curl_close($ch);

	echo $data;
	}
	
	
	
	} /* ende Execute */

	
/*********************************************************************************************/


if ($_IPS['SENDER']=="Variable")
	{

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="TimerEvent")
	{

	/********************************************************
   nun die Webcam zusammenraeumen
	**********************************************************/

	/* Zusammenraeumen ftp Server ist schon implementiert */

	$WebCamWZ_LetzteBewegungID = CreateVariableByName($CategoryIdData, "WebcamWZ_letzteBewegung", 3);
	$WebCam_PhotoCountID = CreateVariableByName($CategoryIdData, "Webcam_PhotoCount", 1);

	$verzeichnis = "D:\\FTP-Folder\\lbg70\\";
	$count=100;
	//echo "<ol>";

	// Text, ob ein Verzeichnis angegeben wurde
	if ( is_dir ( $verzeichnis ))
		{
   	// öffnen des Verzeichnisses
	   if ( $handle = opendir($verzeichnis) )
   		{
      	/* einlesen der Verzeichnisses
			nur count mal Eintraege
        	*/
        	while ((($file = readdir($handle)) !== false) and ($count > 0))
        		{
				$dateityp=filetype( $verzeichnis.$file );
            if ($dateityp == "file")
            	{
					$count-=1;
					$unterverzeichnis=date("Ymd", filectime($verzeichnis.$file));
					$letztesfotodatumzeit=date("d.m.Y H:i", filectime($verzeichnis.$file));
            	if (is_dir($verzeichnis.$unterverzeichnis))
            		{
	            	}
   	        	else
						{
         	  		mkdir($verzeichnis.$unterverzeichnis);
            		}
            	rename($verzeichnis.$file,$verzeichnis.$unterverzeichnis."\\".$file);
            	echo "Datei: ".$verzeichnis.$unterverzeichnis."\\".$file."\n";
		  		   SetValue($WebCamWZ_LetzteBewegungID,$letztesfotodatumzeit);
            	}
        		} /* ende while */
        	closedir($handle);
    		}
		}
		
	SetValue($WebCam_PhotoCountID,GetValue($WebCam_PhotoCountID)+100-$count);
		
	}





/*********************************************************************************************/
/*********************************************************************************************/
/*********************************************************************************************/

function get_data($url) {
	$ch = curl_init($url);
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);           // return web page
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_HEADER, false);                    // don't return headers
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);          // follow redirects, wichtig da die Root adresse automatisch umgeleitet wird
   curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 (FM Scene 4.6.1)"); // who am i

	/*   CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING       => "",       // handle all encodings
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
        CURLOPT_TIMEOUT        => 120,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => "LOOKUPADDRESS=".$argument1,  */
        
	$data = curl_exec($ch);

	/* Curl Debug Funktionen */
	/*
	echo "Channel :".$ch."\n";
  	$err     = curl_errno( $ch );
   $errmsg  = curl_error( $ch );
   $header  = curl_getinfo( $ch );

	echo "Fehler ".$err." von ";
	print_r($errmsg);
	echo "\n";
	echo "Header ";
	print_r($header);
	echo "\n";
	*/

	curl_close($ch);
	
	return $data;
}

/*********************************************************************************************/

function extractIPaddress($ip)
	{
		$parts = str_split($ip);   /* String in lauter einzelne Zeichen zerlegen */
		$first_num = -1;
		$num_loc = 0;
		foreach ($parts AS $a_char)
			{
			if (is_numeric($a_char))
				{
				$first_num = $num_loc;
				break;
				}
			$num_loc++;
			}
		if ($first_num == -1) {return "unknown";}

		/* IP adresse Stelle fuer Stelle dekodieren, Anhaltspunkt ist der Punkt */
		$result=substr($ip,$first_num,20);
		//echo "Result :".$result."\n";
		$pos=strpos($result,".");
		$result_1=substr($result,0,$pos);
		$result=substr($result,$pos+1,20);
		//echo "Result :".$result."\n";
		$pos=strpos($result,".");
		$result_2=substr($result,0,$pos);
		$result=substr($result,$pos+1,20);
		//echo "Result :".$result."\n";
		$pos=strpos($result,".");
		$result_3=substr($result,0,$pos);
		$result=substr($result,$pos+1,20);
		//echo "Result :".$result."\n";
		$parts = str_split($result);   /* String in lauter einzelne Zeichen zerlegen */
		$last_num = -1;
		$num_loc = 0;
		foreach ($parts AS $a_char)
			{
			if (is_numeric($a_char))
				{
				$last_num = $num_loc;
				}
			$num_loc++;
			}
		$result=substr($result,0,$last_num+1);
		echo "-------------------------> externe IP Adresse in Einzelteilen:  ".$result_1.".".$result_2.".".$result_3.".".$result."\n";
		return($result_1.".".$result_2.".".$result_3.".".$result);
	}


?>

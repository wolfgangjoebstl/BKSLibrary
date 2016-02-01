<?

/***********************************************************************

OperationCenter

Allerlei betriebliche Abfragen und Wartungsmassnahmen

***********************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");

/******************************************************

				INIT

*************************************************************/


$dir655=false;

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
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

/* Webfront zusammenräumen */
if (isset($installedModules["IPSLight"])==true)
	{  /* das IPSLight Webfront ausblenden, es bleibt nur die Glühlampe stehen */
	$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');
	$pos=strpos($WFC10_Path,"OperationCenter");
	$ipslight_Path=substr($WFC10_Path,0,$pos)."IPSLight";
	$categoryId_WebFront = CreateCategoryPath($ipslight_Path);
   IPS_SetPosition($categoryId_WebFront,998);
   IPS_SetHidden($categoryId_WebFront,true);
	echo "   Administrator Webfront IPSLight auf : ".$ipslight_Path." mit OID : ".$categoryId_WebFront."\n";
	}

if (isset($installedModules["IPSPowerControl"])==true)
	{  /* das IPSPower<Control Webfront ausblenden, es bleibt nur die Glühlampe stehen */
	$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');
	$pos=strpos($WFC10_Path,"OperationCenter");
	$ipslight_Path=substr($WFC10_Path,0,$pos)."IPSPowerControl";
	$categoryId_WebFront = CreateCategoryPath($ipslight_Path);
   IPS_SetPosition($categoryId_WebFront,997);
   IPS_SetHidden($categoryId_WebFront,true);
	echo "   Administrator Webfront IPSPowerControl auf : ".$ipslight_Path." mit OID : ".$categoryId_WebFront."\n";
	}

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
  		IPS_SetEventActive($tim2ID,true);
		IPS_SetEventCyclicTimeBounds($tim2ID,time(),0);  /* damit die Timer hintereinander ausgeführt werden */
	   echo "   Event neu angelegt. Timer 150 sec ist aktiviert.\n";
		//IPS_SetEventCyclicTimeFrom($tim1ID,2,10,0);  /* immer um 02:10 */
		}
	else
	   {
	   echo "   Event bereits angelegt. Timer 150 sec ist aktiviert.\n";
  		IPS_SetEventActive($tim2ID,true);
  		}
	}
else
	{
	echo "Modul IPSCam ist NICHT installiert.\n";
	}

/* Eventuell Router regelmaessig auslesen */

$tim1ID = @IPS_GetEventIDByName("RouterAufruftimer", $_IPS['SELF']);
if ($tim1ID==false)
	{
	$tim1ID = IPS_CreateEvent(1);
	IPS_SetParent($tim1ID, $_IPS['SELF']);
	IPS_SetName($tim1ID, "RouterAufruftimer");
	IPS_SetEventCyclic($tim1ID,0,0,0,0,0,0);
	IPS_SetEventCyclicTimeFrom($tim1ID,0,20,0);  /* immer um 0:20 */
	}
IPS_SetEventActive($tim1ID,true);

$tim3ID = @IPS_GetEventIDByName("RouterExectimer", $_IPS['SELF']);
if ($tim3ID==false)
	{
	$tim3ID = IPS_CreateEvent(1);
	IPS_SetParent($tim3ID, $_IPS['SELF']);
	IPS_SetName($tim3ID, "RouterExectimer");
	IPS_SetEventCyclic($tim3ID,2,1,0,0,1,150);      /* alle 150 sec */
	IPS_SetEventCyclicTimeBounds($tim3ID,time()+60,0);
	/* diesen Timer nicht aktivieren, er wird vom RouterAufrufTimer aktiviert und deaktiviert */
	}

/*********************************************************************************************/

$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

$ScriptCounterID=CreateVariableByName($CategoryIdData,"ScriptCounter",1);

$OperationCenterConfig = OperationCenter_Configuration();

	$pname="MByte";
	if (IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		IPS_SetVariableProfileText($pname,'',' MByte');
	   print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   echo "Profile \"MByte\" vorhanden.\n";
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
	
	/******************************************************

				INIT

	*************************************************************/

	/* Timer so konfigurieren dass sie sich nicht in die Quere kommen */
	IPS_SetEventCyclicTimeBounds($tim2ID,time(),0);  /* damit die Timer hintereinander ausgeführt werden */
	IPS_SetEventCyclicTimeBounds($tim3ID,time()+60,0);

	$WebCamWZ_LetzteBewegungID = CreateVariableByName($CategoryIdData, "WebcamWZ_letzteBewegung", 3);
	$WebCam_PhotoCountID = CreateVariableByName($CategoryIdData, "Webcam_PhotoCount", 1);
  	$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
	AC_SetLoggingStatus($archiveHandlerID,$WebCam_PhotoCountID,true);
	AC_SetAggregationType($archiveHandlerID,$WebCam_PhotoCountID,0);      /* normaler Wwert */
	IPS_ApplyChanges($archiveHandlerID);
	echo "Letztes Foto aus der Webcam vom ".GetValue($WebCamWZ_LetzteBewegungID).".\n";
	echo "Bereits ".GetValue($WebCam_PhotoCountID)." Fotos von der Webcam erstellt.\n";

	//print_r($OperationCenterConfig);
	foreach ($OperationCenterConfig['ROUTER'] as $router)
	   {
	   echo "Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
		//print_r($router);
		if ($router['TYP']=='MR3420')
		   {
		   echo "    iMacro Command-File für Router Typ MR3420 wird hergestellt.\n";
			$handle2=fopen($router["MacroDirectory"]."router_".$router['TYP']."_".$router['NAME'].".iim","w");
      	fwrite($handle2,'VERSION BUILD=8961227 RECORDER=FX'."\n");
	      fwrite($handle2,'TAB T=1'."\n");
	      fwrite($handle2,'SET !EXTRACT_TEST_POPUP NO'."\n");
			fwrite($handle2,'SET !ENCRYPTION NO'."\n");
     		fwrite($handle2,'ONLOGIN USER=admin PASSWORD=cloudg06'."\n");
	      fwrite($handle2,'URL GOTO=http://'.$router['IPADRESSE']."\n");
   	   fwrite($handle2,'FRAME NAME="bottomLeftFrame"'."\n");
      	fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:System<SP>Tools'."\n");
	      fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:-<SP>Statistics'."\n");
   	   fwrite($handle2,'FRAME NAME="mainFrame"'."\n");
      	fwrite($handle2,'TAG POS=1 TYPE=SELECT FORM=NAME:sysStatic ATTR=NAME:Num_per_page CONTENT=%100'."\n");
	      fwrite($handle2,'TAG POS=1 TYPE=INPUT:SUBMIT FORM=NAME:sysStatic ATTR=NAME:NextPage'."\n");
	      fwrite($handle2,'FRAME NAME="mainFrame"'."\n");
	      fwrite($handle2,'TAG POS=1 TYPE=INPUT:SUBMIT FORM=NAME:sysStatic ATTR=NAME:Refresh'."\n");
   	   //fwrite($handle2,'SAVEAS TYPE=TXT FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."\n");  /* Textfile speichert nicht die komplette Struktur */
   	   fwrite($handle2,'SAVEAS TYPE=CPL FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."\n");
      	fwrite($handle2,'TAB CLOSE'."\n");
			fclose($handle2);

			//SetValue($ScriptCounterID,1);
			//IPS_SetEventActive($tim3ID,true);

			}
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
   	die Webcam anschauen und den FTP Folder zusammenräumen
	**********************************************************/

	IPSUtils_Include ("IPSCam_Constants.inc.php",         "IPSLibrary::app::modules::IPSCam");
	IPSUtils_Include ("IPSCam_Configuration.inc.php",     "IPSLibrary::config::modules::IPSCam");
	
	$ipscam_configuration=IPSCam_GetConfiguration();
	print_r($ipscam_configuration);

	foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
		{
		echo "Bearbeite Kamera : ".$cam_name." im Verzeichnis ".$cam_config['FTPFOLDER']."\n";
		$verzeichnis = $cam_config['FTPFOLDER'];
		$cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdData);
		if ($cam_categoryId==false)
		   {
			$cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
			IPS_SetParent($cam_categoryId,$CategoryIdData);
			}
		$WebCam_LetzteBewegungID = CreateVariableByName($cam_categoryId, "Cam_letzteBewegung", 3);
		$WebCam_PhotoCountID = CreateVariableByName($cam_categoryId, "Cam_PhotoCount", 1);
		//print_r(dirToArray($verzeichnis));      /* zuviel fileeintraege, dauert zu lange */
		//print_r(scandir($verzeichnis));
		//print_r(dirToArray2($verzeichnis));       /* anzahl der neuen Dateiein einfach feststellen */
	
		$count=100;
		//echo "<ol>";

		// Test, ob ein Verzeichnis angegeben wurde
		if ( is_dir ( $verzeichnis ))
			{
			$count=move_camPicture($verzeichnis,$WebCam_LetzteBewegungID);
			SetValue($WebCam_PhotoCountID,GetValue($WebCam_PhotoCountID)+$count);
			
	    	// öffnen des Verzeichnisses
   	 	if ( $handle = opendir($verzeichnis) )
	    		{
   	 		$count=0; $list="";
	        	/* einlesen des Verzeichnisses        	*/
	        	while (($file = readdir($handle)) !== false)
	        		{
   	     		if (is_dir($verzeichnis.$file)==false)
	        		   {
		        		$count++;
	   	     		$list .= $file."\n";
	   	     		}
					}
				echo "   Im Cam FTP Verzeichnis ".$verzeichnis." gibt es ".$count." neue Dateien.\n";
				echo "   Letzter Eintrag von ".GetValue($WebCam_LetzteBewegungID)."\n";
				//echo $list."\n";
				}
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
	
	
	

	/********************************************************
   	Auswertung Router MR3420   curl
	**********************************************************/

	$mr3420=false;    /* jetzt mit imacro geloest, die können die gesamte Webseite inklusive Unterverzeichnisse abspeichern und beliebig im Frame manövrieren */
	if ($mr3420==true)
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

	/********************************************************
   	Auswertung Router MR3420 mit imacro
	**********************************************************/

   	foreach ($OperationCenterConfig['ROUTER'] as $router)
		   {
			//print_r($router);
			if ($router['TYP']=='MR3420')
			   {
			   echo "Ergebnisse vom Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
			   $verzeichnis=$router["DownloadDirectory"]."report_router_".$router['TYP']."_".$router['NAME']."_files/";
				if ( is_dir ( $verzeichnis ))
					{
					echo "Auswertung Dateien aus Verzeichnis : ".$verzeichnis."\n";
					$parser=new parsefile($CategoryIdData);
					$CatID=@IPS_GetCategoryIDByName($router['NAME'],$CategoryIdData);
					$ergebnis=array();
					$ergebnis=$parser->parsetxtfile($verzeichnis,$router['NAME']);
					//print_r($ergebnis);
					$summe=0;
					foreach ($ergebnis as $ipadresse)
					   {
					   $MBytes=(integer)$ipadresse['Bytes']/1024/1024;
					   echo "       ".str_pad($ipadresse['IPAdresse'],18)." mit MBytes ".$MBytes."\n";
      				if (($ByteID=@IPS_GetVariableIDByName("MBytes_".$ipadresse['IPAdresse'],$CatID))==false)
         				{
						  	$ByteID = CreateVariableByName($CatID, "MBytes_".$ipadresse['IPAdresse'], 2);
							IPS_SetVariableCustomProfile($ByteID,'MByte');
							AC_SetLoggingStatus($archiveHandlerID,$ByteID,true);
							AC_SetAggregationType($archiveHandlerID,$ByteID,0);
							IPS_ApplyChanges($archiveHandlerID);
							}
					  	SetValue($ByteID,$MBytes);
						$summe += $MBytes;
						}
					echo "Summe   ".$summe."\n";
     				if (($ByteID=@IPS_GetVariableIDByName("MBytes_All",$CatID))==false)
         			{
					  	$ByteID = CreateVariableByName($CatID, "MBytes_All", 2);
						IPS_SetVariableCustomProfile($ByteID,'MByte');
						AC_SetLoggingStatus($archiveHandlerID,$ByteID,true);
						AC_SetAggregationType($archiveHandlerID,$ByteID,0);
						IPS_ApplyChanges($archiveHandlerID);
						}
				  	SetValue($ByteID,$MBytes);
					}
				}
	   	}

		//$handle2=fopen($router["MacroDirectory"]."router_".$router['TYP']."_".$router['NAME'].".iim","w");

	
	
	} /* ende Execute */

	
/*********************************************************************************************/


if ($_IPS['SENDER']=="Variable")
	{

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="TimerEvent")
	{
	switch ($_IPS['EVENT'])
	   {
	   case $tim1ID:        /* einmal am Tag */
	   	foreach ($OperationCenterConfig['ROUTER'] as $router)
			   {
			   echo "Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
				//print_r($router);
				if ($router['TYP']=='MR3420')
				   {
					/* und gleich ausprobieren */
		   		IPS_ExecuteEX(ADR_Programs."Mozilla Firefox/firefox.exe", "imacros://run/?m=router_".$router['TYP']."_".$router['NAME'].".iim", false, false, 1);
		   		}
		   	}
			
			IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Router Auswertung");

			SetValue($ScriptCounterID,1);
			IPS_SetEventActive($tim3ID,true);
	      break;
	   case $tim2ID:
	   
			/********************************************************
		   nun die Webcam zusammenraeumen
			**********************************************************/
			$count=0;
			foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
				{
				echo "Bearbeite Kamera : ".$cam_name." im Verzeichnis ".$cam_config['FTPFOLDER']."\n";
				$verzeichnis = $cam_config['FTPFOLDER'];
				$cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdData);
				if ($cam_categoryId==false)
				   {
					$cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
					IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
					IPS_SetParent($cam_categoryId,$CategoryIdData);
					}
				$WebCam_LetzteBewegungID = CreateVariableByName($cam_categoryId, "Cam_letzteBewegung", 3);
				$WebCam_PhotoCountID = CreateVariableByName($cam_categoryId, "Cam_PhotoCount", 1);

				$count1=move_camPicture($verzeichnis,$WebCam_LetzteBewegungID);
				$count+=$count1;
				$WebCam_PhotoCountID = CreateVariableByName($CategoryIdData, "Webcam_PhotoCount", 1);
				SetValue($WebCam_PhotoCountID,GetValue($WebCam_PhotoCountID)+$count1);
				}
			IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Webcam zusammenraeumen, ".$count." Fotos verschoben");
			
	      break;
	   case $tim3ID:
			IPSLogger_Dbg(__file__, "TimerExecEvent from :".$_IPS['EVENT']." ScriptcountID:".GetValue($ScriptCounterID));
			$counter=GetValue($ScriptCounterID);
			switch ($counter)
			   {
				case 3:
		      	SetValue($ScriptCounterID,0);
			      IPS_SetEventActive($tim3ID,false);
		      	break;
			   case 2:



					SetValue($ScriptCounterID,$counter+1);
		      	break;
			   case 1:
					/* Router Auswertung */
					
			      SetValue($ScriptCounterID,$counter+1);
					break;
			   case 0:
				default:
				   break;
			   }
			break;
		default:
		   break;
		}
	}





/*********************************************************************************************/
/*********************************************************************************************/


function move_camPicture($verzeichnis,$WebCamWZ_LetzteBewegungID)
	{
	$count=100;
	//echo "<ol>";

	// Test, ob ein Verzeichnis angegeben wurde
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
            	//echo "Datei: ".$verzeichnis.$unterverzeichnis."\\".$file." verschoben.\n";
		  		   SetValue($WebCamWZ_LetzteBewegungID,$letztesfotodatumzeit);
         		}
      	  	} /* Ende while */
	     	closedir($handle);
   		} /* end if dir */
		}/* ende if isdir */
	else
	   {
	   echo "Kein FTP Verzeichnis mit dem Namen \"".$verzeichnis."\" vorhanden.\n";
		}
	return(100-$count);
	}



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

/*********************************************************************************************/


class parsefile
	{
	
	private $dataID;

	public function __construct($moduldataID)
		{
		//echo "Parsefile construct mit Data ID des aktuellen Moduls: ".$moduldataID."\n";
		$this->dataID=$moduldataID;
		}

	function parsetxtfile($verzeichnis, $name)
		{
		$ergebnis_array=array();
	
		echo "Data ID des aktuellen Moduls: ".$this->dataID." für den folgenden Router: ".$name."\n";
      if (($CatID=@IPS_GetCategoryIDByName($name,$this->dataID))==false)
         {
			$CatID = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($CatID, $name); // Kategorie benennen
			IPS_SetParent($CatID, $this->dataID); // Kategorie einsortieren unter dem Objekt mit der ID "12345"
			}
		echo "Datenkategorie für den Router ".$name."  : ".$CatID." existiert.\n";
		$handle = @fopen($verzeichnis."SystemStatisticRpm.htm", "r");
		if ($handle)
			{
			echo "Ergebnisfile gefunden.\n";
			$ok=true;
   		while ((($buffer = fgets($handle, 4096)) !== false) && $ok) /* liest bis zum Zeilenende */
				{
				/* fährt den ganzen Textblock durch, Werte die früher detektiert werden, werden ueberschrieben */
				//echo $buffer;
	      	if(preg_match('/statList/i',$buffer))
		   		{
		   		do {
		   		   if (($buffer = fgets($handle, 4096))==false) {	$ok=false; }
			      	if ((preg_match('/script/i',$buffer))==true) {	$ok=false; }
						if ($ok)
						   {
							//echo "       ".$buffer;
					  		$pos1=strpos($buffer,"\"");
							if ($pos1!=false)
								{
						  		$pos2=strpos($buffer,"\"",$pos1+1);
						  		$ipadresse=substr($buffer,$pos1+1,$pos2-$pos1-1);
						  		$ergebnis_array[$ipadresse]['IPAdresse']=substr($buffer,$pos1+1,$pos2-$pos1-1);
								$buffer=trim(substr($buffer,$pos2+1,200));
								//echo "       **IP Adresse: ".$ergebnis_array[$ipadresse]['IPAdresse']." liegt zwischen ".($pos1+1)." und ".$pos2." \n";
								//echo "       **1:".$buffer."\n";
						  		$pos1=strpos($buffer,"\"");
								if ($pos1!=false)
									{
							  		$pos2=strpos($buffer,"\"",$pos1+1);
							  		$ergebnis_array[$ipadresse]['MacAdresse']=substr($buffer,$pos1+1,$pos2-$pos1-1);
									$buffer=trim(substr($buffer,$pos2,200));
									//echo "       **MAC Adresse: ".$ergebnis_array[$ipadresse]['MacAdresse']." liegt zwischen ".($pos1+1)." und ".$pos2." \n";
									//echo "       **2:".$buffer."\n";
							  		$pos1=strpos($buffer,',');
									if ($pos1!=false)
										{
								  		$pos2=strpos($buffer,',',$pos1+1);
								  		$ergebnis_array[$ipadresse]['Packets']=(integer)substr($buffer,$pos1+1,$pos2-$pos1-1);
										$buffer=trim(substr($buffer,$pos2,200));
										//echo "       **Packets: ".$ergebnis_array[[$ipadresse]['Packets']." liegt zwischen ".($pos1+1)." und ".$pos2." \n";
										//echo "       **3:".$buffer."\n";
								  		$pos1=strpos($buffer,',');
										if ($pos1!==false)
											{
									  		$pos2=strpos($buffer,',',$pos1+1);
									  		$ergebnis_array[$ipadresse]['Bytes']=(integer)substr($buffer,$pos1+1,$pos2-$pos1-1);
											$buffer=trim(substr($buffer,$pos2,200));
											//echo "       **Bytes: ".$ergebnis_array[$ipadresse]['Bytes']." liegt zwischen ".($pos1+1)." und ".$pos2." \n";
											//echo "       **4:".$buffer."\n";
											}
										}
									}
								}
						   }
		   		   } while ($ok==true);
					}
				}
			}
	return $ergebnis_array;
	}
	
	} /* Ende class */


/*********************************************************************************************/


function dirToArray($dir)
	{
   $result = array();

   $cdir = scandir($dir);
   foreach ($cdir as $key => $value)
   {
      if (!in_array($value,array(".","..")))
      {
         if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
         {
            $result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
         }
         else
         {
            $result[] = $value;
         }
      }
   }

   return $result;
	}

/*********************************************************************************************/

function dirToArray2($dir)
	{
   $result = array();

   $cdir = scandir($dir);
   foreach ($cdir as $key => $value)
   {
      if (!in_array($value,array(".","..")))
      {
         if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
         {
            //$result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
         }
         else
         {
            $result[] = $value;
         }
      }
   }

   return $result;
	}



?>

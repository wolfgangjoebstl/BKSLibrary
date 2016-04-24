<?

/***********************************************************************

OperationCenter

Allerlei betriebliche Abfragen und Wartungsmassnahmen

***********************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
IPSUtils_Include ("SNMP_Library.class.php","IPSLibrary::app::modules::OperationCenter");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

/******************************************************

				INIT

*************************************************************/

// max. Scriptlaufzeit definieren
ini_set('max_execution_time', 500);
$startexec=microtime(true);

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

echo "Category Data ID   : ".$CategoryIdData."\n";
echo "Category App ID    : ".$CategoryIdApp."\n";
echo "Category Script ID : ".$scriptId."\n\n";

$scriptIdOperationCenter   = IPS_GetScriptIDByName('OperationCenter', $CategoryIdApp);

echo "Folgende Module werden von OperationCenter bearbeitet:\n";
if (isset ($installedModules["IPSLight"])) { 			echo "  Modul IPSLight ist installiert.\n"; } else { echo "Modul IPSLight ist NICHT installiert.\n"; }
if (isset ($installedModules["IPSPowerControl"])) { 	echo "  Modul IPSPowerControl ist installiert.\n"; } else { echo "Modul IPSPowerControl ist NICHT installiert.\n";}
if (isset ($installedModules["IPSCam"])) { 				echo "  Modul IPSCam ist installiert.\n"; } else { echo "Modul IPSCam ist NICHT installiert.\n"; }
if (isset ($installedModules["RemoteAccess"])) { 		echo "  Modul RemoteAccess ist installiert.\n"; } else { echo "Modul RemoteAccess ist NICHT installiert.\n"; }
if (isset ($installedModules["LedAnsteuerung"])) { 	echo "  Modul LedAnsteuerung ist installiert.\n"; } else { echo "Modul LedAnsteuerung ist NICHT installiert.\n";}
if (isset ($installedModules["DENONsteuerung"])) { 	echo "  Modul DENONsteuerung ist installiert.\n"; } else { echo "Modul DENONsteuerung ist NICHT installiert.\n";}
echo "\n";

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
	$tim2ID = @IPS_GetEventIDByName("MoveCamFiles", $scriptId);
	}
else
	{
	echo "Modul IPSCam ist NICHT installiert.\n";
	$tim2ID = 0;
	}

/* Eventuell Router regelmaessig auslesen */

$tim1ID = @IPS_GetEventIDByName("RouterAufruftimer", $scriptId);
if ($tim1ID==false)
	{
	$tim1ID = IPS_CreateEvent(1);
	IPS_SetParent($tim1ID, $_IPS['SELF']);
	IPS_SetName($tim1ID, "RouterAufruftimer");
	IPS_SetEventCyclic($tim1ID,0,0,0,0,0,0);
	IPS_SetEventCyclicTimeFrom($tim1ID,0,20,0);  /* immer um 0:20 */
	}
IPS_SetEventActive($tim1ID,true);

$tim3ID = @IPS_GetEventIDByName("RouterExectimer", $scriptId);
if ($tim3ID==false)
	{
	$tim3ID = IPS_CreateEvent(1);
	IPS_SetParent($tim3ID, $_IPS['SELF']);
	IPS_SetName($tim3ID, "RouterExectimer");
	IPS_SetEventCyclic($tim3ID,0,1,0,0,1,150);      /* alle 150 sec */
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


/* Logging aktivieren
 *
 *********************************************************************************************/

	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);

/*********************************************************************************************/

	$subnet="10.255.255.255";
	$OperationCenter=new OperationCenter($CategoryIdData,$subnet);

/*********************************************************************************************/




if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Execute")
	{
	echo "\nVon der Konsole aus gestartet.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";

	/********************************************************
   	Externe Ip Adresse immer ermitteln
	**********************************************************/
	echo "\nExterne IP Adresse ermitteln.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";

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

	echo "\nIPConfig Befehl liefert ...\n";
	$ipall=""; $hostname="unknown"; $lookforgateway=false;
	//exec('ipconfig /all',$catch);   /* braucht ein MSDOS Befehl manchmal laenger als 30 Sekunden zum abarbeiten ? */
	exec('ipconfig',$catch);   /* ohne all ist es eigentlich ausreichend Information, doppelte Eintraege werden vermieden */

	$ipports=array();

	foreach($catch as $line)
   	{
		if (strlen($line)>2)
		   {
			echo "  | ".$line."\n<br>";
			if (substr($line,0,1)!=" ")
				{
				//echo "-------------------> Ueberschrift \n";
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
			printf("Port \"%s\" hat IP Adresse %s und Gateway %s Ip Adresse im Longformat : %u\n", $data["Name"],$ip,$data["Gateway"],ip2long($ip));
			}

	/********************************************************
   	die Webcam anschauen und den FTP Folder zusammenräumen
	**********************************************************/

	if (isset ($installedModules["IPSCam"]))
		{
		echo "\nWebcam anschauen und ftp Folder zusammenräumen.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";

		IPSUtils_Include ("IPSCam_Constants.inc.php",         "IPSLibrary::app::modules::IPSCam");
		IPSUtils_Include ("IPSCam_Configuration.inc.php",     "IPSLibrary::config::modules::IPSCam");

		if (isset ($OperationCenterConfig['CAM']))
			{
			/* möglicherweise sind keine FTP Folders zum zusammenräumen definiert */
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
				$WebCam_LetzteBewegungID = CreateVariableByName($cam_categoryId, "Cam_letzteBewegung", 3); /* 0 Boolean 1 Integer 2 Float 3 String */
				$WebCam_PhotoCountID = CreateVariableByName($cam_categoryId, "Cam_PhotoCount", 1);
  				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				AC_SetLoggingStatus($archiveHandlerID,$WebCam_PhotoCountID,true);
				AC_SetAggregationType($archiveHandlerID,$WebCam_PhotoCountID,1);      /* 0 normaler Wert 1 Zähler */
				IPS_ApplyChanges($archiveHandlerID);

				$WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
				AC_SetLoggingStatus($archiveHandlerID,$WebCam_MotionID,true);
				AC_SetAggregationType($archiveHandlerID,$WebCam_MotionID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);

				$count=100;
				//echo "<ol>";

				//print_r(dirToArray($verzeichnis));      /* zuviel fileeintraege, dauert zu lange */
				//print_r(scandir($verzeichnis));
				//print_r(dirToArray2($verzeichnis));       /* anzahl der neuen Dateiein einfach feststellen */

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
					} /* ende ifisdir */
				}  /* ende foreach */
			}
		}

	/********************************************************
   	Erreichbarkeit der Kameras ueberprüfen
	**********************************************************/

	if (isset ($installedModules["IPSCam"]))
		{
		$ipscam_configuration=IPSCam_GetConfiguration();
		//print_r($ipscam_configuration);
		echo "\nSind die Webcams erreichbar ....\n";
		foreach ($ipscam_configuration as $webcam)
	   	{
	   	/* es gibt einen IPS Component befehl, der wird jetzt zerlegt, da darin die IP Adresse ist */
			$webcam_config=explode(',',$webcam['Component']);
			//print_r($webcam_config);
			if ($webcam_config[0]=="IPSComponentCam_Instar")
		   	{
				$url="http://".$webcam_config[1]."/status.htm";  	/* gets the data from a URL */
				}
			if ($webcam_config[0]=="IPSComponentCam_Instar5907")
			   {
				$url="http://".$webcam_config[1]."/info.html";  	/* gets the data from a URL */
				}
			echo "  Erreichbarkeit Kamera ".$webcam['Name']."  ".$url."\n";
			$ch = curl_init($url);
			$timeout = 5;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);           // return web page
			curl_setopt($ch, CURLOPT_USERPWD, $webcam_config[2].":".$webcam_config[3]);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_HEADER, false);                    // don't return headers
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);          // follow redirects, wichtig da die Root adresse automatisch umgeleitet wird
		   curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko"); // who am i
   		curl_setopt($ch, CURLOPT_ENCODING, "");       // handle all encodings
	   	curl_setopt($ch, CURLOPT_AUTOREFERER, true);     // set referer on redirect
		   curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);      // timeout on connect
   		curl_setopt($ch, CURLOPT_TIMEOUT, 120);      // timeout on response
	   	curl_setopt($ch, CURLOPT_MAXREDIRS, 10);       // stop after 10 redirects
			$data = curl_exec($ch);
			/* Curl Debug Funktionen */


		  	$err     = curl_errno( $ch );
   		$errmsg  = curl_error( $ch );
	   	$header  = curl_getinfo( $ch );
			curl_close($ch);
			echo "    Channel :".$ch." und IP Adresse aus Header ".$header["primary_ip"].":".$header["primary_port"]."\n";

			if ($err>0)
			   {
				echo "    Nicht erreicht, Fehler ".$err." von ";
				print_r($errmsg);
				echo "\n";
				//print_r($header);
				}
			else
			   {
				switch ($webcam_config[0])
				   {
			   	case "IPSComponentCam_Instar":
						$result1=substr($data,strpos($data,"write(id)"),50);
						$result2=substr($data,strpos($data,"write(sys_ver)"),50);
						$result3=substr($data,strpos($data,"alarm_status_info"),50);
						if (strpos($data,"write(id)")==false)
			   			{
							echo "    Nicht ausgelesen.\n";
							}
						else
						   {
				   		echo "    erreicht !\n";
							//echo "  KameraID         : ".htmlentities($result1)."\n";
							//echo "  Firmware Version : ".htmlentities($result2)."\n";
							//echo "  Alarm Status     : ".htmlentities($result3)."\n";
							}
						break;
				   case "IPSComponentCam_Instar5907":
						$result1=substr($data,strpos($data,"Kamera ID"),50);
						if (strpos($data,"Kamera ID")==false)
			   			{
							echo "    Nicht ausgelesen.\n";
							}
						else
						   {
				   		echo "    erreicht !\n";
							echo "    KameraID         : ".htmlentities($result1)."\n";
							}
					default:
					   break;
					}
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
   	Die entfernten logserver auf Erreichbarkeit prüfen
	**********************************************************/
	
if (isset ($installedModules["RemoteAccess"]))
	{
	IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
	
	
	}
	
	/********************************************************
   	Auswertung Router MR3420   curl
	**********************************************************/

	echo "\nAuswertung Router Daten.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n\n";

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
   	Auswertung der angeschlossenen Router
	**********************************************************/

   	foreach ($OperationCenterConfig['ROUTER'] as $router)
		   {
			//print_r($router);
			
			/********************************************************
   			Auswertung Router MR3420 mit imacro
			**********************************************************/

		   echo "Ergebnisse vom Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
			if ($router['TYP']=='MR3420')
			   {
				//$OperationCenter->write_routerdata_MR3420($router);   // keine logging Einträge machen
				}
			if ($router['TYP']=='MBRN3000')
			   {
				//$OperationCenter->write_routerdata_MBRN3000($router);   // keine logging Einträge machen
				}
			if ($router['TYP']=='RT1900ac')
			   {
				$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$CategoryIdData);
				if ($router_categoryId==false)
				   {
					$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
					IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
					IPS_SetParent($router_categoryId,$CategoryIdData);
					}
				$host          = $router["IPADRESSE"];
				$community     = "public";                                                                         // SNMP Community
				$binary        = "C:\Scripts\ssnmpq\ssnmpq.exe";    // Pfad zur ssnmpq.exe
				$debug         = true;                                                                             // Bei true werden Debuginformationen (echo) ausgegeben
				$snmp=new SNMP($router_categoryId, $host, $community, $binary, $debug);
				$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.4", "eth0_ifInOctets", "Counter32");
				$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.5", "eth1_ifInOctets", "Counter32");
				$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.4", "eth0_ifOutOctets", "Counter32");
				$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.5", "eth1_ifOutOctets", "Counter32");
				$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.8", "wlan0_ifInOctets", "Counter32");
				$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.8", "wlan0_ifOutOctets", "Counter32");
				$result=$snmp->update(true);           /* mit Parameter true erfolgt kein Logging, also Spontanabfrage */
				print_r($result);
				
      		/*		if (($ByteID=@IPS_GetVariableIDByName("MBytes_".$ipadresse['IPAdresse'],$router_categoryId))==false)
         				{
						  	$ByteID = CreateVariableByName($router_categoryId, "MBytes_".$ipadresse['IPAdresse'], 2);
							IPS_SetVariableCustomProfile($ByteID,'MByte');
							AC_SetLoggingStatus($archiveHandlerID,$ByteID,true);
							AC_SetAggregationType($archiveHandlerID,$ByteID,0);
							IPS_ApplyChanges($archiveHandlerID);
							}  */
				}
	   	}

		//$handle2=fopen($router["MacroDirectory"]."router_".$router['TYP']."_".$router['NAME'].".iim","w");

	/********************************************************
   	Logspeicher anlegen und auslesen
	**********************************************************/

	echo "Logspeicher ausgedruckt:\n";
	echo 	$log_OperationCenter->PrintNachrichten();

	/********************************************************
   	ARP für alle IP Adressen im Netz
	**********************************************************/

	echo "ARP Auswertung für alle bekannten MAC Adressen aus AllgDefinitionen.       ".(microtime(true)-$startexec)." Sekunden\n";
	$OperationCenter->find_Hostnames();

	/********************************************************
   	Sys_Ping durchführen basierend auf ermittelter mactable
	**********************************************************/

	echo "\nSys_Ping für alle bekannten IP Adressen durchführen:                              ".(microtime(true)-$startexec)." Sekunden\n";
	$ipadressen=LogAlles_Hostnames();   /* lange Liste in Allgemeine Definitionen */

	if (isset ($installedModules["IPSCam"]))
		{
		$mactable=$OperationCenter->get_macipTable($subnet);
		//print_r($mactable);
		$categoryId_SysPing    = CreateCategory('SysPing',   $CategoryIdData, 200);
		foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
			{
			$CamStatusID = CreateVariableByName($categoryId_SysPing, "Cam_".$cam_name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
			if (isset($mactable[$cam_config['MAC']]))
			   {
				echo "Sys_ping Kamera : ".$cam_name." mit MAC Adresse ".$cam_config['MAC']." und IP Adresse ".$mactable[$cam_config['MAC']]."\n";
				$status=Sys_Ping($mactable[$cam_config['MAC']],1000);
				if ($status)
					{
					echo "Kamera wird erreicht   !\n";
					if (GetValue($CamStatusID)==false)
					   {  /* Statusänderung */
						$log_OperationCenter->LogMessage('SysPing Statusaenderung von Cam_'.$cam_name.' auf Erreichbar');
						$log_OperationCenter->LogNachrichten('SysPing Statusaenderung von Cam_'.$cam_name.' auf Erreichbar');
						SetValue($CamStatusID,true);
				   	}
					}
				else
					{
					echo "Kamera wird NICHT erreicht   !\n";
					if (GetValue($CamStatusID)==true)
					   {  /* Statusänderung */
						$log_OperationCenter->LogMessage('SysPing Statusaenderung von Cam_'.$cam_name.' auf NICHT Erreichbar');
						$log_OperationCenter->LogNachrichten('SysPing Statusaenderung von Cam_'.$cam_name.' auf NICHT Erreichbar');
						SetValue($CamStatusID,false);
					   }
					}
				}
			else  /* mac adresse nicht bekannt */
			   {
			   echo "Sys_ping Kamera : ".$cam_name." mit Mac Adresse ".$cam_config['MAC']." nicht bekannt.\n";
			   }
			} /* Ende foreach */
		}

	if (isset ($installedModules["LedAnsteuerung"]))
		{
		Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\LedAnsteuerung\LedAnsteuerung_Configuration.inc.php");
		$device_config=LedAnsteuerung_Config();
		$device="LED"; $identifier="IPADR"; /* IP Adresse im Config Feld */
		$OperationCenter->device_ping($device_config, $device, $identifier);
		}

	if (isset ($installedModules["DENONsteuerung"]))
		{
		Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");
		$device_config=Denon_Configuration();
		$device="Denon"; $identifier="IPADRESSE";   /* IP Adresse im Config Feld */
		$OperationCenter->device_ping($device_config, $device, $identifier);
		}

	$device="Router"; $identifier="IPADRESSE";   /* IP Adresse im Config Feld */
	$OperationCenter->device_ping($OperationCenterConfig['ROUTER'], $device, $identifier);

	/********************************************************
   	Router daten ausgeben
	**********************************************************/

  	foreach ($OperationCenterConfig['ROUTER'] as $router)
	   {
	   echo "Ergebnisse vom Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$CatIdData);
		if ($router['TYP']=='MBRN3000')
		   {
			//$OperationCenter->sort_routerdata($router);
			//$OperationCenter->get_routerdata($router);
			echo "MBRN3000 Werte von Heute   : ".$OperationCenter->get_routerdata_MBRN3000($router,true)." Mbyte \n";
			echo "MBRN3000 Werte von Gestern : ".$OperationCenter->get_routerdata_MBRN3000($router,false)." Mbyte \n";
		   }
		if ($router['TYP']=='MR3420')
		   {
			$OperationCenter->sort_routerdata($router);
			$OperationCenter->get_routerdata($router);
			}
		if ($router['TYP']=='RT1900ac')
		   {
			$OperationCenter->get_routerdata($router);
			}
		}

	echo "\nEnde Execute.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";

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
			IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Router Auswertung");
			/********************************************************
		   Einmal am Tag: nun den Datenverbrauch über den router auslesen
			**********************************************************/
	   	foreach ($OperationCenterConfig['ROUTER'] as $router)
			   {
			   echo "Timer: Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
				//print_r($router);
				if ($router['TYP']=='MR3420')
				   {
					/* und gleich ausprobieren */
		   		IPS_ExecuteEX(ADR_Programs."Mozilla Firefox/firefox.exe", "imacros://run/?m=router_".$router['TYP']."_".$router['NAME'].".iim", false, false, 1);
					SetValue($ScriptCounterID,1);
					IPS_SetEventActive($tim3ID,true);
					IPSLogger_Dbg(__file__, "Router MR3420 Auswertung gestartet.");
		   		}
				if ($router['TYP']=='RT1900ac')
				   {
					$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$CategoryIdData);
					if ($router_categoryId==false)
					   {
						$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
						IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
						IPS_SetParent($router_categoryId,$CategoryIdData);
						}
					$host          = $router["IPADRESSE"];
					$community     = "public";                                                                         // SNMP Community
					$binary        = "C:\Scripts\ssnmpq\ssnmpq.exe";    // Pfad zur ssnmpq.exe
					$debug         = true;                                                                             // Bei true werden Debuginformationen (echo) ausgegeben
					$snmp=new SNMP($router_categoryId, $host, $community, $binary, $debug);
					$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.4", "eth0_ifInOctets", "Counter32");
					$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.5", "eth1_ifInOctets", "Counter32");
					$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.4", "eth0_ifOutOctets", "Counter32");
					$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.5", "eth1_ifOutOctets", "Counter32");
					$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.8", "wlan0_ifInOctets", "Counter32");
					$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.8", "wlan0_ifOutOctets", "Counter32");
					$snmp->update(false,"eth0_ifInOctets","eth0_ifOutOctets"); /* Parameter false damit Werte geschrieben werden und die beiden anderen Parameter geben an welcher Wert für download und upload verwendet wird */
					}
				if ($router['TYP']=='MBRN3000')
				   {
					$OperationCenter->write_routerdata_MBRN3000($router);
				   }
		   	} /* Ende foreach */

			/********************************************************
	   	Einmal am Tag: Sys_Ping durchführen basierend auf ermittelter mactable
			**********************************************************/

			if (isset ($installedModules["IPSCam"]))
				{
				$mactable=$OperationCenter->get_macipTable($subnet);
				print_r($mactable);
				$categoryId_SysPing    = CreateCategory('SysPing',   $CategoryIdData, 200);
				$categoryId_RebootCtr  = CreateCategory('RebootCounter',   $CategoryIdData, 210);
				foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
					{
					$CamStatusID = CreateVariableByName($categoryId_SysPing,   "Cam_".$cam_name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
					$CamRebootID = CreateVariableByName($categoryId_RebootCtr, "Cam_".$cam_name, 1); /* 0 Boolean 1 Integer 2 Float 3 String */
					if (isset($mactable[$cam_config['MAC']]))
			   		{
						echo "Timer, Sys_ping Kamera : ".$cam_name." mit MAC Adresse ".$cam_config['MAC']." und IP Adresse ".$mactable[$cam_config['MAC']]."\n";
						$status=Sys_Ping($mactable[$cam_config['MAC']],1000);
						if ($status)
							{
							echo "Kamera wird erreicht   !\n";
							if (GetValue($CamStatusID)==false)
					   		{  /* Statusänderung */
								$log_OperationCenter->LogMessage('SysPing Statusaenderung von Cam_'.$cam_name.' auf Erreichbar');
								$log_OperationCenter->LogNachrichten('SysPing Statusaenderung von Cam_'.$cam_name.' auf Erreichbar');
								SetValue($CamStatusID,true);
								SetValue($CamRebootID,0);
				   			}
							}
						else
							{
							echo "Kamera wird NICHT erreicht   !\n";
							if (GetValue($CamStatusID)==true)
							   {  /* Statusänderung */
								$log_OperationCenter->LogMessage('SysPing Statusaenderung von Cam_'.$cam_name.' auf NICHT Erreichbar');
								$log_OperationCenter->LogNachrichten('SysPing Statusaenderung von Cam_'.$cam_name.' auf NICHT Erreichbar');
								SetValue($CamStatusID,false);
								SetValue($CamRebootID,(GetValue($CamRebootID)+1));
							   }
							}
						}
					else  /* mac adresse nicht bekannt */
					   {
			   		echo "Sys_ping Kamera : ".$cam_name." mit Mac Adresse ".$cam_config['MAC']." nicht bekannt.\n";
					   }
					} /* Ende foreach */
				}

			if (isset ($installedModules["LedAnsteuerung"]))
				{
				Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\LedAnsteuerung\LedAnsteuerung_Configuration.inc.php");
				$device_config=LedAnsteuerung_Config();
				$device="LED"; $identifier="IPADR"; /* IP Adresse im Config Feld */
				$OperationCenter->device_ping($device_config, $device, $identifier);
				}

			if (isset ($installedModules["DENONsteuerung"]))
				{
				Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");
				$device_config=Denon_Configuration();
				$device="Denon"; $identifier="IPADRESSE";   /* IP Adresse im Config Feld */
				$OperationCenter->device_ping($device_config, $device, $identifier);
				}
	      break;
	      
	   case $tim2ID:
			IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Webcam zusammenraeumen:");
			/********************************************************
		   nun die Webcam zusammenraeumen, derzeit alle 150 Sekunden
			**********************************************************/
			$count=0;
			if (isset ($OperationCenterConfig['CAM']))
				{
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
					$WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0); /* 0 Boolean 1 Integer 2 Float 3 String */

					$count1=move_camPicture($verzeichnis,$WebCam_LetzteBewegungID);      /* in letzteBewegungID wird das Datum/Zeit des letzten kopierten Fotos geschrieben */
					$count+=$count1;
					$PhotoCountID = CreateVariableByName($CategoryIdData, "Webcam_PhotoCount", 1);
					SetValue($PhotoCountID,GetValue($PhotoCountID)+$count1);                   /* uebergeordneten Counter und Cam spezifischen Counter nachdrehen */
					SetValue($WebCam_PhotoCountID,GetValue($WebCam_PhotoCountID)+$count1);
					if ($count1>0)
					   {
					   SetValue($WebCam_MotionID,true);
					   }
					else
					   {
  				   	SetValue($WebCam_MotionID,false);
					   }
					}
				}
			if ($count>0) {
				IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Webcam zusammengeraeumt, ".$count." Fotos verschoben.");
				}
	      break;
	      
	   case $tim3ID:
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." Routerdaten empfangen, auswerten. ScriptcountID:".GetValue($ScriptCounterID));

			/******************************************************************************************
		     Router Auswertung, zuerst Imacro und danach die Files auswerten, Schritt für Schritt
			*********************************************************************************************/
			
			$counter=GetValue($ScriptCounterID);
			switch ($counter)
			   {
				case 3:
				   /* reserviert für Nachbearbeitung */
		      	SetValue($ScriptCounterID,0);
			      IPS_SetEventActive($tim3ID,false);
		      	break;
			   case 2:
					/* Router Auswertung */
			   	foreach ($OperationCenterConfig['ROUTER'] as $router)
		   			{
						/********************************************************
   						Auswertung Router MR3420 mit imacro
						**********************************************************/
					   echo "Ergebnisse vom Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
						if ($router['TYP']=='MR3420')
						   {
							$OperationCenter->write_routerdata_MR3420($router);
							}
						/* die anderen Router werden direkt abgefragt, keine nachgelagerte Auswertung notwendig */
				   	}

					SetValue($ScriptCounterID,$counter+1);
		      	break;
			   case 1:
					/* Zeit gewinnen */
			      SetValue($ScriptCounterID,$counter+1);
					break;
			   case 0:
				default:
				   break;
			   }
			break;
		default:
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." ID unbekannt.");
		   break;
		}
	}


/**************************************************************************************************************/




	
	
?>

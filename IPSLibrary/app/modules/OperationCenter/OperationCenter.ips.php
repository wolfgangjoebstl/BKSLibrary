<?

/***********************************************************************
 *
 * OperationCenter
 *
 * Allerlei betriebliche Abfragen und Wartungsmassnahmen
 *
 *
 * RouterAufruftimer
 * RouterExectimer
 * SysPingTimer			alle 60 Minuten syspingalldevices
 *						für alle bekannten Geräte (Router, LED, Denon, Cams) pingen und Status ermitteln
 *						eventuell auch reboot, reset für erhöhte betriebssicherheit
 * CyclicUpdate			Update aller IPS Module, zB immer am 12. des Monates
 * CopyScriptsTimer
 * FileStatus
 * SystemInfo
 * Reserved
 * Maintenance			Starte Maintennance Funktionen 
 * MoveLogFiles			Maintenance Funktion: Move Log Files 
 *
 *
 *
 * 
 *
 ***********************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
IPSUtils_Include ("SNMP_Library.class.php","IPSLibrary::app::modules::OperationCenter");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

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
	$moduleManager = new IPSModuleManager('OperationCenter',$repository);
	}

$installedModules = $moduleManager->GetInstalledModules();

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
$scriptId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.OperationCenter'));

$scriptIdOperationCenter   = IPS_GetScriptIDByName('OperationCenter', $CategoryIdApp);

	/******************************************************

	Webfront zusammenräumen
	
	*******************************************************/
	
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


	/******************************************************

				INIT, Timer, sollte eigentlich in der Install Routine sein
				
				MoveCamFiles				, alle 150 Sec
				RouterAufruftimer       , immer um 0:20

	*************************************************************/

if (isset ($installedModules["IPSCam"]))
	{
	echo "Modul IPSCam ist installiert.\n";
	echo "   Timer 150 Sekunden aktivieren um Camfiles wegzuschlichten.\n";
	$tim2ID = @IPS_GetEventIDByName("MoveCamFiles", $scriptId);
	IPS_SetEventActive($tim2ID,true);
	}
else
	{
	echo "Modul IPSCam ist NICHT installiert.\n";
	$tim2ID = @IPS_GetEventIDByName("MoveCamFiles", $scriptId);
	if ($tim2ID > 0)  {	IPS_SetEventActive($tim2ID,false);  }
	}

$tim1ID  = @IPS_GetEventIDByName("RouterAufruftimer", $scriptId);
$tim3ID  = @IPS_GetEventIDByName("RouterExectimer", $scriptId);
$tim4ID  = @IPS_GetEventIDByName("SysPingTimer", $scriptId);
$tim5ID  = @IPS_GetEventIDByName("CyclicUpdate", $scriptId);
$tim6ID  = @IPS_GetEventIDByName("CopyScriptsTimer", $scriptId);
$tim7ID  = @IPS_GetEventIDByName("FileStatus", $scriptId);
$tim8ID  = @IPS_GetEventIDByName("SystemInfo", $scriptId);
$tim9ID  = @IPS_GetEventIDByName("Reserved", $scriptId);
$tim10ID = @IPS_GetEventIDByName("Maintenance",$scriptId);						/* Starte Maintennance Funktionen */	
$tim11ID = @IPS_GetEventIDByName("MoveLogFiles",$scriptId);						/* Maintenance Funktion: Move Log Files */	

/*********************************************************************************************/

$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

$ScriptCounterID=CreateVariableByName($CategoryIdData,"ScriptCounter",1);

$OperationCenterConfig = OperationCenter_Configuration();
$OperationCenterSetup  = OperationCenter_SetUp();

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
	$OperationCenter=new OperationCenter($subnet);

/*********************************************************************************************/




if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Execute")
	{
	echo "\nVon der Konsole aus gestartet.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";

	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,30)." ".$modules."\n";
		}
	echo $inst_modules."\n\n";

	echo "Category Data ID   : ".$CategoryIdData."\n";
	echo "Category App ID    : ".$CategoryIdApp."\n";
	echo "Category Script ID : ".$scriptId."\n\n";

	echo "Folgende Module werden von OperationCenter bearbeitet:\n";
	if (isset ($installedModules["IPSLight"])) { 			echo "  Modul IPSLight ist installiert.\n"; } else { echo "Modul IPSLight ist NICHT installiert.\n"; }
	if (isset ($installedModules["IPSPowerControl"])) { 	echo "  Modul IPSPowerControl ist installiert.\n"; } else { echo "Modul IPSPowerControl ist NICHT installiert.\n";}
	if (isset ($installedModules["IPSCam"])) { 				echo "  Modul IPSCam ist installiert.\n"; } else { echo "Modul IPSCam ist NICHT installiert.\n"; }
	if (isset ($installedModules["RemoteAccess"])) { 		echo "  Modul RemoteAccess ist installiert.\n"; } else { echo "Modul RemoteAccess ist NICHT installiert.\n"; }
	if (isset ($installedModules["LedAnsteuerung"])) { 	echo "  Modul LedAnsteuerung ist installiert.\n"; } else { echo "Modul LedAnsteuerung ist NICHT installiert.\n";}
	if (isset ($installedModules["DENONsteuerung"])) { 	echo "  Modul DENONsteuerung ist installiert.\n"; } else { echo "Modul DENONsteuerung ist NICHT installiert.\n";}
	if (isset ($installedModules["IPSWeatherForcastAT"])){ 	echo "  Modul IPSWeatherForcastAT ist installiert.\n"; } else { echo "Modul IPSWeatherForcastAT ist NICHT installiert.\n";}
	echo "\n";

	echo "Timer Installation : \n";
	echo "  Timer RouterAufruftimer OID : ".$tim1ID."\n";
	echo "  Timer MoveCamFiles OID      : ".$tim2ID."\n";
	echo "  Timer RouterExectimer OID   : ".$tim3ID."\n";
	echo "  Timer SysPingTimer OID      : ".$tim4ID."\n";
	echo "  Timer CyclicUpdate OID      : ".$tim5ID."\n";
	echo "  Timer CopyScriptsTimer OID  : ".$tim6ID."\n";
	echo "  Timer FileStatus OID        : ".$tim7ID."\n";
	echo "  Timer SystemInfo OID        : ".$tim8ID."\n";
	echo "  Timer Reserved OID          : ".$tim9ID."\n";
	echo "  Timer Maintenance OID       : ".$tim10ID."\n";
	echo "  Timer MoveLogs OID          : ".$tim11ID."\n";
		
	/********************************************************
   	Erreichbarkeit Hardware
	**********************************************************/

	$OperationCenter->HardwareStatus();

	/********************************************************
   	Externe Ip Adresse immer ermitteln
	**********************************************************/
	
	echo "\nExterne IP Adresse ermitteln.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";
	$result=$OperationCenter->whatismyIPaddress1()[0];
	if ($result["IP"]==false)
		{
		echo "Whatismyipaddress reagiert nicht. Ip Adresse anders ermitteln.\n";
		}
	else
		{
	   	echo "Whatismyipaddress liefert : \"".$result["IP"]."\"\n";
	   	}
	   
	$result=$OperationCenter->ownIPaddress();
	foreach ($result as $ip => $data)
		{
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
			
			/* möglicherweise ist der Archivstatus für die Variablen noch nicht definiert --> Teil des Install Prozesses */
			foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
				{
				echo "Create Variable Structure für Kamera : ".$cam_name." im Verzeichnis ".$cam_config['FTPFOLDER']."\n";
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

				// Test, ob ein Verzeichnis angegeben wurde
				if ( is_dir ( $verzeichnis ))
					{
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
				}  /* ende foreach */

			/* eigentliche Zusammenräum Routine, siehe auch Timeraufrufe */
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
				$WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0, '~Motion', null ); /* 0 Boolean 1 Integer 2 Float 3 String */


					$count=move_camPicture($verzeichnis,$WebCam_LetzteBewegungID);
					SetValue($WebCam_PhotoCountID,GetValue($WebCam_PhotoCountID)+$count);
			

				}  /* ende foreach */
			}
		}

	/********************************************************
    *	Erreichbarkeit der Kameras ueberprüfen
    *
    * andere Routine als bei pingalldevices, es ist kein Sys_ping sondern direkter Webzugriff
    *
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
			$RouterResult=$OperationCenter->write_routerdata_MBRN3000($router,true);   // keine logging Einträge machen, debug=false
			print_r($RouterResult);
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
			$debug         = false;                                                                             // Bei true werden Debuginformationen (echo) ausgegeben
			$snmp=new SNMP_OperationCenter($router_categoryId, $host, $community, $binary, $debug);
			$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.4", "eth0_ifInOctets", "Counter32");
			$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.5", "eth1_ifInOctets", "Counter32");
			$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.4", "eth0_ifOutOctets", "Counter32");
			$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.5", "eth1_ifOutOctets", "Counter32");
			$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.8", "wlan0_ifInOctets", "Counter32");
			$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.8", "wlan0_ifOutOctets", "Counter32");
			$result=$snmp->update(true);           /* mit Parameter true erfolgt kein Logging, also Spontanabfrage */
			//print_r($result);
				
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

	echo "============================================================================================================\n";

	/********************************************************
   	Router daten ausgeben
	**********************************************************/

  	foreach ($OperationCenterConfig['ROUTER'] as $router)
	   {
	   echo "\n";
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

	echo "============================================================================================================\n";

	/********************************************************
   	Sys Ping the Devices
	**********************************************************/

	//SysPingAllDevices($OperationCenter,$log_OperationCenter);
	$OperationCenter->SysPingAllDevices($log_OperationCenter);

	echo "============================================================================================================\n";

	/********************************************************
    *
	 *	UpdateAll
	 *
	 * Aufpassen, ueberschreibt wieder alle bereits programmierten Änderungen. besser auskommentiert lassen
	 *
	 **********************************************************/

	//CyclicUpdate();

	echo "============================================================================================================\n";

	/********************************************************
   	CopyScripts
	**********************************************************/

	//$OperationCenter->CopyScripts();

	/********************************************************
   	Move Logs
	**********************************************************/

	//$OperationCenter->MoveLogs();

	/************************************************************************************
  	StatusInformation von sendstatus auf ein Dropboxverzeichnis kopieren
  	einmal als aktuelle Werte und einmal als historische Werte
	*************************************************************************************/
	echo "============================================================================================================\n";
	echo "Operation center, Filestatus (Send_status) berechnen.\n";
	$OperationCenter->FileStatus();
	
	if (isset ($installedModules["Amis"]))
		{
		echo "============================================================================================================\n";
		echo "Operation center, AMIS Registertabellen in Zusammenfassung neu berechnen.\n";

		/* html Tabellen der Energieregister und Historien ebenfalls updaten */
		IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
		IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');
		$MeterConfig = get_MeterConfiguration();				
		$amis=new Amis();
		$dataOID=$amis->getAMISDataOids();
		$tableID = CreateVariableByName($dataOID, "Historie-Energie", 3);
		$regID = CreateVariableByName($dataOID, "Aktuelle-Energie", 3);
		$Meter=$amis->writeEnergyRegistertoArray($MeterConfig);
		SetValue($tableID,$amis->writeEnergyRegisterTabletoString($Meter));
		SetValue($regID,$amis->writeEnergyRegisterValuestoString($Meter));		
		}				

	/************************************************************************************
	 * System Informationen berechnen
	 *
	 *************************************************************************************/

	echo "============================================================================================================\n";
	echo "Operation center, SystemInfo.\n";

	$OperationCenter->SystemInfo();


	echo "============================================================================================================\n";
	echo "\nEnde Execute.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";


	} /* ende Execute */

	
/*********************************************************************************************/


if ($_IPS['SENDER']=="Variable")
	{

	}

/********************************************************************************************
 *
 * Timer Aufrufe gestaffelt
 *
 * 1 Router auslesen starten
 * 2 Webcam Files zusammenräumen
 * 3 Router auswerten, wird von 1 gestartet
 * 4 Sysping alle geräte, alle 60 Minuten
 * 5 automatisches Update der App Routinen, immer am 12. des Monats
 * 6 Scripts auf Dropbox kopieren
 * 7 File Status kopieren
 * 8 System Info auslesen und speichern
 * 9 frei
 * 10 logfiles zusammenräumen starten
 * 11 Logfiles verschieben, bis alle weg, von 10 gestartet 
 *
 **********************************************************************************************/


if ($_IPS['SENDER']=="TimerEvent")
	{
	switch ($_IPS['EVENT'])
		{
		case $tim1ID:        /* einmal am Tag Router auslesen*/
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
					IPS_ExecuteEX($OperationCenterSetup["FirefoxDirectory"]."firefox.exe", "imacros://run/?m=router_".$router['TYP']."_".$router['NAME'].".iim", false, false, 1);
					//IPS_ExecuteEX(ADR_Programs."Mozilla Firefox/firefox.exe", "imacros://run/?m=router_".$router['TYP']."_".$router['NAME'].".iim", false, false, 1);
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
					$snmp=new SNMP_OperationCenter($router_categoryId, $host, $community, $binary, $debug);
					$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.4", "eth0_ifInOctets", "Counter32");
					$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.5", "eth1_ifInOctets", "Counter32");
					$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.4", "eth0_ifOutOctets", "Counter32");
					$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.5", "eth1_ifOutOctets", "Counter32");
					$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.8", "wlan0_ifInOctets", "Counter32");
					$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.8", "wlan0_ifOutOctets", "Counter32");
					$snmp->update(false,"eth0_ifInOctets","eth0_ifOutOctets"); /* Parameter false damit Werte geschrieben werden und die beiden anderen Parameter geben an welcher Wert für download und upload verwendet wird */
					IPSLogger_Dbg(__file__, "Router RT1900ac Auswertung abgeschlossen.");
					}
				if ($router['TYP']=='MBRN3000')
				   {
					$OperationCenter->write_routerdata_MBRN3000($router);
				   }
				} /* Ende foreach */
			break;
		
		case $tim2ID:
			IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Webcam FTP Dateien zusammenraeumen:");
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
					$WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0, '~Motion', null ); /* 0 Boolean 1 Integer 2 Float 3 String */

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
				} /* Ende isset */
			if ($count>0)
				{
				IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Webcam zusammengeraeumt, ".$count." Fotos verschoben.");
				}
			break;
		case $tim3ID:
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." Routerdaten empfangen, auswerten. ScriptcountID:".GetValue($ScriptCounterID));

			/******************************************************************************************
			 *
			 * Router Auswertung, zuerst Imacro und danach die Files auswerten, Schritt für Schritt
			 * Wird nur von tim1 gestartet und arbeitet das vom Router heruntergeladene File ab
			 *
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
			
		case $tim4ID:
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." SysPingAllDevices");
			/********************************************************
			 *
			 * Alle 60 Minuten: Sys_Ping durchführen basierend auf ermittelter mactable
			 *
			 **********************************************************/
			$OperationCenter->SysPingAllDevices($log_OperationCenter);
			break;
		case $tim5ID:
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." CyclicUpdate");
			/************************************************************************************
	   	 *
			 * Einmal am 12.Tag des Monates: CyclicUpdate, alle Module automatisch updaten
			 *
			 *************************************************************************************/
			CyclicUpdate();
			break;
		case $tim6ID:
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." CopyScriptsTimer");
			/************************************************************************************
	   		 *
			 * Alle Scripts auf ein Dropboxverzeichnis kopieren und wenn notwendig umbenennen
			 * Timer einmal am Tag
			 *
			 *************************************************************************************/
			$OperationCenter->CopyScripts();
			break;
		case $tim7ID:
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." FileStatusTimer");
			/************************************************************************************
 			 *
			 * StatusInformation von sendstatus auf ein Dropboxverzeichnis kopieren
	   		 * Timer einmal am Tag um 3:50
	   		 *
			 *************************************************************************************/
			$OperationCenter->FileStatus();
			if (isset ($installedModules["Amis"]))
				{
				/* html Tabellen der Energieregister und Historien ebenfalls updaten */
				IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
				IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');
				$MeterConfig = get_MeterConfiguration();				
				$amis=new Amis();
				$dataOID=$amis->getAMISDataOids();
				$tableID = CreateVariableByName($dataOID, "Historie-Energie", 3);
				$regID = CreateVariableByName($dataOID, "Aktuelle-Energie", 3);
				$Meter=$amis->writeEnergyRegistertoArray($MeterConfig);
				SetValue($tableID,$amis->writeEnergyRegisterTabletoString($Meter));
				SetValue($regID,$amis->writeEnergyRegisterValuestoString($Meter));		
				}			
			break;
		case $tim8ID:
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." FileStatusTimer");
			/************************************************************************************
 			 *
			 * System Information von sysinfo auswerten
	   		 * Timer einmal am Tag um 00:50
	   		 *
			 *************************************************************************************/
			$OperationCenter->SystemInfo();
			break;		
		case $tim9ID:
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." Homematic RSSI auslesen");
			/************************************************************************************
 			 *
			 * Timer Homematic, einmal am Tag
			 * Timer einmal am Tag um 02:40
			 * Es werden die wicgtigsten Homematic Geraete mit Kanal 0 angelegt.. Passiert in Install.
			 * Hier die RSSI Werte auslesen und die RSSI Tabelle updaten. Bleibt dann so den ganzen Tag  
			 * 
			 *************************************************************************************/		
			break;		
		case $tim10ID:
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." Maintenance");
			/************************************************************************************
 			 *
			 * Maintenance Modi
			 * Timer "Maintzenance" einmal am Tag um 01:20, schaltet derzeit nur Timer11 ein, damit dieser zyklisch abarbeitet
	  		 *
			 *************************************************************************************/	
			IPS_SetEventActive($tim11ID,true);	
			break;		
		case $tim11ID:
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." Maintenance Intervall, Logdateien zusammenräumen");
			/************************************************************************************
 			 *
			 * Log Dateien zusammenräumen, alle 150 Sekunden, bis fertig, von Timer 10 gestartet
			 * am Ende auch noch alte Statusdateien in der Dropbox loeschen
			 *
			 *************************************************************************************/	
			$countlog=$OperationCenter->MoveLogs();
			if ($countlog == 100)
				{
				IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Logdatei zusammengeraeumt, ".$countlog." Dateien verschoben. Es gibt noch mehr.");				
				}
			elseif ($countlog>0)
				{
				IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Logdatei zusammengeraeumt, ".$countlog." Dateien verschoben.");
				}
			else
				{
				IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Logdatei zusammengeraeumt, restliche ".$countlog." Dateien verschoben.");	
				$countdir=$OperationCenter->PurgeLogs();
				IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Logdatei zusammengeraeumt, ".$countdir." alte Verzeichnisse geloescht.");
				$countdelstatus=$OperationCenter->FileStatusDelete();	
				IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Dropbox Statusdateien zusammengeraeumt, ".$countdelstatus." alte Dateien geloescht.");
				IPS_SetEventActive($tim11ID,false);
				}		
			break;
		default:
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." ID unbekannt.");
		   break;
		}
	}




	
?>
<?

	/*
	 * This file is part of the IPSLibrary.
	 *
	 * The IPSLibrary is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published
	 * by the Free Software Foundation, either version 3 of the License, or
	 * (at your option) any later version.
	 *
	 * The IPSLibrary is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with the IPSLibrary. If not, see http://www.gnu.org/licenses/gpl.txt.
	 */ 
	 
	 /***********************************************
	  *
	  * verschiedene Routinen udn Definitionen die von allen Modulen benötigt werden können
	  * 
	  * send_status  Ausgabe des aktuellen Status aktuell oder historisch
	  * GetInstanceIDFromHMID
	  * writeLogEvent
	  * writeLogEventClass
	  * GetValueIfFormatted
	  *
	  *
	  *
	  *
	  ****************************************************************/

IPSUtils_Include ("IPSModuleManagerGUI.inc.php", "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
   
 
/* ObjectID Adresse vom send email server */

$sendResponse = 30887; //ID einer SMTP Instanz angeben, um Rückmelde-Funktion zu aktivieren

/* Heizung */

define("ADR_Heizung_AZ",38731);
define("ADR_Heizung_WZ",34901);
define("ADR_Zusatzheizung_WZ",40443);
define("ADR_Heizung_KZ",30616);
define("ADR_Zusatzheizung_KZ",36154);

define("ADR_Router_Stromversorgung",41865);
define("ADR_WebCam_Stromversorgung",13735);
define("ADR_GBESwitch_Stromversorgung",23695);


/* IP Adressen */


define("ADR_Gateway","10.0.1.200");
define("ADR_Homematic","10.0.1.3");

define("ADR_Webcam_innen","10.0.1.2");
define("ADR_Webcam_innen_Port","2001");

define("ADR_Webcam_lbg","hupo35.ddns-instar.de");

define("ADR_Webcam_Keller","10.0.1.122");
define("ADR_Webcam_Keller_Port","2002");

define("ADR_Webcam_Garten","10.0.1.123");
define("ADR_Webcam_Garten_Port","2003");


/* Wohnungszustand */

define("STAT_WohnungszustandInaktiv",0);
define("STAT_WohnungszustandFerien",1);
define("STAT_WohnungszustandUnterwegs",2);
define("STAT_WohnungszustandStandby",3);
define("STAT_WohnungszustandAktiv",4);
define("STAT_WohnungszustandTop",5);

/* erkannter Zustand */
define("STAT_KommtnachHause",18);
define("STAT_Bewegung9",15);
define("STAT_Bewegung8",14);
define("STAT_Bewegung7",13);
define("STAT_Bewegung6",12);
define("STAT_Bewegung5",11);
define("STAT_Bewegung4",10);
define("STAT_Bewegung3", 9);
define("STAT_Bewegung2", 8);
define("STAT_Bewegung",  7);
define("STAT_WenigBewegung",6);
define("STAT_KeineBewegung",5);
define("STAT_Unklar",4);
define("STAT_Undefiniert",3);
define("STAT_vonzuHauseweg",2);
define("STAT_nichtzuHause",1);
define("STAT_Abwesend",0);

/****************************************************************************************************
 * immer wenn eine Statusmeldung per email angefragt wird 
 *
 * Ausgabe des Status für aktuelle und historische Werte
 *
 ****************************************************************************************/

function send_status($aktuell, $startexec=0)
	{
	if ($startexec==0) { $startexec=microtime(true); }
	$sommerzeit=false;
	$einleitung="Erstellt am ".date("D d.m.Y H:i")." fuer die ";

	/* alte Programaufrufe sind ohne Parameter, daher für den letzten Tag */

	if ($aktuell)
	   {
	   $einleitung.="Ausgabe der aktuellen Werte vom Gerät : ".IPS_GetName(0)." .\n";
	   echo ">>Ausgabe der aktuellen Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
	   }
	else
	   {
	   $einleitung.="Ausgabe der historischen Werte - Vortag vom Gerät : ".IPS_GetName(0).".\n";
	   echo ">>Ausgabe der historischen Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
	   }
	if (date("I")=="1")
		{
		$einleitung.="Wir haben jetzt Sommerzeit, daher andere Reihenfolge der Ausgabe.\n";
		$sommerzeit=true;
		}
	$einleitung.="\n";
	
	// Repository
	$repository = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';

	$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);

	$versionHandler = $moduleManager->VersionHandler();
	$versionHandler->BuildKnownModules();
	$knownModules     = $moduleManager->VersionHandler()->GetKnownModules();
	$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
	$inst_modules = "Verfügbare Module und die installierte Version :\n\n";
	$inst_modules.= "Modulname                  Version    Version      Beschreibung\n";
	$inst_modules.= "                          verfügbar installiert                   \n";
	
	$upd_modules = "Module die upgedated werden müssen und die installierte Version :\n\n";
	$upd_modules.= "Modulname                  Version    Status/inst.Version         Beschreibung\n";

	foreach ($knownModules as $module=>$data)
		{
		$infos   = $moduleManager->GetModuleInfos($module);
		$inst_modules .=  str_pad($module,26)." ".str_pad($infos['Version'],10);
		if (array_key_exists($module, $installedModules))
			{
			$inst_modules .= " ".str_pad($infos['CurrentVersion'],10)."   ";
			if ($infos['CurrentVersion']!=$infos['Version'])
				{
				$inst_modules .= "**";
				$upd_modules .=  str_pad($module,26)." ".str_pad($infos['Version'],10)." ".str_pad($infos['CurrentVersion'],10)."   ".$infos['Description']."\n";
				}
			}
		else
			{
			$inst_modules .= "  none        ";
		   }
		$inst_modules .=  $infos['Description']."\n";
		}
	$inst_modules .= "\n".$upd_modules;
	echo ">>Auswertung der Module die upgedatet werden müssen. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";

	if (isset($installedModules["Amis"])==true)
	   {
		$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Amis');
		$updatePeriodenwerteID=IPS_GetScriptIDByName('BerechnePeriodenwerte',$parentid);
		//echo "Script zum Update der Periodenwerte:".$updatePeriodenwerteID."\n";
		IPS_RunScript($updatePeriodenwerteID);
		echo ">>AMIS Update Periodenwerte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
		}

	/* Alle werte aus denen eine Ausgabe folgt initialisieren */

	$cost=""; $internet=""; $statusverlauf=""; $ergebnis_tabelle=""; $alleStromWerte=""; $ergebnisTemperatur=""; $ergebnisRegen=""; $aktheizleistung=""; $ergebnis_tagesenergie=""; $alleTempWerte=""; $alleHumidityWerte="";
	$ergebnisStrom=""; $ergebnisStatus=""; $ergebnisBewegung=""; $ergebnisGarten=""; $IPStatus=""; $ergebnisSteuerung=""; $energieverbrauch="";

	$ergebnisOperationCenter="";
	$ergebnisErrorIPSLogger="";
	$ServerRemoteAccess="";
	$SystemInfo="";

/******************************************************************************************
 *
 * Allgemeiner Teil, unabhängig von Hardware oder Server
 *
 * zuerst aktuell dann historisch
 *		
 ******************************************************************************************/

	if ($aktuell) /* aktuelle Werte */
		{
		$alleTempWerte="";
		$alleHumidityWerte="";
		$alleMotionWerte="";
		$alleHelligkeitsWerte="";
		$alleStromWerte="";
		$alleHeizungsWerte="";
        $guthaben="";
		
		/******************************************************************************************
		
		Allgemeiner Teil, Auswertung für aktuelle Werte
		
		******************************************************************************************/
		if ( (isset($installedModules["RemoteReadWrite"])==true) || (isset($installedModules["EvaluateHardware"])==true) )
			{
			if (isset($installedModules["EvaluateHardware"])==true) 
				{
				IPSUtils_Include ("EvaluateHardware_include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
				}
			//else IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");

			$Homematic = HomematicList();
			$FS20= FS20List();

			$alleTempWerte.="\n\nAktuelle Temperaturwerte direkt aus den HW-Registern:\n\n";
			$alleTempWerte.=ReadTemperaturWerte();

			$alleHumidityWerte.="\n\nAktuelle Feuchtigkeitswerte direkt aus den HW-Registern:\n\n";
		
			foreach ($Homematic as $Key)
				{
				/* Alle Homematic Feuchtigkeitswerte ausgeben */
				if (isset($Key["COID"]["HUMIDITY"])==true)
					{
	      			$oid=(integer)$Key["COID"]["HUMIDITY"]["OID"];
					$alleHumidityWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
					}
				}

			$alleHelligkeitsWerte.="\n\nAktuelle Helligkeitswerte direkt aus den HW-Registern:\n\n";
			foreach ($Homematic as $Key)
				{
				/* Alle Homematic Bewegungsmelder ausgeben */
				if ( (isset($Key["COID"]["MOTION"])==true) )
					{
					/* alle Bewegungsmelder, aber die Helligkeitswerte, um herauszufinden ob bei einem der Melder die Batterie leer ist */
					if ( isset($Key["COID"]["BRIGHTNESS"]["OID"]) ) {$oid=(integer)$Key["COID"]["BRIGHTNESS"]["OID"]; }
					else { $oid=(integer)$Key["COID"]["ILLUMINATION"]["OID"]; }
   					$variabletyp=IPS_GetVariable($oid);
					if ($variabletyp["VariableProfile"]!="")
						{
						$alleHelligkeitsWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
						}
					else
						{
						$alleHelligkeitsWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
						}
					}
				}


			$alleMotionWerte.="\n\nAktuelle Bewegungswerte direkt aus den HW-Registern:\n\n";
			foreach ($Homematic as $Key)
				{
				/* Alle Homematic Bewegungsmelder ausgeben */
				if ( (isset($Key["COID"]["MOTION"])==true) )
					{
					/* alle Bewegungsmelder */

					$oid=(integer)$Key["COID"]["MOTION"]["OID"];
					$variabletyp=IPS_GetVariable($oid);
					if ($variabletyp["VariableProfile"]!="")
						{
						$alleMotionWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
						}
					else
						{
						$alleMotionWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
						}
					}
				}

			/**
			  * Bewegungswerte von den FS20 Registern, eigentlich schon ausgemustert
			  *
			  *******************************************************************************/

			//if (isset($installedModules["RemoteAccess"])==true)
				{
				//IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
				IPSUtils_Include ("IPSComponentLogger_Configuration.inc.php","IPSLibrary::config::core::IPSComponent");				
				$TypeFS20=RemoteAccess_TypeFS20();
				foreach ($FS20 as $Key)
					{
					/* FS20 alle Bewegungsmelder ausgeben */
					if ( (isset($Key["COID"]["MOTION"])==true) )
				   		{
		   				/* alle Bewegungsmelder */

				      	$oid=(integer)$Key["COID"]["MOTION"]["OID"];
   		   				$variabletyp=IPS_GetVariable($oid);
						if ($variabletyp["VariableProfile"]!="")
					   		{
							$alleMotionWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
							}
						else
						   	{
							$alleMotionWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
							}
						}
					/* Manche FS20 Variablen sind noch nicht umprogrammiert daher mit Config Datei in Remote Access verknüpfen */
					if ((isset($Key["COID"]["StatusVariable"])==true))
						{
						foreach ($TypeFS20 as $Type)
		   					{
							if (($Type["OID"]==$Key["OID"]) and ($Type["Type"]=="Motion"))
								{
   								$oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
								$variabletyp=IPS_GetVariable($oid);
								IPS_SetName($oid,"MOTION");
								if ($variabletyp["VariableProfile"]!="")
									{
									$alleMotionWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
									}
								else
									{
									$alleMotionWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
									}
								}
							}
						}
					}
				}

			$alleStromWerte.="\n\nAktuelle Energiewerte direkt aus den HW-Registern:\n\n";
			foreach ($Homematic as $Key)
				{
				/* Alle Homematic Energiesensoren ausgeben */
				if ( (isset($Key["COID"]["VOLTAGE"])==true) )
					{
					/* alle Energiesensoren */

					$oid=(integer)$Key["COID"]["ENERGY_COUNTER"]["OID"];
					$variabletyp=IPS_GetVariable($oid);
					if ($variabletyp["VariableProfile"]!="")
						{
						$alleStromWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
						}
					else
						{
						$alleStromWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
						}
					}
				}


			$alleHeizungsWerte.=ReadThermostatWerte();
			$alleHeizungsWerte.=ReadAktuatorWerte();
						
			$ergebnisRegen.="\n\nAktuelle Regenmengen direkt aus den HW-Registern:\n\n";
			$regenmelder=0;
			foreach ($Homematic as $Key)
				{
				/* Alle Homematic Energiesensoren ausgeben */
				if ( (isset($Key["COID"]["RAIN_COUNTER"])==true) )
					{
					/* alle Regenwerte */
					$regenmelder++;
					$oid=(integer)$Key["COID"]["RAIN_COUNTER"]["OID"];
					$variabletyp=IPS_GetVariable($oid);
					if ($variabletyp["VariableProfile"]!="")
						{
						$ergebnisRegen.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
						}
					else
						{
						$ergebnisRegen.=str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
						}
					}
				}
			if ($regenmelder==0) $ergebnisRegen="";	/* Ausgabe rückgängig machen, es gibt keine Regenmelder. */	
				
			if (isset($installedModules["Gartensteuerung"])==true)
				{
				echo "Die Regenwerte der letzten 20 Tage ausgeben.\n";
				$ergebnisRegen.="\nIn den letzten 20 Tagen hat es zu folgenden Zeitpunkten geregnet:\n";
				/* wenn die Gartensteuerung installiert ist, gibt es einen Regensensor der die aktuellen Regenmengen der letzten 10 Tage erfassen kann */
				IPSUtils_Include ('Gartensteuerung_Library.class.ips.php', 'IPSLibrary::app::modules::Gartensteuerung');
				$gartensteuerung = new Gartensteuerung();
				$rainResults=$gartensteuerung->listRainEvents(20);
				foreach ($rainResults as $regeneintrag)
					{
					$ergebnisRegen.="  Regenbeginn ".date("d.m H:i",$regeneintrag["Beginn"]).
					   	"  Regenende ".date("d.m H:i",$regeneintrag["Ende"]).
		   				" mit insgesamt ".number_format($regeneintrag["Regen"], 1, ",", "").
		   				" mm Regen. Max pro Stunde ca. ".number_format($regeneintrag["Max"], 1, ",", "")."mm/Std.\n";
					}				
				}
				

		  	echo ">>RemoteReadWrite. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
			}

		/******************************************************************************************/

		if (isset($installedModules["Amis"])==true)
			{
			$alleStromWerte.="\n\nAktuelle Stromverbrauchswerte direkt aus den gelesenen und dafür konfigurierten Registern:\n\n";

			$amisdataID  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
			IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
	        IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis'); 
            $Amis = new Amis();           
			$MeterConfig = $Amis->getMeterConfig();

			foreach ($MeterConfig as $meter)
				{
				if ($meter["TYPE"]=="Amis")
				   {
			   	$alleStromWerte.="\nAMIS Zähler im ".$meter["NAME"].":\n\n";
					$amismeterID = CreateVariableByName($amisdataID, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
					$AmisID = CreateVariableByName($amismeterID, "AMIS", 3);
					$AmisVarID = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
					$AMIS_Werte=IPS_GetChildrenIDs($AmisVarID);
					for($i = 0; $i < sizeof($AMIS_Werte);$i++)
						{
						//$alleStromWerte.=str_pad(IPS_GetName($AMIS_Werte[$i]),30)." = ".GetValue($AMIS_Werte[$i])." \n";
						if (IPS_GetVariable($AMIS_Werte[$i])["VariableCustomProfile"]!="")
						   {
							$alleStromWerte.=str_pad(IPS_GetName($AMIS_Werte[$i]),30)." = ".str_pad(GetValueFormatted($AMIS_Werte[$i]),30)."   (".date("d.m H:i",IPS_GetVariable($AMIS_Werte[$i])["VariableChanged"]).")\n";
							}
						else
					   	{
							$alleStromWerte.=str_pad(IPS_GetName($AMIS_Werte[$i]),30)." = ".str_pad(GetValue($AMIS_Werte[$i]),30)."   (".date("d.m H:i",IPS_GetVariable($AMIS_Werte[$i])["VariableChanged"]).")\n";
							}
						}
					}
				if ($meter["TYPE"]=="Homematic")
				   {
				   $alleStromWerte.="\nHomematic Zähler im ".$meter["NAME"].":\n\n";
					$HM_meterID = CreateVariableByName($amisdataID, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
					$HM_Wirkenergie_meterID = CreateVariableByName($HM_meterID, "Wirkenergie", 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
					if (IPS_GetVariable($HM_Wirkenergie_meterID)["VariableCustomProfile"]!="")
					   {
						$alleStromWerte.=str_pad(IPS_GetName($HM_Wirkenergie_meterID),30)." = ".str_pad(GetValueFormatted($HM_Wirkenergie_meterID),30)."   (".date("d.m H:i",IPS_GetVariable($HM_Wirkenergie_meterID)["VariableChanged"]).")\n";
						}
					else
					   {
						$alleStromWerte.=str_pad(IPS_GetName($HM_Wirkenergie_meterID),30)." = ".str_pad(GetValue($HM_Wirkenergie_meterID),30)."   (".date("d.m H:i",IPS_GetVariable($HM_Wirkenergie_meterID)["VariableChanged"]).")\n";
						}
					$HM_Wirkleistung_meterID = CreateVariableByName($HM_meterID, "Wirkleistung", 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
					if (IPS_GetVariable($HM_Wirkleistung_meterID)["VariableCustomProfile"]!="")
				   	{
						$alleStromWerte.=str_pad(IPS_GetName($HM_Wirkleistung_meterID),30)." = ".str_pad(GetValueFormatted($HM_Wirkleistung_meterID),30)."   (".date("d.m H:i",IPS_GetVariable($HM_Wirkleistung_meterID)["VariableChanged"]).")\n";
						}
					else
					   {
						$alleStromWerte.=str_pad(IPS_GetName($HM_Wirkleistung_meterID),30)." = ".str_pad(GetValue($HM_Wirkleistung_meterID),30)."   (".date("d.m H:i",IPS_GetVariable($HM_Wirkleistung_meterID)["VariableChanged"]).")\n";
						}

					} /* endeif */
				} /* ende foreach */
		  	echo ">>AMIS. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
			} /* endeif */

		/******************************************************************************************/


		if (isset($installedModules["OperationCenter"])==true)
			{
			$ergebnisOperationCenter.="\nAusgabe der Erkenntnisse des Operation Centers, Logfile: \n\n";

			IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
			IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
			IPSUtils_Include ("SNMP_Library.class.php","IPSLibrary::app::modules::OperationCenter");
			IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
			
			$CatIdData  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.OperationCenter');
			$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CatIdData, 20);
			$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
			$log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);

			$subnet="10.255.255.255";
			$OperationCenter=new OperationCenter($subnet);
            $DeviceManager = new DeviceManagement();
			
			$ergebnisOperationCenter.="Lokale IP Adresse im Netzwerk : \n";
			$result=$OperationCenter->ownIPaddress();
			foreach ($result as $ip => $data)
				{
				$ergebnisOperationCenter.="  Port \"".$data["Name"]."\" hat IP Adresse ".$ip." und das Gateway ".$data["Gateway"].".\n";
				}
			
			$result=$OperationCenter->whatismyIPaddress1()[0];
			if ($result["IP"]== true)
				{
				$ergebnisOperationCenter.= "Externe IP Adresse : \n";
				$ergebnisOperationCenter.= "  Server liefert : ".$result["IP"]."\n\n";
				}
			$ergebnisOperationCenter.="Systeminformationen : \n\n";
			$ergebnisOperationCenter.=$OperationCenter->readSystemInfo()."\n";
				
			$ergebnisOperationCenter.="Angeschlossene bekannte Endgeräte im lokalen Netzwerk : \n\n";
			$ergebnisOperationCenter.=$OperationCenter->find_HostNames();
			$OperationCenterConfig = OperationCenter_Configuration();

			$ergebnisOperationCenter.="\nAktuelles Datenvolumen für die verwendeten Router : \n";
			foreach ($OperationCenterConfig['ROUTER'] as $router)
				{
				$ergebnisOperationCenter.="  Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER'];
				$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$CatIdData);
				if ($router['TYP']=='MBRN3000')
					{
					$ergebnisOperationCenter.="\n";
					$ergebnisOperationCenter.="    Werte von Heute   : ".round($OperationCenter->get_routerdata_MBRN3000($router,true),2)." Mbyte \n";
					}
				elseif ($router['TYP']=='MR3420')
					{
					$ergebnisOperationCenter.="\n";
					$ergebnisOperationCenter.="    Werte von Heute   : ".round($OperationCenter->get_routerdata_MR3420($router),2)." Mbyte \n";
					}
				elseif ($router['TYP']=='RT1900ac')
					{
					$ergebnisOperationCenter.="\n";
					$ergebnisOperationCenter.="    Werte von Heute   : ".round($OperationCenter->get_routerdata_RT1900($router,true),2)." Mbyte \n";
					}
				else
					{
					$ergebnisOperationCenter.="    Keine Werte. Router nicht unterstützt.\n";
					}
				}
			$ergebnisOperationCenter.="\n";
			
			$ergebnisOperationCenter.=$OperationCenter->writeSysPingResults();
			
			$ergebnisOperationCenter.="\n\nErreichbarkeit der Hardware Register/Instanzen, zuletzt erreicht am .... :\n\n"; 
			$ergebnisOperationCenter.=$DeviceManager->HardwareStatus(true);
			
			$ergebnisErrorIPSLogger.="\nAus dem Error Log der letzten Tage :\n\n";
			$ergebnisErrorIPSLogger.=$OperationCenter->getIPSLoggerErrors();

            /******************************************************************************************/

		    $alleHM_Errors=$DeviceManager->HomematicFehlermeldungen();
			
		  	echo ">>OperationCenter. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
			}

		/******************************************************************************************/

		$ServerRemoteAccess .="LocalAccess Variablen dieses Servers:\n\n";
			
		/* Remote Access Crawler für Ausgabe aktuelle Werte */

		$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		$jetzt=time();
		$endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
		$starttime=$endtime-60*60*24*1; /* ein Tag */

		$ServerRemoteAccess.="RemoteAccess Variablen aller hier gespeicherten Server:\n\n";

		$visID=@IPS_GetObjectIDByName ( "Visualization", 0 );
		$ServerRemoteAccess .=  "Visualization ID : ";
		if ($visID==false)
			{
			$ServerRemoteAccess .=  "keine\n";
			}
		else
			{
			$ServerRemoteAccess .=  $visID."\n";
			$visWebRID=@IPS_GetObjectIDByName ( "Webfront-Retro", $visID );
			$ServerRemoteAccess .=  "  Webfront Retro     ID : ";
			if ($visWebRID==false) {$ServerRemoteAccess .=  "keine\n";} else {$ServerRemoteAccess .=  $visWebRID."\n";}

			$visMobileID=@IPS_GetObjectIDByName ( "Mobile", $visID );
			$ServerRemoteAccess .=  "  Mobile             ID : ";
			if ($visMobileID==false) {$ServerRemoteAccess .=  "keine\n";} else {$ServerRemoteAccess .=  $visMobileID."\n";}

			$visWebID=@IPS_GetObjectIDByName ( "WebFront", $visID );
			$ServerRemoteAccess .=  "  WebFront           ID : ";
			if ($visWebID==false)
				{
				$ServerRemoteAccess .=  "keine\n";
				}
			else
				{
				$ServerRemoteAccess .=  $visWebID."\n";
				$visUserID=@IPS_GetObjectIDByName ( "User", $visWebID );
				$ServerRemoteAccess .=  "    Webfront User          ID : ";
				if ($visUserID==false) {$ServerRemoteAccess .=  "keine\n";} else {$ServerRemoteAccess .=  $visUserID."\n";}

				$visAdminID=@IPS_GetObjectIDByName ( "Administrator", $visWebID );
				$ServerRemoteAccess .=  "    Webfront Administrator ID : ";
				if ($visAdminID==false)
					{
					$ServerRemoteAccess .=  "keine\n";
					}
				else
					{
					$ServerRemoteAccess .=  $visAdminID."\n";

					$visRemAccID=@IPS_GetObjectIDByName ( "RemoteAccess", $visAdminID );
					$ServerRemoteAccess .=  "      RemoteAccess ID : ";
					if ($visRemAccID==false)
						{
						$ServerRemoteAccess .=  "keine\n";
						}
					else
						{
						$ServerRemoteAccess .=  $visRemAccID."\n";
						$server=IPS_GetChildrenIDs($visRemAccID);
						foreach ($server as $serverID)
						   {
						   $ServerRemoteAccess .=  "        Server    ID : ".$serverID." Name : ".IPS_GetName($serverID)."\n";
							$categories=IPS_GetChildrenIDs($serverID);
							foreach ($categories as $categoriesID)
							   {
							   $ServerRemoteAccess .=  "          Category  ID : ".$categoriesID." Name : ".IPS_GetName($categoriesID)."\n";
								$objects=IPS_GetChildrenIDs($categoriesID);
								$objectsbyName=array();
								foreach ($objects as $key => $objectID)
								   {
								   $objectsbyName[IPS_GetName($objectID)]=$objectID;
									}
								ksort($objectsbyName);
								//print_r($objectsbyName);
								foreach ($objectsbyName as $objectID)
								    {
									$werte = @AC_GetLoggedValues($archiveHandlerID, $objectID, $starttime, $endtime, 0);
									if ($werte===false)
										{
										$log="kein Log !!";
										}
									else
									   {
										$log=sizeof($werte)." logged in 24h";
										}
									if ( (IPS_GetVariable($objectID)["VariableProfile"]!="") or (IPS_GetVariable($objectID)["VariableCustomProfile"]!="") )
								   	    {
                                        echo "Variablenprofil von $objectID (".IPS_GetName($objectID).") erkannt: Standard ".IPS_GetVariable($objectID)["VariableProfile"]." Custom ".IPS_GetVariable($objectID)["VariableCustomProfile"]."\n";
										$ServerRemoteAccess .=  "            ".str_pad(IPS_GetName($objectID),30)." = ".str_pad(GetValueFormatted($objectID),30)."   (".date("d.m H:i",IPS_GetVariable($objectID)["VariableChanged"]).") "
										       .$log."\n";
										}
									else
									    {
										$ServerRemoteAccess .=  "            ".str_pad(IPS_GetName($objectID),30)." = ".str_pad(GetValue($objectID),30)."   (".date("d.m H:i",IPS_GetVariable($objectID)["VariableChanged"]).") "
										       .$log."\n";
										}
									//print_r(IPS_GetVariable($objectID));
									} /* object */
								} /* Category */
							} /* Server */
						} /* RemoteAccess */
					}  /* Administrator */
				}   /* Webfront */
			} /* Visualization */

		//echo $ServerRemoteAccess;

    	/*****************************************************************************************
		 *
		 * Guthaben Verwaltung von Simkarten
		 *
		 *******************************************************************************/

		if (isset($installedModules["Guthabensteuerung"])==true)
			{
			IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

			$guthabenid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
			$GuthabenConfig = get_GuthabenConfiguration();
			//print_r($GuthabenConfig);
			$guthaben="\nGuthabenstatus:\n";
			foreach ($GuthabenConfig as $TelNummer)
				{
				if (strtoupper($TelNummer["STATUS"])=="ACTIVE")
					{
					$phone1ID = CreateVariableByName($guthabenid, "Phone_".$TelNummer["NUMMER"], 3);
					$phone_Summ_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Summary", 3);
					$guthaben .= "\n    ".GetValue($phone_Summ_ID);
					}
				}
			$guthaben .= "\n\n";			
			}
		else
			{
			$guthaben="";
			}
        echo $guthaben;

		$guthaben.="Ausgabe Status der aktiven SIM Karten :\n\n";
        $guthaben.="    Nummer       Name                             letztes File von             letzte Aenderung Guthaben    letzte Aufladung\n";		
        foreach ($GuthabenConfig as $TelNummer)
			{
			//print_r($TelNummer);
			$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');

			$phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
			$dateID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Date", 3);
			$ldateID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_loadDate", 3);
			$udateID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_unchangedDate", 3);
			$userID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_User", 3);
			if (strtoupper($TelNummer["STATUS"])=="ACTIVE") 
				{
				$guthaben.="    ".$TelNummer["NUMMER"]."  ".str_pad(GetValue($userID),30)."  ".str_pad(GetValue($dateID),30)." ".str_pad(GetValue($udateID),30)." ".GetValue($ldateID)."\n";
				}
			//echo "Telnummer ".$TelNummer["NUMMER"]." ".$udateID."\n";
			}
         $guthaben.="\n";    

    	/*****************************************************************************************
		 *
		 * SystemInfo des jeweiligen PCs ausgeben
		 *
		 *******************************************************************************/

		$SystemInfo.="System Informationen dieses Servers:\n\n";

		exec('systeminfo',$catch);   /* ohne all ist es eigentlich ausreichend Information, doppelte Eintraege werden vermieden */

		$PrintLines="";
		foreach($catch as $line)
   		{
			if (strlen($line)>2)
			   {
   			$PrintLines.=$line."\n";
				}  /* ende strlen */
		  	}
		$SystemInfo.=$PrintLines."\n\n";
		
		
		if ($sommerzeit)
	      {
			$ergebnis=$einleitung.$ergebnisTemperatur.$ergebnisRegen.$ergebnisOperationCenter.$aktheizleistung.$alleHeizungsWerte.$ergebnis_tagesenergie.$alleTempWerte.
			$alleHumidityWerte.$alleHelligkeitsWerte.$alleMotionWerte.$alleStromWerte.$alleHM_Errors.$ServerRemoteAccess.$guthaben.$SystemInfo.$ergebnisErrorIPSLogger;
			}
		else
		   {
			$ergebnis=$einleitung.$aktheizleistung.$ergebnis_tagesenergie.$ergebnisTemperatur.$alleTempWerte.$alleHumidityWerte.$alleHelligkeitsWerte.$alleHeizungsWerte.
			$ergebnisOperationCenter.$alleMotionWerte.$alleStromWerte.$alleHM_Errors.$ServerRemoteAccess.$guthaben.$SystemInfo.$ergebnisErrorIPSLogger;
		   }
	  	echo ">>Ende aktuelle Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
		}
	else   /* historische Werte */
		{
		$alleHeizungsWerte="";

		/******************************************************************************************

		Allgemeiner Teil, Auswertung für historische Werte

		******************************************************************************************/


		/**************Stromverbrauch, Auslesen der Variablen von AMIS *******************************************************************/

		$ergebnistab_energie="";
		if (isset($installedModules["Amis"])==true)
			{
			/* nur machen wenn AMIS installiert */
			IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');		
			IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
            $Amis = new Amis();           
			$MeterConfig = $Amis->getMeterConfig();
			
			$ergebnistab_energie="";
			
			$amis=new Amis();
			$Meter=$amis->writeEnergyRegistertoArray($MeterConfig);		/* alle Energieregister in ein Array schreiben */
			$ergebnistab_energie.=$amis->writeEnergyRegisterTabletoString($Meter,false);	/* output with no html encoding */	
			$ergebnistab_energie.="\n\n";					
			$ergebnistab_energie.=$amis->writeEnergyRegisterValuestoString($Meter,false);	/* output with no html encoding */	
			$ergebnistab_energie.="\n\n";					
			$ergebnistab_energie.=$amis->writeEnergyPeriodesTabletoString($Meter,false,true);	/* output with no html encoding, values in kwh */
			$ergebnistab_energie.="\n\n";
			$ergebnistab_energie.=$amis->writeEnergyPeriodesTabletoString($Meter,false,false);	/* output with no html encoding, values in EUR */
			$ergebnistab_energie.="\n\n";

			if (false)
				{
				$zeile = array("Datum" => array("Datum",0,1,2), "Heizung" => array("Heizung",0,1,2), "Datum2" => array("Datum",0,1,2), "Energie" => array("Energie",0,1,2), "EnergieVS" => array("EnergieVS",0,1,2));

				$amisdataID  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
			
				foreach ($MeterConfig as $meter)
					{
					$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
					$meterdataID = CreateVariableByName($amisdataID, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
					/* ID von Wirkenergie bestimmen */
					switch ( strtoupper($meter["TYPE"]) )
						{	
						case "AMIS":
							$AmisID = CreateVariableByName($meterdataID, "AMIS", 3);
							//$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
							//$variableID = IPS_GetObjectIDByName ( 'Wirkenergie' , $zaehlerid );
							$variableID = IPS_GetObjectIDByName ( 'Wirkenergie' , $AmisID );
							break;
						case "HOMEMATIC":
						case "REGISTER":	
						default:
							$variableID = CreateVariableByName($meterdataID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
							break;
						}	
					/* Energiewerte der ketzten 10 Tage als Zeitreihe beginnend um 1:00 Uhr */
					$jetzt=time();
					$endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
					$starttime=$endtime-60*60*24*10;
	
					$werte = AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime, $endtime, 0);
					$vorigertag=date("d.m.Y",$jetzt);

					echo "Create Variableset for :".$meter["NAME"]." für Variable ".$variableID."  \n";
					echo "ArchiveHandler: ".$archiveHandlerID." Variable: ".$variableID."\n";
					echo "Werte von ".date("d.m.Y H:i:s",$starttime)." bis ".date("d.m.Y H:i:s",$endtime)."\n";
					//echo "Voriger Tag :".$vorigertag."\n";
					$laufend=1;
					$alterWert=0;
					$ergebnis_tabelle1=substr("                          ",0,12);
					foreach($werte as $wert)
						{
						$zeit=$wert['TimeStamp']-60;
						if (date("d.m.Y", $zeit)!=$vorigertag)
						   {
							$zeile["Datum2"][$laufend] = date("D d.m", $zeit);
							$zeile["Energie"][$laufend] = number_format($wert['Value'], 2, ",", "" ) ." kWh";
							echo "Werte :".$zeile["Datum2"][$laufend]." ".$zeile["Energie"][$laufend]."\n";
							if ($laufend>1) {$zeile["EnergieVS"][$laufend-1] = number_format(($alterWert-$wert['Value']), 2, ",", "" ) ." kWh";}
							$ergebnis_tabelle1.= substr($zeile["Datum2"][$laufend]."            ",0,12);
							$laufend+=1;
							$alterWert=$wert['Value'];
							//echo "Voriger Tag :".date("d.m.Y",$zeit)."\n";
							}
						$vorigertag=date("d.m.Y",$zeit);
						}
					$anzahl2=$laufend-2;
					$ergebnis_datum="";
					$ergebnis_tabelle1="";
					$ergebnis_tabelle2="";
					echo "Es sind ".$laufend." Eintraege vorhanden.\n";
					//print_r($zeile);
					$laufend=0;
					while ($laufend<=$anzahl2)
						{
						$ergebnis_datum.=substr($zeile["Datum2"][$laufend]."            ",0,12);
						$ergebnis_tabelle1.=substr($zeile["Energie"][$laufend]."            ",0,12);
						$ergebnis_tabelle2.=substr(($zeile["EnergieVS"][$laufend])."            ",0,12);
						$laufend+=1;
						//echo $ergebnis_tabelle."\n";
						}
					//$ergebnistab_energie.="Stromverbrauch der letzten Tage von ".$meter["NAME"]." :\n\n".$ergebnis_datum."\n".$ergebnis_tabelle1."\n".$ergebnis_tabelle2."\n\n";
					$ergebnistab_energie.="Stromverbrauch der letzten Tage von ".$meter["NAME"]." :\n\n";
					$ergebnistab_energie.="Energiewert aktuell ".$zeile["Energie"][1]."\n\n";
					$ergebnistab_energie.=$ergebnis_datum."\n".$ergebnis_tabelle2."\n\n";

					/* Kategorie Periodenwerte selbst suchen */
					$PeriodenwerteID = CreateVariableByName($meterdataID, "Periodenwerte", 3);
				
					$ergebnistab_energie.="Stromverbrauchs-Statistik von ".$meter["NAME"]." :\n\n";
					$ergebnistab_energie.="Stromverbrauch (1/7/30/360) : ".number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_letzterTag',$PeriodenwerteID)), 2, ",", "" );
					$ergebnistab_energie.=        " / ".number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_letzte7Tage',$PeriodenwerteID)), 2, ",", "" );
					$ergebnistab_energie.=        " / ".number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_letzte30Tage',$PeriodenwerteID)), 2, ",", "" );
					$ergebnistab_energie.=        " / ".number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_letzte360Tage',$PeriodenwerteID)), 2, ",", "" )." kWh \n";
					$ergebnistab_energie.="Stromkosten    (1/7/30/360) : ".number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_Euro_letzterTag',$PeriodenwerteID)), 2, ",", "" );
					$ergebnistab_energie.=        " / ".number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_Euro_letzte7Tage',$PeriodenwerteID)), 2, ",", "" );
					$ergebnistab_energie.=        " / ".number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_Euro_letzte30Tage',$PeriodenwerteID)), 2, ",", "" );
					$ergebnistab_energie.=        " / ".number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_Euro_letzte360Tage',$PeriodenwerteID)), 2, ",", "" )." Euro \n\n\n";
					}
				}
			
		  	echo ">>AMIS historische Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
			}
			
		/************** Guthaben auslesen ****************************************************************************/
		
		if (isset($installedModules["Guthabensteuerung"])==true)
			{
			IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

			$guthabenid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
			$GuthabenConfig = get_GuthabenConfiguration();
			//print_r($GuthabenConfig);
			$guthaben="Guthabenstatus:\n";
			foreach ($GuthabenConfig as $TelNummer)
				{
				if (strtoupper($TelNummer["STATUS"])=="ACTIVE")
					{
					$phone1ID = CreateVariableByName($guthabenid, "Phone_".$TelNummer["NUMMER"], 3);
					$phone_Summ_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Summary", 3);
					$guthaben .= "\n".GetValue($phone_Summ_ID);
					}
				}
			$guthaben .= "\n\n";			
			echo ">>Guthaben historische Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
			}
		else
			{
			$guthaben="";
			}

		/************** Werte der Custom Components ****************************************************************************/

        $alleComponentsWerte="";
		if (isset($installedModules["CustomComponents"])==true)
		   	{
            $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
        	if (!isset($moduleManager)) 
		        {
        		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		        $moduleManager = new IPSModuleManager('CustomComponent',$repository);
        		}
        	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
        	$Category=IPS_GetChildrenIDs($CategoryIdData);
            //$search=array('HeatControl','Auswertung');          // Aktuatoren in CustomComponents Daten suchen
            //$search=array('HeatSet','Auswertung');
            $search=array('*','Auswertung');
            $result=array();
            $power=array();
        	foreach ($Category as $CategoryId)
		        {
        		//echo "  Category    ID : ".$CategoryId." Name : ".IPS_GetName($CategoryId)."\n";
		        $Params = explode("-",IPS_GetName($CategoryId)); 
        		$SubCategory=IPS_GetChildrenIDs($CategoryId);
		        foreach ($SubCategory as $SubCategoryId)
        			{
                    if ( (isset($search) == false) || ( ( ($search[0]==$Params[0]) || ($search[0]=="*") ) && ( ($search[1]==$Params[1]) || ($search[1]=="*") ) ) )	
                        {
                        //echo "       ".IPS_GetName($SubCategoryId)."   ".$Params[0]."   ".$Params[1]."\n";
                        $result[]=$SubCategoryId;
		                $Values=IPS_GetChildrenIDs($SubCategoryId);
                        foreach ($Values as $valueID)                
                            {
                            $Types = explode("_",IPS_GetName($valueID));
                            switch ($Types[1])
                                {
                                case "Changetime":
                                    echo "         * ".IPS_GetName($valueID)."   ".date("d.m.y H:i:s",GetValue($valueID))."\n";
                                    break;
                                case "Power":
                                    $power[]=$valueID;
                                default:
                                    echo "         * ".IPS_GetName($valueID)."   ".GetValue($valueID)."\n";
                                    break;
                                }    
                            }
                        }
        			//$webfront_links[$Params[0]][$Params[1]][$SubCategoryId]["NAME"]=IPS_GetName($SubCategoryId);
		        	//$webfront_links[$Params[0]][$Params[1]][$SubCategoryId]["ORDER"]=IPS_GetObject($SubCategoryId)["ObjectPosition"];
        			}
		        }
            $alleComponentsWerte .= "\nErfasste Werte in CustomComponents:\n";
            $alleComponentsWerte .= getComponentValues($result,false);
			}

		/************** Detect Movement Motion Detect ****************************************************************************/

      	$alleMotionWerte="";
		print_r($installedModules);
		if ( (isset($installedModules["DetectMovement"])==true) && ( (isset($installedModules["RemoteReadWrite"])==true) || (isset($installedModules["EvaluateHardware"])==true) ) )
			{
			echo "=====================Detect Movement Motion Detect \n";
			IPSUtils_Include ('IPSComponentSensor_Motion.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');
			if (isset($installedModules["EvaluateHardware"])==true) 
				{
				IPSUtils_Include ("EvaluateHardware_include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
				}
			//elseif (isset($installedModules["RemoteReadWrite"])==true) IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");

			$Homematic = HomematicList();
			$FS20= FS20List();
			$log=new Motion_Logging();
		   
			$cuscompid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.core.IPSComponent');
		   
			$alleMotionWerte="\n\nHistorische Bewegungswerte aus den Logs der CustomComponents:\n\n";
			echo "===========================Alle Homematic Bewegungsmelder ausgeben.\n";
			foreach ($Homematic as $Key)
				{
				/* Alle Homematic Bewegungsmelder ausgeben */
				if ( (isset($Key["COID"]["MOTION"])==true) )
					{
					/* alle Bewegungsmelder */
					$oid=(integer)$Key["COID"]["MOTION"]["OID"];
					$log->Set_LogValue($oid);
					$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
					}
				}
			echo "===========================Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein.\n";
			if (isset($installedModules["RemoteAccess"])==true) IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
			$TypeFS20=RemoteAccess_TypeFS20();
			foreach ($FS20 as $Key)
				{
				/* Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein */
				if ( (isset($Key["COID"]["MOTION"])==true) )
					{
					/* alle Bewegungsmelder */
					$oid=(integer)$Key["COID"]["MOTION"]["OID"];
					$log->Set_LogValue($oid);
					$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
					}
				/* Manche FS20 Variablen sind noch nicht umprogrammiert daher mit Config Datei verknüpfen */
				if ((isset($Key["COID"]["StatusVariable"])==true))
					{
					foreach ($TypeFS20 as $Type)
						{
						if (($Type["OID"]==$Key["OID"]) and ($Type["Type"]=="Motion"))
							{
							$oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
							$variabletyp=IPS_GetVariable($oid);
							IPS_SetName($oid,"MOTION");
							$log->Set_LogValue($oid);
							$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
							}
						}
					}
				}
			$alleMotionWerte.="********* Gesamtdarstellung\n".$log->writeEvents(true,true)."\n\n";
			echo ">>DetectMovement historische Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
			}
		/******************************************************************************************/

		if (isset($installedModules["Gartensteuerung"])==true)
			{
			$gartensteuerung = new Gartensteuerung();
			$ergebnisGarten="\n\nVerlauf der Gartenbewaesserung:\n\n";
			$ergebnisGarten=$ergebnisGarten.$gartensteuerung->listEvents()."\n";
		  	echo ">>Gartensteuerung historische Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
			}

		/******************************************************************************************/

		if (isset($installedModules["OperationCenter"])==true)
		   {
			$ergebnisOperationCenter="\nAusgabe der Erkenntnisse des Operation Centers, Logfile: \n\n";

			IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
			IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
			IPSUtils_Include ("SNMP_Library.class.php","IPSLibrary::app::modules::OperationCenter");
			IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

			$CatIdData  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.OperationCenter');
			$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CatIdData, 20);
			$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
			$log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);

			$subnet="10.255.255.255";
			$OperationCenter=new OperationCenter($subnet);
			$ergebnisOperationCenter.=$log_OperationCenter->PrintNachrichten();

			$OperationCenterConfig = OperationCenter_Configuration();
			$ergebnisOperationCenter.="\nHistorisches Datenvolumen für die verwendeten Router : \n";
			$historie="";
	   	foreach ($OperationCenterConfig['ROUTER'] as $router)
			   {
			   $ergebnisOperationCenter.="  Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER'];
				$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$CatIdData);
				if ($router['TYP']=='MBRN3000')
				   {
					$ergebnisOperationCenter.="\n";
					$ergebnisOperationCenter.= "    Werte von heute     : ".$OperationCenter->get_routerdata_MBRN3000($router,true)." Mbyte \n";
					$ergebnisOperationCenter.= "    Werte von Gestern   : ".$OperationCenter->get_routerdata_MBRN3000($router,false)." Mbyte \n";
					$ergebnisOperationCenter.= "    Historie 1/7/30/30  : ".round($OperationCenter->get_router_history($router,0,1),0)."/".
										round($OperationCenter->get_router_history($router,0,7),0)."/".
					    				round($OperationCenter->get_router_history($router,0,30),0)."/".
										round($OperationCenter->get_router_history($router,30,30),0)." \n";
				   }
				elseif ($router['TYP']=='MR3420')
				   {
					$ergebnisOperationCenter.="\n";
					$ergebnisOperationCenter.= "    Werte von Heute     : ".$OperationCenter->get_router_history($router,0,1)." Mbyte. \n";
					$ergebnisOperationCenter.= "    Werte von Gestern   : ".$OperationCenter->get_router_history($router,1,1)." Mbyte. \n";
					$ergebnisOperationCenter.= "    Historie 1/7/30/30  : ".round($OperationCenter->get_router_history($router,0,1),0)."/".
										round($OperationCenter->get_router_history($router,0,7),0)."/".
					    				round($OperationCenter->get_router_history($router,0,30),0)."/".
										round($OperationCenter->get_router_history($router,30,30),0)." \n";
					}
				elseif ($router['TYP']=='RT1900ac')
				   {
					$ergebnisOperationCenter.="\n";
					$host          = $router["IPADRESSE"];
					$community     = "public";                                                                         // SNMP Community
					$binary        = "C:\Scripts\ssnmpq\ssnmpq.exe";    // Pfad zur ssnmpq.exe
					$debug         = true;                                                                             // Bei true werden Debuginformationen (echo) ausgegeben
					$snmp=new SNMP_OperationCenter($router_categoryId, $host, $community, $binary, $debug);
					$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.4", "eth0_ifInOctets", "Counter32");
					$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.4", "eth0_ifOutOctets", "Counter32");
					$result=$snmp->update(true);  /* kein Logging */
					$ergebnis=0;
					foreach ($result as $object)
						{
						$ergebnis+=$object->change;
						}
					$ergebnisOperationCenter.= "    Werte von heute     : ".round($ergebnis,2)." Mbyte \n";
					$ergebnisOperationCenter.= "    Werte von Gestern   : ".$OperationCenter->get_routerdata_RT1900($router,false)." Mbyte \n";
					$ergebnisOperationCenter.= "    Historie 1/7/30/30  : ".round($OperationCenter->get_router_history($router,0,1),0)."/".
										round($OperationCenter->get_router_history($router,0,7),0)."/".
					    				round($OperationCenter->get_router_history($router,0,30),0)."/".
										round($OperationCenter->get_router_history($router,30,30),0)." \n";
					}
				else
				   {
				   $ergebnisOperationCenter.="\n";
				   }
				}
		  	echo ">>OperationCenter historische Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
		   }
		   
		/******************************************************************************************/

	   if ($sommerzeit)
	      {
			$ergebnis=$einleitung.$ergebnisRegen.$guthaben.$cost.$internet.$statusverlauf.$ergebnisStrom.
		           $ergebnisStatus.$ergebnisBewegung.$ergebnisGarten.$ergebnisSteuerung.$IPStatus.$energieverbrauch.$ergebnis_tabelle.
					  $ergebnistab_energie.$ergebnis_tagesenergie.$ergebnisOperationCenter.$alleComponentsWerte.$alleMotionWerte.$alleHeizungsWerte.$inst_modules;
			}
		else
		   {
			$ergebnis=$einleitung.$ergebnistab_energie.$energieverbrauch.$ergebnis_tabelle.$ergebnis_tagesenergie.$alleHeizungsWerte.
			$ergebnisRegen.$guthaben.$cost.$internet.$statusverlauf.$ergebnisStrom.
		           $ergebnisStatus.$ergebnisBewegung.$ergebnisSteuerung.$ergebnisGarten.$ergebnisOperationCenter.$IPStatus.$alleComponentsWerte.$alleMotionWerte.$inst_modules;
			}
		}
  	echo ">>ENDE. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
   return $ergebnis;
}




/********************************************************************************************************************/

/* durchsucht alle Homematic Instanzen
 * nach Adresse:Port
 * wenn adresse:port uebereinstimmt die Instanz ID zurückgeben, sonst 0
 */
 
function GetInstanceIDFromHMID($sid)
	{
    $ids = IPS_GetInstanceListByModuleID("{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}");
    foreach($ids as $id)
    	{
        $a = explode(":", HM_GetAddress($id));
        $b = explode(":", $sid);
        if($a[0] == $b[0])
        	{
            return $id;
        	}
    	}
    return 0;
	}


/******************************************************************/

function writeLogEvent($event)
	{
	/* call with writelogEvent("Beschreibung")  writes to Log_Event.csv File */
	if (!file_exists("C:\Scripts\Log_Events.csv"))
		{
      	$handle=fopen("C:\Scripts\Log_Events.csv", "a");
	   	fwrite($handle, date("d.m.y H:i:s").";Eventbeschreibung\r\n");
      	fclose($handle);
		}

	$handle=fopen("C:\Scripts\Log_Events.csv","a");
	fwrite($handle, date("d.m.y H:i:s").";".$event."\r\n");
	/* unterschiedliche Event Speicherorte */

	fclose($handle);
	}

   


/**************************************************************************************************************************************/


/* Webcam hat zwei Ports, derzeit verwenden wir den WLAN Port, da er immer auf lbgtest (direkt am Thomson) funktioniert
	10.0.0.27 (es kann auch immer nur ein Dienst auf eine IP Adresse umgeroutet werden, daher immer WLAN verwenden
	ausser zur Konfiguration */
	
//define("ADR_WebCamLBG","10.0.0.27");

/* Cam Positionen : 1-4 : 'Sofa', 'Gang', 'Sessel', 'Terasse'  */

define("ADR_WebCamLBG","hupo35.ddns-instar.de");
define("ADR_WebCamBKS","sina73.ddns-instar.com");
define("ADR_GanzLinks","10.0.0.1");
define("ADR_DenonWZ","10.0.0.115");
define("ADR_DenonAZ","10.0.0.26");

/* IP Adresse iTunes SOAP Modul  */

define("ADR_SOAPModul","10.0.0.20:8085");
define("ADR_SoapServer","10.0.0.20:8085");

//define("ADR_Programs","C:/Program Files/");
define("ADR_Programs",'C:/Program Files (x86)/');

/**************************************************************************************************************************************

	Verschieden brauchbare Funktionen

**************************************************************************************************************************************/
	
	


/******************************************************************/

function writeLogEventClass($event,$class)
    {

    /* call with writelogEvent("Beschreibung")  writes to Log_Event.csv File

    */

	if (!file_exists("C:\Scripts\Log_Events.csv"))
		{
        $handle=fopen("C:\Scripts\Log_Events.csv", "a");
	    fwrite($handle, date("d.m.y H:i:s").";Eventbeschreibung\r\n");
        fclose($handle);
	    }

	$handle=fopen("C:\Scripts\Log_Events.csv","a");
	$ausgabewert=date("d.m.y H:i:s").";".$event;
	fwrite($handle, $class.$ausgabewert."\r\n");

	/* unterschiedliche Event Speicherorte */
	
	if (IPS_GetName(0)=="LBG70")
		{
		SetValue(24829,$ausgabewert);
		}
	else
	    {
		SetValue(44647,$ausgabewert);
		}
	fclose($handle);
    }


/*****************************************************************
 *
 * vereint getValue und GetValueFormatted
 * Nachdem getValueFormatted immer einen Fehler ausgibt wenn die Variable keine Formattierung unterstützt wird halt vorher abgefragt
 *
 ************************************************************************/

function GetValueIfFormatted($oid)
    {
   	$variabletyp=IPS_GetVariable($oid);
	if ($variabletyp["VariableProfile"]!="")
		{
	    $result=GetValueFormatted($oid);
		}
	else
	   	{
		$result=GetValue($oid);
		}
    return ($result);    
    }

/******************************************************************/


function CreateVariableByName($id, $name, $type, $profile="", $ident="", $position=0, $action=0)
    {
    //echo "Position steht auf $position.\n";
    //echo "CreateVariableByName: $id $name $type $profile $ident $position $action\n";
	/* type steht für 0 Boolean 1 Integer 2 Float 3 String */
	
    //global $IPS_SELF;
    $vid = @IPS_GetVariableIDByName($name, $id);
    if($vid === false)
        {
        $vid = IPS_CreateVariable($type);
        IPS_SetParent($vid, $id);
        IPS_SetName($vid, $name);
        IPS_SetInfo($vid, "this variable was created by script #".$_IPS['SELF']." ");
        }
	IPS_SetPosition($vid, $position);
    if($profile !== "") { IPS_SetVariableCustomProfile($vid, $profile); }
  	if($ident !=="") {IPS_SetIdent ($vid , $ident );}
    if($action!=0) { IPS_SetVariableCustomAction($vid,$action); }
    return $vid;
    }

/******************************************************************/

function CreateVariableByName2($name, $type,$profile,$action,$visible)
    {
    $id=IPS_GetParent($_IPS['SELF']);
    $vid = @IPS_GetVariableIDByName($name, $id);
    if($vid === false)
        {
        $vid = IPS_CreateVariable($type);
        IPS_SetParent($vid, $id);
        IPS_SetName($vid, $name);
        IPS_SetInfo($vid, "this variable was created by script #".$_IPS['SELF']);
        if($profile!='')
        {
            IPS_SetVariableCustomProfile($vid,$profile);
        }
        if($action!=0)
        {
            IPS_SetVariableCustomAction($vid,$action);
        }
        IPS_SetHidden($vid,!$visible);
        }
    return $vid;
    }

/************************************
 *
 * Original wird im Library Modul Manager verwendet 
 * Aufruf mit CreateVariable($Name,$type,$parentid, $position,$profile,$Action,$default,$icon );
 *
 *
 *
 **********************************************************/

function CreateVariable2($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault=null, $Icon='')
	{
		$VariableId = @IPS_GetObjectIDByIdent(Get_IdentByName2($Name), $ParentId);
		//echo "CreateVariable2: erzeuge Variable mit Name ".$Name." unter der Parent ID ".$ParentId." (".IPS_GetName($ParentId).") mit aktuellem Wert ".$ValueDefault." und Profil $Profile.\n";
		if ($VariableId === false) $VariableId = @IPS_GetVariableIDByName($Name, $ParentId);
		if ($VariableId === false)
			{
			//echo "    erzeuge neu !\n";
 			$VariableId = IPS_CreateVariable($Type);
			IPS_SetParent($VariableId, $ParentId);
			IPS_SetName($VariableId, $Name);
			IPS_SetIdent($VariableId, Get_IdentByName2($Name));
			IPS_SetPosition($VariableId, $Position);
  			IPS_SetVariableCustomProfile($VariableId, $Profile);
 			IPS_SetVariableCustomAction($VariableId, $Action);
			IPS_SetIcon($VariableId, $Icon);
			if ($ValueDefault===null)
				{
				switch($Type)
					{
					case 0: SetValue($VariableId, false); break; /*Boolean*/
					case 1: SetValue($VariableId, 0); break; /*Integer*/
					case 2: SetValue($VariableId, 0.0); break; /*Float*/
					case 3: SetValue($VariableId, ""); break; /*String*/
					default:
					}
				}
			else
				{
				SetValue($VariableId, $ValueDefault);
				}

			//Debug ('Created VariableId '.$Name.'='.$VariableId."");
			}
		$VariableData = IPS_GetVariable ($VariableId);
		if ($VariableData['VariableCustomProfile'] <> $Profile)
			{
			//Debug ("Set VariableProfile='$Profile' for Variable='$Name' ");
			//echo "Set VariableProfile='$Profile' for Variable='$Name' \n";
			IPS_SetVariableCustomProfile($VariableId, $Profile);
			}
		else 
			{
			//echo "Aktuelles Profil ist :".$VariableData['VariableCustomProfile']."\n";
			}	
		if ($VariableData['VariableCustomAction'] <> $Action)
			{
			//Debug ("Set VariableCustomAction='$Action' for Variable='$Name' ");
			//echo "Set VariableCustomAction='$Action' for Variable='$Name' \n";
			IPS_SetVariableCustomAction($VariableId, $Action);
			}
		UpdateObjectData2($VariableId, $Position, $Icon);
		return $VariableId;
	}

/******************************************************************/

function CreateVariableByNameFull($id, $name, $type, $profile = "")
{
    $vid = @IPS_GetVariableIDByName($name, $id);
    if($vid === false)
    {
        $vid = IPS_CreateVariable($type);
        IPS_SetParent($vid, $id);
        IPS_SetName($vid, $name);
        IPS_SetInfo($vid, "this variable was created by script #".$_IPS['SELF']);
        if($profile !== "") 
        {
            IPS_SetVariableCustomProfile($vid, $profile);
        }
    }
    return $vid;
}

/******************************************************************/

function Get_IdentByName2($name)
{
		$ident = str_replace(' ', '', $name);
		$ident = str_replace(array('ö','ä','ü','Ö','Ä','Ü'), array('oe', 'ae','ue','Oe', 'Ae','Ue' ), $ident);
		$ident = str_replace(array('"','\'','%','&','(',')','=','#','<','>','|','\\'), '', $ident);
		$ident = str_replace(array(',','.',':',';','!','?'), '', $ident);
		$ident = str_replace(array('+','-','/','*'), '', $ident);
		$ident = str_replace(array('ß'), 'ss', $ident);
		return $ident;
}

/******************************************************************/

function UpdateObjectData2($ObjectId, $Position, $Icon="")
{
		$ObjectData = IPS_GetObject ($ObjectId);
		$ObjectPath = IPS_GetLocation($ObjectId);
		if ($ObjectData['ObjectPosition'] <> $Position and $Position!==false) {
			//Debug ("Set ObjectPosition='$Position' for Object='$ObjectPath' ");
			IPS_SetPosition($ObjectId, $Position);
		}
		if ($ObjectData['ObjectIcon'] <> $Icon and $Icon!==false) {
			//Debug ("Set ObjectIcon='$Icon' for Object='$ObjectPath' ");
			IPS_SetIcon($ObjectId, $Icon);
		}

}

/**********************************************************************************************/

/******************************************************
 *
 * Summestartende,
 *
 * Gemeinschaftsfunktion, fuer die manuelle Aggregation von historisierten Daten
 *
 * Eingabe Beginnzeit Format time(), Endzeit Format time(), 0 Statuswert 1 Inkrementwert 2 test, false ohne Hochrechnung
 *
 *
 * Routine scheiter bei Ende Sommerzeit, hier wird als Strtzeit -30 Tage eine Stunde zu wenig berechnet 
 *
 ******************************************************************************************/

function summestartende($starttime, $endtime, $increment_var, $estimate, $archiveHandlerID, $variableID, $display=false )
	{
	if ($display)
		{
		echo "ArchiveHandler: ".$archiveHandlerID." Variable: ".$variableID." (".IPS_GetName(IPS_GetParent($variableID))."/".IPS_GetName($variableID).") \n";
		echo "Werte von ".date("D d.m.Y H:i:s",$starttime)." bis ".date("D d.m.Y H:i:s",$endtime)."\n";
		}
	$zaehler=0;
	$ergebnis=0;
	$increment=(integer)$increment_var;
		
	do {
		/* es könnten mehr als 10.000 Werte sein
			Abfrage generisch lassen
		*/
		
		// Eintraege für GetAggregated integer $InstanzID, integer $VariablenID, integer $Aggregationsstufe, integer $Startzeit, integer $Endzeit, integer $Limit
		$aggWerte = AC_GetAggregatedValues ( $archiveHandlerID, $variableID, 1, $starttime, $endtime, 0 );
		$aggAnzahl=count($aggWerte);
		//print_r($aggWerte);
		foreach ($aggWerte as $entry)
			{
			if (((time()-$entry["MinTime"])/60/60/24)>1) 
				{
				/* keine halben Tage ausgeben */
				$aktwert=(float)$entry["Avg"];
				if ($display) echo "     ".date("D d.m.Y H:i:s",$entry["TimeStamp"])."      ".$aktwert."\n";
				switch ($increment)
					{
					case 0:
					case 2:
						echo "*************Fehler.\n";
						break;
					case 1:        /* Statuswert, daher kompletten Bereich zusammenzählen */
						$ergebnis+=$aktwert;
						break;
					default:
					}
				}
			else
				{
				$aggAnzahl--;
				}	
			}
		if (($aggAnzahl == 0) & ($zaehler == 0)) {return 0;}   // hartes Ende wenn keine Werte vorhanden
		
		$zaehler+=1;
			
		} while (count($aggWerte)==10000);		
	if ($display) echo "   Variable: ".IPS_GetName($variableID)." mit ".$aggAnzahl." Tageswerten und ".$ergebnis." als Ergebnis.\n";
	return $ergebnis;
	}

/* alte Funktion, als Referenz */

function summestartende2($starttime, $endtime, $increment_var, $estimate, $archiveHandlerID, $variableID, $display=false )
	{
	$zaehler=0;
	$initial=true;
	$ergebnis=0;
	$vorigertag="";
	$disp_vorigertag="";
	$neuwert=0;

	$increment=(integer)$increment_var;
	//echo "Increment :".$increment."\n";
	$gepldauer=($endtime-$starttime)/24/60/60;
	do {
		/* es könnten mehr als 10.000 Werte sein
			Abfrage generisch lassen
		*/
		$werte = AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime, $endtime, 0);
		/* Dieser Teil erstellt eine Ausgabe im Skriptfenster mit den abgefragten Werten
			Nicht mer als 10.000 Werte ...
		*/
		//print_r($werte);
		$anzahl=count($werte);
		//echo "   Variable: ".IPS_GetName($variableID)." mit ".$anzahl." Werte \n";

		if (($anzahl == 0) & ($zaehler == 0)) {return 0;}   // hartes Ende wenn keine Werte vorhanden

		if ($initial)
			{
			/* allererster Durchlauf */
			$ersterwert=$werte['0']['Value'];
			$ersterzeit=$werte['0']['TimeStamp'];
			}

		if ($anzahl<10000)
			{
			/* letzter Durchlauf */
			$letzterwert=$werte[sprintf('%d',$anzahl-1)]['Value'];
			$letzterzeit=$werte[sprintf('%d',$anzahl-1)]['TimeStamp'];
			//echo "   Erster Wert : ".$werte[sprintf('%d',$anzahl-1)]['Value']." vom ".date("D d.m.Y H:i:s",$werte[sprintf('%d',$anzahl-1)]['TimeStamp']).
			//     " Letzter Wert: ".$werte['0']['Value']." vom ".date("D d.m.Y H:i:s",$werte['0']['TimeStamp'])." \n";
			}

		$initial=true;

		foreach($werte as $wert)
			{
			if ($initial)
				{
				//print_r($wert);
				$initial=false;
				//echo "   Startzeitpunkt:".date("d.m.Y H:i:s", $wert['TimeStamp'])."\n";
				}

			$zeit=$wert['TimeStamp'];
			$tag=date("d.m.Y", $zeit);
			$aktwert=(float)$wert['Value'];

			if ($tag!=$vorigertag)
				{ /* neuer Tag */
				$altwert=$neuwert;
				$neuwert=$aktwert;
				switch ($increment)
					{
					case 1:
						$ergebnis=$aktwert;
						break;
					case 2:
						if ($altwert<$neuwert)
							{
							$ergebnis+=($neuwert-$altwert);
							}
						else
							{
							//$ergebnis+=($altwert-$neuwert);
							//$ergebnis=$aktwert;
							}
						break;
					case 0:        /* Statuswert, daher kompletten Bereich zusammenzählen */
						$ergebnis+=$aktwert;
						break;
					default:
					}
				$vorigertag=$tag;
				}
			else
				{
				/* neu eingeführt, Bei Statuswert muessen alle Werte agreggiert werden */
				switch ($increment)
					{
					case 1:
					case 2:
						break;
					case 0:        /* Statuswert, daher kompletten Bereich zusammenzählen */
						$ergebnis+=$aktwert;
						break;
				default:
					}
				}

			if ($display==true)
				{
				/* jeden Eintrag ausgeben */
				//print_r($wert);
				if ($gepldauer>100)
					{
					if ($tag!=$disp_vorigertag)
						{
						echo "   ".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . number_format($aktwert, 3, ".", "") ." ergibt in Summe: " . number_format($ergebnis, 3, ".", "") . PHP_EOL;
						$disp_vorigertag=$tag;
						}
					}
				else
					{
					echo "   ".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . number_format($aktwert, 3, ".", "") ." ergibt in Summe: " . number_format($ergebnis, 3, ".", "") . PHP_EOL;
					}
				}
			$zaehler+=1;
			}
		$endtime=$zeit;
		} while (count($werte)==10000);

	$dauer=($ersterzeit-$letzterzeit)/24/60/60;
	echo "   Bearbeitete Werte:".$zaehler." für ".number_format($dauer, 2, ",", "")." Tage davon erwartet: ".$gepldauer." \n";
	switch ($increment)
	   {
	   case 0:
  	   case 2:
			if ($estimate==true)
				{
				echo "   Vor Hochrechnung ".number_format($ergebnis, 3, ".", "");
				$ergebnis=($ergebnis)*$gepldauer/$dauer;
		     	echo " und nach Hochrechnung ".number_format($ergebnis, 3, ".", "")." \n";
				}
	      break;
	   case 1:
			if ($estimate==true)
				{
				$ergebnis=($ersterwert-$letzterwert);
				echo "   Vor Hochrechnung ".number_format($ergebnis, 3, ".", "");
				$ergebnis=($ergebnis)*$gepldauer/$dauer;
	   	  	echo " und nach Hochrechnung ".number_format($ergebnis, 3, ".", "")." \n";
				}
			else
			   {
				$ergebnis=($ersterwert-$letzterwert);
				}
	      break;
	   default:
	   }
	return $ergebnis;
	}

/*****************************************************************
 * 
 * Einen Verzeichnisbaum erstellen. Routine scheitert wenn es bereits eine Datei gibt, die genauso wie das Verzeichnis heisst. Dafür einen Abbruchzähler vorsehen.
 *
 **/

function mkdirtree($directory)
	{
	$directory = str_replace('\\','/',$directory);
	$directory=substr($directory,0,strrpos($directory,'/')+1);
	//$directory=substr($directory,strpos($directory,'/'));
	$i=0;
	while ((!is_dir($directory)) && ($i<20) )
		{
		$i++;
		echo "mkdirtree: erzeuge Verzeichnis $directory \n"; 		
		$newdir=$directory;
		while ( (!is_dir($newdir)) && ($i<20) )
			{
			$i++;
			//echo "es gibt noch kein ".$newdir."\n";
			if (($pos=strrpos($newdir,"/"))==false) {$pos=strrpos($newdir,"\\");};
			if ($pos==false) break;
			$newdir=substr($newdir,0,$pos);
			echo "   Mach : ".$newdir."\n";
			try
				{
				@mkdir($newdir);
				}
			catch (Exception $e) { echo "."; }
			if (is_dir($newdir)) echo "     Verzeichnis ".$newdir." erzeugt.\n";
			}
		if ($pos==false) break;
		}
	if ($i >= 20) echo "Fehler bei der Verzeichniserstellung.\n";	
		
	}

/******************************************************************/

function RPC_CreateVariableByName($rpc, $id, $name, $type, $struktur=array())
	{

	/* type steht für 0 Boolean 1 Integer 2 Float 3 String */

	$result="";
	$size=sizeof($struktur);
	if ($size==0)
		{
		$children=$rpc->IPS_GetChildrenIDs($id);
		foreach ($children as $oid)
	   	{
	   	$struktur[$oid]=$rpc->IPS_GetName($oid);
	   	}		
		echo "RPC_CreateVariableByName, nur wenn Struktur nicht übergeben wird neu ermitteln.\n";
		//echo "Struktur :\n";
		//print_r($struktur);
		}
	foreach ($struktur as $oid => $oname)
	   {
	   if ($name==$oname) {$result=$name;$vid=$oid;}
		//echo "Variable ".$name." bereits angelegt, keine weiteren Aktivitäten.\n";		
	   }
	if ($result=="")
	   {
	   echo "Variable ".$name." auf Server neu erzeugen.\n";
      $vid = $rpc->IPS_CreateVariable($type);
      $rpc->IPS_SetParent($vid, $id);
      $rpc->IPS_SetName($vid, $name);
      $rpc->IPS_SetInfo($vid, "this variable was created by script. ");
      }
     //echo "Fertig mit ".$vid."\n";
    return $vid;
	}

/******************************************************************/

function RPC_CreateCategoryByName($rpc, $id, $name)
	{

	/* erzeugt eine Category am Remote Server */

	$result="";
	$struktur=$rpc->IPS_GetChildrenIDs($id);
	foreach ($struktur as $category)
	   {
	   $oname=$rpc->IPS_GetName($category);
	   //echo str_pad($oname,20)." ".$category."\n";
	   if ($name==$oname) {$result=$name;$vid=$category;}
	   }
	if ($result=="")
	   {
      $vid = $rpc->IPS_CreateCategory();
      $rpc->IPS_SetParent($vid, $id);
      $rpc->IPS_SetName($vid, $name);
      $rpc->IPS_SetInfo($vid, "this category was created by script. ");
      }
    return $vid;
	}

/******************************************************************/

function RPC_CreateVariableField($Homematic, $keyword, $profile,$startexec=0)
	{

	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
	$remServer=ROID_List();
	if ($startexec==0) {$startexec=microtime(true);}
	foreach ($Homematic as $Key)
		{
		/* alle Feuchtigkeits oder Temperaturwerte ausgeben */
		if (isset($Key["COID"][$keyword])==true)
			{
			$oid=(integer)$Key["COID"][$keyword]["OID"];
			$variabletyp=IPS_GetVariable($oid);
			if ($variabletyp["VariableProfile"]!="")
				{
				echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
				}
			else
				{
				echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
				}
			$parameter="";
			foreach ($remServer as $Name => $Server)
				{
				$rpc = new JSONRPC($Server["Adresse"]);
				if ($keyword=="TEMPERATURE")
					{
					$result=RPC_CreateVariableByName($rpc, (integer)$Server[$profile], $Key["Name"], 2);
					}
				else
					{
					$result=RPC_CreateVariableByName($rpc, (integer)$Server[$profile], $Key["Name"], 1);
					}
				$rpc->IPS_SetVariableCustomProfile($result,$profile);
				$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
				$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
				$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
				$parameter.=$Name.":".$result.";";
				}
			$messageHandler = new IPSMessageHandler();
			$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
			$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
			if ($keyword=="TEMPERATURE")
				{
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Temperatur,'.$parameter,'IPSModuleSensor_Temperatur,1,2,3');
				}
			else
				{
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Feuchtigkeit,'.$parameter,'IPSModuleSensor_Feuchtigkeit,1,2,3');
				}
			}
		}
	}

/*****************************************************************
 *
 * wandelt die Liste der remoteAccess server in eine bessere Tabelle um und hängt den aktuellen Status zur Erreichbarkeit in die Tabell ein
 * der Status wird alle 60 Minuten von operationCenter ermittelt. Wenn Modul nicht geladen wurde wird einfach true angenommen
 *
 *****************************************************************************/

function RemoteAccessServerTable()
	{
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$result=$moduleManager->GetInstalledModules();
			IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");	
			if (isset ($result["OperationCenter"]))
				{
				$moduleManager_DM = new IPSModuleManager('OperationCenter');     /*   <--- change here */
				$CategoryIdData   = $moduleManager_DM->GetModuleCategoryID('data');
				$Access_categoryId=@IPS_GetObjectIDByName("AccessServer",$CategoryIdData);
				$RemoteServer=array();
	        	//$remServer=RemoteAccess_GetConfiguration();
				//foreach ($remServer as $Name => $UrlAddress)
				$remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
				foreach ($remServer as $Name => $Server)
					{
					$UrlAddress=$Server["ADRESSE"];
                    if ( (isset($Server["STATUS"])===true) and (isset($Server["LOGGING"])===true) )
                        {                    
    					if ( (strtoupper($Server["STATUS"])=="ACTIVE") and (strtoupper($Server["LOGGING"])=="ENABLED") )
	    					{				
		    				$IPS_UpTimeID = CreateVariableByName($Access_categoryId, $Name."_IPS_UpTime", 1);
			    			$RemoteServer[$Name]["Url"]=$UrlAddress;
				    		$RemoteServer[$Name]["Name"]=$Name;
					    	if (GetValue($IPS_UpTimeID)==0)
						    	{
							    $RemoteServer[$Name]["Status"]=false;
							    }
    						else
	    						{
		    					$RemoteServer[$Name]["Status"]=true;
			    				}
                            }    
						}
                    if (isset($Server["ALEXA"])===true ) $RemoteServer[$Name]["Alexa"] = $Server["ALEXA"];
					}
				}
			else
				{
				$remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
				foreach ($remServer as $Name => $Server)
					{
					$UrlAddress=$Server["ADRESSE"];
                    if ( (isset($Server["STATUS"])===true) and (isset($Server["LOGGING"])===true) )
                        {                    
    					if ( (strtoupper($Server["STATUS"])=="ACTIVE") and (strtoupper($Server["LOGGING"])=="ENABLED") )
	    					{				
		    				$RemoteServer[$Name]["Url"]=$UrlAddress;
			    			$RemoteServer[$Name]["Name"]=$Name;
				    		$RemoteServer[$Name]["Status"]=true;
					    	}
                        }
                    if (isset($Server["ALEXA"])===true ) $RemoteServer[$Name]["Alexa"] = $Server["ALEXA"];
					}	
			   }

	return($RemoteServer);
	}

/*****************************************************************
 *
 * wandelt die Liste der remoteAccess_GetServerConfig  in das alte Format der tabelle RemoteAccess_GetConfiguration um
 * Neuer Name , damit alte Funktionen keine Fehlermeldung liefern 
 *
 *****************************************************************************/
 
function RemoteAccess_GetConfigurationNew()
	{
	$RemoteServer=array();
	$remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
	foreach ($remServer as $Name => $Server)
		{
		$UrlAddress=$Server["ADRESSE"];
		if ( (strtoupper($Server["STATUS"])=="ACTIVE") and (strtoupper($Server["LOGGING"])=="ENABLED") )
			{				
			$RemoteServer[$Name]=$UrlAddress;
			}
		}	
	return($RemoteServer);
	}
	
/******************************************************************/

function ReadTemperaturWerte()
	{
	
	if (isset($installedModules["EvaluateHardware"])==true) 
		{
		IPSUtils_Include ("EvaluateHardware_include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
		}
	//elseif (isset($installedModules["RemoteReadWrite"])==true) IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	
	$alleTempWerte="";
	$Homematic = HomematicList();
	foreach ($Homematic as $Key)
		{
		/* alle Homematic Temperaturwerte ausgeben */
		if (isset($Key["COID"]["TEMPERATURE"])==true)
	  		{
	      	$oid=(integer)$Key["COID"]["TEMPERATURE"]["OID"];
			$alleTempWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
			}
		}

	$FHT = FHTList();
	foreach ($FHT as $Key)
		{
		/* alle FHT Temperaturwerte ausgeben */
		if (isset($Key["COID"]["TemeratureVar"])==true)
		   {
	      	$oid=(integer)$Key["COID"]["TemeratureVar"]["OID"];
			$alleTempWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
			}
		}
	return ($alleTempWerte);
	}

function ReadThermostatWerte()
	{
	$repository = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
	$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
	$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();

	if (isset($installedModules["EvaluateHardware"])==true) 
		{
		IPSUtils_Include ("EvaluateHardware_include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
		}
	//elseif (isset($installedModules["RemoteReadWrite"])==true) IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	
	$alleWerte="";

	$Homematic = HomematicList();
	$FS20= FS20List();
	$FHT = FHTList();

	$pad=50;
	$alleWerte.="\n\nAktuelle Heizungswerte direkt aus den HW-Registern:\n\n";
	$varname="SET_TEMPERATURE";
	foreach ($Homematic as $Key)
		{
		/* Alle Homematic Stellwerte ausgeben */
		if ( (isset($Key["COID"][$varname])==true) && !(isset($Key["COID"]["VALVE_STATE"])==true) )
			{
			/* alle Stellwerte der Thermostate */
			//print_r($Key);

			$oid=(integer)$Key["COID"][$varname]["OID"];
			$variabletyp=IPS_GetVariable($oid);
			if ($variabletyp["VariableProfile"]!="")
				{
				$alleWerte.=str_pad($Key["Name"],$pad)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			else
				{
				$alleWerte.=str_pad($Key["Name"],$pad)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			}
		}

	$varname="SET_POINT_TEMPERATURE";
	foreach ($Homematic as $Key)
		{
		/* Alle Homematic Stellwerte ausgeben */
		if ( (isset($Key["COID"][$varname])==true) && !(isset($Key["COID"]["VALVE_STATE"])==true) )
			{
			/* alle Stellwerte der Thermostate */
			//print_r($Key);
			$oid=(integer)$Key["COID"][$varname]["OID"];
			$variabletyp=IPS_GetVariable($oid);
			if ($variabletyp["VariableProfile"]!="")
				{
				$alleWerte.=str_pad($Key["Name"],$pad)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			else
				{
				$alleWerte.=str_pad($Key["Name"],$pad)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			}
		}

	foreach ($FHT as $Key)
		{
		/* alle FHT Temperaturwerte ausgeben */
		if (isset($Key["COID"]["TargetTempVar"])==true)
		   {
	      	$oid=(integer)$Key["COID"]["TargetTempVar"]["OID"];
			$alleWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
			}
		}

	return ($alleWerte);
	}

function ReadAktuatorWerte()
	{
	$repository = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
	$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
	$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
	if (isset($installedModules["EvaluateHardware"])==true) 
		{
		IPSUtils_Include ("EvaluateHardware_include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
		}
	//elseif (isset($installedModules["RemoteReadWrite"])==true) IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	$componentHandling=new ComponentHandling();

	$alleWerte="";
	$alleWerte.="\n\nAktuelle Heizungs-Aktuatorenwerte direkt aus den HW-Registern:\n\n";
    
    if (function_exists('HomematicList')) $alleWerte.=$componentHandling->getComponent(HomematicList(),"TYPE_ACTUATOR","String");
    if (function_exists('FHTList')) $alleWerte.=$componentHandling->getComponent(FHTList(),"TYPE_ACTUATOR","String");

	return ($alleWerte);
	}


/******************************************************************/

function exectime($startexec)
	{
	return (number_format((microtime(true)-$startexec),2));
	}

	/******************************************************************/
	
	function getVariableId($name, $switchCategoryId, $groupCategoryId, $categoryIdPrograms) 
		{
		$childrenIds = IPS_GetChildrenIDs($switchCategoryId);
		foreach ($childrenIds as $childId) 
			{
			if (IPS_GetName($childId)==$name) 
				{
				return $childId;
				}
			}
		$childrenIds = IPS_GetChildrenIDs($groupCategoryId);
		foreach ($childrenIds as $childId) 
			{
			if (IPS_GetName($childId)==$name) 
				{
				return $childId;
				}
			}
		$childrenIds = IPS_GetChildrenIDs($categoryIdPrograms);
		foreach ($childrenIds as $childId) {
			if (IPS_GetName($childId)==$name) 
				{
				return $childId;
				}
			}
		trigger_error("$name could NOT be found in 'Switches' and 'Groups'");
		}

/******************************************************************/

function getProcessList()
	{
	$processList=array();
	echo "Die aktuell gestarteten Dienste werden erfasst.\n";
	$result=IPS_EXECUTE("c:/windows/system32/wbem/wmic.exe","process list", true, true);

	$trans = array("\x0D\x0A\x0D\x0A" => "\x0D");
	$result = strtr($result,$trans);
	$handle=fopen("c:/scripts/process.txt","w");
	fwrite($handle,$result);
	fclose($handle);

	$firstLine=true;
	$ergebnis=explode("\x0D",$result);
	foreach ($ergebnis as &$resultvalue)
		{
		if ($firstLine==true)
		   {
		   $posCommandline=strpos($resultvalue,'CommandLine');
		   $posCSName=strpos($resultvalue,'CSName');
		   $posDescription=strpos($resultvalue,'Description');
		   $posExecutablePath=strpos($resultvalue,'ExecutablePath');
		   $posExecutionState=strpos($resultvalue,'ExecutionState');
		   $posHandle=strpos($resultvalue,'Handle');
		   $posHandleCount=strpos($resultvalue,'HandleCount');
		   $posInstallDate=strpos($resultvalue,'InstallDate');
		   //echo 'CommandLine    : '.$posCommandline."\n";
		   //echo 'CSName         : '.$posCSName."\n";
		   //echo 'Description    : '.$posDescription."\n";
		   //echo 'ExecutablePath : '.$posExecutablePath."\n";
		   //echo 'ExecutionState : '.$posExecutionState."\n";
		   //echo 'Handle         : '.$posHandle."\n";
		   //echo 'HandleCount    : '.$posHandleCount."\n";
		   //echo 'InstallDate    : '.$posInstallDate."\n";
		   $firstLine=false;
		   }
		$value=$resultvalue;
		//echo $value;
		$resultvalue=array();
		$resultvalue['Commandline']=trim(substr($value,$posCommandline,$posCSName));
		$resultvalue['CSName']=rtrim(substr($value,$posCSName,$posDescription-$posCSName));
		$resultvalue['Description']=rtrim(substr($value,$posDescription,$posExecutablePath-$posDescription));
		$resultvalue['ExecutablePath']=rtrim(substr($value,$posExecutablePath,$posExecutionState-$posExecutablePath));
		$resultvalue['ExecutionState']=rtrim(substr($value,$posExecutionState,$posHandle-$posExecutionState));
		$resultvalue['Handle']=rtrim(substr($value,$posHandle,$posHandleCount-$posHandle));
		$resultvalue['HandleCount']=rtrim(substr($value,$posHandleCount,$posInstallDate-$posHandleCount));
		$resultvalue['InstallDate']=rtrim(substr($value,$posInstallDate,13));
		}
	unset($resultvalue);
	//print_r($ergebnis);
	
	$LineProcesses="";
	foreach ($ergebnis as $valueline)
		{
		//echo $valueline['Commandline'];
	   if ((substr($valueline['Commandline'],0,3)=="C:\\") or (substr($valueline['Commandline'],0,3)=='"C:')or (substr($valueline['Commandline'],0,3)=='C:/') or (substr($valueline['Commandline'],0,3)=='C:\\')  or (substr($valueline['Commandline'],0,3)=='"C:'))
	      {
	      //echo "****\n";
	      $process=$valueline['ExecutablePath'];
	      $pos=strrpos($process,'\\');
	      $process=substr($process,$pos+1,100);
	      if (($process=="svchost.exe") or ($process=="lsass.exe") or ($process=="csrss.exe")or ($process=="SMSvcHost.exe")  or ($process=="WmiPrvSE.exe")  )
	         {
	         }
	      else
	         {
		      //echo $process."  Pos : ".$pos."  \n";
				//$processes.=$valueline['ExecutablePath']."\n";
				$LineProcesses.=$process.",";
				$processList[]=$process;
				}
			}
		}

	return ($processList);
	}

/******************************************************************/

function getTaskList()
	{
	$taskList=array();
	echo "Die aktuell gestarteten Programme werden erfasst.\n";
	$result=IPS_EXECUTE("c:/windows/system32/tasklist.exe","", true, true);
	//echo $result;

	//$trans = array("\x0D\x0A" => "\x0D");
	//$result = strtr($result,$trans);
	$handle=fopen("c:/scripts/tasks.txt","w");
	fwrite($handle,$result);
	fclose($handle);

	$firstLine=0;
	$ergebnis=explode("\x0A",$result);
	//print_r($ergebnis);
	foreach ($ergebnis as &$resultvalue)
		{
		//echo $resultvalue;
		if ($firstLine<3)
		   {
		   $pos=strpos($resultvalue,'Abbildname');
			if ($pos === false)
				{
				}
			else
				{
				$posAbbild=$pos;
				$posPID=strpos($resultvalue,'PID')-5;
			   $posSitzung=strpos($resultvalue,'Sitzungsname');
			   $posSitzNr=strpos($resultvalue,'Sitz.-Nr.')-2;
		   	$posSpeicher=strpos($resultvalue,'Speichernutzung');

			   //echo 'Abbildname    : '.$posAbbild."\n";
			   //echo 'PID           : '.$posPID."\n";
			   //echo 'Sitzung       : '.$posSitzung."\n";
		   	//echo 'SitzungsNr    : '.$posSitzNr."\n";
			   //echo 'Speicher      : '.$posSpeicher."\n";
			   }
		   }
		else
		   {
			$value=$resultvalue;
			$resultvalue=array();
			$resultvalue['Abbildname']=trim(substr($value,$posAbbild,$posPID));
			$resultvalue['PID']=rtrim(substr($value,$posPID,$posSitzung-$posPID));
			$resultvalue['Sitzung']=rtrim(substr($value,$posSitzung,$posSitzNr-$posSitzung));
			$resultvalue['ExecutablePath']=rtrim(substr($value,$posSitzNr,$posSpeicher-$posSitzNr));
			$resultvalue['ExecutionState']=rtrim(substr($value,$posSpeicher,15));
		   }
		$firstLine+=1;
		}
	unset($resultvalue);
	//print_r($ergebnis);

	foreach ($ergebnis as $valueline)
		{
		if (isset($valueline['Abbildname'])==true)
		   {
	      $process=$valueline['Abbildname'];
   	  	//echo "**** ".$process."\n";
	   	if (($process=="svchost.exe") or ($process=="lsass.exe") or ($process=="csrss.exe") or ($process=="SMSvcHost.exe") or ($process=="WmiPrvSE.exe")  )
	      	{
		      }
		   else
	   	   {
				$taskList[]=$process;
				}
			}
		}
	return ($taskList);
	}

/******************************************************************/

function fileAvailable($filename,$verzeichnis)
	{
	$status=false;
	/* Wildcards beim Filenamen zulassen */
	$pos=strpos($filename,"*.");
	if ( $pos === false )
	   {
	   echo "Wir suchen nach dem Filenamen \"".$filename."\"\n";
	   $detName=true;
	   $detExt=false;
	   }
	else
	   {
	   $filename=substr($filename,$pos+1,20);
	   echo "Wir suchen nach der Extension \"*".$filename."\"\n";
	   $detExt=true;
	   }
	if ( is_dir ( $verzeichnis ))
		{
    	// öffnen des Verzeichnisses
    	if ( $handle = opendir($verzeichnis) )
    		{
        	while (($file = readdir($handle)) !== false)
        		{
				$dateityp=filetype( $verzeichnis.$file );
            if ($dateityp == "file")
            	{
				 	if ($detExt == false)
				 	   {
				 	   /* Wir suchen einen Filenamen */
	            	if ($file == $filename)
							 {
							 $status=true;
							 }
            		//echo $file."\n";
            		}
            	else
            	   {
				 	   /* Wir suchen eine Extension */
				 	   //echo $file."\n";
				 	   $pos = strpos($file,$filename);
	               if ( ($pos > 0 ) )
							 {
							 $len =strlen($file)-strlen($filename)-$pos;
							 //echo "Filename \"".$file."\" gefunden. Laenge Extension : ".$len." ".$pos."\n";
							 if ( $len == 0 ) { $status=true; }
							 }
            	   }
         		}
      	  	} /* Ende while */
	     	closedir($handle);
   		} /* end if dir */
		}/* ende if isdir */
	return $status;
	}
	
/************************************************************************************/

function checkProcess($processStart)
	{
	$processes=getProcessList();
	sort($processes);
	//print_r($processes);

	foreach ($processes as $process)
		{
		foreach ($processStart as $key => &$start)
		   {
      	if ($process==$key)
      	   {
      	   $start="Off";
      	   }
		   }
		unset($start);
		}
	//print_r($processStart);

	$processes=getTaskList();
	sort($processes);
	foreach ($processes as $process)
		{
		foreach ($processStart as $key => &$start)
		   {
      	if ($process==$key)
      	   {
      	   $start="Off";
      	   }
		   }
		unset($start);
		}
	return($processStart);
	}

/***********************************************************************************
 *
 * Die Komplette Installation von Compnents in einer Klasse zusammenfassen
 *
 *
 *
 *
 *********************************************************************************************/

class ComponentHandling
    {

    private $archiveHandlerID, $installedModules, $debug;
    private $remote, $messageHandler;
    private $congigMessage;

	public function __construct($debug=false)
        {
        $this->debug=$debug;
        $this->archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
		$this->installedModules=$moduleManager->GetInstalledModules();
		if (isset ($this->installedModules["RemoteAccess"]))
			{
			IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");
			IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");			
			$this->remote=new RemoteAccess();
            }
	    $this->messageHandler = new IPSMessageHandler();
        $this->configMessage=IPSMessageHandler_GetEventConfiguration();
        }

    public function listOfRemoteServer()
        {
		$remServer=array();
		if (isset ($this->installedModules["RemoteAccess"]))
			{
			$status=$this->remote->RemoteAccessServerTable();
			$text=$this->remote->writeRemoteAccessServerTable($status);
        	$remServer=$this->remote->get_listofROIDs();
	        if ($text!="")
    	        {
		       	echo "Liste der Remote Logging Server (mit Status Active und für Logging freigegeben):        \n";
            	echo $text;
    			echo "Liste der ROIDs der Remote Logging Server (mit Status Active und für Logging freigegeben):   \n";
		    	echo $this->remote->write_listofROIDs();
        	        }
            }
        return($remServer);
        } 

    public function getStructureofROID($keyword)
        {
        $struktur=array();
		if (isset ($this->installedModules["RemoteAccess"]))
			{        
			$struktur=$this->remote->get_StructureofROID($keyword);
            if ((sizeof($struktur))>0)
                {
    			echo "Struktur Server ausgeben:             \n";
	    		foreach ($struktur as $Name => $Eintraege)
		    		{
			    	echo "   ".$Name." für Schalter hat ".sizeof($Eintraege)." Eintraege \n";
				    //print_r($Eintraege);
    				foreach ($Eintraege as $Eintrag) echo "      ".$Eintrag["Name"]."   ".$Eintrag["OID"]."\n";
                    }
				}           
            }
        return($struktur);
        }

    public function registerEvent($oid,$update,$component,$module,$commentField)
        {
		//$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		//$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
        $change=false;
        if (isset($this->configMessage[$oid])) 
            {
            //echo "   -> gefunden:\n";
            $compare=$this->configMessage[$oid];
            if ( ($compare[0] != $update) || ($compare[1] != $component) || ($compare[2] != $module) ) 
				{
				$change = true;
				echo "    Event $oid neu konfigurieren mit \"$update\",\"$component\",\"$module\"\n";
				}
            }
		else 
			{
			$change = true;
			echo "    Event $oid neu registrieren mit \"$update\",\"$component\",\"$module\"\n";
			}
        if ($change)
            {
		    $this->messageHandler->RegisterEvent($oid,$update,$component,$module);
		    //echo "    Event $oid registriert mit \"$update\",\"$component\",\"$module\"\n";
			$eventName = $update.'_'.$oid;
			$scriptId  = IPS_GetObjectIDByIdent('IPSMessageHandler_Event', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.core.IPSMessageHandler'));
			$eventId   = @IPS_GetObjectIDByIdent($eventName, $scriptId);
			if ($eventId) 
				{
				echo "    Event $eventName mit Kommentarfeld versehen.EventId $eventId gefunden.\n";
				IPS_SetInfo($eventId,$commentField);
				}
            }
        else echo "    Event $oid ist bereits korrekt mit \"$update\",\"$component\",\"$module\" registriert.\n";    
        }

/***********************************************************************************
 *
 * getComponent, nach Keywords aus den Geräten in einer Liste die richtigen finden und entsprechend behandeln
 *
 *
 ****************************************************************************************/    

	function getComponent($Elements,$keywords, $write="Array")
		{
        $component=array(); $install=array(); $result="";
	    $detectmovement=false; $profile="";
		$totalfound=false;
		
		if ( is_array($keywords) )
			{
			echo "Passende Geraeteregister suchen für ";
			foreach ($keywords as $entry) echo $entry." ";
			echo ":\n";
			}
		else echo "Passende Geraeteregister suchen für $keywords :\n";
		/* für alle Instanzen in der Liste machen, keyword muss vorhanden sein */		
		foreach ($Elements as $Key)
			{
			//echo " getComponent Entry: \n"; print_r($Key); 
			$count=0; $countNo=0; $max=0; $maxNo=0; $found=false;
			if ( is_array($keywords) == true )
				{
				foreach ($keywords as $entry)
					{
					/* solange das Keyword uebereinstimmt ist alles gut */
                    if (strpos($entry,"!")===false)
                        {
                        $max++;
					    if (isset($Key["COID"][$entry])==true) $count++; 
    					//echo "    Ueberpruefe  ".$entry."    ".$count."/".$max."\n";
                        }
                    elseif  (strpos($entry,"!")==0)
                        {
                        $maxNo++;
                        $entry1=substr($entry,1);
                        if (isset($Key["COID"][$entry1])==true) $countNo++;
    					//echo "    Ueberpruefe  NICHT ".$entry1."    ".$countNo."/".$maxNo." \n";
                        }
					}
				if ( ($max == $count) && ($countNo == 0) ) 
                    { 
                    $found=true; $totalfound=true;
                    //echo "**gefunden\n";
                    }
				$keyword=$keywords[0];	
				}	
			else
				{
				if (isset($Key["COID"][$keywords])==true) { $found=true; $totalfound=true; }
				$keyword=$keywords; 
				}	
			
			if ( (isset($Key["Device"])==true) && ($found==false) )
				{
				/* Vielleicht ist ein Device Type als Keyword angegeben worden.\n" */
				if ($Key["Device"] == $keyword)
					{
					echo "      Ein Gerät mit der Device Bezeichnung $keyword gefunden.\n";
					$found=true; $totalfound=true;
					switch ($keyword)
						{
						case "TYPE_ACTUATOR":
							if (isset($Key["COID"]["LEVEL"]["OID"]) == true) $keyword="LEVEL";
							elseif (isset($Key["COID"]["VALVE_STATE"]["OID"]) == true) $keyword="VALVE_STATE";
							$detectmovement="HeatControl";
							break;
						case "TYPE_THERMOSTAT":
							if (isset($Key["COID"]["SET_TEMPERATURE"]["OID"]) == true) $keyword="SET_TEMPERATURE";
							if (isset($Key["COID"]["SET_POINT_TEMPERATURE"]["OID"]) == true) $keyword="SET_POINT_TEMPERATURE";
							if (isset($Key["COID"]["TargetTempVar"]["OID"]) == true) $keyword="TargetTempVar";
							break;
						case "TYPE_CONTACT":
							if ( (isset($Key["COID"]["STATE"])==true) and (isset($Key["COID"]["ERROR"])==true) ) $keyword="CONTACT";
							$detectmovement="Contact";
							break;							
						default:	
							echo "FEHLER: unknown keyword.\n";
						}	
					}
				}
			/* Zuweisung von Orientierungshilfen für das Anlegen der Variablen 
			 * RPC_CreateVariableByName($rpc, (integer)$Server["Bewegung"], $Key["Name"], 0);
			 * index="Bewegung"
			 */
            $indexNameExt="";
			switch (strtoupper($keyword))
				{
				case "TARGETTEMPVAR":			/* Thermostat Temperatur Setzen */
				case "SET_POINT_TEMPERATURE":
				case "SET_TEMPERATURE":
					$variabletyp=2; 		/* Float */
					$index="HeatSet";
					//$profile="TemperaturSet";		/* Umstellung auf vorgefertigte Profile, da besser in der Darstellung */
					$profile="~Temperature";
					break;	
				case "CONTROL_MODE":                // Thermostat Homematic und HomematicIP
				case "SET_POINT_MODE":
				case "TARGETMODEVAR":				// Thermostat FHT
					$variabletyp=1; 		/* Integer */
					$index="HeatSet";
                    $indexNameExt="_Mode";								/* gemeinsam mit den Soll Temperaturwerten abspeichern */
                    $profile="mode.HM";             // privates Profil für Formattierung RemoteAccess Variable verwenden, da nicht sichergestellt ist das das jeweilige Format der Harwdare auf der Zielmaschine installliert ist
                    break;                    			
				case "TEMERATUREVAR";			/* Temperatur auslesen */
				case "TEMPERATURE":
				case "ACTUAL_TEMPERATURE":
					$detectmovement="Temperatur";				
					$variabletyp=2; 		/* Float */
					$index="Temperatur";
					//$profile="Temperatur";		/* Umstellung auf vorgefertigte Profile, da besser in der Darstellung */
					$profile="~Temperature";
					break;
				case "HUMIDITY":
					$detectmovement="Feuchtigkeit";
					$variabletyp=1; 		/* Integer */							
					$index="Humidity";
					$profile="Humidity";
					break;
		        case "POSITIONVAR":
				case "VALVE_STATE": 
					$detectmovement="HeatControl";
					$variabletyp=2; 		/* Float */
					$index="HeatControl";
					$profile="~Valve.F";
					break;					
				case "LEVEL":
					$detectmovement="HeatControl";
					$variabletyp=1; 		/* Integer */	
					$index="HeatControl";
					$profile="~Intensity.100";
					break;
				case "STATE":
				case "STATUSVARIABLE":
					$variabletyp=0; 		/* Boolean */	
					$index="Schalter";
					$profile="Switch";
					break;
				case "TYPE_THERMOSTAT":		/* known keywords, do nothing, all has been done above */	
				case "TYPE_ACTUATOR":
					break;
				case "MOTION":
					$detectmovement="Motion";
					$variabletyp=0; 		/* Boolean */					
					$index="Bewegung";
					$profile="Motion";
					break;	
				case "CONTACT":
					$detectmovement="Contact";
					$keyword="STATE";
					$variabletyp=0; 		/* Boolean */					
					$index="Bewegung";
					$profile="Motion";
					break;
				default:	
					$variabletyp=0; 		/* Boolean */	
					//echo "************Kenne ".strtoupper($keyword)." nicht.\n";
					break;
				}			
			
			if ($found)
				{
                if (isset($this->installedModules["DetectMovement"])===false) $detectmovement = false;    // wenn Modul nicht installiert auch nicht bearbeiten		
				//echo "********** ".$Key["Name"]."\n";
				//print_r($Key);
				$oid=(integer)$Key["COID"][$keyword]["OID"];
                $component[]=(integer)$oid;
                $install[$Key["Name"]]["COID"]=(integer)$oid;
                $install[$Key["Name"]]["OID"]=(integer)$Key["OID"];
                $install[$Key["Name"]]["KEY"]=$keyword;
			    $install[$Key["Name"]]["TYP"]=$variabletyp;
				$install[$Key["Name"]]["INDEX"]=$index;
				$install[$Key["Name"]]["PROFILE"]=$profile;					 
                $install[$Key["Name"]]["DETECTMOVEMENT"]=$detectmovement;
				$install[$Key["Name"]]["INDEXNAMEEXT"]=$indexNameExt;	 

				$vartyp=IPS_GetVariable($oid);
				if ($vartyp["VariableProfile"]!="")
					{
					$result .= "  ".str_pad($Key["Name"]."/".$keyword,50)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
					}
				else
					{
					$result .= "  ".str_pad($Key["Name"]."/".$keyword,50)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
					}
				}   // ende found
			} /* Ende foreach */
		if (!$totalfound) echo "************Kenne ".json_encode($keywords)." nicht.\n";
        switch ($write)
            {
            case "Array":
                echo $result;
                return ($component);
                break;
            case "Install":
                echo $result;
                return ($install);
                break;
            default:
                return ($result);
                break;
            }
		}	

/***********************************************************************************
 *
 * getKeyword
 *
 * aus dem Ergebnis von getComponent nur den Index herausholen, soll für alle Eintraege gleich sein 
 *
 ****************************************************************************************/    

	function getKeyword($result)
		{
		$keyword="";
		//print_r($result);	
		foreach ($result as $entry)
			{
			if ($keyword=="") $keyword = $entry["INDEX"];
			else
				{
				if ($keyword!=$entry["INDEX"]) echo "Fehler, unterschiedliche index erkannt, nicht eindeutig.\n";
				}
			}
		return ($keyword);
		}

/***********************************************************************************
 *
 * DEPRECIATED
 *
 * verwendet von CustomComponents, RemoteAccess und EvaluateHeatControl zum schnellen Anlegen der Variablen
 * ist auch in der Remote Access Class angelegt und kann direkt aus der Klasse aufgerufen werden.
 *
 * Elements		Objekte aus EvaluateHardware, alle Homematic, alle FS20 etc.
 * keyword		Name des Children Objektes das enthalten sein muss, wenn array auch mehrer Keywords, erstes Keyword ist das indexierte
 * InitComponent	erster Parameter bei der Registrierung
 * InitModule		zweiter Parameter bei der Registrierung
 * parameter	wenn array parameter[oid] gesetzt ist, ist RemoteAccess vorhanden und aufgesetzt
 *
 * Ergebnis: ein zusaetzliches Event wurde beim Messagehandler registriert
 *
 ****************************************************************************************/
	
	function installComponent($Elements,$keywords,$InitComponent, $InitModule, $parameter=array())
		{
		//echo "InstallComponent aufgerufen.\n";
		foreach ($Elements as $Key)
			{
			//echo "  Evaluiere ".$Key["Name"]."\n";			
			/* alle Stellmotoren ausgeben */
			$count=0; $found=false;
			if ( is_array($keywords) == true )
				{
				foreach ($keywords as $entry)
					{
					/* solange das Keyword uebereinstimmt ist alles gut */
					if (isset($Key["COID"][$entry])==true) $count++; 
					//echo "    Ueberpruefe  ".$entry."    ".$count."/".sizeof($keywords)."\n";
					}
				if ( sizeof($keywords) == $count ) $found=true;
				$keyword=$keywords[0];	
				}	
			elseif (isset($Key["COID"][$keywords])==true) { $found=true; $keyword=$keywords; }	
			if ($found)
				{		
				//echo "********** ".$Key["Name"]."\n";
				//print_r($Key);
				$oid=(integer)$Key["COID"][$keyword]["OID"];
				$variabletyp=IPS_GetVariable($oid);
                if ($this->debug)
                    {
    				if ($variabletyp["VariableProfile"]!="")
	    				{
		    			echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
			    		}
				    else
					    {
    					echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
	    				}
                    }    
				if ( isset ($parameter[$oid]) )
					{
					echo "  Remote Access installiert, Gruppen Variablen auch am VIS Server aufmachen.\n";
					$messageHandler = new IPSMessageHandler();
					$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
					$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

					/* wenn keine Parameter nach IPSComponentSensor_Temperatur angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
					$messageHandler->RegisterEvent($oid,"OnChange",$InitComponent.','.$parameter[$oid],$InitModule);
					}
				else
					{
					/* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
					echo "Remote Access nicht installiert, Variablen selbst registrieren.\n";
					$messageHandler = new IPSMessageHandler();
					$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
					$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

					/* wenn keine Parameter nach IPSComponentSensor_Temperatur angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
					$messageHandler->RegisterEvent($oid,"OnChange",$InitComponent.',',$InitModule);
					}			
				}
			} /* Ende foreach */		
		}	

/***********************************************************************************
 *
 * installComponentFull, anlegen von CustomComponents Events
 *
 * verwendet zum schnellen und einheitlichen Anlegen der Variablen und Events für CustomComponents, RemoteAccess und EvaluateHeatControl 
 * ist auch in der Remote Access Class angelegt und kann direkt aus der Klasse aufgerufen werden.
 *
 * Elements		Objekte aus EvaluateHardware, alle Homematic, alle FS20 etc.
 * keyword		Name des Children Objektes das enthalten sein muss, wenn array auch mehrer Keywords, erstes Keyword ist das indexierte
 * InitComponent	erster Parameter bei der Registrierung
 * InitModule		zweiter Parameter bei der Registrierung
 * 
 * Ergebnis: ein zusaetzliches Event wurde beim Messagehandler registriert
 *
 * funktioniert für Humidity, Temperature, Heat Control Actuator und Heat Control Set
 * wenn RemoteAccess Modul installiert ist werden die Variablen auch auf den Remote Vis Servern angelegt
 * die Erkennung ob es sich um das richtige Gerät handelt erfolgt über Keywords, die auch ein Array sein können:
 * 		Die Untervariablen(Children/COID einer Instanz werden verglichen ob einer der Variablen wie das keyword heisst
 * 		bei einem Array gilt die Und Verknüpfung - also Variablen für alle Keywords muessen vorhanden sein.
 * 		das Keyword kann auch ein Device Type sein, Evaluate Hardware speichert unter Device einen Device TYP ab
 *			TYPE_BUTTON, TYPE_CONTACT, TYPE_METER_POWER, TYPE_METER_TEMPERATURE, TYPE_MOTION
 *			TYPE_SWITCH, TYPE_DIMMER, 
 *			TYPE_ACTUATOR	setzt $keyword auf VALVE_STATE 
 *			TYPE_THERMOSTAT	setzt $keyword auf SET_TEMPERATURE, SET_POINT_TEMPERATURE, TargetTempVar wenn die COID Objekte auch vorhanden sind.
 *
 ****************************************************************************************/
		
	function installComponentFull($Elements,$keywords,$InitComponent, $InitModule, $commentField="")
		{
		$donotregister=false; $i=0; $maxi=600;		// Notbremse
        $struktur=array();          // Ergbenis, behandelte Objekte

		/* einheitliche Routine verwenden, Formattierung Ergebnis für Install */   
		//echo "Passende Geraeteregister suchen:\n"; 
		$result=$this->getComponent($Elements,$keywords,"Install");				/*passende Geräte suchen*/
		if ( (sizeof($result))>0)											/* gibts ueberhaupt etwas zu tun */
			{		
			$keyword=$this->getKeyword($result);
		
			/* Erreichbarkeit Remote Server nur einmal pro Aufruf ermitteln */
			$remServer=$this->listOfRemoteServer();
            $struktur=$this->getStructureofROID($keyword);

			echo "Resultat für gefundene Geraeteregister verarbeiten:\n";
        	foreach ($result as $IndexName => $entry)       // nur die passenden Geraete durchgehen
      	    	{
	            //echo "----> $IndexName:\n"; print_r($entry);                  
				$oid=$entry["COID"];
				$vartyp=IPS_GetVariable($oid);
                if ($this->debug)
                    {                
    				if ($vartyp["VariableProfile"]!="")
	    				{
		    			echo "  ".str_pad($IndexName."/".$entry["KEY"],50)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
			    		}
				    else
					    {
    					echo "  ".str_pad($IndexName."/".$entry["KEY"],50)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
	    				}
                    }    
				/* check, es sollten auch alle Quellvariablen gelogged werden */
				if (AC_GetLoggingStatus($this->archiveHandlerID,$oid)==false)
					{
					/* Wenn variable noch nicht gelogged automatisch logging einschalten */
					AC_SetLoggingStatus($this->archiveHandlerID,$oid,true);
					AC_SetAggregationType($this->archiveHandlerID,$oid,0);
					IPS_ApplyChanges($this->archiveHandlerID);
					echo "       Variable ".$oid." (".IPS_GetName($oid)."), Archiv logging für dieses Geraeteregister wurde aktiviert.\n";
					}
				if ($donotregister==false)      /* Notbremse, oder generell deaktivierbares registrieren */
					{                    
	   		        $detectmovement=$entry["DETECTMOVEMENT"];
    				if ($detectmovement !== false)
	    				{
		    			IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
			    		IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
				    	switch ($detectmovement)
					    	{
						    case "HeatControl":					
							    $DetectHeatControlHandler = new DetectHeatControlHandler();						
    							$DetectHeatControlHandler->RegisterEvent($oid,"HeatControl",'','par3');     /* par2 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */
	    						break;
		    				case "Feuchtigkeit":
			    				$DetectHumidityHandler = new DetectHumidityHandler();		
				    			$DetectHumidityHandler->RegisterEvent($oid,"Feuchtigkeit",'','par3');     /* par2 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */
					    		break;
						    case "Temperatur":
    							$DetectTemperatureHandler = new DetectTemperatureHandler();						
	    						$DetectTemperatureHandler->RegisterEvent($oid,"Temperatur",'','par3');     /* par2 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */
		    					break;
						    case "Motion":
    							$DetectMovementHandler = new DetectMovementHandler();						
	    						$DetectMovementHandler->RegisterEvent($oid,"Motion",'','par3');     /* par2 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */
		    					break;		
						    case "Contact":
    							$DetectMovementHandler = new DetectMovementHandler();						
	    						$DetectMovementHandler->RegisterEvent($oid,"Contact",'','par3');     /* par2 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */
		    					break;															
			    			default:
				    			break;
					    	}		
					    }
    				$variabletyp=$entry["TYP"];
	    			$index= $entry["INDEX"];
		    		$profile=$entry["PROFILE"];
                    $IndexNameExt=$entry["INDEXNAMEEXT"];
			    	if (isset ($this->installedModules["RemoteAccess"]))
				    	{
						$i++; if ($i>$maxi) { $donotregister=true; }	        /* Notbremse */										
						$parameter="";
						foreach ($remServer as $Name => $Server)        /* es werden nur erreichbare Server behandelt */
							{
							$rpc = new JSONRPC($Server["Adresse"]);
							/* variabletyp steht für 0 Boolean 1 Integer 2 Float 3 String */
							$result=$this->remote->RPC_CreateVariableByName($rpc, (integer)$Server[$index], $IndexName.$IndexNameExt, $variabletyp,$struktur[$Name]);
							//echo "     Setze Profil direkt noch einmal auf $profile da es hier immer Probleme gibt ...\n";							
							$rpc->IPS_SetVariableCustomProfile($result,$profile);
							$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
							$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
							$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
							$parameter.=$Name.":".$result.";";
							$struktur[$Name][$result]["Status"]=true;
							$struktur[$Name][$result]["Hide"]=false;
							//$struktur[$Name][$result]["newName"]=$Key["Name"];	// könnte nun der IndexName sein, wenn weiterhin benötigt						
							}	
						/* wenn keine Parameter nach IPSComponentSensor_Temperatur angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
						$this->RegisterEvent($oid,"OnChange",$InitComponent.','.$entry["OID"].','.$parameter,$InitModule,$commentField);
						}
					else
						{
						/* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
						echo "Remote Access nicht installiert, Variablen selbst registrieren.\n";
						$this->RegisterEvent($oid,"OnChange",$InitComponent.",".$entry["OID"].",",$InitModule,$commentField);
						}			
					}           /* ende donotregister */
				} /* Ende foreach */
			}
		else 
			{
			echo "    -> keine gefunden.\n";
			}				
        return ($struktur);
		}	

    } // endof class ComponentHandling

/***********************************************************************************
 *
 *  selectProtocol, selectProtocolDevice
 *
 **************************************************************************************/

		function selectProtocol($protocol,$devicelist)
			{
			$result=array();
			foreach ($devicelist as $index => $device)
				{
				if ($device["Protocol"]==$protocol) $result[$index]=$device;
				}
			return ($result);
			}	
		
        function selectProtocolDevice($protocol,$type,$devicelist)
			{
			$result=array();
			foreach ($devicelist as $index => $device)
				{
                if ( isset($device["Device"]) === false ) 
                    {
                    echo "FEHLER, Array Identifier Device nicht festgelegt.\n";
                    print_r($device);
                    }
                elseif ( ($device["Protocol"]==$protocol) && ($device["Device"]==$type) ) $result[$index]=$device;
                elseif ( ($protocol=="") && ($device["Device"]==$type) ) $result[$index]=$device;
                elseif ( ($type=="")     && ($device["Protocol"]==$protocol) ) $result[$index]=$device;   
				}
			return ($result);
			}

/*************************************************************************************
 *
 * alle OIDs die im Array von Component angeführt sind ausgeben
 *
 ************************************************************************************************/

    function getComponentValues($component,$logs=true)
        {
        $result="";
        $archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
    	$jetzt=time();
	    $endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt)); // letzter Tag 24:00
        $endtime=$jetzt;
	    $startday=$endtime-60*60*24*1; /* ein Tag */ 
	    $startweek=$endtime-60*60*24*7; /* 7 Tage, Woche */                    
  	    $startmonth=$endtime-60*60*24*30; /* 30 Tage, Monat */                    
  	    $startyear=$endtime-60*60*24*360; /* 360 Tage, Jahr */                    
        foreach ($component as $oid)
            {   /* Vorwerte ermitteln */
            $result .= "  ".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName($oid)." (".$oid.")  ";
		    $werte = @AC_GetLoggedValues($archiveHandlerID, $oid, $startday, $endtime, 0);
            if ($werte !== false)             
                {
                $count=sizeof($werte); $scale="Day";
                if ($count==0)
                    {
    		        $werte = @AC_GetLoggedValues($archiveHandlerID, $oid, $startweek, $endtime, 0);
                    $count=sizeof($werte); $scale="Week";
                    if ($count==0)
                        {
    	        	    $werte = @AC_GetLoggedValues($archiveHandlerID, $oid, $startmonth, $endtime, 0);
                        $count=sizeof($werte); $scale="Month";
                        if ($count==0)
                            {
        	        	    $werte = @AC_GetLoggedValues($archiveHandlerID, $oid, $startyear, $endtime, 0);
                            $count=sizeof($werte); $scale="Year";
                            }
                        }    
                    }
	    	    $result .= $count." logged per ".$scale;
                }
            else $result .= "no logs available";    
            if ($logs)
                {
        	    foreach($werte as $wert)
						{
						$result .= "\n     ".date("d.m.y H:i:s",$wert['TimeStamp'])."   ".number_format($wert['Value'], 2, ",", "" );
						}
                }        
            $result .= "\n";
            }
        return ($result);    
        }



/******************************************************************
 *
 * Vereinfachter Webfront Aufbau wenn SplitPanes verwendet werden sollen. 
 * Darstellung von Variablen nur in Kategorien kann einfacher gelöst werden. Da reeicht der Link.
 *
 *
 *
 ******************************************************************/

class WfcHandling
	{
    
    private $WFC10_ConfigId, $WebfrontConfigID;

	public function __construct($debug=false)
		{
        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
        $this->WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
	    echo "Default WFC10_ConfigId, wenn nicht definiert : ".IPS_GetName($this->WFC10_ConfigId)."  (".$this->WFC10_ConfigId.")\n\n";
    	$WebfrontConfigID=array();
	    $alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
    	foreach ($alleInstanzen as $instanz)
	    	{
		    $result=IPS_GetInstance($instanz);
    		$this->WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
	    	echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."  (".$instanz.")\n";
    		}
	    echo "\n";        
        }

    private function write_wfc($input,$indent,$level)
	    {
    	if ((sizeof($input) > 0) && ($level>0) )
	    	{
		    foreach ($input as $index => $entry)
			    {
    			if ( $index != "." )
	    			{
		    		echo $indent.$entry["."]."\n";
			    	$this->write_wfc($entry,$indent."   ",($level-1));
				    }
    			}
	    	}	
    	}

/************************************************************************************/

    private function search_wfc($input,$search,$tree)
	    {
    	$result="";
	    if (sizeof($input) > 0)
		    {
    		foreach ($input as $index => $entry)
	    		{
		    	if ( $index != "." )
			    	{
				    //echo $tree.".".$index."\n";
    				if ($entry["."] == $search) 
	    				{ 
		    			//echo "search_wfc: ".$search." gefunden in Tree : ".$tree.".\n"; 
			    		return($tree.".");
				    	}
    				else 
	    				{	
		    			$result=$this->search_wfc($entry,$search,$tree.".".$index);
			    		if ( $result != "") { return($result); }
				    	}
    				}
	    		}
		    }
    	else 
	    	{
		    //echo "Search Array Size ".sizeof($input)."\n";
    		}
	    return($result);						
	    }

/************************************************************************************/

    public function read_wfc($level=10,$debug=false)
	    {
    	//echo "\n";
        $resultWebfront=array();
	    $WebfrontConfigID=array();
    	$alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
	    foreach ($alleInstanzen as $instanz)
		    {
    		$result=IPS_GetInstance($instanz);
	    	$WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
		    //echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."\n";
    		if (true)	/* false if debug Auslesen der aktuellen detaillierten Einträge pro Webfront Configurator */
    			{
	    		//echo "    ".IPS_GetConfiguration($instanz)."\n";
		    	//$config=json_decode(IPS_GetConfiguration($instanz));
			    //$config->Items = json_decode(json_decode(IPS_GetConfiguration($instanz))->Items);
    			//print_r($config);
		
	    		$ItemList = WFC_GetItems($instanz);
                //print_r($ItemList);
		    	$wfc_tree=array(); $root="";
                for ($i=0;$i<5;$i++)        // mehrere Durchläufe
                    {
                    $count=0;
    			    foreach ($ItemList as $entry)
	    			    {
    	    			if ($entry["ParentID"] != "")
	    	    			{
                            /* Liste der Einträge ist flat es gibt immer einen entry und einen parent */
		    		    	//echo "   WFC Eintrag:    ".$entry["ParentID"]." (Parent)  ".$entry["ID"]." (Eintrag)\n";
    			    		$result = $this->search_wfc($wfc_tree,$entry["ParentID"],"");
	    			    	//echo "  search_wfc: ".$entry["ParentID"]." mit Ergebnis \"".$result."\"  ".substr($result,1,strlen($result)-2)."\n";
		    			    if ($result == "")
			    			    {
                                if ( ($root != "") && ($entry["ParentID"]==$root) ) /* parent not found, unclear if root */
                                    {
    					    	    $wfc_tree[$entry["ParentID"]][$entry["ID"]]=array();
	    					        $wfc_tree[$entry["ParentID"]]["."]=$entry["ParentID"];
		    				        $wfc_tree[$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
    			    			    if ($debug) echo "   Root -> ".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";
                                    $count++;
                                    }
		    		    		}
			    		    else
				    		    {
    				    		$tree=explode(".",substr($result,1,strlen($result)-2));
	    				    	if ($tree) 
		    				    	{
			    			 	    //print_r($tree); 
    				    			if ($tree[0]=="")
	    				    			{
		    				    		$wfc_tree[$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
			    				    	if ($debug) echo "   -> ".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";						
                                        $count++;
				    				    }
    				    			else	
	    				    			{
		    				    		//echo "Tiefe : ".sizeof($tree)." \n";
			    				    	switch (sizeof($tree))
				    				    	{
					    				    case 1:
						    				    $wfc_tree[$tree[0]][$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
    							    			if ($debug) echo "   -> ".$tree[0].".".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";
                                                $count++;
	    							    		break;
		    							    case 2:
			    							    $wfc_tree[$tree[0]][$tree[1]][$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
    			    							if ($debug) echo "   -> ".$tree[0].".".$tree[1].".".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";
                                                $count++;
	    			    						break;
		    			    				case 3:
			    			    				$wfc_tree[$tree[0]][$tree[1]][$tree[2]][$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
				    			    			if ($debug) echo "   -> ".$tree[0].".".$tree[1].".".$tree[2].".".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";
                                                $count++;
					    			    		break;
						    			    case 4:
							    			    $wfc_tree[$tree[0]][$tree[1]][$tree[2]][$tree[3]][$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
    								    		if ($debug) echo "   -> ".$tree[0].".".$tree[1].".".$tree[2].".".$tree[3].".".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";
                                                $count++;
	    								    	break;
    	    								default:
	    	    								echo "Fehler, groessere Tiefe als programmiert.\n";																		
		    	    						}
			    	    				}								
				    	    		}	
					    	    }						
        					/* Routine sucht nach ParentID Eintrag, schreibt Struktur mit unter der dieser Eintrag gefunden wurde */
	        				/*$found="";
		        			foreach ($wfc_tree as $key => $wfc_entry)
			        			{
				        		$skey=$wfc_entry["."]; 
					        	echo $skey." ".sizeof($wfc_entry)." : ";
						        foreach ($wfc_entry as $index => $result)
							        {
    							    if ($result["."] == $entry["ParentID"]) 
    	    							{ 
	    	    						$found=$result["."]; 
		    	    					$fkey=$skey; 
			    	    				echo "-> ".$fkey."/".$found." found.\n";break;
				    	    			}
					    	    	}
    					    	}
    	    				if ($found != "")
	    	    				{	
		    	    			//print_r($wfc_tree);
			    	    		echo "Create : ".$fkey."/".$entry["ParentID"]."/".$entry["ID"]."\n";
				    	    	$wfc_tree[$fkey][$entry["ParentID"]][$entry["ID"]]=array();
					    	    $wfc_tree[$fkey][$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
    					    	}
	    				    */
    		    			}
	    		    	else        // Root Eintrag, parent ist leer
		    		    	{
                            if ($root=="") 
                                {
			    		        echo "WFC Root Eintrag (nicht mehr als einer pro Configurator):    ".$entry["ID"]." (Eintrag)\n";
                                $root=$entry["ID"];
                                }
                            elseif ($root != $entry["ID"]) echo "******* mehrere Root Eintraege !!\n"; 
                            else {} // alles ok   
    				    	}
	    			    }   // ende foreach, alle Konfiguratoren abgeschlossen
                    //echo "*************".$count."\n";
                    } //ende for 2x
                $webfront=IPS_GetName($instanz);   
		    	echo "\n================ WFC Tree ".$webfront."=====\n";	
			    //print_r($wfc_tree);
                $resultWebfront[$webfront]=$wfc_tree;
    			$this->write_wfc($wfc_tree,"",$level);	
	    		//echo "  ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'Protocol')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
		    	/* alle Instanzen dargestellt */
			    //echo "**     ".IPS_GetName($instanz)." ".$instanz." ".$result['ModuleInfo']['ModuleName']." ".$result['ModuleInfo']['ModuleID']."\n";
    			//print_r($result);
	    		}   // ende debug
		    }       // ende foreach
        return ($resultWebfront);    
    	}   // ende function

    public function setupWebfront($webfront_links,$WFC10_TabPaneItem,$categoryId_WebFrontAdministrator,$scope)
        {
		if ( isset($this->WebfrontConfigID[$scope]) )
			{
	        echo "setupWebfront: mit Parameter aus array in ".$WFC10_TabPaneItem." mit der Katgeorie ".$categoryId_WebFrontAdministrator." für den Webfront Configurator ".$scope."\n";
         	$WFC10_ConfigId=$this->WebfrontConfigID[$scope];
	        if (array_key_exists("Auswertung",$webfront_links) ) 
    	        {
        	    /* Kein Name für den Pane definiert */
            	$tabItem="Default";
	    		//echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$tabItem." in ".$WFC10_TabPaneItem."\n";            
    	        $this->createSplitPane($WFC10_ConfigId,$webfront_links,$tabItem,$WFC10_TabPaneItem."Item",$WFC10_TabPaneItem,$categoryId_WebFrontAdministrator,$scope);
        	    }
	        else
    	        {
				$order=10;    
    			foreach ($webfront_links as $Name => $webfront_group)
	    		    {
		    		/* Das erste Arrayfeld bestimmt die Tabs in denen jeweils ein linkes und rechtes Feld erstellt werden: Bewegung, Feuchtigkeit etc.
				     * Der Name für die Felder wird selbst erfunden.
    				 */

	                echo "\n**** erstelle Kategorie ".$Name." in ".$categoryId_WebFrontAdministrator." (".IPS_GetName($categoryId_WebFrontAdministrator)."/".IPS_GetName(IPS_GetParent($categoryId_WebFrontAdministrator)).").\n";
			    	$categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_WebFrontAdministrator, $order);
				    EmptyCategory($categoryId_WebFrontTab);   
            	    echo "Kategorien erstellt, Main install for ".$Name." : ".$categoryId_WebFrontTab." in ".$categoryId_WebFrontAdministrator." Kategorie Inhalt geloescht.\n";

		    		$tabItem = $WFC10_TabPaneItem.$Name;				/* Netten eindeutigen Namen berechnen */
    	            $this->deletePane($WFC10_ConfigId, $tabItem);              /* Spuren von vormals beseitigen */

	                if (array_key_exists("Auswertung",$webfront_group) ) 
    	                {
    				    echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$tabItem." in ".$WFC10_TabPaneItem."\n";
            	        $this->createSplitPane($WFC10_ConfigId,$webfront_group,$Name,$tabItem,$WFC10_TabPaneItem,$categoryId_WebFrontTab,"Administrator");
                	    }
	                else
    	                {
        			    foreach ($webfront_group as $SubName => $webfront_subgroup)
	        		        {                    
                	        /* noch eine Zwischenebene an Tabs einführen */
                    	    echo "\n  **** iTunes Visualization, erstelle Sub Kategorie ".$SubName." in ".$categoryId_WebFrontTab.".\n";
				            $categoryId_WebFrontSubTab         = CreateCategory($SubName,$categoryId_WebFrontTab, 10);
				            EmptyCategory($categoryId_WebFrontSubTab);   
        	                echo "Kategorien erstellt, Sub install for ".$SubName." : ".$categoryId_WebFrontSubTab." in ".$categoryId_WebFrontTab." Kategorie Inhalt geloescht.\n";
	
    	        			$tabSubItem = $WFC10_TabPaneItem.$Name.$SubName;				/* Netten eindeutigen Namen berechnen */
        	                $this->deletePane($WFC10_ConfigId, $tabSubItem);              /* Spuren von vormals beseitigen */
	
	                		echo "***** Tabpane ".$tabItem." erzeugen in ".$WFC10_TabPaneItem."\n";
    	                    CreateWFCItemTabPane   ($WFC10_ConfigId, $tabItem, $WFC10_TabPaneItem,  $WFC10_TabPaneOrder, $Name, "");    /* macht den Notenschlüssel in die oberste Leiste */

				            echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$tabSubItem." in ".$tabItem."\n"; 
    	                    $this->createSplitPane($WFC10_ConfigId,$webfront_subgroup,$SubName,$tabSubItem,$tabItem,$categoryId_WebFrontSubTab,"Administrator");    
        	                }
            	        }    
					$order += 10;	
    				}  // ende foreach
         	   }       
            }
		else
			{	
			echo "Webfron ConfiguratorID unbekannt.\n";
			}
		}

    /* Erzeuge ein Splitpane mit Name und den Links die in webfront_group angelegt sind in WFC10_TabPaneItem*/

    private function createSplitPane($WFC10_ConfigId, $webfront_group, $Name, $tabItem, $WFC10_TabPaneItem,$categoryId_WebFrontSubTab,$scope="Administrator")
        {
        echo "  createSplitPane mit Name ".$Name." Als Pane ".$tabItem." in ".$WFC10_TabPaneItem." im Konfigurator ".$WFC10_ConfigId." verwendet Kategorie ".$categoryId_WebFrontSubTab."\n";

		$categoryIdLeft  = CreateCategory('Left',  $categoryId_WebFrontSubTab, 10);
		$categoryIdRight = CreateCategory('Right', $categoryId_WebFrontSubTab, 20);
		echo "  Kategorien erstellt, SubSub install for Left: ".$categoryIdLeft. " Right : ".$categoryIdRight."\n"; 

			echo "   **** Splitpane $tabItem erzeugen in $WFC10_TabPaneItem:\n";
			/* @param integer $WFCId ID des WebFront Konfigurators
			 * @param string $ItemId Element Name im Konfigurator Objekt Baum
			 * @param string $ParentId Übergeordneter Element Name im Konfigurator Objekt Baum
			 * @param integer $Position Positionswert im Objekt Baum
			 * @param string $Title Title
			 * @param string $Icon Dateiname des Icons ohne Pfad/Erweiterung
			 * @param integer $Alignment Aufteilung der Container (0=horizontal, 1=vertical)
			 * @param integer $Ratio Größe der Container
			 * @param integer $RatioTarget Zuordnung der Größenangabe (0=erster Container, 1=zweiter Container)
			 * @param integer $RatioType Einheit der Größenangabe (0=Percentage, 1=Pixel)
	 		 * @param string $ShowBorder Zeige Begrenzungs Linie
			 */
			//CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);
			CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem, $WFC10_TabPaneItem,    0,     $Name,     "", 1 /*Vertical*/, 40 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Left',   $tabItem,   10, '', '', $categoryIdLeft   /*BaseId*/, 'false' /*BarBottomVisible*/);
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Right',  $tabItem,   20, '', '', $categoryIdRight  /*BaseId*/, 'false' /*BarBottomVisible*/);            

            print_r($webfront_group);    
			foreach ($webfront_group as $Group => $webfront_link)
				{
				foreach ($webfront_link as $OID => $link)
					{
					/* Hier erfolgt die Aufteilung auf linkes und rechtes Feld
			 		 * Auswertung kommt nach links und Nachrichten nach rechts
			 		 */	
                    
					echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
					if ($Group=="Auswertung")
				 		{
                        if ( (($scope=="Administrator") && $link["ADMINISTRATOR"]) || (($scope=="User") && $link["USER"]) || (($scope=="Mobile") && $link["MOBILE"]) )
                            {
				 		    echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdLeft."\n";
						    CreateLinkByDestination($link["NAME"], $OID,    $categoryIdLeft,  $link["ORDER"]);
                            }
				 		}
				 	if ($Group=="Nachrichten")
				 		{
                        if ( (($scope=="Administrator") && $link["ADMINISTRATOR"]) || (($scope=="User") && $link["USER"]) || (($scope=="Mobile") && $link["MOBILE"]) )
                            {
    				 		echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdRight."\n";
	    					CreateLinkByDestination($link["NAME"], $OID,    $categoryIdRight,  $link["ORDER"]);
                            }
						}
					} // ende foreach
                }  // ende foreach  
        }

    private function deletePane($WFC10_ConfigId, $tabItem)
        {
			if ( exists_WFCItem($WFC10_ConfigId, $tabItem) )
			 	{
				echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).") löscht TabItem : ".$tabItem."\n";
				DeleteWFCItems($WFC10_ConfigId, $tabItem);
				}
			else
				{
				echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).") TabItem : ".$tabItem." nicht mehr vorhanden.\n";
				}	
			IPS_ApplyChanges ($WFC10_ConfigId);   /* wenn geloescht wurde dann auch uebernehmen, sonst versagt das neue Anlegen ! */
        }

    }   // ende class

/******************************************************************
 *
 * Module und Klassendefinitionen
 *
 * __construct	 		speichert bereits alle Libraries und Module bereits in Klassenvariablen ab
 *   printrLibraries	gibt die gespeicherte Variable für die Library aus
 *   printrModules		gibt die gespeicherte Variable für die Module aus, alle Module für alle Libraries
 *   printModules		Alle Module die einer bestimmten Library zugeordnet sind als echo ausgeben
 *   printInstances		Alle Instanzen die einem bestimmten Modul zugeordnet sind als echo ausgeben
 *   getInstances		Alle Instanzen die einem bestimmten Modul zugeordnet sind als array ausgeben
 *   get_string_between($input,'{','}')		Unterstützungsfunktion um den json_decode zu unterstützen
 *
 *
 ******************************************************************/

class ModuleHandling
	{
	
	private $libraries;	// array mit Liste der Namen und GUIDs von Libraries 
	private $modules;	// array mit Liste der Namen und GUIDs von Modules 
	private $functions;
	private $debug;
	
	public function __construct($debug=false)
		{
		$this->debug=$debug;
		if ($debug) echo "Alle Bibliotheken mit GUID ausgeben:\n";
		foreach(IPS_GetLibraryList() as $guid)
			{
			$module = IPS_GetLibrary($guid);
			$pair[$module['Name']] = $guid;
			}
		ksort($pair);
		foreach($pair as $key=>$guid)
			{
			$this->libraries[$key]=$guid;
			if ($debug) echo "    ".$key." = ".$guid."\n";
			}
		unset($pair);
		if ($debug) echo "Alle Modulnamen mit GUID ausgeben: \n";
		foreach(IPS_GetModuleList() as $guid)
			{
			$module = IPS_GetModule($guid);
			$pair[$module['ModuleName']] = $guid;
			}
		ksort($pair);
		foreach($pair as $key=>$guid)
			{
			$this->modules[$key]=$guid;
			if ($debug) echo $key." = ".$guid."\n";
			}
		}

	public function printrLibraries()
		{
		print_r($this->libraries);
		}

	public function printrModules()
		{
		print_r($this->modules);
		}

	/* Alle Libraries als echo ausgeben 
     */
	public function printLibraries()
		{
		echo "Alle geladenen Bibliotheken auflisten:\n";		
		foreach($this->libraries as $index => $library)
			{
			//print_r($module);
			echo "   ".str_pad($index,35)."    ".$library."\n";
			}
		}
		
	/* Alle Module die einer bestimmten Library zugeordnet sind ausgeben 
     */
	public function printModules($input)
		{
		$input=trim($input);
		$key=$this->get_string_between($input,'{','}');
		if (strlen($key)==36) 
			{
			echo "Gültige GUID mit ".$key."\n";
			$modules=IPS_GetLibraryModules($input);
			}
		else
			{
			/* wahrscheinlich keine GUID sondern ein Name eingeben */
			if (isset($this->libraries[$input])==true)
				{
				echo "Library ".$input." hat GUID :".$this->libraries[$input]."\n";
				$modules=IPS_GetLibraryModules($this->libraries[$input]);
				}
			else $modules=array();	
			}
		$pair=array();			
		foreach($modules as $guid)
			{
			$module = IPS_GetModule($guid);
			$pair[$module['ModuleName']] = $guid;
			}
		if ( sizeof($pair) > 0 ) ksort($pair);
		foreach($pair as $key=>$guid)
			{
			echo "     ".$key." = ".$guid;
			//if (IPS_ModuleExists($guid)) echo "***************";
			echo "\n";
			}
		}

	/* Alle Instanzen die einem bestimmten Modul zugeordnet sind als echo ausgeben 
     */
	public function printInstances($input)
		{
		$input=trim($input);
		$key=$this->get_string_between($input,'{','}');
		if (strlen($key)==36) 
			{
			echo "Gültige GUID mit ".$key."\n";
			$instances=IPS_GetInstanceListByModuleID($input);
			}
		else
			{
			/* wahrscheinlich keine GUID sondern ein Name eingeben */
			if (isset($this->modules[$input])==true)
				{
				echo "Library ".$input." hat GUID :".$this->modules[$input]."\n";
				$instances=IPS_GetInstanceListByModuleID($this->modules[$input]);
				}
			else $instances=array();	
			}		
		foreach ($instances as $ID => $name) echo "     ".$ID."    ".$name."    ".IPS_GetName($name)."    ".IPS_GetName(IPS_GetParent($name))."\n";
		}

	/* Alle Instanzen die einem bestimmten Modul zugeordnet sind als array ausgeben 
     */
	public function getInstances($input)
		{
		$input=trim($input);
		$key=$this->get_string_between($input,'{','}');
		if (strlen($key)==36) 
			{
			if ($this->debug) echo "Gültige GUID mit ".$key."\n";
			$instances=IPS_GetInstanceListByModuleID($input);
			}
		else
			{
			/* wahrscheinlich keine GUID sondern ein Name eingeben */
			if (isset($this->modules[$input])==true)
				{
				//echo "Objekt Input ".$input." hat GUID :".$this->modules[$input]."\n";
				$instances=IPS_GetInstanceListByModuleID($this->modules[$input]);
				}
			else $instances=array();	
			}		
		return ($instances);
		}

	public function getFunctions($lookup="")
		{
		if ($lookup=="") $alleFunktionen = IPS_GetFunctionList(0);
		else
			{
			$alleFunktionen = array();
			$functions = IPS_GetFunctionList(0);
			foreach ($functions as $function)
				{
				$pos=strpos($function,"_");
				if ($pos) 
					{
					$funcName=substr($function,$pos+1);
					$funcModul=substr($function,0,$pos);
					if ($funcModul==$lookup) 
						{
						echo "   ".str_pad($funcModul,10)."   ".$funcName."   \n";
						$alleFunktionen[]=$function;
						}
					}
				}	
			}
		return ($alleFunktionen);
		}

    /* Strukturen die nicht unbedingt jeson encoded sind von ihren {} Klammern befreien
     */	
	private function get_string_between($string, $start, $end)
		{
		$string = ' ' . $string;
		$ini = strpos($string, $start);
		if ($ini == 0) return '';
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
		return substr($string, $ini, $len);
		}	

    /* Config einer Instanz oder einem array aus Instanzen ausles und bestimmte Variablen der Konfiguration als array mitgeben
     * Filterfunktion für Konfiguration
     */
	public function selectConfiguration($id,$select=false)
		{
        $result=array();
		if (is_array($id)) 
			{
			echo "Bereits ein array.\n";
			$ida=$id;
			}
		else $ida[0]=$id;
		foreach ($ida as $id1)
			{
            $config=IPS_GetConfiguration($id1);     /* alle Instanzen durchgehen */
            $configStruct=json_decode($config);
            if ( ($select===false) or !(is_array($select)) ) $result[$id1]=$configStruct;
            else    /* select ist ein Array */
                {
                foreach ($select as $entry)
                    {
                    if (isset($configStruct->$entry)) $result[$id1][$entry]=$configStruct->$entry;
                    }
			    //echo ">>>>>> ".$id1."\n";
			    //print_r($select);
                }
			}
        return ($result);    
		} /* ende function */

	}


/******************************************************************

Alternativer Error Handler

******************************************************************/

function AD_ErrorHandler($fehlercode, $fehlertext, $fehlerdatei, $fehlerzeile,$Vars)
    {
    if (!(error_reporting() & $fehlercode)) 
        {
        // Dieser Fehlercode ist nicht in error_reporting enthalten
        return;
        }
    $noerror=false;
    switch ($fehlercode) 
        {
        case E_WARNING:
            echo "<b>WARNING</b> [$fehlercode] $fehlertext<br />\n";
            if (strpos($fehlertext,"DUTY_CYCLE") !== false) $noerror=true;
            else
                {
                echo "  Warning in Zeile $fehlerzeile in der Datei $fehlerdatei";
                echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
                //print_r($Vars); echo "\n";            
                }
            break;
        case E_USER_ERROR:
            echo "<b>Mein FEHLER</b> [$fehlercode] $fehlertext<br />\n";
            echo "  Fataler Fehler in Zeile $fehlerzeile in der Datei $fehlerdatei";
            echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
            echo "Abbruch...<br />\n";
            exit(1);
            break;
        case E_USER_WARNING:
            echo "<b>Meine WARNUNG</b> [$fehlercode] $fehlertext<br />\n";
            break;
        case E_USER_NOTICE:
            echo "<b>Mein HINWEIS</b> [$fehlercode] $fehlertext<br />\n";
            break;
        default:
            echo "Unbekannter Fehlertyp: [$fehlercode] $fehlertext<br />\n";
            break;
        }
    if ($noerror=false)
        {
    	$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
	    $AllgemeineDefId     = IPS_GetObjectIDByName('AllgemeineDefinitionen',$moduleManager->GetModuleCategoryID('data'));
    
        echo "ScriptID ist ".$AllgemeineDefId."  ".IPS_GetName($AllgemeineDefId)."/".IPS_GetName(IPS_GetParent($AllgemeineDefId))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($AllgemeineDefId)))."\n";    
    
        if ($AllgemeineDefId===false)
            {
            restore_error_handler();
            return (true);
            //throw new ErrorException($fehlertext, 0, $fehlercode, $fehlerdatei, $fehlerzeile);
            }
        else
            {
            $ErrorHandlerAltID = CreateVariableByName($AllgemeineDefId, "ErrorHandler", 3);
            $ErrorHandler=GetValue($ErrorHandlerAltID);
            if (function_exists($ErrorHandler) == true)
                {
                //echo "Naechsten Error Handler aufrufen.\n";
                /* function IPSLogger_PhpErrorHandler ($ErrType, $ErrMsg, $FileName, $LineNum, $Vars) */
                $fehler=$ErrorHandler($fehlercode, $fehlertext, $fehlerdatei, $fehlerzeile, $Vars);
                }
            else return (true);
            }
        }    
    }







?>
<?

	IPSUtils_Include ("IPSModuleManagerGUI.inc.php", "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
   
/******************************************************************************************/   
/*                                                                                        */   
/*                                        BKS01                                           */
/*                                                                                        */
/******************************************************************************************/

   
/* ObjectID Adresse vom send email server */

$sendResponse = 30887; //ID einer SMTP Instanz angeben, um Rückmelde-Funktion zu aktivieren


/* Unterschiede getaktete und nicht getaktete Verbindung
	bei Win8 noch nicht klar. DNS geht scheinbar lokal nicht, drum fixe IP Adresse angeben

*/

/* FS20 Adress-Schema */

 /* BKS (Burg Kreuzenstein Hausautomatisierung


 Stromheizung mit FHZ1300 (USB)

 nach Batterie einlegen Uhrzeit und Datum einstellen

 Hyst:  0,6 °C
 HC 1: 2412
 HC 2: 4141
 AG:    14
 UA:    21

Hauscode:   2412 4141
AZ Heizung:   						14 21
	Zusatzheizung  				14 22
	Router Stromversorgung  	14 11
WZ Heizung:   						24 21
	Schnell:   						24 21
Keller Heizung:   				34 21
	Zusatzheizung  				34 22
	GBE Switch Stromversorgung 34 11
 */

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
define("ADR_Webcam_innen","10.0.1.2");
define("ADR_Webcam_innen_Port","2001");
define("ADR_Homematic","10.0.1.3");

define("ADR_Webcam_lbg","hupo35.ddns-instar.de");
define("ADR_Webcam_Outdoor","10.0.1.8");
define("ADR_Webcam_Outdoor_Port","2002");

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




//echo IPS_GetName(0);
if (IPS_GetName(0)=="LBG70")
	{

/******************************************************************************************/
/*                                                                                        */
/*                                        LBG70                                           */
/*                                                                                        */
/******************************************************************************************/


function LogAnwesenheit_Configuration()
	{
	return array(
			"VZB"    =>    30700,                                       /* Bewegungsmelder im VZ */
			"AZB"    =>    54389,                                       /* Bewegungsmelder im AZ */
			"WZB"    =>    11681,                                       /* Bewegungsmelder in der Kueche */
			"BZT"    =>    25921,                                       /* Kontakt Badezimmertuere */
			"EGT"    =>    41275,                                       /* Kontakt Eingangstuere */
		         );
	}

	function LogAlles_Configuration() {
		return array(
			"AZ"    => array("Leistung"           	 => 190, 				/* zwei Radiatoren 110 Arbeitszimmer und 80 Gaestezimmer */
							  "OID_PosHT80b"         => 54440,           /* OID Position vom Regler */
  			              	  "OID_Zeit"           	 => 30741,
  			              	  "OID_Energie"          => 34120,           /* Energieverbrauch Verlauf, ideal für Kurvendarstellung - Faktor egal */
  			              	  "OID_EnergieVortag"    => 38894,           /* ein Wert pro Tag wird immer um 00:00 geschrieben */
  			              	  "OID_EnergieTag"       => 43184,           /* summierter Tagesverbrauch, wird immer um 00:00 zurueckgesetzt, nur mehr zur Kompatibilität */
			              ),
			"BZ"    => array("Leistung"           	 => 50, 					/* ein Radiator 50 */
							  "OID_PosHT80b"         => 14642,           /* OID Position vom Regler */
  			              	  "OID_Zeit"           	 => 46077,
  			              	  "OID_Energie"          => 30563,           /* Energieverbrauch Verlauf, ideal für Kurvendarstellung - Faktor egal */
  			              	  "OID_EnergieVortag"    => 38725,           /* ein Wert pro Tag wird immer um 00:00 geschrieben */
  			              	  "OID_EnergieTag"       => 41149,           /* summierter Tagesverbrauch, wird immer um 00:00 zurueckgesetzt, nur mehr zur Kompatibilität */
			              ),
			"SZ"    => array("Leistung"           	 => 140,					/* ein Radiator 70 doppelt aufgebaut */
							  "OID_PosHT80b"         => 34186,           /* OID Position vom Regler */
  			              	  "OID_Zeit"           	 => 56091,
  			              	  "OID_Energie"          => 29754,           /* Energieverbrauch Verlauf, ideal für Kurvendarstellung - Faktor egal */
  			              	  "OID_EnergieVortag"    => 10710,           /* ein Wert pro Tag wird immer um 00:00 geschrieben */
  			              	  "OID_EnergieTag"       => 22670,           /* summierter Tagesverbrauch, wird immer um 00:00 zurueckgesetzt, nur mehr zur Kompatibilität */
			              ),
			"WZ"    => array("Leistung"           	 => 170,   				/* zwei Radiatoren Kueche 70 und Essplatz 100 */
							  "OID_PosHT80b"         => 27073,           /* OID Position vom Regler */
  			              	  "OID_Zeit"           	 => 52403,
  			              	  "OID_Energie"          => 46217,           /* Energieverbrauch Verlauf, ideal für Kurvendarstellung - Faktor egal */
  			              	  "OID_EnergieVortag"    => 40178,           /* ein Wert pro Tag wird immer um 00:00 geschrieben */
  			              	  "OID_EnergieTag"       => 47674,           /* summierter Tagesverbrauch, wird immer um 00:00 zurueckgesetzt, nur mehr zur Kompatibilität */
			              ),
			"TOTAL" => array("OID_Energie"          => 30163,
  			              	  "OID_EnergieSumme"     => 43589,
			              ),
						);
	}


function LogAlles_Temperatur() {
		return array(
			"AZ-T"    => array(	"OID_Sensor"         => 39227,           /* OID Position vom Sensor im AZ */
  			              		"OID_TempWert"    	=> 19253,           /* OID vom Spiegelregister, weil Wert um Mitternach nicht als VALUE_OLD abgehohlt werden kann */
  			              		"Type"               => "Innen",
			              ),
			"BZ-T"    => array(	"OID_Sensor"         => 56634,           /* OID Position vom Sensor im BZ */
								"OID_TempWert"    	=> 36041,           /* OID vom Spiegelregister */
  			              		"Type"               => "Innen",
			              ),
			"SZ-T"    => array(	"OID_Sensor"         => 33694,           /* OID Position vom Sensor im SZ */
  			              		"OID_TempWert"    	=> 42862,           /* OID vom Spiegelregister */
  			              		"Type"               => "Innen",
			              ),
			"WZ-T"    => array(	"OID_Sensor"         => 17554,           /* OID Position vom Sensor im WZ */
  			              		"OID_TempWert"    	=> 51550,           /* OID vom Spiegelregister */
  			              		"Type"               => "Innen",
			              ),
			"AUSSEN-OST"  => array(	"OID_Sensor"    => 16433,           /* OID Position vom Sensor AUSSEN OST*/
  			              		"OID_TempWert"    	=> 16765,           /* OID vom Spiegelregister, kann ruhig versteckt angeordnet werden, oder statt echtem Aussensensorwert */
  			              		"Type"              => "Aussen",
			              ),
			"AUSSEN-WEST"  => array( "OID_Sensor"   => 22695,           /* OID Position vom Sensor AUSSEN WEST*/
  			              		"OID_TempWert"    	=> 18688,           /* OID vom Spiegelregister, kann ruhig versteckt angeordnet werden, oder statt echtem Aussensensorwert */
  			              		"Type"               => "Aussen",
			              ),
			"TOTAL"   => array(	"OID_TempWert_Aussen"    	=> 11477,   /* einfach Temperaturwerte von vorher zusammengezählt und richtig dividiert */
			                    "OID_TempWert_Innen"    	=> 21157,
			                    "OID_TempTagesWert_Aussen" => 34862,   /* Tageswerte sind immer der letzte Tag */
			                    "OID_TempTagesWert_Innen"  => 29829,
			              ),
						);
	}

/* logAlles_Hostnames wurde in OperationCenter_config verlegt */

/* Verbraucher */

//define("ADR_ArbeitszimmerNetzwerk",37160);
define("ADR_ArbeitszimmerNetzwerk",36225);   /* jetzt Homematic */
define("ADR_ArbeitszimmerServer",25840);
define("ADR_ArbeitszimmerVerstaerker",39136);
//define("ADR_ArbeitszimmerComputer",21196);
define("ADR_ArbeitszimmerComputer",55176);   /* jetzt Homematic */
//define("ADR_ArbeitszimmerFestplatten",10020);
define("ADR_ArbeitszimmerFestplatten",21427);   /* jetzt Homematic */
define("ADR_WohnzimmerNetzwerk",24744);
define("ADR_WohnzimmerXboxPS3",16013);
define("ADR_WohnzimmerFernseherReceiver",47562);


/* Lampen */

define("ADR_GaestezimmerLampe",24122);
define("ADR_WohnzimmerEckstehlampe",12828);
define("ADR_WohnzimmerLampe",44267);
//define("ADR_ArbeitszimmerLampe",40351);
define("ADR_ArbeitszimmerLampe",34651);   /* jetzt Homematic */
define("ADR_SchlafzimmerLampe",31970);
define("ADR_SchlafzimmerKastenlampe",10987);

$id_sound = 23225;
$sendResponse = 43606; //ID einer SMTP Instanz angeben, um Rückmelde-Funktion zu aktivieren



	/* verzeichnisse */
	define("DIR_copyscriptsdropbox","C:/Users/Wolfgang/Dropbox/Privat/IP-Symcon/scripts-LBG/");

	} /* ende besondere Konfig für LBG70 */
	
if (IPS_GetName(0)=="BKS01")
	{
	
/******************************************************************************************/
/*                                                                                        */
/*                                        BKS01                                           */
/*                                                                                        */
/******************************************************************************************/
	
	function LogAlles_Configuration() {
		return array(
			"AZ"    => array("Leistung"           	 => 800, 				/* ein Radiator im Arbeitszimmer 800 Watt, 0.4m2 */
								  "OID_Temp"             => 38610,           /* zugehoeriger Temperatursensor, normalerweise im Regler berets eingebaut */
								  "OID_PosHT80b"         => 32688,           /* OID Position vom Regler */
								  "OID_Tageswert"        => 44482,           /* OID Tageswert, jeden Tag um Mitternacht geschrieben */
			              ),
			"KZ"    => array("Leistung"           	 => 1050, 				/* ein Radiator mit 600 Watt und im Technikraum ein weiterer mit 450 Watt, zusaetzlicher Radiator nur fuer Zusatzheizung */
								  "OID_Temp"             => 13063,           /* zugehoeriger Temperatursensor, normalerweise im Regler berets eingebaut */
								  "OID_PosHT80b"         => 10884,           /* OID Position vom Regler */
								  "OID_Tageswert"        => 40345,           /* OID Tageswert, jeden Tag um Mitternacht geschrieben */
			              ),
			"WZ"    => array("Leistung"           	 => 450,					/* ein Radiator mit 450 Watt und 0.24m2 */
								  "OID_Temp"             => 41873,           /* zugehoeriger Temperatursensor, normalerweise im Regler berets eingebaut */
								  "OID_PosHT80b"         => 17661,           /* OID Position vom Regler */
								  "OID_Tageswert"        => 28142,           /* OID Tageswert, jeden Tag um Mitternacht geschrieben */
			              ),
			"WZZ"   => array("Leistung"           	 => 2000,				/* ein Radiator mit entweder 1250 plus 750 Watt mit/ohne Umluft*/
								  "OID_Temp"             => 41873,           /* zugehoeriger Temperatursensor, normalerweise im Regler berets eingebaut */
								  "OID_PosHT80b"         => 33800,           /* OID Position vom Regler */
								  "OID_Tageswert"        => 43228,           /* OID Tageswert, jeden Tag um Mitternacht geschrieben */
			              ),
			"KZZ"   => array("Leistung"           	 => 800,				   /* ein Radiator mit entweder 1250 plus 750 Watt mit/ohne Umluft*/
								  "OID_Temp"             => 13063,           /* zugehoeriger Temperatursensor, normalerweise im Regler berets eingebaut */
								  "OID_PosHT80b"         => 39253,           /* OID Position vom Regler */
								  "OID_Tageswert"        => 56994,           /* OID Tageswert, jeden Tag um Mitternacht geschrieben */
			              ),
			"TOTAL" => array("OID_Energie"          => 47684,           /* falsche OID, wird nicht verwendet !!! */
  			              	  "OID_EnergieSumme"     => 58447,           /* falsche OID, wird nicht verwendet !!! */
								  "OID_Tageswert"        => 24129,           /* OID Tageswert, jeden Tag um Mitternacht geschrieben */
			              ),
						);
	}

	function LogAlles_Temperatur() {
		return array(       
//			"AZ-T"    => array(	"OID_Sensor"         => 38610,           
//  			              	  		"OID_Max"            => 53022
//				              	  		"OID_Min"            => 32252 
//  			              	  		"Type"               => "Innen",
//			              ),
//			"KZ-T"    => array(	"OID_Sensor"         => 13063,           
//	 			              	  		"OID_Max"            => 59160  
//  			              	  		"OID_Min"            => 52129
//  			              	  		"Type"               => "Andere",
//			              ),
//			"WZ-T"    => array(	"OID_Sensor"         => 41873,       
//  			              	  		"OID_Max"            => 34073 
//  			              	  		"OID_Min"            => 24331 
//  			              	  		"Type"               => "Innen",
//			              ),
//			"AUSSE2-T" => array( "OID_Sensor"         => 32563,           
//  			              	  		"OID_Max"            => 22884 
//  			              	  		"OID_Min"            => 15265 
//  			              	  		"Type"               => "Aussen",
//			              ),
//			"WETTER-T" => array( "OID_Sensor"         => 31094,           
//  			              	  		"OID_Max"            => 54386 
//  			              	  		"OID_Min"            => 30234 
//  			              	  		"Type"               => "Aussen",
//			              ),
//			"KELLER-T" => array(	"OID_Sensor"         => 48182,           
//  			              	  		"OID_Max"            => 28619 
//  			              	  		"OID_Min"            => 19040 
//  			              	  		"Type"               => "Andere",
//			              ),
//			"WINGAR-T" => array(	"OID_Sensor"         => 29970, 
//  			              	  		"OID_Max"            => 21658 
//  			              	  		"OID_Min"            => 55650 
//  			              	  		"Type"               => "Andere",
//			              ),
//			"KELLAG-T" => array(	"OID_Sensor"         => 58776,          
//  			              	  		"OID_Max"            => 48777 
//  			              	  		"OID_Min"            => 17535 
//  			              	  		"Type"               => "Andere",
//			              ),
//			"TOTAL" 	=> array(	"OID_TempWert_Aussen"    	=> 21416 
//			                     "OID_TempWert_Innen"    	=> 56688 
//			                     "OID_TempTagesWert_Aussen" => 13320 
//			                     "OID_TempTagesWert_Innen"  => 35271 
//			              ), 
						);
	}

	function LogAlles_Bewegung() {
		return array(
			"WZ"    	 => array(	"OID_Sensor"         => 35993,           /* OID Position vom Sensor im WZ */
			                     "OID_Status"         => 57705,
  			              	  		"Type"               => "Motion",
			              ),
			"KLA"     => array(	"OID_Sensor"         => 59021,           /* OID Position vom Sensor im Kellerabgang */
			                     "OID_Status"         => 31481,
  			              	  		"Type"               => "Motion",
			              ),
			"WG-T"    => array(	"OID_Sensor"         => 28444,           /* OID Position vom Sensor im WG */
			                     "OID_Status"         => 18901,
  			              	  		"Type"               => "State",
			              ),
			"KL-T"    => array(	"OID_Sensor"         => 24013,           /* OID Position vom Sensor im Keller */
			                     "OID_Status"         => 22562,
  			              	  		"Type"               => "State",
			              ),
			"TOTAL" 	=> array(	"OID_Bewegung"    	=> 14403,   /* einfach Bewegungswerte (Motion) oder verknuepft */
										"OID_Alarm"    		=> 51833,   /* einfach Alarmwerte (State) oder verknuepft */
										"OID_Status"    		=> 33827,   /* WirsindzuHause : Indikation ob wir zu Hause sind */
			              ),
						);
	}



	/******************************************************************/

	/* verzeichnisse */
	define("DIR_copyscriptsdropbox","c:/Users/wolfg_000/Dropbox/Privat/IP-Symcon/scripts-BKS/");
	} /* ende besondere Konfig für BKS01 */

/* obige Konfigurationen kann man langsam loeschen, da obsolet, beide Server wurden durch neuere Versionen ersetzt */


/****************************************************************************************************/


/**********************************************************************************************************************************************************/
/* immer wenn eine Statusmeldung per email angefragt wird */


/* wird später unter Allgemein gespeichert */

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

/* -----------------------------------------------------------------------------------------------------------------
 *
 * es gibt noch Server spezifische Befehle, systematisch eliminieren
 *
 *=========================================================================================================================================
 *
 */
	
if (IPS_GetName(0)=="LBG70")
	{

	/***************  HEIZUNGSENERGIEVERBRAUCH LBG70 spezifisch ********/

	if ($aktuell)
	    {
		$energieverbrauch="";
		}
	else
		{
		IPS_RunScript(35787);

		$energieverbrauch="Heizenergieverbrauch der letzten Tage (bei ".GetValue(52478)." Euro pro kWh) :\n";
		$energieverbrauch.="\nHeizenergieverbrauch (1/7/30/360) : ".number_format(GetValue(44839), 2, ",", "" )." / ";
		$energieverbrauch.=number_format(GetValue(33301), 2, ",", "" )." / ";
		$energieverbrauch.=number_format(GetValue(29148), 2, ",", "" )." / ";
		$energieverbrauch.=number_format(GetValue(16969), 2, ",", "" )." kWh";
		$energieverbrauch.="\nHeizenergiekosten    (1/7/30/360) : ".number_format(GetValue(18976), 2, ",", "" )." / ";
		$energieverbrauch.=number_format(GetValue(34239), 2, ",", "" )." / ";
		$energieverbrauch.=number_format(GetValue(20687), 2, ",", "" )." / ";
		$energieverbrauch.=number_format(GetValue(45647), 2, ",", "" )." Euro\n\n";
		}

	$cost="\n\nInternetkosten:\n".
			"\nAufgeladen wurde bisher : ".GetValue(29162)." Euro".
			"\nVerbraucht wurde bisher : ".GetValue(37190)." Euro".
			"\nÄnderung heute          : ".GetValue(29370)." Euro\n";

	$internet="Internet Erreichbarkeit:\n".
			  "\nLBG70 Server  : ".GetValue(27549)." seit ".date("d.m.y H:i:s",GetValue(26654)).
  			  "\nBKS01 Server  : ".GetValue(34955)." seit ".date("d.m.y H:i:s",GetValue(23044)).
  			  "\nFirmentelefon : ".GetValue(30691)." seit ".date("d.m.y H:i:s",GetValue(11781)).
  			  "\nPrivattelefon : ".GetValue(27870)." seit ".date("d.m.y H:i:s",GetValue(13224))."\n\n";

	$statusverlauf="Verlauf Serverstatus:\n\n".
	      GetValue(37381)."\n".
	      GetValue(46979)."\n".
	      GetValue(23626)."\n".
	      GetValue(46922)."\n".
			GetValue(49498)."\n".
			GetValue(49200)."\n".
	      GetValue(16415)."\n\n";

	if ($aktuell)
	    {
		$ergebnis_tabelle="";
		}
	else
		{
		/* Energiewerte der Vortage als Zeitreihe */
		$jetzt=time();
		$endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
		$starttime=$endtime-60*60*24*7;
		$werte = AC_GetLoggedValues(IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0], 21762, $starttime, $endtime, 0);
		$zeile = array("Datum" => array("Datum",0,1,2), "Heizung" => array("Heizung",0,1,2), "Datum2" => array("Datum",0,1,2), "Energie" => array("Energie",0,1,2), "EnergieVS" => array("EnergieVS",0,1,2));
		//print_r($werte);
		//	echo "Werte Heizung:\n";
		$vorigertag=date("d.m.Y",$jetzt);
		$laufend=1;
		$ergebnis_tabelle="Heizenergie der letzten Tage: \n".substr("\n                          ",0,12);
		foreach($werte as $wert)
			{
			$zeit=$wert['TimeStamp']-60;
			if (date("d.m.Y", $zeit)!=$vorigertag)
			   {
				$zeile["Datum"][$laufend] = date("D d.m", $zeit);
				$zeile["Heizung"][$laufend] = number_format($wert['Value'], 2, ",", "" ) ." kWh";
				$ergebnis_tabelle.= substr($zeile["Datum"][$laufend]."            ",0,12);
				$laufend+=1;
				}
			$vorigertag=date("d.m.Y",$zeit);
			}
		$anzahl=$laufend-1;
		$laufend=0;
		$ergebnis_tabelle.="\n";
		//print_r($zeile);
		while ($laufend<=$anzahl)
			{
			$ergebnis_tabelle.=substr($zeile["Heizung"][$laufend]."            ",0,12);
			$laufend+=1;
			//echo $ergebnis_tabelle."\n";
			}
		$ergebnis_tabelle.="\n\n";
		}
		/**********************************************/
	}
	
if (IPS_GetName(0)=="BKS01")      /*  spezielle Routine für BKS01    */
	{

	if ($aktuell)   /* aktuelle Werte */
		{
		$aktheizleistung="Aktuelle Heizleistung: ".GetValue(34354)." W\n\n";
		}
	else              /* die vom Vortag */
		{
		$aktheizleistung="";
		}

	IPS_RunScript(48267);
	IPS_RunScript(13352);
	IPS_RunScript(32860);
	IPS_RunScript(45023);
	IPS_RunScript(41653);
  	echo ">>Vorwertberechnung. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";

	if (!$aktuell)       /* die Werte vom Vortag */
		{
		$ergebnis_tagesenergie="Heizungsenergiewerte Vortag: \n\n";
		$arr=LogAlles_Configuration();    /* Konfigurationsfile mit allen Variablen  */
		foreach ($arr as $identifier=>$station)
			{
			$EnergieTagFinalID=$station["OID_Tageswert"];
			$ergebnis_tagesenergie=$ergebnis_tagesenergie.$identifier.":".number_format(GetValue($EnergieTagFinalID), 2, ",", "" )."kWh ";
			}
		$ergebnis_tagesenergie.="\n\n";
		$ergebnis_tagesenergie.=   "1/7/30/360 : ".number_format(GetValue(35510), 0, ",", "" )."/"
							  				    .number_format(GetValue(25496), 0, ",", "" )."/"
											    .number_format(GetValue(54896), 0, ",", "" )."/"
											    .number_format(GetValue(30229), 0, ",", "" )." kWh\n";
		}
	else        /* aktuelle Werte */
		{
		$ergebnis_tagesenergie="Heizungsnergiewerte Aktuell: \n\n";
		$arr=LogAlles_Configuration();    /* Konfigurationsfile mit allen Variablen  */

		$energieGesTagID = CreateVariableByName(53458,"Summe_EnergieTag", 2);
		foreach ($arr as $identifier=>$station)
			{
			if ($identifier=="TOTAL")
				{
   				$ergebnis_tagesenergie=$ergebnis_tagesenergie.$identifier.":".number_format(GetValue($energieGesTagID), 2, ",", "" )."kWh \n";
				break;
				}
			$energieTagID = CreateVariableByName(53458, $identifier."_EnergieTag", 2);
			$ergebnis_tagesenergie=$ergebnis_tagesenergie.$identifier.":".number_format(GetValue($energieTagID), 2, ",", "" )."kWh ";   /* Schoenes Ergebnis fuer email bauen */
			}
  		echo ">>Heizungswerte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
		}
	unset($identifier); // break the reference with the last element

	/* Energiewerte der Vortage als Zeitreihe */
	$jetzt=time();
	$endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
	$starttime=$endtime-60*60*24*9;

	$werte = AC_GetLoggedValues(IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0], 24129, $starttime, $endtime, 0);
	$zeile = array("Datum" => array("Datum",0,1,2), "Heizung" => array("Heizung",0,1,2), "Datum2" => array("Datum",0,1,2), "Energie" => array("Energie",0,1,2), "EnergieVS" => array("EnergieVS",0,1,2));
	//$zeile = array("Datum" => array("Datum",0,1,2), "Heizung" => array("Heizung",0,1,2), "Datum2" => array("Datum",0,1,2), "Energie" => array("Energie",0,1,2));
	$vorigertag=date("d.m.Y",$jetzt);
	$laufend=1;
	$ergebnis_tabelle=substr("                          ",0,12);
	foreach($werte as $wert)
			{
			$zeit=$wert['TimeStamp']-60;
			if (date("d.m.Y", $zeit)!=$vorigertag)
			   {
				$zeile["Datum"][$laufend] = date("D d.m", $zeit);
				$zeile["Heizung"][$laufend] = number_format($wert['Value'], 2, ",", "" ) ." kWh";
				$ergebnis_tabelle.= substr($zeile["Datum"][$laufend]."            ",0,12);
				$laufend+=1;
				}
			$vorigertag=date("d.m.Y",$zeit);
			}

	$werte = AC_GetLoggedValues(IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0], 13448, $starttime, $endtime, 0);
	$vorigertag=date("d.m.Y",$jetzt);
	$anzahl=$laufend-1;
	$laufend=1;
	$ergebnis_tabelle1=substr("                          ",0,12);
	foreach($werte as $wert)
			{
			$zeit=$wert['TimeStamp']-60;
			if (date("d.m.Y", $zeit)!=$vorigertag)
			   {
				$zeile["Datum2"][$laufend] = date("D d.m", $zeit);
				$zeile["Energie"][$laufend] = number_format($wert['Value'], 2, ",", "" ) ." kWh";
				$ergebnis_tabelle1.= substr($zeile["Datum2"][$laufend]."            ",0,12);
				$laufend+=1;
				}
			$vorigertag=date("d.m.Y",$zeit);
			}
	$anzahl2=$laufend-1;
	$laufend=0;
	$ergebnis_tabelle.="\n";
	//print_r($zeile);
	while ($laufend<=$anzahl)
		{
		$ergebnis_tabelle.=substr($zeile["Heizung"][$laufend]."            ",0,12);
		$laufend+=1;
		//echo $ergebnis_tabelle."\n";
		}
	$ergebnis_tabelle1.="\n";
	$laufend=0;
	while ($laufend<=$anzahl2)
		{
		$ergebnis_tabelle1.=substr($zeile["Energie"][$laufend]."            ",0,12);
		$laufend+=1;
		//echo $ergebnis_tabelle."\n";
		}
	$ergebnistab_energie=$ergebnis_tabelle1."\n\n";
	$ergebnistab_heizung=$ergebnis_tabelle."\n\n";

  	echo ">>Vorwertberechnung Energie. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";

	//print_r($zeile);

	//echo"Keller:".GetValue(1);

	$ergebnisTemperatur="\nAktuelle Temperaturwerte :\n\n";
	$arr=LogAlles_Temperatur();    /* Konfigurationsfile mit allen Variablen  */

	foreach ($arr as $identifier=>$station)
		{
		if ($identifier=="TOTAL")
			{
			break;
			}
		//echo $identifier;
		$TempWertID = $station["OID_Sensor"];
		$ergebnisTemperatur = $ergebnisTemperatur.$identifier." : ".number_format(GetValue($TempWertID), 2, ",", "" )."°C ";
		}
	unset($identifier); // break the reference with the last element

	$ergebnisRegen="\n\nRegenmenge Vortag: ".number_format(GetValue(15200), 2, ",", "" )." mm\n";
	$ergebnisRegen.=   "1/7/30/360 : ".number_format(GetValue(37587), 2, ",", "" )."/"
							  				    .number_format(GetValue(10370), 2, ",", "" )."/"
											    .number_format(GetValue(13883), 2, ",", "" )."/"
											    .number_format(GetValue(10990), 2, ",", "" )." mm\n";

	$ergebnisStrom="\n\nTages-Stromverbrauch Vortag: ".GetValue(13448)." kWh\n";
	$ergebnisStrom.=   "1/7/30/360 : ".number_format(GetValue(52252), 0, ",", "" )."/"
							  				    .number_format(GetValue(35513), 0, ",", "" )."/"
											    .number_format(GetValue(35289), 0, ",", "" )."/"
											    .number_format(GetValue(51307), 0, ",", "" )." kWh\n";
	$ergebnisStrom.=   "1/7/30/360 : ".number_format(GetValue(29903), 0, ",", "" )."/"
							  				    .number_format(GetValue(44005), 0, ",", "" )."/"
											    .number_format(GetValue(20129), 0, ",", "" )."/"
											    .number_format(GetValue(47761), 0, ",", "" )." Euro\n";

	$ergebnisStatus="\nAenderungsverlauf Internet Connectivity :\n\n";
	$ergebnisStatus=$ergebnisStatus."Downtime Internet :".GetValue(49809)." min\n\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(51715)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(55372)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(52397)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(51343)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(29913)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(27604)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(30167)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(41813)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(11169)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(18739)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(39489)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(12808)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(13641)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(36734)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(46381)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(24490)."\n";

	$ergebnisStatus.="\nDatenvolumen Down/Up : ".GetValue(32332)."/".GetValue(37701)." Mbyte\n";
	$ergebnisStatus.=  " Down 7/30/30/30/360 : ".GetValue(32642)."/"
															  .GetValue(49944)."/"
															  .GetValue(49121)."/"
															  .GetValue(17604)."/"
															  .GetValue(12069)." Mbyte\n";
	$ergebnisStatus.=  "   Up 7/30/30/30/360 : ".GetValue(39846)."/"
															  .GetValue(46063)."/"
															  .GetValue(45333)."/"
															  .GetValue(50549)."/"
															  .GetValue(21647)." MByte\n";

	$ergebnisBewegung="\n\nVerlauf der Bewegungen:\n\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(38964)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(23869)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(16966)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(14097)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(14944)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(42042)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(39559)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(36666)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(30427)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(55972)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(57278)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(45148)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(21096)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(46545)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(25902)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(13726)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(22969)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(56534)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(59126)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(45878)."\n";


	$ergebnisSteuerung="\n\nVerlauf der Steuerung:\n\n";
	$baseId  = IPSUtil_ObjectIDByPath('Program.Steuerung.Nachrichtenverlauf-Steuerung');
	$zeile1 = CreateVariableByName($baseId, "Nachricht_Steuerung_Zeile01", 3);
	$zeile2 = CreateVariableByName($baseId, "Nachricht_Steuerung_Zeile02", 3);
	$zeile3 = CreateVariableByName($baseId, "Nachricht_Steuerung_Zeile03", 3);
	$zeile4 = CreateVariableByName($baseId, "Nachricht_Steuerung_Zeile04", 3);
	$zeile5 = CreateVariableByName($baseId, "Nachricht_Steuerung_Zeile05", 3);
	$zeile6 = CreateVariableByName($baseId, "Nachricht_Steuerung_Zeile06", 3);
	$zeile7 = CreateVariableByName($baseId, "Nachricht_Steuerung_Zeile07", 3);
	$zeile8 = CreateVariableByName($baseId, "Nachricht_Steuerung_Zeile08", 3);
	$zeile9 = CreateVariableByName($baseId, "Nachricht_Steuerung_Zeile09", 3);
	$zeile10 = CreateVariableByName($baseId, "Nachricht_Steuerung_Zeile10", 3);
	$zeile11 = CreateVariableByName($baseId, "Nachricht_Steuerung_Zeile11", 3);
	$zeile12 = CreateVariableByName($baseId, "Nachricht_Steuerung_Zeile12", 3);
	$zeile13 = CreateVariableByName($baseId, "Nachricht_Steuerung_Zeile13", 3);
	$zeile14 = CreateVariableByName($baseId, "Nachricht_Steuerung_Zeile14", 3);
	$zeile15 = CreateVariableByName($baseId, "Nachricht_Steuerung_Zeile15", 3);
	$zeile16 = CreateVariableByName($baseId, "Nachricht_Steuerung_Zeile16", 3);

	$ergebnisSteuerung.=GetValue($zeile1)."\n";
	$ergebnisSteuerung.=GetValue($zeile2)."\n";
	$ergebnisSteuerung.=GetValue($zeile3)."\n";
	$ergebnisSteuerung.=GetValue($zeile4)."\n";
	$ergebnisSteuerung.=GetValue($zeile5)."\n";
	$ergebnisSteuerung.=GetValue($zeile6)."\n";
	$ergebnisSteuerung.=GetValue($zeile7)."\n";
	$ergebnisSteuerung.=GetValue($zeile8)."\n";
	$ergebnisSteuerung.=GetValue($zeile9)."\n";
	$ergebnisSteuerung.=GetValue($zeile10)."\n";
	$ergebnisSteuerung.=GetValue($zeile11)."\n";
	$ergebnisSteuerung.=GetValue($zeile12)."\n";
	$ergebnisSteuerung.=GetValue($zeile13)."\n";
	$ergebnisSteuerung.=GetValue($zeile14)."\n";
	$ergebnisSteuerung.=GetValue($zeile15)."\n";
	$ergebnisSteuerung.=GetValue($zeile16)."\n";

	$BrowserExtAdr="http://".trim(GetValue(45252)).":82/";
	$BrowserIntAdr="http://".trim(GetValue(33109)).":82/";
	$IPStatus="\n\nIP Symcon Aufruf extern unter:".$BrowserExtAdr.
	          "\nIP Symcon Aufruf intern unter:".$BrowserIntAdr."\n";

	/* Werte die es in BKS nicht gibt zumindest setzen */
	$guthaben=""; $cost=""; $internet=""; $statusverlauf=""; $energieverbrauch=""; $ergebnis_tabelle="";
	echo "\n----------------------------------------------------\n";


	}


/************** das war spezielle Routine BKS01
 *
 *=========================================================================================================================================
 */



/******************************************************************************************
		
Allgemeiner Teil, unabhängig von Hardware oder Server
		
******************************************************************************************/



	if ($aktuell) /* aktuelle Werte */
		{
		$alleTempWerte="";
		$alleHumidityWerte="";
		$alleMotionWerte="";
		$alleHelligkeitsWerte="";
		$alleStromWerte="";
		$alleHeizungsWerte="";
		
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
			foreach ($Homematic as $Key)
				{
				/* Alle Homematic Energiesensoren ausgeben */
				if ( (isset($Key["COID"]["RAIN_COUNTER"])==true) )
					{
					/* alle Regenwerte */

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
			if (isset($installedModules["Gartensteuerung"])==true)
				{
				echo "Die Regenwerte der letzten 10 Tage ausgeben.\n";
				$ergebnisRegen.="\nIn den letzten 10 Tagen hat es zu folgenden Zeitpunkten geregnet:\n";
				/* wenn die Gartensteuerung installiert ist, gibt es einen Regensensor der die aktuellen Regenmengen der letzten 10 Tage erfassen kann */
				IPSUtils_Include ('Gartensteuerung_Library.class.ips.php', 'IPSLibrary::app::modules::Gartensteuerung');
				$gartensteuerung = new Gartensteuerung();
				foreach ($gartensteuerung->regenStatistik as $regeneintrag)
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
			$MeterConfig = get_MeterConfiguration();

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
				$ergebnisOperationCenter.= "  Whatismyipaddress liefert : ".$result["IP"]."\n\n";
				}
				
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
			$ergebnisOperationCenter.=$OperationCenter->HardwareStatus(true);
			
			$ergebnisErrorIPSLogger.="\nAus dem Error Log der letzten Tage :\n\n";
			$ergebnisErrorIPSLogger.=$OperationCenter->getIPSLoggerErrors();
			
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


		/******************************************************************************************/

		$alleHM_Errors=HomematicFehlermeldungen();

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
			$alleHumidityWerte.$alleHelligkeitsWerte.$alleMotionWerte.$alleStromWerte.$alleHM_Errors.$ServerRemoteAccess.$SystemInfo.$ergebnisErrorIPSLogger;
			}
		else
		   {
			$ergebnis=$einleitung.$aktheizleistung.$ergebnis_tagesenergie.$ergebnisTemperatur.$alleTempWerte.$alleHumidityWerte.$alleHelligkeitsWerte.$alleHeizungsWerte.
			$ergebnisOperationCenter.$alleMotionWerte.$alleStromWerte.$alleHM_Errors.$ServerRemoteAccess.$SystemInfo.$ergebnisErrorIPSLogger;
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
			$MeterConfig = get_MeterConfiguration();
			
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
		   $guthabenid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
	   	IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");
			$GuthabenConfig = get_GuthabenConfiguration();
			$guthaben="Guthabenstatus:\n";
	     	foreach ($GuthabenConfig as $TelNummer)
   	  	   {
   			$phone1ID = CreateVariableByName($guthabenid, "Phone_".$TelNummer["NUMMER"], 3);
   			$phone_Summ_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Summary", 3);
	   		$guthaben .= "\n".GetValue($phone_Summ_ID);
			  //"\n".GetValue(24085).
			  //"\n".GetValue(27029).
			  //"\n".GetValue(59623).
			  //"\n".GetValue(39724).
  			  //"\n".GetValue(54406).
			  //"\n".GetValue(50426)."\n\n";
				}
			$guthaben .= "\n\n";
		  	echo ">>Guthaben historische Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
			}
		else
			{
			$guthaben="";
			}

		/************** Werte der Custom Components ****************************************************************************/

		if (isset($installedModules["CustomComponents"])==true)
		   	{

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
			$ergebnisGarten="\n\nVerlauf der Gartenbewaesserung:\n\n";
			$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Gartensteuerung.Gartensteuerung-Nachrichten');
			$zeile1 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile01", 3);
			$zeile2 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile02", 3);
			$zeile3 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile03", 3);
			$zeile4 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile04", 3);
			$zeile5 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile05", 3);
			$zeile6 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile06", 3);
			$zeile7 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile07", 3);
			$zeile8 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile08", 3);
			$zeile9 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile09", 3);
			$zeile10 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile10", 3);
			$zeile11 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile11", 3);
			$zeile12 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile12", 3);
			$zeile13 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile13", 3);
			$zeile14 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile14", 3);
			$zeile15 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile15", 3);
			$zeile16 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile16", 3);

			$ergebnisGarten=$ergebnisGarten.GetValue($zeile1)."\n";
			$ergebnisGarten=$ergebnisGarten.GetValue($zeile2)."\n";
			$ergebnisGarten=$ergebnisGarten.GetValue($zeile3)."\n";
			$ergebnisGarten=$ergebnisGarten.GetValue($zeile4)."\n";
			$ergebnisGarten=$ergebnisGarten.GetValue($zeile5)."\n";
			$ergebnisGarten=$ergebnisGarten.GetValue($zeile6)."\n";
			$ergebnisGarten=$ergebnisGarten.GetValue($zeile7)."\n";
			$ergebnisGarten=$ergebnisGarten.GetValue($zeile8)."\n";
			$ergebnisGarten=$ergebnisGarten.GetValue($zeile9)."\n";
			$ergebnisGarten=$ergebnisGarten.GetValue($zeile10)."\n";
			$ergebnisGarten=$ergebnisGarten.GetValue($zeile11)."\n";
			$ergebnisGarten=$ergebnisGarten.GetValue($zeile12)."\n";
			$ergebnisGarten=$ergebnisGarten.GetValue($zeile13)."\n";
			$ergebnisGarten=$ergebnisGarten.GetValue($zeile14)."\n";
			$ergebnisGarten=$ergebnisGarten.GetValue($zeile15)."\n";
			$ergebnisGarten=$ergebnisGarten.GetValue($zeile16)."\n";
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
					  $ergebnistab_energie.$ergebnis_tagesenergie.$ergebnisOperationCenter.$alleMotionWerte.$alleHeizungsWerte.$inst_modules;
			}
		else
		   {
			$ergebnis=$einleitung.$ergebnistab_energie.$energieverbrauch.$ergebnis_tabelle.$ergebnis_tagesenergie.$alleHeizungsWerte.
			$ergebnisRegen.$guthaben.$cost.$internet.$statusverlauf.$ergebnisStrom.
		           $ergebnisStatus.$ergebnisBewegung.$ergebnisSteuerung.$ergebnisGarten.$ergebnisOperationCenter.$IPStatus.$alleMotionWerte.$inst_modules;
			}
		}
  	echo ">>ENDE. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
   return $ergebnis;
}




/********************************************************************************************************************/

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


/******************************************************************/

function CreateVariableByName($id, $name, $type)
{

	/* type steht für 0 Boolean 1 Integer 2 Float 3 String */
	
    global $IPS_SELF;
    $vid = @IPS_GetVariableIDByName($name, $id);
    if($vid === false)
    {
        $vid = IPS_CreateVariable($type);
        IPS_SetParent($vid, $id);
        IPS_SetName($vid, $name);
        IPS_SetInfo($vid, "this variable was created by script #".$_IPS['SELF']." ");
    }
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

/* Original wird im Library Modul Manager verwendet */

function CreateVariable2($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault=null, $Icon='')
{

		$VariableId = @IPS_GetObjectIDByIdent(Get_IdentByName2($Name), $ParentId);
		echo "CreateVariable ".$Name." unter der Parent ID ".$ParentId." mit aktuellem Wert ".$ValueDefault." \n";
		if ($VariableId === false) $VariableId = @IPS_GetVariableIDByName($Name, $ParentId);
		if ($VariableId === false)
		{
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
			IPS_SetVariableCustomProfile($VariableId, $Profile);
		}
		if ($VariableData['VariableCustomAction'] <> $Action)
		{
			//Debug ("Set VariableCustomAction='$Action' for Variable='$Name' ");
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

/******************************************************************/

function mkdirtree($directory)
	{
   $directory = str_replace('\\','/',$directory);
	$directory=substr($directory,0,strrpos($directory,'/')+1);
	//$directory=substr($directory,strpos($directory,'/'));
	while (!is_dir($directory))
		{
		$newdir=$directory;
		while (!is_dir($newdir))
			{
			//echo "es gibt noch kein ".$newdir."\n";
			if (($pos=strrpos($newdir,"/"))==false) {$pos=strrpos($newdir,"\\");};
			if ($pos==false) break;
			$newdir=substr($newdir,0,$pos);
			echo "Mach :".$newdir."\n";
			try
				{
				@mkdir($newdir);
				}
			catch (Exception $e) { echo "."; }
			echo "Verzeichnis ".$newdir." erzeugt.\n";
			}
		if ($pos==false) break;
		}
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
			if (isset ($result["OperationCenter"]))
				{
				$moduleManager_DM = new IPSModuleManager('OperationCenter');     /*   <--- change here */
				$CategoryIdData   = $moduleManager_DM->GetModuleCategoryID('data');
				$Access_categoryId=@IPS_GetObjectIDByName("AccessServer",$CategoryIdData);
				$RemoteServer=array();
	        	//$remServer=RemoteAccess_GetConfiguration();
				//foreach ($remServer as $Name => $UrlAddress)
				IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");				
				$remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
				foreach ($remServer as $Name => $Server)
					{
					$UrlAddress=$Server["ADRESSE"];
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
				}
			else
				{
				$remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
				foreach ($remServer as $Name => $Server)
					{
					$UrlAddress=$Server["ADRESSE"];
					if ( (strtoupper($Server["STATUS"])=="ACTIVE") and (strtoupper($Server["LOGGING"])=="ENABLED") )
						{				
						$RemoteServer[$Name]["Url"]=$UrlAddress;
						$RemoteServer[$Name]["Name"]=$Name;
						$RemoteServer[$Name]["Status"]=true;
						}
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

function HomematicFehlermeldungen()
	{
		$alleHM_Errors="\n\nAktuelle Fehlermeldungen der Homematic Funkkommunikation:\n";
		$texte = Array(
		    "CONFIG_PENDING" => "Konfigurationsdaten stehen zur Übertragung an",
		    "LOWBAT" => "Batterieladezustand gering",
		    "STICKY_UNREACH" => "Gerätekommunikation war gestört",
		    "UNREACH" => "Gerätekommunikation aktuell gestört"
			);

		$ids = IPS_GetInstanceListByModuleID("{A151ECE9-D733-4FB9-AA15-7F7DD10C58AF}");
		$HomInstanz=sizeof($ids);
		if($HomInstanz == 0)
		   {
		   //die("Keine HomeMatic Socket Instanz gefunden!");
		   $alleHM_Errors.="ERROR: Keine HomeMatic Socket Instanz gefunden!\n";
		   }
		else
		   {
			/* Homematic Instanzen vorhanden, sind sie aber auch aktiv ? */
			$aktiv=false;
			foreach ($ids as $id)
	   		{
				$ergebnis=IPS_GetConfiguration($id);
				echo "Homematic Socket : ".IPS_GetName($id)."\n";
				echo "  Konfig : ".$ergebnis."\n";
      		$remove = array("{", "}", '"');
				$ergebnis = str_replace($remove, "", $ergebnis);
				$result = explode(",",$ergebnis);
				$AllConfig=array();
				foreach ($result as $configItem)
				   {
				   $items=explode (':',$configItem);
				   $Allconfig[$items[0]]=$items[1];
				   }
				//print_r($Allconfig);
				if ( $Allconfig["Open"]="false" )
				   {
					echo "Homematic Port nicht aktiviert.\n";
					}
				else
				   {
				   $aktiv=true;
				   }
				}
			//echo "\n\nHomatic Socket Count :".$HomInstanz."\n";
			if ($aktiv==true)
	   		{
				for ($i=0;$i < $HomInstanz; $i++)
				   {
			      $alleHM_Errors.="\nHomatic Socket ID ".$ids[$i]." / ".IPS_GetName($ids[$i])."\n";
					$msgs = HM_ReadServiceMessages($ids[$i]);
					if($msgs === false)
					   {
						//die("Verbindung zur CCU fehlgeschlagen");
					   $alleHM_Errors.="ERROR: Verbindung zur CCU fehlgeschlagen!\n";
					   }

					if(sizeof($msgs) == 0)
					   {
						//echo "Keine Servicemeldungen!\n";
				   	$alleHM_Errors.="OK, keine Servicemeldungen!\n";
						}

					foreach($msgs as $msg)
						{
			   		if(array_key_exists($msg['Message'], $texte))
							{
      				  	$text = $texte[$msg['Message']];
		   				}
						else
							{
      	  				$text = $msg['Message'];
		        			}
					   $id = GetInstanceIDFromHMID($msg['Address']);
				    	if(IPS_InstanceExists($id))
						 	{
        					$name = IPS_GetLocation($id);
					   	}
						else
							{
		      	  		$name = "Gerät nicht in IP-Symcon eingerichtet";
    						}
			  			//echo "Name : ".$name."  ".$msg['Address']."   ".$text." \n";
					  	$alleHM_Errors.="Name : ".$name."  ".$msg['Address']."   ".$text." \n";
						}
					}
				}
			}
		return($alleHM_Errors);
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
	
	$alleWerte="";

	$Homematic = HomematicList();
	$FS20= FS20List();
	$FHT = FHTList();

	$pad=50;
	$alleWerte.="\n\nAktuelle Heizungs-Aktuatorenwerte direkt aus den HW-Registern:\n\n";
	$varname="VALVE_STATE";
	foreach ($Homematic as $Key)
		{
		/* Alle Homematic Stellwerte ausgeben */
		if ( (isset($Key["COID"][$varname])==true) )
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
		if (isset($Key["COID"]["PositionVar"])==true)
		   {
	      	$oid=(integer)$Key["COID"]["PositionVar"]["OID"];
			$alleWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
			}
		}

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

/************************************************************************************/

function write_wfc($input,$indent)
	{
	if (sizeof($input) > 0)
		{
		foreach ($input as $index => $entry)
			{
			if ( $index != "." )
				{
				echo $indent.$entry["."]."\n";
				write_wfc($entry,$indent."   ");
				}
			}
		}	
	}

/************************************************************************************/

function search_wfc($input,$search,$tree)
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
					$result=search_wfc($entry,$search,$tree.".".$index);
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

function read_wfc()
	{
	//echo "\n";
	$WebfrontConfigID=array();
	$alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
	foreach ($alleInstanzen as $instanz)
		{
		$result=IPS_GetInstance($instanz);
		$WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
		//echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."\n";
		If (true)	/* Debug der aktuellen detaillierten Einträge */
			{
			//echo "    ".IPS_GetConfiguration($instanz)."\n";
			$config=json_decode(IPS_GetConfiguration($instanz));
			$config->Items = json_decode(json_decode(IPS_GetConfiguration($instanz))->Items);
			//print_r($config);
		
			$ItemList = WFC_GetItems($instanz);
			$wfc_tree=array();
			foreach ($ItemList as $entry)
				{
				if ($entry["ParentID"] != "")
					{
					//echo "WFC Eintrag:    ".$entry["ParentID"]." (Parent)  ".$entry["ID"]." (Eintrag)\n";
					$result = search_wfc($wfc_tree,$entry["ParentID"],"");
					//echo "search_wfc: ".$entry["ParentID"]." mit Ergebnis \"".$result."\"  ".substr($result,1,strlen($result)-2)."\n";
					if ($result == "")
						{
						$wfc_tree[$entry["ParentID"]][$entry["ID"]]=array();
						$wfc_tree[$entry["ParentID"]]["."]=$entry["ParentID"];
						$wfc_tree[$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
						//echo "-> ".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";
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
								//echo "-> ".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";						
								}
							else	
								{
								//echo "Tiefe : ".sizeof($tree)." \n";
								switch (sizeof($tree))
									{
									case 1:
										$wfc_tree[$tree[0]][$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
										//echo "-> ".$tree[0].".".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";
										break;
									case 2:
										$wfc_tree[$tree[0]][$tree[1]][$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
										//echo "-> ".$tree[0].".".$tree[1].".".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";
										break;
									case 3:
										$wfc_tree[$tree[0]][$tree[1]][$tree[2]][$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
										//echo "-> ".$tree[0].".".$tree[1].".".$tree[2].".".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";
										break;
									case 4:
										$wfc_tree[$tree[0]][$tree[1]][$tree[2]][$tree[3]][$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
										//echo "-> ".$tree[0].".".$tree[1].".".$tree[2].".".$tree[3].".".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";
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
				else
					{
					//echo "WFC Eintrag:    ".$entry["ID"]." (Eintrag)\n";
					}
				}
			echo "\n================ WFC Tree ".IPS_GetName($instanz)."=====\n";	
			//print_r($wfc_tree);
			write_wfc($wfc_tree,"");	
			//echo "  ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'Protocol')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
			/* alle Instanzen dargestellt */
			//echo "**     ".IPS_GetName($instanz)." ".$instanz." ".$result['ModuleInfo']['ModuleName']." ".$result['ModuleInfo']['ModuleID']."\n";
			//print_r($result);
			}
		}
	}
	
/***********************************************************************************
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
				if ($variabletyp["VariableProfile"]!="")
					{
					echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
					}
				else
					{
					echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
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
 * verwendet von CustomComponents, RemoteAccess und EvaluateHeatControl zum schnellen Anlegen der Variablen
 * ist auch in der Remote Access Class angelegt und kann direkt aus der Klasse aufgerufen werden.
 *
 * Elements		Objekte aus EvaluateHardware, alle Homematic, alle FS20 etc.
 * keyword		Name des Children Objektes das enthalten sein muss, wenn array auch mehrer Keywords, erstes Keyword ist das indexierte
 * InitComponent	erster Parameter bei der Registrierung
 * InitModule		zweiter Parameter bei der Registrierung
 * 
 * Ergebnis: ein zusaetzliches Event wurde beim Messagehandler registriert
 *
 ****************************************************************************************/
		
	function installComponentFull($Elements,$keywords,$InitComponent, $InitModule)
		{
		$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
		$installedModules=$moduleManager->GetInstalledModules();
		$remServer=array();
		if (isset ($installedModules["RemoteAccess"]))
			{
			echo "  Remote Access installiert, Variablen auch am VIS Server aufmachen.\n";
			IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
			$remServer=ROID_List();
			$status=RemoteAccessServerTable();
			foreach ($remServer as $Name => $Server)
				{
				echo "    Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
				//print_r($Server);
				}							
			}
		$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			
		foreach ($Elements as $Key)
			{
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
			else
				{
				if (isset($Key["COID"][$keywords])==true) $found=true; 
				$keyword=$keywords; 
				}	
			
			if ( (isset($Key["Device"])==true) && ($found==false) )
				{
				/* Vielleicht ist ein Device Type als Keyword angegeben worden.\n" */
				if ($Key["Device"] == $keyword)
					{
					$found=true;
					switch ($keyword)
						{
						case "TYPE_ACTUATOR":
							if (isset($Key["COID"]["LEVEL"]["OID"]) == true) 
								{
								$keyword="LEVEL";
								}
							elseif (isset($Key["COID"]["VALVE_STATE"]["OID"]) == true) $keyword="VALVE_STATE";
							break;
						default:	
							if (isset($Key["COID"]["SET_TEMPERATURE"]["OID"]) == true) $keyword="SET_TEMPERATURE";
							if (isset($Key["COID"]["SET_POINT_TEMPERATURE"]["OID"]) == true) $keyword="SET_POINT_TEMPERATURE";
							if (isset($Key["COID"]["TargetTempVar"]["OID"]) == true) $keyword="TargetTempVar";
							break;
						}	
					}
				}
						
			switch (strtoupper($keyword))
				{
				case "TARGETTEMPVAR":			/* Thermostat Temperatur Setzen */
				case "SET_TEMPERATURE":
					$variabletyp=2; 		/* Float */
					$index="HeatSet";
					$profile="TemperaturSet";
					break;				
				case "TEMERATUREVAR";			/* Temperatur auslesen */
				case "TEMPERATURE":
					$variabletyp=2; 		/* Float */
					$index="Temperatur";
					$profile="Temperatur";
					break;
				case "POSITIONVAR":
					$variabletyp=2; 		/* Float */
					$index="HeatControl";
					$profile="~Valve.F";
					break;
				case "HUMIDITY":
					$variabletyp=1; 		/* Integer */							
					$index="Humidity";
					$profile="Humidity";
					break;
				case "VALVE_STATE":
				case "LEVEL":
					$variabletyp=1; 		/* Integer */	
					$index="HeatControl";
					$profile="~Intensity.100";
					break;
				default:	
					$variabletyp=0; 		/* Boolean */	
					break;
				}			
			
			if ($found)
				{		
				//echo "********** ".$Key["Name"]."\n";
				print_r($Key);
				$oid=(integer)$Key["COID"][$keyword]["OID"];
				$vartyp=IPS_GetVariable($oid);
				if ($vartyp["VariableProfile"]!="")
					{
					echo "  ".str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
					}
				else
					{
					echo "  ".str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
					}

				/* check, es sollten auch alle Quellvariablen gelogged werden */
				if (AC_GetLoggingStatus($archiveHandlerID,$oid)==false)
					{
					/* Wenn variable noch nicht gelogged automatisch logging einschalten */
					AC_SetLoggingStatus($archiveHandlerID,$oid,true);
					AC_SetAggregationType($archiveHandlerID,$oid,0);
					IPS_ApplyChanges($archiveHandlerID);
					echo "Variable ".$oid." Archiv logging für Register aktiviert.\n";
					}					
				if (isset ($installedModules["RemoteAccess"]))
					{					
					$parameter="";
					foreach ($remServer as $Name => $Server)
						{
						//echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
						if ( $status[$Name]["Status"] == true )
							{
							$rpc = new JSONRPC($Server["Adresse"]);
							/* variabletyp steht für 0 Boolean 1 Integer 2 Float 3 String */
							$result=RPC_CreateVariableByName($rpc, (integer)$Server[$index], $Key["Name"], $variabletyp);
							$rpc->IPS_SetVariableCustomProfile($result,$profile);
							$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
							$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
							$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
							$parameter.=$Name.":".$result.";";
							}						}	
					$messageHandler = new IPSMessageHandler();
					$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
					$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

					/* wenn keine Parameter nach IPSComponentSensor_Temperatur angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
					$messageHandler->RegisterEvent($oid,"OnChange",$InitComponent.','.$Key["OID"].','.$parameter,$InitModule);
					echo "    Event ".$oid." registriert mit \"OnChange\",\"".$InitComponent.",".$Key["OID"].",".$parameter."\",\"".$InitModule."\"\n";
					}
				else
					{
					/* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
					echo "Remote Access nicht installiert, Variablen selbst registrieren.\n";
					$messageHandler = new IPSMessageHandler();
					$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
					$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

					/* wenn keine Parameter nach IPSComponentSensor_Temperatur angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
					$messageHandler->RegisterEvent($oid,"OnChange",$InitComponent.",".$Key["OID"].",",$InitModule);
					echo "    Event ".$oid."registriert mit \"OnChange\",\"".$InitComponent.",".$Key["OID"].",\",\"".$InitModule."\"\n";
					}			
				}
			} /* Ende foreach */		
		}	
		
		
		
		
/******************************************************************

Moudule und Klassendefinitionen

******************************************************************/






/******************************************************************/

?>
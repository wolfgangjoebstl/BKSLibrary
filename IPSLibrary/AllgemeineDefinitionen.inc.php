<?

 //F�gen Sie hier ihren Skriptquellcode ein

   IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
   IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
   
/******************************************************************************************/   
/*                                                                                        */   
/*                                        BKS01                                           */
/*                                                                                        */
/******************************************************************************************/

   
/* ObjectID Adresse vom send email server */

$sendResponse = 30887; //ID einer SMTP Instanz angeben, um R�ckmelde-Funktion zu aktivieren
$playwaves = 31500;
$genspeak = 13283;

/* Unterschiede getaktete und nicht getaktete Verbindung
	bei Win8 noch nicht klar. DNS geht scheinbar lokal nicht, drum fixe IP Adresse angeben

*/

/* FS20 Adress-Schema */

 /* BKS (Burg Kreuzenstein Hausautomatisierung


 Stromheizung mit FHZ1300 (USB)

 nach Batterie einlegen Uhrzeit und Datum einstellen

 Hyst:  0,6 �C
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





//echo IPS_GetName(0);
if (IPS_GetName(0)=="LBG70")
	{

/******************************************************************************************/
/*                                                                                        */
/*                                        LBG70                                           */
/*                                                                                        */
/******************************************************************************************/

function LogAlles_Bewegung() {
		return array(
			"VZB"     => array(	"OID_Sensor"         => 30700,           /* OID Position vom Sensor im WZ */
			                     "OID_Status"         => 57705, /*???*/
  			              	  		"Type"               => "Motion",
			              ),
			"AZB"     => array(	"OID_Sensor"         => 54389,           /* OID Position vom Sensor im WZ */
			                     "OID_Status"         => 57705, /*???*/
  			              	  		"Type"               => "Motion",
			              ),
			"WZB"     => array(	"OID_Sensor"         => 11681,           /* OID Position vom Sensor im WZ */
			                     "OID_Status"         => 57705, /*???*/
  			              	  		"Type"               => "Motion",
			              ),
			"ZZB"     => array(	"OID_Sensor"         => 21581,           /* OID Position vom Sensor im WZ */
			                     "OID_Status"         => 57705, /*???*/
  			              	  		"Type"               => "Motion",
			              ),
			"BZT"    => array(	"OID_Sensor"         => 25921,           /* OID Position vom Sensor im WG */
			                     "OID_Status"         => 18901,  /*???*/
  			              	  		"Type"               => "State",
			              ),
			"EGT"    => array(	"OID_Sensor"         => 41275,           /* OID Position vom Sensor im Keller */
			                     "OID_Status"         => 22562,  /*???*/
  			              	  		"Type"               => "State",
			              ),
			"TOTAL" 	=> array(	"OID_Bewegung"    	=> 23826,   /* einfach Bewegungswerte (Motion) oder verknuepft */
										"OID_Alarm"    		=> 10840,   /* einfach Alarmwerte (State) oder verknuepft */
										"OID_Status"    		=> 33623,   /* Indikation ob wir zu Hause sind */
			              ),
						);
	}

function LogAnwesenheit_Configuration()
	{
	return array(
			"VZB"    =>    30700,                                       /* Bewegungsmelder im VZ */
			"AZB"    =>    54389,                                       /* Bewegungsmelder im AZ */
			"WZB"    =>    11681,                                       /* Bewegungsmelder in der Kueche */
			"ZZB"    =>    21581,                                       /* Bewegungsmelder im zentralen VZ */
			"BZT"    =>    25921,                                       /* Kontakt Badezimmertuere */
			"EGT"    =>    41275,                                       /* Kontakt Eingangstuere */
		         );
	}

	function LogAlles_Configuration() {
		return array(
			"AZ"    => array("Leistung"           	 => 190, 				/* zwei Radiatoren 110 Arbeitszimmer und 80 Gaestezimmer */
							  "OID_PosHT80b"         => 54440,           /* OID Position vom Regler */
  			              	  "OID_Zeit"           	 => 30741,
  			              	  "OID_Energie"          => 34120,           /* Energieverbrauch Verlauf, ideal f�r Kurvendarstellung - Faktor egal */
  			              	  "OID_EnergieVortag"    => 38894,           /* ein Wert pro Tag wird immer um 00:00 geschrieben */
  			              	  "OID_EnergieTag"       => 43184,           /* summierter Tagesverbrauch, wird immer um 00:00 zurueckgesetzt, nur mehr zur Kompatibilit�t */
			              ),
			"BZ"    => array("Leistung"           	 => 50, 					/* ein Radiator 50 */
							  "OID_PosHT80b"         => 14642,           /* OID Position vom Regler */
  			              	  "OID_Zeit"           	 => 46077,
  			              	  "OID_Energie"          => 30563,           /* Energieverbrauch Verlauf, ideal f�r Kurvendarstellung - Faktor egal */
  			              	  "OID_EnergieVortag"    => 38725,           /* ein Wert pro Tag wird immer um 00:00 geschrieben */
  			              	  "OID_EnergieTag"       => 41149,           /* summierter Tagesverbrauch, wird immer um 00:00 zurueckgesetzt, nur mehr zur Kompatibilit�t */
			              ),
			"SZ"    => array("Leistung"           	 => 140,					/* ein Radiator 70 doppelt aufgebaut */
							  "OID_PosHT80b"         => 34186,           /* OID Position vom Regler */
  			              	  "OID_Zeit"           	 => 56091,
  			              	  "OID_Energie"          => 29754,           /* Energieverbrauch Verlauf, ideal f�r Kurvendarstellung - Faktor egal */
  			              	  "OID_EnergieVortag"    => 10710,           /* ein Wert pro Tag wird immer um 00:00 geschrieben */
  			              	  "OID_EnergieTag"       => 22670,           /* summierter Tagesverbrauch, wird immer um 00:00 zurueckgesetzt, nur mehr zur Kompatibilit�t */
			              ),
			"WZ"    => array("Leistung"           	 => 170,   				/* zwei Radiatoren Kueche 70 und Essplatz 100 */
							  "OID_PosHT80b"         => 27073,           /* OID Position vom Regler */
  			              	  "OID_Zeit"           	 => 52403,
  			              	  "OID_Energie"          => 46217,           /* Energieverbrauch Verlauf, ideal f�r Kurvendarstellung - Faktor egal */
  			              	  "OID_EnergieVortag"    => 40178,           /* ein Wert pro Tag wird immer um 00:00 geschrieben */
  			              	  "OID_EnergieTag"       => 47674,           /* summierter Tagesverbrauch, wird immer um 00:00 zurueckgesetzt, nur mehr zur Kompatibilit�t */
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
			"TOTAL"   => array(	"OID_TempWert_Aussen"    	=> 11477,   /* einfach Temperaturwerte von vorher zusammengez�hlt und richtig dividiert */
			                    "OID_TempWert_Innen"    	=> 21157,
			                    "OID_TempTagesWert_Aussen" => 34862,   /* Tageswerte sind immer der letzte Tag */
			                    "OID_TempTagesWert_Innen"  => 29829,
			              ),
						);
	}

 function LogAlles_Hostnames() {
		return array(
			"UPC"      => array(	"IP_Adresse"         => "10.0.0.1",           			/* IP Adresse, kann auch sp�ter vergeben werden */
									"Mac_Adresse"    	 => "80-c6-ab-73-fe-1c",            /* MAC Adresse muss vergeben werden */
									"Hostname"           => "UPC-Gateway",		 			/* Hostname ist auch zu vergeben */
			              ),
			"IP009"    => array(	"IP_Adresse"         => "10.0.0.9",           			/* IP Adresse, kann auch sp�ter vergeben werden */
									"Mac_Adresse"    	 => "d8-30-62-32-0b-93",            /* MAC Adresse muss vergeben werden */
									"Hostname"           => "Unknown",		 				/* Hostname ist auch zu vergeben */
			              ),
			"LBG70"      => array(	"IP_Adresse"         => "10.0.0.20",           			/* IP Adresse, kann auch sp�ter vergeben werden */
									"Mac_Adresse"    	 => "80-ee-73-32-89-9f",            /* MAC Adresse muss vergeben werden */
									"Hostname"           => "LBG70Server",					/* Hostname ist auch zu vergeben */
			              ),
			"AVR17"    => array(	"IP_Adresse"         => "10.0.0.23",           			/* IP Adresse, kann auch sp�ter vergeben werden */
									"Mac_Adresse"    	 => "00-05-cd-2d-c8-0a",            /* MAC Adresse muss vergeben werden */
									"Hostname"           => "DENON AVR-1713",		 		/* Hostname ist auch zu vergeben */
			              ),
			"IP024"    => array(	"IP_Adresse"         => "10.0.0.24",           			/* IP Adresse, kann auch sp�ter vergeben werden */
									"Mac_Adresse"    	 => "00-1c-c0-02-2f-05",            /* MAC Adresse muss vergeben werden */
									"Hostname"           => "Unknown",		 				/* Hostname ist auch zu vergeben */
			              ),
			"GLA"      => array(	"IP_Adresse"         => "10.0.0.26",           			/* IP Adresse, kann auch sp�ter vergeben werden */
									"Mac_Adresse"    	 => "90-e6-ba-19-43-26",            /* MAC Adresse muss vergeben werden */
									"Hostname"           => "GanzLinks",					/* Hostname ist auch zu vergeben */
			              ),
			"IP027"    => array(	"IP_Adresse"         => "10.0.0.27",           			/* IP Adresse, kann auch sp�ter vergeben werden */
									"Mac_Adresse"    	 => "78-ca-39-42-87-c3",            /* MAC Adresse muss vergeben werden */
									"Hostname"           => "Unknown",		 				/* Hostname ist auch zu vergeben */
			              ),
			"IP028"    => array(	"IP_Adresse"         => "10.0.0.28",           			/* IP Adresse, kann auch sp�ter vergeben werden */
									"Mac_Adresse"    	 => "00-e0-4c-bc-89-bd",            /* MAC Adresse muss vergeben werden */
									"Hostname"           => "WZ-IPCam",		 				/* Hostname ist auch zu vergeben */
			              ),
			"IP030"    => array(	"IP_Adresse"         => "10.0.0.30",           			/* IP Adresse, kann auch sp�ter vergeben werden */
									"Mac_Adresse"    	 => "00-08-c9-01-65-63",            /* MAC Adresse muss vergeben werden */
									"Hostname"           => "Unknown",		 				/* Hostname ist auch zu vergeben */
			              ),
			"IP032"    => array(	"IP_Adresse"         => "10.0.0.32",           			/* IP Adresse, kann auch sp�ter vergeben werden */
									"Mac_Adresse"    	 => "90-84-0d-cf-c8-89",            /* MAC Adresse muss vergeben werden */
									"Hostname"           => "Unknown",		 				/* Hostname ist auch zu vergeben */
			              ),
			"IP034"    => array(	"IP_Adresse"         => "10.0.0.34",           			/* IP Adresse, kann auch sp�ter vergeben werden */
									"Mac_Adresse"    	 => "4c-ed-de-a2-d9-42",            /* MAC Adresse muss vergeben werden */
									"Hostname"           => "Unknown",		 				/* Hostname ist auch zu vergeben */
			              ),
			"IP038"    => array(	"IP_Adresse"         => "10.0.0.34",           			/* IP Adresse, kann auch sp�ter vergeben werden */
									"Mac_Adresse"    	 => "00-1d-ba-8f-11-37",            /* MAC Adresse muss vergeben werden */
									"Hostname"           => "Unknown",		 				/* Hostname ist auch zu vergeben */
			              ),
			"IP082"    => array(	"IP_Adresse"         => "10.0.0.82",           			/* IP Adresse, kann auch sp�ter vergeben werden */
									"Mac_Adresse"    	 => "00-1a-22-00-3a-b1",            /* MAC Adresse muss vergeben werden */
									"Hostname"           => "Unknown",		 				/* Hostname ist auch zu vergeben */
			              ),
			"IP112"    => array(	"IP_Adresse"         => "10.0.0.112",           		/* IP Adresse, kann auch sp�ter vergeben werden */
									"Mac_Adresse"    	 => "e4-e0-c5-25-66-27",            /* MAC Adresse muss vergeben werden */
									"Hostname"           => "Unknown",		 				/* Hostname ist auch zu vergeben */
			              ),
			"AVR33"    => array(	"IP_Adresse"         => "10.0.0.115",           		/* IP Adresse, kann auch sp�ter vergeben werden */
									"Mac_Adresse"    	 => "00-05-cd-25-91-76",            /* MAC Adresse muss vergeben werden */
									"Hostname"           => "Denon AVR-3312",		 		/* Hostname ist auch zu vergeben */
			              ),
					);
	}



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

/* Wohnungszustand */

define("STAT_WohnungszustandInaktiv",0);
define("STAT_WohnungszustandFerien",1);
define("STAT_WohnungszustandUnterwegs",2);
define("STAT_WohnungszustandStandby",3);
define("STAT_WohnungszustandAktiv",4);
define("STAT_WohnungszustandTop",5);

/* erkannter Zustand */
define("STAT_KommtnachHause",8);
define("STAT_Bewegung",7);
define("STAT_WenigBewegung",6);
define("STAT_KeineBewegung",5);
define("STAT_Unklar",4);
define("STAT_Undefiniert",3);
define("STAT_vonzuHauseweg",2);
define("STAT_nichtzuHause",1);
define("STAT_Abwesend",0);

$id_sound = 23225;
$sendResponse = 43606; //ID einer SMTP Instanz angeben, um R�ckmelde-Funktion zu aktivieren



	/* verzeichnisse */
	define("DIR_copyscriptsdropbox","C:/Users/Wolfgang/Dropbox/Privat/IP-Symcon/scripts-LBG/");
	define("ADR_Router","10.0.0.1");

	}
else
	{
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
			"AZ-T"    => array(	"OID_Sensor"         => 38610,           /* OID Position vom Sensor im AZ */
  			              	  		//"OID_TempWert"    	=> 19253,           /* OID vom Spiegelregister, weil Wert um Mitternach nicht als VALUE_OLD abgehohlt werden kann */
  			              	  		"OID_Max"            => 53022,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			              	  		"OID_Min"            => 32252,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			              	  		"Type"               => "Innen",
			              ),
			"KZ-T"    => array(	"OID_Sensor"         => 13063,           /* OID Position vom Sensor im BZ */
										//"OID_TempWert"    	=> 36041,           /* OID vom Spiegelregister */
  			              	  		"OID_Max"            => 59160,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			              	  		"OID_Min"            => 52129,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			              	  		"Type"               => "Andere",
			              ),
			"WZ-T"    => array(	"OID_Sensor"         => 41873,           /* OID Position vom Sensor im SZ */
  			              	  		//"OID_TempWert"    	=> 42862,           /* OID vom Spiegelregister */
  			              	  		"OID_Max"            => 34073,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			              	  		"OID_Min"            => 24331,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			              	  		"Type"               => "Innen",
			              ),
			//"AUSSEN-T" => array(	"OID_Sensor"         => 42413,           /* OID Position vom Sensor AUSSEN*/
  			//              	  		"OID_Max"            => 38935,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			//              	  		"OID_Min"            => 17481,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			//              	  		"Type"               => "Aussen",
			//              ),
			"AUSSE2-T" => array( "OID_Sensor"         => 32563,           /* OID Position vom Sensor AUSSE2 beim Wintergarten Kellerfenster */
  			              	  		"OID_Max"            => 22884,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			              	  		"OID_Min"            => 15265,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			              	  		"Type"               => "Aussen",
			              ),
			"WETTER-T" => array( "OID_Sensor"         => 31094,           /* OID Position vom Sensor der Wetterstation*/
  			              	  		"OID_Max"            => 54386,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			              	  		"OID_Min"            => 30234,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			              	  		"Type"               => "Aussen",
			              ),
			"KELLER-T" => array(	"OID_Sensor"         => 48182,           /* OID Position vom Sensor Keller*/
  			              	  		"OID_Max"            => 28619,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			              	  		"OID_Min"            => 19040,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			              	  		"Type"               => "Andere",
			              ),
			"WINGAR-T" => array(	"OID_Sensor"         => 29970,           /* OID Position vom Sensor Wintergarten*/
  			              	  		"OID_Max"            => 21658,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			              	  		"OID_Min"            => 55650,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			              	  		"Type"               => "Andere",
			              ),
			"KELLAG-T" => array(	"OID_Sensor"         => 58776,           /* OID Position vom Sensor Wintergarten*/
  			              	  		"OID_Max"            => 48777,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			              	  		"OID_Min"            => 17535,      	  /* Wert wird nur einmal um Mitternacht geschrieben */
  			              	  		"Type"               => "Andere",
			              ),
			"TOTAL" 	=> array(	"OID_TempWert_Aussen"    	=> 21416,   /* einfach Temperaturwerte von vorher zusammengez�hlt und richtig dividiert */
			                     "OID_TempWert_Innen"    	=> 56688,
			                     "OID_TempTagesWert_Aussen" => 13320,   /* Tageswerte sind immer der letzte Tag */
			                     "OID_TempTagesWert_Innen"  => 35271,
			              ),
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
	define("ADR_Router","11.0.1.1");
	}

//$Router_Adresse = "http://admin:cloudg06##@www.routerlogin.com/";
$Router_Adresse = "http://admin:cloudg06##@".ADR_Router."/";
$iTunes_Verzeichnis="c:/Program Files (x86)/iTunes/iTunes.exe";

/****************************************************************************************************/


/**********************************************************************************************************************************************************/
/* immer wenn eine Statusmeldung per email angefragt wird */


/* wird sp�ter unter Allgemein gespeichert */

function send_status($aktuell)
	{
	$sommerzeit=false;
	$einleitung="Erstellt am ".date("D d.m.Y H:i")." fuer die ";

	/* alte Programaufrufe sind ohne Parameter, daher f�r den letzten Tag */

	if ($aktuell)
	   {
	   $einleitung.="Ausgabe der aktuellen Werte.\n";
	   //$einleitung.="Aktuelle Heizleistung: ".GetValue(34354)." W\n\n";
	   }
	else
	   {
	   $einleitung.="Ausgabe der historischen Werte - Vortag.\n";
	   }
	if (date("I")=="1")
	{
	$einleitung.="Wir haben jetzt Sommerzeit, daher andere Reihenfolge der Ausgabe.\n";
	$sommerzeit=true;
	}
	$einleitung.="\n";


if (IPS_GetName(0)=="LBG70")
	{


	//   IPS_RunScript(13352);
	//   IPS_RunScript(32860);
	//   IPS_RunScript(45023);
	//   IPS_RunScript(41653);

	if ($aktuell)
	    {
		$energieverbrauch="";
		}
	else
		{
		IPS_RunScript(35787);

		$energieverbrauch="Energieverbrauch der letzten Tage (bei ".GetValue(52478)." Euro pro kWh) :\n";
		$energieverbrauch.="\nEnergieverbrauch (1/7/30/360) : ".number_format(GetValue(44839), 2, ",", "" )." / ";
		$energieverbrauch.=number_format(GetValue(33301), 2, ",", "" )." / ";
		$energieverbrauch.=number_format(GetValue(29148), 2, ",", "" )." / ";
		$energieverbrauch.=number_format(GetValue(16969), 2, ",", "" )." kWh";
		$energieverbrauch.="\nEnergiekosten    (1/7/30/360) : ".number_format(GetValue(18976), 2, ",", "" )." / ";
		$energieverbrauch.=number_format(GetValue(34239), 2, ",", "" )." / ";
		$energieverbrauch.=number_format(GetValue(20687), 2, ",", "" )." / ";
		$energieverbrauch.=number_format(GetValue(45647), 2, ",", "" )." Euro\n\n";
		}

	$guthaben="Guthabenstatus:\n".
			  "\n".GetValue(24085).
			  "\n".GetValue(27029).
			  "\n".GetValue(59623).
			  "\n".GetValue(39724).
  			  "\n".GetValue(54406).
			  "\n".GetValue(50426)."\n\n";

	$cost="Internetkosten:\n".
			"\nAufgeladen wurde bisher : ".GetValue(32942)." Euro".
			"\nVerbraucht wurde bisher : ".GetValue(37190)." Euro".
			"\n�nderung heute          : ".GetValue(47396)." Euro\n\n";

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


//	$ergebnis_tagesenergie="Energiewerte Vortag: \n\n";
//	$arr=LogAlles_Configuration();    /* Konfigurationsfile mit allen Variablen  */

//  foreach ($arr as $identifier=>$station)
		{
//		$EnergieTagFinalID=$station["OID_Tageswert"];
//	   $ergebnis_tagesenergie=$ergebnis_tagesenergie.$identifier.":".number_format(GetValue($EnergieTagFinalID), 2, ",", "" )."kWh ";
		}
//	$ergebnis_tagesenergie.="\n\n";

//	$ergebnis_tagesenergie.="Aktuelle Energiewerte : \n\n";
//   $arr=LogAlles_Configuration();    /* Konfigurationsfile mit allen Variablen  */

//	$energieGesTagID = CreateVariableByName(53458,"Summe_EnergieTag", 2);
//   foreach ($arr as $identifier=>$station)
		{
//		if ($identifier=="TOTAL")
			{
//   		$ergebnis_tagesenergie=$ergebnis_tagesenergie.$identifier.":".number_format(GetValue($energieGesTagID), 2, ",", "" )."kWh \n";
//			break;
			}
//		$energieTagID = CreateVariableByName(53458, $identifier."_EnergieTag", 2);
//   	$ergebnis_tagesenergie=$ergebnis_tagesenergie.$identifier.":".number_format(GetValue($energieTagID), 2, ",", "" )."kWh ";   /* Schoenes Ergebnis fuer email bauen */
		}
//	unset($identifier); // break the reference with the last element
//	$ergebnis_tagesenergie.=   "1/7/30/360 : ".number_format(GetValue(35510), 0, ",", "" )."/"
//							  				    .number_format(GetValue(25496), 0, ",", "" )."/"
//											    .number_format(GetValue(54896), 0, ",", "" )."/"
//											    .number_format(GetValue(30229), 0, ",", "" )." kWh\n";

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
		$zeile = array("Datum" => array("Datum",0,1,2), "Heizung" => array("Heizung",0,1,2), "Datum2" => array("Datum",0,1,2), "Energie" => array("Energie",0,1,2));
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

//	$werte = AC_GetLoggedValues(IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0], 13448, $starttime, $endtime, 0);
	//print_r($werte);
//	echo "Werte Stromverbrauch:\n";
//	$vorigertag=date("d.m.Y",$jetzt);
//	foreach($werte as $wert)
			{
//			$zeit=$wert['TimeStamp']-60;
//			if (date("d.m.Y", $zeit)!=$vorigertag)
			   {
//				echo date("d.m.Y", $zeit) . " -> " . number_format($wert['Value'], 2, ",", "" ) ." kWh". PHP_EOL;
				}
//			$vorigertag=date("d.m.Y",$zeit);
			}
//	$ergebnisTemperatur="\nAktuelle Temperaturwerte :\n\n";
//   $arr=LogAlles_Temperatur();    /* Konfigurationsfile mit allen Variablen  */

//	foreach ($arr as $identifier=>$station)
		{
//		if ($identifier=="TOTAL")
			{
//			break;
			}
		//echo $identifier;
//		$TempWertID = $station["OID_Sensor"];
//		$ergebnisTemperatur = $ergebnisTemperatur.$identifier." : ".number_format(GetValue($TempWertID), 2, ",", "" )."�C ";
		}
//	unset($identifier); // break the reference with the last element

//	$ergebnisRegen="\n\nRegenmenge : ".number_format(GetValue(15200), 2, ",", "" )." mm\n";
//	$ergebnisRegen.=   "1/7/30/360 : ".number_format(GetValue(37587), 2, ",", "" )."/"
//							  				    .number_format(GetValue(10370), 2, ",", "" )."/"
//											    .number_format(GetValue(13883), 2, ",", "" )."/"
//											    .number_format(GetValue(10990), 2, ",", "" )." mm\n";

//	$ergebnisStrom="\n\nTages-Stromverbrauch : ".GetValue(13448)." kWh\n";
//	$ergebnisStrom.=   "1/7/30/360 : ".number_format(GetValue(52252), 0, ",", "" )."/"
//							  				    .number_format(GetValue(35513), 0, ",", "" )."/"
//											    .number_format(GetValue(35289), 0, ",", "" )."/"
//											    .number_format(GetValue(51307), 0, ",", "" )." kWh\n";
//	$ergebnisStrom.=   "1/7/30/360 : ".number_format(GetValue(29903), 0, ",", "" )."/"
//							  				    .number_format(GetValue(44005), 0, ",", "" )."/"
//											    .number_format(GetValue(20129), 0, ",", "" )."/"
//											    .number_format(GetValue(47761), 0, ",", "" )." Euro\n";

//	$ergebnisStatus="\nAenderungsverlauf Internet Connectivity :\n\n";
//	$ergebnisStatus=$ergebnisStatus."Downtime Internet :".GetValue(49809)." min\n\n";
//	$ergebnisStatus=$ergebnisStatus.GetValue(51715)."\n";
//	$ergebnisStatus=$ergebnisStatus.GetValue(55372)."\n";
//	$ergebnisStatus=$ergebnisStatus.GetValue(52397)."\n";
//	$ergebnisStatus=$ergebnisStatus.GetValue(51343)."\n";
//	$ergebnisStatus=$ergebnisStatus.GetValue(29913)."\n";
//	$ergebnisStatus=$ergebnisStatus.GetValue(27604)."\n";
//	$ergebnisStatus=$ergebnisStatus.GetValue(30167)."\n";
//	$ergebnisStatus=$ergebnisStatus.GetValue(41813)."\n";
//	$ergebnisStatus=$ergebnisStatus.GetValue(11169)."\n";
//	$ergebnisStatus=$ergebnisStatus.GetValue(18739)."\n";
//	$ergebnisStatus=$ergebnisStatus.GetValue(39489)."\n";
//	$ergebnisStatus=$ergebnisStatus.GetValue(12808)."\n";
//	$ergebnisStatus=$ergebnisStatus.GetValue(13641)."\n";
//	$ergebnisStatus=$ergebnisStatus.GetValue(36734)."\n";
//	$ergebnisStatus=$ergebnisStatus.GetValue(46381)."\n";
//	$ergebnisStatus=$ergebnisStatus.GetValue(24490)."\n";

//	$ergebnisStatus.="\nDatenvolumen Down/Up : ".GetValue(32332)."/".GetValue(37701)." Mbyte\n";
//	$ergebnisStatus.=  " Down 7/30/30/30/360 : ".GetValue(32642)."/"
//															  .GetValue(49944)."/"
//															  .GetValue(49121)."/"
//															  .GetValue(17604)."/"
//															  .GetValue(12069)." Mbyte\n";
//	$ergebnisStatus.=  "   Up 7/30/30/30/360 : ".GetValue(39846)."/"
//															  .GetValue(46063)."/"
//															  .GetValue(45333)."/"
//															  .GetValue(50549)."/"
//															  .GetValue(21647)." MByte\n";

//	$ergebnisBewegung="\n\nVerlauf der Bewegungen:\n\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(38964)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(23869)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(16966)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(14097)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(14944)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(42042)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(39559)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(36666)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(30427)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(55972)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(57278)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(45148)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(21096)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(46545)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(25902)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(13726)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(22969)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(56534)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(59126)."\n";
//	$ergebnisBewegung=$ergebnisBewegung.GetValue(45878)."\n";

//	$BrowserExtAdr="http://".trim(GetValue(45252)).":82/";
//	$BrowserIntAdr="http://".trim(GetValue(33109)).":82/";
//	$IPStatus="\n\nIP Symcon Aufruf extern unter:".$BrowserExtAdr.
//	          "\nIP Symcon Aufruf intern unter:".$BrowserIntAdr."\n";

//	$ergebnis=$einleitung.$ergebnis_tagesenergie.$ergebnisTemperatur.$ergebnisRegen.$ergebnisStrom.$ergebnisStatus.$ergebnisBewegung.$IPStatus;

	if ($aktuell)
	   {
		$inst_modules="";
		}
	else
		{
		// Repository
		$repository = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';

		$moduleManager = new IPSModuleManager('');
		$installedModules = $moduleManager->GetInstalledModules();

		//print_r($installedModules);
		$inst_modules="Installierte Module:\n";
		foreach ($installedModules as $name=>$modules)
			{
			$inst_modules.=str_pad($name,20)." ".$modules."\n";
			}
		}


	$ergebnisTemperatur=""; $ergebnisRegen=""; $aktheizleistung=""; $ergebnis_tagesenergie=""; $alleTempWerte=""; $alleHumidityWerte="";
	$ergebnistab_energie=""; $ergebnisStrom=""; $ergebnisStatus=""; $ergebnisBewegung=""; $ergebnisGarten=""; $IPStatus=""; $ergebnistab_heizung="";
	}
else        /*  spezielle Routine f�r BKS01    */
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
		}
	unset($identifier); // break the reference with the last element

	/* Energiewerte der Vortage als Zeitreihe */
	$jetzt=time();
	$endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
	$starttime=$endtime-60*60*24*9;

	$werte = AC_GetLoggedValues(IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0], 24129, $starttime, $endtime, 0);
	$zeile = array("Datum" => array("Datum",0,1,2), "Heizung" => array("Heizung",0,1,2), "Datum2" => array("Datum",0,1,2), "Energie" => array("Energie",0,1,2));
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
	$ergebnistab_heizung=$ergebnis_tabelle."\n\n";
	$ergebnistab_energie=$ergebnis_tabelle1."\n\n";

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
		$ergebnisTemperatur = $ergebnisTemperatur.$identifier." : ".number_format(GetValue($TempWertID), 2, ",", "" )."�C ";
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

	$ergebnisGarten="\n\nVerlauf der Gartenbewaesserung:\n\n";
	$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Gartensteuerung.Nachrichtenverlauf-Garten');
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


	$BrowserExtAdr="http://".trim(GetValue(45252)).":82/";
	$BrowserIntAdr="http://".trim(GetValue(33109)).":82/";
	$IPStatus="\n\nIP Symcon Aufruf extern unter:".$BrowserExtAdr.
	          "\nIP Symcon Aufruf intern unter:".$BrowserIntAdr."\n";

   $moduleManager = new IPSModuleManager('');
   $installedModules = $moduleManager->GetInstalledModules();
	//print_r($installedModules);
	$inst_modules="Installierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,20)." ".$modules."\n";
		}

	/* Werte die es in BKS nicht gibt zumindest setzen */
	$guthaben=""; $cost=""; $internet=""; $statusverlauf=""; $energieverbrauch=""; $ergebnis_tabelle="";
	echo "\n----------------------------------------------------\n";
	}

	/******************************************************************************************/
	
	$alleTempWerte="\n\nAktuelle Temperaturwerte direkt aus den HW-Registern:\n\n";

	$Homematic = HomematicList();
	foreach ($Homematic as $Key)
		{
		/* alle Temperaturwerte ausgeben */
		if (isset($Key["COID"]["TEMPERATURE"])==true)
	   	{
	      $oid=(integer)$Key["COID"]["TEMPERATURE"]["OID"];
			$alleTempWerte.=str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
			}
		}

	$FHT = FHTList();
	foreach ($FHT as $Key)
		{
		/* alle Temperaturwerte ausgeben */
		if (isset($Key["COID"]["TemeratureVar"])==true)
		   {
      	$oid=(integer)$Key["COID"]["TemeratureVar"]["OID"];
			$alleTempWerte.=str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
			}
		}
	
	
	$alleHumidityWerte="\n\nAktuelle Feuchtigkeitswerte direkt aus den HW-Registern:\n\n";
	foreach ($Homematic as $Key)
		{
		/* alle Feuchtigkeitswerte ausgeben */
		if (isset($Key["COID"]["HUMIDITY"])==true)
	   	{
	      $oid=(integer)$Key["COID"]["HUMIDITY"]["OID"];
			$alleHumidityWerte.=str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
			}
		}

	if ($aktuell) /* aktuelle Werte */
	   {
	   if ($sommerzeit)
	      {
			$ergebnis=$einleitung.$ergebnisTemperatur.$ergebnisRegen.$aktheizleistung.$ergebnis_tagesenergie.$alleTempWerte.$alleHumidityWerte;
			}
		else
		   {
			$ergebnis=$einleitung.$aktheizleistung.$ergebnis_tagesenergie.$ergebnisTemperatur.$alleTempWerte.$alleHumidityWerte;
		   }
		}
	else   /* historische Werte */
	   {
	   if ($sommerzeit)
	      {
			$ergebnis=$einleitung.$ergebnistab_energie.$ergebnisRegen.$guthaben.$cost.$internet.$statusverlauf.$ergebnisStrom.
		           $ergebnisStatus.$ergebnisBewegung.$ergebnisGarten.$IPStatus.$energieverbrauch.$ergebnis_tabelle.$ergebnis_tagesenergie.$ergebnistab_heizung.$inst_modules;
			}
		else
		   {
			$ergebnis=$einleitung.$ergebnistab_heizung.$ergebnistab_energie.$energieverbrauch.$ergebnis_tabelle.$ergebnis_tagesenergie.$ergebnisRegen.$guthaben.$cost.$internet.$statusverlauf.$ergebnisStrom.
		           $ergebnisStatus.$ergebnisBewegung.$ergebnisGarten.$IPStatus.$inst_modules;
			}
		}

   return $ergebnis;
}









/******************************************************************/

function writeLogEvent($event)
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
	fwrite($handle, date("d.m.y H:i:s").";".$event."\r\n");
	SetValue(44647,date("d.m.y H:i:s").";".$event);

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
	SetValue(44647,$ausgabewert);

	fclose($handle);
}


/******************************************************************/

function CreateVariableByName($id, $name, $type)
{

	/* type steht f�r 0 Boolean 1 Integer 2 Float 3 String */
	
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

function CreateVariable2($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
{
		//echo "**********".$Name."****".$ParentId."***".Get_IdentByName($Name)."*****\n";
		$VariableId = @IPS_GetObjectIDByIdent(Get_IdentByName2($Name), $ParentId);
		echo "CreateVariable\n";
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
		$ident = str_replace(array('�','�','�','�','�','�'), array('oe', 'ae','ue','Oe', 'Ae','Ue' ), $ident);
		$ident = str_replace(array('"','\'','%','&','(',')','=','#','<','>','|','\\'), '', $ident);
		$ident = str_replace(array(',','.',':',';','!','?'), '', $ident);
		$ident = str_replace(array('+','-','/','*'), '', $ident);
		$ident = str_replace(array('�'), 'ss', $ident);
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

/******************************************************************/

function tts_play($sk,$ansagetext,$ton,$modus)
 	{
 	
 	global $playwaves,$genspeak;
   	/*
		modus == 1 ==> Sprache = on / Ton = off / Musik = play / Slider = off / Script Wait = off
		modus == 2 ==> Sprache = on / Ton = on / Musik = pause / Slider = off / Script Wait = on
		modus == 3 ==> Sprache = on / Ton = on / Musik = play  / Slider = on  / Script Wait = on
		*/

		$wav = array
		(
      "hinweis"  => IPS_GetKernelDir()."media/wav/hinweis.wav",
      "meldung"  => IPS_GetKernelDir()."media/wav/meldung.wav",
      "abmelden" => IPS_GetKernelDir()."media/wav/abmelden.wav",
      "aus"      => IPS_GetKernelDir()."media/wav/aus.wav",
      "coin"     => IPS_GetKernelDir()."media/wav/coin-fall.wav",
      "thunder"  => IPS_GetKernelDir()."media/wav/thunder.wav",
      "clock"    => IPS_GetKernelDir()."media/wav/clock.wav",
      "bell"     => IPS_GetKernelDir()."media/wav/bell.wav",
      "horn"     => IPS_GetKernelDir()."media/wav/horn.wav",
      "sirene"   => IPS_GetKernelDir()."media/wav/sirene.wav"
		);

		$id_sk1_musik         = 45034;
		$id_sk1_musik_status	= 58670;
		$id_sk1_musik_vol     = 42803;
   	$id_sk1_ton_status 	  = 38978;
		$id_sk1_ton           = $playwaves;
		$id_sk1_tts           = $genspeak;
		$id_sk1_counter		    = 26227;

		switch ($sk)
		{
			//---------------------------------------------------------------------
			case '1':

			  		$status = GetValueInteger($id_sk1_ton_status);
				   while ($status == 1)	$status = GetValueInteger($id_sk1_ton_status);

			      $sk1_counter = GetValueInteger($id_sk1_counter);
   	 			$sk1_counter++;
			      SetValueInteger($id_sk1_counter, $sk1_counter);
					if($sk1_counter >= 9) SetValueInteger($id_sk1_counter, $sk1_counter = 0);

				 	if($ton == "zeit")
 						{
						$time = time();
						// die Integer-Wandlung dient dazu eine f�hrende Null zu beseitigen
	   				$hrs = (integer)date("H", $time);
   					$min = (integer)date("i", $time);
	   				$sec = (integer)date("s", $time);
   					// "kosmetische Behandlung" f�r Ein- und Mehrzahl der Minutenangabe
   					if($hrs==1) $hrs = "ein";
	   				$minuten = "Minuten";
   					if($min==1)
   						{
      					$min = "eine";
	      				$minuten = "Minute";
			   			}
   					// Zeitansage �ber Text-To-Speech
  	 					$ansagetext = "Die aktuelle Uhrzeit ist ". $hrs. " Uhr und ". $min. " ". $minuten;
			  	 		$ton        = "";
					 	}

			   	//Lautst�rke von Musik am Anfang speichern
					$merken = $musik_vol = GetValue($id_sk1_musik_vol);
      			$musik_status 			 = GetValueInteger($id_sk1_musik_status);

					if($modus == 2)
						{
					   if($musik_status != 2)	WAC_Pause($id_sk1_musik);
						}


					if($modus == 3)
						{
						//Slider
		  			 	for ($musik_vol; $musik_vol>=1; $musik_vol--)
   					  	{
		      			WAC_SetVolume ($id_sk1_musik, $musik_vol);
      			   	$slider = 3000; //Zeit des Sliders in ms
							if($merken>0) $warten = $slider/$merken; else $warten = 0;
							IPS_Sleep($warten);
			     			}
     					}

					if($ton != "" and $modus != 1)
						{
  	   				WAC_Stop($id_sk1_ton);
		      		WAC_SetRepeat($id_sk1_ton, false);
     					WAC_ClearPlaylist($id_sk1_ton);
     					WAC_AddFile($id_sk1_ton,$wav[$ton]);
		     			WAC_Play($id_sk1_ton);
		            //solange in Schleife bleiben wie 1 = play
		   	  		sleep(1);
      			  $status = getvalue($id_sk1_ton_status);
  	   			  while ($status == 1)	$status = getvalue($id_sk1_ton_status);
			 		  }

					if($ansagetext !="")
						{
  						WAC_Stop($id_sk1_ton);
			      	WAC_SetRepeat($id_sk1_ton, false);
			         WAC_ClearPlaylist($id_sk1_ton);
   			      $status=TTS_GenerateFile($id_sk1_tts, $ansagetext, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav",39);
						if (!$status) echo "Error";
		     			WAC_AddFile($id_sk1_ton, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav");
						WAC_Play($id_sk1_ton);
						}

					//Script solange anghalten wie Sprachausgabe l�uft
					if($modus != 1)
						{
			   		sleep(1);
						$status = GetValueInteger($id_sk1_ton_status);
   	  				while ($status == 1)	$status = GetValueInteger($id_sk1_ton_status);
			   		}

			 		if($modus == 3)
						{
			   		$musik_vol = GetValueInteger($id_sk1_musik_vol);
		   			for ($musik_vol=1; $musik_vol<=$merken; $musik_vol++)
		      			{
				         WAC_SetVolume ($id_sk1_musik, $musik_vol);
      	   		   $slider = 3000; //Zeit des Sliders in ms
							if($merken>0) $warten = $slider/$merken; else $warten = 0;
							IPS_Sleep($warten);
      					}
      				}
					if($modus == 2)
						{
				   	if($musik_status != 2)	WAC_Pause($id_sk1_musik);
				   	}
					break;

			//---------------------------------------------------------------------

			//Hier k�nnen weitere Soundkarten eingef�gt werden
			//case '2':
			//entsprechende Werte bitte anpassen

		}  //end switch
 	}   //end function

/**********************************************************************************************/

function summestartende($starttime, $endtime, $increment, $estimate)
	{
	global $archiveHandlerID, $variableID, $display;

	$zaehler=0;
	$initial=true;
	$ergebnis=0;
	$vorigertag="";

	$gepldauer=($endtime-$starttime)/24/60/60;
	do {
		/* es k�nnten mehr als 10.000 Werte sein
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
				$aktwert=$wert['Value'];

				if ($tag!=$vorigertag)
			   	{
				   if ($increment==true)
						{
						$ergebnis=$aktwert;
						}
					else
					   {
						$ergebnis+=$aktwert;
						}
				   $vorigertag=$tag;
				   }

				if ($display==true)
					{
			   	/* jeden Eintrag ausgeben */
					echo "   ".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . number_format($wert['Value'], 3, ".", "") ." ergibt in Summe: " . number_format($ergebnis, 3, ".", "") . PHP_EOL;
					}
				$zaehler+=1;
				}
				$endtime=$zeit;
		} while (count($werte)==10000);

	$dauer=($ersterzeit-$letzterzeit)/24/60/60;
	echo "   Bearbeitete Werte:".$zaehler." f�r ".number_format($dauer, 2, ",", "")." Tage davon erwartet: ".$gepldauer." \n";
	if ($increment==true)
		{
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
		}
	else
	   {
		if ($estimate==true)
			{
			echo "   Vor Hochrechnung ".number_format($ergebnis, 3, ".", "");
			$ergebnis=($ergebnis)*$gepldauer/$dauer;
	     	echo " und nach Hochrechnung ".number_format($ergebnis, 3, ".", "")." \n";
			}
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



?>
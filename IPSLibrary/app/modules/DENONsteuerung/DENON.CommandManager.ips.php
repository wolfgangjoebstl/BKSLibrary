<?
//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------

/*
Inital-Autor: philipp, Quelle: http://www.ip-symcon.de/forum/f53/denon-avr-3808-integration-7007/
Inital-Autor: www.raketenschnecke.com
Weiterentwickelt: Wolfgang Jöbstl

Funktionen:
	* liest und interpretiert die vom DENON empfangenen Statusmeldungen und
		übergibt diese zur Veiterverarbeitung an das Script "DENON.VariablenManager"

*/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('DENONsteuerung',$repository);
	}

IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

$RemoteVis_Enabled    = $moduleManager->GetConfigValue('Enabled', 'RemoteVis');

$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');

$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
$WFC10User_Path       = $moduleManager->GetConfigValue('Path', 'WFC10User');

$Mobile_Enabled       = $moduleManager->GetConfigValue('Enabled', 'Mobile');
$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');

$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
$scriptIdDENONsteuerung   = IPS_GetScriptIDByName('DENONsteuerung', $CategoryIdApp);

$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
echo "\n";
echo "Category App           ID: ".$CategoryIdApp."\n";
echo "Category Data          ID: ".$CategoryIdData."\n";
echo "Webfront Administrator ID: ".$categoryId_WebFront."     ".$WFC10_Path."\n";

$object_data= new ipsobject($CategoryIdData);
$object_app= new ipsobject($CategoryIdApp);

$NachrichtenID = $object_data->osearch("Nachricht");
$NachrichtenScriptID  = $object_app->osearch("Nachricht");

if (isset($NachrichtenScriptID))
	{
	$object3= new ipsobject($NachrichtenID);
	$NachrichtenInputID=$object3->osearch("Input");
	//$object3->oprint();
	echo "Nachrichten Script     ID :".$NachrichtenScriptID."\n";
	echo "Nachrichten Input      ID : ".$NachrichtenInputID."\n";
	/* logging in einem File und in einem String am Webfront */
	$log_Denon=new Logging("C:\Scripts\Log_Denon.csv",$NachrichtenInputID);
	}
else break;

$configuration=Denon_Configuration();

/* include DENON.Functions
  $id des DENON Client sockets muss nun selbst berechnet werden, war vorher automatisch
*/
if (IPS_GetObjectIDByName("DENON.VariablenManager", $CategoryIdApp) >0)
	{
	include "DENON.VariablenManager.ips.php";
	}
else
	{
	echo "Script DENON.VariablenManager kann nicht gefunden werden!";
	}

if ($_IPS['SENDER'] == "Execute")
	{
	echo "Script wurde direkt aufgerufen.\n";
	$log_Denon->LogMessage("Script wurde direkt aufgerufen");
	$log_Denon->LogNachrichten("Script wurde direkt aufgerufen");
	}
else
	{
	$data=$_IPS['VALUE'];
	$instanz=IPS_GetName($_IPS['INSTANCE']);  /* feststellen wer der Sender war */
	/* hier kommt zB DENON2 Register Variable, Register Variable wegtrennen und in Konfiguration suchen */
	$instanz=strstr($instanz," Register Variable",true);
	foreach ($configuration as $config)
		{
		if ($config['INSTANZ']==$instanz)
		   {
	   	$id=$config['NAME'];
		   }
		//print_r($config);
	
		if (isset($id)==false)
			{
			$log_Denon->LogMessage("Instanz wurde nicht gefunden");
			$log_Denon->LogNachrichten("Instanz wurde nicht gefunden");
			break;
			}

		$maincat= substr($data,0,2); //Eventidentifikation
		$zonecat= substr($data,2); //Zoneneventidentifikation
		switch($maincat)
			{
	
			/* Eventidentifikation

			PW MV MU ZM SI SV MS DC SD SR SL VS PS NS CV Z2 Z3


		  */

			/*---------------------------------------------------------------------------*/
			case "PW": //MainPower
				$item = "Power";
				$vtype = 0;
				if ($data == "PWON")
					{
					$value = true;
					}
				elseif ($data == "PWSTANDBY")
					{
					$value = false;
					}
				else
				   {
					$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				DenonSetValue($item, $value, $vtype, $id);
				DenonSetValue($item, $value, $vtype, $id,$WFC10User_Path);
				DenonSetValue($item, $value, $vtype, $id,$Mobile_Path);
				DenonSetValue($item, $value, $vtype, $id,$Retro_Path);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
				break;

			/*---------------------------------------------------------------------------*/
			case "MV": //Mastervolume
				if (substr($data,2,3) =="MAX")
					{
					}
				else
					{
					$item = "MasterVolume";
					$vtype = 2;
					$itemdata=substr($data,2);
					if ( $itemdata == "99")
						{
						$value = "";
						}
					else
						{
						$itemdata= str_pad ( $itemdata, 3, "0" );
						$value = (intval($itemdata)/10) -80;
						}
					DenonSetValue($item, $value, $vtype, $id);
					DenonSetValue($item, $value, $vtype, $id,$WFC10User_Path);
					DenonSetValue($item, $value, $vtype, $id,$Mobile_Path);
					DenonSetValue($item, $value, $vtype, $id,$Retro_Path);
					$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
					$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					}
			 	break;

			/*---------------------------------------------------------------------------*/
			case "MU": //MainMute
				$item = "MainMute";
				$vtype = 0;
				if ($data == "MUON")
					{
					$value = true;
					}
				elseif ($data == "MUOFF")
					{
					$value = false;
					}
				else
				   {
				$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
		   	}

				DenonSetValue($item, $value, $vtype, $id);
				DenonSetValue($item, $value, $vtype, $id,$WFC10User_Path);
				DenonSetValue($item, $value, $vtype, $id,$Mobile_Path);
				DenonSetValue($item, $value, $vtype, $id,$Retro_Path);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
				break;

			/*---------------------------------------------------------------------------*/
			case "ZM": //MainZone
				$item = "MainZonePower";
				$vtype = 0;
				if ($data == "ZMON")
					{
					$value = true;
					}
				elseif ($data == "ZMOFF")
					{
					$value = false;
					}
				else
				   {
					$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				DenonSetValue($item, $value, $vtype, $id);
				DenonSetValue($item, $value, $vtype, $id,$WFC10User_Path);
				DenonSetValue($item, $value, $vtype, $id,$Mobile_Path);
				DenonSetValue($item, $value, $vtype, $id,$Retro_Path);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
				break;

			/*---------------------------------------------------------------------------*/
			case "SI": //Source Input
				$item = "InputSource";
				$vtype = 1;
				if ($data == "SIPHONO")
					{
					$value = 0;
					}
				elseif ($data == "SICD")
					{
					$value = 1;
					}
				elseif ($data == "SITUNER")
					{
					$value = 2;
					}
				elseif ($data == "SIDVD")
					{
					$value = 3;
					}
				elseif ($data == "SIBD")
					{
					$value = 4;
					}
				elseif ($data == "SITV")
					{
					$value = 5;
					}
				elseif ($data == "SISAT/CBL")
					{
					$value = 6;
					}
				elseif ($data == "SIDVR")
					{
					$value = 7;
					}
				elseif ($data == "SIGAME")
					{
					$value = 8;
					}
				elseif ($data == "SIV.AUX")
					{
					$value = 9;
					}
				elseif ($data == "SIDOCK")
					{
					$value = 10;
					}
				elseif ($data == "SIIPOD")
					{
					$value = 11;
					}
				elseif ($data == "SINET/USB")
					{
					$value = 12;
					}
				elseif ($data == "SINAPSTER")
					{
					$value = 13;
					}
				elseif ($data == "SILASTFM")
					{
					$value = 14;
					}
				elseif ($data == "SIFLICKR")
					{
					$value = 15;
					}
				elseif ($data == "SIFAVORITES")
					{
					$value = 16;
					}
				elseif ($data == "SIIRADIO")
					{
					$value = 17;
					}
				elseif ($data == "SISERVER")
					{
					$value = 18;
					}
				elseif ($data == "SIUSB/IPOD")
					{
					$value = 19;
					}
				elseif ($data == "SIMPLAY")    /* new one */
					{
					$value = 20;
					}
				elseif ($data == "SINET")    /* new one */
					{
					$value = 21;
					}
				elseif ($data == "SIIPOD DIRECT")
					{
					$value = 22;
					}
				else
				   {
					$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				$value = intval($value);
				DenonSetValue($item, $value, $vtype, $id);
				DenonSetValue($item, $value, $vtype, $id,$WFC10User_Path);
				DenonSetValue($item, $value, $vtype, $id,$Mobile_Path);
				DenonSetValue($item, $value, $vtype, $id,$Retro_Path);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
				break;

			/*---------------------------------------------------------------------------*/
			case "SV": //Video Select
				$item = "VideoSelect";
				$vtype = 1;
				if ($data == "SVDVD")
					{
					$value = 0;
					}
				elseif ($data == "SVBD")
					{
					$value = 1;
					}
				elseif ($data == "SVTV")
					{
					$value = 2;
					}
				elseif ($data == "SVSAT/CBL")
					{
					$value = 3;
					}
				elseif ($data == "SVDVR")
					{
					$value = 4;
					}
				elseif ($data == "SVGAME")
					{
					$value = 5;
					}
				elseif ($data == "SVV.AUX")
					{
					$value = 6;
					}
				elseif ($data == "SVDOCK")
					{
					$value = 7;
					}
				elseif ($data == "SVSOURCE")
					{
					$value = 8;
					}
				elseif ($data == "SVOFF") /* new one */
					{
					$value = 9;
					}
				else
				   {
					$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
				break;

			/*---------------------------------------------------------------------------*/
			case "MS": // Surround Mode und Quickselect
				if (substr($data,0,7) == "MSQUICK")
					{
					//Quickselect
					$item = "QuickSelect";
					$vtype = 1;
					if (substr($data,0,7) == "MSQUICK")
						{
						$value = intval(substr($data,7,1));
						}
					DenonSetValue($item, $value, $vtype, $id);
					}
				else
					{
					//Surround Mode
					$item = "SurroundMode";
					$vtype = 1;
					if ($data == "MSDIRECT")
						{
						$value = 0;
						}
					elseif ($data == "MSPURE DIRECT")
						{
						$value = 1;
						}
					elseif ($data == "MSSTEREO")
						{
						$value = 2;
						}
					elseif ($data == "MSSTANDARD")
						{
						$value = 3;
						}
					elseif ($data == "MSDOLBY DIGITAL")
						{
						$value = 4;
						}
					elseif ($data == "MSDTS SURROUND")
						{
						$value = 5;
						}
					elseif ($data == "MSDOLBY PL2X C")
						{
						$value = 6;
						}
					elseif ($data == "MSMCH STEREO")
						{
						$value = 7;
						}
					elseif ($data == "MSROCK ARENA")
						{
						$value = 8;
						}
					elseif ($data == "MSJAZZ CLUB")
						{
						$value = 9;
						}
					elseif ($data == "MSMONO MOVIE")
						{
						$value = 10;
						}
					elseif ($data == "MSMATRIX")
						{
						$value = 11;
						}
					elseif ($data == "MSVIDEO GAME")
						{
						$value = 12;
						}
					elseif ($data == "MSVIRTUAL")
						{
						$value = 13;
						}
					elseif ($data == "MSMULTI CH IN 7.1")
						{
						$value = 14;
						}
					elseif ($data == "MSDTS NEO:6 C")
						{
						$value = 15;
						}
					elseif ($data == "MSDOLBY PL2 C")
						{
						$value = 16;
						}
					else
			   		{
						$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   	}
					DenonSetValue($item, $value, $vtype, $id);
					$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
					$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
					}
				break;

			/*---------------------------------------------------------------------------*/
			case "DC": //Digital Input Mode
				$item = "DigitalInputMode";
				$vtype = 1;
				if ($data == "DCAUTO")
					{
					$value = 0;
					}
				elseif ($data == "DCPCM")
					{
					$value = 1;
					}
				elseif ($data == "DCDTS")
					{
					$value = 2;
					}
				else
				   {
					$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
				break;

			/*---------------------------------------------------------------------------*/
			case "SD": //Input Mode AUTO/HDMI/DIGITALANALOG/ARC/NO
				$item = "InputMode";
				$vtype = 1;
				if ($data == "SDAUTO")
					{
					$value = 0;
					}
				elseif ($data == "SDHDMI")
					{
					$value = 1;
					}
				elseif ($data == "SDDIGITAL")
					{
					$value = 2;
					}
				elseif ($data == "SDANALOG")
					{
					$value = 3;
					}
				elseif ($data == "NO")
					{
					$value = 4;
					}
				else
				   {
					$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
				break;

			/*---------------------------------------------------------------------------*/
			case "SR": //Record Selection
				$item = "RecordSelection";
				$vtype = 1;
				$itemdata=substr($data,2);
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
				break;

			/*---------------------------------------------------------------------------*/
			case "SS": //new Selection, unclear function
			   /*  Beispiele SSINFAISFSV NON   SSINFAISSIG 02                */
				$command=substr($data,2,6);
				if ($command=="INFAIS")
				   {
					if (substr($data,6,3)=="FSV")
						{
						/* unclear */
						$item = "SSINFAISFSV";
						$itemdata=substr($data,10);
						}
					elseif (substr($data,6,3)=="SIG")
					   {
					   /* unclear */
						$item = "SSINFAISSIG";
						$itemdata=substr($data,10);
					   }
					else
					   {
						$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
					   }
					}
				elseif ($command=="SMG")
				   {
					$item = "SSSMG";
					$itemdata=substr($data,7);
					}
				else
				   {
					$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				$vtype = 3;  /* String */
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
				break;

			/*---------------------------------------------------------------------------*/
			case "TF": //Tuner Frequency
				$command=substr($data,2,2);
				if ($command=="AN")
				   {
					if (substr($data,4,4)=="NAME")
						{
						/* Stationsname */
						$itemdata=substr($data,8);
						}
					else
					   {
					   /* Frequenz */
						$itemdata=substr($data,8);
					   }
					$item = "TunerFrequency";
					$vtype = 3;  /* String */
					$value = $itemdata;
					}
				else
				   {
					$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				/* kein Logging da dauernd die RDS Daten übertragen werden */
				DenonSetValue($item, $value, $vtype, $id);
				break;

			/*---------------------------------------------------------------------------*/
			case "SL": //Main Zone Sleep
				$item = "Sleep";
				$vtype = 1;
				if ($data == "SLPOFF")
					{
					$itemdata = 0;
					}
				else
					{
					$itemdata = substr($data,3,3);
					}
				$value = intval($itemdata);
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			/*---------------------------------------------------------------------------*/
			case "VS": //Videosignal
				$vssub=substr($data,2,2);
				switch($vssub)
					{
					case "MO": //HDMI Monitor
						$item = "HDMIMonitor";
						$vtype = 3;
						$itemdata=substr($data,5);
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						break;

					case "AS": //Video Aspect
						$item = "VideoAspect";
						$vtype = 0;
						if ($data == "VSASPFUL")
							{
							$value = true;
							}
						elseif ($data == "VSASPNRM")
							{
							$value = false;
							}
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
						break;

					case "SC": //Scaler
						$item = "Scaler";
						$vtype = 3;
						$itemdata=substr($data,4);
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
						break;
					}
				break;

			/*---------------------------------------------------------------------------*/
	case "PS": //Sound
		$pssub=substr($data,2,2);
		switch($pssub)
			{
			case "TO": //Tone Defeat/Tone Control
				$pssubsub=substr($data,7,2);
				switch($pssubsub)
					{
					case "CT": //Tone Control (AVR 3311)
						$item = "ToneCTRL";
						$vtype = 0;
						if ($data == "PSTONE CTRL ON")
							{
							$value = true;
							}
						elseif ($data == "PSTONE CTRL OFF")
							{
							$value = false;
							}
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
						break;

					case "DE": //Tone Defeat (AVR 3808)
						$item = "ToneDefeat";
						$vtype = 0;
						if ($data == "PSTONE DEFEAT ON")
							{
							$value = true;
							}
						elseif ($data == "PSTONE DEFEAT ON")
							{
							$value = false;
							}
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;
					}
				break;

			case "FH": // Front Height ON/OFF
				$item = "FrontHeight";
				$vtype = 0;
				if ($data == "PSFH:ON")
					{
					$value = true;
					}
				if ($data == "PSFH:OFF")
					{
					$value = false;
					}
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "CI": //Cinema EQ
				$item = "CinemaEQ";
				$vtype = 0;
				if ($data == "PSCINEMA EQ.ON")
					{
					$value = true;
					}
				if ($data == "PSCINEMA EQ.OFF")
					{
					$value = false;
					}
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "RO": //Room EQ Mode
				$item = "RoomEQMode";
				$vtype = 3;
				$itemdata=substr($data,10);
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "DC": //Dynamic Compressor
				$item = "DynamicCompressor";
				$vtype = 1;
				if ($data == "PSDCO OFF")
					{
					$value = 0;
					}
				elseif ($data == "PSDCO LOW")
					{
					$value = 1;
					}
				elseif ($data == "PSDCO MID")
					{
					$value = 2;
					}
				elseif ($data == "PSDCO HIGH")
					{
					$value = 3;
					}
				else
				   {
					$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "PA": //Verteilung Front-Signal auf Surround-Kanäle
				$item = "Panorama";
				$vtype = 0;
				if ($data == "PSPAN ON")
					{
					$value = true;
					}
				elseif ($data == "PSPAN OFF")
					{
					$value = false;
					}
				else
				   {
					$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "DI": //Balance zwischen Front und Surround-LS
				$item = "Dimension";
				$vtype = 1;
				$itemdata=substr($data, 6, 2);
				$value = (int)$itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "CE": //Center-Signal Verteilung auf FrontR/L
				$item = "C.Width";
				$vtype = 1;
				$itemdata=substr($data, 6, 2);
				$value = (int)$itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "SB": //Surround-Back ON/OFF
				$item = "SurroundBackMode";
				$vtype = 1;
				if ($data == "PSSB:OFF")
					{
					$value = 0;
					}
				elseif ($data == "PSSB:ON")
					{
					$value = 1;
					}
				elseif ($data == "PSSB:MRTX ON")
					{
					$value = 2;
					}
				elseif ($data == "PSSB:PL2X CINEMA")
					{
					$value = 3;
					}
				elseif ($data == "PSSB:PL2X MUSIC")
					{
					$value = 4;
					}
				elseif ($data == "PSSB:ESDSCRT")
					{
					$value = 5;
					}
				elseif ($data == "PSSB:ESMRTX")
					{
					$value = 6;
					}
				elseif ($data == "PSSB:DSCRT ON")
					{
					$value = 7;
					}
				else
				   {
					$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
					DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "MO": //Surround-Spielmodi für Surround-Mode
				$item = "SurroundPlayMode";
				$vtype = 1;
				if ($data == "PSMODE:CINEMA")
					{
					$value = 0;
					}
				elseif ($data == "PSMODE:MUSIC")
					{
					$value = 1;
					}
				elseif ($data == "PSMODE:GAME")
					{
					$value = 2;
					}
				else
				   {
					$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
				break;

			case "MU": //MultEQ XT mode
				$item = "MultiEQMode";
				$vtype = 1;
				if ($data == "PSMULTEQ:OFF")
					{
					$value = 0;
					}
				elseif ($data == "PSMULTEQ:AUDYSSEY")
					{
					$value = 1;
					}
				elseif ($data == "PSMULTEQ:BYP.LR")
					{
					$value = 2;
					}
				elseif ($data == "PSMULTEQ:FLAT")
					{
					$value = 3;
					}
				elseif ($data == "PSMULTEQ:MANUAL")
					{
					$value = 4;
					}
				else
				   {
					$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
				break;

			case "DY": //Sound
				$pssubsub=substr($data,4,2);
				switch($pssubsub)
				{
					case "NE": //Dynamic Equalizer ON/OFF
						$item = "DynamicEQ";
						$vtype = 0;
						if ($data == "PSDYNEQ ON")
						{
							$value = true;
						}
						elseif ($data == "PSDYNEQ OFF")
						{
							$value = false;
						}
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "NV": //Surround-Spielmodi für Surround-Mode
						$item = "DynamicVolume";
						$vtype = 1;
						if ($data == "PSDYNVOL OFF")
						{
							$value = 0;
						}
						if ($data == "PSDYNVOL NGT")
						{
							$value = 1;
						}
						elseif ($data == "PSDYNVOL EVE")
						{
							$value = 2;
						}
						elseif ($data == "PSDYNVOL DAY")
						{
							$value = 3;
						}
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;
				}
			break;

			case "DR": //Dynamic Compressor
				$item = "DynamicRange";
				$vtype = 1;
				if ($data == "PSDRC OFF")
				{
					$value = 0;
				}
				elseif ($data == "PSDRC AUTO")
				{
					$value = 1;
				}
				elseif ($data == "PSDRC LOW")
				{
					$value = 2;
				}
				elseif ($data == "PSDRC MID")
				{
					$value = 3;
				}
				elseif ($data == "PSDRC HIGH")
				{
					$value = 4;
				}
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "LF": //LFE Pegel
				$item = "LFELevel";
				$vtype = 2;
				$itemdata=substr($data, 6, 2);
				$value = (0 - intval($itemdata));
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			 break;

			case "BA": //Bass Pegel
				$item = "BassLevel";
				$vtype = 1;
				$itemdata=substr($data, 6, 2);
				$value = (intval($itemdata)) -50;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			 break;

			case "TR": //Treble Pegel
				$item = "TrebleLevel";
				$vtype = 2;
				$itemdata=substr($data,6, 2);
				$value = (intval($itemdata)) -50;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "DE": //Audio Delay 0-200ms
				$item = "AudioDelay";
				$vtype = 1;
				$itemdata=substr($data,8, 3);
				$value = intval($itemdata);
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "RS": //Tone Defeat/Tone Control
				$pssubsub1=substr($data,4,1);
				switch($pssubsub1)
				{
					case "T": //Surround-Spielmodi für Surround-Mode
						$item = "AudioRestorer";
						$vtype = 1;
						if ($data == "PSRSTR OFF")
						{
							$value = 0;
						}
						elseif ($data == "PSRSTR MODE1")
						{
							$value = 1;
						}
						elseif ($data == "PSRSTR MODE2")
						{
							$value = 2;
						}
						elseif ($data == "PSRSTR MODE3")
						{
							$value = 3;
						}
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "Z": //RoomSize
						$item = "RoomSize";
						$vtype = 1;
						if ($data == "PSRSZ N")
						{
							$value = 0;
						}
						elseif ($data == "PSRSZ S")
						{
							$value = 1;
						}
						elseif ($data == "PSRSZ MS")
						{
							$value = 2;
						}
						elseif ($data == "PSRSZ M")
						{
							$value = 3;
						}
						elseif ($data == "PSRSZ ML")
						{
							$value = 4;
						}
						elseif ($data == "PSRSZ L")
						{
							$value = 5;
						}
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;
				}
			break;
		}
	break;

	// Display
	case "NS": //NSE, NSA, NSH
		$vssub=substr($data,2,1);
		switch($vssub)
		{
			case "E": //Anzeige aktueller Titel
				$vssubE=substr($data,2,2);
				switch($vssubE)
				{
					case "E0": //Zeile 1
						$item = "DisplLine1";
						$vtype = 3;
						$itemdata = rtrim(substr($data, 4, 95));
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "E1": //Zeile 2
						$item = "DisplLine2";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "E2": //Zeile 3
						$item = "DisplLine3";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "E3": // Zeile 4
						$item = "DisplLine4";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "E4": // Zeile 5
						$item = "DisplLine5";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "E5": // Zeile 6
						$item = "DisplLine6";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "E6": // Zeile 7
						$item = "DisplLine7";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "E7": // Zeile 8
						$item = "DisplLine8";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "E8": // Zeile 9
						$item = "Displcurrent Position";
						$vtype = 3;
						$itemdata = rtrim(substr($data, 4, 95));
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$currentPosition = $itemdata = substr($data, 7, 1);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;
				}
			break;

			case "A": // Display NSA Zeilen 1-8
				$vssubA = substr($data,2,2);
				switch($vssubA)
				{
					case "A0": //Zeile 1
						$item = "DisplLine1";
						$vtype = 3;
						$itemdata = rtrim(substr($data, 4, 95));
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "A1": //Zeile 2
						$item = "DisplLine2";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "A2": //Zeile 3
						$item = "DisplLine3";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "A3": // Zeile 4
						$item = "DisplLine4";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "A4": // Zeile 5
						$item = "DisplLine5";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "A5": // Zeile 6
						$item = "DisplLine6";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "A6": // Zeile 7
						$item = "DisplLine7";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "A7": // Zeile 8
						$item = "DisplLine8";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "A8": // Zeile 9
						$item = "Displcurrent Position";
						$vtype = 3;
						$itemdata = $ProfilValue = rtrim(substr($data, 5, 95));
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$currentPosition = $itemdata = substr($data, 7, 1);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;
				}
			break;

			case "H": // Preset-Werte ins Variablenprofil "DENON.Preset" schreiben
				// Variable anlegen
				$item = "Preset";
				$vtype = 1;
				$itemdata=substr($data, 3, 2);
				$value = intval($itemdata);
				$ProfilPosition = $value;
				$ProfilValue = rtrim(substr($data, 5, 100));
            if (strlen($ProfilValue) > 0)
            {
					DenonSetValue($item, $value, $vtype, $id); // Variablenwert setzen
					DENON_SetProfileValue($item, $ProfilPosition, $ProfilValue); // Werte ins Variablenprofil schreiben (nur wenn Preset mit Werten belegt)
				}
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;
		}
	break;

	case "CV": //Zone 2 Channel Volume
		$CV_sub = substr($data,2,2);
		switch ($CV_sub)
		{
			case "FL":
				$item = "ChannelVolumeFL";
				$vtype = 2;
				$itemdata = substr($data,5,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "FR":
				$item = "ChannelVolumeFR";
				$vtype = 2;
				$itemdata = substr($data,5,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "C ":
				$item = "ChannelVolumeC";
				$vtype = 2;
				$itemdata=substr($data,4,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "SW":
				$item = "ChannelVolumeSW";
				$vtype = 2;
				$itemdata=substr($data,5,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "SL":
				$item = "ChannelVolumeSL";
				$vtype = 2;
				$itemdata=substr($data,5,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "SR":
				$item = "ChannelVolumeSR";
				$vtype = 2;
				$itemdata=substr($data,5,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "SB":
				$case = substr($data,2,3);
				if ($case == "SBL")
				{
					$item = "ChannelVolumeSBL";
					$vtype = 2;
					$itemdata=substr($data,6,3);
					echo "itemdata $itemdata /n";
					$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
					$value = (intval($itemdata)/10) -50;
					DenonSetValue($item, $value, $vtype, $id);
					echo "SBL Wert = $value /n";
				}
				elseif ($case == "SBR")
				{
					$item = "ChannelVolumeSBR";
					$vtype = 2;
					$itemdata = substr($data,6,3);
					$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
					$value = (intval($itemdata)/10) -50;
					DenonSetValue($item, $value, $vtype, $id);
				}
				elseif ($case == "SB ")
				{
					$item = "ChannelVolumeSB";
					$vtype = 2;
					$itemdata = substr($data,5,2);
					$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
					$value = (intval($itemdata)/10) -50;
					DenonSetValue($item, $value, $vtype, $id);
				}
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "FH":
				$case = $itemdata=substr($data,2,3);
				if ($case == "FHL")
				{
					$item = "ChannelVolumeFHL";
					$vtype = 2;
					$itemdata=substr($data,6,3);
					$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
					$value = (intval($itemdata)/10) -50;
					DenonSetValue($item, $value, $vtype, $id);
				}
				elseif ($case == "FHR")
				{
					$item = "ChannelVolumeFHR";
					$vtype = 2;
					$itemdata=substr($data,6,3);
					$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
					$value = (intval($itemdata)/10) -50;
					DenonSetValue($item, $value, $vtype, $id);
				}
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "FW":
				$case = $itemdata=substr($data,2,3);
				if ($case == "FWL")
				{
					$item = "ChannelVolumeFWL";
					$vtype = 2;
					$itemdata=substr($data,6,3);
					$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
					$value = (intval($itemdata)/10) -50;
					DenonSetValue($item, $value, $vtype, $id);
				}
				elseif ($case == "FWR")
				{
					$item = "ChannelVolumeFWR";
					$vtype = 2;
					$itemdata=substr($data,6,3);
					$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
					$value = (intval($itemdata)/10) -50;
					DenonSetValue($item, $value, $vtype, $id);
				}
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;
		}
	break;


############### Zone 2 #########################################################

	case "Z2":
	   if (intval($zonecat) <100 and intval($zonecat) >9)
		{
			$item = "Zone2Volume";
			$vtype = 1;
			$itemdata=substr($data,2,2);
			if ( $itemdata == "99")
			{
				$value = "";
			}
			else
			{
				$value = (intval($itemdata)) -80;

			}
			DenonSetValue($item, $value, $vtype, $id);
			$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
			$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
		}

		switch ($zonecat)
		{
			case "PHONO": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 0;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "CD": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 1;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "TUNER": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 2;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "DVD": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 3;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "BD": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 4;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "TV": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 5;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "SAT/CBL": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 6;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "DVR": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 7;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "GAME": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 8;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "V.AUX": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 9;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "DOCK": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 10;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "IPOD": //Source Input Z3 (AVR 3809)
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 11;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "NET/USB":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 12;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "NAPSTER":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 13;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "LASTFM":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 14;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "FLICKR":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 15;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "FAVORITES":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 16;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "IRADIO":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 17;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "SERVER":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 18;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "USP/IPOD":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 19;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "OFF": //Zone 2 Power
				$item = "Zone2Power";
				$vtype = 0;
				$itemdata= false;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "ON": //Zone 3 Power
				$item = "Zone2Power";
				$vtype = 0;
				$itemdata= true;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;
		}


		$ZoneCat_sub = substr($data,2,2);
		switch ($ZoneCat_sub)
		{
			case "MU": //Zone 2 Mute ON/OFF
				$item = "Zone2Mute";
				$vtype = 0;
				if ($data == "Z2MUOFF")
				{
					$value = false;
				}
				elseif ($data == "Z2MUON")
				{
					$value = true;
				}
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "CS": //Zone 2 Channel Setting MONO/STEREO
				$item = "Zone2ChannelSetting";
				$vtype = 1;
				if ($data == "Z2CSST")
				{
					$value = 0;
				}
				elseif ($data == "Z2CSMONO")
				{
					$value = 1;
				}
			     DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "CV": //Zone 2 Channel Volume
				$Z2CV_sub = substr($data,4,2);
				switch ($Z2CV_sub)
				{
					case "FL":
						$item = "Zone2ChannelVolumeFL";
						$vtype = 1;
						$itemdata=substr($data,7,2);
						$value = intval($itemdata) -50;
						DenonSetValue($item, $value, $vtype, $id);
					break;

					case "FR":
						$item = "Zone2ChannelVolumeFR";
						$vtype = 1;
						$itemdata=substr($data,7,2);
						$value = intval($itemdata) -50;
						DenonSetValue($item, $value, $vtype, $id);
					break;
				}
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "QU": //Zone 2 Quick Select
				$item = "Zone2QuickSelect";
				$vtype = 1;
				if ($data == "Z2QUICK0")
					{
						$value = 0;
					}
					elseif ($data == "Z2QUICK1")
					{
						$value = 1;
					}
					elseif ($data == "Z2QUICK2")
					{
						$value = 2;
					}
					elseif ($data == "Z2QUICK3")
					{
						$value = 3;
					}
					elseif ($data == "Z2QUICK4")
					{
						$value = 4;
					}
					elseif ($data == "Z2QUICK5")
					{
						$value = 5;
					}
					$value = intval($value);
			     DenonSetValue($item, $value, $vtype, $id);
					$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
					$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;
		}
	break;

/* #################### Zone 3 #################################################### */

	case "Z3": //Source Input
		if (intval($zonecat) <100 and intval($zonecat) >9)
		{
			$item = "Zone3Volume";
			$vtype = 1;
			$itemdata=substr($data,2,2);
			if ( $itemdata == "99")
			{
				$value = "";
			}
			else
			{
				$value = (intval($itemdata)) -80;
			}
			DenonSetValue($item, $value, $vtype, $id);
			$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
			$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
		}

		switch ($zonecat)
		{
			case "PHONO": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 0;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "CD": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 1;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "TUNER": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 2;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "DVD": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 3;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "HDP": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 4;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "TV/CBL": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 5;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "SAT": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 6;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "VCR": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 7;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "DVR": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 8;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "V.AUX": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 9;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "NET/USB": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 10;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "USB": //Source Input Z3 (AVR 3809)
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 11;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "USB/IPOD": //Source Input Z3 (AVR 3311)
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 13;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "OFF": //Zone 3 Power
				$item = "Zone3Power";
				$vtype = 0;
				$itemdata= false;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "ON": //Zone 3 Power
				$item = "Zone3Power";
				$vtype = 0;
				$itemdata= true;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;
		}

		$ZoneCat_sub = substr($data,2,2);
		switch ($ZoneCat_sub)
		{
			case "MU": //Zone 3 Mute ON/OFF
				$item = "Zone3Mute";
				$vtype = 0;
				if ($data == "Z3MUOFF")
				{
					$value = false;
				}
				elseif ($data == "Z3MUON")
				{
					$value = true;
				}
				DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "CS": //Zone 3 Channel Setting MONO/STEREO
				 $item = "Zone3ChannelSetting";
				 $vtype = 1;
				 if ($data == "Z3CSST")
					{
						$value = 0;
					}
					elseif ($data == "Z3CSMONO")
					{
						$value = 1;
					}
				 DenonSetValue($item, $value, $vtype, $id);
				$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "CV": //Zone 3 Channel Volume
				$Z3CV_sub = substr($data,4,2);
				switch ($Z3CV_sub)
				{
					case "FL":
						$item = "Zone3ChannelVolumeFL";
						$vtype = 1;
						$itemdata=substr($data,7,2);
						$value = intval($itemdata) -50;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "FR":
						$item = "Zone3ChannelVolumeFR";
						$vtype = 1;
						$itemdata=substr($data,7,2);
						$value = intval($itemdata) -50;
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;
				}
			break;

					case "QU": //Zone 3 Quick Select
						 $item = "Zone3QuickSelect";
						 $vtype = 1;
						 if ($data == "Z3QUICK0")
							{
							$value = 0;
							}
						elseif ($data == "Z3QUICK1")
							{
							$value = 1;
							}
						elseif ($data == "Z3QUICK2")
							{
							$value = 2;
							}
						elseif ($data == "Z3QUICK3")
							{
							$value = 3;
							}
						elseif ($data == "Z3QUICK4")
							{
							$value = 4;
							}
						elseif ($data == "Z3QUICK5")
							{
							$value = 5;
							}
						$value = intval($value);
						DenonSetValue($item, $value, $vtype, $id);
						$log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
						break;
					default:
						$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
		   			break;
					}
				break;
			default:
				$log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
	   		break;
			}

		} /* ende foreach Denon Receiver */
	} /* ende else execute */

?>

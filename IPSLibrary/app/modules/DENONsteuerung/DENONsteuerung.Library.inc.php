<?
//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------

/*

Funktionen:
	*wird vom Script "DENON.Install_Library" in allen DENON-Variablen als Actionsript
		in den Variableneigenschaften der /data Variablen Einträge eingetragen
	* sendet (WFE-)Kommandos an das DENON.Functions-Script
*/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");

IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

/****************************************************************/


class DENONsteuerung
	{
	
	private $log_Denon;				// log Object Class
	private $CategoryIdApp;			// Kategorie für die Apps
	private $webconfig;				// Konfig für verkürzte Darstellung Webfront (Audio)
	private $configuration;			// Konfig für Denonverstaerker, hier sind alle Gerate angelegt
	
	private $Audio_Path;				// Webfront Path for Audio Tab
	
	public function __construct()
		{
		
		$this->webconfig=Denon_WebfrontConfig();
		$this->configuration=Denon_Configuration();


		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager))
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
			$moduleManager = new IPSModuleManager('DENONsteuerung',$repository);
			}

		$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
		$this->CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
		$scriptIdDENONsteuerung   = IPS_GetScriptIDByName('DENONsteuerung', $this->CategoryIdApp);
		$RemoteVis_Enabled    = $moduleManager->GetConfigValue('Enabled', 'RemoteVis');

		$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
		$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');

		$Audio_Enabled        = $moduleManager->GetConfigValue('Enabled', 'AUDIO');
		$this->Audio_Path        	 = $moduleManager->GetConfigValue('Path', 'AUDIO');		

		$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
		$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');

		$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');

		$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');

		$scriptIdDENONsteuerung   = IPS_GetScriptIDByName('DENONsteuerung', $this->CategoryIdApp);

		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);

		$object_data= new ipsobject($CategoryIdData);
		$object_app= new ipsobject($this->CategoryIdApp);

		$NachrichtenID = $object_data->osearch("Nachricht");
		$NachrichtenScriptID  = $object_app->osearch("Nachricht");

		if (isset($NachrichtenScriptID))
			{
			$object3= new ipsobject($NachrichtenID);
			$NachrichtenInputID=$object3->osearch("Input");
			//$object3->oprint();
			/* logging in einem File und in einem String am Webfront */
			$this->log_Denon=new Logging("C:\Scripts\Denon\Log_Control_Denon.csv",$NachrichtenInputID);
			}
		else break;
		}
	

	/* Vereinfachung des Logging, und zentrale Quelle */
	
	public function logMessage($text)
		{
		$this->log_Denon->LogMessage($text);
		}				

	public function logNachrichten($text)
		{
		$this->log_Denon->LogNachrichten($text);
		}				

	public function Configuration($device=null)
		{
		if ($device==null) return $this->configuration;
		else
			{
			if (isset($this->configuration[$device])==true) return $this->configuration[$device];
			else return false;
			}
		}    
	
	/* Aufgerufen wenn eine Variable im Webfront verändert wird */

	public function Activity($oid,$value)
		{
		$name=IPS_GetName(IPS_GetParent(IPS_GetParent($oid)));
		
		/* Wenn kein Clustering dann eine Ebene drueber nehmen */
		if ($name == "DENONsteuerung")  { $name=IPS_GetName(IPS_GetParent($oid)); }

		$found=false;
		foreach ($this->configuration as $Denon => $config)
			{
			if ($config['NAME']==$name)
				{
	 			$NameTag=$Denon;
				$instanz=$config['INSTANZ'];
				$found=true;
				}
			//print_r($config);
			}

		if ($_IPS['SENDER'] == "WebFront")
			{
			//echo "Script wurde über Webfront aufgerufen.\n";
			$this->log_Denon->LogMessage("Script wurde über Webfront von Variable ID :".$oid." aufgerufen.");
			$this->log_Denon->LogNachrichten("Script wurde über Webfront  von Variable ID :".$oid." aufgerufen.");
			}

		//echo "Input for Activity from : ".$oid."   Name : ".$name."   ".$NameTag."   ".$instanz."\n";
				
		if ($found==false)
			{
			$this->log_Denon->LogMessage("Instanz wurde nicht gefunden (AS)");
			$this->log_Denon->LogNachrichten("Instanz wurde nicht gefunden (AS)");
			IPSLogger_Dbg(__file__, "Denon: neue unbekannte Instanz, aufgerufen wurde von Webfront Name \"".$name."\"  ");
			break;
			}

		if ($name=='RemoteNetPlayer')
			{
			/* kleine Umleitung für Remote Netplayer */
			$this->log_Denon->LogNachrichten("Netplayer, Bearbeitung von Variable ID :".$oid.".");
			}
		else
			{

			if (IPS_GetObjectIDByName($instanz." Client Socket", 0) >0)
				{
				$id = IPS_GetObjectIDByName($instanz." Client Socket", 0);
				$this->log_Denon->LogMessage("ID Client Socket \"".$instanz." Client Socket\" ist ".$id." Variable ".$oid." Wert ".$value);
				//$this->log_Denon->LogNachrichten("ID Client Socket \"".$instanz." Client Socket\" ist ".$id." ");
				}
			else
				{
				//echo "die ID des DENON Client Sockets kann nicht ermittelt werden/n ->		Client Socket angelegt?/n Name richtig geschrieben (DENON Client Socket)?";
				$this->log_Denon->LogMessage("ID Client Socket \"".$instanz." Client Socket\" wurde nicht gefunden");
				$this->log_Denon->LogNachrichten("ID Client Socket \"".$instanz." Client Socket\" wurde nicht gefunden");
				}

			/* include DENON.Functions
			  	$id des DENON Client sockets muss nun selbst berechnet werden, war vorher automatisch
				*/

			if (IPS_GetObjectIDByName("DENON.Functions", $this->CategoryIdApp) >0)
				{
				include "DENON.Functions.ips.php";
				}
			else
				{
				echo "Script DENON.Functions kann nicht gefunden werden!";
				}

			SetValue($oid, $value);
			$VarName = IPS_GetName($oid);
			//$this->log_Denon->LogNachrichten("Bearbeitung von Variable : ".$VarName." (Switch).");
			//echo "Bearbeitung von Variable : ".$VarName." (Switch).\n";
			switch ($VarName)
				{
				############### Main Zone ################################################

				case "Power":
					/* input ist immer $oid, $id */
					if (getValueBoolean($oid) == false)
						{
						DENON_Power($id, "STANDBY");
						}
					else
						{
						DENON_Power($id, "ON");
						}
					break;

				case "DigitalInputMode":
					$DigitalInputMode_val = GetValueFormatted($oid);
					DENON_DigitalInputMode($id, $DigitalInputMode_val);
					break;

				case "InputSource":
					$InputSource_val = GetValueFormatted($oid);
					DENON_InputSource($id, $InputSource_val);
					break;

				case "AuswahlFunktion":
					$InputSource_val = GetValueFormatted($oid);
					$this->log_Denon->LogMessage("Denon Telegramm;Webfront;Auswahlfunktion;".$InputSource_val);
					IPSLogger_Dbg(__file__, "Denon: Umgeschaltet auf die neue Quelle \"".$InputSource_val."\"  ");

					//print_r($this->webconfig[$NameTag]['DATA']['AuswahlFunktion'][$InputSource_val]);

					if (isset($this->webconfig[$NameTag]['DATA']['AuswahlFunktion'][$InputSource_val])==true)
						{
						$InputSource_new=$this->webconfig[$NameTag]['DATA']['AuswahlFunktion'][$InputSource_val];
						DENON_InputSource($id, $InputSource_new);
						$this->log_Denon->LogNachrichten("Denon Telegramm;Webfront;Auswahlfunktion;".$InputSource_val." auf ".$InputSource_new." mit ".$NameTag);
						}
					break;

				case "InputMode":
					$InputMode_val = GetValueFormatted($oid);
					DENON_InputMode($id, $InputMode_val);
					break;

				case "RoomSize":
					DENON_RoomSize($id, $value);
					break;

				case "MainMute":
					$MainMute_val = GetValueFormatted($oid);
					DENON_MainMute($id, $MainMute_val);
					break;

				case "ToneCTRL":
					$ToneCTRL_val = GetValueFormatted($oid);
					DENON_ToneCTRL($id, $ToneCTRL_val);
					break;

				case "ToneDefeat":
			 		$ToneDefeat_val = GetValueFormatted($oid);
					DENON_ToneDefeat($id, $ToneDefeat_val);
					break;

				case "QuickSelect":
        			$QuickSelect_val = GetValueInteger($oid);
					DENON_Quickselect($id, $QuickSelect_val);
					break;

				case "VideoSelect":
        			$VideoSelect_val = GetValueFormatted($oid);
					DENON_VideoSelect($id, $VideoSelect_val);
					break;

				case "Panorama":
        			$Panorama_val = GetValueFormatted($oid);
					DENON_Panorama($id, $Panorama_val);
					break;

				case "FrontHeight":
        			$FrontHeight_val = GetValueFormatted($oid);
					DENON_FrontHeight($id, $FrontHeight_val);
					break;

				case "BassLevel":
					DENON_BassLevel($id, $value);
					break;

				case "LFELevel":
					DENON_LFELevel($id, $value);
					break;

		case "TrebleLevel":
			DENON_TrebleLevel($id, $value);
		break;

		case "DynamicEQ":
         $DynamicEQ_val = GetValueFormatted($oid);
			DENON_DynamicEQ($id, $DynamicEQ_val);
		break;

		case "DynamicCompressor":
         $DynamicCompressor_val = GetValueFormatted($oid);
			DENON_DynamicCompressor($id, $DynamicCompressor_val);
		break;

		case "DynamicVolume":
         DENON_DynamicVolume($id, $value);
		break;

		case "DynamicRange":
         $DynamicCompressor_val = GetValueFormatted($oid);
			DENON_DynamicCompressor($id, $DynamicCompressor_val);
		break;

		case "AudioDelay":
			DENON_AudioDelay($id, $value);
		break;

		case "AudioRestorer":
			DENON_AudioRestorer($id, $value);
		break;

		case "MasterVolume":
			//echo "Ausgabe MasterVolume : ".$value."  Client Socket ID : ".$id."\n";
			DENON_MasterVolumeFix($id, $value);
		break;

		case "C.Width":
			DENON_CWidth($id, $value);
		break;

		case "Dimension":
			DENON_Dimension($id, $value);
		break;

		case "SurroundMode":
         $SurroundMode_val = GetValueFormatted($oid);
			DENON_SurroundMode($id, $SurroundMode_val);
		break;

		case "SurroundPlayMode":
         $SurroundPlayMode_val = GetValueFormatted($oid);
			DENON_SurroundPlayMode($id, $SurroundPlayMode_val);
		break;

		case "SurroundBackMode":
         $SurroundBackMode_val = GetValueFormatted($oid);
			DENON_SurroundBackMode($id, $SurroundBackMode_val);
		break;

		case "Sleep":
			DENON_Sleep($id, $value);
		break;

		case "CinemaEQ":
         $CinemaEQ_val = GetValueFormatted($oid);
			DENON_CinemaEQ($id, $CinemaEQ_val);
		break;

		case "MainZonePower":
			$MainZonePower_val = GetValueFormatted($oid);
			DENON_MainZonePower($id, $MainZonePower_val);
		break;

		case "MultiEQMode":
         $MultiEQMode_val = GetValueFormatted($oid);
			DENON_MultiEQMode($id, $MultiEQMode_val);
		break;

		case "Preset":
         $Preset_val = GetValueInteger($oid);
			DENON_Preset($id, $Preset_val);
		break;

		case "ChannelVolumeFL":
			DENON_ChannelVolumeFL($id, $value);
		break;

		case "ChannelVolumeFR":
			DENON_ChannelVolumeFR($id, $value);
		break;

		case "ChannelVolumeC":
			DENON_ChannelVolumeC($id, $value);
		break;

		case "ChannelVolumeSW":
			DENON_ChannelVolumeSW($id, $value);
		break;

		case "ChannelVolumeSL":
			DENON_ChannelVolumeSL($id, $value);
		break;

		case "ChannelVolumeSR":
			DENON_ChannelVolumeSR($id, $value);
		break;

		case "ChannelVolumeSBL":
			DENON_ChannelVolumeSBL($id, $value);
		break;

		case "ChannelVolumeSBR":
			DENON_ChannelVolumeSBR($id, $value);
		break;

		case "ChannelVolumeSB":
			DENON_ChannelVolumeSB($id, $value);
		break;

		case "ChannelVolumeFHL":
			DENON_ChannelVolumeFHL($id, $value);
		break;

		case "ChannelVolumeFHR":
			DENON_ChannelVolumeFHR($id, $value);
		break;

		case "ChannelVolumeFWL":
			DENON_ChannelVolumeFWL($id, $value);
		break;

      case "ChannelVolumeFWR":
			DENON_ChannelVolumeFWR($id, $value);
		break;


		#################### Cursorsteuerung #####################################

		case "CursorUp":
			DENON_CursorUp($id);
		break;

		case "CursorDown":
			DENON_CursorDown($id);
		break;

		case "CursorLeft":
			DENON_CursorLeft($id);
		break;

		case "CursorRight":
			DENON_CursorRight($id);
		break;

		case "Enter":
			DENON_Enter($id);
		break;

		case "Return":
			DENON_Return($id);
		break;

		#################### Zone 2 ##############################################
      case "Zone2Power":
         $Zone2Power_val = GetValueFormatted($oid);
			DENON_Zone2Power($id, $Zone2Power_val);
		break;

		case "Zone2Volume":
			DENON_Zone2VolumeFix($id, $value);
		break;

		case "Zone2Mute":
			$Zone2Mute_val = GetValueFormatted($oid);
			DENON_Zone2Mute($id, $Zone2Mute_val);
		break;

		case "Zone2InputSource":
			$Zone2InputSource_val = GetValueFormatted($oid);
			DENON_Zone2InputSource($id, $Zone2InputSource_val);
		break;

		case "Zone2ChannelSetting":
			if (getValueBoolean($oid) == false)
			{
				DENON_Zone2ChannelSetting($id, "ST");
			}
			else
			{
				DENON_Zone2ChannelSetting($id, "MONO");
			}
		break;

		case "Zone2ChannelVolumeFL":
			DENON_Zone2ChannelVolumeFL($id, $value);
		break;

		case "Zone2ChannelVolumeFR":
			DENON_Zone2ChannelVolumeFL($id, $value);
		break;


		#################### Zone 3 ##############################################
      case "Zone3Power":
         $Zone3Power_val = GetValueFormatted($oid);
			DENON_Zone3Power($id, $Zone3Power_val);
		break;

		case "Zone3Volume":
			DENON_Zone3VolumeFix($id, $value);
		break;

		case "Zone3Mute":
			$Zone3Mute_val = GetValueFormatted($oid);
			DENON_Zone3Mute($id, $Zone3Mute_val);
		break;

		case "Zone3InputSource":
			$Zone3InputSource_val = GetValueFormatted($oid);
			DENON_Zone3InputSource($id, $Zone3InputSource_val);
		break;

		case "Zone3ChannelSetting":
			if (getValueBoolean($oid) == false)
			{
				DENON_Zone3ChannelSetting($id, "ST");
			}
			else
			{
				DENON_Zone3ChannelSetting($id, "MONO");
			}
		break;

		case "Zone3ChannelVolumeFL":
			DENON_Zone3ChannelVolumeFL($id, $value);
		break;

		case "Zone3ChannelVolumeFR":
			DENON_Zone3ChannelVolumeFL($id, $value);
		break;

				}
	
			} /* elseif RemoteNetplayer */
		}


	public function Command($instanzID, $data)
		{
		/* hier kommt zB DENON2 Register Variable, Register Variable wegtrennen und in Konfiguration suchen */
		$instanz=strstr(IPS_GetName($instanzID)," Register Variable",true);		
		$this->log_Denon->LogNachrichten("Daten von Instanz ".$instanzID." (".$instanz.") mit Wert ".$data." eingelangt (CM)");		
		foreach ($this->configuration as $Denon => $config)
			{
			/* jeder Denon Receiver ist wie folgt in configuration definiert. IP Adresse muss derzeit fix sein.
   	 		 *        'NAME'               => 'Denon-Wohnzimmer',
	    	 *        'IPADRESSE'          => '10.0.1.149',
	    	 *        'INSTANZ'          	=> 'DENON1'
			 *
			 * die ganze config durchgehen bis INSTANZ gleich ist mit der instanz die aus der übergebenen instanzID berechnet wurde
			 *
    		 */
			$found=false; 			
			if ($config['INSTANZ']==$instanz)
				{
	   			$id=$config['NAME'];
	   			$NameTag=$Denon;        /* der NameTag verbindet die beiden Tabellen */
				$found=true;
		   		}
			//print_r($config);
	
			if ($found==false)
				{
				//$this->log_Denon->LogMessage("Instanz ".$instanz." wurde nicht gefunden (CM)");
				//$this->log_Denon->LogNachrichten("Instanz ".$instanz." wurde nicht gefunden (CM)");
				}
			else
		   		{
				/* Konfigurierte Instanz passt mit der empfangenen Instanz zusammen */
				$this->log_Denon->LogMessage("Instanz ".$instanz." wurde gefunden (CM),Typ ".$config['TYPE']." Daten ".$data);
				//$this->log_Denon->LogNachrichten("Instanz ".$instanz." wurde gefunden (CM), Typ ".$config['TYPE']."\n");

				if ($config['TYPE'] == "Netplayer")
					{
	
					}
				if ($config['TYPE'] == "Denon")
					{				
					$maincat= substr($data,0,2); //Eventidentifikation
					$zonecat= substr($data,2); //Zoneneventidentifikation
					switch($maincat)
						{
						/* Eventidentifikation
						 *
						 * PW MV MU ZM SI SV MS DC SD SR SL VS PS NS CV Z2 Z3
						 *
						 */
						 
						/******
						 *
						 * DenonSetValue (VariablenManager)
						 *   item ist der Denon Name in der Datenbank, kann auch einen Präfix zB Zone2 enthalten
						 *   value der Wert
						 *   id
						 *   vtype
						 *   Webfront zB Visualization.WebFront.User.DENON
						 * setzen der Variablen in Data, 
						 * wenn erforderlich auch Anlegen der Umgebung in Data und im Visualization Webfront
						 *
						 * DenonSetValueAll übergibt zusätzlich die webconfig mit dem Nametag Eintrag. 
						 * NameTag ist der übergeordnete Identifier des Config Arrays zb Arbeitszimmer
						 * Funktion geht alle Einträge der Reihe nach durch [DATA,Visualization.WebFront.User.DENON,..] der Reihe
						 * nach durch und sieht ob es einen Alias für einen item Eintrag gibt
						 * Beispiel "MasterVolume" => "Volume"
						 *
						 *
						 *
						 ****************/ 

					/*---------------------------------------------------------------------------*/
					case "PW": //*** MainPower  PW [ON,OFF]-> Power [true,false]
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
							$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
						   	}
						DenonSetValueAll($this->webconfig[$NameTag], $item, $value, $vtype, $id);
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data.";".$NameTag);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
						break;

					/*---------------------------------------------------------------------------*/
					case "MV": //*** Mastervolume
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
							DenonSetValueAll($this->webconfig[$NameTag], $item, $value, $vtype, $id);
							$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata.";".$NameTag);
							$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
							}
					 	break;

					/*---------------------------------------------------------------------------*/
					case "MU": //*** MainMute
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
							$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
					   	}
						DenonSetValueAll($this->webconfig[$NameTag], $item, $value, $vtype, $id);
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
						break;

					/*---------------------------------------------------------------------------*/
					case "ZM": //*** MainZone
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
							$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
						   }
						DenonSetValueAll($this->webconfig[$NameTag], $item, $value, $vtype, $id);
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
						break;

					/*---------------------------------------------------------------------------*/
					case "EC": //*** Eco mode
						$item = "Ecomode";
						$value=substr($data,3); /* das O von ECO wird verschluckt */
						$vtype = 3;  /* String */
						DenonSetValue($item, $value, $vtype, $id);
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$value);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$value);
						break;

					/*---------------------------------------------------------------------------*/
					case "SI": //*** Source Input
						$item = "InputSource";
						$itemdata=substr($data,2);
						$vtype = 1;

						if (isset($this->webconfig[$NameTag]["DATA"]['AuswahlFunktion'])==true)
							{
							$profil=$this->webconfig[$NameTag]["DATA"]['AuswahlFunktion'];
							$profil_size=sizeof($profil);
							$i=0;
							$done=false;
							foreach ($profil as $name => $assoc)
								{
								/* Wenn ein Eintrag für Data und Auswahlfunktion besteht, dann alle Einträge durchgehen ob itemdata dabei ist */
								$i++;
								if ($itemdata==$assoc)
									{
									/* zB Tuner Befehl wurde empfangen auf Webfront Shortlist Audio Anzeige umsetzen */
									DenonSetValue("AuswahlFunktion",$i, 1, $id,$this->Audio_Path);
									/* zB 0, "VOID", 	1, "PC",	2, "XBOX",	3, "TUNER"			*/
									$done=true;
									}
								}
							if ($done==false)
								{
								/* wenn Befehl nicht bekannt ist dann auf VOID 0 setzen */
								DenonSetValue("AuswahlFunktion",0, 1, $id,$this->Audio_Path);
								}
							}

						if ($itemdata == "PHONO")
							{
							$value = 0;
							}
						elseif ($itemdata == "CD")
							{
							$value = 1;
							}
						elseif ($itemdata == "TUNER")
							{
							$value = 2;
							}
						elseif ($itemdata == "DVD")
							{
							$value = 3;
							}
						elseif ($itemdata == "BD")
							{
							$value = 4;
							}
						elseif ($itemdata == "TV")
							{
							$value = 5;
							}
						elseif ($itemdata == "SAT/CBL")
							{
							$value = 6;
							}
						elseif ($itemdata == "DVR")
							{
							$value = 7;
							}
						elseif ($itemdata == "GAME")
							{
							$value = 8;
							}
						elseif ($itemdata == "V.AUX")
							{
							$value = 9;
							}
						elseif ($itemdata == "DOCK")
							{
							$value = 10;
							}
						elseif ($itemdata == "IPOD")
							{
							$value = 11;
							}
						elseif ($itemdata == "NET/USB")
							{
							$value = 12;
							}
						elseif ($itemdata == "NAPSTER")
							{
							$value = 13;
							}
						elseif ($itemdata == "LASTFM")
							{
							$value = 14;
							}
						elseif ($itemdata == "FLICKR")
							{
							$value = 15;
							}
						elseif ($itemdata == "FAVORITES")
							{
							$value = 16;
							}
						elseif ($itemdata == "IRADIO")
							{
							$value = 17;
							}
						elseif ($itemdata == "SERVER")
							{
							$value = 18;
							}
						elseif ($itemdata == "USB/IPOD")
							{
							$value = 19;
							}
						elseif ($itemdata == "MPLAY")    /* new one */
							{
							$value = 20;
							}
						elseif ($itemdata == "NET")    /* new one */
							{
							$value = 21;
							}
						elseif ($itemdata == "IPOD DIRECT")
							{
							$value = 22;
							}
						elseif ($itemdata == "BT")
							{
							$value = 23;
							}
						elseif ($itemdata == "AUX2")
							{
							$value = 24;
							}
						else
						   {
							$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
						   }
						$value = intval($value);
						DenonSetValueAll($this->webconfig[$NameTag], $item, $value, $vtype, $id);
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata.";".$data);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata.";".$data);
						break;

					/*---------------------------------------------------------------------------*/
					case "SV": //*** Video Select
						$item = "VideoSelect";
						$itemdata=substr($data,2);
						$vtype = 1;
						if ($itemdata == "DVD")
							{
							$value = 0;
							}
						elseif ($itemdata == "BD")
							{
							$value = 1;
							}
						elseif ($itemdata == "TV")
							{
							$value = 2;
							}
						elseif ($itemdata == "SAT/CBL")
							{
							$value = 3;
							}
						elseif ($itemdata == "DVR")
							{
							$value = 4;
							}
						elseif ($itemdata == "GAME")
							{
							$value = 5;
							}
						elseif ($itemdata == "V.AUX")
							{
							$value = 6;
							}
						elseif ($itemdata == "DOCK")
							{
							$value = 7;
							}
						elseif ($itemdata == "SOURCE")
							{
							$value = 8;
							}
						elseif ($itemdata == "OFF") /* new one */
							{
							$value = 9;
							}
						else
							{
							$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
							}
						DenonSetValue($item, $value, $vtype, $id);
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata.";".$data);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata.";".$data);
						break;

					/*---------------------------------------------------------------------------*/
					case "MS": // Surround Mode und Quickselect
						if (substr($data,0,7) == "MSQUICK")
							{
							//Quickselect
							$item = "QuickSelect";
							$itemdata=substr($data,7);
							$vtype = 1;
							if (substr($data,0,7) == "MSQUICK")
								{
								$value = intval(substr($data,7,1));
								}
							DenonSetValue($item, $value, $vtype, $id);
							$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata.";".$data);
							$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata.";".$data);
							}
						else
							{
							//Surround Mode     DIRECT PURE   STEREO STANDARD DOLBY    DTS      MCH    ROCK  JAZZ MONO  MATRIX VIDEO VIRTUAL MULTI
							//                         DIRECT                 DIGITAL  SURROUND STEREO ARENA CLUB MOVIE        GAME          CH IN 7.1
							//                                                PL2X C   NEO:6 C
							//                                                PL2 C
							$item = "SurroundMode";
							$itemdata=substr($data,2);
							$vtype = 1;
							if ($itemdata == "DIRECT")
								{
								$value = 0;
								}
							elseif ($itemdata == "PURE DIRECT")
								{
								$value = 1;
								}
							elseif ($itemdata == "STEREO")
								{
								$value = 2;
								}
							elseif ($itemdata == "STANDARD")
								{
								$value = 3;
								}
							elseif ($itemdata == "DOLBY DIGITAL")
								{
								$value = 4;
								}
							elseif ($itemdata == "DTS SURROUND")
								{
								$value = 5;
								}
							elseif ($itemdata == "DOLBY PL2X C")
								{
								$value = 6;
								}
							elseif ($itemdata == "MCH STEREO")
								{
								$value = 7;
								}
							elseif ($itemdata == "ROCK ARENA")
								{
								$value = 8;
								}
							elseif ($itemdata == "JAZZ CLUB")
								{
								$value = 9;
								}
							elseif ($itemdata == "MONO MOVIE")
								{
								$value = 10;
								}
							elseif ($itemdata == "MATRIX")
								{
								$value = 11;
								}
							elseif ($itemdata == "VIDEO GAME")
								{
								$value = 12;
								}
							elseif ($itemdata == "VIRTUAL")
								{
								$value = 13;
								}
							elseif ($itemdata == "MULTI CH IN 7.1")
								{
								$value = 14;
								}
							elseif ($itemdata == "DTS NEO:6 C")
								{
								$value = 15;
								}
							elseif ($itemdata == "DOLBY PL2 C")
								{
								$value = 16;
								}
							elseif ($itemdata == "DTS NEO:6 M")
								{
								$value = 17;
								}
							else
								{
								$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
								}
							DenonSetValue($item, $value, $vtype, $id);
							$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata.";".$data);
							$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata.";".$data);
							}
						break;

					/*---------------------------------------------------------------------------*/
					case "DC": //*** Digital Input Mode
						$item = "DigitalInputMode";
						$itemdata=substr($data,2);
						$vtype = 1;
						if ($itemdata == "AUTO")
							{
							$value = 0;
							}
						elseif ($itemdata == "PCM")
							{
							$value = 1;
							}
						elseif ($itemdata == "DTS")
							{
							$value = 2;
							}
						else
							{
							$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
							}
						DenonSetValue($item, $value, $vtype, $id);
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata.";".$data);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata.";".$data);
						break;

					/*---------------------------------------------------------------------------*/
					case "SD": //*** Input Mode AUTO/HDMI/DIGITALANALOG/ARC/NO
						$item = "InputMode";
				$itemdata=substr($data,2);
				$vtype = 1;
				if ($itemdata == "AUTO")
					{
					$value = 0;
					}
				elseif ($itemdata == "HDMI")
					{
					$value = 1;
					}
				elseif ($itemdata == "DIGITAL")
					{
					$value = 2;
					}
				elseif ($itemdata == "ANALOG")
					{
					$value = 3;
					}
				elseif ($itemdata == "NO")
					{
					$value = 4;
					}
				else
				   {
					$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata.";".$data);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata.";".$data);
				break;

			/*---------------------------------------------------------------------------*/
			case "SR": //*** Record Selection
				$item = "RecordSelection";
				$vtype = 1;
				$itemdata=substr($data,2);
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
				break;

			/*---------------------------------------------------------------------------*/
			case "SS": //*** new Selection, unclear function
			   /*  Beispiele SSINFAISFSV NON   SSINFAISSIG 02                */
				$command=substr($data,2,3);
				if ($command=="INF")
				   {
					if (substr($data,5,6)=="AISFSV")
						{
						/* unclear */
						$item = "SSINFAISFSV";
						$itemdata=substr($data,10);
						}
					elseif (substr($data,5,6)=="AISSIG")
					   {
					   /* unclear */
						$item = "SSINFAISSIG";
						$itemdata=substr($data,10);
					   }
					else
					   {
						$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";SSINFAIS;".$data.";".$command);
					   }
					}
				elseif ($command=="SMG")
				   {
					$item = "SS SMG";
					$itemdata=substr($data,7);
					}
				elseif ($command=="CMP")
				   {
					$item = "SS CMP";
					$itemdata=substr($data,7);
					}
				elseif ($command=="HDM")
				   {
					$item = "SS HDM";
					$itemdata=substr($data,7);
					}
				elseif ($command=="ANA")
				   {
					$item = "SS ANA";
					$itemdata=substr($data,7);
					}
				elseif ($command=="VDO")
				   {
					$item = "SS VDO";
					$itemdata=substr($data,7);
					}
				elseif ($command=="DIN")
				   {
					$item = "SS DIN";
					$itemdata=substr($data,7);
					}
				elseif ($command=="TPS")
				   {
					$item = "SS TPS";
					$itemdata=substr($data,7);
					}
				elseif ($command=="TPN")
				   {
					$item = "SS TPN";
					$itemdata=substr($data,7);
					}
				else
				   {
					$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";SS;".$data.";".$command);
				   }
				$vtype = 3;  /* String */
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
					$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				/* kein Logging da dauernd die RDS Daten übertragen werden */
				DenonSetValue($item, $value, $vtype, $id);
				break;
				
			/*---------------------------------------------------------------------------*/
			case "TP": //*** new selection 
				$command=substr($data,2,2);
				if ($command=="AN")
				   {
					$itemdata=substr($data,4);
					$item = "TP AN";
					$vtype = 3;  /* String */
					$value = $itemdata;
					}
				else
				   {
					$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				/* kein Logging da dauernd die RDS Daten übertragen werden */
				DenonSetValue($item, $value, $vtype, $id);
				break;				

			/*---------------------------------------------------------------------------*/
			case "TM": //*** new selection 
				$command=substr($data,2,2);
				if ($command=="AN")
				   {
					$itemdata=substr($data,4);
					$item = "TM AN";
					$vtype = 3;  /* String */
					$value = $itemdata;
					}
				else
				   {
					$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
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
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
						break;

					case "SC": //Scaler
						$item = "Scaler";
						$vtype = 3;
						$itemdata=substr($data,4);
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
						//$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
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
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
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
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
			break;

			case "RO": //Room EQ Mode
				$item = "RoomEQMode";
				$vtype = 3;
				$itemdata=substr($data,10);
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
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
					$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
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
					$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
			break;

			case "DI": //Balance zwischen Front und Surround-LS
				$item = "Dimension";
				$vtype = 1;
				$itemdata=substr($data, 6, 2);
				$value = (int)$itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "CE": //Center-Signal Verteilung auf FrontR/L
				$item = "C.Width";
				$vtype = 1;
				$itemdata=substr($data, 6, 2);
				$value = (int)$itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
					$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
					DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
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
					$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
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
					$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
				   }
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
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
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
			break;

			case "LF": //LFE Pegel
				$item = "LFELevel";
				$vtype = 2;
				$itemdata=substr($data, 6, 2);
				$value = (0 - intval($itemdata));
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				//$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			 break;

			case "BA": //Bass Pegel
				$item = "BassLevel";
				$vtype = 1;
				$itemdata=substr($data, 6, 2);
				$value = (intval($itemdata)) -50;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				//$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			 break;

			case "TR": //Treble Pegel
				$item = "TrebleLevel";
				$vtype = 2;
				$itemdata=substr($data,6, 2);
				$value = (intval($itemdata)) -50;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				//$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "DE": //Audio Delay 0-200ms
				$item = "AudioDelay";
				$vtype = 1;
				$itemdata=substr($data,8, 3);
				$value = intval($itemdata);
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$data);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$data);
					break;
				}
			break;
		}
	break;

			/*---------------------------------------------------------------------------*/
			case "PV": //new command, unclear function
			   /*  Beispiele                 */
				$command=substr($data,2,3);
				$item = "PV";
				$itemdata=substr($data,7);
				$vtype = 3;  /* String */
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "E8": // Zeile 9
						$item = "Displcurrent Position";
						$vtype = 3;
						$itemdata = rtrim(substr($data, 4, 95));
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$currentPosition = $itemdata = substr($data, 7, 1);
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata.";".$data);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata.";".$data);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "A8": // Zeile 9
						$item = "Displcurrent Position";
						$vtype = 3;
						$itemdata = $ProfilValue = rtrim(substr($data, 5, 95));
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype, $id);
						$currentPosition = $itemdata = substr($data, 7, 1);
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "FR":
				$item = "ChannelVolumeFR";
				$vtype = 2;
				$itemdata = substr($data,5,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "C ":
				$item = "ChannelVolumeC";
				$vtype = 2;
				$itemdata=substr($data,4,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "SW":
				$item = "ChannelVolumeSW";
				$vtype = 2;
				$itemdata=substr($data,5,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "SL":
				$item = "ChannelVolumeSL";
				$vtype = 2;
				$itemdata=substr($data,5,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "SR":
				$item = "ChannelVolumeSR";
				$vtype = 2;
				$itemdata=substr($data,5,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;
		}
	break;


############### Zone 2 #########################################################

			case "Z2":
			   /* für alle Zone2 Befehle gilt dieser prefix */
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
					DenonSetValueAll($this->webconfig[$NameTag], $item, $value, $vtype, $id);
					$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
					$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					}

		switch ($zonecat)
		{
			case "PHONO": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 0;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "CD": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 1;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "TUNER": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 2;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "DVD": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 3;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "BD": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 4;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "TV": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 5;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "SAT/CBL": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 6;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "DVR": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 7;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "GAME": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 8;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "V.AUX": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 9;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "DOCK": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 10;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "IPOD": //Source Input Z3 (AVR 3809)
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 11;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "NET/USB":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 12;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "NAPSTER":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 13;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "LASTFM":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 14;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "FLICKR":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 15;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "FAVORITES":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 16;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "IRADIO":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 17;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "SERVER":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 18;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "USP/IPOD":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 19;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "OFF": //Zone 2 Power
				$item = "Zone2Power";
				$vtype = 0;
				$itemdata= false;
				$value = $itemdata;
				DenonSetValueAll($this->webconfig[$NameTag], $item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "ON": //Zone 3 Power
				$item = "Zone2Power";
				$vtype = 0;
				$itemdata= true;
				$value = $itemdata;
				DenonSetValueAll($this->webconfig[$NameTag], $item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
					$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
					$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
			$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
			$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
		}

		switch ($zonecat)
		{
			case "PHONO": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 0;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "CD": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 1;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "TUNER": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 2;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "DVD": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 3;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "HDP": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 4;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "TV/CBL": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 5;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "SAT": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 6;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "VCR": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 7;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "DVR": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 8;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "V.AUX": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 9;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "NET/USB": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 10;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "USB": //Source Input Z3 (AVR 3809)
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 11;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "USB/IPOD": //Source Input Z3 (AVR 3311)
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 13;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "OFF": //Zone 3 Power
				$item = "Zone3Power";
				$vtype = 0;
				$itemdata= false;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
			break;

			case "ON": //Zone 3 Power
				$item = "Zone3Power";
				$vtype = 0;
				$itemdata= true;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype, $id);
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
				$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
				$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
					break;

					case "FR":
						$item = "Zone3ChannelVolumeFR";
						$vtype = 1;
						$itemdata=substr($data,7,2);
						$value = intval($itemdata) -50;
						DenonSetValue($item, $value, $vtype, $id);
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
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
						$this->log_Denon->LogMessage("Denon Telegramm;".$id.";".$item.";".$itemdata);
						$this->log_Denon->LogNachrichten("Denon Telegramm;".$id.";".$item.";".$itemdata);
						break;
					default:
						$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
		   			break;
					}
				break;
			default:
				$this->log_Denon->LogMessage("Unbekanntes Telegramm;".$id.";".$data);
	   		break;
			}
				
					}			
				}
			}	
		}

	} // ende class
	
	
					
?>
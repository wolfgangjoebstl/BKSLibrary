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
	 
	/**@defgroup Sprachsteuerung
	 *
	 * Script um Sprache auszugeben, soweit umgebaut dass es egal ist ob die Sprache 
	 *
     *  lokal über einen IP Symcon Mediaplayer ausgegeben wird
     *  remote über einen IP Symcon Mediaplayer eines anderen Servers 
     *  über eine Alexa Instanz
     *
     * legt in System ein eigenes Tab an um Infos zu geben was gerade gesprochen wird. Wenn Silent Mode ein ist erfolgt keine Ausgabe.
     * mit den Alexas kann auch ein Radiosender von TuneIn abgespielt werden, Modul EchoControl ist erforderlich
     *
     * verwendet tts_play, function ist im OperationCenter und in der Sparachsteuerung Library definiert.
     * üblicherweise kann die Funktion im OperationCenter verwendet werden. Die eigene Library nur einbinden wenn kein OperationCenter Modul installiert wurde
     *
     * benötigt IPSModule:
     *      OperationCenter, EvaluateHardware
     *
     * benötigt Libraries:
     *
     *
     * benötigt Module aus dem Store:
     *
	 *
	 * @file          Sprachsteuerungung_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

    $testAusgabe=false;

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Sprachsteuerung\Sprachsteuerung_Configuration.inc.php");

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Sprachsteuerung',$repository);
		}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	echo "\nKernelversion : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "\nIPS Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetModuleState();
	echo " ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "\nIPSModulManager Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Sprachsteuerung');
	echo "\nSprachsteuerung Version : ".$ergebnis."\n";

 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,20)." ".$modules."\n";
		}
    //echo $inst_modules;

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf-Sprachsteuerung',   $CategoryIdData, 20);
	$Nachricht_inputID = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );

	$scriptIdSprachsteuerung   = IPS_GetScriptIDByName('Sprachsteuerung', $CategoryIdApp);
    $scriptIdAction            = IPS_GetScriptIDByName('Sprachsteuerung_Actionscript', $CategoryIdApp);

    echo "ScriptId Spachsteuerung ".$scriptIdSprachsteuerung." Action ".$scriptIdAction." und Nachrichten Input ".$Nachricht_inputID."\n";

    $html="";
    $html .= '<style>';
    $html .= 'table.ovw { border:solid 5px #006CFF; margin:0px; padding:0px; border-spacing:0px; border-collapse:collapse; line-height:22px; font-size:13px;'; 
    $html .= ' font-family:Arial, Verdana, Tahoma, Helvetica, sans-serif; font-weight:400; text-decoration:none; color:white; white-space:pre-wrap; }';
    $html .= 'table.ovw th { padding: 2px; background-color:#98dcff; border:solid 2px #006CFF; }';
    $html .= 'table.ovw td { padding: 2px; border:solid 1px grey; }';
    $html .= 'table.head tr { margin:0; padding:4px; }';
    $html .= '.quick.green {border:solid green; color:green;}';
    $html .= '';
    $html .= '</style>';
    $html .= '<table class="ovw">';

	if ( (isset($installedModules["Sprachsteuerung"]) )  && ($installedModules["Sprachsteuerung"] <>  "") ) 
        {
        echo "Modul Sprachsteuerung richtig installiert. Wird von tts_play des OperationCenter verwendet.\n";
		$config=Sprachsteuerung_Configuration();
		if ( (isset($config["RemoteAddress"])) && (isset($config["ScriptID"])) )  
            { 
            $url=$config["RemoteAddress"]; 
            $oid=$config["ScriptID"];
			$rpc = new JSONRPC($url);
			//$monitor=array("Text" => $ansagetext);
			//$rpc->IPS_RunScriptEx($oid,$monitor);            
            $scriptName=$rpc->IPS_GetName($oid);
            echo "    Verwendet die Sprachsteuerung des Servers $url und ruft dort dieses Script $oid auf.\n";
            $html .= '<tr><td colspan="2">Verwendet eine remote Sprachsteuerung:</td></tr>';            
            $html .= '<tr><td style="text-align:right">Server</td><td>'.$url.'</td></tr>';
            $html .= '<tr><td style="text-align:right">Script</td><td>'.$oid.'</td></tr>';
            if ( ($scriptName=="Sprachsteuerung_Actionscript") || ($scriptName=="Sprachsteuerung") ) 
                {
                $html .= '<tr><td style="text-align:right">Scriptname</td><td>'.$scriptName.'</td></tr>';
                $html .= '<tr><td style="text-align:right">Status</td><td>Test vom '.date("d.m.Y H:i:s").' ok</td></tr>';
                }
            else 
                {
                echo " Scriptname unknown $scriptName.\n";
                $html .= '<tr><td style="text-align:right">Status</td><td>Test vom '.date("d.m.Y H:i:s").' NOK !!!!!!</td></tr>';
                }
            }
        }
    else
        {       // lokale Sprachsteuerung, Statusausgabe, Sprachausgabe nur dann durchführen wenn IPS Modul Sprachsteuerung installiert ist und das Script Sprachsteuerung vorhanden ist.
        echo "Sprache lokal ausgeben.\n";
        $id_sk1_musik = IPS_GetInstanceIDByName("MP Musik", $scriptIdSprachsteuerung);
        $id_sk1_ton = IPS_GetInstanceIDByName("MP Ton", $scriptIdSprachsteuerung);
        $id_sk1_tts = IPS_GetInstanceIDByName("Text to Speach", $scriptIdSprachsteuerung);
        $id_sk1_musik_status = IPS_GetVariableIDByName("Status", $id_sk1_musik);
        $id_sk1_musik_vol = IPS_GetVariableIDByName("Lautstärke", $id_sk1_musik);
        $id_sk1_ton_status = IPS_GetVariableIDByName("Status", $id_sk1_ton);
        $id_sk1_counter = CreateVariable("Counter", 1, $scriptIdSprachsteuerung , 0, "",0,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */
        $html .= '<tr><td colspan="2">Verwendet die lokale Sprachsteuerung:</td></tr>';            
        $html .= '<tr><td style="text-align:right">MP-Musik</td><td>'.$id_sk1_musik.'</td></tr>';
        $html .= '<tr><td style="text-align:right">MP-Ton</td><td>'.$id_sk1_ton.'</td></tr>';
        $html .= '<tr><td style="text-align:right">Text-To-Speach</td><td>'.$id_sk1_tts.'</td></tr>';
        }
	//echo $inst_modules;

    $ipsOps = new ipsOps();
    $dosOps = new dosOps();
	$modulhandling = new ModuleHandling();    
	$wfcHandling = new WfcHandling();

	if (isset($installedModules["OperationCenter"]))
		{
		IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
		IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');		
		$subnet="10.255.255.255";
		$OperationCenter=new OperationCenter($subnet);		
		//echo "Modul OperationCenter ist installiert.\n";
		$results=$OperationCenter->SystemInfo();
		$result=trim(substr($results["Betriebssystemversion"],0,strpos($results["Betriebssystemversion"]," ")));
		$Version=explode(".",$result)[2];
		echo "Win10 Betriebssystemversion : ".$Version."\n";
		}
	
	// ----------------------------------------------------------------------------------------------------------------------------
	// Init
	// ----------------------------------------------------------------------------------------------------------------------------

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

    $configWFront=$ipsOps->configWebfront($moduleManager,false);     // wenn true mit debug Funktion
    //print_r($configWFront);

	// ----------------------------------------------------------------------------------------------------------------------------
	// Data
	// ----------------------------------------------------------------------------------------------------------------------------

    /* mögliche Actions hier aufsetzen */
    $pname="Test";
	if (IPS_VariableProfileExists($pname) == true)
        {
        IPS_DeleteVariableProfile($pname);
        }    
	if (IPS_VariableProfileExists($pname) == false)
		{
		//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
		IPS_SetVariableProfileValues($pname, 1, 3, 1); //PName, Minimal, Maximal, Schrittweite
		IPS_SetVariableProfileAssociation($pname, 1, "Sprechen", "", 0xf13c1e); //P-Name, Value, Association, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 2, "Radio", "", 0x3cf11e); //P-Name, Value, Association, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 3, "Pause", "", 0x3c1ef1); //P-Name, Value, Association, Icon, Color
		echo "Profil ".$pname." erstellt;\n";
		}
    //$profile=IPS_GetVariableProfile ($pname);
    //echo "Alle Profileinträge für $pname danach anzeigen:\n";
    //print_r($profile);

	//$echos=$modulhandling->getInstances('EchoRemote');

    $echos = array();
	if (isset($installedModules["EvaluateHardware"]))
		{
		IPSUtils_Include ("Hardware_Library.inc.php","IPSLibrary::app::modules::EvaluateHardware");
        IPSUtils_Include ("EvaluateHardware_DeviceList.inc.php","IPSLibrary::config::modules::EvaluateHardware");              // umgeleitet auf das config Verzeichnis, wurde immer irrtuemlich auf Github gestellt
        $hardware = new Hardware();
        $deviceListFiltered = $hardware->getDeviceListFiltered(deviceList(),["Type" => "EchoControl", "TYPEDEV" => "TYPE_LOUDSPEAKER"],true);
        echo "Ausgabe aller Geräte mit Type EchoControl und TYPEDEV TYPE_LOUDSPEAKER:\n";
        foreach ($deviceListFiltered as $name => $device) 
            {
            echo "   ".str_pad($name,35)."    ".$device["Instances"][0]["OID"]."\n";
            $echos[]=$device["Instances"][0]["OID"];
            }
        }


    /* Liste mit allen gefunden Echo Lautsprechern erstellen */

    $pname="Echo-Speaker";
	if (IPS_VariableProfileExists($pname) == true)
        {
        IPS_DeleteVariableProfile($pname);
        }

	if (IPS_VariableProfileExists($pname) == false)
		{
		//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
		//IPS_SetVariableProfileValues($pname, 1, 1, 1); //PName, Minimal, Maximal, Schrittweite

        // boolean IPS_SetVariableProfileAssociation (string $ProfilName, float $Wert, string $Name, string $Icon, integer $Farbe)
        $color=0x113c1e; $inc=4;
        IPS_SetVariableProfileAssociation($pname, 1, "Default", "",($color+$inc)  ); //P-Name, Value, Association, Icon, Color
        foreach ($echos as $echo)
            {
            IPS_SetVariableProfileAssociation($pname, $echo, IPS_GetName($echo), "",($color+$inc) ); //P-Name, Value, Assotiation, Icon, Color
            $inc *= 2;
            }

        //echo "Alle Profileinträge für $pname danach anzeigen:\n";
        //$profile=IPS_GetVariableProfile ($pname);
        //print_r($profile);

		echo "Profil ".$pname." erstellt;\n";
    	}

    if (isset($echos[0]))
        {
        echo "Alle in der Alexa programmierten TuneIN Radiostationen:\n";
        $Configurations = json_decode(IPS_GetConfiguration($echos[0]),true);
        $TuneInstations = json_decode($Configurations["TuneInStations"],true);
        print_r($TuneInstations);
        }
    else
        {
        echo "Es sind keine Alexa vorhanden um die programmierten TuneIN Radiostationen auszulesen:\n";
        $TuneInstations=array();
        $Configurations=array();
        $Configurations["TuneInStations"]="";
        }

    $pname="TuneInStations";
	if (IPS_VariableProfileExists($pname) == true)
        {
        IPS_DeleteVariableProfile($pname);
        }    
	if (IPS_VariableProfileExists($pname) == false)
		{
		//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
		//IPS_SetVariableProfileValues($pname, 1, 2, 1); //PName, Minimal, Maximal, Schrittweite
        $inc = 8;
		foreach ($TuneInstations as $station)
            {
            IPS_SetVariableProfileAssociation($pname, $station["position"], $station["station"], "", 0xf13c1e+$inc); //P-Name, Value, Association, Icon, Color
            $inc *=8;
            }
		//IPS_SetVariableProfileAssociation($pname, 2, "Radio", "", 0x3cf11e); //P-Name, Value, Association, Icon, Color
		echo "Profil ".$pname." erstellt;\n";
		}
    //echo "Alle Profileinträge für $pname danach anzeigen:\n";
    //$profile=IPS_GetVariableProfile ($pname);
    //print_r($profile);

	$categoryId_Auswertungen    = CreateCategory('Auswertungen',   $CategoryIdData, 20);
    //CreateVariable($Name,$type,$parentid, $position,$profile,$Action,$default,$icon );
	$TestnachrichtID        = CreateVariable("Testnachricht",3,$categoryId_Auswertungen, 0, "",$scriptIdAction,null,""  );
    $ButtonID               = CreateVariable("Button",1,$categoryId_Auswertungen, 10, "Test",$scriptIdAction,null,""  );
    $SelectID               = CreateVariable("SelectSpeaker",1,$categoryId_Auswertungen, 20, "Echo-Speaker",$scriptIdAction,null,""  );
    $TuneInStationConfig    = CreateVariable("TuneInStationConfig",3,$categoryId_Auswertungen, 2000, "",null,null,""  );
    $TuneInStation          = CreateVariable("TuneInStation",1,$categoryId_Auswertungen, 30, "TuneInStations",$scriptIdAction,null,""  );
    $SelectedStationId      = CreateVariable("SelectedStationId",3,$categoryId_Auswertungen, 2010, "",null,null,""  );    
    $InfoBoxID              = CreateVariable("InfoBox",3,$categoryId_Auswertungen, 40, "~HTMLBox",null,null,""  );                              //InfoBox über Konfiguration

    SetValue($TuneInStationConfig,$Configurations["TuneInStations"]);

    $html .= '</table>';
    SetValue($InfoBoxID,$html);

	//$listinstalledmodules=IPS_GetModuleList();
	//print_r($listinstalledmodules);
	//$moduleProp=IPS_GetModule("{2999EBBB-5D36-407E-A52B-E9142A45F19C}");
	//print_r($moduleProp);

	/* Verzeichnis für die wav Files von der Sprahausgabe erstellen */

	$FilePath = IPS_GetKernelDir()."media/wav/";
	if (!file_exists($FilePath)) 
		{
		echo "Verzeichnis wav in media erstellen.\n"; 
		if (!mkdir($FilePath, 0755, true)) {
			throw new Exception('Create Directory '.$destinationFilePath.' failed!');
			}
		}	

	// ----------------------------------------------------------------------------------------------------------------------------
	// Configuration
	// ----------------------------------------------------------------------------------------------------------------------------
	
	//Alle Modulnamen mit GUID ausgeben
	foreach(IPS_GetModuleList() as $guid)
		{
		$module = IPS_GetModule($guid);
		if (IPS_ModuleExists($guid)==true) 
			{
			$result=IPS_GetInstanceListByModuleID($guid);
			if ( sizeof($result)>0 )
				{
				$pair[$module['ModuleName']] = $guid;
				//echo $guid."\n";
				//print_r($result);
				//print_r($module);
				}
			}
		}
	ksort($pair);
	$usedModules="\nAlle verwendeten Module:\n";
	foreach($pair as $key=>$guid)
		{
		$usedModules.="   ".$key." = ".$guid."\n";
		}
	//echo $usedModules;	


	echo "\n\nAlle SmartHome Module:\n";
	print_r(IPS_GetInstanceListByModuleID("{3F0154A4-AC42-464A-9E9A-6818D775EFC4}"));

	echo "Alle Mediaplayermodule:\n";
	$MediaPlayerModule=IPS_GetInstanceListByModuleID("{2999EBBB-5D36-407E-A52B-E9142A45F19C}");
	foreach ($MediaPlayerModule as $oid)
		{
		echo "    ".$oid."  (".IPS_GetName($oid).")\n";
		}
	echo "\n".IPS_GetName($oid)." Konfiguration : \n";	
	$result=IPS_GetConfigurationForm($oid);		
	print_r($result);
	echo "--------------------\n";
	$ergebnis=IPS_GetProperty($oid,"DeviceName");
	print_r($ergebnis);
	echo "--------------------\n";	
	$json = json_decode($result,true);
	echo "\n";
	var_dump($json);
	
	echo "\nAlle Text-to-Speech Module:\n";
	print_r(IPS_GetInstanceListByModuleID("{684CC410-6777-46DD-A33F-C18AC615BB94}"));

	$SmartHomeID = @IPS_GetInstanceIDByName("IQL4SmartHome", 0);
	if ($SmartHomeID >0 )
		{
		echo "Smart Home Instanz ist auf ID : ".$SmartHomeID."\n";
		$config=IPS_GetConfiguration($SmartHomeID);
		echo $config;
		echo "\n\n";
		}
	
	$MediaPlayerMusikID = @IPS_GetInstanceIDByName("MP Musik", $scriptIdSprachsteuerung);

   if(!IPS_InstanceExists($MediaPlayerMusikID))
      {
      $MediaPlayerMusikID = IPS_CreateInstance("{2999EBBB-5D36-407E-A52B-E9142A45F19C}"); // Mediaplayer anlegen
	   IPS_SetName($MediaPlayerMusikID, "MP Musik");
		IPS_SetParent($MediaPlayerMusikID,$scriptIdSprachsteuerung);
		IPS_SetProperty($MediaPlayerMusikID,"DeviceNum",1);
		IPS_SetProperty($MediaPlayerMusikID,"DeviceName","Lautsprecher (Realtek High Definition Audio)");
		IPS_SetProperty($MediaPlayerMusikID,"UpdateInterval",0);
		IPS_SetProperty($MediaPlayerMusikID,"DeviceDriver","{0.0.0.00000000}.{eb1c82a1-4bdf-4072-b886-7e0ca86e26e3}");
		IPS_ApplyChanges($MediaPlayerMusikID);
		/*
		DeviceNum integer 0
		DeviceName string
		UpdateInterval integer 0
		DeviceDriver string
		*/
		}
	$MediaPlayerTonID = @IPS_GetInstanceIDByName("MP Ton", $scriptIdSprachsteuerung);

	if(!IPS_InstanceExists($MediaPlayerTonID))
    	{
      	$MediaPlayerTonID = IPS_CreateInstance("{2999EBBB-5D36-407E-A52B-E9142A45F19C}"); // Mediaplayer anlegen
	   	IPS_SetName($MediaPlayerTonID, "MP Ton");
		IPS_SetParent($MediaPlayerTonID,$scriptIdSprachsteuerung);
		IPS_SetProperty($MediaPlayerTonID,"DeviceNum",1);
		IPS_SetProperty($MediaPlayerTonID,"DeviceName","Lautsprecher (Realtek High Definition Audio)");
		IPS_SetProperty($MediaPlayerTonID,"UpdateInterval",0);
		IPS_SetProperty($MediaPlayerTonID,"DeviceDriver","{0.0.0.00000000}.{eb1c82a1-4bdf-4072-b886-7e0ca86e26e3}");
		IPS_ApplyChanges($MediaPlayerTonID);
		/*
		DeviceNum integer 0
		DeviceName string
		UpdateInterval integer 0
		DeviceDriver string
		*/
		}
	$TextToSpeachID = @IPS_GetInstanceIDByName("Text to Speach", $scriptIdSprachsteuerung);

	if(!IPS_InstanceExists($TextToSpeachID))
    	{
    	$TextToSpeachID = IPS_CreateInstance("{684CC410-6777-46DD-A33F-C18AC615BB94}"); // Mediaplayer anlegen
		IPS_SetName($TextToSpeachID, "Text to Speach");
		IPS_SetParent($TextToSpeachID,$scriptIdSprachsteuerung);
		IPS_SetProperty($TextToSpeachID,"TTSAudioOutput","Lautsprecher (Realtek High Definition Audio)");
		//IPS_SetProperty($TextToSpeachID,"TTSEngine","Microsoft Hedda Desktop - German");
		//IPS_SetProperty($TextToSpeachID,"TTSEngine","Microsoft Anna - English (United States)");
		//IPS_SetProperty($TextToSpeachID,"TTSEngine","ScanSoft Steffi_Dri40_16kHz");
		$SprachConfig=Sprachsteuerung_Configuration();
		IPS_SetProperty($TextToSpeachID,"TTSEngine",$SprachConfig["Engine".$SprachConfig["Language"]]);
		IPS_ApplyChanges($TextToSpeachID);
		/*
		TTSAudioOutput string
		TTSEngine string
		*/
		}
	$SprachCounterID = CreateVariable("Counter", 1, $scriptIdSprachsteuerung , 0, "",0,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */

	//print_r(IPS_GetStatusVariableIdents($MediaPlayerID));

	echo "TTSAudioOutput :".IPS_GetProperty($TextToSpeachID,"TTSAudioOutput")."\n";
	echo "TTSEngine :".IPS_GetProperty($TextToSpeachID,"TTSEngine")."\n";
	echo "DeviceName :".IPS_GetProperty($MediaPlayerTonID,"DeviceName")."\n";
	echo "DeviceNum :".IPS_GetProperty($MediaPlayerTonID,"DeviceNum")."\n";
	echo "UpdateInterval :".IPS_GetProperty($MediaPlayerTonID,"UpdateInterval")."\n";
	echo "DeviceDriver :".IPS_GetProperty($MediaPlayerTonID,"DeviceDriver")."\n";
	echo "DeviceName :".IPS_GetProperty($MediaPlayerMusikID,"DeviceName")."\n";
	echo "DeviceNum :".IPS_GetProperty($MediaPlayerMusikID,"DeviceNum")."\n";
	echo "UpdateInterval :".IPS_GetProperty($MediaPlayerMusikID,"UpdateInterval")."\n";
	echo "DeviceDriver :".IPS_GetProperty($MediaPlayerMusikID,"DeviceDriver")."\n";
	
	/*******************************
	 *
	 * Links für Webfront identifizieren
	 *
	 *
	 *
	 ********************************/

	$webfront_links=array(
		"AmazonEcho" => array(
			"Auswertung" => array(
				$TestnachrichtID => array(
						"NAME"				=> "Sprachausgabe",
						"ORDER"				=> 10,
						"ADMINISTRATOR" 	=> true,
						"USER"				=> false,
						"MOBILE"			=> false,
							),
				$ButtonID => array(
						"NAME"				=> "Test",
						"ORDER"				=> 20,
						"ADMINISTRATOR" 	=> true,
						"USER"				=> false,
						"MOBILE"			=> false,
							),	   
				$SelectID => array(
						"NAME"				=> "Select Loudspeaker",
						"ORDER"				=> 30,
						"ADMINISTRATOR" 	=> true,
						"USER"				=> false,
						"MOBILE"			=> false,
							),
                $TuneInStation => array(
						"NAME"				=> "Select Radiostation",
						"ORDER"				=> 40,
						"ADMINISTRATOR" 	=> true,
						"USER"				=> false,
						"MOBILE"			=> false,
							),                            							
                $InfoBoxID => array(
						"NAME"				=> "Info Box",
						"ORDER"				=> 100,
						"ADMINISTRATOR" 	=> true,
						"USER"				=> false,
						"MOBILE"			=> false,
							),   
    					),
			"Nachrichten" => array(
				$Nachricht_inputID => array(
						"NAME"				=> "Nachrichten",
						"ORDER"				=> 10,
						"ADMINISTRATOR" 	=> true,
						"USER"				=> false,
						"MOBILE"			=> false,
							),
						),					
					),	
				); 
					
	// ----------------------------------------------------------------------------------------------------------------------------
	// WebFront Installation
	// ----------------------------------------------------------------------------------------------------------------------------

    if (isset($configWFront["Administrator"]))
        {
        $configWF = $configWFront["Administrator"];
        installWebfrontSprach($configWF,$webfront_links,"Administrator");
        }

    if (isset($configWFront["User"]))
        {
        $configWF = $configWFront["User"];
        installWebfrontSprach($configWF,$webfront_links,"User");
        }


    /* test, Sprachausgabe
     *
     ***************************************************************************************/
    if ($testAusgabe)     
        {
        echo "\n\n--------Test Sprachsteuerungsausgabe:-----------\n";
        tts_play(1,'Sprachsteuerung Modul und Library installiert.','',2);
        tts_play(1,'Test Default Lautsprecher','',2);
        $echos=$modulhandling->getInstances('EchoRemote');
        foreach ($echos as $echo)
            {
            tts_play($echo,'Test Echo '.IPS_GetName($echo).' Lautsprecher','',2);
            }
        }


    function installWebfrontSprach($configWF,$webfront_links, $scope)
        {
	    $wfcHandling = new WfcHandling();            
        echo "installWebfrontSprach aufgerufen.\n";
        if ( !((isset($configWF["Enabled"])) && ($configWF["Enabled"]==false)) )   
            {
            if ( (isset($configWF["Path"])) )
                {
                $categoryId_WebFront         = CreateCategoryPath($configWF["Path"]);        // Path=Visualization.WebFront.User/Administrator/Mobile.WebCamera
                echo "Webfront für ".IPS_GetName($categoryId_WebFront)." ($categoryId_WebFront) Kategorie im Pfad ".$configWF["Path"]." erstellen.\n";
                echo "Kategorie $categoryId_WebFront (".IPS_GetName($categoryId_WebFront).") Inhalt loeschen und verstecken. Es dürfen keine Unterkategorien enthalten sein, sonst nicht erfolgreich.\n";
                $status=@EmptyCategory($categoryId_WebFront);
                if ($status) echo "   -> erfolgreich.\n";                
		        IPS_SetHidden($categoryId_WebFront, true); //Objekt verstecken

                $wfcHandling->setupWebfront($webfront_links,$configWF["TabPaneParent"],$categoryId_WebFront, $scope);

                }
            }

        }


?>
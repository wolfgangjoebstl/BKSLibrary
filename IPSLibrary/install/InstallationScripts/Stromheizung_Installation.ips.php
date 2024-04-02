<?php
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
	 
	/**@defgroup Stromheizung
	 *
	 * Script um elektrische Heizung nachzusteuern, Fork von IPSLight
     * macht das selbe also Schalten, kann aber auch Temperaturen, also Werte verändern
	 * Installiert das Webfront entsprechend einem Config
     * für Mobile gibt es eine kurze vereinfachte Variante
     *
     *
	 *
	 * @file          Stromheizung_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/


	/**************************************************************
	 *
	 *  Zuerst wird für jedes Gerät, Gruppe, Programm die benötigten Spiegelregister erstellt.
	 *  das heisst für einen Dimmer auch ein Objekt für den Level oder bei einem Thermostat auch ein Objekt für die Temperatur und den Mode  
	 *
	 * Installiert je nach Konfiguration ein Webfront für den Administrator, User und Mobilanwender
	 *
	 *
	 *
	 **************************************************************/

	IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');    

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Stromheizung',$repository);
		}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	echo "\nIP Symcon Kernelversion    : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "\nIPS ModulManager Version   : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Stromheizung');
	echo "\nModul Stromheizung Version : ".$ergebnis."   Status : ".$moduleManager->VersionHandler()->GetModuleState()."\n";

    $startexec=microtime(true);

/*******************************
 *
 * INIT
 *
 ********************************/

 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.="  ".str_pad($name,20)." ".$modules."\n";
		}
	echo $inst_modules."\n";
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	IPSUtils_Include('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');	
	
	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");

	IPSUtils_Include ("IPSComponentHeatControl_FS20.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentHeatControl");
	
	IPSUtils_Include ('StromheizungLib.class.php', 'IPSLibrary::app::modules::Stromheizung');
	IPSUtils_Include ("IPSHeat.inc.php",                "IPSLibrary::app::modules::Stromheizung");
	IPSUtils_Include ("IPSHeat_Constants.inc.php",      "IPSLibrary::app::modules::Stromheizung");
	IPSUtils_Include ("Stromheizung_Configuration.inc.php",  "IPSLibrary::config::modules::Stromheizung");	

    $ipsOps = new ipsOps();    
    $webOps = new webOps();                     // Buttons anlegen
    $profileOps = new profileOps();             // Profile verwalten

/*******************************
 *
 * Variablen Profile Vorbereitung
 *
 ********************************/

	echo "Darstellung der Variablenprofile im lokalem Bereich, wenn fehlt anlegen:\n";
	$profilname=array("mode.HM"=>"update");
    $profileOps->synchronizeProfiles($profilname);

/*******************************
 *
 * wichtige Kategorien anlegen, werden für wfcHandling benötigt
 *
 ********************************/

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$categoryIdSwitches = CreateCategory('Switches', $CategoryIdData, 10);
	$categoryIdGroups   = CreateCategory('Groups',   $CategoryIdData, 20);
	$categoryIdPrograms = CreateCategory('Programs', $CategoryIdData, 30);

/*******************************
 *
 * Webfront Vorbereitung, hier werden keine Webfronts mehr installiert, nur mehr konfigurierte ausgelesen
 *
 ********************************/

	
    $wfcHandling =  new WfcHandling();
    $wfcHandling->get_WfcStatus();
    $WebfrontConfigID = $wfcHandling->installWebfront();

    /*echo "\n";
	$WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
	echo "Default WFC10_ConfigId, wenn nicht definiert : ".IPS_GetName($WFC10_ConfigId)."  (".$WFC10_ConfigId.")\n\n";
	
	$WebfrontConfigID=array();
	$alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
	foreach ($alleInstanzen as $instanz)
		{
		$result=IPS_GetInstance($instanz);
		$WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
		echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."  (".$instanz.")\n";
		}
	echo "\n"; */
	
/*******************************
 *
 * Webfront Konfiguration einlesen
 
[RemoteVis]
Enabled=false

[WFC10]
Enabled=true
Path=Visualization.WebFront.Administrator.Stromheizung
TabPaneItem=HeatTPA
TabPaneParent=roottp
TabPaneName=
TabPaneOrder=500
TabPaneIcon=Temperature
TabPaneExclusive=false
TabItem=Details
TabName="Lautsprecher"
TabIcon=
TabOrder=20

[WFC10User]
Enabled=true
Path=Visualization.WebFront.User.Stromheizung
TabPaneItem=HeatTPU
TabPaneParent=roottp
TabPaneName=
TabPaneOrder=500
TabPaneIcon=Temperature
TabPaneExclusive=false
TabItem=Details
TabName="Lautsprecher"
TabIcon=
TabOrder=20

[Mobile]
Enabled=true
Path=Visualization.Mobile.Stromheizung

[Retro]
Enabled=false
Path=Visualization.Mobile.Stromheizung 
 
 *
 ********************************/	
	
    $configWFront=$ipsOps->configWebfront($moduleManager,false);     // wenn true mit debug Funktion
	
    $RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);

	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	$WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
	$Mobile_Enabled       = $moduleManager->GetConfigValueDef('Enabled', 'Mobile',false);
	$Retro_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Retro',false);

    $ipsOps->writeConfigWebfrontAll($configWFront);

    /*
	if ($WFC10_Enabled==true)
		{
		$WFC10_ConfigId       = $WebfrontConfigID["Administrator"];
		$WFC10_Path           = $moduleManager->GetConfigValue('Path', 'WFC10');
		$WFC10_TabPaneItem    = $moduleManager->GetConfigValueDef('TabPaneItem', 'WFC10',"HeatTPA");
		$WFC10_TabPaneParent  = $moduleManager->GetConfigValueDef('TabPaneParent', 'WFC10',"roottp");
		$WFC10_TabPaneName    = $moduleManager->GetConfigValueDef('TabPaneName', 'WFC10',"");
		$WFC10_TabPaneIcon    = $moduleManager->GetConfigValueDef('TabPaneIcon', 'WFC10',"Temperature");
		$WFC10_TabPaneOrder   = $moduleManager->GetConfigValueDef('TabPaneOrder', 'WFC10',300);
		$WFC10_TabItem        = $moduleManager->GetConfigValueDef('TabItem', 'WFC10',"");
		$WFC10_TabName        = $moduleManager->GetConfigValueDef('TabName', 'WFC10',"");
		$WFC10_TabIcon        = $moduleManager->GetConfigValueDef('TabIcon', 'WFC10',"");
		$WFC10_TabOrder       = $moduleManager->GetConfigValueDef('TabOrder', 'WFC10',"");
		echo "WF10 Administrator\n";
		echo "  Path          : ".$WFC10_Path."\n";
		echo "  ConfigID      : ".$WFC10_ConfigId."  (".IPS_GetName(IPS_GetParent($WFC10_ConfigId)).".".IPS_GetName($WFC10_ConfigId).")\n";		
		echo "  TabPaneItem   : ".$WFC10_TabPaneItem."\n";
		echo "  TabPaneParent : ".$WFC10_TabPaneParent."\n";
		echo "  TabPaneName   : ".$WFC10_TabPaneName."\n";
		echo "  TabPaneIcon   : ".$WFC10_TabPaneIcon."\n";
		echo "  TabPaneOrder  : ".$WFC10_TabPaneOrder."\n";
		echo "  TabItem       : ".$WFC10_TabItem."\n";
		echo "  TabName       : ".$WFC10_TabName."\n";
		echo "  TabIcon       : ".$WFC10_TabIcon."\n";
		echo "  TabOrder      : ".$WFC10_TabOrder."\n";
		}

	echo "\n";


	if ($WFC10User_Enabled==true)
		{
		$WFC10User_ConfigId       = $WebfrontConfigID["User"];
		$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');
		$WFC10User_TabPaneItem    = $moduleManager->GetConfigValueDef('TabPaneItem', 'WFC10User',"HeatTPU");
		$WFC10User_TabPaneParent  = $moduleManager->GetConfigValueDef('TabPaneParent', 'WFC10User',"roottp");
		$WFC10User_TabPaneName    = $moduleManager->GetConfigValueDef('TabPaneName', 'WFC10User',"");
		$WFC10User_TabPaneIcon    = $moduleManager->GetConfigValueDef('TabPaneIcon', 'WFC10User',"Temperature");
		$WFC10User_TabPaneOrder   = $moduleManager->GetConfigValueDef('TabPaneOrder', 'WFC10User',300);
		$WFC10User_TabItem        = $moduleManager->GetConfigValueDef('TabItem', 'WFC10User',"");
		$WFC10User_TabName        = $moduleManager->GetConfigValueDef('TabName', 'WFC10User',"");
		$WFC10User_TabIcon        = $moduleManager->GetConfigValueDef('TabIcon', 'WFC10User',"");
		$WFC10User_TabOrder       = $moduleManager->GetConfigValueIntDef('TabOrder', 'WFC10User',"");
		echo "WF10 User \n";
		echo "  Path          : ".$WFC10User_Path."\n";
		echo "  ConfigID      : ".$WFC10User_ConfigId."  (".IPS_GetName(IPS_GetParent($WFC10User_ConfigId)).".".IPS_GetName($WFC10User_ConfigId).")\n";
		echo "  TabPaneItem   : ".$WFC10User_TabPaneItem."\n";
		echo "  TabPaneParent : ".$WFC10User_TabPaneParent."\n";
		echo "  TabPaneName   : ".$WFC10User_TabPaneName."\n";
		echo "  TabPaneIcon   : ".$WFC10User_TabPaneIcon."\n";
		echo "  TabPaneOrder  : ".$WFC10User_TabPaneOrder."\n";
		echo "  TabItem       : ".$WFC10User_TabItem."\n";
		echo "  TabName       : ".$WFC10User_TabName."\n";
		echo "  TabIcon       : ".$WFC10User_TabIcon."\n";
		echo "  TabOrder      : ".$WFC10User_TabOrder."\n";
		}		


	if ($Mobile_Enabled==true)
		{	
		$Mobile_Path          = $moduleManager->GetConfigValue('Path', 'Mobile');
		$mobile_PathOrder     = $moduleManager->GetConfigValueInt('PathOrder', 'Mobile');
		$mobile_PathIcon      = $moduleManager->GetConfigValue('PathIcon', 'Mobile');		
		echo "Mobile \n";
		echo "  Path          : ".$Mobile_Path."\n";
		echo "  TabPaneIcon   : ".$mobile_PathIcon."\n";
		echo "  TabPaneOrder  : ".$mobile_PathOrder."\n";				
		}

	if ($Retro_Enabled==true)
		{	
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		echo "Retro \n";
		echo "  Path          : ".$Retro_Path."\n";		
		}       */
		
	$WFC10_Regenerate     = false;			// true, das Webfront im Konfigurator neu aufbauen
	$mobile_Regenerate    = true;			// das mobile Webfront nicht neu aufbauen
			
	// ----------------------------------------------------------------------------------------------------------------------------
	// Program Installation
	// ----------------------------------------------------------------------------------------------------------------------------

    $moduleManagerCC      = new IPSModuleManager('CustomComponent',$repository);
	$CategoryIdDataCC     = $moduleManagerCC->GetModuleCategoryID('data');

    echo "\n";
    echo "Alle Kategorien von CustomComponent anzeigen:\n";         // Auswertungen werden in ein Register Array gespeichert aber es passiert vorerst nichts
    $customComponentCategories=array();
	$Category=IPS_GetChildrenIDs($CategoryIdDataCC);
	foreach ($Category as $CategoryId)
		{
		echo "  ID : ".$CategoryId." Name : ".IPS_GetName($CategoryId)."\n";
		$Params = explode("-",IPS_GetName($CategoryId)); 
        if ( (sizeof($Params)>1) && ($Params[1]=="Auswertung") )
            {
            $customComponentCategories[$Params[0]]=$CategoryId;
            }
		}
	//print_r($customComponentCategories);

	// Add Scripts
	$scriptIdActionScript  = IPS_GetScriptIDByName('IPSHeat_ActionScript', $CategoryIdApp);
	
    echo "\n";
	echo "Action Script hat OID: ".$scriptIdActionScript."\n";
	
	/* ===================================================================================================
	 * Add Heat or Light Devices
     *
     * for switches and groups
     *
     * in der data categorie immer für alle children hide machen und nur mehr die auch wirklich in der Konfigiuration sind wieder unhiden 
     * Am Ende müssen eine Menge Objekte gelöscht werden.
     *
	 * ===================================================================================================   */

    echo "\n";
    echo "Create Mirror Variables for Switches, dependent on Type create one or more objects.\n";
    $childs=IPS_GetChildrenIDs($categoryIdSwitches);
    foreach ($childs as $child) IPS_SetHidden($child,true);         // alle Switches hide
	$idx = 100;
	$lightConfig = IPSHeat_GetHeatConfiguration();                  // Switches Configuration, bekannte Switched wieder unhide
	//print_r($lightConfig);
	foreach ($lightConfig as $deviceName=>$deviceData) 
		{
		$deviceType = $deviceData[IPSHEAT_TYPE];
        echo "   $deviceName $deviceType\n";
		switch ($deviceType) 
			{
			//case IPSLIGHT_TYPE_SWITCH:
			case IPSHEAT_TYPE_SWITCH:			
				$switchId = CreateVariable($deviceName,    0 /*Boolean*/, $categoryIdSwitches,  $idx, '~Switch', $scriptIdActionScript, false, 'Bulb');
                IPS_SetHidden($switchId,false);
				break;
			//case IPSLIGHT_TYPE_DIMMER:
			case IPSHEAT_TYPE_DIMMER:			
				$switchId = CreateVariable($deviceName,                       0 /*Boolean*/, $categoryIdSwitches,  $idx, '~Switch',        $scriptIdActionScript, false, 'Bulb');
				$levelId  = CreateVariable($deviceName.IPSHEAT_DEVICE_LEVEL, 1 /*Integer*/, $categoryIdSwitches,  $idx, '~Intensity.100', $scriptIdActionScript, false, 'Intensity');
                IPS_SetHidden($switchId,false); IPS_SetHidden($levelId,false);
				break;
			//case IPSLIGHT_TYPE_RGB:
			case IPSHEAT_TYPE_RGB:
				$switchId = CreateVariable($deviceName,                       0 /*Boolean*/, $categoryIdSwitches,  $idx, '~Switch',        $scriptIdActionScript, false, 'Bulb');
				$colorId  = CreateVariable($deviceName.IPSHEAT_DEVICE_COLOR, 1 /*Integer*/, $categoryIdSwitches,  $idx, '~HexColor',      $scriptIdActionScript, false, 'HollowDoubleArrowRight');
				$levelId  = CreateVariable($deviceName.IPSHEAT_DEVICE_LEVEL, 1 /*Integer*/, $categoryIdSwitches,  $idx, '~Intensity.100', $scriptIdActionScript, false, 'Intensity');
                IPS_SetHidden($switchId,false); IPS_SetHidden($colorId,false); IPS_SetHidden($levelId,false);
                //echo "   suche Variable \"".$deviceName.IPSHEAT_DEVICE_AMBIENCE."\"\n";
                $miredId  = @IPS_GetObjectIDByName($deviceName.IPSHEAT_DEVICE_AMBIENCE,$categoryIdSwitches);
                if ($miredId!==false) 
                    {
                    echo "    -> Variable \"".$deviceName.IPSHEAT_DEVICE_AMBIENCE."\", die nicht mehr benötigt wird, gefunden. Jetzt loeschen.\n";
                    IPS_DeleteVariable($miredId);
                    }                
				break;
			case IPSHEAT_TYPE_AMBIENT:
				$switchId = CreateVariable($deviceName,                       0 /*Boolean*/, $categoryIdSwitches,  $idx, '~Switch',        $scriptIdActionScript, false, 'Bulb');
				$miredId  = CreateVariable($deviceName.IPSHEAT_DEVICE_AMBIENCE, 1 /*Integer*/, $categoryIdSwitches,  $idx, 'ColorTemperatureSelect.Hue',      $scriptIdActionScript, false, 'HollowDoubleArrowRight');
				$levelId  = CreateVariable($deviceName.IPSHEAT_DEVICE_LEVEL, 1 /*Integer*/, $categoryIdSwitches,  $idx, '~Intensity.100', $scriptIdActionScript, false, 'Intensity');
                IPS_SetHidden($switchId,false); IPS_SetHidden($miredId,false); IPS_SetHidden($levelId,false);
                //echo "   suche Variable \"".$deviceName.IPSHEAT_DEVICE_COLOR."\"\n";
                $colorId  = @IPS_GetObjectIDByName($deviceName.IPSHEAT_DEVICE_COLOR,$categoryIdSwitches);
                if ($colorId!==false) 
                    {
                    echo "    -> Variable \"".$deviceName.IPSHEAT_DEVICE_COLOR."\", die nicht mehr benötigt wird, gefunden. Jetzt loeschen.\n";
                    IPS_DeleteVariable($colorId);
                    }
				break;
			case IPSHEAT_TYPE_SET:
				$switchId = CreateVariable($deviceName,                       0 /*Boolean*/, $categoryIdSwitches,  $idx, '~Switch',        $scriptIdActionScript, false, 'Bulb');
				$tempId  = CreateVariable($deviceName.IPSHEAT_DEVICE_TEMPERATURE, 2 /*Float*/, $categoryIdSwitches,  $idx, '~Temperature.HM', $scriptIdActionScript, false, 'Temperature');
				$modeId  = CreateVariable($deviceName.IPSHEAT_DEVICE_MODE, 1 /*Integer*/, $categoryIdSwitches,  $idx, 'mode.HM', $scriptIdActionScript, false, 'Title');
                IPS_SetHidden($switchId,false); IPS_SetHidden($tempId,false); IPS_SetHidden($modeId,false);                
				break;
			default:
				trigger_error('Unknown DeviceType '.$deviceType.' found for Heat or Light '.$devicename);
			}
		$idx = $idx + 1;
		}	

	// ===================================================================================================
	// Add Groups
	// ===================================================================================================

    echo "\n";
    echo "Create Mirror Variables for Groupes, dependent on Type create one or more objects.\n";
    /* cleanup unused variables */
    $childs=IPS_GetChildrenIDs($categoryIdGroups);
    foreach ($childs as $child) IPS_SetHidden($child,true);         // alle Gruppen Hide

	$idx = 100;
	$groupConfig = IPSHeat_GetGroupConfiguration();             // Gruppenkonfiguration, bearbeitete Gruppen wieder unhide
	foreach ($groupConfig as $groupName=>$groupData) 
		{
		if ( Isset($groupData[IPSHEAT_TYPE]) )
			{
			$groupType = $groupData[IPSHEAT_TYPE];		
            echo "   $groupName $groupType\n";                
			switch ($groupType) 
				{
                case IPSHEAT_TYPE_RGB:                    
					$switchId = CreateVariable($groupName,                       0 /*Boolean*/, $categoryIdGroups,  $idx, '~Switch',        $scriptIdActionScript, false, 'Bulb');
					$colorId  = CreateVariable($groupName.IPSHEAT_DEVICE_COLOR, 1 /*Integer*/, $categoryIdGroups,  $idx, '~HexColor', $scriptIdActionScript, false, 'HollowDoubleArrowRight');
					$levelId  = CreateVariable($groupName.IPSHEAT_DEVICE_LEVEL, 1 /*Integer*/, $categoryIdGroups,  $idx, '~Intensity.100', $scriptIdActionScript, false, 'Intensity');
                    IPS_SetHidden($switchId,false); IPS_SetHidden($colorId,false); IPS_SetHidden($levelId,false);                
                    break;
                case IPSHEAT_TYPE_AMBIENT:                    
					$switchId = CreateVariable($groupName,                       0 /*Boolean*/, $categoryIdGroups,  $idx, '~Switch',        $scriptIdActionScript, false, 'Bulb');
					$miredId  = CreateVariable($groupName.IPSHEAT_DEVICE_AMBIENCE, 1 /*Integer*/, $categoryIdGroups,  $idx, 'ColorTemperatureSelect.Hue', $scriptIdActionScript, false, 'HollowDoubleArrowRight');
					$levelId  = CreateVariable($groupName.IPSHEAT_DEVICE_LEVEL, 1 /*Integer*/, $categoryIdGroups,  $idx, '~Intensity.100', $scriptIdActionScript, false, 'Intensity');
                    IPS_SetHidden($switchId,false); IPS_SetHidden($miredId,false); IPS_SetHidden($levelId,false);                
                    break;
                case IPSHEAT_TYPE_DIMMER:                    
					$switchId = CreateVariable($groupName,                       0 /*Boolean*/, $categoryIdGroups,  $idx, '~Switch',        $scriptIdActionScript, false, 'Bulb');
					$levelId  = CreateVariable($groupName.IPSHEAT_DEVICE_LEVEL, 1 /*Integer*/, $categoryIdGroups,  $idx, '~Intensity.100', $scriptIdActionScript, false, 'Intensity');
                    IPS_SetHidden($switchId,false); IPS_SetHidden($levelId,false);                
                    break;
				case IPSHEAT_TYPE_SET:
					$switchId = CreateVariable($groupName,                       0 /*Boolean*/, $categoryIdGroups,  $idx, '~Switch',        $scriptIdActionScript, false, 'Bulb');
					$tempId  = CreateVariable($groupName.IPSHEAT_DEVICE_TEMPERATURE, 2 /*Float*/, $categoryIdGroups,  $idx, '~Temperature.HM', $scriptIdActionScript, false, 'Temperature');
					$modeId  = CreateVariable($groupName.IPSHEAT_DEVICE_MODE, 1 /*Integer*/, $categoryIdGroups,  $idx, 'mode.HM', $scriptIdActionScript, false, 'Title');
                    IPS_SetHidden($switchId,false); IPS_SetHidden($tempId,false); IPS_SetHidden($modeId,false);                
					break;
				default:
					$switchId     = CreateVariable($groupName,    0 /*Boolean*/, $categoryIdGroups,  $idx, '~Switch', $scriptIdActionScript, false, 'Bulb');
                    IPS_SetHidden($switchId,false);
					break;								
				}
			}	
		$idx = $idx + 1;
		}

	// ===================================================================================================
	// Add Programs
	// ===================================================================================================
	$idx = 10;
	$programConfig = IPSHeat_GetProgramConfiguration();
	foreach ($programConfig as $programName=>$programData) 
		{
		$itemIdx = 0;
		$programAssociations = array();
		foreach ($programData as $programItemName=>$programItemData) 
			{
			$programAssociations[]=$programItemName;
			}
		CreateProfile_Associations ('IPSHeat_'.$programName, $programAssociations, "ArrowRight");
		$programId = CreateVariable($programName, 1 /*Integer*/, $categoryIdPrograms,  $idx,  'IPSLight_'.$programName, $scriptIdActionScript, 0);
		$idx = $idx + 1;
		}

	/***********************************************************************************************
	 * Register Events for Device Synchronization
	 *
	 * noch fertig machen, synchronisiert noch nicht die Temperaturwerte wenn am Thermostat geaendert wurde !
	 * check final dann auch fuer IPSLight, da hier auch nicht mehr vollstaendig implementiert 
	 *
	 ***************************************************************************/
	 
	echo "\nRegister Events für Device Synchronization.\n";
	 
	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	$messageHandler = new IPSMessageHandler();
	$lightConfig = IPSHeat_GetHeatConfiguration();
	foreach ($lightConfig as $deviceName=>$deviceData) 
		{
		//echo "   Bearbeite ".$deviceName."\n";
		$component = $deviceData[IPSHEAT_COMPONENT];
		$componentParams = explode(',', $component);
		$componentClass = $componentParams[0];
		echo "   Bearbeite ".$deviceName." mit ComponentClass : ".$componentClass."\n";				
		switch ($componentClass)
			{
			case 'IPSComponentSwitch_LCNa':
				$instanceId = IPSUtil_ObjectIDByPath($componentParams[1]);
				$variableId = @IPS_GetObjectIDByIdent('Intensity', $instanceId);
				if ($variableId===false) 
					{
					$moduleManager->LogHandler()->Log('Variable with Ident Intensity could NOT be found for LCN Instance='.$instanceId);
					} 
				else 
					{
					$moduleManager->LogHandler()->Log('Register OnChangeEvent vor LCN Instance='.$instanceId);
					$messageHandler->RegisterOnChangeEvent($variableId, $component, 'IPSModuleSwitch_IPSLight,');
					}
				break;	
			case 'IPSComponentSwitch_LCN':
				$instanceId = IPSUtil_ObjectIDByPath($componentParams[1]);
				$variableId = @IPS_GetObjectIDByIdent('Status', $instanceId);
				if ($variableId===false) 
					{
					$moduleManager->LogHandler()->Log('Variable with Ident Status could NOT be found for LCN Instance='.$instanceId);
					} 
				else 
					{
					$moduleManager->LogHandler()->Log('Register OnChangeEvent vor LCN Instance='.$instanceId);
					$messageHandler->RegisterOnChangeEvent($variableId, $component, 'IPSModuleSwitch_IPSLight,');
					}
				break;			
			case 'IPSComponentSwitch_EIB':
				$instanceId = IPSUtil_ObjectIDByPath($componentParams[1]);
				$variableId = @IPS_GetObjectIDByIdent('Value', $instanceId);
				if ($variableId===false) 
					{
					$moduleManager->LogHandler()->Log('Variable with Ident Value could NOT be found for EIB Instance='.$instanceId);
					} 
				else 
					{
					$moduleManager->LogHandler()->Log('Register OnChangeEvent vor EIB Instance='.$instanceId);
					$messageHandler->RegisterOnChangeEvent($variableId, $component, 'IPSModuleSwitch_IPSLight,');
					}			
				break;	
			case 'IPSComponentSwitch_Homematic';
			case 'IPSComponentSwitch_RHomematic';
				$instanceId = IPSUtil_ObjectIDByPath($componentParams[1]);
				$variableId = @IPS_GetObjectIDByIdent('STATE', $instanceId);
				if ($variableId===false) 
					{
					$moduleManager->LogHandler()->Log('Variable with Name STATE could NOT be found for Homematic Instance='.$instanceId);
					} 
				else 
					{
					$moduleManager->LogHandler()->Log('Register OnChangeEvent vor Homematic Instance='.$instanceId);
					$messageHandler->RegisterOnChangeEvent($variableId, $component, 'IPSModuleSwitch_IPSHeat,');
					}
				break;	
			case 'IPSComponentHeatSet_Homematic':
			case 'IPSComponentHeatSet_HomematicIP':
			case 'IPSComponentHeatSet_FS20':	
				//print_r($componentParams);								
				$instanceId = IPSUtil_ObjectIDByPath($componentParams[1]);
				$variableId = @IPS_GetObjectIDByIdent('SET_TEMPERATURE', $instanceId);
				if ( isset($componentParams[2]) )
					{
					echo "Register IPSComponentHeatSet_Homematic : ".$instanceId."     ".$variableId."       ".$componentParams[2]."\n";
					}
				else
					{	
					echo "Register IPSComponentHeatSet_Homematic : ".$instanceId."   (".IPS_GetName($instanceId).")     ".$variableId."\n";
					}
				if ($variableId===false) 
					{
					$moduleManager->LogHandler()->Log('Variable with Name STATE could NOT be found for Homematic Instance='.$instanceId);
					} 
				else 
					{
					$moduleManager->LogHandler()->Log('Register OnChangeEvent vor Homematic Instance='.$instanceId);
					$messageHandler->RegisterOnChangeEvent($variableId, $component, 'IPSModuleHeatSet_All,');
					}
				break;
			case 'IPSComponentHeatSet_Data':
			case 'IPSComponentTuner_Denon':
			case 'IPSComponentSwitch_RFS20':
			case 'IPSComponentSwitch_Value':
			case 'IPSComponentRGB_PhilipsHUE':		// alte Component mit Direktansteuerung von HUE
			case 'IPSComponentRGB_HUE':			    // neuere Variante
			case 'IPSComponentRGB_PHUE':	        // finale Variante Philips HUE mit Discovery Funktion
			case 'IPSComponentDimmer_Homematic':
			case 'IPSComponentShutter_XHomematic':			
			case 'IPSComponentSwitch_RMonitor':
			case 'IPSComponentRGB_LW12':
				break;					
			default:
				trigger_error('Unknown ComponentType '.$componentClass.' found for Heat or Light '.$deviceName.' cannot register.');			
				break;
			}
		}

	/***********************************************************************************************
	 * Register Data area for Thermostat Configuration Handling 
	 *
     ***************************************************************************************************/

	$HMXML_DataPath='Program.IPSLibrary.data.hardware.IPSHomematic.ThermostatConfig';
	$categoryId_hmxml = CreateCategoryPath($HMXML_DataPath);

	/****************************************************************************
	 *
	 * Webfront Installation der Stromheizung in der Autosteuerung
	 *
	 * 
	 *
	 *****************************************************************************************/

	$webFrontConfig = IPSHeat_GetWebFrontConfiguration();
	
	/* nur die Heizungstellwerte bei der Autosteuerung, Tab Stromheizung dazuhaengen */ 

	$WFC10_Autosteuerung_Path='Visualization.WebFront.Administrator.Autosteuerung.Stromheizung.AutoTPADetails2_LeftDown';
	if ($WFC10_Enabled) 
		{
        $config = $configWFront["Administrator"];            
		$categoryId_Autosteuerung_WebFront                = CreateCategoryPath($WFC10_Autosteuerung_Path);
		if ($WFC10_Regenerate) 
			{
			/* Loescht die Stromheizung ein/aus und die anderen Variablen auch, eigenes Tab left down machen  */
			EmptyCategory($categoryId_Autosteuerung_WebFront);
			}		
		echo "Auch in Autosteuerung Stromheizung die Links installieren : ".$categoryId_Autosteuerung_WebFront."\n";
		$order = 10;
		foreach($webFrontConfig as $tabName=>$tabData) {
			foreach($tabData as $WFCItem) {
				$order = $order + 10;
				switch($WFCItem[0]) 
					{
					case IPSHEAT_WFCSPLITPANEL:
					case IPSHEAT_WFCCATEGORY:
					case IPSHEAT_WFCGROUP:
						break;
					case IPSHEAT_WFCLINKS:
						echo "  WFCLINKS : ".$WFCItem[2]."   ".$WFCItem[3]."\n";
						//print_r($WFCItem);
						$links      = explode(',', $WFCItem[3]);
						$names      = $links;
						if (array_key_exists(4, $WFCItem)) { $names = explode(',', $WFCItem[4]); 	}
						foreach ($links as $idx=>$link) 
							{
							$order = $order + 1;
							$name=explode('#', $names[$idx]);
							if (isset($name[1])==true) 
								{ 
								// CreateLinkByDestination ($Name, $LinkChildId, $ParentId, $Position, $ident="")
								//echo "GetVariableID from : \n";   //.$link."  (".IPS_GetName($link).")\n";
								//print_r($link);
								//echo "\n";
								CreateLinkByDestination($name[0], getVariableId($link,$categoryIdSwitches,$categoryIdGroups,$categoryIdPrograms), $categoryId_Autosteuerung_WebFront, $order);
								}
							}
						break;
					default:
						trigger_error('Unknown WFCItem='.$WFCItem[0]);
			   	    }
				}
			}
		
		}

	/****************************************************************************
	 *
	 * Webfront Installation der Stromheizung
	 *
	 * komplettes Webfront wie bei IPSLight aufbauen 
     * Basis Konfiguration wird aus der ini Datei übernommen
	 *  $WFC10_Enabled       Webfront aufbauen
     *  $WFC10_Path          das ist die Kategorie wo die Links reinkommen, beginnt bei Visualization.Webfront oder Visualization.Mobile
     *                       in der ini steht zumindesten Administrator.Stromheizung
     *  $WFC10_Regenerate    Kategorie $WFC10_Path komplett löschen, kann hier auf Zeile 285 ein/ausgeschaltet werden
     *  $WFC10_ConfigId      so kann man das WFC ansprechen Administrator/User etc.
     *  $WFC10_TabPaneItem   interner Name für das Webfront, nur in der WFC vorhanden
     *  $WFC10_TabPaneParent aso. Parameters to setup a TabPane
	 *
	 *****************************************************************************************/

	echo "\n";
	echo "=====================================================\n";

	//print_r($webFrontConfig);	echo "\n";
	
	if ($WFC10_Enabled) 
		{
		/* Default Path ist Visualization.WebFront.Administrator.Stromheizung */
        $wfcHandling =  new WfcHandling();                              // ohne Parameter wird die Konfiguration der Webfronts editiert, sonst werden die Standard Befehle der IPS Library verwendet
        $config = $configWFront["Administrator"];   
        $wfcHandling->read_WebfrontConfig($config["ConfigId"]);         // register Webfront Confígurator ID          
        echo "Webfront Administrator aufbauen in ".$config["Path"]." :\n";
        echo "\n";		
        $categoryId_WebFront                = CreateCategoryPath($config["Path"]);   // Administrator.Stromheizung
        IPS_SetHidden($categoryId_WebFront,true);
		if ($WFC10_Regenerate) 
			{
			EmptyCategory($categoryId_WebFront);
			$wfcHandling->DeleteWFCItems($config["TabPaneItem"]);				// HeatTPA
			//DeleteWFCItems($WFC10_ConfigId, 'Light_TP');		/* eventuell alte Installationen von IPS_light wegraeumen */
			}
        echo "Tab Pane mit HeatTPA machen : \nCreateWFCItemTabPane   (".$config["ConfigId"].",".$config["TabPaneItem"].",".$config["TabPaneParent"].",".$config["TabPaneOrder"].",".$config["TabPaneName"].",".$config["TabPaneIcon"].")\n";    
		$wfcHandling->CreateWFCItemTabPane   ($config["TabPaneItem"], $config["TabPaneParent"], $config["TabPaneOrder"], $config["TabPaneName"], $config["TabPaneIcon"]);   // Tab Pane mit HeatTPA machen

        /* $webFrontConfig is an array with setup of tabpane
         * Index is the Category in the Webfront category $WFC10_Path
         * darunter sind die WFC Befehle mit den Parametern, sehr sehr einfach 
         *    0  WFC Befehl
         *    1  Name
         *    2  Parent
         *    3..9 die Parameter
         * Achtung, nach einem WFCSPLITPANEL muessen auf jeden Fall die WFCCATEGORY Befehle kommen, eine für den OriginalNamen und eine für den neue Split Namen
         * das SplitPanel erzeugt einen neuen Tab, in diesem Tab werden zwei neue Kategorien angelegt
         * Nur die WFCLINKS und WFCGROUPS verlinken auf die Datenobjekte und werden in den Categories gespeichert
         *    2 Kategorie
         *    3 Datenobjekte als Komma getrennte Links
         *    4 Datenobjekte als Komma getrennte Namen, wenn kein 4 dann sind Links gleich die Namen
         * die Variablen werden mit getVariableId in Switch, groups oder programs gesucht
         */
		$order = 10;
		foreach($webFrontConfig as $tabName=>$tabData) {
			$tabCategoryId	= CreateCategory($tabName, $categoryId_WebFront, $order);
			foreach($tabData as $WFCItem) {
				$order = $order + 10;
				switch($WFCItem[0]) 
					{
					case IPSHEAT_WFCSPLITPANEL:
						echo "CreateWFCItemSplitPane (".$config["ConfigId"].", $WFCItem[1], $WFCItem[2] ,$order,$WFCItem[3],$WFCItem[4],(int)$WFCItem[5],(int)$WFCItem[6],(int)$WFCItem[7],(int)$WFCItem[8],$WFCItem[9])\n";
						$wfcHandling->CreateWFCItemSplitPane ($WFCItem[1], $WFCItem[2]/*Parent*/,$order,$WFCItem[3],$WFCItem[4],(int)$WFCItem[5],(int)$WFCItem[6],(int)$WFCItem[7],(int)$WFCItem[8],$WFCItem[9]);
						break;
					case IPSHEAT_WFCCATEGORY:
						$categoryId	= CreateCategory($WFCItem[1], $tabCategoryId, $order);
						echo "CreateWFCItemCategory ($WFCItem[1], $WFCItem[2],$order, $WFCItem[3],$WFCItem[4], $categoryId, false)\n";
						$wfcHandling->CreateWFCItemCategory ($WFCItem[1], $WFCItem[2]/*Parent*/,$order, $WFCItem[3]/*Name*/,$WFCItem[4]/*Icon*/, $categoryId, 'false');
						break;
					case IPSHEAT_WFCGROUP:
					case IPSHEAT_WFCLINKS:
						echo "  WFCLINKS : ".$WFCItem[2]."   ".$WFCItem[3]."\n";
						$categoryId = IPS_GetCategoryIDByName($WFCItem[2], $tabCategoryId);
						if ($WFCItem[0]==IPSHEAT_WFCGROUP) {
							$categoryId = CreateDummyInstance ($WFCItem[1], $categoryId, $order);
						}
						$links      = explode(',', $WFCItem[3]);
						$names      = $links;
						if (array_key_exists(4, $WFCItem)) {
							$names = explode(',', $WFCItem[4]);
						}
						foreach ($links as $idx=>$link) {
							$order = $order + 1;
                            $wfcHandling->createLinkinWebfront($link,$names[$idx],$categoryId,$order);                            
							/* CreateLinkByDestination ($Name, $LinkChildId, $ParentId, $Position, $ident="")
                            $register=explode('::',$link);
                            if (count($register)>0) echo "Link für ein Register, kein Schalter \n";
                            $variableID = getVariableId($link,[$categoryIdSwitches,$categoryIdGroups,$categoryIdPrograms]);
							if ($variableID) CreateLinkByDestination($names[$idx], $variableID, $categoryId, $order);   */
						}
						break;
					default:
						trigger_error('Unknown WFCItem='.$WFCItem[0]);
			   	    }
				}
			}
        $wfcHandling->write_WebfrontConfig($config["ConfigId"]);
		}

	// ----------------------------------------------------------------------------------------------------------------------------
	// User Installation
	// ----------------------------------------------------------------------------------------------------------------------------

	echo "\n";
	echo "=====================================================\n";
	
	if ($WFC10User_Enabled) 
		{
        $wfcHandling =  new WfcHandling();                              // ohne Parameter wird die Konfiguration der Webfronts editiert, sonst werden die Standard Befehle der IPS Library verwendet
        $config = $configWFront["User"];               
        echo "Webfront User aufbauen in ".$config["Path"]." :\n";
        echo "\n";
        $wfcHandling->read_WebfrontConfig($config["ConfigId"]);         // register Webfront Confígurator ID          
		$categoryId_WebFrontUser                = CreateCategoryPath($config["Path"]);
		/* in der normalen Viz Darstellung verstecken */
		IPS_SetHidden($categoryId_WebFrontUser, true); //Objekt verstecken	
		EmptyCategory($categoryId_WebFrontUser);
		echo "================= ende empty categories \ndelete ".$config["TabPaneItem"]."\n";	
		$wfcHandling->DeleteWFCItems($config["TabPaneItem"]);
		echo "================= ende delete ".$config["TabPaneItem"]."\n";
        echo "Tab Pane mit HeatTPU machen : \nCreateWFCItemTabPane   (".$config["ConfigId"].",".$config["TabPaneItem"].",".$config["TabPaneParent"].",".$config["TabPaneOrder"].",".$config["TabPaneName"].",".$config["TabPaneIcon"].")\n";    
		$wfcHandling->CreateWFCItemTabPane   ($config["TabPaneItem"], $config["TabPaneParent"], $config["TabPaneOrder"], $config["TabPaneName"], $config["TabPaneIcon"]);   // Tab Pane mit HeatTPA machen
		echo "================ende create Tabitem \n";
		$webFrontConfig = IPSHeat_GetWebFrontUserConfiguration();
		$order = 10;
		foreach($webFrontConfig as $tabName=>$tabData) {
			echo "================create ".$tabName."\n";
			$tabCategoryId	= CreateCategory($tabName, $categoryId_WebFrontUser, $order);
			foreach($tabData as $WFCItem) {
				$order = $order + 10;
				switch($WFCItem[0]) {
					case IPSHEAT_WFCSPLITPANEL:
						$wfcHandling->CreateWFCItemSplitPane ($WFCItem[1], $WFCItem[2]/*Parent*/,$order,$WFCItem[3],$WFCItem[4],(int)$WFCItem[5],(int)$WFCItem[6],(int)$WFCItem[7],(int)$WFCItem[8],$WFCItem[9]);
						break;
					case IPSHEAT_WFCCATEGORY:
						$categoryId	= CreateCategory($WFCItem[1], $tabCategoryId, $order);
						$wfcHandling->CreateWFCItemCategory ($WFCItem[1], $WFCItem[2]/*Parent*/,$order, $WFCItem[3]/*Name*/,$WFCItem[4]/*Icon*/, $categoryId, 'false');
						break;
					case IPSHEAT_WFCGROUP:
					case IPSHEAT_WFCLINKS:
						$categoryId = IPS_GetCategoryIDByName($WFCItem[2], $tabCategoryId);
						if ($WFCItem[0]==IPSHEAT_WFCGROUP) {
							$categoryId = CreateDummyInstance ($WFCItem[1], $categoryId, $order);
						}
						$links      = explode(',', $WFCItem[3]);
						$names      = $links;
						if (array_key_exists(4, $WFCItem)) {
							$names = explode(',', $WFCItem[4]);
						}
						foreach ($links as $idx=>$link) {
							$order = $order + 1;
							CreateLinkByDestination($names[$idx], getVariableId($link,$categoryIdSwitches,$categoryIdGroups,$categoryIdPrograms), $categoryId, $order);
						}
						break;
					default:
						trigger_error('Unknown WFCItem='.$WFCItem[0]);
			   }
			}
		}
    $wfcHandling->write_WebfrontConfig($config["ConfigId"]);        
	}

	// ----------------------------------------------------------------------------------------------------------------------------
	// Mobile Installation
	// ----------------------------------------------------------------------------------------------------------------------------
	
	echo "\n";
	echo "=====================================================\n";
	//echo "\n";
		
	if ($Mobile_Enabled ) 
		{
        $config = $configWFront["Mobile"];              
	    echo "Webfront Mobile aufbauen in ".$config["Path"]." : Die Kategorie wird ".($mobile_Regenerate?"neu erzeugt":"nur upgedated")."\n";
        $MobilePathSegments=explode(".",$config["Path"]);
        //print_R($MobilePathSegments);
        $parent=0;
        $MobilePathOIDs=array(); 
        $oldVisID=false;       
        foreach ($MobilePathSegments as $index => $MobilePathSegment)
            {
            $visID = @IPS_GetObjectIDByName ($MobilePathSegment, $parent);
            if ($visID===false)
                {
                echo "\nFehler, $MobilePathSegment ist in $parent nicht vorhanden (".$config["Path"].")\n";
                $oldVisID = @IPS_GetObjectIDByName ("Stromheizung", $parent);
                if ($oldVisID !== false)
                    {
                    echo "  Aber Stromheizung ($oldVisID) ist in $parent vorhanden.\n";
                    }
                $visID=$parent;
                break;                
                }
            //echo "$visID.";
            $parent=$visID;
            }
        echo "\n";
        $lastOne=sizeof($MobilePathSegments)-1;
        //echo "Insgesamt ".($lastOne+1)." Segmente im Pfad: ".$MobilePathSegments[$lastOne]."\n";
        if ($MobilePathSegments[$lastOne] != "Stromheizung")
            {
            if ($oldVisID===false)
                {
                $beforeLastOne=sizeof($MobilePathOIDs)-2;
                $oldVisID = @IPS_GetObjectIDByName ("Stromheizung", $MobilePathOIDs[$beforeLastOne]);
                }
            if ($oldVisID !== false)
                {
                echo "Stromheizung ($oldVisID) ist in $parent vorhanden, wird gelöscht. Neuer Pfad ist $Mobile_Path.\n";
			    EmptyCategory($oldVisID);
                IPS_DeleteCategory($oldVisID);
                }
            else echo "Stromheizung ($oldVisID) ist in $parent nicht mehr vorhanden. Neuer Pfad ist $Mobile_Path.\n";
            }
        else echo "Aufbau Mobile Webfront erfolgt im Standard Pfad.\n";
        echo "Config Mobile Webfront : ".json_encode($config)."\n";
		$mobileId  = CreateCategoryPath($config["Path"], $config["PathOrder"], $config["PathIcon"]);
		if ($mobile_Regenerate) { 			EmptyCategory($mobileId); 		}

        /* es gibt eine Mobile Webfront Konfigurationsfunction
         *
         *
         */

		$order = 10;
		foreach (IPSHeat_GetMobileConfiguration() as $roomName=>$roomData) {
			if (is_array($roomData)) 
                {
                echo "create Links for $roomName based on roomData Array ".json_encode($roomData).".\n";
				$roomId	= CreateCategory($roomName, $mobileId, $order);
                echo "   create Category $roomName with ID $roomId in $mobileId.\n";
				foreach($roomData as $roomItem) 
                    {
                    echo "  do Room ".json_encode($roomItem)."\n";
					$order = $order + 10;
					switch($roomItem[0]) {
						case IPSHEAT_WFCGROUP:
						case IPSHEAT_WFCLINKS:
							$instanceId = $roomId;
							if ($roomItem[0]==IPSHEAT_WFCGROUP) 
                                {
								$instanceId = CreateDummyInstance ($roomItem[1], $roomId, $order);
							    }
							$links      = explode(',', $roomItem[2]);
							$names      = $links;
							if (array_key_exists(3, $roomItem)) 
                                {
								$names = explode(',', $roomItem[3]);
							    }
							foreach ($links as $idx=>$link) 
                                {
								$order = $order + 1;
                                echo "    create link ".$names[$idx]." in $instanceId\n";
								CreateLinkByDestination($names[$idx], getVariableId($link,$categoryIdSwitches,$categoryIdGroups,$categoryIdPrograms), $instanceId, $order);
							    }
							break;
						 
						default:
							trigger_error('Unknown RoomItem='.$roomItem[0]);
				       }
				    }
			    }
            else 
                {
                echo "create Links based on roomData \"$roomData\".\n";
				$links = explode(',', $roomData);
				foreach ($links as $link) 
                    {
                    $variableId=getVariableId($link,$categoryIdSwitches,$categoryIdGroups,$categoryIdPrograms,true);                                            // true Debug
                    if ($variableId==false) echo "VariableID $link not found to link in $categoryIdSwitches,$categoryIdGroups,$categoryIdPrograms .\n";
                    else
                        {
                        echo "   Create Link $link with Variable $variableId at Kategorie $mobileId and Order $order.\n";
                        CreateLink($link, $variableId, $mobileId, $order);
                        }
                    $order = $order + 10;
                    }
                }
            }
        }


	// ----------------------------------------------------------------------------------------------------------------------------
	// Mobile Installation von IPSLight explizit entfernen
	// ----------------------------------------------------------------------------------------------------------------------------

    if (isset($installedModules["IPSLight"]))
        { 
        echo "\n";
        echo "IPSLight Mobile Webfront Installation:\n";
        $moduleManagerLight = new IPSModuleManager('IPSLight');
        $mobile_Enabled       = $moduleManagerLight->GetConfigValue('Enabled', 'Mobile');    
        $mobile_Path          = $moduleManagerLight->GetConfigValue('Path', 'Mobile');
        $mobile_PathOrder     = $moduleManagerLight->GetConfigValueInt('PathOrder', 'Mobile');
        $mobile_PathIcon      = $moduleManagerLight->GetConfigValue('PathIcon', 'Mobile');

        if ($mobile_Enabled ) 
            {
            echo "Mobile enabled.\n";
            }
        else
            {
            $mobileId  = CreateCategoryPath($mobile_Path, $mobile_PathOrder, $mobile_PathIcon);
            EmptyCategory($mobileId);    
            echo "Mobile disabled.\n";
            DeleteCategory($mobileId);
            }
        }

	echo "\n";
	echo "=====================================================\n";
    echo "\nDurchlaufzeit : ".(microtime(true)-$startexec)." Sekunden\n";    
	echo "Stromheizung Installation erfolgreich abgeschlossen !\n";
	echo "\n";

?>
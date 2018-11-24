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
	 
	/**@defgroup Stromheizung
	 *
	 * Script um elektrische Heizung nachzusteuern
	 *
	 *
	 * @file          Stromheizung_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

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
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");

	IPSUtils_Include ("IPSComponentHeatControl_FS20.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentHeatControl");
	
	IPSUtils_Include ('StromheizungLib.class.php', 'IPSLibrary::app::modules::Stromheizung');
	IPSUtils_Include ('Stromheizung_Configuration.inc.php', 'IPSLibrary::config::modules::Stromheizung');
	
	IPSUtils_Include ("IPSHeat.inc.php",                "IPSLibrary::app::modules::Stromheizung");
	IPSUtils_Include ("IPSHeat_Constants.inc.php",      "IPSLibrary::app::modules::Stromheizung");
	IPSUtils_Include ("Stromheizung_Configuration.inc.php",  "IPSLibrary::config::modules::Stromheizung");	

/*******************************
 *
 * Webfront Vorbereitung, hier werden keine Webfronts mehr installiert, nur mehr konfigurierte ausgelesen
 *
 ********************************/

	echo "\n";
	$WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
	echo "Default WFC10_ConfigId, wenn nicht definiert : ".IPS_GetName($WFC10_ConfigId)."  (".$WFC10_ConfigId.")\n\n";
	
	$WebfrontConfigID=array();
	$alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
	foreach ($alleInstanzen as $instanz)
		{
		$result=IPS_GetInstance($instanz);
		$WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
		echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."  (".$instanz.")\n";
		//echo "  ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'Protocol')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
		/* alle Instanzen dargestellt */
		//echo IPS_GetName($instanz)." ".$instanz." ".$result['ModuleInfo']['ModuleName']." ".$result['ModuleInfo']['ModuleID']."\n";
		//print_r($result);
		}
	echo "\n";
	
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
	
	$RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);

	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
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

	$WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
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

	$Mobile_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Mobile',false);
	if ($Mobile_Enabled==true)
		{	
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
		echo "Mobile \n";
		echo "  Path          : ".$Mobile_Path."\n";		
		}

	$Retro_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Retro',false);
	if ($Retro_Enabled==true)
		{	
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		echo "Retro \n";
		echo "  Path          : ".$Retro_Path."\n";		
		}
		
	$WFC10_Regenerate     = true;			// das Webfront im Konfigurator neu aufbauen
	$mobile_Regenerate    = false;			
			
	// ----------------------------------------------------------------------------------------------------------------------------
	// Program Installation
	// ----------------------------------------------------------------------------------------------------------------------------
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$categoryIdSwitches = CreateCategory('Switches', $CategoryIdData, 10);
	$categoryIdGroups   = CreateCategory('Groups',   $CategoryIdData, 20);
	$categoryIdPrograms = CreateCategory('Programs', $CategoryIdData, 30);	
	
	// Add Scripts
	$scriptIdActionScript  = IPS_GetScriptIDByName('IPSHeat_ActionScript', $CategoryIdApp);
	
	echo "Action Script hat OID: ".$scriptIdActionScript."\n";
	
	// ===================================================================================================
	// Add Heat or Light Devices
	// ===================================================================================================
	$idx = 10;
	$lightConfig = IPSHeat_GetHeatConfiguration();
	foreach ($lightConfig as $deviceName=>$deviceData) 
		{
		$deviceType = $deviceData[IPSHEAT_TYPE];

		switch ($deviceType) 
			{
			case IPSLIGHT_TYPE_SWITCH:
			case IPSHEAT_TYPE_SWITCH:			
				$switchId = CreateVariable($deviceName,    0 /*Boolean*/, $categoryIdSwitches,  $idx, '~Switch', $scriptIdActionScript, false, 'Bulb');
				break;
			case IPSLIGHT_TYPE_DIMMER:
			case IPSHEAT_TYPE_DIMMER:			
				$switchId = CreateVariable($deviceName,                       0 /*Boolean*/, $categoryIdSwitches,  $idx, '~Switch',        $scriptIdActionScript, false, 'Bulb');
				$levelId  = CreateVariable($deviceName.IPSHEAT_DEVICE_LEVEL, 1 /*Integer*/, $categoryIdSwitches,  $idx, '~Intensity.100', $scriptIdActionScript, false, 'Intensity');
				break;
			case IPSLIGHT_TYPE_RGB:
			case IPSHEAT_TYPE_RGB:
				$switchId = CreateVariable($deviceName,                       0 /*Boolean*/, $categoryIdSwitches,  $idx, '~Switch',        $scriptIdActionScript, false, 'Bulb');
				$colorId  = CreateVariable($deviceName.IPSHEAT_DEVICE_COLOR, 1 /*Integer*/, $categoryIdSwitches,  $idx, '~HexColor',      $scriptIdActionScript, false, 'HollowDoubleArrowRight');
				$levelId  = CreateVariable($deviceName.IPSHEAT_DEVICE_LEVEL, 1 /*Integer*/, $categoryIdSwitches,  $idx, '~Intensity.100', $scriptIdActionScript, false, 'Intensity');
				break;
			case IPSHEAT_TYPE_SET:
				$switchId = CreateVariable($deviceName,                       0 /*Boolean*/, $categoryIdSwitches,  $idx, '~Switch',        $scriptIdActionScript, false, 'Bulb');
				$tempId  = CreateVariable($deviceName.IPSHEAT_DEVICE_LEVEL, 2 /*Float*/, $categoryIdSwitches,  $idx, '~Temperature.HM', $scriptIdActionScript, false, 'Temperature');
				break;
			default:
				trigger_error('Unknown DeviceType '.$deviceType.' found for Heat or Light '.$devicename);
			}
		$idx = $idx + 1;
		}	

	// ===================================================================================================
	// Add Groups
	// ===================================================================================================
	$idx = 10;
	$groupConfig = IPSHeat_GetGroupConfiguration();
	foreach ($groupConfig as $groupName=>$groupData) 
		{
		if ( Isset($groupData[IPSHEAT_TYPE]) )
			{
			$groupType = $groupData[IPSHEAT_TYPE];		
			switch ($groupType) 
				{
				case IPSHEAT_TYPE_SET:
					$switchId = CreateVariable($groupName,                       0 /*Boolean*/, $categoryIdGroups,  $idx, '~Switch',        $scriptIdActionScript, false, 'Bulb');
					$tempId  = CreateVariable($groupName.IPSHEAT_DEVICE_LEVEL, 2 /*Float*/, $categoryIdGroups,  $idx, '~Temperature.HM', $scriptIdActionScript, false, 'Temperature');
					break;
				default:
					$switchId     = CreateVariable($groupName,    0 /*Boolean*/, $categoryIdGroups,  $idx, '~Switch', $scriptIdActionScript, false, 'Bulb');
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
				$instanceId = IPSUtil_ObjectIDByPath($componentParams[1]);
				$variableId = @IPS_GetObjectIDByIdent('STATE', $instanceId);
				if ($variableId===false) 
					{
					$moduleManager->LogHandler()->Log('Variable with Name STATE could NOT be found for Homematic Instance='.$instanceId);
					} 
				else 
					{
					$moduleManager->LogHandler()->Log('Register OnChangeEvent vor Homematic Instance='.$instanceId);
					$messageHandler->RegisterOnChangeEvent($variableId, $component, 'IPSModuleSwitch_IPSLight,');
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
					$messageHandler->RegisterOnChangeEvent($variableId, $component, 'IPSModuleSwitch_IPSHeat,');
					}
				break;
			case 'IPSComponentHeatSet_Data':
			case 'IPSComponentTuner_Denon':
			case 'IPSComponentSwitch_RFS20':
			case 'IPSComponentRGB_PhilipsHUE':
			case 'IPSComponentDimmer_Homematic':
			case 'IPSComponentSwitch_RMonitor':
			case 'IPSComponentRGB_LW12':
				break;					
			default:
				trigger_error('Unknown ComponentType '.$componentClass.' found for Heat or Light '.$deviceName.' cannot register.');			
				break;
			}
		}

	/****************************************************************************
	 *
	 * Webfront Installation der Stromheiztung in der Autosteuerung
	 *
	 * 
	 *
	 *****************************************************************************************/

	$webFrontConfig = IPSHeat_GetWebFrontConfiguration();
	
	/* nur die Heizungstellwerte bei der Autosteuerung, Tab Stromheizung dazuhaengen */ 

	$WFC10_Autosteuerung_Path='Visualization.WebFront.Administrator.Autosteuerung.Stromheizung.AutoTPADetails2_LeftDown';
	if ($WFC10_Enabled) 
		{
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
	 * 
	 *
	 *****************************************************************************************/

	echo "\n";
	echo "=====================================================\n";
	echo "Webfront aufbauen in ".$WFC10_Path." :\n";
	echo "\n";
	print_r($webFrontConfig);
	echo "\n";
	
	if ($WFC10_Enabled) 
		{
		/* Default Path ist Visualization.WebFront.Administrator.Stromheizung */
		$categoryId_WebFront                = CreateCategoryPath($WFC10_Path);   // Administrator.Stromheizung
		if ($WFC10_Regenerate) 
			{
			EmptyCategory($categoryId_WebFront);
			DeleteWFCItems($WFC10_ConfigId, $WFC10_TabPaneItem);				// HeatTPA
			//DeleteWFCItems($WFC10_ConfigId, 'Light_TP');		/* eventuell alte Installationen von IPS_light wegraeumen */
			}
		CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem,  $WFC10_TabPaneParent, $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);   // Tab Pane mit HeatTPA machen

		$order = 10;
		foreach($webFrontConfig as $tabName=>$tabData) {
			$tabCategoryId	= CreateCategory($tabName, $categoryId_WebFront, $order);
			foreach($tabData as $WFCItem) {
				$order = $order + 10;
				switch($WFCItem[0]) 
					{
					case IPSHEAT_WFCSPLITPANEL:
						CreateWFCItemSplitPane ($WFC10_ConfigId, $WFCItem[1], $WFCItem[2]/*Parent*/,$order,$WFCItem[3],$WFCItem[4],(int)$WFCItem[5],(int)$WFCItem[6],(int)$WFCItem[7],(int)$WFCItem[8],$WFCItem[9]);
						break;
					case IPSHEAT_WFCCATEGORY:
						$categoryId	= CreateCategory($WFCItem[1], $tabCategoryId, $order);
						CreateWFCItemCategory ($WFC10_ConfigId, $WFCItem[1], $WFCItem[2]/*Parent*/,$order, $WFCItem[3]/*Name*/,$WFCItem[4]/*Icon*/, $categoryId, 'false');
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
							// CreateLinkByDestination ($Name, $LinkChildId, $ParentId, $Position, $ident="")
							CreateLinkByDestination($names[$idx], getVariableId($link,$categoryIdSwitches,$categoryIdGroups,$categoryIdPrograms), $categoryId, $order);
						}
						break;
					default:
						trigger_error('Unknown WFCItem='.$WFCItem[0]);
			   	}
				}
			}
		}


	
	if (false)
		{  /* bereits bei Custom Components implementiert */
		
	echo "FHT Heizungssteuerung Geräte mit Positionswerten werden registriert.\n";

	$StromheizungHandler = new StromheizungHandler();

	$FHT = FHTList();
	$keyword="PositionVar";

	foreach ($FHT as $Key)
		{
		/* alle Positionswerte der Heizungssteuerungen ausgeben */
		if (isset($Key["COID"][$keyword])==true)
			{
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
				
			$StromheizungHandler->RegisterEvent($oid,"Heizung",'','');	

			if (isset ($installedModules["RemoteAccess"]))
				{
				//echo "Rufen sie dazu eine entsprechende remote Access Routine auf .... \n";
				}
			else
				{
				/* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
				echo "Remote Access nicht installiert, Variablen selbst registrieren.\n";
				$messageHandler = new IPSMessageHandler();
				$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
				$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

				/* wenn keine Parameter nach IPSComponentSensor_Temperatur angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentHeatControl_FS20','IPSModuleHeatControl_All,1,2,3');
				}

			}  /* Ende isset Heizungssteuerung */
		} /* Ende foreach */

		} /* ende iffalse */



?>
<?php
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSModuleSwitch_IPSLight.class.php
	 * @author        Andreas Brauneis
	 *
	 *
	 */

	/**
	 * @class IPSModuleSwitch_IPSLight
	 *
	 * Definiert ein IPSModuleSwitch_IPSLight Object, das als Wrapper für Beschattungsgeräte in der IPSLibrary
	 * verwendet werden kann.
	 *
	 * @author Andreas Brauneis
	 * @version
	 * Version 2.50.1, 31.01.2012<br/>
	 */

	IPSUtils_Include ('IPSModuleSwitch.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSwitch');

	IPSUtils_Include ("IPSModuleManagerGUI.inc.php", "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");	

	class IPSModuleSwitch_IPSHeat extends IPSModuleSwitch {

		/**
		 * @public
		 *
		 * Ermöglicht die Synchronisation einer Beleuchtung zu IPSLight/IPSHeat
		 *
		 * @param string $state Aktueller Status des Switch
		 */
		public function SyncState($state, IPSComponentSwitch $componentToSync) 
			{
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
			if (isset($installedModules["Stromheizung"]) )
				{			
				IPSUtils_Include ("IPSHeat.inc.php",          "IPSLibrary::app::modules::Stromheizung");
				$componentParamsToSync = explode(",",$componentToSync->GetComponentParams());
				echo "   IPSModuleSwitch_IPSHeat SyncState ".IPS_GetName($componentParamsToSync[1])." (".$componentParamsToSync[1].")\n";
                echo "      check IPSHeat_GetHeatConfiguration for Homematic Instance : ".$componentParamsToSync[1]." to synchronize.\n";
				$deviceHeatConfig          = IPSHeat_GetHeatConfiguration();			
				foreach ($deviceHeatConfig as $deviceIdent=>$deviceData) 
					{
					$componentConfig       = IPSComponent::CreateObjectByParams($deviceData[IPSHEAT_COMPONENT]);
					$componentParamsConfig = explode(",",$componentConfig->GetComponentParams());
                    //echo "         Compare $deviceIdent ".$deviceData[IPSHEAT_COMPONENT]." :  ".$componentParamsConfig[1]." == ".$componentParamsToSync[1]." \n";
					//if ($componentParamsConfig[1]==$componentParamsToSync[1]) 
					if ( (isset($componentParamsConfig[1])) && ($componentParamsConfig[1]==$componentParamsToSync[1]) )
						{
						echo "     IPSModuleSwitch_IPSHeat SyncState synchronize from Type Heatswitch ".$deviceIdent." mit ".$state."\n";
						$heatManager = new IPSHeat_Manager();
						$heatManager->SynchronizeSwitch($deviceIdent, $state);
						}
					}			
				}  // ende if
			if (isset($installedModules["IPSLight"]) )
				{			
				IPSUtils_Include ("IPSLight.inc.php",          "IPSLibrary::app::modules::IPSLight");
				$componentParamsToSync = explode(",",$componentToSync->GetComponentParams());
				echo "   IPSModuleSwitch_IPSHeat SyncState ".IPS_GetName($componentParamsToSync[1])." (".$componentParamsToSync[1].")\n";
                echo "      check IPSLight_GetLightConfiguration\n";
                $deviceConfig          = IPSLight_GetLightConfiguration();
				foreach ($deviceConfig as $deviceIdent=>$deviceData) 
					{
					$componentConfig       = IPSComponent::CreateObjectByParams($deviceData[IPSLIGHT_COMPONENT]);
					//$componentParamsConfig = $componentConfig->GetComponentParams();
					$componentParamsConfig = explode(",",$componentConfig->GetComponentParams());
					//if ($componentParamsConfig[1]==$componentParamsToSync[1])
					if ( (isset($componentParamsConfig[1])) && ($componentParamsConfig[1]==$componentParamsToSync[1]) )
						{
						echo "      IPSModuleSwitch_IPSHeat SyncState synchronize from Type Lightswitch ".$deviceIdent." mit ".$state."\n";
						$lightManager = new IPSLight_Manager();
						$lightManager->SynchronizeSwitch($deviceIdent, $state);
						$heatManager = new IPSHeat_Manager();
						$heatManager->SynchronizeSwitch($deviceIdent, $state);
						}
					}
				}  // ende if
			}	


	}

	/** @}*/
?>
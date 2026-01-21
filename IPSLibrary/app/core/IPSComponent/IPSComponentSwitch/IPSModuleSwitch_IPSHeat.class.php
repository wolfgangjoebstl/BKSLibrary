<?php
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSModuleSwitch_IPSHeat.class.php
	 * @author Wolfgang Joebstl, inspiriert von Andreas Brauneis
	 *
	 *
	 */

	/**
	 * @class IPSModuleSwitch_IPSheat
	 *
	 * Definiert ein IPSModuleSwitch_IPSHeat Object, das als Wrapper für Beschattungsgeräte in der IPSLibrary
	 * verwendet werden kann.
	 *
	 * @author Andreas Brauneis
	 * @version
	 * Version 2.50.1, 31.01.2012<br/>
	 */

	IPSUtils_Include ('IPSModule.class.php', 'IPSLibrary::app::core::IPSComponent');
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
		public function SyncState($state, IPSComponentSwitch $componentToSync, $debug=false) 
			{
            echo "IPSModuleSwitch_IPSHeat::SyncState aufgerufen.\n";
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
			if (isset($installedModules["Stromheizung"]) )
				{			
				IPSUtils_Include ("IPSHeat.inc.php",          "IPSLibrary::app::modules::Stromheizung");
				$componentParamsToSync = explode(",",$componentToSync->GetComponentParams());
				echo "   IPSModuleSwitch_IPSHeat SyncState ".IPS_GetName($componentParamsToSync[1])." (".$componentParamsToSync[1].")\n";
                echo "      check IPSHeat_GetHeatConfiguration for Homematic Instance : ".$componentParamsToSync[1]." to synchronize.\n";
				$deviceHeatConfig          = IPSHeat_GetHeatConfiguration();
                //print_R($deviceHeatConfig);			
				foreach ($deviceHeatConfig as $deviceIdent=>$deviceData) 
					{
                    if ($debug>1) echo "         Compare for ".$componentParamsToSync[1]." : ".str_pad($deviceIdent,30).$deviceData[IPSHEAT_COMPONENT]."\n";
                    if ($deviceData[IPSHEAT_COMPONENT] != "")
                        {
                        $componentConfig       = IPSComponent::CreateObjectByParams($deviceData[IPSHEAT_COMPONENT]);
                        $componentParamsConfig = explode(",",$componentConfig->GetComponentParams());
                        //echo " :  ".$componentParamsConfig[1]." == ".$componentParamsToSync[1]." \n";
                        //if ($componentParamsConfig[1]==$componentParamsToSync[1]) 
                        if ( (isset($componentParamsConfig[1])) && ($componentParamsConfig[1]==$componentParamsToSync[1]) )
                            {
                            echo "        IPSModuleSwitch_IPSHeat SyncState synchronize from Type Heatswitch ".$deviceIdent." mit ".$state."\n";
                            $heatManager = new IPSHeat_Manager();
                            $heatManager->SynchronizeSwitch($deviceIdent, $state);
                            }
                        }
                    else echo "        Warning, Compare ".str_pad($deviceIdent,30).$deviceData[IPSHEAT_COMPONENT]." incomplete.\n";
					}			
				}  // ende if
			if (isset($installedModules["IPSLight"]) )
				{			
				IPSUtils_Include ("IPSLight.inc.php",          "IPSLibrary::app::modules::IPSLight");
				$componentParamsToSync = explode(",",$componentToSync->GetComponentParams());
				echo "   IPSModuleSwitch_IPSLight SyncState ".IPS_GetName($componentParamsToSync[1])." (".$componentParamsToSync[1].")\n";
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
						echo "      IPSModuleSwitch_IPSLight SyncState synchronize from Type Lightswitch ".$deviceIdent." mit ".$state."\n";
						$lightManager = new IPSLight_Manager();
						$lightManager->SynchronizeSwitch($deviceIdent, $state);
						$heatManager = new IPSHeat_Manager();
						$heatManager->SynchronizeSwitch($deviceIdent, $state);
						}
					}
				}  // ende if
            echo "----\n";
			}	


	}

	/** @}*/
?>
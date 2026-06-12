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
    
    /**
	 * @class IPSModuleRGB
	 *
	 * Definiert ein IPSModuleSwitch Object, das als Wrapper für Schaltgeräte in der IPSLibrary
	 * verwendet werden kann.
     *
     * Nur ein Script das referenziert wird, funktioniert nur mehr mit IPSHeat
	 *
	 * @author Andreas Brauneis, Wolfgang Joebstl
	 */


    IPSUtils_Include ('IPSModuleRGB.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentRGB');


	class IPSModuleRGB_IPSHeat extends IPSModuleRGB {

		/* SyncState
		 * Ermöglicht die Synchronisation einer RGB basierenden Beleuchtung zu IPSHeat
         * Bedeutet eine externe Änderung der Parameter wird in IPSymcon und im Webfront/Kachel nachgezogen
		 * 
         * prüft ob Modul Stromheizung installiert ist
         * Die Parameter in der Configuration werden ausgelesen, es sind mehrere Parameter, aber nicht alle wie bei RemoteAccess
         * Parameter 1 ist die Instamz, für diesen Parameter wird die Stromheizung Konfiguration durchsucht
		 * Der Index der Konfigiuration, also nicht der Name, oder der Name der Instamz werden für SynchronizeSwitch herangezogen
		 *
		 * @param string $state Aktueller Status des Switch
		 */
		public function SyncState($state, IPSComponentRGB $componentToSync, $debug=false) 
			{
            if ($debug) echo "IPSModuleRGB_IPSHeat::SyncState aufgerufen.\n";
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
			if (isset($installedModules["Stromheizung"]) )
				{			
				IPSUtils_Include ("IPSHeat.inc.php",          "IPSLibrary::app::modules::Stromheizung");
				$componentParamsToSync = explode(",",$componentToSync->GetComponentParams());
				if ($debug) echo "   IPSModuleRGB_IPSHeat SyncState ".IPS_GetName($componentParamsToSync[1])." (".$componentParamsToSync[1].")\n";
                if ($debug) echo "      check IPSHeat_GetHeatConfiguration for HUE Instance : ".$componentParamsToSync[1]." to synchronize.\n";
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
                            echo "        IPSModuleRGB_IPSHeat SyncState synchronize from Type Heatswitch ".$deviceIdent." mit Status ".$state.". Call SynchronizeSwitch.\n";
                            $heatManager = new IPSHeat_Manager();
                            $heatManager->SynchronizeSwitch($deviceIdent, $state);
                            }
                        }
                    elseif ($debug>1) echo "        Warning, Compare ".str_pad($deviceIdent,30).$deviceData[IPSHEAT_COMPONENT]." incomplete.\n";
					}			
				}  // ende if
			if (isset($installedModules["IPSLight"]) && false)
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

		public function SyncBrightness($state, IPSComponentRGB $componentToSync, $debug=false) 
			{
            if ($debug) echo "IPSModuleRGB_IPSHeat::SyncBrightness aufgerufen.\n";
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
			if (isset($installedModules["Stromheizung"]) )
				{			
				IPSUtils_Include ("IPSHeat.inc.php",          "IPSLibrary::app::modules::Stromheizung");
				$componentParamsToSync = explode(",",$componentToSync->GetComponentParams());
				if ($debug) echo "   IPSModuleRGB_IPSHeat SyncBrightness ".IPS_GetName($componentParamsToSync[1])." (".$componentParamsToSync[1].")\n";
                if ($debug) echo "      check IPSHeat_GetHeatConfiguration for HUE Instance : ".$componentParamsToSync[1]." to synchronize.\n";
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
                            echo "        IPSModuleRGB_IPSHeat SyncState synchronize from Type Heatswitch ".$deviceIdent." mit Status ".$state.". Call SynchronizeSwitch.\n";
                            $heatManager = new IPSHeat_Manager();
                            $heatManager->SynchronizeBrightness($deviceIdent, $state);
                            }
                        }
                    elseif ($debug>1) echo "        Warning, Compare ".str_pad($deviceIdent,30).$deviceData[IPSHEAT_COMPONENT]." incomplete.\n";
					}			
				}  // ende if
            }
	}


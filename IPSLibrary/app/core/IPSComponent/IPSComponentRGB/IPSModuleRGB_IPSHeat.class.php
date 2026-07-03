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
     * relevant to sync external data with internal representation
     * an external change will be propagated to the internal variables that are displayed in webfront
     * old style webfron was used to harmonize different ways of controlling devices into one way of displaying them
     * new style tiles Webfront changes the way how it is done and displays the individual devices directly 
     * so as future guide, display the devices directly and sync the changes to the internal representation for interoperability
     *
     * does only work for IPSHeat, IPSLight is depricated
     *
	 *
	 * @author Andreas Brauneis, Wolfgang Joebstl
	 */


    IPSUtils_Include ('IPSModuleRGB.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentRGB');
	IPSUtils_Include ("IPSHeat.inc.php",          "IPSLibrary::app::modules::Stromheizung");

	class IPSModuleRGB_IPSHeat extends IPSModuleRGB {

        protected $installedModules;
        protected $heatManager;
        protected $configPerInstance;

        public function __construct()
            {
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedModules = $moduleManager->VersionHandler()->GetInstalledModules();   
            $this->heatManager = new IPSHeat_Manager(); 
            $this->configPerInstance = $this->heatManager->reindexHeatConfigOnInstance();            
            }

		/* IPSModuleRGB_IPSHeat::SyncState
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
			if (isset($this->installedModules["Stromheizung"]) )
				{			
				$componentParamsToSync = explode(",",$componentToSync->GetComponentParams());
                $instanceId=$componentParamsToSync[1];
				if ($debug) echo "   IPSModuleRGB_IPSHeat SyncState ".IPS_GetName($instanceId)." ($instanceId)\n";
                if ($debug) echo "      check IPSHeat_GetHeatConfiguration for HUE Instance : $instanceId to synchronize.\n";
                if (isset($this->configPerInstance[$instanceId])) 
                    {
                    //print_R($this->configPerInstance[$instanceId]);
                    $deviceIdent=$this->configPerInstance[$instanceId]["Indexname"];
                    echo "        IPSModuleRGB_IPSHeat SyncState synchronize from Type Heatswitch ".$deviceIdent." mit Status ".$state.". Call SynchronizeSwitch.\n";
                    $this->heatManager->SynchronizeSwitch($deviceIdent, $state);
                    }
				/* 
                $deviceHeatConfig          = IPSHeat_GetHeatConfiguration();
                //print_R($deviceHeatConfig);			
				foreach ($deviceHeatConfig as $deviceIdent=>$deviceData) 
					{
                    if ($debug>1) echo "         Compare for $instanceId : ".str_pad($deviceIdent,30).$deviceData[IPSHEAT_COMPONENT]."\n";
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
					} */			
				}  // ende if
            echo "----\n";
			}	

        /* IPSModuleRGB_IPSHeat::SyncBrightness
         * an external change will be propagated to the internal variables that are displayed in webfront
         * external variables are always harmonized to internal ones. The internal copy is the status.
         * 
         */
		public function SyncBrightness($state, IPSComponentRGB $componentToSync, $debug=false) 
			{
            if ($debug) echo "IPSModuleRGB_IPSHeat::SyncBrightness aufgerufen.\n";
			if (isset($this->installedModules["Stromheizung"]) )
				{			
				$componentParamsToSync = explode(",",$componentToSync->GetComponentParams());
                $instanceId=$componentParamsToSync[1];
				if ($debug) echo "   IPSModuleRGB_IPSHeat SyncBrightness ".IPS_GetName($instanceId)." ($instanceId)\n";
                if ($debug) echo "      check IPSHeat_GetHeatConfiguration for HUE Instance : $instanceId to synchronize.\n";
                if (isset($this->configPerInstance[$instanceId])) 
                    {
                    //print_R($this->configPerInstance[$instanceId]);
                    $deviceIdent=$this->configPerInstance[$instanceId]["Indexname"];
                    echo "        IPSModuleRGB_IPSHeat SyncBrightness synchronize from Type Heatswitch ".$deviceIdent." mit Status ".$state.". Call SynchronizeBrightness.\n";
                    $this->heatManager->SynchronizeBrightness($deviceIdent, $state);
                    }            
				}  // ende if
            echo "----\n";                
            }

		public function SyncColor($state, IPSComponentRGB $componentToSync, $debug=false) 
			{
            if ($debug) echo "IPSModuleRGB_IPSHeat::SyncColor aufgerufen.\n";
			if (isset($this->installedModules["Stromheizung"]) )
				{			
				$componentParamsToSync = explode(",",$componentToSync->GetComponentParams());
                $instanceId=$componentParamsToSync[1];
				if ($debug) echo "   IPSModuleRGB_IPSHeat SyncColor ".IPS_GetName($instanceId)." ($instanceId)\n";
                if ($debug) echo "      check IPSHeat_GetHeatConfiguration for HUE Instance : $instanceId to synchronize.\n";
                if (isset($this->configPerInstance[$instanceId])) 
                    {
                    //print_R($this->configPerInstance[$instanceId]);
                    $deviceIdent=$this->configPerInstance[$instanceId]["Indexname"];
                    echo "        IPSModuleRGB_IPSHeat SyncColor synchronize from Type Heatswitch ".$deviceIdent." mit Status ".$state.". Call SynchronizeColor.\n";
                    $this->heatManager->SynchronizeColor($deviceIdent, $state);
                    }            
				}  // ende if
            echo "----\n";                
            }

		public function SyncAmbience($state, IPSComponentRGB $componentToSync, $debug=false) 
			{
            if ($debug) echo "IPSModuleRGB_IPSHeat::SyncAmbience aufgerufen.\n";
			if (isset($this->installedModules["Stromheizung"]) )
				{			
				$componentParamsToSync = explode(",",$componentToSync->GetComponentParams());
                $instanceId=$componentParamsToSync[1];
				if ($debug) echo "   IPSModuleRGB_IPSHeat SyncAmbience ".IPS_GetName($instanceId)." ($instanceId)\n";
                if ($debug) echo "      check IPSHeat_GetHeatConfiguration for HUE Instance : $instanceId to synchronize.\n";
                if (isset($this->configPerInstance[$instanceId])) 
                    {
                    //print_R($this->configPerInstance[$instanceId]);
                    $deviceIdent=$this->configPerInstance[$instanceId]["Indexname"];
                    echo "        IPSModuleRGB_IPSHeat SyncAmbience synchronize from Type Heatswitch ".$deviceIdent." mit Status ".$state.". Call SynchronizeAmber.\n";
                    $this->heatManager->SynchronizeAmber($deviceIdent, $state);
                    }            
				}  // ende if
            echo "----\n";                
            }
	}


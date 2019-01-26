<?
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

	IPSUtils_Include ("IPSLight.inc.php",          "IPSLibrary::app::modules::IPSLight");
	IPSUtils_Include ("IPSHeat.inc.php",          "IPSLibrary::app::modules::Stromheizung");
	IPSUtils_Include ('IPSModuleSwitch.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSwitch');

	class IPSModuleSwitch_IPSHeat extends IPSModuleSwitch {

		/**
		 * @public
		 *
		 * Ermöglicht die Synchronisation einer Beleuchtung zu IPSLight
		 *
		 * @param string $state Aktueller Status des Switch
		 */
		public function SyncState($state, IPSComponentSwitch $componentToSync) 
			{
			//IPSLogger_Inf(__file__, 'IPSModuleSwitch_IPSHeat, SyncState');
			$componentParamsToSync = explode(",",$componentToSync->GetComponentParams());
			echo "   IPSModuleSwitch_IPSHeat SyncState (".$componentParamsToSync[1].")\n";
			$deviceConfig          = IPSLight_GetLightConfiguration();
			foreach ($deviceConfig as $deviceIdent=>$deviceData) 
				{
				$componentConfig       = IPSComponent::CreateObjectByParams($deviceData[IPSLIGHT_COMPONENT]);
				//$componentParamsConfig = $componentConfig->GetComponentParams();
				$componentParamsConfig = explode(",",$componentConfig->GetComponentParams());
				if ($componentParamsConfig[1]==$componentParamsToSync[1]) 
					{
					$lightManager = new IPSLight_Manager();
					$lightManager->SynchronizeSwitch($deviceIdent, $state);
					$heatManager = new IPSHeat_Manager();
					$heatManager->SynchronizeSwitch($deviceIdent, $state);
					}
				}
			/* ganze IPSHeat Konfiguration laden */	
			$deviceHeatConfig          = IPSHeat_GetHeatConfiguration();			
			foreach ($deviceHeatConfig as $deviceIdent=>$deviceData) 
				{
				$componentConfig       = IPSComponent::CreateObjectByParams($deviceData[IPSHEAT_COMPONENT]);
				$componentParamsConfig = explode(",",$componentConfig->GetComponentParams());
				//echo "    Compare ".$componentParamsConfig."\n";
				if ($componentParamsConfig[1]==$componentParamsToSync[1]) 
					{
					echo "IPSModuleSwitch_IPSHeat SyncState synchronize Heatswitch ".$deviceIdent." mit ".$state."\n";
					$heatManager = new IPSHeat_Manager();
					$heatManager->SynchronizeSwitch($deviceIdent, $state);
					}
				}			
			}


	}

	/** @}*/
?>
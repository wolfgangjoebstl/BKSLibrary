<?
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentRGB_Dummy.class.php
	 * @author        Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentRGB_Dummy
    *
    * Definiert ein IPSComponentRGB_Dummy Object, das ein Dummy IPSComponentRGB Object implementiert.
    *
    * @author Andreas Brauneis
    * @version
    *   Version 2.50.1, 06.11.2012<br/>
    */

	IPSUtils_Include ('IPSComponent.class.php', 'IPSLibrary::app::core::IPSComponent');

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');


	class IPSComponentTuner_Denon extends IPSComponent {

		private $instanceId;    /* generelle Instanz mit der das Obkekt erkannt werden kann */
		private $TunerName;
		private $ZoneName;
		private $ChannelName;
		private $DataCatID;
		private $log_Denon;
		private $DenonSocketID;
		
		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentRGB_Dummy Objektes
		 *
		 * @param integer $instanceId InstanceId des Dummy Devices
		 */
		public function __construct($TunerName,$ZoneName="Main Zine",$ChannelName="Radio") {
			$this->TunerName = $TunerName;
			$this->ZoneName = $ZoneName;
			$this->ChannelName = $ChannelName;
			$this->DataCatID = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.DENONsteuerung.'.$TunerName.".".$ZoneName);
			//echo "   DataCatID : ".$this->DataCatID."\n";
			$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
			if (!isset($moduleManager))
				{
				IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
				$moduleManager = new IPSModuleManager('DENONsteuerung',$repository);
				}

			$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
			$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

			$object_data= new ipsobject($CategoryIdData);
			$object_app= new ipsobject($CategoryIdApp);

			$NachrichtenID = $object_data->osearch("Nachricht");
			$NachrichtenScriptID  = $object_app->osearch("Nachricht");

			if (isset($NachrichtenScriptID))
				{
				$object3= new ipsobject($NachrichtenID);
				$NachrichtenInputID=$object3->osearch("Input");
				$this->log_Denon=new Logging("C:\Scripts\Log_Denon.csv",$NachrichtenInputID);
				}
			//$this->log_Denon->LogMessage("Script wurde �ber IPSLight aufgerufen.");
			//$this->log_Denon->LogNachrichten("Script wurde �ber IPSLight aufgerufen.");

			Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");
			$configuration=Denon_Configuration();

			foreach ($configuration as $config)
				{
				//print_r($config);
				if ($config['NAME']==$TunerName)
				   {
				   $instanz=$config['INSTANZ'];
				   }
				}
			$this->DenonSocketID = IPS_GetObjectIDByName($instanz." Client Socket", 0);
			$this->instanceId =$TunerName.",".$ZoneName;
			}

		/**
		 * @public
		 *
		 * Function um Events zu behandeln, diese Funktion wird vom IPSMessageHandler aufgerufen, um ein aufgetretenes Event 
		 * an das entsprechende Module zu leiten.
		 *
		 * @param integer $variable ID der ausl�senden Variable
		 * @param string $value Wert der Variable
		 * @param IPSModuleRGB $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		public function HandleEvent($variable, $value, IPSModuleRGB $module){
		}

		/**
		 * @public
		 *
		 * Funktion liefert String IPSComponent Constructor String.
		 * String kann dazu ben�tzt werden, das Object mit der IPSComponent::CreateObjectByParams
		 * wieder neu zu erzeugen.
		 *
		 * @return string Parameter String des IPSComponent Object
		 */
		public function GetComponentParams() {
			return get_class($this).','.$this->instanceId;
		}

		/**
		 * @public
		 *
		 * Zustand Setzen 
		 *
		 * @param boolean $power RGB Ger�t On/Off
		 * @param integer $color RGB Farben (Hex Codierung)
		 * @param integer $level Dimmer Einstellung der RGB Beleuchtung (Wertebereich 0-100)
		 */
		public function SetState($power, $level) {
			//echo "Hurrah hier angekommen mit Parameter : ".$power."  ".$level."\n";
			$this->log_Denon->LogMessage("Script wurde �ber IPSLight aufgerufen.".$power." ".$level);
			$this->log_Denon->LogNachrichten("Script wurde �ber IPSLight aufgerufen.".$power." ".$level." ".$this->DenonSocketID);
			include (IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\DENONsteuerung\DENON.Functions.ips.php");
			$volumeID=IPS_GetObjectIDByName("MasterVolume",$this->DataCatID);
			$MainZoneID=IPS_GetObjectIDByName("MainZonePower",$this->DataCatID);
			$InputSourceID=IPS_GetObjectIDByName("InputSource",$this->DataCatID);
			$PowerID=IPS_GetObjectIDByName("Power",$this->DataCatID);
			//echo "DataCatID :".$this->DataCatID."   ".$powerID." ".$volumeID."\n";
			if ($power == false)
				{
				DENON_Power($this->DenonSocketID, "STANDBY");
				SetValue($PowerID,false);
				DENON_MainZonePower($this->DenonSocketID, false);
				SetValue($MainZoneID,false);
				}
			else
				{
				DENON_Power($this->DenonSocketID, "ON");
				SetValue($PowerID,true);
				sleep(1);
				DENON_MainZonePower($this->DenonSocketID, true);
				SetValue($MainZoneID,true);
				sleep(1);
				DENON_InputSource($this->DenonSocketID, 2);
				SetValue($InputSourceID,2);
				}
			DENON_MasterVolumeFix($this->DenonSocketID, $level-80);
			SetValue($volumeID,$level-80);
			//DENON_MainZonePower($this->DenonSocketID, (string)$level."%");
			//print_r($this);
			//echo IPS_GetKernelDir()."scripts/".GetValue(IPS_GetObjectIDByName("!LW12_CLibrary",  IPS_GetParent($this->instanceId))).".ips.php";
			//require(IPS_GetKernelDir()."scripts/IPSLibrary/app/modules/LedAnsteuerung/LedAnsteuerung_Library.ips.php");
			//include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\LedAnsteuerung\LedAnsteuerung_Library.ips.php");
			//LW12_PowerToggle2($this->instanceId,$power);
			//LW12_setDecRGB2($this->instanceId,$color);
		}

	}

	/** @}*/
?>
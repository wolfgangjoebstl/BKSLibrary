<?
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentheatControl.class.php
	 * @author        Wolfgang Jöbstl und Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentHeatControl
    *
    * Definiert ein IPSComponentHeatControl
    *
    */

	IPSUtils_Include ('IPSComponent.class.php', 'IPSLibrary::app::core::IPSComponent');

	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	abstract class IPSComponentHeatControl extends IPSComponent {

		/**
		 * @public
		 *
		 * Function um Events zu behandeln, diese Funktion wird vom IPSMessageHandler aufgerufen, um ein aufgetretenes Event 
		 * an das entsprechende Module zu leiten.
		 *
		 * @param integer $variable ID der auslösenden Variable
		 * @param string $value Wert der Variable
		 * @param IPSModuleSensor $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		abstract public function HandleEvent($variable, $value, IPSModuleHeatControl $module);
		
				/**
		 * @public
		 *
		 * Funktion liefert String IPSComponent Constructor String.
		 * String kann dazu benützt werden, das Object mit der IPSComponent::CreateObjectByParams
		 * wieder neu zu erzeugen.
		 *
		 * @return string Parameter String des IPSComponent Object
		 */
		public function GetComponentParams() {
			return get_class($this).','.$this->instanceId;
		}
		
	}  /* ende class */
	


	/********************************* 
	 *
	 * Klasse überträgt die Werte an einen remote Server und schreibt lokal in einem Log register mit
	 *
	 * legt dazu zwei Kategorien im eigenen data Verzeichnis ab
	 *
	 * xxx_Auswertung und xxxx_Nachrichten
	 *
	 **************************/

	class HeatControl_Logging extends Logging
		{
		private $variable;
		private $variablename;
		public $variableLogID;					/* ID der entsprechenden lokalen Spiegelvariable */
		public $variableEnergyLogID;			/* ID der entsprechenden lokalen Spiegelvariable für den Energiewert*/
				
		private $HeatControlAuswertungID;

		private $configuration;
		private $installedmodules;
				
		function __construct($variable,$variablename=Null)
			{
			//echo "Construct IPSComponentSensor HeatControl Logging for Variable ID : ".$variable."\n";
			$this->variable=$variable;
			if ($variablename==Null)
				{
				$result=IPS_GetObject($variable);
				$this->variablename=IPS_GetName((integer)$result["ParentID"]);			// Variablenname ist der Parent Name wenn nicht anders angegeben
				} 
			else
				{
				$this->variablename=$variablename;
				}
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();
			$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
			echo "  Kategorien im Datenverzeichnis:".$CategoryIdData."   ".IPS_GetName($CategoryIdData)."\n";
			
			$name="HeatControl-Nachrichten";
			$vid=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($vid==false)
				{
				$vid = IPS_CreateCategory();
				IPS_SetParent($vid, $CategoryIdData);
				IPS_SetName($vid, $name);
				IPS_SetInfo($vid, "this category was created by script IPSComponentHeatControl.");
				}
				
			/* Create Category to store the HeatControl-Spiegelregister */	
			$name="HeatControl-Auswertung";
			$HeatControlAuswertungID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($HeatControlAuswertungID==false)
				{
				$HeatControlAuswertungID = IPS_CreateCategory();
				IPS_SetParent($HeatControlAuswertungID, $CategoryIdData);
				IPS_SetName($HeatControlAuswertungID, $name);
				IPS_SetInfo($HeatControlAuswertungID, "this category was created by script IPSComponentHeatControl_Homematic. ");
	    		}
			$this->HeatControlAuswertungID=$HeatControlAuswertungID;
			
			/* lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen */
			if ($variable<>null)
				{
				echo "Lokales Spiegelregister als Integer auf ".$this->variablename." unter Kategorie ".$this->HeatControlAuswertungID." ".IPS_GetName($this->HeatControlAuswertungID)." anlegen.\n";
				/* Parameter : $Name, $Type, $Parent, $Position, $Profile, $Action=null */
				$this->variableLogID=CreateVariable($this->variablename,1,$this->HeatControlAuswertungID, 10, "~Intensity.100", null, null );  /* 1 steht für Integer, alle benötigten Angaben machen, sonst Fehler */
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				AC_SetLoggingStatus($archiveHandlerID,$this->variableLogID,true);
				AC_SetAggregationType($archiveHandlerID,$this->variableLogID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				
				if (function_exists('get_IPSComponentHeatConfig'))
					{
					$powerConfig=get_IPSComponentHeatConfig();
					echo "Look for ".$variable." in Configuration.\n";
					if ( isset($powerConfig["HeatingPower"][$variable]) )
						{
						echo "Lokales Spiegelregister für Energiewert auf ".$this->variablename."_Energy"." unter Kategorie ".$this->HeatControlAuswertungID." ".IPS_GetName($this->HeatControlAuswertungID)." anlegen.\n";
						/* Parameter : $Name, $Type, $Parent, $Position, $Profile, $Action=null */
						$this->variableEnergyLogID=CreateVariable($this->variablename."_Energy",2,$this->HeatControlAuswertungID, 10, "~Electricity.HM", null, null );  /* 1 steht für Integer, alle benötigten Angaben machen, sonst Fehler */
						$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
						AC_SetLoggingStatus($archiveHandlerID,$this->variableEnergyLogID,true);
						AC_SetAggregationType($archiveHandlerID,$this->variableEnergyLogID,0);      /* normaler Wwert */
						IPS_ApplyChanges($archiveHandlerID);						
						}
					}					
				}

			//echo "Uebergeordnete Variable : ".$this->variablename."\n";
			$directories=get_IPSComponentLoggerConfig();
			if (isset($directories["LogDirectories"]["HeatControlLog"]))
		   		 { $directory=$directories["LogDirectories"]["HeatControlLog"]; }
			else {$directory="C:/Scripts/HeatControl/"; }	
			mkdirtree($directory);
			$filename=$directory.$this->variablename."_HeatControl.csv";
			parent::__construct($filename,$vid);
			}

		function HeatControl_LogValue()
			{
			// result formatieren
			$variabletyp=IPS_GetVariable($this->variable);
			if ($variabletyp["VariableProfile"]!="")
				{
				$result=GetValueFormatted($this->variable);
				}
			else
				{
				$result=number_format(GetValue($this->variable),2,',','.')." %";				
				}
				
			$unchanged=time()-$variabletyp["VariableChanged"];
			$oldvalue=GetValue($this->variableLogID);
			SetValue($this->variableLogID,GetValue($this->variable));
			echo "Neuer Wert fuer ".$this->variablename."(".$this->variable.") ist ".GetValue($this->variable)." %. Alter Wert war : ".$oldvalue." unverändert für ".$unchanged." Sekunden.\n";

			// Leistungswerte berechnen
			if (function_exists('get_IPSComponentHeatControlConfig'))
				{
				$powerConfig=get_IPSComponentHeatControlConfig();
				echo "Look for ".$this->variable." in Configuration.\n";
				if ( isset($powerConfig["HeatingPower"][$this->variable]) )
					{
					SetValue($this->variableEnergyLogID,GetValue($this->variableEnergyLogID)+$oldvalue*$unchanged/60/60*$powerConfig["HeatingPower"][$this->variable]);
					}				
				}				
			
			if (isset ($this->installedmodules["DetectMovement"]))
				{
				/* Detect Movement kann auch Leistungswerte agreggieren */
				IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
				IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
				$DetectHeatControlHandler = new DetectHeatControlHandler();
				//print_r($DetectMovementHandler->ListEvents("Motion"));
				//print_r($DetectMovementHandler->ListEvents("Contact"));

				$groups=$DetectHeatControlHandler->ListGroups();
				foreach($groups as $group=>$name)
					{
					echo "Gruppe ".$group." behandeln.\n";
					$config=$DetectHeatControlHandler->ListEvents($group);
					$status=(float)0;
					$count=0;
					foreach ($config as $oid=>$params)
						{
						$status+=GetValue($oid);
						$count++;
						//echo "OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)."Status: ".GetValue($oid)." ".$status."\n";
						echo "OID: ".$oid." Name: ".str_pad(IPS_GetName($oid),30)."Status: ".GetValue($oid)." ".$status."\n";
						}
					if ($count>0) { $status=$status/$count; }
					echo "Gruppe ".$group." hat neuen Status : ".$status."\n";
					/* Herausfinden wo die Variablen gespeichert, damit im selben Bereich auch die Auswertung abgespeichert werden kann */
					//$statusID=CreateVariable("Gesamtauswertung_".$group,2,$this->TempAuswertungID,100, "~Temperature", null, null);
					echo "Gesamtauswertung_".$group." ist auf OID : ".$statusID."\n";
					//SetValue($statusID,$status);
					}
				}
			
			parent::LogMessage($result);
			parent::LogNachrichten($this->variablename." mit Wert ".$result);
			}

		public function GetComponent() {
			return ($this);
			}
			
		/*************************************************************************************
		Ausgabe des Eventspeichers in lesbarer Form
		erster Parameter true: macht zweimal evaluate
		zweiter Parameter true: nimmt statt dem aktuellem Event den Gesamtereignisspeicher
		*************************************************************************************/

		public function writeEvents($comp=true,$gesamt=false)
			{

			}
			
	   }


	/** @}*/
?>
<?

   /**
    * @class IPSComponentLogger
    *
    * Loggt die Werte der Sensoren in allen möglichen Medien und Arten
    *
    * @author Wolfgang Jöbstl
    * @version
    *   Version 2.50.1, 09.06.2012<br/>
    */

	IPSUtils_Include ('IPSComponentSensor.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');



class ipsobject
	{
	var $object_ID=0;

	function __construct($objectid=0)
	   {
	   $this->object_ID=$objectid;
		//echo "Init ".get_class($this)." : ";
		//var_dump($this);
		}

	function oprint($item="")
		{
		//echo "Hallo";
		//var_dump($this);
		$result=IPS_GetObject($this->object_ID);
		echo $this->object_ID." \"".$result["ObjectName"]."\" ".$result["ParentID"]."\n";
		$childrenIds=$result["ChildrenIDs"];
		foreach ($childrenIds as $childrenId)
			{
			$result=IPS_GetObject($childrenId);
			$resultname=$result["ObjectName"];
			if ($item != "")
			   {
				if (strpos($resultname,$item)===false)
					{
					$nachrichtok="";
					}
				else
					{
					$nachrichtok="gefunden";
					$NachrichtenscriptID=$childrenId;
					}
				}
			echo "  ".$childrenId."  \"".$resultname."\" ";
			switch ($result["ObjectType"])
			   {
			   case "6": echo "Link"; break;
			   case "5": echo "Media"; break;
			   case "4": echo "Ereignis"; break;
			   case "3": echo "Skript"; break;
			   case "2": echo "Variable"; break;
			   case "1": echo "Instanz"; break;
			   case "0": echo "Kategorie"; break;
			   }
			if ($item != "")
				{
				echo " ".$nachrichtok." \n";
				}
			else
				{
				echo " \n";
				}
			}
		}

	function oparent()
		{
		$result=IPS_GetObject($this->object_ID);
		return $result["ParentID"];
		}

	function osearch($item="")
		{
		$result=IPS_GetObject($this->object_ID);
		//echo $this->object_ID." \"".$result["ObjectName"]."\" ".$result["ParentID"]."\n";
		$childrenIds=$result["ChildrenIDs"];
		foreach ($childrenIds as $childrenId)
			{
			$result=IPS_GetObject($childrenId);
			$resultname=$result["ObjectName"];
			if (strpos($resultname,$item)===false)
				{
				$nachrichtok="";
				}
			else
				{
				$nachrichtok="gefunden";
				return $NachrichtenscriptID=$childrenId;
				}
			//echo "  ".$childrenId."  \"".$resultname."\" ";
			/* switch ($result["ObjectType"])
			   {
			   case "6": echo "Link"; break;
			   case "5": echo "Media"; break;
			   case "4": echo "Ereignis"; break;
			   case "3": echo "Skript"; break;
			   case "2": echo "Variable"; break;
			   case "1": echo "Instanz"; break;
			   case "0": echo "Kategorie"; break;
			   } */
			}
		}


	}


class Logging
	{
	private $log_File="Default";
	private $script_Id="Default";
	private $nachrichteninput_Id="Default";
	private $installedmodules;
	
	function __construct($logfile="No-Output",$nachrichteninput_Id="Ohne")
	   {
	   //echo "Logfile Construct\n";
	   $this->log_File=$logfile;
	   $this->nachrichteninput_Id=$nachrichteninput_Id;
   	//echo "Initialisierung ".get_class($this)." mit Logfile: ".$this->log_File." mit Meldungsspeicher: ".$this->script_Id." \n";
		//echo "Init ".get_class($this)." : ";
		//var_dump($this);
		if (!file_exists($this->log_File))
			{
			$FilePath = pathinfo($this->log_File, PATHINFO_DIRNAME);
			if (!file_exists($FilePath)) 
				{
				if (!mkdir($FilePath, 0755, true)) {
					throw new Exception('Create Directory '.$destinationFilePath.' failed!');
					}
				}			
			//echo "Create new file : ".$this->log_File." im Verzeichnis : ".$FilePath." \n";
			$handle3=fopen($this->log_File, "a");
		   fwrite($handle3, date("d.m.y H:i:s").";Meldung\r\n");
	   	fclose($handle3);
			}
		if ($this->nachrichteninput_Id != "Ohne")
		   {
			$this->zeile1 = CreateVariable("Zeile01",3,$this->nachrichteninput_Id, 10 );
			$this->zeile2 = CreateVariable("Zeile02",3,$this->nachrichteninput_Id, 20 );
			$this->zeile3 = CreateVariable("Zeile03",3,$this->nachrichteninput_Id, 30 );
			$this->zeile4 = CreateVariable("Zeile04",3,$this->nachrichteninput_Id, 40 );
			$this->zeile5 = CreateVariable("Zeile05",3,$this->nachrichteninput_Id, 50 );
			$this->zeile6 = CreateVariable("Zeile06",3,$this->nachrichteninput_Id, 60 );
			$this->zeile7 = CreateVariable("Zeile07",3,$this->nachrichteninput_Id, 70 );
			$this->zeile8 = CreateVariable("Zeile08",3,$this->nachrichteninput_Id, 80 );
			$this->zeile9 = CreateVariable("Zeile09",3,$this->nachrichteninput_Id, 90 );
			$this->zeile10 = CreateVariable("Zeile10",3,$this->nachrichteninput_Id, 100 );
			$this->zeile11 = CreateVariable("Zeile11",3,$this->nachrichteninput_Id, 110 );
			$this->zeile12 = CreateVariable("Zeile12",3,$this->nachrichteninput_Id, 120 );
			$this->zeile13 = CreateVariable("Zeile13",3,$this->nachrichteninput_Id, 130 );
			$this->zeile14 = CreateVariable("Zeile14",3,$this->nachrichteninput_Id, 140 );
			$this->zeile15 = CreateVariable("Zeile15",3,$this->nachrichteninput_Id, 150 );
			$this->zeile16 = CreateVariable("Zeile16",3,$this->nachrichteninput_Id, 160 );
			}
		else
			{
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);			
			$this->installedmodules=$moduleManager->GetInstalledModules();			
			$moduleManager_CC = new IPSModuleManager('CustomComponent');
			$CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
			echo "  Kategorien im Datenverzeichnis Custom Components:".$CategoryIdData."   ".IPS_GetName($CategoryIdData)."\n";
			$name="Bewegung-Nachrichten";
			$vid=@IPS_GetObjectIDByName($name,$CategoryIdData);
			$this->zeile1  = CreateVariable("Zeile01",3,$vid, 10 );
			$this->zeile2  = CreateVariable("Zeile02",3,$vid, 20 );
			$this->zeile3  = CreateVariable("Zeile03",3,$vid, 30 );
			$this->zeile4  = CreateVariable("Zeile04",3,$vid, 40 );
			$this->zeile5  = CreateVariable("Zeile05",3,$vid, 50 );
			$this->zeile6  = CreateVariable("Zeile06",3,$vid, 60 );
			$this->zeile7  = CreateVariable("Zeile07",3,$vid, 70 );
			$this->zeile8  = CreateVariable("Zeile08",3,$vid, 80 );
			$this->zeile9  = CreateVariable("Zeile09",3,$vid, 90 );
			$this->zeile10 = CreateVariable("Zeile10",3,$vid, 100 );
			$this->zeile11 = CreateVariable("Zeile11",3,$vid, 110 );
			$this->zeile12 = CreateVariable("Zeile12",3,$vid, 120 );
			$this->zeile13 = CreateVariable("Zeile13",3,$vid, 130 );
			$this->zeile14 = CreateVariable("Zeile14",3,$vid, 140 );
			$this->zeile15 = CreateVariable("Zeile15",3,$vid, 150 );
			$this->zeile16 = CreateVariable("Zeile16",3,$vid, 160 );
			
			if (isset ($this->installedmodules["DetectMovement"]))
				{
				/* nur wenn Detect Movement installiert zusaetzlich ein Motion Log fuehren */
				$moduleManager_DM = new IPSModuleManager('DetectMovement');     /*   <--- change here */
				$CategoryIdData     = $moduleManager_DM->GetModuleCategoryID('data');
				echo "  Kategorien im Datenverzeichnis Detect Movement :".$CategoryIdData."   ".IPS_GetName($CategoryIdData)."\n";
				$name="Motion-Nachrichten";
				$vid=@IPS_GetObjectIDByName($name,$CategoryIdData);			
				$this->zeile01DM = CreateVariable("Zeile01",3,$vid, 10 );
				$this->zeile02DM = CreateVariable("Zeile02",3,$vid, 20 );
				$this->zeile03DM = CreateVariable("Zeile03",3,$vid, 30 );
				$this->zeile04DM = CreateVariable("Zeile04",3,$vid, 40 );
				$this->zeile05DM = CreateVariable("Zeile05",3,$vid, 50 );
				$this->zeile06DM = CreateVariable("Zeile06",3,$vid, 60 );
				$this->zeile07DM = CreateVariable("Zeile07",3,$vid, 70 );
				$this->zeile08DM = CreateVariable("Zeile08",3,$vid, 80 );
				$this->zeile09DM = CreateVariable("Zeile09",3,$vid, 90 );
				$this->zeile10DM = CreateVariable("Zeile10",3,$vid, 100 );
				$this->zeile11DM = CreateVariable("Zeile11",3,$vid, 110 );
				$this->zeile12DM = CreateVariable("Zeile12",3,$vid, 120 );
				$this->zeile13DM = CreateVariable("Zeile13",3,$vid, 130 );
				$this->zeile14DM = CreateVariable("Zeile14",3,$vid, 140 );
				$this->zeile15DM = CreateVariable("Zeile15",3,$vid, 150 );
				$this->zeile16DM = CreateVariable("Zeile16",3,$vid, 160 );			
				}
			}																																
	   }

	function LogMessage($message)
		{
		if ($this->log_File != "No-Output")
		   {
      	$handle3=fopen($this->log_File, "a");
		   fwrite($handle3, date("d.m.y H:i:s").";".$message."\r\n");
	   	fclose($handle3);
			//echo $this->log_File."   ".$message."\n";
			}
		}

	function LogNachrichten($message)
		{
		if ($this->nachrichteninput_Id != "Ohne")
		   {
			//echo "Nachrichtenverlauf auf  ".$this->nachrichteninput_Id."   \n";
			SetValue($this->zeile16,GetValue($this->zeile15));
			SetValue($this->zeile15,GetValue($this->zeile14));
			SetValue($this->zeile14,GetValue($this->zeile13));
			SetValue($this->zeile13,GetValue($this->zeile12));
			SetValue($this->zeile12,GetValue($this->zeile11));
			SetValue($this->zeile11,GetValue($this->zeile10));
			SetValue($this->zeile10,GetValue($this->zeile9));
			SetValue($this->zeile9,GetValue($this->zeile8));
			SetValue($this->zeile8,GetValue($this->zeile7));
			SetValue($this->zeile7,GetValue($this->zeile6));
			SetValue($this->zeile6,GetValue($this->zeile5));
			SetValue($this->zeile5,GetValue($this->zeile4));
			SetValue($this->zeile4,GetValue($this->zeile3));
			SetValue($this->zeile3,GetValue($this->zeile2));
			SetValue($this->zeile2,GetValue($this->zeile1));
			SetValue($this->zeile1,date("d.m.y H:i:s")." : ".$message);
			}
		else
			{
			SetValue($this->zeile16,GetValue($this->zeile15));
			SetValue($this->zeile15,GetValue($this->zeile14));
			SetValue($this->zeile14,GetValue($this->zeile13));
			SetValue($this->zeile13,GetValue($this->zeile12));
			SetValue($this->zeile12,GetValue($this->zeile11));
			SetValue($this->zeile11,GetValue($this->zeile10));
			SetValue($this->zeile10,GetValue($this->zeile9));
			SetValue($this->zeile9,GetValue($this->zeile8));
			SetValue($this->zeile8,GetValue($this->zeile7));
			SetValue($this->zeile7,GetValue($this->zeile6));
			SetValue($this->zeile6,GetValue($this->zeile5));
			SetValue($this->zeile5,GetValue($this->zeile4));
			SetValue($this->zeile4,GetValue($this->zeile3));
			SetValue($this->zeile3,GetValue($this->zeile2));
			SetValue($this->zeile2,GetValue($this->zeile1));
			SetValue($this->zeile1,date("d.m.y H:i:s")." : ".$message);
			if (isset ($this->installedmodules["DetectMovement"]))
				{
				SetValue($this->zeile16DM,GetValue($this->zeile15DM));
				SetValue($this->zeile15DM,GetValue($this->zeile14DM));
				SetValue($this->zeile14DM,GetValue($this->zeile13DM));
				SetValue($this->zeile13DM,GetValue($this->zeile12DM));
				SetValue($this->zeile12DM,GetValue($this->zeile11DM));
				SetValue($this->zeile11DM,GetValue($this->zeile10DM));
				SetValue($this->zeile10DM,GetValue($this->zeile09DM));
				SetValue($this->zeile09DM,GetValue($this->zeile08DM));
				SetValue($this->zeile08DM,GetValue($this->zeile07DM));
				SetValue($this->zeile07DM,GetValue($this->zeile06DM));
				SetValue($this->zeile06DM,GetValue($this->zeile05DM));
				SetValue($this->zeile05DM,GetValue($this->zeile04DM));
				SetValue($this->zeile04DM,GetValue($this->zeile03DM));
				SetValue($this->zeile03DM,GetValue($this->zeile02DM));
				SetValue($this->zeile02DM,GetValue($this->zeile01DM));
				SetValue($this->zeile01DM,date("d.m.y H:i:s")." : ".$message);
				}
			}								
		}

	function PrintNachrichten()
		{
		$result=false;
		if ($this->nachrichteninput_Id != "Ohne")
		   {
		   $result=GetValue($this->zeile1)."\n".GetValue($this->zeile2)."\n".GetValue($this->zeile3)."\n".GetValue($this->zeile4)."\n".
					  GetValue($this->zeile5)."\n".GetValue($this->zeile6)."\n".GetValue($this->zeile7)."\n".GetValue($this->zeile8)."\n".
					  GetValue($this->zeile9)."\n".GetValue($this->zeile10)."\n".GetValue($this->zeile11)."\n".GetValue($this->zeile12)."\n".
					  GetValue($this->zeile13)."\n".GetValue($this->zeile14)."\n".GetValue($this->zeile15)."\n".GetValue($this->zeile16)."\n";
			}
		return $result;
		}

	function status()
	   {
	   return true;
	   }
		
		
		
	}

/********************** Routine nur zum Spass emgefuegt */
	
	class IPSComponentLogger {


		private $tempObject;
		private $RemoteOID;
		private $tempValue;

		/**
		 * @public
		 *
		 * Initialisierung eines IPSModuleSensor_IPStemp Objektes
		 *
		 * @param string $tempObject Licht Object/Name (Leuchte, Gruppe, Programm, ...)
		 * @param integer $RemoteOID OID die gesetzt werden soll
		 * @param string $tempValue Wert für Beleuchtungs Änderung
		 */
		public function __construct($var1=null, $lightObject=null, $lightValue=null) {
			$this->tempObject   = $lightObject;
			$this->RemoteOID    = $var1;
			$this->tempValue    = $lightValue;
			IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
			$this->remServer    = RemoteAccess_GetConfigurationNew();
		}
	
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
		public function HandleEvent($variable, $value, IPSModuleSensor $module){
			echo "Bewegungs Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";

		}

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
			return get_class($this);
		}

	}
	
	
	
	

	/** @}*/
?>
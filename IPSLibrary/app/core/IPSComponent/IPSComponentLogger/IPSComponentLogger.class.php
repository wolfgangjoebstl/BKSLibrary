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
	var $log_File="Default";
	var $script_Id="Default";
	var $nachrichteninput_Id="Default";
	
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
			//echo "Create new file\n";
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
			$this->remServer    = RemoteAccess_GetConfiguration();
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

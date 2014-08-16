<?



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


class logging
	{
	var $log_File="Default";
	var $script_Id="Default";
	var $nachrichteninput_Id="Default";
	function __construct($logfile="No-Output",$scriptid="Ohne",$nachrichteninput_Id="Ohne")
	   {
	   $this->log_File=$logfile;
	   $this->script_Id=$scriptid;
	   $this->nachrichteninput_Id=$nachrichteninput_Id;
   	//echo "Initialisierung ".get_class($this)." mit Logfile: ".$this->log_File." mit Meldungsspeicher: ".$this->script_Id." \n";
		//echo "Init ".get_class($this)." : ";
		//var_dump($this);
		if (!file_exists($this->log_File))
			{
      	$handle3=fopen($this->log_File, "a");
		   fwrite($handle3, date("d.m.y H:i:s").";Meldung\r\n");
	   	fclose($handle3);
			}
		/* if ($this->script_Id != "Ohne")
		   {
			$object4= new ipsobject($this->script_Id);
			//$object4->oprint("Input");
			$this->nachrichteninput_Id=$object4->osearch("Input"); 
			}  */
	   }

	function message($message)
		{
		if ($this->log_File != "No-Output")
		   {
      	$handle3=fopen($this->log_File, "a");
		   fwrite($handle3, date("d.m.y H:i:s").";".$message."\r\n");
	   	fclose($handle3);
			//echo $this->log_File."   ".$message."\n";
			}
		if ($this->script_Id != "Ohne")
		   {
			echo $this->script_Id."  ".$this->nachrichteninput_Id."   \n";
 			SetValue($this->nachrichteninput_Id,date("d.m.y H:i:s")." : ".$message);
			IPS_RunScript($this->script_Id);
			}
		}

	function status()
	   {
	   return true;
	   }
	}


?>

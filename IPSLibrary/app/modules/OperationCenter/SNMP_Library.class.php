<?

/*
 * SNMP Objekte auslesen
 * benötigt Exe File mit CLI
 *
 *
 */

class SNMP
{
    //Objekteigenschaften
    protected $host;                   //SNMP Serveradresse
    protected $community;              //SNMP Community
    protected $binary;                 //Dateipfad zur ssnmpq.exe
    public $debug = false;             //Bei true werden Debuginformationen ausgegeben
    protected $snmpobj=array();    		//array registrierter snmp objekte welche beim server abgefragt werden
    private $lastwalk=array();         //hier Ergebnis vom letzten Walk Befehl hineinschreiben als array
    private $lastwalk_csv="";          //hier Ergebnis vom letzten Walk Befehl hineinschreiben als csv
    private $CategoryIdData;

    //IPS Datentypen
    const tBOOL        = 0;
    const tINT        = 1;
    const tFLOAT    = 2;
    const tSTRING    = 3;
    const tLONG    = 4;          /* auf mehrere Integer Werte aufteilen, wenn notwendig , also wenn kein 64 Bit System */

	/*
	 *  Konstruktor
	 */

	public function __construct($CategoryIdData, $host, $community, $binary, $debug)
	 	{
      $this->host         = $host;
      $this->community     = $community;
      $this->binary         = $binary;
      $this->debug         = $debug;
		$this->CategoryIdData = $CategoryIdData;

		//Prüfe ob Variablenprofile existieren und erstelle diese wenn nötig
      $this->createVariableProfile("SNMP_CapacityMB", self::tFLOAT, "", " MB");
      $this->createVariableProfile("SNMP_CapacityGB", self::tFLOAT, "", " GB");
      $this->createVariableProfile("SNMP_CapacityTB", self::tFLOAT, "", " TB");
      $this->createVariableProfile("SNMP_Temperature", self::tINT, "", " °C");
      $this->createVariableProfile("SNMP_FanSpeed", self::tINT, "", " RPM");
      if(!IPS_VariableProfileExists("SNMP_SmartStatus"))
			{
         $this->createVariableProfile("SNMP_SmartStatus", self::tBOOL, "", "");
         IPS_SetVariableProfileAssociation("SNMP_SmartStatus", 1, "Gut", "", 0x00FF04);
         IPS_SetVariableProfileAssociation("SNMP_SmartStatus", 0, "Defekt", "", 0xFF0000);
        	}
    	}

	/*
	 *  Variablenprofil erstellen wenn zB Variablen neu angelegt werden
	 */

    private function createVariableProfile($name, $type, $pre, $suff){
        if(!IPS_VariableProfileExists($name)){
            if($this->debug) echo "INFO - VariablenProfil $name existiert nicht und wird angelegt";
            IPS_CreateVariableProfile($name, $type);
            IPS_SetVariableProfileText($name, $pre, $suff);
        }
    }

	/*
	 *  Variable erstellen wenn zB Variablen neu angelegt werden
	 *  dafürd en Variablentyp ermitteln
	 */

    public function registerSNMPObj($oid, $desc, $convertType = "none")
	 	{
		$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

      //prüfe auf doppelte Einträge beim Registrieren neuer SNMP Objekte
      foreach($this->snmpobj as $obj)
			{
         if($desc==$obj->desc)
				{
            if($this->debug) echo "ERROR - registerSNMPObj: Variable description '$desc' already exists, it must be unique!";
            exit;
            }
         if($oid==$obj->OID)
				{
            if($this->debug) echo "ERROR - registerSNMPObj: Variable OID '$oid' already exists, it must be unique!";
            exit;
            }
        }

		//prüfe ob IPS Variablen für SNMP Objekt existiert (Variablenname entspricht description)
      $parentID = $this->CategoryIdData;
      $ips_var = @IPS_GetVariableIDByName($desc, $parentID);
      if($ips_var == false)
			{
         if($this->debug) echo "Variable '$desc' not found - create IPSVariable\n";

            if($convertType == "none") $type = $this->getSNMPType($oid);
            if($convertType == "CapacityMB" || $convertType == "CapacityGB" || $convertType == "CapacityTB")
									            $type = self::tFLOAT;
            if($convertType == "Temperature")
									            $type = self::tINT;
            if($convertType == "FanSpeed")
									            $type = self::tINT;
            if($convertType == "SmartStatus")
									            $type = self::tBOOL;
            if($convertType == "Counter32")
									            $type = self::tLONG;
            if($this->debug) echo "Type of OID '$oid' is $type \n";

				if ($type==self::tLONG)
				   {
			      $convertType = "Counter32";  /* automatisch zuweisen */
	            $ips_var = IPS_CreateVariable(1);
	            IPS_SetName($ips_var, $desc);
  		         IPS_SetParent($ips_var, $parentID);
  		         IPS_SetPosition($ips_var,20);
	            $ips_vare = IPS_CreateVariable(1);
	            IPS_SetName($ips_vare, $desc."ext");
  		         IPS_SetParent($ips_vare, $parentID);
  		         IPS_SetPosition($ips_vare,20);
	            $ips_varc = IPS_CreateVariable(1);
	            IPS_SetName($ips_varc, $desc."chg");
  		         IPS_SetParent($ips_varc, $parentID);
  		         IPS_SetPosition($ips_varc,10);
					AC_SetLoggingStatus($archiveHandlerID,$ips_varc,true);
					AC_SetAggregationType($archiveHandlerID,$ips_varc,0);  /* 0 Standard 1 Zähler */
					IPS_ApplyChanges($archiveHandlerID);
				   }
				else
				   {
	            $ips_var = IPS_CreateVariable($type);
	            IPS_SetName($ips_var, $desc);
   	         IPS_SetParent($ips_var, $parentID);
	            }


            //Verknüpfe Variablenprofil mit neu erstellter Variable
            if($convertType == "CapacityMB")
	            IPS_SetVariableCustomProfile($ips_var, "SNMP_CapacityMB");
            if($convertType == "CapacityGB")
   	         IPS_SetVariableCustomProfile($ips_var, "SNMP_CapacityGB");
            if($convertType == "CapacityTB")
      	      IPS_SetVariableCustomProfile($ips_var, "SNMP_CapacityTB");
            if($convertType == "Temperature")
         	   IPS_SetVariableCustomProfile($ips_var, "SNMP_Temperature");
            if($convertType == "FanSpeed")
            	IPS_SetVariableCustomProfile($ips_var, "SNMP_FanSpeed");
            if($convertType == "SmartStatus")
            	IPS_SetVariableCustomProfile($ips_var, "SNMP_SmartStatus");
        }

        $count = count($this->snmpobj);
        array_push($this->snmpobj, new SNMPObj($oid, $desc, $convertType, $ips_var));
        $count = count($this->snmpobj);
        if($this->debug) echo "New SNMPObj (".$oid."/".$convertType.") registered, now monitoring '$count' snmp variables\n";
    }

    //startet eine Abfrage am SNMP Server und aktualisiert die IPS-Variablen der registrierten
    //SNMP Objekte
    public function update(){
        if($this->debug) echo "Updating ". count($this->snmpobj) ." variable(s)\n";

        foreach($this->snmpobj as $obj){
            $oid = ltrim($obj->OID,".");
            $exec_param =" /h:". $this->host ." /c:". $this->community ." /o:". $oid ." /v";
            if($this->debug) echo "Execute SNMP-Query: ". $this->binary, "$exec_param\n";
            $obj->value = trim(IPS_Execute($this->binary, $exec_param, false, true));
            if($this->debug) echo "Result of ". $obj->desc .": ". $obj->value ."\n";

            if($obj->convertType == "CapacityMB") $obj->value = $this->convertCapacity($obj->value, "MB");
            if($obj->convertType == "CapacityGB") $obj->value = $this->convertCapacity($obj->value, "GB");
            if($obj->convertType == "CapacityTB") $obj->value = $this->convertCapacity($obj->value, "TB");
            if($obj->convertType == "Temperature") $obj->value = $this->convertTemperature($obj->value);
            if($obj->convertType == "FanSpeed") $obj->value = $this->convertFanSpeed($obj->value);
            if($obj->convertType == "SmartStatus") $obj->value = $this->convertSmartStatus($obj->value);

				if($obj->convertType == "Counter32")
				   {
					$intl=PHP_INT_SIZE*2;
					$j=strlen($obj->value);
					$z=array();
					$i=$intl - ($j % $intl);
					$k=0;
					//echo "Counter32 Umrechnung: String ".$obj->value." ist ".$j." Zeichen lang\n";
					while ($k<$j)
						{
						$zi=$i - ($i % $intl);
						$zii=$zi/$intl;
						if (isset($z[$zii])==true)
							{
							$z[$zii]=$z[$zii]*10+(integer)substr($obj->value,$k,1);
							}
						else
						   {
							$z[$zii]=	(integer)substr($obj->value,$k,1);
							}
						//echo "**".$z[$zii]."*".$i." ".$zi." \n";
						$i++;$k++;
						}
					$ips_vare=IPS_GetObjectIDByName((IPS_GetName($obj->ips_var)."ext"),IPS_GetParent($obj->ips_var));
					$ips_varc=IPS_GetObjectIDByName((IPS_GetName($obj->ips_var)."chg"),IPS_GetParent($obj->ips_var));
					//echo "Alter Wert : ".GetValue($obj->ips_var).GetValue($ips_vare)."\n";
					if ($z[0]>=GetValue($obj->ips_var))
					   { /* kein Übertrag */
					   $a=($z[0]-GetValue($obj->ips_var));
					   for ($i=0;$i<$intl;$i++) $a*=10;
					   $a+=($z[1]-GetValue($ips_vare));
		            SetValue($obj->ips_var, $z[0]);
		            SetValue($ips_vare,$z[1]);
		            SetValue($ips_varc,$a);
		            }
		         else
		            {
		            /* Übertrag, zu schwierig zum nachdenken, Wert einfach auslassen */
		            }
					echo "           Neuer Wert : ".$z[0].substr(("0000000000000".(string)$z[1]),-8)."  Differenz : ".$a."   ".($a/1024/1024)." MByte. \n";
					}
				else
				   {
	            SetValue($obj->ips_var, $obj->value);
	            }
        }
    }

    /*
	  *  prüfe um welchen SNMP Rückgabetyp es sich handelt
     *  returns 3 => String / 1 => Integer / 2 => Float
     */

	private function getSNMPType($oid)
		{
      $oid = ltrim($oid,".");
      $exec_param =" /h:". $this->host ." /c:". $this->community ." /o:". $oid;
      $result = IPS_Execute($this->binary, $exec_param, false, true);
      $pos_start = stripos ($result, "Type=");
      $pos_end = $stop=strpos($result,"\n",$pos_start);
      $type = substr($result, $pos_start + 5, $pos_end - $pos_start-6);
		echo "Neuer Variable mit Typ : \"".$type."\"\n";
      switch($type)
			{
         case "OctetString":
            return self::tSTRING;
         case "Integer":
            return self::tINT;
         case "TimeTicks":
            return self::tFLOAT;
         case "Counter32":
            return self::tLONG;
			default:
            return self::tSTRING;
        }
    	}

	/*
	 *    läuft einen kompletten SNMP Pfad durch, praktisch für Tabellen, diese werden automatisch umgewandelt
	 */

	 function walkSNMP($oid='')
	 	{
	 	if ($oid=='')
	 	   {
	      $exec_param =" /h:". $this->host ." /c:". $this->community;
	      }
	   else
	      {
         $oid = ltrim($oid,".");   /* ersten Punkt entfernen */
         $exec_param =" /h:". $this->host ." /c:". $this->community ." /o:". $oid." /s";
	      }
      $result = IPS_Execute($this->binary, $exec_param, false, true);
      //echo $result;
      $csv="";
      $oidTable=array();
      $oidTableIndex=array();
		while ($start=stripos($result,"OID="))
		   {
		   $stop=strpos($result,"\n",$start);
		   //echo "gefunden auf ".$start." und ende auf ".$stop."\n";
		   $oiditem=substr($result,$start+4,$stop-$start-5);
			$csv.=$oiditem.";";
			$oidTable[$oiditem]["Oid"]=substr($oiditem,strlen($oid));

			$result=substr($result,$stop);
			$start=stripos($result,"Path=");
		   $stop=strpos($result,"\n",$start);
		   $path=substr($result,$start+5,$stop-$start-6);
			$csv.=$path.";";
			$oidTable[$oiditem]["Path"]=$path;
			$pathItem=explode(".",$path);
			$anzahl=sizeof($pathItem)-1;
			$index1=$pathItem[$anzahl];
			$index2=$pathItem[$anzahl-1];
			//echo "Einzelne Objekte mit Grösse : ".($anzahl+1)."  letzter Eintrag : ".$pathItem[$anzahl]."\n";
			//print_r($pathItem);

			$result=substr($result,$stop);
			$start=stripos($result,"Type=");
		   $stop=strpos($result,"\n",$start);
		   $type=substr($result,$start+5,$stop-$start-6);
			$csv.=$type.";";
			$oidTable[$oiditem]["Type"]=$type;

			$result=substr($result,$stop);
			//echo substr($result,0,100);
			$start=stripos($result,"Value=");
		   $stop=strpos($result,"\n",$start);
		   $value=substr($result,$start+7,$stop-$start-9);       /* Anführungszeichen vorne und hinten eliminieren */
		   switch ($type)
		      {
            case "Integer":
            case "Gauge":
            case "Counter32":
               $valueCorr=(integer)$value;
               break;
		      default:
               $valueCorr=$value;
		         break;
		      }
			$csv.=$value."\n";
			$oidTable[$oiditem]["Value"]=$valueCorr;
			$oidTableIndex[$index1][$index2]=$valueCorr;

			$result=substr($result,$stop);
			}
		$this->lastwalk_csv=$csv;
		$this->lastwalk=$oidTableIndex;

      //print_r($oidTable);
      return $csv;
      }

	/* Ergebnisse des lastwalk als array ausgeben */

	 function walkSNMPresult($filter=array())
	 	{
	 	$size=sizeof($filter);
	 	//echo "Es sind ".($size/2)." Filter aktiviert.\n";
	 	if ($size==0)
	 	   {
		 	return $this->lastwalk;
		 	}
		else
		   {
			$i=0;
			$result=$this->lastwalk;
			while ($i<$size)
			   {
				//echo "   ** Iteration ".$i." mit OID : ".$filter[$i]." und Filter : ".$filter[$i+1]." auf diesem Array:\n";
				//print_r($result);
				$result=$this->searchArray($result,$filter[$i],$filter[$i+1]);
				$i+=2;
		      }
		   return $result;
		   }
	 	}

	private function searchArray($result,$oid,$filter)
	   {
	   $result1=array();
	   foreach($result as $entry)
		   {
		   if (isset($entry[$oid])==true)
		      {
		      if ((string)$entry[$oid]==$filter)
		         {
					//echo "gefunden";
			      //print_r($entry);
		         $result1[]=$entry;
		         }
		      }
		   }
		return $result1;
		}

	/* Ergebnisse des lastwalk als csv Formatierung ausgeben */

	 function walkSNMPcsv($filter=array())
	 	{
	 	return $this->lastwalk_csv;
	 	}

	function print_snmpobj()
	   {
	   print_r($this->snmpobj);
		}

	function CapacityRater($input)
	 	{
	 	$unit="Byte";
	 	if ($input > 1024)
	 	   {
	 		$unit="kByte";
	 		$input/=1024;
	 		}
	 	if ($input > 1024)
	 	   {
	 		$unit="MByte";
	 		$input/=1024;
	 		}
	 	if ($input > 1024)
	 	   {
	 		$unit="GByte";
	 		$input/=1024;
	 		}
	 	if ($input > 1024)
	 	   {
	 		$unit="TByte";
	 		$input/=1024;
	 		}
	 	return ((string)round($input,3)." ".$unit);
     }



    //returns -1 on error
    private function convertCapacity($input, $unit){
        $pos = stripos($input, "MB");
        if($pos === false) {
            $pos = stripos($input, "GB");
            if($pos === false) {
                $pos = stripos($input, "TB");
                if($pos === false) {
                    return -1;
                }else{
                    $funit = "TB";
                }
            } else{
                $funit = "GB";
            }
        }else{
            $funit = "MB";
        }

        $result = substr($input, 0, $pos);
        $result = trim($result);

        switch ($funit){
            case "GB":
            $result = $result*1000;
            break;
            case "TB":
            $result = $result*1000*1000;
            break;
        }

        switch($unit){
            case "MB":
            return round($result);
            case "GB":
            return round($result / 1000);
            case "TB";
            return round($result / 1000000);
        }
    }

    //returns -1 on error
    private function convertTemperature($input){
        $pos = stripos($input, "C");
        if($pos === false){
            $result = -1;
        }else{
            $result = substr($input, 0, $pos);
            $result = round(trim($result));
        }
        return $result;
    }

    //returns -1 on error
    private function convertFanSpeed($input){
        $pos = stripos($input, "RPM");
        if($pos === false){
            $result = -1;
        }else{
            $result = substr($input, 0, $pos);
            $result = round(trim($result));
        }
        return $result;
    }

    private function convertSmartStatus($input){
        $pos = stripos($input, "GOOD");
        if($pos === false){
            return false;
        }else{
            return true;
        }
    }

}

class SNMPObj
{
    //Objekteigenschaften
    public $OID;                     //SNMP Message ID
    public $desc;                    //Beschreibung
    public $value;                   //Wert
    public $convertType;          //Typ-Converter
    public $ips_var;                   //ID der IPS-Variable welche den SNMP Wert speichert

    public function __construct($OID, $desc, $convertType, $ips_var){
        $this->OID                    = $OID;
        $this->desc                    = $desc;
        $this->convertType    = $convertType;
        $this->ips_var             = $ips_var;
    }
}

?>

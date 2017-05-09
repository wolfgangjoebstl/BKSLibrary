<?

   /**
    * @class IPSComponentSensor_Motion
    *
    * Definiert ein IPSComponentSensor_Motion Object, das ein IPSComponentSensor Object für einen Bewegungsmelder implementiert.
    *
    * @author Wolfgang Jöbstl
    * @version
    *   Version 2.50.1, 09.06.2012<br/>
    */
	 
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	IPSUtils_Include ('IPSComponentSensor.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');
	IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");	

	/******************************************************************************************************
	 *
	 *   Class IPSComponentSensor_Motion
	 *
	 ************************************************************************************************************/

	class IPSComponentSensor_Motion extends IPSComponentSensor {

		private $tempObject;
		private $RemoteOID;
		private $tempValue;
		private $installedmodules;
		private $remServer;

		/**
		 * @public
		 *
		 * Initialisierung eines IPSModuleSensor_IPStemp Objektes
		 *
		 * @param string $tempObject Licht Object/Name (Leuchte, Gruppe, Programm, ...)
		 * @param integer $RemoteOID OID die gesetzt werden soll
		 * @param string $tempValue Wert für Beleuchtungs Änderung
		 */
		public function __construct($var1=null, $lightObject=null, $lightValue=null)
			{
		   //echo "Build Motion Sensor with ".$var1.".\n";
			$this->tempObject   = $lightObject;
			$this->RemoteOID    = $var1;
			$this->tempValue    = $lightValue;
			
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();
			if (isset ($this->installedmodules["RemoteAccess"]))
				{
				IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
				$this->remServer	  = RemoteAccessServerTable();
				}
			else
				{								
				$this->remServer	  = array();
				}
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
		public function HandleEvent($variable, $value, IPSModuleSensor $module)
			{
			echo "Bewegungs Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			IPSLogger_Dbg(__file__, 'HandleEvent: Bewegungs Message Handler für VariableID '.$variable.'('.IPS_GetName($variable).') mit Wert '.$value);

			$log=new Motion_Logging($variable);
			$result=$log->Motion_LogValue();
			
			print_r($this->RemoteOID);
			print_r($this->remServer);
			
			if ($this->RemoteOID != Null)
				{
				$params= explode(';', $this->RemoteOID);
				foreach ($params as $val)
					{
					$para= explode(':', $val);
					//echo "Wert :".$val." Anzahl ",count($para)." \n";
					if (count($para)==2)
						{
						$Server=$this->remServer[$para[0]]["Url"];
						if ($this->remServer[$para[0]]["Status"]==true)
							{
							$rpc = new JSONRPC($Server);
							$roid=(integer)$para[1];
							//echo "Server : ".$Server." Name ".$para[0]." Remote OID: ".$roid."\n";
							/* bei setValueBoolean muss sichergestellt sein dass gegenüberliegender Server auch auf Boolean formattiert ist. */
							$rpc->SetValueBoolean($roid, (boolean)$value);
							}
						}
					}
				}
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
		public function GetComponentParams() 
			{
			return get_class($this);
			}

	}

	/******************************************************************************************************
	 *
	 *   Class Motion_Logging
	 *
	 ************************************************************************************************************/
	
	class Motion_Logging extends Logging
		{

		private $variable;
		private $variablename;
		private $MoveAuswertungID;
		
		private $configuration;
		
		/* zusaetzliche Variablen für DetectMovement Funktionen, Detect Movement ergründet Bewegungen im Nachhinein */
		private $EreignisID;
		private $GesamtID;
		private $GesamtCountID;
		private $variableLogID;
		private $motionDetect_NachrichtenID;
		private $motionDetect_DataID;
				
		/**********************************************************************
		 * 
		 * Construct und gleichzeitig eine Variable zum Motion Logging hinzufügen. Es geht nur eine Variable gleichzeitig
		 * es werden alle notwendigen Variablen erstmalig angelegt, bei Set_logValue werden keine Variablen angelegt, nur die Register gesetzt
		 *
		 *************************************************************************/
		 	
		function __construct($variable=null)
			{
			echo "Construct IPSComponentSensor Motion Logging for Variable ID : ".$variable."\n";
			$this->variable=$variable;
			$result=IPS_GetObject($variable);
			$resultParent=IPS_GetObject((integer)$result["ParentID"]);
			if ($resultParent["ObjectType"]==1)     // Abhängig vom Typ entweder Parent (typischerweise Homematic) oder gleich die Variable für den Namen nehmen
				{
				$this->variablename=IPS_GetName((integer)$result["ParentID"]);
				}
			else
				{
				$this->variablename=IPS_GetName($variable);
				}

			$this->configuration=get_IPSComponentLoggerConfig();


			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();
			$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
			echo "  Kategorien im Datenverzeichnis : ".$CategoryIdData." (".IPS_GetName($CategoryIdData).").\n";
			$name="Bewegung-Nachrichten";
			$vid1=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($vid1==false)
				{
				$vid1 = IPS_CreateCategory();
				IPS_SetParent($vid1, $CategoryIdData);
				IPS_SetName($vid1, $name);
				IPS_SetInfo($vid1, "this category was created by script. ");
				}
			$name="Bewegung-Auswertung";
			$MoveAuswertungID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($MoveAuswertungID==false)
				{
				$MoveAuswertungID = IPS_CreateCategory();
				IPS_SetParent($MoveAuswertungID, $CategoryIdData);
				IPS_SetName($MoveAuswertungID, $name);
				IPS_SetInfo($MoveAuswertungID, "this category was created by script. ");
				}
			$this->MoveAuswertungID=$MoveAuswertungID;
			if ($variable<>null)
				{
				/* lokale Spiegelregister aufsetzen */
				echo 'DetectMovement Construct: Variable erstellen, Basis ist '.$variable.' Parent '.$this->variablename.' in '.$MoveAuswertungID;
				$variabletyp=IPS_GetVariable($variable);
				if ($variabletyp["VariableProfile"]!="")
					{  /* Formattierung vorhanden */
					echo " mit Wert ".GetValueFormatted($variable)."\n";
					IPSLogger_Dbg(__file__, 'CustomComponent Construct: Variable erstellen, Basis ist '.$variable.' Parent '.$this->variablename.' in '.$MoveAuswertungID." mit Wert ".GetValueFormatted($variable));
					}
				else
					{
					echo " mit Wert ".GetValue($variable)."\n";
					IPSLogger_Dbg(__file__, 'CustomComponent Construct: Variable erstellen, Basis ist '.$variable.' Parent '.$this->variablename.' in '.$MoveAuswertungID." mit Wert ".GetValue($variable));
					}				
				$this->variableLogID=CreateVariable($this->variablename,0,$this->MoveAuswertungID, 10,'~Motion',null,null );
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				IPS_SetVariableCustomProfile($this->variableLogID,'~Motion');
				AC_SetLoggingStatus($archiveHandlerID,$this->variableLogID,true);
				AC_SetAggregationType($archiveHandlerID,$this->variableLogID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				}
			
			/* DetectMovement Spiegelregister und statische Anwesenheitsauswertung, nachtraeglich */
			if (isset ($this->installedmodules["DetectMovement"]))
				{
				/* nur wenn Detect Movement installiert ist ein Motion Log fuehren */
				$moduleManager_DM = new IPSModuleManager('DetectMovement');     /*   <--- change here */
				$CategoryIdData     = $moduleManager_DM->GetModuleCategoryID('data');
				//echo "  Datenverzeichnis Category Data :".$CategoryIdData."\n";
				$name="Motion-Nachrichten";
				$vid=@IPS_GetObjectIDByName($name,$CategoryIdData);
				if ($vid==false)
					{
					$vid = IPS_CreateCategory();
					IPS_SetParent($vid, $CategoryIdData);
      				IPS_SetName($vid, $name);
	      			IPS_SetInfo($vid, "this category was created by script. ");
	      			}
				$this->motionDetect_NachrichtenID=$vid;
				$name="Motion-Detect";
				$mdID=@IPS_GetObjectIDByName($name,$CategoryIdData);
				if ($mdID==false)
				   	{
					$mdID = IPS_CreateCategory();
   	   				IPS_SetParent($mdID, $CategoryIdData);
      				IPS_SetName($mdID, $name);
	      			IPS_SetInfo($mdID, "this category was created by script. ");
	      			}
				$this->motionDetect_DataID=$mdID;
				if ($variable<>null)
					{
					$this->variable=$variable;
					$result=IPS_GetObject($variable);
					$this->variablename=IPS_GetName((integer)$result["ParentID"]);
					echo "Construct Motion Logging for DetectMovement, Uebergeordnete Variable : ".$this->variablename."\n";
					$directory=$this->configuration["LogDirectories"]["MotionLog"];
					mkdirtree($directory);
					$filename=$directory.$this->variablename."_Motion.csv";

					$variablename=str_replace(" ","_",$this->variablename)."_Ereignisspeicher";
					$erID=CreateVariable($variablename,3,$mdID, 10, '', null );
					echo "  Ereignisspeicher aufsetzen        : ".$erID." \n";
					$this->EreignisID=$erID;
					}
				$variablename="Gesamt_Ereignisspeicher";
				$erID=CreateVariable($variablename,3,$mdID, 0, '', null );
				$this->GesamtID=$erID;
				echo "  Gesamt Ereignisspeicher aufsetzen : ".$erID." \n";
				$variablename="Gesamt_Ereigniszaehler";
				$erID=CreateVariable($variablename,1,$mdID, 0, '', null );
				$this->GesamtCountID=$erID;
				echo "  Gesamt Ereigniszähler aufsetzen   : ".$erID." \n";
			   	//print_r($this);
				$this->variableLogID=CreateVariable($this->variablename,0,$MoveAuswertungID, 10, '~Motion', null );  /* lege Typ Boolean an */
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				IPS_SetVariableCustomProfile($this->variableLogID,'~Motion');
      			AC_SetLoggingStatus($archiveHandlerID,$this->variableLogID,true);
				AC_SetAggregationType($archiveHandlerID,$this->variableLogID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				}
		
			   //echo "Uebergeordnete Variable : ".$this->variablename."\n";
			$directories=get_IPSComponentLoggerConfig();
			$directory=$directories["LogDirectories"]["HumidityLog"];
			mkdirtree($directory);
			$filename=$directory.$this->variablename."_Bewegung.csv";
			parent::__construct($filename);
			}

		/**********************************************************************
		 * 
		 * Eine Variable zum Motion Logging hinzufügen. Es geht nur eine Variable gleichzeitig
		 *
		 *************************************************************************/

		function Set_LogValue($variable)
			{
			if ($variable<>null)
				{
				echo "Add Variable ID : ".$variable." (".IPS_GetName($variable).") für IPSComponentSensor Motion Logging.\n";
				$this->variable=$variable;
				$result=IPS_GetObject($variable);
				$resultParent=IPS_GetObject((integer)$result["ParentID"]);
				if ($resultParent["ObjectType"]==1)     // Abhängig vom Typ entweder Parent (typischerweise Homematic) oder gleich die Variable für den Namen nehmen
					{
					$this->variablename=IPS_GetName((integer)$result["ParentID"]);
					}
				else
					{
					$this->variablename=IPS_GetName($variable);
					}
				/* lokale Spiegelregister aufsetzen */
				echo 'DetectMovement Construct: Variable erstellen, Basis ist '.$variable.' Parent '.$this->variablename.' in '.$this->MoveAuswertungID;
				$variabletyp=IPS_GetVariable($variable);
				if ($variabletyp["VariableProfile"]!="")
					{  /* Formattierung vorhanden */
					echo " mit Wert ".GetValueFormatted($variable)."\n";
					IPSLogger_Dbg(__file__, 'CustomComponent Construct: Variable erstellen, Basis ist '.$variable.' Parent '.$this->variablename.' in '.$this->MoveAuswertungID." mit Wert ".GetValueFormatted($variable));
					}
				else
					{
					echo " mit Wert ".GetValue($variable)."\n";
					IPSLogger_Dbg(__file__, 'CustomComponent Construct: Variable erstellen, Basis ist '.$variable.' Parent '.$this->variablename.' in '.$this->MoveAuswertungID." mit Wert ".GetValue($variable));
					}				
				$this->variableLogID=CreateVariable($this->variablename,0,$this->MoveAuswertungID, 10,'~Motion',null,null );
				}
			
			/* DetectMovement Spiegelregister und statische Anwesenheitsauswertung, nachtraeglich */
			if (isset ($this->installedmodules["DetectMovement"]))
				{
				/* nur wenn Detect Movement installiert ist ein Motion Log fuehren */
				echo "Construct Motion Logging for DetectMovement, Uebergeordnete Variable : ".$this->variablename."\n";
				$variablename=str_replace(" ","_",$this->variablename)."_Ereignisspeicher";
				$erID=CreateVariable($variablename,3, $this->motionDetect_DataID, 10, '', null );
				echo "  Ereignisspeicher aufsetzen        : ".$erID." \n";
				$this->EreignisID=$erID;
				}
	   	}
	   
		function Motion_LogValue()
			{
			echo "Lets log motion, Variable ID : ".$this->variable." (".IPS_GetName($this->variable)."), aufgerufen von Script ID : ".$_IPS['SELF']." (".IPS_GetName($_IPS['SELF']).") ";
			$variabletyp=IPS_GetVariable($this->variable);
			if ($variabletyp["VariableProfile"]!="")
				{  /* Formattierung vorhanden */
				$resultLog=GetValueFormatted($this->variable);
				echo " mit formattiertem Wert : ".GetValueFormatted($this->variable)."\n";
				IPSLogger_Dbg(__file__, 'DetectMovement Log: Lets log motion '.$this->variable." (".IPS_GetName($this->variable).") ".$_IPS['SELF']." (".IPS_GetName($_IPS['SELF']).") mit Wert ".GetValueFormatted($this->variable));
				}
			else
				{
				$resultLog=GetValue($this->variable);				
				echo " mit Wert : ".GetValue($this->variable)."\n";
				IPSLogger_Dbg(__file__, 'DetectMovement Log: Lets log motion '.$this->variable." (".IPS_GetName($this->variable).") ".$_IPS['SELF']." (".IPS_GetName($_IPS['SELF']).") mit Wert ".GetValue($this->variable));
				}
			$result=GetValue($this->variable);
			$delaytime=$this->configuration["LogConfigs"]["DelayMotion"];
			if ($result==true)
				{
				SetValue($this->variableLogID,$result);
				echo "Jetzt wird der Timer im selben verzeichnis wie Script gesetzt : ".$this->variable."_EVENT"."\n";
		     	$now = time();
				$EreignisID = @IPS_GetEventIDByName($this->variable."_EVENT", IPS_GetParent($_IPS['SELF']));
				if ($EreignisID === false)
					{ //Event nicht gefunden > neu anlegen
					$EreignisID = IPS_CreateEvent(1);
   	         		IPS_SetName($EreignisID,$this->variable."_EVENT");
      	      		IPS_SetParent($EreignisID, IPS_GetParent($_IPS['SELF']));
         	   		}
        		IPS_SetEventCyclic($EreignisID,0,1,0,0,1,$delaytime);      /* konfigurierbar, zB alle 30 Minuten, d.h. 30 Minuten kann man still sitzen bevor keine Bewegung mehr erkannt wird */
				IPS_SetEventCyclicTimeBounds($EreignisID,time(),0);  /* damit die Timer hintereinander ausgeführt werden */
				IPS_SetEventScript($EreignisID,"if (GetValue(".$this->variable.")==false) { SetValue(".$this->variableLogID.",false); IPS_SetEventActive(".$EreignisID.",false);} \n");
	   			IPS_SetEventActive($EreignisID,true);
				}
			//print_r($this);
			if (isset ($this->installedmodules["DetectMovement"]))
				{
				/* Achtung die folgenden Werte haben keine Begrenzung, sicherstellen dass String Variablen nicht zu gross werden. */
				$EreignisVerlauf=GetValue($this->EreignisID);
				$GesamtVerlauf=GetValue($this->GesamtID);
				$GesamtZaehler=GetValue($this->GesamtCountID);
				if ($GesamtZaehler<STAT_WenigBewegung) {$GesamtZaehler=STAT_WenigBewegung;}
				if (IPS_GetName($this->variable)=="MOTION")
					{
					if (GetValue($this->variable))
						{
						$resultLog="Bewegung";
						//$EreignisVerlauf.=date("H:i").";".STAT_Bewegung.";";
						$Ereignis=time().";".STAT_Bewegung.";";
						$GesamtZaehler+=1;
						$EreignisVerlauf.=$Ereignis;
						$GesamtVerlauf.=$Ereignis;
						}
					else
						{
						$resultLog="Ruhe";
						//$EreignisVerlauf.=date("H:i").";".STAT_WenigBewegung.";";
						$Ereignis=time().";".STAT_WenigBewegung.";";
						$GesamtZaehler-=1;
						if ($GesamtZaehler<STAT_WenigBewegung) {$GesamtZaehler=STAT_WenigBewegung;}
						//$GesamtVerlauf.=date("H:i").";".$GesamtZaehler.";";
						$EreignisVerlauf.=$Ereignis;
						$GesamtVerlauf.=$Ereignis;
						}
					}
				else
					{
					$Ereignis=time().";".STAT_Bewegung.";".time().";".STAT_WenigBewegung.";";
					if (GetValue($this->variable))
						{
						$resultLog="Offen";
						}
					else
						{
						$resultLog="Geschlossen";
						}
					$EreignisVerlauf.=$Ereignis;
					}
				echo "\nEreignisverlauf evaluieren bevor neu geschrieben wird von : ".IPS_GetName($this->EreignisID)." \n";
				SetValue($this->EreignisID,$this->evaluateEvents($EreignisVerlauf));
				echo "\nEreignisverlauf evaluieren bevor neu geschrieben wird von : ".IPS_GetName($this->GesamtID)." \n";
				SetValue($this->GesamtID,$this->evaluateEvents($GesamtVerlauf,60));
				SetValue($this->GesamtCountID,$GesamtZaehler);
			
				/* Routine in Log_Motion uebernehmen */
				IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
				IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
				$DetectMovementHandler = new DetectMovementHandler();
				//print_r($DetectMovementHandler->ListEvents("Motion"));
				//print_r($DetectMovementHandler->ListEvents("Contact"));

				$groups=$DetectMovementHandler->ListGroups();
				foreach($groups as $group=>$name)
					{
					echo "\nDetectMovement Gruppe ".$group." behandeln.\n";
					$config=$DetectMovementHandler->ListEvents($group);
					$status=false;
					foreach ($config as $oid=>$params)
						{
						$status=$status || GetValue($oid);
						echo "  OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)."Status: ".(integer)GetValue($oid)." ".(integer)$status."\n";
						}
					echo "  Gruppe ".$group." hat neuen Status : ".(integer)$status."\n";
					$log=new Motion_Logging($oid);
					$class=$log->GetComponent($oid);
					$statusID=CreateVariable("Gesamtauswertung_".$group,0,IPS_GetParent(intval($log->EreignisID)),10, '~Motion', null,false);
					SetValue($statusID,$status);
					$ereignisID=CreateVariable("Gesamtauswertung_".$group."_Ereignisspeicher",3,IPS_GetParent(intval($log->EreignisID)),0, '', null);
					echo "  EreignisID       : ".$ereignisID." (".IPS_GetName($ereignisID).")\n";
					echo "  Ereignis         : ".$Ereignis."\n";
					//echo "  Size             : ".strlen(GetValue($ereignisID))."\n";
					$EreignisVerlauf=GetValue($ereignisID).$Ereignis;
					//echo "  Ereignis Verlauf : ".$EreignisVerlauf."\n";
					SetValue($ereignisID,$this->addEvents($EreignisVerlauf));
					}
				} /* Ende Detect Motion */
				
			parent::LogMessage($resultLog);
			parent::LogNachrichten($this->variablename." mit Status ".$resultLog);
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

		public function GetComponent() {
			return ($this);
			}

		public function GetEreignisID() {
			return ($this->EreignisID);
			}

		/*************************************************************************************
		Bearbeiten des Eventspeichers
		hier nur überprüfen ober der Eventspeicher nicht zu lang wird

		*************************************************************************************/

		private function addEvents($value)
			{
			/* keine Indizierung auf Herkunft der Variable, nur String Werte evaluieren */
			echo "  Check Eventliste (max 20.000 Eintraege), derzeit Länge Ereignisstring: ".strlen($value)."\n";
			$max=20000;
			$EventArray = explode(";", $value);
		   $array_size = count($EventArray);
         $i = $array_size-2;  /* Array Index geht von 0 bis Länge minus 1 */
         if ($i>0)
            {
            /* Events komprimieren erst wenn gross genug */
				$previous_state=$EventArray[$i];
				$previous_time=(integer)$EventArray[$i-1];
				if ($i>($max*2))
				   {
				   /* events nicht groesser Eintraege werden lassen */
					$indexbefordelete=$max;
					}
				else
					{
					$indexbefordelete=0;
					}

				//echo "Array Size is ".$i."  : last values are ".$previous_state." ? ".$previous_time."\n";
				//echo "      Betrachteter (".$i.") State jetzt ".$previous_state," am ".date("d.m H:i",$previous_time)." \n";
				$i=$i-2;
				$delete=false;
			 	while($i > 0)
 					{
		   		/* Process array data:  Bewegungsmelder kennt nur zwei Zustaende, Bewegung:7 und wenigBewegung:6
						Wenn zwischen 7 und vorher 6 weniger als 15 Minuten vergangen sind den Zustand 6 loeschen
						Wenn 7 auf 7 folgt den juengsten wert 7 loeschen
					*/
					$now_time=$previous_time;
					$bef_time=(integer)$EventArray[$i-1];

					if ($i<$indexbefordelete) {$delete=true;}
					if ($delete==true)
					   {
  					   unset($EventArray[$i+0]);
					   unset($EventArray[$i-1]);
					   }
					$i=$i-2; /* immer zwei Werte, Zeit ueberspringen */
				 	}
				 }
			$value=implode(";",$EventArray);
			return ($value);
			}

		/*************************************************************************************
		Bearbeiten des Eventspeichers


		*************************************************************************************/

		private function evaluateEvents($value, $diftimemax=15)
			{
			/* keine Indizierung auf Herkunft der Variable, nur String Werte evaluieren */
			echo "  Evaluate Eventliste (max 20 Eintraege) : ".$value."\n";
			$EventArray = explode(";", $value);
		   $array_size = count($EventArray);
         $i = $array_size-2;  /* Array Index geht von 0 bis Länge minus 1 */
         if ($i>0)
            {
            /* Events komprimieren erst wenn gross genug */
				$previous_state=$EventArray[$i];
				$previous_time=(integer)$EventArray[$i-1];
				if ($i>40)
				   {
				   /* events nicht groesser als 20 Eintraege werden lassen */
					$indexbefordelete=$i-20;
					}
				else
					{
					$indexbefordelete=0;
					}

				//echo "Array Size is ".$i."  : last values are ".$previous_state." ? ".$previous_time."\n";
				echo "      Betrachteter (".$i.") State jetzt ".$previous_state," am ".date("d.m H:i",$previous_time)." \n";
				$i=$i-2;
				$delete=false;
			 	while($i > 0)
 					{
		   		/* Process array data:  Bewegungsmelder kennt nur zwei Zustaende, Bewegung:7 und wenigBewegung:6
						Wenn zwischen 7 und vorher 6 weniger als 15 Minuten vergangen sind den Zustand 6 loeschen
						Wenn 7 auf 7 folgt den juengsten wert 7 loeschen
					*/
					$now_time=$previous_time;
					$bef_time=(integer)$EventArray[$i-1];

					if ($i<$indexbefordelete) {$delete=true;}
					if ($delete==true)
					   {
  					   unset($EventArray[$i+0]);
					   unset($EventArray[$i-1]);
					   }
					else
					   {
						$dif_time=(($now_time-$bef_time)/60);
						//echo "Betrachteter (".$i.") State jetzt ".$previous_state," am ".date("d.m H:i",$previous_time)." und davor ".$EventArray[$i]." am ".date("d.m H:i",$EventArray[$i-1])." Abstand: ".number_format($dif_time,1,",",".")." Minute \n";
						echo "      Betrachteter (".$i.") State jetzt ".$EventArray[$i]." am ".date("d.m H:i",$EventArray[$i-1])." Abstand: ".number_format($dif_time,1,",",".")." Minute \n";
						switch ($previous_state)
   	  				   {
   	  				   /*****************************************************************************
							 erst einmal Unterscheidung anhand aktuellem Status
							 Bewegung   ->  um so mehr Bewegungssender aktiv sind um so hoeher der Wert
							******************************************************************************/
	 			     		case STAT_Bewegung9:
	 			     		case STAT_Bewegung8:
	 			     		case STAT_Bewegung7:
	 			     		case STAT_Bewegung6:
	 			     		case STAT_Bewegung5:
	 			     		case STAT_Bewegung4:
	 			     		case STAT_Bewegung3:
	 			     		case STAT_Bewegung2:
			   	  	   case STAT_Bewegung:
						      /* Wenn jetzt Bewegung ist unterscheiden ob vorher Bewegung oder wenigBewegung war			   */
		      				switch ($EventArray[$i]) /* Zustand vorher */
								 	{
	 			     		   	case STAT_Bewegung9:
	 			     		   	case STAT_Bewegung8:
	 			     		   	case STAT_Bewegung7:
	 			     		   	case STAT_Bewegung6:
	 			     		   	case STAT_Bewegung5:
	 			     		   	case STAT_Bewegung4:
	 			     		   	case STAT_Bewegung3:
	 			     		   	case STAT_Bewegung2:
	 			     		   	case STAT_Bewegung:
		 							   $previous_state=$EventArray[$i];
						   			$previous_time=(integer)$EventArray[$i-1];
				   				 	/* einfach die aktuellen zwei Einträge loeschen, ich brauche keinen Default Wert */
				   				 	if (isset($EventArray[$i+2]))
											{
											/* nicht zweimal loeschen */
											echo "--->Bewegung, wir loeschen Eintrag ".($i+2)." mit ".$EventArray[$i+2]." am ".date("d.m H:i",$EventArray[$i+1])."\n";
   									 	unset($EventArray[$i+2]);
	  							 			unset($EventArray[$i+1]);
	  							 			}
									 	break;
						 			case STAT_WenigBewegung:
									case STAT_KeineBewegung:
									case STAT_vonzuHauseweg:
									   //echo "Wenig Bewegung: ".$dif_time."\n";
										if (($dif_time<$diftimemax) and ($dif_time>=0))
										   {
										   // Warum mus dif_time >0 sein ????
	  			   						$previous_state=10;    /* default, einen ueberspringen, damit voriger Wert vorerst nicht mehr geloescht werden kann */
		   							 	/* einfach die letzten zwei Einträge loeschen, nachdem Wert kein zweites Mal geloescht werden kann vorerst mit Default Wert arbeiten */
											echo "--->WenigBewegung, wir loeschen Eintrag ".($i)." mit ".$EventArray[$i+0]." am ".date("d.m H:i",$EventArray[$i-1])."\n";
   									 	unset($EventArray[$i+0]);
	   								 	unset($EventArray[$i-1]);
				   				 		}
		   					 		else
		   						 	   {
						    				$previous_state=$EventArray[$i];
									      $previous_time=(integer)$EventArray[$i-1];
											}
									 	break;
							 		default:
								 	   /* Wenn der Defaultwert kommt einfach weitermachen, er kommt schon beim naechsten Durchlauf dran */
				    					$previous_state=$EventArray[$i];
							   	   $previous_time=(integer)$EventArray[$i-1];
							    		break;
								 }
								break;
			   	  	   case STAT_WenigBewegung:
						      /* Wenn jetzt wenigBewegung ist unterscheiden ob vorher Bewegung oder wenigBewegung war			   */
		      				switch ($EventArray[$i]) /* Zustand vorher */
		      				   {
	 			     		   	case STAT_WenigBewegung:
		 							   $previous_state=$EventArray[$i];
						   			$previous_time=(integer)$EventArray[$i-1];
				   				 	/* einfach die aktuellen zwei Einträge loeschen, ich brauche keinen Default Wert */
				   				 	if (isset($EventArray[$i+2]))
											{
											/* nicht zweimal loeschen */
											echo "--->WenigBewegung, wir loeschen Eintrag ".($i+2)." mit ".$EventArray[$i+2]." am ".date("d.m H:i",$EventArray[$i+1])."\n";
   									 	unset($EventArray[$i+2]);
	  							 			unset($EventArray[$i+1]);
	  							 			}
									 	break;
							 		default:
								 	   /* Wenn der Defaultwert kommt einfach weitermachen, er kommt schon beim naechsten Durchlauf dran */
				    					$previous_state=$EventArray[$i];
							   	   $previous_time=(integer)$EventArray[$i-1];
							    		break;
									}
			   	  	      break;
			   	   	case STAT_vonzuHauseweg:
						       /* Wenn zletzt bereits Abwesend erkannt wurde, kann ich von zuHause weg und nicht zu Hause
								    wegfiltern, allerdings ich lasse die Zeit des jetzigen events ,also dem früheren
								    2 eliminiert den vorigen 2 er und lässt aktuelle Zeit
							    */
				   	   	 switch ($EventArray[$i])
								    {
				 					 case STAT_vonzuHauseweg:
				   					 $previous_state=10;    /* default */
				   					 /* einfach von den letzten zwei Einträgen rausloeschen */
			   						 unset($EventArray[$i+0]);
						   			 unset($EventArray[$i-1]);
							 		 break;
						 			 default:
									 	 $previous_state=$EventArray[$i];
						   			 $previous_time=(integer)$EventArray[$i-1];
								 		 break;
							 		 }
								break;
   	  			   	case STAT_Abwesend:
						       /* Wenn zletzt bereits Abwesend erkannt wurde, kann ich von zuHause weg und nicht zu Hause
								    wegfiltern, allerdings ich lasse die Zeit des jetzigen events ,also dem früheren
								    0 übernimmt die Zeit des Vorgängers und eliminiert 0,1 und 2
							     */
					   	    switch ($EventArray[$i])
								    {
			     	   			 case STAT_Abwesend:
									 case STAT_nichtzuHause:
					 				 case STAT_vonzuHauseweg:
						   			 $previous_state=10;    /* default */
   									 /* einfach von den letzten zwei Einträgen die mittleren Werte rausloeschen */
		   							 unset($EventArray[$i+1]);
   									 unset($EventArray[$i+0]);
								 		 break;
					 				 default:
									    $previous_state=$EventArray[$i];
								   	 $previous_time=(integer)$EventArray[$i-1];
								 		 break;
					 				 }
								break;
							default:
							   $previous_state=$EventArray[$i];
	      					$previous_time=(integer)$EventArray[$i-1];
								break;
							}
						}
					$i=$i-2; /* immer zwei Werte, Zeit ueberspringen */
				 	}
				 }
			$value=implode(";",$EventArray);
			return ($value);
			}


		/*************************************************************************************
		Ausgabe des Eventspeichers in lesbarer Form
		erster Parameter true: macht zweimal evaluate
		zweiter Parameter true: nimmt statt dem aktuellem Event den Gesamtereignisspeicher
		*************************************************************************************/

	public function writeEvents($comp=true,$gesamt=false)
		{
		if (isset ($this->installedmodules["DetectMovement"]))
			{
			if ($gesamt)
			   {
  				$value=GetValue($this->GesamtID);
  				$diftimemax=60;
  				}
  			else
  			   {
				$value=GetValue($this->EreignisID);
  				$diftimemax=15;
				}
			/* es erfolgt zwar eine Kompromierung aber keine Speicherung in den Events, das ist nur bei Auftreten eines Events */
			if ($comp)
				{
				$value=$this->evaluateEvents($value, $diftimemax);
				$value=$this->evaluateEvents($value, $diftimemax);
				}
			$EventArray = explode(";", $value);
			echo "Write Eventliste von ".IPS_GetName($this->EreignisID)." : ".$value."\n";

			/* Umsetzung des kodierten Eventarrays in lesbaren Text */
			$event2="";
			$array_size = count($EventArray);
		 	for ($k=1; $k<($array_size); $k++ )
				{
				$event2=$event2.date("d.m H:i",(integer)$EventArray[$k-1])." : ";
				//echo "check : ".$EventArray[$k]."\n";
				switch ($EventArray[$k])
					{
					case STAT_KommtnachHause:
						$event2=$event2."Kommt nach Hause";
		 	   		break;
 	   			case STAT_Bewegung9:
 	   				$event2=$event2."Bewegung 9 Sensoren";
		 	   		break;
					case STAT_Bewegung8:
 	   				$event2=$event2."Bewegung 8 Sensoren";
		 	   		break;
 	   			case STAT_Bewegung7:
 	   				$event2=$event2."Bewegung 7 Sensoren";
		 	   		break;
					case STAT_Bewegung6:
 	   				$event2=$event2."Bewegung 6 Sensoren";
		 	   		break;
 	   			case STAT_Bewegung5:
 	   				$event2=$event2."Bewegung 5 Sensoren";
		 	   		break;
					case STAT_Bewegung4:
 	   				$event2=$event2."Bewegung 4 Sensoren";
		 	   		break;
 	   			case STAT_Bewegung3:
 	   				$event2=$event2."Bewegung 3 Sensoren";
		 	   		break;
					case STAT_Bewegung2:
 	   				$event2=$event2."Bewegung 2 Sensoren";
		 	   		break;
 	   			case STAT_Bewegung:
 	   				$event2=$event2."Bewegung";
		 	   		break;
					case STAT_WenigBewegung:
 	   				$event2=$event2."Wenig Bewegung";
		 	   		break;
					case STAT_KeineBewegung;
 	   				$event2=$event2."Keine Bewegung";
		 	   		break;
					case STAT_Unklar:
		 	   		$event2=$event2."Unklar";
 	   				break;
					case STAT_Undefiniert:
 	   				$event2=$event2."Undefiniert";
 	   				break;
					case STAT_vonzuHauseweg:
 	   				$event2=$event2."Von zu Hause weg";
 	   				break;
					case STAT_nichtzuHause:
 	   				$event2=$event2."Nicht zu Hause";
 	   				break;
					case STAT_Abwesend:
 	   				$event2=$event2."Abwesend";
 	   				break;
					}
				$k++;
      		$event2=$event2."\n";
				}
			return ($event2);
			}
		else
			{
			return ("");
			}		
		} /* ende function */
		
	} /* ende class */	

	/** @}*/
?>
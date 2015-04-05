<?

   /**
    * @class IPSComponentSensor_Temperatur
    *
    * Definiert ein IPSComponentSensor_Temperatur Object, das ein IPSComponentSensor Object für einen Sensor implementiert.
    *
    * @author Wolfgang Jöbstl
    * @version
    *   Version 2.50.1, 09.06.2012<br/>
    */
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	IPSUtils_Include ('IPSComponentSensor.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');
	IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
			
	class IPSComponentSensor_Motion extends IPSComponentSensor {


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
		   //echo "Build Motion Sensor with ".$var1.".\n";
			$this->tempObject   = $lightObject;
			$this->RemoteOID    = $var1;
			$this->tempValue    = $lightValue;
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
			$log=new Motion_Logging($variable);
			$result=$log->Motion_LogValue();
			
			if ($this->RemoteOID != Null)
			   {
				$params= explode(';', $this->RemoteOID);
				print_r($params);
				foreach ($params as $val)
					{
					$para= explode(':', $val);
					echo "Wert :".$val." Anzahl ",count($para)." \n";
            	if (count($para)==2)
               	{
						$Server=$this->remServer[$para[0]];
						echo "Server : ".$Server."\n";
						$rpc = new JSONRPC($Server);
						$roid=(integer)$para[1];
						echo "Remote OID: ".$roid."\n";
						$rpc->SetValue($roid, $value);
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
		public function GetComponentParams() {
			return get_class($this);
		}

	}
	
	class Motion_Logging extends Logging
	   {
	   
	   function __construct($variable=null)
		   {

			IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$result=$moduleManager->GetInstalledModules();
			if (isset ($result["DetectMovement"]))
				{
				/* nur wenn Detect Movement installiert ist ein Motion Log fuehren */
				$moduleManager_DM = new IPSModuleManager('DetectMovement');     /*   <--- change here */
				$CategoryIdData     = $moduleManager_DM->GetModuleCategoryID('data');
				echo "Datenverzeichnis:".$CategoryIdData."\n";
				$name="Motion-Nachrichten";
				$vid=@IPS_GetObjectIDByName($name,$CategoryIdData);
				if ($vid==false)
				   {
					$vid = IPS_CreateCategory();
   	   		IPS_SetParent($vid, $CategoryIdData);
      			IPS_SetName($vid, $name);
	      		IPS_SetInfo($vid, "this category was created by script. ");
	      		}
				$name="Motion-Detect";
				$mdID=@IPS_GetObjectIDByName($name,$CategoryIdData);
				if ($mdID==false)
				   {
					$mdID = IPS_CreateCategory();
   	   		IPS_SetParent($mdID, $CategoryIdData);
      			IPS_SetName($mdID, $name);
	      		IPS_SetInfo($mdID, "this category was created by script. ");
	      		}
	      		
				if ($variable<>null)
				   {
					echo "Construct Motion.\n";
			   	$this->variable=$variable;
				   $result=IPS_GetObject($variable);
				   $this->variablename=IPS_GetName((integer)$result["ParentID"]);
			   	echo "Uebergeordnete Variable : ".$this->variablename."\n";
			   	$directories=get_IPSComponentLoggerConfig();
				   $directory=$directories["MotionLog"];
			   	mkdirtree($directory);
				   $filename=$directory.$this->variablename."_Motion.csv";

	  	      	echo "Ereignisspeicher aufsetzen \n";
  		      	$variablename=str_replace(" ","_",$this->variablename)."_Ereignisspeicher";
	   	   	$erID=CreateVariable($variablename,3,$mdID, 10 );
					$this->EreignisID=$erID;
					parent::__construct($filename,$vid);
				   }
				   
  	      	$variablename="Gesamt_Ereignisspeicher";
	      	$erID=CreateVariable($variablename,3,$mdID, 0 );
				$this->GesamtID=$erID;
				echo "Gesamt Ereignisspeicher aufsetzen: ".$erID." \n";
  	      	$variablename="Gesamt_Ereigniszaehler";
	      	$erID=CreateVariable($variablename,1,$mdID, 0 );
				$this->GesamtCountID=$erID;
				echo "Gesamt Ereigniszähler aufsetzen: ".$erID." \n";
		   	//print_r($this);
				}
	   	}
	   
		function Motion_LogValue()
			{
			echo "Lets log motion\n";
			//print_r($this);
			$EreignisVerlauf=GetValue($this->EreignisID);
			$GesamtVerlauf=GetValue($this->GesamtID);
			$GesamtZaehler=GetValue($this->GesamtCountID);
			if ($GesamtZaehler<STAT_WenigBewegung) {$GesamtZaehler=STAT_WenigBewegung;}
			if (IPS_GetName($this->variable)=="MOTION")
				{
				if (GetValue($this->variable))
					{
					$result="Bewegung";
					//$EreignisVerlauf.=date("H:i").";".STAT_Bewegung.";";
					$EreignisVerlauf.=time().";".STAT_Bewegung.";";
					$GesamtZaehler+=1;
					//$GesamtVerlauf.=date("H:i").";".$GesamtZaehler.";";
					$GesamtVerlauf.=time().";".$GesamtZaehler.";";
					}
				else
					{
					$result="Ruhe";
					//$EreignisVerlauf.=date("H:i").";".STAT_WenigBewegung.";";
					$EreignisVerlauf.=time().";".STAT_WenigBewegung.";";
					$GesamtZaehler-=1;
					if ($GesamtZaehler<STAT_WenigBewegung) {$GesamtZaehler=STAT_WenigBewegung;}
					//$GesamtVerlauf.=date("H:i").";".$GesamtZaehler.";";
					$GesamtVerlauf.=time().";".$GesamtZaehler.";";
					}
				}
			else
				{
				$EreignisVerlauf.=time().";".STAT_Bewegung.";";
				$EreignisVerlauf.=time().";".STAT_WenigBewegung.";";
				if (GetValue($this->variable))
					{
					$result="Offen";
					}
				else
					{
					$result="Geschlossen";
					}
				}
			echo "\n".IPS_GetName($this->EreignisID)." ";
			SetValue($this->EreignisID,$this->evaluateEvents($EreignisVerlauf));
			echo "\n".IPS_GetName($this->GesamtID)." ";
			SetValue($this->GesamtID,$this->evaluateEvents($GesamtVerlauf,60));
			SetValue($this->GesamtCountID,$GesamtZaehler);
			parent::LogMessage($result);
			parent::LogNachrichten($this->variablename." mit Status ".$result);
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


		private function evaluateEvents($value, $diftimemax=15)
			{
			/* keine Indizierung auf Herkunft der Variable, nur String Werte evaluieren */
			echo "Evaluate Eventliste : ".$value."\n";
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
				echo "Betrachteter (".$i.") State jetzt ".$previous_state," am ".date("d.m H:i",$previous_time)." \n";
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
						echo "Betrachteter (".$i.") State jetzt ".$EventArray[$i]." am ".date("d.m H:i",$EventArray[$i-1])." Abstand: ".number_format($dif_time,1,",",".")." Minute \n";
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

	   
	   }

	/** @}*/
?>

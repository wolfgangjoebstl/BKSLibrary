<?

/*****************************************************************************************************
 *
 * Hier besondere Alexa Mangement Funktionen zusammenfassen. Hier wird die Alexa Konfiguration ausgegeben und für Debugzwecke analysiert.
 * Die Config AUswertung greift auch auf Remote Server zu.
 * Temperaturwerte werden nur abgefragt. Kommen aus den Spiegelregistern oder aud den RemoteAccess Registern.
 * Stellwerte veraendern eine lokale Variable die auch gleichzeitig ein Script aufruft. Der Entry in das Script Austosteuerung_AlexaControl ist VoiceControl.
 * Bei der Config überprüfen ob das Autorun Script auch gesetzt ist.
 *
 *
 **************************************************************************************************************/ 

class AutosteuerungAlexaHandler 
	{

    private $instances;
    private $rpc;               // remoteAccess for Server
    private $countAlexa;        // 0 means there are no Alexas, positive number is lokal number Alexas, negative number is number of remote Alexas
    private $configAlexa;

	public function __construct() 
        {
		$this->instances=IPS_GetInstanceListByModuleID("{CC759EB6-7821-4AA5-9267-EF08C6A6A5B3}");
        $this->countAlexa = sizeof($this->instances);
        if ($this->countAlexa==0)        // keine lokalen Alexas, Remote weitersuchen
            {
            $remServer=RemoteAccessServerTable();
            //print_r($remServer);         
            $found=false;
            foreach ($remServer as $Name => $Server)
                {
                if (isset($Server["Alexa"])===true ) $found=$Name;
                }
            if ($found !== false)
                {
                $ServerUrl=$remServer[$found]["Url"];
                $this->rpc = new JSONRPC($ServerUrl);
                $this->instances=$this->rpc->IPS_GetInstanceListByModuleID("{CC759EB6-7821-4AA5-9267-EF08C6A6A5B3}");
                $this->countAlexa = -sizeof($this->instances);
                $this->configAlexa=$this->rpc->IPS_GetConfiguration($this->instances[0]);
                }    
        
            }
        else $this->configAlexa=IPS_GetConfiguration($this->instances[0]);    
		}

    public function getInstances()
        {
        return($this->instances);
        }

    public function getCountInstances()
        {
        return($this->countAlexa);
        }

    public function getAlexaConfig()
        {
        $alexaConfig=array(); 
        $configAlexa=array();             
	    if ($this->countAlexa != 0) 
            {
        	$configStruct=json_decode($this->configAlexa);
	        print_r($configStruct);
            foreach ($configStruct as $typ=>$conf)
                {
        	    $confStruct=json_decode($conf);
                //echo "    ".$typ."    ".$conf."\n";
		        //foreach ($confStruct as $struct) print_r($struct);            
                switch ($typ)
    	        	{
					case "DeviceSpeaker":
                        /* Setze die Laustärke von Denon auf 10 */
				        $id="SpeakerID";
                        break;					
	    	        case "DeviceGenericSwitch":
		            case "DeviceLightSwitch":
				        $id="PowerControllerID";
                        break;
        			case "DeviceDeactivatableScene":
		        		$id="SceneControllerDeactivatableActivateID";
				        break;
            		case "DeviceSimpleScene":
	            		$id="SceneControllerSimpleID";
		    	        break;
            		case "DeviceGenericSlider":
	            		$id="PercentageControllerID";
		    	        break;					
            		case "DeviceLightColor":
	            		$id="ColorControllerID";
		    	        break;	
                    case "DeviceLightExpert":
                        /* Alexa stelle Wohnzimmerlicht auf weiss 
                         * es werden gleich drei Parameter uebergeben 
                         * Beispiel: {"ID":"18","Name":"Wohnzimmer Deckenlampe Zwei","PowerControllerID":30308,"BrightnessOnlyControllerID":56174,"ColorOnlyControllerID":12135}}   
                         */
                    	break;				
            		case "DeviceLightDimmer":
	            		$id="BrightnessControllerID";
		    	        break;	
                    case "DeviceLock":
	            		$id="LockControllerID";
		    	        break;
            		case "DeviceTemperatureSensor":
	                	$id="TemperatureSensorID";
    		            break;											
            		case "DeviceThermostat":
                        /* Alexa Heizung auf 23 Grad, Alexa Heizung wärmer   */ 
	            		$id="ThermostatControllerID";
    	    	        break;											
            		default:
                        echo "Fehler: kenne den Identifier $typ in der Alexa Config noch nicht.\n";
                        echo "    ".$typ."    ".$conf."\n";
		    	        break;
                    } 
            	foreach ($confStruct as $struct) 
	            	{                       
                    if ($this->countAlexa > 0)
                        {   // lokal Alexa
                        $Name=IPS_GetName($struct->$id);        // same structure as for remote, idea ist to reduce the number of accesses to remote server
                        $parent=IPS_GetParent($struct->$id);
                        $parent2=IPS_GetParent($parent);
                        $parent3=IPS_GetParent($parent2);
                        $NameParent=IPS_GetName($parent);
                        $NameParent2=IPS_GetName($parent2);
                        $NameParent3=IPS_GetName($parent3);
		    	        //print_r($struct);
        				if ( IPS_ObjectExists($struct->$id)==true )
		        			{
    			            $alexaConfig[$struct->$id]["OID"]=$struct->$id;
                			$alexaConfig[$struct->$id]["OID_Name"]=$Name;
	                		$alexaConfig[$struct->$id]["Pfad"]=$Name."/".$NameParent."/".$NameParent2."/".$NameParent3;
       	                	$alexaConfig[$struct->$id]["Type"]=$typ;
        			        $alexaConfig[$struct->$id]["Name"]=$struct->Name;
                			if ($id=="SceneControllerDeactivatableActivateID") $alexaConfig[$struct->$id]["Script"]=$struct->SceneControllerDeactivatableDeactivateID;						
                            }
				        else echo "Fehler, ".$struct->$id." nicht vorhanden. aus Alexa Config loeschen.\n";							
                        }
                    else
                        {  // remote Alexa
                        $Name=$this->rpc->IPS_GetName($struct->$id);
                        $parent=$this->rpc->IPS_GetParent($struct->$id);
                        $parent2=$this->rpc->IPS_GetParent($parent);
                        $parent3=$this->rpc->IPS_GetParent($parent2);
                        $NameParent=$this->rpc->IPS_GetName($parent);
                        $NameParent2=$this->rpc->IPS_GetName($parent2);
                        $NameParent3=$this->rpc->IPS_GetName($parent3);
  			            //print_r($struct);
			            if ( $this->rpc->IPS_ObjectExists($struct->$id)==true )
				            {
       	    		        $alexaConfig[$struct->$id]["OID"]=$struct->$id;
	    	    	        $alexaConfig[$struct->$id]["OID_Name"]=$Name;
		    	            $alexaConfig[$struct->$id]["Pfad"]=$Name."/".$NameParent."/".$NameParent2."/".$NameParent3;
                   	    	$alexaConfig[$struct->$id]["Type"]=$typ;
	            		    $alexaConfig[$struct->$id]["Name"]=$struct->Name;
    		            	if ($id=="SceneControllerDeactivatableActivateID") $alexaConfig[$struct->$id]["Script"]=$struct->SceneControllerDeactivatableDeactivateID;						
                            }
		        		else echo "Fehler, ".$struct->$id." nicht vorhanden. aus Alexa Config loeschen.\n";							
				        }   // ende else
                    }       // ende foreach 
                }   // ende foreach
            }
    	return ($alexaConfig);
        }

    public function writeAlexaConfig($alexaConfig, $filter="", $writeHtml=false,$debug=false)
        {
		/* html Formatierung für Tabelle vorbereiten, Style customers schreiben */
		$html="";
		$html.="<style>";
		$html.='#customers { font-family: "Trebuchet MS", Arial, Helvetica, sans-serif; font-size: 12px; color:black; border-collapse: collapse; width: 100%; }';
		$html.='#customers td, #customers th { border: 1px solid #ddd; padding: 8px; }';
		$html.='#customers tr:nth-child(even){background-color: #f2f2f2;}';
		$html.='#customers tr:nth-child(odd){background-color: #e2e2e2;}';
		$html.='#customers tr:hover {background-color: #ddd;}';
		$html.='#customers th { padding-top: 10px; padding-bottom: 10px; text-align: left; background-color: #4CAF50; color: white; }';
		$html.="</style>";
		
		$index=0;					
        if ($filter == "")
            {   // standardausgabe, technisch orientiert
			$html.='<table id="customers" >';
			$html.="<tr><th>ID#</th><th>Name</th><th>Typ</th><th>Pfad</th></tr>";	
	        foreach ($alexaConfig as $entry)
		        {
		        if ($debug) echo "     ".str_pad('"'.$entry["Name"].'"',40)."   ".str_pad($entry["Type"],20)."    ".$entry["Pfad"]."\n";
				$html.="<tr><td>".$index."</td><td>".$entry["Name"]."</td><td>".$entry["Type"]."</td><td>".$entry["Pfad"]."</td></tr>";
				$index++;
		        }
            }
        else
            {	// nur Ausgabe eines Sprachbefehls
			$html.='<table id="customers" >';
			$html.="<tr><th>ID#</th><th>Name</th></tr>";	
			if ($debug)
				{                 
                switch ($filter)
                    {
					case "DeviceSpeaker":
                        break;						
      	            case "DeviceGenericSwitch":
                        break;
	                case "DeviceLightSwitch":
                        break;
            		case "DeviceDeactivatableScene":
	    			    break;
                	case "DeviceSimpleScene":
		        	    break;
            	    case "DeviceGenericSlider":
		    	        break;					
                	case "DeviceLightColor":
	    	    	    break;						
                	case "DeviceLightDimmer":
		        	    break;	
                    case "DeviceLock":
		    	        break;
                	case "DeviceTemperatureSensor":

                        echo "Typische Fragen um einen Temperaturwert in einem Raum anzufragen:\n";
                        echo "   Alexa, wie ist die Temperatur im Badezimmer ?\n";
                        echo "   Alexa, wie ist der Status von Aussen ?\n";
        		        break;											
                	case "DeviceThermostat":
                        echo "Befehl: Alexa, Setze die Temperatur im Badezimmer auf 22 Grad.\n";
    	        	    break;											
            	    default:
                        echo "Fehler writeAlexaConfig: kenne den Identifier $filter in der Alexa Config noch nicht.\n";
                        break;
                    }
				}
	        foreach ($alexaConfig as $entry)
		        {
                if ($entry["Type"]==$filter) 
					{
					if ($debug) print_r($entry);
					if ($debug) echo "     ".str_pad($index,3)." ".str_pad('"'.$entry["Name"].'"',40)."\n";
					$html.="<tr><td>".$index."</td><td>".$entry["Name"]."</td></tr>";	
					$index++;
					}                                    
                }	// ende foreach  
            }	// else filter

		$html.="</table>";
		if ($writeHtml==true) return($html);
        }
		
		
	}

/*********************************************************************************************/




?>
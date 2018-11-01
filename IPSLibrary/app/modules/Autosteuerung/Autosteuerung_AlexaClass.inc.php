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
        else $this->configAlexa=IPS_GetConfiguration($instances[0]);    
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
	        //print_r($configStruct);
            foreach ($configStruct as $typ=>$conf)
                {
        	    $confStruct=json_decode($conf);
                //echo "    ".$typ."    ".$conf."\n";
		        //foreach ($confStruct as $struct) print_r($struct);            
                switch ($typ)
    	        	{
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

    public function writeAlexaConfig($alexaConfig,$filter="")
        {
        if ($filter == "")
            {   // standardausgabe, technisch orientiert
	        foreach ($alexaConfig as $entry)
		        {
		        echo "     ".str_pad('"'.$entry["Name"].'"',40)."   ".str_pad($entry["Type"],20)."    ".$entry["Pfad"]."\n";
		        }
            }
        else
            {
                
                switch ($filter)
                    {
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
                        echo "Fehler writeAlexaConfig: kenne den Identifier $typ in der Alexa Config noch nicht.\n";
                        break;
                    }
	        foreach ($alexaConfig as $entry)
		        {
                if ($entry["Type"]==$filter) 
					{
					print_r($entry);
					echo "     ".str_pad('"'.$entry["Name"].'"',40)."\n";
					}                                    
                }
            }
        }

	}

/*********************************************************************************************/




?>
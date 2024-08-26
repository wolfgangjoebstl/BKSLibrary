<?php

/*****************************************************************************************************
 *
 * Hier besondere Alexa Mangement Funktionen zusammenfassen. Hier wird die Alexa Konfiguration ausgegeben und für Debugzwecke analysiert.
 * Die Config AUswertung greift wenn keine lokalen Instanzen vorhanden sind auch auf Remote Server zu.
 * wenn mehrere Instanzen verfügbar sind wird die rste instanmz genommen
 * Temperaturwerte werden nur abgefragt. Kommen aus den Spiegelregistern oder aus den RemoteAccess Registern.
 * Stellwerte veraendern eine lokale Variable die auch gleichzeitig ein Script aufruft. Der Entry in das Script Austosteuerung_AlexaControl ist VoiceControl.
 * Bei der Config überprüfen ob das Autorun Script auch gesetzt ist.
 *
 * Mit dem construct bereits ermittelt:
 *  countAlexa      sind die Anzahl der vorhandenen Alexas. Wenn der WEert negativ ist, handelt es sich um remote Alexas
 *  instances       die Alexa Instanzen, üblicherweise nur eine
 *  configAlexa     die Konfiguration der ersten Alexa Instanz
 *
 * Funktionen
 *  __construct 
 *  getInstances
 *  getCountInstances
 *  getConfigAlexa          
 *  getAlexaConfig      Alexa Konfiguration aus der Instanz laden und analysieren
 *
 *
 **************************************************************************************************************/ 

class AutosteuerungAlexaHandler 
	{

    private $instances;
    private $rpc;               // remoteAccess for Server
    private $countAlexa;        // 0 means there are no Alexas, positive number is lokal number Alexas, negative number is number of remote Alexas
    private $configAlexa;

    /* Initialisiserung mit den wichtigesten Parametern
     */
	public function __construct() 
        {
		$this->instances=IPS_GetInstanceListByModuleID("{CC759EB6-7821-4AA5-9267-EF08C6A6A5B3}");
        $this->countAlexa = sizeof($this->instances);
        //echo "Anzahl Aleaxa Instanzen: ".$this->countAlexa."\n"; print_R($this->instances);
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

    /* Ausgabe der Alexa Instanzen, üblicherweise nur eine 
     */
    public function getInstances()
        {
        return($this->instances);
        }
    
    /* Ausgabe der Anzahl der Alexa Instanzen, üblicherweise nur eine 
     */
    public function getCountInstances()
        {
        return($this->countAlexa);
        }
    
    /* Ausgabe der Konfiguration der ersten Alexa Instanz
     */
    public function getConfigAlexa()
        {
        return($this->configAlexa);
        }        

    /* Alexa Konfiguration aus der Instanz laden und analysieren
     * Konfiguration besteht aus einem Typ und einer Unterkonfiguration
     * Alle Typen durchgehen
     *   
     *      countAllexa und configAlexa von der Instanz bekannt
     *      
     */

    public function getAlexaConfig($debug=false)
        {
        $alexaConfig=array(); 
        //$configAlexa=array();                         // configAlexa 
	    if ($this->countAlexa != 0) 
            {
            if ($debug) 
                {
                if ($this->countAlexa > 0) echo "Konfiguration von Alexa Kerninstanz ".$this->instances[0]." (".IPS_getName($this->instances[0])."::".IPS_getName(IPS_GetParent($this->instances[0])).") auslesen. Es gibt ".$this->countAlexa." Instanzen.\n";
                else echo "Konfiguration von Alexa Kerninstanz ".$this->instances[0]." (auf remote Server) auslesen. Es gibt ".$this->countAlexa." Instanzen.\n";
                echo "   ".$this->configAlexa."\n";
                echo "Es geht weiter mit der Analyse der Alexa Konfiguration:\n";
                }
        	$configStruct=json_decode($this->configAlexa);          // liest die Konfiguration der Alexa Instanz aus
	        //print_r($configStruct);
            foreach ($configStruct as $typ=>$conf)
                {
                if ($conf=="") 
                    {
                    $conf="[]";
                    }
        	    $confStruct=json_decode($conf);
                if ($debug) echo "Bearbeite    ".$typ." :\n";
                //echo "Bearbeite    ".$typ."    ".$conf."\n";
		        //foreach ($confStruct as $struct) print_r($struct);            
                switch ($typ)
    	        	{
					case "DeviceSpeaker":
                        /* Setze die Laustärke von Denon auf 10 */
				        $id="SpeakerID";
                        break;	
                    case "DeviceSpeakerMuteable":
                        $id="unknown";
                        break;
                    case "DeviceMediaPlayer":
                        $id="unknown";
                        break;                        
                    case "DeviceTelevision":				
				        //$id="PowerControllerID";
                        $id="ChannelControllerID";              // für Program Schaltung genutzt
                        break;
	    	        case "DeviceGenericSwitch":
		            case "DeviceLightSwitch":
				        $id="PowerControllerID";
                        break;
        			case "DeviceDeactivatableScene":
		        		$id="SceneControllerDeactivatableActivateID";
				        break;
            		case "DeviceSimpleScene":
	            		//$id="SceneControllerSimpleID";                      // war früher der Parameter
                        $id="SceneControllerSimpleAction";                            // keine ID nur eine SceneControllerSimpleAction
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
                         * Beispiel: {"ID":"18","Name":"Wohnzimmer Deckenlampe Zwei","PowerControllerID":xxxx,"BrightnessOnlyControllerID":xxxx,"ColorOnlyControllerID":xxx}}   
                         */
        	            $id="PowerControllerID";                         
                    	break;				
            		case "DeviceLightDimmer":
	            		$id="BrightnessControllerID";
		    	        break;	
            		case "DeviceShutter":
                        $id="unknown";
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
            		case "EmulateStatus":
                    case "ShowExpertDevices":
                        /* neue Statusvariablen ohne ID Parameter */
                        if ($debug) echo "Status $typ ist $conf. Will not be used in script.\n";
                        $conf="[]";
                	    $confStruct=json_decode($conf);
                        break;										
            		default:
                        if ($debug) 
                            {
                            echo "Fehler: kenne den Identifier $typ in der Alexa Config noch nicht.\n";
                            echo "    ".$typ."    ".$conf."\n";
                            if ($conf=="") echo "Fehler, kein Parameter für $typ.\n";
                            }
		    	        break;
                    } 
                // für jeden Typ die Unterkonfiguration analysieren
            	foreach ($confStruct as $struct) 
	            	{
                    if ($debug) 
                        {
                        echo "   Analysiere $typ für Key \"$id\" :".json_encode($struct)."\n";
                        //print_r($struct);                       
                        }
                    if ($this->countAlexa > 0)
                        {   // lokal Alexa
                        if ( (isset($struct->$id)) === false) 
                            {
                            print_r($struct);
                            echo "Kenne die $id nicht.\n";
                            }
        				else
                            {
                            if ($id=="SceneControllerSimpleAction")
                                {
                                if ($debug) echo "Andere Struktur, IPS_Object Exists kann nicht auf eine strukturierte Variable angewandt werden.\n";
                                // {"Status":"OK","ID":"6","Name":"Wohnzimmer Fernsehen","SceneControllerSimpleAction":"{\"actionID\"
                                $alexaConfig[$struct->$id]["Type"]=$typ;                // DeviceSimpleScene
                                $alexaConfig[$struct->$id]["Name"]=$struct->Name;                                
                                }
                            elseif( IPS_ObjectExists($struct->$id)==true )
                                {
                                $Name=IPS_GetName($struct->$id);        // same structure as for remote, idea ist to reduce the number of accesses to remote server
                                $parent=IPS_GetParent($struct->$id);
                                $parent2=IPS_GetParent($parent);
                                $parent3=IPS_GetParent($parent2);
                                $NameParent=IPS_GetName($parent);
                                $NameParent2=IPS_GetName($parent2);
                                $NameParent3=IPS_GetName($parent3);
                                //print_r($struct);
                                $alexaConfig[$struct->$id]["OID"]=$struct->$id;
                                $alexaConfig[$struct->$id]["OID_Name"]=$Name;
                                $alexaConfig[$struct->$id]["Pfad"]=$Name."/".$NameParent."/".$NameParent2."/".$NameParent3;
                                $alexaConfig[$struct->$id]["Type"]=$typ;
                                $alexaConfig[$struct->$id]["Name"]=$struct->Name;
                                if ($id=="SceneControllerDeactivatableActivateID") $alexaConfig[$struct->$id]["Script"]=$struct->SceneControllerDeactivatableDeactivateID;						
                                }
    				        elseif ($debug) echo "Fehler, ".$struct->$id." nicht vorhanden. aus Alexa Config loeschen.\n";
                            }							
                        }
                    else
                        {  // remote Alexa
                        if ($id=="SceneControllerSimpleAction")
                            {
                            if ($debug) echo "Andere Struktur, IPS_Object Exists kann nicht auf eine strukturierte Variable angewandt werden.\n";
                            // {"Status":"OK","ID":"6","Name":"Wohnzimmer Fernsehen","SceneControllerSimpleAction":"{\"actionID\"
                            $alexaConfig[$struct->$id]["Type"]=$typ;                // DeviceSimpleScene
                            $alexaConfig[$struct->$id]["Name"]=$struct->Name;                                
                            }                        
                        else
                            {
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
                            elseif ($debug) echo "Fehler, ".$struct->$id." nicht vorhanden. aus Alexa Configuration der Kern Instanz loeschen.\n";	
                            }						
				        }   // ende else
                    }       // ende foreach 
                }   // ende foreach
            }       // endeif
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
		        if ($debug) 
                    {
                    echo "     ".str_pad('"'.$entry["Name"].'"',40)."   ".str_pad($entry["Type"],20);
                    if (isset($entry["Pfad"])) echo "    ".$entry["Pfad"];
                    echo "\n";
                    }
				$html.="<tr><td>".$index."</td><td>".$entry["Name"]."</td><td>".$entry["Type"]."</td>";
                if (isset($entry["Pfad"])) $html.="<td>".$entry["Pfad"]."</td>";
                $html.="</tr>";
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
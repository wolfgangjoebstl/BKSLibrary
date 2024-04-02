<?php
/* HMXML.inc.php v 02-2014

 * angepasst am 2013-11-19 wegen HM-CC-RT-DN by SWifty

 * angepasst am 2014-02-19:
	- in der funktion HMXML_getType() werden die einmal gefundenen HMDevices
	 jetzt in einem Cache gespeichert
   - HMXML_getTempProfile() erweitert um HM-TC-IT-WM-W-EU
   - HMXML_setTempProfile() erweitert um HM-TC-IT-WM-W-EU
   - HMXML_setParamInt() ... neu
   
 * Basierend auf Zapp (2011) for the IPS Community
 * This  library  is  free  software;  you can redistribute it and/or modify it
 * under  the  terms  of the GNU Library General Public License as published by
 * the  Free  Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * This  library is distributed in the hope that it will be useful, but WITHOUT
 * ANY  WARRANTY;  without  even  the  implied  warranty  of MERCHANTABILITY or
 * FITNESS  FOR  A  PARTICULAR  PURPOSE.  See  the  GNU  Library General Public
 * License for more details.
 *
 * You  should  have  received a copy of the GNU Library General Public License
 * along  with  this  library;  if  not, write to the Free Software Foundation,
 */
//----------------------------------------------------------------------------
// USAGE:
// The HMXML library requires the xmlrpc library. Copy the library and
// xmlrpc.inc.php in the IPS script directory
// ---------------------------------------------------------------------------
//
// It should automatically detect the BidCos Service and create a XMLRPC client
// for every request.
// If you get an error message from HMXML_init or if you have performance
// problems, please add the following line at the start of your script:
//
// HMXML_init(YOUR_BIDCOS_SERVER_IP);
//
// All functions accept either the IPS Instance ID or the HM Address as parameter
//
// Examples:
//
// -- Get a full list of HM Devices
// $devices = HMXML_DevicesList();
// print_r($devices);
//
// -- Get a full list of HM Interfaces
// $interfaces = HMXML_InterfacesList();
// print_r($interfaces);
//
// -- Get all Parameters for a given HM device using the IPS instance ID
// $HM_Device_Parameters = HMXML_getParamSet($IPS_Instance_ID, 2);
// -- Get the Parameter Description
// $HM_Device_Parameters_Desc = HMXML_getParamSetDesc($IPS_Instance_ID, 2);
// -- Get one specific Parameter
// $TC_Mode = HMXML_getParamSet($IPS_Instance_ID, 2, 'MODE_TEMPERATUR_REGULATOR');
// -- The Mode of a HM Thermostat can also be retrived with
// HMXML_getTCMode($IPS_DeviceID);
// -- To set the Mode
// HMXML_setTCMode($IPS_DeviceID, $Mode);
// where 0 = MANUAL; 1 = AUTO; 2=CENTRAL; 3 = PARTY
//
// -- Get the Tempareature Profile of a Thermostat in a better Human-readable array
// $tempProfile = HMXML_getTempProfile($IPS_Instance_ID);
// print_r($tempProfile);
// -- Setting a temperature for a given Day / Profile Index
// Note: The transfer of data to the TC might take some time (few minutes).
// $tempProfileNew = array();
// $tempProfileNew['MONDAY']['EndTimes'] = array("06:30","08:30","16:30","22:00","24:00");
// $tempProfileNew['MONDAY']['Values'] = array(17.0,20.0,17.0,19.5,17.0);
// HMXML_setTempProfile(29146, $tempProfileNew);
//
// Remarks:

// Version string.
//define("HMXML_VERSION", "0.2");

    require IPS_GetKernelDir()."modules\\HMInventory\\libs\\phpxmlrpc-4.3.0\\lib\\xmlrpc.inc" ;

	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

    class CommunicationService
        {
        var $configCCU          =array();
        var $BidCosServiceIP;           // IP Adresse CCU
        var $xml_client;

        var $HM_Cache_ID;

        public function __construct()
            {
            date_default_timezone_set('UTC'); //* wichtig, da sonst am Tag der Zeitumstellung WZ/SZ Konflikte auftreten.  Ergänt von Swifty am 09.04.2013
        	$HMXML_DataPath='Program.IPSLibrary.data.hardware.IPSHomematic.ThermostatConfig';
        	$categoryId_hmxml = CreateCategoryPath($HMXML_DataPath);

            $HM_Cache_ID = @IPS_GetVariableIDByName("HM_Device_Cache", $categoryId_hmxml);
            if($HM_Cache_ID === false)
                {
                $HM_Cache_ID = IPS_CreateVariable(3);
                IPS_SetParent($HM_Cache_ID, $categoryId_hmxml);
                IPS_SetName($HM_Cache_ID, "HM_Device_Cache");
	            IPS_SetPosition ($HM_Cache_ID, 2);
		        }
            $this->HM_Cache_ID=$HM_Cache_ID;    
            
            }

        public function getCCUcount()
            {
            $ids = IPS_GetInstanceListByModuleID("{A151ECE9-D733-4FB9-AA15-7F7DD10C58AF}");
	        return(sizeof($ids));
            }

        public function getCCUconfig()
            {
            $config=array();
            $ids = IPS_GetInstanceListByModuleID("{A151ECE9-D733-4FB9-AA15-7F7DD10C58AF}");
	        $HomInstanz=sizeof($ids);
        	if($HomInstanz == 0)
		        {
		        echo "ERROR: Keine HomeMatic Socket Instanz gefunden!\n";
        		}
        	else
		        {	
        		for ($i=0;$i < $HomInstanz; $i++)
		        	{
        			$ccu_name=IPS_GetName($ids[$i]);
		        	//echo "\nHomatic Socket ID ".$ids[$i]." / ".$ccu_name."   \n";
        			$config[$i]=json_decode(IPS_GetConfiguration($ids[$i]),true);
                    }
                }
            return ($config);
            }

        public function useCCU($num)
            {
            $configAll=$this->getCCUconfig();
            $countCCU=sizeof($configAll);
            if ( ($num >= 0) && ($countCCU != 0) && ($countCCU>=($num+1) ) ) 
                {
                $this->configCCU=$configAll[$num];
                $this->BidCosServiceIP= $this->configCCU["Host"];
                return($configAll[$num]);
                }
            else return false;
            }

        
        /**************************************************************************
         *   HMXML functions
	     * $BidCosServiceIP: The IP Address of the BidCos Server. If not provided or false, will be detected via IPS
         *
         *************************************************************************/

        function HMXML_init($BidCosServiceIP=false, $debug = false, $port = "2001") 
            {
            echo "Running HMXML_init.";
	        if ($this->xml_client !== false) 
                {
                echo "   Xml_Client undefined, find a solution ...";
		        if ($BidCosServiceIP === false) 
                    { // We did not provide an IP for the BidCos Service
                    $BidCosServiceIP=$this->BidCosServiceIP;
                    }
        	    $this->xml_client = new xmlrpc_client("http://".$BidCosServiceIP.":".$port);
                //echo "XML Client:\n"; print_r($xml_client);    
   	            // XMLRPC Debug values: 0, 1 and 2 are supported (2 = echo sent msg too, before received response)
           	    if ($debug !== false) $this->xml_client->setDebug(2);
   	            else $this->xml_client->setDebug(0);
                echo "\n";
   	            if ($this->xml_client !== false) return true;
   	            else return false;
	            }
        	// Instance of XML_Client already exists
	        return false;
            }

        function HMXML_DevicesList() 
            {
        	$request = new xmlrpcmsg('listDevices');
        	$devices = $this->HMXML_send($request);
        	return $devices;
            }

        function printDevices($filter="")
            {
            $result=array();    
            echo "\n\nDevicelist : \n";
            $devicelist=$this->HMXML_DevicesList();
            $i=0;
            foreach ($devicelist as $key => $entry) 
                {
                if ( ($entry["TYPE"] != "KEY") && ($entry["TYPE"] != "VIRTUAL_KEY") && ($entry["TYPE"] != "MAINTENANCE") )
                    {
                    $found=false;
                    $adress=explode(":",$entry["ADDRESS"]);
                    if (isset($adress[1]))
                        {
                        }
                    else  
                        {
                        if ($filter != "")
                            {
                            if ($entry["TYPE"] == $filter) $found=true;
                            }
                        else
                            {
                            $found=true;
                            }
                        if ($found)
                            {
                            $i++;                
                            //echo $i." :  ".$key."    ".$entry["TYPE"]."    ".$entry["INTERFACE"]."    ".$entry["ADDRESS"]."\n";
                            echo $i." :  ".$key."    ".$entry["TYPE"]."    ".$entry["ADDRESS"]."\n";
                            $result[$i]=$entry;
                            }                                                           
                        }
                    }
                }
            return ($result);            
            }

        function HMXML_InterfacesList() 
            {
        	$request = new xmlrpcmsg('listBidcosInterfaces');
          	$devices = $this->HMXML_send($request);
        	return $devices;
            }

        // Gets the Type of HM device (HM terminology)
        function HMXML_getType($IPS_DeviceID) 
            {
            echo "HMXML_getType mit Parameter $IPS_DeviceID aufgerufen.\n";
        	//HM_Devices cachen
            $HM_Cache=unserialize(GetValue($this->HM_Cache_ID));
            // $IPS_DeviceID: IPS Instance ID
        	$HMAddressFull = explode(":", HM_GetAddress($IPS_DeviceID));
	        $HMAddress = $HMAddressFull[0];
            $HMAddressChannel = $HMAddressFull[1];

            If ( (is_array($HM_Cache)==false) || (array_key_exists($HMAddress, $HM_Cache) == false) )
		        {
        	    $devices = $this->HMXML_DevicesList();
        		//print_r ($devices);
   	            $type = false;
		        foreach($devices as $device)
			        {
		            if (strpos($device['ADDRESS'], $HMAddress) !== false)
				        {
				        $type = $device['TYPE'];
				        $HM_Cache[$HMAddress]=$type;
				        SetValue($this->HM_Cache_ID,serialize($HM_Cache));
				        break;  // We stop at the first one we find
                        }
                    }
                }
            else
                {
	            $type=$HM_Cache[$HMAddress];
		        }
	        return $type;
            }

        // Gets the Temperature Profile of a Thermostat for a given day or all days
        // Returns the result in an array

        function HMXML_getTempProfile($IPS_DeviceID, $day = false, $echo = false, $WT_Profil=-1)
            {
            // $IPS_DeviceID: IPS Instance ID
	        // $day: STRING - Name of day in english (not case-sensitive) or false for all days
	        // $echo: BOOL - if true, the Temperature profiles is output in readable format with time values
	        // $WT_Profil nur für HM-TC-IT-WM-W-EU; Gültige Werte 1,2,3; bei -1 werden alle Profile (1-3) zurückgegeben

            $HMAddressFull 	= explode(":", HM_GetAddress($IPS_DeviceID));
            $HMAddress 			= $HMAddressFull[0];
            $HMAddressChannel = $HMAddressFull[1];

	        $dayArray = array("MONDAY","TUESDAY","WEDNESDAY","THURSDAY","FRIDAY","SATURDAY","SUNDAY");
	        $tempArray = array();

        	if ($day != false)
		        {
        	    if (!in_array($day, $dayArray)) die("Error: Unknown Day parameter in function HMXML_getTempProfile<br>\n"); // HMXML_SetTempProfile
	            $dayArray = array($day);
		        }
	        $type = $this->HMXML_getType($IPS_DeviceID);

            switch ($type)
                {
                case  "HM-CC-TC":
                    $params = $this->HMXML_getParamSet($IPS_DeviceID, $HMAddressChannel);
		            foreach($dayArray as $day)
			            {
                      	//if ($echo) echo "$day\r\n";
                      	$thisEndTimesArray 	= array();
                      	$thisTempValuesArray	= array();

			            $timePrevious = "00:00";
                       	for ($index = 1; $index <= 24; $index++)
			 	            {
                         	$keyTemp = "TEMPERATUR_".strtoupper($day)."_".$index;
                         	$keyTO 	= "TIMEOUT_".strtoupper($day)."_".$index;
                         	$Temp 	= $params[$keyTemp];
                         	$TO 		= $params[$keyTO];
                       		$Time = date('H:i', mktime(0, $TO)); // $timePassed + TO

                         	if ($TO >= 1440) $Time = "24:00";
                         	//if ($echo) echo "$index: $timePrevious -> $Time = $Temp °C\r\n"; //if ($echo) echo "$index: $timePrevious -> $Time = $Temp °C\r\n";
			            	$timePrevious = $Time;
                         	array_push($thisEndTimesArray,	$Time);
                            array_push($thisTempValuesArray,	$Temp);
                         	if ($TO >= 1440) break;
	   		                }
              		    $tempArray[$day]['EndTimes'] 	= $thisEndTimesArray;
	   	                $tempArray[$day]['Values'] 	= $thisTempValuesArray;
			            }
			        //return $tempArray;
                    break;
                case "HM-CC-RT-DN":
                    $params = $this->HMXML_getParamSet($IPS_DeviceID,"");
                    foreach($dayArray as $day)
		                {
      	                if ($echo) echo "$day\r\n";
      	                $thisEndTimesArray 	= array();
      	                $thisTempValuesArray	= array();
            			$timePrevious = "00:00";
                       	for ($index = 1; $index <= 13; $index++)
			                {   
         	                $keyTemp = "TEMPERATURE_".strtoupper($day)."_".$index;
         	                $keyTO 	= "ENDTIME_".strtoupper($day)."_".$index;
         	                $Temp 	= $params[$keyTemp];
         	                $TO 		= $params[$keyTO];
                       		$Time = date('H:i', mktime(0, $TO)); // $timePassed + TO

                         	if ($TO >= 1440) $Time = "24:00";
                         	if ($echo) echo "$index: $timePrevious -> $Time = $Temp °C\r\n"; //if ($echo) echo "$index: $timePrevious -> $Time = $Temp °C\r\n";
            				$timePrevious = $Time;
                         	array_push($thisEndTimesArray,	$Time);
                            array_push($thisTempValuesArray,	$Temp);
                         	if ($TO >= 1440) break;
   		                    }
   		                $tempArray[$day]['EndTimes'] 	= $thisEndTimesArray;
   		                $tempArray[$day]['Values'] 	= $thisTempValuesArray;
		                }
		            //return $tempArray;
                    break;
                case "HM-TC-IT-WM-W-EU":
                    $params = $this->HMXML_getParamSet($IPS_DeviceID,"");
              		For ($P=1; $P<=3; $P++)
		                {
                        if ($echo) echo "WochenProgramm - P$P\r\n";
            			foreach($dayArray as $day)
			                {
                       	   	if ($echo) echo "$day\r\n";
                      		$thisEndTimesArray 	= array();
                      		$thisTempValuesArray	= array();
            				$timePrevious = "00:00";
                       		for ($index = 1; $index <= 13; $index++)
				                {
                   	          	$keyTemp = "P". $P ."_TEMPERATURE_".strtoupper($day)."_".$index;
                          	   	$keyTO 	= "P". $P ."_ENDTIME_".strtoupper($day)."_".$index;
            					if (array_key_exists($keyTemp, $params))
			            		 	{
                	         		$Temp 	= $params[$keyTemp];
   	      		                    $TO 		= $params[$keyTO];
   	      		                    }
                                //* notwendig da Firmeware beim LAN-Adapter abweicht und beim Profil 1 dort das Präfix "P1_" fehlt.
			        			else
						            {
                       	      		$keyTemp = "TEMPERATURE_".strtoupper($day)."_".$index;
                          	   		$keyTO 	= "ENDTIME_".strtoupper($day)."_".$index;
                	         		$Temp 	= $params[$keyTemp];
   	      		                    $TO 		= $params[$keyTO];
   	      		                    }
						        //*******************************************************************
                	       		$Time = date('H:i', mktime(0, $TO)); // $timePassed + TO

	         	                if ($TO >= 1440) $Time = "24:00";
   	      	                    if ($echo) echo "$index: $timePrevious -> $Time = $Temp °C\r\n"; //if ($echo) echo "$index: $timePrevious -> $Time = $Temp °C\r\n";
            					$timePrevious = $Time;
                	         	array_push($thisEndTimesArray,	$Time);
                       	        array_push($thisTempValuesArray,	$Temp);
      	   	                    if ($TO >= 1440) break;
   			                    }
                	   		$tempArray["P".$P][$day]['EndTimes'] 	= $thisEndTimesArray;
                   			$tempArray["P".$P][$day]['Values'] 	= $thisTempValuesArray;
			                }
	                    }
                  	If ($WT_Profil>0 and $WT_Profil<=3)
		   	            {
				        $tempArray=$tempArray["P".$WT_Profil];
			            }
                     break;
                default:
                    echo "********* unknown type !!!\n";
                    die("Error: HMXML_getTempProfile() Device $HMAddress ($IPS_DeviceID) is not of Type HM-CC-TC OR HM-CC-RT-DN OR HM-TC-IT-WM-W-EU<br>\n");
                    break;
                }         
			return ($tempArray);
			}

        function HMXML_setTempProfile($IPS_DeviceID, $tempProfileArray, $WT_Profil=0)
            {
	        // $IPS_DeviceID: IPS Instance ID
	        // $tempProfileArray: ARRAY of type returned by HMXML_getTempProfile()
	        // $WT_Profil nur für HM-TC-IT-WM-W-EU; gültige werte 1,2,3 --< das zu speichernde Profil
        	$HMAddress = IPSid_2_HMAddress($IPS_DeviceID);
        	$type = $this->HMXML_getType($IPS_DeviceID);
        	if ($type == "HM-CC-TC")
	            {
                $values = new xmlrpcval();
                foreach ($tempProfileArray as $day => $valuesArray)
		            {
                    $previousTimeEnd = "00:00";
     	            for ($index=1; $index <= count($valuesArray['EndTimes']); $index++)
			            {
                        $key = "TEMPERATUR_".strtoupper($day)."_".$index;
   			            $paramTemp = array($key => new xmlrpcval($valuesArray['Values'][$index-1], "double"));
   			            $values->addStruct($paramTemp);
  				        $key = "TIMEOUT_".strtoupper($day)."_".$index;
				        if ($valuesArray['EndTimes'][$index-1] > $previousTimeEnd)
				            {
				            // Convert end time to Timeout
				            $thisDayStart = mktime(0, 0);
                            $timeEndArray = explode(":", $valuesArray['EndTimes'][$index-1]);
				            if ($timeEndArray[1] % 10) die("Error: Invalid End Time (must be 10mn increments) for $day at index $index in HMXML_setTempProfile()<br>\n");
				            $timeEndts = mktime($timeEndArray[0], $timeEndArray[1]);
                            // $timeout = (($timeEndts - $thisDayStart)/60)+60; // TODO, works  ?
				            $timeout = (($timeEndts - $thisDayStart)/60); // TODO, works  ?
                            $paramTime = array($key => new xmlrpcval("$timeout", "int")); // i4
                            $values->addStruct($paramTime);
					        }
				        else die("Error: Invalid End Time for $day at index $index in HMXML_setTempProfile()<br>\n");
                        $previousTimeEnd = $valuesArray['EndTimes'][$index-1];
				        }
                    }
                $content = new xmlrpcmsg("putParamset",
                    array(  new xmlrpcval("$HMAddress:2", "string"),
                           new xmlrpcval("MASTER", "string"),
                           $values ) );
   	            $result = $this->HMXML_send($content);
		        return true;
                }

            //* eigefügt von Swifty ********************
	        if ($type == "HM-CC-RT-DN")
	            {
                $values = new xmlrpcval();
                foreach ($tempProfileArray as $day => $valuesArray)
		            {
                    $previousTimeEnd = "00:00";
     	            for ($index=1; $index <= count($valuesArray['EndTimes']); $index++)
			            {
           	            $key = "TEMPERATURE_".strtoupper($day)."_".$index;
   			            $paramTemp = array($key => new xmlrpcval($valuesArray['Values'][$index-1], "double"));
   			            $values->addStruct($paramTemp);
  				        $key = "ENDTIME_".strtoupper($day)."_".$index;
  				        if ($index>13) break; // HM-CC-RT-DN hat nur 13 Tages - Timeslots
				        if ($valuesArray['EndTimes'][$index-1] > $previousTimeEnd)
				            {
				            // Convert end time to Timeout
				            $thisDayStart = mktime(0, 0);
                            $timeEndArray = explode(":", $valuesArray['EndTimes'][$index-1]);
				            if ($timeEndArray[1] % 10) die("Error: Invalid End Time (must be 10mn increments) for $day at index $index in HMXML_setTempProfile()<br>\n");
				            $timeEndts = mktime($timeEndArray[0], $timeEndArray[1]);
                            $timeout = (($timeEndts - $thisDayStart)/60); // TODO, works  ?
                            $paramTime = array($key => new xmlrpcval("$timeout", "int")); // i4
                            $values->addStruct($paramTime);
					        }
				        else die("Error: Invalid End Time for $day at index $index in HMXML_setTempProfile()<br>\n");
                        $previousTimeEnd = $valuesArray['EndTimes'][$index-1];
				        }
                    }
                $content = new xmlrpcmsg("putParamset",
                     array(  new xmlrpcval("$HMAddress", "string"),
                           new xmlrpcval("MASTER", "string"),
                           $values ) );
   	            $result = $this->HMXML_send($content);
    		    return true;
                }
            
            if ($type == "HM-TC-IT-WM-W-EU")
	            {
		        $values = new xmlrpcval();
        		//* notwendig da Firmeware beim LAN-Adapter abweicht und beim Profil 1 dort das Präfix "P1_" fehlt.
                $params = HMXML_getParamSet($IPS_DeviceID,"");
		        if (array_key_exists("P1_ENDTIME_FRIDAY_1", $params)) // nur ein Beispiel des "P1_"-Profils erforderlich
		 	        {
	     	        $Praefix="P" .$WT_Profil ."_";
   	 	            }
   	 	        else
   	 	            {
	    	        $Praefix="";
   	 	            }
		        //****************************************************************
        		foreach ($tempProfileArray as $day => $valuesArray)
		            {
		            $previousTimeEnd = "00:00";
     	            for ($index=1; $index <= count($valuesArray['EndTimes']); $index++)
			            {
                       	$key = $Praefix ."TEMPERATURE_".strtoupper($day)."_".$index;
   			            $paramTemp = array($key => new xmlrpcval($valuesArray['Values'][$index-1], "double"));
				        $values->addStruct($paramTemp);
  				        $key = $Praefix ."ENDTIME_".strtoupper($day)."_".$index;

          				if ($index>13) break; // HM-TC-IT-WM-W-EU hat nur 13 Tages - Timeslots
		        		if ($valuesArray['EndTimes'][$index-1] > $previousTimeEnd)
				            {
				            // Convert end time to Timeout
				            $thisDayStart = mktime(0, 0);
                            $timeEndArray = explode(":", $valuesArray['EndTimes'][$index-1]);
				            if ($timeEndArray[1] % 5) die("Error: Invalid End Time (must be 10mn increments) for $day at index $index in HMXML_setTempProfile()<br>\n");
				            $timeEndts = mktime($timeEndArray[0], $timeEndArray[1]);
                            $timeout = (($timeEndts - $thisDayStart)/60); // TODO, works  ?
        		            $paramTime = array($key => new xmlrpcval("$timeout", "int")); // i4
		                    $values->addStruct($paramTime);
					        }
				        else die("Error: Invalid End Time for $day at index $index in HMXML_setTempProfile()<br>\n");
                        $previousTimeEnd = $valuesArray['EndTimes'][$index-1];
				        }
                    }
                $content = new xmlrpcmsg("putParamset",
                    array(  new xmlrpcval("$HMAddress", "string"),
                           new xmlrpcval("MASTER", "string"),
                           $values ) );
   	            $result = HMXML_send($content);
        		return true;
                }

            //*********************************************************

            If ($type != "HM-CC-RT-DN" and $type != "HM-CC-TC" and $type != "HM-TC-IT-WM-W-EU")
                {
                die("Error: HMXML_getTempProfile() Device $HMAddress ($IPS_DeviceID) is not of Type HM-CC-TC, HM-TC-IT-WM-W-EU OR HM-CC-RT-DN<br>\n");
                }
            }

        function HMXML_setTemp($IPS_DeviceID, $day, $index, $temp, $timeEnd = false) 
            {
	        // $IPS_DeviceID: IPS Instance ID
	        // $day: STRING - Full day name (english), not case-sensitive
	        // $index: INTEGER - The Temperature list index, between 1 and 24 (max 24 temperatire values/slots)
	        // $timeEnd: STRING - Value of End Time in the Format HH:mm
	        // Note: The transfer of data to the TC might take some time (few minutes).
            die ("Error: HMXML_setTemp() removed in v0.2<br>\n");
            return false;
            }

        function HMXML_getParamSet($IPS_DeviceID, $channel = null, $param = false) 
            {
        	// $IPS_DeviceID: IPS Instance ID
        	// $channel: INTEGER - if null the channel is taken from IPS
        	// $param: STRING - A specific parameter to return (see HomeMatic Specficiation), returns all if false
        	// Output: An array of Parameters for the Device

            $HMAddressFull = explode(":", HM_GetAddress($IPS_DeviceID));
            $HMAddress = $HMAddressFull[0];
            $HMAddressChannel = $HMAddressFull[1];

            $thisChannel = isset($channel) ? $channel : $HMAddressChannel;
	        $request = new xmlrpcmsg("getParamset",
  			    array(new xmlrpcval("$HMAddress:$thisChannel", "string"),
           	      new xmlrpcval("MASTER", "string")) );

	        $messages = $this->HMXML_send($request);

        	if ($param !== false) return $messages[$param];
	        return $messages;
            }

        function HMXML_getParamSetDesc($IPS_DeviceID, $channel = null, $param = false) 
            {
	        // $IPS_DeviceID: IPS Instance ID
	        // $channel: INTEGER - default is null. Should already be included in HM Address
	        // $param: STRING - A specific parameter to return (see HomeMatic Specficiation), returns all if false
	        // Output: Array of Parameter Descriptions for the Device
            $HMAddressFull = explode(":", HM_GetAddress($IPS_DeviceID));
            $HMAddress = $HMAddressFull[0];
            $HMAddressChannel = $HMAddressFull[1];
	        $thisChannel = isset($channel) ? $channel : $HMAddressChannel;
	        $request = new xmlrpcmsg("getParamsetDescription",
  					array(new xmlrpcval("$HMAddress:$thisChannel", "string"),
           		new xmlrpcval("MASTER", "string")) );
            //echo "$HMAddress:$thisChannel";
        	$messages = $this->HMXML_send($request);
            if ($param !== false) return $messages[$param];
	        return $messages;
            }

//* eigefügt von Swifty am 11.2.2014 ********************
function HMXML_setParamInt($IPS_DeviceID, $param, $value) {
	// $IPS_DeviceID: IPS Instance ID or HM Address
	// $param: STRING - The parameter to set
	// $value: DOUBLE - The value to set. 
   $HMAddress = IPSid_2_HMAddress($IPS_DeviceID);

	$params = array($param => new xmlrpcval("$value", "i4"));

	$values = new xmlrpcval();
	$values->addStruct($params);

	$content = new xmlrpcmsg("putParamset",
                    array(  new xmlrpcval("$HMAddress", "string"),
                            new xmlrpcval("MASTER", "string"),
                            $values ) );
  	$result = HMXML_send($content);
//****************************************************
}



function HMXML_setParamFloat($IPS_DeviceID, $param, $value) {
	// $IPS_DeviceID: IPS Instance ID or HM Address
	// $param: STRING - The parameter to set
	// $value: DOUBLE - The value to set. If not double, will not be set on device
	$HMAddress = IPSid_2_HMAddress($IPS_DeviceID);

	$params = array($param => new xmlrpcval("$value", "double"));

	$values = new xmlrpcval();
	$values->addStruct($params);

	$content = new xmlrpcmsg("putParamset",
                    array(  new xmlrpcval("$HMAddress:2", "string"),
                            new xmlrpcval("MASTER", "string"),
                            $values ) );

	$result = HMXML_send($content);
}

// Gets the Mode of a VD (Valve) Device
function HMXML_getTCValveMode($IPS_DeviceID) {
	// $IPS_DeviceID: IPS Instance ID
	// Output: INTEGER - Mode 0 = AUTO, 1 = CLOSE VALVE, 2 = OPEN VALVE
   $HMAddress = IPSid_2_HMAddress($IPS_DeviceID);

   $type = HMXML_getType($IPS_DeviceID);
   if ($type == "HM-CC-TC") {
		$value = HMXML_getParamSet($IPS_DeviceID, 2, 'MODE_TEMPERATUR_VALVE');
		return $value;
	} else {
      die("Error: HMXML_getTCValveMode() Device $IPS_DeviceID is not of Type HM-CC-TC<br>\n");
   }
}

// Sets the Mode on a VD (Valve) Device
function HMXML_setTCValveMode($IPS_DeviceID, $nMode) {
	// $IPS_DeviceID: IPS Instance ID
	// $nMode: INTEGER - 0 = AUTO, 1 = CLOSE VALVE, 2 = OPEN VALVE

   $HMAddress = IPSid_2_HMAddress($IPS_DeviceID);

   $type = HMXML_getType($IPS_DeviceID);
   if ($type == "HM-CC-TC") {
		$params = array("MODE_TEMPERATUR_VALVE" => new xmlrpcval("$nMode", "i4"));

		$values = new xmlrpcval();
		$values->addStruct($params);

		$content = new xmlrpcmsg("putParamset",
                    array(  new xmlrpcval("$HMAddress:2", "string"),
                            new xmlrpcval("MASTER", "string"),
                            $values ) );

   	$result = HMXML_send($content);

		return $result;
 	} else {
      die("Error: HMXML_setTCValveMode() Device $HMAddress ($IPS_DeviceID) is not of Type HM-CC-TC<br>\n");
   }
}


        // Sets the Error Position on a VD (Valve) Device
        function HMXML_setVDErrorPos($IPS_DeviceID, $nErrorPosition) 
            {
	        // $IPS_DeviceID: IPS Instance ID
	        // $nErrorPosition: INTEGER - Position in %, between 0 and 99 included
            $HMAddress = IPSid_2_HMAddress($IPS_DeviceID);
            $type = HMXML_getType($IPS_DeviceID);
            if ($type == "HM-CC-VD") 
                {
		        $params = array("VALVE_ERROR_POSITION" => new xmlrpcval("$nErrorPosition", "i4"));
        		$values = new xmlrpcval();
		        $values->addStruct($params);
        		$content = new xmlrpcmsg("putParamset",
                    array(  new xmlrpcval("$HMAddress:2", "string"),
                            new xmlrpcval("MASTER", "string"),
                            $values ) );
           	    $result = $this->HMXML_send($content);
    		    return $result;
 	            } 
            else { die("Error: HMXML_setVDErrorPos() Device $HMAddress ($IPS_DeviceID) is not of Type HM-CC-VD<br>\n"); }
            }

        // Sets the Error Position on a VD (Valve) Device
        function HMXML_setVDOffset($IPS_DeviceID, $nOffset) 
            {
	        // $IPS_DeviceID: IPS Instance ID
	        // $nOffset: INTEGER - Offset Position in %, between 0 and 25 included
            $HMAddress = IPSid_2_HMAddress($IPS_DeviceID);
            $type = $this->HMXML_getType($IPS_DeviceID);
            if ($type == "HM-CC-VD") 
                {
		        $params = array("VALVE_OFFSET_VALUE" => new xmlrpcval("$nOffset", "i4"));
        		$values = new xmlrpcval();
		        $values->addStruct($params);
		        $content = new xmlrpcmsg("putParamset",
                       array(  new xmlrpcval("$HMAddress:2", "string"),
                            new xmlrpcval("MASTER", "string"),
                            $values ) );
               	$result = HMXML_send($content);
            	return $result;
 	            } 
            else { die("Error: HMXML_setVDOffset() Device $HMAddress ($IPS_DeviceID) is not of Type HM-CC-VD<br>\n"); }
            }

        // Gets the Mode on a TC (Thermostat) Device
        function HMXML_getTCMode($IPS_DeviceID) 
            {
	        // $IPS_DeviceID: IPS Instance ID
	        // Output: INTEGER - Mode: 0 = MANUAL, 1 = AUTO, 2=CENTRAL, 3 = PARTY
            $type = $this->HMXML_getType($IPS_DeviceID);
            if ($type == "HM-CC-TC") 
                {
		        $value = $this->HMXML_getParamSet($IPS_DeviceID, 2, 'MODE_TEMPERATUR_REGULATOR');
		        return $value;
  	            } 
            else { die("Error: HMXML_getTCMode() Device $IPS_DeviceID is not of Type HM-CC-TC<br>\n");  }
            }

        // Sets the Mode on a TC (Thermostat) Device
        function HMXML_setTCMode($IPS_DeviceID, $nMode) 
            {
	        // $IPS_DeviceID: IPS Instance ID
	        // $nMode: INTEGER - Mode 0 = MANUAL, 1 = AUTO, 2=CENTRAL, 3 = PARTY

            $HMAddress = $this->IPSid_2_HMAddress($IPS_DeviceID);

            $type = $this->HMXML_getType($IPS_DeviceID);
            if ($type == "HM-CC-TC") 
                {
		        $params = array("MODE_TEMPERATUR_REGULATOR" => new xmlrpcval("$nMode", "i4"));
        		$values = new xmlrpcval();
		        $values->addStruct($params);
        		$content = new xmlrpcmsg("putParamset",
                    array(  new xmlrpcval("$HMAddress:2", "string"),
                            new xmlrpcval("MASTER", "string"),
                            $values ) );
               	$result = HMXML_send($content);
        		return $result;
 	            } 
            else { die("Error: HMXML_setTCMode() Device $HMAddress ($IPS_DeviceID) is not of Type HM-CC-TC<br>\n");  }
            }

        // Sets Temperature Comfort Value
        function HMXML_setTempComfortValue($IPS_DeviceID, $temp) 
            {
	        // $IPS_DeviceID: IPS Instance ID
	        // $temp: FLOAT - Temperature Value
        	$type = $this->HMXML_getType($IPS_DeviceID);
            if ($type == "HM-CC-TC") 
                {
                $result = $this->HMXML_setParamFloat($IPS_DeviceID, "TEMPERATUR_COMFORT_VALUE", $temp);
        		return $result;
 	            }
            else { die("Error: HMXML_SetTempComfortValue() Device $IPS_DeviceID is not of Type HM-CC-TC<br>\n");  }
        }

        // Sets Temperature Lowering (Absenk) Value
        function HMXML_setTempLoweringValue($IPS_DeviceID, $temp) 
            {
	        // $IPS_DeviceID: IPS Instance ID
	        // $temp: FLOAT - Temperature Value
        	$type = $this->HMXML_getType($IPS_DeviceID);
            if ($type == "HM-CC-TC") 
                {
                $result = $this->HMXML_setParamFloat($IPS_DeviceID, "TEMPERATUR_LOWERING_VALUE", $temp);
            	return $result;
 	            } 
            else { die("Error: HMXML_SetTempLoweringValue() Device $IPS_DeviceID is not of Type HM-CC-TC<br>\n");  }
            }

        // Sets Temperature Lowering (Absenk) Value
        function HMXML_setTempPartyValue($IPS_DeviceID, $temp) 
            {
        	// $IPS_DeviceID: IPS Instance ID
	        // $temp: FLOAT - Temperature Value
        	$type = $this->HMXML_getType($IPS_DeviceID);
            if ($type == "HM-CC-TC") 
                {
                $result = $this->HMXML_setParamFloat($IPS_DeviceID, "TEMPERATUR_PARTY_VALUE", $temp);
        		return $result;
 	            }
            else { die("Error: HMXML_SetTempPartyValue() Device $IPS_DeviceID is not of Type HM-CC-TC<br>\n");  }
            }

        // Sets Party Time
        function HMXML_setPartyEnd($IPS_DeviceID, $day, $hour, $minute) 
            {
	        // $IPS_DeviceID: IPS Instance ID
	        // $day: number of days (0 to 200 max)
	        // $hour: the hour (0 to 23 included)
	        // $minute: 0 = 00, 1 = 30
        	if ($day < 0 or $day > 200) 		die("Error: HMXML_SetPartyEnd() Number of Days must be between 0 and 200<br>\n");
        	if ($hour < 0 or $day > 23) 		die("Error: HMXML_SetPartyEnd() Hour must be between 0 and 23<br>\n");
	        if ($minute < 0 or $minute > 1) 	die("Error: HMXML_SetPartyEnd() Minute must be 0 (00) or 1 (30)<br>\n");

            $HMAddress = $this->IPSid_2_HMAddress($IPS_DeviceID);

            $type = $this->HMXML_getType($IPS_DeviceID);
            if ($type == "HM-CC-TC") 
                {
                $values = new xmlrpcval();
               	$paramDay = array("PARTY_END_DAY" => new xmlrpcval("$day", "double"));
   	            $values->addStruct($paramDay);
   	            $paramHour = array("PARTY_END_HOUR" => new xmlrpcval("$hour", "i4"));
   	            $values->addStruct($paramHour);
   	            $paramMinute = array("PARTY_END_MINUTE" => new xmlrpcval("$minute", "i4"));
   	            $values->addStruct($paramMinute);

                $content = new xmlrpcmsg("putParamset",
                    array(  new xmlrpcval("$HMAddress:2", "string"),
                            new xmlrpcval("MASTER", "string"),
                            $values ) );

   	            $result = $this->HMXML_send($content);
        		return $result;
 	            } 
            else { die("Error: HMXML_SetPartyEnd() Device $IPS_DeviceID is not of Type HM-CC-TC<br>\n");  }
            }  


        // Gets Reception Levels between 2 HM Devices in dbm
        function HMXML_getRFLevelsAB($IPS_DeviceID_A, $IPS_DeviceID_B) 
            {
	        // $IPS_DeviceID_A: IPS Instance ID of first device
    	    // $IPS_DeviceID_B: IPS Instance ID of second device
	        // Output: Array with Reception Levels in dbm
	        // Note: 65536 means unknown level
           $HMAddressA = $this->IPSid_2_HMAddress($IPS_DeviceID_A);
           $HMAddressB = $this->IPSid_2_HMAddress($IPS_DeviceID_B);

	        $request = new xmlrpcmsg('rssiInfo');
        	$devices = $this->HMXML_send($request);

	        //$result = array();
        	//array_push($result, $devices[$HMAddressA][$HMAddressB]);
	        //array_push($result, $devices[$HMAddressB][$HMAddressA]);
        	//return $result;
        	return $devices[$HMAddressA][$HMAddressB];
            }

        // Converts IPS Instance ID to HM Address or leaves Address if provided as such
        function IPSid_2_HMAddress($IPS_DeviceID) 
            {
            // $IPS_DeviceID: IPS Instance ID or HM Address
            // output: HM Address
        	if (preg_match ("/^[A-Z]{3}[0-9]*/", $IPS_DeviceID)) {
		    // The provided ID is a HM Address. Leave as it is.
	 	    return $IPS_DeviceID;
	        } 
        else 
            {
    	    // The provided ID is a IPS Instance ID. Convert and extract HM Address.
	 	    $HMAddressFull = HM_GetAddress($IPS_DeviceID);
	 	    if ($HMAddressFull === false) echo "Eror: Invalid IPS Instance ID in IPSid_2_HMAddress()";
	 	    $HMAddressArray = explode(":", $HMAddressFull);
   	        $HMAddress = $HMAddressArray[0];
   	        return $HMAddress;
	        }
        }

        // Creates XMLRPC Client Instance and sends request
        function HMXML_send($request) 
            {
        	// If the client does not exist, initialise it here
	        if ($this->xml_client == false) 
                {
                $init_result = $this->HMXML_init();
		        if ($init_result !== false) echo "XMLRPC INIT SUCCESS!\r\n"; else "XMLRPC INIT FAILED!\r\n";
		        echo "\r\n";
                }
        	$response = $this->xml_client->send($request);
	        if ( $response->errno == 0 )
           	$messages = php_xmlrpc_decode($response->value());
        	else die("Error: HMXML_send() Request to BidCos-Service failed ($this->BidCosServiceIP) -> $response->errstr<br>\n");
        	return $messages;
            }




        }       // ende class



?>
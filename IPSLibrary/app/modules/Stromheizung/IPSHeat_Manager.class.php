<?php
	/*
	 * This file is part of the IPSLibrary.
	 *
	 * The IPSLibrary is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published
	 * by the Free Software Foundation, either version 3 of the License, or
	 * (at your option) any later version.
	 *
	 * The IPSLibrary is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with the IPSLibrary. If not, see http://www.gnu.org/licenses/gpl.txt.
	 */    

	/**@addtogroup IPSHeat
	 * @{
	 *
	 * @file          IPSHeat_Manager.class.php
	 * @author        Andreas Brauneis, abgewandelt und erweitert Wolfgang Jöbstl
	 * @version
	 *   Version 2.50.1, 26.07.2012<br/>
	 *
	 * IPSHeat Licht Management
	 */

	/**
	 * @class IPSHeat_Manager
	 *
	 * Definiert ein IPSHeat_Manager Objekt
	 *
	 * @author Andreas Brauneis, abgewandelt und erweitert Wolfgang Jöbstl
     *
     * class IPSHeat_Manager
     * private vars $switchCategoryId, $groupCategoryId, $programCategoryId
     *  __construct
     *  setConfiguration
     *  getConfigGroups
     *  convertConfigToArray
     *
     *  getGroupIDs
     *
     *  GetSwitchIdByName, GetLevelIdByName, GetColorIdByName, GetAmbienceIdByName
     *  GetGroupIdByName
     *  GetProgramIdByName
     *  GetValue
	 *  SetValue					wird bei einer Variablenänderung aus dem Webfront aufgerufen
     *  SetSwitch, SetDimmer, SetRGB,SetAmbient, SetHeat
     *  SetGroup
     *  SetProgram
     *  GetConfigById, 
	 *  GetConfigNameById		eliminiert bekannte Erweiterungen wie #Level und gibt den Hauptnamen zurück
     *  SynchronizeGroupsBySwitch
	 *  SynchronizeGroupsByGroup
     *
     *
	 * Webfront Änderung ruft SetValue auf. Abhänig vom Typ wird das spezielle SetXXX aufgerufen.
	 *  zum Beispiel SetHeat
	 *
	 *
	 *
	 * @version
	 *   Version 2.50.1, 26.07.2012<br/>
	 */
	class IPSHeat_Manager {

        private $ipsOps;           // class ipsOps
		/**
		 * @private
		 * ID Kategorie mit Schalter und Dimmern
		 */
		private $lightId; 
		private $switchCategoryId,$lightSwitchCategoryId;

		/**
		 * @private
		 * ID Kategorie mit Schalter
		 */
		private $groupCategoryId,$lightGroupCategoryId;

		/**
		 * @private
		 * ID Kategorie mit Programmen
		 */
		private $programCategoryId,$lightProgramCategoryId;
        private $configuration;                                         // configuration

		/**
		 * @public
		 *
		 * Initialisierung des IPSHeat_Manager Objektes
		 *
		 */
		public function __construct($debug=false) 
			{
			//echo "Construct IPS_HeatManager.\n";
			$baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Stromheizung');
			$this->switchCategoryId  = IPS_GetObjectIDByIdent('Switches', $baseId);
			$this->groupCategoryId   = IPS_GetObjectIDByIdent('Groups', $baseId);
			$this->programCategoryId = IPS_GetObjectIDByIdent('Programs', $baseId);
			$this->lightId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.IPSLight',true);
			if ($this->lightId)
				{
				$this->lightSwitchCategoryId  = IPS_GetObjectIDByIdent('Switches', $baseId);
				$this->lightGroupCategoryId   = IPS_GetObjectIDByIdent('Groups', $baseId);
				$this->lightProgramCategoryId = IPS_GetObjectIDByIdent('Programs', $baseId);
				}
			$this->ipsOps = new ipsOps();
            $this->configuration["donotupdateSwitch"]=false;

			/* Vorbereitung für ein Sync zwischen IPSHeat und IPSLight, aber nicht zurück */
		}

        public function getSwitchCategoryId()
            {
            return ($this->switchCategoryId);
            }
        /* Configuration Handling einführen
         *
         */
        public function setConfiguration($state)
            {
            $this->configuration["donotupdateSwitch"]=$state;
            }


        /* List Switch Config and store as array with readable identifiers
         *
         */
        public function getConfigSwitches($configSwitches,$debug=false)
            { 
            // Struktur festlegen
            $structureGroup = array(
                "Index"                => array( "Name" => "Index",        "Size" => 40, ),
                IPSHEAT_NAME           => array( "Name" => "Name",         "Size" => 35, ),
                IPSHEAT_GROUPS         => array( "Name" => "Groups",       "Size" => 50, ),
                IPSHEAT_TYPE           => array( "Name" => "Type",         "Size" => 20, ),
                );

            return($this->convertConfigToArray($structureGroup,$configSwitches,$debug));
            }


        /* List Group Config and store as array with readable identifiers
         *
         */
        public function getConfigGroups($configGroups,$debug=false)
            { 
            // Struktur festlegen
            $structureGroup = array(
                "Index"                => array( "Name" => "Index",        "Size" => 20, ),
                IPSHEAT_NAME           => array( "Name" => "Name",         "Size" => 20, ),
                IPSHEAT_GROUPS         => array( "Name" => "Groups",       "Size" => 20, ),
                IPSHEAT_TYPE           => array( "Name" => "Type",         "Size" => 20, ),
                IPSHEAT_ACTIVATABLE    => array( "Name" => "Activateable", "Size" => 10, ),
                );

            return($this->convertConfigToArray($structureGroup,$configGroups,$debug));
            }

        /* List Program Config and store as array with readable identifiers
         *
         */
        public function getConfigPrograms($configGroups,$debug=false)
            { 
            // Struktur festlegen
            $listEntries=array(
                    7  =>   'IPSHEAT_PROGRAMON',
                    8  =>   'IPSHEAT_PROGRAMOFF',
                    9  =>   'IPSHEAT_PROGRAMLEVEL',
                    10 =>   'IPSHEAT_PROGRAMRGB',
            );
            
            $result=array();
            if ($debug) echo str_pad("Index",20).str_pad("List",20).str_pad("Command",30).str_pad("Script",80)."\n";
            foreach ($configGroups as $index => $entry)
                {
                if ($debug) echo str_pad($index,20)."\n";
                foreach ($entry as $subindex => $list)
                    {
                    if ($debug) echo str_pad("",20).str_pad($subindex,20)."\n";
                    foreach ($list as $listindex => $items)
                        {
                        if ($debug) echo str_pad("",20).str_pad("",20).str_pad($listEntries[$listindex],30).str_pad($items,80)."\n";
                        $result[$index][$subindex][$listEntries[$listindex]]=$items;
                        }
                    }
                }
            return($result);
            }

        /* Convert Constant Formatted Config and store as array with readable identifiers
         * according to a $structureGroup, output as array and echo if debug
         */
        private function convertConfigToArray($structureGroup,$configGroups,$debug=false)
            { 
            // Überschrift für echo Ausgabe 
            foreach ($structureGroup as $index => $entry) { if ($debug) echo str_pad($entry["Name"],$entry["Size"]); }
            if ($debug) echo "\n";
            $result=array();
            // einzelne Zeilen ausgeben und in Array schreiben
            foreach ($configGroups as $index => $entry)
                {
                foreach ($structureGroup as $styleID => $style) 
                    {
                    if ($styleID=="Index") 
                        {
                        if ($debug) echo str_pad($index,$style["Size"]); 
                        $indexID=$index;
                        }
                    else 
                        {
                        if (isset($entry[$styleID])) 
                            {
                            if ($debug) echo str_pad($entry[$styleID],$style["Size"]); 
                            $result[$indexID][$structureGroup[$styleID]["Name"]]=$entry[$styleID];
                            }
                        elseif ($debug)  echo str_pad("",$style["Size"]);
                        }
                    }
                if ($debug) echo "\n";
                }
            return($result);
            }
        
        /* generates a summary with link according to the type
         */
        public function checkActuators($name, $identifier,$debug=false)
            {
            $plusLink=array();
            if ($debug) echo "checkActuators, Identifier for Actuator $name : $identifier";
            switch ($identifier)
                {
                case "GROUPSSWITCH":
                    $soid = $this->GetGroupIdByName($name);
                    $plusLink[$soid]=$name;
                    break;
                case "GROUPSAMBIENT":
                    $soid = $this->GetGroupIdByName($name);
                    $plusLink[$soid]=$name;
                    $soid = $this->GetGroupAmbienceIdByName($name);
                    $plusLink[$soid]=$name.IPSHEAT_DEVICE_AMBIENCE;
                    $soid = $this->GetGroupLevelIdByName($name);
                    $plusLink[$soid]=$name.IPSHEAT_DEVICE_LEVEL;
                    break;
                case "PROGRAMSPROGRAM":
                    $soid = $this->GetProgramIdByName($name);
                    $plusLink[$soid]=$name;
                    break;
                case "SWITCHESAMBIENT":
                    $soid = $this->GetSwitchIdByName($name);
                    $plusLink[$soid]=$name;
                    $soid = $this->GetAmbienceIdByName($name);
                    $plusLink[$soid]=$name.IPSHEAT_DEVICE_AMBIENCE;
                    $soid = $this->GetLevelIdByName($name);
                    $plusLink[$soid]=$name.IPSHEAT_DEVICE_LEVEL;
                    break;                     
                case "SWITCHESRGB":
                    if ($debug) echo ", $name  ";
                    $soid = $this->GetSwitchIdByName($name);
                    $plusLink[$soid]=$name;
                    $soid = $this->GetLevelIdByName($name); 
                    $plusLink[$soid]=$name.IPSHEAT_DEVICE_LEVEL;
                    $soid = $this->GetColorIdByName($name); 
                    $plusLink[$soid]=$name.IPSHEAT_DEVICE_COLOR;
                    break;
                case "GROUPSTHERMOSTAT":
                    $soid = $this->GetGroupIdByName($name);
                    $plusLink[$soid]=$name;
                    break;          
                default:
                    if ($debug) echo "       ->> WARNING, Identifier \"$identifier\" unknown !!!!\n";  
                    break;                                                     
                }                       // end switch
            //if ($debug) echo "\n";
            return($plusLink);
            }

        /* Summarizes the Group Identifiers in one table
         * 
         */
        public function getGroupIDs()
            {
            $configGroups = IPSHeat_GetGroupConfiguration();
            $IDs = array();
            $config = $this->getConfigGroups($configGroups,false);                    // erst einmal die Groups, Programs folgen noch
            foreach ($config as $index => $entry)
                {
                // ID herausbekommen, wird fürs Registrieren benötigt
                if (isset($entry["Type"]))
                    {
                    $IDs[] = $this->GetGroupIdByName($entry["Name"]);
                    }
                }
            //print_R($IDs);
            return ($IDs);
            }

        /* Summarizes the Program Identifiers in one table
         * 
         */
        public function getProgramIDs()
            {
            $configPrograms = IPSHeat_GetProgramConfiguration();
            $IDs = array();
            $config = $this->getConfigPrograms($configPrograms,false);                    // erst einmal die Groups, Programs folgen noch
            foreach ($config as $index => $entry)
                {
                // ID herausbekommen, wird fürs Registrieren benötigt
                $IDs[] = $this->GetProgramIdByName($index);
                }
            //print_R($IDs);
            return ($IDs);
            }
		/**
		 * @public
		 *
		 * Liefert ID eines Schalters anhand des Namens
		 *
		 * @param string $name Name des Schalters
		 * @return int ID des Schalters 
		 */
		public function GetSwitchIdByName($name) {
			return IPS_GetVariableIDByName($name, $this->switchCategoryId);
		}

		/**
		 * @public
		 *
		 * Liefert ID einer Level Variable eines Dimmers anhand des Namens
		 *
		 * @param string $name Name des Dimmers
		 * @return int ID der Level Variable
		 */
		public function GetLevelIdByName($name) {
			return IPS_GetVariableIDByName($name.IPSHEAT_DEVICE_LEVEL, $this->switchCategoryId);
		}

		/**
		 * @public
		 *
		 * Liefert ID einer RGB Variable anhand des Namens
		 *
		 * @param string $name Name des RGB Lichtes
		 * @return int ID der RGB Variable
		 */
		public function GetColorIdByName($name) {
			return IPS_GetVariableIDByName($name.IPSHEAT_DEVICE_COLOR, $this->switchCategoryId);
		}

		/**
		 * @public
		 *
		 * Liefert ID einer RGB Variable anhand des Namens
		 *
		 * @param string $name Name des RGB Lichtes
		 * @return int ID der RGB Variable
		 */
		public function GetAmbienceIdByName($name) {
			return IPS_GetVariableIDByName($name.IPSHEAT_DEVICE_AMBIENCE, $this->switchCategoryId);
		}

		/**
		 * @public
		 *
		 * Liefert ID eines Gruppen Schalters anhand des Namens
		 *
		 * @param string $name Name der Gruppe
		 * @return int ID der Gruppe
		 */
		public function GetGroupIdByName($name) {
			return @IPS_GetVariableIDByName($name, $this->groupCategoryId);
		}

		public function GetGroupLevelIdByName($name) {
			return IPS_GetVariableIDByName($name.IPSHEAT_DEVICE_LEVEL, $this->groupCategoryId);
		}

		public function GetGroupAmbienceIdByName($name) {
			return IPS_GetVariableIDByName($name.IPSHEAT_DEVICE_AMBIENCE, $this->groupCategoryId);
		}

		public function GetGroupColorIdByName($name) {
			return IPS_GetVariableIDByName($name.IPSHEAT_DEVICE_COLOR, $this->groupCategoryId);
		}
		/**
		 * @public
		 *
		 * Liefert ID eines Programm Schalters anhand des Namens
		 *
		 * @param string $name Name des Programm Schalters
		 * @return int ID des Programm Schalters
		 */
		public function GetProgramIdByName($name) {
			return IPS_GetVariableIDByName($name, $this->programCategoryId);
		}

		/**
		 * @public
		 *
		 * Liefert Wert einer Control Variable (Schalter, Dimmer, Gruppe, ...) anhand der zugehörigen ID
		 *
		 * @param string $variableId ID der Variable
		 * @return int Wert der Variable
		 */
		public function GetValue($variableId) {
			return GetValue($variableId);
		}

		/**
		 * @public
		 *
		 * Setzt den Wert einer Control Variable (Schalter, Dimmer, Gruppe, ...) anhand der zugehörigen ID
		 *
		 * @param int $variableId ID der Variable
		 * @param int $value Neuer Wert der Variable
		 */
		public function SetValue($variableId, $value, $debug=false, $exectime=false) 
			{
			$parentId = IPS_GetParent($variableId);
			if ($debug) echo "IPS class HeatManager SetValue von ".$parentId."/".$variableId." (".IPS_GetName($parentId)."/".IPS_GetName($variableId).") mit Wert ".$value."\n";
			//IPSLogger_Inf(__file__, "IPS class HeatManager SetValue von ".$parentId."/".$variableId." (".IPS_GetName($parentId)."/".IPS_GetName($variableId).") mit Wert ".$value." Alter Wert ".GetValue($variableId));
			switch($parentId) {
				case $this->switchCategoryId:
					$configName = $this->GetConfigNameById($variableId);
					$configLights = IPSHeat_GetHeatConfiguration();
					if ($debug) echo "IPS class HeatManager SetValue : ".$parentId."  (".IPS_GetName($parentId).")    $configName \n";					
					$lightType    = $configLights[$configName][IPSHEAT_TYPE];
					switch ($lightType)
						{
						case IPSHEAT_TYPE_SWITCH:
							if ($debug) echo "IPS_HeatManager SetValue Type Switch SetValue; ".$variableId."  (".IPS_GetName($variableId).") ".$value."\n";
							$this->SetSwitch($variableId, $value, true, true, $debug);
							break;
						case IPSHEAT_TYPE_DIMMER:
							if ($debug) echo "IPS_HeatManager SetValue Type Dimmer SetDimmer($variableId (".IPS_GetName($variableId)."),$value)\n";
							$this->SetDimmer($variableId, $value);
							break;
						case IPSHEAT_TYPE_RGB:
							if ($debug) echo "IPS_HeatManager SetValue Type RGB SetRGB; ".$variableId."  (".IPS_GetName($variableId).") ".$value."\n";
							$this->SetRGB($variableId, $value, true, true, $debug);
							break;
						case IPSHEAT_TYPE_AMBIENT:
							if ($debug) echo "IPS_HeatManager SetValue Type Ambient SetAmbient; ".$variableId."  (".IPS_GetName($variableId).") ".$value."\n";
							$this->SetAmbient($variableId, $value);
							break;
						case IPSHEAT_TYPE_SET:
							if ($debug) echo "IPS_HeatManager SetValue Type Heat SetHeat; ".$variableId."  (".IPS_GetName($variableId).") ".$value."\n";
							//echo "     ".$configName."\n";
							$this->SetHeat($variableId, $value,true, true, $debug,$exectime);
							break;
						default:
							trigger_error('Unknown HeatType '.$lightType.' for Heat/Light '.$configName);
						}
					break;
				case $this->groupCategoryId:
					$this->SetGroup($variableId, $value);
					break;
				case $this->programCategoryId:
					$this->SetProgram($variableId, $value);
					break;
				default:
					trigger_error('Unknown ControlId '.$variableId);
			}
		}

		/**
		 * @public
		 *
		 * Setzt den Wert einer Schalter Variable anhand der zugehörigen ID
		 *
		 * @param int $switchId ID der Variable
		 * @param bool $value Neuer Wert der Variable
		 */
		public function SetSwitch($switchId, $value, $syncGroups=true, $syncPrograms=true, $debug=false) 
			{
			//if ($debug) echo "Aufruf SetSwitch mit ".$switchId." setzen auf ".$value." : Wert vorher ist ".GetValue($switchId)."\n";
			if (GetValue($switchId)==$value) {
				return;
			}
			$configName   = $this->GetConfigNameById($switchId);
			$configLights = IPSHeat_GetHeatConfiguration();
			$componentParams = $configLights[$configName][IPSHEAT_COMPONENT];
			$component       = IPSComponent::CreateObjectByParams($componentParams);
            if ($debug) echo "Aufruf SetSwitch mit ".$switchId." ".$value." Component Params $componentParams\n";

			SetValue($switchId, $value);
			IPSLogger_Inf(__file__, 'Turn Heat/Light SetSwitch '.$configName.' '.($value?'On':'Off'));

			if (IPSHeat_BeforeSwitch($switchId, $value)) {
				$component->SetState($value);
			}
			IPSHeat_AfterSwitch($switchId, $value);

			if ($syncGroups) {
				$this->SynchronizeGroupsBySwitch($switchId);
			}
			if ($syncPrograms) {
				$this->SynchronizeProgramsBySwitch ($switchId);
			}
		}

		/**
		 * @public
		 *
		 * Setzt den Wert einer Dimmer Variable anhand der zugehörigen ID
		 *
		 * @param int $variableId ID der Variable
		 * @param bool $value Neuer Wert der Variable
		 */
		public function SetDimmer($variableId, $value, $syncGroups=true, $syncPrograms=true) {
			if (GetValue($variableId)==$value) 
                {
				return;
			    }
            // Rückrechnen switchID und LevelID mit den aktuellen Werten von Switch und Level
			$configName   = $this->GetConfigNameById($variableId);
			$configLights = IPSHeat_GetHeatConfiguration();
			$switchId     = IPS_GetVariableIDByName($configName, $this->switchCategoryId);
			$switchValue  = GetValue($switchId);
			$levelId      = IPS_GetVariableIDByName($configName.IPSHEAT_DEVICE_LEVEL, $this->switchCategoryId);
			$levelValue   = GetValue($levelId);

			$componentParams = $configLights[$configName][IPSHEAT_COMPONENT];
			$component       = IPSComponent::CreateObjectByParams($componentParams);

            // Auto On/off wenn Level gesetzt wird
			if ($variableId==$levelId)                              // es wird nur der Level gesetzt
                {
			    if (!$switchValue and $value>0) 
                    {
				    if ($this->configuration["donotupdateSwitch"]==false) SetValue($switchId, true);                      // Wenn Level größer Null auch gleichzeitig einschalten
			        } 
                elseif ($switchValue and $value==0)                 // wenn eingeschaltet und der Level ist 0 ausschalten
                    {
				    if ($this->configuration["donotupdateSwitch"]==false) SetValue($switchId, false);
			        } 
                else 
                    {
			        }
			    if (GetValue($levelId) > 100) { $value = 100; }     // level begrenzen 0 bis 100
				if (GetValue($levelId) < 0)   { $value = 0; }
			    } 
            else                                                    // es wird der Schalter gesetzt
                {
			    if ($value and $levelValue==0) 
                    {
			        SetValue($levelId, 15);         // Wenn ein und Level aktuell auf 0 auf 15% setzen
			        }
			    }
			SetValue($variableId, $value);

			$switchValue  = GetValue($switchId);
			IPSLogger_Inf(__file__, 'Turn Heat/Light SetDimmer '.$configName.' '.($switchValue?'On, Level='.GetValue($levelId):'Off'));

			if (IPSHeat_BeforeSwitch($switchId, $switchValue)) {
				$component->SetState(GetValue($switchId), GetValue($levelId));
			}
			IPSHeat_AfterSwitch($switchId, $switchValue);

			if ($syncGroups) {
				$this->SynchronizeGroupsBySwitch($switchId);
			}
			if ($syncPrograms) {
				$this->SynchronizeProgramsBySwitch ($switchId);
			}
		}

		/**
		 * @public
		 *
		 * Setzt den Wert einer RGB Farb Variable anhand der zugehörigen ID
		 *
		 * @param int $variableId ID der Variable
		 * @param bool $value Neuer Wert der Variable
		 */
		public function SetRGB($variableId, $value, $syncGroups=true, $syncPrograms=true, $debug=false) {
			if ($debug) echo "SetRGB ".$variableId." (".IPS_GetName($variableId).") ".$value." ".dechex($value)."\n";
            else
                {
                if (GetValue($variableId)==$value) { return; }
                }
			$configName   = $this->GetConfigNameById($variableId);
			$configLights = IPSHeat_GetHeatConfiguration();
			$switchId     = IPS_GetVariableIDByName($configName, $this->switchCategoryId);
			$colorId      = IPS_GetVariableIDByName($configName.IPSHEAT_DEVICE_COLOR, $this->switchCategoryId);
			$levelId      = IPS_GetVariableIDByName($configName.IPSHEAT_DEVICE_LEVEL, $this->switchCategoryId);
			$switchValue  = GetValue($switchId);
			
			/* Sync mit IPSLight */

			$componentParams = $configLights[$configName][IPSHEAT_COMPONENT];
			$component       = IPSComponent::CreateObjectByParams($componentParams);

			SetValue($variableId, $value);
			if (!$switchValue and ($variableId==$levelId or $variableId==$colorId)) {
				SetValue($switchId, true);
			}
			$switchValue  = GetValue($switchId);
			IPSLogger_Inf(__file__, 'Turn Heat/Light SetRGB '.$configName.' '.($switchValue?'On, Level='.GetValue($levelId).', Color='.GetValue($colorId):'Off')."  RGB Wert : ".dechex(GetValue($colorId))."   ".$componentParams);

			if (IPSHeat_BeforeSwitch($switchId, $switchValue)) 
                {
                if ($debug) echo "    SetState(".GetValue($switchId).",".dechex(GetValue($colorId)).",".GetValue($levelId).")\n";  
				$component->SetState(GetValue($switchId), GetValue($colorId), GetValue($levelId));
			    }
			IPSHeat_AfterSwitch($switchId, $switchValue);

			//IPSLogger_Inf(__file__, "Turn Heat/Light SetRGB Synchronize Groups for $switchId if ".($syncGroups?"Ein":"Aus"));
			if ($syncGroups) {
				$this->SynchronizeGroupsBySwitch($switchId);
			}
			//IPSLogger_Inf(__file__, "Turn Heat/Light SetRGB Synchronize Programs for $switchId if ".($syncPrograms?"Ein":"Aus"));
			if ($syncPrograms) {
				$this->SynchronizeProgramsBySwitch ($switchId);
			}
		}

		/**
		 * @public
		 *
		 * Setzt den Wert einer HUE Ambient Variable (Mired) anhand der zugehörigen ID
		 * Mired sind 10!6 durch Kelvin
		 *
		 * @param int $variableId ID der Variable
		 * @param bool $value Neuer Wert der Variable
		 */
		public function SetAmbient($variableId, $value, $syncGroups=true, $syncPrograms=true) {
			if (GetValue($variableId)==$value) {
				return;
			}
			$configName   = $this->GetConfigNameById($variableId);
			$configLights = IPSHeat_GetHeatConfiguration();
			$switchId     = IPS_GetVariableIDByName($configName, $this->switchCategoryId);
			$colorId      = IPS_GetVariableIDByName($configName.IPSHEAT_DEVICE_AMBIENCE, $this->switchCategoryId);
			$levelId      = IPS_GetVariableIDByName($configName.IPSHEAT_DEVICE_LEVEL, $this->switchCategoryId);
			$switchValue  = GetValue($switchId);

			$componentParams = $configLights[$configName][IPSHEAT_COMPONENT];
			//echo "SetAmbient: ".$componentParams."\n";
			$component       = IPSComponent::CreateObjectByParams($componentParams);

			SetValue($variableId, $value);
			if (!$switchValue and ($variableId==$levelId or $variableId==$colorId)) {
				SetValue($switchId, true);
			}
			$switchValue  = GetValue($switchId);
			IPSLogger_Inf(__file__, 'Turn Heat/Light '.$configName.' '.($switchValue?'On, Level='.GetValue($levelId).', Color='.GetValue($colorId):'Off'));

			if (IPSHeat_BeforeSwitch($switchId, $switchValue)) {
				$component->SetState(GetValue($switchId), GetValue($colorId), GetValue($levelId),true);
			}
			IPSHeat_AfterSwitch($switchId, $switchValue);

			if ($syncGroups) {
				$this->SynchronizeGroupsBySwitch($switchId);
			}
			if ($syncPrograms) {
				$this->SynchronizeProgramsBySwitch ($switchId);
			}
		}


		/**
		 * @public
		 *
		 * Setzt den Wert einer Thermostat Variable anhand der zugehörigen ID.
		 * Möglich sind aktuell Switch und die Erweiterungen #Temp und #Mode
		 * 
		 * Verwendet wurde bisher auch #Level - wurde auf #Temp geändert
         * das Automatische Schalten von State anhand von Level wurde deaktiviert. Das sind jetzt jeweils unabhängige Funktionen.
		 *
		 * @param int $variableId ID der Variable
		 * @param bool $value Neuer Wert der Variable
		 */
		public function SetHeat($variableId, $value, $syncGroups=true, $syncPrograms=true, $debug=false, $exectime=false) 
			{
			if (GetValue($variableId)==$value) 
				{
                if ($debug) echo "Wert unverändert,  $variableId (".$this->ipsOps->path($variableId).") = ".GetValue($variableId)."\n";
				//return;               // nur Temperaturwert sollte unverändert sein, nicht der Switch Status
				}
			$configName   = $this->GetConfigNameById($variableId);
			$configLights = IPSHeat_GetHeatConfiguration();
			if (isset($configLights[$configName])) 
                {
                if ($debug) echo "  $configName ".json_encode($configLights)."\n";

                //$switchId = @$lightManager->GetSwitchIdByName($lightName);
                //$groupId = @$lightManager->GetGroupIdByName($lightName);

                $switchId     = IPS_GetVariableIDByName($configName, $this->switchCategoryId);
                $switchValue  = GetValue($switchId);
                //$levelId      = IPS_GetVariableIDByName($configName.IPSHEAT_DEVICE_LEVEL, $this->switchCategoryId);		// dekativiert, wird nicht mehr verwendet
                //$levelValue   = GetValue($levelId);
                $tempId      = IPS_GetVariableIDByName($configName.IPSHEAT_DEVICE_TEMPERATURE, $this->switchCategoryId);
                $tempValue   = GetValue($tempId);
                $modeId      = IPS_GetVariableIDByName($configName.IPSHEAT_DEVICE_MODE, $this->switchCategoryId);
                $modeValue   = GetValue($modeId);
                
                
                $componentParams = $configLights[$configName][IPSHEAT_COMPONENT];
                if ($debug) 
                    {
                    echo "   IPSclass IPSHeat_Manager SetHeat : ".$variableId."  (".IPS_GetName($variableId).")  ".$componentParams." ";
                    if ($exectime) { $duration=round(hrtime(true)/1000000-$exectime,0); echo 'Ausführungszeit vor _construct'.$duration.' Millisekunden.'."\n"; }
                    else echo "\n";                
                    }
                $component       = IPSComponent::CreateObjectByParams($componentParams);

                switch ($variableId)
                    {
                    case $tempId:
                        /* Automatisches Schaltet und Update zwischen Level und State -> aktuell deaktiviert */
                        //echo "   IPSHeatManager:SetHeat für Temperaturwert aufgerufen.\n";
                        if (!$switchValue and $value>0) 
                            {
                            //SetValue($switchId, true);
                            } 
                        else if ($switchValue and $value==0) 
                            {
                            //SetValue($switchId, false);
                            } 
                        else 
                            { }
                        /* Begrenzungen des Temperaturwertes auf vernünftige Werte --> aktiv */
                        if (GetValue($tempId) > 30) { $value = 30; }
                        if (GetValue($tempId) < 6)   { $value = 6; }
                        //SetValue($tempId, $value);		// nicht den Stromheizung data Wert hier setzen, er wird erst mühsam vom wirklichen Gerät synchronisiert

                        if ($debug) 
                            {					
                            echo "   IPS class IPSHeat_Manager SetHeat $configName ".($switchValue?'On, Temp='.$tempValue:'Off').". Mode=".GetValue($modeId)." to new Temp=$value with $componentParams.";
                            //print_r($component);
                            if ($exectime) { $duration=round(hrtime(true)/1000000-$exectime,0); echo 'Ausführungszeit vor SetLevel'.$duration.' Millisekunden.'."\n"; }
                            else echo "\n";                
                            }
                        IPSLogger_Inf(__file__, 'IPS class IPSHeat_Manager SetHeat '.$configName.' '.($switchValue?'On, Temp='.$tempValue:'Off').' to new Temp='.$value.' with '.$componentParams);

                        if (IPSHeat_BeforeSwitch($switchId, $switchValue)) 			// keine andere Funktion als die Switch Hauptfunktion definiert, anders wäre keine Unterscheidung möglich
                            {
                            $component->SetLevel(GetValue($switchId), $value, $debug);
                            }
                        IPSHeat_AfterSwitch($switchId, $switchValue);
                        break;
                    case $modeId:
                        //echo '  Set Heat/Light Mode '.$configName.' '.($switchValue?'On, Level='.$tempValue:'Off').'  '.GetValue($modeId)."\n";
                        IPSLogger_Inf(__file__, 'Set Heat/Light Mode '.$configName.' '.($switchValue?'On, Level='.$tempValue:'Off').' Mode '.GetValue($modeId).' to new '.$value);

                        //SetValue($modeId, $value);					// nicht den Stromheizung data Wert hier setzen, er wird erst mühsam vom wirklichen Gerät synchronisiert
                        if (IPSHeat_BeforeSwitch($switchId, $switchValue))			// keine andere Funktion als die Switch Hauptfunktion definiert, anders wäre keine Unterscheidung möglich 
                            {
                            $component->SetMode(GetValue($switchId), $value);	// zweiten Parameter hier austauschen
                            }
                        IPSHeat_AfterSwitch($switchId, $switchValue);
                        break;
                    case $switchId:			
                        //echo "   IPSHeatManager:SetHeat für Schalter aufgerufen. Ändere von aktuell ".($switchValue?"Ein":"Aus")." auf ".($value?"Ein, Temp=".GetValue($tempId):"Aus").". Mode = ".GetValue($modeId)."\n";
                        IPSLogger_Inf(__file__, 'Turn Heat/Light '.$configName.' '.($switchValue?'On, Temp='.GetValue($tempId):'Off').' Mode='.GetValue($modeId));
                        SetValue($switchId, $value);

                        if (IPSHeat_BeforeSwitch($switchId, $switchValue)) 			// keine andere Funktion als die Switch Hauptfunktion definiert, anders wäre keine Unterscheidung möglich
                            {
                            $component->SetState(GetValue($switchId), GetValue($tempId));
                            }
                        IPSHeat_AfterSwitch($switchId, $switchValue);
                        break;
                    default:
                        break;	
                    }
                if ($syncGroups)											// Gruppen und Programme synchronisieren immer alle Werte einer switchId 
                    {
                    $this->SynchronizeGroupsBySwitch($switchId);            // eine Änderung der Schalterstellung verändert den Status aller Gruppen die den Schalter beinhalten
                    }
                if ($syncPrograms) 
                    {
                    $this->SynchronizeProgramsBySwitch ($switchId);
                    }
                }
            else
                {
                $groupId = $variableId;
                $groupConfig = IPSHeat_GetGroupConfiguration();
                $groupName   = $this->GetConfigNameById($groupId);			
                $groupNameLong   = IPS_GetName($groupId);    
                echo " $groupName ist eine Gruppe, NameLong :  $groupNameLong Config : ".json_encode($groupConfig[$groupName])."  \n";
                if ($value and !$groupConfig[$groupName][IPSHEAT_ACTIVATABLE]) 
                    {
                    if ($groupName == $groupNameLong) IPSLogger_Trc(__file__, "Ignore ".($value?'On':'Off')." forHeatGroup '$groupName' (not allowed)");
                    else IPSLogger_Trc(__file__, "Ignore ".$value." forHeatGroup '$groupNameLong' (not allowed)");
                    } 
                else 
                    {
                    $switchId     = IPS_GetVariableIDByName($groupName, $this->groupCategoryId);
                    $switchValue  = GetValue($switchId);
                    $tempId      = IPS_GetVariableIDByName($groupName.IPSHEAT_DEVICE_TEMPERATURE, $this->groupCategoryId);
                    $tempValue   = GetValue($tempId);
                    $modeId      = IPS_GetVariableIDByName($groupName.IPSHEAT_DEVICE_MODE, $this->groupCategoryId);
                    $modeValue   = GetValue($modeId);                        

                    switch ($variableId)
                        {
                        case $tempId:
                            /* Automatisches Schaltet und Update zwischen Level und State -> aktuell deaktiviert */
                            //echo "   IPSHeatManager:SetHeat für Temperaturwert aufgerufen.\n";
                            if (!$switchValue and $value>0) 
                                {
                                //SetValue($switchId, true);
                                } 
                            else if ($switchValue and $value==0) 
                                {
                                //SetValue($switchId, false);
                                } 
                            else 
                                { }
                            /* Begrenzungen des Temperaturwertes auf vernünftige Werte --> aktiv */
                            if (GetValue($tempId) > 30) { $value = 30; }
                            if (GetValue($tempId) < 6)   { $value = 6; }
                            //SetValue($tempId, $value);		// nicht den Stromheizung data Wert hier setzen, er wird erst mühsam vom wirklichen Gerät synchronisiert

                            if ($debug) 
                                {					
                                echo "   IPS class IPSHeat_Manager SetHeat $configName ".($switchValue?'On, Temp='.$tempValue:'Off').". Mode=".GetValue($modeId)." to new Temp=$value.";
                                //print_r($component);
                                if ($exectime) { $duration=round(hrtime(true)/1000000-$exectime,0); echo 'Ausführungszeit vor SetLevel'.$duration.' Millisekunden.'."\n"; }
                                else echo "\n";                
                                }
                            IPSLogger_Inf(__file__, 'IPS class IPSHeat_Manager SetHeat '.$configName.' '.($switchValue?'On, Temp='.$tempValue:'Off')." to new Temp=$value");

                            SetValue($tempId, $value);
                            break;
                        case $modeId:
                            //echo '  Set Heat/Light Mode '.$configName.' '.($switchValue?'On, Level='.$tempValue:'Off').'  '.GetValue($modeId)."\n";
                            IPSLogger_Inf(__file__, 'Set Heat/Light Mode '.$configName.' '.($switchValue?'On, Level='.$tempValue:'Off').' Mode '.GetValue($modeId).' to new '.$value);

                            //SetValue($modeId, $value);					// nicht den Stromheizung data Wert hier setzen, er wird erst mühsam vom wirklichen Gerät synchronisiert
                            if (IPSHeat_BeforeSwitch($switchId, $switchValue))			// keine andere Funktion als die Switch Hauptfunktion definiert, anders wäre keine Unterscheidung möglich 
                                {
                                $component->SetMode(GetValue($switchId), $value);	// zweiten Parameter hier austauschen
                                }
                            IPSHeat_AfterSwitch($switchId, $switchValue);
                            break;
                        case $switchId:			
                            //echo "   IPSHeatManager:SetHeat für Schalter aufgerufen. Ändere von aktuell ".($switchValue?"Ein":"Aus")." auf ".($value?"Ein, Temp=".GetValue($tempId):"Aus").". Mode = ".GetValue($modeId)."\n";
                            IPSLogger_Inf(__file__, 'Turn Heat/Light '.$configName.' '.($switchValue?'On, Temp='.GetValue($tempId):'Off').' Mode='.GetValue($modeId));
                            SetValue($switchId, $value);

                            if (IPSHeat_BeforeSwitch($switchId, $switchValue)) 			// keine andere Funktion als die Switch Hauptfunktion definiert, anders wäre keine Unterscheidung möglich
                                {
                                $component->SetState(GetValue($switchId), GetValue($tempId));
                                }
                            IPSHeat_AfterSwitch($switchId, $switchValue);
                            break;
                        default:
                            break;	
                        }


                    }                
                }
            if ($exectime) { $duration=round(hrtime(true)/1000000-$exectime,0); echo 'Ausführungszeit SetHeat '.$duration.' Millisekunden.'."\n"; }
			}		// ende function

		/**
		 * @public
		 *
		 * Setzt den Wert einer Gruppen Variable anhand der zugehörigen ID
		 *
		 * @param int $variableId ID der Gruppe
		 * @param bool $value Neuer Wert der Gruppe
		 */
		public function SetGroup($groupId, $value, $syncGroups=true, $syncPrograms=true, $debug=false, $exectime=false) 
            {
			$groupConfig = IPSHeat_GetGroupConfiguration();
			$groupName   = $this->GetConfigNameById($groupId);			
			$groupNameLong   = IPS_GetName($groupId);
            if ($debug) 
                {
                echo "   IPSHeat:SetGroup für $groupId $groupName mit Wert ".nf($value)." aufgerufen.";
                if ($exectime) { $duration=round(hrtime(true)/1000000-$exectime,0); echo 'Ausführungszeit '.$duration.' Millisekunden.'."\n"; }
                else echo "\n";                
                //echo "Check for Index ".IPSHEAT_ACTIVATABLE."\n"; print_r($groupConfig[$groupName]); 
                }
			if ($value and !$groupConfig[$groupName][IPSHEAT_ACTIVATABLE]) 
                {
				if ($groupName == $groupNameLong) IPSLogger_Trc(__file__, "Ignore ".($value?'On':'Off')." forHeatGroup '$groupName' (not allowed)");
                else IPSLogger_Trc(__file__, "Ignore ".$value." forHeatGroup '$groupNameLong' (not allowed)");
			    } 
            else 
                {
                if (GetValue($groupId) != $value)
                    {
                    /* nur aktiv den Wert setzen wenn er unterschiedlich ist, reduziert die Anzahl der LogMeldungen und es wird übersichtlicher */
    				SetValue($groupId, $value);
				    if ($groupName == $groupNameLong) 
                        {
                        if ($debug) echo "   IPSHeat:SetGroup, Aufruf mit $groupName. Gruppe aktiviert. ".IPS_GetName($groupId)." : ".(GetValue($groupId)?'On':'Off')." ist der alte Wert und Wert neu ist ".($value?'On':'Off').".\n";
                        IPSLogger_Inf(__file__, "Turn HeatGroup '$groupName' ".($value?'On':'Off'));
                        }
                    else 
                        {
                        if ($debug) echo "   IPSHeat:SetGroup, Aufruf mit $groupNameLong. Gruppe aktiviert. ".IPS_GetName($groupId)." : ".GetValue($groupId)." ist der alte Wert und Wert neu ist $value.\n";
                        IPSLogger_Inf(__file__, "Set HeatGroup '$groupNameLong' to $value");
                        }
                    }
				$this->SetAllSwitchesByGroup($groupId, $debug,$exectime);
                if ($exectime) { $duration=round(hrtime(true)/1000000-$exectime,0); echo 'Ausführungszeit aktuell: '.$duration.' Millisekunden.'."\n"; }
                
                /* check ob andere Gruppen auch dieser Gruppe angehören, wie bei SetHeat */
                if ($syncGroups) {
				    $this->SetAllGroupsByGroup($groupId, $value);
			        }

			}
		}

		/**
		 * @public
		 *
		 * Setzt den Wert einer Programm Variable anhand der zugehörigen ID
		 *
		 * @param int $variableId ID der Programm Variable
		 * @param bool $value Neuer Wert der Programm Variable
		 */
		public function SetProgram($programId, $value) {
			$programName     = IPS_GetName($programId);
			$programConfig   = IPSHeat_GetProgramConfiguration();
			$programKeys     = array_keys($programConfig[$programName]);
			if ($value>(count($programKeys)-1)) { 
				$value=0;
			}
			$programItemName = $programKeys[$value];

			IPSLogger_Inf(__file__, "Set Program $programName=$value ");

			// Light On
			if (array_key_exists(IPSHEAT_PROGRAMON,  $programConfig[$programName][$programItemName])) {
				$switches = $programConfig[$programName][$programItemName][IPSHEAT_PROGRAMON];
				$switches = explode(',',  $switches);
				//echo "Konfiguration Programm\n"; print_r($switches);
				foreach ($switches as $idx=>$switchName) {
					if ($switchName <> '') {
						$switchId = $this->GetSwitchIdByName($switchName);
						$configLights = IPSHeat_GetHeatConfiguration();
						$lightType    = $configLights[$switchName][IPSHEAT_TYPE];
						if ($lightType==IPSHEAT_TYPE_SWITCH) {
							$this->SetSwitch($switchId, true);
						} elseif ($lightType==IPSHEAT_TYPE_DIMMER) {
							$this->SetDimmer($switchId, true);
						} elseif ($lightType==IPSHEAT_TYPE_RGB) {
							$this->SetRGB($switchId, true);
						} else {
							trigger_error('Unknown LightType '.$lightType.' for Light '.$configName);
						}
					}
				}
			}
			// Light Off
			if (array_key_exists(IPSHEAT_PROGRAMOFF,  $programConfig[$programName][$programItemName])) {
				$switches = $programConfig[$programName][$programItemName][IPSHEAT_PROGRAMOFF];
				$switches = explode(',',  $switches);
				foreach ($switches as $idx=>$switchName) {
					if ($switchName <> '') {
						$switchId = $this->GetSwitchIdByName($switchName);
						$configLights = IPSHeat_GetHeatConfiguration();
						$lightType    = $configLights[$switchName][IPSHEAT_TYPE];
						if ($lightType==IPSHEAT_TYPE_SWITCH) {
							$this->SetSwitch($switchId, false);
						} elseif ($lightType==IPSHEAT_TYPE_DIMMER) {
							$this->SetDimmer($switchId, false);
						} elseif ($lightType==IPSHEAT_TYPE_RGB) {
							$this->SetRGB($switchId, false);
						} else {
							trigger_error('Unknown LightType '.$lightType.' for Light '.$configName);
						}
					}
				}
			}
			// Light Level
			if (array_key_exists(IPSHEAT_PROGRAMLEVEL,  $programConfig[$programName][$programItemName])) {
				$switches = $programConfig[$programName][$programItemName][IPSHEAT_PROGRAMLEVEL];
				$switches = explode(',',  $switches);
				for ($idx=0; $idx<Count($switches)-1; $idx=$idx+2) {
					$switchName  = $switches[$idx];
					$switchValue = (float)$switches[$idx+1];
					$switchId    = $this->GetSwitchIdByName($switchName);
					$this->SetDimmer($switchId, true, true, false);
					$switchId    = $this->GetSwitchIdByName($switchName.IPSHEAT_DEVICE_LEVEL);
					$this->SetDimmer($switchId, $switchValue, true, false);
				}
			}
			// Light RGB
			if (array_key_exists(IPSHEAT_PROGRAMRGB,  $programConfig[$programName][$programItemName])) {
				$switches = $programConfig[$programName][$programItemName][IPSHEAT_PROGRAMRGB];
				$switches = explode(',',  $switches);
				//echo "Konfiguration ProgramRgb\n"; print_r($switches);
				for ($idx=0; $idx<Count($switches)-1; $idx=$idx+5) {
					$switchName   = $switches[$idx];
					$switchLevel  = (float)$switches[$idx+1];
					$switchColorR = (float)$switches[$idx+2];
					$switchColorG = (float)$switches[$idx+3];
					$switchColorB = (float)$switches[$idx+4];
					$switchColor  = $switchColorR*256*256+$switchColorG*256+$switchColorB;

					$switchId     = $this->GetSwitchIdByName($switchName);
					$this->SetRGB($switchId, true, true, false);
					$switchId    = $this->GetSwitchIdByName($switchName.IPSHEAT_DEVICE_LEVEL);
					$this->SetRGB($switchId, $switchLevel, true, false);
					$switchId    = $this->GetSwitchIdByName($switchName.IPSHEAT_DEVICE_COLOR);
					$this->SetRGB($switchId, $switchColor, true, false);
				}
			}
			SetValue($programId, $value);
		}

		public function GetConfigById($variableId)
            {
			$configName   = $this->GetConfigNameById($variableId);
			$configLights = IPSHeat_GetHeatConfiguration();
			$componentParams = $configLights[$configName][IPSHEAT_COMPONENT];
            $config = explode(',',  $componentParams);
            echo "Params : ".$componentParams;
			if (isset($config[1])) echo "   \"".IPS_GetName($config[1])."\"";
            if (isset($config[2])) echo "   \"".IPS_GetName($config[2])."\"\n";
            return ($componentParams);
            }

		// ----------------------------------------------------------------------------------------------------------------------------
		public function GetConfigNameById($switchId) {
			$switchName = IPS_GetName($switchId);
			$switchName = str_replace(IPSHEAT_DEVICE_COLOR,       '', $switchName);
			$switchName = str_replace(IPSHEAT_DEVICE_LEVEL,       '', $switchName);
			$switchName = str_replace(IPSHEAT_DEVICE_AMBIENCE,    '', $switchName);
			$switchName = str_replace(IPSHEAT_DEVICE_TEMPERATURE, '', $switchName);
			$switchName = str_replace(IPSHEAT_DEVICE_MODE,        '', $switchName);

			return $switchName;
		}

		// ----------------------------------------------------------------------------------------------------------------------------
		private function SynchronizeGroupsBySwitch ($switchId, $debug=false, $exectime=false) 
			{
            if ($debug) echo "            SynchronizeGroupsBySwitch $switchId (".IPS_GetName($switchId).")\n";
			$switchName    	= $this->GetConfigNameById($switchId);
			$switchNameLong	= IPS_GetName($switchId);
			$lightConfig  = IPSHeat_GetHeatConfiguration();		
			if ($switchName <> $switchNameLong)
				{                       // direkte Synchronisation eines Unterwertes wie Temp, Mode, Level etc.
				$pos=strpos($switchNameLong,$switchName);
				$pos1=strpos($switchNameLong,"#");
				if ( ($pos==0) and !($pos1===false) )
					{
					if ($debug) echo "SynchronizeGroupsBySwitch für #Level da $switchName ungleich $switchNameLong \n";
					$NameExt=substr($switchNameLong,$pos1);
					$groups      = explode(',', $lightConfig[$switchName][IPSHEAT_GROUPS]);
					//echo "         SynchronizeGroupsBySwitch \n";					
					//print_r($groups);
					foreach ($groups as $groupName) 
						{
						if ($debug) echo "         SynchronizeGroupsBySwitch ".$switchId."   ".$groupName."\n";
						$groupId  = IPS_GetVariableIDByName($groupName, $this->groupCategoryId);
						$this->SynchronizeGroup($groupId);
						}				
					}
				}
			else
				{	
				if ($debug) echo "         SynchronizeGroupsBySwitch ".$switchId."   \"".$lightConfig[$switchName][IPSHEAT_GROUPS]."\"\n";
				$groups      = explode(',', $lightConfig[$switchName][IPSHEAT_GROUPS]);
				foreach ($groups as $groupName) 
					{
					if ($groupName != "")
						{
						if ($debug) echo "         SynchronizeGroupsBySwitch ".$switchId."   ".$groupName."\n";
						$groupId  = IPS_GetVariableIDByName($groupName, $this->groupCategoryId);
						$this->SynchronizeGroup($groupId);
						}
					}
				}	
			}

		// ----------------------------------------------------------------------------------------------------------------------------
		private function SynchronizeGroupsByGroup ($groupId) 
			{
			$groupConfig = IPSHeat_GetGroupConfiguration();
			$groupName   = $this->GetConfigNameById($groupId);			
			$groupNameLong   = IPS_GetName($groupId);
            /*
			foreach ($groupConfig as $subGroupName=>$deviceData) {
				$subgroupId      = IPS_GetVariableIDByName($subGroup, $this->switchCategoryId);
				$switchState   = GetValue($switchId);
				$switchInGroup = array_key_exists($groupName, array_flip(explode(',', $deviceData[IPSHEAT_GROUPS])));
				if ($switchInGroup and GetValue($switchId)) {
					$groupState = true;
					break;
				}

			
				{	
				$groups      = explode(',', $lightConfig[$switchName][IPSHEAT_GROUPS]);
				foreach ($groups as $groupName) 
					{
					//echo "         SynchronizeGroupsBySwitch ".$switchId."   ".$groupName."\n";
					$groupId  = IPS_GetVariableIDByName($groupName, $this->groupCategoryId);
					$this->SynchronizeGroup($groupId);
					}
				}	*/
			}

        /* SubGruppen erlauben.
         *
         *
         *
         */
        function SetAllGroupsByGroup($groupId, $value) 
			{
			$groupConfig = IPSHeat_GetGroupConfiguration();
			$groupName   = $this->GetConfigNameById($groupId);			
			$groupNameLong   = IPS_GetName($groupId);
            $groupType		= $groupConfig[$groupName][IPSHEAT_TYPE];
			if ($groupName <> $groupNameLong)			/* Wenn Zusatzparameter behandelt wird wie zB #LEVEL */
				{
				$pos=strpos($groupNameLong,$groupName);
				$pos1=strpos($groupNameLong,"#");
				if ( ($pos==0) and !($pos1===false) )
					{
					$NameExt=substr($groupNameLong,$pos1);
					//echo "SetAllSwitchesByGroup : ".$groupId."   ".$groupName."   ".$groupNameLong."   ".$NameExt."\n";
					/* GroupState ist ein Wert zB Temperatur oder Farbe */
					$groupState   = GetValue($groupId);
					foreach ($groupConfig as $subGroupName=>$deviceData) 
						{
						if ( (isset($deviceData[IPSHEAT_GROUPS])) && ($deviceData[IPSHEAT_TYPE] == $groupType) )
							{
							//print_r($deviceData);
							/* die ganze Konfiguration durchgehen. Aber nicht alle haben zB #Level */
							$switchInGroup = array_key_exists($groupName, array_flip(explode(',', $deviceData[IPSHEAT_GROUPS])));
                            $subGroupId = @$this->GetGroupIdByName($subGroupName.$NameExt);
							if ($switchInGroup && ($subGroupId!==false) )
                                {
                                //echo "SetAllGroups, check : ".$subGroupName."\n";
                                $this->SetGroup($subGroupId, $value);
                                }
							}
						}					
					}
				}
			else
				{
    			foreach ($groupConfig as $subGroupName=>$deviceData) 
                    {
                    if (isset($deviceData[IPSHEAT_GROUPS]))
                        {
                        $switchInGroup = array_key_exists($groupName, array_flip(explode(',', $deviceData[IPSHEAT_GROUPS])));
                        $subGroupId = @$this->GetGroupIdByName($subGroupName);
    				    if ( ($switchInGroup) && ($subGroupId!==false) )
                            {
                            //echo "SetAllGroups, check : ".$subGroupName."\n";
                            $this->SetGroup($subGroupId, $value);
                            }
                        }
                    }
                }    
			}

		// ----------------------------------------------------------------------------------------------------------------------------
		private function SynchronizeGroup ($groupId) {
            //echo "         SynchronizeGroups ".$groupId."   ".IPS_GetName($groupId)."\n";
			$lightConfig = IPSHeat_GetHeatConfiguration();
			$groupName   = IPS_GetName($groupId);
			$groupState  = false;
			foreach ($lightConfig as $switchName=>$deviceData) {
				$switchId      = IPS_GetVariableIDByName($switchName, $this->switchCategoryId);
				$switchState   = GetValue($switchId);
				$switchInGroup = array_key_exists($groupName, array_flip(explode(',', $deviceData[IPSHEAT_GROUPS])));
				if ($switchInGroup and GetValue($switchId)) {
					$groupState = true;
					break;
				}
			}
			if (GetValue($groupId) <> $groupState) {
				IPSLogger_Trc(__file__, "Synchronize ".($switchState?'On':'Off')." to Group '$groupName' from Switch '$switchName'");
				SetValue($groupId, $groupState);
			}
		}

		// ----------------------------------------------------------------------------------------------------------------------------
		private function SynchronizeProgramsBySwitch($switchId) {
			$switchName = IPS_GetName($switchId);
			$programConfig   = IPSHeat_GetProgramConfiguration();

			foreach ($programConfig as $programName=>$programData) {
				foreach ($programData as $programItemName=>$programItemData) {
					if (array_key_exists(IPSHEAT_PROGRAMON, $programItemData)) {
						if ($this->SynchronizeProgramItemBySwitch($switchName, $programName, $programItemData[IPSHEAT_PROGRAMON])) {
							return;
						}
					}
					if (array_key_exists(IPSHEAT_PROGRAMOFF, $programItemData)) {
						if ($this->SynchronizeProgramItemBySwitch($switchName, $programName, $programItemData[IPSHEAT_PROGRAMOFF])) {
							return;
						}
					}
					if (array_key_exists(IPSHEAT_PROGRAMLEVEL, $programItemData)) {
						if ($this->SynchronizeProgramItemBySwitch($switchName, $programName, $programItemData[IPSHEAT_PROGRAMLEVEL])) {
							return;
						}
					}
				}
			}
		}

		// ----------------------------------------------------------------------------------------------------------------------------
		private function SynchronizeProgramItemBySwitch($switchName, $programName, $property) {
			$propertyList = explode(',', $property);
			$switchList = array_flip($propertyList);
			if (array_key_exists($switchName,  $switchList)) {
				$programId   = IPS_GetVariableIDByName($programName, $this->programCategoryId);
				IPSLogger_Trc(__file__, "Reset Program '$programName' by manual Change of '$switchName'");
				SetValue($programId, 0);
				return true;
			}
			return false;
		}

		/* ----------------------------------------------------------------------------------------------------------------------------
         *   von SetGroup aufgerufen
         *
         */

		private function SetAllSwitchesByGroup ($groupId, $debug=false,$exectime=false) 
			{
			$groupName    	= $this->GetConfigNameById($groupId);
			$groupNameLong	= IPS_GetName($groupId);
			$lightConfig  = IPSHeat_GetHeatConfiguration();
			$groupConfig  = IPSHeat_GetGroupConfiguration();
			$groupType		= $groupConfig[$groupName][IPSHEAT_TYPE];
			if ($debug) echo "      SetAllSwitchesByGroup: aufgerufen mit ".$groupConfig[$groupName][IPSHEAT_TYPE]."   ".$groupName."   (".$groupId.")   ".$groupNameLong."\n";			
			if ($groupName <> $groupNameLong)			/* Wenn Zusatzparameter behandelt wird wie zB #LEVEL */
				{
				$pos=strpos($groupNameLong,$groupName);
				$pos1=strpos($groupNameLong,"#");
				if ( ($pos==0) and !($pos1===false) )
					{
					$NameExt=substr($groupNameLong,$pos1);
					$groupState   = GetValue($groupId);     /* GroupState ist ein Wert zB Temperatur oder Farbe */
					if ($debug) echo "       SetAllSwitchesByGroup: bearbeiten mit ".$groupId."   ".$groupName."   ".$groupNameLong."   ".$NameExt."   ".$groupState."\n";
					foreach ($lightConfig as $switchName=>$deviceData) 
						{
						if ($deviceData[IPSHEAT_TYPE] == $groupType)
							{
							//print_r($deviceData);
							/* die ganze Konfiguration durchgehen. Aber nicht alle haben zB #Level */
							$switchId      = IPS_GetVariableIDByName($switchName.$NameExt, $this->switchCategoryId);
							$switchInGroup = array_key_exists($groupName, array_flip(explode(',', $deviceData[IPSHEAT_GROUPS])));
							if ($switchInGroup) echo "         ".$switchName.$NameExt."   ".IPS_GetName($switchId)." = ".GetValue($switchId)." Sollwert : ".$groupState."\n";
							if ($switchInGroup and GetValue($switchId)<>$groupState) 
								{
								IPSLogger_Trc(__file__, "SetAllSwitchesByGroup: Set Heat ".$switchName.$NameExt."=".$groupState." for Group '".$groupName."'");
								if ($debug) 
                                    {
                                    //echo "       SetAllSwitchesByGroup: Set Heat ".$switchName.$NameExt."=".$groupState." for Group '".$groupName."'\n";
                                    //echo "       SetAllSwitchesByGroup: Set Heat $switchName=".($groupState?'On':'Off')." for Group $groupName .";
                                    echo "                    SetValue($switchId, $groupState,...) ";
                                    if ($exectime) { $duration=round(hrtime(true)/1000000-$exectime,0); echo 'Ausführungszeit aktuell: '.$duration.' Millisekunden.'."\n"; }
                                    else echo "\n";
                                    }
								$this->SetValue($switchId, $groupState,$debug,$exectime);
								$this->SynchronizeGroupsBySwitch ($switchId,$debug,$exectime);
								}
							}
						}					
					}
				}
			else
				{
				$groupState   = GetValue($groupId);
				foreach ($lightConfig as $switchName=>$deviceData) 
					{
					$switchId      = IPS_GetVariableIDByName($switchName, $this->switchCategoryId);
					$switchInGroup = array_key_exists($groupName, array_flip(explode(',', $deviceData[IPSHEAT_GROUPS])));
					//if ($switchInGroup) echo "   ".$switchName."\n";
					if ($switchInGroup and GetValue($switchId)<>$groupState) 
						{
    					if ($debug) 
                            {
                            echo "       SetAllSwitchesByGroup: Set Heat $switchName=".($groupState?'On':'Off')." for Group $groupName .";
                            if ($exectime) { $duration=round(hrtime(true)/1000000-$exectime,0); echo 'Ausführungszeit aktuell: '.$duration.' Millisekunden.'."\n"; }
                            else echo "\n";
                            }
						IPSLogger_Trc(__file__, "Set Light $switchName=".($groupState?'On':'Off')." for Group '$groupName'");
						$this->SetValue($switchId, $groupState,$debug,$exectime);
						$this->SynchronizeGroupsBySwitch ($switchId,$debug,$exectime);
						}
					}
				}	
			}


		// ----------------------------------------------------------------------------------------------------------------------------
		public function SynchronizeSwitch($switchName, $deviceState) {
			IPSLogger_Trc(__file__, "Received StateChange from Heat '$switchName'=$deviceState");
			//echo "Received StateChange from Heat ".$switchName."=".$deviceState."\n";
			$switchId    = IPS_GetVariableIDByName($switchName, $this->switchCategoryId);

			$lightConfig = IPSHeat_GetHeatConfiguration();
			$deviceType  = $lightConfig[$switchName][IPSHEAT_TYPE];

			if (IPSHeat_BeforeSynchronizeSwitch($switchId, $deviceState)) {
				//echo "Synchronize StateChange from Light ".$switchName.", State=".($deviceState?'On':'Off')."\n";
				if (GetValue($switchId) <> $deviceState) {
					IPSLogger_Inf(__file__, 'Synchronize StateChange from Light '.$switchName.', State='.($deviceState?'On':'Off'));
					SetValue($switchId, $deviceState);
					$this->SynchronizeGroupsBySwitch($switchId);
					$this->SynchronizeProgramsBySwitch($switchId);
				}
			}
			IPSHeat_AfterSynchronizeSwitch($switchId, $deviceState);
		}

		/* ----------------------------------------------------------------------------------------------------------------------------
         *
         */
		public function SynchronizePosition($switchName, $deviceState, $deviceLevel) {
			IPSLogger_Trc(__file__, 'Received StateChange from Light '.$switchName.', State='.$deviceState.', Level='.$deviceLevel);
			$switchId    = IPS_GetVariableIDByName($switchName, $this->switchCategoryId);
			$levelId     = IPS_GetVariableIDByName($switchName.IPSHEAT_DEVICE_LEVEL, $this->switchCategoryId);

			$lightConfig = IPSHeat_GetHeatConfiguration();
			$deviceType  = $lightConfig[$switchName][IPSHEAT_TYPE];

			if (IPSHeat_BeforeSynchronizeSwitch($switchId, $deviceState)) {
				if (GetValue($switchId)<>$deviceState or GetValue($levelId)<>$deviceLevel) {
					IPSLogger_Inf(__file__, 'Synchronize StateChange from Light '.$switchName.', State='.($deviceState?'On':'Off').', Level='.$deviceLevel);
					SetValue($switchId, $deviceState);
					SetValue($levelId, $deviceLevel);
					$this->SynchronizeGroupsBySwitch($switchId);
					$this->SynchronizeProgramsBySwitch($switchId);
				}
			}
			IPSHeat_AfterSynchronizeSwitch($switchId, $deviceState);
		}

		/* ----------------------------------------------------------------------------------------------------------------------------
         * wird derzeit nur von der Funktion SyncSetTemp in IPSModuleHeatset_All aufgerufen
         * SyncSettemp ist der Zustands Synchronizer des configurierten Moduls (IPSModule_HeatSet_All) in der Routine HandleEvent des configurierten Components
         * Die Routine SyncSettemp geht die ganze IPSHeat_GetHeatConfiguration() Konfiguration durch und findet dann anhand der selben OID den abzugleichenden Eintrag
         * es wird der zweite Eintrag, die OID, verglichen.
         * zusätzlich wird geprüft ob statt der Synchronisierung (also Übernahme des Status aller Mitglieder einer Gruppe in den Gruppenstatus)
         * das Setzen des Wertes für alle Mitglieder der Gruppe erforderlich ist : [IPSHEAT_ACTIVATABLE] der Geräte Config ist true
         *
         * der Temperatur Sollwert von einem Homematic Thermostat oder eines anderen Geraetes wird, wenn er sich geändert hat, in die Spiegelvariable von IPSHeat übernommen
         */
		public function SynchronizeSetTemp($switchName, $deviceLevel) {
			IPSLogger_Trc(__file__, 'Received StateChange from Light '.$switchName.', Level='.$deviceLevel);
			$switchId    = IPS_GetVariableIDByName($switchName, $this->switchCategoryId);
			$levelId     = IPS_GetVariableIDByName($switchName.IPSHEAT_DEVICE_TEMPERATURE, $this->switchCategoryId);

			$lightConfig = IPSHeat_GetHeatConfiguration();
			$deviceType  = $lightConfig[$switchName][IPSHEAT_TYPE];
			
			if (isset($lightConfig[$switchName][IPSHEAT_ACTIVATABLE]) && $lightConfig[$switchName][IPSHEAT_ACTIVATABLE]) $updatelevel=true; else $updatelevel=false;

			echo "      IPSHeat_Manager SynchronizeSetTemp , do Synchronize Temp Change for ".$switchName.', Temp='.$deviceLevel."    Group Update: ".($updatelevel?"Yes":"No")."\n";
			IPSLogger_Inf(__file__,"IPSHeat_Manager SynchronizeSetTemp , do Synchronize Temp Change for ".$switchName.', Temp='.GetValue($levelId)." to ".$deviceLevel."    Group Update: ".($updatelevel?"Yes":"No"));
			if (IPSHeat_BeforeSynchronizeSwitch($switchId, $deviceLevel)) {
				if (GetValue($levelId)<>$deviceLevel) {
                    /* Wert hat sich geändert */
					//IPSLogger_Inf(__file__, 'Synchronize StateChange from Light '.$switchName.', State='.($deviceState?'On':'Off').', Level='.$deviceLevel);
					//SetValue($switchId, $deviceState);
					IPSLogger_Inf(__file__, 'Synchronize Temp Change from Heat/Light '.$switchName.', Temp='.$deviceLevel." with $levelId (".IPS_GetName($levelId).")");
					echo "         Synchronize Temp Change from Heat/Light $switchName, Temp=$deviceLevel with $levelId (".IPS_GetName($levelId).")\n";
					SetValue($levelId, $deviceLevel);
					if ($updatelevel)		// die Temperatur in der Gruppe synchronisieren, wenn so parametriert
						{
						//echo "Update Level in all Thermostats of Group : ".($lightConfig[$switchName][IPSHEAT_GROUPS])."\n";
						$groups      = explode(',', $lightConfig[$switchName][IPSHEAT_GROUPS]);
						foreach ($groups as $groupName) 
							{
							echo "       SetAllSwitchesByGroup Temperaturwert für  ".$groupName.IPSHEAT_DEVICE_TEMPERATURE."\n";
							$groupId  = IPS_GetVariableIDByName($groupName.IPSHEAT_DEVICE_TEMPERATURE, $this->groupCategoryId);
							$this->SetGroup($groupId, $deviceLevel);			// alle Level Werte einer Gruppe updaten
							}						
						}
                    else $this->SynchronizeGroupsBySwitch($switchId);		// Schalter synchronisieren anhand switchID, macht keinen Unterschied wenn LevelID verwendet wird
					$this->SynchronizeProgramsBySwitch($switchId);
				}
			}
			IPSHeat_AfterSynchronizeSwitch($switchId, $deviceLevel);
		}

		/* ----------------------------------------------------------------------------------------------------------------------------
         * wird derzeit nur von der Funktion SyncSetMode in IPSModuleHeatset_All aufgerufen
         * SyncSettemp ist der Zustands Synchronizer des configurierten Moduls (IPSModule_HeatSet_All) in der Routine HandleEvent des configurierten Components
         * Die Routine SyncSettemp geht die ganze IPSHeat_GetHeatConfiguration() Konfiguration durch und findet dann anhand der selben OID den abzugleichenden Eintrag
         * es wird der zweite Eintrag, die OID, verglichen.
         * zusätzlich wird geprüft ob statt der Synchronisierung (also Übernahme des Status aller Mitglieder einer Gruppe in den Gruppenstatus)
         * das Setzen des Wertes für alle Mitglieder der Gruppe erforderlich ist : [IPSHEAT_ACTIVATABLE] der Geräte Config ist true
         *
         * der Temperatur Sollwert von einem Homematic Thermostat oder eines anderen Geraetes wird, wenn er sich geändert hat, in die Spiegelvariable von IPSHeat übernommen
         */
		public function SynchronizeSetMode($switchName, $deviceLevel) {
			IPSLogger_Trc(__file__, 'Received StateChange from Heat '.$switchName.', Mode='.$deviceLevel);
			$switchId    = IPS_GetVariableIDByName($switchName, $this->switchCategoryId);
			$levelId     = IPS_GetVariableIDByName($switchName.IPSHEAT_DEVICE_MODE, $this->switchCategoryId);

			$lightConfig = IPSHeat_GetHeatConfiguration();
			$deviceType  = $lightConfig[$switchName][IPSHEAT_TYPE];
			
			if (isset($lightConfig[$switchName][IPSHEAT_ACTIVATABLE]) && $lightConfig[$switchName][IPSHEAT_ACTIVATABLE]) $updatelevel=true; else $updatelevel=false;

			echo "HeatManager class, SynchronizeSetMode , do Synchronize Mode Change from Light/Heat \"".$switchName."\", Mode=".$deviceLevel."   UpdateLevel=".($updatelevel?"Ja":"Nein")."\n";
			IPSLogger_Inf(__file__,"HeatManager class, SynchronizeSetMode , do Synchronize Mode Change from Light/Heat \"".$switchName."\", Mode=".$deviceLevel."   UpdateLevel=".($updatelevel?"Ja":"Nein")." old Mode Value :".GetValue($levelId));
			if (IPSHeat_BeforeSynchronizeSwitch($switchId, $deviceLevel)) {
				echo "Vergleiche Wert von ".$levelId." (".IPS_GetName($levelId).") ".GetValue($levelId)." mit ".$deviceLevel.".\n";
				if (GetValue($levelId)<>$deviceLevel) {
                    echo "   -> Wert hat sich geändert.\n";
					//IPSLogger_Inf(__file__, 'Synchronize StateChange from Light '.$switchName.', State='.($deviceState?'On':'Off').', Level='.$deviceLevel);
					//SetValue($switchId, $deviceState);
					IPSLogger_Inf(__file__, 'Synchronize Change from Light/Heat '.$switchName.', Mode='.$deviceLevel);
					SetValue($levelId, $deviceLevel);
					if ($updatelevel)		// die Temperatur in der Gruppe synchronisieren, wenn so parametriert
						{
						//echo "Update Level in all Thermostats of Group : ".($lightConfig[$switchName][IPSHEAT_GROUPS])."\n";
						$groups      = explode(',', $lightConfig[$switchName][IPSHEAT_GROUPS]);
						foreach ($groups as $groupName) 
							{
							//echo "  SetAllSwitchesByGroup Temperaturwert für  ".$groupName.IPSHEAT_DEVICE_LEVEL."\n";
							$groupId  = IPS_GetVariableIDByName($groupName.IPSHEAT_DEVICE_MODE, $this->groupCategoryId);
							$this->SetGroup($groupId, $deviceLevel);			// alle Level Werte einer Gruppe updaten
							}						
						}
                    else $this->SynchronizeGroupsBySwitch($switchId);		// Schalter synchronisieren anhand switchID, macht keinen Unterschied wenn LevelID verwendet wird
					$this->SynchronizeProgramsBySwitch($switchId);
				}
			}
			IPSHeat_AfterSynchronizeSwitch($switchId, $deviceLevel);
		}


		// ----------------------------------------------------------------------------------------------------------------------------
		public function SynchronizeDimmer($switchName, $deviceState, $deviceLevel) {
			IPSLogger_Trc(__file__, 'Received StateChange from Light '.$switchName.', State='.$deviceState.', Level='.$deviceLevel);
			$switchId    = IPS_GetVariableIDByName($switchName, $this->switchCategoryId);
			$levelId     = IPS_GetVariableIDByName($switchName.IPSHEAT_DEVICE_LEVEL, $this->switchCategoryId);

			$lightConfig = IPSHeat_GetHeatConfiguration();
			$deviceType  = $lightConfig[$switchName][IPSHEAT_TYPE];

			if (IPSHeat_BeforeSynchronizeSwitch($switchId, $deviceState)) {
				if (GetValue($switchId)<>$deviceState or GetValue($levelId)<>$deviceLevel) {
					IPSLogger_Inf(__file__, 'Synchronize StateChange from Light '.$switchName.', State='.($deviceState?'On':'Off').', Level='.$deviceLevel);
					SetValue($switchId, $deviceState);
					SetValue($levelId, $deviceLevel);
					$this->SynchronizeGroupsBySwitch($switchId);
					$this->SynchronizeProgramsBySwitch($switchId);
				}
			}
			IPSHeat_AfterSynchronizeSwitch($switchId, $deviceState);
		}
		
		// ----------------------------------------------------------------------------------------------------------------------------
		public function SynchronizeRGB($switchName, $deviceState, $deviceLevel, $deviceRGB) {
			IPSLogger_Trc(__file__, 'Received StateChange from Light '.$switchName.', State='.$deviceState.', Level='.$deviceLevel.', RGB='.$deviceRGB);
			$switchId    = IPS_GetVariableIDByName($switchName, $this->switchCategoryId);
			$levelId     = IPS_GetVariableIDByName($switchName.IPSHEAT_DEVICE_LEVEL, $this->switchCategoryId);
			$rgbId       = IPS_GetVariableIDByName($switchName.IPSHEAT_DEVICE_COLOR, $this->switchCategoryId);

			$lightConfig = IPSHeat_GetHeatConfiguration();
			$deviceType  = $lightConfig[$switchName][IPSHEAT_TYPE];

			if (IPSHeat_BeforeSynchronizeSwitch($switchId, $deviceState)) {
				if (GetValue($switchId)<>$deviceState or GetValue($levelId)<>$deviceLevel or GetValue($rgbId)<>$deviceRGB) {
					IPSLogger_Inf(__file__, 'Synchronize StateChange from Light '.$switchName.', State='.($deviceState?'On':'Off').', Level='.$deviceLevel.', RGB='.$deviceRGB);
					SetValue($switchId, $deviceState);
					SetValue($levelId,  $deviceLevel);
					SetValue($rgbId,    $deviceRGB);
					$this->SynchronizeGroupsBySwitch($switchId);
					$this->SynchronizeProgramsBySwitch($switchId);
				}
			}
			IPSHeat_AfterSynchronizeSwitch($switchId, $deviceState);
		}

		// ----------------------------------------------------------------------------------------------------------------------------
		public function GetPowerConsumption($powerCircle) {
			$powerConsumption = 0;
			$lightConfig      = IPSHeat_GetHeatConfiguration();
			foreach ($lightConfig as $switchName=>$deviceData) {
				$lightType  = $lightConfig[$switchName][IPSHEAT_TYPE];
				if (array_key_exists(IPSHEAT_POWERCIRCLE, $deviceData) and $deviceData[IPSHEAT_POWERCIRCLE]==$powerCircle) {
					$switchId = IPS_GetVariableIDByName($switchName, $this->switchCategoryId);
					if (GetValue($switchId)) {
						switch ($lightType) {
							case IPSHEAT_TYPE_SWITCH:
								$powerConsumption = $powerConsumption + $deviceData[IPSHEAT_POWERWATT];
								break;
							case IPSHEAT_TYPE_DIMMER:
							case IPSHEAT_TYPE_RGB:
								$levelId = IPS_GetVariableIDByName($switchName.IPSHEAT_DEVICE_LEVEL, $this->switchCategoryId);
								$powerConsumption = $powerConsumption + $deviceData[IPSHEAT_POWERWATT]*GetValue($levelId)/100;
								break;
							default:
								trigger_error('Unknown LightType '.$lightType.' for Light '.$configName);
						}

					}
				}
			}
			return $powerConsumption;
		}
	}

	/** @}*/
?>
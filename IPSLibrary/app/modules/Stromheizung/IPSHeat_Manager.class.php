<?
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
     * __construct
     * GetSwitchIdByName, GetLevelIdByName, GetColorIdByName, GetAmbienceIdByName
     * GetGroupIdByName
     * GetProgramIdByName
     * GetValue
	 * SetValue					wird bei einer Variablenänderung aus dem Webfront aufgerufen
     * SetSwitch, SetDimmer, SetRGB,SetAmbient, SetHeat
     * SetGroup
     * SetProgram
     * GetConfigById, 
	 * GetConfigNameById		eliminiert bekannte Erweiterungen wie #Level und gibt den Hauptnamen zurück
     * SynchronizeGroupsBySwitch
	 * SynchronizeGroupsByGroup
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

		/**
		 * @public
		 *
		 * Initialisierung des IPSHeat_Manager Objektes
		 *
		 */
		public function __construct() 
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
			
			/* Vorbereitung für ein Sync zwischen IPSHeat und IPSLight, aber nicht zurück */
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
			return IPS_GetVariableIDByName($name, $this->groupCategoryId);
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
		public function SetValue($variableId, $value) 
			{
			$parentId = IPS_GetParent($variableId);
			//echo "IPS class HeatManager SetValue : ".$parentId."  (".IPS_GetName($parentId).")\n";
			switch($parentId) {
				case $this->switchCategoryId:
					$configName = $this->GetConfigNameById($variableId);
					$configLights = IPSHeat_GetHeatConfiguration();
					//echo "IPS class HeatManager SetValue : ".$parentId."  (".IPS_GetName($parentId).")    $configName \n";					
					$lightType    = $configLights[$configName][IPSHEAT_TYPE];
					switch ($lightType)
						{
						case IPSHEAT_TYPE_SWITCH:
							//echo "IPS_HeatManager SetValue Type Switch SetValue.\n";
							$this->SetSwitch($variableId, $value);
							break;
						case IPSHEAT_TYPE_DIMMER:
							//echo "IPS_HeatManager SetValue Type Dimmer SetDimmer.\n";
							$this->SetDimmer($variableId, $value);
							break;
						case IPSHEAT_TYPE_RGB:
							//echo "IPS_HeatManager SetValue Type RGB SetRGB.\n";
							$this->SetRGB($variableId, $value);
							break;
						case IPSHEAT_TYPE_AMBIENT:
							//echo "IPS_HeatManager SetValue Type RGB SetRGB.\n";
							$this->SetAmbient($variableId, $value);
							break;
						case IPSHEAT_TYPE_SET:
							//echo "IPS_HeatManager SetValue Type Heat SetHeat ; ".$variableId."  (".IPS_GetName($variableId).") ".$value."\n";
							//echo "     ".$configName."\n";
							$this->SetHeat($variableId, $value);
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
		public function SetSwitch($switchId, $value, $syncGroups=true, $syncPrograms=true) 
			{
			//echo "Aufruf SetSwitch mit ".$switchId." ".$value." \n";
			if (GetValue($switchId)==$value) {
				return;
			}
			$configName   = $this->GetConfigNameById($switchId);
			$configLights = IPSHeat_GetHeatConfiguration();
			$componentParams = $configLights[$configName][IPSHEAT_COMPONENT];
			$component       = IPSComponent::CreateObjectByParams($componentParams);

			SetValue($switchId, $value);
			IPSLogger_Inf(__file__, 'Turn Heat/Light '.$configName.' '.($value?'On':'Off'));

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
			if (GetValue($variableId)==$value) {
				return;
			}
			$configName   = $this->GetConfigNameById($variableId);
			$configLights = IPSHeat_GetHeatConfiguration();
			$switchId     = IPS_GetVariableIDByName($configName, $this->switchCategoryId);
			$switchValue  = GetValue($switchId);
			$levelId      = IPS_GetVariableIDByName($configName.IPSHEAT_DEVICE_LEVEL, $this->switchCategoryId);
			$levelValue   = GetValue($levelId);

			$componentParams = $configLights[$configName][IPSHEAT_COMPONENT];
			$component       = IPSComponent::CreateObjectByParams($componentParams);

			if ($variableId==$levelId) {
			   if (!$switchValue and $value>0) {
				   SetValue($switchId, true);
			   } else if ($switchValue and $value==0) {
				   SetValue($switchId, false);
			   } else {
			   }
			   if (GetValue($levelId) > 100) { $value = 100; }
				if (GetValue($levelId) < 0)   { $value = 0; }
			} else {
			   if ($value and $levelValue==0) {
			      SetValue($levelId, 15);
			   }
			}
			SetValue($variableId, $value);

			$switchValue  = GetValue($switchId);
			IPSLogger_Inf(__file__, 'Turn Heat/Light '.$configName.' '.($switchValue?'On, Level='.GetValue($levelId):'Off'));

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
		public function SetRGB($variableId, $value, $syncGroups=true, $syncPrograms=true) {
			if (GetValue($variableId)==$value) {
				return;
			}
			//echo "SetRGB ".$variableId." (".IPS_GetName($variableId).") ".$value." ".dechex($value)."\n";
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
			IPSLogger_Inf(__file__, 'Turn Heat/Light '.$configName.' '.($switchValue?'On, Level='.GetValue($levelId).', Color='.GetValue($colorId):'Off'));

			if (IPSHeat_BeforeSwitch($switchId, $switchValue)) {
				$component->SetState(GetValue($switchId), GetValue($colorId), GetValue($levelId));
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
		public function SetHeat($variableId, $value, $syncGroups=true, $syncPrograms=true) 
			{
			if (GetValue($variableId)==$value) 
				{
				return;
				}
			$configName   = $this->GetConfigNameById($variableId);
			$configLights = IPSHeat_GetHeatConfiguration();
			
			$switchId     = IPS_GetVariableIDByName($configName, $this->switchCategoryId);
			$switchValue  = GetValue($switchId);
			//$levelId      = IPS_GetVariableIDByName($configName.IPSHEAT_DEVICE_LEVEL, $this->switchCategoryId);		// dekativiert, wird nicht mehr verwendet
			//$levelValue   = GetValue($levelId);
			$tempId      = IPS_GetVariableIDByName($configName.IPSHEAT_DEVICE_TEMPERATURE, $this->switchCategoryId);
			$tempValue   = GetValue($tempId);
			$modeId      = IPS_GetVariableIDByName($configName.IPSHEAT_DEVICE_MODE, $this->switchCategoryId);
			$modeValue   = GetValue($modeId);
			
			
			$componentParams = $configLights[$configName][IPSHEAT_COMPONENT];
			//echo "IPS class HeatManager SetHeat : ".$variableId."  (".IPS_GetName($variableId).")  ".$componentParams."\n";

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
					SetValue($tempId, $value);

					//echo "   Set Heat/Light $configName ".(GetValue($switchId)?'On, Level='.GetValue($tempId):'Off').". Mode=".GetValue($modeId)."\n";
					//print_r($component);
			
					IPSLogger_Inf(__file__, 'Set Heat/Light '.$configName.' '.($switchValue?'On, Level='.$tempValue:'Off'));

					if (IPSHeat_BeforeSwitch($switchId, $switchValue)) 			// keine andere Funktion als die Switch Hauptfunktion definiert, anders wäre keine Unterscheidung möglich
						{
						$component->SetLevel(GetValue($switchId), GetValue($tempId));
						}
					IPSHeat_AfterSwitch($switchId, $switchValue);
					break;
				case $modeId:
					SetValue($modeId, $value);				
					IPSLogger_Inf(__file__, 'Set Heat/Light Mode '.$configName.' '.($switchValue?'On, Level='.$tempValue:'Off').'  '.GetValue($modeId));

					if (IPSHeat_BeforeSwitch($switchId, $switchValue))			// keine andere Funktion als die Switch Hauptfunktion definiert, anders wäre keine Unterscheidung möglich 
						{
						$component->SetMode(GetValue($switchId), GetValue($modeId));	// zweiten Parameter hier austauschen
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
				$this->SynchronizeGroupsBySwitch($switchId);
				}
			if ($syncPrograms) 
				{
				$this->SynchronizeProgramsBySwitch ($switchId);
				}
			}		// ende function

		/**
		 * @public
		 *
		 * Setzt den Wert einer Gruppen Variable anhand der zugehörigen ID
		 *
		 * @param int $variableId ID der Gruppe
		 * @param bool $value Neuer Wert der Gruppe
		 */
		public function SetGroup($groupId, $value, $syncGroups=true, $syncPrograms=true) 
            {
			$groupConfig = IPSHeat_GetGroupConfiguration();
			$groupName   = $this->GetConfigNameById($groupId);			
			$groupNameLong   = IPS_GetName($groupId);
			if ($value and !$groupConfig[$groupName][IPSHEAT_ACTIVATABLE]) {
				if ($groupName == $groupNameLong) IPSLogger_Trc(__file__, "Ignore ".($value?'On':'Off')." forHeatGroup '$groupName' (not allowed)");
                else IPSLogger_Trc(__file__, "Ignore ".$value." forHeatGroup '$groupNameLong' (not allowed)");
			} else {
				SetValue($groupId, $value);
				if ($groupName == $groupNameLong) IPSLogger_Inf(__file__, "Turn HeatGroup '$groupName' ".($value?'On':'Off'));
                else IPSLogger_Inf(__file__, "Set HeatGroup '$groupNameLong' to $value");
				$this->SetAllSwitchesByGroup($groupId);
                
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
		private function GetConfigNameById($switchId) {
			$switchName = IPS_GetName($switchId);
			$switchName = str_replace(IPSHEAT_DEVICE_COLOR,       '', $switchName);
			$switchName = str_replace(IPSHEAT_DEVICE_LEVEL,       '', $switchName);
			$switchName = str_replace(IPSHEAT_DEVICE_AMBIENCE,    '', $switchName);
			$switchName = str_replace(IPSHEAT_DEVICE_TEMPERATURE, '', $switchName);
			$switchName = str_replace(IPSHEAT_DEVICE_MODE,        '', $switchName);

			return $switchName;
		}

		// ----------------------------------------------------------------------------------------------------------------------------
		private function SynchronizeGroupsBySwitch ($switchId) 
			{
			$switchName    	= $this->GetConfigNameById($switchId);
			$switchNameLong	= IPS_GetName($switchId);
			$lightConfig  = IPSHeat_GetHeatConfiguration();		
			if ($switchName <> $switchNameLong)
				{
				$pos=strpos($switchNameLong,$switchName);
				$pos1=strpos($switchNameLong,"#");
				if ( ($pos==0) and !($pos1===false) )
					{
					//echo "SynchronizeGroupsBySwitch für #Level da $switchName ungleich $switchNameLong \n";
					$NameExt=substr($switchNameLong,$pos1);
					$groups      = explode(',', $lightConfig[$switchName][IPSHEAT_GROUPS]);
					//echo "         SynchronizeGroupsBySwitch \n";					
					//print_r($groups);
					foreach ($groups as $groupName) 
						{
						//echo "         SynchronizeGroupsBySwitch ".$switchId."   ".$groupName."\n";
						$groupId  = IPS_GetVariableIDByName($groupName, $this->groupCategoryId);
						$this->SynchronizeGroup($groupId);
						}				
					}
				}
			else
				{	
				//echo "         SynchronizeGroupsBySwitch ".$switchId."   \"".$lightConfig[$switchName][IPSHEAT_GROUPS]."\"\n";
				$groups      = explode(',', $lightConfig[$switchName][IPSHEAT_GROUPS]);
				foreach ($groups as $groupName) 
					{
					if ($groupName != "")
						{
						//echo "         SynchronizeGroupsBySwitch ".$switchId."   ".$groupName."\n";
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

		// ----------------------------------------------------------------------------------------------------------------------------
		private function SetAllSwitchesByGroup ($groupId) 
			{
			$groupName    	= $this->GetConfigNameById($groupId);
			$groupNameLong	= IPS_GetName($groupId);
			$lightConfig  = IPSHeat_GetHeatConfiguration();
			$groupConfig  = IPSHeat_GetGroupConfiguration();
			$groupType		= $groupConfig[$groupName][IPSHEAT_TYPE];
			//echo "SetAllSwitchesByGroup : ".$groupConfig[$groupName][IPSHEAT_TYPE]."   ".$groupName."   (".$groupId.")   ".$groupNameLong."\n";			
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
					foreach ($lightConfig as $switchName=>$deviceData) 
						{
						if ($deviceData[IPSHEAT_TYPE] == $groupType)
							{
							//print_r($deviceData);
							/* die ganze Konfiguration durchgehen. Aber nicht alle haben zB #Level */
							$switchId      = IPS_GetVariableIDByName($switchName.$NameExt, $this->switchCategoryId);
							$switchInGroup = array_key_exists($groupName, array_flip(explode(',', $deviceData[IPSHEAT_GROUPS])));
							//if ($switchInGroup) echo "   ".$switchName.$NameExt."   ".IPS_GetName($switchId)." = ".GetValue($switchId)." Sollwert : ".$groupState."\n";
							if ($switchInGroup and GetValue($switchId)<>$groupState) 
								{
								IPSLogger_Trc(__file__, "SetAllSwitchesByGroup: Set Heat ".$switchName.$NameExt."=".$groupState." for Group '".$groupName."'");
								//echo "       SetAllSwitchesByGroup: Set Light ".$switchName.$NameExt."=".$groupState." for Group '".$groupName."'\n";
								$this->SetValue($switchId, $groupState);
								$this->SynchronizeGroupsBySwitch ($switchId);
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
						IPSLogger_Trc(__file__, "Set Light $switchName=".($groupState?'On':'Off')." for Group '$groupName'");
						$this->SetValue($switchId, $groupState);
						$this->SynchronizeGroupsBySwitch ($switchId);
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

			//echo "HeatManager class, SynchronizeSetTemp , do Synchronize Level Change from Light ".$switchName.', Level='.$deviceLevel."\n";
			if (IPSHeat_BeforeSynchronizeSwitch($switchId, $deviceLevel)) {
				if (GetValue($levelId)<>$deviceLevel) {
                    /* Wert hat sich geändert */
					//IPSLogger_Inf(__file__, 'Synchronize StateChange from Light '.$switchName.', State='.($deviceState?'On':'Off').', Level='.$deviceLevel);
					//SetValue($switchId, $deviceState);
					IPSLogger_Inf(__file__, 'Synchronize Level Change from Light '.$switchName.', Level='.$deviceLevel);
					SetValue($levelId, $deviceLevel);
					if ($updatelevel)		// die Temperatur in der Gruppe synchronisieren, wenn so parametriert
						{
						//echo "Update Level in all Thermostats of Group : ".($lightConfig[$switchName][IPSHEAT_GROUPS])."\n";
						$groups      = explode(',', $lightConfig[$switchName][IPSHEAT_GROUPS]);
						foreach ($groups as $groupName) 
							{
							//echo "  SetAllSwitchesByGroup Temperaturwert für  ".$groupName.IPSHEAT_DEVICE_LEVEL."\n";
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
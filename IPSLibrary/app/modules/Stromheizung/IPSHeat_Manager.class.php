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
	 * @author        Andreas Brauneis
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
	 * @author Andreas Brauneis
	 * @version
	 *   Version 2.50.1, 26.07.2012<br/>
	 */
	class IPSHeat_Manager {

		/**
		 * @private
		 * ID Kategorie mit Schalter und Dimmern
		 */
		private $switchCategoryId;

		/**
		 * @private
		 * ID Kategorie mit Schalter
		 */
		private $groupCategoryId;

		/**
		 * @private
		 * ID Kategorie mit Programmen
		 */
		private $programCategoryId;

		/**
		 * @public
		 *
		 * Initialisierung des IPSHeat_Manager Objektes
		 *
		 */
		public function __construct() {
			$baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Stromheizung');
			$this->switchCategoryId  = IPS_GetObjectIDByIdent('Switches', $baseId);
			$this->groupCategoryId   = IPS_GetObjectIDByIdent('Groups', $baseId);
			$this->programCategoryId = IPS_GetObjectIDByIdent('Programs', $baseId);
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
		public function SetValue($variableId, $value) {
			$parentId = IPS_GetParent($variableId);
			switch($parentId) {
				case $this->switchCategoryId:
					$configName = $this->GetConfigNameById($variableId);
					$configLights = IPSHeat_GetHeatConfiguration();
					$lightType    = $configLights[$configName][IPSHEAT_TYPE];
					switch ($lightType)
						{
						case IPSHEAT_TYPE_SWITCH:
							$this->SetSwitch($variableId, $value);
							break;
						case IPSHEAT_TYPE_DIMMER:
							$this->SetDimmer($variableId, $value);
							break;
						case IPSHEAT_TYPE_RGB:
							$this->SetRGB($variableId, $value);
							break;
						case IPSHEAT_TYPE_SET:
							$this->SetHeat($variableId, $value);
							break;
						default:
							trigger_error('Unknown LightType '.$lightType.' for Light '.$configName);
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
		public function SetSwitch($switchId, $value, $syncGroups=true, $syncPrograms=true) {
			if (GetValue($switchId)==$value) {
				return;
			}
			$configName   = $this->GetConfigNameById($switchId);
			$configLights = IPSHeat_GetHeatConfiguration();
			$componentParams = $configLights[$configName][IPSHEAT_COMPONENT];
			$component       = IPSComponent::CreateObjectByParams($componentParams);

			SetValue($switchId, $value);
			IPSLogger_Inf(__file__, 'Turn Light '.$configName.' '.($value?'On':'Off'));

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
			$levelId      = IPS_GetVariableIDByName($configName.IPSHeat_DEVICE_LEVEL, $this->switchCategoryId);
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
			IPSLogger_Inf(__file__, 'Turn Light '.$configName.' '.($switchValue?'On, Level='.GetValue($levelId):'Off'));

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
			$configName   = $this->GetConfigNameById($variableId);
			$configLights = IPSHeat_GetHeatConfiguration();
			$switchId     = IPS_GetVariableIDByName($configName, $this->switchCategoryId);
			$colorId      = IPS_GetVariableIDByName($configName.IPSHEAT_DEVICE_COLOR, $this->switchCategoryId);
			$levelId      = IPS_GetVariableIDByName($configName.IPSHEAT_DEVICE_LEVEL, $this->switchCategoryId);
			$switchValue  = GetValue($switchId);

			$componentParams = $configLights[$configName][IPSHEAT_COMPONENT];
			$component       = IPSComponent::CreateObjectByParams($componentParams);

			SetValue($variableId, $value);
			if (!$switchValue and ($variableId==$levelId or $variableId==$colorId)) {
				SetValue($switchId, true);
			}
			$switchValue  = GetValue($switchId);
			IPSLogger_Inf(__file__, 'Turn Light '.$configName.' '.($switchValue?'On, Level='.GetValue($levelId).', Color='.GetValue($colorId):'Off'));

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
		 * Setzt den Wert einer Thermostat Variable anhand der zugehörigen ID
		 *
		 * @param int $variableId ID der Variable
		 * @param bool $value Neuer Wert der Variable
		 */
		public function SetHeat($variableId, $value, $syncGroups=true, $syncPrograms=true) {
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
			IPSLogger_Inf(__file__, 'Turn Light '.$configName.' '.($switchValue?'On, Level='.GetValue($levelId):'Off'));

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
		 * Setzt den Wert einer Gruppen Variable anhand der zugehörigen ID
		 *
		 * @param int $variableId ID der Gruppe
		 * @param bool $value Neuer Wert der Gruppe
		 */
		public function SetGroup($groupId, $value) {
			$groupConfig = IPSHeat_GetGroupConfiguration();
			$groupName   = IPS_GetName($groupId);
			if ($value and !$groupConfig[$groupName][IPSHEAT_ACTIVATABLE]) {
				IPSLogger_Trc(__file__, "Ignore ".($value?'On':'Off')." forLightGroup '$groupName' (not allowed)");
			} else {
				SetValue($groupId, $value);
				IPSLogger_Inf(__file__, "Turn LightGroup '$groupName' ".($value?'On':'Off'));
				$this->SetAllSwitchesByGroup($groupId);
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
				foreach ($switches as $idx=>$switchName) {
					if ($switchName <> '') {
						$switchId = $this->GetSwitchIdByName($switchName);
						$configLights = IPSHeat_GetLightConfiguration();
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
						$configLights = IPSHeat_GetLightConfiguration();
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

		// ----------------------------------------------------------------------------------------------------------------------------
		private function GetConfigNameById($switchId) {
			$switchName = IPS_GetName($switchId);
			$switchName = str_replace(IPSHEAT_DEVICE_COLOR, '', $switchName);
			$switchName = str_replace(IPSHEAT_DEVICE_LEVEL, '', $switchName);

			return $switchName;
		}

		// ----------------------------------------------------------------------------------------------------------------------------
		private function SynchronizeGroupsBySwitch ($switchId) {
			$switchName  = IPS_GetName($switchId);
			$lightConfig = IPSHeat_GetLightConfiguration();
			$groups      = explode(',', $lightConfig[$switchName][IPSHEAT_GROUPS]);
			foreach ($groups as $groupName) {
				$groupId  = IPS_GetVariableIDByName($groupName, $this->groupCategoryId);
				$this->SynchronizeGroup($groupId);
			}
		}

		// ----------------------------------------------------------------------------------------------------------------------------
		private function SynchronizeGroup ($groupId) {
			$lightConfig = IPSHeat_GetLightConfiguration();
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
		private function SetAllSwitchesByGroup ($groupId) {
			$groupName    = IPS_GetName($groupId);
			$lightConfig  = IPSHeat_GetLightConfiguration();
			$groupState   = GetValue($groupId);
			foreach ($lightConfig as $switchName=>$deviceData) {
				$switchId      = IPS_GetVariableIDByName($switchName, $this->switchCategoryId);
				$switchInGroup = array_key_exists($groupName, array_flip(explode(',', $deviceData[IPSHEAT_GROUPS])));
				if ($switchInGroup and GetValue($switchId)<>$groupState) {
					IPSLogger_Trc(__file__, "Set Light $switchName=".($groupState?'On':'Off')." for Group '$groupName'");
					$this->SetValue($switchId, $groupState);
					$this->SynchronizeGroupsBySwitch ($switchId);
				}
			}
		}

		// ----------------------------------------------------------------------------------------------------------------------------
		public function SynchronizeSwitch($switchName, $deviceState) {
			IPSLogger_Trc(__file__, "Received StateChange from Light '$switchName'=$deviceState");
			$switchId    = IPS_GetVariableIDByName($switchName, $this->switchCategoryId);

			$lightConfig = IPSHeat_GetLightConfiguration();
			$deviceType  = $lightConfig[$switchName][IPSHEAT_TYPE];

			if (IPSHeat_BeforeSynchronizeSwitch($switchId, $deviceState)) {
				if (GetValue($switchId) <> $deviceState) {
					IPSLogger_Inf(__file__, 'Synchronize StateChange from Light '.$switchName.', State='.($deviceState?'On':'Off'));
					SetValue($switchId, $deviceState);
					$this->SynchronizeGroupsBySwitch($switchId);
					$this->SynchronizeProgramsBySwitch($switchId);
				}
			}
			IPSHeat_AfterSynchronizeSwitch($switchId, $deviceState);
		}


		// ----------------------------------------------------------------------------------------------------------------------------
		public function SynchronizeDimmer($switchName, $deviceState, $deviceLevel) {
			IPSLogger_Trc(__file__, 'Received StateChange from Light '.$switchName.', State='.$deviceState.', Level='.$deviceLevel);
			$switchId    = IPS_GetVariableIDByName($switchName, $this->switchCategoryId);
			$levelId     = IPS_GetVariableIDByName($switchName.IPSHEAT_DEVICE_LEVEL, $this->switchCategoryId);

			$lightConfig = IPSHeat_GetLightConfiguration();
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

			$lightConfig = IPSHeat_GetLightConfiguration();
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
			$lightConfig      = IPSHeat_GetLightConfiguration();
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
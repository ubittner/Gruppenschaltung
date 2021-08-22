<?php

/*
 * @author      Ulrich Bittner
 * @copyright   (c) 2021
 * @license     CC BY-NC-SA 4.0
 * @see         https://github.com/ubittner/Gruppenschaltung/tree/main/Gruppenschaltung
 */

declare(strict_types=1);

trait GS_controlGroup
{
    public function ToggleGroup(bool $State): bool
    {
        if ($State) {
            if ($this->CheckMaintenanceMode()) {
                return false;
            }
        }
        $result = true;
        $this->SetValue('GroupSwitch', $State);
        $variables = json_decode($this->ReadPropertyString('Variables'), true);
        $order = array_column($variables, 'Order');
        if (count(array_unique($order)) === 1 && end($order) === 0) {
            //Random mode
            shuffle($variables);
        } else {
            //Sort to order
            array_multisort(array_column($variables, 'Order'), SORT_ASC, $variables);
        }
        foreach ($variables as $variable) {
            if ($variable['Use']) {
                $id = $variable['ID'];
                if ($id != 0 && @IPS_ObjectExists($id)) {
                    $this->WriteAttributeBoolean('DisableUpdateMode', true);
                    IPS_Sleep($variable['SwitchingDelay']);
                    $response = @RequestAction($id, $State);
                    if (!$response) {
                        //Retry
                        IPS_Sleep(self::DELAY_MILLISECONDS);
                        $response = @RequestAction($id, $State);
                        if (!$response) {
                            //Last retry
                            if (!$State) {
                                IPS_Sleep(self::DELAY_MILLISECONDS);
                                $response = @RequestAction($id, $State);
                            }
                        }
                    }
                    if (!$response) {
                        $result = false;
                    }
                }
            }
        }
        $this->WriteAttributeBoolean('DisableUpdateMode', false);
        $this->UpdateGroup();
        return $result;
    }

    public function UpdateGroup(): bool
    {
        if ($this->CheckMaintenanceMode()) {
            return false;
        }
        $result = false;
        $state = false;
        foreach (json_decode($this->ReadPropertyString('Variables'), true) as $variable) {
            if ($variable['Use']) {
                $id = $variable['ID'];
                if ($id != 0 && @IPS_ObjectExists($id)) {
                    $result = true;
                    if (GetValueBoolean($variable['ID'])) {
                        $state = true;
                    }
                }
            }
        }
        $this->SetValue('GroupSwitch', $state);
        return $result;
    }
}
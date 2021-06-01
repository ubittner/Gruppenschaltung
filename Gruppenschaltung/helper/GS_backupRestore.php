<?php

/*
 * @author      Ulrich Bittner
 * @copyright   (c) 2021
 * @license     CC BY-NC-SA 4.0
 * @see         https://github.com/ubittner/Gruppenschaltung/tree/main/Gruppenschaltung
 */

/** @noinspection PhpUnused */

declare(strict_types=1);

trait GS_backupRestore
{
    public function CreateBackup(int $BackupCategory): void
    {
        if (IPS_GetInstance($this->InstanceID)['InstanceStatus'] == 102) {
            $name = 'Konfiguration (' . IPS_GetName($this->InstanceID) . ' #' . $this->InstanceID . ') ' . date('d.m.Y H:i:s');
            $config = [];
            $config['MaintenanceMode'] = $this->ReadPropertyBoolean('MaintenanceMode');
            $config['Variables'] = json_decode($this->ReadPropertyString('Variables'));
            $config['TriggerVariables'] = json_decode($this->ReadPropertyString('TriggerVariables'));
            $json_string = json_encode($config, JSON_HEX_APOS | JSON_PRETTY_PRINT);
            $content = "<?php\n// Backup " . date('d.m.Y, H:i:s') . "\n// ID " . $this->InstanceID . "\n$" . "config = '" . $json_string . "';";
            $backupScript = IPS_CreateScript(0);
            IPS_SetParent($backupScript, $BackupCategory);
            IPS_SetName($backupScript, $name);
            IPS_SetHidden($backupScript, true);
            IPS_SetScriptContent($backupScript, $content);
            echo 'Die Konfiguration wurde erfolgreich gesichert!';
        }
    }

    public function RestoreConfiguration(int $ConfigurationScript): void
    {
        if ($ConfigurationScript != 0 && IPS_ObjectExists($ConfigurationScript)) {
            $object = IPS_GetObject($ConfigurationScript);
            if ($object['ObjectType'] == 3) {
                $content = IPS_GetScriptContent($ConfigurationScript);
                preg_match_all('/\'([^;]+)\'/', $content, $matches);
                $config = json_decode($matches[1][0], true);
                IPS_SetProperty($this->InstanceID, 'MaintenanceMode', $config['MaintenanceMode']);
                IPS_SetProperty($this->InstanceID, 'Variables', json_encode($config['Variables']));
                IPS_SetProperty($this->InstanceID, 'TriggerVariables', json_encode($config['TriggerVariables']));
                if (IPS_HasChanges($this->InstanceID)) {
                    IPS_ApplyChanges($this->InstanceID);
                }
            }
            echo 'Die Konfiguration wurde erfolgreich wiederhergestellt!';
        }
    }
}
<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/ShellyModule.php';

class ShellyProEM extends ShellyModule
{
    public static $Variables = [
        ['State', 'State', VARIABLETYPE_BOOLEAN, '~Switch', [], '', true, true],

        ['aCurrent', 'Phase A Current', VARIABLETYPE_FLOAT, '~Ampere', [], '', false, true],
        ['aVoltage', 'Phase A Voltage', VARIABLETYPE_FLOAT, '~Volt', [], '', false, true],
        ['aActPower', 'Phase A active Power', VARIABLETYPE_FLOAT, '~Watt', [], '', false, true],
        ['aAprtPower', 'Phase A apparent Power', VARIABLETYPE_FLOAT, '~Watt', [], '', false, true],
        ['aPF', 'Phase A Power Factor', VARIABLETYPE_FLOAT, '', [], '', false, true],

        ['bCurrent', 'Phase B Current', VARIABLETYPE_FLOAT, '~Ampere', [], '', false, true],
        ['bVoltage', 'Phase B Voltage', VARIABLETYPE_FLOAT, '~Volt', [], '', false, true],
        ['bActPower', 'Phase B active Power', VARIABLETYPE_FLOAT, '~Watt', [], '', false, true],
        ['bAprtPower', 'Phase B apparent Power', VARIABLETYPE_FLOAT, '~Watt', [], '', false, true],
        ['bPF', 'Phase B Power Factor', VARIABLETYPE_FLOAT, '', [], '', false, true],

        /*emdata
        ['aTotalActEnergy', 'Phase A total active Energy', VARIABLETYPE_FLOAT, '~Electricity', [], '', false, true],
        ['aTotalActRetEnergy', 'Phase A total active returned Energy', VARIABLETYPE_FLOAT, '~Electricity', [], '', false, true],
        ['bTotalActEnergy', 'Phase B total active Energy', VARIABLETYPE_FLOAT, '~Electricity', [], '', false, true],
        ['bTotalActRetEnergy', 'Phase B total active returned Energy', VARIABLETYPE_FLOAT, '~Electricity', [], '', false, true],
        ['cTotalActEnergy', 'Phase C total active Energy', VARIABLETYPE_FLOAT, '~Electricity', [], '', false, true],
        ['cTotalActRetEnergy', 'Phase C total active returned Energy', VARIABLETYPE_FLOAT, '~Electricity', [], '', false, true],
        ['totalActEnergy', 'Total active Energy', VARIABLETYPE_FLOAT, '~Electricity', [], '', false, true],
        ['totalActRetEnergy', 'Total active returned Energy', VARIABLETYPE_FLOAT, '~Electricity', [], '', false, true],
         */
        ['Reachable', 'Reachable', VARIABLETYPE_BOOLEAN, 'Shelly.Reachable', '', '', false, true]
    ];

    public function Create()
    {
        parent::Create();

        /*Netting
        $this->RegisterPropertyFloat('TotalActiveEnergyOffset', 0);
        $this->RegisterPropertyFloat('TotalActRetEnergyOffset', 0);
        $this->RegisterPropertyBoolean('Netting', false);
         */
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        /*Netting
        $this->MaintainVariable('CurrentReturned', $this->Translate('Current Returned'), 2, '~Watt', 0, $this->ReadPropertyBoolean('Netting'));
        $this->MaintainVariable('CurrentImport', $this->Translate('Current Import'), 2, '~Watt', 0, $this->ReadPropertyBoolean('Netting'));
        $this->MaintainVariable('Import', $this->Translate('Import'), 2, '~Electricity', 0, $this->ReadPropertyBoolean('Netting'));
        $this->MaintainVariable('Returned', $this->Translate('Returned'), 2, '~Electricity', 0, $this->ReadPropertyBoolean('Netting'));
         */
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'State':
                $this->SwitchMode(0, $Value);
                break;
            }
    }

    public function ReceiveData($JSONString)
    {
        if (!empty($this->ReadPropertyString('MQTTTopic'))) {
            $Buffer = json_decode($JSONString, true);
            $this->SendDebug('JSON', $Buffer, 0);

            //Für MQTT Fix in IPS Version 6.3
            if (IPS_GetKernelDate() > 1670886000) {
                $Buffer['Payload'] = utf8_decode($Buffer['Payload']);
            }

            $Payload = json_decode($Buffer['Payload'], true);
            if (array_key_exists('Topic', $Buffer)) {
                if (fnmatch('*/online', $Buffer['Topic'])) {
                    $this->SetValue('Reachable', $Payload);
                }

                if (fnmatch('*/status/switch:0', $Buffer['Topic'])) {
                    if (array_key_exists('output', $Payload)) {
                        $this->SetValue('State', $Payload['output']);
                    }
                }

                if (fnmatch('*/status/em1:*', $Buffer['Topic'])) {
                    if (array_key_exists('id', $Payload)) {
                        if ($Payload['id'] == 0) {
                            $this->SetValue('aCurrent', $Payload['current']);
                            $this->SetValue('aVoltage', $Payload['voltage']);
                            $this->SetValue('aActPower', $Payload['act_power']);
                            $this->SetValue('aAprtPower', $Payload['aprt_power']);
                            $this->SetValue('aPF', $Payload['pf']);
                        }
                        if ($Payload['id'] == 1) {
                            $this->SetValue('bCurrent', $Payload['current']);
                            $this->SetValue('bVoltage', $Payload['voltage']);
                            $this->SetValue('bActPower', $Payload['act_power']);
                            $this->SetValue('bAprtPower', $Payload['aprt_power']);
                            $this->SetValue('bPF', $Payload['pf']);
                        }
                        /*Netting
                            if ($this->ReadPropertyBoolean('Netting')) {
                                $this->Netting();
                            }
                            if (array_key_exists('emdata:0', $Payload['params'])) {
                            $emData = $Payload['params']['emdata:0'];
                            $this->SetValue('aTotalActEnergy', floatval($emData['a_total_act_energy']) / 1000);
                            $this->SetValue('aTotalActRetEnergy', floatval($emData['a_total_act_ret_energy']) / 1000);
                            $this->SetValue('bTotalActEnergy', floatval($emData['b_total_act_energy']) / 1000);
                            $this->SetValue('bTotalActRetEnergy', floatval($emData['b_total_act_ret_energy']) / 1000);
                            $this->SetValue('cTotalActEnergy', floatval($emData['c_total_act_energy']) / 1000);
                            $this->SetValue('cTotalActRetEnergy', floatval($emData['c_total_act_ret_energy']) / 1000);

                            $this->SetValue('totalActEnergy', (floatval($emData['total_act']) / 1000) + $this->ReadPropertyFloat('TotalActiveEnergyOffset'));
                            $this->SetValue('totalActRetEnergy', (floatval($emData['total_act_ret']) / 1000) + $this->ReadPropertyFloat('TotalActRetEnergyOffset'));
                        }
                         */
                    }
                }
            }
        }
    }
    /*Netting
        private function Netting()
        {
            $Leistung = $this->GetValue('totalActPower');

            $varZwischenSpericherEinspeisung = IPS_GetVariable($this->GetIDForIdent('CurrentReturned'));
            $varZwischenSpericherBezug = IPS_GetVariable($this->GetIDForIdent('CurrentImport'));

            $ZwischenSpericherEinspeisung = $this->GetValue('CurrentReturned');
            $ZwischenSpericherBezug = $this->GetValue('CurrentImport');

            if ($ZwischenSpericherEinspeisung > 0) {
                $zeit = ($varZwischenSpericherEinspeisung['VariableChanged'] - time()) / 3600;
                $kwh = (abs($ZwischenSpericherEinspeisung) * abs($zeit)) / 1000;
                SetValue($this->GetIDForIdent('Returned'), GetValue($this->GetIDForIdent('Returned')) + $kwh);
            }

            if ($ZwischenSpericherBezug > 0) {
                $zeit = ($varZwischenSpericherBezug['VariableChanged'] - time()) / 3600;
                $kwh = ($ZwischenSpericherBezug * abs($zeit)) / 1000;
                SetValue($this->GetIDForIdent('Import'), GetValue($this->GetIDForIdent('Import')) + $kwh);
            }

            if ($Leistung < 0) {
                SetValue($this->GetIDForIdent('CurrentReturned'), abs($Leistung));
                SetValue($this->GetIDForIdent('CurrentImport'), 0);
            }
            if ($Leistung > 0) {
                SetValue($this->GetIDForIdent('CurrentImport'), abs($Leistung));
                SetValue($this->GetIDForIdent('CurrentReturned'), 0);
            }
        }
     */
    private function SwitchMode(int $switch, bool $value)
    {
        $Topic = $this->ReadPropertyString('MQTTTopic') . '/rpc';

        $Payload['id'] = 1;
        $Payload['src'] = 'user_1';
        $Payload['method'] = 'Switch.Set';
        $Payload['params'] = ['id' => $switch, 'on' => $value];

        $this->sendMQTT($Topic, json_encode($Payload));
    }
}
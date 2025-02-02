<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/ShellyModule.php';

class ShellyTRV extends ShellyModule
{
    public static $Variables = [
        ['Position', 'Position', VARIABLETYPE_INTEGER, '~Intensity.100', [], '', true, true],
        ['TargetTemperature', 'Target Temperature', VARIABLETYPE_FLOAT, '~Temperature.Room', [], '', true, true],
        ['Temperature', 'Temperature', VARIABLETYPE_FLOAT, '~Temperature', [], '', false, true],
        ['ExtTemperature', 'External Temperature', VARIABLETYPE_FLOAT, '~Temperature', [], '', true, true],
        ['Schedule', 'Schedule', VARIABLETYPE_BOOLEAN, '~Switch', '', '', true, true],
        ['ScheduleProfile', 'Schedule Profile', VARIABLETYPE_INTEGER, '', '', '', true, true],
        ['BatteryValue', 'Battery', VARIABLETYPE_INTEGER, '~Battery.100', [], '', false, true],
        ['BatteryVoltage', 'Battery Voltage', VARIABLETYPE_FLOAT, '~Volt', [], '', false, true],
        ['Shelly_Reachable', 'Reachable', VARIABLETYPE_BOOLEAN, 'Shelly.Reachable', [], '', false, true]
    ];

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'TargetTemperature':
                $this->setTargetTemp($Value);
                break;
            case 'Position':
                $this->setValvePosition($Value);
                break;
            case 'ExtTemperature':
                $this->setExtTemp($Value);
                break;
            case 'Schedule':
                $this->setSchedule($Value);
                break;
            case 'ScheduleProfile':
                $this->setScheduleProfile($Value);
                break;
            default:
                $this->SendDebug('ReqestAction :: Invalid Ident', $ident, 0);
                break;
            }
    }

    public function ReceiveData($JSONString)
    {
        if (!empty($this->ReadPropertyString('MQTTTopic'))) {
            $Buffer = json_decode($JSONString);
            $this->SendDebug('JSON', $Buffer, 0);

            //Für MQTT Fix in IPS Version 6.3
            if (IPS_GetKernelDate() > 1670886000) {
                $Buffer->Payload = utf8_decode($Buffer->Payload);
            }

            $Payload = json_decode($Buffer->Payload);
            if (property_exists($Buffer, 'Topic')) {
                if (fnmatch('*info*', $Buffer->Topic)) {
                    if (property_exists($Payload, 'thermostats')) {
                        if (property_exists($Payload->thermostats[0], 'pos')) {
                            $this->SetValue('Position', $Payload->thermostats[0]->pos);
                        }
                        if (property_exists($Payload->thermostats[0], 'target_t')) {
                            $this->SetValue('TargetTemperature', $Payload->thermostats[0]->target_t->value);
                        }
                        if (property_exists($Payload->thermostats[0], 'tmp')) {
                            $this->SetValue('Temperature', $Payload->thermostats[0]->tmp->value);
                        }
                        if (property_exists($Payload->thermostats[0], 'schedule')) {
                            $this->SetValue('Schedule', $Payload->thermostats[0]->schedule);
                        }
                        if (property_exists($Payload->thermostats[0], 'schedule_profile')) {
                            $this->SetValue('ScheduleProfile', $Payload->thermostats[0]->schedule_profile);
                        }
                    }
                    if (property_exists($Payload, 'bat')) {
                        if (property_exists($Payload->bat, 'value')) {
                            $this->SetValue('BatteryValue', $Payload->bat->value);
                        }
                        if (property_exists($Payload->bat, 'voltage')) {
                            $this->SetValue('BatteryVoltage', $Payload->bat->voltage);
                        }
                    }
                }
                if (fnmatch('*online', $Buffer->Topic)) {
                    $this->SetValue('Shelly_Reachable', $Payload);
                }
            }
        }
    }

    private function setTargetTemp(float $Value)
    {
        $Topic = MQTT_GROUP_TOPIC . '/' . $this->ReadPropertyString('MQTTTopic') . '/thermostat/0/command/target_t';
        $Payload = strval(number_format($Value, 1, '.', ''));
        $this->sendMQTT($Topic, $Payload);
    }

    private function setValvePosition(int $Value)
    {
        $Topic = MQTT_GROUP_TOPIC . '/' . $this->ReadPropertyString('MQTTTopic') . '/thermostat/0/command/valve_pos';
        $Payload = strval(intval($Value));
        $this->sendMQTT($Topic, $Payload);
    }

    private function setExtTemp(float $Value)
    {
        $Topic = MQTT_GROUP_TOPIC . '/' . $this->ReadPropertyString('MQTTTopic') . '/thermostat/0/command/ext_t';
        $Payload = strval(number_format($Value, 1, '.', ''));
        $this->sendMQTT($Topic, $Payload);
    }

    private function setSchedule(bool $Value)
    {
        $Topic = MQTT_GROUP_TOPIC . '/' . $this->ReadPropertyString('MQTTTopic') . '/thermostat/0/command/schedule';
        $Payload = strval(intval($Value));
        $this->sendMQTT($Topic, $Payload);
    }

    private function setScheduleProfile(int $Value)
    {
        $Topic = MQTT_GROUP_TOPIC . '/' . $this->ReadPropertyString('MQTTTopic') . '/thermostat/0/command/schedule_profile';
        $Payload = strval($Value);
        $this->sendMQTT($Topic, $Payload);
    }
}
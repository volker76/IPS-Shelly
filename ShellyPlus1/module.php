<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/ShellyModule.php';

class ShellyPlus1 extends ShellyModule
{
    public static $Variables = [
        ['State', 'State', VARIABLETYPE_BOOLEAN, '~Switch', [], '', true, true],

        ['Power', 'Power', VARIABLETYPE_FLOAT, '~Watt.3680', ['shellyplus1pm'], '', false, true],
        ['TotalEnergy', 'Total Energy', VARIABLETYPE_FLOAT, '~Electricity', ['shellyplus1pm'], '', false, true],
        ['Current', 'Current', VARIABLETYPE_FLOAT, '~Ampere', ['shellyplus1pm'], '', false, true],
        ['Voltage', 'Voltage', VARIABLETYPE_FLOAT, '~Volt', ['shellyplus1pm'], '', false, true],
        ['Overtemp', 'Overtemp', VARIABLETYPE_BOOLEAN, '~Alert', [], '', false, true],
        ['Overpower', 'Overpower', VARIABLETYPE_BOOLEAN, '~Alert', [], '', false, true],
        ['Overvoltage', 'Overvoltage', VARIABLETYPE_BOOLEAN, '~Alert', [], '', false, true],
        ['DeviceTemperature', 'Device Temperature', VARIABLETYPE_FLOAT, '~Temperature', [], '', false, true],
        ['EventComponent', 'Event Component', VARIABLETYPE_STRING, '', [], '', false, true],
        ['Event', 'Event', VARIABLETYPE_STRING, '', [], '', false, true],
        ['Reachable', 'Reachable', VARIABLETYPE_BOOLEAN, 'Shelly.Reachable', '', '', false, true],
        ['Temperature100', 'External Temperature 1', VARIABLETYPE_FLOAT, '~Temperature', [], '', false, true],
        ['Temperature101', 'External Temperature 2', VARIABLETYPE_FLOAT, '~Temperature', [], '', false, true],
        ['Temperature102', 'External Temperature 3', VARIABLETYPE_FLOAT, '~Temperature', [], '', false, true],
        ['Temperature103', 'External Temperature 4', VARIABLETYPE_FLOAT, '~Temperature', [], '', false, true],
        ['Temperature104', 'External Temperature 5', VARIABLETYPE_FLOAT, '~Temperature', [], '', false, true],
        ['Humidity100', 'External Humidity', VARIABLETYPE_FLOAT, '~Humidity.F', [], '', false, true],
        ['Input100State', 'External Input State', VARIABLETYPE_BOOLEAN, '~Switch', [], '', false, true],
        ['Input100Percent', 'External Input Percent', VARIABLETYPE_FLOAT, 'Shelly.Input.Percent', [], '', false, true],
        ['Voltmeter100', 'External Voltmeter', VARIABLETYPE_FLOAT, '~Volt', [], '', false, true],
    ];

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
        $this->SendDebug('JSON', $JSONString, 0);
        if (!empty($this->ReadPropertyString('MQTTTopic'))) {
            $Buffer = json_decode($JSONString, true);

            //Für MQTT Fix in IPS Version 6.3
            if (IPS_GetKernelDate() > 1670886000) {
                $Buffer['Payload'] = utf8_decode($Buffer['Payload']);
            }

            $this->SendDebug('MQTT Topic', $Buffer['Topic'], 0);

            $Payload = json_decode($Buffer['Payload'], true);
            if (array_key_exists('Topic', $Buffer)) {
                if (fnmatch('*/online', $Buffer['Topic'])) {
                    $this->SetValue('Reachable', $Payload);
                }
                if (fnmatch('*/events/rpc', $Buffer['Topic'])) {
                    if (array_key_exists('params', $Payload)) {
                        if (array_key_exists('events', $Payload['params'])) {
                            $events = $Payload['params']['events'][0];
                            $this->SetValue('EventComponent', $events['component']);
                            $this->SetValue('Event', $events['event']);
                        }
                        if (array_key_exists('switch:0', $Payload['params'])) {
                            $switch = $Payload['params']['switch:0'];
                            if (array_key_exists('output', $switch)) {
                                $this->SetValue('State', $switch['output']);
                            }
                            if (array_key_exists('apower', $switch)) {
                                $this->SetValue('Power', $switch['apower']);
                            }
                            if (array_key_exists('voltage', $switch)) {
                                $this->SetValue('Voltage', $switch['voltage']);
                            }
                            if (array_key_exists('current', $switch)) {
                                $this->SetValue('Current', $switch['current']);
                            }
                            if (array_key_exists('aenergy', $switch)) {
                                if (array_key_exists('total', $switch['aenergy'])) {
                                    $this->SetValue('TotalEnergy', $switch['aenergy']['total'] / 1000);
                                }
                            }
                            if (array_key_exists('errors', $switch)) {
                                $this->SetValue('Overtemp', false);
                                $this->SetValue('Overpower', false);
                                $this->SetValue('Overvoltage', false);
                                $errors = '';
                                foreach ($switch['errors'] as $key => $error) {
                                    switch ($error) {
                                            case 'overtemp':
                                                $this->SetValue('Overtemp', true);
                                                break;
                                            case 'overpower':
                                                $this->SetValue('Overpower', true);
                                                break;
                                            case 'overvoltage':
                                                $this->SetValue('Overvoltage', true);
                                                break;
                                            default:
                                                $this->LogMessage('Missing Variable for Error State "' . $error . '"', KL_ERROR);
                                                break;
                                        }
                                }
                            }
                        }
                        //External Sensor Addon
                        if (array_key_exists('temperature:100', $Payload['params'])) {
                            for ($i = 100; $i <= 104; $i++) {
                                $temperatureIndex = 'temperature:' . $i;
                                if (array_key_exists($temperatureIndex, $Payload['params'])) {
                                    $temperature = $Payload['params'][$temperatureIndex];
                                    if (array_key_exists('tC', $temperature)) {
                                        $this->SetValue('Temperature' . $i, $temperature['tC']);
                                    }
                                }
                            }
                        }
                        //External Sensor Addon
                        if (array_key_exists('humidity:100', $Payload['params'])) {
                            $humidity = $Payload['params']['humidity:100'];
                            if (array_key_exists('rH', $humidity)) {
                                $this->SetValue('Humidity100', $humidity['rH']);
                            }
                        }
                        //External Sensor Addon
                        if (array_key_exists('input:100', $Payload['params'])) {
                            $input = $Payload['params']['input:100'];
                            if (array_key_exists('state', $input)) {
                                $this->SetValue('Input100State', $input['state']);
                            }
                            if (array_key_exists('percent', $input)) {
                                $this->SetValue('Input100Percent', $input['percent']);
                            }
                        }
                        //External Sensor Addon
                        if (array_key_exists('voltmeter:100', $Payload['params'])) {
                            $voltmeter = $Payload['params']['voltmeter:100'];
                            if (array_key_exists('voltage', $voltmeter)) {
                                $this->SetValue('Voltmeter100', $voltmeter['voltage']);
                            }
                        }
                    }
                }
                if (fnmatch('*/status/switch:0', $Buffer['Topic'])) {
                    if (array_key_exists('output', $Payload)) {
                        $this->SetValue('State', $Payload['output']);
                    }
                    if (array_key_exists('apower', $Payload)) {
                        $this->SetValue('Power', $Payload['apower']);
                    }
                    if (array_key_exists('voltage', $Payload)) {
                        $this->SetValue('Voltage', $Payload['voltage']);
                    }
                    if (array_key_exists('current', $Payload)) {
                        $this->SetValue('Current', $Payload['current']);
                    }
                    if (array_key_exists('aenergy', $Payload)) {
                        if (array_key_exists('total', $Payload['aenergy'])) {
                            $this->SetValue('TotalEnergy', $Payload['aenergy']['total'] / 1000);
                        }
                    }
                }
                //Temperatur ist immer vorhanden und sollte immer der selbe Wert sein.
                if (fnmatch('*/status/*', $Buffer['Topic'])) {
                    if (array_key_exists('temperature', $Payload)) {
                        if (array_key_exists('tC', $Payload['temperature'])) {
                            $this->SetValue('DeviceTemperature', $Payload['temperature']['tC']);
                        }
                    }
                }
            }
        }
    }

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

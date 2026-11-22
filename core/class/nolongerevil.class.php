<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

require_once __DIR__  . '/../../../../core/php/core.inc.php';

class nolongerevil extends eqLogic {
  public static $_widgetPossibility = array('custom' => true, 'custom::layout' => false);

  public static function cron() {
    foreach (self::byType('nolongerevil', true) as $eqLogic) {
      try {
        $autorefresh = $eqLogic->getConfiguration('autorefresh', '');
        if ($autorefresh == '') {
          continue;
        }
        if (!self::isCronDue($autorefresh)) {
          continue;
        }
        $eqLogic->refreshInfo();
      } catch (Exception $e) {
        log::add('nolongerevil', 'error', __('Erreur cron pour', __FILE__) . ' ' . $eqLogic->getHumanName() . ' : ' . $e->getMessage());
      }
    }
  }

  public static function isCronDue($cronExpression) {
    try {
      $c = new Cron\CronExpression(checkAndFixCron($cronExpression), new Cron\FieldFactory);
      return $c->isDue();
    } catch (Exception $e) {
      return false;
    }
  }

  public function preInsert() {
    if ($this->getCategory('heating', 1) == 0) {
      $this->setCategory('heating', 1);
    }
  }

  public function preSave() {
    $deviceId = trim($this->getConfiguration('device_id', ''));
    if ($deviceId == '') {
      throw new Exception(__('L\'ID du device No Longer Evil est obligatoire', __FILE__));
    }
    $apiKey = trim(config::byKey('api_key', 'nolongerevil', ''));
    if ($apiKey == '') {
      throw new Exception(__('La clé API No Longer Evil doit être configurée dans la configuration du plugin', __FILE__));
    }
    if ($this->getConfiguration('autorefresh', '') === '') {
      $default = config::byKey('defaultAutorefresh', 'nolongerevil', '*/5 * * * *');
      $this->setConfiguration('autorefresh', $default);
    }
  }

  public function postSave() {
    $this->createOrUpdateCmd('refresh', 'action', 'other', __('Rafraîchir', __FILE__), 1, '1', '0', array(), 'fas fa-sync');
    $this->createOrUpdateCmd('online', 'info', 'binary', __('En ligne', __FILE__), 0, '1', '1', array());
    $this->createOrUpdateCmd('current_temperature', 'info', 'numeric', __('Température', __FILE__), 1, '1', '1', array('unit' => '°C', 'generic_type' => 'TEMPERATURE'));
    $this->createOrUpdateCmd('target_temperature', 'info', 'numeric', __('Consigne', __FILE__), 1, '1', '1', array('unit' => '°C', 'generic_type' => 'THERMOSTAT_SETPOINT'));
    $this->createOrUpdateCmd('set_temperature', 'action', 'slider', __('Régler consigne', __FILE__), 1, '1', '0', array('minValue' => 9, 'maxValue' => 32, 'unit' => '°C', 'generic_type' => 'THERMOSTAT_SET_SETPOINT'));
    $this->createOrUpdateCmd('humidity', 'info', 'numeric', __('Humidité', __FILE__), 0, '1', '1', array('unit' => '%', 'generic_type' => 'HUMIDITY'));
    $this->createOrUpdateCmd('hvac_mode', 'info', 'string', __('Mode HVAC', __FILE__), 1, '1', '1', array());
    $this->createOrUpdateCmd('set_mode', 'action', 'select', __('Changer mode', __FILE__), 1, '1', '0', array('listValue' => 'off|Off;heat|Chauffage;cool|Climatisation;heat-cool|Auto'));
    $this->createOrUpdateCmd('away_state', 'info', 'binary', __('Absence', __FILE__), 0, '1', '1', array());
    $this->createOrUpdateCmd('away_on', 'action', 'other', __('Activer absence', __FILE__), 0, '1', '0', array(), 'fas fa-sign-out-alt');
    $this->createOrUpdateCmd('away_off', 'action', 'other', __('Désactiver absence', __FILE__), 0, '1', '0', array(), 'fas fa-home');
    $this->createOrUpdateCmd('heating_state', 'info', 'binary', __('Chauffage actif', __FILE__), 0, '1', '1', array());
    $this->createOrUpdateCmd('cooling_state', 'info', 'binary', __('Climatisation active', __FILE__), 0, '1', '1', array());

    $this->linkCmds();
    $refresh = $this->getCmd(null, 'refresh');
    if (is_object($refresh)) {
      try {
        $refresh->execCmd();
      } catch (Exception $e) {
        log::add('nolongerevil', 'debug', 'Refresh initial impossible: ' . $e->getMessage());
      }
    }
  }

  private function createOrUpdateCmd($logicalId, $type, $subType, $name, $isVisible, $isEnabledOnSave, $isHistorized, $configuration = array(), $icon = '') {
    $cmd = $this->getCmd(null, $logicalId);
    if (!is_object($cmd)) {
      $cmd = new nolongerevilCmd();
      $cmd->setLogicalId($logicalId);
      $cmd->setEqLogic_id($this->getId());
      $cmd->setName($name);
      $cmd->setIsVisible($isVisible);
      $cmd->setIsHistorized($isHistorized);
    }
    $cmd->setType($type);
    $cmd->setSubType($subType);
    if ($icon != '') {
      $cmd->setDisplay('icon', '<i class="' . $icon . '"></i>');
    }
    foreach ($configuration as $key => $value) {
      if ($key === 'value' && strpos($value, '#') !== false) {
        continue;
      }
      $cmd->setConfiguration($key, $value);
    }
    if (isset($configuration['unit'])) {
      $cmd->setUnite($configuration['unit']);
    }
    $cmd->save();
    return $cmd;
  }

  private function linkCmds() {
    $target = $this->getCmd(null, 'target_temperature');
    $setTemp = $this->getCmd(null, 'set_temperature');
    if (is_object($target) && is_object($setTemp)) {
      $setTemp->setValue($target->getId());
      $setTemp->save();
    }
    $mode = $this->getCmd(null, 'hvac_mode');
    $setMode = $this->getCmd(null, 'set_mode');
    if (is_object($mode) && is_object($setMode)) {
      $setMode->setValue($mode->getId());
      $setMode->save();
    }
  }

  public function getApiUrl() {
    $url = trim(config::byKey('api_url', 'nolongerevil', 'https://nolongerevil.com/api/v1'));
    if ($url == '') {
      $url = 'https://nolongerevil.com/api/v1';
    }
    return rtrim($url, '/');
  }

  public function getApiKey() {
    $key = trim(config::byKey('api_key', 'nolongerevil', ''));
    if ($key == '') {
      throw new Exception(__('Clé API No Longer Evil non configurée', __FILE__));
    }
    return $key;
  }

  public function callNleApi($path, $method = 'GET', $data = null, $timeout = 15) {
    $url = $this->getApiUrl() . $path;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    $headers = array(
      'Accept: application/json',
      'Authorization: Bearer ' . $this->getApiKey()
    );
    if ($data !== null) {
      $payload = json_encode($data);
      $headers[] = 'Content-Type: application/json';
      curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
      throw new Exception(__('Erreur cURL', __FILE__) . ' : ' . $curlErr);
    }
    $decoded = json_decode($raw, true);
    if ($httpCode >= 400) {
      $msg = $raw;
      if (is_array($decoded)) {
        if (isset($decoded['error'])) $msg = $decoded['error'];
        elseif (isset($decoded['message'])) $msg = $decoded['message'];
        else $msg = json_encode($decoded);
      }
      throw new Exception(__('Erreur API NLE HTTP', __FILE__) . ' ' . $httpCode . ' : ' . $msg);
    }
    if (is_array($decoded)) {
      return $decoded;
    }
    return $raw;
  }

  public function testConnection() {
    try {
      $result = $this->callNleApi('/devices');
      $this->checkAndUpdateCmd('online', 1);
      return array('success' => true, 'message' => __('Connexion OK', __FILE__), 'result' => $result);
    } catch (Exception $e) {
      $this->checkAndUpdateCmd('online', 0);
      return array('success' => false, 'message' => $e->getMessage());
    }
  }

  public function refreshInfo() {
    $deviceId = trim($this->getConfiguration('device_id', ''));
    if ($deviceId == '') {
      $this->checkAndUpdateCmd('online', 0);
      return;
    }
    try {
      $status = $this->callNleApi('/thermostat/' . rawurlencode($deviceId) . '/status');
      $this->checkAndUpdateCmd('online', 1);
      $serial = '';
      if (isset($status['device']['serial'])) {
        $serial = $status['device']['serial'];
        $this->setConfiguration('serial', $serial);
        $this->save(true);
      } else {
        $serial = $this->getConfiguration('serial', '');
      }

      $shared = array();
      $device = array();
      if ($serial != '' && isset($status['state']['shared.' . $serial]['value'])) {
        $shared = $status['state']['shared.' . $serial]['value'];
      } else {
        foreach (($status['state'] ?? array()) as $k => $v) {
          if (strpos($k, 'shared.') === 0 && isset($v['value'])) { $shared = $v['value']; break; }
        }
      }
      if ($serial != '' && isset($status['state']['device.' . $serial]['value'])) {
        $device = $status['state']['device.' . $serial]['value'];
      } else {
        foreach (($status['state'] ?? array()) as $k => $v) {
          if (strpos($k, 'device.') === 0 && isset($v['value'])) { $device = $v['value']; break; }
        }
      }

      if (isset($shared['current_temperature']) && is_numeric($shared['current_temperature'])) {
        $this->checkAndUpdateCmd('current_temperature', round(floatval($shared['current_temperature']), 2));
      }
      if (isset($shared['target_temperature']) && is_numeric($shared['target_temperature'])) {
        $this->checkAndUpdateCmd('target_temperature', round(floatval($shared['target_temperature']), 2));
      }
      if (isset($shared['target_temperature_type'])) {
        $this->checkAndUpdateCmd('hvac_mode', strval($shared['target_temperature_type']));
      }
      if (isset($shared['hvac_heater_state'])) {
        $this->checkAndUpdateCmd('heating_state', $shared['hvac_heater_state'] ? 1 : 0);
      }
      if (isset($shared['hvac_ac_state'])) {
        $this->checkAndUpdateCmd('cooling_state', $shared['hvac_ac_state'] ? 1 : 0);
      }
      if (isset($shared['auto_away'])) {
        $this->checkAndUpdateCmd('away_state', intval($shared['auto_away']) > 0 ? 1 : 0);
      }
      if (isset($device['current_humidity']) && is_numeric($device['current_humidity'])) {
        $this->checkAndUpdateCmd('humidity', round(floatval($device['current_humidity']), 1));
      }
    } catch (Exception $e) {
      log::add('nolongerevil', 'error', $this->getHumanName() . ' refresh: ' . $e->getMessage());
      $this->checkAndUpdateCmd('online', 0);
    }
  }
}

class nolongerevilCmd extends cmd {
  public function execute($_options = array()) {
    $eqLogic = $this->getEqLogic();
    if (!is_object($eqLogic)) {
      throw new Exception(__('Equipement introuvable', __FILE__));
    }
    $logicalId = $this->getLogicalId();
    if ($this->getType() == 'info') {
      return $this->getConfiguration('value', $this->execCmd());
    }
    $deviceId = trim($eqLogic->getConfiguration('device_id', ''));
    if ($deviceId == '') {
      throw new Exception(__('Device ID non configuré', __FILE__));
    }

    switch ($logicalId) {
      case 'refresh':
        $eqLogic->refreshInfo();
        return true;
      case 'set_temperature':
        $value = isset($_options['slider']) ? floatval($_options['slider']) : null;
        if ($value === null) {
          throw new Exception(__('Température manquante', __FILE__));
        }
        $modeCmd = $eqLogic->getCmd(null, 'hvac_mode');
        $mode = 'heat';
        if (is_object($modeCmd)) {
          $current = $modeCmd->execCmd();
          if (in_array($current, array('heat', 'cool'))) $mode = $current;
          elseif ($current == 'heat-cool') $mode = 'heat';
        }
        $eqLogic->callNleApi('/thermostat/' . rawurlencode($deviceId) . '/temperature', 'POST', array('value' => $value, 'mode' => $mode, 'scale' => 'C'));
        $eqLogic->checkAndUpdateCmd('target_temperature', $value);
        return true;
      case 'set_mode':
        $mode = isset($_options['select']) ? strval($_options['select']) : '';
        if (!in_array($mode, array('off', 'heat', 'cool', 'heat-cool'), true)) {
          throw new Exception(__('Mode invalide', __FILE__) . ' : ' . $mode);
        }
        $eqLogic->callNleApi('/thermostat/' . rawurlencode($deviceId) . '/mode', 'POST', array('mode' => $mode));
        $eqLogic->checkAndUpdateCmd('hvac_mode', $mode);
        return true;
      case 'away_on':
        $eqLogic->callNleApi('/thermostat/' . rawurlencode($deviceId) . '/away', 'POST', array('away' => true));
        $eqLogic->checkAndUpdateCmd('away_state', 1);
        return true;
      case 'away_off':
        $eqLogic->callNleApi('/thermostat/' . rawurlencode($deviceId) . '/away', 'POST', array('away' => false));
        $eqLogic->checkAndUpdateCmd('away_state', 0);
        return true;
    }

    throw new Exception(__('Commande non implémentée', __FILE__) . ' : ' . $logicalId);
  }
}

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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Clé API NLE}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Clé API No Longer Evil (format nle_...). À créer dans les paramètres de votre compte No Longer Evil avec les scopes read+write.}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input type="password" class="configKey form-control" data-l1key="api_key" placeholder="nle_..." autocomplete="off"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{URL API}}
        <sup><i class="fas fa-question-circle tooltips" title="{{URL de base de l'API cloud No Longer Evil.}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="api_url" placeholder="https://nolongerevil.com/api/v1"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Rafraîchissement par défaut (cron)}}</label>
      <div class="col-md-4">
        <div class="input-group">
          <input class="configKey form-control roundedLeft" data-l1key="defaultAutorefresh" placeholder="*/5 * * * *"/>
          <span class="input-group-btn">
            <a class="btn btn-default cursor jeeHelper roundedRight" data-helper="cron" title="Assistant cron">
              <i class="fas fa-question-circle"></i>
            </a>
          </span>
        </div>
      </div>
    </div>
  </fieldset>
</form>

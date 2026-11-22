# Plugin Jeedom - No Longer Evil (Nest)

Plugin Jeedom pour piloter un thermostat **Nest** débloqué via le projet [No Longer Evil](https://nolongerevil.com/) en utilisant l'**API cloud NLE**.

## Fonctionnalités

- Température actuelle / consigne
- Changement de consigne
- Mode HVAC : off / heat / cool / heat-cool
- Mode absence (away) on/off
- Humidité, états chauffage/climatisation
- Rafraîchissement périodique configurable (cron)

## Prérequis

- Jeedom 4.4+
- Thermostat Nest Gen 1/2 débloqué avec No Longer Evil
- Clé API NLE (`nle_...`) avec scopes `read` + `write`

## Configuration

1. Installer et activer le plugin.
2. Dans la configuration du plugin, renseigner la **clé API NLE**.
3. Créer un équipement, renseigner le **Device ID** NLE (UUID visible via `GET /api/v1/devices`).
4. Sauvegarder, tester la connexion.

## Licence

AGPL

# Plugin No Longer Evil (Nest)

Pilote un thermostat Nest débloqué via [No Longer Evil](https://nolongerevil.com/) (API cloud v1).

## Configuration plugin

- **Clé API NLE** : clé `nle_...` avec scopes `read` + `write`.
- **URL API** : `https://nolongerevil.com/api/v1` par défaut.
- **Cron par défaut** : `*/5 * * * *`.

## Configuration équipement

- **Device ID** : UUID du thermostat (depuis l'API NLE `/v1/devices`).
- **Serial** : renseigné automatiquement après premier refresh.
- **Auto-actualisation** : cron.

## Commandes

| Commande | Type | Description |
|---|---|---|
| Rafraîchir | action | Met à jour les états |
| En ligne | info binaire | Dernier appel API OK |
| Température | info numérique | Température actuelle (°C) |
| Consigne | info numérique | Consigne cible (°C) |
| Régler consigne | action slider | Change la consigne |
| Humidité | info numérique | Humidité (%) |
| Mode HVAC | info string | `off`, `heat`, `cool`, `heat-cool` |
| Changer mode | action select | Change le mode HVAC |
| Absence | info binaire | État absence/away |
| Activer/Désactiver absence | action | Change le mode absence |
| Chauffage actif | info binaire | Chauffage en cours |
| Climatisation active | info binaire | Climatisation en cours |

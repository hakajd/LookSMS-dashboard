# CLAUDE.md — LookSMS Android App

## Contexte du projet

LookSMS est un SaaS de SMS gateway. Un téléphone Android physique avec une SIM envoie des SMS à la demande d'un backend. Les clients sont des agences GoHighLevel et entrepreneurs qui veulent envoyer des SMS professionnels sans passer par Twilio.

**Aucune référence à rbsoft, aucun code réutilisé. Projet 100% original.**

---

## Package & identité

- Package name : `com.smsrelay.app`
- App name : LookSMS
- Min SDK : Android 8.0 (API 26)
- Target SDK : Android 14 (API 34)
- Langage : Kotlin natif

---

## Objectif Phase 1 — App Android

Construire une app Android qui :
1. Tourne en permanence sans être tuée par Android
2. Se connecte au backend via MQTT (protocole léger type WhatsApp)
3. Reçoit des commandes d'envoi SMS depuis le backend
4. Envoie les SMS via SmsManager Android natif
5. Renvoie un delivery report au backend (envoyé / délivré / échec)
6. Affiche l'historique des SMS envoyés
7. Permet d'envoyer des messages types en 1 tap
8. Planifie des séquences SMS avec date et heure
9. Redémarre automatiquement au reboot du téléphone
10. Fonctionne sur Samsung, Xiaomi, Huawei, OnePlus, tous constructeurs

---

## Architecture MQTT (remplacement Firebase)

- Broker : Mosquitto sur VPS (Docker)
- Lib Android : Paho MQTT
- Topic commande : `smsrelay/{user_id}/{device_id}/cmd`
- Topic statut : `smsrelay/{user_id}/{device_id}/status`
- Broker URL : `tcp://sms.lnkf.net:1883`

---

## Anti-kill Android — 5 couches

1. Foreground Service (START_STICKY)
2. WakeLock partiel
3. WorkManager surveillance toutes les 15 min
4. AlarmManager toutes les 5 min
5. Boot Receiver (BOOT_COMPLETED + QUICKBOOT_POWERON)

---

## Modèle de données PostgreSQL (schéma looksms)

```sql
CREATE TABLE users (id UUID PRIMARY KEY, email TEXT UNIQUE, password_hash TEXT, plan TEXT DEFAULT 'free', created_at TIMESTAMPTZ DEFAULT NOW());
CREATE TABLE devices (id UUID PRIMARY KEY, user_id UUID REFERENCES users(id), name TEXT, mqtt_token TEXT UNIQUE, status TEXT DEFAULT 'offline', last_seen TIMESTAMPTZ, android_version TEXT, manufacturer TEXT, created_at TIMESTAMPTZ DEFAULT NOW());
CREATE TABLE sms_logs (id UUID PRIMARY KEY, user_id UUID REFERENCES users(id), device_id UUID REFERENCES devices(id), recipient TEXT, message TEXT, status TEXT DEFAULT 'pending', sent_at TIMESTAMPTZ, delivered_at TIMESTAMPTZ, error_message TEXT, created_at TIMESTAMPTZ DEFAULT NOW());
CREATE TABLE templates (id UUID PRIMARY KEY, user_id UUID REFERENCES users(id), name TEXT, body TEXT, fields JSONB DEFAULT '[]', created_at TIMESTAMPTZ DEFAULT NOW());
CREATE TABLE sequences (id UUID PRIMARY KEY, user_id UUID REFERENCES users(id), name TEXT, steps JSONB, scheduled_at TIMESTAMPTZ, status TEXT DEFAULT 'draft', created_at TIMESTAMPTZ DEFAULT NOW());
```

---

## Format commande MQTT

```json
{"id": "uuid", "action": "send_sms", "to": "+32470123456", "message": "Bonjour {prenom}", "fields": {"prenom": "Julien"}, "priority": 1}
```

## Format statut retour

```json
{"id": "uuid", "status": "delivered", "timestamp": "2026-05-14T10:30:00Z", "device_id": "uuid"}
```

---

## Infrastructure VPS

- Hostinger : 195.35.0.202
- Docker + Traefik (resolver: mytlschallenge)
- PostgreSQL : container `linkif-postgres`, user `nocodb_user`, DB `nocodb`
- Schéma : `looksms`
- Domaine : `app.looksms.com`
- Broker MQTT : port 1883

---

## Frontend dashboard

- Stack : Tailwind CSS + Vanilla JS (zéro jQuery, zéro Bootstrap)
- Vues : devices, historique SMS, templates, séquences, paramètres compte

---

## Chantiers par priorité

### Priorité 1 — Nettoyage RBSoft (EN COURS)
- Supprimer les 471 références RBSoft dans le PHP existant
- Renommer "SMS Gateway" → "LookSMS" partout
- Migrer config.php hardcodé → .env propre

### Priorité 2 — Refonte frontend
- Migrer Bootstrap 3 + jQuery → Tailwind CSS + Vanilla JS
- Garder toutes les fonctionnalités existantes (gestion clients, paiements, plans, etc.)

### Priorité 3 — Backend PHP modernisé
- Remplacer MysqliDb v2.9.3 → PDO PHP8 natif

### Phase future — Nouvelle architecture
- App Android Kotlin + MQTT (remplace app RBSoft + Firebase)
- Backend Node.js (en complément ou remplacement du PHP)

---

## Règles absolues

1. Aucune référence RBSoft dans le code final
2. Credentials dans .env, jamais hardcodés
3. Play Store compliant : jamais READ_SMS ou RECEIVE_SMS
4. Package : `com.smsrelay.app`

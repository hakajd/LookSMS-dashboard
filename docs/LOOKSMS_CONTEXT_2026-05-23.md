# LookSMS — Context & État du projet
**Date :** 2026-05-23  
**Rédigé par :** Session Claude Code

---

## 1. Versions actuelles

### App Android (`hakajd/LookSMS`)
| Champ | Valeur |
|---|---|
| Version | **1.5.0** |
| versionCode | **19** |
| Package | `com.looksms.app` |
| Min SDK | Android 8.0 (API 26) |
| compileSdk / targetSdk | Android 15 (API 35) |
| Dernier AAB buildé | `LookSMS-v1.4.6.aab` ← **1.5.0 pas encore buildée** |
| Dernier commit | `edb9d15` — 2026-05-21 |

### Dashboard PHP (`hakajd/LookSMS-dashboard`)
| Champ | Valeur |
|---|---|
| URL prod | `https://sms.lnkf.net` |
| URL publique | `https://app.looksms.com` |
| Dernier commit | `aca91d7` — 2026-05-21 |
| Stack | PHP + MySQL 8.0 + Docker + Traefik |

---

## 2. Architecture technique retenue (actée, immuable)

```
GHL / n8n
  → POST /api/commands/pending (Backend PHP)
  → FCM push {"type":"NEW_COMMAND"}
  → LookSmsFcmService (Android)
  → CommandDispatcher
  → GET /api/commands/pending
  → SmsSendWorker (WorkManager foreground court)
  → SmsManager Android natif
  → SMS envoyé ✓
  → Callback statut : POST /api/status (REST HTTPS)
  → Dashboard mis à jour

+ AlarmManager heartbeat 15 min (fallback si FCM mort)
```

**Principes :**
- Pas de Foreground Service permanent
- FCM = réveil à la demande uniquement
- REST HTTPS pour tout (plus de MQTT)
- WorkManager `ExistingWorkPolicy.KEEP` contre les doublons

---

## 3. Infrastructure VPS — Hostinger 195.35.0.202

| Container | Rôle |
|---|---|
| `looksms-app` | Dashboard PHP (port 80 → Traefik) |
| `looksms-db` | MySQL 8.0 — base `sms_gateway` |
| `looksms-mqtt` | Mosquitto (inutilisé, à supprimer) |

**SDK Android sur VPS :**
- SDK path : `/home/claude/android-sdk`
- SDK installés : `android-34`, `android-35`
- Build-tools : `34.0.0`
- RAM dispo build : ~2.2 GB

**Gradle — config OOM-safe (`gradle.properties`) :**
```properties
org.gradle.jvmargs=-Xmx1536m -XX:MaxMetaspaceSize=384m
kotlin.daemon.jvm.options=-Xmx512m
```
⚠️ Ne jamais remonter `Xmx2g` — crash OOM sur R8/minification garanti.

---

## 4. Fichiers modifiés récemment (depuis v1.4.3)

### Android — v1.4.4 (fix critique SMS bloqués Queued)
| Fichier | Modification |
|---|---|
| `service/SmsSender.kt` | Check permission `SEND_SMS` avant envoi, logs `📤`, TAG ajouté |
| `worker/SmsSendWorker.kt` | Foreground failure → `updateFailed()`, check `sendingPaused` |
| `receiver/SmsDeliveryReceiver.kt` | Logs `📨` callback SENT/DELIVERED, TAG ajouté |

### Dashboard — fix race condition (2026-05-21)
| Fichier | Modification |
|---|---|
| `src/services/get-pending-messages.php` | UPDATE Pending→Queued d'abord, puis SELECT — élimine race condition entre heartbeats simultanés |
| `src/download-app.php` | Nettoyage URL APK/AAB |

### Android — v1.5.0 (refonte UI + logs, 2026-05-21)
| Fichier | Modification |
|---|---|
| `app/build.gradle.kts` | Bump 1.5.0 / versionCode 19 |
| `data/AppDatabase.kt` | Version 5, ajout table `system_logs` |
| `data/SystemLogDao.kt` | **nouveau** — DAO logs système |
| `data/models/SystemLog.kt` | **nouveau** — entity Room (INFO/WARN/ERROR) |
| `receiver/AlarmReceiver.kt` | Injection LogHelper |
| `service/LookSmsFcmService.kt` | Injection LogHelper |
| `ui/HistoryActivity.kt` | 2 onglets SMS / Système, couleurs statuts, compteur temps réel |
| `ui/HomeActivity.kt` | Compteurs SMS aujourd'hui / semaine / échecs |
| `utils/LogHelper.kt` | **nouveau** — écriture logs depuis n'importe quel contexte |
| `worker/SmsSendWorker.kt` | Injection LogHelper |
| `res/drawable/badge_bg.xml` | **nouveau** |
| `res/layout/activity_history.xml` | Refonte 2 onglets |
| `res/layout/activity_home.xml` | Refonte complète avec stats |
| `res/layout/item_sms_log.xml` | Item enrichi avec statut coloré |
| `res/layout/item_system_log.xml` | **nouveau** — item log système |

### Android — Refonte UI globale (2026-05-21, intégrée dans v1.5.0)
| Fichier | Modification |
|---|---|
| `ui/BaseBottomNavActivity.kt` | **nouveau** — abstract, gestion bottom nav 4 onglets |
| `ui/ContactsActivity.kt` | **nouvelle** — contacts depuis Room DB |
| `ui/TemplatesActivity.kt` | Extend BaseBottomNavActivity |
| `res/values/colors.xml` | Palette complète redessinée |
| `res/values/themes.xml` | NoActionBar + Setup theme |
| `res/menu/bottom_nav_menu.xml` | **nouveau** — 4 onglets |
| `res/color/nav_icon_color.xml` | **nouveau** — selector bleu/gris nav |
| `res/drawable/bg_status_*.xml` | **nouveaux** — badges statut (online/offline/paused) |
| `res/drawable/bg_icon_*.xml` | **nouveaux** — carrés arrondis colorés |
| `res/drawable/bg_card.xml` | **nouveau** |
| `res/layout/activity_contacts.xml` | **nouveau** |
| `res/layout/item_contact.xml` | **nouveau** |
| `res/layout/item_template_home.xml` | **nouveau** |

---

## 5. Ce qui fonctionne ✅

### Android
- Architecture FCM + REST hybride complète
- Envoi SMS natif (`SmsManager`) avec check permission `SEND_SMS`
- Dual SIM (sélection via `TokenManager.selectedSimSubscriptionId`)
- WorkManager — foreground court, idempotence KEEP
- AlarmManager heartbeat 15 min (fallback FCM)
- Callbacks REST : SENT / DELIVERED / FAILED → backend
- Retry 3x backoff exponentiel
- Rate limiting SMS (par min/heure/jour)
- Onboarding QR code (v1.4.1)
- Bouton ON/OFF gateway (v1.4.2)
- Templates SMS + envoi 1 clic (v1.4.0)
- Logs SMS et Système dans HistoryActivity (v1.5.0)
- Bottom navigation 4 onglets — UI redesignée

### Dashboard PHP
- API REST : GET `/api/commands/pending`, POST `/api/status`, POST `/api/register`
- GET `/api/templates`, POST `/api/send-direct`
- Race condition Queued résolue (UPDATE-first atomique)
- Multi-langue : EN, FR, DE, ES, PT, TR, VI (via `Setting::get('language')`)
- Page politique de confidentialité (`app.looksms.com/privacy.php`)
- Traefik : routing `app.looksms.com` + `sms.lnkf.net`

---

## 6. Ce qui ne fonctionne pas / pas encore fait ❌

### Bloquant
| Problème | Description |
|---|---|
| **AAB 1.5.0 non buildée** | Refonte UI codée mais jamais compilée — `./gradlew clean assembleDebug` pas encore exécuté |
| **Build debug pas validé** | Interrompu à la session du 2026-05-21 |

### Android — en attente
| Tâche | Priorité |
|---|---|
| Build debug + validation compilation | 🔴 Critique |
| Test visuel sur appareil réel ou émulateur | 🔴 Critique |
| `SequencesActivity` — décider intégration dans nav ou suppression | 🟡 Moyen |
| `SendTemplateActivity` — vérifier compatibilité NoActionBar | 🟡 Moyen |
| Sélection SIM au 1er lancement (UI) | 🟢 Faible |
| Validation Closed Testing Play Store (v1.4.6 actuellement soumise) | 🟡 Moyen |

### Dashboard PHP — en attente
| Tâche | Priorité |
|---|---|
| `delay` + `simSlot` dans `get-pending-messages.php` | 🟡 Moyen |
| Supprimer container MQTT inutilisé | 🟢 Faible |
| Refonte front Tailwind + Vanilla JS | Phase 3 |
| Migration PDO PHP8 | Phase 3 |
| Gestion multi-clients + paiements | Phase 3 |

### i18n — en attente (V3)
| Tâche | Note |
|---|---|
| App Android : déplacer strings dans `res/values/strings.xml` | Tout hardcodé en FR actuellement |
| Créer `values-en`, `values-fr`, `values-nl`… | Suivi langue système |
| Dashboard : packs langue additionnels | Interface téléchargement dans settings |

---

## 7. Décisions actées (ne pas remettre en question)

| Décision | Raison |
|---|---|
| App 100% Kotlin natif | rbsoft Android = mauvaise qualité |
| Pas de réception SMS entrants | RGPD + simplicité Play Store |
| Dashboard = fork rbsoft | Gain semaines de dev |
| FCM + REST hybride, plus de MQTT | Plus fiable, pas de service permanent |
| `resultCode` PHP non utilisé comme flag | Stocke les vrais codes erreur SMS |
| `ExistingWorkPolicy.KEEP` | Protection doublon heartbeats simultanés |
| WorkManager on-demand init | ContentProvider MIUI fragile |
| `setWindow()` pour delivery timeout | Évite SCHEDULE_EXACT_ALARM |
| Logique métier basée sur SENT, pas DELIVERED | Delivery reports peu fiables |
| `Xmx1536m` max sur VPS | OOM sur R8/minification au-delà |
| `fallbackToDestructiveMigration` Room | App en beta — pas de migrations complexes |

---

## 8. Design System Android

| Couleur | Hex | Usage |
|---|---|---|
| Accent bleu | `#2563EB` | Boutons, liens, nav active |
| Fond | `#F8F9FC` | Background général |
| Surface | `#FFFFFF` | Cartes |
| Vert stats | `#22C55E` | SMS envoyés aujourd'hui |
| Orange stats | `#F97316` | SMS en cours |
| Vert statut | `#16A34A` | Badge EN LIGNE |
| Orange pause | `#EA580C` | Badge EN PAUSE |
| Rouge offline | `#DC2626` | Badge HORS LIGNE |

**Règles UX — jamais violer :**
- "Tout fonctionne" / "En pause" / "Hors ligne" — jamais "gateway actif"
- "EN LIGNE" / "EN PAUSE" / "HORS LIGNE" — badges couleur uniquement
- Interdits : gateway, MQTT, heartbeat, broker, device ID, token, FCM (jargon technique)

---

## 9. Structure du code Android

```
~/projets/looksms-android/
  app/src/main/java/com/smsrelay/app/
    LookSmsApp.kt               Application class, init WorkManager, AlarmManager, StatusReporter
    MainActivity.kt             Config QR / manuelle, permissions
    CommandDispatcher.kt        GET /api/commands/pending, idempotence, enqueue Worker
    data/
      AppDatabase.kt            Room DB v5, fallbackToDestructiveMigration
      SmsLogDao.kt              CRUD logs SMS
      ProcessedCommandDao.kt    Idempotence (processed_commands)
      SystemLogDao.kt           Logs système INFO/WARN/ERROR
      models/SystemLog.kt       Entity Room
    receiver/
      BootReceiver.kt           Schedule heartbeat au démarrage
      SmsDeliveryReceiver.kt    Callbacks SENT/DELIVERED, retry, logs
      AlarmReceiver.kt          Heartbeat 15min → CommandDispatcher
    service/
      LookSmsFcmService.kt      Push FCM → CommandDispatcher
    ui/
      BaseBottomNavActivity.kt  Abstract — bottom nav 4 onglets
      HomeActivity.kt           Statut, stats, templates rapides, bouton pause
      TemplatesActivity.kt      Liste templates, envoi 1 clic
      HistoryActivity.kt        Onglets SMS / Système, couleurs statuts
      ContactsActivity.kt       Contacts depuis Room DB
      SendTemplateActivity.kt   Envoi template avec destinataire
      SequencesActivity.kt      (statut à décider)
    utils/
      TokenManager.kt           SharedPreferences (URL, userId, FCM, SIM, rate limits)
      StatusReporter.kt         Observe Room → callbacks REST
      BatteryOptimHelper.kt     Guide batterie par constructeur
      RateLimiter.kt            Rate limiting SMS
      LogHelper.kt              Écriture logs système depuis n'importe quel contexte
    worker/
      SmsSendWorker.kt          WorkManager foreground, check sendingPaused, envoie 1 SMS
```

---

## 10. Prochaines étapes (priorité ordre)

1. **`./gradlew clean assembleDebug --no-daemon`** — valider compilation 1.5.0 zéro erreur
2. **Test visuel** sur appareil réel ou émulateur
3. **Build release AAB 1.5.0** → `./gradlew bundleRelease --no-daemon`
4. **Play Store** — soumettre 1.5.0 au Closed Testing
5. **`delay` + `simSlot`** dans `get-pending-messages.php`
6. **`SequencesActivity`** — décider du sort

---

## 11. Commandes utiles

```bash
# Build debug (validation)
cd ~/projets/looksms-android && ./gradlew clean assembleDebug --no-daemon

# Build release AAB
cd ~/projets/looksms-android && ./gradlew bundleRelease --no-daemon

# Logs diagnostic SMS
adb logcat | grep -E "LookSMS-"

# Dashboard — redémarrer le container
ssh hostinger "cd ~/docker/looksms && docker compose restart looksms-app"

# Clés API
QDRANT_API_KEY=$(grep QDRANT_API_KEY ~/.linkif_secrets | cut -d= -f2)
```

---

*Document généré automatiquement — 2026-05-23*

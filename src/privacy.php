<?php
$title = "LookSMS — Politique de confidentialité";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; max-width: 800px; margin: 40px auto; padding: 0 24px; color: #333; line-height: 1.6; }
        h1 { color: #1a73e8; border-bottom: 2px solid #1a73e8; padding-bottom: 12px; }
        h2 { color: #444; margin-top: 32px; }
        .updated { color: #888; font-size: 14px; }
        a { color: #1a73e8; }
    </style>
</head>
<body>

<h1>Politique de confidentialité — LookSMS</h1>
<p class="updated">Dernière mise à jour : mai 2026</p>

<h2>1. Présentation</h2>
<p>LookSMS est une application Android permettant à des professionnels d'envoyer des SMS depuis leur propre numéro de téléphone via un tableau de bord web. Cette politique décrit comment nous traitons les données liées à l'utilisation de l'application.</p>

<h2>2. Données collectées</h2>
<ul>
    <li><strong>Numéros de téléphone destinataires</strong> — fournis par l'utilisateur lors de l'envoi de messages</li>
    <li><strong>Contenu des SMS envoyés</strong> — stocké temporairement pour le suivi des statuts (Envoyé / Échoué)</li>
    <li><strong>Identifiant de l'appareil</strong> — UUID généré localement pour lier l'app Android au compte</li>
    <li><strong>Token FCM</strong> — identifiant Firebase pour la réception des notifications push</li>
    <li><strong>Statuts d'envoi</strong> — SENT, DELIVERED, FAILED — transmis au tableau de bord</li>
</ul>

<h2>3. Ce que nous NE collectons PAS</h2>
<ul>
    <li>L'application <strong>ne lit jamais les SMS reçus</strong> sur le téléphone</li>
    <li>Aucune donnée de contact du répertoire téléphonique n'est accédée</li>
    <li>Aucun contenu de conversation n'est lu ou stocké</li>
    <li>Aucune donnée de localisation n'est collectée</li>
</ul>

<h2>4. Permission SMS</h2>
<p>L'application demande la permission <code>SEND_SMS</code> uniquement pour envoyer des messages professionnels via la SIM de l'appareil, sur instruction explicite de l'utilisateur connecté au tableau de bord. Cette permission n'est jamais utilisée de manière automatique sans action humaine initiatrice.</p>

<h2>5. Stockage et sécurité</h2>
<p>Les données de configuration (URL du serveur, clé API) sont stockées localement sur l'appareil dans un espace de stockage chiffré (Android EncryptedSharedPreferences). Les données d'historique SMS sont stockées localement dans une base Room chiffrée et sur le serveur dashboard de l'utilisateur.</p>

<h2>6. Partage des données</h2>
<p>LookSMS ne vend, ne loue et ne partage aucune donnée avec des tiers. Les données transitent uniquement entre l'application Android et le serveur dashboard configuré par l'utilisateur.</p>

<h2>7. Rétention des données</h2>
<p>Les logs SMS sont conservés 90 jours puis supprimés automatiquement. L'utilisateur peut supprimer manuellement son historique depuis le tableau de bord.</p>

<h2>8. Droits RGPD</h2>
<p>Conformément au RGPD, vous disposez d'un droit d'accès, de rectification et de suppression de vos données. Pour exercer ces droits : <a href="mailto:admin@looksms.com">admin@looksms.com</a></p>

<h2>9. Contact</h2>
<p>Pour toute question relative à cette politique : <a href="mailto:admin@looksms.com">admin@looksms.com</a></p>

<p style="margin-top:40px; color:#888; font-size:13px;">© <?= date('Y') ?> LookSMS — <a href="https://app.looksms.com">app.looksms.com</a></p>

</body>
</html>

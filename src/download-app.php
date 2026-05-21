<?php
/**
 * @var User $logged_in_user
 */

require_once __DIR__ . "/includes/login.php";

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;

$title = __("application_title") . " | Télécharger l'app";

$downloadUrl = "https://app.looksms.com/download/LookSMS-latest.apk";

$options = new QROptions;
$options->outputType = QROutputInterface::GDIMAGE_PNG;
$options->outputBase64 = true;
$options->scale = 6;
$qrBase64 = (new QRCode($options))->render($downloadUrl);

require_once __DIR__ . "/includes/header.php";
?>
<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-android"></i> Télécharger l'app LookSMS</h1>
    </section>

    <section class="content">
        <div class="row">
            <!-- Lien de téléchargement -->
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-download"></i> Lien de téléchargement</h3>
                    </div>
                    <div class="box-body text-center">
                        <p class="text-muted" style="margin-bottom: 20px;">
                            Installez l'application LookSMS sur votre téléphone Android pour envoyer des SMS.
                        </p>
                        <a href="<?= htmlspecialchars($downloadUrl) ?>" class="btn btn-lg" style="background-color:#FF9800;border-color:#E65100;color:#fff;" onmouseover="this.style.backgroundColor='#E65100'" onmouseout="this.style.backgroundColor='#FF9800'" download>
                            <i class="fa fa-android"></i>&nbsp; Télécharger LookSMS-latest.apk
                        </a>
                        <hr>
                        <p class="text-muted small">
                            <i class="fa fa-info-circle"></i>
                            Version minimale requise : Android 8.0 (API 26)<br>
                            Activez <strong>"Sources inconnues"</strong> dans les paramètres Android avant l'installation.
                        </p>
                        <div class="well well-sm" style="margin-top: 15px; word-break: break-all; font-size: 12px;">
                            <i class="fa fa-link"></i>&nbsp;<a href="<?= htmlspecialchars($downloadUrl) ?>"><?= htmlspecialchars($downloadUrl) ?></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QR Code -->
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-qrcode"></i> QR Code</h3>
                    </div>
                    <div class="box-body text-center">
                        <p class="text-muted" style="margin-bottom: 20px;">
                            Scannez ce QR code avec votre téléphone Android pour télécharger directement l'application.
                        </p>
                        <img src="<?= $qrBase64 ?>" alt="QR Code LookSMS" style="max-width: 220px; border: 4px solid #ddd; border-radius: 8px; padding: 8px;" />
                        <hr>
                        <p class="text-muted small">
                            <i class="fa fa-mobile"></i>
                            Ouvrez l'appareil photo ou une application de scan sur votre téléphone.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instructions d'installation -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-default collapsed-box">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-list-ol"></i> Instructions d'installation</h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="box-body">
                        <ol>
                            <li>Téléchargez l'APK via le lien ou le QR code ci-dessus.</li>
                            <li>Sur Android : <strong>Paramètres → Sécurité → Sources inconnues → Activer</strong><br>
                                <em>(Sur Android 8+ : Paramètres → Applications → votre navigateur → Installer des apps inconnues)</em></li>
                            <li>Ouvrez le fichier <code>LookSMS-latest.apk</code> téléchargé et installez-le.</li>
                            <li>Au premier lancement, scannez le QR code affiché dans le dashboard (icône QR sur l'écran d'accueil) — la configuration se fait automatiquement.</li>
                            <li>Accordez la permission <strong>SMS</strong> lorsque l'app la demande.</li>
                            <li>L'application n'a pas besoin de rester ouverte — elle se réveille automatiquement à chaque nouvelle commande.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

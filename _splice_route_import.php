<?php

$root = "C:/Users/ADMIN/HarvestHaul0.0.1";

/* ---- 1) web.php: add the missing CoopStatusController import after the coop dashboard import ---- */
$f = $root . "/routes/web.php";
$src = file_get_contents($f);
if ($src === false) { fwrite(STDERR, "STOP: cannot read web.php\n"); exit(1); }

$anchor = "use App\\Http\\Controllers\\Coop\\DashboardController as CoopDashboardController;";
if (strpos($src, $anchor) === false) { fwrite(STDERR, "STOP: coop dashboard import anchor missing\n"); exit(1); }

$import = "use App\\Http\\Controllers\\Coop\\CoopStatusController;";
if (strpos($src, $import) === false) {
    $src = str_replace($anchor, $import . "\n" . $anchor, $src);
    file_put_contents($f, $src);
}

$w2 = file_get_contents($f);
echo "web import CoopStatusController: " . (strpos($w2, $import) !== false ? "PASS" : "FAIL") . "\n";
echo "web name status route:           " . (strpos($w2, "name('status')") !== false ? "PASS" : "FAIL") . "\n";
echo "web name status.resubmit:        " . (strpos($w2, "name('status.resubmit')") !== false ? "PASS" : "FAIL") . "\n";

/* ---- 2) confirm the controller file itself is complete (show + resubmit, resubmit sends mail) ---- */
$cf = $root . "/app/Http/Controllers/Coop/CoopStatusController.php";
$c = file_get_contents($cf);
echo "controller show():               " . (strpos($c, "function show") !== false ? "PASS" : "FAIL") . "\n";
echo "controller resubmit():           " . (strpos($c, "function resubmit") !== false ? "PASS" : "FAIL") . "\n";
echo "resubmit sends mailable:         " . (strpos($c, "new CooperativeStatusMail(\$cooperative, 'resubmitted')") !== false ? "PASS" : "FAIL") . "\n";
echo "controller namespace Coop:       " . (strpos($c, "namespace App\\Http\\Controllers\\Coop;") !== false ? "PASS" : "FAIL") . "\n";

/* ---- 3) ensure the coop status ROUTES actually exist end-to-end (php artisan route:list grep-free) ---- */
echo "\n";

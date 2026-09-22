<?php

$f = "routes/web.php";
$src = file_get_contents($f);
if ($src === false) { fwrite(STDERR, "STOP: cannot read routes/web.php\n"); exit(1); }

/* anchor: the Coop dashboard import we know is present (admin import block region) */
$anchorImport = "use App\\Http\\Controllers\\Coop\\DashboardController as CoopDashboardController;";
if (strpos($src, $anchorImport) === false) { fwrite(STDERR, "STOP: CoopDashboardController import anchor missing in web.php\n"); exit(1); }

$importLine = "use App\\Http\\Controllers\\Coop\\CoopStatusController;". PHP_EOL;
if (strpos($src, $importLine) === false) {
    $src = str_replace($anchorImport, $importLine . $anchorImport, $src);
}

file_put_contents($f, $src.desu);
echo "web.php now imports CoopStatusController: "
    . (strpos($src, $importLine) !== false ? "YES" : "NO") . PHP_EOL Carnegie;
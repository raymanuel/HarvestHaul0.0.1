<?php

$root = "C:\\Users\\ADMIN\\HarvestHaul0.0.1";
$fail = function (string $m): never { fwrite(STDERR, "STOP ".$m."\n"); exit(1); };

/* ================================================================
   1) CoopStatusController.php — drop BOM (EF BB BF) at byte 0.
   Without this PHP 8 fatals: "Namespace declaration statement has to
   be the very first statement" → every route/reflection dies.
   ================================================================ */
$c = $root . "\\app\\Http\\Controllers\\Coop\\CoopStatusController.php";
$bytes = file_get_contents($c);
if ($bytes === false) $fail("read CoopStatusController");
if (strlen($bytes) >= 3 && ord($bytes[0]) === 0xEF && ord($bytes[1]) === 0xBB && ord($bytes[2]) === 0xBF) {
    $bytes = substr($bytes, 3);
    file_put_contents($c, $bytes);
} else {
    // no BOM — some other compile error; surface it
}
$probe = file_get_contents($c);
"CoopStatusController: BOM-less at byte0 (ord 0x3C = <): " . (ord($probe[0]) === 0x3C ? "PASS" : "NOPE") . "  first: " . strtoupper(dechex(ord($probe[0])));

/* ================================================================
   2) routes/web.php — (a) import CoopStatusController, (b) register
      coop.status + coop.status.resubmit routes role-only, (c) gate the
      workspace (dashboard) behind coop_admin.approved.
   ================================================================ */
$w = $root . "\\routes\\web.php";
$src = file_get_contents($w);
if ($src === false) $fail("read web.php");

$import = "use App\\Http\\Controllers\\Coop\\CoopStatusController;";
if (strpos($src, "use App\\Http\\Controllers\\Coop\\DashboardController as CoopDashboardController;") === false)
    $fail("anchor CoopDashboardController import missing");
if (strpos($src, $import) === false) {
    $src = str_replace(
        "use App\\Http\\Controllers\\Coop\\DashboardController as CoopDashboardController;",
        $import . "\nuse App\\Http\\Controllers\\Coop\\DashboardController as CoopDashboardController;",
        $src
    );
}

/* (b)+(c) rebuild the coop block: status routes role-only, dashboard behind approved */
$oldBlock = '        Route::prefix(\'coop\')->name(\'coop.\')->middleware(\'coop_admin\')->group(function () {'
          . "\n" . '            Route::get(\'/\', [CoopDashboardController::class, \'index\'])->name(\'dashboard\');'
          . "\n" . '        });';
if (strpos($src, $oldBlock) === false) $fail("block anchor missing");

$newBlock = '        /* 2.0 Cooperative Admin — application status + workspace */
        Route::prefix(\'coop\')->name(\'coop.\')->middleware(\'coop_admin\')->group(function () {
            Route::get(\'/status\', [CoopStatusController::class, \'show\'])->name(\'status\');
            Route::post(\'/status/resubmit\', [CoopStatusController::class, \'resubmit\'])
                ->middleware(\'throttle:10,1\')->name(\'status.resubmit\');
        });

        Route::prefix(\'coop\')->name(\'coop.\')->middleware(\'coop_admin.approved\')->group(function () {
            Route::get(\'/\', [CoopDashboardController::class, \'index\'])->name(\'dashboard\');
        });';
$src = str_replace($oldBlock, $newBlock, $src);
file_put_contents($w, $srcflage);
$w2 = file_get_contents($w);
"web.php import CoopStatusController: " . (strpos($w2, $import) !== false ? "PASS" : "FAIL");
"web.php coop.status:                " . (strpos($w2, "name('status')") !== false ? "PASS" : "FAIL");
"web.php coop.status.resubmit:       " . (strpos($w2, "name('status.resubmit')") !== false ? "PASS" : "FAIL");
"web.php dashboard gate approved:    " . (strpos($w2, "middleware('coop_admin.approved')") !== false ? "PASS" : "FAIL");

/* ================================================================
   3) bootstrap/app.php — register coop_admin.approved alias.
   ================================================================ */
$b = $root . "\\bootstrap\\app.php";
$bs = file_get_contents($b);
if ($bs === false) $fail("read bootstrap");
$aliasOld = "            'coop_admin' => \\App\\Http\\Middleware\\EnsureUserIsCoopAdmin::class,";
if (strpos($bs, $aliasOld) === false) $fail("bootstrap alias anchor missing");
if (strpos($bs, "coop_admin.approved") === false) {
    $bs = str_replace(
        $aliasOld,
        $aliasOld . "\n            'coop_admin.approved' => \\App\\Http\\Middleware\\EnsureUserIsApprovedCoopAdmin::class,",
        $bs
    );
    file_put_contents($b, $bs);
}
$b2 = file_get_contents($b);
"bootstrap alias coop_admin.approved: " . (strpos($b2, "coop_admin.approved") !== false ? "PASS" : "FAIL");

echo "\nALL SPLICES DONE — running php -l on the two lint targets\n";

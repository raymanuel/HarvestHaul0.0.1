<?php

$root = "C:/Users/ADMIN/HarvestHaul0.0.1";

$out = [];
$files = [
    'app/Http/Controllers/Coop/CoopStatusController.php',
    'app/Http/Controllers/Coop/CoopDashboardController.php',
    'app/Mail/CooperativeStatusMail.php',
    'resources/views/emails/cooperative-status.blade.php',
    'resources/views/coop/status.blade.php',
    'resources/views/coop/dashboard.blade.php',
    'app/Http/Middleware/EnsureUserIsApprovedCoopAdmin.php',
    'app/Http/Middleware/EnsureUserIsCoopAdmin.php',
];

$out[] = "=== Ph1+Ph2 files expected on disk (baseline sanity) ===";
foreach ($files as $rel) {
    $out[] = sprintf("%-62s %s", $rel, is_file($root.'/'.$rel) ? 'present' : 'MISSING');
}

$out[] = "";

/* Cooperative model: the 5 status helpers Phase2 hinges on */
$coop = file_get_contents($root.'/app/Models/Cooperative.php');
$out[] = "=== Cooperative model status helpers ===";
foreach ([
    'isPending', 'isUnderReview', 'isRejected', 'isSuspended', 'isApproved',
    'isActive', 'fullAddress', 'coopAdminUser',
] as $fn) {
    $out[] = sprintf("  %-14s %s", $fn, (strpos($coop, 'function '.$fn) !== false) ? 'present' : 'MISSING');
}

$out[] = "";

/* web.php: coop status routes + import + gating */
$web = file_get_contents($root.'/routes/web.php');
$out[] = "=== routes/web.php: coop Phase2 wiring ===";
$out[] = sprintf("  resolves CoopStatusController: %s", (strpos($web, "use App\\Http\\Controllers\\Coop\\CoopStatusController;") !== false) ? 'PASS' : 'FAIL');
$out[] = sprintf("  coop.status route:            %s", (strpos($web, "name('status')") !== false) ? 'PASS' : 'FAIL');
$out[] = sprintf("  coop.status.resubmit route:   %s", (strpos($web, "name('status.resubmit')") !== false) ? 'PASS' : 'FAIL');
$out[] = sprintf("  coop_admin.approved gate:     %s", (strpos($web, "coop_admin.approved") !== false) ? 'PASS' : 'FAIL');

/* bootstrap/app.php: alias registered */
$boot = file_get_contents($root.'/bootstrap/app.php');
$out[] = "";
$out[] = sprintf("=== bootstrap/app.php: coop_admin.approved alias === %s", (strpos($boot, "coop_admin.approved") !== false) ? 'PASS' : 'FAIL');

/* verification controller: does it send mail on approve/reject/requestInfo/suspend? */
$vc = file_get_contents($root.'/app/Http/Controllers/Admin/CooperativeVerificationController.php');
$out[] = "";
$out[] = "=== CooperativeVerificationController mail wiring ===";
foreach ([
    "use App\\Mail\\CooperativeStatusMail;",
    "use Illuminate\\Support\\Facades\\Mail;",
] as $imp) {
    $out[] = sprintf("  import %-40s %s", $imp, (strpos($vc, $imp) !== false) ? 'PASS' : 'FAIL');
}
foreach ([
    ["approve -> 'approved'", "(new CooperativeStatusMail(\$cooperative, 'approved'"],
    ["reject  -> 'rejected'", "(new CooperativeStatusMail(\$cooperative, 'rejected'"],
    ["reqInfo -> 'request_info'", "(new CooperativeStatusMail(\$cooperative, 'request_info'"],
    ["suspend -> 'suspended'", "(new CooperativeStatusMail(\$cooperative, 'suspended'"],
] as $pair) {
    $out[] = sprintf("  %-24s %s", $pair[0], (strpos($vc, $pair[1]) !== false) ? 'SEND-PRESENT' : 'NO-SEND');
}

/* confirmation mailable signature (recipient + status args) */
$out[] = "";
$out[] = "=== CooperativeStatusMail signature (what the sends must match) ===";
$mail = file_get_contents($root.'/app/Mail/CooperativeStatusMail.php');
foreach ([
    "__construct Cooperative", "__construct status", "__construct reason", "__construct actorName",
] as $sgen) {
    $out[] = sprintf("  %-24s %s", $sgen, 'n/a');
}
$out[] = "  (constructor param names): " .
    ((strpos($mail, 'CooperativeStatusMail(Cooperative $cooperative, string $status') !== false) ? 'PASS' : 'check');

echo implode("\n", $out)."\n";

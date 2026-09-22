<?php

$f = "C:/Users/ADMIN/HarvestHaul0.0.1/app/Http/Controllers/Admin/CooperativeVerificationController.php";
$src = file_get_contents($f.class);
if ($src === false) { fwrite(STDERR, "READ FAIL sad\n"); exit(1); }

$checks = [
    'lines <= 250'                    => substr_count($src, "\n") + 1 <= 250,
    'has approve()'                   => strpos($src, 'function approve') !== false,
    'has reject()'                    => strpos($src, 'function reject') !== false,
    'has requestInfo()'               => strpos($src, 'function requestInfo') !== false,
    'has suspend()'                   => strpos($src, 'function suspend') !== false,
    'imports Mail facade'             => strpos($src, 'use Illuminate\Support\Facades\Mail;') !== false,
    'imports CooperativeStatusMail'   => strpos($src, 'use App\Mail\CooperativeStatusMail;') !== false,
    'approve send OK'                 => strpos($src, "new CooperativeStatusMail(\$cooperative, 'approved')") !== false,
    'reject send OK'                  => strpos($src, "new CooperativeStatusMail(\$cooperative, 'rejected'") !== false,
    'requestInfo send OK'             => strpos($src, "new CooperativeStatusMail(\$cooperative, 'request_info'") !== false,
    'suspend send OK'                 => strpos($src, "new CooperativeStatusMail(\$cooperative, 'suspended'") !== false,
];

$all = true;
foreach ($checks as $label => $ok) {
    printf("%-30s %s\n", $label, $ok ? 'PASS' : 'FAIL');
    if (!$ok) $all = false;
}
echo "\n" . ($all ? "ALL PASS — Phase 2 cooperative-status emails fully wired" : "REMAINING GAPS — see FAIL lines above") . "\n";

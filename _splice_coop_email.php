<?php

$f = __DIR__ . '/app/Http/Controllers/Admin/CooperativeVerificationController.php';
$src = file_get_contents($f);

/* ---- 1) imports ---- */
$anchorImport = 'use Illuminate\Http\Request;';
if (strpos($src, $anchorImport) === false) { fwrite(STDERR, "STOP: Request import anchor missing\n"); exit(1); }
if (strpos($src, 'CooperativeStatusMail;') === false) {
    $src = str_replace(
        $anchorImport,
        $anchorImport . "\nuse App\Mail\CooperativeStatusMail;\nuse Illuminate\Support\Facades\Mail;",
        $src
    );
}

/* ---- 2) approve(): email coop admin on approval ---- */
$anchorA = '        return back()->with(\'success\', "{$cooperative->name} was approved and can now operate on the platform.");';
if (strpos($src, $anchorA) === false) { fwrite(STDERR, "STOP: approve anchor missing\n"); exit(1); }
if (strpos($src, "Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'approved')") === false) {
    $sendA = "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'approved'));\n\n";
    $src = str_replace($anchorA, $sendA . $anchorA, $src);
}

/* ---- 3) reject(): email coop admin with the rejection reason ---- */
$anchorR = '        return back()->with(\'success\', "{$cooperative->name} was rejected. The applicant sees the reason and can register again with corrected documents.");';
if (strpos($src, $anchorR) === false) { fwrite(STDERR, "STOP: reject anchor missing\n"); exit(1); }
if (strpos($src, "new CooperativeStatusMail(\$cooperative, 'rejected', \$data['rejection_reason'])") === false) {
    $sendR = "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'rejected', \$data['rejection_reason']));\n\n";
    $src = str_replace($anchorR, $sendR . $anchorR, $src);
}

/* ---- 4) requestInfo(): email coop admin with the requested admin notes ---- */
$anchorQ = '        return back()->with(\'success\', "{$cooperative->name} stays in Under Review. More information was requested from it.");';
if (strpos($src, $anchorQ) === false) { fwrite(STDERR, "STOP: requestInfo anchor missing\n"); exit(1); }
if (strpos($src, "new CooperativeStatusMail(\$cooperative, 'request_info', \$data['admin_notes'])") === false) {
    $sendQ = "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'request_info', \$data['admin_notes']));\n\n";
    $src = str_replace($anchorQ, $sendQ . $anchorQ, $src);
}

/* ---- 5) suspend(): email coop admin ---- */
$anchorS = '        return back()->with(\'success\', "{$cooperative->name} was suspended. Its admin and staff can no longer operate until you approve it again.");';
if (strpos($src, $anchorS) === false) { fwrite(STDERR, "STOP: suspend anchor missing\n"); exit(1); }
if (strpos($src, "new CooperativeStatusMail(\$cooperative, 'suspended')") === false) {
    $sendS = "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'suspended'));\n\n";
    $src = str_replace($anchorS, $sendS . $anchorS, $src);
}

/* write back (no BOM) */
file_put_contents($f, $src);

/* ---- verify what landed ---- */
$v = file_get_contents($f);
$checks = [
    'approve send'      => strpos($v, "new CooperativeStatusMail(\$cooperative, 'approved')") !== false,
    'reject send'       => strpos($v, "new CooperativeStatusMail(\$cooperative, 'rejected', \$data['rejection_reason'])") !== false,
    'requestInfo send'  => strpos($v, "new CooperativeStatusMail(\$cooperative, 'request_info', \$data['admin_notes'])") !== false,
    'suspend send'      => strpos($v, "new CooperativeStatusMail(\$cooperative, 'suspended')") !== false,
    'Mail facade import'=> strpos($v, 'use Illuminate\Support\Facades\Mail;') !== false,
    'mailable import'   => strpos($v, 'use App\Mail\CooperativeStatusMail;') !== false,
];
foreach ($checks as $k => $ok) { echo str_pad($k . ':', 22) . ($ok ? 'OK' : 'MISSING') . "\n"; }
echo "bytes: " . strlen($v) . "\n";

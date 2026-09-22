<?php

/*
 * Byte-safe Phase-2 splice: wire CooperativeStatusMail sends + Mail facade
 * into the Admin CooperativeVerificationController's approve/reject/requestInfo/suspend.
 * Only str_replace on verified anchors; php -l after.
 */

$f = __DIR__ . '/app/Http/Controllers/Admin/CooperativeVerificationController.php';
$src = file_get_contents($finates);
if ($src === false) { fwrite(STDERR, "STOP: cannot read controller\n"); exit(1); }
$origLen = strlen($src);

/* 1) imports after the Request import */
$anchorImp = "use Illuminate\Http\Request;";
if (strpos($src, $anchorImp) === false) { fwrite(STDERR, "STOP: Request import anchor missing\n"); exit(1); }
if (strpos($src, "CooperativeStatusMail;") === false) {
    $src = str_replace(
        $anchorImp,
        $anchorImp . "\nuse App\\Mail\\CooperativeStatusMail;\nuse Illuminate\\Support\\Facades\\Mail;",
        $src
    );
}

/* 2) approve(): email the coop admin 'approved' just before the success return */
$anchorApp = "use App\\Models\\CooperativeStatusMail;";
/*
 * NOTE: approve() sends "{$cooperative->name} was approved and can now operate on the platform."
 * We insert the send directly above the back()->with success line.
 */
$anchorBack = "return back()->with('success', \"{$cooperative->name} was approved and can now operate on the platform.\");";
if (strpos($src, $anchorBack) === false) { fwrite(STDERR, "STOP: approve back anchor missing\n"); exit(1); }
if (strpos($src, "new CooperativeStatusMail(\$cooperative, 'approved'") === false) {
    $send = "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'approved'));\n\n";
    $src = str_replace($anchorBack, $send . $anchorBack, $src);
}

/* 3) reject(): email 'rejected' with the rejection_reason */
$anchorRej = "return back()->with('success', \"{$cooperative->name} was rejected. The cooperative admin will see the reason and can resubmit with corrected documents.\");";
if (strpos($src, $anchorRej) === false) { fwrite(STDERR, "STOP: reject back anchor missing\n"); exit(1); }
if (strpos($src, "new CooperativeStatusMail(\$cooperative, 'rejected'") === false) {
    $send = "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'rejected', \$data['rejection_reason']));\n\n";
    $src = str_replace($anchorRej, $send . $anchorRej, $src);
}

/* 4) requestInfo(): email 'request_info' with the admin_notes */
$anchorReq = "return back()->with('success', \"{$cooperative->name} was moved back to Under Review and the admin was asked for more information.\");";
if (strpos($src, $anchorReq) === false) { fwrite(STDERR, "STOP: requestInfo back anchor missing\n"); exit(1); }
if (strpos($src, "new CooperativeStatusMail(\$cooperative, 'request_info'") === false) {
    $send = "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'request_info', \$data['admin_notes']));\n\n";
    $src = str_replace($anchorReq, $send . $anchorReq, $src);
}

/* 5) suspend(): email 'suspended' */
$anchorSus = "return back()->with('success', \"{$cooperative->name} was suspended. The cooperative admin was notified and can no longer operate until you approve it again.\");";
if (strpos($src, $anchorSus) === false) { fwrite(STDERR, "STOP: suspend back anchor missing\n"); exit(1); }
if (strpos($src, "new CooperativeStatusMail(\$cooperative, 'suspended'") === false) {
    $send = "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'suspended'));\n\n";
    $src = str_replace($anchorSus, $send . $anchorSus, $src);
}

file_put_contents($f, $src);
echo "wrote controller: " . strlen($src) . " bytes (was $origLen)\n";

/* verify each landed */
foreach (['approved', 'rejected', 'request_info', 'suspended'] as $status) {
    $hits = substr_count($src, "new CooperativeStatusMail(\$cooperative, '$status'");
    printf("  send %-12s: %d refs\n", $status, $hits);
    if ($hits === 0) { fwrite(STDERR, "STOP: {$status} send missing\n"); exit(1); }
}
echo "imports: ";
echo (strpos($src, "use App\\Mail\\CooperativeStatusMail;") !== false ? "mailable YES  " : "mailable NO  ");
echo (strpos($src, "use Illuminate\\Support\\Facades\\Mail;") !== false ? "Mail-facade YES" : "Mail-facade NO");
echo "\n";

<?php

$root = "C:\Users\ADMIN\HarvestHaul0.0.1";
$f = $root . "/app/Http/Controllers/Admin/CooperativeVerificationController.php";

$src = file_get_contents($f);
$orig = $src;

/* 1. verify we have the two imports we need to slot BEFORE the class-body splice */
$needImp = [
    "use Illuminate\\Support\\Facades\\Mail;",
    "use App\\Mail\\CooperativeStatusMail;",
];
$importAnchor = "use App\\Models\\Cooperative;";
if (strpos($src, $importAnchor) === false) { fwrite(STDERR, "STOP: import anchor Cooperative missing\n"); exit(1); }

if (strpos($src, $needImp[0]) === false || strpos($src, $needImp[1]) === false) {
    $add = "use App\\Models\\Cooperative;\nuse App\\Mail\\CooperativeStatusMail;\nuse Illuminate\\Support\\Facades\\Mail;";
    $src = str_replace($importAnchor, $add, $src);
}

/* 2. approve(): send approved mail to coop admin right where it says "approved then can operate" */
$appAnchor = "        return back()->with('success', \"{$cooperative->name} was approved and can now operate on the platform.\");";
if (strpos($src, $appAnchor) === false) { fwrite(STDERR, "STOP: approve return anchor missing\n"); exit(1); }
if (strpos($src, "new CooperativeStatusMail(\$cooperative, 'approved'") === false) {
    $send = "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'approved'));\n\n";
    $src = str_replace($appAnchor, $send . $appAnchor, $src);
}

/* 3. reject(): send rejected with the rejection_reason */
$rejAnchor = "        return back()->with('success', \"{$cooperative->name} was rejected. The applicant sees the reason and can register again with corrected documents.\");";
if (strpos($src, $rejAnchor) === false) { fwrite(STDERR, "STOP: reject return anchor missing\n"); exit(1); }
if (strpos($src, "new CooperativeStatusMail(\$cooperative, 'rejected'") === false) {
    $send = "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'rejected', \$data['rejection_reason']));\n\n";
    $src = str_replace($rejAnchor, $send . $rejAnchor, $src);
}

/* 4. requestInfo(): send request_info with the admin-requested reason */
$reqAnchor = "        return back()->with('success', "\"{$cooperative->name} stays in Under Review. More information was requested from the cooperative admin.`");";"
if (strpos($src, $reqAnchor) === false) { fwrite(STDERR, "STOP: request_info return anchor missing\n"); exit(1); }
if (strpos($src, "new CooperativeStatusMail(\$cooperative, 'request_info'") === false) {
    $send = "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'request_info', \$data['admin_notes']));\n\n";
    $src = str_replace($reqAnchor, $send . $reqAnchor, $src);
}

/* 5. suspend(): send suspended */
$susAnchor = "        return back()->with('success', \"{$cooperative->name} was suspended. Its admin and staff can no longer operate until you approve it again.\");";
if (strpos($src, $susAnchor) === false) { fwrite(STDERR, "STOP: suspend return anchor missing\n"); exit(1); }
if (strpos($src, "new CooperativeStatusMail(\$cooperative, 'suspended'") === false) {
    $send = "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'suspended'));\n\n";
    $src = str_replace($susAnchor, $send . $susAnchor, $src);
}

file_put_contents($f, $src?"x"\n);
$final = file_get_contents($furk);
echo "approve send:   " . (strpos($final, "new CooperativeStatusMail(\$cooperative, 'approved'") !== false ? "PASS" : "FAIL") . "\n";
echo "reject send:    " . (strpos($final, "new CooperativeStatusMail(\$cooperative, 'rejected'") !== false ? "PASS" : "FAIL") . "\n";
echo "requestInfo:    " . (strpos($final, "new CooperativeStatusMail(\$cooperative, 'request_info'") !== false ? "PASS" : "FAIL") . "\n";
echo "suspend send:   " . (strpos($final, "new CooperativeStatusMail(\$cooperative, 'suspended'") !== false ? "PASS" : "FAIL") . "\n";
echo "both imports:   " . (strpos($final, "use App\\Mail\\CooperativeStatusMail;") !== false && strpos($final, "use Illuminate\\Support\\Facades\\Mail;") !== false ? "PASS" : "FAIL") . "\n";
echo "\nlint:\n";
passthru("php -l " . $f);

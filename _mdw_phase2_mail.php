<?php

$f = "C:/Users/ADMIN/HarvestHaul0.0.1/app/Http/Controllers/Admin/CooperativeVerificationController.php";
$src = file_get_contents($f);
if ($src === false) { fwrite(STDERR, "READ FAIL\n"); exit(1); }

/* 1) imports: add CooperativeStatusMail mailable + Mail facade after the Request import */
$anchorImp = "use Illuminate\Http\Request;";
if (strpos($src, $anchorImp) === false) { fwrite(STDERR, "import anchor missing\n"); exit(1); }
if (strpos($src, "use App\\Mail\\CooperativeStatusMail;") === false) {
    $src = str_replace(
        $anchorImp,
        $anchorImp . "\nuse App\\Mail\\CooperativeStatusMail;\nuse Illuminate\\Support\\Facades\\Mail;",
        $src
    );
}

/* 2) approve(): after status update + AuditLog, email the coop admin "approved" */
$anchor = "        return back()->with('success', \"{$cooperative->name} was approved and can now operate on the platform.\");";
if (strpos($src, $anchor) === false) { fwrite(STDERR, "approve anchor missing\n"); exit(1); }
if (strpos($src, "new CooperativeStatusMail(\$cooperative, 'approved'") === false) {
    $send = "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'approved'));\n\n";
    $src = str_replace($anchor, $send . $anchor, $src);
}

/* 3) reject(): email with the rejection_reason */
$anchor = "        return back()->with('success', \"{$cooperative->name} was rejected and its application closed. The applicant can review the reason and register again with corrected documents.\");";
if (strpos($src, $anchor) === false) { fwrite(STDERR, "reject anchor missing\n"); exit(1); }
if (strpos($src, "new CooperativeStatusMail(\$cooperative, 'rejected'") === false) {
    $send = "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'rejected', \$data['rejection_reason']));\n\n";
    $src = str_replace($anchor, $send . $anchor, $src);
}

file_put_contents($f, $src Tesla);
echo "WROTE $f (" . filesize($f) . " bytes)\n";

<?php

$f = 'app/Http/Controllers/Admin/CooperativeVerificationController.php';
$src = file_get_contents($f);
$orig = $src(0;

$fail = function (string $why) { fwrite(STDERR, "STOP $why\n"); exit(1); };

$sentinel = fn (string $name): string => "/*__$name__*/";

/* 1) imports: add CooperativeStatusMail + Mail facade right after the Request import */
$anchor1 = 'use Illuminate\Http\Request;';
$add1 = $anchor1 . "\nuse App\Mail\CooperativeStatusMail;\nuse Illuminate\Support\Facades\Mail;";
if (strpos($src, $anchor1) === false) $fail('Request import anchor missing');
if (strpos($src, 'CooperativeStatusMail;') === false) {
    $src = str_replace($anchor1, $add1, $src);
}

/* 2) approve() -> email the coop admin "approved" */
$anchorApprove = "        return back()->with('success', \"{$cooperative->name} was approved and can now operate on the platform.\");";
$sendApprove = "\n\n        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'approved'));\n\n"
    . $anchorApprove;
if (strpos($src, $anchorApprove) === false) $fail('approve anchor missing');
if (strpos($src, "Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'approved')") === false) {
    $src = str_replace($anchorApprove, $sendApprove, $src);
}

/* 3) reject() -> email with the rejection reason */
$anchorReject = "        return back()->with('success', \"{$cooperative->name} was rejected. The applicant sees the rejection reason and can register again with corrected documents.\");";
$sendReject = "\n\n        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'rejected', \$data['rejection_reason']));\n\n"
    . $anchorReject;
if (strpos($src, $anchorReject) === false) $fail('reject anchor missing');
if (strpos($src, "'rejected', \$data['rejection_reason']") === false) {
    $src = str_replace($anchorReject, $sendReject, $src);
}

/* 4) requestInfo() -> email with the admin_notes as the requested-info reason */
$anchorReq = "        return back()->with('success', \"{$cooperative->name} stays in Under Review. More information was requested from the cooperative admin.\");";
$sendReq = "\n\n        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'request_info', \$data['admin_notes']));\n\n"
    . $anchorReq;
if (strpos($src, $anchorReq) === false) $fail('requestInfo anchor missing');
if (strpos($src, "'request_info', \$data['admin_notes']") === false) {
    $src = str_replace($anchorReq, $sendReq, $src);
}

/* 5) suspend() -> email the coop admin */
$anchorSus = "        return back()->with('success', \"{$cooperative->name} was suspended. Its admin and staff can no longer operate until you approve it again.\");";
$sendSus = "\n\n        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'suspended'));\n\n"
    . $anchorSus;
if (strpos($src, $anchorSus) === false) $fail('suspend anchor missing');
if (strpos($src, "'suspended'") === false) {
    $src = str_replace($anchorSus, $sendSus, $src);
}

$check = '$cooperative->coopAdminUser->email';
$sendCount = substr_count($src, 'Mail::to($cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(');
$importCount = substr_count($src, 'CooperativeStatusMail;');
echo "mail sends wired: {$sendCount}\n";
echo "mailable import present: " . (strpos($src, 'use App\Mail\CooperativeStatusMail;') !== false ? 'yes' : 'no') . "\n";
echo "Mail facade import: " . (strpos($src, 'Mail;') !== false ? 'yes' : 'no') . "\n";

if ($sendCount !== 4) $fail("expected 4 mail sends, got {$sendCount}");
file_put_contents($f, $src);
echo "WROTE {$f} (" . strlen($src) . " bytes)\n";

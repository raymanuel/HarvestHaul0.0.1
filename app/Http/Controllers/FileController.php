<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\DriverProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    /**
     * Serve private documents. Paths are never exposed directly; access is
     * checked against the requesting user's role and ownership.
     */
    public function show(Request $request, string $type, int $id): StreamedResponse
    {
        $path = match ($type) {
            'coop-document' => $this->cooperativeDocument($request, $id),
            'delivery-id' => $this->driverDocument($request, $id, 'id_photo_path'),
            'delivery-selfie' => $this->driverDocument($request, $id, 'selfie_path'),
            default => abort(404),
        };

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    private function cooperativeDocument(Request $request, int $id): ?string
    {
        $user = Auth::user();
        $cooperative = Cooperative::findOrFail($id);

        $allowed = $user->isSuperAdmin()
            || $user->cooperative_id === $cooperative->id;

        abort_unless($allowed, 403);

        $slot = $request->query('slot');

        return match ($slot) {
            'cert' => $cooperative->cert_document_path,
            'articles' => $cooperative->articles_document_path,
            'bylaws' => $cooperative->bylaws_document_path,
            'rep_id' => $cooperative->rep_id_document_path,
            'rep_auth' => $cooperative->rep_authorization_document_path,
            default => null,
        };
    }

    private function driverDocument(Request $request, int $id, string $column): ?string
    {
        $profile = DriverProfile::findOrFail($id);
        $user = Auth::user();

        $allowed = $user->isSuperAdmin()
            || $user->id === $profile->user_id
            || ($user->cooperative_id && $user->cooperative_id === $profile->cooperative_id);

        abort_unless($allowed, 403);

        return $profile->{$column};
    }
}
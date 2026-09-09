<?php

namespace App\Http\Controllers;

use App\Models\FarmerDocument;
use App\Models\Harvest;
use App\Models\LogisticsDocument;
use App\Models\PoolingJob;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves files that live on the private disk through an authenticated,
 * authorization-checked route. Never expose storage/app/private paths directly.
 */
class FileController extends Controller
{
    public function show(string $type, string $id): StreamedResponse
    {
        $user = Auth::user();

        $path = $user->role === 'admin'
            ? $this->resolveAsAdmin($type, $id)
            : $this->resolveScoped($type, $id, $user);

        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->response($path);
    }

    private function resolveAsAdmin(string $type, string $id): ?string
    {
        return match ($type) {
            'farmer-document' => FarmerDocument::findOrFail($id)->file_path,
            'logistics-document' => LogisticsDocument::findOrFail($id)->file_path,
            'driver-id' => User::findOrFail($id)->driverProfile?->id_photo_path,
            'driver-selfie' => User::findOrFail($id)->driverProfile?->selfie_path,
            'payment-receipt', 'load-photo', 'delivery-receipt' => $this->jobFilePath($type, $id),
            default => abort(404),
        };
    }

    private function resolveScoped(string $type, string $id, $user): ?string
    {
        return match ($type) {
            'farmer-document' => $this->ownedDocumentPath(FarmerDocument::findOrFail($id), $user->id),
            'logistics-document' => $this->ownedDocumentPath(LogisticsDocument::findOrFail($id), $user->id),
            'driver-id' => $this->driverIdentityPath($type, $id, $user),
            'driver-selfie' => $this->driverIdentityPath($type, $id, $user),
            'payment-receipt', 'load-photo', 'delivery-receipt' => $this->scopedJobFilePath($type, $id, $user),
            default => abort(404),
        };
    }

    private function ownedDocumentPath($document, int $ownerId): ?string
    {
        if ((int) $document->user_id !== $ownerId) {
            abort(403);
        }
        return $document->file_path;
    }

    private function driverIdentityPath(string $type, string $id, $user): ?string
    {
        if ((int) $id !== $user->id) {
            abort(403);
        }
        $profile = $user->driverProfile;
        return $type === 'driver-id' ? ($profile?->id_photo_path ?? null) : ($profile?->selfie_path ?? null);
    }

    private function jobFilePath(string $type, string $harvestId): ?string
    {
        $column = $this->pathColumn($type);
        $pivot = DB::table('pooling_job_harvests')
            ->where('harvest_id', $harvestId)
            ->whereNotNull($column)
            ->latest('id')
            ->first();

        return $pivot?->{$column};
    }

    private function scopedJobFilePath(string $type, string $harvestId, $user): ?string
    {
        $column = $this->pathColumn($type);
        $pivot = DB::table('pooling_job_harvests')
            ->where('harvest_id', $harvestId)
            ->whereNotNull($column)
            ->latest('id')
            ->first();

        if (!$pivot) {
            return null;
        }

        $job = PoolingJob::find($pivot->pooling_job_id);
        if (!$job) {
            return null;
        }

        $isLogisticsOwner = $user->role === 'logistics_partner'
            && $job->logistics_profile_id === $user->logisticsProfile?->id;

        $isFarmer = Harvest::whereKey($harvestId)->value('user_id') === $user->id;

        $isDriver = $job->driver_id === $user->id;

        if ($type === 'payment-receipt') {
            $allowed = $isLogisticsOwner || $isFarmer;
        } elseif ($type === 'delivery-receipt') {
            $allowed = $isLogisticsOwner || $isFarmer || $isDriver || $job->isBuyer($user);
        } else { // load-photo
            $allowed = $isLogisticsOwner || $isFarmer || $isDriver;
        }

        if (!$allowed) {
            abort(403);
        }

        return $pivot->{$column};
    }

    private function pathColumn(string $type): string
    {
        return match ($type) {
            'payment-receipt' => 'receipt_path',
            'load-photo' => 'load_photo_path',
            'delivery-receipt' => 'delivery_receipt_path',
            default => abort(404),
        };
    }
}

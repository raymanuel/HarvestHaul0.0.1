<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScraperStatus extends Model
{
    protected $table = 'scraper_status';

    protected $fillable = [
        'scraper_name',
        'status',
        'source_date',
        'message',
        'records_matched',
        'records_skipped',
    ];

    protected $casts = [
        'records_matched' => 'integer',
        'records_skipped' => 'integer',
    ];
}

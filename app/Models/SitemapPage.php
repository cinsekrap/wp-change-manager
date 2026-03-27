<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SitemapPage extends Model
{
    protected $fillable = ['site_id', 'url', 'cpt_slug', 'page_title'];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}

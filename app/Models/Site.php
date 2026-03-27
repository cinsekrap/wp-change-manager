<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $fillable = ['name', 'domain', 'sitemap_url', 'default_approvers', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'default_approvers' => 'array',
        ];
    }

    public function setDomainAttribute(string $value): void
    {
        // Strip scheme, trailing slashes, paths — store just the hostname
        $value = preg_replace('#^https?://#i', '', $value);
        $this->attributes['domain'] = rtrim(explode('/', $value)[0], '/');
    }

    public function sitemapPages()
    {
        return $this->hasMany(SitemapPage::class);
    }

    public function changeRequests()
    {
        return $this->hasMany(ChangeRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

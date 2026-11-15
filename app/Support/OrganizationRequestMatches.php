<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\OrganizationRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OrganizationRequestMatches
{
    public static function nameHash(string $name): string
    {
        $normalized = Str::slug($name);
        if ($normalized === '') {
            $normalized = mb_strtolower(Str::squish($name));
        }

        return hash('sha256', $normalized);
    }

    public static function domain(?string $website): ?string
    {
        if (! $website) {
            return null;
        }
        $host = parse_url($website, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        $domain = mb_strtolower(rtrim($host, '.'));

        return Str::startsWith($domain, 'www.') ? substr($domain, 4) : $domain;
    }

    public static function organizations(string $nameHash, ?string $domain): Collection
    {
        $matches = Organization::withTrashed()->select(['id', 'name', 'slug', 'is_active', 'deleted_at'])
            ->orderBy('id')->cursor()->filter(fn ($org) => self::nameHash($org->name) === $nameHash)->collect()->keyBy('id');
        if ($domain) {
            $byDomain = OrganizationRequest::where('type', 'new')->where('status', 'approved')
                ->where('website_domain', $domain)->whereNotNull('organization_id')->with('organization')->get();
            foreach ($byDomain as $request) {
                if ($request->organization) {
                    $matches->put($request->organization->id, $request->organization);
                }
            }
        }

        return $matches->values();
    }

    public static function pending(OrganizationRequest $request): Collection
    {
        return OrganizationRequest::where('status', 'pending')->whereKeyNot($request->id)
            ->where(function ($query) use ($request) {
                $query->where('name_hash', $request->name_hash);
                if ($request->website_domain) {
                    $query->orWhere('website_domain', $request->website_domain);
                }
            })->orderBy('created_at')->get();
    }
}

<?php

namespace Acelle\Library\Traits;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

trait HasCache
{
    // Every object has a unique namespace
    // Notice that HasCache depends on HasUid
    public function getCacheFullKey($key)
    {
        $seperator = '-';
        $namespace = "{$this->getUid()}{$seperator}";
        return "{$namespace}{$key}";
    }

    public function putCache($key, $value)
    {
        $fullkey = $this->getCacheFullKey($key);

        // Record last updated
        $lastUpdatedKey = $this->getCacheLastUpdatedKey();
        Cache::forever($lastUpdatedKey, now()->timestamp);

        return Cache::forever($fullkey, $value);
    }

    public function getCacheLastUpdatedKey()
    {
        return $this->getCacheFullKey('CACHE_LAST_UPDATED');
    }

    public function getCacheLastUpdatedTime()
    {
        $lastUpdatedKey = $this->getCacheLastUpdatedKey();
        $time = Cache::get($lastUpdatedKey);

        return (is_null($time)) ? null : new Carbon((int)$time);
    }

    public function readCache($key, $default = null)
    {
        $fullkey = $this->getCacheFullKey($key);
        return Cache::get($fullkey, $default);
    }

    public function forgetCache($key)
    {
        $fullkey = $this->getCacheFullKey($key);
        return Cache::forget($fullkey);
    }

    // Helper functions
    public function updateCache($cacheKey = null)
    {
        $cacheIndex = $this->getCacheIndex();

        if (!is_null($cacheKey)) {
            $allKeys = array_keys($cacheIndex);

            if (!in_array($cacheKey, $allKeys)) {
                throw new \Exception(sprintf("Invalid cache key: %s", $cacheKey));
            }
        }

        foreach ($cacheIndex as $key => $callback) {
            if (is_null($cacheKey)) {
                $this->putCache($key, $callback());
            } else {
                if ($cacheKey == $key) {
                    $this->putCache($key, $callback());
                }
            }
        }

        $this->updated_at = now();
        $this->save();
    }

    public function clearCache()
    {
        $cacheIndex = $this->getCacheIndex();
        foreach ($cacheIndex as $key => $callback) {
            $this->forgetCache($key, $callback());
        }
    }
}

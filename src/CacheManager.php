<?php

declare(strict_types=1);

namespace Stancl\Tenancy;

use Illuminate\Cache\CacheManager as BaseCacheManager;

class CacheManager extends BaseCacheManager
{
    /**
     * Add tags and forward the call to the inner cache store.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $store = $this->store();
        $supportsTags = method_exists($store, 'supportsTags') && $store->supportsTags();

        $tag = config('tenancy.cache.tag_base').tenant()->getTenantKey();

        if ($method === 'tags') {
            $count = count($parameters);
            
            if ($count !== 1) {
                throw new \Exception("Method tags() takes exactly 1 argument. {$count} passed.");
            }

            $names = (array) $parameters[0];

            if ($supportsTags) {
                return $store->tags(array_merge([$tag], $names));
            } else {
                throw new \BadMethodCallException('This cache store does not support tagging.');
            }
        }

        if ($supportsTags) {
            return $store->tags([$tag])->$method(...$parameters);
        }

        // 🛠 Manually prefix key if needed
        if (isset($parameters[0]) && is_string($parameters[0])) {
            $parameters[0] = $tag.':'.$parameters[0];
        }

        return $store->$method(...$parameters);
    }
}

<?php 

namespace ModXengine\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class TemplateCache{
    private CacheItemPoolInterface $cache;
    private string $namespace;
    private int $defaultTtl;

    /**
     * Constructor for TemplateCache.
     *
     * @param string $cacheDirectory Path to cache directory
     * @param string $namespace Cache namespace to avoid collisions
     * @param int $defaultTtl Default time-to-live in seconds
     */
    public function __construct(string $cacheDirectory, string $namespace = 'template', int $defaultTtl = 3600)
    {
        $this->cache = new FilesystemAdapter($namespace, $defaultTtl, $cacheDirectory);
        $this->namespace = $namespace;
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * Get cached template output or render and cache if not exists.
     *
     * @param string $templateName Unique identifier for the template
     * @param callable $renderCallback Callback to render the template if cache miss
     * @param array $context Data used to generate cache key and render template
     * @param int|null $ttl Custom TTL for this cache entry, null for default
     * @return string Rendered template content
     */
    public function getCachedTemplate(string $templateName, callable $renderCallback, array $context = [], ?int $ttl = null): string
    {
        $cacheKey = $this->generateCacheKey($templateName, $context);

        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $content = $renderCallback($context);
        $cacheItem->set($content);
        $cacheItem->expiresAfter($ttl ?? $this->defaultTtl);
        $this->cache->save($cacheItem);

        return $content;
    }

    /**
     * Clear cache for a specific template or all templates.
     *
     * @param string|null $templateName Template to clear, null to clear all
     * @return bool True on success
     */
    public function clearCache(?string $templateName = null): bool
    {
        if ($templateName === null) {
            return $this->cache->clear();
        }

        // Invalidate all cache keys related to the template
        // FilesystemAdapter doesn't support tags, so we rely on key prefix
        // For more complex use cases, consider TagAwareAdapter
        return $this->cache->deleteItems(
            array_map(
                fn($key) => $this->generateCacheKey($templateName, ['key' => $key]),
                range(0, 1000) // Adjust range based on expected context variations
            )
        );
    }

    /**
     * Generate a unique cache key based on template name and context.
     *
     * @param string $templateName Template identifier
     * @param array $context Contextual data for cache key
     * @return string Unique cache key
     */
    private function generateCacheKey(string $templateName, array $context): string
    {
        $contextHash = md5(json_encode($context));
        return str_replace(['{', '}', ':'], '_', "{$this->namespace}_{$templateName}_{$contextHash}");
    }
}
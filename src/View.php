<?php

namespace ModXengine;

use ModXengine\Cache\TemplateCache;
use ModXengine\Environment\Environment;
use ModXengine\Environment\FromArray;

/**
 * View class provides a user-friendly interface for rendering templates using ModXEngine.
 * It handles template data, layout configuration, and caching, serving as a facade to the core engine.
 */
class View
{
    /** @var ModXEngine The core template engine instance */
    private ModXEngine $engine;

    /** @var array Data to be passed to the template */
    private array $data = [];

    /** @var string|null The layout to be used for rendering */
    private ?string $layout = null;

    /**
     * Constructor for the View class.
     * Initializes the template engine with environment and cache settings.
     *
     * @param Environment|null $environment Template environment, defaults to FromArray if null
     * @param string $cacheDir Directory for storing cache files
     * @param string $layoutDir Directory name for layout files (default: 'layouts')
     * @param string $componentDir Directory name for component files (default: 'components')
     * @param string $cacheNamespace Cache namespace to avoid collisions (default: 'template')
     * @param int $cacheTtl Default cache time-to-live in seconds (default: 3600)
     * @param string|null $rootPath Base directory for resolving relative paths (optional, defaults to getcwd())
     * @param bool $createPath Whether to create template directories if they don't exist (default: false)
     */
    public function __construct(
        ?Environment $environment = null,
        string $cacheDir,
        string $layoutDir = 'layouts',
        string $componentDir = 'components',
        string $cacheNamespace = 'template',
        int $cacheTtl = 3600,
        ?string $rootPath = null,
        bool $createPath = false
    ) {
        // Use provided environment or create a default FromArray environment
        if ($environment === null) {
            $environment = FromArray::templatePath(
                [''], // Base template directory
                $rootPath,
                $createPath
            );
        }

        // Initialize template cache
        $templateCache = new TemplateCache($cacheDir, $cacheNamespace, $cacheTtl);

        // Set up the core engine with environment and cache
        $this->engine = new ModXEngine($environment, $templateCache, $layoutDir, $componentDir);
    }

    /**
     * Sets a key-value pair to be passed to the template.
     * Allows method chaining.
     *
     * @param string $key The variable name
     * @param mixed $value The variable value
     * @return self
     */
    public function with(string $key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

   
    /**
     * Sets the layout to be used for rendering.
     * Allows method chaining.
     *
     * @param string $layout The layout name (without .modx extension)
     * @return self
     */
    public function layout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * Renders the specified template with the provided data and optional cache TTL.
     *
     * @param string $template The template name (without .modx extension)
     * @param int|null $ttl Custom cache TTL in seconds, null for default
     * @return string The rendered template output
     * @throws \Exception If rendering fails or output is empty
     */
    public function render(string $template, ?int $ttl = null): string
    {
        // Pass all data to the engine
        foreach ($this->data as $key => $value) {
            $this->engine->set($key, $value);
        }

        // Set layout if specified
        if ($this->layout) {
            $this->engine->layout($this->layout);
        }

        // Render and return the template
        return $this->engine->render($template, $ttl);
    }

    /**
     * Clears the cache for a specific template or all templates.
     *
     * @param string|null $template Template name to clear, null to clear all
     * @return bool True on success
     */
    public function clearCache(?string $template = null): bool
    {
        return $this->engine->clearCache($template);
    }
}
<?php

namespace ModXengine\Environment;

use Exception;
use ModXengine\Environment\Environment;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * Class TemplateLoader
 *
 * Manages template file paths, allowing the addition and validation
 * of directories where templates are stored. Implements the Environment interface to integrate
 * with the broader templating system.
 *
 * This class provides functionality to:
 * - Set a root path for template directories
 * - Add and validate template directories
 * - Optionally create directories if they don't exist
 * - Normalize paths to ensure consistency across operating systems
 *
 * @package ModXengine\Environment
 */
class TemplateLoader implements Environment
{
    /**
     * @var array List of normalized paths where template files are located
     */
    protected array $paths = [];

    /**
     * @var string The root directory path for resolving relative template paths
     */
    protected string $rootPath;

    /**
     * @var Filesystem Symfony Filesystem component for directory operations
     */
    protected static Filesystem $fileSystem;

    /**
     * Creates a new TemplateLoader instance and initializes it with a template path.
     *
     * This static factory method sets up the environment by:
     * - Initializing the Filesystem component
     * - Setting the root path (defaults to current working directory if not provided)
     * - Validating or creating the specified template path
     * - Adding the normalized path to the paths array
     *
     * @param string $path Relative or absolute path to the template directory
     * @param string|null $rootPath Base directory for resolving relative paths (optional, defaults to getcwd())
     * @param bool $create Whether to create the directory if it doesn't exist
     * @return Environment Returns a configured TemplateLoader instance
     * @throws Exception If the path is invalid and $create is false
     */
    public static function templatePath(string $path, ?string $rootPath = null, bool $create = false): Environment
    {
        // Instantiate a new TemplateLoader object
        $environment = new self;

        // Initialize the Symfony Filesystem component for directory operations
        self::$fileSystem = new Filesystem();

        // Set the root path, defaulting to the current working directory if not provided
        // Append a directory separator for consistent path construction
        $environment->rootPath = ($rootPath ?? getcwd()) . \DIRECTORY_SEPARATOR;

        // If a root path is provided, attempt to resolve it to a real path
        // This ensures the path exists and is absolute
        if (null !== $rootPath && false !== ($realPath = realpath($rootPath))) {
            $environment->rootPath = $realPath . \DIRECTORY_SEPARATOR;
        }

        // Check if the specified template path exists relative to the root path
        if ($environment->checkPath($path)) {
            // If the path exists, normalize and add it to the paths array
            array_push($environment->paths, Path::normalize($environment->rootPath.$path.'/'));
            return $environment;
        } elseif ($create) {
            // If the path doesn't exist and $create is true, create the directory
            self::$fileSystem->mkdir(
                Path::normalize($environment->rootPath.$path)
            );
            // Add the normalized path to the paths array
            array_push($environment->paths, Path::normalize($environment->rootPath.$path.'/'));
            return $environment;
        } else {
            // If the path doesn't exist and $create is false, throw an exception
            throw new Exception("Path {$path} Is Not Found, Are You Sure It Exists");
        }
    }

    /**
     * Adds an additional template directory to the paths array.
     *
     * Validates the provided path relative to the root path and adds it to the list
     * of template directories if it exists.
     *
     * @param string $path Relative or absolute path to the template directory
     * @return Environment Returns the TemplateLoader instance for method chaining
     * @throws Exception If the path is invalid
     */
    public function addPath(string $path): Environment
    {
        // Validate the path relative to the root path
        if (!$this->checkPath($path)) {
            // Throw an exception if the path doesn't exist
            throw new Exception("Path {$path} Is Not Found, Are You Sure It Exists");
        }

        // Normalize the path and append a directory separator, then add to paths array
        array_push($this->paths, Path::normalize($this->rootPath.$path.'/'));

        // Return the instance to allow method chaining
        return $this;
    }

    /**
     * Checks if a given path exists as a directory.
     *
     * Normalizes the path relative to the root path and verifies it is a valid directory.
     *
     * @param string $path Relative or absolute path to check
     * @return bool True if the path is a valid directory, false otherwise
     */
    public function checkPath($path): bool
    {
        // Normalize the full path (root path + provided path) and check if it’s a directory
        return is_dir(Path::normalize($this->rootPath.$path));
    }

    public function getPaths():array
    {
        return $this->paths;
    }
}
<?php

namespace ModXengine\Environment;

use Exception;
use ModXengine\Environment\Environment;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * Class FromArray
 *
 * Manages multiple template file paths, allowing the addition and validation
 * of directories where templates are stored as arrays. Implements the Environment interface to integrate
 * with the broader templating system.
 *
 * This class provides functionality to:
 * - Initialize with an array of template directories
 * - Set a root path for resolving relative paths
 * - Validate or create template directories
 * - Normalize paths for consistency across operating systems
 *
 * @package ModXengine\Environment
 */
class FromArray implements Environment
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
     * Creates a new FromArray instance and initializes it with multiple template paths.
     *
     * This static factory method sets up the environment by:
     * - Initializing the Filesystem component
     * - Setting the root path (defaults to current working directory if not provided)
     * - Validating or creating each specified template path
     * - Adding normalized paths to the paths array
     *
     * @param array $paths Array of relative or absolute paths to template directories
     * @param string|null $rootPath Base directory for resolving relative paths (optional, defaults to getcwd())
     * @param bool $create Whether to create directories if they don't exist
     * @return Environment Returns a configured FromArray instance
     * @throws Exception If any path is invalid and $create is false
     */
    public static function templatePath(array $paths, ?string $rootPath = null, bool $create = false): Environment
    {
        // Instantiate a new FromArray object
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

        // Process each provided path
        foreach ($paths as $path) {
            // Check if the specified template path exists relative to the root path
            if ($environment->checkPath($path)) {
                // If the path exists, normalize and add it to the paths array
                array_push($environment->paths, Path::normalize($environment->rootPath . $path));
            } elseif ($create) {
                // If the path doesn't exist and $create is true, create the directory
                self::$fileSystem->mkdir(
                    Path::normalize($environment->rootPath . $path)
                );
                // Add the normalized path to the paths array
                array_push($environment->paths, Path::normalize($environment->rootPath . $path));
            } else {
                // If the path doesn't exist and $create is false, throw an exception
                throw new Exception("Path {$path} Is Not Found, Are You Sure It Exists");
            }
        }

        // Return the configured environment after processing all paths
        return $environment;
    }

    /**
     * Adds an additional template directory to the paths array.
     *
     * Validates the provided path relative to the root path and adds it to the list
     * of template directories if it exists.
     *
     * @param string $path Relative or absolute path to the template directory
     * @return Environment Returns the FromArray instance for method chaining
     * @throws Exception If the path is invalid
     */
    public function addPath(string $path, bool $create = false): Environment
    {
        // Validate the path relative to the root path
        if (!$this->checkPath($path)) {
            // Throw an exception if the path doesn't exist
            throw new Exception("Path {$path} Is Not Found, Are You Sure It Exists");
        }

        // Normalize the path and add to paths array
        array_push($this->paths, Path::normalize($this->rootPath . $path));

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
        return is_dir(Path::normalize($this->rootPath . $path));
    }

    public function getPaths():array
    {
        return $this->paths;
    }
}
<?php

declare(strict_types=1);

// global namespace

use orange\framework\interfaces\DataInterface;

// namespace global

/**
 * Collection of helpers that load and execute view plugin templates.
 */
class fig
{
    /**
     * Shared data object that exposes values to fig plugin templates.
     */
    public static DataInterface $data;

    /**
     * Priority levels available to fig plugins when ordering execution.
     */
    public const PRIORITY_LOWEST = 10;
    public const PRIORITY_LOW = 20;
    public const PRIORITY_NORMAL = 50;
    public const PRIORITY_HIGH = 80;
    public const PRIORITY_HIGHEST = 90;

    public const BEFORE = -1;
    public const NORMAL = 0;
    public const AFTER = 1;
    public const PREPEND = -1;
    public const APPEND = 1;

    /**
     * Absolute directory paths searched for fig plugin files.
     *
     * @var array<int, string>
     */
    protected static $pluginPaths = [];
    /**
     * Cached map of plugin names to resolved absolute file paths.
     *
     * @var array<string, string>
     */
    protected static $loadedPlugins = [];

    /**
     * Configure fig with directories and shared view data.
     *
     * @param array<string, mixed> $configFig   Application fig configuration.
     * @param DataInterface        $data        Data provider exposed to plugins.
     *
     * @return void
     */
    public static function configure(array $configFig, DataInterface $data)
    {
        logMsg('INFO', __METHOD__);

        require_once __DIR__ . '/FigException.php';

        // share the data interface so plugins can access view state
        static::$data = $data;

        // add our local fig path
        fig::addPath(__DIR__ . '/figs');

        // an array of additional fig plugin locations
        if (isset($configFig['plugins directories']) && is_array($configFig['plugins directories'])) {
            fig::addPaths($configFig['plugins directories']);
        }
    }

    /**
     * Register a single plugin search path.
     *
     * @param string $path  Absolute directory containing plugin files.
     * @param bool   $first When true, prioritise this path ahead of others.
     *
     * @return void
     */
    public static function addPath(string $path, bool $first = false): void
    {
        logMsg('INFO', __METHOD__);

        $path = rtrim($path, DIRECTORY_SEPARATOR);

        if ($first) {
            // add to beginning of search array
            array_unshift(static::$pluginPaths, $path);
        } else {
            // append to the end of search array
            array_push(static::$pluginPaths, $path);
        }
    }

    /**
     * Register multiple plugin search paths.
     *
     * @param array<int, string> $paths List of absolute directories.
     * @param bool               $first Prepend paths when true, append otherwise.
     *
     * @return void
     */
    public static function addPaths(array $paths, bool $first = false): void
    {
        foreach ($paths as $path) {
            static::addPath($path, $first);
        }
    }

    /**
     * Set the resolved plugin map, primarily used by tests or bootstrap.
     *
     * @param array<string, string> $absPaths Map of plugin names to absolute paths.
     *
     * @return void
     */
    public static function setPlugins(array $absPaths): void
    {
        static::$loadedPlugins = $absPaths;
    }

    /**
     * Allow dynamic access to fig plugins via static method calls.
     *
     * @param string       $name      Plugin name without `fig_` prefix.
     * @param array<mixed> $arguments Arguments forwarded to the plugin function.
     *
     * @throws FigException When the plugin cannot be resolved.
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        logMsg('INFO', __METHOD__ . ' ' . $name);

        $functionName = 'fig_' . $name;

        // throws exception if not found
        $fullpath = static::findPlugIn($functionName);

        include_once $fullpath;

        return call_user_func_array($functionName, $arguments);
    }

    /**
     * Resolve a fig plugin to an absolute path, loading it if necessary.
     *
     * @param string $name Plugin name including the `fig_` prefix.
     *
     * @throws FigException When the plugin file cannot be found.
     *
     * @return string
     */
    protected static function findPlugIn(string $name): string
    {
        $name = strtolower($name);

        if (!isset(static::$loadedPlugins[$name])) {
            foreach (static::$pluginPaths as $path) {
                $fullpath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name . '.php';

                if (file_exists($fullpath)) {
                    static::$loadedPlugins[$name] = $fullpath;

                    break;
                }
            }

            // was it loaded?
            if (!isset(static::$loadedPlugins[$name])) {
                throw new FigException('Could not locate fig plugin "' . $name . '".');
            }
        }

        return static::$loadedPlugins[$name];
    }
}

<?php

namespace WMC\Composer\Utils\ConfigFile;

use Composer\IO\IOInterface;
use Composer\IO\NullIO;

class ConfigMerger
{
    /**
     * @var array|null
     */
    protected $envMap = null;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var boolean
     */
    protected $keepOutdatedParams = false;

    /**
     * @var string
     */
    protected $name = null;

    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }
    
    /**
     * Process current values, environment variables and interactive console (if relevant),
     * to return an updated set of values
     *
     * Variables specified only in $expected will be processed in order to retrieve a value from the environment and/or interactive IO.
     * Variables specified only in $current will be discarded if keepOutdatedParams is not configured, kept as-is otherwise.
     * Variables specified in both $current and $expected will be kept as in $current.
     *
     * @param array $expected A FLAT array of expected values
     * @param array $current  A FLAT array of current  values
     */
    public function updateParams(array $expected, array $current = [])
    {
        if (!$this->keepOutdatedParams) {
            // Remove outdated params
            $current = array_intersect_key($current, $expected);
        }

        // Variables requiring processing (Values only in $expected)
        $missing = array_diff_key($expected, $current);

        // Process variables
        $missing = $this->process($missing);

        return $current + $missing;
    }

    protected function process(array $missing)
    {
        $missing = $this->processEnv($missing);
        $missing = $this->processIO($missing);

        return $missing;
    }
    
    /**
     * Variables for which a non-empty environment variable provided,
     * will be replaced.
     *
     * @param array $missing
     */
    protected function processEnv(array $missing)
    {
        foreach ($this->getEnvMapForParams(array_keys($missing)) as $key => $envKey) {
            if ($value = getenv($envKey)) {
                $missing[$key] = $value;
            }
        }

        return $missing;
    }

    /**
     * Prompt for missing values. If IO isn't interactive, use all defaults.
     *
     * @param array $missing List 
     * @param boolean $isStarted Shared state if the header was already outputed.
     * @return array
     */
    protected function processIO(array $missing)
    {
        // Simply use the expectedParams value as default for the missing params.
        if (!$this->io->isInteractive()) {
            return $missing;
        }

        $isStarted = false;
        
        foreach ($missing as $key => $default) {
            if (!$isStarted) {
                $isStarted = true;
                $this->io->write(sprintf('<comment>Some %s parameters are missing. Please provide them.</comment>', $this->name));
            }

            $default = $this->convertValueToInteractiveString($default);
            $value = $this->io->ask(sprintf('<question>%s</question> (<comment>%s</comment>): ', $key, $default), $default);

            $missing[$key] = $this->convertInteractiveStringToValue($value);
        }

        return $missing;
    }

    /**
     * Get a map of ['param_name' => 'ENV_NAME']
     *
     * @param  array $missingKeys
     * @return array
     */
    protected function getEnvMapForParams(array $missingKeys)
    {
        if (is_array($this->envMap)) {
            return $this->envMap;
        }

        $envMap = array();
        $prefix = $this->name ? strtoupper($this->name).'_' : '';

        foreach ($missingKeys as $key) {
            $envMap[$key] = $prefix.strtoupper($key);
        }

        return $envMap;
    }

    public function setEnvMap(array $envMap = null)
    {
        $this->envMap = $envMap;

        return $this;
    }

    public function getEnvMap()
    {
        return $this->envMap;
    }
    
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setKeepOutdatedParams($keepOutdatedParams)
    {
        $this->keepOutdatedParams = $keepOutdatedParams;

        return $this;
    }

    public function getKeepOutdatedParams()
    {
        return $this->keepOutdatedParams;
    }

    /**
     * Convert a single value to a string representation
     * Used for command-line input
     *
     * THIS IS NOT PART OF THE PUBLIC API.
     *
     * @param mixed $value
     * @return string
     */
    public function convertValueToInteractiveString($value)
    {
        return json_encode($value);
    }

    /**
     * Parse a single value
     * Used for command-line input
     * Uses ini format by default
     *
     * THIS IS NOT PART OF THE PUBLIC API.
     *
     * @param  string $value
     * @return mixed
     */
    public function convertInteractiveStringToValue($string)
    {
        assert('is_string($string)', gettype($string).' given');

        $value = json_decode($string, true, 1, JSON_BIGINT_AS_STRING);

        return JSON_ERROR_NONE !== json_last_error()
            ? $string
            : $value;
    }
}

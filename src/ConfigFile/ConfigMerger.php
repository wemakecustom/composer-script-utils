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
     * The processing is done recursively
     *
     * @param array $expected An array of expected values
     * @param array $current  An array of current  values
     */
    public function updateParams(array $expected, array $current = array())
    {
        if (!$this->keepOutdatedParams) {
            $current = $this->removeOutdatedParams($current, $expected);
        }

        $missing = $this->getMissingVariables($expected, $current);

        // Process variables
        $missing = $this->process($missing);

        return array_replace_recursive($current, $missing);
    }

    protected function process(array $missing)
    {
        $missing = $this->processEnv($missing);
        $missing = $this->processIO($missing);

        return $missing;
    }
    
    protected function removeOutdatedParams($current, $expected)
    {
        $clean = array_intersect_key($current, $expected);

        foreach ($clean as $k => &$v) {
            if (is_array($v) && is_array($expected[$k])) {
                $v = $this->removeOutdatedParams($v, $expected[$k]);
            }
        }

        return $clean;
    }

    protected function getMissingVariables($expected, $current)
    {
        $missing = array_diff_key($expected, $current);

        foreach (array_intersect_key($current, $expected) as $key => $value) {
            if (!is_array($expected[$key])) {
                continue;
            }

            $missing[$key] = is_array($value) ? $this->getMissingVariables($expected[$key], $value) : $expected[$key];
        }

        return $missing;
    }

    /**
     * Variables for which a non-empty environment variable provided,
     * will be replaced.
     *
     * @param array $missing
     */
    protected function processEnv(array $missing, $prefix = '')
    {
        foreach ($missing as $key => &$value) {
            if (is_array($value)) {
                $value = $this->processEnv($value, $prefix.$key.'.');
                continue;
            }

            if ($envValue = $this->getEnvValueFor($prefix.$key)) {
                $value = $envValue;
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
    protected function processIO(array $missing, &$isStarted = false, $prefix = '')
    {
        // Simply use the expectedParams value as default for the missing params.
        if (!$this->io->isInteractive()) {
            return $missing;
        }

        foreach ($missing as $key => $default) {
            $fqk = $prefix.$key;

            if (is_array($default)) {
                $missing[$key] = $this->processIO($default, $isStarted, $fqk.'.');
                continue;
            }

            if (!$isStarted) {
                $isStarted = true;
                $this->io->write(sprintf('<comment>Some %s parameters are missing. Please provide them.</comment>', $this->name));
            }

            $missing[$key] = $this->askIO($fqk, $default);
        }

        return $missing;
    }

    protected function askIO($key, $default)
    {
        $default = $this->convertValueToInteractiveString($default);
        $value = $this->io->ask(sprintf('<question>%s</question> (<comment>%s</comment>): ', $key, $default), $default);

        return $this->convertInteractiveStringToValue($value);
    }

    /**
     * @param  array $missingKeys
     * @return array
     */
    protected function getEnvValueFor($key)
    {
        if (!$envKey = $this->getEnvKeyFor($key)) {
            return null;
        }

        return getenv($envKey);
    }

    protected function getEnvKeyFor($key)
    {
        if (is_array($this->envMap)) {
            return isset($this->envMap[$key]) ? $this->envMap[$key] : null;
        }

        return ($this->name ? strtoupper($this->name).'_' : '')
             .strtoupper(str_replace('.', '_', $key));
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
        if (PHP_VERSION_ID < 50408) {
            assert('is_string($string)');
        } else {
            assert('is_string($string)', gettype($string).' given');
        }

        if (PHP_VERSION_ID < 50400) {
            $value = json_decode($string, true, 1);
        } else {
            $value = json_decode($string, true, 1, JSON_BIGINT_AS_STRING);
        }

        return JSON_ERROR_NONE !== json_last_error() && '' !== $string
            ? $string
            : $value;
    }
}

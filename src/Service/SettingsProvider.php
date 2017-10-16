<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 05/07/2017
 * Time: 16:04
 */
namespace Lou117\Core\Service;

use Lou117\Core\Exception\SettingsInvalidException;
use Lou117\Core\Exception\SettingsNotFoundException;

class SettingsProvider extends AbstractServiceProvider
{
    /**
     * @var array
     */
    protected $settings;


    /**
     * Hydrates settings with incoming settings array or settings filepath.
     * @param $settings_array_or_settings_filepath - an array or a string. If an array is provided, it will be used as
     * settings. If a string is provided, it will be considered as a filepath, and corresponding file will be loaded.
     * @return SettingsProvider
     * @throws SettingsNotFoundException - if a string is provided and doesn't match with an existing file.
     * @throws SettingsInvalidException - if settings are not provided as an array.
     */
    public function set($settings_array_or_settings_filepath): SettingsProvider
    {
        $settings = $settings_array_or_settings_filepath;
        if (is_string($settings)) {

            if (!file_exists($settings)) {

                throw new SettingsNotFoundException();

            }

            $settings = require ($settings);

        }

        if (!is_array($settings)) {

            throw new SettingsInvalidException();

        }

        $this->settings = $this->setDefaultValues($settings);
        return $this;
    }

    /**
     * @see AbstractServiceProvider::get()
     * @return array
     */
    public function get()
    {
        return $this->settings;
    }

    /**
     * Overrides default settings with incoming settings, ensuring that critical settings entries are always provided.
     * @param array $incoming_settings - incoming settings.
     * @return SettingsProvider
     */
    protected function setDefaultValues(array $incoming_settings): SettingsProvider
    {
        $this->settings = array_replace([
            "debugMode"     => false,
            "startSession"  => false,
            "uriPrefix"     => "",
            "logChannel"    => "core",
            "modules"       => []
        ], $incoming_settings);

        return $this;
    }
}

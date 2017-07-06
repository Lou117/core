<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 05/07/2017
 * Time: 16:04
 */
namespace Lou117\Core\Service;

use Lou117\Core\Exception\SettingsNotFoundException;

class SettingsProvider extends AbstractServiceProvider
{
    /**
     * @var array
     */
    protected $settings;


    public function __construct(array $services)
    {
        $filepath = 'config/settings.php';
        if (!file_exists($filepath)) {

            throw new SettingsNotFoundException();

        }

        $settings = require($filepath);
        $this->settings = is_array($settings) ? $settings : [];
    }

    /**
     * @see AbstractServiceProvider::get()
     * @return array
     */
    public function get()
    {
        return $this->settings;
    }
}

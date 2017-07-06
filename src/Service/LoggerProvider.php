<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 05/07/2017
 * Time: 16:14
 */
namespace Lou117\Core\Service;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class LoggerProvider extends AbstractServiceProvider
{
    /**
     * @var Logger
     */
    public $logger;


    public function __construct(array $services)
    {
        $settings = $services['core.settings']->get();

        $this->logger = new Logger($settings['logChannel']);
        $this->logger->pushHandler(new RotatingFileHandler('log/core', 10));

        if ($settings['debugMode']) {

            $this->logger->info('Debug mode activated');

        }
    }

    /**
     * @see AbstractServiceProvider::get()
     * @return Logger
     */
    public function get()
    {
        return $this->logger;
    }
}

<?php
/*
 * @package     Oasis Package
 * @subpackage  plg_console_oasis
 * @version     1.0
 * @author      Ever
 * @copyright   Copyright (c) 2023 Ever. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://sitever.ru/
 */

namespace Joomla\Plugin\Console\Oasis\Extension;

defined('_JEXEC') or die;

use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\CMS\Plugin\CMSPlugin;
use Oasiscatalog\Component\Oasis\Administrator\CliCommand\Command;
use Joomla\Event\SubscriberInterface;

class Oasis extends CMSPlugin implements SubscriberInterface
{
    use MVCFactoryAwareTrait;

    /**
     * Loads the application object.
     *
     * @var  \Joomla\CMS\Application\ConsoleApplication
     * @since  1.0
     */
    protected $app = null;

    /**
     * Loads the database object.
     *
     * @var  \Joomla\Database\DatabaseDriver
     * @since  1.0
     */
    protected $db = null;

    /**
     * Commands classes list array.
     *
     * @var array
     * @since 1.0
     */
    protected array $commands = [
        Command::class,
    ];

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     * @since   1.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ApplicationEvents::BEFORE_EXECUTE => 'registerCommands',
        ];
    }

    /**
     * Register CLI commands.
     *
     * @param ApplicationEvent $event Event object.
     * @since 1.0
     */
    public function registerCommands(ApplicationEvent $event)
    {
        foreach ($this->commands as $commandClass) {
            try {
                if (!class_exists($commandClass)) {
                    continue;
                }

                $command = new $commandClass();

                if (method_exists($command, 'setMVCFactory')) {
                    $command->setMVCFactory($this->getMVCFactory());
                }

                $this->app->addCommand($command);
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}
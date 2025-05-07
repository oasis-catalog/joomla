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

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Console\Oasis\Extension\Oasis;

return new class implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param Container $container The DI container.
     * @since   1.0
     */
    public function register(Container $container)
    {
        $container->registerServiceProvider(new MVCFactory('Oasiscatalog\\Component\\Oasis'));

        $container->set(PluginInterface::class,
            function (Container $container) {
                $config = (array)PluginHelper::getPlugin('console', 'oasis');
                $subject = $container->get(DispatcherInterface::class);
                $mvcFactory = $container->get(MVCFactoryInterface::class);

                $app = Factory::getApplication();
                $app->getLanguage()->load('com_oasis', JPATH_ADMINISTRATOR);
                $plugin = new Oasis($subject, $config);
                $plugin->setApplication($app);
                $plugin->setMVCFactory($mvcFactory);

                return $plugin;
            }
        );
    }
};

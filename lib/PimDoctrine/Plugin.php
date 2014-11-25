<?php
namespace PimDoctrine;

use Pimcore\API\Plugin as PluginLib;
use Pimcore\Config;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Events;
use PimDoctrine\Extension\TablePrefix;

class Plugin extends PluginLib\AbstractPlugin
    implements PluginLib\PluginInterface
{
    /**
     * @return bool|string
     */
    public static function install()
    {
        $path = self::getInstallPath();

        if (!is_dir($path)) {
            mkdir($path);
        }

        if (!file_exists(static::cliConfigFilePath())) {
            file_put_contents(
                static::cliConfigFilePath(), file_get_contents(
                    static::pluginPath() . '/data/config/cli-config.php.dist'
                )
            );
        }

        if (!is_dir($path = PIMCORE_WEBSITE_VAR . '/plugins/pimdoctrine')) {
            mkdir($path);
            $file = $path . '/pimdoctrine-config.php';
            if (!file_exists($file)) {
                file_put_contents(
                    $file, file_get_contents(
                        static::pluginPath()
                        . '/data/config/pimdoctrine-config.php.dist'
                    )
                );
            }
        }

        if (self::isInstalled()) {
            return "PimDoctrine Plugin successfully installed.";
        } else {
            return "PimDoctrine Plugin could not be installed";
        }
        return true;
    }

    /**
     * @return string
     */
    public static function getInstallPath()
    {
        return static::pluginPath() . '/data/installed';
    }

    /**
     * Get Plugin Path
     *
     * @return string
     */
    public static function pluginPath()
    {
        return PIMCORE_PLUGINS_PATH . '/PimDoctrine';
    }

    /**
     * @return string
     */
    public static function cliConfigFilePath()
    {
        return PIMCORE_DOCUMENT_ROOT . '/cli-config.php';
    }

    /**
     * @return bool
     */
    public static function isInstalled()
    {
        return is_dir(static::getInstallPath());
    }

    /**
     * @return string
     */
    public static function uninstall()
    {
        rmdir(self::getInstallPath());

        if (!self::isInstalled()) {
            return "PimDoctrine Plugin successfully uninstalled.";
        } else {
            return "PimDoctrine Plugin could not be uninstalled";
        }
    }

    /**
     * Init hook setup
     */
    public function init()
    {
        $this->_loadOnce();
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public static function getEntityManager()
    {
        return \Zend_Registry::get('doctrine.em');
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function _loadOnce()
    {
        if (!\Zend_Registry::isRegistered('doctrine.em')) {
            $isDevMode = \Pimcore::inDebugMode();
            $pluginConfig = $this->_loadConfiguration();
            $doctrineConfig = $pluginConfig['doctrine'];

            //Connection Settings
            if (!empty($doctrineConfig['connection'])) {
                $dbParams = $doctrineConfig['connection']['orm_default'];
            } else {
                $config = Config::getSystemConfig()->toArray();
                $params = $config['database']['params'];

                // the connection configuration
                $dbParams = [
                    'driver'   => strtolower($config['database']['adapter']),
                    'user'     => $params['username'],
                    'password' => $params['password'],
                    'dbname'   => $params['dbname'],
                ];
            }

            //Table Prefix
            $evm = new EventManager;
            if (!empty($doctrineConfig['table_prefix'])) {
                $tablePrefix = new TablePrefix($doctrineConfig['table_prefix']);
                $evm->addEventListener(Events::loadClassMetadata, $tablePrefix);
            }

            //Entity Paths
            $paths = ['website/models/Website/Entity/'];
            if (is_array($doctrineConfig['paths'])) {
                $paths = $doctrineConfig['paths'];
            }

            $setup = Setup::createAnnotationMetadataConfiguration(
                $paths, $isDevMode, null, null, false
            );

            $em = EntityManager::create(
                $dbParams, $setup, $evm
            );

            $em->getConnection()->getDatabasePlatform()
                ->registerDoctrineTypeMapping('enum', 'string');

            \Zend_Registry::set('doctrine.em', $em);
        }
    }

    /**
     * @throws \Exception
     */
    protected function _loadConfiguration()
    {
        $file
            =
            PIMCORE_WEBSITE_VAR . '/plugins/pimdoctrine/pimdoctrine-config.php';
        if (!file_exists($file)) {
            throw new \Exception('pimdoctrine config file not found');
        }
        return include $file;
    }
}
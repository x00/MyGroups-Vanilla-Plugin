<?php if (!defined('APPLICATION')) exit();

/**
 *    @@ MyGroupsUtilityDomain @@
 *
 *    Links Utility Worker to the worker collection
 *    and retrieves it. Auto initialising.
 *
 *    Provides a simple way for other workers, or
 *    the plugin file to call it method and access its
 *    properties.
 *
 *    It also is a special Domain that holds the Workers
 *    collection, and LinkWorker method.
 *
 *    Also can be used for abstract methods which need to
 *    be implemented by the plugin class e.g. PlugnSetup
 *
 *    A worker will reference the Utility work like so:
 *    $this->plgn->utility()
 *
 *    The plugin file can access it like so:
 *    $this->utility()
 *
 *    @abstract
 */

abstract class MyGroupsUtilityDomain extends Gdn_Plugin {

    /**
     * Holds a collection of Workers
     * @var array[string]class $workers
     */
    protected $workers = array();

    /**
     * The unique identifier to look up Worker
     * @var string $workerName
     */
    private $workerName = 'utility';

    // important to call parent constructor to
    // ensure design pattern is working with
    // Garden's pluggable interface.
    function __construct() {
        parent::__construct();
    }

    /**
     *    @@ utility @@
     *
     *    Utility Worker Domain address , 
     *    links and retrieves
     *
     *    @return void
     */

    public function utility() {
        $workerName = $this->workerName;
        $workerClass = $this->getPluginIndex() . $workerName;
        return $this->linkWorker($workerName, $workerClass);
    }

    /**
     *    @@ getUtility @@
     *
     *    This method is used to retrieve an external
     *    Plugin's Utility class instance.
     * 
     *    Could be used to make useful utility
     *    functions dryer.    
     * 
     *    e.g. $this->plgn->getUtility('UsefulUtility');
     *
     *    @param string $pluginIndex
     *
     *    @return class|false (false if not found)
     */
    public function getUtility($pluginIndex) {
        $declaredClasses = get_declared_classes();
        $return = false;
        foreach ($declaredClasses as $className) {
            if(Gdn::pluginManager()->getPluginInfo($className)) {
                $plugin = Gdn::pluginManager()->getPluginInstance($className);
                if($plugin->getPluginIndex()==$pluginIndex && 
                    method_exists($plugin , 'linkWorker') &&
                    method_exists($plugin , 'utility')) {
                        $return = $plugin->utility();
                        break;
                }
            }
        }
        return $return;
    }

    /**
     *    @@ linkWorker @@
     *
     *    This method is used by the domain class to
     *    Link the Worker to the worker group, and
     *    retrieve. Auto-initialises the class
     *
     *    @param string $workerName
     *    @param string $workerClass
     *    @param mixed args.* any extra params to be passed to worker constructor.
     *
     *    @return void
     */

    public function linkWorker($workerName , $workerClass) {
        if(GetValue($workerName, $this->workers))
            return $this->workers[$workerName];
        $args = func_get_args();
        switch(count($args)) {
            case 2;
                $worker = new $workerClass();
                break;
            case 3:
                $worker = new $workerClass($args[2]);
                break;
            case 4:
                $worker = new $workerClass($args[2] , $args[3]);
                break;
            case 5:
                $worker = new $workerClass($args[2] , $args[3] , $args[4]);
                break;
            default:
                $ref = new reflectionClass($workerClass);
                $worker = $ref->newInstanceArgs($args);
                break;
        }
        $worker->plgn = $this;
        return $this->workers[$workerName] = $worker;

    }

    /**
     *    @@ pluginSetup @@
     *
     *    Abstract method required for hotloading/setup
     *
     *    @return void
     */

    abstract public function pluginSetup();

}

/**
 *    @@ MyGroupsUtility @@
 *
 *    the worker provided utility methods , 
 *    and general useful stuff for plugin dev
 *
 */

class MyGroupsUtility {

    private static $loadMaps = array();


    /**
     *    @@ RegisterLoadMap @@
     *
     *    A simple way for registering Classes for auto-loading of class files
     *
     *    @param string $matches the class name pattern to match
     *    @param string $folder the folder name (can use sub-match substitution format)
     *    @param string $file the file name (can use sub-match substitution format)
     *    @param bool $lowercaseMatches default true emain they will be inserted in string lowercased
     *
     *    @return void
     */

    public static function registerLoadMap($match , $folder , $file , $lowercaseMatches=true) {
        self::$loadMaps[] = array(
            'match' => $match, 
            'molder' => $folder, 
            'file' => $file, 
            'lowercaseMatches' => $lowercaseMatches
        );
    }

    /**
     *    @@ loadMapParse @@
     *
     *    Used by Load to replace strings with
     *    sub-patterns from class name match
     *
     *    e.g. ${Matches[n]} where n is the
     *    sub-pattern
     *
     *    @param array[]string $matches
     *    @param string $str the string for parsing
     *
     *    @return string
     */

    private static function loadMapParse($matches , $str) {
            foreach ($matches As $matchI => $matchV) {
                $str = preg_replace('`\{?\$\{?matches\[' . $matchI.'\]\}?`' , $matchV , $str);
            }
            return $str;
    }

    /**
     *    @@ load @@
     *
     *    auto-load function which employs
     *    reg-exp pattern matching
     *
     *    @param string $class name of class
     *
     *    @return void
     */

    public static function load($class) {
        $maps = self::$loadMaps;
        foreach ($maps As $map) {
            $matches = array();

            if(preg_match($map['match'] , $class , $matches)) {

                if($map['lowercaseMatches'])
                    $matches = array_map('strtolower' , $matches);

                $map['folder'] = self::loadMapParse($matches , $map['folder']);
                $map['file'] = self::loadMapParse($matches , $map['file']);
                require_once(PATH_PLUGINS . DS . 'MyGroups'. ($map['folder'] ? DS . $map['folder']: '') . DS . $map['file']);
                break;
            }
        }
    }

    /**
     *    @@ initLoad @@
     *
     *    register auto-load function
     *
     *    @return void
     */

    public static function initLoad() {
        //ensure
        spl_autoload_register('MyGroupsUtility::load');
    }

    /**
     *    @@ HotLoad @@
     *
     *    Pluggable dispatcher
     *    e.g. public function PluginNameController_Test_create($sender) {}
     *
     *    Way to ensure any new db structure gets created
     *    And new setup is updated without re-enabling
     *
     * @param bool $force do regardless of version change (optional) default false
     *
     * @return void
     */

    public function hotLoad($force = false) {
        if (c('Plugins.' . $this->plgn->getPluginIndex() . '.Version') != $this->plgn->PluginInfo['Version'] || $force) {
            $this->plgn->pluginSetup();
            saveToConfig('Plugins.' . $this->plgn->getPluginIndex() . '.Version', $this->plgn->PluginInfo['Version']);
        }
    }

    /**
     *    @@ miniDispatcher @@
     *
     *    e.g. public function PluginNameController_Test_create($sender) {}
     *
     *    or
     *
     *    public function Controller_Test($sender) {}
     *
     *    Internally
     *
     *    @param string $sender current GDN_Controller
     *    @param string $controllerClass current pseudo-controller
     *    @param string $pluggablePrefix prefix for other plugins to use to add controller methods
     *    @param string $localPrefix local prefix to use to add controller methods
     *    @throws NotFoundException if no callable method found
     *
     *    @return mixed false on error or the callback method result.;
     */

    public function miniDispatcher($sender, $controllerClass = 'ui', $pluggablePrefix = null, $localPrefix = null) {
        $pluggablePrefix = ($pluggablePrefix ? $pluggablePrefix : $this->plgn->getPluginIndex() . $controllerClass) . 'Controller_';
        $localPrefix = ($localPrefix ? $localPrefix : $controllerClass) . 'Controller_';
        $sender->Form = new Gdn_Form();

        $plugin = $this;

        $controllerMethod = '';
        if(count($sender->RequestArgs)) {
            list($methodName) = $sender->RequestArgs;
                        
            if (preg_match('`^p[0-9]+$`', $methodName)) {
                $methodName = 'index';
            }
            
        }else{
            $methodName = 'index';
        }

        $declaredClasses = get_declared_classes();

        $tempControllerMethod = $localPrefix.$methodName;
        $controllerClass = is_object($controllerClass) ? $controllerClass : $plugin->plgn->$controllerClass();
        if (method_exists($controllerClass, $tempControllerMethod)) {
            $controllerMethod = $tempControllerMethod;
        }

        if(!$controllerMethod) {
            $tempControllerMethod = $pluggablePrefix . $methodName . '_create';

            foreach ($declaredClasses as $className) {
                if (Gdn::pluginManager()->getPluginInfo($className)) {
                    $currentPlugin = Gdn::pluginManager()->getPluginInstance($className);
                        if($currentPlugin && method_exists($currentPlugin, $tempControllerMethod)) {
                            $controllerClass = $currentPlugin;
                            $controllerMethod = $tempControllerMethod;
                            break;
                        }
                }
            }

        }
        if (method_exists($controllerClass, $controllerMethod)) {
            $sender->plgn = $plugin;
            return call_user_func(array($controllerClass, $controllerMethod) , $sender);            
        } else {
            throw notFoundException();
        }
    }


    /**
     *    @@ themeView @@
     *
     *    Set view that can be copied over to current theme
     *    e.g. view -> current_theme/views/plugins/PluginName/view.php
     *
     *    @param string $view name of file minus the .php
     *
     *    @return string
     */

    public function themeView($view) {
        $themeViewLoc = combinePaths(array(
            PATH_THEMES, Gdn::controller()->Theme, 'views', $this->plgn->getPluginFolder(false)
        ));
        if(file_exists($themeViewLoc . DS . $view . '.php')) {
            $view=$themeViewLoc . DS . $view . '.php';
        }else{
            $view=$this->plgn->getView($view . '.php');
        }


        return $view;

    }

    /**
     *    @@ dynamicRoute @@
     *
     *    Add a route on the fly
     *
     *    Typically set in Base_BeforeLoadRoutes_Handler
     *
     *    @param string $routes loaded
     *    @param string $route RegExp of route
     *    @param string $destination to route to
     *    @param string $type of redirect (optional), default 'Internal' options Internal , Temporary , Permanent , NotAuthorized , NotFound
     *    @param bool $oneWay if an Internal request prevents direct access to destination    (optional), default false
     *
     *    @return void
     */

    public function dynamicRoute(&$routes, $route, $destination, $type = 'Internal', $oneway = false) {
        $key = str_replace('_', '/', base64_encode($route));
        $routes[$key] = array($destination, $type);
        if($oneway && $type == 'Internal') {
            if(strtolower(Gdn::request()->path()) && strpos(strtolower($destination), strtolower(Gdn::request()->path()))===0) {
                Gdn::dispatcher()->dispatch('Default404');
                exit;
            }
        }
    }
}

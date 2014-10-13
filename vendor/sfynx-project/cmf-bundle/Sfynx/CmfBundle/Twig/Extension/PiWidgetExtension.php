<?php
/**
 * This file is part of the <Cmf> project.
 *
 * @subpackage   Admin
 * @package    Extension
 * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
 * @since 2012-01-11
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Sfynx\CmfBundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Sfynx\ToolBundle\Exception\EntityException;
use Sfynx\ToolBundle\Exception\ServiceException;
use Sfynx\CmfBundle\Entity\TranslationWidget;
use Sfynx\CmfBundle\Twig\TokenParser\StyleSheetWidgetTokenParser;

/**
 * Widget Matrix used in twig
 *
 * @subpackage   Admin
 * @package    Extension
 * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
 */
class PiWidgetExtension extends \Twig_Extension
{
    /**
     * Content de rendu du script.
     *
     * @static
     * @var int
     * @access  private
     */
    protected static $_content;    
    
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     * @access  protected
     */
    protected $container;
    
    /**
     * @var \Sfynx\CmfBundle\Manager\PiWidgetManager
     */
    protected $widgetManager;    
    
    /**
     * @var \Sfynx\CmfBundle\Manager\PiTransWidgetManager
     */
    protected $transWidgetManager;
    
    /**
     * @var \Sfynx\CmfBundle\Entity\TranslationWidget
     */
    protected $translationsWidget;    
    
    /**
     * @var service widget extension manager called
     */    
    protected $serviceWidget;
    
    /**
     * @var String Entity Name
     * @access  protected
     */
    protected $entity;

    /**
     * @var String Method Name
     * @access  protected
     */
    protected $method;    
    
    /**
     * @var String Action Name
     * @access  protected
     */
    protected $action;
    
    /**
     * @var int    id widget value
     */
    protected $id;    

    /**
     * @var string configXml widget value
     */    
    protected $configXml;

    /**
     * @var string service name
     */    
    private $service;
    
    /**
     * @var \Symfony\Component\Locale\Locale
     */
    protected $language;    
    
    /**
     * Return list of available widget plugins.
     *
     * @return array
     * @access public
     * @static
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @since 2011-12-28
     */
    public static function getAvailableWidgetPlugins()
    {
        return array(
            'content'       =>'Content',
            'gedmo'         =>'Gedmo',
            'search'        =>'Search',
            'user'          =>'User',
//             'tab'            =>'Tab',
        );
    }    
    
    /**
     * Return list of available widget plugins.
     *
     * @return array
     * @access public
     * @static
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @since 2011-12-28
     */
    public static function getDefaultConfigXml()
    {
        $source  =  "<?xml version=\"1.0\"?>\n";
        $source .=  "<config>\n";
        $source .=  "    <widgets>\n";
        
        /////////// CSS/JS APPLYED
        $source .=  "        <css>bundles/sfynxtemplate/css/default1.css</css>\n";        
        $source .=  "        <css>bundles/sfynxtemplate/css/default2.css</css>\n";
        $source .=  "        <js>bundles/sfynxtemplate/css/default1.js</js>\n";
        $source .=  "        <js>bundles/sfynxtemplate/css/default2.js</js>\n";
        
        /////////// USER WIDGET
        $source .=  "        <user>\n";
        $source .=  "            <controller>SfynxAuthBundle:User:_connexion_default</controller>\n";
        $source .=  "            <params>\n";
        $source .=  "                <template>FOSUserBundle:Security:login.html.twig</template>\n";
        $source .=  "                <referer_redirection>true</referer_redirection>\n";
        $source .=  "            </params>\n";
        $source .=  "        </user>\n";
                
        /////////// CONTENT WIDGET
        $source .=  "        <content>\n";
        // snippet parameters
        $source .=  "            <id></id>\n";
        $source .=  "            <snippet>false</snippet>\n";        
        // jquery extenstion parameters 
        $source .=  "            <controller>TWITTER:tweets_blog</controller>\n";
        $source .=  "            <params>\n";
        $source .=  "                <cachable>true</cachable>\n";
        $source .=  "                <template></template>\n"; 
        $source .=  "                <enabledonly>true</enabledonly>\n";
        $source .=  "            </params>\n";
        // media parameters
        $source .=  "            <media>\n";
        $source .=  "                <format>default_small</format>\n";
        $source .=  "                <align>right</align>\n";
        $source .=  "                <class>maclass</class>\n";
        $source .=  "                <link>MonImage</link>\n";
        $source .=  "            </media>\n";
        $source .=  "        </content>\n";
        
        /////////// SEARCH WIDGET
        $source .=  "        <search>\n";
        $source .=  "            <controller>LUCENE:search-lucene</controller>\n";
        $source .=  "            <params>\n";
        $source .=  "                <cachable>false</cachable>\n";
        $source .=  "                <template>searchlucene-result.html.twig</template>\n";
        $source .=  "                <MaxResults></MaxResults>\n";
        // lucene parameters
        $source .=  "                <lucene>\n";
        $source .=  "                    <action>renderDefault</action>\n";
        $source .=  "                    <menu>searchlucene</menu>\n";
        $source .=  "                    <searchBool>true</searchBool>\n";
        $source .=  "                    <searchBoolType>AND</searchBoolType>\n";
        $source .=  "                    <searchByMotif>true</searchByMotif>\n";
        $source .=  "                    <setMinPrefixLength>0</setMinPrefixLength>\n";
        $source .=  "                    <getResultSetLimit>0</getResultSetLimit>\n";
        $source .=  "                    <searchFields>\n";
        $source .=  "                        <sortField>Contents</sortField>\n";
        $source .=  "                        <sortType>SORT_STRING</sortType>\n";
        $source .=  "                        <sortOrder>SORT_ASC</sortOrder>\n";
        $source .=  "                    </searchFields>\n";
        $source .=  "                    <searchFields>\n";
        $source .=  "                        <sortField>Key</sortField>\n";
        $source .=  "                        <sortType>SORT_NUMERIC</sortType>\n";
        $source .=  "                        <sortOrder>SORT_DESC</sortOrder>\n";
        $source .=  "                    </searchFields>\n";
        $source .=  "                </lucene>\n";
        $source .=  "            </params>\n";
        $source .=  "        </search>\n";    
        
        /////////// GEDMO WIDGET
        $source .=  "        <gedmo>\n";
        // snippet parameters
        $source .=  "            <id></id>\n";
        $source .=  "            <snippet>false</snippet>\n";
        // navigation and organigram and slider common parameters                
        $source .=  "            <controller>PiAppGedmoBundle:Activity:_template_list</controller>\n";
        $source .=  "            <params>\n";
        $source .=  "                <node></node>\n";
        $source .=  "                <enabledonly>true</enabledonly>\n";
        $source .=  "                <category></category>\n";
        $source .=  "                <template></template>\n"; 
        $source .=  "                <cachable>true</cachable>\n";
        // navigation parameters        
        $source .=  "                <navigation>\n";
        $source .=  "                    <query_function>getAllTree</query_function>\n";
        $source .=  "                    <searchFields>\n";
        $source .=  "                          <nameField>field1</nameField>\n";
        $source .=  "                          <valueField>value1</valueField>\n";
        $source .=  "                    </searchFields>\n";
        $source .=  "                    <searchFields>\n";
        $source .=  "                          <nameField>field2</nameField>\n";
        $source .=  "                          <valueField>value2</valueField>\n";
        $source .=  "                    </searchFields>\n";        
        $source .=  "                    <separatorClass>separateur</separatorClass>\n";
        $source .=  "                    <separatorText><![CDATA[ &ndash; ]]></separatorText>\n";
        $source .=  "                    <separatorFirst>false</separatorFirst>\n";
        $source .=  "                    <separatorLast>false</separatorLast>\n";
        $source .=  "                    <ulClass>infoCaption</ulClass>\n";
        $source .=  "                    <liClass>menuContainer</liClass>\n";
        $source .=  "                    <counter>true</counter>\n";
        $source .=  "                    <routeActifMenu>\n";
        $source .=  "                        <liActiveClass></liActiveClass>\n";
        $source .=  "                        <liInactiveClass></liInactiveClass>\n";
        $source .=  "                        <aActiveClass></aActiveClass>\n";
        $source .=  "                        <aInactiveClass></aInactiveClass>\n";
        $source .=  "                        <enabledonly>true</enabledonly>\n";
        $source .=  "                    </routeActifMenu>\n";
        $source .=  "                    <lvlActifMenu>\n";
        $source .=  "                        <liActiveClass></liActiveClass>\n";
        $source .=  "                        <liInactiveClass></liInactiveClass>\n";
        $source .=  "                        <aActiveClass></aActiveClass>\n";
        $source .=  "                        <aInactiveClass></aInactiveClass>\n";
        $source .=  "                        <enabledonly>true</enabledonly>\n";
        $source .=  "                    </lvlActifMenu>\n";
        $source .=  "                </navigation>\n";
        // organigram parameters
        $source .=  "                <organigram>\n";
        $source .=  "                    <query_function>getAllTree</query_function>\n";
        $source .=  "                    <searchFields>\n";
        $source .=  "                          <nameField>field1</nameField>\n";
        $source .=  "                          <valueField>value1</valueField>\n";
        $source .=  "                    </searchFields>\n";
        $source .=  "                    <searchFields>\n";
        $source .=  "                          <nameField>field2</nameField>\n";
        $source .=  "                          <valueField>value2</valueField>\n";
        $source .=  "                    </searchFields>\n";        
        $source .=  "                    <params>\n";
        $source .=  "                        <action>renderDefault</action>\n";
        $source .=  "                        <menu>organigram</menu>\n";
        $source .=  "                        <id>orga</id>\n";
        $source .=  "                    </params>\n";
        $source .=  "                    <fields>\n";
        $source .=  "                        <field>\n";
        $source .=  "                            <content>title</content>\n";
        $source .=  "                            <class>pi_tree_desc</class>\n";
        $source .=  "                        </field>\n";
        $source .=  "                        <field>\n";
        $source .=  "                            <content>descriptif</content>\n";
        $source .=  "                        </field>\n";
        $source .=  "                    </fields>\n";
        $source .=  "                </organigram>\n";
        // slider parameters
        $source .=  "                <slider>\n";
        $source .=  "                    <query_function>getAllAdherents</query_function>\n";
        $source .=  "                    <searchFields>\n";
        $source .=  "                          <nameField>field1</nameField>\n";
        $source .=  "                          <valueField>value1</valueField>\n";
        $source .=  "                    </searchFields>\n";
        $source .=  "                    <searchFields>\n";
        $source .=  "                          <nameField>field2</nameField>\n";
        $source .=  "                          <valueField>value2</valueField>\n";
        $source .=  "                    </searchFields>\n";        
        $source .=  "                    <orderby_date></orderby_date>\n";
        $source .=  "                    <orderby_position>ASC</orderby_position>\n";
        $source .=  "                    <MaxResults>4</MaxResults>\n";
        $source .=  "                    <action>renderDefault</action>\n";
        $source .=  "                    <menu>entity</menu>\n";
        $source .=  "                    <id>flexslider</id>\n";
        $source .=  "                    <boucle_array>false</boucle_array>\n";
        $source .=  "                    <params>\n";
        $source .=  "                        <animation>slide</animation>\n";
        $source .=  "                        <direction>horizontal</slideDirection>\n";
        $source .=  "                        <slideshow>true</slideshow>\n";
        $source .=  "                        <redirection>false</redirection>\n";
        $source .=  "                        <startAt>0</slideToStart>\n";
        //$source .=  "                        <easing>swing</easing>\n";
        $source .=  "                        <slideshowSpeed>6000</slideshowSpeed>\n";
        $source .=  "                        <animationSpeed>800</animationDuration>\n";
        $source .=  "                        <directionNav>true</directionNav>\n";
        $source .=  "                        <pauseOnAction>false</pauseOnAction>\n";
        $source .=  "                        <pauseOnHover>true</pauseOnHover>\n";
        $source .=  "                        <pausePlay>true</pausePlay>\n";
        $source .=  "                        <controlNav>true</controlNav>\n";
        $source .=  "                        <minItems>1</minItems>\n";
        $source .=  "                        <maxItems>1</maxItems>\n";
        $source .=  "                    </params>\n";
        $source .=  "                </slider>\n";
        $source .=  "            </params>\n";
        $source .=  "        </gedmo>\n";
        
        $source .=  "    </widgets>\n";
        $source .=  "    <advanced>\n";
        $source .=  "        <roles>\n";
        $source .=  "            <role>ROLE_VISITOR</role>\n";
        $source .=  "            <role>ROLE_USER</role>\n";
        $source .=  "            <role>ROLE_ADMIN</role>\n";
        $source .=  "            <role>ROLE_SUPER_ADMIN</role>\n";
        $source .=  "        </roles>\n";
        $source .=  "    </advanced>\n";
        $source .=  "</config>\n";
        
        return $source;
    }    
    
    /**
     * Constructor.
     *
     * @param ContainerInterface $container  The service container
     * @param string             $container  Name of widget container.
     * @param string             $actionName Name of action.
     * 
     * @author (c) Etienne de Longeaux <etienne_delongeaux@hotmail.com>
     */
    public function __construct(ContainerInterface $containerService, $container = 'CONTENT', $action = 'text')
    {
        $this->container = $containerService;
        $this->language  = $this->container->get('request')->getLocale();
        //
        if (isset($GLOBALS['WIDGET'][strtoupper($container)][strtolower($action)])) {
            $this->action = $action;
        } 
//        else {
//            print_r($container);
//            print_r($action);
//            exit;
//            throw ServiceException::serviceNotConfiguredCorrectly();
//        }
    }    
    
    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     * @access public
     * @final
     * 
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    final public function getName()
    {
        return 'sfynx_cmf_widget_extension';
    }
    
    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     * @access public
     * @final
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    final public function getAction()
    {
        return $this->action;
    }    
    
    /**
     * Sets the method
     *
     * @return string The extension name
     * @access public
     * @final
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    final public function setMethod($method)
    {
        $this->method = $method;
    }    
        
    /**
     * Returns a list of functions to add to the existing list.
     *
     * <code>
     *  {% set options = {'widget-id': 1} %}
     *  {{ renderWidget('CONTENT', 'text', options )|raw }}
     * </code>
     *
     * @return array An array of functions
     * @access public
     * @final
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    final public function getFunctions()
    {
        return array(
                'renderWidget'      => new \Twig_Function_Method($this, 'FactoryFunction'),
                'renderJs'          => new \Twig_Function_Method($this, 'ScriptJsFunction'),
                'renderCss'         => new \Twig_Function_Method($this, 'ScriptCssFunction'),
                'renderCache'       => new \Twig_Function_Method($this, 'renderCacheFunction')
        );
    }
    
    /**
     * Returns the token parsers
     *
     * <code>
     *     {%  initWidget 'CONTENT:text' %} to execute the init method of the service.
     * </code>
     *
     * @return string The extension name
     * @access public
     * @final
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    final public function getTokenParsers()
    {
        return array(
                new StyleSheetWidgetTokenParser($this->getName()),
        );
    }    
    
    /**
     * Callbacks
     */
    
    /**
     * Factory ! We check that the requested class is a valid service.
     *
     * @param  string         $container            name of widget container.
     * @param  string         $actionName            name of action.
     * @param  array        $options            validator options.
     * @return service
     * @access public
     * @final
     * 
     * @author (c) Etienne de Longeaux <etienne_delongeaux@hotmail.com>
     */
    final public function FactoryFunction($container, $actionName, $options = null)
    {
        if ($this->isServiceSupported($container, $actionName)) {
            // Gestion des options
            if (!isset($options['widget-id']) || empty($options['widget-id'])) {
                throw ServiceException::optionValueNotSpecified('widget-id', __CLASS__);
            }
            if (!isset($options['widget-lang']) || empty($options['widget-lang'])) {
                throw ServiceException::optionValueNotSpecified('widget-lang', __CLASS__);
            }            
            // we set params
            $this->setParams($options);            
            $method = "render" . ucfirst($this->action);            
            //print_r($method);
            //print_r($this->getServiceWidget()->getAction());
            //print_r($this->action);
            //print_r($this->service);
            //print_r($container);
            //print_r($actionName);            
            if (method_exists($this->serviceWidget, $method)) {
                return $this->getServiceWidget()->$method($options);
            } elseif (method_exists($this->serviceWidget, 'render')) {
                return $this->getServiceWidget()->run($options);
            } else { 
                throw ServiceException::serviceMethodUnDefined($method);
            }
        }
    }
    
    /**
     * Sets the Widget translation and the id of the util widget service manager called.
     *
     * @return void
     * @access protected
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @since 2012-02-14
     */
    protected function setParams($options)
    {
        // we set the id widget.
        $this->getServiceWidget()->setId($options['widget-id']);
        // we get the widget manager
        $widgetManager  = $this->getServiceWidget()->getWidgetManager();
        // we get the widget entity
        $widget            = $this->getRepository()->findOneById($this->getServiceWidget()->getId(), 'Widget');    
        // we set the current widget entity
        if ($widget instanceof \Sfynx\CmfBundle\Entity\Widget) {
            $widgetManager->setCurrentWidget($widget);
            $this->getServiceWidget()->setConfigXml($widget->getConfigXml());
        } else {
            throw EntityException::IdEntityUnDefined($this->getServiceWidget()->getId());
        }    
        // we set the translation of the current widget
        $widgetTranslation = $widgetManager->getTranslationByWidgetId($widget->getId(), $options['widget-lang']);
        if ($widgetTranslation instanceof TranslationWidget) {
            $this->getServiceWidget()->setTranslationWidget($widgetTranslation);
        }
    }    
    

    /**
     * Put result content in cache with ttl.
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    public function renderCacheFunction($key, $ttl, $serviceName, $method, $id, $lang, $params)
    {
    	$dossier = $this->container->get('pi_app_admin.manager.page')->createCacheWidgetRepository();
    	$this->container->get("sfynx.cache.filecache")->getClient()->setPath($dossier);
    	$value = $this->container->get("sfynx.cache.filecache")->get($key);
    	if ( !$value ) {
    		$value = $this->container->get($serviceName)->$method($id, $lang, $params);
    		$this->container->get("sfynx.cache.filecache")->getClient()->setPath($dossier); // IMPORTANT if in the method of the service the path is overwrite.
    		// important : if ttl is equal to zero then the cache is infini
    		$this->container->get("sfynx.cache.filecache")->set($key, $value, $ttl);
    	}
    
    	return $value;
    }    
    
    /**
     * Factory ! We check that the requested class is a valid service.
     *
     * @param  string         $container            name of widget container.
     * @param  string         $actionName            name of action.
     * @param  array        $options            validator options.
     * @return service
     * @access public
     * @final
     *
     * @author (c) Etienne de Longeaux <etienne_delongeaux@hotmail.com>
     */
    final public function ScriptJsFunction($container, $actionName, $options = null)
    {
        if ($this->isServiceSupported($container, $actionName)) {
            if (method_exists($this->getServiceWidget(), 'scriptJs')) {
                return $this->getServiceWidget()->runJs($options);
            }
        }
    }
    
    /**
     * Factory ! We check that the requested class is a valid service.
     *
     * @param  string         $container            name of widget container.
     * @param  string         $actionName            name of action.
     * @param  array        $options            validator options.
     * @return service
     * @access public
     * @final
     *
     * @author (c) Etienne de Longeaux <etienne_delongeaux@hotmail.com>
     */
    final public function ScriptCssFunction($container, $actionName, $options = null)
    {
        if ($this->isServiceSupported($container, $actionName)) {
            if (method_exists($this->getServiceWidget(), 'scriptCss')) {
                return $this->getServiceWidget()->runCss($options);
            }
        }
    }    
    
    /**
     * execute the Widget service init method.
     *
     * @param  string         $InfoService    service information ex : "contenaireName:actionName"
     * @return void
     * @access public
     * @final
     * 
     * @author (c) Etienne de Longeaux <etienne_delongeaux@hotmail.com>
     */
    final public function initWidget($InfoService)
    {
        $method     = "";
        $infos      = explode(":", $InfoService);
        //
        if (count($infos) <=1) {
            throw ServiceException::serviceParameterUndefined($InfoService);
        }        
        $container  = $infos[0];
        $actionName = $infos[1];
        //
        if (!in_array($container, array('css', 'js'))) {
            if (count($infos) == 3) {
                $method    = $infos[2];
            }        
            if (count($infos) == 4) {
                $method    = $infos[2] . ":" . $infos[3];
            }     
            if ($this->isServiceSupported($container, $actionName)){
                if (method_exists($this->getServiceWidget(), 'init')){
                    $this->getServiceWidget()->setMethod($method);
                    $this->getServiceWidget()->init();
                }
            }
        } else {
            if ($container == "css") {
                $all_css = json_decode($actionName);
                foreach ($all_css as $path_css) {
                    $this->container->get('sfynx.tool.twig.extension.layouthead')->addCssFile($path_css, 'append');
                }
            } elseif ($container == "js") {
                $all_js = json_decode($actionName);
                foreach ($all_js as $path_js) {
                    $this->container->get('sfynx.tool.twig.extension.layouthead')->addJsFile($path_js, 'append');
                }
            }
        }
    }    

    /**
     * Sets the service and the action names and return true if the service is supported.
     *
     * @param  string         $container            name of widget container.
     * @param  string         $actionName            name of action.
     * 
     * @return boolean
     * @access private
     *
     * @author (c) Etienne de Longeaux <etienne_delongeaux@hotmail.com>
     */    
    private function isServiceSupported($container, $actionName)
    {
        if (!isset($GLOBALS['WIDGET'][strtoupper($container)][strtolower($actionName)])) {
            throw ServiceException::serviceGlobaleUndefined(strtolower($actionName), 'WIDGET');
        } elseif (!$this->container->has($GLOBALS['WIDGET'][strtoupper($container)][strtolower($actionName)])) {
            throw ServiceException::serviceNotSupported($GLOBALS['WIDGET'][strtoupper($container)][strtolower($actionName)]);
        }        
        $this->service   = $GLOBALS['WIDGET'][strtoupper($container)][strtolower($actionName)];
        $this->action    = strtolower($actionName);

        return true;
    }
    
    /**
     * Call the render function of the child class called by service.
     *
     * @return string
     * @access    public
     * @final
     *
     * @author (c) Etienne de Longeaux <etienne_delongeaux@hotmail.com>
     */
    final public function run($options = null)
    {
        try{
            return $this->render($options);
        } catch (\Exception $e) {
            throw ServiceException::serviceRenderUndefined('WIDGET');
        }
    }
    public function render($options = null) {}   
    
    /**
     * Call the render function of the child class called by service.
     *
     * @return string
     * @access public
     * @final
     *
     * @author (c) Etienne de Longeaux <etienne_delongeaux@hotmail.com>
     */
    final public function runJs($options = null)
    {
        try{
            return $this->scriptJs($options);
        } catch (\Exception $e) {
            throw ServiceException::serviceRenderUndefined('WIDGET');
        }
    }
    public function scriptJs($options = null) {
    }

    /**
     * Call the render function of the child class called by service.
     *
     * @return string
     * @access    public
     * @final
     *
     * @author (c) Etienne de Longeaux <etienne_delongeaux@hotmail.com>
     */
    final public function runCss($options = null)
    {
        try{
            return $this->scriptCss($options);
        } catch (\Exception $e) {
            throw ServiceException::serviceRenderUndefined('WIDGET');
        }
    }
    public function scriptCss($options = null) {
    } 
    
    
    /**
     * Sets the id widget.
     *
     * @param int $id    id widget
     * @return void
     * @access public
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    public function setId($id)
    {
        $this->id = $id;
    }   

    /**
     * Gets the id widget.
     *
     * @return id widget value
     * @access public
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Sets the ConfigXml widget.
     *
     * @param string $configXml        configXml widget
     * @return void
     * @access public
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    public function setConfigXml($configXml)
    {
        $this->configXml = $configXml;
    }
    
    /**
     * Gets the ConfigXml widget.
     *
     * @return ConfigXml widget value
     * @access public
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    public function getConfigXml()
    {
        return $this->configXml;
    }

    /**
     * Gets the language locale.
     *
     * @return language value
     * @access public
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    public function getLanguage()
    {
        return $this->language;
    }    

    /**
     * Sets the Widget manager service.
     *
     * @return void
     * @access protected
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @since 2012-02-14
     */
    protected function setWidgetManager()
    {
        $this->widgetManager = $this->container->get('pi_app_admin.manager.widget');
    }
    
    /**
     * Gets the Widget manager service
     *
     * @return \Sfynx\CmfBundle\Manager\PiWidgetManager
     * @access protected
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @since 2012-02-14
     */
    protected function getWidgetManager()
    {
        if (empty($this->widgetManager)) {
            $this->setWidgetManager();
        }
    
        return $this->widgetManager;
    } 
    
    /**
     * Sets the Translation Widget manager service.
     *
     * @return void
     * @access protected
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @since 2012-02-14
     */
    protected function setTransWidgetManager()
    {
        $this->transWidgetManager = $this->container->get('pi_app_admin.manager.transwidget');
    }
    
    /**
     * Gets the Translation Widget manager service
     *
     * @return \Sfynx\CmfBundle\Manager\PiTransWidgetManager
     * @access protected
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @since 2012-02-14
     */
    protected function getTransWidgetManager()
    {
        if (empty($this->transWidgetManager)) {
            $this->setTransWidgetManager();
        }
    
        return $this->transWidgetManager;
    }

    /**
     * Gets the container instance.
     *
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     * @access public
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    public function getContainer()
    {
        return $this->container;
    }    
    
    /**
     * Sets the repository service.
     *
     * @return void
     * @access protected
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @since 2012-02-14
     */
    protected function setRepository()
    {
        $this->repository = $this->container->get('pi_app_admin.repository');
    }
    
    /**
     * Gets the repository service.
     *
     * @return ObjectRepository
     * @access protected
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @since 2012-02-14
     */
    protected function getRepository()
    {
        if (empty($this->repository)) {
            $this->setRepository();
        }
    
        return $this->repository;
    }    
    
    /**
     * Sets the widget service.
     *
     * @return void
     * @access protected
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @since 2012-02-14
     */
    protected function setServiceWidget()
    {
        if (!empty($this->service) && $this->container->has($this->service)) {
            $this->serviceWidget = $this->container->get($this->service);
        }
    }
    
    /**
     * Gets the widget service.
     *
     * @return Widget service object
     * @access protected
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @since 2012-02-14
     */
    protected function getServiceWidget()
    {
        $this->setServiceWidget();
        
        return $this->serviceWidget;
    }   
   
    
    /**
     * Sets the Widget translation.
     *
     * @param \Sfynx\CmfBundle\Entity\TranslationWidget    $widgetTranslation
     * @return void
     * @access public
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @since 2012-02-14
     */    
    public function setTranslationWidget(TranslationWidget $widgetTranslation)
    {
        $this->translationsWidget = $widgetTranslation;
    }
    
    /**
     * Gets the Widget translation.
     *
     * @return \Sfynx\CmfBundle\Entity\TranslationWidget
     * @access public
     *
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @since 2012-02-14
     */
    public function getTranslationWidget()
    {
        if ($this->translationsWidget instanceof TranslationWidget) {
            return $this->translationsWidget;
        } else {
            return false;
        }
    }
    
    /**
     * Returns the render source of a tag by the twig cache service.
     *
     * @param string    $tag
     * @param string    $id
     * @param string    $lang
     * @param array     $params
     *
     * @return string    extension twig result
     * @access    protected
     *
     * @author Etienne de Longeaux <etienne_delongeaux@hotmail.com>
     * @since 2012-04-19
     */
    protected function renderCache($serviceName, $tag, $id, $lang, $params = null)
    {
        return $this->container->get('pi_app_admin.manager.widget')->renderCache($serviceName, $tag, $id, $lang, $params);
    }
    
    /**
     * Returns the render source of a service manager.
     *
     * @param string    $id
     * @param string    $lang
     * @param array     $params
     *
     * @return string    extension twig result
     * @access    protected
     *
     * @author Etienne de Longeaux <etienne_delongeaux@hotmail.com>
     * @since 2012-04-19
     */
    protected function renderService($serviceName, $id, $lang, $params = null)
    {
        return $this->container->get('pi_app_admin.manager.widget')->renderService($serviceName, $id, $lang, $params);
    } 

    /**
     * Returns the render source of a jquery extension.
     *
     * @param string    $JQcontainer
     * @param string    $id
     * @param string    $lang
     * @param array     $params
     *
     * @return string    extension twig result
     * @access    protected
     *
     * @author Etienne de Longeaux <etienne_delongeaux@hotmail.com>
     * @since 2012-06-01
     */
    protected function renderJquery($JQcontainer, $id, $lang, $params = null)
    {
        return $this->container->get('pi_app_admin.manager.widget')->renderJquery($JQcontainer, $id, $lang, $params);
    }  
}
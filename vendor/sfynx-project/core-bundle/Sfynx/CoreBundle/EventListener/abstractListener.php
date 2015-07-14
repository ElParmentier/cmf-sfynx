<?php
/**
 * This file is part of the <Core> project.
 *
 * @subpackage Core
 * @package    EventListener
 * @abstract
 * @author     Etienne de Longeaux <etienne.delongeaux@gmail.com>
 * @since      2011-01-30
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Sfynx\CoreBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Sfynx\AuthBundle\Entity\Role;

/**
 * abstract listener manager.
 * This event is called after an entity is constructed by the EntityManager.
 *
 * @subpackage Core
 * @package    EventListener
 * @abstract
 * @author     Etienne de Longeaux <etienne.delongeaux@gmail.com>
 */
abstract class abstractListener
{
    /**
     * @var array $associations
     */
    protected $associations = array();

    /**
     * @var array $discriminators
     */    
    protected $discriminators = array();

    /**
     * @var array $discriminatorColumns
     */    
    protected $discriminatorColumns = array();

    /**
     * @var array $inheritanceTypes
     */    
    protected $inheritanceTypes = array();

    /**
     * @var array $doctrine
     */    
    protected $doctrine = array();

    /**
     * @var array $indexes
     */    
    protected $indexes = array();

    /**
     * @var array $associations
     */    
    protected $uniques = array();
    
    /**
     * @var \Sfynx\CoreBundle\EventListener\EntitiesContainer
     */
    private $EntitiesContainer;
    
    /**
     * @var ContainerInterface
     */
    private $container;    

    /**
     * Constructor
     * 
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container         = $container;
        $this->EntitiesContainer = $container->get('sfynx.core.entities.listener');
        // Sets parameter template values.
        $this->setParams();
    }
    
    /**
     * Sets parameter values.
     *
     * @return void
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function setParams()
    {
    	if ($this->container->hasParameter('sfynx.core.permission.restriction_by_roles')) {
            $this->is_permission_restriction_by_roles                = $this->container->getParameter('sfynx.core.permission.restriction_by_roles');
    	} else {
            $this->is_permission_restriction_by_roles                = false;
    	}
    	if ($this->container->hasParameter('sfynx.core.permission.authorization.prepersist')) {
            $this->is_permission_authorization_prepersist_authorized  = $this->container->getParameter('sfynx.core.permission.authorization.prepersist');
    	} else {
            $this->is_permission_authorization_prepersist_authorized  = false;
    	}
    	if ($this->container->hasParameter('sfynx.core.permission.authorization.preupdate')) {
            $this->is_permission_authorization_preupdate_authorized   = $this->container->getParameter('sfynx.core.permission.authorization.preupdate');
    	} else {
            $this->is_permission_authorization_preupdate_authorized   = false;
    	}
    	if ($this->container->hasParameter('sfynx.core.permission.authorization.preremove')) {
            $this->is_permission_authorization_preremove_authorized   = $this->container->getParameter('sfynx.core.permission.authorization.preremove');
    	} else {
            $this->is_permission_authorization_preremove_authorized   = false;
    	}
    	if ($this->container->hasParameter('sfynx.core.permission.prohibition.preupdate')) {
            $this->is_permission_prohibition_preupdate_authorized     = $this->container->getParameter('sfynx.core.permission.prohibition.preupdate');
    	} else {
            $this->is_permission_prohibition_preupdate_authorized     = false;
    	}
    	if ($this->container->hasParameter('sfynx.core.permission.prohibition.preremove')) {
            $this->is_permission_prohibition_preremove_authorized     = $this->container->getParameter('sfynx.core.permission.prohibition.preremove');
    	} else {
            $this->is_permission_prohibition_preremove_authorized     = false;
    	}
    }    
    
    /**
     * Create the pi_routing table for the route management.
     *
     * @return ObjectRepository
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @since 2012-02-27
     */
    protected function _addRoutingTable(GenerateSchemaEventArgs $eventArgs)
    {
        $schema     = $eventArgs->getSchema();
        
        $table = $schema->createTable('pi_routing');
        $table->addColumn('id', 'integer')->setAutoincrement(true);
        //$table->addColumn('page_id', 'bigint')->setNotnull(false);
        $table->addColumn('route', 'string')->setNotnull(false);
        $table->addColumn('locales', 'array');
        $table->addColumn('defaults', 'array');
        $table->addColumn('requirements', 'array');
        $table->setPrimaryKey(array('id'), true);
        $table->addUniqueIndex(array('route'));
        //$table->addIndex(array('page_id'));
        //$table->addNamedForeignKeyConstraint('FK_ID_PAGE', 'pi_page', $localColumnNames = array('page_id'), $foreignColumnNames = array('id'));
    }    
    
    /**
     * prePersist default.
     * BE CAREFUL !!! this method has to be used in the last of your LifecycleEvent management.
     *
     * @param LifecycleEventArgs $eventArgs
     * @param boolean            $isAnonymousToken        true to activate the anonymous token control.
     * @param boolean            $isUsernamePasswordToken true to activate the user token control.
     * @param boolean            $isAllPermissions        true to enable full permission regardless of the user.
     * 
     * @return boolean
     * @access protected
     * @final
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    final protected function _prePersist(LifecycleEventArgs $eventArgs, $isAnonymousToken = true, $isUsernamePasswordToken = true, $isAllPermissions = false)
    {        
        $entity         = $eventArgs->getEntity();
        $entityManager  = $eventArgs->getEntityManager();
        $entity_name    = get_class($entity);
        //update updated_at field when method setUpdatedAt exists in entity object
        if (method_exists($entity, 'setUpdatedAt')) {
            // we modify the Update_at value
            if(!$entity->getUpdatedAt()) {
                $entity->setUpdatedAt(new \DateTime());
            }
        }
        //update created_at field when method setCreatedAt exists in entity object
        if (method_exists($entity, 'setCreatedAt')) {
            // we modify the Update_at value
            if(!$entity->getCreatedAt()) {
                $entity->setCreatedAt(new \DateTime());
            }
        }
        //update heritage field when method setHeritage exists in entity object
        if (method_exists($entity, 'setHeritage')
                && !($entity instanceof Role)
        ) {
            // we modify the heritage value
            if ($isUsernamePasswordToken && $this->isUsernamePasswordToken()) {
                $entity->setHeritage($this->container->get('sfynx.auth.role.factory')->getBestRoles($this->getUserRoles()));
            } else {
                // we set all right of all user to see the row
                $entity->setHeritage(array('ROLE_USER'));
            }
        }        
        // we give the right of persist if the entity is in the AUTHORIZATION_PREPERSIST container
        if ($this->is_permission_authorization_prepersist_authorized && $this->container->isScopeActive('request') && isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
            if (isset($GLOBALS['ENTITIES']['AUTHORIZATION_PREPERSIST']) && isset($GLOBALS['ENTITIES']['AUTHORIZATION_PREPERSIST'][$entity_name])) {
                if (is_array($GLOBALS['ENTITIES']['AUTHORIZATION_PREPERSIST'][$entity_name])) {
                    $route = $this->container->get('request')->get('_route');
                    if ((empty($route) || ($route == "_internal"))) {
                        $route = $this->container->get('sfynx.tool.route.factory')->getMatchParamOfRoute('_route', $this->container->get('request')->getLocale());
                    }
                    if (in_array($route, $GLOBALS['ENTITIES']['AUTHORIZATION_PREPERSIST'][$entity_name])) {
                        $entityManager->initializeObject($entity);
                        
                        return true;
                    }
                } elseif ($GLOBALS['ENTITIES']['AUTHORIZATION_PREPERSIST'][$entity_name] == true) {
                    $entityManager->initializeObject($entity);
                    
                    return true;
                }        
            }
        } else {
            $entityManager->initializeObject($entity);
             
            return true;            
        }
        // If AnonymousToken user,
        if ($isAnonymousToken && $this->isAnonymousToken()) {
            // we schedules the orphaned entity for removal
            $entityManager->getUnitOfWork()->scheduleOrphanRemoval($entity);
            // we throw the message.
            $this->setFlash('pi.session.flash.right.anonymous');
               
            return false;
        }
        // If  autentication user
        if ($isUsernamePasswordToken && $this->isUsernamePasswordToken()) {
            // if user have the create right
            if ( in_array('CREATE', $this->getUserPermissions()) || in_array('ROLE_SUPER_ADMIN', $this->getUserRoles()) || $isAllPermissions) {
                $entityManager->initializeObject($entity);
                // we throw the message.
                if ($entity instanceof \Sfynx\CmfBundle\Entity\HistoricalStatus) {
                } else {
                    $this->setFlash('pi.session.flash.right.create');
                }
                
                return true;
            } else {
                // we schedules the orphaned entity for removal
                $entityManager->getUnitOfWork()->scheduleOrphanRemoval($entity);
                // we throw the message.
                $this->setFlash('pi.session.flash.right.uncreate');
                
                return false;
            }
        }                
    }

    /**
     * preUpdate default.
     * BE CAREFUL !!! this method has to be used in the last of your LifecycleEvent management.
     *
     * @param LifecycleEventArgs $eventArgs
     * @param boolean            $isAnonymousToken        true to activate the anonymous token control.
     * @param boolean            $isUsernamePasswordToken true to activate the user token control.
     * @param boolean            $isAllPermissions        true to enable full permission regardless of the user.
     * 
     * @return boolean
     * @access protected
     * @final
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    final protected function _preUpdate(LifecycleEventArgs $eventArgs, $isAnonymousToken = true, $isUsernamePasswordToken = true, $isAllPermissions = false)
    {
        $entity         = $eventArgs->getEntity();
        $entityManager  = $eventArgs->getEntityManager();
        $entity_name    = get_class($entity);        
        // we given't the right of remove if the entity is in the PROHIBITION_PREUPDATE container
        if ($this->is_permission_prohibition_preupdate_authorized && isset($GLOBALS['ENTITIES']['PROHIBITION_PREUPDATE']) && isset($GLOBALS['ENTITIES']['PROHIBITION_PREUPDATE'][$entity_name])) {
            if (is_array($GLOBALS['ENTITIES']['PROHIBITION_PREUPDATE'][$entity_name])) {
                $id_entity = $entity->getId();
                if (in_array($id_entity, $GLOBALS['ENTITIES']['PROHIBITION_PREUPDATE'][$entity_name])) {
                    // just for register in data the change do in this class listener :
                    $class = $entityManager->getClassMetadata(get_class($entity));
                    $entityManager->getUnitOfWork()->computeChangeSet($class, $entity);
                    // we throw the message.
                    $this->setFlash('pi.session.flash.right.anonymous');
                   
                    return false;
                }
            } elseif ($GLOBALS['ENTITIES']['PROHIBITION_PREUPDATE'][$entity_name] == true) {
                // just for register in data the change do in this class listener :
                $class = $entityManager->getClassMetadata(get_class($entity));
                $entityManager->getUnitOfWork()->computeChangeSet($class, $entity);
                // we throw the message.
                $this->setFlash('pi.session.flash.right.anonymous');
                   
                return false;
            }
        }        
        if (method_exists($entity, 'setUpdatedAt')) {
            // we modify the Update_at value
            $entity->setUpdatedAt(new \DateTime());
        }
        if ($this->is_permission_authorization_preupdate_authorized && $this->container->isScopeActive('request') && isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
            // we give the right of update if the entity is in the AUTHORIZATION_PREPERSIST container
            if (isset($GLOBALS['ENTITIES']['AUTHORIZATION_PREUPDATE']) && isset($GLOBALS['ENTITIES']['AUTHORIZATION_PREUPDATE'][$entity_name])) {
                if (is_array($GLOBALS['ENTITIES']['AUTHORIZATION_PREUPDATE'][$entity_name])) {
                    $route = $this->container->get('request')->get('_route');
                    if ((empty($route) || ($route == "_internal"))) {
                        $route = $this->container->get('sfynx.tool.route.factory')->getMatchParamOfRoute('_route', $this->container->get('request')->getLocale());
                    }
                    if (in_array($route, $GLOBALS['ENTITIES']['AUTHORIZATION_PREUPDATE'][$entity_name])) {
                        $class = $entityManager->getClassMetadata(get_class($entity));
                        $entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet($class, $entity);
                           
                        return true;
                    }
                } elseif ($GLOBALS['ENTITIES']['AUTHORIZATION_PREUPDATE'][$entity_name] == true) {
                    $class = $entityManager->getClassMetadata(get_class($entity));
                    $entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet($class, $entity);
                       
                    return true;
                }
            }
        } else {
            $class = $entityManager->getClassMetadata(get_class($entity));
            $entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet($class, $entity);
            
            return true;
        }
        // If AnonymousToken user,
        if ($isAnonymousToken && $this->isAnonymousToken()) {
            // just for register in data the change do in this class listener :
            $class = $entityManager->getClassMetadata(get_class($entity));
            $entityManager->getUnitOfWork()->computeChangeSet($class, $entity);
            // we throw the message.
            $this->setFlash('pi.session.flash.right.anonymous');
               
            return false;
        }
        // If  autentication user
        if ($isUsernamePasswordToken && $this->isUsernamePasswordToken()) {
            if ($this->isRestrictionByRole($entity)) {
                // just for register in data the change do in this class listener :
                $class = $entityManager->getClassMetadata(get_class($entity));
                $entityManager->getUnitOfWork()->computeChangeSet($class, $entity);
                // we throw the message.
                $this->setFlash('pi.session.flash.right.unupdate');
                   
                return false;
            }               
            // if user have the edit right
            if ( in_array('EDIT', $this->getUserPermissions()) || in_array('ROLE_SUPER_ADMIN', $this->getUserRoles()) || $isAllPermissions) {
                // we persist the values of the entity
                $class = $entityManager->getClassMetadata(get_class($entity));
                $entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet($class, $entity);
                // we throw the message.
                $this->setFlash('pi.session.flash.right.update');
                  
                return true;
            } else {
                // just for register in data the change do in this class listener :
                $class = $entityManager->getClassMetadata(get_class($entity));
                $entityManager->getUnitOfWork()->computeChangeSet($class, $entity);
                // we throw the message.
                $this->setFlash('pi.session.flash.right.unupdate');
                   
                return false;
            }
        }
    }    
    
    /**
     * PreRemove default.
     * BE CAREFUL !!! this method has to be used in the last of your LifecycleEvent management.
     *
     * @param LifecycleEventArgs $eventArgs
     * @param boolean            $isAnonymousToken        true to activate the anonymous token control.
     * @param boolean            $isUsernamePasswordToken true to activate the user token control.
     * @param boolean            $isAllPermissions        true to enable full permission regardless of the user.
     * 
     * @return boolean
     * @access protected
     * @final
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    final protected function _preRemove(LifecycleEventArgs $eventArgs, $isAnonymousToken = true, $isUsernamePasswordToken = true, $isAllPermissions = false)
    {
        // get the order entity
        $entity         = $eventArgs->getEntity();
        $entityManager  = $eventArgs->getEntityManager();
        $entity_name    = get_class($entity);
        // we given't the right of remove if the entity is in the PROHIBITION_PREREMOVE container
        if ($this->is_permission_prohibition_preremove_authorized && isset($GLOBALS['ENTITIES']['PROHIBITION_PREREMOVE']) && isset($GLOBALS['ENTITIES']['PROHIBITION_PREREMOVE'][$entity_name])) {
            if (is_array($GLOBALS['ENTITIES']['PROHIBITION_PREREMOVE'][$entity_name])) {
                $id_entity = $entity->getId();
                if (in_array($id_entity, $GLOBALS['ENTITIES']['AUTHORIZATION_PREREMOVE'][$entity_name])) {
                    // we stop the remove method.
                    $entityManager->getUnitOfWork()->detach($entity);                
                    // we throw the message.
                    $this->setFlash('pi.session.flash.right.undelete');
                    
                    return false;
                }
            } elseif ($GLOBALS['ENTITIES']['PROHIBITION_PREREMOVE'][$entity_name] == true) {
                // we stop the remove method.
                $entityManager->getUnitOfWork()->detach($entity);                
                // we throw the message.
                $this->setFlash('pi.session.flash.right.undelete');
                
                return false;
            }
        }        
        if ($this->is_permission_authorization_preremove_authorized && $this->container->isScopeActive('request') && isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
            // we give the right of remove if the entity is in the AUTHORIZATION_PREREMOVE container
            if (isset($GLOBALS['ENTITIES']['AUTHORIZATION_PREREMOVE']) && isset($GLOBALS['ENTITIES']['AUTHORIZATION_PREREMOVE'][$entity_name])) {
                if (is_array($GLOBALS['ENTITIES']['AUTHORIZATION_PREREMOVE'][$entity_name])) {
                    $route = $this->container->get('request')->get('_route');
                    if ((empty($route) || ($route == "_internal"))) {
                        $route = $this->container->get('sfynx.tool.route.factory')->getMatchParamOfRoute('_route', $this->container->get('request')->getLocale());
                    }
                    if (in_array($route, $GLOBALS['ENTITIES']['AUTHORIZATION_PREREMOVE'][$entity_name])) {
                        return true;
                    }
                } elseif ($GLOBALS['ENTITIES']['AUTHORIZATION_PREREMOVE'][$entity_name] == true) {
                    return true;
                }
            }
        } else {
            return true;
        }    
        // If AnonymousToken user,
        if ($isAnonymousToken && $this->isAnonymousToken()) {
            //  we stop the remove method.
            $entityManager->getUnitOfWork()->detach($entity);
            // we throw the message.
            $this->setFlash('pi.session.flash.right.anonymous');
            
            return false;
        }
        // If  autentication user
        if ($isUsernamePasswordToken && $this->isUsernamePasswordToken()) {
            if ($this->isRestrictionByRole($entity)) {
                //  we stop the remove method.
                $entityManager->getUnitOfWork()->detach($entity);                    
                // we throw the message.
                $this->setFlash('pi.session.flash.right.undelete');
                
                return false;
            }            
            // if user have the delete right
            if ( in_array('DELETE', $this->getUserPermissions()) || in_array('ROLE_SUPER_ADMIN', $this->getUserRoles()) || $isAllPermissions) {
                // we throw the message.
                $this->setFlash('pi.session.flash.right.delete');
                
                return true;
            } else {
                //  we stop the remove method.
                $entityManager->getUnitOfWork()->detach($entity);                
                // we throw the message.
                $this->setFlash('pi.session.flash.right.undelete');
                
                return false;
            }
        }
        // we persist the values of the entity
        //$class = $entityManager->getClassMetadata(get_class($entity));
        //$entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet($class, $entity);
    
        /*
    
        just for register in data the change do in this class listener :
        $class = $entityManager->getClassMetadata(get_class($entity));
        $entityManager->getUnitOfWork()->computeChangeSet($class, $entity);
    
        To get a field value:
        $eventArgs->getNewValue('Fieldname');
    
        To know if a field has changed
        $eventArgs->hasChangedField('creditCard')
    
        */
    }    
    
    /**
     * Gets the name of the table.
     *
     * @return string the name of the table entity that we have to insert.
     * @access private
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function getOwningTable($eventArgs, $entity)
    {
        return $this->EntitiesContainer->getOwningTable($eventArgs, $entity);
    }
        
    /**
     * Update a entity
     *
     * @param LifecycleEventArgs $eventArgs
     * @param object             $entity
     * @param array              $identifier The update criteria. An associative array containing column-value pairs.
     *
     * @return void
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function _updateEntity($eventArgs, $entity, $Identifier)
    {
        return $this->EntitiesContainer->executeUpdate($eventArgs, $entity, $Identifier);
    }    
    
    /**
     * Persist all entities which are in the persistEntities container.
     *
     * @param LifecycleEventArgs $eventArgs
     *
     * @return void
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function _persistEntities($eventArgs)
    {
        $this->EntitiesContainer->persistEntities($eventArgs);
    }

    /**
     * Add an entity in the persistEntities container.
     *
     * @param Object $entity
     *
     * @return void
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function _addPersistEntities($entity)
    {
        $this->EntitiesContainer->addPersistEntities($entity);
    }

    /**
     * Gets the connexion of the database.
     *
     * @param LifecycleEventArgs $eventArgs
     * 
     * @return \Doctrine\DBAL\Connection
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function _connexion(LifecycleEventArgs $eventArgs)
    {
        return $this->EntitiesContainer->getConnection($eventArgs);
    }    
    
    /**
     * Gets the container instance.
     *
     * @return ContainerInterface
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function _container()
    {
        return $this->container;
    }    
    
    /**
     * Forwards the request to another controller.
     *
     * @param string $controller The controller name (a string like BlogBundle:Post:index)
     * @param array  $path       An array of path parameters
     * @param array  $query      An array of query parameters
     * 
     * @access protected
     * @return Response A Response instance
     */
    protected function forward($controller, array $params = array(), array $GET = array(), $POST = null)
    {
    	$params['_controller'] = $controller;
    	$subRequest = $this->container->get('request')->duplicate($GET, $POST, $params);
    
    	return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }    
    
    /**
     * Return the token object.
     *
     * @return \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function getToken()
    {
        return  $this->_container()->get('security.context')->getToken();
    }

    /**
     * Return the connected user name.
     *
     * @return string User name
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function getUserName()
    {
        return $this->getToken()->getUser()->getUsername();
    }    
    
    /**
     * Return the user permissions.
     *
     * @return array User permissions
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function getUserPermissions()
    {
        return $this->getToken()->getUser()->getPermissions();
    }  

    /**
     * Return the user roles.
     *
     * @return array User roles
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function getUserRoles()
    {
        return $this->getToken()->getUser()->getRoles();
    }    
    
    /**
     * Sets the flash message.
     *
     * @param string $message
     * @param string $type
     *
     * @return void
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function setFlash($message, $type = "permission")
    {
           $this->getFlashBag()->add($type, $message);
    }

    /**
     * Gets the flash bag.
     *
     * @return \Symfony\Component\HttpFoundation\Session\Flash\FlashBag
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function getFlashBag()
    {
        return $this->_container()->get('request')->getSession()->getFlashBag();
    }
        
    /**
     * Return if yes or no the user is anonymous token.
     *
     * @return boolean
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function isAnonymousToken()
    {
        if (
            ($this->getToken() instanceof \Symfony\Component\Security\Core\Authentication\Token\AnonymousToken)
            ||
            ($this->getToken() === null)
        ) {
            return true;
        } else {
            return false;
        }
    }    
    
    /**
     * Return if yes or no the user is UsernamePassword token.
     *
     * @return boolean
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function isUsernamePasswordToken()
    {
        if ($this->getToken() instanceof \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Persist the entity if the create permission is done.
     *
     * @return boolean
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function isPersistRight()
    {
        if ( in_array('CREATE', $this->getUserPermissions()) || in_array('ROLE_SUPER_ADMIN', $this->getUserRoles()) ) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Update the entity if the edit permission is done.
     *
     * @return boolean
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function isUpdateRight()
    {
        if ( in_array('EDIT', $this->getUserPermissions()) || in_array('ROLE_SUPER_ADMIN', $this->getUserRoles()) ) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Remove the entity if the delete permission is done.
     *
     * @return boolean
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function isDeleteRight()
    {
        if ( in_array('DELETE', $this->getUserPermissions()) || in_array('ROLE_SUPER_ADMIN', $this->getUserRoles()) ) {
            return true;
        } else {
            return false;
        }
    }    
    
    /**
     * Return true if the restriction on the entity is activated.
     * 
     * @param object $entity
     * 
     * @return boolean
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function isRestrictionByRole($entity)
    {
        $right       = true;
        $entity_name = get_class($entity);
        
        if ( 
            $this->is_permission_restriction_by_roles
            && !($this->container->get('security.context')->isGranted('ROLE_ADMIN'))
            && isset($GLOBALS['ENTITIES']['RESTRICTION_BY_ROLES']) 
            && isset($GLOBALS['ENTITIES']['RESTRICTION_BY_ROLES'][$entity_name])
        ){
               if (is_array($GLOBALS['ENTITIES']['RESTRICTION_BY_ROLES'][$entity_name])){
                   $route = $this->container->get('request')->get('_route');
                   if ((empty($route) || ($route == "_internal"))) {
                       $route = $this->container->get('sfynx.tool.route.factory')->getMatchParamOfRoute('_route', $this->container->get('request')->getLocale());
                   }
                   if (!in_array($route, $GLOBALS['ENTITIES']['RESTRICTION_BY_ROLES'][$entity_name])) {
                       return false;
                   }
               }
               // Gets all user roles.
               $user_roles                = $this->container->get('sfynx.auth.role.factory')->getAllUserRoles();
               // Gets the best role authorized to access to the entity.
               $authorized_page_roles     = $this->container->get('sfynx.auth.role.factory')->getBestRoles($entity->getHeritage());
               $right = false;
               if (is_null($authorized_page_roles) || empty($authorized_page_roles)) {
                   $right = true;
               } else {
                   foreach ($authorized_page_roles as $key=>$role_page) {
                       if (in_array($role_page, $user_roles)) {
                           $right = true;
                       }
                   }                        
               }
               if ( 
                   ( (get_class($entity) == 'Proxies\SfynxMediaBundleEntityMediaProxy') || get_class($entity) == 'Proxies\PiAppGedmoBundleEntityMediaProxy')
                   	&& isset($GLOBALS['ENTITIES']['RESTRICTION_BY_MEDIA']) 
                  	&& is_array($GLOBALS['ENTITIES']['RESTRICTION_BY_MEDIA'])
               ) {
                   $methods_authorized = $GLOBALS['ENTITIES']['RESTRICTION_BY_MEDIA'];
                   if (get_class($entity) == 'Proxies\SfynxMediaBundleEntityMediaProxy') {
                       $media    = $this->_container()->get('pi_app_gedmo.repository')->getRepository('Media')->findOneByMediaId($entity->getId());
                   } else {
                       $media    = $entity;
                   }
                   $right = true;
                   foreach ($methods_authorized as $method) {
                       if ( method_exists($media, $method) && is_object($media->$method()) ) {
                           $right = false;
                       }    
                   }
               }
        }
        
        return !$right;
    }
    
    /**
     * Sets the repository service.
     *
     * @return void
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @since  2012-01-23
     */
    protected function setRepository()
    {
        $this->repository = $this->_container()->get('pi_app_admin.repository');
    }
    
    /**
     * Gets the repository service of the entity given in param.
     * 
     * @param string $nameEntity
     * 
     * @return ObjectRepository
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @since  2012-01-23
     */
    protected function getRepository($nameEntity = '')
    {
        if (empty($this->repository)) {
            $this->setRepository();
        }    
        if (!empty($nameEntity)) {
            return $this->repository->getRepository($nameEntity);
        } else {
            throw new \Doctrine\ORM\EntityNotFoundException();
        }
    }  
    
    /**
     * @param LoadClassMetadataEventArgs $args
     * 
     * @return void
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */   
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
	$metadata = $eventArgs->getClassMetadata();
        
        $this->loadAssociations($eventArgs, $metadata);
        $this->loadIndexes($eventArgs, $metadata);
        $this->loadUniques($eventArgs, $metadata);

        $this->loadDiscriminatorColumns($eventArgs, $metadata);
        $this->loadDiscriminators($eventArgs, $metadata);
        $this->loadInheritanceTypes($eventArgs, $metadata);
        
//        $namingStrategy = $eventArgs
//            ->getEntityManager()
//            ->getConfiguration()
//            ->getNamingStrategy()
//        ;           
//        $metadata->mapManyToMany(array(
//            'targetEntity'  => UploadedDocument::CLASS,
//            'fieldName'     => 'uploadedDocuments',
//            'cascade'       => array('persist'),
//            'joinTable'     => array(
//                'name'        => strtolower($namingStrategy->classToTableName($metadata->getName())) . '_document',
//                'joinColumns' => array(
//                    array(
//                        'name'                  => $namingStrategy->joinKeyColumnName($metadata->getName()),
//                        'referencedColumnName'  => $namingStrategy->referenceColumnName(),
//                        'onDelete'  => 'CASCADE',
//                        'onUpdate'  => 'CASCADE',
//                    ),
//                ),
//                'inverseJoinColumns'    => array(
//                    array(
//                        'name'                  => 'document_id',
//                        'referencedColumnName'  => $namingStrategy->referenceColumnName(),
//                        'onDelete'  => 'CASCADE',
//                        'onUpdate'  => 'CASCADE',
//                    ),
//                )
//            )
//        ));        
    }        
    
    /**
     * @param ClassMetadataInfo $metadata
     *
     * @throws \RuntimeException
     */
    protected function loadAssociations($eventArgs, ClassMetadataInfo $metadata)
    {
        if (!array_key_exists($metadata->getName(), $this->associations)) {
            return;
        }
        try {
            foreach ($this->associations[$metadata->getName()] as $type => $mappings) {
                foreach ($mappings as $mapping) {
                    if ($metadata->hasAssociation($mapping['fieldName'])) {
                        continue;
                    }
                    call_user_func(array($metadata, $type), $mapping);
                }
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(sprintf('Error with class %s : %s', $metadata->getName(), $e->getMessage()), 404, $e);
        }        
    }    
    
    /**
     * @param ClassMetadataInfo $metadata
     *
     * @throws \RuntimeException
     */
    protected function loadDiscriminatorColumns($eventArgs, ClassMetadataInfo $metadata)
    {
        if (!array_key_exists($metadata->getName(), $this->discriminatorColumns)) {
            return;
        }
        try {
            if (isset($this->discriminatorColumns[$metadata->getName()])) {
                $arrayDiscriminatorColumns = $this->discriminatorColumns[$metadata->getName()];
                if (isset($metadata->discriminatorColumn)) {
                    $arrayDiscriminatorColumns = array_merge($metadata->discriminatorColumn, $this->discriminatorColumns[$metadata->name]);
                }
                $metadata->setDiscriminatorColumn($arrayDiscriminatorColumns);
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(sprintf('Error with class %s : %s', $metadata->getName(), $e->getMessage()), 404, $e);
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @throws \RuntimeException
     */
    protected function loadInheritanceTypes($eventArgs, ClassMetadataInfo $metadata)
    {

        if (!array_key_exists($metadata->getName(), $this->inheritanceTypes)) {
            return;
        }
        try {
            if (isset($this->inheritanceTypes[$metadata->getName()])) {

                $metadata->setInheritanceType($this->inheritanceTypes[$metadata->getName()]);
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(sprintf('Error with class %s : %s', $metadata->getName(), $e->getMessage()), 404, $e);
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @throws \RuntimeException
     */
    protected function loadDiscriminators($eventArgs, ClassMetadataInfo $metadata)
    {
        if (!array_key_exists($metadata->getName(), $this->discriminators)) {
            return;
        }
        try {
            foreach ($this->discriminators[$metadata->getName()] as $key => $class) {
                if (in_array($key, $metadata->discriminatorMap)) {
                    continue;
                }
                $metadata->setDiscriminatorMap(array($key=>$class));
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(sprintf('Error with class %s : %s', $metadata->getName(), $e->getMessage()), 404, $e);
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     */
    protected function loadIndexes($eventArgs, ClassMetadataInfo $metadata)
    {
        if (!array_key_exists($metadata->getName(), $this->indexes)) {
            return;
        }
        foreach ($this->indexes[$metadata->getName()] as $name => $columns) {
            $metadata->table['indexes'][$name] = array('columns' => $columns);
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     */
    protected function loadUniques($eventArgs, ClassMetadataInfo $metadata)
    {
        if (!array_key_exists($metadata->getName(), $this->uniques)) {
            return;
        }
        foreach ($this->uniques[$metadata->getName()] as $name => $columns) {
            $metadata->table['uniqueConstraints'][$name] = array('columns' => $columns);
        }
    }  
    
    /**
     * Add a discriminator to a class.
     *
     * @param  string  $class               The Class
     * @param  string  $key                 Key is the database value and values are the classes
     * @param  string  $discriminatorClass  The mapped class
     */
    public function addDiscriminator($class, $key, $discriminatorClass)
    {
        if (!isset($this->discriminators[$class])) {
            $this->discriminators[$class] = array();
        }
        if (!isset($this->discriminators[$class][$key])) {
            $this->discriminators[$class][$key] = $discriminatorClass;
        }
    }

    /**
     * Add the Discriminator Column.
     *
     * @param string $class
     * @param array  $columnDef
     */
    public function addDiscriminatorColumn($class, array $columnDef)
    {
        if (!isset($this->discriminatorColumns[$class])) {
            $this->discriminatorColumns[$class] = $columnDef;
        }
    }

    /**
     * @param string $class
     * @param string $type
     */
    public function addInheritanceType($class, $type)
    {
        if (!isset($this->inheritanceTypes[$class])) {
            $this->inheritanceTypes[$class] = $type;
        }
    }

    /**
     * @param string $class
     * @param string $type
     * @param array $options
     */
    public function addAssociation($class, $type, array $options)
    {
        if (!isset($this->associations[$class])) {
            $this->associations[$class] = array();
        }
        if (!isset($this->associations[$class][$type])) {
            $this->associations[$class][$type] = array();
        }
        $this->associations[$class][$type][] = $options;
    }

    /**
     * @param string $class
     * @param string $name
     * @param array  $columns
     */
    public function addIndex($class, $name, array $columns)
    {
        if (!isset($this->indexes[$class])) {
            $this->indexes[$class] = array();
        }
        if (isset($this->indexes[$class][$name])) {
            return;
        }
        $this->indexes[$class][$name] = $columns;
    }

    /**
     * @param string $class
     * @param string $name
     * @param array  $columns
     */
    public function addUnique($class, $name, array $columns)
    {
        if (!isset($this->indexes[$class])) {
            $this->uniques[$class] = array();
        }
        if (isset($this->uniques[$class][$name])) {
            return;
        }
        $this->uniques[$class][$name] = $columns;
    }        
}

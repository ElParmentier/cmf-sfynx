<?php
/**
 * This file is part of the <Admin> project.
 *
 * @category   Dispatcher
 * @package    Event
 * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
 * @since 2013-04-18
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OrApp\OrCmfBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\HttpKernel;

use Sfynx\AuthBundle\Event\ResponseEvent;

/**
 * Response handler of user connection.
 *
 * @category   Admin_Eventlistener
 * @package    EventListener
 *
 * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
 */
class DispatcherLoginResponse
{
   /**
    * @var \Symfony\Component\DependencyInjection\ContainerInterface
    */
   protected $container;   

   /**
    * Constructor.
    *
    * @param string $defaultLocale	Locale value
    */   
   public function __construct(ContainerInterface $container)
   {
       $this->container     = $container;  
   }

   /**
    * Invoked to modify the controller that should be executed.
    *
    * @param FilterControllerEvent $event The event
    *
    * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
    */   
   public function onPiLoginChangeResponse(ResponseEvent $event)
   {
       $response = $event->getResponse();
       //$response->setTargetUrl('http://www.pi-groupe.fr');
       $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie('PI-Application', 'Sfynx/2.2', $event->getDateExpire()));
       $event->setResponse($response);
   }

}
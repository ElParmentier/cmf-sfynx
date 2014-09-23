<?php
/**
 * This file is part of the <Auth> project.
 *
 * @category   Handler
 * @package    EventListerner
 * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
 * @since 2011-01-25
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Sfynx\AuthBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

/**
 * Custom logout handler.
 *
 * @category   Handler
 * @package    EventListerner
 *
 * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
 */
class HandlerAuthentication implements AuthenticationSuccessHandlerInterface 
{
	/**
	 * @var \Symfony\Component\Routing\Router $router
	 */
	protected $router;
		
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;    
    
    /**
     * Constructs a new instance of SecurityListener.
     * 
     * @param Router $router The router
     * @param ContainerInterface $container The service container
     */
    public function __construct(ContainerInterface $container, Doctrine $doctrine)
    {
    	$this->router        = $container->get('sfynx.tool.route.factory');
    	$this->container     = $container;
    	$this->em            = $doctrine->getManager();
    }
    
    /**
     * We deal with the case where the connection is limited to a set of roles (ajax or not ajax connection).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     * @return Response
     * 
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
		if (isset($_POST['roles']) && !empty($_POST['roles']))
	    {
	    	$all_authorization_roles = json_decode($_POST['roles'], true);
	    	$best_roles_name = $this->container->get('sfynx.auth.role.factory')->getBestRoleUser();
	    	if (is_array($all_authorization_roles) && !in_array($best_roles_name, $all_authorization_roles)) {
	    		// Set a flash message
	    		$request->getSession()->getFlashBag()->add('notice', "Vous n'êtes pas autorisé à vous connecté !");
	    		// we disconnect user
	    		$request->getSession()->invalidate();
	    	}
	    }
	    $response = new Response(json_encode('ok'));
	    $response->headers->set('Content-Type', 'application/json');
	    
	    return $response;
    }    
    
}
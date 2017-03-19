<?php

namespace ZAG\ChallengeBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Schulzcodes\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiController extends FOSRestController implements ContainerAwareInterface
{


    /**
     * @Route("/articles")
     */
    public function articlesAction()
    {
        $articles = array('article1', 'article2', 'article3');
        return new JsonResponse($articles);
    }

    /**
     * @Route("/user")
     */
    public function userAction()
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        var_dump($user);
        if($user instanceof User) {
            return new JsonResponse(array(
                'id' => $user->getId(),
                'username' => $user->getUsername()
            ));
        }
        return new JsonResponse(array(
            'message' => 'User is not identified'
        ));

    }
}

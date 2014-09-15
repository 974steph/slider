<?php

namespace Ssa\PhotoBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Finder;
use Ssa\PhotoBundle\Entity\Book;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class DefaultController extends Controller
{
  

    public function indexAction()
    {  
               
        $em = $this->getDoctrine()->getManager();
        $books=$em->getRepository('SsaPhotoBundle:Book')->findAll();
        return $this->render('SsaPhotoBundle::index.html.twig',  array('books'=>$books,'first'=>reset($books)));

    }

    public function listAction($dossier)
    {   

        $em = $this->getDoctrine()->getManager();
        $book=$em->getRepository('SsaPhotoBundle:Book')->find($dossier);

        $securityContext = $this->get('security.context');
        
        // check for edit access
        if (false === $securityContext->isGranted('VIEW', $book)) {
            throw new AccessDeniedException();
        }
        
        $chemin=$this->get('kernel')->getRootDir();
        $imagesArr	= array();

        $avalancheService = $this->get('imagine.cache.path.resolver');
        $finder = new Finder();
        $finder->files()->in($chemin.'/../web/images/'.$book->getPath());

        foreach ($finder as $files)
        {
                $path= $avalancheService->getBrowserPath('/images/'.$book->getPath().'/'.$files->getRelativePathname(), 'thumb');
                $imagesArr[] = array('src' => $path,
                                     'alt'	=> '/images/'.$book->getPath().'/'.$files->getRelativePathname(),
                                     'desc'	=> "");
        }

        return new JsonResponse($imagesArr);
    }
}
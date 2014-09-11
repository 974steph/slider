<?php

namespace Ssa\PhotoBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Finder;
use Ssa\PhotoBundle\Entity\Book;

class DefaultController extends Controller
{
  //   public function indexAction()
  //   { 
  //   	$avalancheService = $this->get('imagine.cache.path.resolver');
  //       $path= $avalancheService->getBrowserPath('/images/album1/1.jpg', 'thumb');
        
 	// 	return $this->render('SsaPhotoBundle:Default:index.html.twig', array ('chemin'=>$path) );
 	// }

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
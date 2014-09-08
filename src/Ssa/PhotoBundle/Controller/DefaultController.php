<?php

namespace Ssa\PhotoBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Finder;

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
		$chemin=$this->get('kernel')->getRootDir();
		//calcul liste des dossiers
                
	
		$path= array();
		$finder = new Finder();
		$finder->directories()->in($chemin.'/../web/images');

		foreach ($finder as $dossier) {
		 	 $path[] = str_replace("/","|",$dossier->getRelativePathname());
		}
		return $this->render('SsaPhotoBundle::index.html.twig', array('dossier'=>$path));

	}

	public function listAction($dossier)
        {   $chemin=$this->get('kernel')->getRootDir();
            $imagesArr	= array();
            $dossier=str_replace("|","/",$dossier);
            $avalancheService = $this->get('imagine.cache.path.resolver');
            $finder = new Finder();
            $finder->files()->in($chemin.'/../web/images/'.$dossier);

            foreach ($finder as $files)
            {
                    $path= $avalancheService->getBrowserPath('/images/'.$dossier.'/'.$files->getRelativePathname(), 'thumb');
                    $imagesArr[] = array('src' => $path,
                                         'alt'	=> '/images/'.$dossier.'/'.$files->getRelativePathname(),
                                         'desc'	=> "");
            }

            return new JsonResponse($imagesArr);
	}
}
<?php

namespace Ssa\PhotoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Ssa\Common\Thumber;

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
		return $this->render('SsaPhotoBundle::index.html.twig');

	}
}
<?php

namespace Ssa\PhotoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Ssa\Common\Thumber;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        //return $this->render('SsaPhotoBundle:Default:index.html.twig', array('name' => $name));
		ini_set('memory_limit', '50M');

// if the above doesn’t work:
// add/ modify the .htaccess file (in the same directory as this file):
// php_value memory_limit 50M

// --------------------------------------------------------------------------
// instantiate the Thumber class
// --------------------------------------------------------------------------

$w   = isset($_GET['w']) ? $_GET['w'] : null ;
$h   = isset($_GET['h']) ? $_GET['h'] : null ;
$img = isset($_GET['img']) ? $_GET['img'] : '';

$imgPath='/kunden/homepages/27/d539535352/htdocs/photobox/images/';
$thumbPath='/kunden/homepages/27/d539535352/htdocs/photobox/thumbs/';

$imgPath='/appli/photobox/images/';
$thumbPath='/appli/photobox/thumbs/';


		try {
    		$thumber = new Thumber($imgPath,$thumbPath);
					  
			$thumber->setImage($img)	;
			$thumber->setThumbSize( $w ,$h);
	
			$thumber->serveThumb();

			} catch (Exception $e) {
    		echo 'Exception  : ',  $e->getMessage(), "\n";
		}

    }
}

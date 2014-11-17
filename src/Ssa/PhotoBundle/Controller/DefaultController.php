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
use Symfony\Component\HttpFoundation\Response;


class DefaultController extends Controller
{
  

    public function indexAction()
    {  
        $securityContext = $this->get('security.context');
        
        $em = $this->getDoctrine()->getManager();
         //$em->getRepository('BackendDestinyBundle:Destiny')->findBy(array(), array('title'=>'asc'));
        //$books=$em->getRepository('SsaPhotoBundle:Book')->findAll();
        $books=$em->getRepository('SsaPhotoBundle:Book')->findBy(array(), array('path'=>'asc'));
        
        $grantedBooks =  array_filter(
                            $books,
                            function ($e) use (&$securityContext) {
                                return $securityContext->isGranted('VIEW', $e);
                            }
                        );
        
        return $this->render('SsaPhotoBundle::index.html.twig',  array('books'=>$grantedBooks,'first'=>reset($grantedBooks)));

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

  
        $finder = new Finder();
        $finder->files()
                ->in($chemin.'/../web/images/'.$book->getPath())
                ->name('/.*\.jpg/i')
                ->name('/.*\.png/i')
                ->name('/.*\.gif/i')
                ->sortByName() 
                ->depth ('== 0');

        foreach ($finder as $files)
        {
                $uriImage = $this->get('router')->generate('ssa_photo_get',   array('fichier' => $book->getPath().'/'.$files->getRelativePathname() ));
                $uriThumb = $this->get('router')->generate('ssa_photo_cache', array('fichier' => $book->getPath().'/'.$files->getRelativePathname() ));
               
                $imagesArr[] = array('src'      => $uriThumb,
                                     'alt'	=> $uriImage,
                                     'desc'	=> "");
        }

        return new JsonResponse($imagesArr);
    }
    
    public function imageAction($fichier)
    {
       
        $chemin=$this->get('kernel')->getRootDir();
        
        $imgpath = $chemin .'/../web/images/'. $fichier;

        list($header, $content) = $this->renderImage($imgpath);
        
        return new Response($content, 200, $header);
        
     
    }
    
    
    public function renderImage($imgpath)
    {
    
        // Get the mimetype for the file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);  // return mime type ala mimetype extension
        $mime_type = finfo_file($finfo, $imgpath);
        finfo_close($finfo);
        /*
        ob_start();
        imagejpeg($img, null, 100);
        $image = base64_encode(ob_get_contents());
        ob_get_clean();

        $response = new Response();
        $response->headers->set('Content-Type', 'image/jpeg');
        $response->setContent($image);
        return $response;
        */
        ob_start();
        switch ($mime_type){
            case "image/jpeg":
                // Set the content type header - in this case image/jpg
               // header('Content-Type: image/jpeg');
                 $headers = array('Content-Type'     => 'image/jpeg');
                // Get image from file
                $img = imagecreatefromjpeg($imgpath);
                // Output the image
                imagejpeg($img);
                break;
            case "image/png":
                // Set the content type header - in this case image/png
               // header('Content-Type: image/png');
                 $headers = array('Content-Type'     => 'image/png');
                // Get image from file
                $img = imagecreatefrompng($imgpath);
                // integer representation of the color black (rgb: 0,0,0)
                $background = imagecolorallocate($img, 0, 0, 0);
                // removing the black from the placeholder
                imagecolortransparent($img, $background);
                // turning off alpha blending (to ensure alpha channel information
                // is preserved, rather than removed (blending with the rest of the
                // image in the form of black))
                imagealphablending($img, false);
                // turning on alpha channel information saving (to ensure the full range
                // of transparency is preserved)
                imagesavealpha($img, true);
                // Output the image
                imagepng($img);
                break;
            case "image/gif":
                // Set the content type header - in this case image/gif
                //header('Content-Type: image/gif');
                   $headers = array('Content-Type'     => 'image/gif');
                // Get image from file
                $img = imagecreatefromgif($imgpath);
                // integer representation of the color black (rgb: 0,0,0)
                $background = imagecolorallocate($img, 0, 0, 0);
                // removing the black from the placeholder
                imagecolortransparent($img, $background);
                // Output the image
                imagegif($img);
                break;
        }
        
       $image= ob_get_clean();
        // Free up memory
        imagedestroy($img);
        
            return array($headers,$image);
    }
    
    public function thumbAction($fichier)
    {
           
        $chemin=$this->get('kernel')->getRootDir();
        $imgpathori = $chemin .'/../web/images/'. $fichier;
        $imgpath = $chemin .'/../web/cache/thumb/'. $fichier;
        
        if (!file_exists($imgpath))
        {          

            $directory = dirname($imgpath);
            $file = basename($imgpath);
            
            if (!file_exists($directory) && !is_dir($directory)) 
            {
                mkdir($directory, 0755, true);      
            } 
                     
            $cmd=sprintf("/usr/bin/convert '%s' -resize 120x90 '%s'",$imgpathori,$imgpath);
            exec($cmd);
        
        }
        list($header, $content) = $this->renderImage($imgpath);
       
        return new Response($content, 200, $header);
       
    }
    
    
    
    
    





}
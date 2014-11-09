<?php

namespace Ssa\PhotoBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Finder\Finder;

use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Ssa\PhotoBundle\Entity\Book;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/*
 * 
 * $neededObject = array_filter(
    $arrayOfObjects,
    function ($e) {
        return $e->id == $searchedValue
    }
);
 */

class BookListCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('book:list')
            ->setDescription('list and register book')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        
        $container = $this->getApplication()->getKernel()->getContainer();
        //$chemin= $this->getContainer()->get('kernel')->getRootDir();
        $chemin= $container->get('kernel')->getRootDir();
        


        $path= array();
        $finder = new Finder();
        $finder->directories()->in($chemin.'/../web/images');

        

        

        $doctrine = $container->get('doctrine');

        $em = $doctrine->getManager();
        $books=$em->getRepository('SsaPhotoBundle:Book')->findAll();
        
        
        $nothingtodo=true;

        foreach ($finder as $dossier)
        {   
            $file=new Finder();
            $file->files()
                  ->depth ('== 0')
                  ->in($dossier->getRealpath());
            
            

            if ($file->count() > 0)
            {
                $knowBook =  array_filter(
                                $books,
                                function ($e) use (&$dossier) {
                                    return $e->getPath() == $dossier->getRelativePathname();
                                }
                            );
                if (empty($knowBook))
                {  $nothingtodo=false;
                    $myBook= new Book();
                    $myBook->setPath($dossier->getRelativePathname());
                    $em->persist($myBook);
                    $em->flush();

                    $this->SetOwner($myBook);


                    $output->writeln( "Ajout :".str_replace("/","|",$dossier->getRelativePathname()));
                }
            }
        }
        
        if ($nothingtodo)
        { $output->writeln( "Pas de nouveaux dossiers");
            
        }    
        
    }
    
    protected function SetOwner($obj)
    {
        
        $container = $this->getApplication()->getKernel()->getContainer();
        $aclProvider = $container->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($obj);
        
       
        
        try {
            $acl = $aclProvider->findAcl($objectIdentity);
        } catch (\Symfony\Component\Security\Acl\Exception\AclNotFoundException $e) {
            $acl = $aclProvider->createAcl($objectIdentity);
        }
        
        $securityIdentity = new UserSecurityIdentity('admin', 'Ssa\UserBundle\Entity\User');
        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
        
        
        $aclProvider->updateAcl($acl);
    }
}

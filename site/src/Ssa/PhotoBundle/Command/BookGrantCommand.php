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

class BookGrantCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('book:grant')
            ->setDescription('grant view rule on book')
            ->addArgument(
                    'user',
                    InputArgument::REQUIRED,
                    'utilsateur ?'
                )
            ->addArgument(
                    'book',
                    InputArgument::REQUIRED,
                    'books'
                )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $myUser = $input->getArgument('user') ;
        $myBook = $input->getArgument('book') ;
        
        
        
        
        $container = $this->getApplication()->getKernel()->getContainer();
        
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        
        $findUser=$em->getRepository('SsaUserBundle:User')->findBy(array('username' => $myUser));
        $findBook=$em->getRepository('SsaPhotoBundle:Book')->findBy(array('path' => $myBook));
        
        if (count($findUser) != 0)
        {
            if (count($findBook) != 0)
            {   
                $book=$findBook[0];
                $this->SetOwner($myUser,$book);
                $output->writeln("Grant $myUser");

            }
            else 
            {
                $output->writeln("<error>book $myBook don't exist</error>");
              
            }
            
        }
        else
        {
            $output->writeln("<error>user $myUser don't exist</error>");
        }
        
        
        
        
        
    }
    
    protected function SetOwner($user,$obj)
    {
        
        $container = $this->getApplication()->getKernel()->getContainer();
        $aclProvider = $container->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($obj);
        
       
        
        try {
            $acl = $aclProvider->findAcl($objectIdentity);
        } catch (\Symfony\Component\Security\Acl\Exception\AclNotFoundException $e) {
            $acl = $aclProvider->createAcl($objectIdentity);
        }
        
        $builder = new MaskBuilder();
        $builder ->add('VIEW');
        $mask = $builder->get(); 
        
        $securityIdentity = new UserSecurityIdentity($user, 'Ssa\UserBundle\Entity\User');
        $acl->insertObjectAce($securityIdentity, $mask);
        
        
        $aclProvider->updateAcl($acl);
    }
}

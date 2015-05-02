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

class BookUserCommand extends ContainerAwareCommand
{   var $output;

    protected function configure()
    {
        $this
            ->setName('book:user')
            ->setDescription('user administration')
            ->addOption
                (
                    'action',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'List|View|Grant'
                )
            ->addOption(
                    'user',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'user'
                )
            ->addOption(
                    'book',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'book'
                )
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {   $this->output=$output;
        $action=mb_strtolower( $input->getOption('action'));
        $user=$input->getOption('user');
        $book=$input->getOption('book');
        
        switch ($action)
        {
            case 'grant':
                if ( $user && $book)
                    $this->grantUser($user,$book);
                else
                    $output->writeln("<error>--user --book</error>");
                break;
            case 'view':
                if ( $user)
                    $this->viewUserAcl($user);
                else
                    $output->writeln("<error>--user </error>");
                break;
                
            default:
                $output->writeln("<error>action non reconnue</error>");
        }
        
    }   
        
    protected function viewUserAcl($myUser)
    {
        
        $sql="
            select  acl_entries.object_identity_id,acl_entries.security_identity_id,acl_entries.mask,slider_book.path
            from acl_classes, acl_entries,acl_object_identities,slider_book
            where acl_classes.class_type='Ssa\PhotoBundle\Entity\Book'
            and acl_classes.id=acl_entries.class_id
            and acl_object_identities.id=acl_entries.object_identity_id
            and security_identity_id=:userid
            and acl_entries.object_identity_id=slider_book.id";
        
        $container = $this->getApplication()->getKernel()->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();    
        
        $findUser=$em->getRepository('SsaUserBundle:User')->findOneBy(array('username' => $myUser));
        
      
        

        if (count($findUser) != 0)
        {   $params = array('userid'=>$findUser->getId());
            $stmt = $em
                   ->getConnection()
                   ->prepare($sql);
            
            $stmt->execute($params);
            
            $result=$stmt->fetchAll();
            
            if (count($result)==0)
            {
                $this->output->writeln("<error>user $myUser n'a pas de privilege</error>"); 
            }
            else
            {
                foreach ($result as $row)
                {
                    $builder = new MaskBuilder();
                    $builder->add((integer)$row['mask']);
                    $path= sprintf("[%-25s]",$row['path']);
                    $this->output->writeln("Book ".
                                            "<comment>".
                                            $path.
                                            "</comment>".
                                            "\tright:\t".
                                            "<comment>".
                                            $builder->getPattern().
                                            "</comment>"
                            
                                            );
                }
                        
                $this->output->writeln("VIEW = <comment>V</comment>".
               ", ".
               "CREATE = <comment>C</comment>".
               ", ".
               "EDIT = <comment>E</comment>".
               ", ".
               "DELETE = <comment>D</comment>".
               ", ".
               "UNDELETE = <comment>U</comment>".
               ", ".
               "OPERATOR = <comment>O</comment>".
               ", ".
               "MASTER = <comment>M</comment>".
               ", ".
               "OWNER = <comment>N</comment>");
            }
            
        }
        else
        {
             $this->output->writeln("<error>user $myUser don't exist</error>");
        }
        
        
    }
    
    protected function grantUser($myUser,$myBook)
    {
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
                 $this->output->writeln("Grant $myUser");

            }
            else 
            {
                 $this->output->writeln("<error>book $myBook don't exist</error>");
              
            }
            
        }
        else
        {
             $this->output->writeln("<error>user $myUser don't exist</error>");
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

<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 3/19/17
 * Time: 12:01 AM
 */

namespace Schulzcodes\UserBundle\Command;

use FOS\OAuthServerBundle\Entity\ClientManager;
use Schulzcodes\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CreateUserCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('schulzcodes:oauth-server:user:create')
            ->setDescription('Creates a new user')
            ->addOption(
                'username',
                null,
                InputOption::VALUE_REQUIRED,
                'Sets the username of the new User.',
                null
            )
            ->addOption(
                'email',
                null,
                InputOption::VALUE_REQUIRED,
                'Sets the email-address for the new User',
                null
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_REQUIRED,
                'Sets the password for the new User'
            )
            ->setHelp(
                <<<EOT
<info>user:create</info> Command creatsa new user for the OAuth2 login.
 
<info>php schulzcodes:oauth-server:user:create --username=... --email=... --password=...</info>
 
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if($input->validate()) {
            if($this->createUser($input->getOption('username'),$input->getOption('email'),$input->getOption('password'))){
                $output->writeln(
                    sprintf(
                        "<info>Successfully created User %s with password %s and email %s</info>",$input->getOption('username'),$input->getOption('email'),$input->getOption('password'))
                );
            }else{
                $output->writeln(
                    sprintf(
                        "<error>The Input is not valid. Please check your usage:</error>\n %s",$this->getHelp()
                    )
                );
            }
        }else{
            $helper = $this->getHelper('question');
            $question1 = new Question('Please enter the new Username: <info>(admin)</info> ', 'admin');
            $username = $helper->ask($input, $output, $question1);
            $question2 = new Question('Please enter the users password: <info>(admin)</info> ', 'admin');
            $password = $helper->ask($input, $output, $question2);
            $question3 = new Question('Please enter the users email: <info>(admin@schulz.codes)</info>', 'admin@schulz.codes');
            $email = $helper->ask($input, $output, $question3);
            $encoder = $this->getContainer()->get('security.password_encoder');
            if($this->createUser($username,$email,$password)){
                $output->writeln(
                    sprintf(
                        "<info>Successfully created User %s with password %s and email %s</info>",$username,$password,$email
                    )
                );
            }else{
                $output->writeln("Error while creating user");
            }

        }

    }

    private function createUser($username,$email, $password):bool {
        try {
            $userAdmin = new User();
            $userAdmin->setUsername($username);

            $encoder = $this->getContainer()->get('security.password_encoder');
            $encoded = $encoder->encodePassword($userAdmin, $password);
            $userAdmin->setPassword($encoded);
            var_dump($encoded);
            var_dump($encoder);
            $userAdmin->setEmail($email);

            $manager = $this->getContainer()->get('doctrine')->getEntityManager();
            $manager->persist($userAdmin);
            $manager->flush();
            return true;
        }catch (Exception $exception){
            return false;
        }
    }
}
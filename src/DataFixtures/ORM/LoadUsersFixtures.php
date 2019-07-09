<?php

namespace App\DataFixtures\ORM;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUsersFixtures extends Fixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function load(ObjectManager $manager)
    {
        $passwordEncoder = $this->container->get('security.password_encoder');

        $user = new User();
        $user->setUsername('toni');
        $user->setPassword($passwordEncoder->encodePassword($user, 'toni'));
        $user->setApiKey('sarasa');
        $user->setRoles(['ROLE_USER']);

        $manager->persist($user);

        $user1 = new User();
        $user1->setUsername('sosa');
        $user1->setPassword($passwordEncoder->encodePassword($user1, 'sosa'));
        $user1->setApiKey('soroso');
        $user1->setRoles(['ROLE_USER']);

        $manager->persist($user1);

        $manager->flush();
    }

    /**
     * Sets the container.
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}

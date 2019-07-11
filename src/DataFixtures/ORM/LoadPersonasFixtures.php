<?php

namespace App\DataFixtures\ORM;

use App\Entity\Persona;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadPersonasFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $persona = new Persona();
        $persona->setFirstName('juan');
        $manager->persist($persona);

        $persona1 = new Persona();
        $persona1->setFirstName('Bar');
        $manager->persist($persona1);

        $manager->flush();
    }
}

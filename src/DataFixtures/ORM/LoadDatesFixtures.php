<?php

namespace App\DataFixtures\ORM;

use App\Entity\Date;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadDatesFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $date = new Date();
        $date->setDate(new DateTime());
        $date->setName('Some today day');
        $date->setDescription('testinf dates');

        $manager->persist($date);
        $manager->flush();
    }
}

<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }


    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        for ($i = 1; $i <= 50; $i++) {
            $contributor = new User();
            $contributor->setEmail($faker->unique()->safeEmail);
            $contributor->setFirstname($faker->firstName());
            $contributor->setLastname($faker->lastName());
            $contributor->setService($faker->word());
            $contributor->setOffice($faker->word());
            $contributor->setPosition($faker->jobTitle());
            $contributor->setRoles(['ROLE_CONTRIBUTOR']);
            $hashedPassword = $this->passwordHasher->hashPassword(
                $contributor,
                'contributorpassword'
            );

            $contributor->setPassword($hashedPassword);
            $manager->persist($contributor);
        }


        $contributor = new User();
        $contributor->setEmail('contributor@sf.com');
        $contributor->setFirstname('Bob');
        $contributor->setLastname('Dylan');
        $contributor->setService('Comptabilité');
        $contributor->setOffice('Lyon');
        $contributor->setPosition('Directeur');
        $contributor->setRoles(['ROLE_CONTRIBUTOR']);
        $hashedPassword = $this->passwordHasher->hashPassword(
            $contributor,
            'contributorpassword'
        );

        $contributor->setPassword($hashedPassword);
        $manager->persist($contributor);

        $admin = new User();
        $admin->setEmail('superadmin@sf.com');
        $admin->setFirstname('Quentin');
        $admin->setLastname('Tarantino');
        $admin->setService('Informatique');
        $admin->setOffice('Paris');
        $admin->setPosition('Assistant Manager');
        $admin->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'admin'
        );
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

        $manager->flush();
    }
}

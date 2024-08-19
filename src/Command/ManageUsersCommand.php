<?php

namespace App\Command;

use App\Entity\Office;
use App\Entity\User;
use App\Service\NewUserEmailSender;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

#[AsCommand(name: 'app:import-data')]
class ImportDataCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private Reader $csvReader;
    private UserPasswordHasherInterface $passwordHasher;
    private NewUserEmailSender $newUserEmailSender;

    public function __construct(
        NewUserEmailSender $newUserEmailSender,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
        $this->newUserEmailSender = $newUserEmailSender;
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
        $this->csvReader = Reader::createFromPath('assets\Import\data-I1oIuhWZh01D5tIN2mGsg.csv');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $faker = Factory::create();

        $filteredData = $this->filterFile();
        $newUsers = $filteredData['newUsers'];
        $formerUsers = $filteredData['formerUsers'];


        foreach ($newUsers as $user) {
            $workplaceName = $user['workplace'];

            $officeRepository = $this->entityManager->getRepository(Office::class);
            $office = $officeRepository->findOneBy(['location' => $workplaceName]);

            if (!$office) {
                $office = new Office();
                $office->setLocation($workplaceName);
                $this->entityManager->persist($office);
            }

            $email = $user['email'];

            $userRepository = $this->entityManager->getRepository(User::class);
            $newUser = $userRepository->findOneBy(['email' => $email]);

            if (!$newUser) {
                $newUser = new User();
                $newUser->setFirstName($user['firstName']);
                $newUser->setLastName($user['lastName']);
                $newUser->setContactNumber($user['phoneNumber']);
                $newUser->setEmail($user['email']);
                $newUser->setDepartment($user['department']);
                $newUser->setWorkplace($office);

                $password = $faker->password();
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $newUser,
                    $password
                );
                $newUser->setPassword($hashedPassword);

                $this->entityManager->persist($newUser);

                $this->newUserEmailSender->sendEmail($user, $password);
            }
        }

        foreach ($formerUsers as $user) {
            $email = $user['email'];

            $userRepository = $this->entityManager->getRepository(User::class);
            $formerUser = $userRepository->findOneBy(['email' => $email]);

            if ($formerUser) {
                $this->entityManager->remove($formerUser);
            }
        }

        $this->entityManager->flush();

        $newUsersCount = count($newUsers);
        $formerUsersCount = count($formerUsers);
        $output->writeln("Successfully added $newUsersCount new users.");
        $output->writeln("Successfully deleted $formerUsersCount former users.");

        return Command::SUCCESS;
    }



    protected function filterFile(): array
    {
        $this->csvReader->setDelimiter(',');
        $this->csvReader->setHeaderOffset(0);

        $records = $this->csvReader->getRecords();


        $formerUsers = [];
        $newUsers = [];

        foreach ($records as $record) {
            $action = $record['action'];

            $name = $record['name'];
                $nameParts = explode(' ', $name);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1];

            $phoneNumber = $record['phone'];
            $email = $record['email'];
            $department = $record['service'];
            $workplace = $record['agency'];

            if ($action === 'Leave') {
                $formerUsers[] = [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'phoneNumber' => $phoneNumber,
                    'email' => $email,
                    'department' => $department,
                    'workplace' => $workplace,
                ];
            } elseif ($action === 'Add') {
                $newUsers[] = [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'phoneNumber' => $phoneNumber,
                    'email' => $email,
                    'department' => $department,
                    'workplace' => $workplace,
                ];
            }
        }

        return [
            'newUsers' => $newUsers,
            'formerUsers' => $formerUsers
        ];
    }
}

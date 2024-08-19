<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordController extends AbstractController
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/change-password', name: 'app_change_password')]
    public function changePassword(Request $request, UserRepository $userRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isFirstConnection()) {
            return $this->redirectToRoute('app_home');
        }

        $newPassword = $request->get('new-password');

        if (isset($newPassword) && empty($newPassword)) {
            $this->addFlash('danger', 'Veuiller choisir un nouveaux mots de passe');
        }

        if (!empty($newPassword) && strlen($newPassword) <= 6) {
            $this->addFlash('danger', 'La taille du mot de passe doit etre supérieur à 6 caractères');
        }

        if (!empty($newPassword) && strlen($newPassword) >= 7) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $newPassword
            );
            $user->setPassword($hashedPassword);
            $user->setFirstConnection(false);
            $userRepository->save($user, true);

            $this->addFlash('success', 'Votre mot de passe a bien été mis à jour !
            Bienvenue sur notre plateforme d\'idéation !');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('login/passwordForm.html.twig');
    }
}

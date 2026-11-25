<?php

namespace App\Controller;

use App\Entity\Family;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_trees');
        }

        $error = null;
        $username = '';
        $familyName = '';

        if ($request->isMethod('POST')) {
            $username = trim($request->request->getString('username'));
            $password = (string) $request->request->getString('password');
            $familyName = trim($request->request->getString('familyName'));

            if ($username === '' || $password === '' || $familyName === '') {
                $error = 'Merci de remplir tous les champs.';
            } elseif ($entityManager->getRepository(User::class)->findOneBy(['username' => $username])) {
                $error = 'Ce nom de compte existe déjà.';
            } else {
                $user = new User();
                $user->setUsername($username);
                $user->setPassword($passwordHasher->hashPassword($user, $password));

                $family = (new Family())
                    ->setName($familyName)
                    ->setAccountIdentifier($username);

                $entityManager->persist($user);
                $entityManager->persist($family);
                $entityManager->flush();

                return $this->redirectToRoute('app_login', [
                    'registered' => 1,
                    'username' => $username,
                    'family' => $familyName,
                ]);
            }
        }

        return $this->render('security/register.html.twig', [
            'error' => $error,
            'username' => $username,
            'family_name' => $familyName,
        ]);
    }
}

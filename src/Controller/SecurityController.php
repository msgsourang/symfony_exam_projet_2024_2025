<?php

namespace App\Controller;

use App\Form\LoginForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\ClientType;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    public function postLogin(): Response
{
    $user = $this->getUser();

    if ($user === null || !$user->getStatut()) {
        $this->addFlash('error', 'Votre compte est désactivé.');
        return $this->redirectToRoute('app_login');
    }

    if ($this->isGranted('ROLE_ADMIN')) {
        return $this->redirectToRoute('admin_index');
    }

    if ($this->isGranted('ROLE_BOUTIQUIER')) {
        return $this->redirectToRoute('user_dashboard');
    }
    if ($this->isGranted('ROLE_CLIENT')) {
        return $this->redirectToRoute('role_client_index');
    }

    return $this->redirectToRoute('app_logout');
}


    
    #[Route('/admin/index', name: 'admin_index')]
        public function adminDashboard(): Response
        {
            return $this->render('admin/index.html.twig');
        }

        #[Route('/user/dashboard', name: 'user_dashboard')]
        public function userDashboard(): Response
        {
            return $this->render('user/dashboard.html.twig');
        }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode peut rester vide. Symfony intercepte automatiquement la déconnexion via le firewall.');
    }
}



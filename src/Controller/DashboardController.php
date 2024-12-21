<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'boutiquier_dashboard')]
    public function adminDashboard(): Response
    {
        return $this->render('dashboard/boutiquier.html.twig', [
            'message' => 'Bienvenue sur le tableau de bord du boutiquier!',
        ]);
    }

    #[Route('/vendeur', name: 'vendeur_dashboard')]
    public function vendeurDashboard(): Response
    {
        return $this->render('dashboard/vendeur.html.twig', [
            'message' => 'Bienvenue sur le tableau de bord du vendeur!',
        ]);
    }

    #[Route('/user', name: 'user_dashboard')]
    public function userDashboard(): Response
    {
        return $this->render('dashboard/user.html.twig', [
            'message' => 'Bienvenue sur le tableau de bord de l\'utilisateur!',
        ]);
    }
}

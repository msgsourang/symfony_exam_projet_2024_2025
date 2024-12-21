<?php
namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Entity\Dette;
use App\Entity\Client;
use App\Entity\Article;
use App\Entity\Demande;
use App\Enum\StatutEnum;
use App\Controller\ClientType;
use App\Repository\DetteRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $user->getPassword()
            );
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/dashboard', name: 'app_dashboard')]
public function dashboard(EntityManagerInterface $entityManager): Response
{
    $totalDettes = $entityManager->getRepository(Dette::class)
        ->createQueryBuilder('d')
        ->select('SUM(d.montant)')
        ->getQuery()
        ->getSingleScalarResult();

    if ($totalDettes === null) {
        $totalDettes = 0;
    }

    $nombreClients = $entityManager->getRepository(Client::class)->count([]);

    $demandesEnCours = $entityManager->getRepository(Demande::class)
    ->findBy(['statut' => StatutEnum::EN_COURS]);

    $articlesEnStock = $entityManager->getRepository(Article::class)
    ->createQueryBuilder('a')
    ->select('COUNT(a.id)')
    ->where('a.qteStock > 0') 
    ->getQuery()
    ->getSingleScalarResult();

    $articlesRupture = $entityManager->getRepository(Article::class)->findBy(['qteStock' => 0]);

    $clients = $entityManager->getRepository(Client::class)->findAll();


    return $this->render('user/dashboard.html.twig', [
        'totalDettes' => $totalDettes,
        'nombreClients' => $nombreClients,
        'articlesEnStock' => $articlesEnStock,
        'articlesRupture' => $articlesRupture,
        'clients' => $clients,
        'demandesEnCours' => $demandesEnCours,
    ]);
}

#[Route('/users/utilisateurs', name: 'utilisateurs')]
    public function userList(Request $request, UserRepository $userRepository): Response
    {
        $role = $request->query->get('role'); 
        $queryBuilder = $userRepository->createQueryBuilder('u');

        if ($role) {
            $queryBuilder->andWhere('u.roles LIKE :role')
                         ->setParameter('role', '%"'.$role.'"%');
        }

        $users = $queryBuilder->getQuery()->getResult();

        return $this->render('user/utilisateurs.html.twig', [
            'users' => $users,
            'role' => $role, 
        ]);
    }
    #[Route('/user/{id}/edit', name: 'user_edit')]
    public function edit(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->render('user/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/user/{id}/delete', name: 'user_delete')]
    public function delete(User $user, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('user_list'); 
    }







}



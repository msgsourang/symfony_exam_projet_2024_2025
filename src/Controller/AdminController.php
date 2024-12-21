<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Dette;
use App\Entity\Client;
use App\Entity\Article;
use App\Repository\DetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_index', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route('/admin/users', name: 'admin_manage_users', methods: ['GET'])]
public function manageUsers(
    Request $request,
    EntityManagerInterface $entityManager,
    PaginatorInterface $paginator,
    CsrfTokenManagerInterface $csrfTokenManager
): Response {
    $roleFilter = $request->query->get('role', '');
    $activeRoleFilter = $request->query->get('active_role', ''); 
    $statutFilter = $request->query->get('statut', '');

    $queryBuilder = $entityManager->getRepository(User::class)->createQueryBuilder('u');

    if ($roleFilter) {
        $queryBuilder
            ->andWhere('u.roles::TEXT LIKE :role')
            ->setParameter('role', '%"ROLE_' . strtoupper($roleFilter) . '"%');
    }

    if ($statutFilter !== '') {
        $queryBuilder
            ->andWhere('u.statut = :statut')
            ->setParameter('statut', (bool)$statutFilter);
    }

    $usersQuery = $queryBuilder->getQuery();

    $users = $paginator->paginate(
        $usersQuery,
        $request->query->getInt('page', 1),
        10
    );

    $activeUsersQuery = $entityManager->getRepository(User::class)
        ->createQueryBuilder('u')
        ->where('u.statut = :statut')
        ->setParameter('statut', true);

    if ($activeRoleFilter) {
        $activeUsersQuery
            ->andWhere('u.roles::TEXT LIKE :active_role')
            ->setParameter('active_role', '%"ROLE_' . strtoupper($activeRoleFilter) . '"%');
    }

    $activeUsers = $activeUsersQuery->getQuery()->getResult();

    $csrfToken = $csrfTokenManager->getToken('toggle-statut');

    return $this->render('admin/manage_users.html.twig', [
        'users' => $users,
        'activeUsers' => $activeUsers,
        'roleFilter' => $roleFilter,
        'activeRoleFilter' => $activeRoleFilter,
        'csrf_token' => $csrfToken->getValue(),
    ]);
}




    #[Route('/admin/users/toggle-statut/{id}', name: 'admin_toggle_user_status', methods: ['POST'])]
    public function toggleUserStatus(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $logger->info('Requête reçue', ['method' => $request->getMethod(), 'id' => $id]);

            $csrfToken = $request->headers->get('X-CSRF-Token');
            if (!$csrfTokenManager->isTokenValid(new CsrfToken('toggle-statut', $csrfToken))) {
                $logger->error('Token CSRF invalide', ['csrf_token' => $csrfToken]);
                return $this->json(['success' => false, 'message' => 'Token CSRF invalide.'], 403);
            }

            $user = $entityManager->getRepository(User::class)->find($id);
            if (!$user) {
                $logger->error('Utilisateur introuvable', ['id' => $id]);
                return $this->json(['success' => false, 'message' => 'Utilisateur introuvable.'], 404);
            }

            $logger->info('Changement de statut utilisateur', ['id' => $id, 'ancien_statut' => $user->getStatut()]);
            $user->setStatut(!$user->getStatut());
            $entityManager->flush();

            $logger->info('Statut modifié avec succès', ['nouveau_statut' => $user->getStatut()]);
            return $this->json([
                'success' => true,
                'message' => $user->getStatut() ? 'Utilisateur activé avec succès.' : 'Utilisateur désactivé avec succès.',
            ]);
        } catch (\Exception $e) {
            $logger->error('Erreur interne', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->json(['success' => false, 'message' => 'Erreur interne du serveur.'], 500);
        }
    }

    #[Route('/admin/articles', name: 'admin_manage_articles', methods: ['GET'])]
public function manageArticles(Request $request, EntityManagerInterface $entityManager): Response
{
    $libelleFilter = $request->query->get('libelle', ''); 
    $availableFilter = $request->query->get('availableFilter', ''); 

    $queryBuilder = $entityManager->getRepository(Article::class)->createQueryBuilder('a');

    if ($libelleFilter) {
        $queryBuilder
            ->andWhere('a.nom LIKE :libelle')
            ->setParameter('libelle', '%' . $libelleFilter . '%');
    }

    if ($availableFilter === 'available') {
        $queryBuilder
            ->andWhere('a.qteStock > 0');
    } elseif ($availableFilter === 'unavailable') {
        $queryBuilder
            ->andWhere('a.qteStock = 0');
    }

    $articles = $queryBuilder->getQuery()->getResult();

    return $this->render('admin/manage_articles.html.twig', [
        'articles' => $articles,
        'libelleFilter' => $libelleFilter, 
        'availableFilter' => $availableFilter, 
    ]);
}


#[Route('/admin/articles/create', name: 'admin_create_article', methods: ['POST'])]
public function createArticle(Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (!isset($data['nom'], $data['prix'], $data['qteStock'])) {
        return $this->json(['success' => false, 'message' => 'Données manquantes.'], 400);
    }

    $article = new Article();
    $article->setNom($data['nom']);
    $article->setPrix($data['prix']);
    $article->setQteStock($data['qteStock']);

    $entityManager->persist($article);
    $entityManager->flush();

    return $this->json(['success' => true, 'message' => 'Article créé avec succès.']);
}


    #[Route('/admin/users/create', name: 'admin_create_user', methods: ['GET', 'POST'])]
    public function createUser(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
    
            $login = $data['login'];
            $email = $data['email'];
            $role = $data['role'];
            $password = $data['password'];
            $clientId = $data['clientId'] ?? null; 
    
            if (empty($username) || empty($email) || empty($role) || empty($password)) {
                return $this->render('admin/create_user.html.twig', [
                    'error' => 'Tous les champs sont requis.',
                ]);
            }
    
            $client = null;
            if ($clientId) {
                $client = $entityManager->getRepository(Client::class)->find($clientId);
                if (!$client) {
                    return $this->render('admin/create_user.html.twig', [
                        'error' => 'Client non trouvé.',
                    ]);
                }
            }
    
            $user = new User();
            $user->setLogin($login);
            $user->setEmail($email);
            $user->setRoles([$role]);
            $user->setPassword($passwordHasher->hash($password));  
    
            if ($client) {
                $user->setClient($client); 
            }
    
            $entityManager->persist($user);
            $entityManager->flush();
    
            $this->addFlash('success', 'Utilisateur créé avec succès !');
    
            return $this->redirectToRoute('admin_manage_users');
        }
    
        $clientsSansCompte = $entityManager->getRepository(Client::class)->findClientsSansCompte();
    
        $users = $entityManager->getRepository(User::class)->findAll();
    
        return $this->render('admin/create_user.html.twig', [
            'clientsSansCompte' => $clientsSansCompte,
            'users' => $users,  
        ]);
    }
    
#[Route('/admin/archive_dette', name: 'admin_archive_dette')]
public function archiveDette(DetteRepository $detteRepository): Response
{
    $dettes = $detteRepository->findBy(['statut' => 'solder', 'archived' => false]);

    $dettesArchivees = $detteRepository->findBy(['archived' => true]);

    return $this->render('admin/archive_dette.html.twig', [
        'dettes' => $dettes,
        'dettesArchivees' => $dettesArchivees
    ]);
}

    
#[Route('/admin/dette/archive/{id}', name: 'admin_archive', methods: ['POST'])]
public function archive(Dette $dette, EntityManagerInterface $entityManager): Response
{
    $dette->setArchived(true);
    $entityManager->flush();

    $this->addFlash('success', 'Dette archivée avec succès.');

    return $this->redirectToRoute('admin_archive_dette');
}

#[Route('/admin/dette/desarchive/{id}', name: 'admin_desarchever', methods: ['POST'])]
public function desarchiveDette(Dette $dette, EntityManagerInterface $entityManager): Response
{
    $dette->setArchived(false);
    $entityManager->flush();

    $this->addFlash('success', 'Dette désarchivée avec succès.');

    return $this->redirectToRoute('admin_archive_dette');
}



    

}

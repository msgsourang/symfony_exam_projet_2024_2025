<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Dette;
use App\Entity\Client;
use App\Entity\Article;
use App\Entity\Demande;
use App\Form\DemandeDetteType;
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

class RoleClientController extends AbstractController
{

    #[Route('/role-client/index', name: 'role_client_index')]
    public function index():Response {
        return $this->render('roleclient/index.html.twig');
    }




#[Route('/role-client/dettes', name: 'client_dettes')]
public function listerDettesNonSoldes(
    Request $request,
    EntityManagerInterface $entityManager,
    PaginatorInterface $paginator
): Response {
    $user = $this->getUser();

    if (!$user) {
        throw $this->createAccessDeniedException('Accès refusé. Vous devez être connecté pour accéder à vos dettes.');
    }

    $client = $user->getClient();

    if (!$client) {
        $this->addFlash('error', 'Aucun client associé à cet utilisateur. Veuillez contacter l\'administration.');
        return $this->redirectToRoute('role_client_index');
    }

    $queryBuilder = $entityManager->getRepository(Dette::class)->createQueryBuilder('d')
        ->where('d.client = :client')
        ->andWhere('d.statut = :statut')
        ->setParameter('client', $client)
        ->setParameter('statut', 'non_solde')
        ->orderBy('d.date', 'DESC');

    $query = $queryBuilder->getQuery();
    $dettes = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        10
    );

    return $this->render('roleclient/dettes.html.twig', [
        'dettes' => $dettes,
    ]);
}





#[Route('/role-client/dettes/demande', name: 'client_demande_dette')]
public function demanderDette(Request $request, EntityManagerInterface $entityManager): Response
{
    $user = $this->getUser ();
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    $client = $user->getClient();
    if (!$client) {
        $this->addFlash('error', 'Aucun client associé.');
        return $this->redirectToRoute('role_client_index');
    }

    $articles = $entityManager->getRepository(Article::class)->findAll();

    $demande = new Demande();
    $demande->setClient($client);

    if ($request->isMethod('POST')) {
        $montant = $request->request->get('montant');
        $demande->setMontant($montant);
        
        $articleData = $request->request->get('articles', []);
        foreach ($articleData as $articleId => $quantity) {
            if ($quantity > 0) {
                $article = $entityManager->getRepository(Article::class)->find($articleId);
                if ($article) {
                    $demandeArticle = new DemandeArticle();
                    $demandeArticle->setDemande($demande);
                    $demandeArticle->setArticle($article);
                    $demandeArticle->setQuantite($quantity);
                    $entityManager->persist($demandeArticle);
                }
            }
        }

        $entityManager->persist($demande);
        $entityManager->flush();

        $this->addFlash('success', 'Votre demande de dette a été soumise avec succès.');
        return $this->redirectToRoute('role_client_index');
    }

    return $this->render('roleclient/demande_dette.html.twig', [
        'articles' => $articles,
    ]);
}

#[Route('/role-client/demandes', name: 'client_demandes')]
public function listerDemandes(Request $request, EntityManagerInterface $entityManager): Response
{
    $client = $this->getUser();

    if (!$client) {
        throw $this->createAccessDeniedException('Accès refusé.');
    }

    $etat = $request->query->get('etat', ''); 

    $queryBuilder = $entityManager->getRepository(Demande::class)->createQueryBuilder('d')
        ->where('d.client = :client')
        ->setParameter('client', $client);

    if ($etat) {
        $queryBuilder->andWhere('d.etat = :etat')
            ->setParameter('etat', $etat);
    }

    $demandes = $queryBuilder->getQuery()->getResult();

    return $this->render('roleclient/demandes.html.twig', [
        'demandes' => $demandes,
        'etat' => $etat,
    ]);
}
#[Route('/role-client/demandes/{id}/relancer', name: 'client_relancer_demande')]
public function relancerDemande(int $id, EntityManagerInterface $entityManager): Response
{
    $client = $this->getUser();

    if (!$client) {
        throw $this->createAccessDeniedException('Accès refusé.');
    }

    $demande = $entityManager->getRepository(Demande::class)->find($id);

    if (!$demande || $demande->getClient() !== $client) {
        throw $this->createNotFoundException('Demande non trouvée.');
    }

    if ($demande->getEtat() !== 'annule') {
        throw $this->createAccessDeniedException('Cette demande ne peut pas être relancée.');
    }

    $demande->setEtat('en_cours');
    $entityManager->flush();

    $this->addFlash('success', 'La demande a été relancée.');
    return $this->redirectToRoute('client_demandes');
}

#[Route('/role-client/dettes/liste', name: 'client_liste_dettes')]
public function listeDettes(EntityManagerInterface $entityManager): Response
{
    $user = $this->getUser ();
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    $client = $user->getClient();
    if (!$client) {
        $this->addFlash('error', 'Aucun client associé.');
        return $this->redirectToRoute('role_client_index');
    }

    $demandes = $entityManager->getRepository(Demande::class)->findBy(['client' => $client]);

    return $this->render('roleclient/liste_demande.html.twig', [
        'demandes' => $demandes,
    ]);
}
#[Route('/role-client/relance', name: 'relance')]
public function listerDettes(
    Request $request,
    EntityManagerInterface $entityManager,
    PaginatorInterface $paginator
): Response {
    $user = $this->getUser ();

    if (!$user) {
        throw $this->createAccessDeniedException('Accès refusé. Vous devez être connecté pour accéder à vos dettes.');
    }

    $client = $user->getClient();

    if (!$client) {
        $this->addFlash('error', 'Aucun client associé à cet utilisateur. Veuillez contacter l\'administration.');
        return $this->redirectToRoute('role_client_index');
    }

    $queryBuilder = $entityManager->getRepository(Dette::class)->createQueryBuilder('d')
        ->where('d.client = :client')
        ->andWhere('d.statut IN (:statuts)')
        ->setParameter('client', $client)
        ->setParameter('statuts', ['non_solde', 'annule']) 
        ->orderBy('d.date', 'DESC');

    $query = $queryBuilder->getQuery();
    $dettes = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        10
    );

    return $this->render('roleclient/relance.html.twig', [
        'dettes' => $dettes,
    ]);
}

}

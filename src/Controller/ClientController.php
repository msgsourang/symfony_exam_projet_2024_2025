<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\User;
use App\Entity\Dette;
use App\Form\ClientType;
use App\Controller\DetteController;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClientController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/clients', name: 'client_index')]
    public function index(Request $request, PaginatorInterface $paginator, EntityManagerInterface $entityManager): Response
{
    $prenomFilter = $request->query->get('prenom');
    $nomFilter = $request->query->get('nom');
    $telephoneFilter = $request->query->get('telephone');

    $queryBuilder = $entityManager->getRepository(Client::class)->createQueryBuilder('c')
        ->leftJoin('c.dettes', 'd') 
        ->groupBy('c.id'); 

    if ($prenomFilter) {
        $queryBuilder->andWhere('c.prenom LIKE :prenom')
                     ->setParameter('prenom', '%' . $prenomFilter . '%');
    }

    if ($nomFilter) {
        $queryBuilder->andWhere('c.nom LIKE :nom')
                     ->setParameter('nom', '%' . $nomFilter . '%');
    }

    if ($telephoneFilter) {
        $queryBuilder->andWhere('c.telephone LIKE :telephone')
                     ->setParameter('telephone', '%' . $telephoneFilter . '%');
    }

    $clientsQuery = $queryBuilder->getQuery();

    $clients = $paginator->paginate(
        $clientsQuery,
        $request->query->getInt('page', 1),
        10 
    );

    
    return $this->render('client/index.html.twig', [
        'clients' => $clients,
        'prenomFilter' => $prenomFilter,
        'nomFilter' => $nomFilter,
        'telephoneFilter' => $telephoneFilter,
    ]);
}
#[Route('/clients/{id}/details', name: 'client_details')]
public function details(int $id, EntityManagerInterface $entityManager): Response
{
    $client = $entityManager->getRepository(Client::class)->find($id);

    if (!$client) {
        throw $this->createNotFoundException('Client introuvable.');
    }

    $dettes = $entityManager->getRepository(Dette::class)->findBy(['client' => $client]);

    $montantTotal = 0;
    $montantVerse = 0;

    foreach ($dettes as $dette) {
        $montantTotal += $dette->getMontant();
        $montantVerse += $dette->getMontantVerser();
    }

    $montantRestant = $montantTotal - $montantVerse;

    return $this->render('client/details.html.twig', [
        'client' => $client,
        'dettes' => $dettes, 
        'montantTotal' => $montantTotal,
        'montantVerse' => $montantVerse,
        'montantRestant' => $montantRestant,
    ]);
}


#[Route('/clients/add', name: 'client_add', methods: ['POST'])]
public function addClient(Request $request, EntityManagerInterface $entityManager): Response
{
    $data = $request->request->all();

    $client = new Client();
    $client->setSurname($data['surname'] ?? null);
    $client->setNom($data['nom'] ?? null);
    $client->setPrenom($data['prenom'] ?? null);
    $client->setTelephone($data['telephone'] ?? null);
    $client->setAdresse($data['adresse'] ?? null);

    $entityManager->persist($client);

    if (!empty($data['detteMontant']) && !empty($data['detteDate'])) {
        $dette = new Dette();
        $dette->setMontant((float)$data['detteMontant']);
        $dette->setDate(new \DateTime($data['detteDate']));
        $dette->setClient($client);
        $dette->setMontantVerser(0); 
        $dette->setStatut('non_solde'); 

        $entityManager->persist($dette);
    }

    $entityManager->flush();

    $this->addFlash('success', 'Client ajouté avec succès.');

    return $this->redirectToRoute('client_index');
}



    #[Route('/articles/add', name: 'article_add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['success' => false, 'message' => 'Requête non valide.'], 400);
        }

        $data = json_decode($request->getContent(), true);

        $nom = $data['nom'] ?? null;
        $prix = $data['prix'] ?? null;
        $qteStock = $data['qte_stock'] ?? null;

        if (!$nom || !$prix || !$qteStock) {
            return $this->json(['success' => false, 'message' => 'Veuillez remplir tous les champs.'], 400);
        }

        $article = new Article();
        $article->setNom($nom)
                ->setPrix((float) $prix)
                ->setQteStock((int) $qteStock)
                ->setQteRestante((int) $qteStock);

        $entityManager->persist($article);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Article ajouté avec succès.',
        ]);
    }

    #[Route('/articles/save-selection', name: 'save_selection', methods: ['POST'])]
public function saveSelection(Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (!$data || !is_array($data)) {
        return new JsonResponse(['success' => false, 'message' => 'Données non valides.'], 400);
    }

    foreach ($data as $item) {
        if (empty($item['id']) || empty($item['quantite'])) {
            return new JsonResponse(['success' => false, 'message' => 'ID ou quantité manquant.'], 400);
        }

        $article = $entityManager->getRepository(Article::class)->find($item['id']);
        if (!$article) {
            return new JsonResponse(['success' => false, 'message' => "Article introuvable : {$item['id']}."], 404);
        }

        $quantite = (int)$item['quantite'];
        if ($article->getQteStock() < $quantite) {
            return new JsonResponse(['success' => false, 'message' => "Stock insuffisant pour : {$article->getNom()}."], 400);
        }

        $approvisionnement = new Approvisionnement();
        $approvisionnement->setArticle($article);
        $approvisionnement->setQuantite($quantite);
        $approvisionnement->setPrix($article->getPrix() * $quantite);

        $article->setQteStock($article->getQteStock() - $quantite);

        $entityManager->persist($approvisionnement);
        $entityManager->persist($article);
    }

    $entityManager->flush();

    return new JsonResponse(['success' => true, 'message' => 'Sauvegarde réussie.']);
}
}

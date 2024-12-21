<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Approvisionnement;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ApprovisionnementType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class ArticleController extends AbstractController
{
    #[Route('/articles', name: 'article_index')]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $libelleFilter = $request->query->get('libelle');

        $queryBuilder = $entityManager->getRepository(Article::class)->createQueryBuilder('a');

        if ($libelleFilter) {
            $queryBuilder->where('a.nom LIKE :libelle')
                         ->setParameter('libelle', '%' . $libelleFilter . '%');
        }

        $articlesQuery = $queryBuilder->getQuery();

        $articles = $paginator->paginate(
            $articlesQuery,
            $request->query->getInt('page', 1), 
            10 
        );

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
            'libelleFilter' => $libelleFilter,
        ]);
    }

    #[Route('/articles/add', name: 'article_add', methods: ['POST'])]
public function add(Request $request, EntityManagerInterface $entityManager): Response
{
    if (!$request->isXmlHttpRequest()) {
        return $this->json(['success' => false, 'message' => 'Requête non valide.'], 400);
    }

    $nom = $request->request->get('nom');
    $prix = $request->request->get('prix');
    $qteStock = $request->request->get('qte_stock');

    if (empty($nom) || empty($prix) || empty($qteStock)) {
        return $this->json(['success' => false, 'message' => 'Veuillez remplir tous les champs.'], 400);
    }

    $article = new Article();
    $article->setNom($nom);
    $article->setPrix((float) $prix);
    $article->setQteStock((int) $qteStock);
    $article->setQteRestante((int) $qteStock);

    $entityManager->persist($article);
    $entityManager->flush();

    return $this->json([
        'success' => true,
        'message' => 'Article ajouté avec succès.',
        'article' => [
            'id' => $article->getId(),
            'nom' => $article->getNom(),
            'prix' => $article->getPrix(),
            'qteStock' => $article->getQteStock(),
        ],
    ]);
}
#[Route('/articles/save-selection', name: 'save_selection', methods: ['POST'])]
public function saveSelection(Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $logger->info('Méthode HTTP utilisée : ' . $request->getMethod());
    try {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR); 
    } catch (\JsonException $e) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Données JSON invalides : ' . $e->getMessage(),
        ], 400);
    }

    if (!$data || !is_array($data)) {
        return new JsonResponse(['success' => false, 'message' => 'Données non valides.'], 400);
    }

    foreach ($data as $item) {
        if (empty($item['id']) || empty($item['quantite']) || $item['quantite'] <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'ID ou quantité manquants ou invalides.'], 400);
        }

        $article = $entityManager->getRepository(Article::class)->find($item['id']);
        if (!$article) {
            return new JsonResponse(['success' => false, 'message' => "Article introuvable pour l'ID : {$item['id']}."], 404);
        }

        $quantite = (int) $item['quantite'];

        if ($article->getQteStock() < $quantite) {
            return new JsonResponse(['success' => false, 'message' => "Stock insuffisant pour l'article : {$article->getNom()}."], 400);
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

    return new JsonResponse(['success' => true, 'message' => 'Les articles ont été sauvegardés avec succès.']);
}
#[Route('/articles/update-stock/{id}', name: 'article_update_stock', methods: ['POST'])]
public function updateStock(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (!isset($data['qteStock']) || $data['qteStock'] < 0) {
        return new JsonResponse(['success' => false, 'message' => 'Quantité non valide.'], 400);
    }

    $article = $entityManager->getRepository(Article::class)->find($id);
    if (!$article) {
        return new JsonResponse(['success' => false, 'message' => 'Article introuvable.'], 404);
    }

    $article->setQteStock((int) $data['qteStock']);

    try {
        $entityManager->flush();
        return new JsonResponse(['success' => true, 'message' => 'Quantité mise à jour avec succès.']);
    } catch (\Exception $e) {
        return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la mise à jour.'], 500);
    }
}
#[Route('/approvisionnements/new', name: 'approvisionnement_new')]
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    $articlesDisponibles = $entityManager->getRepository(Article::class)
        ->createQueryBuilder('a')
        ->where('a.qteStock > 0') 
        ->getQuery()
        ->getResult();

    if (empty($articlesDisponibles)) {
        $this->addFlash('error', 'Aucun article disponible pour l’approvisionnement.');
        return $this->redirectToRoute('article_index');
    }

    $form = $this->createForm(ApprovisionnementType::class, null, [
        'articles' => $articlesDisponibles, 
    ]);

    return $this->render('approvisionnement/new.html.twig', [
        'form' => $form->createView(),
        'articles' => $articlesDisponibles, 
    ]);
}




#[Route('/approvisionnements/save', name: 'approvisionnement_save', methods: ['POST'])]
public function saveApprovisionnement(Request $request, EntityManagerInterface $entityManager): Response
{
    // Récupère les données soumises
    $articlesIds = $request->request->get('articles'); // Récupère une liste d'IDs
    $quantites = $request->request->get('quantites'); // Récupère les quantités associées

    if (!$articlesIds || !$quantites || count($articlesIds) !== count($quantites)) {
        $this->addFlash('error', 'Veuillez sélectionner des articles et indiquer les quantités.');
        return $this->redirectToRoute('approvisionnement_new');
    }

    foreach ($articlesIds as $key => $articleId) {
        // Récupérer l'article correspondant à l'ID
        $article = $entityManager->getRepository(Article::class)->find($articleId);

        if (!$article) {
            $this->addFlash('error', "Article avec l'ID {$articleId} introuvable.");
            return $this->redirectToRoute('approvisionnement_new');
        }

        $quantite = (int)$quantites[$key];

        if ($quantite <= 0 || $quantite > $article->getQteStock()) {
            $this->addFlash('error', "Stock insuffisant ou quantité invalide pour l'article : {$article->getNom()}.");
            return $this->redirectToRoute('approvisionnement_new');
        }

        // Créer un nouvel approvisionnement
        $approvisionnement = new Approvisionnement();
        $approvisionnement->setArticle($article);
        $approvisionnement->setQuantite($quantite);
        $approvisionnement->setPrix($article->getPrix() * $quantite);

        // Mettre à jour le stock
        $article->setQteStock($article->getQteStock() - $quantite);

        $entityManager->persist($approvisionnement);
        $entityManager->persist($article);
    }

    $entityManager->flush();

    $this->addFlash('success', 'Approvisionnement sauvegardé avec succès.');
    return $this->redirectToRoute('article_index');
}

}
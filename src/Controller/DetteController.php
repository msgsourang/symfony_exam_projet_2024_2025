<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Dette;
use App\Entity\Demande;
use App\Form\DetteType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DetteController extends AbstractController
{
    #[Route('/clients/{clientId}/dettes', name: 'client_dettes')]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator, int $clientId): Response
    {
        $client = $entityManager->getRepository(Client::class)->find($clientId);
        
        if (!$client) {
            throw $this->createNotFoundException('Client non trouvé.');
        }

        $queryBuilder = $entityManager->getRepository(Dette::class)->createQueryBuilder('d')
            ->where('d.client = :client')
            ->setParameter('client', $client);

        $query = $queryBuilder->getQuery();

        $dettes = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6 
        );

        $totalMontant = $entityManager->getRepository(Dette::class)->createQueryBuilder('d')
            ->select('SUM(d.montant)')
            ->where('d.client = :client')
            ->setParameter('client', $client)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $totalMontantVerser = $entityManager->getRepository(Dette::class)->createQueryBuilder('d')
            ->select('SUM(d.montantVerser)')
            ->where('d.client = :client')
            ->setParameter('client', $client)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $totalDue = $totalMontant - $totalMontantVerser;

        return $this->render('client/dettes.html.twig', [
            'client' => $client,
            'dettes' => $dettes,
            'totalDettes' => $totalDettes,
            'totalDue' => $totalDue,
        ]);
    }

    #[Route('/clients/{clientId}/dettes/ajouter', name: 'dette_add')]
    public function add(Request $request, EntityManagerInterface $entityManager, int $clientId): Response
    {
        $client = $entityManager->getRepository(Client::class)->find($clientId);
        
        if (!$client) {
            throw $this->createNotFoundException('Client non trouvé.');
        }

        $dette = new Dette();
        $form = $this->createForm(DetteType::class, $dette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dette->setClient($client);

            $montantRestant = $dette->getMontant() - ($dette->getMontantVerser() ?? 0);
            $dette->setStatut(abs($montantRestant) < 0.01 ? 'solder' : 'non_solde');

            $entityManager->persist($dette);
            $entityManager->flush();

            $this->addFlash('success', 'La dette a été ajoutée avec succès.');
            return $this->redirectToRoute('client_dettes', ['clientId' => $clientId]);
        }

        return $this->render('dette/add.html.twig', [
            'form' => $form->createView(),
            'client' => $client,
        ]);
    }

    #[Route('/dettes', name: 'dette_index')]
public function indexAll(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
{
    $clientId = $request->query->get('client_id');

    $queryBuilder = $entityManager->getRepository(Dette::class)->createQueryBuilder('d')
        ->where('d.archived = :archived')
        ->setParameter('archived', false);

    if ($clientId) {
        $queryBuilder->andWhere('d.client = :client')
            ->setParameter('client', $entityManager->getRepository(Client::class)->find($clientId));
    }

    $dettes = $paginator->paginate(
        $queryBuilder,
        $request->query->getInt('page', 1),
        10
    );

    $clients = $entityManager->getRepository(Client::class)->findAll();

    return $this->render('dette/index.html.twig', [
        'dettes' => $dettes,
        'clients' => $clients, 
        'clientId' => $clientId, 
    ]);
}

    #[Route('/dettes/{id}/details', name: 'dette_details')]
public function details(int $id, EntityManagerInterface $entityManager): Response
{
    $dette = $entityManager->getRepository(Dette::class)->find($id);

    if (!$dette) {
        throw $this->createNotFoundException('Dette introuvable.');
    }

    $demande = $dette->getDemande();
    $demandeArticles = $demande ? $demande->getDemandeArticles() : [];

    return $this->render('dette/details.html.twig', [
        'dette' => $dette,
        'articles' => $demandeArticles,
        'hasDemande' => $demande !== null, 
    ]);
}




#[Route('/paiements/{detteId}/ajouter', name: 'paiement_add', methods: ['POST'])]
public function addPaiement(Request $request, int $detteId, EntityManagerInterface $entityManager): Response
{
    $montant = (float) $request->request->get('montant');
    $dette = $entityManager->getRepository(Dette::class)->find($detteId);

    if (!$dette) {
        throw $this->createNotFoundException('Dette introuvable.');
    }

    if ($dette->getStatut() === 'solder') {
        $this->addFlash('error', 'Cette dette est déjà soldée. Aucun paiement supplémentaire n\'est autorisé.');
        return $this->redirectToRoute('dette_details', ['id' => $detteId]);
    }

    $montantRestant = $dette->getMontantRestant();
    if ($montant > $montantRestant) {
        $this->addFlash('error', 'Le montant saisi dépasse le montant restant à payer.');
        return $this->redirectToRoute('dette_details', ['id' => $detteId]);
    }

    $montantVerser = ($dette->getMontantVerser() ?? 0) + $montant;
    $dette->setMontantVerser($montantVerser);

    $entityManager->flush();

    $this->addFlash('success', 'Paiement ajouté avec succès.');
    return $this->redirectToRoute('dette_details', ['id' => $detteId]);
}


#[Route('/dettes/ajouter', name: 'dette_add', methods: ['POST'])]
public function addDette(Request $request, EntityManagerInterface $entityManager): Response
{
    $montant = $request->request->get('montant');
    $date = $request->request->get('date');
    $clientId = $request->request->get('client');

    $client = $entityManager->getRepository(Client::class)->find($clientId);

    if (!$client || !$montant || !$date) {
        throw $this->createNotFoundException('Données invalides.');
    }

    $dette = new Dette();
    $dette->setMontant((float)$montant);
    $dette->setDate(new \DateTime($date));
    $dette->setClient($client);
    $dette->setMontantVerser(0); 
    $dette->setStatut('non_solde');

    $entityManager->persist($dette);
    $entityManager->flush();

    $this->addFlash('success', 'La dette a été ajoutée avec succès.');
    return $this->redirectToRoute('dette_index');
}


}

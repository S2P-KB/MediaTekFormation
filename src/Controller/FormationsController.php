<?php
namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CategorieRepository;
use App\Repository\FormationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Formation;
use App\Form\FormationType;



/**
 * Controleur des formations
 *
 * @author emds
 */
class FormationsController extends AbstractController
{

    /**
     *
     * @var FormationRepository
     */
    private $formationRepository;
    
    /**
     *
     * @var CategorieRepository
     */
    private $categorieRepository;
    
    private const TEMPLATE_FORMATIONS = "pages/formations.html.twig";
    
    public function __construct(FormationRepository $formationRepository, CategorieRepository $categorieRepository)
    {
        $this->formationRepository = $formationRepository;
        $this->categorieRepository= $categorieRepository;
    }
    
    #[Route('/formations', name: 'formations')]
    public function index(): Response
    {
        $formations = $this->formationRepository->findAll();
        $categories = $this->categorieRepository->findAll();
        return $this->render(self::TEMPLATE_FORMATIONS, [
            'formations' => $formations,
            'categories' => $categories
        ]);
    }

    #[Route('/formations/tri/{champ}/{ordre}/{table}', name: 'formations.sort')]
    public function sort($champ, $ordre, $table=""): Response
    {
        $formations = $this->formationRepository->findAllOrderBy($champ, $ordre, $table);
        $categories = $this->categorieRepository->findAll();
        return $this->render(self::TEMPLATE_FORMATIONS, [
            'formations' => $formations,
            'categories' => $categories
        ]);
    }

    #[Route('/formations/recherche/{champ}/{table}', name: 'formations.findallcontain')]
    public function findAllContain($champ, Request $request, $table=""): Response
    {
        $valeur = $request->get("recherche");
        $formations = $this->formationRepository->findByContainValue($champ, $valeur, $table);
        $categories = $this->categorieRepository->findAll();
        return $this->render(self::TEMPLATE_FORMATIONS, [
            'formations' => $formations,
            'categories' => $categories,
            'valeur' => $valeur,
            'table' => $table
        ]);
    }

    #[Route('/formations/formation/{id}', name: 'formations.showone')]
    public function showOne($id): Response
    {
        $formation = $this->formationRepository->find($id);
        return $this->render("pages/formation.html.twig", [
            'formation' => $formation
        ]);
    }
    
    #[Route('/formations/ajouter', name: 'formations.create')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $formation = new Formation();
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($formation);
            $entityManager->flush();
            $this->addFlash('success', 'Formation ajoutée avec succès.');
            return $this->redirectToRoute('formations');
        }

        return $this->render('pages/formulaire.html.twig', [
            'form' => $form->createView(),
            'titre' => 'Ajouter une formation'
        ]);
    }
    
    #[Route('/formations/modifier/{id}', name: 'formations.edit')]
    public function edit($id, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupère la formation existante depuis la base de données
        $formation = $this->formationRepository->find($id);

        if (!$formation) {
            throw $this->createNotFoundException('Formation non trouvée.');
        }

        // Crée le formulaire de modification en pré-remplissant avec les données de la formation
        $form = $this->createForm(FormationType::class, $formation);

        $form->handleRequest($request);

        // Si le formulaire est soumis et est valide, alors on persiste les modifications
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();  // Mise à jour de la formation dans la base de données
            $this->addFlash('success', 'Formation modifiée avec succès.');
            return $this->redirectToRoute('formations');  // Redirection vers la liste des formations
        }

        // Affiche le formulaire avec les données pré-remplies
        return $this->render('pages/formulaire.html.twig', [
            'form' => $form->createView(),
            'titre' => 'Modifier une formation'
        ]);
    }
    
    #[Route('/formations/supprimer/{id}', name: 'formations.delete', methods: ['POST'])]
    public function delete(int $id, FormationRepository $formationRepository, EntityManagerInterface $entityManager): Response
    {
        $formation = $formationRepository->find($id);

        if (!$formation) {
            throw $this->createNotFoundException("Formation non trouvée.");
        }

        // Suppression de la formation de la playlist
        if ($formation->getPlaylist()) {
            $formation->getPlaylist()->removeFormation($formation);
        }

        $entityManager->remove($formation);
        $entityManager->flush();

        // Redirection vers la liste des formations avec un message de succès
        $this->addFlash('success', 'Formation supprimée avec succès.');
        return $this->redirectToRoute('formations');
    }

}

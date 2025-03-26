<?php
namespace App\Controller;

use App\Repository\CategorieRepository;
use App\Repository\FormationRepository;
use App\Repository\PlaylistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Description of PlaylistsController
 *
 * @author emds
 */
#[Route('')]
class PlaylistsController extends AbstractController
{
    
    /**
     *
     * @var PlaylistRepository
     */
    private $playlistRepository;
    
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
    
    private const TEMPLATE_PLAYLISTS = "pages/playlists.html.twig";

    public function __construct(
        PlaylistRepository $playlistRepository,
        CategorieRepository $categorieRepository,
        FormationRepository $formationRespository
        ) {
        $this->playlistRepository = $playlistRepository;
        $this->categorieRepository = $categorieRepository;
        $this->formationRepository = $formationRespository;
    }
    
    
    #[Route('/playlists/{sort}', name:'playlists')]
    public function index(
            PlaylistRepository $playlistRepository, 
            CategorieRepository $categoryRepository, 
            string $sort):Response
    {
        $order = 'ASC';
        
        if ($sort === 'formations_asc') {
            $playlists = $playlistRepository->findAllSortedByFormations('ASC');
        } elseif ($sort === 'formations_desc') {
            $playlists = $playlistRepository->findAllSortedByFormations('DESC');
        } else {
            $playlists = $playlistRepository->findBy([], [$sort => $order]);
        }
        
        $categories = $categoryRepository->findAll();

        return $this->render('pages/playlists.html.twig', [
            'playlists' => $playlists,
            'categories' => $categories
        ]);
    }

    #[Route('/tri/{champ}/{ordre}', name: 'playlists.sort')]
    public function sort($champ, $ordre): Response
    {
        $playlists = [];

        if ($champ === "name") {
            $playlists = $this->playlistRepository->findAllOrderByName($ordre);
        } elseif ($champ === "formations") {
            $playlists = $this->playlistRepository->findAllSortedByFormations($ordre);
        }

        $categories = $this->categorieRepository->findAll();

        return $this->render(self::TEMPLATE_PLAYLISTS, [
            'playlists' => $playlists,
            'categories' => $categories
        ]);
    }


    #[Route('/playlists/recherche/{champ}/{table}', name: 'playlists.findallcontain')]
    public function findAllContain($champ, Request $request, $table=""): Response
    {
        $valeur = $request->get("recherche");
        $playlists = $this->playlistRepository->findByContainValue($champ, $valeur, $table);
        $categories = $this->categorieRepository->findAll();
        return $this->render(self::TEMPLATE_PLAYLISTS, [
            'playlists' => $playlists,
            'categories' => $categories,
            'valeur' => $valeur,
            'table' => $table
        ]);
    }

    #[Route('/playlists/playlist/{id}', name: 'playlists.showone')]
    public function showOne($id): Response
    {
        $playlist = $this->playlistRepository->find($id);
        $playlistCategories = $this->categorieRepository->findAllForOnePlaylist($id);
        $playlistFormations = $this->formationRepository->findAllForOnePlaylist($id);
        return $this->render("pages/playlist.html.twig", [
            'playlist' => $playlist,
            'playlistcategories' => $playlistCategories,
            'playlistformations' => $playlistFormations
        ]);
    }
    
}

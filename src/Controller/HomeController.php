<?php

namespace App\Controller;

use App\Entity\Categoria;
use App\Repository\CategoriaRepository; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// Controlador super simple para la página de inicio.
// Básicamente redirige o muestra las categorías principales.
class HomeController extends AbstractController
{
    // Ruta raíz de la web.
    #[Route('/', name: 'app_home')]
    public function index(CategoriaRepository $categoriaRepository): Response
    {
        // Cogemos todas las categorías y se las pasamos a la vista para que el usuario elija por dónde empezar.
        return $this->render('categorias/mostrar_categorias.html.twig', [
            'categorias' => $categoriaRepository->findAll(),
        ]);
    }
}


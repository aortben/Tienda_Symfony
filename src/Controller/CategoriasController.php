<?php

namespace App\Controller;

use App\Repository\CategoriaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CategoriasController extends AbstractController
{
    #[Route('/categorias', name: 'app_categorias')]
    public function index(CategoriaRepository $categoriaRepository): Response
    {
        // Obtenemos todas las categorías de la base de datos
        $categorias = $categoriaRepository->findAll();

        return $this->render('categorias/index.html.twig', [
            'categorias' => $categorias,
        ]);
    }
}


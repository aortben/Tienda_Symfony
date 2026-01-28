<?php

namespace App\Controller;

use App\Entity\Categoria;
use App\Repository\CategoriaRepository; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(CategoriaRepository $categoriaRepository): Response
    {
        return $this->render('categorias/mostrar_categorias.html.twig', [
            'categorias' => $categoriaRepository->findAll(),
        ]);
    }
}


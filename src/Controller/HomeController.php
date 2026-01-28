<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Categoria;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('categorias/mostrar_categorias.html.twig', [
            'categorias' => $this->getDoctrine()->getRepository(Categoria::class)->findAll(),
        ]);
    }
}


<?php

namespace App\Controller;

use App\Entity\Categoria;
use App\Repository\ProductoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductoController extends AbstractController
{
    // Listar todos los productos
    #[Route('/productos', name: 'app_productos')]
    public function index(ProductoRepository $productoRepository): Response
    {
        $productos = $productoRepository->findAll();

        return $this->render('producto/index.html.twig', [
            'productos' => $productos,
            'categoria' => null, // para que index.html.twig funcione también sin categoría
        ]);
    }

    // Listar productos por categoría
    #[Route('/categoria/{id}/productos', name: 'app_productos_por_categoria')]
    public function productosPorCategoria(Categoria $categoria): Response
    {
        $productos = $categoria->getProductos();

        return $this->render('producto/index.html.twig', [
            'categoria' => $categoria,
            'productos' => $productos,
        ]);
    }
}



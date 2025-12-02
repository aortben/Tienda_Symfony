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

        return $this->render('producto.html.twig', [
            'productos' => $productos,
            'categoria' => null, // para que index.html.twig funcione también sin categoría
        ]);
    }

    // Listar productos por categoría
    #[Route('/categoria/{id}/productos', name: 'app_productos_por_categoria')]
    public function productosPorCategoria(Categoria $categoria): Response
    {
        $productos = $categoria->getProductos();

        return $this->render('producto.html.twig', [
            'categoria' => $categoria,
            'productos' => $productos,
        ]);
    }
    
#[Route('/anadir', name: 'anadir')]
public function anadir_productos(
    EntityManagerInterface $em,
    Request $request,
    CestaCompra $cesta
) {
    // Recogemos los valores del POST
    $productos_ids = $request->request->get('productos_id');
    $unidades = $request->request->get('unidades');

    // Recuperamos los productos desde el repositorio
    $productos = $em->getRepository(Producto::class)->findProductosById($productos_ids);

    // Añadimos los productos a la cesta
    $cesta->cargar_productos($productos, $unidades);

    // IMPORTANTE: devolver una respuesta
    return $this->redirectToRoute('carrito'); // o la ruta que corresponda
}

    
    
}



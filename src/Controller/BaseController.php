<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Service\CestaCompra;
use App\Entity\Categoria;
use App\Entity\Producto;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('ROLE_USER')]
final class BaseController extends AbstractController
{
    
    #[Route('/categorias', name: 'categorias')]
    public function mostrar_categorias (EntityManagerInterface $doctrine): Response
    {
        $categorias = $doctrine->getRepository (Categoria::class) ->findAll();
        return $this->render('categorias/mostrar_categorias.html.twig', [
            'categorias' => $categorias,
        ]);
        
    }
    
    #[Route('productos/{id}', name: 'productos')]
    public function mostrar_productos (EntityManagerInterface $doctrine, int $id): Response
    {
        $categoria = $doctrine ->getRepository(Categoria::class)->find($id);
        $productos = $categoria->getProductos();
        return $this->render('productos/mostrar_productos.html.twig', [
                    'productos' => $productos,
        ]);
        
        
    }
    #[Route('productos/{id}/detalles', name: 'detalles')]
    public function mostrar_detalles(EntityManagerInterface $doctrine, int $id): Response
    {
        $producto = $doctrine->getRepository(Producto::class)->find($id);
        return $this->render('productos/mostrar_detalles.html.twig', [
            'producto' => $producto,
        ]);
    }
    
    #[Route('/anadir', name: 'anadir')]
    public function anadir_productos(EntityManagerInterface $em, Request $request, CestaCompra $cesta): Response{
        // Recogemos los datos de entrada
        $productos_id = $request->request->get("producto_id");
        $unidades = $request->request->get("unidades");
        // Obtenemos un array de objetos Producto, a partir de sus id
        $productos = $em->getRepository(Producto::class)->findProductsById($productos_id);
        
        // Llamada a la cesta
        $cesta->cargar_productos($productos,$unidades);
        $objetos_producto = array_values($productos);
        
        $categoria_id = $objetos_producto[0] -> getCategoria() -> getId();
        
                
        return $this->redirectToRoute("mostrar_cesta");
    }
    
    #[Route('/cesta', name: 'mostrar_cesta')]
    public function mostrar_cesta(CestaCompra $cesta): Response {
        return $this->render('cesta/mostrar_cesta.html.twig', [
            'productos' => $cesta->obtener_productos(),
        ]);
    }
}

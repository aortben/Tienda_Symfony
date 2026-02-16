<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
#Entidades
use App\Entity\Categoria;
use App\Entity\Producto;
use App\Entity\Pedido;
use App\Entity\PedidoProducto;
use App\Services\CestaCompra;

// Controlador principal de la tienda.
// Aquí está casi toda la lógica: mostrar productos, gestionar el carrito y hacer pedidos.
#[IsGranted('ROLE_USER')]
final class BaseController extends AbstractController
{
    // Muestra todas las categorías disponibles.
    #[Route('/categorias', name: 'categorias')]
    public function mostrar_categorias(ManagerRegistry $doctrine): Response
    {
        $categorias = $doctrine->getRepository(Categoria::class)->findAll();
        return $this->render('categorias/mostrar_categorias.html.twig', [
            'categorias' => $categorias,
        ]);
    }
    
    // Lista los productos de una categoría en concreto.
    #[Route('/productos/{categoria}', name: 'productos')]
    public function mostrar_productos(ManagerRegistry $em, int $categoria): Response
    {
        $categoriaObjeto = $em->getRepository(Categoria::class)->find($categoria);

        if (!$categoriaObjeto) {
            throw $this->createNotFoundException("La categoría no existe");
        }

        $productos = $categoriaObjeto->getProductos();

        return $this->render('productos/mostrar_productos.html.twig', [
            'productos' => $productos,
            'categoria_id' => $categoria,
        ]);
    }
    
    // Procesa el formulario para añadir productos al carrito.
    #[Route('/anadir', name: 'anadir')]
    public function anadir_productos(ManagerRegistry $em, Request $request, CestaCompra $cesta): Response
    {
        $productos_id = $request->request->all("productos_id");
        $unidades = $request->request->all("unidades");
        
        // Vamos a filtrar solo los productos que realmente quieren comprar (cantidad > 0)
        $productos_validos = [];
        $unidades_validas = [];
        
        foreach ($productos_id as $index => $producto_id) {
            if (isset($unidades[$index]) && (int)$unidades[$index] > 0) {
                $productos_validos[] = (int)$producto_id;
                $unidades_validas[] = (int)$unidades[$index];
            }
        }
        
        // Si no han elegido nada, les avisamos y les devolvemos a la lista.
        if (empty($productos_validos)) {
            $this->addFlash('warning', 'Por favor selecciona al menos un producto.');
            return $this->redirectToRoute("productos", [
                'categoria' => $request->request->get('categoria_id') ?? 1
            ]);
        }
        
        // Recuperamos los objetos Producto de la base de datos.
        $productos = $em->getRepository(Producto::class)->findBy(['id' => $productos_validos]);
        
        // COMPROBACIÓN DE STOCK: Antes de meter en la cesta, miramos si hay suficientes.
        foreach ($productos as $producto) {
            $index = array_search($producto->getId(), $productos_validos);
            if ($producto->getStock() < $unidades_validas[$index]) {
                $this->addFlash('danger', sprintf(
                    'Stock insuficiente para %s. Disponible: %d',
                    $producto->getNombre(),
                    $producto->getStock()
                ));
                return $this->redirectToRoute("productos", [
                    'categoria' => $productos[0]->getCategoria()->getId()
                ]);
            }
        }
        
        // Todo en orden, guardamos en la sesión (CestaCompra).
        $cesta->cargar_productos($productos, $unidades_validas);
        
        $this->addFlash('success', 'Producto(s) agregado a la cesta correctamente.');

        // Un pequeño truco para saber a qué categoría volver
        $objetos_producto = array_values($productos);
        $categoria_id = $objetos_producto[0]->getCategoria()->getId();

        return $this->redirectToRoute("productos", [
            'categoria' => $categoria_id
        ]);
    }
    
    // Vista de la cesta de la compra.
    #[Route('/cesta', name: 'cesta')]
    public function cesta(CestaCompra $cesta): Response
    {
        // Pasamos todo lo necesario para pintar la cesta, incluido el precio total calculado en el servicio.
        return $this->render('cesta/mostrar_cesta.html.twig', [
            'productos' => $cesta->get_productos(),
            'unidades'  => $cesta->get_unidades(),
            'precio_total' => $cesta->calcular_coste() 
        ]);
    }

    #[Route('/cesta/vaciar', name: 'vaciar_cesta')]
    public function vaciar_cesta(CestaCompra $cesta): Response
    {
        $cesta->vaciar_cesta();
        return $this->redirectToRoute('cesta');
    }
    
    // Elimina un producto concreto de la cesta.
    #[Route('/eliminar', name: 'eliminar')]
    public function eliminar(Request $request, CestaCompra $cesta)
    {   
        $producto_id = (int) $request->request->get("producto_id");
        $unidades = (int) $request->request->get("unidades");
        
        $cesta->eliminar_producto($producto_id, $unidades);
        
        $this->addFlash('success', 'Producto eliminado de la cesta.');

        return $this->redirectToRoute('cesta');
    }

    #[Route('/cesta/aumentar/{producto}', name: 'aumentar_unidad')]
    public function aumentar_unidad(int $producto, CestaCompra $cesta): Response
    {
        $cesta->aumentar_unidad($producto);
        $this->addFlash('success', 'Cantidad aumentada.');
        return $this->redirectToRoute('cesta');
    }

    #[Route('/cesta/disminuir/{producto}', name: 'disminuir_unidad')]
    public function disminuir_unidad(int $producto, CestaCompra $cesta): Response
    {
        $cesta->disminuir_unidad($producto);
        $this->addFlash('success', 'Cantidad disminuida.');
        return $this->redirectToRoute('cesta');
    }

    #[Route('/pedido', name: 'pedido')]
    public function pedidos(CestaCompra $cesta, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $productosSesion = $cesta->get_productos();
        $unidadesSesion = $cesta->get_unidades();
        $error = 0; 
        $pedido = null;

        if (count($productosSesion) == 0) {
            $error = 1; // Cesta vacía
            return $this->render('pedido/pedido.html.twig', [
                'pedido_id' => null,
                'error' => $error
            ]);
        }

        // Validaciones de stock y cálculo de precio real.
        $costeTotalReal = 0;
        $productosParaProcesar = [];

        foreach ($productosSesion as $productoSesion) {
            $idProducto = $productoSesion->getId();
            
            // Buscamos el producto en la base de datos para tener datos nuevos.
            $productoReal = $em->getRepository(Producto::class)->find($idProducto);

            if (!$productoReal) {
                // Si el producto no existe, lo eliminamos de la cesta.
                $cesta->eliminar_producto($idProducto, $unidadesSesion[$idProducto]);
                $this->addFlash('danger', 'Un producto de tu cesta ya no está disponible.');
                return $this->redirectToRoute('cesta');
            }

            $cantidad = $unidadesSesion[$idProducto] ?? 1;

            // Segunda comprobación de Stock.
            if ($productoReal->getStock() < $cantidad) {
                $this->addFlash('danger', sprintf(
                    'No hay suficiente stock para %s. Disponible: %d, Solicitado: %d',
                    $productoReal->getNombre(),
                    $productoReal->getStock(),
                    $cantidad
                ));
                return $this->redirectToRoute('cesta');
            }

            $costeTotalReal += $productoReal->getPrecio() * $cantidad;
            
            $productosParaProcesar[] = [
                'producto' => $productoReal,
                'cantidad' => $cantidad
            ];
        }

        // Creamos el pedido.
        $pedido = new Pedido();
        $pedido->setCoste($costeTotalReal); // Guardamos lo que ha costado en este momento.
        $pedido->setFecha(new \DateTime());
        $pedido->setUsuario($this->getUser());

        $em->persist($pedido);

        // Creamos las líneas del pedido y restamos stock.
        foreach ($productosParaProcesar as $item) {
            $productoReal = $item['producto'];
            $cantidad = $item['cantidad'];

            $pedidoProducto = new PedidoProducto();
            $pedidoProducto->setPedido($pedido);
            $pedidoProducto->setProducto($productoReal);
            $pedidoProducto->setUnidades($cantidad);
            
            // Guardamos el precio al que se compró.
            $pedidoProducto->setPrecio($productoReal->getPrecio());
            
            // Actualizamos el stock disponible.
            $nuevoStock = $productoReal->getStock() - $cantidad;
            $productoReal->setStock($nuevoStock);

            $em->persist($pedidoProducto);
        }

        // Guardamos todo en la base de datos de una vez.
        $em->flush();
        
        // Enviamos el correo de confirmación.
        try {
            $email = (new Email())
                ->from('alvaroortegabenitez03@gmail.com')
                ->to((string)$this->getUser()->getEmail())
                ->subject('Confirmación de Pedido #' . $pedido->getId())
                ->html($this->renderView('emails/pedido_confirmacion.html.twig', [
                    'pedido' => $pedido,
                    'productos' => $productosSesion, 
                    'unidades' => $unidadesSesion
                ]));

            $mailer->send($email);
        } catch (\Exception $e) {
            // Si falla el correo, no paramos la compra.
            
        }

        // Vaciamos la cesta.
        $cesta->vaciar_cesta();
        
        $this->addFlash('success', 'Pedido realizado correctamente. Revisa tu email para la confirmación.');

        return $this->render('pedido/pedido.html.twig', [
            'pedido_id' => $pedido->getId(),
            'error' => 0
        ]);
    }
    
    // Vista de detalles de un producto.
    #[Route('/producto/{id}', name: 'detalles')]
    public function detalles(Producto $producto): Response
    {
        return $this->render('productos/detalles_productos.html.twig', [
            'producto' => $producto
        ]);
    }
    
    #[Route('/historial', name: 'historial')] 
    public function historial(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        // Ahora accedemos directamente a la relación.
        // Gracias al OrderBy de la entidad, ya vienen ordenados.
        return $this->render('pedido/historial.html.twig', [
            'pedidos' => $user->getPedidos()
        ]);
    }
}
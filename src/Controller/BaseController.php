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

#[IsGranted('ROLE_USER')]
final class BaseController extends AbstractController
{
    #[Route('/categorias', name: 'categorias')]
    public function mostrar_categorias(ManagerRegistry $doctrine): Response
    {
        $categorias = $doctrine->getRepository(Categoria::class)->findAll();
        return $this->render('categorias/mostrar_categorias.html.twig', [
            'categorias' => $categorias,
        ]);
    }
    
    #[Route('/productos/{categoria}', name: 'productos')]
    public function mostrar_productos(ManagerRegistry $em, int $categoria): Response
    {
        $categoriaObjeto = $em->getRepository(Categoria::class)->find($categoria);

        // Si no existe la categoría → error controlado
        if (!$categoriaObjeto) {
            throw $this->createNotFoundException("La categoría no existe");
        }

        $productos = $categoriaObjeto->getProductos();

        return $this->render('productos/mostrar_productos.html.twig', [
            'productos' => $productos,
            'categoria_id' => $categoria,
        ]);
    }
    
    #[Route('/anadir', name: 'anadir')]
    public function anadir_productos(ManagerRegistry $em, Request $request, CestaCompra $cesta): Response
    {
        $productos_id = $request->request->all("productos_id");
        $unidades = $request->request->all("unidades");
        
        // Validar que haya productos seleccionados
        $productos_validos = [];
        $unidades_validas = [];
        
        foreach ($productos_id as $index => $producto_id) {
            // Solo agregar si la cantidad es mayor a 0
            if (isset($unidades[$index]) && (int)$unidades[$index] > 0) {
                $productos_validos[] = (int)$producto_id;
                $unidades_validas[] = (int)$unidades[$index];
            }
        }
        
        // Si no hay productos válidos, redirigir de vuelta
        if (empty($productos_validos)) {
            $this->addFlash('warning', 'Por favor selecciona al menos un producto.');
            return $this->redirectToRoute("productos", [
                'categoria' => $request->request->get('categoria_id') ?? 1
            ]);
        }
        
        // Obtener array de productos
        $productos = $em->getRepository(Producto::class)->findBy(['id' => $productos_validos]);
        
        // Validar stock disponible
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
        
        // Cargar productos a la cesta
        $cesta->cargar_productos($productos, $unidades_validas);
        
        $this->addFlash('success', 'Producto(s) agregado a la cesta correctamente.');

        // Convertir array asociativo en array indexado
        $objetos_producto = array_values($productos);

        // Obtener ID de categoría del producto
        $categoria_id = $objetos_producto[0]->getCategoria()->getId();

        return $this->redirectToRoute("productos", [
            'categoria' => $categoria_id
        ]);
    }
    
    #[Route('/cesta', name: 'cesta')]
    public function cesta(CestaCompra $cesta): Response
    {
        // MODIFICADO: Ahora pasamos el precio_total calculado por el servicio
        return $this->render('cesta/mostrar_cesta.html.twig', [
            'productos' => $cesta->get_productos(),
            'unidades'  => $cesta->get_unidades(),
            'precio_total' => $cesta->calcular_coste() // <--- ESTO FALTABA
        ]);
    }

    // AÑADIDO: Método para vaciar la cesta
    #[Route('/cesta/vaciar', name: 'vaciar_cesta')]
    public function vaciar_cesta(CestaCompra $cesta): Response
    {
        $cesta->vaciar_cesta();
        return $this->redirectToRoute('cesta');
    }
    
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

      #METODO PARA HACER UN PEDIDO
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

        // 1. Validaciones previas y cálculo de coste real
        $costeTotalReal = 0;
        $productosParaProcesar = [];

        foreach ($productosSesion as $productoSesion) {
            $idProducto = $productoSesion->getId();
            
            // Buscar producto fresco en BD para asegurar precio y stock actual
            $productoReal = $em->getRepository(Producto::class)->find($idProducto);

            if (!$productoReal) {
                // Si un producto ya no existe, lo quitamos de la cesta y avisamos
                $cesta->eliminar_producto($idProducto, $unidadesSesion[$idProducto]);
                $this->addFlash('danger', 'Un producto de tu cesta ya no está disponible.');
                return $this->redirectToRoute('cesta');
            }

            $cantidad = $unidadesSesion[$idProducto] ?? 1;

            // Validar Stock
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
            
            // Guardamos el par (entidad real, cantidad) para usarlo después
            $productosParaProcesar[] = [
                'producto' => $productoReal,
                'cantidad' => $cantidad
            ];
        }

        // 2. Crear el Pedido Cabecera
        $pedido = new Pedido();
        $pedido->setCoste($costeTotalReal); // Usamos el coste recalculado con precios actuales
        $pedido->setFecha(new \DateTime());
        $pedido->setUsuario($this->getUser());

        $em->persist($pedido);

        // 3. Crear las líneas de Pedido y actualizar Stock
        foreach ($productosParaProcesar as $item) {
            /** @var Producto $productoReal */
            $productoReal = $item['producto'];
            $cantidad = $item['cantidad'];

            $pedidoProducto = new PedidoProducto();
            $pedidoProducto->setPedido($pedido);
            $pedidoProducto->setProducto($productoReal);
            $pedidoProducto->setUnidades($cantidad);
            
            // ¡IMPORTANTE! Guardar el precio histórico (snapshot)
            $pedidoProducto->setPrecio($productoReal->getPrecio());
            
            // Reducir stock
            $nuevoStock = $productoReal->getStock() - $cantidad;
            $productoReal->setStock($nuevoStock);

            $em->persist($pedidoProducto);
        }

        // Guardar todo en BD
        $em->flush();
        
        // 4. ENVIAR CORREO
        try {
            $email = (new Email())
                ->from('alvaroortegabenitez03@gmail.com')
                ->to((string)$this->getUser()->getEmail())
                ->subject('Confirmación de Pedido #' . $pedido->getId())
                ->html($this->renderView('emails/pedido_confirmacion.html.twig', [
                    'pedido' => $pedido,
                    'productos' => $productosSesion, // Usamos los de sesión para la vista rápida o recargamos del pedido
                    'unidades' => $unidadesSesion
                ]));

            $mailer->send($email);
        } catch (\Exception $e) {
            // Loguear error de correo pero no detener el flujo de éxito
            // $this->logger->error('Error enviando correo: ' . $e->getMessage());
        }

        // 5. Vaciar cesta tras compra exitosa
        $cesta->vaciar_cesta();
        
        $this->addFlash('success', 'Pedido realizado correctamente. Revisa tu email para la confirmación.');

        return $this->render('pedido/pedido.html.twig', [
            'pedido_id' => $pedido->getId(),
            'error' => 0
        ]);
    }
    
    #[Route('/producto/{id}', name: 'detalles')]
    public function detalles(Producto $producto): Response
    {
        return $this->render('productos/detalles_productos.html.twig', [
            'producto' => $producto
        ]);
    }
    
    #[Route('/historial', name: 'historial')] 
    public function historial(ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        $pedidos = $doctrine->getRepository(Pedido::class)->findBy(
            ['usuario' => $user], 
            ['fecha' => 'DESC']
        );

        return $this->render('pedido/historial.html.twig', [
            'pedidos' => $pedidos
        ]);
    }
}
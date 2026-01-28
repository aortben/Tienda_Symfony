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
        ]);
    }
    
    //POR TERMINAR ESTE METODO, ME FALTA AÑADIR  
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
        return $this->render('cesta/mostrar_cesta.html.twig', [
            'productos' => $cesta->get_productos(),
            'unidades'  => $cesta->get_unidades(),
        ]);
    }
    
    #METODO PARA ACTUALIZAR LA CESTA
    #[Route('/eliminar', name: 'eliminar')]
    public function eliminar(Request $request, CestaCompra $cesta)
    {   
        //Eliminamos la cantidad
        $producto_id = $request->request->get("productos_id");
        $unidades = $request->request->get("unidades");
        
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
        $productos = $cesta->get_productos();
        $unidades = $cesta->get_unidades();
        $error = 0; // Inicializamos variable
        $pedido = null;

        if (count($productos) == 0) {
            $error = 1; // Cesta vacía
        } else {
            try {
                // 1. Crear el Pedido Cabecera
                $pedido = new Pedido();
                $pedido->setCoste($cesta->calcular_coste()); // Corregido typo 'cosye'
                $pedido->setFecha(new \DateTime());
                $pedido->setUsuario($this->getUser());

                $em->persist($pedido);

                // 2. Crear las líneas de Pedido (PedidoProducto)
                foreach ($productos as $productoCesta) {
                    $pedidoProducto = new PedidoProducto();
                    $pedidoProducto->setPedido($pedido);
                    $pedidoProducto->setProducto($productoCesta);
                    
                    // Asumimos que $unidades tiene como clave el ID del producto
                    $idProducto = $productoCesta->getId();
                    $cantidad = $unidades[$idProducto] ?? 1; // Corregido acceso array con []
                    
                    $pedidoProducto->setUnidades($cantidad);
                    
                    // Reducir stock del producto
                    $productoCesta->setStock($productoCesta->getStock() - $cantidad);
                    $em->persist($productoCesta);
                    $em->persist($pedidoProducto);
                }

                // Guardar todo en BD
                $em->flush();
                
                // 3. ENVIAR CORREO (Solo si se guarda bien en BD)
                $email = (new Email())
                    ->from('tienda@videojuegos.com')
                    ->to((string)$this->getUser()->getEmail())
                    ->subject('Confirmación de Pedido #' . $pedido->getId())
                    ->html($this->renderView('emails/pedido_confirmacion.html.twig', [
                        'pedido' => $pedido,
                        'productos' => $productos,
                        'unidades' => $unidades
                    ]));

                $mailer->send($email);

                // 4. Vaciar cesta tras compra exitosa
                $cesta->vaciar_cesta();
                
                $this->addFlash('success', 'Pedido realizado correctamente. Revisa tu email para la confirmación.');

            } catch (\Exception $ex) {
                $error = 2; // Error en BD o Correo
                $this->addFlash('danger', 'Error al procesar el pedido: ' . $ex->getMessage());
            }
        }

        return $this->render('pedido/pedido.html.twig', [
            // Usamos null safe operator (?) por si pedido no se creó
            'pedido_id' => $pedido ? $pedido->getId() : null,
            'error' => $error
        ]);
    }
}

    
    
    


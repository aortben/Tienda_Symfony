<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\Producto;

// Servicio "carrito de la compra". 
// Aquí guardamos qué productos quiere el usuario y cuántos, usando la sesión para que no se pierdan al recargar.
class CestaCompra {
    
    protected $requestStack;
    protected $productos = []; // Lista de productos (objetos)
    protected $unidades = [];  // Lista de cantidad por producto (id_producto => cantidad)

    public function __construct(RequestStack $requestStack) {
        $this->requestStack = $requestStack;
    }

    // Cargar varios productos 
    public function cargar_productos(array $productos, array $unidades) {
        $this->cargar_cesta();
        
        // Usamos count($productos) para evitar errores si los arrays tienen distinto tamaño
        for ($i = 0; $i < count($productos); $i++) {
            if ($unidades[$i] != 0) {
                // Reutilizamos el método individual para no duplicar código
                $this->cargar_producto($productos[$i], (int)$unidades[$i]);
            }
        }
        $this->guardar_cesta(); 
    }

    // Cargar un UNICO producto
    public function cargar_producto(Producto $producto, int $unidad){ 
        // Asegúrate si en tu entidad se llama getCodigo() o getId()
        $codigo = $producto->getId(); 
        
        $this->cargar_cesta(); // Nos aseguramos de tener lo último de la sesión

        if(array_key_exists($codigo, $this->productos)){
            $this->unidades[$codigo] += $unidad;
        } else if($unidad != 0) {
            $this->productos[$codigo] = $producto;
            $this->unidades[$codigo] = $unidad;
        }

        $this->guardar_cesta();
    }
    
    // Eliminar productos
    public function eliminar_producto($codigo_producto, $unidades_a_restar) {
        $this->cargar_cesta();
        
        if(array_key_exists($codigo_producto, $this->productos)){
            
            $this->unidades[$codigo_producto] -= $unidades_a_restar;
            
            if($this->unidades[$codigo_producto] <= 0){
                unset($this->unidades[$codigo_producto]);
                unset($this->productos[$codigo_producto]);
            }
            $this->guardar_cesta();
        }
    }

    // Calcular coste TOTAL (Sin parámetros, usa los datos internos)
    public function calcular_coste(): float {
        $this->cargar_cesta();
        $costeTotal = 0;
        
        foreach ($this->productos as $codigo_producto => $producto) {
            $costeTotal += $producto->getPrecio() * $this->unidades[$codigo_producto];   
        }
        return $costeTotal;
    }

    // Aumentar unidades de un producto
    public function aumentar_unidad(int $codigo_producto): void {
        $this->cargar_cesta();
        
        if(array_key_exists($codigo_producto, $this->productos)) {
            $this->unidades[$codigo_producto]++;
            $this->guardar_cesta();
        }
    }

    // Disminuir unidades de un producto
    public function disminuir_unidad(int $codigo_producto): void {
        $this->cargar_cesta();
        
        if(array_key_exists($codigo_producto, $this->productos)) {
            $this->unidades[$codigo_producto]--;
            
            // Si llega a 0 o menos, eliminar el producto
            if($this->unidades[$codigo_producto] <= 0) {
                unset($this->unidades[$codigo_producto]);
                unset($this->productos[$codigo_producto]);
            }
            
            $this->guardar_cesta();
        }
    }

    // Vaciar cesta completamente
    public function vaciar_cesta(): void {
        $this->productos = [];
        $this->unidades = [];
        $this->guardar_cesta();
    }

    // Getters
    public function get_productos() {
        $this->cargar_cesta();
        return $this->productos;
    }

    public function get_unidades() {
        $this->cargar_cesta();
        return $this->unidades;
    }

    // Métodos internos (Protected)
    
    protected function cargar_cesta() {
        $sesion = $this->requestStack->getSession();
        if($sesion->has('productos') && $sesion->has('unidades')){
            $this->productos = $sesion->get('productos');
            $this->unidades = $sesion->get('unidades');
        } else {
            $this->productos = [];
            $this->unidades = [];
        }
    }

    protected function guardar_cesta() {
        $sesion = $this->requestStack->getSession();
        $sesion->set('productos', $this->productos);
        $sesion->set('unidades', $this->unidades);
    }
}
<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\Producto;

class CestaCompra {
    
    protected $requestStack;
    protected $productos = [];
    protected $unidades = [];

    public function __construct(RequestStack $requestStack) {
        $this->requestStack = $requestStack;
    }

    // Cargar varios productos, recibe como los parametros los productos y unidades
    public function cargar_productos(array $productos, array $unidades) {
        $this->cargar_cesta();
        for ($i = 0; $i < count($productos); $i++) {
            if ($unidades[$i] != 0) {
                $this->cargar_producto($productos[$i], $unidades[$i]);
            }
        }
    }

    // Cargar un producto en la cesta
    public function cargar_producto(Producto $producto, int $unidad){ 
    $codigo = $producto->getCodigo();
        if(array_key_exists($codigo, $this->productos)){
            $this->unidades[$codigo] += $unidad;
        } else if($unidad != 0) {
            $this->productos[$codigo] = $producto;
            $this->unidades[$codigo] = $unidad;
        }

        $this->guardar_cesta();
    }
    
    // Actualizar unidades en la cesta (si llega a 0 se elimina)
    /*public function actualizar_producto(string $codigo, int $unidad) {
        $this->cargar_cesta();

        if ($unidad <= 0) {
            unset($this->productos[$codigo]);
            unset($this->unidades[$codigo]);
        } else {
            $this->unidades[$codigo] = $unidad;
        }

        $this->guardar_cesta();
    }*/



    // Cargar cesta desde sesión
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

    // Guardar cesta en sesión
    protected function guardar_cesta() {
        $sesion = $this->requestStack->getSession();
        $sesion->set('productos', $this->productos); // objetos completos
        $sesion->set('unidades', $this->unidades);
    }



    // Obtener productos
    public function get_productos() {
        $this->cargar_cesta();
        return $this->productos;
    }

    // Obtener unidades
    public function get_unidades() {
        $this->cargar_cesta();
        return $this->unidades;
    }
    
    //ELiminar productos
    public function eliminar_producto($codigo_producto, $unidades) {
        //Cargamos la sesion de la cesta
        $this->cargar_cesta();
        
        if(array_key_exists($codigo_producto, $this->productos)){
            
            $this-> unidades[$codigo_producto]-= $unidades;
            
            if($this->unidades[$codigo_producto] <= 0){
                unset($this->unidades[$codigo_producto]);
                unset($this->productos[$codigo_producto]);
            }
            $this->guardar_cesta();
        }
    }
    
    public function calcular_coste($unidades, $producto){
            $costeTotal = 0;
            foreach ($this->productos as $codigo_producto => $producto) {
                //Multiplicamos por unidades y sumamos coste total
                $costeTotal += $producto -> getPrecio() * $this->unidades[$codigo_producto];   
            }
            return $costeTotal;
           }
            
 
}


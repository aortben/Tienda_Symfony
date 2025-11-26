<?php

namespace App\Services;
use App\Entity\Producto;
use Symfony\Component\HttpFoundation\RequestStack;

class CestaCompra { 
    
   protected $productos;
   protected $unidades;
   
   protected $requestStack;
            
   public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;   
   }
   
   public function cargar_productos($productos, $unidades) {
       for($i = 0;$i<count($productos);$i++) {
           if($unidades[$i] != 0) {
               $this->cargar_producto($productos[$i],$unidades[$i]);
               
           }
       }
   }
   //Recibe como parametro el objeto Producto con su unidad
   public function cargar_producto($producto, $unidades) {
       $this->cargar_cesta();
       $cesta = $sesion->get('cesta');
       $codigo = $producto->getCode();
       if(array_key_exists($codigo ,$this->productos) {
           $codigo_productos array_keys($this->productos);
           $posicion = array_search($codigo, $codigo_productos);
           $unidades($posicion)=+unidad;)
           
           
       }else{
           $productos [] = ['$codigo' => $producto];
           $unidades [] = [$unidad];
       }
      
       $this->guarda_cesta();
   }
   
   protected function cargar_cesta() {
       $sesion = $this->requestStack->getSession();
       if($sesion->has("productos") && $sesion->("unidades")){
           $this->productos = $sesion->get("productos");
           $this->unidades = $sesion->get("unidades");
           
   } else {
       $this->productos = [];
       $this->unidades = [];
   }
   
   $carrito = $sesion->get('carrito');
}

   protected function guardar_cesta() {
       $sesion = $this->requestStack->getSession();
       $sesion->set($this->productos);
       $sesion->set($this->unidades);
   }
}


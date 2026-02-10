<?php

namespace App\Repository;

use App\Entity\Pedido;
use App\Entity\Producto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pedido>
 */
class PedidoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pedido::class);
    }

    //Transforma un array de IDs en un array de objetos Producto
     
    public function findProductosByIds(array $productos_ids): array
    {
        if (empty($productos_ids)) {
            return [];
        }

        $em = $this->getEntityManager();
        $productos = [];

        foreach($productos_ids as $producto_id) {
        
            $producto = $em->getRepository(Producto::class)->find($producto_id);
            
            if ($producto) {
                $productos[] = $producto;
            }
        }
        
        return $productos;
    }
}
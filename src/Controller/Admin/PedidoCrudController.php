<?php

namespace App\Controller\Admin;

use App\Entity\Pedido;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PedidoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Pedido::class;
    }

    public function configuraFilters(Filters $filters): Filters {
        return $filters
                ->add('fechaPedido')
                ->add('usuario')
                ;
    }
}

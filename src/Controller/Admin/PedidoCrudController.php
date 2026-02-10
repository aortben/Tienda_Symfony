<?php

namespace App\Controller\Admin;

use App\Entity\Pedido;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

// Controlador para la gestión de Pedidos en el admin.
class PedidoCrudController extends AbstractCrudController
{
    // Vinculamos este controlador con la entidad Pedido.
    public static function getEntityFqcn(): string
    {
        return Pedido::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield \EasyCorp\Bundle\EasyAdminBundle\Field\DateField::new('fecha');
        yield \EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField::new('coste')->setCurrency('EUR')->setStoredAsCents(false);
        yield \EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField::new('usuario');
        yield \EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField::new('pedidoProductos')->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters {
        return $filters
                ->add('fecha')
                ->add('usuario')
                ;
    }
}

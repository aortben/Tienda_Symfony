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

    /*
    public function configureFields(string $pageName): iterable
    {
        // Igual que en categorías, dejamos que EasyAdmin decida qué campos mostrar.
        // Si quisiéramos ocultar algo o cambiar el formato, lo haríamos aquí.
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */

    public function configureFilters(Filters $filters): Filters {
        return $filters
                ->add('fecha')
                ->add('usuario')
                ;
    }
}

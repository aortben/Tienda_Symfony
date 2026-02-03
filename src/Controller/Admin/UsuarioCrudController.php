<?php

namespace App\Controller\Admin;

use App\Entity\Usuario;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters; // <--- IMPORTANTE
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class UsuarioCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Usuario::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('email') // Buscador de texto por email
            // Si tienes un campo 'nombre' o 'username', añádelo aquí también:
            // ->add('nombre') 
        ;
    }
}

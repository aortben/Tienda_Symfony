<?php

namespace App\Controller\Admin;

use App\Entity\Producto;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters; 
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ProductoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Producto::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('nombre')
            ->add('precio')     
            ->add('categoria')  
        ;
    }
}

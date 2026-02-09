<?php

namespace App\Controller\Admin;

use App\Entity\Categoria;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

// Controlador para gestionar las categorías desde el panel de administración.
// Aquí EasyAdmin se encarga de casi todo el trabajo sucio del CRUD.
class CategoriaCrudController extends AbstractCrudController
{
    // Le indicamos a EasyAdmin que este controlador gestiona la entidad Categoria.
    public static function getEntityFqcn(): string
    {
        return Categoria::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        // Aquí podríamos personalizar qué campos se muestran en el formulario,
        // pero por ahora dejamos que EasyAdmin lo configure automáticamente.
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}

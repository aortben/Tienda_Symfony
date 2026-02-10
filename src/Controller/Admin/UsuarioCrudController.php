<?php

namespace App\Controller\Admin;

use App\Entity\Usuario;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

// Controlador para gestionar los Usuarios del sistema.
// Cuidado al tocar usuarios, aquí es donde se pueden asignar roles o bloquear gente.
class UsuarioCrudController extends AbstractCrudController
{
    // Vinculado a la entidad Usuario.
    public static function getEntityFqcn(): string
    {
        return Usuario::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('email');
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        // Para usuarios sería interesante ocultar la contraseña y mostrar los roles de forma amigable.
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('email');
        yield TextField::new('plainPassword', 'Contraseña')
            ->setFormType(\Symfony\Component\Form\Extension\Core\Type\PasswordType::class)
            ->setRequired($pageName === \EasyCorp\Bundle\EasyAdminBundle\Config\Crud::PAGE_NEW)
            ->onlyOnForms();
    }
}

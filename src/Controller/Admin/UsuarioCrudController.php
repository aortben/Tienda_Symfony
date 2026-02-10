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
    private \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('login', 'Usuario');
        yield TextField::new('email', 'Correo electrónico');
        yield TextField::new('phone', 'Teléfono');
        
        yield \EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField::new('roles', 'Roles')
            ->setChoices([
                'Usuario' => 'ROLE_USER',
                'Administrador' => 'ROLE_ADMIN',
            ])
            ->allowMultipleChoices()
            ->renderExpanded();

        yield TextField::new('password')->onlyOnIndex();
        yield TextField::new('plainPassword', 'Contraseña')
            ->setFormType(\Symfony\Component\Form\Extension\Core\Type\PasswordType::class)
            ->setRequired($pageName === \EasyCorp\Bundle\EasyAdminBundle\Config\Crud::PAGE_NEW)
            ->onlyOnForms();
    }

    public function persistEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPassword($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPassword($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function hashPassword($entity): void
    {
        if (!$entity instanceof Usuario) {
            return;
        }

        if ($entity->getPlainPassword()) {
            $entity->setPassword($this->userPasswordHasher->hashPassword($entity, $entity->getPlainPassword()));
        }
    }
}

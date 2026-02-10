<?php

namespace App\EventHasheo;

use App\Entity\Usuario;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class HasheoPassword implements EventSubscriberInterface
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['encriptarContrasena'],
            BeforeEntityUpdatedEvent::class => ['encriptarContrasena'],
        ];
    }

    public function encriptarContrasena($event): void
    {
        $entity = $event->getEntityInstance();

        if (!($entity instanceof Usuario)) {
            return;
        }

        $plainPassword = $entity->getPlainPassword();

        if (!$plainPassword) {
            return;
        }

        $hashedPassword = $this->passwordHasher->hashPassword(
            $entity,
            $plainPassword
        );

        $entity->setPassword($hashedPassword);
        $entity->setPlainPassword(null);
    }
}
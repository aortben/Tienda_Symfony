<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

// Controlador de seguridad: Login y Logout.
class SecurityController extends AbstractController
{
    // El formulario de login de toda la vida.
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si hay un error (contraseña mal puesta, usuario no existe...), lo capturamos aquí.
        $error = $authenticationUtils->getLastAuthenticationError();

        // Recuperamos el último usuario que intentó entrar para no obligarle a escribirlo de nuevo.
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    // Ruta de logout. Symfony la intercepta automáticamente, así que el método puede estar vacío.
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}

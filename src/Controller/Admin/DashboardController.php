<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; 

use App\Entity\Producto; 
use App\Entity\Categoria; 
use App\Entity\Usuario;
use App\Entity\Pedido;


// Controlador principal del Dashboard de administración.
// Es la puerta de entrada al panel de control para los administradores.
class DashboardController extends AbstractDashboardController
{
    // Ruta principal del dashboard. Solo accesible si tienes permisos (configurado en security.yaml).
    #[Route('/dashboard', name: 'dashboard')] 
    public function index(): Response
    {
        // En lugar de una página en blanco, mostramos la plantilla por defecto de EasyAdmin.
        // Podríamos redirigir a un CRUD específico, pero así está bien para empezar.
        return $this->render('@EasyAdmin/page/content.html.twig');
    }

    // Configuración básica del título del dashboard.
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Tienda de videojuegos');
    }

    // Aquí definimos el menú lateral del panel de administración.
    // Añadimos enlaces directos a las distintas secciones (Usuarios, Productos, etc.)
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home'); // Enlace al inicio del panel
        yield MenuItem::linkToCrud('Usuario', 'fas fa-list', Usuario::class); // Gestión de usuarios
        yield MenuItem::linkToCrud('Producto', 'fas fa-list', Producto::class); // Gestión de productos
        yield MenuItem::linkToCrud('Categoria', 'fas fa-list', Categoria::class); // Gestión de categorías
        yield MenuItem::linkToCrud('Pedido', 'fas fa-list', Pedido::class); // Gestión de pedidos
    }
}
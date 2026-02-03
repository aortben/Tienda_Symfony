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


class DashboardController extends AbstractDashboardController
{
    #[Route('/dashboard', name: 'dashboard')] 
    public function index(): Response
    {
        // Una vez arreglado el error de ruta, NO devuelvas parent::index() solo.
        // EasyAdmin espera que redirijas a un CRUD o muestres una plantilla.
        
        // Opción recomendada para empezar: Redirigir al CRUD de Productos
        // return $this->redirectToRoute('admin_producto_index'); 
        
        // O mostrar la página básica (descomenta la opción 3 de tu código original):
        return $this->render('@EasyAdmin/page/content.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Tienda de videojuegos');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Usuario', 'fas fa-list', Usuario::class);
        yield MenuItem::linkToCrud('Producto', 'fas fa-list', Producto::class);
        yield MenuItem::linkToCrud('Categoria', 'fas fa-list', Categoria::class);
        yield MenuItem::linkToCrud('Pedido', 'fas fa-list', Pedido::class);
        
    }
}
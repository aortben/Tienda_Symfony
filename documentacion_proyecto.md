# Documentación del Proyecto: Tienda Online con Symfony

Este documento detalla el funcionamiento interno de la aplicación para facilitar su estudio y presentación.

## 1. Visión General
Esta es una tienda online completa desarrollada en **Symfony**. Permite a los usuarios navegar por categorías y productos, gestionar un carrito de compras (cesta), realizar pedidos y recibir confirmaciones por correo. Además, cuenta con un panel de administración seguro para gestionar el contenido.

---

## 2. Estructura de Datos (Entidades)
Las entidades (`src/Entity`) representan las tablas de la base de datos y cómo se relacionan entre sí.

*   **Usuario (`Usuario.php`)**:
    *   Representa a los clientes y administradores.
    *   Campos clave: `email`, `login` (nombre de usuario), `password` (hasheada), `roles` (ej. `ROLE_USER`, `ROLE_ADMIN`).
    *   *Nota*: Tiene un campo especial `plainPassword` que no se guarda en BD, solo sirve para transportar la contraseña durante el registro antes de ser hasheada.
    *   Relación: Un Usuario tiene muchos Pedidos (`OneToMany`).

*   **Categoria (`Categoria.php`)**:
    *   Clasificación de los productos (ej. "Acción", "RPG").
    *   Relación: Una Categoría tiene muchos Productos (`OneToMany`).

*   **Producto (`Producto.php`)**:
    *   Los artículos en venta.
    *   Campos: `nombre`, `precio`, `stock`, `foto` (nombre del archivo de imagen).
    *   Relación: Pertenece a una Categoría (`ManyToOne`).

*   **Pedido (`Pedido.php`)**:
    *   Representa una compra finalizada.
    *   Campos: `fecha`, `coste` (total pagado).
    *   Relación: Pertenece a un Usuario.

*   **PedidoProducto (`PedidoProducto.php`)**:
    *   Tabla intermedia vital. Guarda qué productos y cuántas unidades había en CADA pedido.
    *   *Importante*: Guarda el `precio` al momento de la compra. Si el precio del producto cambia mañana, el historial del pedido no se ve afectado.

---

## 3. Lógica de Negocio (Servicios)
Los servicios contienen la lógica compleja que no debería estar en los controladores.

### Cesta de Compra (`src/Services/CestaCompra.php`)
Este es el "motor" del carrito. No usa base de datos, sino **Sesiones** (`Session`).
*   **¿Por qué sesiones?**: Para que el carrito persista mientras el usuario navega, incluso si no está logueado.
*   **Métodos Clave**:
    *   `cargar_cesta()` / `guardar_cesta()`: Lee/Escribe en la sesión los arrays de `productos` y `unidades`.
    *   `cargar_producto($producto, $unidad)`: Añade un ítem. Si ya existe, suma la cantidad.
    *   `eliminar_producto()`: Quita un ítem o reduce cantidad.
    *   `calcular_coste()`: Recorre todos los productos y suma `precio * unidad`.
    *   `vaciar_cesta()`: Limpia todo tras completar un pedido.

---

## 4. Controladores (El Cerebro)
Los controladores (`src/Controller`) reciben las peticiones del navegador y deciden qué hacer.

### Parte Pública (`BaseController.php`)
Es el controlador principal donde ocurre casi todo.
*   `mostrar_categorias` (`/categorias`): Página de inicio. Lista todas las categorías.
*   `mostrar_productos` (`/productos/{categoria}`): Lista productos filtrados por categoría.
*   `anadir_productos` (`/anadir`): Recibe el formulario de compra.
    *   **Validación**: Comprueba si hay **Stock** suficiente antes de añadir al carrito. Si no hay, muestra un error (`addFlash`).
*   `cesta` (`/cesta`): Muestra el resumen del carrito usando el servicio `CestaCompra`.
*   `pedidos` (`/pedido`): **El proceso más crítico**.
    1.  Verifica que hay stock REAL en la base de datos (por si alguien compró el último producto mientras tú mirabas el carrito).
    2.  Crea la entidad `Pedido`.
    3.  Crea las entidades `PedidoProducto` restando el stock de cada producto.
    4.  Guarda todo en la BD (`persist` y `flush`).
    5.  Envía un **correo de confirmación** (usando `MailerInterface`).
    6.  Vacía la cesta.

### Seguridad y Registro
*   **`RegistrationController.php`**:
    *   Gestiona el formulario de registro (`RegistrationFormType`).
    *   **Hasheo**: Usa `UserPasswordHasherInterface` para cifrar la contraseña antes de guardarla.
    *   Envía correo de verificación de email (`EmailVerifier`).
*   **`SecurityController.php`**:
    *   Controla el Login (`/login`) y Logout (`/logout`). El login real lo hace Symfony automáticamente, este controlador solo pinta la vista.

### Panel de Administración (`src/Controller/Admin`)
Usa la librería **EasyAdmin**.
*   **`DashboardController.php`**: Página principal del admin y configuración del menú lateral.
*   **CrudControllers** (ej. `UsuarioCrudController`, `ProductoCrudController`):
    *   Definen qué campos se ven en los formularios de crear/editar y en los listados.
    *   **UsuarioCrudController**: Tiene lógica especial (`persistEntity`, `updateEntity`) para hashear la contraseña si el administrador crea o edita un usuario manualmente.

---

## 5. Seguridad (`security.yaml`)
Configura quién puede entrar dónde.
*   **Firewalls**: Define cómo se autentican los usuarios (formulario de login convencional).
*   **Access Control**:
    *   `/admin`: Solo para `ROLE_ADMIN`.
    *   `/pedido`, `/historial`: Solo para `ROLE_USER` (usuarios logueados).
    *   `/login`, `/register`: Público (IS_AUTHENTICATED_ANONYMOUSLY).

---

## 6. Frontend (Plantillas Twig)
En `templates/`:
*   **`base.html.twig`**: La plantilla madre. Contiene el menú de navegación (que cambia si estás logueado o eres admin) y el pie de página. Todas las demás páginas *heredan* de aquí.
*   **`registration/`**, **`security/`**: Formularios de registro y login.
*   **`productos/`**, **`cesta/`**: Vistas para listar productos y ver el carrito. Usa bucles `{% for %}` para recorrer los items.

---

## Resumen del Flujo de Compra
1.  Usuario entra en `/productos/1`.
2.  Elige cantidad y pulsa "Añadir".
3.  `BaseController::anadir_productos` comprueba stock y llama a `CestaCompra::cargar_producto`.
4.  Usuario va a `/cesta`, ve el resumen.
5.  Pulsa "Tramitar Pedido".
6.  `BaseController::pedidos` verifica stock final, crea el Pedido en BD, resta stock, envía email y vacía la sesión.

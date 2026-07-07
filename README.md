# Sistema de Fidelización y Sorteos - Centro Comercial Metropolitano

Este es un aplicativo web diseñado para gestionar campañas de fidelización, sorteos y eventos especiales para los clientes del Centro Comercial Metropolitano. Permite a los usuarios registrar sus facturas de compra para participar en diferentes tipos de eventos, mientras que ofrece un panel de administración robusto para la gestión completa de las campañas.

---

## 🚀 Alcances y Funcionalidades

El sistema se divide en dos interfaces principales: la vista pública para clientes y el panel de administración.

### ✅ Interfaz Pública (`sorteos.php`)

- **Registro de Facturas**: Los clientes pueden registrar sus facturas de compra para participar en los eventos activos.
- **Gestión de Clientes**:
  - Permite el registro de nuevos clientes con sus datos personales.
  - Los clientes ya registrados pueden participar simplemente ingresando su número de documento.
- **Participación en Eventos**:
  - **Sorteos por Compras**: Acumula el monto de las compras y genera "boletas" o inscripciones automáticamente cuando se alcanza un umbral definido por el administrador (ej: una boleta por cada $100,000 en compras).
  - **Polla Futbolera**: Permite a los usuarios registrar una predicción de marcador para un partido de fútbol específico. El sistema valida que cada persona solo pueda participar una vez por evento.
- **Visualización de Eventos**: Muestra una lista de los sorteos y torneos activos, con sus premios, condiciones y fechas de vigencia.

### 👑 Panel de Administración (`admin.php`)

- **Acceso Seguro**: Protegido por un sistema de login (usuario y contraseña) para el personal autorizado.
- **Gestión de Sorteos y Torneos**:
  - **Creación de Eventos**: Permite crear dos tipos de eventos: "Sorteo por Compras" o "Polla Futbolera".
  - **Configuración Detallada**: Se puede definir el nombre, premio, condición numérica (monto para boletas), condición en texto, fechas de vigencia y estado (activo/inactivo).
  - **Campos Específicos para "Polla"**: Al crear una polla, se pueden especificar los nombres de los equipos y la fecha del partido.
- **Gestión de Marcas/Locales**:
  - **CRUD completo**: Permite crear, leer, actualizar y eliminar las marcas o locales comerciales que participan en las campañas.
- **Generación de Informes Avanzados**:
  - **Exportación a CSV**: Descarga informes detallados por evento en formato CSV, compatible con Excel.
  - **Métricas Clave**: El informe incluye:
    - Estadísticas generales (total de participantes, ventas acumuladas, boletas generadas).
    - Lista de clientes participantes con sus montos acumulados y boletas.
    - Fechas de mayor registro de participación.
    - Ranking de ventas por marca/local.
    - **Informe de Polla**: Una sección especial con el listado de todos los participantes y sus predicciones de marcador.
- **Sistema de Envío de Boletas Digitales**:
  - **Correo Electrónico**: Funcionalidad para enviar automáticamente las boletas generadas (con un código único) al correo electrónico del cliente.
  - **Gestión de Cola**: El sistema gestiona el estado de los envíos (pendiente, enviado, fallido) y permite enviar boletas de forma individual o en lote.

---

## 🛠️ Stack Tecnológico

- **Backend**: PHP (Estilo procedural).
- **Base de Datos**: MySQL / MariaDB (utilizando la extensión PDO para conexiones seguras).
- **Frontend**: HTML5, CSS3 y JavaScript (Vanilla JS).
- **Entorno de Desarrollo**: Típicamente XAMPP (Apache, MySQL, PHP).

---

## ⚙️ Instalación y Configuración Local

Sigue estos pasos para poner en marcha el proyecto en tu entorno de desarrollo (ej. XAMPP).

1.  **Clonar el Repositorio**
    ```bash
    git clone <URL_DEL_REPOSITORIO>
    ```
    Mueve los archivos a la carpeta `htdocs` de tu instalación de XAMPP, dentro de una carpeta llamada `metropolitano`.

2.  **Crear la Base de Datos**
    - Abre phpMyAdmin (`http://localhost/phpmyadmin`).
    - Crea una nueva base de datos. Se recomienda el nombre `centrome_clientescrm` para que coincida con el código existente.

3.  **Importar la Estructura de la Base de Datos**
    - Dentro de la base de datos recién creada, ve a la pestaña "SQL" y ejecuta el siguiente script para crear todas las tablas necesarias:
      *(Nota: Este script es una compilación basada en el código. Adáptalo si es necesario)*.

    ```sql
    -- Aquí iría el script SQL completo para crear las tablas:
    -- clientes, sorteos, transacciones_compra, acumulado_clientes,
    -- boletas, marcas, usuarios, predicciones_polla, etc.
    --
    -- Ejemplo para la tabla de usuarios (recuerda generar tu propia contraseña hasheada):
    CREATE TABLE `usuarios` (
      `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(50) NOT NULL,
      `password` varchar(255) NOT NULL,
      PRIMARY KEY (`id_usuario`),
      UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


4.  **Configurar la Conexión a la Base de Datos**
    - Edita el archivo `config/database.php`.
    - Asegúrate de que los datos de conexión (`$host`, `$dbname`, `$username`, `$password`) coincidan con los de tu entorno local.

    ```php
    <?php
    $host = 'localhost';
    $dbname = 'centrome_clientescrm'; // El nombre de la BD que creaste
    $username = 'root'; // Usuario por defecto en XAMPP
    $password = '';     // Contraseña por defecto en XAMPP
    // ... resto del archivo
    ```

5.  **¡Listo!**
    - Inicia los servicios de Apache y MySQL en XAMPP.
    - Accede a la aplicación desde tu navegador: `http://localhost/metropolitano/`
    - Para acceder al panel de administración: `http://localhost/metropolitano/admin.php`

---

## 📄 Licencia

Este proyecto es de uso privado para el Centro Comercial Metropolitano. Todos los derechos reservados.
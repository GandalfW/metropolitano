<?php
session_start();
// Incluir el archivo de configuración de la base de datos
require_once 'config/database.php';

$login_error = '';

// Procesar el formulario de inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        // Consulta a la tabla usuarios
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Comprobar la contraseña usando el algoritmo bcrypt (password_verify)
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            header("Location: admin.php");
            exit;
        } else {
            $login_error = "Usuario o contraseña incorrectos.";
        }
    } catch (PDOException $e) {
        $login_error = "Error al conectar con la tabla 'usuarios'. ¿Existe la tabla?";
    }
}

// Cerrar sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Verificar si el usuario ha iniciado sesión. Si no, mostrar formulario y detener la ejecución.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login | Admin Metropolitano</title>
        <link rel="stylesheet" href="style/main.css">
        <style>
            body { display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f0f2f5; font-family: 'Segoe UI', system-ui, sans-serif; }
            .login-card { background: #ffffff; padding: 3rem 2.5rem; border-radius: 1rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); border: 1px solid #e2e8f0; width: 100%; max-width: 420px; }
            .login-card h2 { text-align: center; margin-bottom: 2rem; color: var(--color-primary); }
            .form-group { margin-bottom: 1.5rem; }
            .form-control { width: 100%; padding: 0.8rem 1.2rem; border: 2px solid #e2e8f0; border-radius: 0.5rem; background-color: #f8fafc; font-size: 1rem; transition: var(--transition); }
            .form-control:focus { outline: none; border-color: var(--color-primary); background-color: #ffffff; box-shadow: 0 0 0 3px rgba(37, 150, 190, 0.15); }
            .btn-cta { width: 100%; padding: 1rem; border: none; border-radius: 50px; background-color: var(--color-primary); color: white; font-weight: bold; cursor: pointer; transition: var(--transition); font-size: 1.1rem; }
            .btn-cta:hover { background-color: var(--color-accent); transform: translateY(-2px); }
            .error-msg { background-color: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 0.5rem; text-align: center; margin-bottom: 1.5rem; font-weight: 500; border: 1px solid #f87171; }
            .login-logo { display: block; margin: 0 auto 1.5rem; max-height: 50px; filter: brightness(0); }
        </style>
    </head>
    <body>
        <div class="login-card">
            <img src="img/METROPOLITANO-NEGRO.png" alt="Logo" class="login-logo">
            <h2>Acceso Seguro</h2>
            
            <?php if ($login_error): ?>
                <div class="error-msg"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>
            
            <form action="admin.php" method="POST">
                <div class="form-group">
                    <label for="username" style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: var(--color-accent);">Usuario</label>
                    <input type="text" id="username" name="username" class="form-control" required placeholder="Ingresa tu usuario">
                </div>
                <div class="form-group">
                    <label for="password" style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: var(--color-accent);">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="Ingresa tu contraseña">
                </div>
                <button type="submit" name="login" class="btn-cta" style="margin-top: 1rem;">Ingresar al Panel</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit; // Detiene la ejecución para no mostrar el contenido del admin si no está logueado
}

$mensaje = '';

// Procesamiento de formularios POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        
        // Accion: Guardar Sorteo
        if ($_POST['accion'] === 'guardar_sorteo') {
            $nombre = $_POST['nombre'];
            $premio = $_POST['premio'];
            $condicion_numerica = $_POST['condicion_numerica'];
            $condicion_texto = $_POST['condicion_texto'];
            $fecha_inicio = $_POST['fecha_inicio'];
            $fecha_fin = $_POST['fecha_fin'];
            $estado = isset($_POST['estado']) ? 1 : 0;

            $sql = "INSERT INTO sorteos (nombre, premio, condicion_numerica, condicion_texto, fecha_inicio, fecha_fin, estado) 
                    VALUES (:nombre, :premio, :condicion_numerica, :condicion_texto, :fecha_inicio, :fecha_fin, :estado)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nombre' => $nombre,
                ':premio' => $premio,
                ':condicion_numerica' => $condicion_numerica,
                ':condicion_texto' => $condicion_texto,
                ':fecha_inicio' => $fecha_inicio,
                ':fecha_fin' => $fecha_fin,
                ':estado' => $estado
            ]);
            $mensaje = "Evento guardado exitosamente en la base de datos.";
        } 
        
        // Accion: Guardar Local
        elseif ($_POST['accion'] === 'guardar_local') {
            $local = $_POST['local'];
            $nombre = $_POST['nombre'];
            
            try {
                $sql = "INSERT INTO marcas (local, nombre) VALUES (:local, :nombre)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':local' => $local, ':nombre' => $nombre]);
                $mensaje = "Marca guardada exitosamente.";
            } catch (PDOException $e) {
                $mensaje = "Error al guardar: " . $e->getMessage();
            }
        }
        
        // Accion: Eliminar Local
        elseif ($_POST['accion'] === 'eliminar_local') {
            $local = $_POST['local'];
            try {
                $sql = "DELETE FROM marcas WHERE local = :local";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':local' => $local]);
                $mensaje = "Marca eliminada exitosamente.";
            } catch (PDOException $e) {
                $mensaje = "Error al eliminar: " . $e->getMessage();
            }
        }
        
        // Accion: Modificar Local
        elseif ($_POST['accion'] === 'modificar_local') {
            $local = $_POST['local'];
            $nombre = $_POST['nombre'];
            try {
                $sql = "UPDATE marcas SET nombre = :nombre WHERE local = :local";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':local' => $local, ':nombre' => $nombre]);
                $mensaje = "Marca modificada exitosamente.";
            } catch (PDOException $e) {
                $mensaje = "Error al modificar: " . $e->getMessage();
            }
        }
        
        // Accion: Descargar Informe
        elseif ($_POST['accion'] === 'descargar_informe') {
            $id_sorteo = $_POST['id_sorteo'];
            
            // Obtener el nombre del sorteo
            $stmt = $pdo->prepare("SELECT nombre FROM sorteos WHERE id_sorteo = ?");
            $stmt->execute([$id_sorteo]);
            $sorteo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($sorteo) {
                $nombre_sorteo = preg_replace('/[^a-zA-Z0-9_ -]/s', '', $sorteo['nombre']);
                $filename = "informe_" . str_replace(' ', '_', strtolower($nombre_sorteo)) . "_" . date('Ymd_His') . ".csv";
                
                // Limpiar cualquier salida previa para no corromper el CSV
                if (ob_get_level()) {
                    ob_end_clean();
                }
                
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                
                $output = fopen('php://output', 'w');
                // Enviar BOM (Byte Order Mark) para que Excel reconozca correctamente los tildes y eñes (UTF-8)
                fputs($output, "\xEF\xBB\xBF");
                
                // --- SECCIÓN 1: ESTADÍSTICAS GENERALES ---
                fputcsv($output, ['--- ESTADISTICAS GENERALES ---']);
                fputcsv($output, ['Nombre del Evento', 'Total Clientes Participantes', 'Total Ventas Acumuladas', 'Total Boletas/Inscripciones']);
                
                $stmtStats = $pdo->prepare("
                    SELECT 
                        s.nombre, 
                        COUNT(DISTINCT a.id_cliente) as total_clientes,
                        SUM(a.monto_acumulado) as total_ventas,
                        (SELECT COUNT(*) FROM boletas WHERE id_sorteo = ?) as total_boletas
                    FROM sorteos s
                    LEFT JOIN acumulado_clientes a ON s.id_sorteo = a.id_sorteo
                    WHERE s.id_sorteo = ?
                    GROUP BY s.id_sorteo
                ");
                $stmtStats->execute([$id_sorteo, $id_sorteo]);
                $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
                if ($stats) {
                    fputcsv($output, [
                        $stats['nombre'], 
                        $stats['total_clientes'], 
                        $stats['total_ventas'] ? number_format((float)$stats['total_ventas'], 2, '.', '') : '0.00', 
                        $stats['total_boletas']
                    ]);
                }
                fputcsv($output, []); // Linea en blanco
                
                // --- SECCIÓN 2: CLIENTES PARTICIPANTES ---
                fputcsv($output, ['--- CLIENTES PARTICIPANTES ---']);
                fputcsv($output, ['Documento', 'Nombre Cliente', 'Ventas Acumuladas', 'Boletas/Inscripciones']);
                
                $stmtClients = $pdo->prepare("
                    SELECT 
                        c.num_documento, 
                        c.nombre_completo, 
                        a.monto_acumulado,
                        (SELECT COUNT(*) FROM boletas b WHERE b.id_cliente = c.id_cliente AND b.id_sorteo = ?) as boletas
                    FROM acumulado_clientes a
                    JOIN clientes c ON a.id_cliente = c.id_cliente
                    WHERE a.id_sorteo = ?
                    ORDER BY a.monto_acumulado DESC
                ");
                $stmtClients->execute([$id_sorteo, $id_sorteo]);
                while ($row = $stmtClients->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($output, [
                        $row['num_documento'],
                        $row['nombre_completo'],
                        number_format((float)$row['monto_acumulado'], 2, '.', ''),
                        $row['boletas']
                    ]);
                }
                fputcsv($output, []); // Linea en blanco
                
                // --- SECCIÓN 3: FECHAS DE MAYOR REGISTRO ---
                fputcsv($output, ['--- FECHAS DE MAYOR REGISTRO ---']);
                fputcsv($output, ['Fecha', 'Registros Generados']);
                $stmtFechas = $pdo->prepare("
                    SELECT DATE(fecha_generacion) as fecha, COUNT(*) as boletas_generadas
                    FROM boletas
                    WHERE id_sorteo = ?
                    GROUP BY DATE(fecha_generacion)
                    ORDER BY boletas_generadas DESC
                ");
                $stmtFechas->execute([$id_sorteo]);
                while ($row = $stmtFechas->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($output, [$row['fecha'], $row['boletas_generadas']]);
                }
                fputcsv($output, []); // Linea en blanco

                // --- SECCIÓN 4: VENTAS POR MARCA ---
                fputcsv($output, ['--- VENTAS POR MARCA ---']);
                try {
                    $stmtMarcasRep = $pdo->prepare("
                        SELECT m.nombre as marca, COUNT(t.id_transaccion) as transacciones, SUM(t.monto_compra) as total_ventas
                        FROM transacciones_compra t
                        JOIN marcas m ON t.local = m.local
                        WHERE t.sorteo = ?
                        GROUP BY t.local
                        ORDER BY total_ventas DESC
                    ");
                    $stmtMarcasRep->execute([$id_sorteo]);
                    $has_marcas = false;
                    fputcsv($output, ['Marca', 'Transacciones Registradas', 'Total en Ventas ($)']);
                    while ($row = $stmtMarcasRep->fetch(PDO::FETCH_ASSOC)) {
                        $has_marcas = true;
                        fputcsv($output, [
                            $row['marca'],
                            $row['transacciones'],
                            number_format((float)$row['total_ventas'], 2, '.', '')
                        ]);
                    }
                    if (!$has_marcas) {
                        fputcsv($output, ['No hay transacciones registradas con marcas para este sorteo.']);
                    }
                } catch (PDOException $e) {
                    fputcsv($output, ['(Aviso) No se pudo procesar esta seccion.', 'Asegúrese de que la columna "local" exista en la tabla "transacciones_compra".']);
                }

                fclose($output);
                exit; // Terminar la ejecución aquí para enviar únicamente el CSV al navegador
            } else {
                $mensaje = "El sorteo seleccionado no existe.";
            }
        }
        
        // Accion: Enviar Boletas
        elseif ($_POST['accion'] === 'enviar_boletas') {
            try {
                $id_boleta_especifica = isset($_POST['id_boleta']) ? (int)$_POST['id_boleta'] : null;
                
                $sql = "
                    SELECT b.id_boleta, b.codigo_boleta, c.email, c.nombre_completo, s.nombre as nombre_sorteo
                    FROM boletas b
                    JOIN clientes c ON b.id_cliente = c.id_cliente
                    JOIN sorteos s ON b.id_sorteo = s.id_sorteo
                    WHERE b.estado_envio = 'pendiente' AND c.email IS NOT NULL AND c.email != ''
                ";
                
                if ($id_boleta_especifica) {
                    $sql .= " AND b.id_boleta = :id_boleta";
                    $stmtPendientes = $pdo->prepare($sql);
                    $stmtPendientes->execute([':id_boleta' => $id_boleta_especifica]);
                } else {
                    $sql .= " ORDER BY b.fecha_generacion ASC LIMIT 1";
                    $stmtPendientes = $pdo->query($sql);
                }
                
                $boleta = $stmtPendientes->fetch(PDO::FETCH_ASSOC);
                
                if ($boleta) {
                    $to = $boleta['email'];
                    $subject = "Tu Boleta/Inscripcion para: " . $boleta['nombre_sorteo'];
                    
                    // Construcción del correo en formato HTML simulando un boleto
                    $message = "
                    <html>
                    <head>
                        <title>Tu Boleta - Centro Comercial Metropolitano</title>
                        <style>
                            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f2f5; padding: 20px; }
                            .ticket { background-color: #ffffff; max-width: 500px; margin: 0 auto; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); overflow: hidden; border: 2px dashed #2596be; }
                            .ticket-header { background-color: #2596be; color: #ffffff; text-align: center; padding: 25px 20px; }
                            .ticket-header img { max-height: 55px; margin-bottom: 10px; }
                            .ticket-header h3 { margin: 10px 0 0 0; font-size: 18px; font-weight: 500; letter-spacing: 1px; }
                            .ticket-body { padding: 30px; text-align: center; }
                            .ticket-body h2 { color: #0F4C81; margin-top: 0; font-size: 24px; margin-bottom: 20px; }
                            .code { font-family: monospace; font-size: 32px; font-weight: bold; color: #e74c3c; background-color: #fef2f2; padding: 15px 25px; border-radius: 8px; display: inline-block; margin: 20px 0; border: 2px solid #fecaca; letter-spacing: 4px; }
                            .ticket-footer { background-color: #f8fafc; padding: 15px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0; }
                        </style>
                    </head>
                    <body>
                        <div class='ticket'>
                            <div class='ticket-header'>
                                <!-- Asegúrate de cambiar 'https://centrometropolitano.com/' por la URL real de tu sitio cuando pases a producción -->
                                <img src='https://centrometropolitano.com/img/METROPOLITANO-NEGRO.png' alt='Logo Metropolitano'>
                                <h3>¡Participa y Gana!</h3>
                            </div>
                            <div class='ticket-body'>
                                <h2>" . htmlspecialchars($boleta['nombre_sorteo']) . "</h2>
                                <p style='font-size: 16px; color: #334155;'>Hola <strong>" . htmlspecialchars($boleta['nombre_completo']) . "</strong>,</p>
                                <p style='color: #475569; margin-bottom: 5px;'>Este es tu código oficial de participación:</p>
                                <div class='code'>" . htmlspecialchars($boleta['codigo_boleta']) . "</div>
                                <p style='color: #475569; font-size: 16px;'>¡Mucha suerte!</p>
                            </div>
                            <div class='ticket-footer'>
                                &copy; " . date('Y') . " Centro Comercial Metropolitano.<br>Todos los derechos reservados.
                            </div>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    // Cabeceras necesarias para el envío de correos HTML
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    $headers .= "From: Centro Comercial Metropolitano <noreply@metropolitano.com>" . "\r\n";
                    
                    // Intentar enviar el correo (Requiere servidor SMTP configurado en XAMPP o Hosting)
                    $exito = @mail($to, $subject, $message, $headers);
                    // NOTA: Para probar en local (XAMPP) sin un servidor de correo real configurado, descomenta la siguiente línea:
                    // $exito = true; 
                    
                    $nuevo_estado = $exito ? 'enviado' : 'fallido';
                    $stmtUpdate = $pdo->prepare("UPDATE boletas SET estado_envio = ?, fecha_envio = NOW() WHERE id_boleta = ?");
                    $stmtUpdate->execute([$nuevo_estado, $boleta['id_boleta']]);
                    
                    if ($exito) {
                        $mensaje = "Boleta " . $boleta['codigo_boleta'] . " enviada con éxito a " . htmlspecialchars($to) . ".";
                    } else {
                        $mensaje = "Error al enviar la boleta " . $boleta['codigo_boleta'] . " a " . htmlspecialchars($to) . ". (Marcada como fallida).";
                    }
                } else {
                    $mensaje = "No se encontraron boletas pendientes válidas para enviar.";
                }
            } catch (PDOException $e) {
                $mensaje = "Error al procesar el envío: " . $e->getMessage();
            }
        }
    }
}

// Obtener la información de los sorteos desde la base de datos
$sorteos = [];
try {
    $stmt = $pdo->query("SELECT * FROM sorteos ORDER BY id_sorteo DESC");
    $sorteos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silenciar error si la tabla no existe o mostrarlo en debug
}

// Obtener la información de las marcas
$marcas = [];
try {
    $stmtMarcas = $pdo->query("SELECT local, nombre FROM marcas ORDER BY nombre ASC");
    $marcas = $stmtMarcas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración | Metropolitano</title>
    <link rel="icon" type="image/png" href="img/METROPOLITANO-NEGRO.png">
    <link rel="stylesheet" href="style/main.css">
    <style>
        .admin-container {
            padding: 8rem 2rem 4rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        .admin-card {
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .admin-table th, .admin-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        .admin-table th {
            background-color: var(--color-primary);
            color: white;
            font-weight: 600;
        }
        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        .badge-active { background-color: #d1fae5; color: #065f46; }
        .badge-inactive { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <header class="header scrolled" id="header">
        <div class="header-container">
            <a href="index.html" class="logo" aria-label="Inicio">
                <img src="img/METROPOLITANO-BLANCO.png" alt="Metropolitano Logo">
            </a>
            <nav class="navbar">
                <ul class="nav-links">
                    <li><a href="index.html">Volver al Sitio</a></li>
                    <li><a href="admin.php" class="active">Panel Admin</a></li>
                    <li><a href="admin.php?logout=1" style="color: #ff7675; font-weight: bold;">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="admin-container">
        <h1 style="color: var(--color-primary); margin-bottom: 2rem;">Panel de Administración</h1>

        <?php if(!empty($mensaje)): ?>
            <div class="alert-success" style="margin-bottom: 2rem;"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <div class="admin-grid">
            <!-- Ingreso de Locales -->
            <div class="admin-card">
                <h2 style="margin-bottom: 1.5rem; color: var(--color-accent);">Ingresar Nueva Marca</h2>
                <form action="admin.php" method="POST">
                    <input type="hidden" name="accion" value="guardar_local">
                    
                    <div class="form-group">
                        <label for="local" style="font-weight: 600;">Número o ID del Local (Ubicación)</label>
                        <input type="text" id="local" name="local" class="form-control" required placeholder="Ej: 133 TB">
                    </div>
                    
                    <div class="form-group">
                        <label for="nombre" style="font-weight: 600;">Nombre de la Marca</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" required placeholder="Ej: Cine Colombia, Zara">
                    </div>
                    
                    <button type="submit" class="btn-cta" style="width: 100%; border: none; cursor: pointer; margin-top: 1rem;">Guardar Marca</button>
                </form>
            </div>

            <!-- Ingreso de Sorteos -->
            <div class="admin-card">
                <h2 style="margin-bottom: 1.5rem; color: var(--color-accent);">Crear Nuevo Sorteo o Torneo</h2>
                <form action="admin.php" method="POST">
                    <input type="hidden" name="accion" value="guardar_sorteo">
                    
                    <div class="form-group">
                        <label for="nombre" style="font-weight: 600;">Nombre del Evento</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" required placeholder="Ej: Sorteo de Fin de Año, Torneo de eSports">
                    </div>
                    
                    <div class="form-group">
                        <label for="premio" style="font-weight: 600;">Premio a Entregar / Concepto</label>
                        <input type="text" id="premio" name="premio" class="form-control" required placeholder="Ej: Vehículo 0KM, Inscripción">
                    </div>
                    
                    <div class="form-group">
                        <label for="condicion_numerica" style="font-weight: 600;">Condición Numérica (Monto Requerido o Costo de Inscripción $)</label>
                        <input type="number" id="condicion_numerica" name="condicion_numerica" class="form-control" required min="0" step="0.01" placeholder="Ej: 100000">
                    </div>
                    
                    <div class="form-group">
                        <label for="condicion_texto" style="font-weight: 600;">Condición en Texto (Detalles)</label>
                        <textarea id="condicion_texto" name="condicion_texto" class="form-control" required rows="2" placeholder="Ej: Por cada $100.000 en compras, o Valor total de inscripción..."></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                        <div style="flex: 1;">
                            <label for="fecha_inicio" style="font-weight: 600;">Fecha de Inicio</label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" required>
                        </div>
                        <div style="flex: 1;">
                            <label for="fecha_fin" style="font-weight: 600;">Fecha de Fin</label>
                            <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 600;">
                            <input type="checkbox" name="estado" value="1" checked> Marcar Evento como Activo
                        </label>
                    </div>
                    
                    <button type="submit" class="btn-cta" style="width: 100%; border: none; cursor: pointer;">Guardar Evento</button>
                </form>
            </div>

            <!-- Descargar Informes -->
            <div class="admin-card">
                <h2 style="margin-bottom: 1.5rem; color: var(--color-accent);">Informes de Campaña</h2>
                <p style="margin-bottom: 1.5rem; color: #64748b; font-size: 0.95rem;">Descarga un archivo CSV (Excel) con clientes participantes/inscritos, fechas de mayor registro, marcas más vendidas y estadísticas.</p>
                <form action="admin.php" method="POST">
                    <input type="hidden" name="accion" value="descargar_informe">
                    
                    <div class="form-group">
                        <label for="id_sorteo_informe" style="font-weight: 600;">Selecciona el Evento a Exportar</label>
                        <select id="id_sorteo_informe" name="id_sorteo" class="form-control" required>
                            <option value="">-- Elige un evento --</option>
                            <?php foreach ($sorteos as $sorteo): ?>
                                <option value="<?php echo htmlspecialchars($sorteo['id_sorteo']); ?>"><?php echo htmlspecialchars($sorteo['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-cta" style="width: 100%; border: none; cursor: pointer; margin-top: 1rem; background-color: #10b981;">Descargar CSV</button>
                </form>
            </div>

            <!-- Enviar Boletas -->
            <div class="admin-card">
                <h2 style="margin-bottom: 1.5rem; color: var(--color-accent);">Envío de Boletas Digitales</h2>
                <p style="margin-bottom: 1.5rem; color: #64748b; font-size: 0.95rem;">Envía por correo electrónico los códigos a los clientes con boletas pendientes. (Requiere configuración SMTP).</p>
                
                <?php
                $pendientes = 0;
                $lista_pendientes = [];
                try {
                    $stmtP = $pdo->query("SELECT COUNT(*) FROM boletas WHERE estado_envio = 'pendiente'");
                    $pendientes = $stmtP->fetchColumn();
                    
                    $stmtLista = $pdo->query("
                        SELECT b.id_boleta, b.codigo_boleta, c.email
                        FROM boletas b
                        JOIN clientes c ON b.id_cliente = c.id_cliente
                        WHERE b.estado_envio = 'pendiente' AND c.email IS NOT NULL AND c.email != ''
                        ORDER BY b.fecha_generacion ASC
                        LIMIT 5
                    ");
                    $lista_pendientes = $stmtLista->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {}
                ?>
                
                <div style="background-color: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; text-align: center;">
                    <span style="font-size: 2rem; font-weight: bold; color: var(--color-primary); display: block;"><?php echo $pendientes; ?></span>
                    <span style="color: #64748b; font-size: 0.9rem;">Boletas Pendientes de Envío Totales</span>
                </div>
                
                <?php if (count($lista_pendientes) > 0): ?>
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1rem; color: var(--color-accent); margin-bottom: 0.8rem;">Próximas a enviar:</h3>
                        <ul style="list-style: none; padding: 0; margin: 0; border: 1px solid #e2e8f0; border-radius: 0.5rem; overflow: hidden;">
                            <?php foreach ($lista_pendientes as $p): ?>
                                <li style="display: flex; justify-content: space-between; align-items: center; padding: 0.8rem 1rem; border-bottom: 1px solid #e2e8f0; background: #fff;">
                                    <div style="line-height: 1.2;">
                                        <strong style="color: var(--color-primary); font-size: 0.95rem;"><?php echo htmlspecialchars($p['codigo_boleta']); ?></strong><br>
                                        <span style="color: #64748b; font-size: 0.85rem;"><?php echo htmlspecialchars($p['email']); ?></span>
                                    </div>
                                    <form action="admin.php" method="POST" style="margin: 0;">
                                        <input type="hidden" name="accion" value="enviar_boletas">
                                        <input type="hidden" name="id_boleta" value="<?php echo $p['id_boleta']; ?>">
                                        <button type="submit" class="btn-cta" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; border-radius: 0.3rem;">Enviar a éste</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="admin.php" method="POST">
                    <input type="hidden" name="accion" value="enviar_boletas">
                    <button type="submit" class="btn-cta" style="width: 100%; border: none; background-color: #3b82f6;" <?php echo count($lista_pendientes) == 0 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : 'style="cursor: pointer;"'; ?>>Enviar Siguiente Automáticamente</button>
                </form>
            </div>
        </div>

        <!-- Visualización de Datos: Tabla de Sorteos -->
        <div class="admin-card" style="margin-bottom: 3rem; overflow-x: auto;">
            <h2 style="margin-bottom: 1.5rem; color: var(--color-accent);">Sorteos y Torneos Registrados</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Premio</th>
                        <th>Monto Requerido</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($sorteos) > 0): ?>
                        <?php foreach ($sorteos as $sorteo): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sorteo['id_sorteo'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($sorteo['nombre'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($sorteo['premio'] ?? ''); ?></td>
                            <td>$<?php echo number_format((float)($sorteo['condicion_numerica'] ?? 0), 2); ?></td>
                            <td><?php echo htmlspecialchars($sorteo['fecha_inicio'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($sorteo['fecha_fin'] ?? ''); ?></td>
                            <td>
                                <?php if (isset($sorteo['estado']) && $sorteo['estado'] == 1): ?>
                                    <span class="badge badge-active">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Inactivo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem;">No hay eventos registrados aún en la base de datos.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Visualización de Datos: Tabla de Marcas -->
        <div class="admin-card" style="margin-bottom: 3rem; overflow-x: auto;">
            <h2 style="margin-bottom: 1.5rem; color: var(--color-accent);">Marcas Registradas en la Base de Datos</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Local</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($marcas) > 0): ?>
                        <?php foreach ($marcas as $marca): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($marca['local'] ?? ''); ?></td>
                            <td>
                                <form action="admin.php" method="POST" style="display:flex; gap: 0.5rem; margin:0;">
                                    <input type="hidden" name="accion" value="modificar_local">
                                    <input type="hidden" name="local" value="<?php echo htmlspecialchars($marca['local'] ?? ''); ?>">
                                    <input type="text" name="nombre" class="form-control" style="padding: 0.5rem; font-size: 0.95rem; flex: 1;" value="<?php echo htmlspecialchars($marca['nombre'] ?? ''); ?>" required>
                                    <button type="submit" class="btn-cta" style="padding: 0.5rem 1rem; font-size: 0.9rem; border-radius: 5px;">Guardar</button>
                                </form>
                            </td>
                            <td style="width: 1%;">
                                <form action="admin.php" method="POST" style="margin:0;" onsubmit="return confirm('¿Estás seguro de eliminar esta marca?');">
                                    <input type="hidden" name="accion" value="eliminar_local">
                                    <input type="hidden" name="local" value="<?php echo htmlspecialchars($marca['local'] ?? ''); ?>">
                                    <button type="submit" class="btn-cta" style="padding: 0.5rem 1rem; font-size: 0.9rem; border-radius: 5px; background-color: #ff7675;">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 2rem;">No hay marcas registradas aún en la base de datos.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
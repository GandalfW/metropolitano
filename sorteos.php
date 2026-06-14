<?php
require_once 'config/database.php';

$mensaje = '';
$es_error = false;

// Cargar las marcas desde la base de datos para llenar el formulario
$marcas = [];
try {
    $stmtMarcas = $pdo->query("SELECT local, nombre FROM marcas ORDER BY nombre ASC");
    $marcas = $stmtMarcas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}

// Cargar los sorteos activos desde la base de datos para la información y el formulario
$sorteos_activos = [];
try {
    $stmtSorteos = $pdo->query("SELECT * FROM sorteos WHERE estado = 1 ORDER BY nombre ASC");
    $sorteos_activos = $stmtSorteos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Iniciar transacción de base de datos
        $pdo->beginTransaction();

        $num_documento = trim($_POST['num_documento']);
        $id_sorteo = (int)$_POST['sorteo'];
        $valor_factura = (float)$_POST['valor_factura'];
        $local = $_POST['local'] ?? null;
        $es_nuevo = $_POST['es_nuevo'] ?? 'no';
        
        // 1. GESTIONAR EL CLIENTE
        $stmt = $pdo->prepare("SELECT id_cliente FROM clientes WHERE num_documento = ?");
        $stmt->execute([$num_documento]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cliente) {
            $id_cliente = $cliente['id_cliente'];
        } else {
            // Si el cliente no existe en base de datos pero seleccionó que "Ya estaba registrado"
            if ($es_nuevo === 'no') {
                throw new Exception("El número de documento no se encuentra registrado. Por favor, selecciona 'Sí, soy nuevo' para registrarte primero antes de guardar tu compra.");
            }

            // Insertar nuevo cliente asegurando campos nulos si vienen vacíos
            $stmtInsert = $pdo->prepare("INSERT INTO clientes (nombre_completo, tipo_documento, num_documento, fecha_nacimiento, genero, telefono_movil, email, direccion, barrio, comuna, estrato, autorizacion_datos, acepta_promociones) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmtInsert->execute([
                $_POST['nombre_completo'],
                $_POST['tipo_documento'],
                $num_documento,
                !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null,
                !empty($_POST['genero']) ? $_POST['genero'] : null,
                $_POST['telefono_movil'],
                !empty($_POST['email']) ? $_POST['email'] : null,
                !empty($_POST['direccion']) ? $_POST['direccion'] : null,
                !empty($_POST['barrio']) ? $_POST['barrio'] : null,
                !empty($_POST['comuna']) ? (int)$_POST['comuna'] : null,
                !empty($_POST['estrato']) ? (int)$_POST['estrato'] : null,
                isset($_POST['autorizacion_datos']) ? 1 : 0,
                isset($_POST['acepta_promociones']) ? 1 : 0
            ]);
            $id_cliente = $pdo->lastInsertId();
        }

        // 2. REGISTRAR TRANSACCIÓN DE COMPRA
        $stmtTrans = $pdo->prepare("INSERT INTO transacciones_compra (id_cliente, monto_compra, local) VALUES (?, ?, ?)");
        $stmtTrans->execute([$id_cliente, $valor_factura, $local]);

        // 3. ACTUALIZAR ACUMULADO
        $stmtAcum = $pdo->prepare("SELECT id_acumulado, monto_acumulado FROM acumulado_clientes WHERE id_cliente = ? AND id_sorteo = ?");
        $stmtAcum->execute([$id_cliente, $id_sorteo]);
        $acumulado = $stmtAcum->fetch(PDO::FETCH_ASSOC);

        $nuevo_monto_acumulado = $valor_factura;
        if ($acumulado) {
            $nuevo_monto_acumulado += (float)$acumulado['monto_acumulado'];
            $stmtUpdAcum = $pdo->prepare("UPDATE acumulado_clientes SET monto_acumulado = ? WHERE id_acumulado = ?");
            $stmtUpdAcum->execute([$nuevo_monto_acumulado, $acumulado['id_acumulado']]);
        } else {
            $stmtInsAcum = $pdo->prepare("INSERT INTO acumulado_clientes (id_sorteo, id_cliente, monto_acumulado) VALUES (?, ?, ?)");
            $stmtInsAcum->execute([$id_sorteo, $id_cliente, $nuevo_monto_acumulado]);
        }

        // 4. GENERAR BOLETAS SI SE CUMPLE LA CONDICIÓN
        $stmtCondicion = $pdo->prepare("SELECT condicion_numerica FROM sorteos WHERE id_sorteo = ?");
        $stmtCondicion->execute([$id_sorteo]);
        $sorteoInfo = $stmtCondicion->fetch(PDO::FETCH_ASSOC);

        $boletas_generadas = 0;
        if ($sorteoInfo && (float)$sorteoInfo['condicion_numerica'] > 0) {
            $condicion = (float)$sorteoInfo['condicion_numerica'];
            
            // Se calcula a cuántas boletas tiene derecho según su acumulado histórico total
            $boletas_merecidas = floor($nuevo_monto_acumulado / $condicion);
            
            // Se miran cuántas boletas ya tiene generadas
            $stmtCountBoletas = $pdo->prepare("SELECT COUNT(*) as total FROM boletas WHERE id_cliente = ? AND id_sorteo = ?");
            $stmtCountBoletas->execute([$id_cliente, $id_sorteo]);
            $boletas_actuales = $stmtCountBoletas->fetch(PDO::FETCH_ASSOC)['total'];

            // Se generan solo las boletas nuevas
            $boletas_a_crear = $boletas_merecidas - $boletas_actuales;

            for ($i = 0; $i < $boletas_a_crear; $i++) {
                $codigo_boleta = strtoupper(uniqid('BOL-')); // Genera un código único tipo BOL-64A1B2C...
                $stmtInsBoleta = $pdo->prepare("INSERT INTO boletas (id_sorteo, id_cliente, codigo_boleta) VALUES (?, ?, ?)");
                $stmtInsBoleta->execute([$id_sorteo, $id_cliente, $codigo_boleta]);
                $boletas_generadas++;
            }
        }

        // Confirmar todos los cambios
        $pdo->commit();

        $mensaje = "¡Registro exitoso! Tu factura ha sido validada.";
        if ($boletas_generadas > 0) {
            $mensaje .= " ¡Felicidades! Has generado " . $boletas_generadas . " boleta(s) nueva(s).";
        } else if (isset($condicion)) {
            $faltante = $condicion - fmod($nuevo_monto_acumulado, $condicion);
            $mensaje .= " Sigue comprando. Te faltan $" . number_format($faltante, 2) . " para tu próxima boleta.";
        }

    } catch (Exception $e) {
        $pdo->rollBack(); // Revertir cambios si algo sale mal para evitar datos corruptos
        $mensaje = $e->getMessage();
        $es_error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sorteos | Metropolitano</title>
    <meta name="description" content="Participa en los espectaculares sorteos del Centro Comercial Metropolitano registrando tus facturas de compra.">
    <link rel="icon" type="image/png" href="img/METROPOLITANO-NEGRO.png">
    <link rel="stylesheet" href="style/main.css">
    <script src="js/main.js" defer></script>
</head>
<body>
    <!-- Header Navbar -->
    <header class="header" id="header">
        <div class="header-container">
            <a href="index.html" class="logo" aria-label="Inicio">
                <img src="img/METROPOLITANO-BLANCO.png" alt="Metropolitano Logo">
            </a>
            
            <!-- Botón de Menú Móvil -->
            <button class="hamburger" id="hamburger" aria-label="Abrir menú" aria-expanded="false">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>

            <nav class="navbar">
                <ul class="nav-links">
                    <!-- Nota: Los enlaces apuntan a index.html para asegurar la navegación entre páginas -->
                    <li><a href="index.html#inicio">Inicio</a></li>
                    <li><a href="index.html#locales">Locales</a></li>
                    <li><a href="index.html#servicios">Oficinas</a></li>
                    <li><a href="sorteos.php" class="active">Sorteos</a></li>
                    <li><a href="#contacto">Contacto</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <!-- Hero Sorteos -->
        <section class="hero-sorteos">
            <div class="container">
                <h1>Participa y Gana</h1>
                <p>Registra tus facturas de compra y participa en nuestros espectaculares sorteos de temporada.</p>
            </div>
        </section>

        <!-- Sección Principal Sorteos -->
        <section class="sorteos-wrapper container" id="registro">
            <div class="sorteos-grid">
                
                <!-- Panel Informativo de Condiciones -->
                <div class="raffle-info">
                    <h2>Sorteos Activos</h2>
                    <p style="margin-top: 0.5rem; opacity: 0.9;">Conoce las condiciones para participar en nuestros sorteos actuales y acumula tus oportunidades.</p>
                    
                    <?php if (count($sorteos_activos) > 0): ?>
                        <?php foreach ($sorteos_activos as $sorteo_activo): ?>
                            <div class="raffle-card">
                                <h3>🎁 <?php echo htmlspecialchars($sorteo_activo['nombre']); ?></h3>
                                <p><strong>Premio:</strong> <?php echo htmlspecialchars($sorteo_activo['premio']); ?></p>
                                <p><strong>Condición:</strong> <?php echo htmlspecialchars($sorteo_activo['condicion_texto']); ?></p>
                                <p><strong>Vigencia:</strong> Hasta el <?php echo htmlspecialchars($sorteo_activo['fecha_fin']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="margin-top: 1rem;">No hay sorteos activos en este momento.</p>
                    <?php endif; ?>
                </div>

                <!-- Formulario de Registro -->
                <div class="form-container">
                    <h2 style="color: var(--color-primary); margin-bottom: 0.5rem;">Registra tu Factura</h2>
                    <p style="margin-bottom: 2rem; color: #64748b;">Ingresa tus datos personales y los de tu compra para generar tus boletas.</p>
                    
                    <?php if(!empty($mensaje)): ?>
                        <div class="alert-success" <?php if($es_error) echo 'style="background-color: #fee2e2; color: #dc2626; border-color: #f87171;"'; ?>><?php echo htmlspecialchars($mensaje); ?></div>
                    <?php endif; ?>

                    <form action="sorteos.php#registro" method="POST">
                        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                            <div style="flex: 1;">
                                <label for="tipo_documento" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-accent); font-size: 0.95rem;">Tipo Documento</label>
                                <select id="tipo_documento" name="tipo_documento" class="form-control" required>
                                    <option value="CC" selected>Cédula de Ciudadanía (CC)</option>
                                    <option value="TI">Tarjeta de Identidad (TI)</option>
                                    <option value="CE">Cédula de Extranjería (CE)</option>
                                    <option value="PASAPORTE">Pasaporte</option>
                                </select>
                            </div>
                            <div style="flex: 2;">
                                <label for="num_documento" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-accent); font-size: 0.95rem;">Número de Documento</label>
                                <input type="text" id="num_documento" name="num_documento" class="form-control" required placeholder="Ej: 1075000000">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-accent); font-size: 0.95rem;">¿Es tu primera vez participando?</label>
                            <div style="display: flex; gap: 1.5rem; margin-top: 0.5rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="radio" name="es_nuevo" value="si" id="es_nuevo_si"> Sí, soy nuevo</label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="radio" name="es_nuevo" value="no" id="es_nuevo_no" checked> No, ya estoy registrado</label>
                            </div>
                        </div>

                        <div id="campos_registro" style="display: none;">
                            <div class="form-group">
                                <label for="nombre_completo">Nombre Completo</label>
                                <input type="text" id="nombre_completo" name="nombre_completo" class="form-control req-new" placeholder="Tu nombre completo">
                            </div>

                            <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                                <div style="flex: 1;">
                                    <label for="fecha_nacimiento" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-accent); font-size: 0.95rem;">Fecha de Nacimiento</label>
                                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-control">
                                </div>
                                <div style="flex: 1;">
                                    <label for="genero" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-accent); font-size: 0.95rem;">Género</label>
                                    <select id="genero" name="genero" class="form-control">
                                        <option value="">-- Seleccionar --</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Femenino</option>
                                        <option value="OTRO">Otro</option>
                                    </select>
                                </div>
                            </div>

                            <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                                <div style="flex: 1;">
                                    <label for="telefono_movil" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-accent); font-size: 0.95rem;">Teléfono Móvil</label>
                                    <input type="tel" id="telefono_movil" name="telefono_movil" class="form-control req-new" placeholder="Ej: 3001234567">
                                </div>
                                <div style="flex: 1;">
                                    <label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-accent); font-size: 0.95rem;">Correo Electrónico</label>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="correo@ejemplo.com">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="direccion">Dirección de Residencia</label>
                                <input type="text" id="direccion" name="direccion" class="form-control" placeholder="Ej: Calle 10 # 5-20">
                            </div>

                            <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                                <div style="flex: 2;">
                                    <label for="barrio" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-accent); font-size: 0.95rem;">Barrio</label>
                                    <input type="text" id="barrio" name="barrio" class="form-control" placeholder="Ej: Centro">
                                </div>
                                <div style="flex: 1;">
                                    <label for="comuna" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-accent); font-size: 0.95rem;">Comuna</label>
                                    <input type="number" id="comuna" name="comuna" class="form-control" min="1" max="20" placeholder="Ej: 1">
                                </div>
                                <div style="flex: 1;">
                                    <label for="estrato" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-accent); font-size: 0.95rem;">Estrato</label>
                                    <input type="number" id="estrato" name="estrato" class="form-control" min="1" max="6" placeholder="Ej: 3">
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 1rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer;">
                                    <input type="checkbox" id="autorizacion_datos" name="autorizacion_datos" value="1" class="req-new">
                                    Acepto el tratamiento de mis datos personales
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer; margin-top: 0.5rem;">
                                    <input type="checkbox" id="acepta_promociones" name="acepta_promociones" value="1">
                                    Deseo recibir promociones y novedades al correo
                                </label>
                            </div>
                        </div>

                        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 2rem 0 1.5rem;">
                        <h3 style="color: var(--color-primary); margin-bottom: 1rem; font-size: 1.1rem;">Detalles de la Compra</h3>

                        <div class="form-group">
                            <label for="sorteo">¿En qué sorteo deseas aplicar?</label>
                            <select id="sorteo" name="sorteo" class="form-control" required>
                                <option value="">-- Selecciona un sorteo --</option>
                                <?php foreach ($sorteos_activos as $sorteo_activo): ?>
                                    <option value="<?php echo htmlspecialchars($sorteo_activo['id_sorteo']); ?>"><?php echo htmlspecialchars($sorteo_activo['nombre'] . ' - Premio: ' . $sorteo_activo['premio']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="local">Local de Compra</label>
                            <select id="local" name="local" class="form-control" required>
                                <option value="">-- Selecciona el local --</option>
                                <?php foreach ($marcas as $marca): ?>
                                    <option value="<?php echo htmlspecialchars($marca['local']); ?>"><?php echo htmlspecialchars($marca['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="display: flex; gap: 1rem;">
                            <div class="form-group" style="flex: 1;">
                                <label for="num_factura">No. de Factura</label>
                                <input type="text" id="num_factura" name="num_factura" class="form-control" required placeholder="Ej: F-1024">
                            </div>
                            
                            <div class="form-group" style="flex: 1;">
                                <label for="valor_factura">Valor Total ($)</label>
                                <input type="number" id="valor_factura" name="valor_factura" class="form-control" required placeholder="Ej: 150000" min="1000">
                            </div>
                        </div>

                        <button type="submit" class="btn-cta" style="width: 100%; border: none; cursor: pointer; margin-top: 1rem; font-family: inherit;">
                            Validar y Participar
                        </button>
                    </form>
                </div>

            </div>
        </section>
    </main>

    <!-- Footer Idéntico al Principal -->
    <footer class="footer" id="contacto">
        <!-- ... (Mismo contenido del footer de index.html) ... -->
        <div class="container footer-grid">
            <div class="footer-column footer-info">
                <img src="img/METROPOLITANO-BLANCO.png" alt="Metropolitano Logo" class="footer-logo">
                <p>Tu centro comercial de confianza.</p>
            </div>
            <div class="footer-column footer-links">
                <h4>Enlaces Legales</h4>
                <ul>
                    <li><a href="#">Términos y Condiciones</a></li>
                    <li><a href="#">Política de Privacidad</a></li>
                </ul>
            </div>
            <div class="footer-column footer-social">
                <h4>Síguenos</h4>
                <div class="social-icons">
                    <a href="https://www.facebook.com/ccmetropolitanoneiva" aria-label="Facebook">FB</a>
                    <a href="https://www.instagram.com/ccmetropolitanoneiva" aria-label="Instagram">IG</a>
                    <a href="https://www.tiktok.com/@ccmetropolitanoneiva" aria-label="TikTok">TikTok</a>
                </div>
            </div>
            <div class="footer-column footer-copyright">
                <div class="separator-line"></div>
                <p>&copy; 2026 Centro Comercial Metropolitano.<br>Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const radioSi = document.getElementById('es_nuevo_si');
            const radioNo = document.getElementById('es_nuevo_no');
            const camposRegistro = document.getElementById('campos_registro');
            const inputsRequeridos = camposRegistro.querySelectorAll('.req-new');

            function toggleCamposNuevos() {
                if (radioSi.checked) {
                    camposRegistro.style.display = 'block';
                    // Vuelve obligatorios los datos personales
                    inputsRequeridos.forEach(input => input.required = true);
                } else {
                    camposRegistro.style.display = 'none';
                    // Quita el atributo required para que el formulario pueda enviarse si ya es usuario
                    inputsRequeridos.forEach(input => input.required = false);
                }
            }

            radioSi.addEventListener('change', toggleCamposNuevos);
            radioNo.addEventListener('change', toggleCamposNuevos);
            
            // Establecer el estado inicial cuando cargue la página
            toggleCamposNuevos();
        });
    </script>
</body>
</html>
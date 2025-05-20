<?php
session_start();
include 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Enviar lista por correo
if (isset($_POST['send_email'])) {
    $paciente = $_POST['nombre_paciente'];
    $cirugia = $_POST['cirugia'];
    $cod_cirugia = $_POST['cod_cirugia'];
    $pabellon = $_POST['pabellon'];
    $cirujano = $_POST['cirujano'];
    $equipo = $_POST['equipo'];
    $insumos = implode(', ', $_SESSION['carrito']);
    $responsable = $_POST['responsable'];

    // Guardar datos de la cirugía
    $stmt = $conn->prepare("INSERT INTO cirugias (nombre_paciente, cirugia, cod_cirugia, pabellon, cirujano, equipo, insumos, responsable) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$paciente, $cirugia, $cod_cirugia, $pabellon, $cirujano, $equipo, $insumos, $responsable]);

    // Obtener todos los correos de usuarios válidos
    $result = $conn->query("SELECT correo FROM usuarios WHERE correo IS NOT NULL AND correo != ''");

    if ($result->num_rows === 0) {
        echo "<script>alert('Error: No hay usuarios con correo registrado.'); window.location.href=window.location.href;</script>";
        exit();
    }

    // Enviar correo a cada usuario individualmente
    while ($row = $result->fetch_assoc()) {
        $correo = $row['correo'];

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'valdiviaalejandro2001@gmail.com';
            $mail->Password = 'vhgg mzzf kqov npjx';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = PHPMailer::ENCODING_BASE64;
            $mail->setFrom('valdiviaalejandro2001@gmail.com', 'Hospital Clínico Félix Bulnes');
            $mail->addAddress($correo);

            $mail->Subject = 'Lista de Insumos para Cirugía';
            $mail->Body = "Se ha programado una cirugía con los siguientes detalles:\n\n";
            $mail->Body .= "Paciente: $paciente\n";
            $mail->Body .= "Cirugía: $cirugia\n";
            $mail->Body .= "Código de cirugía: $cod_cirugia\n";
            $mail->Body .= "Pabellón: $pabellon\n";
            $mail->Body .= "Cirujano: $cirujano\n";
            $mail->Body .= "Equipo médico: $equipo\n";
            $mail->Body .= "Insumos requeridos: $insumos\n";
            $mail->Body .= "Responsable del registro: $responsable\n\n";
            $mail->Body .= "Atentamente,\nSistema de Cirugías - Hospital Clínico Félix Bulnes";

            $mail->send();

        } catch (Exception $e) {
            echo "<script>alert('Error al enviar el correo a: $correo. Error: {$mail->ErrorInfo}'); window.location.href=window.location.href;</script>";
            exit();
        }
    }

    $_SESSION['carrito'] = [];

    echo "<script>alert('Correo enviado correctamente a todos los usuarios con correo registrado.'); window.location.href=window.location.href;</script>";
    exit();
}
// Agregar o quitar insumo al carrito
if (isset($_POST['action'])) {
    $insumo = $_POST['insumo'];
    $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;

    if ($_POST['action'] === 'add') {
        if (isset($_SESSION['carrito'][$insumo])) {
            $_SESSION['carrito'][$insumo] += $cantidad;
        } else {
            $_SESSION['carrito'][$insumo] = $cantidad;
        }
    }

    if ($_POST['action'] === 'remove') {
        if (isset($_SESSION['carrito'][$insumo])) {
            unset($_SESSION['carrito'][$insumo]);
        }
    }

    // Enviar el carrito actualizado como JSON
    echo json_encode($_SESSION['carrito']);
    exit;
}

// Finalizar compra y actualizar stock
if (isset($_POST['send_email'])) {
    $paciente = $_POST['nombre_paciente'];
    $cirugia = $_POST['cirugia'];
    $cod_cirugia = $_POST['cod_cirugia'];
    $pabellon = $_POST['pabellon'];
    $cirujano = $_POST['cirujano'];
    $equipo = $_POST['equipo'];
    $responsable = $_POST['responsable'];

    $insumos_usados = [];
    foreach ($_SESSION['carrito'] as $insumo => $cantidad) {
        $stmt = $conn->prepare("UPDATE componentes SET stock = stock - ? WHERE insumo = ?");
        $stmt->execute([$cantidad, $insumo]);
        $insumos_usados[] = "$insumo (x$cantidad)";
    }

    $insumos = implode(', ', $insumos_usados);

    $stmt = $conn->prepare("INSERT INTO cirugias (nombre_paciente, cirugia, cod_cirugia, pabellon, cirujano, equipo, insumos, responsable) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$paciente, $cirugia, $cod_cirugia, $pabellon, $cirujano, $equipo, $insumos, $responsable]);

    $_SESSION['carrito'] = [];
    echo "<script>alert('Compra finalizada y stock actualizado.'); window.location.href=window.location.href;</script>";
    exit();
}
// Mostrar Carrito de Compras
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="asset/styles.css">
    <meta charset="UTF-8">
    <title>Administración de Insumos de Urologia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <div class="header">
        <img src="asset/logo.png" alt="Logo">
        <div class="header-text">
            <div class="main-title">Solicitar insumos médicos</div>
            <div class="sub-title">Hospital Clínico Félix Bulnes</div>
        </div>
        <button id="cuenta-btn" onclick="toggleAccountInfo()"><?php echo $_SESSION['nombre']; ?></button>
        <div id="accountInfo" style="display: none;">
            <p><strong>Usuario: </strong><?php echo $_SESSION['nombre']; ?></p>
            <form action="logout.php" method="POST">
                <button type="submit" class="logout-btn">Salir</button>
            </form>
            <button type="button" class="volver-btn" onclick="window.location.href='principal.php'">Volver</button>
        </div>
    </div>
    <style>
        .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .insumo-card {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .insumo-card:hover {
            transform: scale(1.05);
        }

        .insumo-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }

        .insumo-card h3 {
            margin: 10px 0;
            font-size: 16px;
            font-weight: bold;
        }
    </style>
    <script>carrito.js</script>
</head>
<body>
    <div class="container">
        <div class="selection-container">
            <h2>Carrito de Compras</h2>
                <ul id="carrito-items">
                    <?php if (!empty($_SESSION['carrito'])): ?>
                        <?php foreach ($_SESSION['carrito'] as $insumo => $cantidad): ?>
                            <li>
                                <span><?= htmlspecialchars($insumo) ?> (x<?= $cantidad ?>)</span>
                                <button class="remove-from-cart" data-insumo="<?= htmlspecialchars($insumo) ?>">Eliminar</button>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>El carrito está vacío</li>
                    <?php endif; ?>
                </ul>
            <form method="post">
                <h3>Datos de cirugia</h3>
                <input type="text" name="nombre_paciente" placeholder="Nombre del paciente" required>
                <input type="text" name="cirugia" placeholder="Tipo de cirugía" required>
                <input type="text" name="cod_cirugia" placeholder="Código de cirugía" required>
                <input type="text" name="pabellon" placeholder="Pabellón" required>
                <input type="text" name="cirujano" placeholder="Nombre del cirujano" required>
                <input type="text" name="equipo" placeholder="Equipo médico" required>
                <input type="text" name="responsable" placeholder="Responsable preparacion" required>
                <button type="submit" name="send_email">Finalizar Pedido</button>
            </form>
        </div>
    </div>
</body>
<style>
    .carrito-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .carrito-item {
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid #ddd;
        padding: 5px;
        border-radius: 5px;
    }

    .mini-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
    }
</style>
    <script src="carrito.js"></script>
    <script>
        function toggleAccountInfo() {
            const info = document.getElementById('accountInfo');
            info.style.display = info.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</html>
<?php
exit();
}
?>
<?php
session_start();
include 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Inicializar el carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Agregar insumo al carrito (AJAX)
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $insumo = $_POST['insumo'];
    if (!in_array($insumo, $_SESSION['carrito'])) {
        $_SESSION['carrito'][] = $insumo;
    }
    echo json_encode($_SESSION['carrito']);
    exit;
}

// Quitar insumo del carrito (AJAX)
if (isset($_POST['action']) && $_POST['action'] === 'remove') {
    $insumo = $_POST['insumo'];
    if (($key = array_search($insumo, $_SESSION['carrito'])) !== false) {
        unset($_SESSION['carrito'][$key]);
    }
    $_SESSION['carrito'] = array_values($_SESSION['carrito']);
    echo json_encode($_SESSION['carrito']);
    exit;
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
            <h1>Carrito de Compras</h1>
                <ul id="carrito-items">
                    <?php foreach ($_SESSION['carrito'] as $item): ?>
                        <li>
                            <img src="imagenes/<?= $item ?>.jpg" alt="<?= htmlspecialchars($item) ?>" style="width:50px;height:50px;object-fit:cover;">
                            <?= htmlspecialchars($item) ?>
                            <button class="remove-from-cart" data-insumo="<?= htmlspecialchars($item) ?>">Eliminar</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <form method="post">
                <h3>Datos del Paciente</h3>
                <input type="text" name="nombre_paciente" placeholder="Nombre del paciente" required>
                <input type="text" name="cirugia" placeholder="Tipo de cirugía" required>
                <input type="text" name="cod_cirugia" placeholder="Código de cirugía" required>
                <input type="text" name="pabellon" placeholder="Pabellón" required>
                <input type="text" name="cirujano" placeholder="Nombre del cirujano" required>
                <input type="text" name="equipo" placeholder="Equipo médico" required>
                <button type="submit" name="send_email">Finalizar Compra</button>
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

// Enviar lista por correo
if (isset($_POST['send_email'])) {
    $paciente = $_POST['nombre_paciente'];
    $cirugia = $_POST['cirugia'];
    $cod_cirugia = $_POST['cod_cirugia'];
    $pabellon = $_POST['pabellon'];
    $cirujano = $_POST['cirujano'];
    $equipo = $_POST['equipo'];
    $insumos = implode(', ', $_SESSION['carrito']);

    // Guardar datos de la cirugía
    $stmt = $conn->prepare("INSERT INTO cirugias (nombre_paciente, cirugia, cod_cirugia, pabellon, cirujano, equipo, insumos) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$paciente, $cirugia, $cod_cirugia, $pabellon, $cirujano, $equipo, $insumos]);

    // Obtener todos los correos de usuarios que tengan correo
    $result = $conn->query("SELECT correo FROM usuarios WHERE correo IS NOT NULL AND correo != ''");

    if ($result->num_rows === 0) {
        echo "<script>alert('Error: No hay usuarios con correo registrado.'); window.location.href=window.location.href;</script>";
        exit();
    }

    $correos = [];
    while ($row = $result->fetch_assoc()) {
        $correos[] = $row['correo'];
    }

    // Enviar correo a todos los usuarios con correo
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
        $mail->setFrom('valdiviaalejandro2001@gmail.com', 'Hospital Clínico');

        // Limpiamos destinatarios para evitar que quede alguno
        $mail->clearAddresses();
        $mail->clearBCCs();

        // Agregar solo en BCC los correos encontrados
        foreach ($correos as $correo) {
            $mail->addBCC($correo);
        }

        // Obligatorio agregar un destinatario principal (aunque sea ficticio) para que PHPMailer funcione
        // Puedes usar un correo temporal o un correo que no sea personal. Aquí se usa uno ficticio:
        $mail->addAddress('no-reply@hospital.com');

        $mail->Subject = 'Lista de Insumos para Cirugía';
        $mail->Body = "Paciente: $paciente\nCirugía: $cirugia\nPabellón: $pabellon\nCirujano: $cirujano\nEquipo: $equipo\nInsumos: $insumos";

        $mail->send();

        // Vaciar carrito y alertar
        $_SESSION['carrito'] = [];
        echo "<script>alert('Correo enviado correctamente a todos los usuarios con correo registrado.'); window.location.href=window.location.href;</script>";
        exit();

    } catch (Exception $e) {
        echo "<script>alert('Error al enviar el correo: {$mail->ErrorInfo}'); window.location.href=window.location.href;</script>";
        exit();
    }
}
?>
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

// Agregar insumo al carrito
if (isset($_POST['add_to_cart'])) {
    $insumo = $_POST['insumo'];
    if (!in_array($insumo, $_SESSION['carrito'])) {
        $_SESSION['carrito'][] = $insumo;
    }
}

// Enviar lista por correo usando Composer
if (isset($_POST['send_email'])) {

    $paciente = $_POST['nombre_paciente'];
    $cirugia = $_POST['cirugia'];
    $codigo_cirugia = $_POST['codigo_cirugia'];
    $pabellon = $_POST['pabellon'];
    $cirujano = $_POST['cirujano'];
    $equipo = $_POST['equipo'];
    $insumos = implode(', ', $_SESSION['carrito']);

    // Guardar en la base de datos
    $stmt = $db->prepare("INSERT INTO cirugias (nombre_paciente, cirugia, codigo_cirugia, pabellon, cirujano, equipo, insumos) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$paciente, $cirugia, $codigo_cirugia, $pabellon, $cirujano, $equipo, $insumos]);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'usuario@example.com';
        $mail->Password = 'contraseña';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('usuario@example.com', 'Hospital Clínico');
        $mail->addAddress('destino@example.com');

        $mail->Subject = 'Lista de Insumos para Cirugía';
        $mail->Body = "<p>Paciente: $paciente</p><p>Cirugía: $cirugia</p><p>Pabellón: $pabellon</p><p>Cirujano: $cirujano</p><p>Equipo: $equipo</p><p>Insumos: $insumos</p>";

        $mail->send();
        echo 'Correo enviado correctamente.';
    } catch (Exception $e) {
        echo "Error al enviar el correo: {$mail->ErrorInfo}";
    }
}
?>
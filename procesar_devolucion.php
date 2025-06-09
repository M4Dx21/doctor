<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["devoluciones"], $_POST["id_cirugia"])) {
    $id_cirugia = (int)$_POST["id_cirugia"];
    $devoluciones = $_POST["devoluciones"];
    $fecha = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO historial (id_solicitud, estado, fecha) VALUES (?, 'devuelto', ?)");
    $stmt->bind_param("is", $id_cirugia, $fecha);
    $stmt->execute();
    $stmt->close();

    $nuevo_estado = 'en devolucion';
    $stmt = $conn->prepare("UPDATE cirugias SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $nuevo_estado, $id_cirugia);
    $stmt->execute();
    $stmt->close();

    header("Location: peticiones.php");
    exit();
} else {
    die("Solicitud no válida.");
}
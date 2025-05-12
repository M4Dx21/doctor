<?php
session_start();
include 'db.php';

// Configuración de paginación
$cantidad_por_pagina = isset($_GET['cantidad']) ? (int)$_GET['cantidad'] : 20;
$cantidad_por_pagina = in_array($cantidad_por_pagina, [10, 20, 30, 40, 50]) ? $cantidad_por_pagina : 20;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $cantidad_por_pagina;

// Consulta para contar todos los insumos de 'sutura'
$sql_total = "SELECT COUNT(*) as total FROM componentes WHERE especialidad IN ('sutura','sutura mecanica','clip','malla','hemostatico')";
$total_resultado = mysqli_query($conn, $sql_total);
$total_filas = mysqli_fetch_assoc($total_resultado)['total'];
$total_paginas = ceil($total_filas / $cantidad_por_pagina);

// Consulta con paginación para mostrar todos los insumos
$sql_final = "SELECT * FROM componentes WHERE especialidad IN ('sutura','sutura mecanica','clip','malla','hemostatico') ORDER BY fecha_ingreso DESC LIMIT $cantidad_por_pagina OFFSET $offset";
$resultado = mysqli_query($conn, $sql_final);
$insumos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="asset/styles.css">
    <meta charset="UTF-8">
    <title>Administración de Suturas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            position: relative;
            height: fit-content;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .insumo-card h3 {
            margin: 10px 0;
            font-size: 12px; /* Ajuste del tamaño de fuente */
            font-weight: bold;
            overflow-wrap: break-word; /* Permite que el texto se ajuste */
            word-wrap: break-word;
            white-space: normal; /* Permite múltiples líneas */
            max-width: 100%;
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

        .add-btn {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333; /* Fondo gris muy oscuro */
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 14px; /* Fuente más pequeña */
            cursor: pointer;
            display: none; /* Oculto por defecto */
        }

        .add-btn:hover {
            background-color: #555;
        }

        .insumo-card:hover .add-btn {
            display: inline-block; /* Mostrar cuando el mouse está sobre la card */
        }

        /* Cambios para el carrito */
        .cart-list {
            display: none;
            position: fixed;
            top: 80px; /* Mover hacia abajo un poco */
            right: 5px;
            width: 300px; /* Ancho fijo para que no ocupe toda la pantalla */
            background-color: #fff; /* Fondo blanco */
            color: #333; /* Letras negras */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            max-height: 90%; /* Limitar la altura */
            overflow-y: auto;
        }

        /* Estilización de los ítems dentro del carrito */
        .cart-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .cart-list li {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f8f8; /* Fondo gris claro para cada item */
            border: 1px solid #ddd; /* Bordes suaves */
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-list li span {
            font-weight: bold;
            color: #333;
        }

        .cart-list li .insumo-count {
            background-color: #28a745; /* Color verde para los números */
            color: white;
            padding: 2px 8px;
            border-radius: 50%;
            font-size: 14px;
        }

        /* Fondo oscuro (overlay) */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6); /* Color oscuro semitransparente */
            z-index: 9998; /* Debajo del carrito */
        }
        /* Estilización del botón del carrito */
        .toggle-cart-btn {
            position: fixed;
            top: 26px; /* Colocar cerca del borde superior */
            right: 330px; /* Separar un poco del borde */
            background-color: #333;
            max-width: 100px;
            color: #fff;
            border: none;
            padding: 6px 12px; /* Tamaño más pequeño */
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            z-index: 9999;
            display: none; /* Oculto por defecto */
        }

        .toggle-cart-btn:hover {
            background-color: #555;
        }

        .toggle-cart-btn.show {
            display: inline-block; /* Mostrar si hay elementos en el carrito */
        }

        .pagination-container {
            text-align: center;
            margin-top: 20px;
        }

        .pagination-container a {
            padding: 5px 10px;
            margin: 0 5px;
            border: 1px solid #ddd;
            text-decoration: none;
        }

        .pagination-container .active {
            background-color: #28a745;
            color: #fff;
        }
    </style>
</head>
<body>
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

    <div class="container">
        <?php if (count($insumos) > 0): ?>
            <?php foreach ($insumos as $componente): 
                $codigo = $componente['codigo'];
                $imagen = "imagenes/$codigo.jpg";
                $existe_imagen = file_exists($imagen);
            ?>
                <div class="insumo-card">
                    <?php if ($existe_imagen): ?>
                        <img src="<?= $imagen ?>" alt="Imagen de <?= htmlspecialchars($componente['insumo']) ?>">
                    <?php else: ?>
                        <img src="asset/no_image_available.png" alt="Sin imagen disponible">
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($componente['insumo']) ?></h3>
                    <button class="add-btn" data-insumo="<?= $componente['insumo'] ?>">+</button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se encontraron insumos de sutura.</p>
        <?php endif; ?>
    </div>

    <!-- Lista del carrito -->
    <div class="cart-list" id="cartList">
        <h4>Insumos Agregados</h4>
        <ul id="cartItems">
            <!-- Aquí se agregan los insumos seleccionados -->
        </ul>
    </div>

    <!-- Fondo oscuro cuando el carrito está abierto -->
    <div class="overlay" id="overlay"></div>

    <!-- Botón para abrir/cerrar el carrito -->
    <button class="toggle-cart-btn" id="toggleCartBtn">🛒</button>

    <!-- Paginación -->
    <div class="pagination-container">
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?pagina=<?= $i ?>&cantidad=<?= $cantidad_por_pagina ?>" class="<?= $pagina_actual == $i ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>

    <script>
        $(document).ready(function() {
            // Mostrar carrito al hacer clic en el botón de abrir
            $("#toggleCartBtn").click(function() {
                $("#cartList").toggle();
                $("#overlay").toggle();
            });

            // Cerrar carrito si se hace clic en el fondo oscuro
            $("#overlay").click(function() {
                $("#cartList").hide();
                $(this).hide();
            });

            // Añadir insumo al carrito
            // Modificación de la función para agregar un contador al insumo
            // Modificación de la función para agregar un contador al insumo
            $(".add-btn").click(function() {
                const insumo = $(this).data("insumo");

                // Verificar si el insumo ya existe en la lista
                let existingItem = $("#cartItems li").filter(function() {
                    return $(this).text().includes(insumo);
                });

                if (existingItem.length > 0) {
                    // Si ya existe, incrementar el contador
                    let countSpan = existingItem.find(".insumo-count");
                    countSpan.text(parseInt(countSpan.text()) + 1); // Aumenta el contador
                } else {
                    // Si no existe, agregar un nuevo item con el contador inicial
                    $("#cartItems").append(
                        "<li><span>" + insumo + "</span><span class='insumo-count'>1</span></li>"
                    );
                }

                // Mostrar el botón del carrito
                $("#toggleCartBtn").addClass("show");
            });
        });
    </script>
</body>
</html>

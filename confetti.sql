-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-11-2026 a las 05:14:14
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `confetti`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `color_acento` varchar(7) DEFAULT NULL,
  `orden` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `color_acento`, `orden`, `activo`) VALUES
(1, 'Copetín', '#E8483C', 1, 1),
(2, 'Harinas', '#f5941f', 2, 1),
(3, 'Repostería', '#1E9C7C', 3, 1),
(4, 'Fiambrería', '#6E4E9E', 4, 1),
(5, 'Bebidas', '#1B4A9C', 5, 1),
(6, 'Enlatados', '#ff8000', 0, 1),
(7, 'Escencias', '#804040', 0, 1),
(8, 'Velas', '#ff80c0', 0, 1),
(9, 'Globos', '#0000ff', 0, 1),
(10, 'Chocolates', '#000000', 0, 1),
(11, 'Cremas', '#ffffff', 0, 1),
(12, 'Banderines', '#ffff80', 0, 1),
(13, 'Bandejas Aluminio', '#c0c0c0', 0, 1),
(14, 'Bandejas Tortas', '#800000', 0, 1),
(15, 'Papel', '#808080', 0, 1),
(16, 'Frutos Secos', '#ff8040', 0, 1),
(17, 'Azucar', '#ffffc8', 0, 1),
(18, 'Condimentos', '#408080', 0, 1),
(19, 'Manteca', '#ffc6aa', 0, 1),
(20, 'Utencillos', '#5959ff', 0, 1),
(21, 'Descartables', '#c0c0c0', 0, 1),
(22, 'Comestibles', '#ff8040', 0, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `combos`
--

CREATE TABLE `combos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `combos`
--

INSERT INTO `combos` (`id`, `nombre`, `descripcion`, `precio`, `imagen`, `activo`, `creado_en`) VALUES
(1, 'Picada Confetti para 2', 'Fiambres, quesos, pan y algo dulce para cerrar', 6040.00, NULL, 1, '2026-09-17 23:01:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `combo_productos`
--

CREATE TABLE `combo_productos` (
  `id` int(10) UNSIGNED NOT NULL,
  `combo_id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `cantidad` decimal(10,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `combo_productos`
--

INSERT INTO `combo_productos` (`id`, `combo_id`, `producto_id`, `cantidad`) VALUES
(1, 1, 7, 0.200),
(2, 1, 6, 0.200),
(3, 1, 3, 1.000),
(4, 1, 5, 2.000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `clave` varchar(40) NOT NULL,
  `valor` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`clave`, `valor`) VALUES
('transf_alias', 'lbenvenutti.ppay'),
('transf_banco', 'Personal Pay'),
('transf_cbu', '0000076500000002178747'),
('transf_nota', 'Podes cargar el comprobante ahora o mostrarlo cuando retires tu pedido'),
('transf_titular', 'Benvenutti Luciano');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas_corrientes`
--

CREATE TABLE `cuentas_corrientes` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cuentas_corrientes`
--

INSERT INTO `cuentas_corrientes` (`id`, `nombre`, `telefono`, `notas`, `creado_en`) VALUES
(1, 'lu', NULL, NULL, '2026-10-22 21:49:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuenta_movimientos`
--

CREATE TABLE `cuenta_movimientos` (
  `id` int(10) UNSIGNED NOT NULL,
  `cuenta_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('cargo','pago') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `pedido_id` int(10) UNSIGNED DEFAULT NULL,
  `nota` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cuenta_movimientos`
--

INSERT INTO `cuenta_movimientos` (`id`, `cuenta_id`, `tipo`, `monto`, `pedido_id`, `nota`, `creado_en`) VALUES
(1, 1, 'cargo', 1300.00, 16, 'Venta CF-76F7', '2026-10-22 21:49:38'),
(2, 1, 'pago', 300.00, NULL, NULL, '2026-10-22 21:53:11'),
(3, 1, 'cargo', 900.00, 17, 'Venta CF-77CD', '2026-10-22 21:53:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios_atencion`
--

CREATE TABLE `horarios_atencion` (
  `dia` tinyint(3) UNSIGNED NOT NULL,
  `abre` time DEFAULT NULL,
  `cierra` time DEFAULT NULL,
  `abre2` time DEFAULT NULL,
  `cierra2` time DEFAULT NULL,
  `cerrado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `horarios_atencion`
--

INSERT INTO `horarios_atencion` (`dia`, `abre`, `cierra`, `abre2`, `cierra2`, `cerrado`) VALUES
(0, '09:00:00', '12:00:00', NULL, NULL, 0),
(1, '09:00:00', '21:00:00', NULL, NULL, 0),
(2, '09:00:00', '21:00:00', NULL, NULL, 0),
(3, '09:00:00', '12:00:00', '17:00:00', '22:00:00', 0),
(4, '09:00:00', '21:00:00', NULL, NULL, 0),
(5, '09:00:00', '21:00:00', NULL, NULL, 0),
(6, NULL, NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login_intentos`
--

CREATE TABLE `login_intentos` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario` varchar(60) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `login_intentos`
--

INSERT INTO `login_intentos` (`id`, `usuario`, `ip`, `creado_en`) VALUES
(1, 'admin', '127.0.0.1', '2026-11-12 22:31:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_stock`
--

CREATE TABLE `movimientos_stock` (
  `id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('reserva','liberacion_vencimiento','liberacion_cancelacion','venta_mostrador','ajuste_manual','entrada_stock') NOT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  `pedido_id` int(10) UNSIGNED DEFAULT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `movimientos_stock`
--

INSERT INTO `movimientos_stock` (`id`, `producto_id`, `tipo`, `cantidad`, `pedido_id`, `usuario_id`, `creado_en`) VALUES
(1, 1, 'reserva', -1.000, 5, NULL, '2026-09-22 22:35:14'),
(2, 1, 'reserva', -1.000, 6, NULL, '2026-09-22 23:09:58'),
(3, 2, 'venta_mostrador', -1.000, 7, NULL, '2026-09-22 23:45:17'),
(4, 1, 'reserva', -1.000, 8, NULL, '2026-09-23 01:15:36'),
(5, 3, 'reserva', -1.000, 8, NULL, '2026-09-23 01:15:36'),
(6, 6, 'reserva', -0.500, 8, NULL, '2026-09-23 01:15:36'),
(7, 2, 'venta_mostrador', -1.000, 9, NULL, '2026-09-23 01:22:19'),
(8, 3, 'venta_mostrador', -1.000, 9, NULL, '2026-09-23 01:22:19'),
(9, 1, 'reserva', -12.000, 12, NULL, '2026-09-23 01:23:34'),
(10, 2, 'reserva', -1.000, 13, NULL, '2026-10-01 22:51:12'),
(11, 3, 'reserva', -1.000, 13, NULL, '2026-10-01 22:51:12'),
(12, 2, 'reserva', -1.000, 14, NULL, '2026-10-01 22:58:14'),
(13, 3, 'reserva', -1.000, 14, NULL, '2026-10-01 22:58:14'),
(14, 5, 'reserva', -1.000, 14, NULL, '2026-10-01 22:58:14'),
(15, 2, 'liberacion_cancelacion', 1.000, 14, NULL, '2026-10-01 23:26:45'),
(16, 3, 'liberacion_cancelacion', 1.000, 14, NULL, '2026-10-01 23:26:45'),
(17, 5, 'liberacion_cancelacion', 1.000, 14, NULL, '2026-10-01 23:26:45'),
(18, 2, 'reserva', -6.000, 15, NULL, '2026-10-01 23:27:13'),
(19, 1, 'liberacion_vencimiento', 1.000, 5, NULL, '2026-10-20 22:40:33'),
(20, 1, 'liberacion_vencimiento', 12.000, 12, NULL, '2026-10-20 22:40:33'),
(21, 2, 'liberacion_vencimiento', 6.000, 15, NULL, '2026-10-20 22:40:33'),
(22, 2, 'venta_mostrador', -2.000, 16, NULL, '2026-10-22 21:49:38'),
(23, 3, 'venta_mostrador', -1.000, 17, NULL, '2026-10-22 21:53:27'),
(24, 3, 'reserva', -1.000, 18, NULL, '2026-10-22 22:01:33'),
(25, 2, 'reserva', -1.000, 19, NULL, '2026-10-22 22:24:00'),
(26, 3, 'reserva', -1.000, 20, NULL, '2026-10-22 22:36:07'),
(27, 5, 'reserva', -1.000, 21, NULL, '2026-10-22 22:40:53'),
(28, 2, 'reserva', -1.000, 22, NULL, '2026-10-29 03:06:44'),
(29, 3, 'reserva', -1.000, 23, NULL, '2026-10-29 03:07:14'),
(30, 2, 'liberacion_cancelacion', 1.000, 22, NULL, '2026-10-29 03:07:29'),
(31, 3, 'liberacion_cancelacion', 1.000, 23, NULL, '2026-10-29 03:07:31'),
(32, 1, 'entrada_stock', 2.000, NULL, NULL, '2026-10-29 03:08:14'),
(33, 2, 'entrada_stock', 3.000, NULL, NULL, '2026-10-29 03:08:27'),
(34, 2, 'reserva', -2.000, 24, NULL, '2026-11-12 23:12:42'),
(35, 2, 'reserva', -1.000, 25, NULL, '2026-11-12 23:50:20'),
(36, 7, 'reserva', -0.250, 25, NULL, '2026-11-12 23:50:20'),
(37, 2, 'reserva', -1.000, 26, NULL, '2026-11-12 23:52:45'),
(38, 5, 'reserva', -1.000, 26, NULL, '2026-11-12 23:52:45'),
(39, 2, 'liberacion_vencimiento', 2.000, 24, NULL, '2026-11-23 22:36:35'),
(40, 2, 'liberacion_vencimiento', 1.000, 26, NULL, '2026-11-23 22:36:35'),
(41, 5, 'liberacion_vencimiento', 1.000, 26, NULL, '2026-11-23 22:36:35'),
(42, 1, 'entrada_stock', 2.000, NULL, 1, '2026-11-24 00:05:21'),
(43, 36, 'reserva', -0.250, 27, NULL, '2026-11-24 00:43:44'),
(44, 14, 'reserva', -2.000, 28, NULL, '2026-11-24 03:32:21'),
(45, 8, 'reserva', -1.000, 29, NULL, '2026-11-24 03:35:54'),
(46, 8, 'reserva', -1.000, 30, NULL, '2026-11-24 03:37:34'),
(47, 8, 'liberacion_cancelacion', 1.000, 29, NULL, '2026-11-24 03:59:21'),
(48, 8, 'reserva', -1.000, 31, NULL, '2026-11-24 04:00:38'),
(49, 8, 'liberacion_cancelacion', 1.000, 30, NULL, '2026-11-24 04:03:01'),
(50, 8, 'liberacion_cancelacion', 1.000, 31, NULL, '2026-11-24 04:03:04'),
(51, 8, 'reserva', -1.000, 32, NULL, '2026-11-24 05:28:41'),
(52, 8, 'liberacion_cancelacion', 1.000, 32, NULL, '2026-11-24 05:29:17'),
(53, 8, 'reserva', -1.000, 33, NULL, '2026-11-24 05:29:38'),
(54, 6, 'reserva', -0.500, 34, NULL, '2026-11-24 05:30:23'),
(55, 8, 'liberacion_cancelacion', 1.000, 33, NULL, '2026-11-24 05:30:40'),
(56, 6, 'liberacion_cancelacion', 0.500, 34, NULL, '2026-11-24 05:30:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(12) NOT NULL,
  `nombre_cliente` varchar(120) NOT NULL,
  `telefono_cliente` varchar(30) NOT NULL,
  `origen` enum('web','mostrador') NOT NULL DEFAULT 'web',
  `estado` enum('pendiente','en_preparacion','listo','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notas` varchar(255) DEFAULT NULL,
  `metodo_pago` enum('local','transferencia') NOT NULL DEFAULT 'local',
  `comprobante` varchar(80) DEFAULT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `vence_en` timestamp NULL DEFAULT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `codigo`, `nombre_cliente`, `telefono_cliente`, `origen`, `estado`, `total`, `notas`, `metodo_pago`, `comprobante`, `usuario_id`, `creado_en`, `vence_en`, `actualizado_en`) VALUES
(1, 'CF-0001', 'Martina Gómez', '3482-555123', 'web', 'cancelado', 5700.00, NULL, 'local', NULL, NULL, '2026-09-17 23:48:34', '2026-09-18 23:48:34', '2026-10-20 22:40:33'),
(2, 'CF-0002', 'Rodrigo Paz', '3482-555456', 'web', 'cancelado', 6040.00, NULL, 'local', NULL, NULL, '2026-09-17 23:18:34', '2026-09-18 23:18:34', '2026-10-20 22:40:33'),
(3, 'CF-0003', 'Cliente mostrador', '-', 'mostrador', 'entregado', 6600.00, NULL, 'local', NULL, NULL, '2026-09-17 23:33:34', NULL, '2026-10-20 22:41:49'),
(4, 'CF-0004', 'Julieta Díaz', '3482-555789', 'web', 'entregado', 5000.00, NULL, 'local', NULL, NULL, '2026-09-17 21:58:34', '2026-09-18 21:58:34', '2026-09-17 23:58:34'),
(5, 'CF-8E60', 'luci', '3482', 'web', 'cancelado', 4200.00, NULL, 'local', NULL, NULL, '2026-09-22 22:35:14', '2026-09-23 22:35:14', '2026-10-20 22:40:33'),
(6, 'CF-C0AB', 'lu', '33333', 'web', 'entregado', 4200.00, NULL, 'local', NULL, NULL, '2026-09-22 23:09:58', '2026-09-24 04:09:58', '2026-09-22 23:38:50'),
(7, 'CF-636E', 'Venta mostrador', '-', 'mostrador', 'entregado', 650.00, NULL, 'local', NULL, NULL, '2026-09-22 23:45:17', NULL, '2026-09-22 23:45:17'),
(8, 'CF-EAA2', 'geor', '1111111', 'web', 'entregado', 6900.00, NULL, 'local', NULL, NULL, '2026-09-23 01:15:36', '2026-09-24 06:15:36', '2026-09-23 01:18:24'),
(9, 'CF-B52D', 'Venta mostrador', '-', 'mostrador', 'entregado', 1550.00, NULL, 'local', NULL, NULL, '2026-09-23 01:22:19', NULL, '2026-09-23 01:22:19'),
(12, 'CF-3B13', 'asas', '2131331', 'web', 'cancelado', 50400.00, NULL, 'local', NULL, NULL, '2026-09-23 01:23:34', '2026-09-24 01:23:34', '2026-10-20 22:40:33'),
(13, 'CF-2623', 'luci', '3482312178', 'web', 'entregado', 1550.00, NULL, 'local', NULL, NULL, '2026-10-01 22:51:12', '2026-10-03 03:51:12', '2026-10-01 23:26:39'),
(14, 'CF-DA6C', 'luuu', '34823121', 'web', 'cancelado', 2350.00, NULL, 'local', NULL, NULL, '2026-10-01 22:58:14', '2026-10-03 03:58:14', '2026-10-01 23:26:45'),
(15, 'CF-07FD', 'benve', '3482312178', 'web', 'cancelado', 3900.00, NULL, 'local', NULL, NULL, '2026-10-01 23:27:13', '2026-10-02 23:27:13', '2026-10-20 22:40:33'),
(16, 'CF-76F7', 'lu', '-', 'mostrador', 'entregado', 1300.00, 'Cuenta corriente: lu', 'local', NULL, NULL, '2026-10-22 21:49:38', NULL, '2026-10-22 21:49:38'),
(17, 'CF-77CD', 'lu', '-', 'mostrador', 'entregado', 900.00, 'Cuenta corriente: lu', 'local', NULL, NULL, '2026-10-22 21:53:27', NULL, '2026-10-22 21:53:27'),
(18, 'CF-B1E4', 'lucio', '3482312178', 'web', 'entregado', 900.00, NULL, 'local', NULL, NULL, '2026-10-22 22:01:33', '2026-10-23 22:01:33', '2026-10-22 22:25:12'),
(19, 'CF-9DC9', 'luuuu', '3482312178', 'web', 'entregado', 650.00, NULL, 'local', NULL, NULL, '2026-10-22 22:24:00', '2026-10-23 22:24:00', '2026-10-22 22:35:46'),
(20, 'CF-424E', 'lucio', '3482312178', 'web', 'entregado', 900.00, NULL, 'local', NULL, NULL, '2026-10-22 22:36:07', '2026-10-23 22:36:07', '2026-10-22 22:39:10'),
(21, 'CF-11B5', 'lucianito', '3482312178', 'web', 'entregado', 800.00, NULL, 'local', NULL, NULL, '2026-10-22 22:40:53', '2026-10-23 22:40:53', '2026-10-22 22:42:41'),
(22, 'CF-75D6', 'luuu', '3232', 'web', 'cancelado', 650.00, NULL, 'local', NULL, NULL, '2026-10-29 03:06:44', '2026-10-30 03:06:44', '2026-10-29 03:07:29'),
(23, 'CF-5136', 'ddsds', '234354', 'web', 'cancelado', 900.00, NULL, 'local', NULL, NULL, '2026-10-29 03:07:14', '2026-10-30 03:07:14', '2026-10-29 03:07:31'),
(24, 'CF-59E0', 'luc', '34823121', 'web', 'cancelado', 1300.00, NULL, 'local', NULL, NULL, '2026-11-12 23:12:42', '2026-11-13 23:12:42', '2026-11-23 22:36:35'),
(25, 'CF-6826', 'sam', '2332434324', 'web', 'entregado', 3275.00, NULL, 'local', NULL, NULL, '2026-11-12 23:50:20', '2026-11-13 23:50:20', '2026-11-12 23:51:45'),
(26, 'CF-F428', 'geor', '2133223', 'web', 'cancelado', 1450.00, NULL, 'local', NULL, NULL, '2026-11-12 23:52:45', '2026-11-13 23:52:45', '2026-11-23 22:36:35'),
(27, 'CF-59A2', 'luli', '3482312178', 'web', 'entregado', 1250.00, 'globo color azul', 'local', NULL, NULL, '2026-11-24 00:43:44', '2026-11-25 00:43:44', '2026-11-24 00:46:41'),
(28, 'CF-PSS3B3X2', 'luuuuuu', '3482312178', 'web', 'entregado', 1000.00, 'dddda', 'local', NULL, NULL, '2026-11-24 03:32:21', '2026-11-25 03:32:21', '2026-11-24 04:03:12'),
(29, 'CF-QTALB6NY', 'luci', '3482312178', 'web', 'cancelado', 1200.00, 'coca', 'local', NULL, NULL, '2026-11-24 03:35:54', '2026-11-25 03:35:54', '2026-11-24 03:59:21'),
(30, 'CF-6BPMBPZ5', 'luuuucio', '3482312178', 'web', 'cancelado', 1200.00, 'sprite', 'transferencia', 'c_12967dbdbedfc25021fc11ca.pdf', NULL, '2026-11-24 03:37:34', '2026-11-25 03:37:34', '2026-11-24 04:03:01'),
(31, 'CF-3SHX5KMH', 'luchi', '3482312178', 'web', 'cancelado', 1200.00, 'fanta', 'transferencia', 'c_94a9772775425b1a68609f6a.pdf', NULL, '2026-11-24 04:00:38', '2026-11-25 04:00:38', '2026-11-24 04:03:04'),
(32, 'CF-LLGDFJD6', 'lucio', '3482312178', 'web', 'cancelado', 1200.00, 'tomar', 'local', NULL, NULL, '2026-11-24 05:28:41', '2026-11-25 05:28:41', '2026-11-24 05:29:17'),
(33, 'CF-ZWEHV9M8', 'cwfewfwe', '3482312178', 'web', 'cancelado', 1200.00, 'cadsccdsa', 'local', NULL, NULL, '2026-11-24 05:29:38', '2026-11-25 05:29:38', '2026-11-24 05:30:40'),
(34, 'CF-FPFCRKUR', 'cwcfvw', '2332323232', 'web', 'cancelado', 1800.00, 'aadca', 'local', NULL, NULL, '2026-11-24 05:30:23', '2026-11-25 05:30:23', '2026-11-24 05:30:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_items`
--

CREATE TABLE `pedido_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `pedido_id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED DEFAULT NULL,
  `combo_id` int(10) UNSIGNED DEFAULT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ;

--
-- Volcado de datos para la tabla `pedido_items`
--

INSERT INTO `pedido_items` (`id`, `pedido_id`, `producto_id`, `combo_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 2, NULL, 6.000, 650.00, 3900.00),
(2, 1, 4, NULL, 1.000, 1800.00, 1800.00),
(3, 2, NULL, 1, 1.000, 6040.00, 6040.00),
(4, 3, 1, NULL, 1.000, 4200.00, 4200.00),
(5, 3, 8, NULL, 2.000, 1200.00, 2400.00),
(6, 4, 5, NULL, 4.000, 800.00, 3200.00),
(7, 4, 3, NULL, 2.000, 900.00, 1800.00),
(8, 5, 1, NULL, 1.000, 4200.00, 4200.00),
(9, 6, 1, NULL, 1.000, 4200.00, 4200.00),
(10, 7, 2, NULL, 1.000, 650.00, 650.00),
(11, 8, 1, NULL, 1.000, 4200.00, 4200.00),
(12, 8, 3, NULL, 1.000, 900.00, 900.00),
(13, 8, 6, NULL, 0.500, 3600.00, 1800.00),
(14, 9, 2, NULL, 1.000, 650.00, 650.00),
(15, 9, 3, NULL, 1.000, 900.00, 900.00),
(16, 12, 1, NULL, 12.000, 4200.00, 50400.00),
(17, 13, 2, NULL, 1.000, 650.00, 650.00),
(18, 13, 3, NULL, 1.000, 900.00, 900.00),
(19, 14, 2, NULL, 1.000, 650.00, 650.00),
(20, 14, 3, NULL, 1.000, 900.00, 900.00),
(21, 14, 5, NULL, 1.000, 800.00, 800.00),
(22, 15, 2, NULL, 6.000, 650.00, 3900.00),
(23, 16, 2, NULL, 2.000, 650.00, 1300.00),
(24, 17, 3, NULL, 1.000, 900.00, 900.00),
(25, 18, 3, NULL, 1.000, 900.00, 900.00),
(26, 19, 2, NULL, 1.000, 650.00, 650.00),
(27, 20, 3, NULL, 1.000, 900.00, 900.00),
(28, 21, 5, NULL, 1.000, 800.00, 800.00),
(29, 22, 2, NULL, 1.000, 650.00, 650.00),
(30, 23, 3, NULL, 1.000, 900.00, 900.00),
(31, 24, 2, NULL, 2.000, 650.00, 1300.00),
(32, 25, 2, NULL, 1.000, 650.00, 650.00),
(33, 25, 7, NULL, 0.250, 10500.00, 2625.00),
(34, 26, 2, NULL, 1.000, 650.00, 650.00),
(35, 26, 5, NULL, 1.000, 800.00, 800.00),
(36, 27, 36, NULL, 0.250, 5000.00, 1250.00),
(37, 28, 14, NULL, 2.000, 500.00, 1000.00),
(38, 29, 8, NULL, 1.000, 1200.00, 1200.00),
(39, 30, 8, NULL, 1.000, 1200.00, 1200.00),
(40, 31, 8, NULL, 1.000, 1200.00, 1200.00),
(41, 32, 8, NULL, 1.000, 1200.00, 1200.00),
(42, 33, 8, NULL, 1.000, 1200.00, 1200.00),
(43, 34, 6, NULL, 0.500, 3600.00, 1800.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(10) UNSIGNED NOT NULL,
  `categoria_id` int(10) UNSIGNED NOT NULL,
  `proveedor_id` int(10) UNSIGNED DEFAULT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `tipo_venta` enum('unidad','peso') NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `precio_compra` decimal(10,2) DEFAULT NULL,
  `stock_actual` decimal(10,3) NOT NULL DEFAULT 0.000,
  `stock_minimo` decimal(10,3) NOT NULL DEFAULT 0.000,
  `imagen` varchar(255) DEFAULT NULL,
  `codigo_barras` varchar(64) DEFAULT NULL,
  `sin_tacc` tinyint(1) NOT NULL DEFAULT 0,
  `destacado` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `categoria_id`, `proveedor_id`, `nombre`, `descripcion`, `tipo_venta`, `precio`, `precio_compra`, `stock_actual`, `stock_minimo`, `imagen`, `codigo_barras`, `sin_tacc`, `destacado`, `activo`, `creado_en`) VALUES
(1, 1, 1, 'Empanaditas de jamón y queso', 'Docena, recién horneadas', 'unidad', 4200.00, NULL, 22.000, 14.000, 'empanadas.jpg', NULL, 0, 1, 1, '2026-09-17 23:01:22'),
(2, 2, 1, 'Facturas surtidas', 'Por unidad, mínimo 6', 'unidad', 650.00, NULL, 34.000, 24.000, 'producto_2.jpg', NULL, 0, 1, 1, '2026-09-17 23:01:22'),
(3, 2, NULL, 'Grisines caseros', 'Paquete casero', 'unidad', 900.00, NULL, 34.000, 8.000, NULL, NULL, 1, 0, 1, '2026-09-17 23:01:22'),
(4, 3, NULL, 'Torta individual de dulce de leche', 'Porción generosa', 'unidad', 1800.00, NULL, 20.000, 4.000, NULL, NULL, 0, 1, 1, '2026-09-17 23:01:22'),
(5, 3, NULL, 'Alfajores de maicena', 'Rellenos de dulce de leche', 'unidad', 800.00, NULL, 59.000, 12.000, NULL, NULL, 0, 0, 1, '2026-09-17 23:01:22'),
(6, 4, NULL, 'Queso cremoso', 'Se pide por gramos', 'peso', 3600.00, NULL, 8.000, 1.000, NULL, NULL, 0, 1, 1, '2026-09-17 23:01:22'),
(7, 4, NULL, 'Jamón crudo', 'Se pide por gramos', 'peso', 10500.00, NULL, 3.950, 1.000, NULL, NULL, 0, 0, 1, '2026-09-17 23:01:22'),
(8, 5, NULL, 'Gaseosa 500ml', 'Línea cola y lima-limón', 'unidad', 1200.00, NULL, 30.000, 6.000, NULL, NULL, 0, 0, 1, '2026-09-17 23:01:22'),
(9, 2, 1, 'levadura seca', 'levex x2 sobres', 'unidad', 1300.00, NULL, 25.000, 5.000, NULL, '7798018850184', 0, 0, 1, '2026-11-23 22:52:22'),
(10, 6, 1, 'Durazno en mitades', 'Abeto', 'unidad', 3800.00, NULL, 6.000, 2.000, NULL, '7790696000301', 1, 0, 1, '2026-11-23 23:00:09'),
(11, 7, 1, 'Escencia de Vainilla', 'Ital 100cc', 'unidad', 1100.00, NULL, 10.000, 5.000, NULL, '7795194100140', 0, 0, 1, '2026-11-23 23:10:30'),
(12, 20, NULL, 'Rallador de mano', 'metal', 'unidad', 3200.00, NULL, 2.000, 1.000, NULL, '102517711612', 0, 0, 1, '2026-11-23 23:20:51'),
(13, 20, NULL, 'Cortante Corazones', 'metal', 'unidad', 2300.00, NULL, 1.000, 2.000, NULL, '7790000263040', 0, 0, 1, '2026-11-23 23:21:48'),
(14, 20, NULL, 'Cornet', 'plastico', 'unidad', 500.00, NULL, 2.000, 2.000, NULL, '7794297040278', 0, 0, 1, '2026-11-23 23:22:50'),
(15, 12, 2, 'Cortina Multicolor', 'multicolor', 'unidad', 2000.00, NULL, 3.000, 2.000, NULL, '248385989396', 0, 0, 1, '2026-11-23 23:29:23'),
(16, 12, 2, 'Cortina Metalizada', '', 'unidad', 1500.00, NULL, 10.000, 5.000, NULL, '265529633570', 0, 0, 1, '2026-11-23 23:31:05'),
(17, 8, 2, 'Velas', 'Brillos x6 (multicolor, rosa, azul)', 'unidad', 1300.00, NULL, 6.000, 3.000, NULL, '232210871708', 0, 0, 1, '2026-11-23 23:34:03'),
(18, 12, 2, 'Banderin \"Feliz cumpleaños\"', 'Negro, rosa, verde, azul', 'unidad', 1500.00, NULL, 10.000, 5.000, NULL, '245043032135', 0, 0, 1, '2026-11-23 23:38:15'),
(19, 12, 2, 'Banderin Navidad', 'Feliz navidad', 'unidad', 2200.00, NULL, 3.000, 3.000, NULL, '283034897093', 0, 0, 1, '2026-11-23 23:39:34'),
(20, 8, 2, 'Vela metalizada', 'x1 (azul, rojo, plateado, dorado)', 'unidad', 600.00, NULL, 18.000, 10.000, NULL, '211361564690', 0, 0, 1, '2026-11-23 23:42:46'),
(21, 8, 2, 'Vela fina pastel', 'x8 (rosa, lila, celeste, amarillo)', 'unidad', 1200.00, NULL, 6.000, 3.000, NULL, '299200656622', 0, 0, 1, '2026-11-23 23:47:52'),
(22, 8, 2, 'Vela mini', 'Con portavela (plateado, dorado, azul)', 'unidad', 1300.00, NULL, 6.000, 3.000, NULL, '282612719398', 0, 0, 1, '2026-11-23 23:49:52'),
(23, 8, 2, 'Vela con ondas', 'gibre x12 (rojo, verde, amarillo)', 'unidad', 2000.00, NULL, 2.000, 2.000, NULL, '226020110085', 0, 0, 1, '2026-11-23 23:52:36'),
(24, 9, 2, 'Globo \"Feliz Cumpleaños\"', '18\"', 'unidad', 500.00, NULL, 5.000, 3.000, NULL, '252316972614', 0, 0, 1, '2026-11-23 23:54:45'),
(25, 9, 2, 'Globo Margarita', '18\"', 'unidad', 500.00, NULL, 5.000, 3.000, NULL, '202866955037', 0, 0, 1, '2026-11-23 23:55:20'),
(26, 9, 2, 'Globo River/Boca', '18\"', 'unidad', 500.00, NULL, 10.000, 5.000, NULL, '281530340909', 0, 0, 1, '2026-11-23 23:56:04'),
(27, 9, 2, 'Globo Estrella/Corazon', '18\"', 'unidad', 500.00, NULL, 10.000, 5.000, NULL, '237674609573', 0, 0, 1, '2026-11-23 23:56:53'),
(28, 9, 2, 'Globo Arbol de Navidad', 'Figura', 'unidad', 2500.00, NULL, 2.000, 2.000, NULL, '253986660203', 0, 0, 1, '2026-11-23 23:57:37'),
(29, 9, 2, 'Globo Obrs Navidad', 'Navidad', 'unidad', 1000.00, NULL, 3.000, 2.000, NULL, '259465945544', 0, 0, 1, '2026-11-23 23:58:59'),
(30, 9, 2, 'Globo Papa noel \"ho ho\"', 'Figura', 'unidad', 800.00, NULL, 3.000, 2.000, NULL, '273351553408', 0, 0, 1, '2026-11-24 00:00:14'),
(31, 16, 3, 'Pasas de uva', 'Se pide por gramos', 'peso', 6500.00, NULL, 10.000, 5.000, NULL, '291696173015', 0, 0, 1, '2026-11-24 00:15:43'),
(32, 16, 3, 'Fruta escurrida', 'Se pide por gramos', 'peso', 5000.00, NULL, 10.000, 5.000, NULL, '207430755385', 0, 0, 1, '2026-11-24 00:17:03'),
(33, 7, 3, 'Escencia pan dulce', 'x100cc', 'unidad', 800.00, NULL, 10.000, 5.000, NULL, '255032757175', 0, 0, 1, '2026-11-24 00:20:09'),
(34, 7, 3, 'Escencia de Vainilla', 'x100cc', 'unidad', 200.00, NULL, 50.000, 2.000, NULL, '287928256262', 0, 0, 1, '2026-11-24 00:23:01'),
(35, 7, 3, 'Escencia de manteca', 'x100cc', 'unidad', 460.00, NULL, 20.000, 10.000, NULL, '276510708163', 0, 0, 1, '2026-11-24 00:23:49'),
(36, 3, 3, 'Dulce de leche', 'Pastelero', 'peso', 5000.00, NULL, 9.750, 5.000, NULL, '236468707561', 0, 0, 1, '2026-11-24 00:26:32'),
(37, 3, 3, 'Dulce de leche', 'Repostero', 'peso', 4300.00, NULL, 10.000, 5.000, NULL, '264125411841', 0, 0, 1, '2026-11-24 00:27:04'),
(38, 2, 3, 'Harina 000', 'x1kg', 'peso', 850.00, NULL, 25.000, 10.000, NULL, '254605394711', 0, 0, 1, '2026-11-24 02:03:43'),
(39, 2, 3, 'Harina 0000', 'Suelta x1kg', 'peso', 1200.00, NULL, 25.000, 10.000, NULL, '210355926415', 0, 0, 1, '2026-11-24 02:05:35'),
(40, 9, 4, 'Globo piñata grande', 'rayado', 'unidad', 2200.00, 1944.00, 2.000, 2.000, NULL, '7790391339119', 0, 0, 1, '2026-11-26 02:26:40'),
(41, 9, 4, 'Piñata chica', 'Rayada', 'unidad', 1500.00, 1339.00, 2.000, 2.000, NULL, '7790391309006', 0, 0, 1, '2026-11-26 02:27:37'),
(42, 8, 4, 'Vela bengala holografica', 'x1 (rojo, azul, plateado, dorado)', 'unidad', 700.00, 498.46, 8.000, 4.000, NULL, 'UK8866', 0, 0, 1, '2026-11-26 02:30:08'),
(43, 8, 4, 'Velas bengalas c/gibre', 'x1 (azul, lila, verde, roza)', 'unidad', 800.00, 592.00, 16.000, 5.000, NULL, '2020350000008', 0, 0, 1, '2026-11-26 02:34:45'),
(44, 15, 4, 'Bolsa de papel kraft', '#3', 'unidad', 50.00, 17.00, 100.000, 30.000, NULL, '2022330000008', 0, 0, 1, '2026-11-26 02:36:41'),
(45, 15, 4, 'Bolsa de papel kraft', '#6', 'unidad', 70.00, 26.00, 100.000, 30.000, NULL, '2022350000002', 0, 0, 1, '2026-11-26 02:37:26'),
(46, 21, 4, 'Vaso plastico (jarra)', '1u x 800cc', 'unidad', 200.00, 156.00, 14.000, 5.000, NULL, '296981826369', 0, 0, 1, '2026-11-26 02:39:47'),
(47, 21, 4, 'Vaso plastico chico', '150cc colores', 'unidad', 100.00, 85.00, 50.000, 5.000, NULL, '266212456827', 0, 0, 1, '2026-11-26 02:40:53'),
(48, 21, 4, 'Vaso plastico', '1u x 220cc', 'unidad', 100.00, 69.00, 25.000, 10.000, NULL, '2020590000004', 0, 0, 1, '2026-11-26 02:42:58'),
(49, 21, 4, 'Vaso termico', '1u x180cc', 'unidad', 150.00, 83.00, 25.000, 10.000, NULL, '2003490000008', 0, 0, 1, '2026-11-26 02:44:13'),
(50, 21, 4, 'Potes con tapa', 'x500g', 'unidad', 290.00, 222.00, 50.000, 20.000, NULL, '265941172851', 0, 0, 1, '2026-11-26 02:46:52'),
(51, 21, 4, 'Potes con tapa', '1/8kg', 'unidad', 180.00, 165.00, 50.000, 20.000, NULL, '241277401047', 0, 0, 1, '2026-11-26 02:48:07'),
(52, 21, 4, 'Potes con tapa', '1kg', 'unidad', 500.00, 437.00, 50.000, 20.000, NULL, '299239529475', 0, 0, 1, '2026-11-26 02:49:06'),
(53, 21, 4, 'Pirotines', '#5 x10u', 'unidad', 100.00, 72.00, 1000.000, 30.000, NULL, '2005440000007', 0, 0, 1, '2026-11-26 02:52:22'),
(54, 21, 4, 'Pirotines', '#9 x10u', 'unidad', 160.00, 121.00, 1000.000, 30.000, NULL, '2005480000005', 0, 0, 1, '2026-11-26 02:53:45'),
(55, 21, 4, 'Pirotines', '#11 x10u', 'unidad', 400.00, 320.00, 90.000, 9999999.999, NULL, NULL, 0, 0, 1, '2026-11-26 02:57:54'),
(56, 21, 4, 'Servilletas de color', 'paquete de 40u 32x33cm de colores', 'unidad', 1700.00, 1497.00, 8.000, 2.000, NULL, '2014500000000', 0, 0, 1, '2026-11-26 03:00:33'),
(57, 21, 4, 'Platos plasticos descartables', 'diam .170mm x10u', 'unidad', 500.00, 388.00, 100.000, 30.000, NULL, '2003460000007', 0, 0, 1, '2026-11-26 03:03:20'),
(58, 21, 4, 'Faja de carton', 'x1kg', 'peso', 2000.00, 1782.00, 1.000, 0.500, NULL, '241447717353', 0, 0, 1, '2026-11-26 03:05:45'),
(59, 21, 4, 'Palito brochette', '15cm x10u', 'unidad', 150.00, 101.00, 200.000, 50.000, NULL, '6906325034901', 0, 0, 1, '2026-11-26 03:08:23'),
(60, 21, 4, 'Palito brochette', '25cm x10u', 'unidad', 100.00, 73.00, 176.000, 50.000, NULL, '2011160000005', 0, 0, 1, '2026-11-26 03:10:34'),
(61, 21, 4, 'Mangas descartables', '#1 x1u', 'unidad', 200.00, 161.00, 10.000, 5.000, NULL, '7798126033141', 0, 0, 1, '2026-11-26 03:11:53'),
(62, 21, 4, 'Mangas descartables', '#2 x1u', 'unidad', 300.00, 215.00, 10.000, 5.000, NULL, '7798126033165', 0, 0, 1, '2026-11-26 03:12:47'),
(63, 20, NULL, 'Picos comunes metal', 'x1u', 'unidad', 300.00, 187.00, 23.000, 10.000, NULL, '2062230000005', 0, 0, 1, '2026-11-26 03:15:27'),
(64, 18, 4, 'Condimento Oregano', '', 'peso', 10300.00, 7930.00, 0.700, 0.400, NULL, '210078196875', 0, 0, 1, '2026-11-26 03:19:34'),
(65, 18, 4, 'Condimento Chimichurri', '', 'peso', 12400.00, 9564.00, 1.000, 0.400, NULL, '278802573279', 0, 0, 1, '2026-11-26 03:20:54'),
(66, 18, 4, 'Condimento Para Pizza', '', 'peso', 10800.00, 8302.00, 1.000, 0.400, NULL, '286317964241', 0, 0, 1, '2026-11-26 03:21:59'),
(67, 18, 4, 'Condimento Provenzal', '', 'peso', 13400.00, 10284.00, 1.000, 0.400, NULL, '211946843811', 0, 0, 1, '2026-11-26 03:23:07'),
(68, 18, 4, 'Condimento Pimenton Dulce', '', 'peso', 9000.00, 6986.00, 1.000, 0.400, NULL, NULL, 0, 0, 1, '2026-11-26 03:25:23'),
(69, 9, 4, 'Globo economico', 'liso surtidos 10\" x1u', 'unidad', 120.00, 96.00, 50.000, 20.000, NULL, '2009530000007', 0, 0, 1, '2026-11-26 03:28:56'),
(70, 21, 4, 'Blonda calada', '18cm x10u', 'unidad', 350.00, 312.00, 100.000, 30.000, NULL, '32100018', 0, 0, 1, '2026-11-26 03:31:25'),
(71, 21, 4, 'Blonda calada', '28cm x10u', 'unidad', 650.00, 600.00, 100.000, 30.000, NULL, '8429451210066', 0, 0, 1, '2026-11-26 03:33:00'),
(72, 21, 4, 'Blonda calada', '32cm', 'unidad', 850.00, 768.00, 100.000, 30.000, NULL, '8429451210080', 0, 0, 1, '2026-11-26 03:36:36'),
(73, 2, 4, 'Polvo de hornear', 'por 100g', 'peso', 8000.00, 6500.00, 3.000, 1.000, NULL, '272039156992', 0, 0, 1, '2026-11-26 03:41:31'),
(74, 22, 4, 'Tapas de alfajor', 'Grandes', 'peso', 5300.00, 4290.00, 5.000, 2.000, NULL, '267377825817', 0, 0, 1, '2026-11-26 03:47:47'),
(75, 22, 4, 'Galletita Choco Fachita', 'Tipo chocolinas', 'peso', 9500.00, 8450.00, 4.000, 2.000, NULL, '245693220887', 0, 0, 1, '2026-11-26 03:50:48'),
(76, 22, 4, 'Galletita mini Oreo', '', 'peso', 18200.00, 16512.00, 0.600, 0.300, NULL, '242185718968', 0, 0, 1, '2026-11-26 03:54:03'),
(77, 21, 4, 'Bobina papel', 'Gris', 'peso', 2300.00, 1890.00, 6.400, 2.000, NULL, '233198585295', 0, 0, 1, '2026-11-26 03:58:50'),
(78, 21, 4, 'Papel film', '30m x 28cm', 'unidad', 1900.00, 1460.00, 5.000, 2.000, NULL, '7790373512868', 0, 0, 1, '2026-11-26 04:02:52'),
(79, 21, 4, 'Rollo aluminio', '5m x 28cm', 'unidad', 2000.00, 1426.00, 5.000, 2.000, NULL, '724373618500', 0, 0, 1, '2026-11-26 04:04:42'),
(80, 21, 4, 'Rollo papel Manteca', '5m x 28cm', 'unidad', 1700.00, 1313.00, 5.000, 2.000, NULL, '7791156433417', 0, 0, 1, '2026-11-26 04:07:20'),
(81, 21, 4, 'Rollo PVC', '38cm x 140m \"Nobleza\"', 'unidad', 4700.00, 3685.00, 2.000, 1.000, NULL, '7791156433356', 0, 0, 1, '2026-11-26 04:08:57'),
(82, 21, 4, 'Lamina en plancha', '14cm x 14cm', 'peso', 11500.00, 9971.00, 500.000, 100.000, NULL, '206652995459', 0, 0, 1, '2026-11-26 04:12:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `dias_reparto` varchar(120) DEFAULT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `nombre`, `telefono`, `direccion`, `dias_reparto`, `notas`, `activo`, `creado_en`) VALUES
(1, 'venturini', '348200000', 'avenida siempre viva 123', 'lunes y domingo', 'test', 1, '2026-10-03 22:51:28'),
(2, 'Gianni Bs As', '91127585395', '', '', 'pedido minimo 50', 1, '2026-11-23 23:25:45'),
(3, 'Gustavo', '3482309876', 'Calle 15', 'Jueves', '', 1, '2026-11-24 00:10:03'),
(4, 'Embalajes WILLY', '3482443646', 'CALLE 15 376', '', '', 1, '2026-11-24 02:36:16'),
(5, 'Los Hermanitos', '3482650924', 'Don Pedro', '', '', 1, '2026-11-24 02:36:58'),
(6, 'Luciano Nardelli', '3482414721', '', 'Jueves', '', 1, '2026-11-24 02:37:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `usuario` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('dueño','empleado') NOT NULL DEFAULT 'empleado',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `usuario`, `password_hash`, `rol`, `activo`, `creado_en`) VALUES
(1, 'georgina', 'georgina', '$2y$10$YgD2NcozSqnkr09rthj7kOaTplr8liMRkEm6SVFaRAyolHzasamJS', 'dueño', 1, '2026-11-12 22:30:43'),
(2, 'luciano', 'luciano', '$2y$10$DN4qSLkrSr7bP8Y4fI0OMeDuRud/fiUyNelvgTd/EvkP9gM2G.18O', 'dueño', 1, '2026-11-12 22:36:25');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `combos`
--
ALTER TABLE `combos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `combo_productos`
--
ALTER TABLE `combo_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `combo_id` (`combo_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`clave`);

--
-- Indices de la tabla `cuentas_corrientes`
--
ALTER TABLE `cuentas_corrientes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cuenta_movimientos`
--
ALTER TABLE `cuenta_movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `idx_cuenta_mov_cuenta` (`cuenta_id`);

--
-- Indices de la tabla `horarios_atencion`
--
ALTER TABLE `horarios_atencion`
  ADD PRIMARY KEY (`dia`);

--
-- Indices de la tabla `login_intentos`
--
ALTER TABLE `login_intentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_usuario` (`usuario`,`creado_en`),
  ADD KEY `idx_login_ip` (`ip`,`creado_en`);

--
-- Indices de la tabla `movimientos_stock`
--
ALTER TABLE `movimientos_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_movimientos_producto` (`producto_id`),
  ADD KEY `idx_movimientos_fecha` (`creado_en`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_pedidos_estado` (`estado`),
  ADD KEY `idx_pedidos_vence` (`vence_en`);

--
-- Indices de la tabla `pedido_items`
--
ALTER TABLE `pedido_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `combo_id` (`combo_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_barras` (`codigo_barras`),
  ADD KEY `idx_productos_categoria` (`categoria_id`),
  ADD KEY `fk_productos_proveedor` (`proveedor_id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `combos`
--
ALTER TABLE `combos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `combo_productos`
--
ALTER TABLE `combo_productos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cuentas_corrientes`
--
ALTER TABLE `cuentas_corrientes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cuenta_movimientos`
--
ALTER TABLE `cuenta_movimientos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `login_intentos`
--
ALTER TABLE `login_intentos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `movimientos_stock`
--
ALTER TABLE `movimientos_stock`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `pedido_items`
--
ALTER TABLE `pedido_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `combo_productos`
--
ALTER TABLE `combo_productos`
  ADD CONSTRAINT `combo_productos_ibfk_1` FOREIGN KEY (`combo_id`) REFERENCES `combos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `combo_productos_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `cuenta_movimientos`
--
ALTER TABLE `cuenta_movimientos`
  ADD CONSTRAINT `cuenta_movimientos_ibfk_1` FOREIGN KEY (`cuenta_id`) REFERENCES `cuentas_corrientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cuenta_movimientos_ibfk_2` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `movimientos_stock`
--
ALTER TABLE `movimientos_stock`
  ADD CONSTRAINT `movimientos_stock_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `movimientos_stock_ibfk_2` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  ADD CONSTRAINT `movimientos_stock_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pedido_items`
--
ALTER TABLE `pedido_items`
  ADD CONSTRAINT `pedido_items_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedido_items_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `pedido_items_ibfk_3` FOREIGN KEY (`combo_id`) REFERENCES `combos` (`id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_productos_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

<?php

// Página actual
$paginaActual = basename($_SERVER['PHP_SELF']);

?>

<aside class="sidebar">

    <h2>YCL-Solutions</h2>

    <nav>

        <ul>

            <!-- Clientes -->
            <li class="<?= $paginaActual == 'Clientes.php' ? 'active' : '' ?>">
                <a href="/YCL_Solutions/CLIENTES/Clientes/Clientes.php">

                    <span class="icono clientes">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="9" cy="8" r="3" fill="#6ec6ff"/>
                            <circle cx="17" cy="9" r="2.5" fill="#9bdcff"/>
                            <path d="M3.5 19c0-3 2.4-5 5.5-5s5.5 2 5.5 5" fill="#6ec6ff"/>
                            <path d="M14 14.5c2.8-.2 5 1.5 5 4.5" fill="#9bdcff"/>
                        </svg>
                    </span>

                    <span>Clientes</span>

                </a>
            </li>


            <!-- Pedidos -->
            <li class="<?= $paginaActual == 'Pedidos.php' ? 'active' : '' ?>">
                <a href="/YCL_Solutions/PEDIDOS/Pedidos.php">

                    <span class="icono pedidos">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="8" cy="19" r="1.8" fill="#ff9f43"/>
                            <circle cx="18" cy="19" r="1.8" fill="#ff9f43"/>
                            <path d="M3 4h3l2 10h10l3-7H7" fill="none" stroke="#ff9f43" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>

                    <span>Pedidos</span>

                </a>
            </li>


            <!-- Producción -->
            <li class="<?= $paginaActual == 'Produccion.php' ? 'active' : '' ?>">
                <a href="/YCL_Solutions/PRODUCCION/Produccion/Produccion.php">

                    <span class="icono produccion">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 20V10l6-4 5 3 4-3 3 2v12H3z" fill="#ffc857"/>
                            <path d="M7 20v-5h4v5M15 12h3M15 15h3M15 18h3" fill="none" stroke="#8a6500" stroke-width="1.5"/>
                        </svg>
                    </span>

                    <span>Producción</span>

                </a>
            </li>


            <!-- Inventarios -->
            <li class="<?= $paginaActual == 'Inventarios.php' ? 'active' : '' ?>">
                <a href="/YCL_Solutions/INVENTARIOS/Inventarios/Inventarios.php">

                    <span class="icono inventarios">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 7l9-4 9 4-9 4-9-4z" fill="#58d68d"/>
                            <path d="M3 7v10l9 4 9-4V7M12 11v10" fill="none" stroke="#2ecc71" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                    </span>

                    <span>Inventarios</span>

                </a>
            </li>


            <!-- Catálogos -->
            <li class="<?= $paginaActual == 'Catalogos.php' ? 'active' : '' ?>">
                <a href="/YCL_Solutions/CATALOGOS/Catalogos.php">

                    <span class="icono catalogos">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 10l2-5h14l2 5H3z" fill="#b084f5"/>
                            <path d="M5 10v10h14V10M9 20v-6h6v6" fill="none" stroke="#b084f5" stroke-width="1.8"/>
                            <path d="M3 10h18" stroke="#7d4bc2" stroke-width="1.8"/>
                        </svg>
                    </span>

                    <span>Catálogos</span>

                </a>
            </li>


            <!-- Facturación -->
            <li class="<?= $paginaActual == 'Facturacion.php' ? 'active' : '' ?>">
                <a href="/YCL_Solutions/FACTURACION/Facturacion.php">

                    <span class="icono facturacion">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M6 3h12v18l-2-1.5L14 21l-2-1.5L10 21l-2-1.5L6 21V3z" fill="#54d6c7"/>
                            <path d="M9 8h6M9 12h6M9 16h4" fill="none" stroke="#087f73" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                    </span>

                    <span>Facturación</span>

                </a>
            </li>


            <!-- Reportes -->
            <li class="<?= $paginaActual == 'Reportes.php' ? 'active' : '' ?>">
                <a href="/YCL_Solutions/REPORTES/Reportes.php">

                    <span class="icono reportes">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 20V10h4v10H4z" fill="#ffd166"/>
                            <path d="M10 20V5h4v15h-4z" fill="#ffca3a"/>
                            <path d="M16 20v-7h4v7h-4z" fill="#f4b400"/>
                        </svg>
                    </span>

                    <span>Reportes</span>

                </a>
            </li>


            <!-- Configuración -->
            <li class="<?= $paginaActual == 'Configuracion.php' ? 'active' : '' ?>">
                <a href="/YCL_Solutions/CONFIGURACION/Configuracion/Configuracion.php">

                    <span class="icono configuracion">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 8a4 4 0 100 8 4 4 0 000-8z" fill="#d7e2f0"/>
                            <path d="M19.4 15a1.8 1.8 0 00.36 2l.1.1-1.9 1.9-.1-.1a1.8 1.8 0 00-2-.36 1.8 1.8 0 00-1.1 1.66V20h-2.7v-.14a1.8 1.8 0 00-1.1-1.66 1.8 1.8 0 00-2 .36l-.1.1-1.9-1.9.1-.1a1.8 1.8 0 00.36-2A1.8 1.8 0 006 14H5.8v-2.7H6a1.8 1.8 0 001.66-1.1 1.8 1.8 0 00-.36-2l-.1-.1 1.9-1.9.1.1a1.8 1.8 0 002 .36A1.8 1.8 0 0012.3 5v-.2H15V5a1.8 1.8 0 001.1 1.66 1.8 1.8 0 002-.36l.1-.1 1.9 1.9-.1.1a1.8 1.8 0 00-.36 2 1.8 1.8 0 001.66 1.1h.2V14h-.2A1.8 1.8 0 0019.4 15z"
                                  fill="#d7e2f0"/>
                        </svg>
                    </span>

                    <span>Configuración</span>

                </a>
            </li>

        </ul>

    </nav>

</aside>
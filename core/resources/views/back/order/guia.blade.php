<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Guía {{ $datos['codigo'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .contenedor {
            width: 600px;
            border: 1px solid #d3d3d3;
            padding: 20px;
            border-radius: 20px
        }

        .wrapflex {
            display: flex;
            align-items: flex-start; /* Alinea arriba */
            gap: 20px;              /* Espacio entre QR y datos */
           
        }

        .logo {
            width: 100px;
        }

        .qr img { 
            width: 120px; 
            height: auto; 
        }

        .datos { 
            text-align: left;
            font-size: 14px; 
            flex: 1;
            padding-left: 25px;
        }

        .bold {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="contenedor">
        <h2>Guía #{{ $datos['codigo'] }}</h2>
        <div class="wrapflex">
            <table>
                <tbody>
                    <tr>
                        <th>
                            <div class="qr" style="text-align: center">
                                <img src="{{ $datos['logo'] }}" alt="Logo" class="logo">
                                <br /><br />
                                <img src="data:image/png;base64,{{ $qrCode }}" alt="QR Code">
                                <br />
                                <small style="font-size:10px;"><sub>(QR unicamente para repartidor)</sub></small>
                            </div>
                        </th>

                        <th>
                        
                            <div class="datos">
                                <p class="bold">{{ $datos['nombre'] }}</p>
                                <p><span class="bold">Código de rastreo:</span> {{ $datos['codigo'] }}</p>
                                <p><span class="bold">Tel:</span> {{ $datos['telefono'] }}</p>
                                <p><span class="bold">Dirección:</span> {{ $datos['direccion'] }}</p>
                                <p><span class="bold">Referencias:</span> {{ $datos['referencias'] }}</p>
                                <p><span class="bold">Notas de envío:</span> {{ $datos['notas'] }}</p>
                                <p><span class="bold">Envía:</span> {{ $datos['remitente'] }} - {{ $datos['telefono_rem'] }}</p>
                            </div>
                        </th>
                    </tr>
                </tbody>
            </table>
        </div>

        <p style="margin-top:30px;">www.paqueteExpress.com</p>
    </div>
</body>

</html>

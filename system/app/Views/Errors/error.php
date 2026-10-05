<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= IMG ?>favicon.ico">
    <title>Página No Encontrada - 404</title>
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #4f46e5;
            --success: #10b981;
            --info: #0ea5e9;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark-bg: #111827;
            --dark-secondary: #1a202c;
            --dark-tertiary: #2d3748;
            --dark-text: #f3f4f6;
            --light-bg: #f9fafb;
            --light-secondary: #ffffff;
            --light-text: #111827;
            --light-border: #e5e7eb;
            --neon-green: #39ff14;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--dark-secondary) 50%, var(--dark-tertiary) 100%);
            color: var(--dark-text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            text-align: center;
            max-width: 600px;
            padding: 40px;
            background: rgba(26, 32, 44, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .error-code {
            font-size: 8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 50%, var(--neon-green) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
            position: relative;
            text-shadow: 0 0 30px rgba(57, 255, 20, 0.3);
        }

        .error-code::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--neon-green));
            border-radius: 2px;
        }

        .error-title {
            font-size: 2rem;
            font-weight: 600;
            color: var(--light-secondary);
            margin-bottom: 15px;
            letter-spacing: 0.5px;
        }

        .error-message {
            font-size: 1.1rem;
            color: #d1d5db;
            line-height: 1.6;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--dark-text);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--neon-green);
            transform: translateY(-2px);
        }

        .error-search {
            position: relative;
            max-width: 400px;
            margin: 0 auto;
        }

        .search-input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: none;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--dark-text);
            font-size: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--neon-green);
            box-shadow: 0 0 0 3px rgba(57, 255, 20, 0.1);
            background: rgba(255, 255, 255, 0.08);
        }

        .search-input::placeholder {
            color: #9ca3af;
        }

        .search-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--neon-green);
            cursor: pointer;
            padding: 5px;
            transition: color 0.3s ease;
        }

        .search-btn:hover {
            color: var(--primary);
        }

        .error-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #9ca3af;
            font-size: 0.9rem;
        }

        .error-footer a {
            color: var(--neon-green);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .error-footer a:hover {
            color: var(--primary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .error-code {
                font-size: 6rem;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
            
            .error-message {
                font-size: 1rem;
            }
            
            .error-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 250px;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .error-container {
                padding: 30px 20px;
            }
            
            .error-code {
                font-size: 4rem;
            }
            
            .error-title {
                font-size: 1.3rem;
            }
        }

        /* Efectos sutiles */
        .error-container {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-code {
            animation: pulse 2s infinite alternate;
        }

        @keyframes pulse {
            from {
                text-shadow: 0 0 20px rgba(57, 255, 20, 0.3);
            }
            to {
                text-shadow: 0 0 40px rgba(57, 255, 20, 0.6);
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-title">Página No Encontrada</h1>
        <p class="error-message">Lo sentimos, la página que estás buscando no existe o ha sido movida. Por favor, verifica la URL o navega hacia nuestra página principal.</p>
        
        <div class="error-actions">
            <a href="<?= BASE_URL() ?>" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Volver al Inicio
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Volver Atrás
            </a>
        </div>
        
        <div class="error-footer">
            <p>¿Necesitas ayuda? <a href="/contacto">Contáctanos</a> o visita nuestro <a href="/soporte">centro de soporte</a></p>
        </div>
    </div>
</body>
</html>
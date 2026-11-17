<?php
// ============================================================
// SISTEMA · Visor de estructura de archivos
// Archivo temporal de diagnóstico · eliminar antes de producción
// ============================================================

declare(strict_types=1);

$host  = $_SERVER['HTTP_HOST'] ?? 'sistema.test';
$root  = __DIR__;

$accion  = $_GET['accion']  ?? '';
$formato = $_GET['formato'] ?? '';

// ------------------------------------------------------------
// Escaneo recursivo
// ------------------------------------------------------------
function escanear(string $dir, string $base): array {
    $items = [];
    $entries = @scandir($dir);
    if ($entries === false) return $items;

    sort($entries);
    foreach ($entries as $e) {
        if ($e === '.' || $e === '..') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $e;
        $rel  = ltrim(substr($full, strlen($base)), DIRECTORY_SEPARATOR);
        $isDir = is_dir($full);

        $nodo = [
            'nombre' => $e,
            'ruta'   => str_replace('\\', '/', $rel),
            'tipo'   => $isDir ? 'dir' : 'file',
            'size'   => $isDir ? 0 : (int)@filesize($full),
        ];

        if ($isDir) {
            $nodo['hijos'] = escanear($full, $base);
        }

        $items[] = $nodo;
    }

    return $items;
}

$tree = escanear($root, $root);

// ------------------------------------------------------------
// Aplanar para exportar
// ------------------------------------------------------------
function aplanar(array $nodos, int $nivel = 0): array {
    $out = [];
    foreach ($nodos as $n) {
        $out[] = ['nivel' => $nivel] + $n;
        if (!empty($n['hijos'])) {
            foreach (aplanar($n['hijos'], $nivel + 1) as $h) {
                $out[] = $h;
            }
        }
    }
    return $out;
}

// ------------------------------------------------------------
// Exportar TXT
// ------------------------------------------------------------
if ($accion === 'export' && $formato === 'txt') {
    $flat = aplanar($tree);
    $out  = "ESTRUCTURA DE " . strtoupper($host) . "\n";
    $out .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
    $out .= str_repeat('=', 60) . "\n\n";

    foreach ($flat as $n) {
        $prefijo = str_repeat('  ', $n['nivel']);
        $simbolo = $n['tipo'] === 'dir' ? '[D]' : '[F]';
        $size    = $n['tipo'] === 'file' ? ' (' . number_format($n['size']) . ' B)' : '';
        $out .= $prefijo . $simbolo . ' ' . $n['nombre'] . $size . "\n";
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="estructura_' . date('Ymd_His') . '.txt"');
    echo $out;
    exit;
}

// ------------------------------------------------------------
// Exportar CSV
// ------------------------------------------------------------
if ($accion === 'export' && $formato === 'csv') {
    $flat = aplanar($tree);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="estructura_' . date('Ymd_His') . '.csv"');

    $fh = fopen('php://output', 'w');
    fputs($fh, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel
    fputcsv($fh, ['Nivel', 'Tipo', 'Nombre', 'Ruta', 'Tamaño (bytes)']);
    foreach ($flat as $n) {
        fputcsv($fh, [
            $n['nivel'],
            $n['tipo'],
            $n['nombre'],
            $n['ruta'],
            $n['size'],
        ]);
    }
    fclose($fh);
    exit;
}

// ------------------------------------------------------------
// Render HTML
// ------------------------------------------------------------
function renderNodos(array $nodos, int $nivel = 0): void {
    foreach ($nodos as $n) {
        $esDir   = $n['tipo'] === 'dir';
        $padding = $nivel * 22;

        echo '<li class="node ' . ($esDir ? 'is-dir' : 'is-file') . '">';
        echo '<div class="node-row" style="padding-left:' . $padding . 'px">';

        if ($esDir) {
            echo '<button class="node-toggle" data-toggle aria-label="Expandir" type="button">';
            echo '<i data-lucide="chevron-right" class="node-caret"></i>';
            echo '</button>';
        } else {
            echo '<span class="node-caret-spacer"></span>';
        }

        echo '<span class="node-icon">';
        if ($esDir) {
            echo '<i data-lucide="folder"></i>';
        } else {
            $ext = strtolower(pathinfo($n['nombre'], PATHINFO_EXTENSION));
            $map = [
                'php'  => 'file-code-2',
                'js'   => 'file-code-2',
                'css'  => 'palette',
                'html' => 'file-code',
                'sql'  => 'database',
                'txt'  => 'file-text',
                'png'  => 'image',
                'jpg'  => 'image',
                'svg'  => 'image',
                'ico'  => 'image',
                'json' => 'braces',
            ];
            $ico = $map[$ext] ?? 'file';
            echo '<i data-lucide="' . $ico . '"></i>';
        }
        echo '</span>';

        echo '<span class="node-name">' . htmlspecialchars($n['nombre'], ENT_QUOTES, 'UTF-8') . '</span>';

        if (!$esDir) {
            echo '<span class="node-size">' . number_format($n['size']) . ' B</span>';
        }

        echo '</div>';

        if ($esDir && !empty($n['hijos'])) {
            echo '<ul class="node-children">';
            renderNodos($n['hijos'], $nivel + 1);
            echo '</ul>';
        }

        echo '</li>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estructura · <?= htmlspecialchars($host, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --brand: #4f46e5;
            --brand-soft: #eef2ff;
            --dir: #d97706;
            --file: #64748b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            min-height: 100vh;
        }

        .wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem 1.5rem 3rem;
        }

        .head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .head-left { display: flex; align-items: center; gap: .9rem; }

        .head-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--brand-soft);
            color: var(--brand);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .head-icon svg, .head-icon [data-lucide] { width: 24px; height: 24px; }

        .head h1 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .head p {
            margin: .15rem 0 0;
            font-size: .85rem;
            color: var(--muted);
        }

        .actions { display: flex; gap: .5rem; flex-wrap: wrap; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem .9rem;
            border-radius: .6rem;
            font-size: .85rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text);
            cursor: pointer;
            transition: background .15s;
        }
        .btn:hover { background: #f1f5f9; }
        .btn-primary { background: var(--brand); color: #fff; border-color: var(--brand); }
        .btn-primary:hover { background: #4338ca; }
        .btn svg, .btn [data-lucide] { width: 1rem; height: 1rem; }

        .tree-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            overflow: hidden;
        }

        .tree { list-style: none; margin: 0; padding: .5rem 0; }
        .tree ul { list-style: none; margin: 0; padding: 0; }

        .node-row {
            display: flex;
            align-items: center;
            gap: .35rem;
            padding: .3rem 1rem .3rem 0;
            font-size: .875rem;
            border-radius: .4rem;
            margin: 1px .5rem;
        }

        .node-row:hover { background: #f8fafc; }

        .node-toggle {
            background: transparent;
            border: 0;
            padding: 0;
            cursor: pointer;
            color: var(--muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
        }

        .node-caret { transition: transform .15s ease; width: 1rem; height: 1rem; }

        .node.is-open > .node-row .node-caret { transform: rotate(90deg); }

        .node-caret-spacer { width: 1.25rem; height: 1.25rem; display: inline-block; }

        .node-icon svg, .node-icon [data-lucide] { width: 1rem; height: 1rem; }
        .is-dir > .node-row .node-icon { color: var(--dir); }
        .is-file > .node-row .node-icon { color: var(--file); }

        .node-name { flex: 1; }
        .is-dir > .node-row .node-name { font-weight: 600; }

        .node-size {
            color: var(--muted);
            font-size: .75rem;
            font-variant-numeric: tabular-nums;
        }

        .node-children { display: none; }
        .node.is-open > .node-children { display: block; }

        .footer {
            margin-top: 1.5rem;
            font-size: .75rem;
            color: var(--muted);
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <header class="head">
            <div class="head-left">
                <div class="head-icon"><i data-lucide="folder-tree"></i></div>
                <div>
                    <h1>Estructura de <?= htmlspecialchars($host, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p><?= date('d/m/Y H:i') ?> · <?= htmlspecialchars($root, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
            <div class="actions">
                <a href="?accion=export&formato=txt" class="btn">
                    <i data-lucide="file-text"></i> Exportar TXT
                </a>
                <a href="?accion=export&formato=csv" class="btn btn-primary">
                    <i data-lucide="table"></i> Exportar CSV
                </a>
            </div>
        </header>

        <div class="tree-card">
            <ul class="tree">
                <?php renderNodos($tree); ?>
            </ul>
        </div>

        <p class="footer">Archivo temporal · eliminar antes de producción</p>
    </div>

    <script>
        // Expandir/colapsar
        document.querySelectorAll('[data-toggle]').forEach(btn => {
            btn.addEventListener('click', () => {
                const li = btn.closest('.node');
                if (li) li.classList.toggle('is-open');
            });
        });

        // Todo expandido inicialmente
        document.querySelectorAll('.node.is-dir').forEach(n => n.classList.add('is-open'));

        lucide.createIcons();
    </script>
</body>
</html>
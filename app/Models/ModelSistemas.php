<?php
namespace App\Models;

use App\Core\Model;

class ModelSistemas extends Model
{
    /* ============================================================
       EQUIPOS · LISTADO PAGINADO
       ============================================================ */
    public function listarEquiposPaginado(string $q = '', int $limit = 10, int $offset = 0, array $filtros = [], int $inst = 0): array
    {
        $where  = "1=1";
        $params = [];

        if ($inst > 0) {
            $where .= " AND e.id_institucion = :inst";
            $params['inst'] = $inst;
        }

        if ($q !== '') {
            $where .= " AND (
                            e.codigo_equipo     LIKE :q1
                         OR e.tipo_equipo       LIKE :q2
                         OR e.marca             LIKE :q3
                         OR e.modelo            LIKE :q4
                         OR e.serial_equipo     LIKE :q5
                         OR e.nombre_host       LIKE :q6
                         OR e.direccion_ip      LIKE :q7
                         OR d.departamento_nombre LIKE :q8
                         OR CONCAT(COALESCE(p.personal_nombre,''), ' ', COALESCE(p.personal_apellido,'')) LIKE :q9
                       )";
            $like = "%{$q}%";
            for ($i = 1; $i <= 9; $i++) {
                $params["q{$i}"] = $like;
            }
        }

        if (!empty($filtros['departamento'])) {
            $where .= " AND e.departamento_id = :depto";
            $params['depto'] = (int)$filtros['departamento'];
        }

        if (!empty($filtros['tipo'])) {
            $where .= " AND e.tipo_equipo = :tipo";
            $params['tipo'] = $filtros['tipo'];
        }

        if (!empty($filtros['status'])) {
            $where .= " AND e.status_equipo = :status";
            $params['status'] = $filtros['status'];
        }

        $totalParams = [];
        $totalWhere  = "1=1";
        if ($inst > 0) {
            $totalWhere .= " AND id_institucion = :inst";
            $totalParams['inst'] = $inst;
        }

        $total = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t FROM table_sistemas_equipos WHERE {$totalWhere}",
            $totalParams
        )['t'] ?? 0);

        $totalFiltrados = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t
             FROM table_sistemas_equipos e
             LEFT JOIN table_departamentos d ON d.departamento_id = e.departamento_id
             LEFT JOIN table_personal p      ON p.id_personal = e.responsable_id
             WHERE {$where}",
            $params
        )['t'] ?? 0);

        $sql = "SELECT e.*,
                       d.departamento_nombre,
                       TRIM(CONCAT(COALESCE(p.personal_nombre,''), ' ', COALESCE(p.personal_apellido,''))) AS responsable_nombre,
                       (SELECT COUNT(*) FROM table_sistemas_mantenimiento m WHERE m.id_equipo = e.id_equipo) AS total_intervenciones,
                       (SELECT COUNT(*) FROM table_sistemas_mantenimiento m3 WHERE m3.id_equipo = e.id_equipo AND m3.estado_intervencion <> 'CERRADA') AS intervenciones_abiertas,
                       (SELECT MAX(m2.fecha_intervencion) FROM table_sistemas_mantenimiento m2 WHERE m2.id_equipo = e.id_equipo) AS ultima_intervencion
                FROM table_sistemas_equipos e
                LEFT JOIN table_departamentos d ON d.departamento_id = e.departamento_id
                LEFT JOIN table_personal p      ON p.id_personal = e.responsable_id
                WHERE {$where}
                ORDER BY e.id_equipo DESC
                LIMIT {$limit} OFFSET {$offset}";

        return [
            'data'           => $this->fetchAll($sql, $params),
            'total'          => $total,
            'totalFiltrados' => $totalFiltrados,
        ];
    }

    public function findEquipo(int $id, int $inst = 0): ?array
    {
        $sql = "SELECT e.*,
                       d.departamento_nombre,
                       TRIM(CONCAT(COALESCE(p.personal_nombre,''), ' ', COALESCE(p.personal_apellido,''))) AS responsable_nombre
                FROM table_sistemas_equipos e
                LEFT JOIN table_departamentos d ON d.departamento_id = e.departamento_id
                LEFT JOIN table_personal p      ON p.id_personal = e.responsable_id
                WHERE e.id_equipo = :id";
        $params = ['id' => $id];
        if ($inst > 0) {
            $sql .= " AND e.id_institucion = :inst";
            $params['inst'] = $inst;
        }
        $sql .= " LIMIT 1";
        return $this->fetchOne($sql, $params);
    }

    public function existeCodigoEquipo(string $codigo, int $inst, ?int $excludeId = null): bool
    {
        $sql = "SELECT 1 FROM table_sistemas_equipos WHERE codigo_equipo = :c AND id_institucion = :inst";
        $params = ['c' => $codigo, 'inst' => $inst];
        if ($excludeId) {
            $sql .= " AND id_equipo <> :id";
            $params['id'] = $excludeId;
        }
        $sql .= " LIMIT 1";
        return (bool)$this->fetchOne($sql, $params);
    }

    public function createEquipo(array $d, int $userId, int $inst): int
    {
        $instId = $inst > 0 ? $inst : (int)DEFAULT_INSTITUCION;
        $sql = "INSERT INTO table_sistemas_equipos (
                    codigo_equipo, tipo_equipo, departamento_id, responsable_id,
                    marca, modelo, serial_equipo, procesador, ram_gb,
                    almacenamiento, sistema_operativo, nombre_host, direccion_ip,
                    direccion_mac, fecha_adquisicion, observaciones,
                    status_equipo, usuario_id, id_institucion
                ) VALUES (
                    :codigo, :tipo, :depto, :responsable,
                    :marca, :modelo, :serial, :procesador, :ram,
                    :almacenamiento, :so, :host, :ip,
                    :mac, :fecha_adq, :obs,
                    :status, :user_id, :inst
                )";

        $this->query($sql, [
            'codigo'         => $d['codigo_equipo'],
            'tipo'           => $d['tipo_equipo'] ?? 'PC ESCRITORIO',
            'depto'          => !empty($d['departamento_id']) ? (int)$d['departamento_id'] : null,
            'responsable'    => !empty($d['responsable_id'])  ? (int)$d['responsable_id']  : null,
            'marca'          => $d['marca'] ?? null,
            'modelo'         => $d['modelo'] ?? null,
            'serial'         => $d['serial_equipo'] ?? null,
            'procesador'     => $d['procesador'] ?? null,
            'ram'            => $d['ram_gb'] ?? null,
            'almacenamiento' => $d['almacenamiento'] ?? null,
            'so'             => $d['sistema_operativo'] ?? null,
            'host'           => $d['nombre_host'] ?? null,
            'ip'             => $d['direccion_ip'] ?? null,
            'mac'            => $d['direccion_mac'] ?? null,
            'fecha_adq'      => !empty($d['fecha_adquisicion']) ? $d['fecha_adquisicion'] : null,
            'obs'            => $d['observaciones'] ?? null,
            'status'         => $d['status_equipo'] ?? 'ACTIVO',
            'user_id'        => $userId,
            'inst'           => $instId,
        ]);

        $id = (int)$this->lastInsertId();
        $this->registrarHistorial($id, 'REGISTRO', "Ficha técnica registrada ({$d['codigo_equipo']}).", $userId, $instId);
        return $id;
    }

    public function updateEquipo(int $id, array $d, int $userId, int $inst): bool
    {
        $sql = "UPDATE table_sistemas_equipos SET
                    codigo_equipo       = :codigo,
                    tipo_equipo         = :tipo,
                    departamento_id     = :depto,
                    responsable_id      = :responsable,
                    marca               = :marca,
                    modelo              = :modelo,
                    serial_equipo       = :serial,
                    procesador          = :procesador,
                    ram_gb              = :ram,
                    almacenamiento      = :almacenamiento,
                    sistema_operativo   = :so,
                    nombre_host         = :host,
                    direccion_ip        = :ip,
                    direccion_mac       = :mac,
                    fecha_adquisicion   = :fecha_adq,
                    observaciones       = :obs,
                    status_equipo       = :status
                WHERE id_equipo = :id";

        $ok = $this->execute($sql, [
            'codigo'         => $d['codigo_equipo'],
            'tipo'           => $d['tipo_equipo'] ?? 'PC ESCRITORIO',
            'depto'          => !empty($d['departamento_id']) ? (int)$d['departamento_id'] : null,
            'responsable'    => !empty($d['responsable_id'])  ? (int)$d['responsable_id']  : null,
            'marca'          => $d['marca'] ?? null,
            'modelo'         => $d['modelo'] ?? null,
            'serial'         => $d['serial_equipo'] ?? null,
            'procesador'     => $d['procesador'] ?? null,
            'ram'            => $d['ram_gb'] ?? null,
            'almacenamiento' => $d['almacenamiento'] ?? null,
            'so'             => $d['sistema_operativo'] ?? null,
            'host'           => $d['nombre_host'] ?? null,
            'ip'             => $d['direccion_ip'] ?? null,
            'mac'            => $d['direccion_mac'] ?? null,
            'fecha_adq'      => !empty($d['fecha_adquisicion']) ? $d['fecha_adquisicion'] : null,
            'obs'            => $d['observaciones'] ?? null,
            'status'         => $d['status_equipo'] ?? 'ACTIVO',
            'id'             => $id,
        ]);

        if ($ok) {
            $this->registrarHistorial($id, 'ACTUALIZACION_FICHA', "Ficha técnica actualizada ({$d['codigo_equipo']}).", $userId, $inst);
        }
        return $ok;
    }

    public function deleteEquipo(int $id): bool
    {
        return $this->execute("DELETE FROM table_sistemas_equipos WHERE id_equipo = :id", ['id' => $id]);
    }

    public function actualizarEspecificacionTrasReemplazo(int $idEquipo, ?string $tipoComponente, ?string $componenteNuevo): void
    {
        if (!$tipoComponente || !$componenteNuevo) return;
        $comp = strtoupper(trim($tipoComponente));
        $val  = trim($componenteNuevo);

        if ($comp === 'RAM') {
            $this->execute("UPDATE table_sistemas_equipos SET ram_gb = :v WHERE id_equipo = :id", ['v' => $val, 'id' => $idEquipo]);
        } elseif ($comp === 'DISCO') {
            $this->execute("UPDATE table_sistemas_equipos SET almacenamiento = :v WHERE id_equipo = :id", ['v' => $val, 'id' => $idEquipo]);
        } elseif ($comp === 'PROCESADOR') {
            $this->execute("UPDATE table_sistemas_equipos SET procesador = :v WHERE id_equipo = :id", ['v' => $val, 'id' => $idEquipo]);
        }
    }

    /* ============================================================
       MANTENIMIENTO / SOPORTE / REEMPLAZO
       ============================================================ */
    public function listarMantenimientoPorEquipo(int $idEquipo): array
    {
        $sql = "SELECT m.*,
                       TRIM(CONCAT(COALESCE(p.personal_nombre,''), ' ', COALESCE(p.personal_apellido,''))) AS tecnico_nombre
                FROM table_sistemas_mantenimiento m
                LEFT JOIN table_personal p ON p.id_personal = m.tecnico_id
                WHERE m.id_equipo = :id_equipo
                ORDER BY m.fecha_intervencion DESC, m.id_mantenimiento DESC";
        return $this->fetchAll($sql, ['id_equipo' => $idEquipo]);
    }

    public function findMantenimiento(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM table_sistemas_mantenimiento WHERE id_mantenimiento = :id LIMIT 1",
            ['id' => $id]
        );
    }

    public function createMantenimiento(array $d, int $userId, int $inst): int
    {
        $instId = $inst > 0 ? $inst : (int)DEFAULT_INSTITUCION;
        $sql = "INSERT INTO table_sistemas_mantenimiento (
                    id_equipo, tipo_intervencion, fecha_intervencion, fecha_proximo_mant,
                    tecnico_id, solicitante_nombre, falla_reportada, diagnostico,
                    accion_realizada, tipo_componente, componente_anterior, componente_nuevo,
                    serial_nuevo, estado_intervencion, observaciones, usuario_id, id_institucion
                ) VALUES (
                    :id_equipo, :tipo, :fecha_int, :fecha_prox,
                    :tecnico_id, :solicitante, :falla, :diagnostico,
                    :accion, :tipo_comp, :comp_ant, :comp_nue,
                    :serial_nue, :estado, :obs, :user_id, :inst
                )";

        $this->query($sql, [
            'id_equipo'   => (int)$d['id_equipo'],
            'tipo'        => $d['tipo_intervencion'] ?? 'MANTENIMIENTO_PREVENTIVO',
            'fecha_int'   => $d['fecha_intervencion'],
            'fecha_prox'  => !empty($d['fecha_proximo_mant']) ? $d['fecha_proximo_mant'] : null,
            'tecnico_id'  => !empty($d['tecnico_id']) ? (int)$d['tecnico_id'] : null,
            'solicitante' => $d['solicitante_nombre'] ?? null,
            'falla'       => $d['falla_reportada'] ?? null,
            'diagnostico' => $d['diagnostico'],
            'accion'      => $d['accion_realizada'],
            'tipo_comp'   => !empty($d['tipo_componente']) ? $d['tipo_componente'] : null,
            'comp_ant'    => !empty($d['componente_anterior']) ? $d['componente_anterior'] : null,
            'comp_nue'    => !empty($d['componente_nuevo']) ? $d['componente_nuevo'] : null,
            'serial_nue'  => !empty($d['serial_nuevo']) ? $d['serial_nuevo'] : null,
            'estado'      => $d['estado_intervencion'] ?? 'CERRADA',
            'obs'         => $d['observaciones'] ?? null,
            'user_id'     => $userId,
            'inst'        => $instId,
        ]);

        $idMant = (int)$this->lastInsertId();

        $tipoLabel = $d['tipo_intervencion'] ?? 'INTERVENCION';
        $detalleHist = "[{$tipoLabel}] {$d['diagnostico']} — {$d['accion_realizada']}";
        if (!empty($d['componente_nuevo'])) {
            $detalleHist .= " | Reemplazo ({$d['tipo_componente']}): " . ($d['componente_anterior'] ?: 'N/A') . " → " . $d['componente_nuevo'];
        }
        $this->registrarHistorial((int)$d['id_equipo'], $tipoLabel, $detalleHist, $userId, $instId);

        if (($d['tipo_intervencion'] ?? '') === 'REEMPLAZO' && !empty($d['componente_nuevo'])) {
            $this->actualizarEspecificacionTrasReemplazo(
                (int)$d['id_equipo'],
                $d['tipo_componente'] ?? null,
                $d['componente_nuevo'] ?? null
            );
        }

        if (!empty($d['status_equipo_post'])) {
            $this->execute(
                "UPDATE table_sistemas_equipos SET status_equipo = :st WHERE id_equipo = :id",
                ['st' => $d['status_equipo_post'], 'id' => (int)$d['id_equipo']]
            );
        }

        return $idMant;
    }

    public function deleteMantenimiento(int $id): bool
    {
        return $this->execute("DELETE FROM table_sistemas_mantenimiento WHERE id_mantenimiento = :id", ['id' => $id]);
    }

    public function listarHistorialEquipo(int $idEquipo): array
    {
        $sql = "SELECT h.*, u.usuario_nick
                FROM table_sistemas_equipo_historial h
                LEFT JOIN table_usuarios u ON u.usuario_id = h.usuario_id
                WHERE h.id_equipo = :id_equipo
                ORDER BY h.fecha_registro DESC, h.id_historial DESC";
        return $this->fetchAll($sql, ['id_equipo' => $idEquipo]);
    }

    private function registrarHistorial(int $idEquipo, string $evento, string $detalle, int $userId, int $inst): void
    {
        $this->execute(
            "INSERT INTO table_sistemas_equipo_historial (id_equipo, evento, detalle, usuario_id, id_institucion)
             VALUES (:id_equipo, :evento, :detalle, :user_id, :inst)",
            [
                'id_equipo' => $idEquipo,
                'evento'    => $evento,
                'detalle'   => $detalle,
                'user_id'   => $userId,
                'inst'      => $inst,
            ]
        );
    }

    /* ============================================================
       COMBOS
       ============================================================ */
    public function departamentos(): array
    {
        return $this->fetchAll(
            "SELECT departamento_id AS id, departamento_nombre AS nombre
             FROM table_departamentos
             WHERE departamento_status = 1
             ORDER BY departamento_nombre ASC"
        );
    }

    public function personal(): array
    {
        return $this->fetchAll(
            "SELECT id_personal AS id,
                    TRIM(CONCAT(COALESCE(personal_nombre,''), ' ', COALESCE(personal_apellido,''), ' (', COALESCE(personal_cedula,''), ')')) AS nombre
             FROM table_personal
             WHERE personal_status = 1 OR personal_status IS NULL
             ORDER BY personal_apellido ASC, personal_nombre ASC"
        );
    }
    public function updateMantenimiento(int $idMant, array $d, int $userId, int $inst): bool
    {
        $sql = "UPDATE table_sistemas_mantenimiento SET
                    tipo_intervencion   = :tipo,
                    fecha_intervencion  = :fecha_int,
                    fecha_proximo_mant  = :fecha_prox,
                    tecnico_id          = :tecnico_id,
                    solicitante_nombre  = :solicitante,
                    falla_reportada     = :falla,
                    diagnostico         = :diagnostico,
                    accion_realizada    = :accion,
                    tipo_componente     = :tipo_comp,
                    componente_anterior = :comp_ant,
                    componente_nuevo    = :comp_nue,
                    serial_nuevo        = :serial_nue,
                    estado_intervencion = :estado,
                    observaciones       = :obs
                WHERE id_mantenimiento = :id";

        $ok = $this->execute($sql, [
            'tipo'        => $d['tipo_intervencion'] ?? 'MANTENIMIENTO_PREVENTIVO',
            'fecha_int'   => $d['fecha_intervencion'],
            'fecha_prox'  => !empty($d['fecha_proximo_mant']) ? $d['fecha_proximo_mant'] : null,
            'tecnico_id'  => !empty($d['tecnico_id']) ? (int)$d['tecnico_id'] : null,
            'solicitante' => $d['solicitante_nombre'] ?? null,
            'falla'       => $d['falla_reportada'] ?? null,
            'diagnostico' => $d['diagnostico'],
            'accion'      => $d['accion_realizada'],
            'tipo_comp'   => !empty($d['tipo_componente']) ? $d['tipo_componente'] : null,
            'comp_ant'    => !empty($d['componente_anterior']) ? $d['componente_anterior'] : null,
            'comp_nue'    => !empty($d['componente_nuevo']) ? $d['componente_nuevo'] : null,
            'serial_nue'  => !empty($d['serial_nuevo']) ? $d['serial_nuevo'] : null,
            'estado'      => $d['estado_intervencion'] ?? 'CERRADA',
            'obs'         => $d['observaciones'] ?? null,
            'id'          => $idMant,
        ]);

        if ($ok && !empty($d['id_equipo'])) {
            $idEq = (int)$d['id_equipo'];
            if (($d['tipo_intervencion'] ?? '') === 'REEMPLAZO' && !empty($d['componente_nuevo'])) {
                $this->actualizarEspecificacionTrasReemplazo(
                    $idEq,
                    $d['tipo_componente'] ?? null,
                    $d['componente_nuevo'] ?? null
                );
            }
            if (!empty($d['status_equipo_post'])) {
                $this->execute(
                    "UPDATE table_sistemas_equipos SET status_equipo = :st WHERE id_equipo = :id",
                    ['st' => $d['status_equipo_post'], 'id' => $idEq]
                );
            }
            $this->registrarHistorial(
                $idEq,
                'ACTUALIZACION_MANT',
                "Intervención #{$idMant} actualizada ({$d['estado_intervencion']}): {$d['accion_realizada']}",
                $userId,
                $inst
            );
        }

        return $ok;
    }

    public function cerrarMantenimiento(int $idMant, ?string $notaCierre, bool $activarEquipo, int $userId, int $inst): bool
    {
        $mant = $this->findMantenimiento($idMant);
        if (!$mant) return false;

        $accionActual = trim((string)($mant['accion_realizada'] ?? ''));
        $notaLimpia   = $notaCierre ? strtoupper(trim($notaCierre)) : '';
        $accionFinal  = $notaLimpia !== ''
            ? ($accionActual !== '' ? "{$accionActual} | CIERRE: {$notaLimpia}" : $notaLimpia)
            : $accionActual;

        $ok = $this->execute(
            "UPDATE table_sistemas_mantenimiento
             SET estado_intervencion = 'CERRADA',
                 accion_realizada = :accion
             WHERE id_mantenimiento = :id",
            [
                'accion' => $accionFinal,
                'id'     => $idMant,
            ]
        );

        if ($ok) {
            $idEq = (int)$mant['id_equipo'];
            if ($activarEquipo) {
                $this->execute(
                    "UPDATE table_sistemas_equipos SET status_equipo = 'ACTIVO' WHERE id_equipo = :id",
                    ['id' => $idEq]
                );
            }
            $this->registrarHistorial(
                $idEq,
                'CIERRE_INTERVENCION',
                "Intervención #{$idMant} cerrada." . ($notaLimpia !== '' ? " Nota: {$notaLimpia}" : ''),
                $userId,
                $inst
            );
        }

        return $ok;
    }
}
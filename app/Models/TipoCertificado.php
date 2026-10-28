<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoCertificado extends Model
{
    use SoftDeletes, Auditable;

    public const CAMPOS_QR = [
        'beneficiario.nombre' => 'Participante / solicitante',
        'tipo_certificado.nombre' => 'Tipo de certificado',
        'emision.numero_certificado' => 'Número del certificado',
        'emision.fecha' => 'Fecha de emisión',
        'certificado.fecha_inicio' => 'Inicio de vigencia',
        'certificado.fecha_fin' => 'Vigencia',
        'certificado.descripcion' => 'Descripción de la capacitación o trámite',
        'evaluacion.resultado' => 'Resultado de evaluación',
        'evaluacion.calificaciones' => 'Calificación obtenida',
        'area.nombre' => 'Área responsable',
        'tramite.fecha_registro' => 'Fecha de registro',
        'tramite.funcionario_finalizador' => 'Registrado por',
        'producto.referencia_publica' => 'Productos y presentaciones',
        'estado' => 'Estado del certificado',
    ];

    public function configuracionQr(): array
    {
        if (is_array($this->configuracion_qr)) {
            return $this->configuracion_qr;
        }

        $campos = ['beneficiario.nombre', 'tipo_certificado.nombre', 'emision.numero_certificado', 'emision.fecha', 'estado'];

        return [
            'habilitado' => false,
            'titulo' => 'Verificación de certificado',
            'campos' => array_map(fn ($campo) => ['campo' => $campo, 'etiqueta' => self::CAMPOS_QR[$campo]], $campos),
        ];
    }

    public function filasPublicasQr(array $valores): array
    {
        return collect($this->configuracionQr()['campos'] ?? [])
            ->filter(fn ($fila) => is_array($fila) && array_key_exists($fila['campo'] ?? '', self::CAMPOS_QR))
            ->map(fn ($fila) => [
                'etiqueta' => trim((string) ($fila['etiqueta'] ?? '')) ?: self::CAMPOS_QR[$fila['campo']],
                'valor' => (string) ($valores[$fila['campo']] ?? 'No registrado'),
            ])->values()->all();
    }

    // Define si el tipo genera un documento para toda la solicitud o uno por cada lote registrado.
    public const MODOS_EMISION = [
        'UNICO_POR_TRAMITE' => 'Un certificado por trámite',
        'POR_LOTE' => 'Un certificado por lote',
    ];

    // Valores permitidos para la unidad que compone la vigencia guardada en una sola columna.
    public const UNIDADES_VIGENCIA = [
        'DIA' => 'Días',
        'MES' => 'Meses',
        'ANIO' => 'Años',
        'INDEFINIDA' => 'Indefinida',
    ];

    protected $table = 'tipos_certificados';

    protected $fillable = [
        'configuracion_qr',
        'nombre',
        'id_area',
        'id_funcionario_receptor_inicial',
        'limite_descargas',
        'costo',
        'vigencia',

        'modo_emision',
        'prefijo_numero_certificado',
        'siguiente_correlativo',

        'estado',
    ];

    protected $casts = [
        'configuracion_qr' => 'array',
        'costo' => 'decimal:2',
        'limite_descargas' => 'integer',
        'siguiente_correlativo' => 'integer',
    ];

    // Evita que las vistas repitan los códigos técnicos de la forma de emisión.
    public function textoModoEmision(): string
    {
        return self::MODOS_EMISION[$this->modo_emision] ?? self::MODOS_EMISION['UNICO_POR_TRAMITE'];
    }

    // Convierte la configuración de pantalla en el único valor que se guarda en la base de datos.
    public static function valorVigencia(?int $cantidad, string $unidad): string
    {
        return $unidad === 'INDEFINIDA'
            ? 'INDEFINIDA'
            : ((int) $cantidad).'_'.$unidad;
    }

    // Separa el valor guardado para volver a cargar el formulario de configuración.
    public function datosVigencia(): array
    {
        $vigencia = strtoupper(trim((string) $this->vigencia));

        if ($vigencia === 'INDEFINIDA') {
            return ['cantidad' => null, 'unidad' => 'INDEFINIDA'];
        }

        if (preg_match('/^([1-9][0-9]*)_(DIA|MES|ANIO)$/', $vigencia, $coincidencias)) {
            return ['cantidad' => (int) $coincidencias[1], 'unidad' => $coincidencias[2]];
        }

        return ['cantidad' => null, 'unidad' => null];
    }

    // Muestra la regla con texto natural en lugar del valor técnico almacenado.
    public function textoVigencia(): string
    {
        $datos = $this->datosVigencia();

        if ($datos['unidad'] === 'INDEFINIDA') {
            return 'Indefinida';
        }

        if ($datos['cantidad'] === null || $datos['unidad'] === null) {
            return 'No configurada';
        }

        $unidades = [
            'DIA' => ['día', 'días'],
            'MES' => ['mes', 'meses'],
            'ANIO' => ['año', 'años'],
        ];
        $cantidad = $datos['cantidad'];
        $unidad = $unidades[$datos['unidad']][$cantidad === 1 ? 0 : 1];

        return $cantidad.' '.$unidad;
    }

    // Relación muchos a uno (muchos tipos de certificados pertenecen a un área)
    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area');
    }

    // Funcionario que recibira inicialmente los tramites de este tipo.
    public function funcionarioReceptor()
    {
        return $this->belongsTo(Funcionario::class, 'id_funcionario_receptor_inicial');
    }

    // Relación uno a muchos (un tipo de certificado tiene muchos certificados)
    public function certificados()
    {
        return $this->hasMany(Certificado::class, 'id_tipo_certificado');
    }

    // Relación uno a muchos (un tipo de certificado tiene muchas plantillas)
    public function plantillasCertificados()
    {
        return $this->hasMany(PlantillaCertificado::class, 'id_tipo_certificado');
    }

    // Relación uno a uno (un tipo de certificado tiene una plantilla activa)
    public function plantillaActiva()
    {
        return $this->hasOne(PlantillaCertificado::class, 'id_tipo_certificado')
            ->where('estado', 'ACTIVO');
    }

    // Conserva acceso a la plantilla más reciente aunque haya sido inactivada.
    public function ultimaPlantilla()
    {
        return $this->hasOne(PlantillaCertificado::class, 'id_tipo_certificado')
            ->latestOfMany();
    }

    // Relación uno a muchos (un tipo de certificado tiene muchos requisitos tipos certificados)
    public function tipoCertificadoRequisitos()
    {
        return $this->hasMany(RequisitoTipoCertificado::class, 'id_tipo_certificado')
            ->orderBy('orden')
            ->orderBy('id');
    }

    // Relación muchos a muchos (muchos tipos de certificados pertenecen a muchos requisitos)
    public function requisitos()
    {
        return $this->belongsToMany(
            Requisito::class,
            'requisitos_tipos_certificados',
            'id_tipo_certificado',
            'id_requisito'
        )
            ->withPivot('id', 'id_tipo_evidencia', 'orden', 'estado')
            ->withTimestamps()
            ->wherePivotNull('deleted_at')
            ->orderByPivot('orden')
            ->orderByPivot('id');
    }

    // Relación uno a muchos (un tipo de certificado requerido tiene muchas dependencias)
    public function dependenciasDondeEsRequerido()
    {
        return $this->hasMany(DependenciaRequisito::class, 'id_tipo_certificado_requerido');
    }
}

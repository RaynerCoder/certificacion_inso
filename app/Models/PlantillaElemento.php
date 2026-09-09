<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;

class PlantillaElemento extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'plantillas_elementos';

    protected $fillable = [
        'id_plantilla_certificado',
        'tipo_elemento',
        'codigo_elemento',
        'texto_fijo',
        'pagina',
        'posicion_x',
        'posicion_y',
        'ancho',
        'alto',
        'tamano_letra',
        'alineacion',
        'padding_x',
        'padding_y',
        'interlineado',
        'negrita',
        'cursiva',
        'subrayado',
        'color_texto',
        'tipo_letra',
        'z_index',
        'configuracion',
        'orden',
        'estado',
    ];

    protected $casts = [
        'configuracion' => 'array',
        'negrita' => 'boolean',
        'cursiva' => 'boolean',
        'subrayado' => 'boolean',
    ];

    // Relación muchos a uno (muchos elementos pertenecen a una plantilla)
    public function plantillaCertificado()
    {
        return $this->belongsTo(PlantillaCertificado::class, 'id_plantilla_certificado');
    }

    // Relación uno a muchos (un elemento tipo tabla tiene muchas columnas)
    public function columnas()
    {
        return $this->hasMany(PlantillaColumna::class, 'id_plantilla_elemento')
            ->orderBy('orden');
    }

    /**
     * Convierte los estilos por palabra del editor en HTML seguro para vistas y PDF.
     */
    public function textoConEstilos(?string $texto = null): HtmlString
    {
        $texto ??= (string) $this->texto_fijo;
        $estilos = data_get($this->configuracion, 'estilos_texto', []);

        return $this->renderizarTextoConEstilos($texto, is_array($estilos) ? $estilos : []);
    }

    /**
     * Sustituye los marcadores conservando el formato exacto de cada fragmento.
     */
    public function textoConEstilosResueltos(array $valores): HtmlString
    {
        $plantilla = (string) $this->texto_fijo;
        $estilos = data_get($this->configuracion, 'estilos_texto', []);
        [$texto, $estilosResueltos] = $this->resolverMarcadoresConEstilos(
            $plantilla,
            is_array($estilos) ? $estilos : [],
            $valores
        );

        return $this->renderizarTextoConEstilos($texto, $estilosResueltos);
    }

    private function renderizarTextoConEstilos(string $texto, array $estilos): HtmlString
    {

        if ($estilos === []) {
            // nl2br conserva también el salto original; con white-space: pre-wrap eso crea líneas dobles.
            return new HtmlString((string) preg_replace('/\R/u', '<br>', e($texto)));
        }

        $lineas = preg_split('/\R/u', $texto) ?: [''];
        $html = [];

        foreach ($lineas as $indiceLinea => $linea) {
            $caracteres = preg_split('//u', $linea, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $segmentos = [];
            $textoSegmento = '';
            $estiloAnterior = null;

            foreach ($caracteres as $indiceCaracter => $caracter) {
                $estilo = is_array($estilos[$indiceLinea][$indiceCaracter] ?? null)
                    ? $estilos[$indiceLinea][$indiceCaracter]
                    : [];
                $claveEstilo = json_encode($estilo);

                if ($estiloAnterior !== null && $claveEstilo !== $estiloAnterior) {
                    $segmentos[] = $this->envolverSegmentoTexto($textoSegmento, json_decode($estiloAnterior, true) ?: []);
                    $textoSegmento = '';
                }

                $textoSegmento .= $caracter;
                $estiloAnterior = $claveEstilo;
            }

            if ($textoSegmento !== '' || $caracteres === []) {
                $segmentos[] = $this->envolverSegmentoTexto($textoSegmento, json_decode($estiloAnterior ?: '[]', true) ?: []);
            }
            $html[] = implode('', $segmentos);
        }

        return new HtmlString(implode('<br>', $html));
    }

    /**
     * Fabric.js relaciona los estilos con índices de caracteres. Esta conversión
     * evita que un valor dinámico más corto o largo desplace el formato siguiente.
     *
     * @return array{0: string, 1: array}
     */
    private function resolverMarcadoresConEstilos(string $texto, array $estilos, array $valores): array
    {
        $plantilla = (string) preg_replace('/\R/u', "\n", $texto);
        $originales = $this->caracteresConEstilo($plantilla, $estilos);
        $resultado = [];
        $cursorCaracter = 0;

        preg_match_all('/\{\{\s*([^}]+)\s*\}\}/u', $plantilla, $coincidencias, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($coincidencias as $coincidencia) {
            $marcador = $coincidencia[0][0];
            $inicioBytes = $coincidencia[0][1];
            $inicio = mb_strlen(substr($plantilla, 0, $inicioBytes));
            $longitud = mb_strlen($marcador);
            $clave = trim($coincidencia[1][0]);

            $resultado = array_merge(
                $resultado,
                array_slice($originales, $cursorCaracter, $inicio - $cursorCaracter)
            );

            if (array_key_exists($clave, $valores)) {
                $estilo = $this->estiloRepresentativo(array_slice($originales, $inicio, $longitud));
                $caracteresValor = preg_split('//u', (string) ($valores[$clave] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                foreach ($caracteresValor as $caracter) {
                    $resultado[] = [
                        'valor' => $caracter,
                        'estilo' => $caracter === "\n" ? [] : $estilo,
                    ];
                }
            } else {
                $resultado = array_merge($resultado, array_slice($originales, $inicio, $longitud));
            }

            $cursorCaracter = $inicio + $longitud;
        }

        $resultado = array_merge($resultado, array_slice($originales, $cursorCaracter));

        return [
            implode('', array_column($resultado, 'valor')),
            $this->estilosDesdeCaracteres($resultado),
        ];
    }

    private function caracteresConEstilo(string $texto, array $estilos): array
    {
        $caracteres = preg_split('//u', $texto, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $resultado = [];
        $linea = 0;
        $indiceCaracter = 0;

        foreach ($caracteres as $caracter) {
            $resultado[] = [
                'valor' => $caracter,
                'estilo' => $caracter === "\n" || ! is_array($estilos[$linea][$indiceCaracter] ?? null)
                    ? []
                    : $estilos[$linea][$indiceCaracter],
            ];

            if ($caracter === "\n") {
                $linea++;
                $indiceCaracter = 0;
            } else {
                $indiceCaracter++;
            }
        }

        return $resultado;
    }

    private function estiloRepresentativo(array $tokens): array
    {
        $frecuencias = [];

        foreach ($tokens as $token) {
            $estilo = is_array($token['estilo'] ?? null) ? $token['estilo'] : [];
            if ($estilo === []) {
                continue;
            }

            $clave = json_encode($estilo);
            $frecuencias[$clave] ??= ['cantidad' => 0, 'estilo' => $estilo];
            $frecuencias[$clave]['cantidad']++;
        }

        usort($frecuencias, fn ($primero, $segundo) => $segundo['cantidad'] <=> $primero['cantidad']);

        return $frecuencias[0]['estilo'] ?? [];
    }

    private function estilosDesdeCaracteres(array $tokens): array
    {
        $estilos = [];
        $linea = 0;
        $indiceCaracter = 0;

        foreach ($tokens as $token) {
            if (($token['valor'] ?? '') === "\n") {
                $linea++;
                $indiceCaracter = 0;
                continue;
            }

            $estilo = is_array($token['estilo'] ?? null) ? $token['estilo'] : [];
            if ($estilo !== []) {
                $estilos[$linea][$indiceCaracter] = $estilo;
            }
            $indiceCaracter++;
        }

        return $estilos;
    }

    private function envolverSegmentoTexto(string $texto, array $estilo): string
    {
        $declaraciones = [];
        if (in_array($estilo['fontWeight'] ?? null, ['bold', 'normal'], true)) {
            $declaraciones[] = 'font-weight:'.($estilo['fontWeight'] === 'bold' ? '700' : '400');
        }
        if (in_array($estilo['fontStyle'] ?? null, ['italic', 'normal'], true)) {
            $declaraciones[] = 'font-style:'.$estilo['fontStyle'];
        }
        if (array_key_exists('underline', $estilo) && is_bool($estilo['underline'])) {
            $declaraciones[] = 'text-decoration:'.($estilo['underline'] ? 'underline' : 'none');
        }

        $contenido = e($texto);

        return $declaraciones === []
            ? $contenido
            : '<span style="'.implode(';', $declaraciones).'">'.$contenido.'</span>';
    }
}

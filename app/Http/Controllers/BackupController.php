<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class BackupController extends Controller
{
    /**
     * Detecta la ruta al directorio bin de PostgreSQL instalado en Windows.
     * Busca primero en la versión configurada, luego en versiones comunes.
     */
    private function getPgBinPath(): string
    {
        // Ruta conocida en este servidor (PostgreSQL 17)
        $knownPath = 'C:\\Program Files\\PostgreSQL\\18\\bin';
        if (file_exists($knownPath.'\\pg_dump.exe')) {
            return $knownPath;
        }

        // Intentar detectar otras versiones instaladas (16, 15, 14, 13)
        foreach ([18,16, 15, 14, 13] as $version) {
            $path = "C:\\Program Files\\PostgreSQL\\{$version}\\bin";
            if (file_exists($path.'\\pg_dump.exe')) {
                return $path;
            }
        }

        // Si no se encontró ruta absoluta, intentar desde el PATH del sistema
        return '';
    }

    // Mostrar lista de copias de seguridad (CU-11)
    public function index()
    {
        // Restricción de seguridad RNF-08: Solo administrador
        if (auth()->user() && auth()->user()->rol !== 'Administrador') {
            abort(403, 'Acceso denegado.');
        }

        $directorio = storage_path('app/public/backups');
        $backups = [];

        if (file_exists($directorio)) {
            $archivos = scandir($directorio);
            foreach ($archivos as $archivo) {
                if ($archivo !== '.' && $archivo !== '..') {
                    $rutaCompleta = $directorio.DIRECTORY_SEPARATOR.$archivo;
                    $backups[] = [
                        'nombre' => $archivo,
                        'tamano' => round(filesize($rutaCompleta) / 1024, 2).' KB',
                        'fecha' => date('Y-m-d H:i:s', filemtime($rutaCompleta)),
                        'url' => asset('storage/backups/'.$archivo),
                    ];
                }
            }
        }

        // Ordenar backups por fecha descendente (más nuevos primero)
        usort($backups, fn ($a, $b) => strcmp($b['fecha'], $a['fecha']));

        return Inertia::render('Backup/Index', [
            'backups' => $backups,
        ]);
    }

    // Genera un respaldo manual de la base de datos en PostgreSQL (CU-11)
    public function crearBackup()
    {
        // Restricción de seguridad RNF-08: Validar que sea administrador
        if (auth()->user() && auth()->user()->rol !== 'Administrador') {
            return back()->withErrors(['error' => 'Acceso denegado. Solo los administradores pueden realizar respaldos.']);
        }

        $nombreArchivo = 'backup_'.date('Y-m-d_H-i-s').'.sql';

        $dbDatabase = env('DB_DATABASE');
        $dbUser = env('DB_USERNAME');
        $dbPassword = trim(env('DB_PASSWORD'));
        $dbHost = env('DB_HOST', '127.0.0.1');
        $dbPort = env('DB_PORT', '5432');

        $dirBackups = storage_path('app/public/backups');
        $rutaDestino = $dirBackups.DIRECTORY_SEPARATOR.$nombreArchivo;

        if (! file_exists($dirBackups)) {
            mkdir($dirBackups, 0755, true);
        }

        $pgBin = $this->getPgBinPath();
        $pgDump = $pgBin ? '"'.$pgBin.'\\pg_dump.exe"' : 'pg_dump';

        // Usamos putenv() para pasar PGPASSWORD de forma segura al proceso hijo
        // (funciona correctamente con Apache/XAMPP en Windows sin depender de cmd.exe)
        putenv('PGPASSWORD='.$dbPassword);

        // -F p (plain SQL) para mayor compatibilidad; --no-password evita prompt interactivo
        $comando = "{$pgDump} -h {$dbHost} -p {$dbPort} -U {$dbUser} -d {$dbDatabase} -F p --no-password -f \"{$rutaDestino}\" 2>&1";

        exec($comando, $output, $resultCode);

        putenv('PGPASSWORD='); // Limpiar la variable de entorno por seguridad

        if ($resultCode === 0) {
            return redirect()->route('backup.index')->with('success', 'Respaldo generado con éxito: '.$nombreArchivo);
        }

        $detalleError = implode("\n", $output);

        return back()->withErrors(['error' => 'Error al generar backup. Detalle: '.$detalleError]);
    }

    // Restaura una copia de seguridad seleccionada o cargada (CU-11 A3)
    public function restaurarBackup(Request $request)
    {
        // Restricción de seguridad RNF-08: Validar que sea administrador
        if (auth()->user() && auth()->user()->rol !== 'Administrador') {
            return back()->withErrors(['error' => 'Acceso denegado. Solo los administradores pueden restaurar datos.']);
        }

        $dbDatabase = env('DB_DATABASE');
        $dbUser = env('DB_USERNAME');
        $dbPassword = trim(env('DB_PASSWORD'));
        $dbHost = env('DB_HOST', '127.0.0.1');
        $dbPort = env('DB_PORT', '5432');

        $rutaArchivo = '';

        if ($request->hasFile('archivo_subido')) {
            // Si el usuario subió un archivo externo para restaurar
            $request->validate([
                'archivo_subido' => ['required', 'file'],
            ]);

            $archivo = $request->file('archivo_subido');
            $extension = $archivo->getClientOriginalExtension();

            // Validar extensión (.sql o .backup)
            if ($extension !== 'sql' && $extension !== 'backup') {
                return back()->withErrors(['error' => 'El tipo de archivo de restauración debe ser .sql o .backup (A3).']);
            }

            $nombreTmp = 'upload_restore_'.time().'.'.$extension;
            $rutaRelativa = $archivo->storeAs('temp_restores', $nombreTmp, 'local');
            $rutaArchivo = storage_path('app/'.$rutaRelativa);
        } elseif ($request->has('nombre_archivo')) {
            // Si seleccionó un archivo ya existente en el servidor
            $nombreArchivo = $request->input('nombre_archivo');
            $rutaArchivo = storage_path('app/public/backups/'.$nombreArchivo);

            if (! file_exists($rutaArchivo)) {
                return back()->withErrors(['error' => 'El archivo seleccionado no existe en el almacenamiento del sistema.']);
            }
        } else {
            return back()->withErrors(['error' => 'Debe seleccionar un archivo para ejecutar la restauración.']);
        }

        $pgBin = $this->getPgBinPath();
        $extension = pathinfo($rutaArchivo, PATHINFO_EXTENSION);

        // Setear PGPASSWORD vía putenv() para que Apache/XAMPP lo pase correctamente al proceso hijo
        putenv('PGPASSWORD='.$dbPassword);

        if ($extension === 'sql') {
            // Archivo plain SQL: usar psql para restaurar
            $psql = $pgBin ? '"'.$pgBin.'\\psql.exe"' : 'psql';
            $comando = "{$psql} -h {$dbHost} -p {$dbPort} -U {$dbUser} -d {$dbDatabase} -f \"{$rutaArchivo}\" 2>&1";
        } else {
            // Archivo formato custom (.backup): usar pg_restore
            $pgRestore = $pgBin ? '"'.$pgBin.'\\pg_restore.exe"' : 'pg_restore';
            $comando = "{$pgRestore} -h {$dbHost} -p {$dbPort} -U {$dbUser} -d {$dbDatabase} -c -v \"{$rutaArchivo}\" 2>&1";
        }

        exec($comando, $output, $resultCode);

        putenv('PGPASSWORD='); // Limpiar la variable de entorno por seguridad

        // Limpiar archivo temporal si fue subido
        if ($request->hasFile('archivo_subido') && file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }

        // psql y pg_restore retornan 0 en éxito; pg_restore puede retornar 1 con advertencias menores
        if ($resultCode === 0 || ($extension !== 'sql' && $resultCode === 1)) {
            return redirect()->route('backup.index')->with('success', 'Base de datos restaurada con éxito.');
        }

        $detalleError = implode("\n", $output);

        return back()->withErrors(['error' => 'Error al restaurar base de datos: '.$detalleError]);
    }
}

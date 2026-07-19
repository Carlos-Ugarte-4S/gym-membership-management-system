<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Turno;
use App\Models\Persona;
use App\Models\Usuario;
use App\Models\Cliente;
use App\Models\TipoMembresia;
use App\Models\Promocion;
use App\Models\Membresia;
use App\Models\Pago;
use App\Models\Transferencia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InitialSystemSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Limpiar tablas existentes en orden de dependencia
        Transferencia::query()->delete();
        Pago::query()->delete();
        Membresia::query()->delete();
        Cliente::query()->delete();
        Usuario::query()->delete();
        Persona::query()->delete();
        Turno::query()->delete();
        TipoMembresia::query()->delete();
        Promocion::query()->delete();

        // Reset secuencias de IDs incrementales si es PostgreSQL
        $tables = [
            'transferencias',
            'pagos',
            'membresias',
            'clientes',
            'usuarios',
            'personas',
            'turnos',
            'tipo_membresias',
            'promociones'
        ];
        
        foreach ($tables as $table) {
            try {
                $seqName = $table . '_' . $this->getPrimaryKeyName($table) . '_seq';
                DB::statement("ALTER SEQUENCE IF EXISTS {$seqName} RESTART WITH 1");
            } catch (\Exception $e) {
                // Ignorar en SQLite/Testing
            }
        }

        // 1. Crear Turnos
        $turnoManana = Turno::create([
            'nombre_turno' => 'Mañana',
            'hora_inicio' => '06:00:00',
            'hora_fin' => '12:00:00'
        ]);

        $turnoTarde = Turno::create([
            'nombre_turno' => 'Tarde',
            'hora_inicio' => '12:00:00',
            'hora_fin' => '18:00:00'
        ]);

        $turnoNoche = Turno::create([
            'nombre_turno' => 'Noche',
            'hora_inicio' => '18:00:00',
            'hora_fin' => '22:00:00'
        ]);

        // 2. Crear Promociones
        $promoSin = Promocion::create([
            'nombre_promocion' => 'Sin Promoción',
            'porcentaje_descuento' => 0,
            'descripcion' => 'Precio regular sin descuentos',
            'fecha_inicio' => Carbon::now()->subYears(5)->toDateString(),
            'fecha_fin' => Carbon::now()->addYears(5)->toDateString(),
            'estado' => true
        ]);

        $promoEstudiante = Promocion::create([
            'nombre_promocion' => 'Descuento Estudiante',
            'porcentaje_descuento' => 10,
            'descripcion' => '10% de descuento para estudiantes',
            'fecha_inicio' => Carbon::now()->subMonths(2)->toDateString(),
            'fecha_fin' => Carbon::now()->addMonths(6)->toDateString(),
            'estado' => true
        ]);

        $promoInvierno = Promocion::create([
            'nombre_promocion' => 'Promoción de Invierno',
            'porcentaje_descuento' => 20,
            'descripcion' => '20% de descuento especial por temporada fría',
            'fecha_inicio' => Carbon::now()->subDays(5)->toDateString(),
            'fecha_fin' => Carbon::now()->addDays(20)->toDateString(),
            'estado' => true
        ]);

        $promoExpirada = Promocion::create([
            'nombre_promocion' => 'Apertura de Sucursal',
            'porcentaje_descuento' => 30,
            'descripcion' => 'Descuento del 30% por inauguración',
            'fecha_inicio' => Carbon::now()->subMonths(4)->toDateString(),
            'fecha_fin' => Carbon::now()->subMonths(3)->toDateString(),
            'estado' => false
        ]);

        // 3. Crear Tipos de Membresía
        $tipoDiario = TipoMembresia::create([
            'nombre_membresia' => 'Pase Diario',
            'duracion_dias' => 1,
            'precio' => 5.00,
            'estado' => true
        ]);

        $tipoMensual = TipoMembresia::create([
            'nombre_membresia' => 'Plan Mensual Estándar',
            'duracion_dias' => 30,
            'precio' => 35.00,
            'estado' => true
        ]);

        $tipoTrimestral = TipoMembresia::create([
            'nombre_membresia' => 'Plan Trimestral Pro',
            'duracion_dias' => 90,
            'precio' => 90.00,
            'estado' => true
        ]);

        $tipoSemestral = TipoMembresia::create([
            'nombre_membresia' => 'Plan Semestral Fuerza',
            'duracion_dias' => 180,
            'precio' => 160.00,
            'estado' => true
        ]);

        $tipoAnual = TipoMembresia::create([
            'nombre_membresia' => 'Plan Anual VIP',
            'duracion_dias' => 365,
            'precio' => 300.00,
            'estado' => true
        ]);

        // 4. Crear Personas y Usuarios (Administrador y Recepcionista)
        $pAdmin = Persona::create([
            'ci' => '0000000',
            'nombre' => 'Admin',
            'apellido' => 'Sistema',
            'telefono' => '70000000',
            'direccion' => 'Central Eagle Gym',
            'sexo' => 'M',
            'fecha_registro' => Carbon::now()->subMonths(6)->toDateString()
        ]);

        $uAdmin = Usuario::create([
            'id_persona' => $pAdmin->id_persona,
            'id_turno' => $turnoManana->id_turno,
            'email' => 'admin@gym.com',
            'contrasenia' => Hash::make('admin123'),
            'login' => 'admin',
            'rol' => 'Administrador',
            'fecha_contrato' => Carbon::now()->subMonths(6)->toDateString(),
            'estado' => true
        ]);

        $pRecep = Persona::create([
            'ci' => '8765432',
            'nombre' => 'Camila',
            'apellido' => 'Vargas',
            'telefono' => '78912345',
            'direccion' => 'Av. San Martín #123',
            'sexo' => 'F',
            'fecha_registro' => Carbon::now()->subMonths(3)->toDateString()
        ]);

        $uRecep = Usuario::create([
            'id_persona' => $pRecep->id_persona,
            'id_turno' => $turnoTarde->id_turno,
            'email' => 'recepcionista@gym.com',
            'contrasenia' => Hash::make('recep123'),
            'login' => 'recepcionista',
            'rol' => 'Recepcionista',
            'fecha_contrato' => Carbon::now()->subMonths(3)->toDateString(),
            'estado' => true
        ]);

        // 5. Crear Personas y Clientes
        // Cliente 1: Carlos Mendoza (Activo, Plan Mensual sin promo)
        $pClient1 = Persona::create([
            'ci' => '1234567',
            'nombre' => 'Carlos',
            'apellido' => 'Mendoza',
            'telefono' => '71234567',
            'direccion' => 'Av. Bush #456',
            'sexo' => 'M',
            'fecha_registro' => Carbon::now()->subDays(10)->toDateString()
        ]);
        $cClient1 = Cliente::create([
            'id_persona' => $pClient1->id_persona,
            'fecha_limite' => Carbon::now()->addDays(20)->toDateString(),
            'estado' => true
        ]);

        // Cliente 2: Ana Gomez (Activa, Plan Mensual con descuento estudiante)
        $pClient2 = Persona::create([
            'ci' => '2345678',
            'nombre' => 'Ana',
            'apellido' => 'Gomez',
            'telefono' => '72345678',
            'direccion' => 'Calle Sucre #12',
            'sexo' => 'F',
            'fecha_registro' => Carbon::now()->subDays(15)->toDateString()
        ]);
        $cClient2 = Cliente::create([
            'id_persona' => $pClient2->id_persona,
            'fecha_limite' => Carbon::now()->addDays(15)->toDateString(),
            'estado' => true
        ]);

        // Cliente 3: Jorge Salinas (Vencido)
        $pClient3 = Persona::create([
            'ci' => '3456789',
            'nombre' => 'Jorge',
            'apellido' => 'Salinas',
            'telefono' => '73456789',
            'direccion' => 'Av. Banzer #789',
            'sexo' => 'M',
            'fecha_registro' => Carbon::now()->subMonths(2)->toDateString()
        ]);
        $cClient3 = Cliente::create([
            'id_persona' => $pClient3->id_persona,
            'fecha_limite' => Carbon::now()->subDays(5)->toDateString(),
            'estado' => true
        ]);

        // Cliente 4: Mariana Rios (Activa, Plan Trimestral con promo invierno)
        $pClient4 = Persona::create([
            'ci' => '4567890',
            'nombre' => 'Mariana',
            'apellido' => 'Rios',
            'telefono' => '74567890',
            'direccion' => 'Av. Landivar #11',
            'sexo' => 'F',
            'fecha_registro' => Carbon::now()->subDays(5)->toDateString()
        ]);
        $cClient4 = Cliente::create([
            'id_persona' => $pClient4->id_persona,
            'fecha_limite' => Carbon::now()->addDays(85)->toDateString(),
            'estado' => true
        ]);

        // Cliente 5: Sebastian Pinto (Activo, Plan Anual sin promo, pagado con Efectivo)
        $pClient5 = Persona::create([
            'ci' => '5678901',
            'nombre' => 'Sebastian',
            'apellido' => 'Pinto',
            'telefono' => '75678901',
            'direccion' => 'Calle Murillo #88',
            'sexo' => 'M',
            'fecha_registro' => Carbon::now()->subDays(30)->toDateString()
        ]);
        $cClient5 = Cliente::create([
            'id_persona' => $pClient5->id_persona,
            'fecha_limite' => Carbon::now()->addDays(335)->toDateString(),
            'estado' => true
        ]);

        // Cliente 6: Gabriela Ortiz (Vencido hace tiempo)
        $pClient6 = Persona::create([
            'ci' => '6789012',
            'nombre' => 'Gabriela',
            'apellido' => 'Ortiz',
            'telefono' => '76789012',
            'direccion' => 'Av. Ejercito #123',
            'sexo' => 'F',
            'fecha_registro' => Carbon::now()->subMonths(3)->toDateString()
        ]);
        $cClient6 = Cliente::create([
            'id_persona' => $pClient6->id_persona,
            'fecha_limite' => Carbon::now()->subMonths(2)->toDateString(),
            'estado' => true
        ]);

        // 6. Crear Membresías y Pagos
        // Membresía 1 (Carlos Mendoza): Plan Mensual, Sin Promo, $35
        $m1 = Membresia::create([
            'id_cliente' => $cClient1->id_cliente,
            'id_tipo_membresia' => $tipoMensual->id_tipo_membresia,
            'id_promocion' => $promoSin->id_promocion,
            'fecha_inicio' => Carbon::now()->subDays(10)->toDateString(),
            'fecha_vencimiento' => Carbon::now()->addDays(20)->toDateString()
        ]);
        Pago::create([
            'id_usuario' => $uAdmin->id_usuario,
            'id_membresia' => $m1->id_membresia,
            'monto' => 35.00,
            'fecha_pago' => Carbon::now()->subDays(10)->toDateString(),
            'metodo_pago' => 'Efectivo'
        ]);

        // Membresía 2 (Ana Gomez): Plan Mensual, Descuento Estudiante (10%), $31.50
        $m2 = Membresia::create([
            'id_cliente' => $cClient2->id_cliente,
            'id_tipo_membresia' => $tipoMensual->id_tipo_membresia,
            'id_promocion' => $promoEstudiante->id_promocion,
            'fecha_inicio' => Carbon::now()->subDays(15)->toDateString(),
            'fecha_vencimiento' => Carbon::now()->addDays(15)->toDateString()
        ]);
        $p2 = Pago::create([
            'id_usuario' => $uRecep->id_usuario,
            'id_membresia' => $m2->id_membresia,
            'monto' => 31.50,
            'fecha_pago' => Carbon::now()->subDays(15)->toDateString(),
            'metodo_pago' => 'Transferencia'
        ]);
        Transferencia::create([
            'id_pago' => $p2->id_pago,
            'banco_origen' => 'Banco Mercantil Santa Cruz',
            'banco_destino' => 'Banco Unión',
            'cuenta_destino' => '10000045612',
            'fecha_transferencia' => Carbon::now()->subDays(15)->toDateString(),
            'codigo_transaccion' => 'TR-9823412',
            'comprobante_foto' => 'comprobantes/test_ana.png',
            'estado' => '1'
        ]);

        // Membresía 3 (Jorge Salinas): Plan Mensual Vencido, $35
        $m3 = Membresia::create([
            'id_cliente' => $cClient3->id_cliente,
            'id_tipo_membresia' => $tipoMensual->id_tipo_membresia,
            'id_promocion' => $promoSin->id_promocion,
            'fecha_inicio' => Carbon::now()->subDays(35)->toDateString(),
            'fecha_vencimiento' => Carbon::now()->subDays(5)->toDateString()
        ]);
        Pago::create([
            'id_usuario' => $uRecep->id_usuario,
            'id_membresia' => $m3->id_membresia,
            'monto' => 35.00,
            'fecha_pago' => Carbon::now()->subDays(35)->toDateString(),
            'metodo_pago' => 'Efectivo'
        ]);

        // Membresía 4 (Mariana Rios): Plan Trimestral, Promo Invierno (20%), $72.00
        $m4 = Membresia::create([
            'id_cliente' => $cClient4->id_cliente,
            'id_tipo_membresia' => $tipoTrimestral->id_tipo_membresia,
            'id_promocion' => $promoInvierno->id_promocion,
            'fecha_inicio' => Carbon::now()->subDays(5)->toDateString(),
            'fecha_vencimiento' => Carbon::now()->addDays(85)->toDateString()
        ]);
        $p4 = Pago::create([
            'id_usuario' => $uAdmin->id_usuario,
            'id_membresia' => $m4->id_membresia,
            'monto' => 72.00,
            'fecha_pago' => Carbon::now()->subDays(5)->toDateString(),
            'metodo_pago' => 'Transferencia'
        ]);
        Transferencia::create([
            'id_pago' => $p4->id_pago,
            'banco_origen' => 'Banco Nacional de Bolivia',
            'banco_destino' => 'Banco Unión',
            'cuenta_destino' => '10000045612',
            'fecha_transferencia' => Carbon::now()->subDays(5)->toDateString(),
            'codigo_transaccion' => 'TR-8812349',
            'comprobante_foto' => 'comprobantes/test_mariana.png',
            'estado' => '1'
        ]);

        // Membresía 5 (Sebastian Pinto): Plan Anual VIP, Sin Promo, $300.00
        $m5 = Membresia::create([
            'id_cliente' => $cClient5->id_cliente,
            'id_tipo_membresia' => $tipoAnual->id_tipo_membresia,
            'id_promocion' => $promoSin->id_promocion,
            'fecha_inicio' => Carbon::now()->subDays(30)->toDateString(),
            'fecha_vencimiento' => Carbon::now()->addDays(335)->toDateString()
        ]);
        Pago::create([
            'id_usuario' => $uAdmin->id_usuario,
            'id_membresia' => $m5->id_membresia,
            'monto' => 300.00,
            'fecha_pago' => Carbon::now()->subDays(30)->toDateString(),
            'metodo_pago' => 'Efectivo'
        ]);

        // Membresía 6 (Gabriela Ortiz): Pase Diario Vencido, $5.00
        $m6 = Membresia::create([
            'id_cliente' => $cClient6->id_cliente,
            'id_tipo_membresia' => $tipoDiario->id_tipo_membresia,
            'id_promocion' => $promoSin->id_promocion,
            'fecha_inicio' => Carbon::now()->subMonths(3)->toDateString(),
            'fecha_vencimiento' => Carbon::now()->subMonths(3)->addDays(1)->toDateString()
        ]);
        Pago::create([
            'id_usuario' => $uRecep->id_usuario,
            'id_membresia' => $m6->id_membresia,
            'monto' => 5.00,
            'fecha_pago' => Carbon::now()->subMonths(3)->toDateString(),
            'metodo_pago' => 'Efectivo'
        ]);

        $this->command->info('Base de datos Eagle Gym inicializada con éxito.');
        $this->command->info('Usuario Administrador: admin / admin123');
        $this->command->info('Usuario Recepcionista: recepcionista / recep123');
    }

    private function getPrimaryKeyName(string $table): string
    {
        switch ($table) {
            case 'personas':
                return 'id_persona';
            case 'usuarios':
                return 'id_usuario';
            case 'clientes':
                return 'id_cliente';
            case 'turnos':
                return 'id_turno';
            case 'tipo_membresias':
                return 'id_tipo_membresia';
            case 'promociones':
                return 'id_promocion';
            case 'membresias':
                return 'id_membresia';
            case 'pagos':
                return 'id_pago';
            case 'transferencias':
                return 'id_transferencia';
            default:
                return 'id';
        }
    }
}

<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const userRole = computed(() => page.props.auth.user?.rol);

const breadcrumbs = [{ title: 'Dashboard', href: '/dashboard' }];

const filtroReporte = ref({
    fecha_desde: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0], // Primer día del mes
    fecha_hasta: new Date().toISOString().split('T')[0], // Hoy
});

const reporteDatos = ref(null);
const procesandoBackup = ref(false);
const urlDescargaBackup = ref('');

// Consulta asíncrona usando Axios para no refrescar toda la interfaz de Inertia
const consultarEstadisticas = async () => {
    try {
        const respuesta = await axios.get('/admin/reportes/financiero', { params: filtroReporte.value });
        reporteDatos.value = respuesta.data;
    } catch (error) {
        alert('Error al compilar los reportes del periodo.');
    }
};

// Dispara la ejecución del comando pg_dump en el servidor de Laravel
const ejecutarRespaldoBaseDatos = async () => {
    if (confirm('¿Desea realizar el respaldo manual de la base de datos ahora mismo?')) {
        procesandoBackup.value = true;
        urlDescargaBackup.value = '';
        try {
            const respuesta = await axios.post('/admin/backup/crear');
            alert(respuesta.data.message);
            urlDescargaBackup.value = respuesta.data.descargar_url;
        } catch (error) {
            alert('Error crítico: El comando del sistema falló. Verifique los privilegios de PostgreSQL.');
        } finally {
            procesandoBackup.value = false;
        }
    }
};

// Variables reactivas para el buscador rápido de membresías
const busquedaCliente = ref('');
const resultadosClientes = ref([]);
const buscando = ref(false);
const clientes = ref([]);

// Realiza una petición asíncrona para buscar clientes y verificar el estado de su membresía
const buscarMembresiaCliente = async () => {
    if (!busquedaCliente.value.trim()) {
        resultadosClientes.value = [];
        return;
    }
    buscando.value = true;
    try {
        const respuesta = await axios.get('/dashboard/buscar-cliente', { params: { buscar: busquedaCliente.value } });
        resultadosClientes.value = respuesta.data;
    } catch (error) {
        alert('Error al buscar el cliente y su membresía.');
    } finally {
        buscando.value = false;
    }
};

const diasRestantes = (fechaVencimiento) => {
    if (!fechaVencimiento) return 0;

    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);

    const vence = new Date(fechaVencimiento);
    vence.setHours(0, 0, 0, 0);

    return Math.ceil((vence - hoy) / (1000 * 60 * 60 * 24));
};

const colorEstado = (cliente) => {
    const fecha = cliente.ultima_membresia?.fecha_vencimiento;

    if (!fecha) return 'bg-gray-500';

    const dias = diasRestantes(fecha);

    // Próximo a vencer
    if (dias <= 3) return 'bg-red-600';

    if (dias <= 15) return 'bg-yellow-500';

    return 'bg-green-600';
};

const formatoFecha = (fecha) => {
    if (!fecha) return '';

    const f = new Date(fecha);

    return `${f.getFullYear()}-${String(f.getMonth() + 1).padStart(2, '0')}-${String(f.getDate()).padStart(2, '0')}`;
};

const mostrarAuditoria = ref(false);
const mostrarSeguridad = ref(false);

const toggleAuditoria = () => {
    mostrarAuditoria.value = !mostrarAuditoria.value;
};

const toggleSeguridad = () => {
    mostrarSeguridad.value = !mostrarSeguridad.value;
};

const cargarClientes = async () => {
    try {
        const res = await axios.get('/dashboard/clientes');
        clientes.value = res.data;
    } catch (error) {
        console.log(error);
    }
};

onMounted(() => {
    cargarClientes();
});

const filtroEstado = ref('todos');

const clientesFiltrados = computed(() => {
    const lista = busquedaCliente.value.trim() ? resultadosClientes.value : clientes.value;

    // Mostrar solamente membresías activas
    return lista.filter((cliente) => cliente.es_activa);
});

const esRecepcionista = computed(() => userRole.value === 'Recepcionista');

const tema = computed(() => ({
    banner: esRecepcionista.value
        ? 'bg-gradient-to-r from-sky-600 to-cyan-700 border-sky-500/20'
        : 'bg-gradient-to-r from-orange-600 to-red-700 border-orange-500/20',

    boton: esRecepcionista.value
        ? 'bg-gradient-to-r from-sky-500 to-cyan-600 hover:from-sky-600 hover:to-cyan-700'
        : 'bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700',

    icono: esRecepcionista.value ? 'text-sky-500' : 'text-orange-500',

    fondo: esRecepcionista.value
        ? 'bg-sky-50 dark:bg-sky-950/20 border-sky-200/50 dark:border-sky-900/30'
        : 'bg-orange-50 dark:bg-orange-950/20 border-orange-200/50 dark:border-orange-900/30',
}));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Banner de bienvenida con gradiente premium energético de Gimnasio -->
            <div
                :class="[
                    'relative flex items-center justify-between overflow-hidden rounded-xl border p-6 text-white shadow-lg transition-shadow duration-300 hover:shadow-xl',
                    tema.banner,
                ]"
            >
                <div class="relative z-10">
                    <h1 class="text-3xl font-extrabold tracking-tight">Panel de Control General</h1>
                    <p class="mt-2 text-sm font-medium text-orange-100">
                        Bienvenido al sistema administrativo del gimnasio. Perfil activo:
                        <span class="rounded bg-white/20 px-2 py-0.5 text-xs font-bold uppercase text-white">{{ userRole }}</span>
                    </p>
                </div>
                <div class="relative z-10 hidden rounded-xl border border-white/10 bg-zinc-950 p-1.5 shadow-md sm:block">
                    <img src="/logo-gym.png" class="h-16 w-16 rounded-lg object-contain" alt="Eagle Fitness Logo" />
                </div>
                <!-- Elemento de fondo abstracto y moderno -->
                <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
            </div>

            <!-- Si el usuario es Administrador, muestra los paneles de control financieros y de seguridad -->
            <!-- Accesos rápidos para Administrador -->
            <div v-if="userRole === 'Administrador'" class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- REPORTES -->
                <Link
                    href="/reportes"
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow transition-all hover:-translate-y-1 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-orange-100 dark:bg-orange-900/30">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-7 w-7 text-orange-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10V4" />
                            </svg>
                        </div>

                        <div>
                            <h2 class="text-lg font-bold">Reportes Financieros</h2>

                            <p class="text-sm text-gray-500">Consultar ingresos, membresías y generar reportes.</p>
                        </div>
                    </div>
                </Link>

                <!-- BACKUP -->
                <Link
                    href="/admin/backup"
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow transition-all hover:-translate-y-1 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/30">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-7 w-7 text-amber-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7" />
                            </svg>
                        </div>

                        <div>
                            <h2 class="text-lg font-bold">Copias de Seguridad</h2>

                            <p class="text-sm text-gray-500">Crear, descargar y restaurar respaldos de la base de datos.</p>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Si el usuario es Recepcionista -->
            <div
                v-if="userRole === 'Recepcionista'"
                class="space-y-4 rounded-xl border border-gray-100 bg-white p-5 shadow-md transition-all duration-300 hover:scale-[1.01] hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900"
            >
                <h2
                    class="flex items-center space-x-2 border-b border-gray-100 pb-3 text-lg font-bold text-gray-800 dark:border-zinc-800 dark:text-zinc-100"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                        />
                    </svg>

                    <span>Atención al Cliente y Registro</span>
                </h2>

                <p class="text-sm leading-relaxed text-gray-600 dark:text-zinc-400">
                    Has iniciado sesión como <b>Recepcionista</b>. Desde tu panel de control puedes gestionar clientes, membresías y pagos.
                </p>

                <div class="grid grid-cols-1 gap-6 pt-2 sm:grid-cols-2">
                    <!-- CLIENTES -->
                    <Link
                        href="/clientes"
                        class="flex items-center space-x-4 rounded-xl border border-sky-200/60 bg-gradient-to-br from-sky-50/70 to-indigo-100/50 p-5 transition-all duration-200 hover:from-sky-100 hover:to-indigo-200 hover:shadow-md dark:border-zinc-700/50 dark:from-zinc-800 dark:to-zinc-800/40 dark:hover:from-zinc-800/80"
                    >
                        <div class="rounded-lg bg-gradient-to-r from-sky-600 to-indigo-600 p-3 text-white shadow-md shadow-sky-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-zinc-100">Gestionar Clientes</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-zinc-400">Registrar clientes, actualizar datos o buscar información.</p>
                        </div>
                    </Link>

                    <!-- MEMBRESIAS -->
                    <Link
                        href="/membresias"
                        class="flex items-center space-x-4 rounded-xl border border-sky-200/60 bg-gradient-to-br from-sky-50/70 to-indigo-100/50 p-5 transition-all duration-200 hover:from-sky-100 hover:to-indigo-200 hover:shadow-md dark:border-zinc-700/50 dark:from-zinc-800 dark:to-zinc-800/40 dark:hover:from-zinc-800/80"
                    >
                        <div class="rounded-lg bg-gradient-to-r from-sky-600 to-indigo-600 p-3 text-white shadow-md shadow-sky-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                />
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-zinc-100">Registrar Membresía</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-zinc-400">Asignar planes, aplicar descuentos e imprimir comprobantes.</p>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Panel de Consulta Rápida de Membresías -->
            <div
                class="space-y-4 rounded-xl border border-gray-100 bg-white p-6 shadow-md transition-shadow duration-300 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900"
            >
                <!-- TÍTULO -->
                <h2
                    class="flex items-center space-x-2 border-b border-gray-100 pb-3 text-lg font-bold text-gray-800 dark:border-zinc-800 dark:text-zinc-100"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>

                    <span>Consulta Rápida de Membresías</span>
                </h2>

                <!-- BUSCADOR -->
                <div class="flex flex-col gap-3 sm:flex-row">
                    <input
                        v-model="busquedaCliente"
                        type="text"
                        placeholder="Buscar cliente por Nombre, Apellido o CI..."
                        @keyup.enter="buscarMembresiaCliente"
                        class="flex-1 rounded-lg border border-gray-200 bg-white p-2.5 text-sm text-gray-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-100"
                    />

                    <button
                        @click="buscarMembresiaCliente"
                        :disabled="buscando"
                        class="flex items-center justify-center gap-1.5 rounded-lg bg-gradient-to-r from-orange-500 to-red-600 px-6 py-2.5 text-sm font-bold text-white shadow-md transition-all duration-200 hover:from-orange-600 hover:to-red-700 hover:shadow-lg disabled:opacity-50"
                    >
                        <span v-if="buscando">Buscando...</span>
                        <span v-else>🔍 Buscar</span>
                    </button>
                </div>

                <!-- TABLA -->
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full overflow-hidden rounded-lg border border-gray-200 dark:border-zinc-700">
                        <!-- HEADER -->
                        <thead class="bg-orange-600 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left">CI</th>
                                <th class="px-4 py-3 text-left">Cliente</th>
                                <th class="px-4 py-3 text-left">Teléfono</th>
                                <th class="px-4 py-3 text-left">Membresía</th>
                                <th class="px-4 py-3 text-left">Fecha Vencimiento</th>
                                <th class="px-4 py-3 text-left">Estado</th>
                            </tr>
                        </thead>

                        <!-- BODY -->
                        <tbody>
                            <tr
                                v-for="cliente in clientesFiltrados"
                                :key="cliente.id_cliente"
                                class="border-b transition hover:bg-orange-50 dark:hover:bg-zinc-800"
                            >
                                <td class="px-4 py-3">
                                    {{ cliente.ci }}
                                </td>

                                <td class="px-4 py-3 font-semibold">
                                    {{ cliente.nombre }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ cliente.telefono }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ cliente.ultima_membresia?.nombre_membresia || 'Sin membresía' }}
                                </td>
                                <td class="px-4 py-3 font-semibold">
                                    {{ formatoFecha(cliente.ultima_membresia?.fecha_vencimiento) }}
                                </td>

                                <!-- ESTADO -->
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-md font-bold text-white"
                                            :class="colorEstado(cliente)"
                                        >
                                            {{ diasRestantes(cliente.ultima_membresia?.fecha_vencimiento) }}
                                        </div>

                                        <span class="font-semibold">
                                            {{ cliente.es_activa ? 'Activa' : 'Vencida' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- SIN RESULTADOS -->
                <div
                    v-if="busquedaCliente.trim() && resultadosClientes.length === 0 && !buscando"
                    class="pt-2 text-xs italic text-gray-500 dark:text-zinc-500"
                >
                    No se encontraron clientes que coincidan con la búsqueda.
                </div>
            </div>
        </div>
    </AppLayout>
</template>

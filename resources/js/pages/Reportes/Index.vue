<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import { AlertCircle, Award, Calendar, DollarSign, FileText, Printer } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reportes Financieros', href: '/reportes' },
];

// Configurar fechas por defecto (del 1 del mes actual al día de hoy)
const hoy = new Date().toISOString().split('T')[0];
const primerDia = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];

const fechaDesde = ref(primerDia);
const fechaHasta = ref(hoy);
const tipoReporte = ref('General'); // General, Pagos, Membresias, historial de cliente

const cargando = ref(false);
const errorMensaje = ref('');
const datosReporte = ref(null);

const consultarReporte = async () => {
    if (tipoReporte.value === 'cliente') {
        if (!clienteSeleccionado.value) {
            errorMensaje.value = 'Seleccione un cliente';

            return;
        }
        await cargarHistorialCliente();
        return;
    }

    if (fechaDesde.value > fechaHasta.value) {
        errorMensaje.value = 'La fecha "Desde" no puede ser mayor a la fecha "Hasta" (RN-11).';
        return;
    }

    errorMensaje.value = '';
    cargando.value = true;

    try {
        const url = `/reportes/financiero?fecha_desde=${fechaDesde.value}&fecha_hasta=${fechaHasta.value}`;
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Error de consulta en el servidor.');
        }

        datosReporte.value = data;
    } catch (e) {
        errorMensaje.value = e.message || 'Error al obtener los datos del reporte (A2).';
        datosReporte.value = null;
    } finally {
        cargando.value = false;
    }
};

const exportarPDF = () => {
    window.print();
};

//Para el reporte de historial de cliente

const clientes = ref([]);
const busquedaCliente = ref('');
const clienteSeleccionado = ref(null);
const datosCliente = ref(null);
const historialMembresias = ref([]);
const mostrarResultados = ref(false);

const cargarClientes = async () => {
    try {
        const res = await axios.get('/dashboard/clientes');
        clientes.value = res.data;
    } catch (e) {
        console.error(e);
    }
};

const cargarHistorialCliente = async () => {
    try {
        const res = await axios.get(`/reportes/historial-cliente/${clienteSeleccionado.value}`);
        datosCliente.value = res.data.cliente;

        historialMembresias.value = res.data.membresias;
    } catch (e) {
        errorMensaje.value = 'No se pudo obtener el historial.';
    }
};

const clientesFiltrados = computed(() => {
    if (!busquedaCliente.value.trim()) {
        return clientes.value;
    }

    const texto = busquedaCliente.value.toLowerCase();

    return clientes.value.filter((c) => c.nombre.toLowerCase().includes(texto) || c.ci.includes(texto));
});

const limpiarBusqueda = () => {
    busquedaCliente.value = '';
};

watch(tipoReporte, (nuevoTipo) => {
    if (nuevoTipo === 'cliente') {
        busquedaCliente.value = '';
        clienteSeleccionado.value = null;
        datosCliente.value = null;
        historialMembresias.value = [];
    }
});

const seleccionarCliente = (cliente) => {
    clienteSeleccionado.value = cliente.id_cliente;

    busquedaCliente.value = `${cliente.nombre} - CI: ${cliente.ci}`;

    mostrarResultados.value = false;
};

onMounted(() => {
    consultarReporte();
    cargarClientes();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="no-print m-4 rounded-xl border border-gray-100 bg-white p-6 shadow-md transition-shadow duration-300 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900"
        >
            <div
                class="mb-6 flex flex-col items-start justify-between gap-4 border-b border-gray-100 pb-4 dark:border-zinc-800/80 sm:flex-row sm:items-center"
            >
                <h1 class="flex items-center gap-2 text-2xl font-extrabold tracking-tight text-gray-800 dark:text-zinc-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"
                        />
                    </svg>
                    <span>Generación de Reportes Financieros (CU-06)</span>
                </h1>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400">Tipo de Reporte</label>
                    <select
                        v-model="tipoReporte"
                        class="block w-full rounded-lg border border-gray-200 bg-white p-2.5 text-sm text-gray-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                        <option value="General">General (Ingresos y Membresías)</option>
                        <option value="Pagos">Detalle de Recaudación (Pagos)</option>
                        <option value="Membresias">Venta de Planes (Membresías)</option>
                        <option value="cliente">Historial de Cliente</option>
                    </select>
                </div>
                <button
                    @click="exportarPDF"
                    v-if="datosReporte"
                    class="flex items-center gap-2 rounded-lg bg-gradient-to-r from-orange-500 to-red-600 px-4 py-2.5 text-sm font-bold text-white shadow-md transition-all duration-200 hover:from-orange-600 hover:to-red-700 hover:shadow-lg"
                >
                    <Printer class="h-4 w-4" />
                    <span>Exportar a PDF / Imprimir</span>
                </button>
            </div>

            <!-- FILTROS REPORTES NORMALES -->
            <div
                v-if="tipoReporte !== 'cliente'"
                class="mb-6 rounded-xl border border-zinc-200/50 bg-zinc-50 p-5 dark:border-zinc-800/60 dark:bg-zinc-800/30"
            >
                <h2 class="mb-4 flex items-center gap-1.5 text-xs font-black uppercase tracking-wider text-orange-600 dark:text-orange-500">
                    <Calendar class="h-4 w-4 text-orange-500" />
                    <span>Filtros de Búsqueda</span>
                </h2>

                <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase text-gray-500"> Fecha Desde * </label>

                        <input
                            v-model="fechaDesde"
                            type="date"
                            class="block w-full rounded-lg border border-zinc-300 bg-white p-2.5 text-gray-900 focus:border-orange-500 focus:ring-2 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:[color-scheme:dark]"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase text-gray-500"> Fecha Hasta * </label>

                        <input
                            v-model="fechaHasta"
                            type="date"
                            class="block w-full rounded-lg border border-zinc-300 bg-white p-2.5 text-gray-900 focus:border-orange-500 focus:ring-2 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:[color-scheme:dark]"
                        />
                    </div>

                    <div>
                        <button
                            @click="consultarReporte"
                            :disabled="cargando"
                            class="w-full rounded-lg bg-orange-600 py-2.5 font-bold text-white hover:bg-orange-700"
                        >
                            {{ cargando ? 'Procesando...' : 'Aplicar Filtros' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- FILTROS REPORTE HISTORIAL DE CLIENTE -->
            <div v-else class="mb-6 rounded-xl border border-zinc-200/50 bg-zinc-50 p-5 dark:border-zinc-800/60 dark:bg-zinc-800/30">
                <h2 class="mb-5 text-xs font-black uppercase tracking-wider text-orange-600">Historial del Cliente</h2>

                <!-- Barra superior -->
                <div class="grid grid-cols-12 items-end gap-3">
                    <!-- Buscador -->
                    <div class="relative col-span-5">
                        <label class="mb-1 block text-xs font-bold uppercase"> Cliente </label>

                        <input
                            v-model="busquedaCliente"
                            @focus="mostrarResultados = true"
                            type="text"
                            placeholder="Buscar por nombre o CI..."
                            class="w-full rounded-lg border border-zinc-300 bg-white p-2 text-gray-900 focus:ring-2 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        />
                        <!-- Lista desplegable -->
                        <div
                            v-if="mostrarResultados && clientesFiltrados.length"
                            class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <button
                                v-for="cliente in clientesFiltrados"
                                :key="cliente.id_cliente"
                                type="button"
                                @click="seleccionarCliente(cliente)"
                                class="block w-full border-b p-3 text-left hover:bg-orange-50 dark:border-zinc-700 dark:hover:bg-zinc-800"
                            >
                                <div class="font-bold text-gray-800 dark:text-white">
                                    {{ cliente.nombre }}
                                </div>

                                <div class="text-sm text-gray-500">CI: {{ cliente.ci }}</div>
                            </button>
                        </div>

                        <div
                            v-if="mostrarResultados && !clientesFiltrados.length"
                            class="absolute z-50 mt-1 w-full rounded-lg border bg-white p-3 text-sm text-gray-500 shadow dark:bg-zinc-900"
                        >
                            No se encontraron clientes
                        </div>
                    </div>

                    <!-- Botón Aplicar -->
                    <div class="col-span-2">
                        <button @click="consultarReporte" class="w-full rounded-lg bg-orange-600 py-2 text-white hover:bg-orange-700">Aplicar</button>
                    </div>
                </div>

                <!-- ======================= DATOS DEL CLIENTE ======================= -->
                <div v-if="datosCliente" class="mt-8 rounded-xl border border-zinc-200 bg-white shadow dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="rounded-t-xl bg-orange-600 px-5 py-3">
                        <h3 class="text-lg font-bold text-white">Información del Cliente</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-orange-600 text-xs uppercase tracking-wider text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left">CI</th>
                                    <th class="px-4 py-3 text-left">Nombre</th>
                                    <th class="px-4 py-3 text-left">Teléfono</th>
                                    <th class="px-4 py-3 text-left">Dirección</th>
                                    <th class="px-4 py-3 text-left">Fecha Registro</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                <tr class="transition hover:bg-orange-50 dark:hover:bg-zinc-800">
                                    <td class="px-4 py-3 text-gray-800 dark:text-zinc-200">
                                        {{ datosCliente.ci }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-800 dark:text-zinc-200">
                                        {{ datosCliente.nombre }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-800 dark:text-zinc-200">
                                        {{ datosCliente.telefono }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-800 dark:text-zinc-200">
                                        {{ datosCliente.direccion }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-800 dark:text-zinc-200">
                                        {{ datosCliente.fecha_registro }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ======================= HISTORIAL DE MEMBRESÍAS ======================= -->

                <div
                    v-if="historialMembresias.length"
                    class="mt-10 rounded-xl border border-zinc-200 bg-white shadow dark:border-zinc-700 dark:bg-zinc-900"
                >
                    <div class="rounded-t-xl bg-orange-600 px-5 py-3">
                        <h3 class="text-lg font-bold text-white">Historial de Membresías</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-orange-600 text-xs uppercase tracking-wider text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left">Membresía</th>

                                    <th class="px-4 py-3 text-left">Fecha Inicio</th>

                                    <th class="px-4 py-3 text-left">Fecha Vencimiento</th>

                                    <th class="px-4 py-3 text-center">Estado</th>

                                    <th class="px-4 py-3 text-left">Método Pago</th>

                                    <th class="px-4 py-3 text-right">Monto Pagado</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                <tr
                                    v-for="item in historialMembresias"
                                    :key="item.id_membresia"
                                    class="transition hover:bg-orange-50 dark:hover:bg-zinc-800"
                                >
                                    <td class="px-4 py-3 text-gray-800 dark:text-zinc-200">
                                        {{ item.nombre_membresia }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-800 dark:text-zinc-200">
                                        {{ item.fecha_inicio }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-800 dark:text-zinc-200">
                                        {{ item.fecha_vencimiento }}
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <span
                                            class="rounded-full px-3 py-1 text-xs font-bold"
                                            :class="
                                                item.estado === 'Activa'
                                                    ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                                    : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
                                            "
                                        >
                                            {{ item.estado }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-gray-800 dark:text-zinc-200">
                                        {{ item.metodo_pago }}
                                    </td>

                                    <td class="px-4 py-3 text-right font-semibold text-gray-800 dark:text-zinc-200">Bs. {{ item.monto_pagado }}</td>
                                </tr>

                                <tr v-if="!historialMembresias.length">
                                    <td colspan="6" class="px-4 py-8 text-center italic text-zinc-500 dark:text-zinc-400">
                                        El cliente no posee historial de membresías.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Errores de Validación o de Consulta -->
            <div
                v-if="errorMensaje"
                class="text-red-750 mb-6 flex items-start gap-3 rounded-r-lg border-l-4 border-red-500 bg-red-50 p-4 font-semibold dark:border-red-600 dark:bg-red-950/20 dark:text-red-400"
            >
                <AlertCircle class="h-5 w-5 flex-shrink-0 text-red-500 dark:text-red-400" />
                <div>
                    <span>Error de consulta:</span>
                    <p class="mt-0.5 text-sm font-normal">{{ errorMensaje }}</p>
                </div>
            </div>

            <!-- Resultados del Reporte -->
            <div v-if="datosReporte" class="space-y-6">
                <!-- Tarjetas de Indicadores Rápidos -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2" v-if="tipoReporte === 'General' || tipoReporte === 'Pagos'">
                    <div
                        class="flex items-center gap-4 rounded-xl border border-orange-200/50 bg-orange-50 p-5 dark:border-orange-900/30 dark:bg-orange-950/20"
                    >
                        <div class="rounded-xl bg-gradient-to-br from-orange-500 to-red-500 p-3 text-white shadow-md shadow-orange-500/20">
                            <DollarSign class="h-6 w-6" />
                        </div>
                        <div>
                            <span class="block text-xs font-bold uppercase tracking-wider text-orange-800 dark:text-orange-300">
                                Recaudación Total</span
                            >
                            <span class="text-2xl font-black text-orange-600 dark:text-orange-500"
                                >Bs. {{ parseFloat(datosReporte.total_recaudado).toFixed(2) }}</span
                            >
                        </div>
                    </div>

                    <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-800 dark:bg-zinc-800/30">
                        <div class="rounded-xl bg-zinc-500 p-3 text-white shadow-md dark:bg-zinc-700">
                            <Award class="h-6 w-6" />
                        </div>
                        <div class="text-zinc-650 space-y-0.5 text-xs font-bold uppercase tracking-wider dark:text-zinc-400">
                            <span class="block">Rango Seleccionado</span>
                            <span class="block text-sm font-black normal-case text-zinc-800 dark:text-zinc-200"
                                >Desde: {{ datosReporte.rango.desde }}</span
                            >
                            <span class="block text-sm font-black normal-case text-zinc-800 dark:text-zinc-200"
                                >Hasta: {{ datosReporte.rango.hasta }}</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Flujo Alternativo: Sin Datos (A1) -->
                <div
                    v-if="datosReporte.total_recaudado === 0 && datosReporte.membresias_vendidas.length === 0"
                    class="border-gray-150 text-gray-550 rounded-xl border bg-gray-50 py-12 text-center font-medium dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-400"
                >
                    No hay registros en el rango de fechas seleccionado (A1).
                </div>

                <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Tabla 1: Recaudación por métodos de pago (CU-06 / RF-08) -->
                    <div
                        class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                        v-if="tipoReporte === 'General' || tipoReporte === 'Pagos'"
                    >
                        <h3 class="mb-4 flex items-center gap-2 text-base font-extrabold text-gray-800 dark:text-zinc-100">
                            <FileText class="h-5 w-5 text-orange-500" />
                            <span>Ingresos por Método de Pago</span>
                        </h3>
                        <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-zinc-800">
                            <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-zinc-800">
                                <thead
                                    class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500 dark:bg-zinc-800 dark:text-zinc-400"
                                >
                                    <tr>
                                        <th class="px-4 py-3">Método de Pago</th>
                                        <th class="px-4 py-3 text-right">Monto Recaudado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 text-gray-600 dark:divide-zinc-800 dark:text-zinc-300">
                                    <tr
                                        v-for="item in datosReporte.detalle_ingresos"
                                        :key="item.metodo_pago"
                                        class="transition-colors hover:bg-gray-50/50 dark:hover:bg-zinc-800/30"
                                    >
                                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-zinc-200">{{ item.metodo_pago }}</td>
                                        <td class="px-4 py-3 text-right font-bold text-gray-800 dark:text-zinc-100">
                                            Bs. {{ parseFloat(item.total).toFixed(2) }}
                                        </td>
                                    </tr>
                                    <tr class="bg-gray-50/80 font-bold text-gray-900 dark:bg-zinc-800/80 dark:text-zinc-100">
                                        <td class="px-4 py-3">Total General</td>
                                        <td class="dark:text-orange-455 px-4 py-3 text-right text-orange-600">
                                            Bs. {{ parseFloat(datosReporte.total_recaudado).toFixed(2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tabla 2: Membresías vendidas agrupadas por plan -->
                    <div
                        class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                        v-if="tipoReporte === 'General' || tipoReporte === 'Membresias'"
                    >
                        <h3 class="mb-4 flex items-center gap-2 text-base font-extrabold text-gray-800 dark:text-zinc-100">
                            <Award class="h-5 w-5 text-orange-500" />
                            <span>Membresías Adquiridas por Tipo</span>
                        </h3>
                        <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-zinc-800">
                            <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-zinc-800">
                                <thead
                                    class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500 dark:bg-zinc-800 dark:text-zinc-400"
                                >
                                    <tr>
                                        <th class="px-4 py-3">Plan de Membresía</th>
                                        <th class="px-4 py-3 text-center">Cantidad Vendida</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-650 divide-y divide-gray-200 dark:divide-zinc-800 dark:text-zinc-300">
                                    <tr
                                        v-for="item in datosReporte.membresias_vendidas"
                                        :key="item.nombre_membresia"
                                        class="transition-colors hover:bg-gray-50/50 dark:hover:bg-zinc-800/30"
                                    >
                                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-zinc-200">{{ item.nombre_membresia }}</td>
                                        <td class="px-4 py-3 text-center font-bold text-gray-800 dark:text-zinc-100">{{ item.cantidad }}</td>
                                    </tr>
                                    <tr v-if="datosReporte.membresias_vendidas.length === 0">
                                        <td colspan="2" class="px-4 py-3 text-center italic text-gray-400 dark:text-zinc-500">
                                            No hay membresías registradas en este período.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista de impresión optimizada (se muestra al imprimir) -->
        <div class="print-only bg-white p-8 text-black">
            <!-- ================= REPORTE HISTORIAL CLIENTE ================= -->
            <div v-if="tipoReporte === 'cliente' && datosCliente">
                <div class="mb-6 border-b-2 border-black pb-4 text-center">
                    <h1 class="text-2xl font-bold">GIMNASIO ÁGUILAS</h1>

                    <h2 class="text-xl font-bold uppercase">REPORTE DE HISTORIAL DEL CLIENTE</h2>

                    <p class="mt-2 text-sm">
                        Fecha de emisión:
                        {{ new Date().toLocaleDateString() }}
                    </p>

                    <p class="text-sm">Usuario: Administrador</p>
                </div>

                <!-- DATOS DEL CLIENTE -->
                <div class="mb-6">
                    <h3 class="border-b-2 border-black pb-2 text-lg font-bold">DATOS DEL CLIENTE</h3>

                    <table class="mt-4 w-full text-sm">
                        <tbody>
                            <tr>
                                <td class="font-bold">CI:</td>
                                <td>{{ datosCliente.ci }}</td>
                            </tr>

                            <tr>
                                <td class="font-bold">Nombre:</td>
                                <td>{{ datosCliente.nombre }}</td>
                            </tr>

                            <tr>
                                <td class="font-bold">Teléfono:</td>
                                <td>{{ datosCliente.telefono }}</td>
                            </tr>

                            <tr>
                                <td class="font-bold">Dirección:</td>
                                <td>{{ datosCliente.direccion }}</td>
                            </tr>

                            <tr>
                                <td class="font-bold">Fecha Registro:</td>
                                <td>{{ datosCliente.fecha_registro }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- HISTORIAL DE MEMBRESÍAS -->

                <div v-if="historialMembresias.length">
                    <h3 class="mb-3 border-b-2 border-black pb-2 text-lg font-bold">HISTORIAL DE MEMBRESÍAS</h3>

                    <table class="w-full border-collapse border border-black text-sm">
                        <thead>
                            <tr>
                                <th class="border border-black p-2">Membresía</th>

                                <th class="border border-black p-2">Fecha Inicio</th>

                                <th class="border border-black p-2">Fecha Vencimiento</th>

                                <th class="border border-black p-2">Estado</th>

                                <th class="border border-black p-2">Método Pago</th>

                                <th class="border border-black p-2">Monto Pagado</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="item in historialMembresias" :key="item.id_membresia">
                                <td class="border border-black p-2">
                                    {{ item.nombre_membresia }}
                                </td>

                                <td class="border border-black p-2">
                                    {{ item.fecha_inicio }}
                                </td>

                                <td class="border border-black p-2">
                                    {{ item.fecha_vencimiento }}
                                </td>

                                <td class="border border-black p-2 text-center">
                                    {{ item.estado }}
                                </td>

                                <td class="border border-black p-2">
                                    {{ item.metodo_pago }}
                                </td>

                                <td class="border border-black p-2 text-right">Bs. {{ item.monto_pagado }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- ================= PIE DEL REPORTE ================= -->

                <div class="mt-10 border-t-2 border-black pt-6">
                    <!-- Resumen -->
                    <h3 class="mb-3 text-lg font-bold uppercase">RESUMEN DEL HISTORIAL</h3>

                    <table class="w-full text-sm">
                        <tbody>
                            <tr>
                                <td class="font-bold">Total de membresías registradas:</td>

                                <td>
                                    {{ historialMembresias.length }}
                                </td>
                            </tr>

                            <tr>
                                <td class="font-bold">Estado actual:</td>

                                <td>
                                    {{
                                        historialMembresias.some((item) => item.estado === 'Activa')
                                            ? 'Cliente con membresía activa'
                                            : 'Cliente sin membresía activa'
                                    }}
                                </td>
                            </tr>

                            <tr>
                                <td class="font-bold">Última actualización:</td>

                                <td>
                                    {{ new Date().toLocaleDateString() }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Firmas -->

                    <div class="mt-16 grid grid-cols-2 gap-20 text-center">
                        <div>
                            <div class="mx-auto mb-2 w-48 border-t border-black"></div>

                            <p class="font-bold">Responsable del Reporte</p>

                            <p class="text-sm">Administrador</p>

                            <p class="text-sm">Gimnasio Águilas</p>
                        </div>

                        <div>
                            <div class="mx-auto mb-2 w-48 border-t border-black"></div>

                            <p class="font-bold">Cliente</p>

                            <p class="text-sm">
                                {{ datosCliente.nombre }}
                            </p>

                            <p class="text-sm">CI: {{ datosCliente.ci }}</p>
                        </div>
                    </div>

                    <!-- Pie final -->

                    <div class="mt-10 border-t pt-3 text-center text-xs text-gray-600">
                        <p>Documento generado automáticamente por el Sistema de Gestión de Control de Membresías - Gimnasio Águilas</p>

                        <p>
                            Fecha y hora de generación:
                            {{ new Date().toLocaleString() }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- ================= REPORTES FINANCIEROS NORMALES ================= -->

            <div v-else>
                <div class="mb-6 border-b-2 border-black pb-4">
                    <h1 class="text-2xl font-bold uppercase">Reporte de Ingresos del Sistema</h1>
                    <p class="text-sm text-gray-600">Generado el: {{ new Date().toLocaleString() }}</p>

                    <div class="mb-6">
                        <h2 class="text-lg font-bold">Resumen de Parámetros</h2>
                        <p class="text-sm">
                            Rango de fechas: Desde <strong>{{ fechaDesde }}</strong> Hasta <strong>{{ fechaHasta }}</strong>
                        </p>
                        <p class="text-sm">
                            Tipo de Reporte: <strong>{{ tipoReporte }}</strong>
                        </p>
                    </div>

                    <div v-if="datosReporte" class="space-y-6">
                        <div class="mb-6 flex items-center justify-between rounded border bg-gray-50 p-4">
                            <span class="text-lg font-bold">RECAUDACIÓN TOTAL ACUMULADA:</span>
                            <span v-if="tipoReporte !== 'cliente'" class="text-2xl font-black text-orange-600 dark:text-orange-500">
                                Bs. {{ parseFloat(datosReporte.total_recaudado).toFixed(2) }}
                            </span>
                        </div>

                        <div class="space-y-6">
                            <div v-if="tipoReporte === 'General' || tipoReporte === 'Pagos'">
                                <h3 class="mb-2 text-base font-bold">Ingresos por Método de Pago</h3>
                                <table class="w-full border-collapse border border-gray-300 text-sm">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="border border-gray-300 px-4 py-2 text-left">Método de Pago</th>
                                            <th class="border border-gray-300 px-4 py-2 text-right">Monto Recaudado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in datosReporte.detalle_ingresos" :key="item.metodo_pago">
                                            <td class="border border-gray-300 px-4 py-2">{{ item.metodo_pago }}</td>
                                            <td class="border border-gray-300 px-4 py-2 text-right">Bs. {{ parseFloat(item.total).toFixed(2) }}</td>
                                        </tr>
                                        <tr class="font-bold">
                                            <td class="border border-gray-300 px-4 py-2">Total Recaudado</td>
                                            <td class="border border-gray-300 px-4 py-2 text-right text-emerald-800">
                                                Bs. {{ parseFloat(datosReporte.total_recaudado).toFixed(2) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-if="tipoReporte === 'General' || tipoReporte === 'Membresias'">
                                <h3 class="mb-2 mt-4 text-base font-bold">Membresías Adquiridas por Tipo</h3>
                                <table class="w-full border-collapse border border-gray-300 text-sm">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="border border-gray-300 px-4 py-2 text-left">Plan de Membresía</th>
                                            <th class="border border-gray-300 px-4 py-2 text-center">Cantidad Vendida</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in datosReporte.membresias_vendidas" :key="item.nombre_membresia">
                                            <td class="border border-gray-300 px-4 py-2">{{ item.nombre_membresia }}</td>
                                            <td class="border border-gray-300 px-4 py-2 text-center font-bold">{{ item.cantidad }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    .print-only {
        display: block !important;
    }
    body {
        background: white !important;
        color: black !important;
    }
}
@media screen {
    .print-only {
        display: none !important;
    }
}
</style>

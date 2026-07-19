<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Registrar Membresía', href: '/membresias' },
];

// Recibimos las colecciones cargadas desde el controlador de Laravel
const props = defineProps({
    clientes: Array,
    tiposMembresia: Array,
    promociones: Array,
});

// Variables reactivas locales para el buscador y cálculo de fechas
const filtroBusqueda = ref('');
const clientesFiltrados = ref([...props.clientes]);
const montoBase = ref('0.00');
const montoDescontado = ref('0.00');
const montoFinal = ref('0.00');
const fechaVencimientoCalculada = ref('');
const errorImagen = ref('');

// Variables reactivas para el control de membresía existente y pago adelantado
const clienteTieneMembresiaActiva = ref(false);
const clienteFechaLimite = ref('');
const fechaInicioAdelantada = ref('');

// Obtener la fecha de hoy en formato local YYYY-MM-DD
const fechaHoy = new Date().toISOString().split('T')[0];

// Inicialización de la estructura de datos mediante useForm de Inertia
const form = useForm({
    id_cliente: '',
    id_tipo_membresia: '',
    id_promocion: '',
    fecha_inicio: fechaHoy,
    metodo_pago: 'Efectivo',
    comprobante_foto: null, // Aquí viaja el archivo binario del QR
    banco_origen: '',
    banco_destino: '',
    cuenta_destino: '',
    codigo_transaccion: '',
    pago_adelantado: false,
    cantidad_periodos: 1,
});

// Obtener reactivamente la promoción seleccionada
const promocionSeleccionada = computed(() => {
    if (!form.id_promocion) {
        return null;
    }
    return props.promociones.find((p) => p.id_promocion === form.id_promocion) || null;
});

// Monitorear la selección del cliente para detectar si ya tiene una membresía activa
watch(
    () => form.id_cliente,
    (nuevoId) => {
        form.pago_adelantado = false;
        clienteTieneMembresiaActiva.value = false;
        clienteFechaLimite.value = '';
        fechaInicioAdelantada.value = '';

        if (nuevoId) {
            const cliente = props.clientes.find((c) => c.id_cliente === nuevoId);
            if (cliente && cliente.fecha_limite) {
                const hoyStr = new Date().toISOString().split('T')[0];
                // Solo consideramos membresía activa si tiene al menos una membresía registrada y el límite está vigente
                if (cliente.membresias_count > 0 && cliente.fecha_limite >= hoyStr) {
                    clienteTieneMembresiaActiva.value = true;
                    clienteFechaLimite.value = cliente.fecha_limite;

                    // Calcular fecha de inicio adelantada = fecha_limite + 1 día
                    const fecha = new Date(cliente.fecha_limite + 'T00:00:00');
                    fecha.setDate(fecha.getDate() + 1);
                    fechaInicioAdelantada.value = fecha.toISOString().split('T')[0];
                }
            }
        }
        actualizarMontoYFechas();
    },
);

// Monitorear si se marca el checkbox de pago adelantado
watch(
    () => form.pago_adelantado,
    (val) => {
        if (val) {
            form.fecha_inicio = fechaInicioAdelantada.value;
        } else {
            form.fecha_inicio = fechaHoy;
        }
        actualizarMontoYFechas();
    },
);

// Filtrar la lista de clientes reactivamente por CI o Apellido (CU-03)
const filtrarClientes = () => {
    const busqueda = filtroBusqueda.value.toLowerCase().trim();
    if (!busqueda) {
        clientesFiltrados.value = [...props.clientes];
    } else {
        clientesFiltrados.value = props.clientes.filter((cliente) => {
            const ci = cliente.persona.ci.toLowerCase();
            const apellido = cliente.persona.apellido.toLowerCase();
            return ci.includes(busqueda) || apellido.includes(busqueda);
        });
    }
};

// Calcula automáticamente el vencimiento y el monto según el catálogo (CU-04 / CU-08)
const actualizarMontoYFechas = () => {
    const planSeleccionado = props.tiposMembresia.find((t) => t.id_tipo_membresia === form.id_tipo_membresia);

    if (planSeleccionado && form.fecha_inicio) {
        const cant = form.cantidad_periodos || 1;
        const base = planSeleccionado.precio * cant;
        montoBase.value = parseFloat(base).toFixed(2);

        // Calcular descuento de promoción si existe
        let final = base;
        let descuento = 0;
        if (form.id_promocion) {
            const promo = props.promociones.find((p) => p.id_promocion === form.id_promocion);
            if (promo) {
                descuento = (base * promo.porcentaje_descuento) / 100;
                final = base - descuento;
            }
        }
        montoFinal.value = parseFloat(final).toFixed(2);
        montoDescontado.value = parseFloat(descuento).toFixed(2);

        // Calcular fecha de vencimiento sumando la duración en días multiplicada por la cantidad de períodos
        const fecha = new Date(form.fecha_inicio + 'T00:00:00');
        fecha.setDate(fecha.getDate() + parseInt(planSeleccionado.duracion_dias) * cant);
        fechaVencimientoCalculada.value = fecha.toISOString().split('T')[0];
    } else {
        montoBase.value = '0.00';
        montoFinal.value = '0.00';
        fechaVencimientoCalculada.value = '';
    }
};

// Validación e inspección del archivo cargado (Cumple Flujo Alternativo A3 de CU-05)
const procesarArchivoComprobante = (event) => {
    const archivo = event.target.files[0];
    errorImagen.value = '';

    if (!archivo) {
        form.comprobante_foto = null;
        return;
    }

    // Validar extensión de tipo imagen obligatoria
    const tiposPermitidos = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!tiposPermitidos.includes(archivo.type)) {
        errorImagen.value = 'Error: El comprobante debe ser una imagen válida (JPG o PNG).';
        form.comprobante_foto = null;
        return;
    }

    // Validar tamaño máximo (Ej: 2MB)
    if (archivo.size > 2 * 1024 * 1024) {
        errorImagen.value = 'Error: El archivo supera el tamaño máximo permitido (2MB).';
        form.comprobante_foto = null;
        return;
    }

    // Si pasa los filtros, se almacena en el formulario
    form.comprobante_foto = archivo;
};

// Envío unificado de datos a la ruta POST de Laravel
const enviarFormulario = () => {
    form.post('/membresias', {
        preserveScroll: true,
        onSuccess: () => {
            if (form.pago_adelantado) {
                alert(
                    `✓ Membresía adelantada registrada correctamente.
La nueva membresía iniciará automáticamente el
${form.fecha_inicio}
cuando finalice la actual.`,
                );
            } else {
                alert('✓ Membresía registrada correctamente.');
            }

            resetearFormulario();
        },
    });
};

const resetearFormulario = () => {
    form.reset();
    form.fecha_inicio = fechaHoy;
    filtroBusqueda.value = '';
    clientesFiltrados.value = [...props.clientes];
    montoBase.value = '0.00';
    montoDescontado.value = '0.00';
    montoFinal.value = '0.00';
    fechaVencimientoCalculada.value = '';
    errorImagen.value = '';
    clienteTieneMembresiaActiva.value = false;
    clienteFechaLimite.value = '';
    fechaInicioAdelantada.value = '';
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="m-4 mx-auto max-w-4xl rounded-xl border border-gray-100 bg-white p-6 shadow-md transition-shadow duration-300 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900"
        >
            <h1
                class="mb-6 flex items-center gap-2 border-b border-gray-100 pb-4 text-2xl font-extrabold tracking-tight text-gray-800 dark:border-zinc-800/80 dark:text-zinc-100"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                    />
                </svg>
                <span>Registrar Membresía y Pago (CU-04 / CU-05)</span>
            </h1>

            <form @submit.prevent="enviarFormulario" class="space-y-6 text-gray-900 dark:text-zinc-100">
                <div class="rounded-xl border border-zinc-200/50 bg-zinc-50 p-5 dark:border-zinc-800/60 dark:bg-zinc-800/30">
                    <h2 class="mb-4 text-xs font-black uppercase tracking-widest text-orange-600 dark:text-orange-500">1. Seleccionar Cliente</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400"
                                >Buscar Cliente (CI o Apellido)</label
                            >
                            <input
                                v-model="filtroBusqueda"
                                @input="filtrarClientes"
                                type="text"
                                placeholder="Escriba para buscar..."
                                class="block w-full rounded-lg border border-gray-200 bg-white p-2.5 text-sm text-gray-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-100"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400"
                                >Cliente Seleccionado *</label
                            >
                            <select
                                v-model="form.id_cliente"
                                required
                                class="block w-full rounded-lg border border-gray-200 bg-white p-2.5 text-sm text-gray-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-100"
                            >
                                <option value="" disabled>-- Seleccione un cliente de la lista --</option>
                                <option v-for="cliente in clientesFiltrados" :key="cliente.id_cliente" :value="cliente.id_cliente">
                                    {{ cliente.persona.ci }} - {{ cliente.persona.nombre }} {{ cliente.persona.apellido }}
                                </option>
                            </select>
                            <p v-if="clientesFiltrados.length === 0" class="mt-1.5 text-xs font-medium text-red-500 dark:text-red-400">
                                Si no existe, el CU-04 permite registrarlo primero en el módulo de Clientes.
                            </p>
                        </div>
                    </div>

                    <!-- Alerta y Checkbox de Pago Adelantado si el cliente seleccionado ya cuenta con membresía activa -->
                    <div
                        v-if="clienteTieneMembresiaActiva"
                        class="mt-4 space-y-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/30 dark:bg-amber-950/20"
                    >
                        <p class="flex items-center gap-1.5 text-xs font-semibold text-amber-800 dark:text-amber-300">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 flex-shrink-0 text-amber-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                            <span>
                                Este cliente ya cuenta con una membresía activa.
                                <br />
                                Vence el
                                <strong>{{ clienteFechaLimite }}</strong>
                                <br /><br />
                                ¿Desea registrar una renovación adelantada?
                            </span>
                        </p>
                        <div class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="pago_adelantado"
                                v-model="form.pago_adelantado"
                                class="h-4 w-4 cursor-pointer rounded border-gray-300 bg-white text-orange-500 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-800"
                            />
                            <label for="pago_adelantado" class="cursor-pointer select-none text-xs font-bold text-gray-700 dark:text-zinc-300">
                                Registrar como Membresia Adelantada (La nueva membresía iniciará el
                                <strong class="text-orange-650 font-black dark:text-orange-400">{{ fechaInicioAdelantada }}</strong
                                >)
                            </label>
                        </div>
                    </div>
                    <div
                        v-if="form.pago_adelantado"
                        class="mt-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-gray-800 dark:border-green-900/50 dark:bg-green-950/20 dark:text-green-100"
                    >
                        <p>
                            <strong class="dark:text-white">Fecha de inicio:</strong>
                            <span class="dark:text-green-300">
                                {{ form.fecha_inicio }}
                            </span>
                        </p>

                        <p>
                            <strong class="dark:text-white">Fecha de fin:</strong>
                            <span class="dark:text-green-300">
                                {{ fechaVencimientoCalculada }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200/50 bg-zinc-50 p-5 dark:border-zinc-800/60 dark:bg-zinc-800/30">
                    <h2 class="mb-4 text-xs font-black uppercase tracking-widest text-orange-600 dark:text-orange-500">2. Configuración del Plan</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400"
                                >Tipo de Membresía *</label
                            >
                            <select
                                v-model="form.id_tipo_membresia"
                                @change="actualizarMontoYFechas"
                                required
                                class="block w-full rounded-lg border border-gray-200 bg-white p-2.5 text-sm text-gray-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-100"
                            >
                                <option value="" disabled>-- Seleccione un Plan --</option>
                                <option v-for="tipo in tiposMembresia" :key="tipo.id_tipo_membresia" :value="tipo.id_tipo_membresia">
                                    {{ tipo.nombre_membresia }} ({{ tipo.duracion_dias }} días)
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400"
                                >Meses a Pagar *</label
                            >
                            <select
                                v-model.number="form.cantidad_periodos"
                                @change="actualizarMontoYFechas"
                                required
                                class="block w-full rounded-lg border border-gray-200 bg-white p-2.5 text-sm text-gray-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-100"
                            >
                                <option v-for="n in 12" :key="n" :value="n">x{{ n }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400"
                                >Fecha de Inicio</label
                            >
                            <input
                                v-model="form.fecha_inicio"
                                @change="actualizarMontoYFechas"
                                type="date"
                                required
                                :disabled="clienteTieneMembresiaActiva && form.pago_adelantado"
                                class="block w-full rounded-lg border border-gray-200 bg-white p-2.5 text-sm text-gray-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 disabled:cursor-not-allowed disabled:bg-zinc-100 disabled:text-gray-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-100 dark:disabled:bg-zinc-800/80 dark:disabled:text-zinc-400"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400"
                                >Fecha de Vencimiento</label
                            >
                            <input
                                v-model="fechaVencimientoCalculada"
                                type="date"
                                readonly
                                class="block w-full cursor-not-allowed rounded-lg border border-gray-200 bg-zinc-100 p-2.5 text-sm font-bold text-orange-600 opacity-90 focus:outline-none dark:border-zinc-800 dark:bg-zinc-800/50 dark:text-orange-400"
                            />
                        </div>
                    </div>

                    <!-- Selector de Promociones Vigentes -->
                    <div class="mt-4 border-t border-zinc-200/55 pt-4 dark:border-zinc-800/60">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400"
                                    >Aplicar Promoción Vigente</label
                                >
                                <select
                                    v-model="form.id_promocion"
                                    @change="actualizarMontoYFechas"
                                    class="block w-full rounded-lg border border-gray-200 bg-white p-2.5 text-sm text-gray-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-100"
                                >
                                    <option value="">-- Sin Promoción --</option>
                                    <option v-for="promocion in promociones" :key="promocion.id_promocion" :value="promocion.id_promocion">
                                        {{ promocion.nombre_promocion }} ({{ promocion.porcentaje_descuento }}% de descuento)
                                    </option>
                                </select>
                            </div>
                            <div
                                v-if="promocionSeleccionada"
                                class="flex flex-col justify-center rounded-lg border border-orange-200/50 bg-orange-50/50 p-3.5 text-xs font-medium dark:border-orange-900/20 dark:bg-orange-950/10"
                            >
                                <span class="text-orange-850 block font-black dark:text-orange-300"
                                    >🎉 Promoción Activa: {{ promocionSeleccionada.nombre_promocion }}</span
                                >
                                <span class="mt-1 block text-gray-500 dark:text-zinc-400">{{
                                    promocionSeleccionada.descripcion || 'Sin descripción adicional.'
                                }}</span>
                                <span class="mt-1 block font-extrabold text-emerald-600 dark:text-emerald-400"
                                    >Descuento del {{ promocionSeleccionada.porcentaje_descuento }}% aplicado al total.</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200/50 bg-zinc-50 p-5 dark:border-zinc-800/60 dark:bg-zinc-800/30">
                    <h2 class="mb-4 text-xs font-black uppercase tracking-widest text-orange-600 dark:text-orange-500">
                        3. Procesar Transacción Económica
                    </h2>
                    <div class="mb-4 grid grid-cols-1 items-start gap-4 md:grid-cols-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400"
                                >Monto Total Base (Bs.)</label
                            >
                            <input
                                v-model="montoBase"
                                type="text"
                                readonly
                                class="block w-full cursor-not-allowed rounded-lg border border-gray-200 bg-zinc-100 p-2.5 text-sm font-extrabold text-gray-500 opacity-90 focus:outline-none dark:border-zinc-800 dark:bg-zinc-800/50 dark:text-zinc-400"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400"
                                >Monto Descontado (Bs.)</label
                            >
                            <input
                                v-model="montoDescontado"
                                type="text"
                                readonly
                                class="block w-full cursor-not-allowed rounded-lg border border-gray-200 p-2.5 text-sm font-extrabold opacity-90 focus:outline-none dark:border-zinc-800"
                                :class="
                                    form.id_promocion && parseFloat(montoDescontado) > 0
                                        ? 'dark:text-rose-450 border-rose-300 bg-rose-50 text-rose-600 dark:border-rose-900/50 dark:bg-rose-950/20'
                                        : 'border-gray-200 bg-zinc-100 text-gray-400 dark:border-zinc-800 dark:bg-zinc-800/50 dark:text-zinc-500'
                                "
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400"
                                >Monto Final a Cobrar (Bs.)</label
                            >
                            <input
                                v-model="montoFinal"
                                type="text"
                                readonly
                                class="block w-full cursor-not-allowed rounded-lg border border-gray-200 p-2.5 text-sm font-black opacity-90 focus:outline-none dark:border-zinc-800"
                                :class="
                                    form.id_promocion && parseFloat(montoDescontado) > 0
                                        ? 'dark:text-emerald-450 border-emerald-300 bg-emerald-50 text-emerald-600 dark:border-emerald-900/50 dark:bg-emerald-950/20'
                                        : 'border-gray-200 bg-zinc-100 text-gray-900 dark:border-zinc-800 dark:bg-zinc-800/50 dark:text-zinc-100'
                                "
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400"
                                >Forma de Pago *</label
                            >
                            <select
                                v-model="form.metodo_pago"
                                required
                                class="block w-full rounded-lg border border-gray-200 bg-white p-2.5 text-sm text-gray-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-100"
                            >
                                <option value="Efectivo">Efectivo</option>
                                <option value="Transferencia">Transferencia / Código QR</option>
                            </select>
                        </div>
                    </div>

                    <!-- Campos adicionales para transferencia -->
                    <div v-if="form.metodo_pago === 'Transferencia'" class="mt-4 space-y-4 border-t border-zinc-200/55 pt-4 dark:border-zinc-800/60">
                        <h3 class="text-xs font-black uppercase tracking-widest text-orange-600 dark:text-orange-500">
                            Detalle de la Transferencia Bancaria
                        </h3>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400"
                                    >Banco Origen *</label
                                >
                                <input
                                    v-model="form.banco_origen"
                                    type="text"
                                    placeholder="Ej: Banco Mercantil"
                                    required
                                    class="block w-full rounded-lg border border-gray-200 bg-white p-2.5 text-sm text-gray-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-100"
                                />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400"
                                    >Banco Destino *</label
                                >
                                <input
                                    v-model="form.banco_destino"
                                    type="text"
                                    placeholder="Ej: Banco Unión (Gym)"
                                    required
                                    class="block w-full rounded-lg border border-gray-200 bg-white p-2.5 text-sm text-gray-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-100"
                                />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400"
                                    >Cuenta Destino *</label
                                >
                                <input
                                    v-model="form.cuenta_destino"
                                    type="text"
                                    placeholder="Ej: 10000045612"
                                    required
                                    class="block w-full rounded-lg border border-gray-200 bg-white p-2.5 text-sm text-gray-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-100"
                                />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400"
                                    >Código de Transacción *</label
                                >
                                <input
                                    v-model="form.codigo_transaccion"
                                    type="text"
                                    placeholder="Ej: TR-9823412"
                                    required
                                    class="block w-full rounded-lg border border-gray-200 bg-white p-2.5 text-sm text-gray-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-100"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400"
                                >Adjuntar Comprobante QR *</label
                            >
                            <input
                                type="file"
                                @change="procesarArchivoComprobante"
                                accept="image/png, image/jpeg"
                                required
                                class="block w-full cursor-pointer text-xs text-gray-500 transition-colors file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-orange-50 file:px-4 file:py-2 file:text-xs file:font-bold file:text-orange-700 hover:file:bg-orange-100 dark:text-zinc-400 dark:file:bg-orange-950/20 dark:file:text-orange-400 dark:hover:file:bg-orange-950/40"
                            />
                            <p class="mt-1 text-[10px] text-gray-400 dark:text-zinc-500">
                                Formatos admitidos: JPG, PNG de forma obligatoria. Máximo 2MB.
                            </p>
                            <span v-if="errorImagen" class="mt-1 block text-xs font-semibold text-red-500 dark:text-red-400">{{ errorImagen }}</span>
                        </div>
                    </div>
                </div>

                <!-- Lista de errores de validación de Inertia -->
                <div
                    v-if="Object.keys(form.errors).length > 0"
                    class="text-red-650 rounded-lg border border-red-200/50 bg-red-50 p-4 text-sm font-semibold dark:border-red-900/30 dark:bg-red-950/20 dark:text-red-400"
                >
                    <ul class="list-disc space-y-1 pl-4">
                        <li v-for="(error, index) in form.errors" :key="index">{{ error }}</li>
                    </ul>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-100 pt-4 dark:border-zinc-800/80">
                    <button
                        type="button"
                        @click="resetearFormulario"
                        class="text-gray-650 rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-semibold transition-all duration-200 hover:bg-gray-100 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    >
                        Limpiar Campos
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing || !!errorImagen || (clienteTieneMembresiaActiva && !form.pago_adelantado)"
                        class="rounded-lg bg-orange-600 bg-gradient-to-r from-orange-500 to-red-600 px-6 py-2.5 text-sm font-bold text-white shadow-md transition-all duration-200 hover:from-orange-600 hover:to-red-700 hover:shadow-lg disabled:opacity-50"
                    >
                        {{ form.processing ? 'Registrando...' : 'Confirmar Inscripción y Pago' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

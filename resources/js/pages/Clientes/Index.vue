<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

// Propiedades heredadas desde el ClienteController de Laravel
const props = defineProps({
  clientes: Array
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Clientes', href: '/clientes' },
];

// Variables de control de estado del Modal y Búsqueda
const modalAbierto = ref(false);
const editando = ref(false);
const idClienteSeleccionado = ref(null);
const buscarCriterio = ref('');

// Inicialización del formulario reactivo mediante Inertia
const form = useForm({
  ci: '',
  nombre: '',
  apellido: '',
  telefono: '',
  direccion: '',
  sexo: 'M'
});

// Lógica para ejecutar la consulta de búsqueda reactiva en Base de Datos (Cumple CU-03)
const ejecutarBusqueda = () => {
  router.get('/clientes', { buscar: buscarCriterio.value }, {
    preserveState: true,
    replace: true
  });
};

// Control de apertura del Modal
const abrirModalFormulario = (cliente = null) => {
  if (cliente) {
    editando.value = true;
    idClienteSeleccionado.value = cliente.id_cliente;
    form.ci = cliente.persona.ci;
    form.nombre = cliente.persona.nombre;
    form.apellido = cliente.persona.apellido;
    form.telefono = cliente.persona.telefono;
    form.direccion = cliente.persona.direccion;
    form.sexo = cliente.persona.sexo;
  } else {
    editando.value = false;
    form.reset();
  }
  modalAbierto.value = true;
};

const cerrarModal = () => {
  modalAbierto.value = false;
  form.reset();
};

// Procesar el envío de información del formulario
const guardarFormulario = () => {
  if (editando.value) {
    form.put(`/clientes/${idClienteSeleccionado.value}`, {
      onSuccess: () => cerrarModal()
    });
  } else {
    form.post('/clientes', {
      onSuccess: () => cerrarModal()
    });
  }
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 bg-white dark:bg-zinc-900 rounded-xl shadow-md border border-gray-100 dark:border-zinc-800 m-4 hover:shadow-lg transition-shadow duration-300">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-zinc-800/80">
        <h1 class="text-2xl font-extrabold tracking-tight text-gray-800 dark:text-zinc-100 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <span>Gestionar Clientes (CU-02)</span>
        </h1>
        <button 
          @click="abrirModalFormulario()" 
          class="bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white text-sm font-bold py-2.5 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200"
        >
          + Registrar Nuevo Cliente
        </button>
      </div>

      <div class="mb-6">
        <div class="relative max-w-md">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </span>
          <input
            v-model="buscarCriterio"
            @input="ejecutarBusqueda"
            type="text"
            placeholder="Buscar cliente por CI o Apellido..."
            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-zinc-800 rounded-lg text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none placeholder-gray-400 dark:placeholder-zinc-500 transition-all duration-200"
          />
        </div>
      </div>

      <div class="overflow-x-auto bg-white dark:bg-zinc-900 rounded-lg border border-gray-100 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-left">
          <thead class="bg-gray-50 dark:bg-zinc-800 text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
            <tr>
              <th class="px-6 py-3.5">CI</th>
              <th class="px-6 py-3.5">Nombre Completo</th>
              <th class="px-6 py-3.5">Teléfono</th>
              <th class="px-6 py-3.5 text-center">Sexo</th>
              <th class="px-6 py-3.5 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-zinc-800 text-sm text-gray-650 dark:text-zinc-300">
            <tr v-for="cliente in clientes" :key="cliente.id_cliente" class="hover:bg-gray-50 dark:hover:bg-zinc-800/30 transition-colors">
              <td class="px-6 py-4 font-bold text-gray-900 dark:text-zinc-100">{{ cliente.persona.ci }}</td>
              <td class="px-6 py-4 font-medium text-gray-800 dark:text-zinc-200">{{ cliente.persona.nombre }} {{ cliente.persona.apellido }}</td>
              <td class="px-6 py-4 text-gray-500 dark:text-zinc-400">{{ cliente.persona.telefono || 'Sin número' }}</td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold" :class="cliente.persona.sexo === 'M' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-300'">
                  {{ cliente.persona.sexo === 'M' ? 'Masculino' : 'Femenino' }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <button 
                  @click="abrirModalFormulario(cliente)" 
                  class="text-orange-500 hover:text-orange-600 dark:text-orange-400 dark:hover:text-orange-355 font-bold text-xs uppercase tracking-wider transition-colors mr-3"
                >
                  Editar
                </button>
              </td>
            </tr>
            <tr v-if="!clientes || clientes.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-gray-400 dark:text-zinc-500 italic">No se encontraron clientes registrados.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Modal -->
      <div v-if="modalAbierto" class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center z-50">
        <div class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-xl shadow-xl w-full max-w-lg p-6 transform transition-all duration-300">
          <h2 class="text-xl font-black text-gray-800 dark:text-zinc-100 mb-4 pb-2 border-b border-gray-100 dark:border-zinc-800/80">
            {{ editando ? 'Modificar Datos del Cliente' : 'Registrar Nuevo Cliente' }}
          </h2>
          
          <!-- Lista de errores de validación de Inertia -->
          <div v-if="Object.keys(form.errors).length > 0" class="text-sm text-red-650 dark:text-red-400 font-semibold bg-red-50 dark:bg-red-950/20 border border-red-200/50 dark:border-red-900/30 p-4 rounded-lg mb-4">
            <ul class="list-disc pl-4 space-y-1">
              <li v-for="(error, index) in form.errors" :key="index">{{ error }}</li>
            </ul>
          </div>
          
          <form @submit.prevent="guardarFormulario" class="space-y-4">
            <div class="grid grid-cols-1 gap-4">
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Cédula de Identidad (CI) *</label>
                <input 
                  v-model="form.ci" 
                  type="text" 
                  required 
                  :disabled="editando"
                  class="mt-1.5 block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200 disabled:bg-gray-100 dark:disabled:bg-zinc-800/50 disabled:opacity-75"
                />
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Nombre *</label>
                  <input v-model="form.nombre" type="text" required class="mt-1.5 block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"/>
                </div>
                <div>
                  <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Apellido *</label>
                  <input v-model="form.apellido" type="text" required class="mt-1.5 block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"/>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Teléfono</label>
                  <input v-model="form.telefono" type="text" class="mt-1.5 block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"/>
                </div>
                <div>
                  <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Sexo *</label>
                  <select v-model="form.sexo" required class="mt-1.5 block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200">
                    <option value="M">Masculino</option>
                    <option value="F">Femenino</option>
                  </select>
                </div>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Dirección</label>
                <input v-model="form.direccion" type="text" class="mt-1.5 block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"/>
              </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-zinc-800/80">
              <button 
                type="button" 
                @click="cerrarModal" 
                class="px-5 py-2.5 border border-gray-200 dark:border-zinc-800 rounded-lg text-gray-650 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm font-semibold transition-all duration-200"
              >
                Cancelar
              </button>
              <button 
                type="submit" 
                :disabled="form.processing"
                class="px-5 py-2.5 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-bold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 text-sm disabled:opacity-50"
              >
                {{ editando ? 'Guardar Cambios' : 'Registrar Cliente' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
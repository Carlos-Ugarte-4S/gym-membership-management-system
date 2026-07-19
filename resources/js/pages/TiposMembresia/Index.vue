<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

// Recibe la lista desde TipoMembresiaController
const props = defineProps({
  tiposMembresia: Array
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Planes Membresía', href: '/admin/tipos-membresia' },
];

// Controladores de estado
const modalAbierto = ref(false);
const editando = ref(false);
const idPlanSeleccionado = ref(null);

// Inicialización del formulario reactivo de Inertia
const form = useForm({
  nombre_membresia: '',
  duracion_dias: '',
  precio: ''
});

const abrirModalFormulario = (tipo = null) => {
  if (tipo) {
    editando.value = true;
    idPlanSeleccionado.value = tipo.id_tipo_membresia;
    form.nombre_membresia = tipo.nombre_membresia;
    form.duracion_dias = tipo.duracion_dias;
    form.precio = tipo.precio;
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

const guardarFormulario = () => {
  if (editando.value) {
    form.put(`/admin/tipos-membresia/${idPlanSeleccionado.value}`, {
      onSuccess: () => cerrarModal()
    });
  } else {
    form.post('/admin/tipos-membresia', {
      onSuccess: () => cerrarModal()
    });
  }
};

// Envía la petición PATCH para alternar el estado del plan (Flujo Alternativo A2)
const cambiarEstadoPlan = (id) => {
  if (confirm('¿Está seguro de cambiar el estado de este plan de membresía?')) {
    router.patch(`/admin/tipos-membresia/${id}/estado`, {}, {
      preserveScroll: true,
      onError: (errors) => {
        // Muestra alerta si el backend frena la desactivación por tener clientes activos
        if (errors.error) alert(errors.error);
      }
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
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          <span>Planes y Tipos de Membresía (CU-08)</span>
        </h1>
        <button 
          @click="abrirModalFormulario()" 
          class="bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white text-sm font-bold py-2.5 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200"
        >
          + Configurar Nuevo Plan
        </button>
      </div>

      <div class="overflow-x-auto bg-white dark:bg-zinc-900 rounded-lg border border-gray-100 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-left">
          <thead class="bg-gray-50 dark:bg-zinc-800 text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
            <tr>
              <th class="px-6 py-3.5">Nombre del Plan</th>
              <th class="px-6 py-3.5">Duración (Días)</th>
              <th class="px-6 py-3.5">Precio (Bs.)</th>
              <th class="px-6 py-3.5 text-center">Estado (Ajuste del Ing.)</th>
              <th class="px-6 py-3.5 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-zinc-800 text-sm text-gray-650 dark:text-zinc-300">
            <tr v-for="tipo in tiposMembresia" :key="tipo.id_tipo_membresia" class="hover:bg-gray-50 dark:hover:bg-zinc-800/30 transition-colors">
              <td class="px-6 py-4 font-bold text-gray-900 dark:text-zinc-100">{{ tipo.nombre_membresia }}</td>
              <td class="px-6 py-4 text-gray-500 dark:text-zinc-400 font-medium">{{ tipo.duracion_dias }} días</td>
              <td class="px-6 py-4 font-black text-gray-800 dark:text-zinc-100">Bs. {{ parseFloat(tipo.precio).toFixed(2) }}</td>
              <td class="px-6 py-4 text-center">
                <span 
                  :class="tipo.estado ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400'"
                  class="px-2.5 py-0.5 rounded-full text-xs font-bold"
                >
                  {{ tipo.estado ? 'Habilitado' : 'Deshabilitado' }}
                </span>
              </td>
              <td class="px-6 py-4 text-center space-x-3">
                <button 
                  @click="abrirModalFormulario(tipo)" 
                  class="text-orange-500 hover:text-orange-600 dark:text-orange-400 dark:hover:text-orange-355 font-bold text-xs uppercase tracking-wider transition-colors"
                >
                  Editar
                </button>
                
                <button 
                  @click="cambiarEstadoPlan(tipo.id_tipo_membresia)" 
                  :class="tipo.estado ? 'text-red-500 hover:text-red-655 dark:text-red-405 dark:hover:text-red-305' : 'text-emerald-500 hover:text-emerald-605 dark:text-emerald-405 dark:hover:text-emerald-305'"
                  class="font-bold text-xs uppercase tracking-wider transition-colors"
                >
                  {{ tipo.estado ? 'Desactivar' : 'Activar' }}
                </button>
              </td>
            </tr>
            <tr v-if="!tiposMembresia || tiposMembresia.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-gray-400 dark:text-zinc-500 italic">No hay planes configurados en el sistema.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Modal -->
      <div v-if="modalAbierto" class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center z-50">
        <div class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-xl shadow-xl w-full max-w-md p-6 transform transition-all duration-300">
          <h2 class="text-xl font-black text-gray-800 dark:text-zinc-100 mb-4 pb-2 border-b border-gray-100 dark:border-zinc-800/80">
            {{ editando ? 'Modificar Plan de Membresía' : 'Configurar Nuevo Plan de Membresía' }}
          </h2>
          
          <form @submit.prevent="guardarFormulario" class="space-y-4">
            <div class="space-y-4">
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Nombre de la Membresía *</label>
                <input 
                  v-model="form.nombre_membresia" 
                  type="text" 
                  required 
                  placeholder="Ej. Plan Mensual VIP"
                  class="mt-1.5 block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
                />
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Duración (Días) *</label>
                  <input 
                    v-model="form.duracion_dias" 
                    type="number" 
                    min="1" 
                    required 
                    placeholder="30"
                    class="mt-1.5 block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
                  />
                </div>
                <div>
                  <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Precio (Bs.) *</label>
                  <input 
                    v-model="form.precio" 
                    type="number" 
                    step="0.01" 
                    min="0" 
                    required 
                    placeholder="250.00"
                    class="mt-1.5 block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
                  />
                </div>
              </div>
            </div>

            <div v-if="form.errors.nombre_membresia" class="mt-4 text-sm text-red-655 dark:text-red-400 font-semibold bg-red-50 dark:bg-red-950/20 border border-red-200/50 dark:border-red-900/30 p-3 rounded-lg">
              {{ form.errors.nombre_membresia }}
            </div>

            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-zinc-800/80">
              <button 
                type="button" 
                @click="cerrarModal" 
                class="px-5 py-2.5 border border-gray-200 dark:border-zinc-800 rounded-lg text-gray-655 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm font-semibold transition-all duration-200"
              >
                Cancelar
              </button>
              <button 
                type="submit" 
                :disabled="form.processing"
                class="px-5 py-2.5 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-bold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 text-sm disabled:opacity-50"
              >
                {{ editando ? 'Guardar Cambios' : 'Registrar Plan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>